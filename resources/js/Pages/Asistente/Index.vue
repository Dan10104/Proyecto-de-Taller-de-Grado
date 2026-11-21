<script setup>
import { computed, ref, nextTick, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import Icon from '../../Components/Icon.vue';

const props = defineProps({ maxInputChars: { type: Number, default: 2000 } });
const question = ref(''), messages = ref([]), pending = ref(false), error = ref(''), notice = ref('');
const input = ref(null), transcript = ref(null);
const length = computed(() => Array.from(question.value.replace(/\r\n/g, '\n').trim()).length);
let controller, generation = 0, sequence = 0, stopNavigation;

function clear(focus = false) {
  generation++;
  controller?.abort(); controller = null;
  question.value = ''; messages.value = []; error.value = ''; pending.value = false;
  notice.value = focus ? 'Conversación limpiada.' : '';
  if (focus) nextTick(() => input.value?.focus());
}
function lifecycleClear() { clear(false); }
async function send() {
  if (pending.value || !length.value) return;
  if (length.value > props.maxInputChars) { error.value = `La consulta admite hasta ${props.maxInputChars} caracteres.`; return; }
  const text = question.value.replace(/\r\n/g, '\n').trim();
  question.value = ''; error.value = ''; notice.value = ''; pending.value = true;
  const current = ++generation;
  controller = new AbortController();
  const requestController = controller;
  const clientDeadline = setTimeout(() => {
    if (current === generation) { error.value = 'Servicio de asistencia no disponible temporalmente.'; requestController.abort(); }
  }, 20000);
  const exchange = { id: ++sequence, question: text, answer: null, link: null };
  messages.value.push(exchange);
  if (messages.value.length > 20) { messages.value.shift(); notice.value = 'Se muestran las últimas 20 consultas de esta página.'; }
  try {
    const cookie = document.cookie.split('; ').find(item => item.startsWith('XSRF-TOKEN='));
    const response = await fetch('/asistente/consultar', {
      method: 'POST', credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': cookie ? decodeURIComponent(cookie.slice(11)) : '' },
      body: JSON.stringify({ pregunta: text }),
    });
    const data = await response.json();
    if (current !== generation) return;
    if (!response.ok) {
      error.value = [401,403,419].includes(response.status) ? 'Tu acceso o sesión cambió. Recarga la página para continuar.'
        : response.status === 429 ? 'Alcanzaste el límite de consultas. Espera un momento e inténtalo nuevamente.'
        : response.status === 422 ? `Escribe una consulta válida de hasta ${props.maxInputChars} caracteres.`
        : 'Servicio de asistencia no disponible temporalmente.';
      return;
    }
    const row = messages.value.find(item => item.id === exchange.id);
    if (row && typeof data.answer === 'string') {
      row.answer = data.answer;
      row.link = data.link && typeof data.link.href === 'string' && data.link.href.startsWith('/') && !data.link.href.startsWith('//') ? data.link : null;
    }
  } catch (failure) {
    if (current === generation && failure.name !== 'AbortError') error.value = 'No se pudo completar la consulta. Comprueba tu conexión e inténtalo nuevamente.';
  } finally {
    clearTimeout(clientDeadline);
    if (current === generation) { pending.value = false; controller = null; await nextTick(); input.value?.focus(); transcript.value?.scrollTo({ top: transcript.value.scrollHeight, behavior: 'smooth' }); }
  }
}
function enter(event) {
  if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) { event.preventDefault(); send(); }
}
onMounted(() => {
  clear();
  window.addEventListener('pagehide', lifecycleClear);
  window.addEventListener('pageshow', lifecycleClear);
  window.addEventListener('espacio-u:logout', lifecycleClear);
  stopNavigation = router.on('before', lifecycleClear);
});
onUnmounted(() => {
  clear(); stopNavigation?.();
  window.removeEventListener('pagehide', lifecycleClear);
  window.removeEventListener('pageshow', lifecycleClear);
  window.removeEventListener('espacio-u:logout', lifecycleClear);
});
</script>


