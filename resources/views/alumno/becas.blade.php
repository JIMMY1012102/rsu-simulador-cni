<!doctype html><html lang="es"><head><meta name="csrf-token" content="{{ csrf_token() }}"><meta charset=utf8><meta name=viewport content="width=device-width,initial-scale=1,viewport-fit=cover"><style>:root{color-scheme:light;box-sizing:border-box;padding-top:env(safe-area-inset-top,0px);padding-bottom:env(safe-area-inset-bottom,0px)}html{scroll-padding-top:env(safe-area-inset-top,0px)}body{margin:0;padding:0;font:14px -apple-system,BlinkMacSystemFont,sans-serif;background:#fff;color:#000}img{max-width:100%}[hidden]:not([hidden=until-found i]){display:none!important}</style></head><body>
<title>Misión Beca CNI</title>
<meta name="claude:viewport-fit-sides" content="cover">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700&family=Unbounded:wght@700;900&display=swap" rel="stylesheet">
<style>
/* Concept: arcade-cinematic mission. A pinned unlock sequence, two mission chapters with sticky titles, then a family strip and a closing marquee. Dark-first single look. */
:root{
  --ink:#070b1f; --ink-2:#0c1332; --ink-3:#131c47; --line:rgba(160,184,255,.18);
  --text:#f3f6ff; --muted:#aab7dd; --blue:#2f63f0; --blue-soft:#8fb0ff;
  --gold:#ffc83d; --fire:#ff6a2b; --green:#2fd37a;
  --accent:var(--blue-soft);
  --display:'Unbounded','Arial Black',system-ui,sans-serif;
  --body:'Noto Sans',system-ui,-apple-system,'Segoe UI',sans-serif;
  color-scheme:dark;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth;overflow-x:clip}
body{background:var(--ink);color:var(--text);font-family:var(--body);font-size:16px;line-height:1.6;overflow-x:clip}
a{color:inherit;text-decoration:none}
:focus-visible{outline:3px solid var(--gold);outline-offset:3px;border-radius:6px}
.wrap{width:100%;max-width:1240px;margin-inline:auto;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}
h1,h2,h3,p{margin:0}
.sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}

/* HUD */
.hud{position:fixed;top:0;left:0;right:0;z-index:50;padding-top:calc(env(safe-area-inset-top,0px) + 10px);padding-bottom:10px;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px));background:rgba(7,11,31,.74);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid var(--line)}
.hud-in{max-width:1240px;margin-inline:auto;display:flex;align-items:center;justify-content:space-between;gap:16px}
.brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:.95rem}
.brand-mark{width:32px;height:32px;border-radius:9px;background:var(--blue);display:grid;place-items:center}
.xp{display:flex;align-items:center;gap:12px;font-size:.85rem;font-weight:600;color:var(--muted)}
.xp b{color:var(--gold);font-variant-numeric:tabular-nums}
.xp-bar{width:clamp(80px,16vw,180px);height:8px;border-radius:99px;background:rgba(255,255,255,.14);overflow:hidden}
.xp-bar i{display:block;height:100%;background:var(--gold);transform-origin:left;transform:scaleX(0)}
.who{color:var(--text)}

