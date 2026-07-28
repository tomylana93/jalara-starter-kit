# Testing

- Organize tests by observable domain under `tests/Feature/{Domain}`; do not mirror implementation-layer folders such as `app/Actions` when the behavior is already covered through the domain's HTTP boundary.
- Keep one canonical feature test per user-visible behavior. Action/controller extraction is an implementation refactor and does not justify duplicate tests when existing request tests already cover persistence, validation, redirects, authentication, and session effects.
- Put database-free object contracts in `tests/Unit`; bind `Tests\TestCase` locally only when a framework service container is required, and do not add database refresh to unit tests.
- Prefer stable public outcomes over collaborator wiring, exact internal call sequences, or class-shape assertions. Add direct action tests only when an action exposes independently meaningful branching that cannot be exercised clearly through its owning domain boundary.
- Treat migration/rename assertions for removed paths, symbols, or implementation structures as temporary safeguards. Remove them after the transition is established unless backward incompatibility is a documented product contract.
- Name files after the owning domain or subject and keep related happy-path, validation, authorization, and side-effect cases together. Replace scaffold examples with tests for real application contracts.
- Browser test isolation, grouping, and fast iteration commands: `mem:testing/browser`.