<template>
  <AppLayout title="Asistente">
    <div class="page-heading">
      <div><h1>Asistente</h1><p>Encuentre el procedimiento y el módulo que necesita.</p></div>
      <span class="feature-chips assistant-tag"><span><Icon name="shield"/>Solo orientación</span></span>
    </div>
    <div class="assistant-layout">
      <details class="disclosure assistant-scope" aria-label="Alcance del asistente">
        <summary>Qué puede hacer el asistente</summary>
        <ul class="assistant-scope-list">
          <li><Icon name="search"/>Indicarle dónde realizar una operación según sus permisos.</li>
          <li><Icon name="info"/>Explicar requisitos y el significado de los estados.</li>
          <li><Icon name="layers"/>Abrir el módulo autorizado desde la respuesta.</li>
        </ul>
        <ul class="assistant-scope-list is-limits">
          <li><Icon name="lock"/>No ejecuta acciones: verifique estados y operaciones en los módulos oficiales.</li>
          <li><Icon name="shield"/>Su pregunta se envía a OpenAI, proveedor externo de IA, con orientación y capacidades mínimas; no se adjuntan sus registros.</li>
          <li><Icon name="clock"/>No guarda la conversación: permanece solo en esta página y se pierde al salir o recargar.</li>
          <li><Icon name="checklist"/>Hasta {{ maxInputChars }} caracteres por consulta; se muestran las últimas 20 consultas.</li>
        </ul>
      </details>

      <section class="panel assistant-conversation" aria-label="Asistente de ESPACIO-U">
        <div class="assistant-toolbar">
          <div><strong>Conversación</strong><small>Historial temporal de esta página</small></div>
          <button type="button" class="btn btn-secondary" @click="clear(true)" :disabled="!messages.length && !question && !error"><Icon name="refresh"/>Limpiar</button>
        </div>
        <div ref="transcript" class="assistant-transcript" role="log" aria-label="Conversación" aria-live="polite" aria-relevant="additions text">
          <div v-if="!messages.length" class="assistant-empty">
            <span class="assistant-emblem"><Icon name="chat"/></span>
            <h2>¿En qué procedimiento necesita ayuda?</h2>
            <p>Consulte cómo solicitar una reserva, revisar un préstamo o usar los módulos habilitados para usted.</p>
            <button type="button" class="assistant-suggestion" @click="question = '¿Cómo solicito una reserva?'; input?.focus()">¿Cómo solicito una reserva?</button>
          </div>
          <article v-for="message in messages" :key="message.id" class="assistant-exchange">
            <div class="assistant-bubble assistant-user"><strong>Usted</strong><p>{{ message.question }}</p></div>
            <div v-if="message.answer" class="assistant-bubble assistant-answer"><strong>Asistente</strong><p>{{ message.answer }}</p><a v-if="message.link" class="btn btn-secondary" :href="message.link.href">{{ message.link.label }}</a></div>
          </article>
        </div>
        <div class="assistant-status" role="status" aria-live="polite"><span v-if="pending" class="assistant-pending">Preparando orientación…</span><span v-else>{{ notice }}</span></div>
        <p v-if="error" class="alert alert-error assistant-error" role="alert">{{ error }}</p>
        <form class="assistant-composer" @submit.prevent="send" autocomplete="off">
          <label for="assistant-question" class="sr-only">Su consulta sobre ESPACIO-U</label>
          <textarea id="assistant-question" ref="input" v-model="question" rows="3" placeholder="Escriba su consulta…" autocomplete="off" autocorrect="off" spellcheck="false" aria-describedby="assistant-privacy assistant-count assistant-keyboard" :aria-invalid="length > maxInputChars" @keydown="enter"></textarea>
          <div class="assistant-compose-actions">
            <small id="assistant-privacy" class="assistant-privacy"><Icon name="lock"/>No incluya contraseñas, claves, códigos ni información sensible.</small>
            <small id="assistant-keyboard" class="assistant-keyboard">Enter para enviar · Shift+Enter para una nueva línea</small>
            <span id="assistant-count" class="assistant-count" :class="{ 'assistant-over': length > maxInputChars }">{{ length }} / {{ maxInputChars }}</span>
            <button class="btn btn-primary" type="submit" :disabled="pending || !length || length > maxInputChars">{{ pending ? 'Consultando…' : 'Enviar consulta' }}</button>
          </div>
        </form>
      </section>
    </div>
  </AppLayout>
</template>
