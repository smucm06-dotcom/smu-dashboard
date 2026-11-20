<style>
/* What the Punjab Development Program design adds to the findings styles shared with the other initiative
   pages. The design is built on the Special Education one, as the Water Filtration Plants page is, so this
   keeps only the parts the PDP sections use. Screen sizes follow the room beside the sidebar (the "psrp"
   container), as on the other pages. */
.psrp-findings {
  /* The design's font. Optical sizing sharpens the big numbers. */
  --font: "Inter", "Segoe UI", system-ui, -apple-system, Roboto, sans-serif;
  /* Response types: done, instructions given, not answered. */
  --r-fixed: #0b6b45; --r-dir: #4fa97f; --r-plan: #c4cbc7; --r-disp: #e8a53a; --r-nota: #c9452f; --r-none: #841a13;
  /* The work sites at the latest visit: no problem, 1 to 3 problems, 4 or more, and not visited yet. */
  --st-g: #0ca30c; --st-s: #e09b06; --st-m: #d03b3b; --st-u: #9aa8a0;
  font-optical-sizing: auto;
}
.psrp-findings h2 { letter-spacing: -0.02em; }
.psrp-findings .tile b, .psrp-findings .out-num, .psrp-findings .imp-num { letter-spacing: -0.02em; }
.psrp-findings.filterbar input[type=date] { min-width: 142px; }
.psrp-tip { position: fixed; z-index: 2000; pointer-events: none; background: var(--surface); color: var(--ink); border: 1px solid var(--line-strong); border-radius: var(--radius-sm); box-shadow: var(--shadow); padding: 6px 10px; font-size: 13px; line-height: 1.35; max-width: 280px; }
.psrp-tip b { display: block; }

/* The banner's four cards are the steps of the work, as in the design: numbered like every initiative
   page's (shared findings styles), with arrows between them where there is room. Only this page's banner. */
.topbar-stat-card { position: relative; }
@media (min-width: 1200px) {
  .topbar-stats-grid { column-gap: 22px; }
  .topbar-stat-card + .topbar-stat-card::before { content: "\2192"; position: absolute; left: -16px; top: 50%; transform: translateY(-50%); color: #a9ecc8; font-weight: 700; font-size: 15px; line-height: 1; }
}

/* Impact */
.psrp-findings .impact { display: grid; gap: 16px; }
.psrp-findings .impact.two { grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 0 0 14px; }
.psrp-findings .imp-card { background: var(--surface); color: var(--ink); border-radius: var(--radius); border-left: 5px solid var(--accent); padding: 16px 20px; display: grid; grid-template-columns: minmax(0, 1fr); align-content: start; box-shadow: var(--shadow); }
.psrp-findings .imp-card.kcard { border-left-color: var(--st-m); }
.psrp-findings .imp-tag { grid-column: 1 / -1; justify-self: start; font-size: 11.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); background: var(--accent-soft); border-radius: 99px; padding: 2px 10px; margin-bottom: 8px; }
.psrp-findings .imp-num { font-size: 40px; font-weight: 800; line-height: 1; color: var(--accent); margin-bottom: 6px; }
.psrp-findings .imp-txt b { display: block; font-size: 16px; line-height: 1.3; font-weight: 750; }
.psrp-findings .imp-txt small { display: block; color: var(--ink-2); font-size: 13.5px; line-height: 1.45; margin-top: 4px; }

/* What PDP will build: the scope of work */
.psrp-findings .plan-g { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
.psrp-findings .plan-g > div { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-sm); padding: 12px 14px; box-shadow: var(--shadow); }
.psrp-findings .plan-g b { display: block; font-size: 24px; font-weight: 800; letter-spacing: -.01em; font-variant-numeric: tabular-nums; color: var(--ink); }
.psrp-findings .plan-g b small { font-size: 14px; font-weight: 650; color: var(--muted); margin-left: 3px; }
.psrp-findings .plan-g span { display: block; font-size: 14px; color: var(--ink-2); margin-top: 2px; }
.psrp-findings .plan-g em { display: block; font-style: normal; font-size: 12.5px; color: var(--muted); margin-top: 2px; }
.psrp-findings .plan-note { margin: 12px 0 0; font-size: 14px; color: var(--ink-2); }

