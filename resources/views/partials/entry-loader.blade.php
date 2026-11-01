@if(!($designPreview ?? false))
<style>
.rdsa-entry-loader[hidden]{display:none!important}.rdsa-entry-loader{position:fixed;inset:0;z-index:99999;display:grid;place-items:center;background:#080d1a;color:#f3f5fa;text-align:center;font-family:Arial,sans-serif;transition:opacity .25s ease}.rdsa-entry-loader.is-leaving{opacity:0;pointer-events:none}.rdsa-entry-loader-inner{padding:30px}.rdsa-entry-loader-mark{display:grid;place-items:center;width:82px;height:82px;margin:0 auto 22px;border:1px solid rgba(214,181,104,.4);border-radius:26px;color:#d6b568;font-size:19px;letter-spacing:.12em;position:relative}.rdsa-entry-loader-mark:after{content:'';position:absolute;inset:-9px;border:2px solid transparent;border-top-color:#d6b568;border-radius:32px;animation:rdsa-entry-spin 1.2s linear infinite}.rdsa-entry-loader p{font-size:14px;line-height:1.6;margin:0 0 22px}.rdsa-entry-loader button{background:transparent;color:#b4bfd6;border:1px solid rgba(255,255,255,.2);border-radius:24px;padding:10px 18px;font:inherit;font-size:12px;cursor:pointer}.rdsa-entry-loader button:focus-visible{outline:2px solid #d6b568;outline-offset:5px}@keyframes rdsa-entry-spin{to{transform:rotate(360deg)}}@media(prefers-reduced-motion:reduce){.rdsa-entry-loader{transition:none}.rdsa-entry-loader-mark:after{animation:none}}
</style>
<div class="rdsa-entry-loader" data-entry-loader hidden><div class="rdsa-entry-loader-inner"><div class="rdsa-entry-loader-mark" aria-hidden="true">RDSA</div><p role="status">{{ $entryLabel ?? 'Bienvenue à la RDSA' }}</p><button type="button" data-entry-continue>Accéder maintenant</button></div></div>
<script>
(function(){
 const panel=document.querySelector('[data-entry-loader]');
 let finished=false,timer;
 function finish(){if(finished)return;finished=true;clearTimeout(timer);panel.classList.add('is-leaving');setTimeout(function(){panel.hidden=true;document.documentElement.dataset.rdsaBootReady='true';window.dispatchEvent(new Event('rdsa:boot-ready'))},250)}
 panel.hidden=false;
 panel.querySelector('button').addEventListener('click',finish);
 document.addEventListener('DOMContentLoaded',finish,{once:true});
 window.addEventListener('pageshow',finish,{once:true});
 timer=setTimeout(finish,4000);
})();
</script>
@endif