/* HERO */
.hero{position:relative;min-height:100svh;display:flex;overflow:hidden;isolation:isolate;background:radial-gradient(120% 90% at 70% 10%,#14246f 0%,var(--ink) 60%)}
.hero-grid{position:absolute;inset:-10%;z-index:-3;background-image:linear-gradient(var(--line) 1px,transparent 1px),linear-gradient(90deg,var(--line) 1px,transparent 1px);background-size:64px 64px;-webkit-mask-image:radial-gradient(ellipse at 65% 35%,#000 0,transparent 70%);mask-image:radial-gradient(ellipse at 65% 35%,#000 0,transparent 70%)}
.orb{position:absolute;border-radius:50%;filter:blur(70px);z-index:-2;pointer-events:none}
.orb.a{width:46vw;height:46vw;background:var(--blue);opacity:.5;right:-8vw;top:-6vw}
.orb.b{width:30vw;height:30vw;background:var(--fire);opacity:.2;left:-10vw;bottom:8vh}
.ghost{position:absolute;font-family:var(--display);font-weight:900;line-height:.8;color:transparent;-webkit-text-stroke:2px rgba(143,176,255,.16);pointer-events:none;user-select:none;white-space:nowrap;z-index:-1}
.hero .ghost{font-size:34vw;left:-2vw;top:9vh}
.hero-wrap{position:relative;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);grid-template-rows:auto auto auto;align-content:end;column-gap:24px;row-gap:20px;padding-top:calc(env(safe-area-inset-top,0px) + clamp(84px,13vh,130px));padding-bottom:clamp(28px,6vh,64px)}
.hero-art{grid-column:2;grid-row:1;justify-self:end;position:relative;width:min(100%,560px,calc(40vh * 1.27));margin-bottom:-4vh}
.art-layer{position:relative}
.art-layer svg{display:block;width:100%;height:auto;filter:drop-shadow(0 30px 40px rgba(0,0,0,.45))}
.art-layer.cap{position:absolute;width:24%;left:-2%;top:-10%}
.chip-wrap{position:absolute;z-index:2}
.chip{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:999px;font-weight:700;font-size:.95rem;white-space:nowrap;box-shadow:0 12px 30px rgba(0,0,0,.35)}
.chip-gold{background:var(--gold);color:var(--ink)}
.chip-glass{background:rgba(19,28,71,.85);border:1px solid var(--line);color:var(--text);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}
.chip-blue{background:var(--blue);color:#fff}
.c1{top:2%;right:-2%}.c2{left:-6%;top:34%}.c3{right:-4%;bottom:20%}.c4{left:8%;bottom:2%}
.hero-title{grid-column:1 / -1;grid-row:2;position:relative;z-index:3;font-family:var(--display);font-weight:900;letter-spacing:-.02em;line-height:1}
.hero-title .l1{display:block;font-size:clamp(2.3rem,9.4vw,8.2rem);white-space:nowrap}
.ch{display:inline-block;will-change:transform}
.hero-title .l2{display:flex;align-items:center;gap:.25em;font-size:clamp(1.5rem,5.2vw,4.4rem);color:var(--gold);margin-top:.12em}
.slot{display:block;height:1.15em;overflow:hidden}
.slot-track{display:block;animation:slot 9s cubic-bezier(.7,0,.2,1) infinite}
.slot-track span{display:block;height:1.15em;line-height:1.15;white-space:nowrap}
@keyframes slot{0%,18%{transform:translateY(0)}25%,43%{transform:translateY(-1.15em)}50%,68%{transform:translateY(-2.3em)}75%,93%{transform:translateY(-3.45em)}100%{transform:translateY(-4.6em)}}
.hero-copy{grid-column:1 / -1;grid-row:3;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:20px 40px;position:relative;z-index:3}
.hero-copy p{max-width:46ch;color:var(--muted);font-size:clamp(1rem,1.6vw,1.2rem)}
.btns{display:flex;flex-wrap:wrap;gap:12px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:48px;padding:0 24px;border-radius:999px;font-weight:700;font-size:1rem;border:1.5px solid transparent;transition:transform .2s ease,filter .2s ease}
.btn{font-family:inherit;cursor:pointer}.btns form{margin:0}
.btn:hover{transform:translateY(-2px);filter:brightness(1.08)}
.btn-gold{background:var(--gold);color:var(--ink)}
.btn-blue{background:var(--blue);color:#fff}
.btn-fire{background:var(--fire);color:var(--ink)}
.btn-green{background:var(--green);color:var(--ink)}
.btn-ghost{border-color:var(--line);color:var(--text);background:rgba(255,255,255,.04)}
.cue{position:absolute;right:max(16px,env(safe-area-inset-right,0px));bottom:18px;display:flex;flex-direction:column;align-items:center;gap:8px;font-size:.8rem;color:var(--muted);z-index:3}
.cue i{display:block;width:2px;height:44px;background:linear-gradient(var(--gold),transparent);animation:cue 1.8s ease-in-out infinite;transform-origin:top}
@keyframes cue{0%{transform:scaleY(0)}60%{transform:scaleY(1)}100%{transform:scaleY(1);opacity:0}}

/* Chapter basics */
.chapter{position:relative;padding-block:clamp(80px,14vh,160px);overflow:clip}
.chapter+.chapter{border-top:1px solid var(--line)}
.chapter .ghost{font-size:clamp(14rem,36vw,34rem);right:-4vw;top:6vh}
.chap-tag{font-weight:700;font-size:.95rem;color:var(--accent)}
.title-xl{font-family:var(--display);font-weight:900;font-size:clamp(2.6rem,6.4vw,5.2rem);line-height:.98;letter-spacing:-.02em;text-wrap:balance}
.kw{display:inline-block;overflow:hidden;vertical-align:top;padding-bottom:.1em;margin-right:.22em}
.kw>span{display:inline-block}
.split{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:clamp(28px,5vw,72px);align-items:start}
.sticky{position:sticky;top:calc(env(safe-area-inset-top,0px) + 90px);display:flex;flex-direction:column;gap:20px}
.lead{color:var(--muted);font-size:1.1rem;max-width:40ch}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.pill{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:999px;background:rgba(255,255,255,.07);border:1px solid var(--line);font-weight:600;font-size:.9rem}
.pill b{color:var(--gold)}
.req{display:flex;gap:16px;align-items:flex-start;padding-block:24px;border-bottom:1px solid var(--line);font-size:clamp(1.15rem,2.3vw,1.6rem);font-weight:600;line-height:1.35}
.req:first-child{padding-top:0}
.req svg{flex:0 0 auto;width:32px;height:32px;margin-top:2px}
.req small{display:block;margin-top:6px;font-size:.95rem;font-weight:400;color:var(--muted)}
.panel{background:var(--ink-3);border:1px solid var(--line);border-radius:26px;padding:clamp(20px,3vw,32px);display:grid;gap:14px;margin-top:32px}
.panel h3{font-family:var(--display);font-weight:700;font-size:1.15rem}
.panel p{color:var(--muted)}
.panel .num{color:var(--text);font-weight:700;font-variant-numeric:tabular-nums}
.note{font-size:.88rem;color:var(--muted);line-height:1.55}
.bcp{--accent:var(--fire);background:linear-gradient(180deg,var(--ink),#120b1f)}
.bcp .pill b{color:var(--fire)}

/* Unlock (scrollytelling) */
.unlock{position:relative;background:linear-gradient(180deg,var(--ink-2),var(--ink))}
.unlock-head{padding-block:clamp(72px,12vh,130px) 32px;display:grid;gap:16px}
.unlock-head .title-xl{max-width:14ch}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,460px),1fr));gap:20px;padding-bottom:clamp(64px,10vh,120px)}
.step{display:grid;grid-template-columns:minmax(0,.8fr) minmax(0,1.2fr);gap:clamp(20px,4vw,48px);align-items:center;background:var(--ink-3);border:1px solid var(--line);border-radius:28px;padding:clamp(20px,3vw,32px)}
.step-art{aspect-ratio:1;width:100%;max-width:380px;border-radius:32px;background:radial-gradient(circle at 30% 20%,rgba(143,176,255,.4),transparent 62%),var(--ink-2);border:1px solid var(--line);display:grid;place-items:center;padding:12%}
.step-art--photo{padding:0;overflow:hidden;aspect-ratio:4/3;background:#fff}.step-art--photo img{display:block;width:100%;height:100%;object-fit:contain}.step-art svg{width:100%;height:auto;display:block}
.step-tag{font-weight:700;color:var(--gold);font-size:.95rem}
.step-word{font-family:var(--display);font-weight:900;font-size:clamp(2.1rem,7vw,5rem);line-height:1.05;letter-spacing:-.02em;overflow:hidden;padding-block:.06em}
.step-word .wi{display:inline-block}
.step-text{color:var(--muted);font-size:clamp(1rem,1.5vw,1.2rem);max-width:36ch;margin-top:12px}
.stage-label,.unlock-bar{display:none}
.js-anim .unlock-stage{position:relative;height:100svh;overflow:hidden}
.js-anim .steps{position:absolute;inset:0;display:block;padding:0}
.js-anim .step{position:absolute;top:0;bottom:0;left:0;right:0;margin-inline:auto;max-width:1240px;width:100%;background:none;border:0;border-radius:0;align-content:center;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}
.js-anim .step:not(:first-child){opacity:0}
.js-anim .stage-label{display:block;position:absolute;left:0;right:0;top:calc(env(safe-area-inset-top,0px) + 84px);margin-inline:auto;max-width:1240px;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px));font-weight:700;color:var(--muted);z-index:2}
.js-anim .stage-label b{color:var(--gold);font-variant-numeric:tabular-nums}
.js-anim .unlock-bar{display:block;position:absolute;left:max(16px,env(safe-area-inset-left,0px));right:max(16px,env(safe-area-inset-right,0px));bottom:calc(env(safe-area-inset-bottom,0px) + 28px);max-width:1208px;margin-inline:auto;height:6px;border-radius:99px;background:rgba(255,255,255,.14);overflow:hidden}
.unlock-bar b{display:block;height:100%;background:var(--gold);transform-origin:left;transform:scaleX(.25)}

/* BCP route */
.route{position:relative;padding-left:56px;display:grid;gap:36px}
.route-line{position:absolute;left:19px;top:10px;bottom:10px;width:4px;border-radius:4px;background:rgba(255,255,255,.14);overflow:hidden}
.route-line i{display:block;width:100%;height:100%;background:var(--fire);transform-origin:top;transform:scaleY(1)}
.js-anim .route-line i{transform:scaleY(0)}
.node{position:relative}
.node::before{content:"";position:absolute;left:-56px;top:2px;width:42px;height:42px;border-radius:50%;background:var(--ink);border:3px solid var(--fire);box-shadow:0 0 0 6px var(--ink)}
.node.on::before{background:var(--fire)}
.node .n{position:absolute;left:-56px;top:2px;width:42px;height:42px;display:grid;place-items:center;font-family:var(--display);font-weight:900;font-size:1rem;color:var(--fire)}
.node.on .n{color:var(--ink)}
.node h3{font-family:var(--display);font-weight:700;font-size:clamp(1.2rem,2.4vw,1.7rem);line-height:1.2;padding-top:6px}
.node p{color:var(--muted);margin-top:8px;max-width:44ch}

/* Marquee */
.marquee{overflow:hidden;white-space:nowrap}
.marquee-track{display:inline-flex;gap:20px;padding-right:20px;animation:mq 34s linear infinite;will-change:transform}
.js-anim .marquee-track{animation:none}
@keyframes mq{to{transform:translateX(-50%)}}
.uni{display:inline-flex;align-items:center;padding:14px 28px;border-radius:999px;border:1.5px solid var(--line);font-family:var(--display);font-weight:700;font-size:1.25rem;background:rgba(255,255,255,.04)}
.unis{margin-top:56px}
.unis-h{margin-bottom:16px}

/* Parents */
.family{padding-block:clamp(80px,14vh,150px);background:linear-gradient(180deg,#0a1a1b,var(--ink));border-top:1px solid var(--line)}
.family-in{display:grid;gap:32px;justify-items:start}
.family .chap-tag{color:var(--green)}
.big-p{font-family:var(--display);font-weight:700;font-size:clamp(1.5rem,3.8vw,3rem);line-height:1.25;letter-spacing:-.01em;max-width:24ch}
.w{display:inline-block;margin-right:.28em}
.sharebox{width:100%;max-width:720px;background:var(--ink-3);border:1px solid var(--line);border-radius:22px;padding:20px 22px;display:grid;gap:14px}
.sharebox p{font-size:.98rem;color:var(--text);line-height:1.55;word-break:break-word}
.share-row{display:flex;flex-wrap:wrap;align-items:center;gap:14px}
.status{font-size:.9rem;color:var(--green);min-height:1.4em}

/* Finale */
.finale{padding-block:clamp(72px,12vh,140px) 40px;border-top:1px solid var(--line);overflow:clip}
.big-mq{font-family:var(--display);font-weight:900;font-size:clamp(3rem,12vw,9rem);line-height:1.05;letter-spacing:-.02em}
.big-mq .o{color:transparent;-webkit-text-stroke:2px var(--blue-soft)}
.big-mq .s{color:var(--gold)}
.big-mq .marquee-track{gap:.5em;padding-right:.5em}
.finale-in{display:grid;gap:36px;padding-top:56px}
.finale .title-xl{max-width:12ch}
.quote{display:flex;flex-wrap:wrap;gap:22px;align-items:center;border:1.5px dashed var(--line);border-radius:26px;padding:24px}
.quote .quote-video{flex:1 1 360px;min-width:0;display:block}.quote-video iframe{display:block;width:100%;aspect-ratio:16/9;border:0;border-radius:16px;background:#000}.quote-video>a{display:inline-block;margin-top:8px;color:var(--blue-soft);font-size:.9rem;text-decoration:underline}.quote .ph{width:84px;height:84px;border-radius:50%;border:2px dashed var(--blue-soft);display:grid;place-items:center;font-size:.8rem;font-weight:700;color:var(--blue-soft);text-align:center}
.quote q{display:block;font-family:var(--display);font-weight:700;font-size:clamp(1.05rem,2vw,1.4rem);line-height:1.35;quotes:none}
.quote div{flex:1 1 280px;display:grid;gap:6px}
.quote span{color:var(--muted);font-size:.9rem}
.foot{padding-block:40px 56px;border-top:1px solid var(--line);margin-top:56px;display:grid;gap:10px}
.foot a{color:var(--blue-soft);text-decoration:underline;text-underline-offset:3px}
.foot ul{margin:0;padding-left:1.1em;display:grid;gap:4px}

@media (max-width:900px){
  .hero-wrap{grid-template-columns:minmax(0,1fr)}
  .hero-art{grid-column:1;grid-row:1;justify-self:center;width:min(78vw,380px);margin-bottom:0}
  .c1{right:-6%}.c2{left:-8%}.c3{right:-8%}
  .chip{font-size:.8rem;padding:8px 12px}
  .split{grid-template-columns:minmax(0,1fr)}
  .sticky{position:static}
  .cue{display:none}
  .step{grid-template-columns:minmax(0,1fr)}
  .step-art{max-width:150px;border-radius:24px}
  .js-anim .step{gap:12px}
}
@media (max-width:480px){.xp .who,.brand span{display:none}}
@media (prefers-reduced-motion:reduce){
  html{scroll-behavior:auto}
  *{animation:none!important;transition:none!important}
}
</style>

<header class="hud">
  <div class="hud-in">
    <div class="brand">
      <div class="brand-mark" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6z"/></svg></div>
      <a href="{{ route('alumno.panel') }}">← Volver al panel</a>
    </div>
    <div class="xp" aria-label="Tu avance en la misión">
      <span class="who">{{ $usuario->nombres }}</span>
      <span id="lvl">Nivel 1</span>
      <div class="xp-bar" aria-hidden="true"><i id="xpfill"></i></div>
      <span><b id="xpnum">0</b> XP</span>
    </div>
  </div>
</header>

<main>
  <!-- HERO -->
  <section class="hero" aria-labelledby="hero-title" data-parallax-host data-start="top top">
    <div class="hero-grid" aria-hidden="true"></div>
    <div class="orb a" data-speed=".25" aria-hidden="true"></div>
    <div class="orb b" data-speed=".5" aria-hidden="true"></div>
    <div class="ghost" data-speed=".18" aria-hidden="true">BECA</div>

    <div class="wrap hero-wrap">
      <div class="hero-art">
        <div class="art-layer" data-speed=".1">
          <div class="mx" data-mouse="-16">
            <svg viewBox="0 0 560 440" role="img" aria-label="Laptop con el simulador abierto mostrando tu avance">
              <defs>
                <linearGradient id="gScreen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#16246b"/><stop offset="1" stop-color="#2a52e0"/></linearGradient>
                <linearGradient id="gBase" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2a3a8f"/><stop offset="1" stop-color="#121a52"/></linearGradient>
              </defs>
              <ellipse cx="280" cy="418" rx="236" ry="14" fill="#000" opacity=".35"/>
              <path d="M26 372H534L504 404Q500 412 490 412H70Q60 412 56 404Z" fill="url(#gBase)"/>
              <rect x="228" y="372" width="104" height="10" rx="5" fill="#0b1240"/>
              <rect x="70" y="30" width="420" height="346" rx="20" fill="#0e1649" stroke="#6f8dff" stroke-opacity=".7" stroke-width="3"/>
              <rect x="88" y="48" width="384" height="310" rx="10" fill="url(#gScreen)"/>
              <circle cx="108" cy="68" r="5" fill="#ff6a6a"/><circle cx="126" cy="68" r="5" fill="#ffc83d"/><circle cx="144" cy="68" r="5" fill="#46d17a"/>
              <rect x="170" y="60" width="150" height="16" rx="8" fill="#fff" opacity=".14"/>
              <text x="108" y="124" font-family="Unbounded, Arial Black, sans-serif" font-weight="900" font-size="26" fill="#fff">Misión Beca</text>
              <text x="108" y="148" font-family="'Noto Sans', sans-serif" font-weight="600" font-size="13" fill="#dbe6ff">5.º de secundaria · Área C</text>
              <rect x="108" y="170" width="344" height="14" rx="7" fill="#fff" opacity=".18"/>
              <rect x="108" y="170" width="236" height="14" rx="7" fill="#ffc83d"/>
              <text x="108" y="206" font-family="'Noto Sans', sans-serif" font-weight="700" font-size="13" fill="#fff">68 / 100 XP</text>
              <rect x="108" y="296" width="38" height="44" rx="6" fill="#fff" opacity=".28"/>
              <rect x="160" y="268" width="38" height="72" rx="6" fill="#fff" opacity=".4"/>
              <rect x="212" y="240" width="38" height="100" rx="6" fill="#fff" opacity=".55"/>
              <rect x="264" y="212" width="38" height="128" rx="6" fill="#ffc83d"/>
              <circle cx="396" cy="288" r="40" fill="#2fd37a"/>
              <path d="M376 288l14 14 28-30" fill="none" stroke="#06210f" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
        <div class="art-layer cap" data-speed="-.18">
          <div class="mx" data-mouse="26">
            <svg viewBox="0 0 120 100" aria-hidden="true"><path d="M60 8L116 36L60 64L4 36Z" fill="#ffc83d"/><path d="M26 52V74C26 84 42 92 60 92C78 92 94 84 94 74V52L60 70Z" fill="#e0a516"/><path d="M110 40V74" stroke="#ffc83d" stroke-width="5"/><circle cx="110" cy="80" r="6" fill="#ffc83d"/></svg>
          </div>
        </div>
        <div class="chip-wrap c1" data-speed="-.3"><div class="chip chip-gold" data-mouse="34">+20 XP</div></div>
        <div class="chip-wrap c2" data-speed="-.4"><div class="chip chip-glass" data-mouse="-28"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffc83d" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/></svg>Tu cuarto*</div></div>
        <div class="chip-wrap c3" data-speed="-.25"><div class="chip chip-glass" data-mouse="30"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffc83d" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="3" width="16" height="14" rx="3"/><path d="M4 11h16M7 17v3M17 17v3"/></svg>Pasajes</div></div>
        <div class="chip-wrap c4" data-speed="-.35"><div class="chip chip-blue" data-mouse="-22">Beca 18</div></div>
      </div>

      <h1 class="hero-title" id="hero-title" aria-label="Desbloquea tu laptop, tu cuarto y tu carrera">
        <span class="l1" aria-hidden="true">DESBLOQUEA</span>
        <span class="l2" aria-hidden="true"><span class="slot"><span class="slot-track"><span>TU LAPTOP</span><span>TU CUARTO</span><span>TU CARRERA</span><span>TU FUTURO</span><span>TU LAPTOP</span></span></span></span>
      </h1>

      <div class="hero-copy">
        <p>Dos becas reales que te dan lo necesario para empezar la universidad. Baja y completa la misión.</p>
        <div class="btns">
          <a class="btn btn-gold" href="#desbloqueas">Empezar la misión</a>
          <form method="POST" action="{{ route('examen.independiente') }}" target="_top">@csrf<button class="btn btn-ghost" type="submit">Dar examen</button></form>
        </div>
      </div>
    </div>
    <div class="cue" aria-hidden="true"><span>Baja</span><i></i></div>
  </section>

  <!-- DESBLOQUEAS -->
  <section class="unlock" id="desbloqueas" aria-labelledby="unlock-title">
    <div class="wrap unlock-head">
      <p class="chap-tag" style="color:var(--gold)">Lo que desbloqueas con Beca 18</p>
      <h2 class="title-xl" id="unlock-title">Cuatro premios que sí cambian tu vida</h2>
    </div>
    <div class="unlock-stage">
      <p class="stage-label">Premio <b id="stepnum">1</b> de 4</p>
      <div class="steps">
        <article class="step">
          <div class="step-art step-art--photo"><img src="{{ asset('images/becas/laptop.webp') }}" alt="Laptop ASUS con una imagen de Spider-Man" width="1200" height="900" decoding="async"></div>
          <div>
            <p class="step-tag">Beca 18</p>
            <h3 class="step-word"><span class="wi">LAPTOP</span></h3>
            <p class="step-text">Una computadora portátil o equipo similar para estudiar. Tuya desde el primer día.</p>
          </div>
        </article>
        <article class="step">
          <div class="step-art step-art--photo"><img src="{{ asset('images/becas/cuarto.webp') }}" alt="Habitación con cama individual y escritorio" width="738" height="415" decoding="async"></div>
          <div>
            <p class="step-tag">Beca 18</p>
            <h3 class="step-word"><span class="wi">CUARTO*</span></h3>
            <p class="step-text">Alojamiento para vivir cerca de tu universidad, además de alimentación. *Cuando corresponde según tu caso.</p>
          </div>
        </article>
        <article class="step">
          <div class="step-art step-art--photo"><img src="{{ asset('images/becas/pasajes.webp') }}" alt="Buses universitarios de transporte" width="602" height="332" decoding="async"></div>
          <div>
            <p class="step-tag">Beca 18</p>
            <h3 class="step-word"><span class="wi">PASAJES</span></h3>
            <p class="step-text">Movilidad local para ir a clases, y transporte entre provincias al inicio y al final de la beca.</p>
          </div>
        </article>
        <article class="step">
          <div class="step-art step-art--photo"><img src="{{ asset('images/becas/carrera.webp') }}" alt="Universidades peruanas donde continuar tus estudios" width="721" height="425" decoding="async"></div>
          <div>
            <p class="step-tag">Beca 18</p>
            <h3 class="step-word"><span class="wi">CARRERA</span></h3>
            <p class="step-text">Matrícula y pensión pagadas, más acompañamiento académico y de bienestar mientras estudias.</p>
          </div>
        </article>
      </div>
      <div class="unlock-bar" aria-hidden="true"><b></b></div>
    </div>
  </section>

  <!-- BECA 18 -->
  <section class="chapter" id="beca18" aria-labelledby="b18-title" data-parallax-host>
    <div class="ghost" data-speed=".3" aria-hidden="true">18</div>
    <div class="wrap split">
      <div class="sticky">
        <p class="chap-tag">Misión 01 · Pronabec</p>
        <h2 class="title-xl" id="b18-title" data-kinetic>BECA 18</h2>
        <p class="lead">El Estado paga tu carrera en universidad, instituto o escuela superior. Es un concurso público, y tú compites con tu promedio y tu examen.</p>
        <div class="chips">
          <span class="pill">Postular es gratis</span>
          <span class="pill">Cierra en <b data-deadline="">por confirmar</b></span>
        </div>
        <div class="btns">
          <a class="btn btn-blue" href="https://www.pronabec.gob.pe/beca-18/" target="_blank" rel="noopener noreferrer">Ver cómo postular</a>
        </div>
      </div>
      <div>
        <h3 class="sr">Qué necesitas</h3>
        <div class="req"><svg viewBox="0 0 32 32" fill="none" stroke="#8fb0ff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="16" cy="16" r="13" opacity=".35"/><path class="ck" d="M9 16.5l5 5 9-11"/></svg><div>Ser peruano/a con DNI</div></div>
        <div class="req"><svg viewBox="0 0 32 32" fill="none" stroke="#8fb0ff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="16" cy="16" r="13" opacity=".35"/><path class="ck" d="M9 16.5l5 5 9-11"/></svg><div>Buen rendimiento en tus dos últimos grados<small>En la modalidad Ordinaria, tercio superior de tu promoción.</small></div></div>
        <div class="req"><svg viewBox="0 0 32 32" fill="none" stroke="#8fb0ff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="16" cy="16" r="13" opacity=".35"/><path class="ck" d="M9 16.5l5 5 9-11"/></svg><div>Venir de una familia con pocos recursos, o estar en una situación especial</div></div>
        <div class="req"><svg viewBox="0 0 32 32" fill="none" stroke="#8fb0ff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="16" cy="16" r="13" opacity=".35"/><path class="ck" d="M9 16.5l5 5 9-11"/></svg><div>Rendir el examen nacional de preselección<small>Pronabec publica exámenes de convocatorias pasadas para practicar.</small></div></div>

        <div class="panel">
          <h3>Practica con lo que usa Pronabec</h3>
          <p>El libro "Prepárate" y los exámenes de convocatorias pasadas están en la web de Pronabec. Resuélvelos en el simulador y mide tu avance.</p>
          <p>¿Dudas? Línea gratuita <span class="num">0800 000 18</span> o WhatsApp <span class="num">914 121 106</span>.</p>
          <p class="note">Hay varias modalidades con requisitos distintos. Revisa la tuya en las bases oficiales. Según El Peruano, la inscripción de la convocatoria 2027 llegó hasta el 16 de octubre de 2026: confirma en Pronabec cuándo abre la siguiente.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- BECAS BCP -->
  <section class="chapter bcp" id="bcp" aria-labelledby="bcp-title" data-parallax-host>
    <div class="ghost" data-speed=".3" aria-hidden="true">BCP</div>
    <div class="wrap split">
      <div class="sticky">
        <p class="chap-tag">Misión 02 · Banco de Crédito</p>
        <h2 class="title-xl" id="bcp-title" data-kinetic>BECAS BCP</h2>
        <p class="lead">Una beca completa en universidades top del país. Más difícil de ganar, y con ruta clara de tres fases.</p>
        <div class="chips">
          <span class="pill"><b>11</b> ciclos pagados</span>
          <span class="pill">Cierra en <b data-deadline="">por confirmar</b></span>
        </div>
        <div class="btns">
          <a class="btn btn-fire" href="https://www.viabcp.com/becasbcp" target="_blank" rel="noopener noreferrer">Ver cómo postular</a>
          <form method="POST" action="{{ route('examen.independiente') }}" target="_top">@csrf<button class="btn btn-ghost" type="submit">Practicar en el simulador</button></form>
        </div>
      </div>
      <div>
        <h3 class="chap-tag" style="margin-bottom:28px;font-size:1.05rem">Tu ruta para ganarla</h3>
        <div class="route" id="route">
          <div class="route-line" aria-hidden="true"><i></i></div>
          <div class="node"><span class="n" aria-hidden="true">1</span><h3>Verifican tus requisitos</h3><p>Ser peruano/a o residente permanente, tener necesidad económica y buenas notas de 3.º a 5.º de secundaria.</p></div>
          <div class="node"><span class="n" aria-hidden="true">2</span><h3>Test psicométrico</h3><p>Una evaluación a cargo de Becas BCP. Llega descansado/a y con calma.</p></div>
          <div class="node"><span class="n" aria-hidden="true">3</span><h3>Entrevista personal</h3><p>Conversan contigo. Además tienes que aprobar la admisión de la universidad aliada.</p></div>
        </div>

        <div class="panel">
          <h3>Qué cubre</h3>
          <p>Matrícula y pensión durante 11 ciclos académicos, y los gastos de graduación y titulación. Puede incluir una asignación mensual de manutención, según el caso.</p>
          <p class="note">La lista de carreras y universidades cambia cada año. Confírmala en las bases de la convocatoria vigente.</p>
        </div>
      </div>
    </div>
    <div class="unis">
      <p class="chap-tag unis-h wrap">Universidades aliadas en las últimas bases</p>
      <div class="marquee" aria-label="PUCP, UP, UPC, UTEC, UDEP, ULima">
        <div class="marquee-track" aria-hidden="true">
          <span class="uni">PUCP</span><span class="uni">UP</span><span class="uni">UPC</span><span class="uni">UTEC</span><span class="uni">UDEP</span><span class="uni">ULima</span>
          <span class="uni">PUCP</span><span class="uni">UP</span><span class="uni">UPC</span><span class="uni">UTEC</span><span class="uni">UDEP</span><span class="uni">ULima</span>
        </div>
      </div>
    </div>
  </section>

  <!-- PARA TUS PAPAS -->
  <section class="family" aria-labelledby="fam-title">
    <div class="wrap family-in">
      <p class="chap-tag">Para tus papás</p>
      <h2 class="sr" id="fam-title">Mensaje para tu familia</h2>
      <p class="big-p reveal-words">Beca 18 paga matrícula y pensión. Postular no cuesta nada. Si en casa dudan, enséñales esto.</p>
      <div class="sharebox">
        <p id="sharemsg">Mira estas becas para la universidad. Beca 18 (Pronabec) paga matrícula y pensión, y postular es gratis: https://www.pronabec.gob.pe/beca-18/ . También está Becas BCP: https://www.viabcp.com/becasbcp</p>
        <div class="share-row">
          <button class="btn btn-green" id="copybtn" type="button">Copiar mensaje para mi familia</button>
          <span class="status" id="copystatus" role="status" aria-live="polite"></span>
        </div>
      </div>
    </div>
  </section>

  <!-- FINAL -->
  <section class="finale" aria-labelledby="fin-title">
    <div class="marquee big-mq" aria-hidden="true">
      <div class="marquee-track">
        <span>PRACTICA</span><span class="o">POSTULA</span><span class="s">GANA</span><span>PRACTICA</span><span class="o">POSTULA</span><span class="s">GANA</span>
        <span>PRACTICA</span><span class="o">POSTULA</span><span class="s">GANA</span><span>PRACTICA</span><span class="o">POSTULA</span><span class="s">GANA</span>
      </div>
    </div>
    <div class="wrap finale-in">
      <h2 class="title-xl" id="fin-title" data-kinetic>TU TURNO</h2>
      <div class="btns">
        <form method="POST" action="{{ route('examen.independiente') }}" target="_top">@csrf<button class="btn btn-gold" type="submit">Practicar en el simulador</button></form>
        <form method="POST" action="{{ route('examen.independiente') }}" target="_top">@csrf<button class="btn btn-ghost" type="submit">Dar examen</button></form>
      </div>
      <div class="quote">
        <div class="quote-video">
          <iframe src="https://www.youtube-nocookie.com/embed/HpvIx7UlrO0"
                  title="Video de Lizeth Atoccsa en YouTube"
                  loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
          <a href="https://www.youtube.com/watch?v=HpvIx7UlrO0" target="_blank" rel="noopener noreferrer">Abrir video en YouTube ↗</a>
        </div>
        <div>
          <h3>Conoce la historia de Lizeth Atoccsa</h3>
          <p>Beneficiaria de Beca 18 que comparte su experiencia universitaria, aprendizajes y consejos para estudiantes.</p>
          <div class="btns">
            <a class="btn btn-gold" href="https://www.youtube.com/@LizethVlogger" target="_blank" rel="noopener noreferrer">Ver su YouTube ↗</a>
            <a class="btn btn-ghost" href="https://www.tiktok.com/@lizeth.atoccsa" target="_blank" rel="noopener noreferrer">Ver su TikTok ↗</a>
          </div>
        </div>
      </div>
      <footer class="foot">
        <p class="note">Confirma fechas, requisitos y beneficios en las páginas oficiales antes de postular. Cambian en cada convocatoria.</p>
        <p class="note">Fuentes:</p>
        <ul class="note">
          <li><a href="https://www.pronabec.gob.pe/beca-18/" target="_blank" rel="noopener noreferrer">Pronabec · Beca 18</a> y <a href="https://elperuano.pe/noticia/306541-beca-18-pronabec-ofrece-6000-vacantes-para-estudiar-carreras-acordes-con-necesidades-del-pais" target="_blank" rel="noopener noreferrer">El Peruano, convocatoria 2027</a></li>
          <li><a href="https://elpopular.pe/educacion/2022/01/12/beca-18-convocatoria-2022-cuales-son-beneficios-cubre-beca-estudios-pronabec-105477" target="_blank" rel="noopener noreferrer">El Popular · beneficios de Beca 18</a> (2022)</li>
          <li><a href="https://www.viabcp.com/becasbcp" target="_blank" rel="noopener noreferrer">Becas BCP</a> y <a href="https://www.udep.edu.pe/admision-piura/wp-content/uploads/sites/75/2026/09/Becas-BCP-convocatoria-2027.pdf" target="_blank" rel="noopener noreferrer">bases Becas BCP 2027 (UDEP)</a></li>
        </ul>
      </footer>
    </div>
  </section>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script>
(function () {
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var root = document.documentElement;

  /* HUD: XP follows scroll progress (works without GSAP) */
  var xpFill = document.getElementById("xpfill");
  var xpNum = document.getElementById("xpnum");
  var lvl = document.getElementById("lvl");
  function hud() {
    var max = Math.max(1, root.scrollHeight - window.innerHeight);
    var p = Math.min(1, Math.max(0, window.scrollY / max));
    xpFill.style.transform = "scaleX(" + p + ")";
    xpNum.textContent = Math.round(p * 100);
    lvl.textContent = "Nivel " + (1 + Math.min(4, Math.floor(p * 5)));
  }
  window.addEventListener("scroll", hud, { passive: true });
  window.addEventListener("resize", hud);
  hud();

  /* Countdown: fill data-deadline="YYYY-MM-DD" when the official close date is known */
  Array.prototype.forEach.call(document.querySelectorAll("[data-deadline]"), function (el) {
    var v = el.getAttribute("data-deadline");
    if (!v) return;
    var d = new Date(v + "T23:59:59");
    var days = Math.ceil((d - new Date()) / 864e5);
    el.textContent = days > 0 ? days + " días" : "cerrado";
  });

  /* Copy message for the family */
  var btn = document.getElementById("copybtn");
  var msg = document.getElementById("sharemsg");
  var status = document.getElementById("copystatus");
  function selectMsg() {
    var r = document.createRange();
    r.selectNodeContents(msg);
    var s = window.getSelection();
    s.removeAllRanges();
    s.addRange(r);
    status.textContent = "Selecciona y copia el mensaje.";
  }
  btn.addEventListener("click", function () {
    var text = msg.innerText;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        status.textContent = "Mensaje copiado. Pégalo en WhatsApp.";
      }, selectMsg);
    } else {
      selectMsg();
    }
  });

  if (reduce || !window.gsap || !window.ScrollTrigger) return;

  try {
    gsap.registerPlugin(ScrollTrigger);
    root.classList.add("js-anim");
    var vh = function () { return window.innerHeight; };

    /* Kinetic hero title: split letters */
    var l1 = document.querySelector(".l1");
    var txt = l1.textContent;
    l1.textContent = "";
    var letters = [];
    txt.split("").forEach(function (c) {
      var s = document.createElement("span");
      s.className = "ch";
      s.textContent = c;
      l1.appendChild(s);
      letters.push(s);
    });
    var mid = (letters.length - 1) / 2;

    gsap.from(letters, { yPercent: 120, skewY: 12, duration: 0.9, ease: "power4.out", stagger: 0.045 });
    gsap.from(".hero-copy > *", { y: 24, opacity: 0, duration: 0.7, stagger: 0.1, delay: 0.6, ease: "power2.out" });
    gsap.from(".hero .chip", { scale: 0.5, opacity: 0, duration: 0.7, ease: "back.out(2)", stagger: 0.12, delay: 0.5 });

    /* Hero scroll: letters drift apart */
    gsap.to(letters, {
      y: function (i) { return (i % 2 ? 1 : -1) * 40; },
      x: function (i) { return (i - mid) * 14; },
      rotation: function (i) { return (i - mid) * 2.2; },
      scale: 1.04, opacity: 0.1, ease: "none",
      scrollTrigger: { trigger: ".hero", start: "top top", end: "bottom top", scrub: true }
    });
    gsap.to(".l2", { yPercent: -60, opacity: 0, ease: "none",
      scrollTrigger: { trigger: ".hero", start: "top top", end: "70% top", scrub: true } });

    /* Parallax layers */
    gsap.utils.toArray("[data-speed]").forEach(function (el) {
      var sp = parseFloat(el.getAttribute("data-speed"));
      var host = el.closest("[data-parallax-host]") || el;
      var isHero = host.getAttribute("data-start") === "top top";
      var k = function () { return sp * vh() * 0.6; };
      var st = { trigger: host, start: isHero ? "top top" : "top bottom", end: "bottom top", scrub: true, invalidateOnRefresh: true };
      if (isHero) {
        gsap.fromTo(el, { y: 0 }, { y: k, ease: "none", scrollTrigger: st });
      } else {
        gsap.fromTo(el, { y: function () { return -k() / 2; } }, { y: function () { return k() / 2; }, ease: "none", scrollTrigger: st });
      }
    });

    /* Mouse parallax in hero */
    var heroEl = document.querySelector(".hero");
    var mouseEls = gsap.utils.toArray("[data-mouse]");
    heroEl.addEventListener("pointermove", function (e) {
      var r = heroEl.getBoundingClientRect();
      var nx = (e.clientX - r.left) / r.width - 0.5;
      var ny = (e.clientY - r.top) / r.height - 0.5;
      mouseEls.forEach(function (el) {
        var f = parseFloat(el.getAttribute("data-mouse"));
        gsap.to(el, { x: nx * f, y: ny * f, duration: 0.8, ease: "power3.out", overwrite: "auto" });
      });
    });

    /* Scrollytelling: pinned unlock sequence */
    var steps = gsap.utils.toArray(".step");
    var n = steps.length;
    var counter = document.getElementById("stepnum");
    var bar = document.querySelector(".unlock-bar b");
    var tl = gsap.timeline({
      defaults: { ease: "power2.out" },
      scrollTrigger: { trigger: ".unlock-stage", start: "top top", end: "+=" + (n * 90) + "%", scrub: 0.6, pin: true, anticipatePin: 1 }
    });
    steps.forEach(function (s, i) {
      var t = i * 1.4;
      if (i > 0) {
        tl.to(steps[i - 1], { opacity: 0, y: -50, scale: 0.97, duration: 0.45 }, t);
        tl.fromTo(s, { opacity: 0, y: 60 }, { opacity: 1, y: 0, duration: 0.6 }, t + 0.2);
        tl.fromTo(s.querySelector(".wi"), { yPercent: 110 }, { yPercent: 0, duration: 0.6 }, t + 0.25);
        tl.fromTo(s.querySelector(".step-art img, .step-art svg"), { scale: 0.65, rotation: -10 }, { scale: 1, rotation: 0, duration: 0.7, ease: "back.out(1.6)" }, t + 0.2);
        tl.to(bar, { scaleX: (i + 1) / n, duration: 0.5, ease: "none" }, t + 0.2);
      }
      (function (num) {
        tl.call(function () { counter.textContent = num; }, null, t + 0.3);
      })(i + 1);
    });
    tl.to({}, { duration: 0.6 });

    /* Kinetic titles reveal word by word, tied to scroll */
    gsap.utils.toArray("[data-kinetic]").forEach(function (el) {
      var words = el.textContent.trim().split(/\s+/);
      el.setAttribute("aria-label", words.join(" "));
      el.textContent = "";
      var inner = words.map(function (w) {
        var k = document.createElement("span");
        k.className = "kw";
        var i = document.createElement("span");
        i.textContent = w;
        k.appendChild(i);
        el.appendChild(k);
        return i;
      });
      gsap.fromTo(inner, { yPercent: 110 }, { yPercent: 0, stagger: 0.12, ease: "none",
        scrollTrigger: { trigger: el, start: "top 92%", end: "top 55%", scrub: true } });
    });

    /* Requirements: dim to bright, check draws in */
    gsap.utils.toArray(".req").forEach(function (r) {
      var st = { trigger: r, start: "top 88%", end: "top 55%", scrub: true };
      gsap.fromTo(r, { opacity: 0.45, x: -24 }, { opacity: 1, x: 0, ease: "none", scrollTrigger: st });
      var ck = r.querySelector(".ck");
      gsap.fromTo(ck, { strokeDasharray: 40, strokeDashoffset: 40 }, { strokeDashoffset: 0, ease: "none", scrollTrigger: st });
    });

    /* BCP route: line fills, nodes light up */
    gsap.to("#route .route-line i", { scaleY: 1, ease: "none",
      scrollTrigger: { trigger: "#route", start: "top 70%", end: "bottom 60%", scrub: true } });
    gsap.utils.toArray(".node").forEach(function (nd) {
      ScrollTrigger.create({ trigger: nd, start: "top 65%",
        onEnter: function () { nd.classList.add("on"); },
        onLeaveBack: function () { nd.classList.remove("on"); } });
    });

    /* Family sentence: words light up on scroll */
    var rw = document.querySelector(".reveal-words");
    var ws = rw.textContent.trim().split(/\s+/);
    rw.setAttribute("aria-label", ws.join(" "));
    rw.textContent = "";
    var wEls = ws.map(function (w) {
      var s = document.createElement("span");
      s.className = "w";
      s.textContent = w;
      rw.appendChild(s);
      return s;
    });
    gsap.fromTo(wEls, { opacity: 0.35 }, { opacity: 1, stagger: 0.1, ease: "none",
      scrollTrigger: { trigger: rw, start: "top 82%", end: "bottom 50%", scrub: true } });

    /* Marquees: speed follows scroll velocity */
    var tweens = [];
    gsap.utils.toArray(".marquee-track").forEach(function (tr, i) {
      var dir = i % 2 ? 1 : -1;
      var tw = gsap.fromTo(tr, { xPercent: dir < 0 ? 0 : -50 }, { xPercent: dir < 0 ? -50 : 0, duration: 32, ease: "none", repeat: -1 });
      tweens.push(tw);
    });
    ScrollTrigger.create({ start: 0, end: "max", onUpdate: function (self) {
      var boost = 1 + Math.min(Math.abs(self.getVelocity()) / 400, 5);
      tweens.forEach(function (tw) { tw.timeScale(Math.max(tw.timeScale(), boost)); });
    } });
    gsap.ticker.add(function () {
      tweens.forEach(function (tw) {
        var ts = tw.timeScale();
        if (ts > 1.01) tw.timeScale(ts + (1 - ts) * 0.06);
      });
    });

    window.addEventListener("load", function () { ScrollTrigger.refresh(); });
  } catch (e) {
    root.classList.remove("js-anim");
    if (window.console) console.error(e);
  }
})();
</script>

</body></html>