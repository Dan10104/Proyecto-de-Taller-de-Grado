<script setup>
// Isolated test page only. This module is absent from the production application entry point.
// Canvas supplies synthetic camera frames to the actual QrScanner component; no physical-camera claim.
import {ref,nextTick,onBeforeUnmount} from 'vue';
import QrScanner from '../../resources/js/Components/QrScanner.vue';
import {qrPost} from '../../resources/js/qr';
const goal=ref(1),targetType=ref('recurso'),reservation=ref(6),kind=ref('space'),loan=ref(5),condition=ref('ADECUADA');
const mode=ref('valid'),ready=ref(false),busy=ref(false),message=ref('Preparar el código antes de iniciar la cámara simulada.'),stats=ref(null);
let decodeError='', canvas=null, frame=null, sourceImage=null, streams=[], urls=[],requests=[];
const originalCamera=navigator.mediaDevices?.getUserMedia?.bind(navigator.mediaDevices), originalFetch=window.fetch.bind(window);
const originalConsole={log:console.log,warn:console.warn,error:console.error}; let consoleMessages=[];
for(const level of Object.keys(originalConsole)) console[level]=(...args)=>{consoleMessages.push(args.map(v=>typeof v==='string'?v:'[object]').join(' '));originalConsole[level](...args);};
window.fetch=(url,options={})=>{let keys=[];if(options.body){try{keys=Object.keys(JSON.parse(options.body));}catch{keys=['non-json'];}}requests.push({url:String(url),method:options.method||'GET',keys});return originalFetch(url,options);};
function resetImage(){if(frame)cancelAnimationFrame(frame);frame=null;sourceImage=null;canvas=null;for(const u of urls)URL.revokeObjectURL(u);urls=[];}
async function prepare(){
 ready.value=false;stats.value=null;busy.value=true;resetImage();message.value='Preparando datos sintéticos en memoria…';
 try {
  const { BrowserQRCodeReader } = await import('@zxing/browser');
  if(!BrowserQRCodeReader.prototype.qaWrapped){const decode=BrowserQRCodeReader.prototype.decodeFromStream;BrowserQRCodeReader.prototype.decodeFromStream=function(stream,video,callback){return decode.call(this,stream,video,(result,error,controls)=>{decodeError=error?.getKind?.()||error?.name||'';callback(result,error,controls);});};BrowserQRCodeReader.prototype.qaWrapped=true;}
  if(mode.value==='valid'||mode.value==='unknown') {
   let svg;
   if(mode.value==='valid') {const result=await qrPost(`/qr/${targetType.value}/${goal.value}`,{});svg=result.svg;result.svg=null;}
   else {const {BrowserQRCodeSvgWriter}=await import('@zxing/library');const bytes=crypto.getRandomValues(new Uint8Array(32));let token=btoa(String.fromCharCode(...bytes)).replaceAll('+','-').replaceAll('/','_').replace(/=+$/,'');svg=new XMLSerializer().serializeToString(new BrowserQRCodeSvgWriter().write(token,320,320));token=null;bytes.fill(0);}
   const url=URL.createObjectURL(new Blob([svg],{type:'image/svg+xml'}));svg=null;urls.push(url);
   sourceImage=new Image();await new Promise((resolve,reject)=>{sourceImage.onload=resolve;sourceImage.onerror=reject;sourceImage.src=url;});
   canvas=document.createElement('canvas');canvas.width=640;canvas.height=640;
   const paint=()=>{if(!canvas||!sourceImage)return;const c=canvas.getContext('2d');c.fillStyle='white';c.fillRect(0,0,640,640);c.save();c.translate(320,320);c.rotate(Math.sin(performance.now()/250)*0.06);const size=460+Math.sin(performance.now()/170)*40;c.drawImage(sourceImage,-size/2,-size/2,size,size);c.restore();frame=requestAnimationFrame(paint);};paint();
  }
  navigator.mediaDevices.getUserMedia=async()=>{
   if(mode.value==='denied')throw new DOMException('Synthetic denial','NotAllowedError');
   if(mode.value==='absent')throw new DOMException('Synthetic absence','NotFoundError');
   if(mode.value==='blank'){canvas=document.createElement('canvas');canvas.width=640;canvas.height=480;canvas.getContext('2d').fillRect(0,0,640,480);}
   const stream=canvas.captureStream(15);streams.push(stream);return stream;
  };
  ready.value=true;message.value='Cámara simulada preparada. Use el componente real inferior.';
 } catch { message.value='No se pudo preparar. Inicie sesión administrativa en la aplicación local.'; }
 finally {busy.value=false;}
}
async function decoded(payload){
 busy.value=true;const start=performance.now();
 const persisted=[location.href,JSON.stringify(localStorage),JSON.stringify(sessionStorage),JSON.stringify(history.state),...consoleMessages].some(v=>v?.includes(payload));
 const body={qr_token:payload};payload=null;
 const url=kind.value==='space'?`/reservas/${reservation.value}/qr`:`/prestamos/${loan.value}/qr/${kind.value==='delivery'?'entregar':'devolver'}`;
 if(kind.value==='delivery')Object.assign(body,{condicion_entrega:'Inspección física sintética de QA',equipo_verificado:true,receptor_verificado:true});
 if(kind.value==='return')Object.assign(body,{condicion:condition.value,descripcion_condicion:'Condición sintética de QA'});
 try {const result=await qrPost(url,body);message.value=result.validacion.resultado+' · '+result.message;await nextTick();stats.value={elapsedMs:Math.round(performance.now()-start),persistedPayload:persisted,tracksEnded:streams.every(s=>s.getTracks().every(t=>t.readyState==='ended')),requests:requests.slice(-2).map(r=>({method:r.method,url:r.url,keys:r.keys})),totalRequests:requests.length,validationId:result.validacion.id};}
 catch {message.value='La operación no se confirmó.';}
 finally {resetImage();busy.value=false;ready.value=false;}
}
function inspect(){stats.value={decodeError,tracksEnded:streams.every(s=>s.getTracks().every(t=>t.readyState==='ended')),activeTracks:streams.flatMap(s=>s.getTracks()).filter(t=>t.readyState==='live').length,requests:requests.slice(-2).map(r=>({method:r.method,url:r.url,keys:r.keys})),totalRequests:requests.length};}
onBeforeUnmount(()=>{resetImage();streams.forEach(s=>s.getTracks().forEach(t=>t.stop()));if(originalCamera)navigator.mediaDevices.getUserMedia=originalCamera;window.fetch=originalFetch;Object.assign(console,originalConsole);consoleMessages=[];});
</script>
<template><main><h1>Prueba aislada del escáner QR</h1><p>Solo desarrollo local. Frames sintéticos en memoria; se ejecuta QrScanner.vue y el backend autenticado. No sustituye la prueba física.</p>
<label>Recurso sintético <input type="number" v-model="goal" min="1"></label><label>Reserva sintética <input type="number" v-model="reservation" min="1"></label><label>Préstamo sintético <input type="number" v-model="loan" min="1"></label>
<label>Tipo de objetivo <select v-model="targetType"><option value="recurso">Recurso</option><option value="reserva">Reserva</option><option value="prestamo">Préstamo</option></select></label>
<label>Operación <select v-model="kind"><option value="space">Uso de espacio</option><option value="delivery">Entrega</option><option value="return">Devolución</option></select></label>
<label>Condición <select v-model="condition"><option>ADECUADA</option><option>CON_INCIDENCIA</option><option>NO_DETERMINABLE</option></select></label>
<label>Fuente de cámara <select v-model="mode"><option value="valid">QR generado por el servidor</option><option value="unknown">QR desconocido sintético</option><option value="denied">Permiso denegado</option><option value="absent">Cámara ausente</option><option value="blank">Frame ilegible</option></select></label>
<button @click="prepare" :disabled="busy">Preparar simulación</button><p role="status">{{message}}</p>
<QrScanner v-if="ready" :disabled="busy" @decoded="decoded"/>
<button v-if="ready" @click="ready=false">Desmontar escáner</button>
<button @click="inspect">Inspeccionar estado seguro</button><pre v-if="stats">{{JSON.stringify(stats,null,2)}}</pre>
</main></template>
<style>body{font:16px system-ui;margin:24px;background:#f5f7fc;color:#162137}main{max-width:900px;margin:auto}label{display:block;margin:12px 0}input,select,button{font:inherit;padding:8px;margin:4px;border:1px solid #9ba9bc;border-radius:6px}button{cursor:pointer}video{width:min(100%,440px);height:auto}pre{white-space:pre-wrap;overflow-wrap:anywhere;background:white;padding:18px}p{line-height:1.6}</style>
