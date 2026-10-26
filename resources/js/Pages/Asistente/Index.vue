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
    <div class="page-heading assistant-heading"><div><span class="eyebrow">ORIENTACIÓN DE LA PLATAFORMA</span><h1>Asistente</h1><p>Encuentra el procedimiento y el módulo que necesitas.</p></div><span class="assistant-tag"><Icon name="shield"/>Solo orientación</span></div>
    <div class="assistant-layout">
      <section class="panel assistant-conversation" aria-label="Asistente de ESPACIO-U">
        <div class="assistant-toolbar"><div><strong>Tu consulta, paso a paso</strong><small>Historial temporal de esta página</small></div><button type="button" class="btn btn-secondary" @click="clear(true)" :disabled="!messages.length && !question && !error">Limpiar</button></div>
        <div ref="transcript" class="assistant-transcript" role="log" aria-label="Conversación" aria-live="polite" aria-relevant="additions text">
          <div v-if="!messages.length" class="assistant-empty"><span class="assistant-emblem"><Icon name="search"/></span><h2>¿En qué procedimiento necesitas ayuda?</h2><p>Puedes consultar cómo solicitar una reserva, revisar un préstamo o utilizar los módulos habilitados para ti.</p><p class="assistant-example">Por ejemplo: «¿Cómo solicito una reserva?»</p></div>
          <article v-for="message in messages" :key="message.id" class="assistant-exchange"><div class="assistant-bubble assistant-user"><strong>Tú</strong><p>{{ message.question }}</p></div><div v-if="message.answer" class="assistant-bubble assistant-answer"><strong>Asistente ESPACIO-U</strong><p>{{ message.answer }}</p><a v-if="message.link" class="btn btn-secondary" :href="message.link.href">{{ message.link.label }}<Icon name="arrow"/></a></div></article>
        </div>
        <div class="assistant-status" role="status" aria-live="polite"><span v-if="pending">Preparando orientación…</span><span v-else>{{ notice }}</span></div>
        <p v-if="error" class="alert alert-error assistant-error" role="alert">{{ error }}</p>
        <form class="assistant-composer" @submit.prevent="send" autocomplete="off">
          <label for="assistant-question">Tu consulta sobre ESPACIO-U</label>
          <textarea id="assistant-question" ref="input" v-model="question" rows="3" placeholder="Escribe tu consulta…" autocomplete="off" autocorrect="off" spellcheck="false" aria-describedby="assistant-privacy assistant-count assistant-keyboard" :aria-invalid="length > maxInputChars" @keydown="enter"></textarea>
          <div class="assistant-compose-actions"><small id="assistant-keyboard">Enter para enviar · Shift+Enter para una nueva línea</small><span id="assistant-count" :class="{ 'assistant-over': length > maxInputChars }">{{ length }} / {{ maxInputChars }}</span><button class="btn btn-primary" type="submit" :disabled="pending || !length || length > maxInputChars">{{ pending ? 'Consultando…' : 'Enviar consulta' }}<Icon name="arrow"/></button></div>
          <p id="assistant-privacy" class="assistant-privacy">No incluyas contraseñas, claves, códigos ni información sensible. Tu pregunta se envía a OpenAI, proveedor externo de IA, con orientación y capacidades mínimas; no se adjuntan tus registros.</p>
          <p class="assistant-privacy">El asistente brinda orientación. Verifica estados y operaciones en los módulos oficiales.</p>
        </form>
      </section>
      <aside class="assistant-help" aria-label="Alcance del asistente"><div class="panel"><span class="eyebrow">GUÍA PARA TU CONSULTA</span><h2>Orientación según tus permisos</h2><ul><li>Encuentra dónde realizar una operación.</li><li>Consulta requisitos y significado de estados.</li><li>Abre el módulo autorizado desde la respuesta.</li></ul></div><div class="assistant-memory"><Icon name="shield"/><p>La conversación permanece solo en esta página. Al salir o recargar, el historial se pierde.</p></div></aside>
    </div>
  </AppLayout>
</template>

