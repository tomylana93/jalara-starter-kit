---
paths:
  - 'app/Http/Presenters/**'
---

# Presenters

## Presenters build every Inertia payload; models never cross the boundary
A controller passes data to Inertia through a static Presenter method that returns an explicit array of scalars, documented with an array-shape PHPDoc. Never hand an Eloquent model or collection to `Inertia::render()`.

## Return lists with array_values(...-&gt;all())
A method documented `@return list<...>` must wrap the collection in `array_values(...->all())`. PHPStan does not narrow `->values()->all()` to a list and the analysis fails.
