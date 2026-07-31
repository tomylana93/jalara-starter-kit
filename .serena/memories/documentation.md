# Internal Documentation

- Documentation is authenticated+verified app-shell content; readers only see `published`, while management routes/actions require `Role::SuperAdmin`.
- Persistence: `documentation_categories` and `documentations`; UUIDs, one-level categories, manual integer positions, Tiptap JSON in `content`, extracted plain text in `searchable_text`, and permanent deletion. No revision history or autosave.
- `published_at` records first publication and permanently freezes the slug; switching back to draft does not clear it.
- Tiptap content is server-validated by `App\Support\DocumentationContent`; supported structure includes headings 1–3, emphasis, lists, quote/code, links, and tables. Links allow internal `/` paths or HTTP(S) only.
- Search is portable lexical database search for SQLite/MySQL/PostgreSQL, limited to published documents and eight results; no vector/AI dependency.
- UI routes/components live under `resources/js/pages/documentation` and `resources/js/components/documentation`. Documentation is an internal `NavFooter` item; Repository remains external.
- Global command palette is mounted in `AppShell`; Ctrl/Cmd+K opens it outside editable controls, while editor/input shortcuts remain untouched. Documentation requests start at two characters with a 250 ms debounce.
- The palette's `CommandGroup` recomputes its filter only when the search string changes, so it permanently hides any group whose items mount later. Server-filtered results must therefore render outside a `CommandGroup`, with `provideCommandGroupContext` called at the palette root because `CommandItem` still injects it, and `CommandEmpty` gated on the remote results instead of the filter count. Nesting a `ScrollArea` inside `CommandList` also collapses the list.