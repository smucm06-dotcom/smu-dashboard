<style>
/* What the School Meal design adds to the findings styles shared with the PSRP pages. The design is
   built on the Special Education one, so most of this matches that page's styles. */
.psrp-findings {
  /* The design's font. Optical sizing sharpens the big numbers. */
  --font: "Inter", "Segoe UI", system-ui, -apple-system, Roboto, sans-serif;
  /* Response types: greens acted, grey neutral, warm not acted. */
  --r-fixed: #0b6b45; --r-dir: #4fa97f; --r-plan: #c4cbc7; --r-disp: #e8a53a; --r-nota: #c9452f; --r-none: #841a13;
  /* Map pin for a school in an SMU letter. Checked to stay apart from the green, dark and red pins,
     also for colour-blind readers. */
  --letter: #7048e8;
  font-optical-sizing: auto;
}
.psrp-findings h2 { letter-spacing: -0.02em; }
.psrp-findings .tile b, .psrp-findings .out-num, .psrp-findings .imp-num { letter-spacing: -0.02em; }
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
.psrp-findings .vt tbody tr[data-filter], .psrp-findings .vt tbody tr[data-open] { cursor: pointer; }
.psrp-findings .vt tbody tr[data-filter]:hover, .psrp-findings .vt tbody tr[data-open]:hover { background: var(--accent-soft); }
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

/* How the milk reaches students */
.psrp-findings .g2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; margin-top: 14px; align-items: start; }
.psrp-findings .g2.eq { align-items: stretch; }
.psrp-findings .g2.eq > .card { height: 100%; }
.psrp-findings .prog-big { font-size: 26px; font-weight: 800; color: var(--accent); letter-spacing: -0.02em; }
.psrp-findings .progress h3 { margin-bottom: 2px; }
.psrp-findings .progress .sub, .psrp-findings .ans-card .sub, .psrp-findings .pad .sub { color: var(--muted); font-size: 13.5px; margin: 2px 0 8px; }
.psrp-findings .irow .num b { font-weight: 700; }
.psrp-findings .rt-S { background: var(--r-fixed); }
.psrp-findings .rt-A { background: var(--r-disp); }
.psrp-findings .rt-U { background: var(--r-nota); }
.psrp-findings .mini { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 12px; }
.psrp-findings .mini > div { background: var(--surface-2); border-radius: var(--radius-sm); padding: 10px 12px; }
.psrp-findings .mini b { display: block; font-size: 22px; font-weight: 800; color: var(--ink); letter-spacing: -0.02em; font-variant-numeric: tabular-nums; }
.psrp-findings .mini span { font-size: 13px; color: var(--ink-2); }

/* What changed */
.psrp-findings .ans-card { padding: 16px 18px 18px; }
.psrp-findings .segbar { display: flex; gap: 2px; height: 30px; margin: 4px 0 12px; }
.psrp-findings .segbar i { display: block; height: 100%; min-width: 3px; }
.psrp-findings .segbar i:first-child { border-radius: 6px 0 0 6px; }
.psrp-findings .segbar i:last-child { border-radius: 0 6px 6px 0; }
.psrp-findings .outs.three { grid-template-columns: repeat(3, 1fr); }
.psrp-findings .outs.six { grid-template-columns: repeat(6, 1fr); }
.psrp-findings button.out { display: flex; flex-direction: column; gap: 2px; text-align: left; border: 0; border-radius: var(--radius-sm); background: var(--surface-2); padding: 10px 12px; cursor: pointer; font: inherit; color: inherit; box-shadow: none; }
.psrp-findings button.out:hover { background: var(--accent-soft); }
.psrp-findings .out-lbl { display: flex; align-items: center; gap: 7px; }
.psrp-findings .out-num { color: var(--ink); }
.psrp-findings .acts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.psrp-findings .acts.one { grid-template-columns: minmax(0, 1fr); max-width: 560px; }
.psrp-findings .acts .act-col.hide { display: none; }
.psrp-findings .act-col { padding: 16px 18px; }
.psrp-findings .act-col > p { color: var(--muted); font-size: 13px; margin: 2px 0 6px; }
.psrp-findings .act-col .link { margin-top: 10px; font-size: 13.5px; font-weight: 650; }
.psrp-findings .showall { margin-top: 12px; }
.psrp-findings .fu-t { min-width: 820px; }
.psrp-findings .fu-t td { vertical-align: top; }
.psrp-findings .fu-t .res { white-space: nowrap; font-weight: 650; }
.psrp-findings .fu-t .res.ok { color: var(--good-ink); }
.psrp-findings .fu-t .res.bad { color: var(--crit-ink); }
.psrp-findings .fu-t .res.na { color: var(--muted); font-weight: 500; }
.psrp-findings .tbadge { display: inline-block; font-size: 12px; font-weight: 650; border-radius: 99px; padding: 1px 8px; margin: 1px 4px 1px 0; }

