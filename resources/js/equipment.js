export const labels = {
 DISPONIBLE:'Disponible',PRESTADO:'Prestado',EN_MANTENIMIENTO:'En mantenimiento',BAJA:'Baja',
 PENDIENTE:'Pendiente',APROBADO:'Aprobado',RECHAZADO:'Rechazado',ENTREGADO:'Entregado',DEVUELTO:'Devuelto',
 ADECUADA:'Adecuada',CON_INCIDENCIA:'Con incidencia',NO_DETERMINABLE:'No determinable',
 PENDIENTE_REVISION:'Pendiente de revisión',CONFIRMADA:'Confirmada',ABIERTA:'Abierta',CERRADA:'Cerrada',
 INCIDENCIA:'Incidencia',MANTENIMIENTO:'Mantenimiento',BLOQUEADO:'Bloqueado'
};
export const options = values => values.map(value=>({value,label:labels[value]||value}));
export const person = value => value ? value.nombres+' '+value.apellidos : '—';
export const clean = data => Object.fromEntries(Object.entries(data).filter(([,v])=>v!=='' && v!==null && v!==false));