/* Issues: how the sites did at the latest visit, and the problems found */
.psrp-findings .stcard { margin-bottom: 16px; }
.psrp-findings .segbar { display: flex; gap: 2px; height: 30px; margin: 4px 0 12px; }
.psrp-findings .segbar i { display: block; height: 100%; min-width: 3px; }
.psrp-findings .segbar i:first-child { border-radius: 6px 0 0 6px; } .psrp-findings .segbar i:last-child { border-radius: 0 6px 6px 0; }
.psrp-findings .stcard .outs { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.psrp-findings .stcard .out { background: var(--surface-2); border-radius: var(--radius-sm); padding: 10px 12px; text-align: left; font: inherit; color: inherit; border: 0; cursor: pointer; display: flex; flex-direction: column; gap: 2px; }
.psrp-findings .stcard .out:hover { background: var(--accent-soft); }
.psrp-findings .out-lbl { display: flex; align-items: center; gap: 7px; }
.psrp-findings .out-num { color: var(--ink); }
.psrp-findings .out-num span { font-size: 14px; font-weight: 600; color: var(--muted); }
.psrp-findings .sw { display: inline-block; width: 11px; height: 11px; border-radius: 3px; flex: none; }
.psrp-findings .sw.st-G, .psrp-findings .seg-G { background: var(--st-g); } .psrp-findings .sw.st-S, .psrp-findings .seg-S { background: var(--st-s); } .psrp-findings .sw.st-M, .psrp-findings .seg-M { background: var(--st-m); }
.psrp-findings .tgrp + .tgrp { margin-top: 16px; }
.psrp-findings .tgrp > h3 { font-size: 14px; font-weight: 750; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); margin: 0 0 8px; }
.psrp-findings .tiles.four { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 0; }

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
.psrp-findings .dn { color: var(--crit-ink); font-weight: 700; } .psrp-findings .up { color: var(--good-ink); font-weight: 700; }
.psrp-findings .lgd2 { display: flex; flex-wrap: wrap; gap: 6px 14px; font-size: 13px; color: var(--ink-2); padding: 10px 14px 12px; border-top: 1px solid var(--grid); }
.psrp-findings .lgd2 span { display: inline-flex; align-items: center; gap: 6px; }
.psrp-findings .r-fixed { background: var(--r-fixed); } .psrp-findings .r-dir { background: var(--r-dir); } .psrp-findings .r-plan { background: var(--r-plan); }
.psrp-findings .r-disp { background: var(--r-disp); } .psrp-findings .r-nota { background: var(--r-nota); } .psrp-findings .r-none { background: var(--r-none); }
.psrp-findings .pad .sub { color: var(--muted); font-size: 13.5px; margin: 2px 0 8px; }
.psrp-findings .empty { color: var(--muted); font-size: 14px; padding: 6px 0; }
/* The two SMU letters, side by side where there is room */
.psrp-findings .letters { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
.psrp-findings .letter h3 { margin: 0 0 2px; }
.psrp-findings .letter .lt-when { font-size: 13.5px; color: var(--muted); margin: 0 0 8px; }
.psrp-findings .letter .lt-when b { color: var(--ink-2); }
.psrp-findings .letter ul { margin: 6px 0 0; padding-left: 18px; font-size: 14px; color: var(--ink-2); }
.psrp-findings .letter li { margin: 3px 0; }
.psrp-findings .letters + .callout { margin-top: 14px; }

/* Back at the same cities */
.psrp-findings .gs { display: flex; height: 12px; border-radius: 4px; overflow: hidden; gap: 2px; min-width: 120px; }
.psrp-findings .gs i { display: block; height: 100%; }
.psrp-findings .gs .g { background: var(--good); } .psrp-findings .gs .s { background: var(--crit); }
.psrp-findings .rck { display: grid; grid-template-columns: 260px minmax(100px, 1fr) 150px; gap: 12px; align-items: center; border-top: 1px solid var(--grid); padding: 8px 4px; font-size: 14px; width: 100%; background: none; border-left: 0; border-right: 0; border-bottom: 0; text-align: left; font: inherit; color: inherit; }
.psrp-findings button.rck:hover { background: var(--accent-soft); }
.psrp-findings .rck .num { text-align: right; font-variant-numeric: tabular-nums; color: var(--ink-2); }
.psrp-findings .rck-h { font-size: 12.5px; color: var(--muted); font-weight: 650; border-top: 0; }
.psrp-findings .ckey { display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: 13px; color: var(--ink-2); margin-top: 10px; }
.psrp-findings .ckey span { display: inline-flex; align-items: center; gap: 7px; }
.psrp-findings .ckey i { display: inline-block; width: 18px; height: 6px; border-radius: 2px; }
.psrp-findings .rev-card { margin-top: 14px; }
.psrp-findings .chg { display: flex; align-items: center; gap: 10px; }
.psrp-findings .chg .stack { flex: 1; min-width: 120px; }
.psrp-findings .chg span { white-space: nowrap; font-size: 12.5px; color: var(--ink-2); min-width: 92px; }
.psrp-findings .s-less { background: var(--crit); } .psrp-findings .s-same { background: var(--line-strong); } .psrp-findings .s-more { background: var(--good); }
.psrp-findings .again { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-top: 8px; }
.psrp-findings .again button { display: flex; justify-content: space-between; gap: 10px; align-items: center; text-align: left; background: var(--surface-2); border: 0; border-radius: var(--radius-sm); padding: 9px 12px; font: inherit; color: inherit; cursor: pointer; }
.psrp-findings .again button:hover { background: var(--accent-soft); }
.psrp-findings .again small { display: block; color: var(--muted); font-size: 12.5px; }
/* The problems found at each visit, oldest first */
.psrp-findings .seq { display: flex; gap: 4px; align-items: center; flex-wrap: wrap; justify-content: flex-end; }
.psrp-findings .seq span { display: inline-block; min-width: 24px; text-align: center; font-weight: 750; font-size: 13.5px; border-radius: 6px; padding: 2px 5px; font-variant-numeric: tabular-nums; }
.psrp-findings .seq .sG { background: var(--good-soft); color: var(--good-ink); } .psrp-findings .seq .sS { background: var(--warn-soft); color: var(--warn-ink); } .psrp-findings .seq .sM { background: var(--crit-soft); color: var(--crit-ink); }

/* Map pins, badges and the city report */
.psrp-findings .pin.sG { background: var(--st-g); } .psrp-findings .pin.sS { background: var(--st-s); } .psrp-findings .pin.sM { background: var(--st-m); }
.psrp-findings .pin.su { background: var(--surface); box-shadow: 0 0 0 1.5px var(--st-u); width: 8px; height: 8px; margin: 0 2px; }
.psrp-findings .b-st-G { background: var(--good-soft); color: var(--good-ink); }
.psrp-findings .b-st-S { background: var(--warn-soft); color: var(--warn-ink); }
.psrp-findings .b-st-M { background: var(--crit-soft); color: var(--crit-ink); }
.psrp-findings .tbadge { display: inline-block; font-size: 12px; font-weight: 650; border-radius: 99px; padding: 1px 8px; margin: 1px 4px 1px 0; }
.psrp-findings .chip.ok { background: var(--good-soft); color: var(--good-ink); }
.psrp-findings .rep { background: var(--surface-2); border-radius: var(--radius-sm); padding: 10px 12px; margin-top: 8px; border-left: 3px solid var(--s1); }
.psrp-findings .rep-h { font-size: 12.5px; color: var(--muted); font-weight: 650; margin-bottom: 4px; }
.psrp-findings .rlab { display: block; font-size: 11.5px; font-weight: 750; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); margin-top: 8px; }
/* The department's answer keeps its paragraphs, three lines until clicked. */
.psrp-findings .rresp { font-size: 13.5px; color: var(--ink-2); margin-top: 4px; cursor: pointer; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; white-space: pre-line; }
.psrp-findings .rresp.open { display: block; }
.psrp-findings .p-iss { margin-top: 6px; padding-left: 18px; }
.psrp-findings .p-iss li { margin: 4px 0; }
.psrp-findings .p-iss li small { display: block; color: var(--muted); font-size: 12.5px; }
.psrp-findings .p-plan { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 12px; font-size: 13.5px; color: var(--ink-2); margin-top: 6px; }
.psrp-findings .p-plan b { color: var(--ink); font-variant-numeric: tabular-nums; }
.psrp-findings details.visit > summary { cursor: pointer; color: var(--accent); font-weight: 650; font-size: 14px; margin-top: 12px; }
/* The visit's photos and videos: small pictures three to a row that open the file itself, or a "Photo 1"
   or "Video 1" link where Drive can't show one. Videos carry a play mark. */
