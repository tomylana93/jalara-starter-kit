# Commit Message Convention

Use Conventional Commits compatible with Release Please.

Format:

`<type>(<scope>): <description>`

Allowed types:

- `feat`: new functionality
- `fix`: bug fix
- `refactor`: restructuring without behavior changes
- `perf`: performance improvement
- `docs`: documentation only
- `test`: tests only
- `style`: formatting only
- `build`: dependencies or build system
- `ci`: CI/CD changes
- `chore`: maintenance
- `revert`: revert a previous change

Rules:

- Infer the commit message from the staged changes.
- Use a short, lowercase, domain-oriented scope when appropriate.
- Write the description in imperative form.
- Keep the subject concise.
- Describe the intent of the change, not filenames.
- Do not use vague messages such as `update files`, `fix code`, or `changes`.
- Do not include a period at the end of the subject.
- Use `!` for breaking changes:
  `<type>(<scope>)!: <description>`
- Add a body only when additional context is necessary.
- If multiple unrelated changes are staged, describe the dominant change instead of inventing a generic message.

Examples:

`feat(auth): add passkey authentication`

`fix(shipment): prevent duplicate POD upload`

`refactor(admin): standardize table structure`

`build(frontend): migrate package manager to pnpm`

`ci(release): configure release please`

`feat(api)!: change shipment response format`