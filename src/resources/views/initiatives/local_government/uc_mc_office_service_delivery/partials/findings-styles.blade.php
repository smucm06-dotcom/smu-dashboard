<style>
/* What the UC MC design adds to the findings styles shared with the other initiative pages. The design is
   built on the Special Education one, which is built on the PSRP one, so this keeps only the parts the
   UC MC sections use. Screen sizes follow the room beside the sidebar (the "psrp" container), as on
   the other pages. */
.psrp-findings {
  /* The design's font. Optical sizing sharpens the big numbers. */
  --font: "Inter", "Segoe UI", system-ui, -apple-system, Roboto, sans-serif;
  /* Response types: greens acted, grey neutral, warm not acted. */
  --r-fixed: #0b6b45; --r-dir: #4fa97f; --r-plan: #c4cbc7; --r-disp: #e8a53a; --r-nota: #c9452f; --r-none: #841a13;
  /* Lines of the trend chart, and the office pins: serving, open but not serving, closed. */
  --c-open: #12875a; --c-pres: #2f6db5; --c-full: #b4541a; --st-s: #0ca30c; --st-p: #e09b06; --st-c: #d03b3b;
  font-optical-sizing: auto;
}
.psrp-findings h2 { letter-spacing: -0.02em; }
.psrp-findings .tile b, .psrp-findings .out-num, .psrp-findings .imp-num { letter-spacing: -0.02em; }
.psrp-findings.filterbar input[type=date] { min-width: 142px; }
.psrp-tip { position: fixed; z-index: 2000; pointer-events: none; background: var(--surface); color: var(--ink); border: 1px solid var(--line-strong); border-radius: var(--radius-sm); box-shadow: var(--shadow); padding: 6px 10px; font-size: 13px; line-height: 1.35; max-width: 280px; }
.psrp-tip b { display: block; }

/* The banner's four cards are the steps of the work, numbered as in the design, with arrows between
   them where there is room. Only this page's banner. */
