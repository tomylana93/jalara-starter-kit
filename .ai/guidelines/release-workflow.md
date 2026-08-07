# Release Workflow

This file is the source of truth for commit messages, pull-request
descriptions, and release automation. Human authors, editor assistants, and
agents all follow it.

## Branching

- Work happens on `dev`. `main` receives `dev` through a pull request that is
  merged with a merge commit.
- Draft pull requests run the fast CI gate. Ready pull requests and pushes to
  `main` run the full gate. Pushes to a working branch run nothing on their own:
  a branch push and its own pull request's `synchronize` event would otherwise
  produce two runs in one concurrency group, and the cancelled one reads as a
  failed check. Open the pull request as a draft to get CI on a branch.
- Every merge commit into `main` carries an empty body. GitHub copies the
  pull-request title into it by default, and Release Please parses that copy as a
  conventional commit in its own right, so the changelog lists the same change
  twice: once for the real commit and once for the merge that brought it. The
  repository setting cannot express this — GitHub accepts only
  `PR_TITLE`+`PR_BODY`, `PR_TITLE`+`BLANK`, and `MERGE_MESSAGE`+`PR_TITLE`, and
  the first two move the title into the subject instead of removing it — so the
  merge itself clears the body:

  ```shell
  gh pr merge <number> --merge \
      --subject "Merge pull request #<number> from <branch>" \
      --body ""
  ```

  Merging from the web interface prefills the title in the body; clear it before
  confirming. This applies to the release pull request too.

## Conventional Commits

- Every non-merge commit and every pull-request title uses an English
  Conventional Commit with an optional scope:
  `<type>(<optional scope>): <description>`. English is mandatory authoring guidance;
  CI programmatically validates Conventional Commit structure and allowed types,
  not natural-language detection.
- Allowed types: `feat`, `fix`, `perf`, `refactor`, `docs`, `test`, `build`,
  `ci`, `chore`, `revert`.
- A breaking change is marked with `!` after the type or scope, or with a
  `BREAKING CHANGE:` footer.
- Merge commits are exempt; they are how `dev` reaches `main`.
- Dependabot commits use the `chore(deps):` convention.
- The `conventional commits` CI job programmatically validates the Conventional
  Commit structure, allowed types, and scopes for the pull-request title and
  every non-merge commit the pull request introduces.

## Pull-request descriptions

Every pull-request description contains exactly these three sections:

```markdown
## Summary

What changed and why, in a few sentences.

## Testing

The commands that were run and their result.

## Release impact

The version bump the commits imply (major, minor, patch, or none) and anything
an operator has to do after the release.
```

## Release Please

- Release Please targets `main` and runs only after the full `tests` gate has
  succeeded there, so no release is produced from unverified code.
- Versioning starts at `0.1.0` from bootstrap SHA
  `de8e57123d469ec15c8fd3a89f48a3da7fc0e23f` and follows default SemVer.
- It maintains `CHANGELOG.md`, the `.release-please-manifest.json` version
  manifest, the Git tag, the GitHub Release, and the runtime version in
  `version.json`, which `config('app.version')` reads and the footer renders.
- Automation is opt-in. It stays off until the repository sets the
  `RELEASE_ENABLED` variable to `true` and provides a fine-grained
  `RELEASE_PLEASE_TOKEN` secret with Contents, Pull requests, and Issues
  read/write access.
- Releasing means merging the release pull request Release Please opens.
