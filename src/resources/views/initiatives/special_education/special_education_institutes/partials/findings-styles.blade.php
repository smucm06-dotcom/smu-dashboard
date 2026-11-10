<style>
/* What the Special Education design adds to the findings styles shared with the PSRP pages. */
.psrp-findings {
  /* The design's font. Optical sizing sharpens the big numbers. */
  --font: "Inter", "Segoe UI", system-ui, -apple-system, Roboto, sans-serif;
  /* Response types: greens acted, grey neutral, warm not acted. */
  --r-fixed: #0b6b45; --r-dir: #4fa97f; --r-plan: #c4cbc7; --r-disp: #e8a53a; --r-nota: #c9452f; --r-none: #841a13;
  font-optical-sizing: auto;
}
.psrp-findings h2 { letter-spacing: -0.02em; }
.psrp-findings .tile b, .psrp-findings .out-num { letter-spacing: -0.02em; }
.psrp-findings.filterbar input[type=date] { min-width: 142px; }
.psrp-tip { position: fixed; z-index: 2000; pointer-events: none; background: var(--surface); color: var(--ink); border: 1px solid var(--line-strong); border-radius: var(--radius-sm); box-shadow: var(--shadow); padding: 6px 10px; font-size: 13px; line-height: 1.35; max-width: 280px; }
.psrp-tip b { display: block; }

