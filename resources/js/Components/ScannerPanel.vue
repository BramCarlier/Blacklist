<script setup>
import { ref, onBeforeUnmount } from 'vue';
const emit = defineEmits(['scanned']);
const video = ref(null); const message = ref('Use the rear camera to scan QR/barcode ID payloads, or tap NFC.'); let stream=null; let raf=null;
function parsePayload(text){
  let payload={ text, raw:text };
  try { const j=JSON.parse(text); payload={...payload, documentNumber:j.id_document_number||j.documentNumber||j.id||'', firstName:j.first_name||j.firstName||'', lastName:j.last_name||j.lastName||'', birthDate:j.birth_date||j.birthDate||'', nationality:j.nationality||''}; } catch {}
  return payload;
}
async function startCamera(){
  if(!('BarcodeDetector' in window)){ message.value='BarcodeDetector is not available on this browser. Use manual input or a QR scanner keyboard app.'; return; }
  stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}, audio:false}); video.value.srcObject=stream; await video.value.play();
  const detector = new BarcodeDetector({formats:['qr_code','pdf417','aztec','code_128','data_matrix']}); message.value='Scanning…';
  const tick=async()=>{ try { const codes=await detector.detect(video.value); if(codes.length){ const raw=codes[0].rawValue; emit('scanned', parsePayload(raw), 'camera'); message.value='Scan captured.'; stopCamera(); return; } } catch(e){ message.value=e.message; } raf=requestAnimationFrame(tick);}; tick();
}
function stopCamera(){ if(raf) cancelAnimationFrame(raf); if(stream){ stream.getTracks().forEach(t=>t.stop()); stream=null; } }
async function scanNfc(){
  if(!('NDEFReader' in window)){ message.value='Web NFC is not available. On Android, use Chrome over HTTPS and a supported NDEF tag.'; return; }
  try { const reader = new NDEFReader(); await reader.scan(); message.value='Hold an NFC tag/card near the phone.'; reader.onreading = ev => { const records=[...ev.message.records]; const text=records.map(r=> new TextDecoder(r.encoding || 'utf-8').decode(r.data)).join('\n'); emit('scanned', parsePayload(text || ev.serialNumber), 'nfc'); message.value='NFC captured.'; }; } catch(e){ message.value=e.message; }
}
onBeforeUnmount(stopCamera);
</script>
<template><div class="card"><h2 class="mb-2 text-xl font-semibold">Scan input</h2><p class="mb-4 text-sm text-slate-400">{{ message }}</p><video ref="video" class="mb-3 aspect-video w-full rounded-xl bg-black" playsinline muted></video><div class="grid gap-2 sm:grid-cols-2"><button class="btn btn-primary" @click="startCamera">Start camera scan</button><button class="btn btn-muted" @click="scanNfc">Read NFC tag</button><button class="btn btn-muted sm:col-span-2" @click="stopCamera">Stop camera</button></div><div class="mt-4 rounded-xl bg-slate-950 p-3 text-xs text-slate-400">Best scanner format: QR containing JSON like {"firstName":"Ada","lastName":"Lovelace","documentNumber":"ABC123"}.</div></div></template>
