import { nextTick } from 'vue';
export const stateLabels = { DISPONIBLE: 'Disponible', BLOQUEADO: 'Bloqueado', EN_MANTENIMIENTO: 'En mantenimiento' };
export const optionsFor = (rows) => rows.map(row => ({ value: row.id, label: row.nombre }));
export const statesFor = (states) => states.map(value => ({ value, label: stateLabels[value] }));
export async function focusError() {
  await nextTick();
  (document.querySelector('[aria-invalid="true"]') || document.querySelector('[role="alert"][tabindex="-1"]'))?.focus();
}
export function localPeriod(value) {
  if (!value) return '';
  const [date, time] = value.split(' ');
  return `${date.split('-').reverse().join('/')} · ${time}`;
}
