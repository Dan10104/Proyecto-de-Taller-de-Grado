<script setup>
import {Link,useForm} from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import InputField from '../../Components/InputField.vue';
import FormErrors from '../../Components/FormErrors.vue';
import OperationForm from '../../Components/OperationForm.vue';
import {reservationDate} from '../../reservations';
import {focusError} from '../../spaces';
const props=defineProps({equipamiento:Object,seleccion:Object});
const form=useForm({...props.seleccion,motivo:''});
function send(){form.post('/prestamos',{onError:focusError});}
</script>
<template>
 <AppLayout title="Solicitar préstamo" section="Préstamos"><div class="page-heading"><div><h1>{{equipamiento.nombre}}</h1><p>{{equipamiento.codigo}} · {{equipamiento.tipo}} · {{equipamiento.ubicacion}}</p></div><Link href="/equipamientos/disponibilidad" class="btn btn-secondary">Cambiar equipo o periodo</Link></div>
  <section class="panel form-panel"><h2>Prepare su solicitud</h2><dl class="detail-grid"><div><dt>Inicio previsto</dt><dd>{{reservationDate(seleccion.inicio_previsto.replace('T',' '))}}</dd></div><div><dt>Fin previsto</dt><dd>{{reservationDate(seleccion.fin_previsto.replace('T',' '))}}</dd></div></dl><p class="alert">La solicitud quedará pendiente de aprobación. El equipo todavía no está comprometido para usted.</p>
   <OperationForm label="Enviar solicitud" message="Se registrará una solicitud a su nombre para el equipo y periodo indicados." :disabled="form.processing" @confirm="send"><FormErrors :errors="form.errors"/><InputField id="motivo" label="Motivo académico del préstamo" type="textarea" v-model="form.motivo" required maxlength="500" :error="form.errors.motivo"/><p class="hint">Máximo 500 caracteres. Fechas y horas de Bolivia.</p></OperationForm>
  </section>
 </AppLayout>
</template>
