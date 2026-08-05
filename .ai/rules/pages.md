---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Keep confirm-dialog target and open state in separate refs
Registry dialog action components (`AlertDialogAction`, and any primitive forwarding `@click` as a fallthrough attribute) run their own close handler BEFORE the consumer's listener, because Vue merges the component's own props ahead of inherited attrs. A confirm flow whose `open` binding also clears the pending target therefore reads null in its confirm handler and silently sends nothing — no error, no request. Keep "which record is pending" and "is the dialog open" in separate refs, and clear the target only after dispatching. jsdom can order these the other way, so a passing component test is not evidence; drive the close explicitly (`findComponent(AlertDialog).vm.$emit('update:open', false)`) before clicking confirm.