.topbar-stats-grid { counter-reset: ucmc-step; }
.topbar-stat-card { counter-increment: ucmc-step; position: relative; }
.topbar-stat-label { display: flex; align-items: center; gap: 7px; }
.topbar-stat-label::before { content: counter(ucmc-step); flex: none; width: 19px; height: 19px; border-radius: 50%; background: #a9ecc8; color: #063d28; font-size: 11px; font-weight: 800; letter-spacing: 0; display: inline-flex; align-items: center; justify-content: center; }
/* On phones the cards are narrow, so the number sits above the label. */
@media (max-width: 600px) {
  .topbar-stat-label { display: block; }
  .topbar-stat-label::before { display: flex; width: 14px; height: 14px; font-size: 8.5px; margin-bottom: 3px; }
}
@media (min-width: 1200px) {
  .topbar-stats-grid { column-gap: 22px; }
  .topbar-stat-card + .topbar-stat-card::before { content: "\2192"; position: absolute; left: -16px; top: 50%; transform: translateY(-50%); color: #a9ecc8; font-weight: 700; font-size: 15px; line-height: 1; }
}

/* Impact */
.psrp-findings .impact { display: grid; gap: 16px; }
.psrp-findings .impact.two { grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 0 0 14px; }
.psrp-findings .imp-card { background: var(--surface); color: var(--ink); border-radius: var(--radius); border-left: 5px solid var(--accent); padding: 16px 20px; display: grid; grid-template-columns: minmax(0, 1fr); align-content: start; box-shadow: var(--shadow); }
.psrp-findings .imp-tag { grid-column: 1 / -1; justify-self: start; font-size: 11.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); background: var(--accent-soft); border-radius: 99px; padding: 2px 10px; margin-bottom: 8px; }
.psrp-findings .imp-num { font-size: 40px; font-weight: 800; line-height: 1; color: var(--accent); margin-bottom: 6px; }
.psrp-findings .imp-txt b { display: block; font-size: 16px; line-height: 1.3; font-weight: 750; }
.psrp-findings .imp-txt small { display: block; color: var(--ink-2); font-size: 13.5px; line-height: 1.45; margin-top: 4px; }

/* Issues */
.psrp-findings .tiles.nine { grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 0; }

/* Trend chart */
.psrp-findings .key-chart { border-left: 5px solid var(--st-c); }
.psrp-findings .key-chart .kf { font-size: 18px; line-height: 1.4; font-weight: 650; margin: 8px 0 10px; max-width: 72ch; }
.psrp-findings .key-chart .kf b { color: var(--st-c); font-size: 30px; font-weight: 800; letter-spacing: -0.02em; margin-right: 4px; }
.psrp-findings .chart { width: 100%; height: auto; display: block; margin-top: 6px; overflow: visible; }
.psrp-findings .chart .gl { stroke: var(--grid); stroke-width: 1; }
.psrp-findings .chart .ax { fill: var(--muted); font-size: 12px; }
.psrp-findings .chart .axb { fill: var(--ink-2); font-size: 12.5px; font-weight: 650; }
.psrp-findings .chart .vl { font-size: 12.5px; font-weight: 750; }
.psrp-findings .chart .hit { fill: transparent; }
.psrp-findings .chart .hit:hover { fill: var(--accent-soft); fill-opacity: .55; }
.psrp-findings .ckey { display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: 13px; color: var(--ink-2); margin-top: 4px; }
.psrp-findings .ckey span { display: inline-flex; align-items: center; gap: 7px; }
.psrp-findings .ckey i { display: inline-block; width: 18px; height: 3px; border-radius: 2px; }
.psrp-findings .ckey i.thick { height: 5px; }

/* Department response */
.psrp-findings .vt { font-size: 14px; min-width: 860px; }
.psrp-findings .vt th { text-align: left; font-size: 13px; font-weight: 650; color: var(--accent); background: var(--accent-soft); padding: 9px 10px; vertical-align: bottom; line-height: 1.25; }
.psrp-findings .vt th.r, .psrp-findings .vt td.r { text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings .vt td { padding: 10px; border-top: 1px solid var(--grid); }
.psrp-findings .vt .lab { font-weight: 650; }
.psrp-findings .vt tbody tr[data-filter] { cursor: pointer; }
.psrp-findings .vt tbody tr[data-filter]:hover { background: var(--accent-soft); }
.psrp-findings .stack { display: flex; gap: 2px; height: 12px; min-width: 170px; }
.psrp-findings .stack i { display: block; height: 100%; }
.psrp-findings .stack i:first-child { border-radius: 4px 0 0 4px; }
.psrp-findings .stack i:last-child { border-radius: 0 4px 4px 0; }
.psrp-findings .iss-t td.lab { font-weight: 650; }
.psrp-findings .iss-t .stack { min-width: 220px; height: 14px; }
.psrp-findings .iss-t .stack i { min-width: 0; }
.psrp-findings .iss-t tr.tot td { border-top: 2px solid var(--line-strong); font-weight: 700; background: var(--surface-2); }
.psrp-findings .dn { color: var(--crit-ink); font-weight: 700; }
.psrp-findings .lgd2 { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: 13px; color: var(--ink-2); padding: 10px 14px 12px; border-top: 1px solid var(--grid); }
.psrp-findings .lgd2 span { display: inline-flex; align-items: center; gap: 6px; }
.psrp-findings .sw { display: inline-block; width: 11px; height: 11px; border-radius: 3px; flex: none; }
.psrp-findings .r-fixed { background: var(--r-fixed); } .psrp-findings .r-dir { background: var(--r-dir); } .psrp-findings .r-plan { background: var(--r-plan); }
.psrp-findings .r-disp { background: var(--r-disp); } .psrp-findings .r-nota { background: var(--r-nota); } .psrp-findings .r-none { background: var(--r-none); }
.psrp-findings .g2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; margin-top: 14px; align-items: start; }
.psrp-findings .g2.eq { align-items: stretch; }
.psrp-findings .g2.eq > .card { height: 100%; }
.psrp-findings .ans-card { padding: 16px 18px 18px; }
.psrp-findings .pad .sub, .psrp-findings .ans-card .sub { color: var(--muted); font-size: 13.5px; margin: 2px 0 8px; }
.psrp-findings .segbar { display: flex; gap: 2px; height: 30px; margin: 4px 0 12px; }
.psrp-findings .segbar i { display: block; height: 100%; min-width: 3px; }
.psrp-findings .segbar i:first-child { border-radius: 6px 0 0 6px; } .psrp-findings .segbar i:last-child { border-radius: 0 6px 6px 0; }
.psrp-findings .g2 #l-outs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.psrp-findings .out-lbl { display: flex; align-items: center; gap: 7px; }
.psrp-findings .out-num { color: var(--ink); }
.psrp-findings .why .irow { grid-template-columns: minmax(0, 1fr) minmax(60px, 0.8fr) 62px 62px; }
.psrp-findings .empty { color: var(--muted); font-size: 14px; padding: 6px 0; }

/* Checked again */
.psrp-findings .gs { display: flex; height: 12px; border-radius: 4px; overflow: hidden; gap: 2px; min-width: 120px; }
.psrp-findings .gs i { display: block; height: 100%; }
.psrp-findings .gs .g { background: var(--good); } .psrp-findings .gs .s { background: var(--crit); }
.psrp-findings .rck { display: grid; grid-template-columns: 220px minmax(100px, 1fr) 150px; gap: 12px; align-items: center; border-top: 1px solid var(--grid); padding: 8px 4px; font-size: 14px; width: 100%; background: none; border-left: 0; border-right: 0; border-bottom: 0; text-align: left; font: inherit; color: inherit; }
.psrp-findings button.rck:hover { background: var(--accent-soft); }
.psrp-findings .rck .num { text-align: right; font-variant-numeric: tabular-nums; color: var(--ink-2); }
.psrp-findings .rck-h { font-size: 12.5px; color: var(--muted); font-weight: 650; border-top: 0; }

/* Map pins, badges and the office report */
.psrp-findings .pin.ss { background: var(--st-s); }
.psrp-findings .pin.sp { background: var(--st-p); }
.psrp-findings .pin.sc { background: var(--st-c); }
.psrp-findings .pin.rr { background: var(--surface); box-shadow: 0 0 0 2.5px var(--ink); width: 9px; height: 9px; margin: 0 1.5px; }
.psrp-findings .b-st-S { background: var(--good-soft); color: var(--good-ink); }
.psrp-findings .b-st-P { background: var(--warn-soft); color: var(--warn-ink); }
.psrp-findings .b-st-C { background: var(--crit-soft); color: var(--crit-ink); }
.psrp-findings .tbadge { display: inline-block; font-size: 12px; font-weight: 650; border-radius: 99px; padding: 1px 8px; margin: 1px 4px 1px 0; }
.psrp-findings .chip.ok { background: var(--good-soft); color: var(--good-ink); }
.psrp-findings .rep { background: var(--surface-2); border-radius: var(--radius-sm); padding: 10px 12px; margin-top: 8px; border-left: 3px solid var(--s1); }
.psrp-findings .rep-h { font-size: 12.5px; color: var(--muted); font-weight: 650; margin-bottom: 4px; }
.psrp-findings .rlab { display: block; font-size: 11.5px; font-weight: 750; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); margin-top: 8px; }
/* The department's answer keeps its paragraphs, three lines until clicked. */
.psrp-findings .rresp { font-size: 13.5px; color: var(--ink-2); margin-top: 4px; cursor: pointer; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; white-space: pre-line; }
.psrp-findings .rresp.open { display: block; }
.psrp-findings .cnts { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
.psrp-findings .p-iss { margin-top: 6px; padding-left: 18px; }
.psrp-findings .p-iss li { margin: 2px 0; }
.psrp-findings details.visit > summary { cursor: pointer; color: var(--accent); font-weight: 650; font-size: 14px; margin-top: 12px; }

/* Districts */
.psrp-findings .dt { min-width: 1040px; }

/* Where the records start, a line on the sources. */
.psrp-findings.ucmc-sources { color: var(--muted); font-size: 13px; padding: 18px 0 36px; margin-top: 26px; border-top: 1px solid var(--line); }

/* The one-row filter bar is in the shared findings styles. This page has eight links, so sixteen
   paddings share the room left beside them. */
.psrp-findings.filterbar { --nav-pads: 16; }

@container psrp (max-width: 1100px) {
  .psrp-findings .impact.two { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .g2 { grid-template-columns: minmax(0, 1fr); }
}
@container psrp (max-width: 720px) {
  .psrp-findings .imp-num { font-size: 34px; }
  .psrp-findings .tiles.nine { grid-template-columns: 1fr 1fr; }
  .psrp-findings .rck { grid-template-columns: minmax(0, 1fr) 110px; }
  .psrp-findings .rck .gs { grid-column: 1 / -1; grid-row: 2; }
  .psrp-findings .iss-t .stack { min-width: 130px; }
  .psrp-findings .key-chart .kf { font-size: 16px; }
  .psrp-findings .key-chart .kf b { font-size: 26px; }
}
@container psrp (max-width: 420px) {
  .psrp-findings .tiles.nine { grid-template-columns: 1fr; }
  .psrp-findings .g2 #l-outs { grid-template-columns: 1fr; }
}
</style>
