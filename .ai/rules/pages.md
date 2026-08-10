---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Keep confirm-dialog target and open state in separate refs
Registry dialog action components (`AlertDialogAction`, and any primitive forwarding `@click` as a fallthrough attribute) run their own close handler BEFORE the consumer's listener, because Vue merges the component's own props ahead of inherited attrs. A confirm flow whose `open` binding also clears the pending target therefore reads null in its confirm handler and silently sends nothing — no error, no request. Keep "which record is pending" and "is the dialog open" in separate refs, and clear the target only after dispatching. jsdom can order these the other way, so a passing component test is not evidence; drive the close explicitly (`findComponent(AlertDialog).vm.$emit('update:open', false)`) before clicking confirm.

## Wire a data table from the page: visit, columns, and Wayfinder serialization
The consuming page owns the visit: turn the table's `query-change` into a Wayfinder `router.get` with `only: ['<resource>']`, `preserveState`, `preserveScroll`, `replace` — that is what parks table state in the URL. Wayfinder `queryParams` drops null, so an absent search never appears, and it serializes an array as `key[]=v` (URL-encoded) which Laravel parses back as an ordered list; that is how `ids[]` and the filter arrays reach the server. Spread `query.filters` into the visit's `query`.

Columns live domain-local in `pages/<domain>/<resource>/columns.ts` as a `create<X>Columns(t)` factory: the translator is injected because column defs are built outside a component setup scope. A column's `accessorKey` MUST equal the backend sort key.

The row-selection column follows the official shadcn-vue example and lives in that same `columns.ts` as an `id: 'select'` column: `Checkbox` bound to `getIsAllPageRowsSelected() || (getIsSomePageRowsSelected() && 'indeterminate')` / `toggleAllPageRowsSelected`, rows to `getIsSelected` / `toggleSelected`, with `enableSorting: false` and `enableHiding: false`. Use `aria-label`, not the doc's `ariaLabel` — a camelCase fallthrough attr reaches the DOM lowercased and is not a valid ARIA attribute.

A reka-ui `DropdownMenuItem as-child` wrapping an Inertia `Link` must be given `route(...).url`, not the route definition object: the bound href lands on the anchor as `[object Object]` before Inertia can resolve it.