.psrp-findings .media-h { font-size: 12.5px; color: var(--muted); font-weight: 650; margin: 10px 0 0; }
.psrp-findings .photos { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; margin-top: 6px; }
.psrp-findings .photos a { position: relative; display: block; color: var(--accent); text-decoration: none; font-size: 13px; }
.psrp-findings .photos a.thumb { aspect-ratio: 4 / 3; border-radius: var(--radius-sm); overflow: hidden; background: var(--surface-3); }
.psrp-findings .photos a.thumb img { display: block; width: 100%; height: 100%; object-fit: cover; transition: transform .15s ease; }
.psrp-findings .photos a.thumb:hover img { transform: scale(1.04); }
.psrp-findings .photos a.thumb:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
.psrp-findings .photos a.thumb span { position: absolute; left: 4px; bottom: 4px; background: rgba(6, 61, 40, 0.78); color: #fff; border-radius: 99px; padding: 0 7px; font-size: 11.5px; font-weight: 650; line-height: 1.6; }
.psrp-findings .photos a.thumb.video::after { content: ""; position: absolute; left: 50%; top: 50%; width: 30px; height: 30px; transform: translate(-50%, -50%); border-radius: 50%; background: rgba(6, 61, 40, 0.78) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%23fff' d='M5 3.5v9l8-4.5z'/%3E%3C/svg%3E") center / 14px no-repeat; pointer-events: none; }
.psrp-findings .photos a.nothumb { align-self: start; justify-self: start; background: var(--surface-2); border-radius: 99px; padding: 2px 10px; }
.psrp-findings .photos a.nothumb:hover { background: var(--accent-soft); }
@media (prefers-reduced-motion: reduce) { .psrp-findings .photos a.thumb img { transition: none; } .psrp-findings .photos a.thumb:hover img { transform: none; } }
/* Cities with no location in the visit file, listed under the map. */
.psrp-findings .map-note .link { font-size: inherit; }

/* The finding list is as wide as its longest choice; capped, the map's tools share one row on wide screens. */
.psrp-findings #f-find { max-width: 260px; }

/* Districts */
.psrp-findings .dt { min-width: 1040px; }

/* Where the records end, a line on the sources. */
.psrp-findings.pdp-sources { color: var(--muted); font-size: 13px; padding: 18px 0 36px; margin-top: 26px; border-top: 1px solid var(--line); }

/* The one-row filter bar is in the shared findings styles. This page has eight links, so sixteen
   paddings share the room left beside them. */
.psrp-findings.filterbar { --nav-pads: 16; }

@container psrp (max-width: 1100px) {
  .psrp-findings .impact.two, .psrp-findings .letters, .psrp-findings .again { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .plan-g { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@container psrp (max-width: 720px) {
  .psrp-findings .imp-num { font-size: 34px; }
  /* Columns that can shrink below their longest word, so the page never runs wider than a phone. */
  .psrp-findings .tiles.four, .psrp-findings .plan-g { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .psrp-findings .plan-g > div { min-width: 0; overflow-wrap: anywhere; padding: 10px 12px; }
  .psrp-findings .stcard .outs { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .rck { grid-template-columns: minmax(0, 1fr) 110px; }
  .psrp-findings .rck .gs { grid-column: 1 / -1; grid-row: 2; }
  .psrp-findings .rck-h span:nth-child(2) { display: none; }
  .psrp-findings .iss-t .stack { min-width: 130px; }
  .psrp-findings .chg span { min-width: 0; }
  /* Six map buttons share the full width, three to a row. */
  .psrp-findings .tools .seg { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); width: 100%; border-radius: 12px; }
  .psrp-findings .tools .seg button { padding: 6px 4px; font-size: 13px; border-left: 0; border-right: 1px solid var(--line); border-bottom: 1px solid var(--line); }
  .psrp-findings .tools .seg button + button { border-left: 0; }
  .psrp-findings #f-find { width: 100%; max-width: 100%; }
  .psrp-findings .again button { flex-direction: column; align-items: flex-start; }
  .psrp-findings .seq { justify-content: flex-start; }
}
@container psrp (max-width: 420px) {
  .psrp-findings .tiles.four { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .plan-g b { font-size: 21px; }
}
</style>
