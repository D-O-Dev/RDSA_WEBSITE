<!DOCTYPE html>
<html lang="fr" data-theme="dark">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <style>
    :root {
      --bgimg: url({!! json_encode(asset(\App\Models\SiteContent::current()['admin_background_path']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!})
    }
  </style>
  <title>RDSA · Administration</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script>try { var t = localStorage.getItem('rdsa-admin-theme'); document.documentElement.dataset.theme = t === 'light' ? 'light' : 'dark' } catch (e) { }</script>
  <style>
    :root {
      --bg: #080D1A;
      --panel: rgba(14, 22, 48, .5);
      --panel2: rgba(255, 255, 255, .07);
      --line: rgba(255, 255, 255, .12);
      --edge: rgba(255, 255, 255, .16);
      --blue: #315FE8;
      --blue2: #4979FF;
      --gold: #D6B568;
      --tx: #F3F5FA;
      --tx2: #B4BFD6;
      --tx3: #8591AC;
      --ok: #35C98A;
      --warn: #E8B653;
      --err: #F16B75;
      --glass: rgba(14, 22, 48, .5);
      --side: rgba(10, 16, 34, .55);
      --on: linear-gradient(135deg, #5b87ff, #2447c9);
      --veil: linear-gradient(180deg, rgba(5, 9, 24, .25), rgba(5, 9, 24, .62));
      --sky: radial-gradient(1.5px 1.5px at 12% 18%, #fff, transparent), radial-gradient(1px 1px at 38% 10%, #fff, transparent), radial-gradient(1.5px 1.5px at 66% 24%, #fff, transparent), radial-gradient(1px 1px at 84% 12%, #fff, transparent), radial-gradient(1px 1px at 52% 34%, #cfe0ff, transparent), linear-gradient(180deg, #09112e 0%, #16275a 55%, #6a5a68 90%, #1a1520);
      --sh: 0 24px 60px rgba(0, 0, 0, .38);
      --r: 26px
    }

    [data-theme=light] {
      --bg: #E9EEF7;
      --panel: rgba(255, 255, 255, .55);
      --panel2: rgba(255, 255, 255, .6);
      --line: rgba(30, 50, 100, .12);
      --edge: rgba(255, 255, 255, .95);
      --blue: #2A55D6;
      --blue2: #3C6AF0;
      --gold: #9A7A2C;
      --tx: #0E1730;
      --tx2: #46526B;
      --tx3: #6B7893;
      --ok: #14935F;
      --warn: #A97508;
      --err: #C73A47;
      --glass: rgba(255, 255, 255, .52);
      --side: rgba(255, 255, 255, .58);
      --on: #16234a;
      --veil: linear-gradient(180deg, rgba(244, 247, 253, .72), rgba(233, 239, 249, .86));
      --sky: linear-gradient(180deg, #f6f8fd, #dfe7f4 60%, #c7d4ea);
      --sh: 0 20px 50px rgba(60, 80, 140, .16)
    }

    * {
      box-sizing: border-box;
      margin: 0
    }

    html {
      scroll-behavior: smooth
    }

    body {
      font: 15px/1.55 Manrope, system-ui, sans-serif;
      background: var(--bg);
      color: var(--tx);
      min-height: 100vh;
      overflow-x: hidden
    }

    h1,
    h2,
    h3 {
      font-weight: 600;
      letter-spacing: -.015em
    }

    button,
    input,
    select,
    textarea {
      font: inherit;
      color: inherit
    }

    :focus-visible {
      outline: 2px solid var(--blue2);
      outline-offset: 2px
    }

    .bg {
      position: fixed;
      inset: 0;
      z-index: -1;
      overflow: hidden
    }

    .bg-img {
      position: absolute;
      inset: -6%;
      background: var(--bgimg, none) center/cover, var(--sky);
      transition: transform .25s ease-out;
      will-change: transform
    }

    .bg::after {
      content: "";
      position: absolute;
      inset: 0;
      background: var(--veil), radial-gradient(800px 500px at 85% 0, rgba(73, 121, 255, .25), transparent 60%)
    }

    .glass,
    .side,
    .top,
    .card,
    .tw,
    .md,
    .toast,
    .sel,
    .in,
    .srch input,
    textarea {
      backdrop-filter: blur(var(--bl, 22px)) saturate(1.5);
      -webkit-backdrop-filter: blur(var(--bl, 22px)) saturate(1.5)
    }

    /* Barre latérale flottante */
    .side {
      position: fixed;
      left: 20px;
      top: 20px;
      bottom: 20px;
      width: 240px;
      background: var(--side);
      border: 1px solid var(--edge);
      border-radius: 30px;
      display: flex;
      flex-direction: column;
      z-index: 30;
      box-shadow: var(--sh), inset 0 1px 0 rgba(255, 255, 255, .14);
      transition: transform .35s
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 24px 22px 14px
    }

    .brand img {
      height: 38px;
      width: auto;
      border-radius: 10px
    }

    .brand-fb {
      width: 38px;
      height: 38px;
      border-radius: 12px;
      background: var(--on);
      display: none;
      place-items: center;
      font-weight: 800;
      color: #fff
    }

    .brand b {
      display: block;
      font-size: 19px;
      letter-spacing: .02em
    }

    .brand small {
      color: var(--tx3);
      font-size: 12px
    }

    .nav {
      flex: 1;
      overflow-y: auto;
      padding: 4px 14px 12px;
      scrollbar-width: none
    }

    .nav h4 {
      font: 600 12px Manrope;
      color: var(--tx3);
      padding: 16px 12px 6px
    }

    .nav a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 4px 10px 4px 4px;
      margin: 3px 0;
      border-radius: 99px;
      color: var(--tx2);
      text-decoration: none;
      transition: background .2s, color .2s
    }

    .nav a i {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: grid;
      place-items: center;
      font-size: 16px;
      background: var(--panel2);
      border: 1px solid var(--line);
      transition: background .25s, box-shadow .25s
    }

    .nav a:hover {
      color: var(--tx)
    }

    .nav a.on {
      color: var(--tx);
      font-weight: 600;
      background: var(--panel2)
    }

    .nav a.on i {
      background: var(--on);
      color: #fff;
      border-color: transparent;
      box-shadow: 0 0 18px rgba(73, 121, 255, .65)
    }

    .cnt {
      margin-left: auto;
      background: var(--gold);
      color: #1a1405;
      font: 700 11px Manrope;
      padding: 1px 8px;
      border-radius: 99px;
      min-width: 22px;
      text-align: center
    }

    .me {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0 14px 16px;
      padding: 10px;
      border-radius: 20px;
      background: var(--panel2);
      border: 1px solid var(--line)
    }

    .av {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: var(--on);
      display: grid;
      place-items: center;
      font-weight: 700;
      color: #fff;
      flex: none
    }

    .me small {
      color: var(--tx3);
      display: block;
      font-size: 12px
    }

    .main {
      margin-left: 280px;
      padding: 20px 20px 40px 0;
      min-height: 100vh
    }

    .top {
      position: sticky;
      top: 20px;
      z-index: 20;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 12px 10px 24px;
      margin-bottom: 26px;
      background: var(--glass);
      border: 1px solid var(--edge);
      border-radius: 99px;
      box-shadow: var(--sh), inset 0 1px 0 rgba(255, 255, 255, .14)
    }

    .top h1 {
      font-size: 19px;
      line-height: 1.15
    }

    .crumb {
      color: var(--tx3);
      font-size: 12.5px
    }

    .sp {
      flex: 1
    }

    .ib,
    .btn {
      border: 1px solid var(--edge);
      background: var(--panel2);
      border-radius: 99px;
      cursor: pointer;
      transition: transform .15s, background .2s, box-shadow .2s
    }

    .ib {
      width: 42px;
      height: 42px;
      display: grid;
      place-items: center;
      position: relative
    }

    .ib:hover,
    .btn:hover {
      box-shadow: 0 0 16px rgba(73, 121, 255, .4)
    }

    .ib:active,
    .btn:active {
      transform: scale(.96)
    }

    .burger {
      display: none
    }

    .sel {
      background: var(--panel2);
      border: 1px solid var(--edge);
      border-radius: 99px;
      padding: 10px 16px;
      max-width: 190px
    }

    .sel option,
    select.in option {
      color: #0E1730;
      background: #fff
    }

    .dot {
      position: absolute;
      top: 9px;
      right: 10px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--err)
    }

    .wrap {
      padding: 0 8px 0 4px;
      max-width: 1360px
    }

    .view {
      display: none
    }

    .view.on {
      display: block;
      animation: enter .5s cubic-bezier(.2, .7, .2, 1)
    }

    @keyframes enter {
      from {
        opacity: 0;
        transform: translateX(26px)
      }

      to {
        opacity: 1;
        transform: none
      }
    }

    .hero {
      padding: 6px 6px 26px
    }

    .hero h2 {
      font-size: clamp(30px, 4.6vw, 54px);
      font-weight: 300;
      line-height: 1.08;
      max-width: 700px
    }

    .hero h2 em {
      font-style: normal;
      color: var(--blue2);
      font-weight: 500
    }

    .hero p {
      color: var(--tx2);
      margin-top: 10px;
      max-width: 520px
    }

    .hero .btn {
      margin-top: 18px
    }

    .grid {
      display: grid;
      gap: 18px
    }

    .g4 {
      grid-template-columns: repeat(auto-fit, minmax(215px, 1fr))
    }

    .g2 {
      grid-template-columns: repeat(auto-fit, minmax(330px, 1fr))
    }

    .card {
      background: var(--glass);
      border: 1px solid var(--edge);
      border-radius: var(--r);
      padding: 22px;
      box-shadow: var(--sh), inset 0 1px 0 rgba(255, 255, 255, .14)
    }

    .card h3 {
      font-size: 18px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 10px
    }

    .kpi {
      position: relative;
      overflow: hidden
    }

    .kpi i {
      position: absolute;
      right: 16px;
      top: 16px;
      width: 36px;
      height: 36px;
      display: grid;
      place-items: center;
      border-radius: 50%;
      background: var(--panel2);
      border: 1px solid var(--line);
      color: var(--blue2)
    }

    .kpi span {
      color: var(--tx2);
      font-size: 13px
    }

    .kpi b {
      display: block;
      font-size: 28px;
      font-weight: 600;
      margin-top: 6px;
      letter-spacing: -.02em
    }

    .kpi small {
      color: var(--tx3);
      font-size: 12.5px
    }

    .kpi.gold {
      background: linear-gradient(180deg, var(--glass) 30%, rgba(49, 95, 232, .55))
    }

    .kpi.gold b {
      color: var(--gold)
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      font-weight: 600
    }

    .btn.pri {
      background: linear-gradient(135deg, #5b87ff, #2b4fd3);
      border-color: transparent;
      color: #fff;
      box-shadow: 0 6px 22px rgba(73, 121, 255, .45)
    }

    .btn.gold {
      background: linear-gradient(135deg, #F0CF8E, #D6B568);
      color: #1a1405;
      border-color: transparent;
      box-shadow: 0 6px 22px rgba(214, 181, 104, .35)
    }

    .btn.bad {
      color: var(--err)
    }

    .btn.sm {
      padding: 6px 14px;
      font-size: 13px
    }

    .bar {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      margin-bottom: 18px
    }

    .srch {
      flex: 1;
      min-width: 180px;
      position: relative
    }

    .srch i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--tx3)
    }

    .in,
    .srch input,
    textarea {
      width: 100%;
      background: var(--panel2);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 11px 14px
    }

    .srch input {
      padding-left: 42px;
      border-radius: 99px
    }

    .in:focus,
    textarea:focus,
    .srch input:focus {
      border-color: var(--blue2);
      box-shadow: 0 0 0 3px rgba(73, 121, 255, .2)
    }

    label {
      display: block;
      font-weight: 600;
      font-size: 13.5px;
      margin-bottom: 6px;
      color: var(--tx2)
    }

    .f {
      margin-bottom: 14px
    }

    .hint {
      color: var(--tx3);
      font-size: 12.5px;
      margin-top: 4px
    }

    .tw {
      overflow-x: auto;
      background: var(--glass);
      border: 1px solid var(--edge);
      border-radius: var(--r);
      box-shadow: var(--sh)
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 640px
    }

    th {
      text-align: left;
      font: 600 13px Manrope;
      color: var(--tx3);
      padding: 15px 16px;
      white-space: nowrap
    }

    td {
      padding: 14px 16px;
      border-top: 1px solid var(--line);
      vertical-align: middle
    }

    tbody tr {
      transition: background .2s
    }

    tbody tr:hover {
      background: rgba(73, 121, 255, .1)
    }

    .act {
      display: flex;
      gap: 6px;
      justify-content: flex-end;
      flex-wrap: wrap
    }

    .empty {
      text-align: center;
      padding: 44px 16px;
      color: var(--tx3)
    }

    .empty i {
      font-size: 34px;
      color: var(--gold);
      display: block;
      margin-bottom: 8px
    }

    .bd {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 3px 11px;
      border-radius: 99px;
      font-size: 12.5px;
      font-weight: 600;
      border: 1px solid currentColor;
      background: color-mix(in srgb, currentColor 13%, transparent)
    }

    .bd.ok {
      color: var(--ok)
    }

    .bd.wa {
      color: var(--warn)
    }

    .bd.er {
      color: var(--err)
    }

    .bd.in2 {
      color: var(--blue2)
    }

    .bd.mu {
      color: var(--tx3)
    }

    .tabs {
      display: inline-flex;
      gap: 4px;
      flex-wrap: wrap;
      margin-bottom: 18px;
      padding: 5px;
      border-radius: 99px;
      background: var(--glass);
      border: 1px solid var(--edge)
    }

    .tabs button {
      background: none;
      border: 0;
      padding: 9px 16px;
      cursor: pointer;
      color: var(--tx2);
      border-radius: 99px;
      font-weight: 600;
      transition: background .2s
    }

    .tabs button.on {
      color: #fff;
      background: var(--on);
      box-shadow: 0 0 16px rgba(73, 121, 255, .5)
    }

    .tp {
      display: none
    }

    .tp.on {
      display: block;
      animation: enter .35s
    }

    .row2 {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 0 16px
    }

    .sw {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 12px 0;
      border-top: 1px solid var(--line)
    }

    .sw input {
      appearance: none;
      width: 46px;
      height: 26px;
      border-radius: 99px;
      background: var(--line);
      border: 1px solid var(--edge);
      position: relative;
      cursor: pointer;
      transition: background .2s;
      flex: none
    }

    .sw input::after {
      content: "";
      position: absolute;
      top: 3px;
      left: 3px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: #fff;
      transition: transform .2s
    }

    .sw input:checked {
      background: var(--blue)
    }

    .sw input:checked::after {
      transform: translateX(20px)
    }

    .chart {
      display: flex;
      align-items: flex-end;
      gap: 12px;
      height: 200px;
      padding-top: 10px
    }

    .chart div {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-end;
      height: 100%;
      gap: 6px;
      font-size: 12px;
      color: var(--tx3)
    }

    .chart i {
      display: block;
      width: 100%;
      max-width: 50px;
      border-radius: 12px;
      background: repeating-linear-gradient(135deg, rgba(255, 255, 255, .5) 0 3px, rgba(255, 255, 255, .1) 3px 8px);
      border: 1px solid var(--line);
      height: 0;
      transition: height .9s cubic-bezier(.2, .7, .2, 1)
    }

    .chart div:last-child i {
      background: var(--on);
      border-color: transparent;
      box-shadow: 0 0 22px rgba(73, 121, 255, .6)
    }

    .chart em {
      font-style: normal;
      color: var(--tx2);
      font-weight: 600
    }

    .feed {
      list-style: none;
      padding: 0
    }

    .feed li {
      display: flex;
      gap: 12px;
      padding: 11px 0;
      border-top: 1px solid var(--line)
    }

    .feed li:first-child {
      border: 0
    }

    .feed i {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--panel2);
      border: 1px solid var(--line);
      display: grid;
      place-items: center;
      color: var(--blue2);
      flex: none
    }

    .feed small {
      color: var(--tx3);
      display: block
    }

    .media {
      aspect-ratio: 4/3;
      border-radius: 18px;
      background: linear-gradient(135deg, var(--c1), var(--c2));
      display: grid;
      place-items: center;
      font-size: 30px;
      color: rgba(255, 255, 255, .85)
    }

    .mc {
      padding: 14px
    }

    .mc b {
      display: block;
      margin: 10px 0 2px
    }

    .drop {
      border: 2px dashed var(--edge);
      border-radius: var(--r);
      padding: 28px;
      text-align: center;
      color: var(--tx3);
      cursor: pointer;
      background: var(--glass);
      transition: border-color .2s, background .2s
    }

    .drop:hover,
    .drop.dv {
      border-color: var(--gold)
    }

    .lock {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(214, 181, 104, .12);
      border: 1px solid rgba(214, 181, 104, .4);
      color: var(--gold);
      padding: 11px 14px;
      border-radius: 16px;
      font-size: 13.5px
    }

    .scan {
      display: flex;
      gap: 10px;
      flex-wrap: wrap
    }

    .scan .in {
      flex: 1;
      min-width: 200px;
      border-radius: 99px
    }

    .ov {
      position: fixed;
      inset: 0;
      background: rgba(4, 8, 20, .55);
      backdrop-filter: blur(6px);
      z-index: 60;
      display: none;
      place-items: center;
      padding: 16px
    }

    .ov.on {
      display: grid
    }

    .md {
      background: var(--side);
      border: 1px solid var(--edge);
      border-radius: 28px;
      width: min(520px, 100%);
      max-height: 90vh;
      overflow: auto;
      padding: 26px;
      box-shadow: var(--sh);
      animation: pop .25s cubic-bezier(.2, .8, .3, 1.2)
    }

    @keyframes pop {
      from {
        opacity: 0;
        transform: scale(.94) translateY(10px)
      }
    }

    .md h3 {
      font-size: 21px;
      margin-bottom: 12px
    }

    .md .act {
      margin-top: 18px
    }

    .kv {
      display: grid;
      grid-template-columns: 130px 1fr;
      gap: 6px 12px;
      margin-bottom: 14px
    }

    .kv span {
      color: var(--tx3)
    }

    .proof {
      height: 130px;
      border-radius: 18px;
      border: 1px dashed var(--edge);
      display: grid;
      place-items: center;
      color: var(--tx3);
      margin-bottom: 14px;
      background: var(--panel2)
    }

    #toasts {
      position: fixed;
      right: 18px;
      bottom: 18px;
      display: grid;
      gap: 10px;
      z-index: 120
    }

    .toast {
      background: var(--side);
      border: 1px solid var(--edge);
      border-left: 4px solid var(--ok);
      padding: 12px 18px;
      border-radius: 18px;
      box-shadow: var(--sh);
      display: flex;
      gap: 10px;
      align-items: center;
      animation: enter .3s
    }

    .toast.er {
      border-left-color: var(--err)
    }

    .scrim {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(4, 8, 20, .55);
      z-index: 25
    }

    /* Connexion */
    #login {
      position: fixed;
      inset: 0;
      z-index: 100;
      display: grid;
      grid-template-columns: minmax(260px, 32%) 1fr;
      background: var(--bg)
    }

    #login.hid {
      display: none
    }

    .lg-l {
      position: relative;
      background: linear-gradient(180deg, rgba(5, 9, 24, .35), rgba(5, 9, 24, .9)), var(--bgimg, none) center/cover, var(--sky);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 34px;
      color: #F3F5FA
    }

    .lg-l .brand {
      padding: 0
    }

    .lg-w {
      border-left: 3px solid var(--gold);
      padding-left: 16px
    }

    .lg-w h2 {
      font-size: 34px;
      font-weight: 500;
      line-height: 1.1
    }

    .lg-w p {
      color: #B4BFD6;
      margin-top: 10px;
      font-size: 14px
    }

    .lg-l small {
      color: #8591AC;
      font-size: 12px
    }

    .lg-r {
      display: grid;
      place-items: center;
      padding: 24px;
      position: relative;
      background: var(--veil), var(--sky)
    }

    .lg-c {
      width: min(430px, 100%);
      padding: 38px 36px 30px;
      text-align: center;
      background: var(--glass);
      border: 1px solid var(--edge);
      border-radius: 32px;
      box-shadow: var(--sh), inset 0 1px 0 rgba(255, 255, 255, .14);
      backdrop-filter: blur(24px) saturate(1.5)
    }

    .lg-logo {
      width: 78px;
      height: 78px;
      border-radius: 50%;
      margin: 0 auto 18px;
      display: grid;
      place-items: center;
      border: 1px solid rgba(214, 181, 104, .5);
      background: var(--panel2);
      overflow: hidden;
      color: var(--gold);
      font-size: 30px
    }

    .lg-logo img {
      width: 70%
    }

    .lg-c h2 {
      font-size: 28px
    }

    .lg-c>p {
      color: var(--tx3);
      margin: 4px 0 26px;
      font-size: 14px
    }

    .lg-f {
      text-align: left;
      margin-bottom: 16px;
      position: relative
    }

    .lg-f>i {
      position: absolute;
      left: 16px;
      top: 41px;
      color: var(--tx3)
    }

    .lg-f .in {
      padding: 14px 44px;
      height: 52px;
      border-radius: 16px
    }

    .lg-f button.eye {
      position: absolute;
      right: 8px;
      top: 33px;
      width: 40px;
      height: 40px;
      background: none;
      border: 0;
      color: var(--tx3);
      cursor: pointer;
      border-radius: 50%
    }

    .lg-fp {
      text-align: right;
      margin: -6px 0 18px
    }

    .lg-fp a {
      color: var(--gold);
      font-size: 13.5px;
      text-decoration: none;
      cursor: pointer
    }

    .lg-b {
      width: 100%;
      height: 54px;
      border: 0;
      border-radius: 16px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 22px;
      font-weight: 700;
      font-size: 16px;
      color: #1a1405;
      background: linear-gradient(135deg, #F7D9A0, #D6B568);
      box-shadow: 0 10px 30px rgba(214, 181, 104, .35);
      transition: transform .15s, box-shadow .2s
    }

    .lg-b:hover {
      box-shadow: 0 12px 36px rgba(214, 181, 104, .55)
    }

    .lg-b:active {
      transform: scale(.985)
    }

    #lgErr {
      color: var(--err);
      font-size: 13px;
      min-height: 20px;
      margin-bottom: 8px;
      text-align: left
    }

    .lg-s {
      margin-top: 22px;
      color: var(--tx3);
      font-size: 13px
    }

    @media(max-width:960px) {
      .side {
        transform: translateX(-120%)
      }

      .side.open {
        transform: none
      }

      .scrim.on {
        display: block
      }

      .main {
        margin-left: 0;
        padding: 12px 12px 40px
      }

      .top {
        top: 12px;
        padding-left: 12px;
        margin-bottom: 20px
      }

      .burger {
        display: grid
      }

      .sel {
        max-width: 120px
      }

      .hide-s {
        display: none
      }

      #login {
        grid-template-columns: 1fr
      }

      .lg-l {
        display: none
      }
    }

    @media(prefers-reduced-motion:reduce) {
      * {
        animation: none !important;
        transition: none !important
      }

      html {
        scroll-behavior: auto
      }
    }

    /* ===== v3 : fonctionnalités avancées ===== */
    [hidden] {
      display: none !important
    }

    input[type=checkbox] {
      accent-color: var(--blue2)
    }

    .srchbtn {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      border-radius: 99px;
      background: var(--panel2);
      border: 1px solid var(--edge);
      color: var(--tx3);
      cursor: pointer;
      min-width: 240px;
      margin-left: 12px
    }

    kbd {
      font: 600 11px Manrope;
      border: 1px solid var(--line);
      border-radius: 6px;
      padding: 1px 7px;
      color: var(--tx3);
      margin-left: auto
    }

    .qa {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin: 0 0 22px
    }

    .dn {
      position: relative;
      width: 132px;
      height: 132px;
      flex: none
    }

    .dn b {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      font-size: 26px;
      font-weight: 600
    }

    .donut {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 17px), #000 calc(100% - 16px));
      mask: radial-gradient(farthest-side, transparent calc(100% - 17px), #000 calc(100% - 16px))
    }

    .legend {
      display: grid;
      gap: 9px;
      font-size: 14px
    }

    .legend span::before {
      content: "";
      display: inline-block;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--c);
      margin-right: 9px
    }

    .cd {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      text-align: center
    }

    .cd div {
      background: var(--panel2);
      border: 1px solid var(--line);
      border-radius: 20px;
      padding: 14px 4px
    }

    .cd b {
      display: block;
      font-size: 28px;
      font-weight: 600
    }

    .cd small {
      color: var(--tx3)
    }

    .todo {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 0;
      border-top: 1px solid var(--line);
      margin: 0;
      font-weight: 500;
      color: var(--tx)
    }

    .todo.dn2 span {
      text-decoration: line-through;
      color: var(--tx3)
    }

    .todo span {
      flex: 1
    }

    .todo input {
      width: 18px;
      height: 18px
    }

    .ibs {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      border: 1px solid var(--line);
      background: var(--panel2);
      cursor: pointer;
      display: inline-grid;
      place-items: center;
      flex: none
    }

    .ibs:hover {
      box-shadow: 0 0 12px rgba(73, 121, 255, .45)
    }

    .pgn {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px;
      border-top: 1px solid var(--line)
    }

    .bulk {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      padding: 10px 18px;
      margin-bottom: 14px;
      border-radius: 99px;
      background: rgba(73, 121, 255, .18);
      border: 1px solid var(--blue2)
    }

    .drawer {
      position: fixed;
      top: 20px;
      right: 20px;
      bottom: 20px;
      width: min(440px, calc(100% - 24px));
      background: var(--side);
      border: 1px solid var(--edge);
      border-radius: 30px;
      z-index: 50;
      padding: 26px;
      overflow: auto;
      transform: translateX(120%);
      transition: transform .35s cubic-bezier(.2, .7, .2, 1);
      box-shadow: var(--sh);
      backdrop-filter: blur(var(--bl, 22px)) saturate(1.5)
    }

    .drawer.on {
      transform: none
    }

    .drawer h3.s {
      font-size: 16px;
      margin: 18px 0 6px
    }

    .tl {
      list-style: none;
      padding: 0;
      border-left: 2px solid var(--line);
      margin-left: 8px
    }

    .tl li {
      padding: 0 0 14px 16px;
      position: relative
    }

    .tl li::before {
      content: "";
      position: absolute;
      left: -7px;
      top: 6px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: var(--blue2)
    }

    .ticket {
      border: 1px solid var(--edge);
      border-radius: 24px;
      padding: 22px;
      text-align: center;
      background: var(--panel2)
    }

    .qr {
      display: grid;
      place-items: center;
      background: #fff;
      border-radius: 16px;
      padding: 12px;
      width: fit-content;
      margin: 14px auto
    }

    .cal {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 6px;
      text-align: center
    }

    .cal b {
      font-size: 12px;
      color: var(--tx3);
      font-weight: 600
    }

    .cal button {
      aspect-ratio: 1;
      border-radius: 14px;
      border: 1px solid transparent;
      background: var(--panel2);
      cursor: pointer;
      position: relative;
      min-height: 38px
    }

    .cal button:hover {
      border-color: var(--blue2)
    }

    .cal button.td {
      border-color: var(--blue2)
    }

    .cal button.ed {
      background: linear-gradient(135deg, #F0CF8E, #D6B568);
      color: #1a1405;
      font-weight: 700
    }

    .cal button.ev::after {
      content: "";
      position: absolute;
      bottom: 5px;
      left: 50%;
      width: 6px;
      height: 6px;
      margin-left: -3px;
      border-radius: 50%;
      background: var(--blue2)
    }

    .mx {
      min-width: 0
    }

    .mx td,
    .mx th {
      text-align: center
    }

    .mx td:first-child,
    .mx th:first-child {
      text-align: left
    }

    .rng {
      width: 100%;
      accent-color: var(--blue2)
    }

    .pre {
      display: flex;
      gap: 10px;
      flex-wrap: wrap
    }

    .pre button {
      width: 92px;
      height: 58px;
      border-radius: 16px;
      border: 2px solid var(--line);
      cursor: pointer;
      color: #fff;
      font-size: 12px;
      font-weight: 600
    }

    .pre button.on {
      border-color: var(--gold)
    }

    .cmdk {
      position: fixed;
      inset: 0;
      z-index: 90;
      display: none;
      justify-content: center;
      align-items: flex-start;
      padding-top: 14vh;
      background: rgba(4, 8, 20, .5);
      backdrop-filter: blur(6px)
    }

    .cmdk.on {
      display: flex
    }

    .cmdb {
      width: min(580px, 92%);
      background: var(--side);
      border: 1px solid var(--edge);
      border-radius: 26px;
      box-shadow: var(--sh);
      overflow: hidden;
      backdrop-filter: blur(var(--bl, 22px)) saturate(1.5);
      animation: pop .2s
    }

    .cmdb input {
      width: 100%;
      border: 0;
      background: none;
      padding: 18px 22px;
      font-size: 17px;
      border-bottom: 1px solid var(--line);
      outline: 0
    }

    .cmdl {
      max-height: 50vh;
      overflow: auto;
      padding: 8px
    }

    .cmdl button {
      display: flex;
      align-items: center;
      gap: 12px;
      width: 100%;
      text-align: left;
      background: none;
      border: 0;
      border-radius: 14px;
      padding: 10px 12px;
      cursor: pointer
    }

    .cmdl button.on,
    .cmdl button:hover {
      background: rgba(73, 121, 255, .22)
    }

    .cmdl small {
      margin-left: auto;
      color: var(--tx3)
    }

    .notif {
      position: fixed;
      top: 92px;
      right: 32px;
      width: 340px;
      max-width: calc(100% - 24px);
      z-index: 40;
      background: var(--side);
      border: 1px solid var(--edge);
      border-radius: 24px;
      padding: 14px;
      box-shadow: var(--sh);
      display: none;
      backdrop-filter: blur(var(--bl, 22px)) saturate(1.5)
    }

    .notif.on {
      display: block;
      animation: pop .2s
    }

    .notif a {
      display: flex;
      gap: 12px;
      padding: 10px;
      border-radius: 14px;
      text-decoration: none;
      color: var(--tx);
      cursor: pointer;
      align-items: center
    }

    .notif a:hover {
      background: var(--panel2)
    }

    .cmp .card {
      padding: 15px
    }

    .cmp td {
      padding: 8px 14px
    }

    .cmp .grid {
      gap: 12px
    }

    @media(max-width:960px) {
      .srchbtn {
        min-width: 0;
        padding: 10px
      }

      .srchbtn span,
      .srchbtn kbd {
        display: none
      }

      .coll {
        display: none
      }

      .drawer {
        top: 12px;
        right: 12px;
        bottom: 12px
      }


      .notif {
        top: 76px;
        right: 12px
      }

    }

    @media print {
      body>*:not(#ov) {
        display: none !important
      }

      #ov {
        position: static !important;
        display: block !important;
        background: none !important;
        backdrop-filter: none !important
      }

      .md {
        box-shadow: none !important;
        border: 0 !important;
        background: #fff !important;
        width: 100% !important;
        max-height: none !important;
        animation: none !important
      }

      .md,
      .md * {
        color: #000 !important
      }

      .noprint {
        display: none !important
      }
    }
  </style>

@vite('resources/css/admin-components.css')
</head>