/* Tables */
.psrp-findings .vt { font-size: 14px; min-width: 860px; }
.psrp-findings .vt th { text-align: left; font-size: 13px; font-weight: 650; color: var(--accent); background: var(--accent-soft); padding: 9px 10px; vertical-align: bottom; line-height: 1.25; }
.psrp-findings .vt th.r, .psrp-findings .vt td.r { text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings .vt td { padding: 10px; border-top: 1px solid var(--grid); }
.psrp-findings .vt .lab { font-weight: 650; }
.psrp-findings .vt td small { display: block; color: var(--muted); font-size: 12px; font-weight: 400; }
.psrp-findings .vt tbody tr[data-filter] { cursor: pointer; }
.psrp-findings .vt tbody tr[data-filter]:hover { background: var(--accent-soft); }
.psrp-findings .stack { display: flex; gap: 2px; height: 12px; min-width: 170px; }
.psrp-findings .stack i { display: block; height: 100%; }
.psrp-findings .stack i:first-child { border-radius: 4px 0 0 4px; }
.psrp-findings .stack i:last-child { border-radius: 0 4px 4px 0; }
.psrp-findings .s-less { background: var(--crit); }
.psrp-findings .s-same { background: var(--line-strong); }
.psrp-findings .s-more { background: var(--good); }
.psrp-findings .key { display: flex; gap: 16px; flex-wrap: wrap; color: var(--ink-2); font-size: 13px; padding: 10px 14px 12px; border-top: 1px solid var(--grid); }
.psrp-findings .key span { display: inline-flex; align-items: center; gap: 6px; }
.psrp-findings .key i { width: 11px; height: 11px; border-radius: 3px; display: inline-block; }
.psrp-findings .dt { min-width: 1040px; }
.psrp-findings .up { color: var(--good-ink); font-weight: 700; }
.psrp-findings .dn { color: var(--crit-ink); font-weight: 700; }
.psrp-findings .empty { color: var(--muted); font-size: 14px; padding: 6px 0; }

/* The issues */
.psrp-findings .tiles { grid-template-columns: repeat(4, 1fr); }
.psrp-findings .top.two { display: grid; grid-template-columns: 1fr 1fr; column-gap: 28px; }
.psrp-findings .iss-t td.lab { font-weight: 650; }
.psrp-findings .iss-t .stack { height: 14px; min-width: 220px; }
.psrp-findings .iss-t .stack i { min-width: 0; }
.psrp-findings .legend-row { display: flex; gap: 14px; flex-wrap: wrap; color: var(--ink-2); font-size: 13px; padding: 10px 14px 12px; border-top: 1px solid var(--grid); }
.psrp-findings .legend-row span { display: inline-flex; align-items: center; gap: 6px; }
.psrp-findings .sw { display: inline-block; width: 11px; height: 11px; border-radius: 3px; flex: none; }
.psrp-findings .r-fixed { background: var(--r-fixed); }
.psrp-findings .r-dir { background: var(--r-dir); }
.psrp-findings .r-plan { background: var(--r-plan); }
.psrp-findings .r-disp { background: var(--r-disp); }
.psrp-findings .r-nota { background: var(--r-nota); }
.psrp-findings .r-none { background: var(--r-none); }

/* What changed */
.psrp-findings .ans-card { padding: 16px 18px 18px; }
.psrp-findings .ans-card .sub { margin-top: 2px; }
.psrp-findings .segbar { display: flex; gap: 2px; height: 30px; margin: 4px 0 12px; }
.psrp-findings .segbar i { display: block; height: 100%; min-width: 3px; }
.psrp-findings .segbar i:first-child { border-radius: 6px 0 0 6px; }
.psrp-findings .segbar i:last-child { border-radius: 0 6px 6px 0; }
.psrp-findings .outs.six { grid-template-columns: repeat(6, 1fr); }
.psrp-findings button.out { display: flex; flex-direction: column; gap: 2px; text-align: left; border: 0; border-radius: var(--radius-sm); background: var(--surface-2); padding: 10px 12px; cursor: pointer; font: inherit; color: inherit; box-shadow: none; }
.psrp-findings button.out:hover { background: var(--accent-soft); }
.psrp-findings .out-lbl { display: flex; align-items: center; gap: 7px; }
.psrp-findings .out-num { color: var(--ink); }
.psrp-findings .acts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.psrp-findings .act-col { padding: 16px 18px; }
.psrp-findings .act-col > p { color: var(--muted); font-size: 13px; margin: 2px 0 6px; }
.psrp-findings .act { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; width: 100%; text-align: left; background: none; border: 0; border-top: 1px solid var(--grid); padding: 9px 0; font: inherit; color: inherit; }
.psrp-findings button.act:hover { background: var(--accent-soft); }
.psrp-findings .act b { display: block; font-size: 14px; font-weight: 680; line-height: 1.32; }
.psrp-findings .act small { display: block; color: var(--muted); font-size: 12.5px; line-height: 1.4; margin-top: 2px; }
.psrp-findings .act .cnt { align-self: start; white-space: nowrap; font-size: 12px; font-weight: 700; color: var(--accent); background: var(--accent-soft); border-radius: 99px; padding: 1px 9px; }
.psrp-findings .act.zero { opacity: .55; }
.psrp-findings details.act-more > summary { cursor: pointer; color: var(--accent); font-weight: 650; font-size: 13.5px; padding: 8px 0 2px; border-top: 1px solid var(--grid); }

/* Back at the same schools */
.psrp-findings .chg { display: flex; align-items: center; gap: 10px; }
.psrp-findings .chg .stack { flex: 1; min-width: 120px; }
.psrp-findings .chg span { font-size: 12.5px; color: var(--ink-2); white-space: nowrap; min-width: 92px; }
.psrp-findings .said { font-size: 13px; color: var(--ink-2); max-width: 48ch; }
.psrp-findings details.more-d > summary { cursor: pointer; color: var(--accent); font-weight: 700; padding: 14px 18px; }
.psrp-findings details.more-d > p { padding: 0 18px 10px; }
.psrp-findings .rv-list button { align-items: center; }
.psrp-findings .rv-list .mk { display: flex; gap: 5px; flex: none; }
.psrp-findings .rv-list .mk i { font-style: normal; font-size: 12px; font-weight: 700; border-radius: 99px; padding: 1px 8px; }
.psrp-findings .rv-list .mk .g { background: var(--good-soft); color: var(--good-ink); }
.psrp-findings .rv-list .mk .w { background: var(--crit-soft); color: var(--crit-ink); }

/* Map and school report */
.psrp-findings .pin.r { width: 11px; height: 11px; margin: 0 1px; background: var(--deep); box-shadow: 0 0 0 2px var(--surface), 0 0 0 3.5px var(--good); }
.psrp-findings .ritem { padding: 8px 0; border-top: 1px solid var(--grid); }
.psrp-findings .ritem:first-of-type { border-top: 0; }
.psrp-findings .ri-h { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.psrp-findings .rlab { display: block; font-size: 11.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; margin: 6px 0 1px; }
.psrp-findings .ri-is .rlab { margin-top: 0; }
.psrp-findings .ri-is { font-size: 13.5px; font-weight: 650; line-height: 1.35; }
.psrp-findings .ri-h .badge { flex: none; font-size: 12px; padding: 2px 8px; }
.psrp-findings .rtx { font-size: 13px; color: var(--ink-2); margin-top: 4px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; cursor: pointer; }
.psrp-findings .rtx.open { display: block; }
.psrp-findings .rtx + .rtx { margin-top: 6px; }
.psrp-findings .rep { background: var(--surface-2); border-left: 3px solid var(--s1); border-radius: var(--radius-sm); padding: 10px 12px; margin-top: 8px; }
.psrp-findings .rep-h { font-size: 12.5px; color: var(--muted); font-weight: 650; margin-bottom: 4px; }
.psrp-findings .cmp { font-size: 13.5px; margin-top: 6px; width: 100%; }
.psrp-findings .cmp th { font-size: 12px; color: var(--muted); font-weight: 650; text-align: right; padding: 2px 0 4px 8px; }
.psrp-findings .cmp th:first-child { text-align: left; padding-left: 0; }
.psrp-findings .cmp td { padding: 5px 0 5px 8px; border-top: 1px solid var(--grid); text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings .cmp td:first-child { text-align: left; padding-left: 0; }
.psrp-findings details.visit summary { cursor: pointer; color: var(--accent); font-weight: 650; font-size: 14px; margin-top: 10px; }

/* Map tools: four show buttons, the list and the search, with room for the Download button on
   the same line on laptops. A long choice is cut short in the closed list; the map legend and
   the open list show it in full. */
@container psrp (min-width: 721px) {
  .psrp-findings #f-find { max-width: 260px; }
  .psrp-findings #mapsec .search { flex-basis: 200px; min-width: 160px; }
}

/* Sources line at the end of the page */
.psrp-findings.spe-sources { color: var(--muted); font-size: 13px; padding: 18px 0 36px; margin-top: 26px; border-top: 1px solid var(--line); }

/* The filter bar looks and lines up as on the PSRP pages. To keep the section links on the
   same line as the four filters, what the page shows gives way first: it is cut short with
   "…" (hover shows it all), then left out. Only then do the links move to a second line,
   as they do on the PSRP pages. */
.psrp-findings.filterbar .fb-meta { flex: 1 1 var(--spe-nav-text, 420px); flex-wrap: nowrap; }
/* Phones have no links in the bar (as on the PSRP pages), so there the text always shows. */
@container psrp (min-width: 721px) {
  .psrp-findings.filterbar .fb-meta { container: speFilterMeta / inline-size; }
}
.psrp-findings.filterbar .scope { flex: 1 1 0; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.psrp-findings.filterbar .nav { flex: none; flex-wrap: nowrap; white-space: nowrap; }
/* The links keep the PSRP spacing where there is room and close up a little where there isn't,
   such as when "Clear filters" shows. --spe-nav-text is the links' text and gaps, set by the script. */
.psrp-findings.filterbar .nav a { padding-left: clamp(0px, calc((100cqi - var(--spe-nav-text, 420px)) / 12), 10px); padding-right: clamp(0px, calc((100cqi - var(--spe-nav-text, 420px)) / 12), 10px); }
@container speFilterMeta (max-width: 600px) {
  .psrp-findings.filterbar .scope { display: none; }
}

/* Four filters wrap to three or four rows here, which would cover too much of the screen
   under the taller banner if they stayed on it. */
@container psrp (max-width: 900px) {
  .psrp-findings.filterbar { position: static; }
}
@container psrp (max-width: 1100px) {
  .psrp-findings .tiles { grid-template-columns: repeat(2, 1fr); }
  .psrp-findings .acts { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .outs.six { grid-template-columns: repeat(3, 1fr); }
}
@container psrp (max-width: 720px) {
  .psrp-findings .top.two { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .outs.six { grid-template-columns: 1fr 1fr; }
  .psrp-findings .chg span { min-width: 0; }
  .psrp-findings .iss-t .stack { min-width: 130px; }
  .psrp-findings.filterbar input[type=date] { min-width: 0; }
}
@container psrp (max-width: 420px) {
  .psrp-findings .tiles, .psrp-findings .outs.six { grid-template-columns: 1fr; }
}

/* The design's two Impact cards, at the top of "What changed after the SMU visits". */
.psrp-findings .impact { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 14px; }
.psrp-findings .imp-card { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: center; column-gap: 18px; background: var(--surface); color: var(--ink); border: 1px solid var(--line); border-left: 5px solid var(--mint); border-radius: var(--radius); padding: 16px 20px; box-shadow: var(--shadow); }
.psrp-findings .imp-tag { grid-column: 1 / -1; justify-self: start; margin-bottom: 8px; font-size: 11.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); background: var(--accent-soft); border-radius: 99px; padding: 2px 10px; }
.psrp-findings .imp-num { font-size: 44px; font-weight: 800; line-height: 1; color: var(--accent); letter-spacing: -0.02em; }
.psrp-findings .imp-txt b { display: block; font-size: 16px; font-weight: 750; line-height: 1.3; }
.psrp-findings .imp-txt small { display: block; color: var(--ink-2); font-size: 13.5px; line-height: 1.45; margin-top: 4px; }
@container psrp (max-width: 720px) {
  .psrp-findings .impact { grid-template-columns: minmax(0, 1fr); gap: 10px; }
  .psrp-findings .imp-num { font-size: 36px; }
}
@container psrp (max-width: 420px) {
  .psrp-findings .imp-card { column-gap: 12px; padding: 12px 14px; }
  .psrp-findings .imp-num { font-size: 30px; }
  .psrp-findings .imp-txt b { font-size: 14.5px; }
  .psrp-findings .imp-txt small { font-size: 12.5px; }
}
</style>
