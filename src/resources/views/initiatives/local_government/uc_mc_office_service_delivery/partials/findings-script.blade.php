<script>
// Everything above the records table, and the cards in the banner, is worked out here from one
// download, so changing a filter is instant and does not reload the page. This is the UC MC design's
// own code, inside the page frame the other initiative pages use.
// The date filter applies to visits only: the SMU letters, weekly reports and the department's
// responses always count.
(function () {
  'use strict';

  var $ = function (id) { return document.getElementById(id); };
  var page = $('psrpPage');
  var META = {}, TOT = {}, REP = [], RC = [], ALL = [], OI = {}, RI = {}, loaded = false;
  var MN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var QL = ['Jan&ndash;Mar', 'Apr&ndash;Jun', 'Jul&ndash;Sep', 'Oct&ndash;Dec'];

  var ISS = {
    closed: { label: 'Office closed', short: 'office closed', why: 'During office hours' },
    absent: { label: 'Secretary or CO away', short: 'secretary or CO away', why: 'Office open, in-charge not there' },
    noserv: { label: 'Citizens not being served', short: 'no service', why: 'Office open, no service given' },
    over: { label: 'Overcharging reported', short: 'overcharging', why: 'Fee schedule not followed' },
    nofee: { label: 'Fee schedule not displayed', short: 'no fee schedule', why: 'Citizens cannot check the fee' },
    private: { label: 'Private person running the office', short: 'private person', why: 'Unauthorised person at work' },
    unclean: { label: 'Office not clean', short: 'unclean office', why: 'Hygiene' },
    board: { label: 'No office board', short: 'no office board', why: 'Office hard to find' },
    dilap: { label: 'Building dilapidated', short: 'dilapidated building', why: 'Needs repair' }
  };
  var IORD = ['closed', 'absent', 'noserv', 'over', 'nofee', 'private', 'unclean', 'board', 'dilap'];
  function bad(v, k) {
    if (!v) return null;
    switch (k) {
      case 'closed': return v.op === 0;
      case 'absent': return v.op === 0 ? true : v.ip === null ? null : v.ip === 0;
      case 'noserv': return v.op === 0 ? true : v.sd === null ? null : v.sd === 0;
      case 'nofee': return v.fd === null ? null : v.fd === 0;
      case 'over': return v.ff === null ? null : v.ff === 0;
      case 'private': return v.pp === null ? null : v.pp === 1;
      case 'dilap': return v.db === null ? null : v.db === 1;
      case 'unclean': return v.cl === null ? null : v.cl === 0;
      case 'board': return v.bd === null ? null : v.bd === 0;
    }
    return null;
  }
  /* Findings at a visit: closed offices are not counted again for "away" or "no service". */
  var FIND = [
    { id: 'closed', f: function (v) { return v.op === 0; } },
    { id: 'absent', base: function (v) { return v.op === 1; }, f: function (v) { return v.op === 1 && v.ip === 0; } },
    { id: 'noserv', base: function (v) { return v.op === 1; }, f: function (v) { return v.op === 1 && v.sd === 0; } },
    { id: 'over', base: function (v) { return v.ff !== null; }, f: function (v) { return v.ff === 0; } },
    { id: 'nofee', base: function (v) { return v.fd !== null; }, f: function (v) { return v.fd === 0; } },
    { id: 'private', base: function (v) { return v.pp !== null; }, f: function (v) { return v.pp === 1; } },
    { id: 'unclean', base: function (v) { return v.cl !== null; }, f: function (v) { return v.cl === 0; } },
    { id: 'board', base: function (v) { return v.bd !== null; }, f: function (v) { return v.bd === 0; } },
    { id: 'dilap', base: function (v) { return v.db !== null; }, f: function (v) { return v.db === 1; } }
  ];
  FIND.forEach(function (f) { f.label = ISS[f.id].label; f.why = ISS[f.id].why; });
  var FI = {}; FIND.forEach(function (f) { FI[f.id] = f; });
  var TILES = ['closed', 'absent', 'noserv', 'over', 'nofee', 'private', 'unclean', 'board', 'dilap'];
  function status(v) { return !v ? null : v.op === 0 ? 'C' : (v.ip === 1 && v.sd === 1) ? 'S' : 'P'; }
  var STL = { S: 'Open and serving', P: 'Open, but in-charge away or not serving', C: 'Closed' };

  var RT = [
    { id: 'acted', label: 'Fixed or action taken', sub: 'Put right, or action on staff', sw: 'r-fixed', badge: 'b-good' },
    { id: 'later', label: 'Promised for later', sub: 'Waiting for development funds', sw: 'r-plan', badge: 'b-mute' },
    { id: 'directed', label: 'Only instructions issued', sub: 'General orders to all UC staff', sw: 'r-dir', badge: 'b-info' },
    { id: 'explained', label: 'Reason given', sub: 'Secretary on other duty, and similar', sw: 'r-disp', badge: 'b-warn' },
    { id: 'denied', label: 'Denied', sub: 'No evidence found, says the department', sw: 'r-nota', badge: 'b-crit' },
    { id: 'notans', label: 'Not answered', sub: 'The response skips this point', sw: 'r-none', badge: 'b-crit' }
  ];
  var RTI = {}; RT.forEach(function (t) { RTI[t.id] = t; });
  var LT = [
    { id: 'acted', i: 1, label: 'Admitted', sub: 'Lapse accepted, action taken' },
    { id: 'explained', i: 3, label: 'Justified', sub: 'A reason given for it' },
    { id: 'denied', i: 2, label: 'Denied', sub: 'Said not to have happened' },
    { id: 'notans', i: 4, label: 'Unaddressed', sub: 'Not dealt with in the response' }
  ];
  var RSN = [
    { id: 'charge', label: 'Holds additional charge of another UC' },
    { id: 'field', label: 'On field duty (stray-dog culling, Suthra Punjab, inspections)' },
    { id: 'office', label: 'At the ADLG / DDLG office or on other official work' },
    { id: 'court', label: 'In court or at arbitration hearings' },
    { id: 'leave', label: 'On approved leave' },
    { id: 'present', label: 'Says the secretary was in fact present' },
    { id: 'other', label: 'Other reason' }
  ];
  var SA = [
    { id: 'expl', label: 'Explanation called' }, { id: 'show', label: 'Show-cause notice issued' }, { id: 'proc', label: 'Departmental proceedings (PEEDA)' },
    { id: 'warn', label: 'Warning issued' }, { id: 'susp', label: 'Suspension' }, { id: 'pay', label: 'Pay stopped or deducted' }, { id: 'rec', label: 'Action recommended' }
  ];

  /* helpers */
  function esc(s) { return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function n(x) { return Number(x).toLocaleString('en-US'); }
  function pct(a, b) { if (!b) return 0; var p = a / b * 100; return p > 0 && p < 0.5 ? '<1' : p > 99.5 && p < 100 ? '>99' : Math.round(p); }
  function pl(c, one, many) { return n(c) + ' ' + (c === 1 ? one : many); }
  function dl(iso) { if (!iso) return ''; var p = iso.split('-'); return Number(p[2]) + ' ' + MN[Number(p[1]) - 1] + ' ' + p[0]; }
  function ml(iso) { var p = iso.split('-'); return MN[Number(p[1]) - 1] + ' ' + p[0]; }
  function days(a, b) { return Math.round((new Date(b) - new Date(a)) / 864e5); }
  function median(a) { if (!a.length) return null; a = a.slice().sort(function (x, y) { return x - y; }); var m = Math.floor(a.length / 2); return a.length % 2 ? a[m] : Math.round((a[m - 1] + a[m]) / 2); }
  function andList(a) { return a.length < 2 ? a.join('') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1]; }
  function range(a, b) { if (!a || !b) return ''; return ml(a) === ml(b) ? ml(a) : (a.slice(0, 4) === b.slice(0, 4) ? MN[Number(a.split('-')[1]) - 1] : ml(a)) + ' to ' + ml(b); }
  function qkey(dt) { var y = Number(dt.slice(0, 4)), m = Number(dt.slice(5, 7)); return y * 4 + Math.floor((m - 1) / 3); }
  function qlabel(k, lastDt) {
    var y = Math.floor(k / 4), q = k % 4, lab = QL[q];
    if (lastDt && qkey(lastDt) === k) { var lm = Number(lastDt.slice(5, 7)); if (lm < q * 3 + 3) lab = MN[q * 3] + (lm > q * 3 + 1 ? '&ndash;' + MN[lm - 1] : ''); }
    return lab + ' ' + y;
  }

  /* Dates: the filter keeps the visits made between From and To. */
  function keyToISO(k) { var s = String(k); return s.slice(0, 4) + '-' + s.slice(4, 6) + '-' + s.slice(6, 8); }
  function isoToKey(s) { return s ? Number(s.replace(/-/g, '')) : 0; }
  var KEYS = { min: 0, max: 0 };
  function fullRange() { return state.from === KEYS.min && state.to === KEYS.max; }
  function rangeText() { return dl(keyToISO(state.from)) + ' to ' + dl(keyToISO(state.to)); }
  // The first and last days the chosen dates cover, for the quarters and the banner.
  function firstDay() { return keyToISO(state.from || KEYS.min); }
  function lastDay() { return keyToISO(state.to || KEYS.max); }

  var state = { district: $('f-district').value, from: isoToKey($('f-from').value), to: isoToKey($('f-to').value), sort: 'visited', dir: -1, show: 'all', filter: '', sel: null };
  function inScope(d) { return !state.district || d === state.district; }
  // Offices with a visit in the dates chosen, in the district chosen.
  function scoped() { return ALL.filter(function (o) { return inScope(o.d) && o.v.length; }); }
  function where() { return state.district ? ' in ' + state.district : ''; }

  /* Per-office fields that depend on the visits in the date range */
  function derive() {
    ALL.forEach(function (o) {
      o.v = o.av.filter(function (v) { var k = isoToKey(v.dt); return k >= state.from && k <= state.to; });
      o.V = o.v.length ? o.v[o.v.length - 1] : null;
    });
    // The first visit after the department's response, among the visits in the dates chosen.
    RC.forEach(function (c) { c.nx = c.o.v.filter(function (v) { return v.dt > c.after; })[0] || null; });
  }

  /* summary of a selection */
  function summarise(list) {
    var o = { offices: list.length, listed: 0, unlisted: 0, visits: 0, first: null, last: null, find: {}, base: {}, st: { S: 0, P: 0, C: 0 }, seen: { U: 0, M: 0 }, again: { U: 0, M: 0 } };
    FIND.forEach(function (f) { o.find[f.id] = 0; o.base[f.id] = 0; });
    var ds = Object.keys(TOT).filter(inScope);
    o.total = ds.reduce(function (a, d) { return a + TOT[d].u + TOT[d].m; }, 0);
    o.uc = ds.reduce(function (a, d) { return a + TOT[d].u; }, 0);
    o.mc = ds.reduce(function (a, d) { return a + TOT[d].m; }, 0);
    o.nd = ds.length;
    list.forEach(function (s) {
      if (s.ml) o.listed++; else o.unlisted++;
      o.visits += s.v.length;
      // UCs and MCs visited in the selection, and those visited more than once
      o.seen[s.t]++; if (s.v.length > 1) o.again[s.t]++;
      if (!o.first || s.v[0].dt < o.first) o.first = s.v[0].dt;
      if (!o.last || s.V.dt > o.last) o.last = s.V.dt;
      var v = s.V;
      FIND.forEach(function (f) { if (!f.base || f.base(v)) o.base[f.id]++; if (f.f(v)) o.find[f.id]++; });
      o.st[status(v)]++;
    });
    /* reports: these always count, whatever the dates */
    var reps = REP.filter(function (r) { return inScope(r.d); });
    o.L = reps.filter(function (r) { return r.f === 'L'; }); o.W = reps.filter(function (r) { return r.f === 'W'; });
    o.cases = reps.reduce(function (a, r) { return a + r.c[0]; }, 0);
    o.lc = [0, 0, 0, 0, 0]; o.L.forEach(function (r) { r.c.forEach(function (x, i) { o.lc[i] += x; }); });
    o.wItems = 0; o.wt = {}; RT.forEach(function (t) { o.wt[t.id] = 0; });
    o.top = {}; IORD.forEach(function (k) { o.top[k] = { n: 0, t: {} }; RT.forEach(function (t) { o.top[k].t[t.id] = 0; }); });
    o.rsn = {}; RSN.forEach(function (x) { o.rsn[x.id] = 0; });
    o.fixed = {}; IORD.forEach(function (k) { o.fixed[k] = 0; });
    o.wDates = {}; o.wOff = {};
    o.W.forEach(function (r) {
      if (r.sd) o.wDates[r.sd] = 1;
      r.it.forEach(function (it) {
        o.wItems++; o.wt[it[3]]++; o.top[it[2]].n++; o.top[it[2]].t[it[3]]++;
        if (it[4]) o.rsn[it[4]] = (o.rsn[it[4]] || 0) + 1;
        if (it[3] === 'acted') o.fixed[it[2]]++;
        o.wOff[it[0] || (r.d + it[1])] = 1;
      });
    });
    o.sa = {}; o.saN = 0; SA.forEach(function (x) { o.sa[x.id] = 0; });
    reps.forEach(function (r) { if (r.sa.length) o.saN++; r.sa.forEach(function (k) { o.sa[k] = (o.sa[k] || 0) + 1; }); });
    o.lDates = o.L.map(function (r) { return r.ld; }).filter(Boolean).sort();
    /* rechecks */
    o.rc = RC.filter(function (c) { return inScope(c.o.d); });
    o.rcNx = o.rc.filter(function (c) { return c.nx; });
    o.rcI = {}; IORD.forEach(function (k) { o.rcI[k] = { g: 0, s: 0 }; });
    o.rcGone = 0; o.rcChk = 0;
    o.rcNx.forEach(function (c) {
      c.iss.forEach(function (k) { var b = bad(c.nx, k); if (b === null) return; o.rcChk++; if (b) o.rcI[k].s++; else { o.rcI[k].g++; o.rcGone++; } });
    });
    return o;
  }

  /* The cards in the banner, as in the design */
  function setCard(id, num, sub) {
    var el = $(id), info = $(id + '-sub');
    if (el) el.textContent = n(num);
    if (info && sub !== undefined) info.textContent = sub;
  }
  function renderFlow(o) {
    setCard('c-total', o.total, n(o.uc) + ' union councils and ' + n(o.mc) + ' municipal committees' + (state.district ? '' : ', in ' + n(o.nd) + ' districts'));
    // Every visit row in the selection, and how many of the UCs and MCs visited were visited more than once.
    var again = [];
    if (o.seen.U) again.push(pct(o.again.U, o.seen.U) + '% of UCs (' + n(o.again.U) + ' of ' + n(o.seen.U) + ')');
    if (o.seen.M) again.push(pct(o.again.M, o.seen.M) + '% of MCs (' + n(o.again.M) + ' of ' + n(o.seen.M) + ')');
    setCard('c-visited', o.visits, again.length ? 'Visited more than once: ' + again.join(' and ') : 'None yet');
    var nW = Object.keys(o.wDates).length;
    setCard('c-reports', o.cases, o.cases ? 'Cases in ' + pl(o.L.length, 'SMU letter', 'SMU letters') + (o.L.length ? ' (' + range(o.lDates[0], o.lDates[o.lDates.length - 1]) + ')' : '') + (nW ? ' and ' + pl(nW, 'weekly report', 'weekly reports') : '') : 'None in this selection');
    setCard('c-replied', o.L.length + o.W.length, (o.L.length + o.W.length) ? 'From district officers, on every letter and weekly report on file' : '');
  }

  /* impact cards */
  function quarterRates(list) {
    var Q = {};
    list.forEach(function (s) { s.v.forEach(function (v) {
      var k = qkey(v.dt), q = Q[k] || (Q[k] = { k: k, n: 0, op: 0, ip: 0, full: 0 });
      q.n++; if (v.op === 1) q.op++; if (v.op === 1 && v.ip === 1) q.ip++; if (v.op === 1 && v.ip === 1 && v.sd === 1) q.full++;
    }); });
    var out = [];
    if (!KEYS.min) return out;
    var k0 = qkey(firstDay()), k1 = qkey(lastDay());
    for (var k = k0; k <= k1; k++) out.push(Q[k] || { k: k, n: 0, op: 0, ip: 0, full: 0 });
    return out;
  }
  function renderImpact(o) {
    var acted = o.lc[1] + o.wt.acted, of = o.lc[0] + o.wItems, fx = [];
    if (o.fixed.board) fx.push('office boards put up');
    if (o.fixed.nofee) fx.push('fee schedules displayed');
    if (o.fixed.unclean) fx.push('offices cleaned');
    $('imp-1').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (of ? n(acted) + ' of ' + n(of) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (of ? 'problems SMU reported were fixed or acted on, says the department' : 'No SMU report here yet') + '</b><small>' +
      (of ? (fx.length ? 'Mostly ' + andList(fx) + '. ' : '') + (o.saN ? 'Action on named staff, such as PEEDA proceedings, explanations and warnings, in ' + pl(o.saN, 'response', 'responses') + '.' : '') : '') + '</small></div>';
    var good = IORD.map(function (k) { var a = o.rcI[k]; return { k: k, g: a.g, t: a.g + a.s }; }).filter(function (x) { return x.t >= 5; });
    var best = good.slice().sort(function (a, b) { return b.g / b.t - a.g / a.t || b.t - a.t; }).slice(0, 2);
    var worst = good.slice().sort(function (a, b) { return (b.t - b.g) - (a.t - a.g); })[0];
    $('imp-2').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (o.rcChk ? n(o.rcGone) + ' of ' + n(o.rcChk) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (o.rcChk ? 'reported problems were gone when SMU went back to the office' : 'No reported office visited again here yet') + '</b><small>' +
      (o.rcChk ? (best.length ? 'Put right most often: ' + andList(best.map(function (x) { return ISS[x.k].short; })) + '. ' : '') +
        (worst && worst.g / worst.t < 0.5 ? 'Seen again most often: ' + ISS[worst.k].short + '.' : '') : '') + '</small></div>';
  }

  /* issues */
  function irow(label, c, max, of, filter, good) {
    return '<button type="button" class="irow"' + (filter ? ' data-filter="' + filter + '"' : '') + '><span>' + esc(label) + '</span><span class="bar"><i style="width:' + (max ? c / max * 100 : 0) + '%' + (good ? ';background:var(--good)' : '') + '"></i></span><span class="num"><b>' + n(c) + '</b></span><span class="num pct">' + pct(c, of) + '%</span></button>';
  }
  function irowHead(a, b, c) { return '<div class="irow irow-h"><span>' + a + '</span><span></span><span class="num">' + b + '</span><span class="num">' + (c || 'Share') + '</span></div>'; }
  function renderIssues(o) {
    $('tiles').innerHTML = TILES.map(function (id) {
      var f = FI[id], c = o.find[id], b = o.base[id];
      return '<button type="button" class="tile" data-filter="f:' + id + '"><b>' + n(c) + '</b><span>' + esc(f.label) + '</span><small>' + esc(f.why) + ' &middot; ' + (b ? pct(c, b) + '% of ' + n(b) + ' checked' : 'not checked') + '</small></button>';
    }).join('');
  }

  /* trend */
  var SER = [
    { id: 'op', label: 'Office open', c: 'var(--c-open)', w: 2.5 },
    { id: 'ip', label: 'Open, with the secretary or CO present', c: 'var(--c-pres)', w: 2.5 },
    { id: 'full', label: 'Open, in-charge present and serving citizens', c: 'var(--c-full)', w: 4 }
  ];
  function renderTrend(list) {
    var Q = quarterRates(list), MINV = 10, last = lastDay();
    var QQ = Q.filter(function (q) { return q.n >= 20; }), qa = QQ[0], qb = QQ[QQ.length - 1];
    if (qa && qb && qa !== qb) {
      var pa = Math.round(qa.full / qa.n * 100), pb = Math.round(qb.full / qb.n * 100);
      $('kf').innerHTML = '<b>' + pb + '%</b> of offices visited in ' + qlabel(qb.k, last) + ' were open, with the in-charge present and serving citizens, ' + (pb < pa ? 'down from ' : pb > pa ? 'up from ' : 'the same as ') + pa + '% in ' + qlabel(qa.k) + '.';
    } else $('kf').innerHTML = 'Too few visits here to compare quarters.';
    $('ckey').innerHTML = SER.map(function (s) { return '<span><i class="' + (s.w > 3 ? 'thick' : '') + '" style="background:' + s.c + '"></i>' + esc(s.label) + '</span>'; }).join('');
    if (!Q.length) { $('chart').innerHTML = ''; $('chart-note').textContent = 'No visit in this selection.'; return; }
    var W = 760, H = 300, l = 40, r = 18, t = 14, b = 62, cw = (W - l - r) / Q.length;
    function x(i) { return l + cw * i + cw / 2; }
    function y(p) { return t + (H - t - b) * (1 - p); }
    var g = '<svg class="chart" viewBox="0 0 ' + W + ' ' + H + '" role="img" aria-label="Share of visits where the office was open, the in-charge present and citizens served, by quarter">';
    [0, 0.25, 0.5, 0.75, 1].forEach(function (p) { g += '<line class="gl" x1="' + l + '" x2="' + (W - r) + '" y1="' + y(p) + '" y2="' + y(p) + '"></line><text class="ax" x="' + (l - 8) + '" y="' + (y(p) + 4) + '" text-anchor="end">' + Math.round(p * 100) + '%</text>'; });
    Q.forEach(function (q, i) {
      g += '<rect class="hit" x="' + (l + cw * i) + '" y="' + t + '" width="' + cw + '" height="' + (H - t - b + 46) + '" data-tip="<b>' + qlabel(q.k, last) + '</b>' + (q.n ? pl(q.n, 'visit', 'visits') + '<br>' + SER.map(function (s) { return esc(s.label) + ': ' + pct(q[s.id], q.n) + '%'; }).join('<br>') : 'No visits') + '"></rect>';
      var lab = qlabel(q.k, last).split(' ');
      g += '<text class="axb" x="' + x(i) + '" y="' + (H - b + 20) + '" text-anchor="middle">' + lab[0] + '</text><text class="ax" x="' + x(i) + '" y="' + (H - b + 35) + '" text-anchor="middle">' + lab[1] + '</text>';
      g += '<text class="ax" x="' + x(i) + '" y="' + (H - b + 54) + '" text-anchor="middle">' + (q.n ? n(q.n) + ' visits' : '&ndash;') + '</text>';
    });
    SER.forEach(function (s) {
      var pts = Q.map(function (q, i) { return q.n >= MINV ? [x(i), y(q[s.id] / q.n), Math.round(q[s.id] / q.n * 100)] : null; });
      var d = '', on = false;
      pts.forEach(function (p) { if (!p) { on = false; return; } d += (on ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); on = true; });
      g += '<path d="' + d + '" fill="none" style="stroke:' + s.c + '" stroke-width="' + s.w + '" stroke-linejoin="round" stroke-linecap="round" pointer-events="none"></path>';
      var real = pts.filter(Boolean);
      real.forEach(function (p) { g += '<circle cx="' + p[0] + '" cy="' + p[1] + '" r="' + (s.w > 3 ? 4.5 : 3.5) + '" style="fill:' + s.c + ';stroke:var(--surface)" stroke-width="1.5" pointer-events="none"></circle>'; });
      if (s.id !== 'full') [real[0], real[real.length - 1]].forEach(function (p, j) { if (!p || (j && real.length < 2)) return; g += '<text class="vl" x="' + (p[0] + (j ? 8 : -8)) + '" y="' + (p[1] + 4) + '" text-anchor="' + (j ? 'start' : 'end') + '" style="fill:' + s.c + '" pointer-events="none">' + p[2] + '%</text>'; });
    });
    g += '</svg>';
    $('chart').innerHTML = g;
    var few = Q.filter(function (q) { return q.n && q.n < MINV; }).length;
    /* the same offices, first visit against latest visit */
    var R = list.filter(function (s) { return s.v.length > 1; }), f = 0, lt = 0;
    R.forEach(function (s) { if (status(s.v[0]) === 'S') f++; if (status(s.V) === 'S') lt++; });
    $('chart-note').textContent = (R.length >= 20 ? 'This is not only because different offices were visited: at the ' + n(R.length) + ' offices SMU visited more than once, ' + n(f) + ' were open and serving at the first visit and ' + n(lt) + ' at the latest. ' : '') +
      (few ? 'Quarters with fewer than ' + MINV + ' visits are left off the lines. ' : '') + 'Hover a quarter for its figures.';
  }

  /* department response */
  function seg(el, parts, tot, unit) {
    $(el).innerHTML = parts.map(function (p) { return p.c ? '<i class="' + p.sw + '" style="width:' + (p.c / tot * 100) + '%" data-tip="<b>' + esc(p.label) + '</b>' + pl(p.c, unit, unit + 's') + ', ' + pct(p.c, tot) + '%"></i>' : ''; }).join('');
    $(el).setAttribute('aria-label', parts.map(function (p) { return p.label + ' ' + p.c; }).join(', '));
  }
  function renderChanged(o) {
    var wd = Object.keys(o.wDates).sort();
    // When SMU wrote letters and when weekly reports, from the response file.
    $('changed-sub').textContent = (o.lDates.length || wd.length)
      ? 'SMU sent its findings ' + (o.lDates.length ? 'as letters ' + (wd.length ? 'up to ' + ml(o.lDates[o.lDates.length - 1]) : 'from ' + range(o.lDates[0], o.lDates[o.lDates.length - 1])) : '') +
        (o.lDates.length && wd.length ? ' and ' : '') + (wd.length ? 'as weekly district-wise reports ' + (o.lDates.length ? 'from ' + ml(wd[0]) : 'from ' + range(wd[0], wd[wd.length - 1])) : '') + '. The department\'s district officers (DDLGs) responded to each.'
      : 'No SMU letter or weekly report is on file' + where() + '.';
    $('w-range').textContent = wd.length ? range(wd[0], wd[wd.length - 1]) : 'none here';
    $('w-sub').textContent = o.wItems ? 'Each problem at each office counts once, sorted by what the department\'s response says about it. Click a row to see those offices on the map.' : 'No weekly report in this selection.';
    $('l-range').textContent = o.lDates.length ? range(o.lDates[0], o.lDates[o.lDates.length - 1]) : 'none here';
    $('l-sub').textContent = o.lc[0] ? pl(o.lc[0], 'case', 'cases') + ' in ' + pl(o.L.length, 'letter', 'letters') + ', split by the department itself in its response.' : 'No letter in this selection.';
    var split = LT.reduce(function (a, t) { return a + o.lc[t.i]; }, 0);
    seg('l-bar', LT.map(function (t) { return { c: o.lc[t.i], sw: RTI[t.id].sw, label: t.label }; }), split || 1, 'case');
    $('l-outs').innerHTML = LT.map(function (t) { return '<div class="card out" style="background:var(--surface-2);border-radius:var(--radius-sm);padding:10px 12px"><div class="out-num">' + n(o.lc[t.i]) + '</div><div class="out-lbl"><span class="sw ' + RTI[t.id].sw + '"></span>' + esc(t.label) + '</div><div class="out-sub">' + esc(t.sub) + '</div></div>'; }).join('');
    /* weekly reports: problem x response, with a total row */
    function stack(lbl, a, tt) {
      return '<div class="stack" role="img" aria-label="' + RT.map(function (t) { return t.label + ' ' + a[t.id]; }).join(', ') + '" data-tip="<b>' + esc(lbl) + '</b>' + RT.filter(function (t) { return a[t.id]; }).map(function (t) { return esc(t.label) + ': ' + n(a[t.id]); }).join('<br>') + '">' +
        RT.map(function (t) { return a[t.id] ? '<i class="' + t.sw + '" style="width:' + a[t.id] / tt * 100 + '%"></i>' : ''; }).join('') + '</div>';
    }
    var rows = IORD.map(function (k) { return { k: k, a: o.top[k] }; }).filter(function (x) { return x.a.n; }).sort(function (a, b) { return b.a.n - a.a.n; });
    $('iss-t').innerHTML = rows.length ? '<thead><tr><th>Problem reported</th><th class="r">Problems</th><th>Department\'s response</th><th class="r">Fixed or acted on</th></tr></thead><tbody>' + rows.map(function (x) {
      var a = x.a, tt = a.n || 1;
      return '<tr data-filter="c:' + x.k + '"><td class="lab">' + esc(ISS[x.k].label) + '</td><td class="r">' + n(a.n) + '</td><td>' + stack(ISS[x.k].label, a.t, tt) + '</td><td class="r' + (a.t.acted / tt < 0.25 ? ' dn' : '') + '">' + n(a.t.acted) + ' <span class="pct">' + pct(a.t.acted, tt) + '%</span></td></tr>';
    }).join('') + '<tr class="tot"><td class="lab">All problems, at ' + pl(Object.keys(o.wOff).length, 'office', 'offices') + '</td><td class="r">' + n(o.wItems) + '</td><td>' + stack('All problems', o.wt, o.wItems || 1) + '</td><td class="r">' + n(o.wt.acted) + ' <span class="pct">' + pct(o.wt.acted, o.wItems) + '%</span></td></tr></tbody>'
      : '<tbody><tr><td class="empty" style="padding:12px 18px">No weekly report in this selection.</td></tr></tbody>';
    $('iss-key').innerHTML = RT.map(function (t) { return '<span><i class="sw ' + t.sw + '"></i>' + esc(t.label) + '</span>'; }).join('');
    /* reasons */
    var rs = RSN.reduce(function (a, x) { return a + o.rsn[x.id]; }, 0), mr = Math.max.apply(null, RSN.map(function (x) { return o.rsn[x.id]; }).concat([1]));
    $('why-sub').textContent = rs ? 'For closed offices, absent secretaries and no service, where the department gave a reason.' : 'No reason given in this selection.';
    $('why-rows').innerHTML = rs ? irowHead('Reason given', 'Problems') + RSN.filter(function (x) { return o.rsn[x.id]; }).map(function (x) { return irow(x.label, o.rsn[x.id], mr, rs, ''); }).join('') : '';
    var lastRep = o.W.length ? o.W.map(function (r) { return r.sd; }).filter(Boolean).sort().pop() : o.lDates[o.lDates.length - 1];
    $('caveat').innerHTML = '<b>These are the department\'s own statements.</b> ' + (lastRep ? 'The latest response on file is on SMU\'s report of ' + dl(lastRep) + '. ' : '') + 'Each office\'s full response text is in its report on the map.';
  }

  /* rechecks */
  function renderRecheck(o) {
    var rc = o.rcNx, gaps = rc.map(function (c) { return days(c.after, c.nx.dt); });
    $('rc-sub').textContent = o.rc.length ? 'SMU went back to ' + pl(rc.length, 'reported office', 'reported offices') + ' after the department\'s response' + where() + (rc.length ? ', usually ' + pl(median(gaps) || 0, 'day', 'days') + ' later' : '') + (fullRange() ? '' : ', in the dates chosen') + '.' : 'No reported office in this selection.';
    var mx = 1, rows = IORD.map(function (k) { var a = o.rcI[k]; mx = Math.max(mx, a.g + a.s); return { k: k, g: a.g, s: a.s }; }).filter(function (x) { return x.g + x.s; })
      .sort(function (a, b) { return b.g / (b.g + b.s) - a.g / (a.g + a.s); });
    $('rc-rows').innerHTML = rows.length ? '<div class="rck rck-h"><span>Problem reported</span><span></span><span class="num">Gone / rechecked</span></div>' + rows.map(function (x) {
      var t = x.g + x.s;
      return '<button type="button" class="rck" data-filter="g:' + x.k + '"><span>' + esc(ISS[x.k].label) + '</span><span class="gs" style="width:' + Math.max(18, t / mx * 100) + '%" data-tip="<b>' + esc(ISS[x.k].label) + '</b>' + n(x.g) + ' gone, ' + n(x.s) + ' still there"><i class="g" style="width:' + x.g / t * 100 + '%"></i><i class="s" style="width:' + x.s / t * 100 + '%"></i></span><span class="num"><b>' + n(x.g) + '</b> of ' + n(t) + ' &middot; ' + pct(x.g, t) + '%</span></button>';
    }).join('') : '<p class="empty">No reported office visited again in this selection.</p>';
    $('fu-note').textContent = rows.length ? 'Click a row to see those offices on the map; each office\'s report shows its visits before and after.' : '';
  }

  /* districts */
  function districtRows() {
    var by = {};
    ALL.forEach(function (s) { if (s.v.length) (by[s.d] = by[s.d] || []).push(s); });
    return Object.keys(TOT).map(function (d) {
      var L = by[d] || [], v = L.length, c = { closed: 0, absent: 0, open: 0, S: 0, over: 0, overB: 0, visits: 0, listed: 0 };
      L.forEach(function (s) { var x = s.V; c.visits += s.v.length; if (s.ml) c.listed++; if (x.op === 0) c.closed++; else { c.open++; if (x.ip === 0) c.absent++; } if (status(x) === 'S') c.S++; if (x.ff !== null) { c.overB++; if (x.ff === 0) c.over++; } });
      var cases = REP.filter(function (r) { return r.d === d; }).reduce(function (a, r) { return a + r.c[0]; }, 0);
      var tot = TOT[d].u + TOT[d].m;
      return { d: d, total: tot, visited: c.listed, all: v, cov: tot ? c.listed / tot : 0, visits: c.visits, closed: v ? c.closed / v : -1, absent: c.open ? c.absent / c.open : -1, serving: v ? c.S / v : -1, over: c.overB ? c.over / c.overB : -1, cases: cases };
    });
  }
  var DCOLS = [
    { k: 'd', t: 'District' }, { k: 'total', t: 'Total offices', r: 1 }, { k: 'visited', t: 'Visited', r: 1 }, { k: 'cov', t: 'Share visited' }, { k: 'visits', t: 'Visits', r: 1 },
    { k: 'serving', t: 'Open and serving', r: 1 }, { k: 'closed', t: 'Closed', r: 1 }, { k: 'absent', t: 'In-charge away (open offices)', r: 1 }, { k: 'over', t: 'Overcharging reported', r: 1 }, { k: 'cases', t: 'Cases reported', r: 1 }
  ];
  function renderDistricts() {
    var D = districtRows(), k = state.sort, dir = state.dir;
    var rows = D.slice().sort(function (a, b) { var x = a[k], y = b[k]; return (typeof x === 'string' ? x.localeCompare(y) : x - y) * dir || b.total - a.total || a.d.localeCompare(b.d); });
    function rate(r, key, hi, low) { var x = r[key]; return x < 0 ? '<td class="r">&ndash;</td>' : '<td class="r' + (low ? (x <= hi ? ' dn' : '') : (x >= hi ? ' dn' : '')) + '">' + Math.round(x * 100) + '%</td>'; }
    $('dt').innerHTML = '<thead><tr>' + DCOLS.map(function (c) {
      var on = c.k === k;
      return '<th scope="col" class="' + (c.r ? 'r' : '') + '" aria-sort="' + (on ? (dir > 0 ? 'ascending' : 'descending') : 'none') + '"><button type="button" data-sort="' + c.k + '">' + c.t + (on ? (dir > 0 ? ' ↑' : ' ↓') : '') + '</button></th>';
    }).join('') + '</tr></thead><tbody>' + rows.map(function (r) {
      return '<tr data-district="' + esc(r.d) + '"' + (r.d === state.district ? ' class="on"' : '') + '><td><button type="button" class="dname" data-district="' + esc(r.d) + '">' + esc(r.d) + '</button></td><td class="r">' + n(r.total) + '</td><td class="r">' + n(r.visited) + '</td>' +
        '<td><div class="cov"><div class="bar"><i style="width:' + Math.min(1, r.cov) * 100 + '%"></i></div><span>' + pct(r.visited, r.total) + '%</span></div></td><td class="r">' + n(r.visits) + '</td>' +
        rate(r, 'serving', 0.2, true) + rate(r, 'closed', 0.4) + rate(r, 'absent', 0.6) + rate(r, 'over', 0.6) + '<td class="r">' + (r.cases ? n(r.cases) : '&ndash;') + '</td></tr>';
    }).join('') + '</tbody>';
    var none = D.filter(function (r) { return !r.all; }).map(function (r) { return r.d; });
    $('dt-sub').textContent = none.length ? andList(none) + ' ' + (none.length === 1 ? 'has' : 'have') + ' no SMU visit' + (fullRange() ? ' yet' : ' in the dates chosen') + '. ' : '';
  }

  /* map */
  var map = L.map('map', { preferCanvas: true, minZoom: 5, maxZoom: 18, zoomSnap: 0.5 });
  map.attributionControl.setPrefix(false); // No "Leaflet" or flag in the corner, only the basemap's credit.
  var tileBase = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_';
  L.tileLayer(tileBase + 'Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', attribution: 'Basemap &copy; Esri, HERE, Garmin, OpenStreetMap contributors' }).addTo(map);
  L.tileLayer(tileBase + 'Reference/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', pane: 'overlayPane' }).addTo(map);
  var css = getComputedStyle($('psrpFindings'));
  function cv(name) { return css.getPropertyValue(name).trim(); }
  var C = { S: cv('--st-s'), P: cv('--st-p'), C: cv('--st-c'), surf: cv('--surface'), ink: cv('--ink') };
  var renderer = L.canvas({ padding: 0.3, tolerance: 5 });
  var pins = L.layerGroup().addTo(map);
  var ring = null, focusPin = null, drawn = {};
  var BOUNDS = [[27.9, 69.3], [33.9, 75.2]];
  function radius() { var z = map.getZoom() || 7; return z < 7 ? 3 : z < 8 ? 3.5 : z < 10 ? 4.5 : z < 12 ? 6 : 7.5; }
  function makeMarker(s) {
    var st = status(s.V), r = radius(), rep = s.r.length > 0;
    var m = L.circleMarker([s.la, s.lo], { radius: rep ? r + 1 : r, renderer: renderer, stroke: true, color: rep ? C.ink : C.surf, weight: rep ? 2 : 0.8, fillColor: C[st], fillOpacity: 1 });
    m.bindTooltip(function () { return esc(s.label) + ', ' + esc(s.d) + ' (' + STL[st].toLowerCase() + ', ' + dl(s.V.dt) + ')' + (rep ? ' · reported to the department' : ''); }, { direction: 'top', offset: [0, -4] });
    m.on('click', function () { openOffice(s, false); });
    return m;
  }
  function wItemsOf(s) { var out = []; s.r.forEach(function (id) { var r = RI[id]; if (r && r.f === 'W') r.it.forEach(function (it) { if (it[0] === s.k) out.push(it); }); }); return out; }
  function matchesFilter(s) {
    var f = state.filter; if (!f) return true;
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 'f:') return FI[id].f(s.V);
    if (k === 't:') return wItemsOf(s).some(function (it) { return it[3] === id; });
    if (k === 'c:') return wItemsOf(s).some(function (it) { return it[2] === id; });
    if (k === 'g:') return RC.some(function (c) { return c.o === s && c.nx && c.iss.indexOf(id) >= 0 && bad(c.nx, id) !== null; });
    if (f === 'fu') return RC.some(function (c) { return c.o === s && c.nx; });
    if (f === 'rv') return s.v.length > 1;
    return true;
  }
  function filterLabel(f) {
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 'f:') return FI[id].label;
    if (k === 't:') return 'Response: ' + RTI[id].label;
    if (k === 'c:') return 'Reported: ' + ISS[id].label;
    if (k === 'g:') return 'Rechecked: ' + ISS[id].label;
    if (f === 'fu') return 'Visited again after the report';
    if (f === 'rv') return 'Visited more than once';
    return '';
  }
  function matchesMap(s) {
    if (state.show === 'r' && !s.r.length) return false;
    if ('SPC'.indexOf(state.show) >= 0 && state.show.length === 1 && status(s.V) !== state.show) return false;
    return matchesFilter(s);
  }
  function renderMap(fit) {
    pins.clearLayers();
    drawn = {};
    var list = scoped().filter(matchesMap), cnt = { S: 0, P: 0, C: 0 }, rep = 0, unmapped = 0, pts = [], visitIds = [];
    list.sort(function (a, b) { return (a.r.length ? 1 : 0) - (b.r.length ? 1 : 0); });
    list.forEach(function (s) {
      if (s.la === null || s.la === undefined) { unmapped++; return; }
      cnt[status(s.V)]++; if (s.r.length) rep++; pins.addLayer(makeMarker(s)); drawn[s.k] = 1; pts.push([s.la, s.lo]);
      // The visit the office's pin comes from: its latest in the chosen dates.
      visitIds.push(s.V.id);
    });
    // As on the other pages: only a chosen finding or response can be downloaded, and the file
    // holds exactly the offices on the map, one row each, from our visit file.
    $('map-download').hidden = !(state.filter && visitIds.length);
    if (state.filter) {
      $('dl-ids').value = visitIds.join(',');
      $('dl-issue').value = filterLabel(state.filter);
      $('dl-label').textContent = 'Download ' + pl(visitIds.length, 'office', 'offices');
    }
    fitSearchHint(); // The button takes room from the search box, or gives it back.
    var lg = ['S', 'P', 'C'].filter(function (k) { return cnt[k]; }).map(function (k) { return '<div class="lg"><span class="pin s' + k.toLowerCase() + '"></span>' + STL[k] + ' <span class="lg-n">' + n(cnt[k]) + '</span></div>'; }).join('');
    $('legend').innerHTML = lg + (rep ? '<div class="lg"><span class="pin rr"></span>Dark ring: reported to the department <span class="lg-n">' + n(rep) + '</span></div>' : '') +
      (state.filter ? '<div class="lg"><span class="lg-n">With "' + esc(filterLabel(state.filter)) + '"</span></div>' : '') + (pts.length ? '' : '<div class="lg">No office to show</div>');
    $('map-note').textContent = (unmapped ? pl(unmapped, 'office', 'offices') + ' in this view ' + (unmapped === 1 ? 'has' : 'have') + ' no usable map location in the monitoring data, so ' + (unmapped === 1 ? 'it is' : 'they are') + ' not on the map. Search finds ' + (unmapped === 1 ? 'it' : 'them') + '. ' : '') + 'Offices in the total list that SMU has not visited have no location, so they are not shown.';
    if (fit) {
      var zone = pts.length ? pts : scoped().filter(function (s) { return s.la !== null; }).map(function (s) { return [s.la, s.lo]; });
      if (zone.length && (state.district || state.filter)) map.fitBounds(L.latLngBounds(zone).pad(0.08), { maxZoom: 12 });
      else map.fitBounds(BOUNDS);
    }
    if (state.sel) placeRing(OI[state.sel] || null);
  }
  map.on('zoomend', function () { if (loaded) renderMap(false); });
  // The office opened always shows inside its ring, even when the map's choices hide its pin.
  function placeRing(s) {
    if (ring) { map.removeLayer(ring); ring = null; }
    if (focusPin) { map.removeLayer(focusPin); focusPin = null; }
    if (s && s.la !== null && s.la !== undefined && s.V) {
      if (!drawn[s.k]) focusPin = makeMarker(s).addTo(map);
      ring = L.circleMarker([s.la, s.lo], { radius: 13, color: C.ink, weight: 2.5, fill: false, interactive: false, renderer: renderer }).addTo(map);
    }
  }
  function syncSeg() { document.querySelectorAll('#mapsec .seg button').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.show === state.show)); }); }

  /* office panel */
  function renderHint() {
    $('panel').innerHTML = '<div class="panel-hint"><h3>Office report</h3><p>Click a pin on the map, or search above, to see the office\'s visits here.</p><ul>' +
      '<li><span class="pin ss" style="display:inline-block;vertical-align:-1px"></span> <b>Green:</b> open, with the secretary or CO present and citizens being served at the latest visit.</li>' +
      '<li><span class="pin sp" style="display:inline-block;vertical-align:-1px"></span> <b>Amber:</b> open, but the in-charge was away or citizens were not being served.</li>' +
      '<li><span class="pin sc" style="display:inline-block;vertical-align:-1px"></span> <b>Red:</b> closed during office hours.</li>' +
      '<li><b>Dark ring:</b> named in an SMU report to the department. You will also see the department\'s response.</li></ul></div>';
  }
  function yn(val, goodWhen, naText) {
    if (val === null || val === undefined) return '<span class="na">' + (naText || 'Not checked') + '</span>';
    var good = val === goodWhen;
    return '<span class="' + (good ? 'ok' : 'bad') + '">' + (good ? '✓ ' : '✗ ') + (val === 1 ? 'Yes' : 'No') + '</span>';
  }
  function visitBlock(v) {
    var f = FIND.filter(function (x) { return x.f(v); });
    var h = '<div class="chips">' + (f.length ? f.map(function (x) { return '<span class="chip">' + esc(x.label) + '</span>'; }).join('') : '<span class="chip ok">No problem found</span>') + '</div>';
    var oldForm = 'Not on the form then';
    h += '<table class="rt"><tbody>' + [
      ['Office open', yn(v.op, 1)],
      ['Secretary or CO present', v.op === 0 ? '<span class="na">Office closed</span>' : yn(v.ip, 1)],
      ['Citizens being served', v.op === 0 ? '<span class="na">Office closed</span>' : yn(v.sd, 1)],
      ['Fee schedule displayed', v.op === 0 ? '<span class="na">Office closed</span>' : yn(v.fd, 1, 'Not applicable')],
      ['Fee schedule followed (no overcharging)', v.op === 0 ? '<span class="na">Office closed</span>' : yn(v.ff, 1, 'Not applicable')],
      ['Private person running the office', v.op === 0 ? '<span class="na">Office closed</span>' : yn(v.pp, 0, oldForm)],
      ['Building dilapidated', v.op === 0 ? '<span class="na">Office closed</span>' : yn(v.db, 0, oldForm)],
      ['Office clean', v.op === 0 ? '<span class="na">Office closed</span>' : yn(v.cl, 1, oldForm)],
      ['Office board displayed', yn(v.bd, 1, oldForm)]
    ].map(function (r) { return '<tr><td>' + r[0] + '</td><td>' + r[1] + '</td></tr>'; }).join('') + '</tbody></table>';
    h += '<p class="note">Visited by ' + (v.ag === 'D' ? 'a district focal person' : v.ag === 'S' ? 'the SMU team' : v.ag === 'F' ? 'an SMU field agent' : 'SMU') + '.</p>';
    return h;
  }
  function openOffice(s, focus, quiet) {
    if (!s.V) return;
    state.sel = s.k; placeRing(s);
    if (focus && s.la !== null && s.la !== undefined) map.setView([s.la, s.lo], Math.max(map.getZoom(), 12));
    var v = s.V, st = status(v);
    var h = '<div class="p-top"><div><div class="p-name">' + esc(s.label) + '</div><div class="p-meta">' + (s.t === 'M' ? 'Municipal Committee' : 'Union Council') + ' &middot; ' + esc(s.d) + ' &middot; ' + esc(s.th) + (s.ml ? '' : ' &middot; not in the total list') + '</div></div><button type="button" class="x" id="p-close" aria-label="Close report">×</button></div>';
    h += '<div class="badges"><span class="badge b-st-' + st + '">● ' + STL[st] + '</span><span class="badge b-mute">' + (s.v.length > 1 ? pl(s.v.length, 'visit', 'visits') + ', ' + dl(s.v[0].dt) + ' to ' + dl(v.dt) : 'Visited ' + dl(v.dt)) + '</span>' + (s.r.length ? '<span class="badge b-info">In ' + pl(s.r.length, 'SMU report', 'SMU reports') + '</span>' : '') + '</div>';
    if (s.la === null || s.la === undefined) h += '<p class="note">This office has no usable map location, so it has no pin.</p>';
    if (s.r.length) {
      h += '<div class="p-sec"><h3>Reported to the department</h3>' + s.r.slice().reverse().map(function (id) {
        var r = RI[id], c = RC.filter(function (x) { return x.rid === id && x.o === s; })[0];
        var b = '<div class="rep"><div class="rep-h">' + (r.f === 'W' ? 'SMU weekly report of ' + dl(r.sd) : 'Department letter of ' + dl(r.ld)) + (c && c.rvV ? ', on the visit of ' + dl(c.rvV.dt) : '') + '</div>';
        if (r.f === 'W') {
          var its = r.it.filter(function (it) { return it[0] === s.k; });
          b += '<ul class="p-iss">' + its.map(function (it) { var t = RTI[it[3]]; return '<li>' + esc(ISS[it[2]].label) + ' <span class="tbadge badge ' + t.badge + '">' + esc(t.label) + '</span>' + (it[4] ? ' <small>' + esc((RSN.filter(function (x) { return x.id === it[4]; })[0] || { label: '' }).label) + '</small>' : '') + '</li>'; }).join('') + '</ul>';
        } else {
          b += '<div class="cnts">' + LT.filter(function (t) { return r.c[t.i]; }).map(function (t) { return '<span class="tbadge badge ' + RTI[t.id].badge + '">' + esc(t.label) + ' ' + r.c[t.i] + '</span>'; }).join('') + '</div><p class="note">The letter gives counts for the whole district (' + pl(r.c[0], 'case', 'cases') + '), not per office.</p>';
        }
        b += '<small class="rlab">Department\'s response (' + esc(r.d) + ')</small><p class="rresp" title="Click to read all of it">' + esc(r.x || 'No response text on file') + '</p>';
        if (c && c.nx) {
          var res = c.iss.map(function (k) { var x = bad(c.nx, k); return esc(ISS[k].label) + ' &ndash; ' + (x === null ? 'not checked' : x ? '<span class="bad">seen again</span>' : '<span class="ok">gone</span>'); });
          b += '<p class="note"><b>Visited again ' + dl(c.nx.dt) + ':</b> ' + (res.length ? res.join('; ') : 'no problem was recorded at the reported visit') + '.</p>';
        } else if (c) b += '<p class="note">Not visited again since' + (fullRange() ? '' : ', in the dates chosen') + '.</p>';
        return b + '</div>';
      }).join('') + '</div>';
    }
    h += '<div class="p-sec"><h3 style="margin-top:4px">Latest visit, ' + dl(v.dt) + '</h3>' + visitBlock(v) + '</div>';
    var older = s.v.slice(0, -1).reverse();
    older.slice(0, 12).forEach(function (e) { h += '<details class="visit"><summary>Earlier visit, ' + dl(e.dt) + ' &middot; ' + STL[status(e)].toLowerCase() + '</summary>' + visitBlock(e) + '</details>'; });
    if (older.length > 12) h += '<p class="note">' + pl(older.length - 12, 'earlier visit is', 'earlier visits are') + ' not listed here.</p>';
    var panel = $('panel');
    panel.innerHTML = h; panel.scrollTop = 0;
    $('p-close').addEventListener('click', function () { state.sel = null; placeRing(null); renderHint(); });
    if (!quiet && page.clientWidth <= 1100) panel.scrollIntoView({ block: 'start' });
  }

  /* page */
  function renderAll(fit) {
    var list = scoped(), o = summarise(list);
    $('f-clear').hidden = !state.district && fullRange();
    if (KEYS.min) { $('f-from').value = keyToISO(state.from); $('f-to').value = keyToISO(state.to); }
    nameForPdf();
    renderFlow(o); renderImpact(o); renderIssues(o); renderTrend(list); renderChanged(o); renderRecheck(o); renderDistricts(); renderMap(fit);
    if (state.sel) {
      var s = OI[state.sel];
      if (!s || list.indexOf(s) < 0) { state.sel = null; placeRing(null); renderHint(); } else openOffice(s, false, true);
    }
  }
  // What the page shows, for the name of the PDF made with Export to PDF ("Kot Addu, visits from ...").
  function nameForPdf() {
    var place = state.district ? state.district : 'all districts', dates = !KEYS.min || fullRange() ? '' : ', visits from ' + rangeText();
    $('pdfExport').setAttribute('data-pdf-what', place + dates);
  }
  // Tells the records table below to show the same district and dates.
  function filtersChanged() {
    document.dispatchEvent(new CustomEvent('psrp:filters', { detail: {
      district: state.district,
      from: !KEYS.min || state.from === KEYS.min ? '' : keyToISO(state.from),
      to: !KEYS.max || state.to === KEYS.max ? '' : keyToISO(state.to)
    } }));
  }
  function fillDistricts() {
    var names = Object.keys(TOT).sort();
    if (state.district && names.indexOf(state.district) < 0) names.push(state.district);
    $('f-district').innerHTML = '<option value="">All districts</option>' + names.map(function (d) { return '<option' + (d === state.district ? ' selected' : '') + '>' + esc(d) + '</option>'; }).join('');
  }
  function setDistrict(d) {
    state.district = d;
    $('f-district').value = d;
    if (loaded) renderAll(true);
    filtersChanged();
  }
  function setRange(a, b) {
    a = Math.max(a || KEYS.min, KEYS.min); b = Math.min(b || KEYS.max, KEYS.max);
    if (a > b) { var t = a; a = b; b = t; }
    state.from = a; state.to = b;
    if (loaded) { derive(); renderAll(false); }
    filtersChanged();
  }
  function selectFilter(f) {
    state.filter = f; state.show = 'all'; syncSeg();
    $('f-find').value = Array.prototype.some.call($('f-find').options, function (o) { return o.value === f; }) ? f : '';
    renderMap(true); $('mapsec').scrollIntoView();
  }

  // Typing a date passes through years like 0002, which are not worth acting on.
  function usableDate(value) { return value === '' || value >= '2000-01-01'; }
  $('psrpFilters').addEventListener('submit', function (e) { e.preventDefault(); });
  $('f-district').addEventListener('change', function () { setDistrict(this.value); });
  $('f-from').addEventListener('change', function () { if (loaded && KEYS.min && usableDate(this.value)) setRange(this.value ? isoToKey(this.value) : KEYS.min, state.to); });
  $('f-to').addEventListener('change', function () { if (loaded && KEYS.min && usableDate(this.value)) setRange(state.from, this.value ? isoToKey(this.value) : KEYS.max); });
  $('f-clear').addEventListener('click', function () {
    state.district = ''; state.from = KEYS.min; state.to = KEYS.max;
    fillDistricts();
    if (loaded) { derive(); renderAll(true); }
    filtersChanged();
  });
  $('f-find').addEventListener('change', function () { state.filter = this.value; if (state.filter) { state.show = 'all'; syncSeg(); } renderMap(true); });
  document.querySelector('#mapsec .seg').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    state.show = b.dataset.show; syncSeg(); renderMap(false);
  });
  $('psrpFindings').addEventListener('click', function (e) {
    var t = e.target.closest('.rresp');
    if (t) { t.classList.toggle('open'); return; }
    t = e.target.closest('[data-open]');
    if (t) { var s = OI[t.dataset.open]; if (s) { openOffice(s, true); $('map').scrollIntoView({ block: 'center' }); } return; }
    t = e.target.closest('[data-filter]');
    if (t) { selectFilter(t.dataset.filter); return; }
    t = e.target.closest('[data-sort]');
    if (t) { var k = t.dataset.sort; if (state.sort === k) state.dir = -state.dir; else { state.sort = k; state.dir = k === 'd' ? 1 : -1; } renderDistricts(); return; }
    t = e.target.closest('tr[data-district], .dname');
    if (t) { setDistrict(t.dataset.district); $('issues').scrollIntoView(); }
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.search')) $('results').hidden = true;
  });
  /* Hover layer */
  var tip = $('tip');
  $('psrpFindings').addEventListener('mouseleave', function () { tip.hidden = true; });
  $('psrpFindings').addEventListener('mousemove', function (e) {
    var t = e.target.closest('[data-tip]');
    if (!t) { tip.hidden = true; return; }
    tip.innerHTML = t.getAttribute('data-tip'); tip.hidden = false;
    var x = Math.min(e.clientX + 14, window.innerWidth - tip.offsetWidth - 8), y = e.clientY - tip.offsetHeight - 10;
    tip.style.left = Math.max(8, x) + 'px'; tip.style.top = (y < 4 ? e.clientY + 16 : y) + 'px';
  });
  $('q').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase(), box = $('results');
    if (q.length < 2 || !loaded) { box.hidden = true; return; }
    var num = q.replace(/^uc\s*-?\s*#?\s*/, '');
    var hits = scoped().filter(function (s) {
      return (s.label + ' ' + s.d + ' ' + s.th).toLowerCase().indexOf(q) >= 0 || (/^\d+$/.test(num) && String(s.no) === num);
    });
    hits.sort(function (a, b) { return (b.r.length ? 1 : 0) - (a.r.length ? 1 : 0) || a.d.localeCompare(b.d); });
    box.innerHTML = hits.slice(0, 12).map(function (s) {
      return '<button type="button" data-hit="' + esc(s.k) + '">' + esc(s.label) + '<small>' + esc(s.d) + ' &middot; ' + esc(s.th) + ' &middot; ' + STL[status(s.V)].toLowerCase() + ', ' + dl(s.V.dt) + (s.r.length ? ' &middot; reported' : '') + '</small></button>';
    }).join('') || '<button type="button" disabled>No office found</button>';
    box.hidden = false;
  });
  $('results').addEventListener('click', function (e) {
    var b = e.target.closest('[data-hit]'); if (!b) return;
    var s = OI[b.dataset.hit]; if (!s) return;
    openOffice(s, true); $('results').hidden = true; $('q').value = '';
    $('map').scrollIntoView({ block: 'center' });
  });

  // Section links must land below the banner and filter bar, whatever their height on this screen.
  var bar = document.querySelector('.filterbar');
  var banner = document.querySelector('.app-topbar');
  var links = bar.querySelector('.nav');
  // The links stay beside the filters while their text and gaps fit (see the filter bar styles).
  function measureLinks() {
    if (!links.offsetWidth) return;
    var list = links.querySelectorAll('a'), total = (parseFloat(getComputedStyle(links).columnGap) || 0) * (list.length - 1);
    list.forEach(function (a) { var r = document.createRange(); r.selectNodeContents(a); total += r.getBoundingClientRect().width; });
    $('psrpFilters').style.setProperty('--nav-text', Math.ceil(total) + 2 + 'px');
  }
  // The search box keeps the design's wording where it fits; on laptops, where it shares its line
  // with the Download button, it says the same thing more briefly.
  var search = $('q'), searchWords = [search.placeholder, 'UC, MC or place name'], measure = document.createElement('canvas').getContext('2d');
  window.addEventListener('resize', function () { fitSearchHint(); });
  function fitSearchHint() {
    if (!search.clientWidth) return;
    var cs = getComputedStyle(search);
    measure.font = cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily;
    var room = search.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight) - 4;
    search.placeholder = measure.measureText(searchWords[0]).width <= room ? searchWords[0] : searchWords[1];
  }
  new ResizeObserver(fitSearchHint).observe(search);

  function setScrollOffset() {
    measureLinks();
    var sticky = getComputedStyle(bar).position === 'sticky';
    document.documentElement.style.scrollPaddingTop = ((banner ? banner.offsetHeight : 0) + (sticky ? bar.offsetHeight : 0) + 12) + 'px';
  }
  setScrollOffset();
  var offsetWatcher = new ResizeObserver(setScrollOffset);
  offsetWatcher.observe(bar);
  if (banner) offsetWatcher.observe(banner);
  window.addEventListener('resize', setScrollOffset);
  new ResizeObserver(function () { map.invalidateSize(); }).observe($('map'));

  function group(label, list, key, pre) { return '<optgroup label="' + label + '">' + list.map(function (t) { return '<option value="' + key + t.id + '">' + esc((pre || '') + t.label) + '</option>'; }).join('') + '</optgroup>'; }

  function start(data) {
    META = data.meta; TOT = data.totals; REP = data.reports; RC = data.recheck;
    // Visits come once; the date filter picks from them. Reports and responses don't change with dates.
    ALL = data.offices.map(function (o) {
      o.av = o.v.map(function (a) { return { dt: a[0], ag: a[1], op: a[2], ip: a[3], sd: a[4], fd: a[5], ff: a[6], pp: a[7], db: a[8], cl: a[9], bd: a[10], id: a[11] }; });
      o.v = o.av;
      o.label = o.t === 'M' ? 'MC ' + o.n : 'UC ' + o.no + (o.n ? ' ' + o.n : '');
      o.r = o.r || [];
      return o;
    });
    OI = {}; ALL.forEach(function (o) { OI[o.k] = o; });
    RI = {}; REP.forEach(function (r) { RI[r.i] = r; });
    RC = RC.filter(function (c) { return OI[c.k]; });
    RC.forEach(function (c) { c.o = OI[c.k]; c.r = RI[c.rid]; c.rvV = c.o.av[c.rv]; });

    KEYS.min = isoToKey(META.firstVisit); KEYS.max = isoToKey(META.lastVisit);
    state.from = Math.max(state.from || KEYS.min, KEYS.min);
    state.to = Math.min(state.to || KEYS.max, KEYS.max);
    if (state.from > state.to) { state.from = KEYS.min; state.to = KEYS.max; }
    if (KEYS.min) ['f-from', 'f-to'].forEach(function (id) { $(id).min = keyToISO(KEYS.min); $(id).max = keyToISO(KEYS.max); });

    fillDistricts();
    $('f-district').value = state.district;
    $('f-find').innerHTML = '<option value="">Any office</option><option value="fu">Visited again after the report</option><option value="rv">Visited more than once</option>' +
      group('Found at the latest visit', FIND, 'f:') + group('Reported in a weekly report', IORD.map(function (k) { return { id: k, label: ISS[k].label }; }), 'c:', 'Reported: ') + group('Department response', RT, 't:', 'Response: ') +
      group('Rechecked after the report', IORD.map(function (k) { return { id: k, label: ISS[k].label }; }), 'g:', 'Rechecked: ');

    derive();
    loaded = true;
    $('load-note').hidden = true;
    renderHint();
    map.invalidateSize();
    renderAll(true);

    // The figures have just pushed everything down, so go back to any section linked to.
    var target = location.hash ? document.getElementById(location.hash.slice(1)) : null;
    if (target) { setScrollOffset(); target.scrollIntoView({ behavior: 'instant' }); }
  }
  function load() {
    $('load-note').hidden = false;
    $('load-note').textContent = 'Loading the figures…';
    fetch(page.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
      .then(function (response) {
        if (!response.ok) throw new Error('The figures failed to load: ' + response.status);
        return response.json();
      })
      .then(start)
      .catch(function (error) {
        console.error(error);
        $('load-note').innerHTML = 'The figures could not be loaded. <button type="button" class="link" id="f-retry">Try again</button>';
        $('f-retry').addEventListener('click', load);
      });
  }

  map.fitBounds(BOUNDS);
  renderHint();
  load();
  window.addEventListener('load', function () { if (!loaded) { map.invalidateSize(); map.fitBounds(BOUNDS); } });
})();
</script>
