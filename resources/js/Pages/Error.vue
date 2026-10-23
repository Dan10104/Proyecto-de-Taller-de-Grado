<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Brand from '../Components/Brand.vue';

defineProps({ status: Number });
const text = {
  403: ['Acceso no autorizado', 'Su cuenta no tiene permiso para realizar esta acción.'],
  404: ['Página no encontrada', 'Compruebe la dirección o regrese al inicio.'],
  419: ['La sesión necesita renovarse', 'Vuelva a iniciar sesión antes de continuar.'],
  429: ['Espere un momento', 'Se recibieron demasiadas solicitudes. Inténtelo nuevamente en un minuto.'],
};
</script>

<template>
  <Head :title="`${status} · ESPACIO-U`"/>
  <main class="session-screen">
    <Brand/>
    <span class="error-code">{{ status }}</span>
    <h1>{{ text[status]?.[0]||'Servicio no disponible' }}</h1>
    <p>{{ text[status]?.[1]||'Inténtelo nuevamente más tarde.' }}</p>
    <Link :href="status===419?'/login':'/inicio'" class="btn btn-primary">{{ status===419?'Iniciar sesión':'Volver al inicio' }}</Link>
  </main>
</template>
