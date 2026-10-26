// Native modal dialogs make the background inert. Keep Tab at the content boundaries
// inside the dialog as well, instead of cycling through browser chrome.
export function keepDialogFocus(event) {
  if (event.key !== 'Tab') return;
  const dialog = event.currentTarget;
  const controls = [...dialog.querySelectorAll('button, input, select, textarea, a[href], [tabindex]')]
    .filter(element => !element.disabled && element.tabIndex >= 0 && element.getClientRects().length > 0);
  if (!controls.length) { event.preventDefault(); return; }
  const first = controls[0], last = controls.at(-1);
  if (event.shiftKey && dialog.ownerDocument.activeElement === first) {
    event.preventDefault(); last.focus();
  } else if (!event.shiftKey && dialog.ownerDocument.activeElement === last) {
    event.preventDefault(); first.focus();
  }
}
