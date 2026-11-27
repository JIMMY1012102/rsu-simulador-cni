<div class="cni-asistente" data-asistente>
<style>
.cni-asistente{position:fixed;right:24px;bottom:24px;z-index:10000;font-family:inherit}.cni-asistente__abrir{border:0;border-radius:999px;padding:13px 18px;background:#173b66;color:#fff;font-weight:700;cursor:pointer;box-shadow:0 5px 20px #0003}.cni-asistente__ventana{display:none;position:absolute;right:0;bottom:62px;width:min(360px,calc(100vw - 32px));height:min(500px,calc(100vh - 120px));background:#fff;color:#172033;border:1px solid #dbe2ea;border-radius:16px;box-shadow:0 12px 40px #0003;overflow:hidden;flex-direction:column}.cni-asistente.is-abierto .cni-asistente__ventana{display:flex}.cni-asistente__cabecera{padding:14px 16px;background:#173b66;color:#fff;display:flex;justify-content:space-between}.cni-asistente__mensajes{padding:14px;overflow:auto;flex:1;display:flex;flex-direction:column;gap:10px}.cni-asistente__mensaje{padding:10px 12px;border-radius:12px;background:#f0f3f7;white-space:pre-wrap;overflow-wrap:anywhere;max-width:90%;font-size:14px}.cni-asistente__mensaje--usuario{align-self:flex-end;background:#e0edff}.cni-asistente__form{display:flex;gap:8px;padding:12px;border-top:1px solid #e5eaf0}.cni-asistente__entrada{min-width:0;flex:1;border:1px solid #cbd5e1;border-radius:9px;padding:10px}.cni-asistente__enviar{border:0;border-radius:9px;padding:0 14px;background:#173b66;color:#fff}.cni-asistente button:focus-visible,.cni-asistente input:focus-visible{outline:3px solid #70a9ed;outline-offset:2px}@media(max-width:480px){.cni-asistente{right:12px;bottom:12px}}
</style>
<section class="cni-asistente__ventana" role="dialog" aria-label="Asistente de inteligencia artificial">
<header class="cni-asistente__cabecera"><strong>Asistente CNI</strong><button type="button" data-cerrar aria-label="Cerrar chat">×</button></header>
<div class="cni-asistente__mensajes" data-mensajes aria-live="polite"><div class="cni-asistente__mensaje">¡Hola! Soy tu asistente de inteligencia artificial. ¿En qué puedo ayudarte?</div></div>
<form class="cni-asistente__form" data-form><input class="cni-asistente__entrada" name="mensaje" maxlength="500" required placeholder="Escribe tu pregunta…" autocomplete="off"><button class="cni-asistente__enviar" type="submit">Enviar</button></form>
</section>
<button class="cni-asistente__abrir" type="button" data-abrir aria-expanded="false">✦ Asistente IA</button>
<script>
(() => {
const root=document.currentScript.closest('[data-asistente]');if(!root||root.dataset.inicializado)return;root.dataset.inicializado='1';
const open=root.querySelector('[data-abrir]'),close=root.querySelector('[data-cerrar]'),messages=root.querySelector('[data-mensajes]'),form=root.querySelector('[data-form]'),input=form.querySelector('input'),send=form.querySelector('button');
const session=window.crypto?.randomUUID?crypto.randomUUID():String(Date.now());
const add=(value,kind='')=>{const el=document.createElement('div');el.className='cni-asistente__mensaje'+(kind?' cni-asistente__mensaje--'+kind:'');el.textContent=value;messages.appendChild(el);messages.scrollTop=messages.scrollHeight;};
open.addEventListener('click',()=>{root.classList.add('is-abierto');open.setAttribute('aria-expanded','true');input.focus();});
close.addEventListener('click',()=>{root.classList.remove('is-abierto');open.setAttribute('aria-expanded','false');open.focus();});
form.addEventListener('submit',async event=>{event.preventDefault();const mensaje=input.value.trim();if(!mensaje||mensaje.length>500)return;add(mensaje,'usuario');input.value='';input.disabled=send.disabled=true;const wait=document.createElement('div');wait.className='cni-asistente__mensaje';wait.textContent='Consultando…';messages.appendChild(wait);
try{const response=await fetch(@json(route('asistente.chat')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({mensaje,sesion:session})});const data=await response.json();wait.remove();add(data.respuesta||'El asistente no está disponible en este momento.');}
catch(_){wait.remove();add('El asistente no está disponible en este momento. Intente de nuevo en un minuto.');}
finally{input.disabled=send.disabled=false;input.focus();}});
})();
</script>
</div>
