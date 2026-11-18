<style>
/* What the Additional Classrooms design adds to the findings styles shared with the PSRP pages.
   The design is built on the Special Education one, so most of this matches that page's styles. */
.psrp-findings {
  /* The design's font. Optical sizing sharpens the big numbers. */
  --font: "Inter", "Segoe UI", system-ui, -apple-system, Roboto, sans-serif;
  /* Response types: greens acted, warm not acted. Work status reuses them. */
  --r-fixed: #0b6b45; --r-dir: #4fa97f; --r-disp: #e8a53a; --r-nota: #c9452f; --r-none: #841a13;
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
.psrp-findings .vt tbody tr[data-filter] { cursor: pointer; }
.psrp-findings .vt tbody tr[data-filter]:hover { background: var(--accent-soft); }
.psrp-findings .dt { min-width: 1040px; }
.psrp-findings .dn { color: var(--crit-ink); font-weight: 700; }
.psrp-findings .empty { color: var(--muted); font-size: 14px; padding: 6px 0; }

/* The issues */
.psrp-findings .tiles { grid-template-columns: repeat(4, 1fr); }
.psrp-findings #inotes { margin-top: 10px; }
.psrp-findings .sw { display: inline-block; width: 11px; height: 11px; border-radius: 3px; flex: none; }
.psrp-findings .r-fixed { background: var(--r-fixed); }
.psrp-findings .r-dir { background: var(--r-dir); }
.psrp-findings .r-disp { background: var(--r-disp); }
.psrp-findings .r-nota { background: var(--r-nota); }
.psrp-findings .r-none { background: var(--r-none); }

/* Where construction stands. Work status reuses the response colours:
   green going on, amber stopped, red not started. */
.psrp-findings .st-on { background: var(--r-fixed); }
.psrp-findings .st-stop { background: var(--r-disp); }
.psrp-findings .st-not { background: var(--r-nota); }
.psrp-findings .st-done { background: var(--r-dir); }
.psrp-findings .g2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; margin-top: 14px; align-items: start; }
.psrp-findings .g2.eq { align-items: stretch; }
.psrp-findings .g2.eq > .card { height: 100%; }
.psrp-findings .vt.cause-t { min-width: 520px; }
.psrp-findings .cause-t .bar { min-width: 70px; }
.psrp-findings .irow .num b { font-weight: 700; }
.psrp-findings .prog-big { font-size: 26px; font-weight: 800; color: var(--accent); letter-spacing: -0.02em; }
.psrp-findings .progress h3 { margin-bottom: 2px; }

/* What changed */
.psrp-findings .ans-card { padding: 16px 18px 18px; }
.psrp-findings .ans-card .sub { margin-top: 2px; }
.psrp-findings .segbar { display: flex; gap: 2px; height: 30px; margin: 4px 0 12px; }
.psrp-findings .segbar i { display: block; height: 100%; min-width: 3px; }
.psrp-findings .segbar i:first-child { border-radius: 6px 0 0 6px; }
.psrp-findings .segbar i:last-child { border-radius: 0 6px 6px 0; }
.psrp-findings .outs.three { grid-template-columns: repeat(3, 1fr); }
.psrp-findings .outs.four { grid-template-columns: repeat(4, 1fr); }
.psrp-findings button.out { display: flex; flex-direction: column; gap: 2px; text-align: left; border: 0; border-radius: var(--radius-sm); background: var(--surface-2); padding: 10px 12px; cursor: pointer; font: inherit; color: inherit; box-shadow: none; }
.psrp-findings button.out:hover { background: var(--accent-soft); }
.psrp-findings .out-lbl { display: flex; align-items: center; gap: 7px; }
.psrp-findings .out-num { color: var(--ink); }
.psrp-findings .acts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.psrp-findings .acts.one { grid-template-columns: minmax(0, 1fr); max-width: 560px; }
.psrp-findings .act-col { padding: 16px 18px; }
.psrp-findings .act-col > p { color: var(--muted); font-size: 13px; margin: 2px 0 6px; }
.psrp-findings .act-col .link { margin-top: 10px; font-size: 13.5px; font-weight: 650; }

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
.psrp-findings .rtx.na { cursor: default; font-style: italic; }
.psrp-findings .rcx { font-size: 12.5px; color: var(--muted); margin-top: 4px; line-height: 1.4; }
.psrp-findings .rep { background: var(--surface-2); border-left: 3px solid var(--s1); border-radius: var(--radius-sm); padding: 10px 12px; margin-top: 8px; }
.psrp-findings .rep-h { font-size: 12.5px; color: var(--muted); font-weight: 650; margin-bottom: 4px; }

/* Map tools: three show buttons, the list and the search, with room for the Download button on
   the same line on laptops. A long choice is cut short in the closed list; the map legend and
   the open list show it in full. */
@container psrp (min-width: 721px) {
  .psrp-findings #f-find { max-width: 260px; }
  .psrp-findings #mapsec .search { flex-basis: 200px; min-width: 160px; }
}

/* Sources line at the end of the page */
.psrp-findings.ac-sources { color: var(--muted); font-size: 13px; padding: 18px 0 36px; margin-top: 26px; border-top: 1px solid var(--line); }

/* The one-row filter bar (filters and section links together) is in the shared findings styles. */

/* Four filters wrap to three or four rows here, which would cover too much of the screen
   under the taller banner if they stayed on it. */
@container psrp (max-width: 900px) {
  .psrp-findings.filterbar { position: static; }
}
@container psrp (max-width: 1100px) {
  .psrp-findings .tiles { grid-template-columns: repeat(2, 1fr); }
  .psrp-findings .g2 { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .acts { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .outs.four { grid-template-columns: repeat(2, 1fr); }
}
@container psrp (max-width: 720px) {
  .psrp-findings .outs.three { grid-template-columns: 1fr 1fr; }
  .psrp-findings .vt.cause-t { min-width: 0; }
  .psrp-findings .cause-t .bar { display: none; }
  .psrp-findings.filterbar input[type=date] { min-width: 0; }
}
@container psrp (max-width: 420px) {
  .psrp-findings .tiles, .psrp-findings .outs.three, .psrp-findings .outs.four { grid-template-columns: 1fr; }
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
