<style>
/* What the Phase 2 design adds to the styles shared with Phase 1. */
.psrp-findings.filterbar input[type=date] { min-width: 142px; }
.psrp-findings .pin.o { background: var(--muted); }
.psrp-tip { position: fixed; z-index: 2000; pointer-events: none; background: var(--surface); color: var(--ink); border: 1px solid var(--line-strong); border-radius: var(--radius-sm); box-shadow: var(--shadow); padding: 6px 10px; font-size: 13px; line-height: 1.35; max-width: 280px; }
.psrp-tip b { display: block; }
.psrp-findings .stats.three { grid-template-columns: repeat(3, 1fr); }

.psrp-findings .vt { font-size: 14px; min-width: 860px; }
.psrp-findings .vt th { text-align: left; font-size: 13px; font-weight: 650; color: var(--accent); background: var(--accent-soft); padding: 9px 10px; vertical-align: bottom; line-height: 1.25; }
.psrp-findings .vt th.r, .psrp-findings .vt td.r { text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings .vt td { padding: 10px; border-top: 1px solid var(--grid); }
.psrp-findings .vt tbody tr[data-issue] { cursor: pointer; }
.psrp-findings .vt tbody tr[data-issue]:hover { background: var(--accent-soft); }
.psrp-findings .vt .lab { font-weight: 650; }
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

.psrp-findings .ct { font-size: 14px; margin-top: 10px; width: 100%; }
.psrp-findings .ct th { text-align: left; font-size: 12.5px; color: var(--muted); font-weight: 650; padding: 4px 6px 6px; line-height: 1.25; }
.psrp-findings .ct td { padding: 7px 6px; border-top: 1px solid var(--grid); }
.psrp-findings .ct .r { text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings button.out { text-align: left; border: 0; border-bottom: 3px solid var(--s1); background: var(--surface); cursor: pointer; font: inherit; color: inherit; }
.psrp-findings button.out:hover { background: var(--accent-soft); }
.psrp-findings .dvt td.up { color: var(--good-ink); }
.psrp-findings .dvt td.dn { color: var(--crit-ink); }
.psrp-findings .dvd { font-size: 14px; margin-top: 8px; width: 100%; }
.psrp-findings .dvd th { text-align: left; font-size: 12px; font-weight: 650; color: var(--muted); padding: 2px 0 4px; }
.psrp-findings .dvd th.r, .psrp-findings .dvd td.r { text-align: right; font-variant-numeric: tabular-nums; }
.psrp-findings .dvd td { padding: 5px 0; border-top: 1px solid var(--grid); }
.psrp-findings .dvd td.r { padding-left: 10px; }

.psrp-findings .tiles { grid-template-columns: repeat(4, 1fr); }
.psrp-findings .top.two { display: grid; grid-template-columns: 1fr 1fr; column-gap: 28px; }
.psrp-findings #inotes { margin-top: 10px; }
.psrp-findings .tile span.flag { display: inline; }

@container psrp (max-width: 1100px) {
  .psrp-findings .stats.three { grid-template-columns: repeat(2, 1fr); }
  .psrp-findings .tiles { grid-template-columns: repeat(2, 1fr); }
}
@container psrp (max-width: 720px) {
  .psrp-findings .stats.three { grid-template-columns: 1fr; }
  .psrp-findings .top.two { grid-template-columns: minmax(0, 1fr); }
  .psrp-findings.filterbar input[type=date] { min-width: 0; }
}
@container psrp (max-width: 420px) {
  .psrp-findings .tiles { grid-template-columns: 1fr; }
}
</style>
