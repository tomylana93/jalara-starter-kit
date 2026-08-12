#!/usr/bin/env bash
#
# Prints `true` when the given commit is the one that merged a Release Please
# release pull request, and `false` otherwise.
#
# This is what separates the two halves of releasing. The creator opens and
# refreshes the release pull request and must stay out of the way once it is
# merged; the publisher tags that merge and must never run for anything else.
#
# Usage: release-commit.sh <repository> <sha>

set -euo pipefail

repository="${1:?the owner/name of the repository is required}"
sha="${2:?the commit sha is required}"

head_ref="$(gh api "repos/${repository}/commits/${sha}/pulls" \
    --jq "[.[] | select(.merge_commit_sha == \"${sha}\") | .head.ref] | first // \"\"")"

case "${head_ref}" in
    release-please--branches--*) echo 'true' ;;
    *) echo 'false' ;;
esac
