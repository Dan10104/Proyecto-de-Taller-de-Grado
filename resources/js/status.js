// Lenguaje visual único para los estados del dominio.
// Cada valor de enum se muestra con su etiqueta, un tono semántico y una figura,
// de modo que el estado nunca dependa solo del color. No se definen estados nuevos:
// las claves son exactamente los valores de app/Enums.

const tone = (label, tone, mark) => ({ label, tone, mark });

// Tonos: positive, attention, negative, info, neutral.
// Figuras: check, clock, cross, lock, tool, dot, ring, minus, alert, arrow.
const shared = {
  ACTIVO: tone('Activo', 'positive', 'check'),
  INACTIVO: tone('Inactivo', 'neutral', 'minus'),
  DISPONIBLE: tone('Disponible', 'positive', 'check'),
  BLOQUEADO: tone('Bloqueado', 'negative', 'lock'),
  EN_MANTENIMIENTO: tone('En mantenimiento', 'attention', 'tool'),
  PRESTADO: tone('Prestado', 'info', 'arrow'),
  BAJA: tone('Baja', 'neutral', 'minus'),
  PENDIENTE: tone('Pendiente', 'attention', 'clock'),
  APROBADA: tone('Aprobada', 'positive', 'check'),
  APROBADO: tone('Aprobado', 'positive', 'check'),
  RECHAZADA: tone('Rechazada', 'negative', 'cross'),
  RECHAZADO: tone('Rechazado', 'negative', 'cross'),
  CANCELADA: tone('Cancelada', 'neutral', 'minus'),
  LIBERADA: tone('Liberada', 'info', 'ring'),
  FINALIZADA: tone('Finalizada', 'neutral', 'check'),
  ENTREGADO: tone('Entregado', 'info', 'arrow'),
  DEVUELTO: tone('Devuelto', 'neutral', 'check'),
  PENDIENTE_REVISION: tone('Pendiente de revisión', 'attention', 'clock'),
  CONFIRMADA: tone('Confirmada', 'positive', 'check'),
  ADECUADA: tone('Adecuada', 'positive', 'check'),
  CON_INCIDENCIA: tone('Con incidencia', 'attention', 'alert'),
  NO_DETERMINABLE: tone('No determinable', 'neutral', 'ring'),
  ABIERTA: tone('Abierta', 'attention', 'alert'),
  CERRADA: tone('Cerrada', 'neutral', 'check'),
  INCIDENCIA: tone('Incidencia', 'neutral', 'alert'),
  MANTENIMIENTO: tone('Mantenimiento', 'neutral', 'tool'),
  HABILITADO: tone('Habilitado', 'positive', 'check'),
  DESHABILITADO: tone('Deshabilitado', 'neutral', 'minus'),
  REGISTRADA: tone('Registrada', 'positive', 'check'),
  VALIDO: tone('Válido', 'positive', 'check'),
  INCONSISTENTE: tone('Inconsistente', 'negative', 'cross'),
  NO_VERIFICABLE: tone('No verificable', 'neutral', 'ring'),
  MANTENER_POR_USO: tone('Mantener por uso', 'positive', 'check'),
  ALERTAR: tone('Alertar', 'attention', 'alert'),
  LIBERAR: tone('Liberar', 'info', 'ring'),
  EXITO: tone('Éxito', 'positive', 'check'),
  RECHAZO: tone('Rechazo', 'attention', 'alert'),
  ERROR: tone('Error', 'negative', 'cross'),
  VALIDA: tone('Válida', 'positive', 'check'),
  INVALIDA: tone('Inválida', 'negative', 'cross'),
};

// Valores cuyo significado cambia según la entidad.
const byDomain = {
  evidencia: { PENDIENTE: tone('Pendiente', 'attention', 'clock') },
  verificacion: { PENDIENTE: tone('Pendiente', 'attention', 'clock') },
  bloqueo: { true: tone('Vigente', 'attention', 'lock'), false: tone('Desactivado', 'neutral', 'minus') },
  lectura: { true: tone('No leída', 'info', 'dot'), false: tone('Leída', 'neutral', 'check') },
};

export function statusOf(value, domain = null) {
  const key = String(value);
  return byDomain[domain]?.[key] || shared[key] || tone(key.replaceAll('_', ' ').toLowerCase(), 'neutral', 'ring');
}

export const statusLabel = (value, domain = null) => statusOf(value, domain).label;
