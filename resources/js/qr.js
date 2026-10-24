// QR payloads are transient request bodies, never Inertia form/history state.
export async function qrPost(url, data) {
  const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));
  let body = JSON.stringify(data);
  if (Object.hasOwn(data, 'qr_token')) delete data.qr_token;
  try {
    const response = await fetch(url, { method: 'POST', credentials: 'same-origin', cache: 'no-store',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-XSRF-TOKEN': cookie ? decodeURIComponent(cookie.slice(11)) : '' }, body });
    body = null;
    let result;
    try { result = await response.json(); } catch { throw new Error('No se pudo confirmar el resultado. Consulte el estado antes de reintentar.'); }
    if (!response.ok && !result.validacion) {
      throw new Error(response.status === 403 ? 'Ya no dispone de autorización para esta operación.' : response.status === 419 ? 'La sesión debe actualizarse. Recargue la página.' : Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo completar la operación.');
    }
    return result;
  } catch (error) {
    if (error instanceof TypeError) throw new Error('No se pudo confirmar el resultado. Revise la conexión y consulte el estado antes de reintentar.');
    throw error;
  } finally { body = null; }
}
