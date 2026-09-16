@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
@scoped(['tests/**'])
# Pest

- This project uses Pest. Create tests with `{{ $assist->artisanCommand('make:test --pest {name}') }}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change with `composer test:focused -- <path-or-filter>`.
- Rerun a test after each change to it.
- Use Composer scripts as the test runner entry point; do not invoke Pest from `vendor/bin` directly.
- Before reporting the task complete, run `composer ci:check` and report any failure.
@endscoped
