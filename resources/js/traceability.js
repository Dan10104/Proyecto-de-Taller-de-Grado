export const operations = { RESERVA_APROBADA: 'Reserva aprobada', RESERVA_CANCELADA: 'Reserva cancelada', PRESTAMO_ENTREGADO: 'Equipamiento entregado', PRESTAMO_DEVUELTO: 'Devolución confirmada' };
export const verificationResults = { VALIDO: 'Válido', INCONSISTENTE: 'Inconsistente', PENDIENTE: 'Pendiente', NO_VERIFICABLE: 'No verificable' };
const dateFormatter = new Intl.DateTimeFormat('es-BO', { timeZone: 'America/La_Paz', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' });
export function traceabilityDate(value) {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : dateFormatter.format(date).replace(', ', ' · ');
}
export const explanations = {
  VALIDO: 'El hash reconstruido coincide con la evidencia local y el registro externo confirmado.',
  INCONSISTENTE: 'Se encontró una diferencia comprobable. La evidencia histórica se conserva para su revisión.',
  PENDIENTE: 'En la última comprobación, la evidencia local coincidía, pero el registro externo aún no estaba confirmado.',
  NO_VERIFICABLE: 'No fue posible completar la comprobación. Este resultado no demuestra una alteración.',
};
