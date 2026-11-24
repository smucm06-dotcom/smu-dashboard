<style>
/* CM Punjab Honhaar Scholarship: the parts of the "Honhaar Scholarship Call Center Feedback" design that the
   shared findings styles (psrp_shared/findings-styles) do not have. The design's sizes are kept; its screen
   widths become the room beside the sidebar (@container psrp). */
.psrp-findings { --font: "Inter", "Segoe UI", system-ui, -apple-system, Roboto, sans-serif; font-optical-sizing: auto; }
.psrp-findings h2 { letter-spacing: -0.02em; }
.psrp-findings .imp-num, .psrp-findings .skey b, .psrp-findings .types .tn { letter-spacing: -0.02em; }
.psrp-findings.filterbar input[type=date] { min-width: 142px; }
.psrp-tip { position: fixed; z-index: 2000; pointer-events: none; background: var(--surface); color: var(--ink); border: 1px solid var(--line-strong); border-radius: var(--radius-sm); box-shadow: var(--shadow); padding: 6px 10px; font-size: 13px; line-height: 1.4; max-width: 320px; }
.psrp-tip b { display: block; }

/* The filter bar keeps one row on this page, whatever is chosen (the user, 24 Nov 2026): the filters keep
   their room and the section links that do not fit beside them go under "More" (see the script). Phones keep
   the stacked filters every page has. */
@container psrp (min-width: 721px) {
  .psrp-findings.filterbar .psrp-wrap { flex-wrap: nowrap; }
  .psrp-findings.filterbar .fg { flex: 0 1 auto; min-width: 0; }
  .psrp-findings.filterbar .fg .ctl { min-width: 0; }
  .psrp-findings.filterbar input[type=date] { min-width: 128px; }
  .psrp-findings.filterbar #f-clear { flex: none; white-space: nowrap; }
  .psrp-findings.filterbar .fb-meta { flex: 1 1 0; min-width: 0; justify-content: flex-end; flex-wrap: nowrap; gap: 4px; }
  .psrp-findings.filterbar .nav { flex: 0 1 auto; min-width: 0; overflow: hidden; margin-left: auto; gap: 2px; }
  .psrp-findings.filterbar .nav a { flex: none; padding-left: 7px; padding-right: 7px; }
}
.psrp-findings .nav a[hidden] { display: none; }
.psrp-findings .nav-more { position: relative; flex: none; }
.psrp-findings .nav-more[hidden] { display: none; }
.psrp-findings .nav-more summary { list-style: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 99px; font-size: 14px; font-weight: 550; color: var(--ink-2); white-space: nowrap; user-select: none; }
.psrp-findings .nav-more summary::-webkit-details-marker { display: none; }
.psrp-findings .nav-more summary svg { width: 15px; height: 15px; transition: transform .15s ease; }
.psrp-findings .nav-more[open] summary svg { transform: rotate(180deg); }
.psrp-findings .nav-more summary:hover, .psrp-findings .nav-more[open] summary { background: var(--accent-soft); color: var(--accent); }
.psrp-findings .nav-more-menu { position: absolute; right: 0; top: calc(100% + 6px); z-index: 950; display: grid; min-width: 180px; padding: 6px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-sm); box-shadow: var(--shadow); }
.psrp-findings .nav-more-menu a { padding: 7px 10px; border-radius: 6px; color: var(--ink-2); text-decoration: none; font-size: 14px; font-weight: 550; white-space: nowrap; }
.psrp-findings .nav-more-menu a:hover, .psrp-findings .nav-more-menu a:focus-visible { background: var(--accent-soft); color: var(--accent); }

/* The banner's four cards are the steps from the list to satisfaction, as in the design: numbered like every
   initiative page's (shared findings styles), with arrows between them where there is room, and the
   design's second line under two of them. Only this page's banner. */
