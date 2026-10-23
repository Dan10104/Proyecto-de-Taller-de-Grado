import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

export function useAccess() {
  const page = usePage();
  return {
    can: (code) => (page.props.auth?.permisos || []).includes(code),
    auth: computed(() => page.props.auth),
  };
}

export function dateLabel(value) {
  if (!value) return '—';
  return new Intl.DateTimeFormat('es-BO', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'America/La_Paz',
  }).format(new Date(value));
}

export async function finishLogout() {
  const submit = () => {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));
    return fetch('/logout', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-XSRF-TOKEN': cookie ? decodeURIComponent(cookie.slice(11)) : '',
        Accept: 'text/html',
      },
    });
  };
  let response = await submit();
  if (response.status === 419) {
    await fetch('/login', { credentials: 'same-origin', cache: 'no-store' });
    response = await submit();
  }
  if (!response.ok) throw new Error('Cierre no confirmado');
  router.clearHistory();
  sessionStorage.removeItem('espacio-u.logout-pending');
  window.location.replace('/login');
}
