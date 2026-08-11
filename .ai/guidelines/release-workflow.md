# Release Workflow

This is the authoring policy for commits, pull requests, and releases. Read `mem:release_process` only when diagnosing release automation internals.

## Branching and CI

- Work happens on `dev`; merge it into `main` through a merge-commit pull request.
- Open a working-branch pull request as draft to run the fast gate. Ready pull requests and pushes to `main` run the full gate; branch pushes alone run no CI.
- Merge commits into `main`, including release pull requests, require an empty body to prevent duplicate Release Please changelog entries:

  ```shell
  gh pr merge <number> --merge \
      --subject "Merge pull request #<number> from <branch>" \
      --body ""
  ```

## Commits and pull requests

- Every non-merge commit and pull-request title is an English Conventional Commit: `<type>(<optional scope>): <description>`.
- Allowed types are `feat`, `fix`, `perf`, `refactor`, `docs`, `test`, `build`, `ci`, `chore`, `revert`. Mark breaking changes with `!` or a `BREAKING CHANGE:` footer. Dependabot uses `chore(deps):`.
- Every pull-request description contains exactly `## Summary`, `## Testing`, and `## Release impact`; the last names the implied bump and operator action.

## Releases

- Release Please targets `main` only after its full test gate succeeds.
- Automation remains off until `RELEASE_ENABLED=true` and a fine-grained `RELEASE_PLEASE_TOKEN` has Contents, Pull requests, and Issues read/write.
- Release by merging the release pull request that Release Please opens.
