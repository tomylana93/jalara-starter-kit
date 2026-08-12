#!/usr/bin/env bash
#
# Collects the provenance of every commit in a range so that
# `php artisan ci:release-eligibility` can judge it.
#
# This script only gathers; it decides nothing. Keeping the judgement in a
# tested Artisan command is what makes the release boundary something the test
# suite covers rather than something only a live run can exercise.
#
# Usage: provenance-report.sh <repository> <ledger> <head> <output>

set -euo pipefail

repository="${1:?the owner/name of the repository is required}"
ledger="${2:?the remediation ledger path is required}"
head="${3:?the head commit is required}"
output="${4:?the output path is required}"

# The ledger records the oldest commit that is ever inspected — the cutover to
# this workflow. Everything up to the newest release has already been judged and
# published, so the effective baseline moves forward to that tag whenever the tag
# descends from the recorded one.
baseline="$(jq -r '.baseline // ""' "${ledger}")"

if [ -z "${baseline}" ]; then
    echo "::error::${ledger} declares no baseline commit."
    exit 1
fi

latest_tag="$(git tag --list 'v*' --sort=-v:refname | head -n 1)"

if [ -n "${latest_tag}" ]; then
    tag_sha="$(git rev-parse "${latest_tag}^{commit}")"

    if git merge-base --is-ancestor "${baseline}" "${tag_sha}"; then
        baseline="${tag_sha}"
    fi
fi

if ! git merge-base --is-ancestor "${baseline}" "${head}"; then
    echo "::error::${baseline} is not an ancestor of ${head}; the branch history was rewritten."
    exit 1
fi

commits='[]'

for sha in $(git rev-list --reverse "${baseline}..${head}"); do
    subject="$(git log -1 --format=%s "${sha}")"
    parents="$(git log -1 --format=%P "${sha}" | tr ' ' '\n' | sed '/^$/d' | jq -R . | jq -s .)"

    pull_requests='[]'

    for number in $(gh api "repos/${repository}/commits/${sha}/pulls" --jq '.[].number'); do
        pull="$(gh api "repos/${repository}/pulls/${number}" --jq '{
            number,
            title,
            merged_at,
            merge_commit_sha,
            head: {sha: .head.sha, repo: {full_name: (.head.repo.full_name // "")}},
            base: {repo: {full_name: (.base.repo.full_name // "")}}
        }')"

        reviews="$("$(dirname "$0")/pull-request-reviews.sh" "${repository}" "${number}")"

        pull_requests="$(jq \
            --argjson pull "${pull}" \
            --argjson reviews "${reviews}" \
            '. + [$pull + {reviews: $reviews}]' <<<"${pull_requests}")"
    done

    commits="$(jq \
        --arg sha "${sha}" \
        --arg subject "${subject}" \
        --argjson parents "${parents}" \
        --argjson pull_requests "${pull_requests}" \
        '. + [{sha: $sha, subject: $subject, parents: $parents, pull_requests: $pull_requests}]' \
        <<<"${commits}")"
done

# Every remediation ledger entry is answered with evidence gathered from git,
# never from the label the entry carries. A `revert` claim is proven the only
# way a machine can prove one: the remediating commit's patch must be the exact
# inverse of the offending commit's patch, which `git patch-id` decides over
# both regardless of context lines, whitespace of the diff header, or the commit
# message the author chose.
#
# `status` says whether the entry is still an open exception. An offending
# commit inside the range is `open` and has to prove itself. One already behind
# the effective baseline is `closed`: an earlier run judged it and published the
# result, so it is not re-litigated and a leftover entry cannot fail a run for
# naming a commit this range no longer contains.
range="$(git rev-list --reverse "${baseline}..${head}")"

# The stable patch-id of the diff between two revisions, or the empty string
# when either is unreadable here (a rewritten history, an entry naming a commit
# that never existed). An unreadable side yields nothing and can therefore never
# compare equal, so it cannot prove anything by accident.
#
# The two revisions are passed in the order that makes the patch read forwards.
# `git diff -R` is deliberately not used: it swaps the `a/` and `b/` prefixes in
# the header, which `git patch-id` hashes, so an exact revert would not compare
# equal to the inverse of what it reverted.
patch_id() {
    git diff "${1}" "${2}" 2>/dev/null \
        | git patch-id --stable 2>/dev/null \
        | cut -d' ' -f1
}

position() {
    local index=0

    # An empty range reads as one empty line, and an entry naming no commit
    # would otherwise match it and look like the first commit in the range.
    if [ -z "${1}" ]; then
        echo '-1'

        return 0
    fi

    while IFS= read -r candidate; do
        if [ "${candidate}" = "${1}" ]; then
            echo "${index}"

            return 0
        fi

        index=$((index + 1))
    done <<<"${range}"

    echo '-1'
}

remediations='[]'

while IFS=$'\t' read -r entry_sha entry_by; do
    [ -n "${entry_sha}" ] || continue

    offending="$(position "${entry_sha}")"
    remediating="$(position "${entry_by}")"

    if [ "${offending}" -ge 0 ]; then
        status='open'
    elif git merge-base --is-ancestor "${entry_sha}" "${baseline}" 2>/dev/null; then
        status='closed'
    else
        status='unknown'
    fi

    order='null'
    reverts='null'

    if [ "${status}" = 'open' ]; then
        if [ "${remediating}" -gt "${offending}" ]; then
            order='true'
        else
            order='false'
        fi

        # What the offending commit did, against what the remediating commit
        # undid. Equal ids mean the remediating commit applies exactly that
        # inverse and nothing else; an unrelated commit, or one that undoes only
        # part of it, differs.
        offending_patch="$(patch_id "${entry_sha}^" "${entry_sha}")"
        remediating_inverse="$(patch_id "${entry_by}" "${entry_by}^")"

        if [ -n "${offending_patch}" ] && [ "${offending_patch}" = "${remediating_inverse}" ]; then
            reverts='true'
        else
            reverts='false'
        fi
    fi

    remediations="$(jq \
        --arg sha "${entry_sha}" \
        --arg by "${entry_by}" \
        --arg status "${status}" \
        --argjson order "${order}" \
        --argjson reverts "${reverts}" \
        '. + [{sha: $sha, by: $by, status: $status, order: $order, reverts: $reverts}]' \
        <<<"${remediations}")"
done < <(jq -r '(.remediated // [])[] | [(.sha // ""), (.remediation.by // "")] | @tsv' "${ledger}")

jq -n \
    --arg baseline "${baseline}" \
    --arg head "${head}" \
    --argjson commits "${commits}" \
    --argjson remediations "${remediations}" \
    '{baseline: $baseline, head: $head, commits: $commits, remediations: $remediations}' >"${output}"

echo "Collected provenance for $(jq 'length' <<<"${commits}") commit(s) in ${baseline}..${head}."