.topbar-stat-card { position: relative; }
.topbar-stat-extra { margin-top: 6px; padding-top: 6px; border-top: 1px solid rgba(255, 255, 255, .18); }
.topbar-stat-extra b { color: #fff; font-weight: 800; font-variant-numeric: tabular-nums; }
@media (min-width: 1200px) {
  .topbar-stats-grid { column-gap: 22px; }
  .topbar-stat-card + .topbar-stat-card::before { content: "\2192"; position: absolute; left: -16px; top: 50%; transform: translateY(-50%); color: #a9ecc8; font-weight: 700; font-size: 15px; line-height: 1; }
}
/* The last card is the result, as the design marks it. */
.topbar-stat-card:last-child { background: rgba(255, 255, 255, .16); border-color: #a9ecc8; }

/* Two cards side by side */
.psrp-findings .g2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; align-items: stretch; }
.psrp-findings .card.pad { padding: 16px 18px 18px; }
.psrp-findings .card .sub { color: var(--ink-2); font-size: 14px; margin: 4px 0 10px; line-height: 1.5; }
.psrp-findings .card .note { color: var(--muted); font-size: 13px; margin-top: 10px; line-height: 1.45; }

/* Satisfied against an issue */
.psrp-findings .sbar { display: flex; gap: 2px; height: 34px; margin: 14px 0 8px; }
.psrp-findings .sbar i { display: block; height: 100%; min-width: 3px; }
.psrp-findings .sbar i:first-child { border-radius: 6px 0 0 6px; }
.psrp-findings .sbar i:last-child { border-radius: 0 6px 6px 0; }
.psrp-findings .sbar .g { background: var(--good); }
.psrp-findings .sbar .c { background: var(--crit); }
.psrp-findings .skey { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 6px; }
.psrp-findings .skey > div { background: var(--surface-2); border-radius: var(--radius-sm); padding: 10px 12px; }
.psrp-findings .skey b { display: block; font-size: 24px; font-weight: 800; font-variant-numeric: tabular-nums; }
.psrp-findings .skey span { display: flex; align-items: center; gap: 7px; font-size: 13.5px; color: var(--ink-2); font-weight: 600; }
.psrp-findings .sw { display: inline-block; width: 11px; height: 11px; border-radius: 3px; flex: none; }
.psrp-findings .sw.g { background: var(--good); }
.psrp-findings .sw.c { background: var(--crit); }
.psrp-findings .sw.a { background: var(--s1); }

/* Bar rows */
.psrp-findings .br { display: grid; grid-template-columns: minmax(180px, 330px) minmax(80px, 1fr) 150px; gap: 12px; align-items: center; border-top: 1px solid var(--grid); padding: 8px 2px; font-size: 14px; }
.psrp-findings .br:first-of-type { border-top: 0; }
.psrp-findings .br .lb { font-weight: 650; line-height: 1.3; min-width: 0; overflow-wrap: anywhere; }
.psrp-findings .br .lb small { display: block; color: var(--muted); font-weight: 500; font-size: 12.5px; margin-top: 1px; }
.psrp-findings .br .trk { height: 14px; background: var(--surface-3); border-radius: 0 4px 4px 0; overflow: hidden; }
.psrp-findings .br .trk i { display: block; height: 100%; background: var(--crit); border-radius: 0 4px 4px 0; min-width: 2px; }
.psrp-findings .br .trk.a i { background: var(--s1); }
.psrp-findings .br .vl { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.psrp-findings .br .vl b { font-size: 16px; font-weight: 800; }
.psrp-findings .br .vl span { color: var(--muted); font-size: 13px; margin-left: 6px; }
.psrp-findings .br.sm { grid-template-columns: minmax(120px, 190px) minmax(60px, 1fr) 118px; padding: 6px 2px; }
.psrp-findings .br.sm .vl b { font-size: 14.5px; }
.psrp-findings .br.zero { opacity: .5; }

/* Yes and no */
.psrp-findings .yn { display: flex; gap: 2px; height: 26px; margin: 12px 0 8px; }
.psrp-findings .yn i { display: block; height: 100%; min-width: 3px; }
.psrp-findings .yn i:first-child { border-radius: 6px 0 0 6px; background: var(--good); }
.psrp-findings .yn i:last-child { border-radius: 0 6px 6px 0; background: var(--crit); }
.psrp-findings .ynk { display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: 14px; }
.psrp-findings .ynk span { display: inline-flex; align-items: center; gap: 7px; }
.psrp-findings .ynk b { font-variant-numeric: tabular-nums; }
.psrp-findings .asked { display: inline-block; font-size: 12px; font-weight: 650; color: var(--info-ink); background: var(--info-soft); border-radius: 99px; padding: 1px 9px; margin-top: 4px; }

/* District table */
.psrp-findings .dt { min-width: 860px; }
.psrp-findings .dt td.sr { min-width: 230px; }
.psrp-findings .rate { display: flex; align-items: center; gap: 10px; }
.psrp-findings .rate .trk { flex: 1; height: 12px; background: var(--surface-3); border-radius: 0 4px 4px 0; overflow: hidden; min-width: 90px; }
.psrp-findings .rate .trk i { display: block; height: 100%; background: var(--s1); border-radius: 0 4px 4px 0; }
.psrp-findings .rate b { min-width: 48px; text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings .dt td small { color: var(--muted); font-size: 12.5px; margin-left: 4px; }
.psrp-findings .dt tr.few td { color: var(--muted); }
.psrp-findings .dt tr.few .rate .trk i { background: var(--line-strong); }
.psrp-findings .few-tag { display: inline-block; font-size: 11px; font-weight: 700; color: var(--muted); background: var(--surface-3); border-radius: 99px; padding: 0 7px; margin-left: 6px; vertical-align: 1px; }
.psrp-findings .dt th button[aria-sort="ascending"]::after { content: " \2191"; }
.psrp-findings .dt th button[aria-sort="descending"]::after { content: " \2193"; }

/* What the follow-up calls show */
.psrp-findings .impact { display: grid; gap: 16px; }
.psrp-findings .impact.two { grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 0; }
.psrp-findings .imp-card { background: var(--surface); color: var(--ink); border-radius: var(--radius); border: 1px solid var(--line); border-left: 5px solid var(--accent); padding: 16px 20px; display: grid; grid-template-columns: auto minmax(0, 1fr); column-gap: 18px; align-items: center; box-shadow: var(--shadow); }
.psrp-findings .imp-tag { grid-column: 1 / -1; justify-self: start; font-size: 11.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--accent); background: var(--accent-soft); border-radius: 99px; padding: 2px 10px; margin-bottom: 8px; }
.psrp-findings .imp-num { font-size: 44px; font-weight: 800; line-height: 1; color: var(--accent); }
.psrp-findings .imp-txt b { display: block; font-size: 16px; line-height: 1.3; font-weight: 750; }
.psrp-findings .imp-txt small { display: block; color: var(--ink-2); font-size: 13.5px; line-height: 1.45; margin-top: 4px; }
.psrp-findings .imp-note { color: var(--muted); font-size: 13.5px; margin-top: 10px; max-width: 110ch; }

/* Universities and colleges */
.psrp-findings .types { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
.psrp-findings .types .card { padding: 12px 14px; }
.psrp-findings .types h3 { font-size: 14.5px; }
.psrp-findings .types .tn { font-size: 26px; font-weight: 800; font-variant-numeric: tabular-nums; margin-top: 2px; }
.psrp-findings .types .tn small { font-size: 13px; font-weight: 600; color: var(--muted); margin-left: 6px; letter-spacing: 0; }
.psrp-findings .types p { color: var(--ink-2); font-size: 13px; line-height: 1.4; margin-top: 2px; }
.psrp-findings .types .card.none { border-top: 4px solid var(--warn); }
.psrp-findings .tbl-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px; padding: 12px 14px; border-bottom: 1px solid var(--line); }
.psrp-findings .ut { min-width: 980px; }
.psrp-findings .ut th { padding: 9px 10px; line-height: 1.25; }
.psrp-findings .ut td.nm { max-width: 340px; font-weight: 600; }
.psrp-findings .ut td.nm small { display: block; margin-left: 0; font-weight: 500; }
.psrp-findings .ut tbody tr { cursor: default; }
.psrp-findings .ut tbody tr:hover { background: none; }
.psrp-findings .more-row { padding: 10px 14px; border-top: 1px solid var(--grid); }

/* Repeat calls */
.psrp-findings .chr { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 10px; border-top: 1px solid var(--grid); padding: 7px 2px; font-size: 14px; }
.psrp-findings .chr:first-child { border-top: 0; }
.psrp-findings .chr b { font-variant-numeric: tabular-nums; }
.psrp-findings .chr.sub-r { padding-left: 18px; color: var(--ink-2); font-size: 13.5px; }
.psrp-findings .chr.head { font-weight: 700; }

.psrp-findings.honhaar-sources { color: var(--muted); font-size: 13px; padding: 26px 0 36px; margin-top: 34px; border-top: 1px solid var(--line); }

@container psrp (max-width: 1100px) {
  .psrp-findings .types { grid-template-columns: 1fr 1fr; }
}
@container psrp (max-width: 760px) {
  .psrp-findings .g2, .psrp-findings .impact.two { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .br, .psrp-findings .br.sm { grid-template-columns: minmax(0, 1fr) 118px; }
  .psrp-findings .br .trk { grid-column: 1 / -1; grid-row: 2; }
  .psrp-findings .skey b { font-size: 20px; }
}
@container psrp (max-width: 720px) {
  .psrp-findings .imp-num { font-size: 36px; }
}
@container psrp (max-width: 420px) {
  .psrp-findings .types { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .imp-card { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings .skey { grid-template-columns: minmax(0, 1fr); }
}
</style>