<style scoped>
.assistant-heading{align-items:center}.assistant-tag{display:flex;gap:8px;align-items:center;background:#eaf2f8;color:#21466a;border:1px solid #cadbea;border-radius:24px;padding:10px 14px;font-weight:600;font-size:13px;white-space:nowrap}.assistant-tag svg{width:17px;height:17px}.assistant-layout{display:grid;grid-template-columns:minmax(0,1fr) 265px;gap:24px;align-items:start}.assistant-conversation{padding:0;overflow:hidden;min-width:0}.assistant-toolbar{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px 24px;border-bottom:1px solid #e0e7ef}.assistant-toolbar strong{display:block;color:#173d63}.assistant-toolbar small{display:block;margin-top:5px;color:#56677c}.assistant-transcript{min-height:300px;max-height:520px;overflow:auto;padding:24px;background:#f8fafc}.assistant-empty{max-width:490px;text-align:center;margin:15px auto 30px;color:#53667c;line-height:1.75}.assistant-empty h2{font-size:22px;color:#1f3f60;line-height:1.4}.assistant-emblem{display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:18px;background:#e6eef7;color:#285781}.assistant-example{font-size:13px;padding:12px;background:#edf2f8;border-radius:8px}.assistant-exchange{display:grid;gap:14px;margin-bottom:25px}.assistant-bubble{max-width:90%;padding:16px 20px;border-radius:14px;border:1px solid #dce5ef;line-height:1.7;overflow-wrap:anywhere}.assistant-bubble strong{font-size:12px;letter-spacing:.025em}.assistant-bubble p{white-space:pre-wrap;margin:6px 0}.assistant-user{justify-self:end;background:#eaf2fc;color:#193e63}.assistant-answer{justify-self:start;background:#fff;color:#263e55}.assistant-answer a{margin-top:10px;white-space:normal}.assistant-status{min-height:26px;padding:5px 24px;color:#53677e;font-size:13px}.assistant-error{margin:10px 24px}.assistant-composer{padding:16px 24px 22px;border-top:1px solid #e0e7ef}.assistant-composer label{display:block;font-weight:600;color:#253e59;margin-bottom:9px}.assistant-composer textarea{display:block;width:100%;resize:vertical;min-height:100px;border:1px solid #aebed1;border-radius:10px;padding:13px;background:white;color:#203c59;font:inherit;line-height:1.5}.assistant-composer textarea:focus{outline:3px solid #b5d5f4;outline-offset:2px;border-color:#356795}.assistant-compose-actions{display:flex;gap:12px;align-items:center;margin-top:12px;flex-wrap:wrap}.assistant-compose-actions small{color:#56677c;flex:1;min-width:160px;font-size:11px}.assistant-compose-actions span{font-size:12px;color:#56677c}.assistant-compose-actions .assistant-over{color:#a02727}.assistant-privacy{font-size:12px;line-height:1.65;color:#52657b;margin:15px 0 0}.assistant-help .panel{padding:24px}.assistant-help h2{font-size:19px;line-height:1.4;color:#214260;margin:12px 0}.assistant-help ul{padding-left:18px;line-height:1.7;color:#496077;font-size:14px}.assistant-help li{margin:10px 0}.assistant-help .panel>p{font-size:13px;line-height:1.7;border-top:1px solid #e0e7ef;padding-top:18px;color:#52657b}.assistant-memory{display:flex;gap:12px;margin:20px 7px;color:#586d81;font-size:12px;line-height:1.7}.assistant-memory svg{flex:0 0 18px}.assistant-memory p{margin:0}@media(max-width:1150px){.assistant-layout{grid-template-columns:1fr}.assistant-help{display:none}}@media(max-width:560px){.assistant-heading{align-items:flex-start}.assistant-tag{display:none}.assistant-toolbar,.assistant-composer{padding:16px}.assistant-transcript{padding:16px;min-height:260px}.assistant-empty h2{font-size:20px}.assistant-bubble{max-width:100%;padding:14px}.assistant-compose-actions .btn{width:100%;justify-content:center}.assistant-toolbar .btn{min-width:70px}.assistant-privacy{font-size:11px}}
</style>
