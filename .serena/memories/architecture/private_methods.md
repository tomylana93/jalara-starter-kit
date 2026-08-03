# Private Method Refactoring & Placement

## Retention vs. Extraction Criteria

- Retain a private method when it is small, class-local, dependency-free, supports the owner’s primary responsibility, contains no distinct business rule, and extraction adds no meaningful testability or reuse.
- Extract a private method when it owns a named business concept, separate dependency, complex query, external integration, domain calculation, transaction, or reusable transformation, or gives the class another reason to change.
- Inline a private method when it only renames a trivial expression or delegates to an existing canonical owner.
- Remove dead or superseded methods.

## Architectural Placement

- **Validation**: Place in Form Requests or custom `ValidationRule` classes.
- **Authorization**: Place in policies or Form Request authorization.
- **Use Cases & Transactions**: Place in Actions (`App\Actions\`).
- **Entity Behavior**: Place directly on models or value objects.
- **Complex Queries**: Place in query objects or justified Eloquent scopes.
- **Formatting & Presentation**: Place in presenters or Eloquent API resources.
- **Integrations & Files**: Place in focused clients or service classes.
- **Queue Adapters**: Keep Jobs as thin adapters containing only queue identity, serialization, retries/timeout/backoff, Action invocation, and terminal failure handling.

## Prohibited Abstractions

- Never extract logic into generic `Helper`, `Utility`, `Manager`, `CommonService`, or miscellaneous service classes.
- Do not create an interface, DTO, repository, or service solely to eliminate a private method.
- Preserve public APIs, authorization order, transaction boundaries, exception behavior, and query/N+1 behavior during extraction.
