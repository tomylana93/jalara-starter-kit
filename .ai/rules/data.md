---
paths:
  - 'app/Data/**'
---

# Data

## Data objects sit only at the action boundary
`app/Data/{Domain}` holds `final readonly` objects that cross the action boundary: `*Data` goes in, `*Result` comes out. The suffix encodes direction so one use case can have both without a name clash.

Three invariants, enforced by `tests/Feature/DataLayerTest.php` because none of them can fail behaviourally:
- final readonly
- no `Illuminate\Http` dependency — `InitializeSuperAdminData` is built by a console command from config, and an HTTP dependency would make that caller impossible. Form Requests build data through `toData()`; a data object never reads a Request itself.
- no `toArray()` — presenters own every Inertia payload, and a second path to the same place competes with them.

Carry domain types, not primitives: resolve enums in `toData()` so an action assigns rather than parses. This is safe because every enum-backed field is already validated with `Rule::enum`/`Rule::in`.

Do not introduce value objects (Email, Slug) — validation of those stays in the Form Request. Do not type a genuinely polymorphic payload: `StageImageUpload` keeps `array $payload`, an opaque per-target blob persisted as JSON.
