// Navegación de la aplicación: una sola definición para el menú lateral y para la página de inicio.
// Cada entrada aparece solo si la cuenta tiene el permiso que exige su destino.
// «match» lista los prefijos de ruta que marcan la entrada como actual; «exact» exige coincidencia exacta.

export function navigationGroups(can, unread = 0) {
  const any = (...codes) => codes.some(code => can(code));
  return [
    { label: 'Mi espacio', items: [
      { href: '/inicio', label: 'Inicio', icon: 'home', show: true, match: ['/inicio'], exact: true },
      { href: '/dashboard', label: 'Dashboard', icon: 'chart', show: can('dashboard.ver'), match: ['/dashboard'], note: 'Indicadores de su cuenta' },
      { href: '/notificaciones', label: 'Notificaciones', icon: 'bell', show: can('notificaciones.ver_propias'), match: ['/notificaciones'], count: unread, note: 'Avisos de sus solicitudes' },
      { href: '/asistente', label: 'Asistente', icon: 'chat', show: can('chatbot.consultar'), match: ['/asistente'], note: 'Orientación sobre procedimientos' },
    ] },
    { label: 'Espacios y reservas', items: [
      { href: '/disponibilidad', label: 'Disponibilidad', icon: 'search', show: can('espacios.ver'), match: ['/disponibilidad'], note: 'Espacios libres por fecha y horario' },
      { href: '/espacios', label: 'Espacios académicos', icon: 'building', show: can('espacios.ver'), match: ['/espacios'], note: 'Ubicación, capacidad y estado' },
      { href: '/reservas', label: 'Reservas', icon: 'calendar', show: any('reservas.ver_propias', 'reservas.ver_administrativas'), match: ['/reservas'], note: 'Solicitudes y reservas registradas' },
    ] },
    { label: 'Equipamiento y préstamos', items: [
      { href: '/equipamientos', label: 'Equipamiento', icon: 'monitor', show: can('equipamientos.ver'), match: ['/equipamientos'], note: 'Inventario y estado' },
      { href: '/prestamos', label: 'Préstamos', icon: 'exchange', show: any('prestamos.ver_propios', 'prestamos.ver_administrativos'), match: ['/prestamos'], note: 'Solicitudes, entregas y devoluciones' },
      { href: '/incidencias', label: 'Incidencias', icon: 'tool', show: can('incidencias.ver'), match: ['/incidencias'], note: 'Incidencias y mantenimientos' },
    ] },
    { label: 'Monitoreo y evidencia', items: [
      { href: '/iot/monitoreo', label: 'Monitoreo', icon: 'signal', show: can('iot.monitoreo.ver'), match: ['/iot/monitoreo'], note: 'Sensores y lecturas' },
      { href: '/iot/sensores', label: 'Sensores IoT', icon: 'sensor', show: can('iot.sensores.ver'), match: ['/iot/sensores'], note: 'Dispositivos por espacio' },
      { href: '/qr', label: 'Códigos QR', icon: 'qr', show: can('qr.gestionar'), match: ['/qr'], note: 'Etiquetas de espacios y equipos' },
      { href: can('liberacion.politicas.ver') ? '/liberacion/politicas' : '/liberacion/asignaciones', label: 'Políticas de liberación', icon: 'clock', show: any('liberacion.politicas.ver', 'liberacion.politicas.gestionar'), match: ['/liberacion/politicas', '/liberacion/asignaciones'], note: 'Reglas de liberación de reservas' },
      { href: '/liberacion/evaluaciones', label: 'Evaluaciones', icon: 'checklist', show: any('liberacion.evaluaciones.ver_administrativas', 'liberacion.evaluaciones.ver_propias'), match: ['/liberacion/evaluaciones'], note: 'Evaluaciones de liberación' },
      { href: '/trazabilidad', label: 'Trazabilidad', icon: 'network', show: can('trazabilidad.evidencias.ver'), match: ['/trazabilidad'], note: 'Registro verificable de operaciones' },
    ] },
    { label: 'Administración', items: [
      { href: '/reportes', label: 'Reportes', icon: 'report', show: can('reportes.ver'), match: ['/reportes'], note: 'Consultas y exportación' },
      { href: '/actividad', label: 'Bitácora', icon: 'activity', show: can('actividad.ver'), match: ['/actividad'], note: 'Registro de actividad' },
      { href: '/usuarios', label: 'Usuarios', icon: 'users', show: can('usuarios.ver'), match: ['/usuarios'], note: 'Cuentas y acceso' },
      { href: '/roles', label: 'Roles y permisos', icon: 'shield', show: can('roles.ver'), match: ['/roles', '/permisos'], note: 'Roles y sus permisos' },
    ] },
  ].map(group => ({ ...group, items: group.items.filter(item => item.show) })).filter(group => group.items.length);
}

// Módulos que la cuenta puede abrir, sin contar la propia página de inicio.
export function authorizedModules(can, unread = 0) {
  return navigationGroups(can, unread).flatMap(group => group.items).filter(item => item.href !== '/inicio');
}
