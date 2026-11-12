<style>
/* The page's look comes from the findings design. Its names stay inside .psrp-findings,
   so the sidebar, banner, upload buttons and records table keep their own. */
html { scroll-behavior: smooth; }
body { background: #eef5f0; }

.app-content { max-width: none; padding: 0; }

/* The layout follows the room left beside the sidebar, not the size of the screen. */
.psrp-page { container: psrp / inline-size; min-width: 0; --psrp-anchor-offset: 0px; --psrp-red: #A61B1B; color: var(--text); }

.psrp-findings {
  --bg: #eef5f0; --surface: #ffffff; --surface-2: #f4f9f5; --surface-3: #e2eee6;
  --ink: #0b2118; --ink-2: #3f5449; --muted: #647a6e; --grid: #e0ebe4;
  --line: rgba(14, 26, 20, 0.12); --line-strong: rgba(14, 26, 20, 0.24);
  --accent: #0b6b45; --accent-soft: #dcf0e4; --accent-ink: #ffffff; --deep: #063d28; --mint: #a9ecc8;
  --s1: #12875a; --s1-track: #dcf0e4;
  --good: #0ca30c; --good-ink: #006300; --good-soft: #e2f2e2;
  --warn: #fab219; --warn-ink: #7a5200; --warn-soft: #fff3d6;
  --crit: #d03b3b; --crit-ink: #ad2828; --crit-soft: #fbe7e7;
  --info-ink: #0b6b5e; --info-soft: #d9f2ec;
  --shadow: 0 1px 2px rgba(6, 61, 40, 0.05), 0 10px 28px rgba(6, 61, 40, 0.08);
  --font: "Plus Jakarta Sans", "Public Sans", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --radius: 14px; --radius-sm: 8px;
  color-scheme: light;
  color: var(--ink);
  font: 15px/1.5 var(--font);
}

.psrp-findings h2, .psrp-findings h3, .psrp-findings p { margin: 0; }
.psrp-findings button, .psrp-findings input, .psrp-findings select { font: inherit; color: inherit; }
.psrp-findings button { cursor: pointer; }
.psrp-findings :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
.psrp-wrap { max-width: 1360px; margin: 0 auto; padding: 0 24px; }
.psrp-findings.filterbar { position: sticky; top: var(--topbar-height); z-index: 900; background: var(--bg); border-bottom: 1px solid var(--line); }
.psrp-findings.filterbar .psrp-wrap { display: flex; align-items: center; gap: 12px; padding-top: 9px; padding-bottom: 9px; flex-wrap: wrap; }
.psrp-findings.filterbar label { font-weight: 650; font-size: 14px; }
.psrp-findings .ctl { background: var(--surface); border: 1px solid var(--line-strong); border-radius: var(--radius-sm); padding: 7px 10px; min-height: 36px; }
.psrp-findings .scope { color: var(--muted); font-size: 13px; }
.psrp-findings .nav { margin-left: auto; display: flex; gap: 16px; font-size: 14px; flex-wrap: wrap; }
.psrp-findings .nav a { color: var(--ink-2); text-decoration: none; }
.psrp-findings .nav a:hover { color: var(--accent); text-decoration: underline; }
.psrp-findings .link { background: none; border: 0; color: var(--accent); text-decoration: underline; padding: 2px; }
.psrp-findings section { padding-top: 34px; }
.psrp-findings .sec-h { margin-bottom: 14px; }
.psrp-findings h2 { font-size: 22px; line-height: 1.25; letter-spacing: -0.01em; }
.psrp-findings .sec-h p { color: var(--ink-2); margin-top: 4px; max-width: 92ch; }
.psrp-findings h3 { font-size: 16px; line-height: 1.3; }
.psrp-findings .card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); }
.psrp-findings .verdict { font-size: 18px; line-height: 1.45; padding: 16px 20px; border-left: 4px solid var(--accent); margin-bottom: 14px; }
.psrp-findings .verdict b { font-weight: 720; }
.psrp-findings .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.psrp-findings .stat { padding: 16px 18px; border-top: 4px solid var(--line-strong); }
.psrp-findings .stat.t-crit { border-top-color: var(--crit); }
.psrp-findings .stat.t-info { border-top-color: var(--s1); }
.psrp-findings .stat.t-warn { border-top-color: var(--warn); }
.psrp-findings .stat.t-good { border-top-color: var(--good); }
.psrp-findings .stat-lbl { font-weight: 680; font-size: 14px; }
.psrp-findings .stat-num { font-size: 36px; font-weight: 750; line-height: 1.2; margin-top: 3px; letter-spacing: -0.015em; }
.psrp-findings .stat-sub { color: var(--ink-2); font-size: 13.5px; line-height: 1.4; margin-top: 3px; }
.psrp-findings .progress { padding: 14px 18px; margin-top: 14px; }
.psrp-findings .progress-h { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; font-size: 14px; }
.psrp-findings .track { height: 12px; background: var(--surface-3); border-radius: 4px; overflow: hidden; margin-top: 8px; }
.psrp-findings .track > i { display: block; height: 100%; background: var(--s1); border-radius: 0 4px 4px 0; min-width: 2px; }
.psrp-findings .out-h { margin: 24px 0 10px; }
.psrp-findings .out-h p { color: var(--ink-2); font-size: 14px; margin-top: 2px; }
.psrp-findings .outs { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
.psrp-findings .out { padding: 12px 14px; }
.psrp-findings .out-num { font-size: 26px; font-weight: 740; line-height: 1.2; }
.psrp-findings .out-lbl { font-weight: 650; font-size: 14px; line-height: 1.3; }
.psrp-findings .out-sub { color: var(--muted); font-size: 12.5px; line-height: 1.35; margin-top: 3px; }
.psrp-findings .callout { display: flex; gap: 10px; background: var(--warn-soft); color: var(--ink); border-radius: var(--radius); padding: 12px 16px; margin-top: 14px; font-size: 14.5px; }
.psrp-findings .callout svg { flex: none; margin-top: 3px; fill: var(--warn-ink); }
.psrp-findings .since { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; }
.psrp-findings .since .card { padding: 14px 18px; }
.psrp-findings .since-num { font-size: 26px; font-weight: 740; margin-top: 3px; line-height: 1.25; }
.psrp-findings .since-sub { color: var(--ink-2); font-size: 13.5px; margin-top: 3px; }
.psrp-findings .tools { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 10px; }
.psrp-findings .seg { display: inline-flex; border: 1px solid var(--line-strong); border-radius: var(--radius-sm); overflow: hidden; background: var(--surface); }
.psrp-findings .seg button { border: 0; background: none; padding: 7px 12px; min-height: 36px; font-size: 14px; }
.psrp-findings .seg button + button { border-left: 1px solid var(--line); }
.psrp-findings .seg button[aria-pressed="true"] { background: var(--accent); color: var(--accent-ink); font-weight: 650; }
.psrp-findings .search { position: relative; flex: 1 1 240px; max-width: 400px; }
.psrp-findings .search input { width: 100%; }
.psrp-findings .results { position: absolute; left: 0; right: 0; top: 100%; margin-top: 4px; z-index: 950; background: var(--surface); border: 1px solid var(--line-strong); border-radius: var(--radius-sm); box-shadow: var(--shadow); max-height: 280px; overflow: auto; }
.psrp-findings .results button { display: block; width: 100%; text-align: left; border: 0; background: none; padding: 8px 10px; font-size: 14px; line-height: 1.3; }
.psrp-findings .results button:hover, .psrp-findings .results button:focus { background: var(--surface-3); }
.psrp-findings .results small { color: var(--muted); display: block; }
.psrp-findings .mapgrid { display: grid; grid-template-columns: minmax(0, 1fr) 430px; gap: 14px; }
.psrp-findings .mapcard { position: relative; overflow: hidden; height: 680px; isolation: isolate; }
.psrp-findings #map { position: absolute; inset: 0; background: var(--surface-3); }
.psrp-findings .leaflet-container { font: 13px/1.4 var(--font); }
.psrp-findings .legend { position: absolute; left: 10px; bottom: 22px; z-index: 900; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-sm); box-shadow: var(--shadow); padding: 8px 11px; font-size: 13px; line-height: 1.7; }
.psrp-findings .lg { display: flex; align-items: center; gap: 8px; }
.psrp-findings .pin { width: 12px; height: 12px; border-radius: 50%; flex: none; }
.psrp-findings .pin.v { background: var(--good); }
.psrp-findings .pin.n { background: var(--surface); border: 2.5px solid var(--crit); }
.psrp-findings .lg-n { color: var(--muted); font-size: 12px; }
.psrp-findings .panel { height: 680px; overflow-y: auto; padding: 18px; }
.psrp-findings .panel-hint { color: var(--ink-2); }
.psrp-findings .panel-hint h3 { margin-bottom: 6px; color: var(--ink); }
.psrp-findings .panel-hint ul { padding-left: 19px; margin: 10px 0 0; }
.psrp-findings .panel-hint li { margin-bottom: 6px; }
.psrp-findings .p-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
.psrp-findings .p-name { font-size: 18px; font-weight: 720; line-height: 1.25; }
.psrp-findings .p-meta { color: var(--ink-2); font-size: 13.5px; margin-top: 3px; }
.psrp-findings .x { flex: none; width: 30px; height: 30px; border: 1px solid var(--line); border-radius: var(--radius-sm); background: var(--surface); font-size: 16px; line-height: 1; color: var(--ink-2); }
.psrp-findings .badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.psrp-findings .badge { display: inline-flex; align-items: center; gap: 5px; border-radius: 99px; padding: 3px 10px; font-size: 13px; font-weight: 650; }
.psrp-findings .b-good { background: var(--good-soft); color: var(--good-ink); }
.psrp-findings .b-crit { background: var(--crit-soft); color: var(--crit-ink); }
.psrp-findings .b-warn { background: var(--warn-soft); color: var(--warn-ink); }
.psrp-findings .b-info { background: var(--info-soft); color: var(--info-ink); }
.psrp-findings .b-mute { background: var(--surface-3); color: var(--ink-2); }
.psrp-findings .p-sec { margin-top: 18px; }
.psrp-findings .p-sec h3 { font-size: 14px; margin-bottom: 6px; }
.psrp-findings .chips { display: flex; flex-wrap: wrap; gap: 5px; }
.psrp-findings .chip { background: var(--crit-soft); color: var(--crit-ink); border-radius: 4px; padding: 2px 8px; font-size: 12.5px; font-weight: 600; }
.psrp-findings .reply { background: var(--surface-2); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 11px 13px; font-size: 14px; }
.psrp-findings .reply p + p { margin-top: 7px; }
.psrp-findings .reply small { display: block; color: var(--muted); font-size: 12px; font-weight: 650; margin-bottom: 1px; }
.psrp-findings table { border-collapse: collapse; width: 100%; }
.psrp-findings .rt td { padding: 5px 0; border-top: 1px solid var(--grid); font-size: 14px; vertical-align: top; }
.psrp-findings .rt td:last-child { text-align: right; font-weight: 600; padding-left: 12px; white-space: nowrap; }
.psrp-findings .rt tr:first-child td { border-top: 0; }
.psrp-findings .ok { color: var(--good-ink); }
.psrp-findings .bad { color: var(--crit-ink); }
.psrp-findings .na { color: var(--muted); font-weight: 400; }
.psrp-findings .map-note { color: var(--muted); font-size: 13px; margin-top: 8px; }
.psrp-findings .tiles { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 14px; }
.psrp-findings .tile { text-align: left; background: var(--surface); border: 1px solid var(--line); border-left: 4px solid var(--crit); border-radius: var(--radius); padding: 12px 14px; box-shadow: var(--shadow); }
.psrp-findings .tile:hover { background: var(--surface-2); }
.psrp-findings .tile b { display: block; font-size: 26px; line-height: 1.2; font-weight: 740; }
.psrp-findings .tile span { display: block; font-weight: 650; font-size: 14px; line-height: 1.3; }
.psrp-findings .tile small { display: block; color: var(--muted); font-size: 12.5px; line-height: 1.35; margin-top: 3px; }
.psrp-findings .issgrid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr); gap: 14px; align-items: start; }
.psrp-findings .pad { padding: 16px 18px; }
.psrp-findings .sub { color: var(--muted); font-size: 13.5px; margin-top: 2px; }
.psrp-findings .rows { margin-top: 10px; }
.psrp-findings .irow { display: grid; grid-template-columns: 200px minmax(60px, 1fr) 96px 92px; align-items: center; gap: 10px; width: 100%; background: none; border: 0; border-top: 1px solid var(--grid); padding: 7px 4px; text-align: left; font-size: 14px; }
.psrp-findings .irow:hover { background: var(--surface-2); }
.psrp-findings .irow-h { font-size: 12.5px; color: var(--muted); padding-bottom: 3px; border-top: 0; font-weight: 650; }
.psrp-findings .irow-h:hover { background: none; }
.psrp-findings .bar { height: 10px; background: var(--surface-3); border-radius: 0 4px 4px 0; overflow: hidden; }
.psrp-findings .bar > i { display: block; height: 100%; background: var(--s1); border-radius: 0 4px 4px 0; }
.psrp-findings .num { text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings .pct { color: var(--muted); font-size: 12.5px; }
.psrp-findings .flag { color: var(--warn-ink); font-weight: 750; }
.psrp-findings .note { font-size: 13px; color: var(--ink-2); margin-top: 10px; }
.psrp-findings .note + .note { margin-top: 5px; }
.psrp-findings .top button { display: flex; justify-content: space-between; gap: 10px; width: 100%; background: none; border: 0; border-top: 1px solid var(--grid); padding: 7px 4px; text-align: left; font-size: 14px; line-height: 1.3; }
.psrp-findings .top button:hover { background: var(--surface-2); }
.psrp-findings .top small { display: block; color: var(--muted); font-size: 12.5px; }
.psrp-findings .top b { flex: none; font-variant-numeric: tabular-nums; }
.psrp-findings .insights { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }
.psrp-findings .insight { padding: 12px 16px; font-size: 14.5px; }
.psrp-findings .insight small { display: block; color: var(--muted); font-weight: 650; font-size: 12.5px; margin-bottom: 2px; }
.psrp-findings .tablewrap { overflow-x: auto; }
.psrp-findings .dt { font-size: 14px; min-width: 900px; }
.psrp-findings .dt th { position: sticky; top: 0; background: var(--surface-2); text-align: left; font-size: 13px; font-weight: 650; padding: 0; border-bottom: 1px solid var(--line-strong); vertical-align: bottom; }
.psrp-findings .dt th button { width: 100%; text-align: inherit; border: 0; background: none; padding: 9px 10px; line-height: 1.25; }
.psrp-findings .dt th.r { text-align: right; }
.psrp-findings .dt td { padding: 7px 10px; border-top: 1px solid var(--grid); }
.psrp-findings .dt td.r { text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings .dt tbody tr { cursor: pointer; }
.psrp-findings .dt tbody tr:hover { background: var(--surface-2); }
.psrp-findings .dt tr.on { background: var(--accent-soft); }
.psrp-findings .dt .dname { font-weight: 650; background: none; border: 0; padding: 0; text-align: left; }
.psrp-findings .cov { display: flex; align-items: center; gap: 8px; min-width: 130px; }
.psrp-findings .cov .bar { flex: 1; }
.psrp-findings .cov span { width: 38px; text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings.filterbar { background: var(--surface); box-shadow: 0 2px 12px rgba(6, 61, 40, 0.08); border-bottom: 0; }
.psrp-findings .nav a { padding: 4px 10px; border-radius: 99px; font-weight: 550; }
.psrp-findings .nav a:hover { background: var(--accent-soft); color: var(--accent); text-decoration: none; }
.psrp-findings section { padding-top: 42px; }
.psrp-findings h2 { font-weight: 800; font-size: 24px; }
.psrp-findings h2::before { content: ""; display: block; width: 38px; height: 4px; border-radius: 4px; background: var(--s1); margin-bottom: 9px; }
.psrp-findings h3 { font-weight: 700; }
.psrp-findings .card { border-color: transparent; }
.psrp-findings .verdict { color: #fff; border: 0; padding: 20px 24px; font-size: 19px; font-weight: 500; background: linear-gradient(110deg, var(--deep), #0d7049); }
.psrp-findings .verdict b { color: var(--mint); font-weight: 750; }
.psrp-findings .stat { border-top: 0; position: relative; padding: 18px 20px; }
.psrp-findings .stat-top { display: flex; align-items: center; gap: 10px; }
.psrp-findings .stat-ico { flex: none; width: 38px; height: 38px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; }
.psrp-findings .stat-ico svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.psrp-findings .t-crit .stat-ico { background: var(--crit-soft); color: var(--crit-ink); }
.psrp-findings .t-info .stat-ico { background: var(--accent-soft); color: var(--accent); }
.psrp-findings .t-warn .stat-ico { background: var(--warn-soft); color: var(--warn-ink); }
.psrp-findings .t-good .stat-ico { background: var(--good-soft); color: var(--good-ink); }
.psrp-findings .stat-num { font-size: 40px; font-weight: 800; margin-top: 8px; }
.psrp-findings .track { background: var(--surface-3); height: 14px; border-radius: 99px; }
.psrp-findings .track > i { background: linear-gradient(90deg, #0b6b45, #2fb57f); border-radius: 99px; }
.psrp-findings .out { border-bottom: 3px solid var(--s1); }
.psrp-findings .out-num { font-weight: 800; color: var(--accent); }
.psrp-findings .callout { border-radius: var(--radius); }
.psrp-findings .since-num { color: var(--accent); font-weight: 800; }
.psrp-findings .seg { border-radius: 99px; }
.psrp-findings .seg button[aria-pressed="true"] { background: var(--accent); }
.psrp-findings .ctl { border-color: var(--line); border-radius: var(--radius-sm); }
.psrp-findings .mapcard, .psrp-findings .panel { box-shadow: var(--shadow); }
.psrp-findings .p-name { font-weight: 800; }
.psrp-findings .reply { border-left: 3px solid var(--s1); }
.psrp-findings .tile { border: 0; border-top: 4px solid var(--crit); transition: transform .15s ease, box-shadow .15s ease; }
.psrp-findings .tile:hover { transform: translateY(-2px); background: var(--surface); box-shadow: 0 14px 30px rgba(6, 61, 40, 0.14); }
.psrp-findings .tile { display: flex; flex-direction: column; justify-content: flex-start; }
.psrp-findings .tile b { font-weight: 800; }
.psrp-findings .irow { grid-template-columns: 245px minmax(60px, 1fr) 96px 92px; }
.psrp-findings .bar { border-radius: 99px; }
.psrp-findings .bar > i { background: linear-gradient(90deg, #0b6b45, #2fb57f); border-radius: 99px; }
.psrp-findings .irow:hover, .psrp-findings .top button:hover { background: var(--accent-soft); }
.psrp-findings .top b { background: var(--crit-soft); color: var(--crit-ink); border-radius: 99px; padding: 1px 10px; font-size: 12.5px; align-self: center; }
.psrp-findings .insight { border-left: 4px solid var(--s1); border-radius: var(--radius); }
.psrp-findings .dt th { background: var(--accent-soft); color: var(--accent); }
.psrp-findings .dt tbody tr:hover { background: var(--accent-soft); }
@media (prefers-reduced-motion: reduce) {
  .psrp-findings .tile { transition: none; }
  .psrp-findings .tile:hover { transform: none; }
}
@container psrp (max-width: 1100px) {
  .psrp-findings .mapgrid, .psrp-findings .issgrid { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .mapcard { height: 500px; }
  .psrp-findings .panel { height: auto; max-height: 720px; }
  .psrp-findings .outs, .psrp-findings .tiles { grid-template-columns: repeat(3, 1fr); }
  .psrp-findings .stats { grid-template-columns: repeat(2, 1fr); }
}
@container psrp (max-width: 720px) {
  .psrp-wrap { padding: 0 16px; }
  .psrp-findings .outs, .psrp-findings .tiles, .psrp-findings .insights, .psrp-findings .since { grid-template-columns: 1fr 1fr; }
  .psrp-findings .since { grid-template-columns: 1fr; }
  .psrp-findings .insights { grid-template-columns: 1fr; }
  .psrp-findings .nav { display: none; }
  .psrp-findings .mapcard { height: 420px; }
  .psrp-findings .irow { grid-template-columns: minmax(0, 1fr) 62px 62px; }
  .psrp-findings .irow .bar, .psrp-findings .irow .hb { display: none; }
  .psrp-findings .stat-num { font-size: 30px; }
}
@container psrp (max-width: 420px) {
  .psrp-findings .stats, .psrp-findings .outs, .psrp-findings .tiles { grid-template-columns: 1fr; }
}

/* Additions to the design: the date range, and the states it needs that the design did not. */
.psrp-breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; padding-top: 10px; padding-bottom: 10px; color: var(--muted); font-size: 11px; overflow-wrap: anywhere; }
.psrp-breadcrumb a { color: var(--primary); font-weight: 600; }
.psrp-breadcrumb a:hover { text-decoration: underline; }
.psrp-breadcrumb svg { width: 13px; height: 13px; }
/* Export to PDF, at the end of the breadcrumb row (see pdf-export). */
.psrp-pdf-btn { margin-left: auto; display: inline-flex; align-items: center; gap: 7px; padding: 7px 14px; border: 1px solid #0b6b45; border-radius: 8px; background: #0b6b45; color: #fff; font: 600 13px/1.2 "Inter", "Segoe UI", system-ui, sans-serif; white-space: nowrap; cursor: pointer; box-shadow: 0 1px 2px rgba(0, 0, 0, .08); }
.psrp-pdf-btn:hover { background: #063d28; border-color: #063d28; }
.psrp-pdf-btn:focus-visible { outline: 2px solid #c9a24a; outline-offset: 2px; }
.psrp-pdf-btn:disabled { opacity: .75; cursor: progress; }
.psrp-pdf-btn svg { width: 16px; height: 16px; flex: none; }
.psrp-findings .fg { display: inline-flex; align-items: center; gap: 8px; min-width: 0; }
.psrp-findings .fb-meta { display: flex; align-items: center; flex: 1 1 auto; flex-wrap: wrap; gap: 4px 12px; min-width: 0; }
.psrp-findings .ctl { max-width: 100%; }

/* The filter bar keeps the filters and the section links on one row where they fit, on every
   initiative page. What the page shows gives way first: it is cut short with "…" (hovering shows
   all of it), then left out, and the links close up a little. Only then do the links take a
   second row. --nav-text is the links' own width, which each page's script sets. */
.psrp-findings.filterbar .fb-meta { flex: 1 1 var(--nav-text, 420px); flex-wrap: nowrap; }
.psrp-findings.filterbar .scope { flex: 1 1 0; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.psrp-findings.filterbar .nav { flex: none; flex-wrap: nowrap; white-space: nowrap; }
.psrp-findings.filterbar .nav a { padding-left: clamp(0px, calc((100cqi - var(--nav-text, 420px)) / 12), 10px); padding-right: clamp(0px, calc((100cqi - var(--nav-text, 420px)) / 12), 10px); }
/* Phones have no links in the bar, so there the text always shows. */
@container psrp (min-width: 721px) {
  .psrp-findings.filterbar .fb-meta { container: filterMeta / inline-size; }
}
@container filterMeta (max-width: 600px) {
  .psrp-findings.filterbar .scope { display: none; }
}

.psrp-findings .dl-btn { display: inline-flex; align-items: center; gap: 6px; background: var(--accent); border-color: var(--accent); color: var(--accent-ink); font-weight: 650; white-space: nowrap; }
.psrp-findings .dl-btn:hover { background: var(--deep); border-color: var(--deep); }
.psrp-findings .dl-btn svg { width: 16px; height: 16px; }
.psrp-findings .verdict .link { color: var(--mint); }
.psrp-findings .verdict:empty { min-height: 66px; }

@container psrp (max-width: 720px) {
  /* Three filters would cover too much of a phone screen if they stayed on it. */
  .psrp-findings.filterbar { position: static; }
  .psrp-findings .fg { flex: 1 1 100%; }
  .psrp-findings .fg label { flex: 0 0 58px; }
  .psrp-findings .fg .ctl { flex: 1 1 auto; width: 100%; min-width: 0; }
  .psrp-findings .link { min-height: 32px; }
}

@container psrp (max-width: 900px) {
  /* Easier to tap. */
  .psrp-findings .nav a { display: inline-flex; align-items: center; min-height: 32px; }
}

@container psrp (max-width: 420px) {
  /* Gives the issue names more room on the smallest phones. */
  .psrp-findings .irow { grid-template-columns: minmax(0, 1fr) 54px 54px; gap: 6px; }
}

/* On very short screens only the banner stays, or the page would have no room left. */
@media (max-height: 600px) {
  .psrp-findings.filterbar { position: static; }
}

@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
}
</style>
