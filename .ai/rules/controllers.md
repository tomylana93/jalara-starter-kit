---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Inject Actions as method parameters, not constructor dependencies
A controller action receives the Action it needs in its own signature, after the request: `public function store(StoreThingRequest $request, CreateThing $createThing)`. Do not add a constructor to hold them.