/* Back at the same schools */
.psrp-findings .chg { display: flex; align-items: center; gap: 10px; }
.psrp-findings .chg .stack { flex: 1; min-width: 120px; }
.psrp-findings .chg span { font-size: 12.5px; color: var(--ink-2); white-space: nowrap; min-width: 92px; }
.psrp-findings .rv-list button { align-items: center; }
.psrp-findings .rv-list .mk { display: flex; gap: 5px; flex: none; }
.psrp-findings .rv-list .mk i { font-style: normal; font-size: 12px; font-weight: 700; border-radius: 99px; padding: 1px 8px; }
.psrp-findings .rv-list .mk .g { background: var(--good-soft); color: var(--good-ink); }
.psrp-findings .rv-list .mk .w { background: var(--crit-soft); color: var(--crit-ink); }

/* Map pins, as on the other pages, and one for a school in an SMU letter */
.psrp-findings .pin.a { width: 11px; height: 11px; margin: 0 1px; background: var(--deep); box-shadow: 0 0 0 2px var(--surface), 0 0 0 3.5px var(--good); }
.psrp-findings .pin.r { width: 13px; height: 13px; background: var(--letter); box-shadow: 0 0 0 1.5px var(--surface); }

/* The school report beside the map */
.psrp-findings .ritem { padding: 8px 0; border-top: 1px solid var(--grid); }
.psrp-findings .ritem:first-of-type { border-top: 0; }
.psrp-findings .ri-h { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.psrp-findings .rlab { display: block; font-size: 11.5px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; margin: 6px 0 1px; }
.psrp-findings .ri-is .rlab { margin-top: 0; }
.psrp-findings .ri-is { font-size: 13.5px; font-weight: 650; line-height: 1.35; }
.psrp-findings .ri-h .badge { flex: none; font-size: 12px; padding: 2px 8px; }
.psrp-findings .rresp { font-size: 13px; color: var(--ink-2); margin-top: 6px; cursor: pointer; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.psrp-findings .rresp.open { display: block; }
.psrp-findings .rresp.na { cursor: default; font-style: italic; -webkit-line-clamp: unset; }
.psrp-findings .shared { font-size: 12.5px; color: var(--muted); margin-top: 4px; }
.psrp-findings .rep { background: var(--surface-2); border-left: 3px solid var(--s1); border-radius: var(--radius-sm); padding: 10px 12px; margin-top: 8px; }
.psrp-findings .rep-h { font-size: 12.5px; color: var(--muted); font-weight: 650; margin-bottom: 4px; }
.psrp-findings .chip.ok { background: var(--good-soft); color: var(--good-ink); }
.psrp-findings .chip.mute { background: var(--surface-3); color: var(--ink-2); }
.psrp-findings .photos { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.psrp-findings .photos a { font-size: 13px; font-weight: 650; color: var(--accent); background: var(--accent-soft); border-radius: 99px; padding: 2px 10px; text-decoration: none; }
.psrp-findings .photos a:hover { text-decoration: underline; }
.psrp-findings details.visit > summary { cursor: pointer; color: var(--accent); font-weight: 650; font-size: 14px; margin-top: 12px; }
.psrp-findings .insights.four { grid-template-columns: repeat(4, 1fr); }

/* Map tools: five show buttons, the list and the search, with room for the Download button on
   the same line on laptops. A long choice is cut short in the closed list; the map legend and
   the open list show it in full. */
@container psrp (min-width: 721px) {
  .psrp-findings #f-find { max-width: 260px; }
  .psrp-findings #mapsec .search { flex-basis: 200px; min-width: 160px; }
}
/* On laptops the buttons and the list give up a little room, so Download still fits beside them. */
@container psrp (min-width: 721px) and (max-width: 1180px) {
  .psrp-findings #mapsec .seg button { padding-left: 9px; padding-right: 9px; }
  .psrp-findings #f-find { max-width: 240px; }
  .psrp-findings #mapsec .search { flex-basis: 160px; }
}
/* On phones the five buttons share the full width, and a long one takes two lines in its own box. */
@container psrp (max-width: 520px) {
  .psrp-findings #mapsec .seg { display: flex; width: 100%; }
  .psrp-findings #mapsec .seg button { flex: 1 1 auto; padding: 6px 6px; font-size: 13px; line-height: 1.25; }
}

/* Sources line at the end of the page */
.psrp-findings.meal-sources { color: var(--muted); font-size: 13px; padding: 18px 0 36px; margin-top: 26px; border-top: 1px solid var(--line); }

/* The one-row filter bar (filters and section links together) is in the shared findings styles.
   This page has seven links, so fourteen paddings share the room left beside them. */
.psrp-findings.filterbar { --nav-pads: 14; }

/* The design's three Impact cards, at the top of "What changed after the SMU visits". */
.psrp-findings .impact { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 14px; }
.psrp-findings .imp-card { display: grid; grid-template-columns: minmax(0, 1fr); align-content: start; background: var(--surface); color: var(--ink); border: 1px solid var(--line); border-left: 5px solid var(--accent); border-radius: var(--radius); padding: 16px 20px; box-shadow: var(--shadow); }
.psrp-findings .imp-tag { justify-self: start; margin-bottom: 8px; font-size: 11.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); background: var(--accent-soft); border-radius: 99px; padding: 2px 10px; }
.psrp-findings .imp-num { font-size: 40px; font-weight: 800; line-height: 1; color: var(--accent); margin-bottom: 6px; }
.psrp-findings .imp-txt b { display: block; font-size: 16px; font-weight: 750; line-height: 1.3; }
.psrp-findings .imp-txt small { display: block; color: var(--ink-2); font-size: 13.5px; line-height: 1.45; margin-top: 4px; }

@container psrp (max-width: 1100px) {
  .psrp-findings .tiles { grid-template-columns: repeat(2, 1fr); }
  .psrp-findings .g2 { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .acts { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .outs.six { grid-template-columns: repeat(3, 1fr); }
  .psrp-findings .impact { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .insights.four { grid-template-columns: 1fr 1fr; }
}
@container psrp (max-width: 720px) {
  .psrp-findings .top.two { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .outs.three, .psrp-findings .outs.six, .psrp-findings .mini { grid-template-columns: 1fr 1fr; }
  .psrp-findings .chg span { min-width: 0; }
  .psrp-findings .iss-t .stack { min-width: 130px; }
  .psrp-findings.filterbar input[type=date] { min-width: 0; }
  .psrp-findings .impact { gap: 10px; }
  .psrp-findings .imp-num { font-size: 34px; }
}
@container psrp (max-width: 420px) {
  .psrp-findings .tiles, .psrp-findings .outs.three, .psrp-findings .outs.six, .psrp-findings .mini, .psrp-findings .insights.four { grid-template-columns: 1fr; }
  .psrp-findings .imp-card { padding: 12px 14px; }
  .psrp-findings .imp-num { font-size: 30px; }
  .psrp-findings .imp-txt b { font-size: 14.5px; }
  .psrp-findings .imp-txt small { font-size: 12.5px; }
}
</style>
