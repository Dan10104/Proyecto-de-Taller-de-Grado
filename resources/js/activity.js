// Presentación: etiquetas legibles de las acciones de la bitácora.
// El servidor entrega el código de la acción (y una etiqueta derivada de él sin tildes);
// aquí solo se decide cómo se lee en pantalla. No cambia el código ni el dato registrado.

const ACTIONS = {
  RESERVA_SOLICITADA: 'Reserva solicitada',
  RESERVA_APROBADA: 'Reserva aprobada',
  RESERVA_RECHAZADA: 'Reserva rechazada',
  RESERVA_CANCELADA: 'Reserva cancelada',
  RESERVA_ALERTADA_LIBERACION: 'Reserva con alerta de liberación',
  RESERVA_LIBERADA: 'Reserva liberada',
  PRESTAMO_SOLICITADO: 'Préstamo solicitado',
  PRESTAMO_APROBADO: 'Préstamo aprobado',
  PRESTAMO_RECHAZADO: 'Préstamo rechazado',
  PRESTAMO_ENTREGADO: 'Préstamo entregado',
  PRESTAMO_DEVUELTO: 'Préstamo devuelto',
  PRESTAMO_VENCIDO: 'Préstamo vencido',
  DEVOLUCION_PENDIENTE_REVISION: 'Devolución pendiente de revisión',
  INCIDENCIA_REGISTRADA: 'Incidencia registrada',
  INCIDENCIA_CERRADA: 'Incidencia cerrada',
  MANTENIMIENTO_REGISTRADO: 'Mantenimiento registrado',
  MANTENIMIENTO_CERRADO: 'Mantenimiento cerrado',
  REPORTE_EXPORTADO: 'Reporte exportado',
  USUARIO_CREADO: 'Usuario creado',
  USUARIO_EDITADO: 'Usuario editado',
  USUARIO_DESHABILITADO: 'Usuario deshabilitado',
  ROL_CREADO: 'Rol creado',
  ROL_EDITADO: 'Rol editado',
  PERMISOS_ROL_ACTUALIZADOS: 'Permisos del rol actualizados',
  ROL_ASIGNADO: 'Rol asignado',
  ROL_RETIRADO: 'Rol retirado',
  ESPACIO_CREADO: 'Espacio creado',
  ESPACIO_EDITADO: 'Espacio editado',
  ESPACIO_ESTADO_CAMBIADO: 'Estado del espacio cambiado',
  BLOQUEO_ESPACIO_CREADO: 'Bloqueo de espacio creado',
  BLOQUEO_ESPACIO_DESACTIVADO: 'Bloqueo de espacio desactivado',
  PISO_CREADO: 'Piso creado',
  PISO_EDITADO: 'Piso editado',
  TIPO_ESPACIO_CREADO: 'Tipo de espacio creado',
  TIPO_ESPACIO_EDITADO: 'Tipo de espacio editado',
  CARACTERISTICA_ESPACIO_CREADA: 'Característica de espacio creada',
  CARACTERISTICA_ESPACIO_EDITADA: 'Característica de espacio editada',
  TIPO_EQUIPAMIENTO_CREADO: 'Tipo de equipamiento creado',
  TIPO_EQUIPAMIENTO_EDITADO: 'Tipo de equipamiento editado',
  EQUIPAMIENTO_CREADO: 'Equipamiento creado',
  EQUIPAMIENTO_EDITADO: 'Equipamiento editado',
  EQUIPAMIENTO_ESTADO_CAMBIADO: 'Estado del equipamiento cambiado',
  SENSOR_CREADO: 'Sensor creado',
  SENSOR_EDITADO: 'Sensor editado',
  SENSOR_REASIGNADO: 'Sensor reasignado',
  SENSOR_HABILITADO: 'Sensor habilitado',
  SENSOR_DESHABILITADO: 'Sensor deshabilitado',
  SENSOR_CREDENCIAL_CONSULTADA: 'Credencial del sensor consultada',
  QR_GENERADO: 'Código QR generado',
  QR_ROTADO: 'Código QR rotado',
  QR_DESACTIVADO: 'Código QR desactivado',
  POLITICA_LIBERACION_CREADA: 'Política de liberación creada',
  POLITICA_LIBERACION_ACTIVADA: 'Política de liberación activada',
  POLITICA_LIBERACION_DESACTIVADA: 'Política de liberación desactivada',
  POLITICA_ASIGNADA_ESPACIO: 'Política asignada a un espacio',
  POLITICA_RETIRADA_ESPACIO: 'Política retirada de un espacio',
};

// Palabras frecuentes de los códigos, con su tilde o su sigla, para valores no catalogados arriba.
const WORDS = {
  prestamo: 'préstamo', prestamos: 'préstamos', politica: 'política', politicas: 'políticas',
  devolucion: 'devolución', revision: 'revisión', liberacion: 'liberación', caracteristica: 'característica',
  evaluacion: 'evaluación', asignacion: 'asignación', notificacion: 'notificación', validacion: 'validación',
  verificacion: 'verificación', sesion: 'sesión', codigo: 'código', operacion: 'operación',
  autenticacion: 'autenticación', automatica: 'automática', automatico: 'automático', academico: 'académico',
  tecnologico: 'tecnológico', qr: 'QR', iot: 'IoT', id: 'ID', csv: 'CSV', pdf: 'PDF',
};

// Convierte un código en mayúsculas con guiones bajos en una frase legible.
export function humanize(code) {
  const text = String(code ?? '').trim();
  if (!text) return '';
  const words = text.toLowerCase().split(/[_\s]+/).map(word => WORDS[word] || word);
  const phrase = words.join(' ');
  return phrase.charAt(0).toUpperCase() + phrase.slice(1);
}

// Etiqueta de una acción: la catalogada, o el código humanizado, o la etiqueta que envió el servidor.
export function actionLabel(code, fallback = '') {
  if (code && ACTIONS[code]) return ACTIONS[code];
  return code ? humanize(code) : fallback;
}
