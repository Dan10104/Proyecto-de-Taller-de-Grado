export const reservationStates = { PENDIENTE: 'Pendiente', APROBADA: 'Aprobada', RECHAZADA: 'Rechazada', CANCELADA: 'Cancelada', LIBERADA: 'Liberada', FINALIZADA: 'Finalizada' };
export function reservationDate(value) {
  if (!value) return '—';
  const [date, time] = value.split(' ');
  return `${date.split('-').reverse().join('/')} · ${time}`;
}
