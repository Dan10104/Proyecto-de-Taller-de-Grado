import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { finishLogout } from './security';

if (sessionStorage.getItem('espacio-u.logout-pending')) {
  const root = document.getElementById('app');
  root.removeAttribute('data-page');
  root.innerHTML = '<main class="session-screen"><h1>Cierre de sesión pendiente</h1><p>No se mostrarán datos hasta confirmar el cierre.</p><button class="btn btn-primary" id="retry-logout">Reintentar cierre</button><p role="status" id="logout-status"></p></main>';
  const attempt = () => finishLogout().catch(() => {
    document.getElementById('logout-status').textContent = 'Compruebe su conexión e inténtelo nuevamente.';
  });
  document.getElementById('retry-logout').addEventListener('click', attempt);
  window.addEventListener('online', attempt);
  attempt();
} else {
  createInertiaApp({ pages: './Pages' });
}
