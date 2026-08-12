#!/usr/bin/env bash
#
# Prints the reviews of one pull request with each reviewer's resolved
# repository permission attached.
#
# The review payload names a reviewer's relationship to the repository, not what
# it lets them do: `MEMBER` is any account in the owning organisation and
# `COLLABORATOR` includes a read-only invitation. Whether an approval counts is
# a question about write access, so the permission is resolved per reviewer.
#
# A permission the token cannot read is recorded as `unknown` rather than
# guessed. The policy treats that as unconfirmed, which is the safe reading, and
# says so distinctly.
#
# Usage: pull-request-reviews.sh <repository> <pull-request-number>

set -euo pipefail

repository="${1:?the owner/name of the repository is required}"
number="${2:?the pull request number is required}"

reviews="$(gh api "repos/${repository}/pulls/${number}/reviews" --paginate --jq '[.[] | {
    state,
    commit_id,
    login: (.user.login // "")
}]')"

permissions='{}'

for login in $(jq -r '[.[].login] | unique | .[] | select(. != "")' <<<"${reviews}"); do
    # The endpoint answers `none` for an account that is not a collaborator, so
    # a failure here means the permission could not be read at all.
    if response="$(gh api "repos/${repository}/collaborators/${login}/permission" 2>/dev/null)"; then
        permission="$(jq -r '.permission // "none"' <<<"${response}")"
    else
        permission='unknown'
    fi

    permissions="$(jq --arg login "${login}" --arg permission "${permission}" \
        '. + {($login): $permission}' <<<"${permissions}")"
done

jq --argjson permissions "${permissions}" \
    '[.[] | . + {permission: ($permissions[.login] // "unknown")}]' <<<"${reviews}"
