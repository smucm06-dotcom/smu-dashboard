<script>
// Everything above the records table, and the cards in the banner, is worked out here from one
// download, so changing a filter is instant and does not reload the page. This is the Water Filtration
// Plants design's own code, inside the page frame the other initiative pages use.
// The date filter applies to visits only: the SMU reports and the department's responses always count.
(function () {
  'use strict';

  var $ = function (id) { return document.getElementById(id); };
  var page = $('psrpPage');
  var META = {}, DIS = [], KEYS = [], TX = [], REP = [], RC = [], ALL = [], UNV = [], OI = {}, loaded = false;
  var DN = [], TOT = {}, KK = {};
  var MN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  var GL = {
    notwork: { label: 'Plant not working', short: 'plant not working', why: 'No filtered water on the visit day' },
    absent: { label: 'Operator not present', short: 'operator away', why: 'No one at the plant' },
    unclean: { label: 'Plant not clean', short: 'plant not clean', why: 'Dirty plant or water filling area' },
    taps: { label: 'Water taps not working', short: 'taps not working', why: 'Not working, broken or stolen' },
    repair: { label: 'Repairs needed', short: 'repairs needed', why: 'Broken floor, leaks, unfinished work' },
    filter: { label: 'Filter needs replacing', short: 'filter', why: 'Or a coarse or fine filter not working' },
    water: { label: 'Water quality problem', short: 'water quality', why: 'Poor quality or smell' },
    power: { label: 'Electricity problem', short: 'electricity', why: 'No power, meter cut or bill pending' },
    motor: { label: 'Motor, pump or bore not working', short: 'motor or pump', why: 'Water cannot be drawn' },
    salary: { label: 'Operator unpaid for months', short: 'operator unpaid', why: 'Told to the field team' },
    private: { label: 'Run by a private company', short: 'private company', why: '' },
    notfound: { label: 'Not found at the listed location', short: 'not found', why: '' },
    closed: { label: 'Plant locked', short: 'plant locked', why: '' },
    misuse: { label: 'Theft, misuse or short hours', short: 'theft or misuse', why: '' },
    other: { label: 'Other problem', short: 'other', why: '' }
  };
  var GORD = ['notwork', 'absent', 'unclean', 'taps', 'repair', 'filter', 'water', 'power', 'motor', 'salary', 'private', 'notfound', 'closed', 'misuse', 'other'];
  var TILES = ['absent', 'unclean', 'repair', 'taps', 'filter', 'water', 'power', 'motor', 'salary'];
  var STL = { F: 'Working', N: 'Not working', X: 'Not found at the listed location', I: 'Locked, could not be checked' };
  var STS = { F: 'Gave water on the visit day', N: 'Includes plants shut for a long time', X: 'Nothing at the place in PSPA\'s list', I: 'Plant room and filling area shut' };
  var SORD = ['F', 'N', 'X', 'I'];
  var OTHER = [];

  var RT = [
    { id: 'fixed', label: 'Fixed, says the department', sub: 'Made working, cleaned, taps or filters replaced', sw: 'r-fixed', badge: 'b-good' },
    { id: 'later', label: 'Promised or under way', sub: 'To be rehabilitated, contractor told to act', sw: 'r-dir', badge: 'b-info' },
    { id: 'explained', label: 'Reason given', sub: 'One operator looks after two or more plants', sw: 'r-disp', badge: 'b-warn' },
    { id: 'denied', label: 'Denied', sub: 'Plant exists, power intact, water safe', sw: 'r-nota', badge: 'b-crit' },
    { id: 'notpspa', label: 'Not PSPA\'s plant, says the department', sub: 'Run by WASA, a council, an NGO and others', sw: 'r-npspa', badge: 'b-mute' },
    { id: 'notans', label: 'Not answered', sub: 'The response skips this point', sw: 'r-none', badge: 'b-crit' },
    { id: 'none', label: 'No response yet', sub: 'No response in the file', sw: 'r-plan', badge: 'b-mute' }
  ];
  var RTI = {}; RT.forEach(function (t) { RTI[t.id] = t; });
  var AG = [
    { id: 'wasa', label: 'WASA (water and sanitation agency)' }, { id: 'ngo', label: 'An NGO, a private owner or the community' }, { id: 'lg', label: 'A municipal committee, TMA or LG&CD' },
    { id: 'phed', label: 'Public Health Engineering (PHED)' }, { id: 'cant', label: 'A cantonment board' }, { id: 'dept', label: 'Another department (school, college, hospital)' },
    { id: 'aband', label: 'Abandoned, removed or dropped' }, { id: 'other', label: 'Not named' }
  ];
  var CMP = [
    { g: 'notwork', label: 'Plant working' }, { g: 'absent', label: 'Operator present' }, { g: 'unclean', label: 'Plant clean' }, { g: 'taps', label: 'Water taps working' },
    { g: 'repair', label: 'No repairs needed' }, { g: 'filter', label: 'Filter fine' }, { g: 'water', label: 'Water quality fine' }, { g: 'power', label: 'Electricity fine' }
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
  function wl(w) { /* SMU report dates */
    if (w[0] === w[1]) return dl(w[0]);
    var a = w[0].split('-'), b = w[1].split('-');
    if (a[0] === b[0] && a[1] === b[1]) return Number(a[2]) + '&ndash;' + dl(w[1]);
    return dl(w[0]) + ' to ' + dl(w[1]);
  }
  function months(isos) { /* "Jun to Aug 2025 and Jan 2026": runs of months that have dates */
    var ks = {}; isos.forEach(function (d) { ks[Number(d.slice(0, 4)) * 12 + Number(d.slice(5, 7)) - 1] = 1; });
    var a = Object.keys(ks).map(Number).sort(function (x, y) { return x - y; }), runs = [];
    a.forEach(function (k) { var r = runs[runs.length - 1]; if (r && k === r[1] + 1) r[1] = k; else runs.push([k, k]); });
    function lab(k, yr) { return MN[k % 12] + (yr ? ' ' + Math.floor(k / 12) : ''); }
    return andList(runs.map(function (r) { return r[0] === r[1] ? lab(r[0], 1) : lab(r[0], Math.floor(r[0] / 12) !== Math.floor(r[1] / 12)) + ' to ' + lab(r[1], 1); }));
  }
  function vdate(v) { return v.src === 'R' && v.w1 && v.w1 !== v.dt ? wl([v.dt, v.w1]) : dl(v.dt); }
  function plabel(p) { return p.n + (p.ml ? '' : ' (not in PSPA\'s list)'); }

  /* dates: visits on or after From and on or before To count */
  function keyToISO(k) { var s = String(k); return s.slice(0, 4) + '-' + s.slice(4, 6) + '-' + s.slice(6, 8); }
  function isoToKey(s) { return s ? Number(s.replace(/-/g, '')) : 0; }
  var DK = { min: 0, max: 0 };
  function fullRange() { return state.from === DK.min && state.to === DK.max; }
  function rangeText() { return dl(keyToISO(state.from)) + ' to ' + dl(keyToISO(state.to)); }

  var state = { district: $('f-district').value, from: isoToKey($('f-from').value), to: isoToKey($('f-to').value), sort: 'visited', dir: -1, show: 'all', filter: '', sel: null };
  function inScope(d) { return !state.district || d === state.district; }
  // Plants with a visit in the dates chosen, in the district chosen.
  function scoped() { return ALL.filter(function (p) { return inScope(p.dn) && p.v.length; }); }
  function where() { return state.district ? ' in ' + state.district : ''; }

  /* Per-plant fields that depend on the visits in the date range */
  function derive() {
    ALL.forEach(function (p) {
      p.v = p.av.filter(function (v) { var k = isoToKey(v.dt); return k >= state.from && k <= state.to; });
      p.V = p.v.length ? p.v[p.v.length - 1] : null;
    });
    // SMU's first visit after the report and the response, among the visits in the dates chosen.
    RC.forEach(function (c) {
      c.nxV = c.p.v.filter(function (v) { return v.dt > c.after; })[0] || null;
      c.res = c.nxV ? c.g.map(function (g) { var x = present(c.nxV, g); return [g, x === null ? null : x ? 1 : 0]; }) : [];
    });
  }

  function has(v, key) { return v.keys.indexOf(KK[key]) >= 0; }
  /* is a problem group present at a visit? null = could not be checked */
  function present(v, g) {
    if (!v) return null;
    if (g === 'notfound') return v.st === 'X';
    if (v.st === 'X') return null;
    if (g === 'closed') return v.st === 'I' || has(v, 'closed');
    if (v.st === 'I') return null;
    if (g === 'notwork') return v.st === 'N';
    return v.keys.some(function (i) { return KEYS[i].g === g; });
  }

  /* summary of a selection */
  function summarise(list) {
    var o = { plants: list.length, listed: 0, unlisted: 0, visits: 0, first: null, last: null, st: { F: 0, N: 0, X: 0, I: 0 }, find: {}, base: 0, other: {}, tt: 0, tw: 0, tapPlants: 0 };
    GORD.forEach(function (g) { o.find[g] = 0; });
    OTHER.forEach(function (k) { o.other[k.i] = 0; });
    var ds = DN.filter(inScope);
    o.total = ds.reduce(function (a, d) { return a + TOT[d]; }, 0);
    o.nd = ds.length;
    list.forEach(function (p) {
      if (p.ml) o.listed++; else o.unlisted++;
      o.visits += p.v.length;
      if (!o.first || p.v[0].dt < o.first) o.first = p.v[0].dt;
      if (!o.last || p.V.dt > o.last) o.last = p.V.dt;
      var v = p.V;
      o.st[v.st]++;
      if (v.st === 'F' || v.st === 'N') {
        o.base++;
        GORD.forEach(function (g) { if (present(v, g)) o.find[g]++; });
        OTHER.forEach(function (k) { if (v.keys.indexOf(k.i) >= 0) o.other[k.i]++; });
      }
      if (v.st === 'F' && v.tt !== null) { o.tt += v.tt; o.tw += v.tw; o.tapPlants++; }
    });
    /* reports: every date counts */
    var reps = REP.filter(function (r) { return inScope(r.p.dn); });
    o.reps = reps; o.items = 0; o.rt = {}; RT.forEach(function (t) { o.rt[t.id] = 0; });
    o.top = {}; GORD.forEach(function (g) { o.top[g] = { n: 0, t: {} }; RT.forEach(function (t) { o.top[g].t[t.id] = 0; }); });
    o.fixedG = {}; GORD.forEach(function (g) { o.fixedG[g] = 0; });
    o.repPlants = {}; o.answered = 0; o.gaps = []; o.noResp = [];
    o.ag = {}; AG.forEach(function (a) { o.ag[a.id] = {}; }); o.agN = {};
    reps.forEach(function (r) {
      o.repPlants[r.k] = 1;
      if (r.x !== null || r.na) o.answered++; else o.noResp.push(r.w[1]);
      if (r.rd && !r.rdx) o.gaps.push(days(r.w[1], r.rd));
      if (r.ag) { o.ag[r.ag][r.k] = 1; o.agN[r.k] = 1; }
      r.it.forEach(function (it) { o.items++; o.rt[it[1]]++; o.top[it[0]].n++; o.top[it[0]].t[it[1]]++; if (it[1] === 'fixed') o.fixedG[it[0]]++; });
    });
    o.nRepPlants = Object.keys(o.repPlants).length;
    /* rechecks: the next visit in the dates chosen */
    o.rc = RC.filter(function (c) { return inScope(c.p.dn) && c.nxV; });
    o.rcI = {}; GORD.forEach(function (g) { o.rcI[g] = { g: 0, s: 0 }; });
    o.rcGone = 0; o.rcChk = 0; o.rcPlants = {};
    o.rc.forEach(function (c) {
      o.rcPlants[c.k] = 1;
      c.res.forEach(function (x) { if (x[1] === null) return; o.rcChk++; if (x[1]) o.rcI[x[0]].s++; else { o.rcI[x[0]].g++; o.rcGone++; } });
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
    setCard('c-total', o.total, 'In PSPA\'s list' + (state.district ? ' for ' + state.district : ', in ' + n(o.nd) + ' districts'));
    setCard('c-visited', o.listed, o.plants ? pct(o.listed, o.total) + '% of total plants, visited from ' + range(o.first, o.last) + (o.unlisted ? '. Also ' + pl(o.unlisted, 'reported plant', 'reported plants') + ' not in the list' : '') : 'None yet');
    var rf = o.reps.map(function (r) { return r.w[0]; }).sort(), rl = o.reps.map(function (r) { return r.w[1]; }).sort();
    setCard('c-reports', o.nRepPlants, o.reps.length ? 'Plants, with ' + pl(o.items, 'problem', 'problems') + ' in SMU reports from ' + range(rf[0], rl[rl.length - 1]) : 'None in this selection');
    var md = median(o.gaps.filter(function (x) { return x >= 0; }));
    setCard('c-replied', o.answered, o.reps.length ? 'Of ' + pl(o.reps.length, 'plant report', 'plant reports') + (md !== null ? ', usually ' + pl(md, 'day', 'days') + ' after the report' : '') : '');
  }

  /* impact cards */
  var FIXW = { notwork: 'plants made working again', unclean: 'cleaning done', taps: 'taps repaired', filter: 'filters replaced', repair: 'repairs done', closed: 'plants opened' };
  function renderImpact(o) {
    var fx = Object.keys(FIXW).filter(function (g) { return o.fixedG[g]; }).sort(function (a, b) { return o.fixedG[b] - o.fixedG[a]; }).slice(0, 3).map(function (g) { return FIXW[g]; });
    $('imp-1').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (o.items ? n(o.rt.fixed) + ' of ' + n(o.items) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (o.items ? 'problems SMU reported were fixed, says the department' : 'No SMU report here yet') + '</b><small>' + (fx.length ? 'Mostly ' + andList(fx) + '.' : '') + '</small></div>';
    var good = GORD.map(function (g) { var a = o.rcI[g]; return { g: g, gone: a.g, t: a.g + a.s }; }).filter(function (x) { return x.t >= 10; });
    var best = good.slice().sort(function (a, b) { return b.gone / b.t - a.gone / a.t || b.t - a.t; })[0];
    var worst = good.slice().sort(function (a, b) { return (b.t - b.gone) - (a.t - a.gone); })[0];
    $('imp-2').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (o.rcChk ? n(o.rcGone) + ' of ' + n(o.rcChk) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (o.rcChk ? 'reported problems were gone when SMU went back to the plant' : 'No reported plant visited again here yet') + '</b><small>' +
      (o.rcChk ? (best && best.gone / best.t >= 0.4 ? 'Put right most often: ' + GL[best.g].short + '. ' : '') + (worst && worst.gone / worst.t < 0.5 ? 'Still there most often: ' + GL[worst.g].short + '.' : '') : '') + '</small></div>';
  }

  /* issues */
  function irow(label, c, max, of, filter) {
    return '<button type="button" class="irow"' + (filter ? ' data-filter="' + filter + '"' : '') + '><span>' + esc(label) + '</span><span class="bar"><i style="width:' + (max ? c / max * 100 : 0) + '%"></i></span><span class="num"><b>' + n(c) + '</b></span><span class="num pct">' + pct(c, of) + '%</span></button>';
  }
  function irowHead(a, b, c) { return '<div class="irow irow-h"><span>' + a + '</span><span></span><span class="num">' + b + '</span><span class="num">' + (c || 'Share') + '</span></div>'; }
  function renderIssues(o) {
    var vis = o.st.F + o.st.N + o.st.X + o.st.I;
    $('st-bar').innerHTML = SORD.map(function (s) { return o.st[s] ? '<i class="seg-' + s + '" style="width:' + o.st[s] / vis * 100 + '%" data-tip="<b>' + esc(STL[s]) + '</b>' + pl(o.st[s], 'plant', 'plants') + ', ' + pct(o.st[s], vis) + '%"></i>' : ''; }).join('');
    $('st-bar').setAttribute('aria-label', SORD.map(function (s) { return STL[s] + ' ' + o.st[s]; }).join(', '));
    $('st-outs').innerHTML = SORD.map(function (s) {
      return '<button type="button" class="out" data-filter="s:' + s + '"><div class="out-num">' + n(o.st[s]) + ' <span>' + pct(o.st[s], vis) + '%</span></div><div class="out-lbl"><span class="sw st-' + s + '"></span>' + esc(STL[s]) + '</div><div class="out-sub">' + esc(STS[s]) + '</div></button>';
    }).join('');
    $('st-taps').innerHTML = o.tt ? 'At the working plants, <b>' + n(o.tw) + ' of ' + n(o.tt) + ' water taps</b> (' + pct(o.tw, o.tt) + '%) were giving water.' : '';
    $('tiles').innerHTML = TILES.map(function (g) {
      var c = o.find[g];
      return '<button type="button" class="tile" data-filter="f:' + g + '"><b>' + n(c) + '</b><span>' + esc(GL[g].label) + '</span><small>' + esc(GL[g].why) + ' &middot; ' + pct(c, o.base) + '% of ' + n(o.base) + ' plants found</small></button>';
    }).join('');
    var rows = OTHER.map(function (k) { return { k: k, c: o.other[k.i] }; }).filter(function (x) { return x.c; }).sort(function (a, b) { return b.c - a.c; });
    var mx = rows.length ? rows[0].c : 1;
    $('other-box').hidden = !rows.length;
    $('other-sum').textContent = 'Other problems the field teams found (' + rows.length + ')';
    $('other-rows').innerHTML = irowHead('Problem', 'Plants', 'Of found') + rows.map(function (x) { return irow(x.k.label, x.c, mx, o.base, 'k:' + x.k.i); }).join('');
  }

  /* department response */
  function stack(lbl, a, tt) {
    return '<div class="stack" role="img" aria-label="' + RT.map(function (t) { return t.label + ' ' + a[t.id]; }).join(', ') + '" data-tip="<b>' + esc(lbl) + '</b>' + RT.filter(function (t) { return a[t.id]; }).map(function (t) { return esc(t.label) + ': ' + n(a[t.id]); }).join('<br>') + '">' +
      RT.map(function (t) { return a[t.id] ? '<i class="' + t.sw + '" style="width:' + a[t.id] / tt * 100 + '%"></i>' : ''; }).join('') + '</div>';
  }
  function renderChanged(o) {
    var rows = GORD.map(function (g) { return { g: g, a: o.top[g] }; }).filter(function (x) { return x.a.n; }).sort(function (a, b) { return b.a.n - a.a.n; });
    $('iss-t').innerHTML = rows.length ? '<thead><tr><th>Problem reported</th><th class="r">Problems</th><th>Department\'s response</th><th class="r">Fixed, says the department</th></tr></thead><tbody>' + rows.map(function (x) {
      var a = x.a, tt = a.n || 1;
      return '<tr data-filter="c:' + x.g + '"><td class="lab">' + esc(GL[x.g].label) + '</td><td class="r">' + n(a.n) + '</td><td>' + stack(GL[x.g].label, a.t, tt) + '</td><td class="r' + (a.t.fixed / tt < 0.25 ? ' dn' : '') + '">' + n(a.t.fixed) + ' <span class="pct">' + pct(a.t.fixed, tt) + '%</span></td></tr>';
    }).join('') + '<tr class="tot"><td class="lab">All problems, at ' + pl(o.nRepPlants, 'plant', 'plants') + '</td><td class="r">' + n(o.items) + '</td><td>' + stack('All problems', o.rt, o.items || 1) + '</td><td class="r">' + n(o.rt.fixed) + ' <span class="pct">' + pct(o.rt.fixed, o.items) + '%</span></td></tr></tbody>'
      : '<tbody><tr><td class="empty" style="padding:12px 18px">No SMU report in this selection.</td></tr></tbody>';
    $('iss-key').innerHTML = RT.map(function (t) { return '<span data-tip="' + esc(t.sub) + '"><i class="sw ' + t.sw + '"></i>' + esc(t.label) + '</span>'; }).join('');
    /* not PSPA's */
    var nA = Object.keys(o.agN).length, nIn = Object.keys(o.agN).filter(function (k) { return OI[k] && OI[k].ml; }).length, ag = AG.map(function (a) { return { a: a, c: Object.keys(o.ag[a.id]).length }; }).filter(function (x) { return x.c; }).sort(function (a, b) { return b.c - a.c; });
    $('ag-sub').textContent = nA ? 'For ' + pl(nA, 'reported plant', 'reported plants') + ', the department\'s response says the plant is run by someone else, so the problems were not taken up' + (nIn ? ', although ' + (nIn === nA ? (nA === 1 ? 'it is' : 'all are') : n(nIn) + ' of them are') + ' in PSPA\'s own list of plants' : '') + '. The response names:' : 'The department did not say this about any reported plant here.';
    var mx = ag.length ? ag[0].c : 1;
    $('ag-rows').innerHTML = ag.length ? irowHead('Who runs the plant, says the department', 'Plants', 'Of these') + ag.map(function (x) { return irow(x.a.label, x.c, mx, nA, 'a:' + x.a.id); }).join('') : '';
    $('ag-rows').parentNode.hidden = !nA;
    $('caveat').innerHTML = '<b>These are the department\'s own statements.</b> ' + (o.noResp.length ? pl(o.noResp.length, 'plant report', 'plant reports') + ' (SMU reports of ' + months(o.noResp) + ') ' + (o.noResp.length === 1 ? 'has' : 'have') + ' no response in the file. ' : '') +
      (META.respLast ? 'The latest response on file is dated ' + dl(META.respLast) + '. ' : '') + 'Each plant\'s full response is in its report on the map.';
  }

  /* back at the same plants */
  function renderRevisit(list, o) {
    var rv = list.filter(function (p) { return p.v.length > 1; });
    var gaps = o.rc.map(function (c) { return days(c.r.w[1], c.nxV.dt); });
    $('rev-sub').textContent = rv.length ? 'SMU went back to ' + pl(rv.length, 'plant', 'plants') + where() + '. ' + (o.rc.length ? n(Object.keys(o.rcPlants).length) + ' of them had been reported to the department before the later visit.' : '') : 'No plant' + where() + ' has been visited twice yet.';
    var mx = 1, rows = GORD.map(function (g) { var a = o.rcI[g]; mx = Math.max(mx, a.g + a.s); return { g: g, gone: a.g, s: a.s }; }).filter(function (x) { return x.gone + x.s; })
      .sort(function (a, b) { return (b.gone + b.s) - (a.gone + a.s) || b.gone - a.gone; });
    $('rc-sub').textContent = o.rc.length ? 'The next visit came ' + (median(gaps) >= 60 ? 'usually about ' + Math.round(median(gaps) / 30) + ' months' : 'usually ' + pl(median(gaps), 'day', 'days')) + ' after the SMU report. Click a row to see those plants on the map.' : 'No reported plant visited again in this selection.';
    $('rc-rows').innerHTML = rows.length ? '<div class="rck rck-h"><span>Problem reported</span><span></span><span class="num">Gone / checked again</span></div>' + rows.map(function (x) {
      var t = x.gone + x.s;
      return '<button type="button" class="rck" data-filter="g:' + x.g + '"><span>' + esc(GL[x.g].label) + '</span><span class="gs" style="width:' + Math.max(18, t / mx * 100) + '%" data-tip="<b>' + esc(GL[x.g].label) + '</b>' + n(x.gone) + ' gone, ' + n(x.s) + ' still there"><i class="g" style="width:' + x.gone / t * 100 + '%"></i><i class="s" style="width:' + x.s / t * 100 + '%"></i></span><span class="num"><b>' + n(x.gone) + '</b> of ' + n(t) + ' &middot; ' + pct(x.gone, t) + '%</span></button>';
    }).join('') : '<p class="empty">No reported plant visited again in this selection.</p>';
    /* first and latest visit */
    var vg = rv.map(function (p) { return days(p.v[0].dt, p.V.dt); });
    $('cmp-sub').textContent = rv.length ? pl(rv.length, 'plant', 'plants') + '. The first and latest visits were usually ' + (median(vg) >= 60 ? 'about ' + Math.round(median(vg) / 30) + ' months' : pl(median(vg), 'day', 'days')) + ' apart. Each check counts the plants where it could be seen at both visits.' : '';
    $('cmp-t').innerHTML = rv.length ? '<thead><tr><th>Check</th><th class="r">First visit</th><th class="r">Latest visit</th><th>Change at each plant</th></tr></thead><tbody>' + CMP.map(function (c) {
      var a = { n: 0, f: 0, l: 0, up: 0, same: 0, dn: 0 };
      rv.forEach(function (p) {
        var x = present(p.v[0], c.g), y = present(p.V, c.g); if (x === null || y === null) return;
        a.n++; if (!x) a.f++; if (!y) a.l++;
        if (x && !y) a.up++; else if (!x && y) a.dn++; else a.same++;
      });
      if (!a.n) return '';
      var cls = a.l > a.f ? 'up' : a.l < a.f ? 'dn' : '';
      return '<tr data-filter="v:' + c.g + '"><td class="lab">' + esc(c.label) + '</td><td class="r">' + n(a.f) + ' of ' + n(a.n) + '</td><td class="r ' + cls + '">' + n(a.l) + ' of ' + n(a.n) + '</td>' +
        '<td><div class="chg"><div class="stack" role="img" aria-label="' + a.up + ' better, ' + a.same + ' same, ' + a.dn + ' worse" data-tip="<b>' + esc(c.label) + '</b>' + n(a.up) + ' better, ' + n(a.same) + ' no change, ' + n(a.dn) + ' worse"><i class="s-more" style="width:' + a.up / a.n * 100 + '%"></i><i class="s-same" style="width:' + a.same / a.n * 100 + '%"></i><i class="s-less" style="width:' + a.dn / a.n * 100 + '%"></i></div><span>' + n(a.up) + ' better, ' + n(a.dn) + ' worse</span></div></td></tr>';
    }).join('') + '</tbody>' : '<tbody><tr><td class="empty" style="padding:12px 18px">No plant visited twice in this selection.</td></tr></tbody>';
    /* reported again and again */
    var ag = list.filter(function (p) { return p.r.length >= 3; }).sort(function (a, b) { return b.r.length - a.r.length || a.n.localeCompare(b.n); });
    var two = list.filter(function (p) { return p.r.length === 2; }).length;
    $('again-card').hidden = !ag.length && !two;
    $('again-sub').textContent = (ag.length + two) ? pl(ag.length + two, 'plant was', 'plants were') + ' reported more than once, because SMU found problems again at a later visit' + (ag.length ? '. The ones below were reported three times or more. Click one to open its report.' : '.') : '';
    $('again').innerHTML = ag.map(function (p) {
      var w = p.r.map(function (i) { return REP[i].w[1]; }).sort();
      return '<button type="button" data-open="' + esc(p.k) + '"><span>' + esc(plabel(p)) + '<small>' + esc(p.dn) + (p.t ? ' &middot; ' + esc(p.t) : '') + ' &middot; ' + range(w[0], w[w.length - 1]) + '</small></span><span class="times">' + p.r.length + '<span>times</span></span></button>';
    }).join('');
  }

  /* districts */
  function districtRows() {
    var by = {};
    ALL.forEach(function (p) { if (p.v.length) (by[p.dn] = by[p.dn] || []).push(p); });
    return DN.map(function (d) { return summarise1(by[d] || [], d); });
  }
  function summarise1(L, d) {
    var c = { visited: 0, F: 0, N: 0, base: 0, absent: 0, unclean: 0 };
    L.forEach(function (p) { if (p.ml) c.visited++; var v = p.V; if (v.st === 'F') c.F++; if (v.st === 'N') c.N++; if (v.st === 'F' || v.st === 'N') { c.base++; if (present(v, 'absent')) c.absent++; if (present(v, 'unclean')) c.unclean++; } });
    var reps = REP.filter(function (r) { return r.p.dn === d; }), items = 0, fixed = 0, rp = {};
    reps.forEach(function (r) { rp[r.k] = 1; r.it.forEach(function (it) { items++; if (it[1] === 'fixed') fixed++; }); });
    var g = 0, t = 0;
    RC.forEach(function (x) { if (x.p.dn !== d || !x.nxV) return; x.res.forEach(function (y) { if (y[1] === null) return; t++; if (!y[1]) g++; }); });
    var tot = TOT[d], vis = L.length;
    return { d: d, total: tot, visited: c.visited, all: vis, cov: tot ? c.visited / tot : 0, work: vis ? c.F / vis : -1, notw: vis ? c.N / vis : -1, absent: c.base ? c.absent / c.base : -1, unclean: c.base ? c.unclean / c.base : -1,
      rep: Object.keys(rp).length, fixed: items ? fixed / items : -1, fixedN: fixed, items: items, gone: t ? g / t : -1, goneN: g, chk: t };
  }
  var DCOLS = [
    { k: 'd', t: 'District' }, { k: 'total', t: 'Total plants', r: 1 }, { k: 'visited', t: 'Visited', r: 1 }, { k: 'cov', t: 'Share visited' },
    { k: 'work', t: 'Working', r: 1 }, { k: 'notw', t: 'Not working', r: 1 }, { k: 'absent', t: 'Operator away', r: 1 }, { k: 'unclean', t: 'Not clean', r: 1 },
    { k: 'rep', t: 'Plants reported', r: 1 }, { k: 'fixed', t: 'Fixed, says the department', r: 1 }, { k: 'gone', t: 'Gone when checked again', r: 1 }
  ];
  function renderDistricts() {
    var D = districtRows(), k = state.sort, dir = state.dir;
    var rows = D.slice().sort(function (a, b) { var x = a[k], y = b[k]; return (typeof x === 'string' ? x.localeCompare(y) : x - y) * dir || b.total - a.total || a.d.localeCompare(b.d); });
    function rate(r, key, cut, low, tip) { var x = r[key]; return x < 0 ? '<td class="r">&ndash;</td>' : '<td class="r' + (low ? (x <= cut ? ' dn' : '') : (x >= cut ? ' dn' : '')) + '"' + (tip ? ' data-tip="' + tip + '"' : '') + '>' + Math.round(x * 100) + '%</td>'; }
    $('dt').innerHTML = '<thead><tr>' + DCOLS.map(function (c) {
      var on = c.k === k;
      return '<th scope="col" class="' + (c.r ? 'r' : '') + '" aria-sort="' + (on ? (dir > 0 ? 'ascending' : 'descending') : 'none') + '"><button type="button" data-sort="' + c.k + '">' + c.t + (on ? (dir > 0 ? ' ↑' : ' ↓') : '') + '</button></th>';
    }).join('') + '</tr></thead><tbody>' + rows.map(function (r) {
      return '<tr data-district="' + esc(r.d) + '"' + (r.d === state.district ? ' class="on"' : '') + '><td><button type="button" class="dname" data-district="' + esc(r.d) + '">' + esc(r.d) + '</button></td><td class="r">' + n(r.total) + '</td><td class="r">' + n(r.visited) + '</td>' +
        '<td><div class="cov"><div class="bar"><i style="width:' + Math.min(1, r.cov) * 100 + '%"></i></div><span>' + pct(r.visited, r.total) + '%</span></div></td>' +
        rate(r, 'work', 0.5, true) + rate(r, 'notw', 0.4) + rate(r, 'absent', 0.6) + rate(r, 'unclean', 0.6) + '<td class="r">' + (r.rep ? n(r.rep) : '&ndash;') + '</td>' +
        rate(r, 'fixed', 0.2, true, r.items ? n(r.fixedN) + ' of ' + n(r.items) + ' problems' : '') + rate(r, 'gone', 0.25, true, r.chk ? n(r.goneN) + ' of ' + n(r.chk) + ' problems' : '') + '</tr>';
    }).join('') + '</tbody>';
    var none = D.filter(function (r) { return !r.all; }).map(function (r) { return r.d; });
    $('dt-sub').textContent = none.length ? andList(none) + ' ' + (none.length === 1 ? 'has' : 'have') + ' no SMU visit' + (fullRange() ? ' yet' : ' in these dates') + '. ' : '';
  }

  /* map */
  var map = L.map('map', { preferCanvas: true, minZoom: 5, maxZoom: 18, zoomSnap: 0.5 });
  map.attributionControl.setPrefix(false); // No "Leaflet" or flag in the corner, only the basemap's credit.
  var tileBase = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_';
  L.tileLayer(tileBase + 'Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', attribution: 'Basemap &copy; Esri, HERE, Garmin, OpenStreetMap contributors' }).addTo(map);
  L.tileLayer(tileBase + 'Reference/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', pane: 'overlayPane' }).addTo(map);
  var css = getComputedStyle($('psrpFindings'));
  function cv(name) { return css.getPropertyValue(name).trim(); }
  var C = { F: cv('--st-s'), N: cv('--st-c'), X: cv('--st-x'), I: cv('--st-p'), U: cv('--st-u'), surf: cv('--surface'), ink: cv('--ink') };
  var renderer = L.canvas({ padding: 0.3, tolerance: 5 });
  var pins = L.layerGroup().addTo(map);
  var ring = null, focusPin = null, drawn = {};
  var BOUNDS = [[27.9, 69.3], [33.9, 75.2]];
  function radius() { var z = map.getZoom() || 7; return z < 7 ? 3 : z < 8 ? 3.5 : z < 10 ? 4.5 : z < 12 ? 6 : 7.5; }
  function makeMarker(p) {
    var r = radius(), m;
    if (p.un) {
      m = L.circleMarker([p.la, p.lo], { radius: Math.max(2.5, r - 1), renderer: renderer, stroke: true, color: C.U, weight: 1.2, fillColor: C.surf, fillOpacity: 1 });
      m.bindTooltip(function () { return esc(p.n) + ', ' + esc(p.dn) + ' (' + esc(p.k) + ', not visited yet)'; }, { direction: 'top', offset: [0, -4] });
    } else {
      var st = p.V.st, rep = p.r.length > 0;
      m = L.circleMarker([p.la, p.lo], { radius: rep ? r + 1 : r, renderer: renderer, stroke: true, color: rep ? C.ink : C.surf, weight: rep ? 2 : 0.8, fillColor: C[st], fillOpacity: 1 });
      m.bindTooltip(function () { return esc(p.n) + ', ' + esc(p.dn) + ' (' + STL[st].toLowerCase() + ', ' + dl(p.V.dt) + ')' + (rep ? ' · reported to the department' : ''); }, { direction: 'top', offset: [0, -4] });
    }
    m.on('click', function () { openPlant(p, false); });
    return m;
  }
  function itemsOf(p) { var out = []; p.r.forEach(function (i) { REP[i].it.forEach(function (it) { out.push(it); }); }); return out; }
  function matchesFilter(p) {
    var f = state.filter; if (!f) return true;
    var k = f.slice(0, 2), id = f.slice(2);
    if (p.un) return false;
    if (k === 's:') return p.V.st === id;
    if (k === 'f:') return (p.V.st === 'F' || p.V.st === 'N') && present(p.V, id);
    if (k === 'k:') return (p.V.st === 'F' || p.V.st === 'N') && p.V.keys.indexOf(Number(id)) >= 0;
    if (k === 't:') return itemsOf(p).some(function (it) { return it[1] === id; });
    if (k === 'c:') return itemsOf(p).some(function (it) { return it[0] === id; });
    if (k === 'a:') return p.r.some(function (i) { return REP[i].ag === id; });
    if (k === 'g:') return RC.some(function (c) { return c.p === p && c.nxV && c.res.some(function (x) { return x[0] === id && x[1] !== null; }); });
    if (k === 'v:') return p.v.length > 1 && present(p.v[0], id) !== null && present(p.V, id) !== null;
    if (f === 'rv') return p.v.length > 1;
    if (f === 'r2') return p.r.length > 1;
    return true;
  }
  function filterLabel(f) {
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 's:') return STL[id];
    if (k === 'f:') return GL[id].label;
    if (k === 'k:') return KEYS[Number(id)].l;
    if (k === 't:') return 'Response: ' + RTI[id].label;
    if (k === 'c:') return 'Reported: ' + GL[id].label;
    if (k === 'a:') return 'Not PSPA\'s: ' + AG.filter(function (a) { return a.id === id; })[0].label;
    if (k === 'g:') return 'Checked again: ' + GL[id].label;
    if (k === 'v:') return 'Visited more than once: ' + CMP.filter(function (c) { return c.g === id; })[0].label.toLowerCase();
    if (f === 'rv') return 'Visited more than once';
    if (f === 'r2') return 'Reported more than once';
    return '';
  }
  function matchesMap(p) {
    if (state.show === 'u') return !!p.un;
    if (p.un) return false;
    if (state.show === 'r' && !p.r.length) return false;
    if (SORD.indexOf(state.show) >= 0 && p.V.st !== state.show) return false;
    return matchesFilter(p);
  }
  // The latest visit in the dates chosen that is a row of our visit file, for the map's Download.
  function fileVisit(p) { for (var i = p.v.length - 1; i >= 0; i--) if (p.v[i].id) return p.v[i]; return null; }
  function renderMap(fit) {
    pins.clearLayers();
    drawn = {};
    var src = state.show === 'u' ? UNV.filter(function (p) { return inScope(p.dn); }) : scoped();
    var list = src.filter(matchesMap), cnt = { F: 0, N: 0, X: 0, I: 0 }, rep = 0, un = 0, unmapped = 0, fromList = 0, pts = [], visitIds = [];
    list.sort(function (a, b) { return (a.r.length ? 1 : 0) - (b.r.length ? 1 : 0); });
    list.forEach(function (p) {
      if (!p.un) { var fv = fileVisit(p); if (fv) visitIds.push(fv.id); }
      if (p.la === null || p.la === undefined) { unmapped++; return; }
      if (p.un) un++; else { cnt[p.V.st]++; if (p.r.length) rep++; if (p.pl) fromList++; }
      pins.addLayer(makeMarker(p)); drawn[p.k] = 1; pts.push([p.la, p.lo]);
    });
    // As on the other pages: only a chosen finding or response can be downloaded, and the file holds the
    // plants it picks, one row each, from our visit file.
    $('map-download').hidden = !(state.filter && state.show !== 'u' && visitIds.length);
    if (state.filter) {
      $('dl-ids').value = visitIds.join(',');
      $('dl-issue').value = filterLabel(state.filter);
      $('dl-label').textContent = 'Download ' + pl(visitIds.length, 'plant', 'plants');
    }
    fitSearchHint(); // The button takes room from the search box, or gives it back.
    var lg = SORD.filter(function (k) { return cnt[k]; }).map(function (k) { return '<div class="lg"><span class="pin s' + k + '"></span>' + esc(STL[k]) + ' <span class="lg-n">' + n(cnt[k]) + '</span></div>'; }).join('');
    $('legend').innerHTML = lg + (rep ? '<div class="lg"><span class="pin rr"></span>Dark ring: reported to the department <span class="lg-n">' + n(rep) + '</span></div>' : '') +
      (un ? '<div class="lg"><span class="pin su"></span>In PSPA\'s list, not visited yet <span class="lg-n">' + n(un) + '</span></div>' : '') +
      (state.filter && state.show !== 'u' ? '<div class="lg"><span class="lg-n">With "' + esc(filterLabel(state.filter)) + '"</span></div>' : '') + (pts.length ? '' : '<div class="lg">No plant to show</div>');
    // Pins are where the field team recorded each plant at its latest visit; PSPA's list only fills in.
    $('map-note').textContent = (unmapped ? pl(unmapped, 'plant', 'plants') + ' in this view ' + (unmapped === 1 ? 'has' : 'have') + ' no usable map location, in the visit file or in PSPA\'s list, so ' + (unmapped === 1 ? 'it is' : 'they are') + ' not on the map. Search finds ' + (unmapped === 1 ? 'it' : 'them') + '. ' : '') +
      (state.show === 'u' ? 'Plants not visited yet are placed at their location in PSPA\'s list.' : 'Pins are placed where the field team recorded the plant at its latest visit' +
        (fromList ? '; ' + pl(fromList, 'plant', 'plants') + ' with no usable location in the visit file ' + (fromList === 1 ? 'is' : 'are') + ' placed at the location in PSPA\'s list.' : '.'));
    if (fit) {
      var zone = pts.length ? pts : src.filter(function (p) { return p.la !== null; }).map(function (p) { return [p.la, p.lo]; });
      if (zone.length && (state.district || state.filter)) map.fitBounds(L.latLngBounds(zone).pad(0.08), { maxZoom: 12 });
      else map.fitBounds(BOUNDS);
    }
    if (state.sel) placeRing(OI[state.sel] || null);
  }
  map.on('zoomend', function () { if (loaded) renderMap(false); });
  // The plant opened always shows inside its ring, even when the map's choices hide its pin.
  function placeRing(p) {
    if (ring) { map.removeLayer(ring); ring = null; }
    if (focusPin) { map.removeLayer(focusPin); focusPin = null; }
    if (p && p.la !== null && p.la !== undefined && (p.un || p.V)) {
      if (!drawn[p.k]) focusPin = makeMarker(p).addTo(map);
      ring = L.circleMarker([p.la, p.lo], { radius: 13, color: C.ink, weight: 2.5, fill: false, interactive: false, renderer: renderer }).addTo(map);
    }
  }
  function syncSeg() { document.querySelectorAll('#mapsec .seg button').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.show === state.show)); }); }

  /* plant panel */
  function renderHint() {
    $('panel').innerHTML = '<div class="panel-hint"><h3>Plant report</h3><p>Click a pin on the map, or search above, to see the plant\'s visits here.</p><ul>' +
      '<li><span class="pin sF" style="display:inline-block;vertical-align:-1px"></span> <b>Green:</b> working at the latest visit.</li>' +
      '<li><span class="pin sN" style="display:inline-block;vertical-align:-1px"></span> <b>Red:</b> not working.</li>' +
      '<li><span class="pin sX" style="display:inline-block;vertical-align:-1px"></span> <b>Purple:</b> not found at the location in PSPA\'s list.</li>' +
      '<li><span class="pin sI" style="display:inline-block;vertical-align:-1px"></span> <b>Amber:</b> locked, so it could not be checked.</li>' +
      '<li><b>Dark ring:</b> named in an SMU report to the department. You will also see the department\'s response.</li></ul></div>';
  }
  function visitBlock(v) {
    var f = v.keys.filter(function (i) { return KEYS[i].k !== 'notfound' && KEYS[i].k !== 'closed'; });
    var h = '<div class="badges"><span class="badge b-st-' + v.st + '">● ' + esc(STL[v.st]) + '</span>' + (v.tt !== null && v.tt !== undefined && v.st !== 'X' ? '<span class="badge b-mute">' + n(v.tw) + ' of ' + pl(v.tt, 'tap', 'taps') + ' working</span>' : '') + '</div>';
    h += '<div class="chips">' + (f.length ? f.map(function (i) { return '<span class="chip">' + esc(KEYS[i].l) + '</span>'; }).join('') : (v.st === 'F' ? '<span class="chip ok">No problem found</span>' : '')) + '</div>';
    if (v.src === 'R') h += '<p class="note">This visit is known from the SMU report; it is not in the monitoring data, so only the problems in the report are shown.</p>';
    if (v.ph && v.ph.length) h += '<div class="photos" aria-label="Photos from the visit">' + v.ph.map(photo).join('') + '</div>';
    return h;
  }
  // A photo from the visit file's Images column, shown small; clicking it opens the photo itself. A Google
  // Drive photo is shown through Drive's own small copy; if that can't be shown (the file isn't shared,
  // or it isn't a picture), the link stays as "Photo 1", "Photo 2" and so on.
  function photo(ref, i) {
    var drive = !/^https?:/i.test(ref);
    var link = drive ? 'https://drive.google.com/open?id=' + encodeURIComponent(ref) : ref;
    var label = 'Photo ' + (i + 1);
    var small = drive ? '<img src="https://drive.google.com/thumbnail?id=' + encodeURIComponent(ref) + '&amp;sz=w400" alt="' + label + ' from the visit" loading="lazy" referrerpolicy="no-referrer">' : '';
    return '<a class="' + (small ? 'thumb' : 'nothumb') + '" href="' + esc(link) + '" target="_blank" rel="noopener noreferrer" title="Open ' + label.toLowerCase() + ' in a new tab">' + small + '<span>' + label + '</span></a>';
  }
  function openPlant(p, focus, quiet) {
    if (!p.un && !p.V) return;
    state.sel = p.k; placeRing(p);
    if (focus && p.la !== null && p.la !== undefined) map.setView([p.la, p.lo], Math.max(map.getZoom(), 12));
    var h = '<div class="p-top"><div><div class="p-name">' + esc(p.n) + '</div><div class="p-meta">' + (p.ml ? 'Code ' + esc(p.k) + ' &middot; ' : 'Not in PSPA\'s list &middot; ') + esc(p.dn) + (p.t ? ' &middot; ' + esc(p.t) : '') + '</div></div><button type="button" class="x" id="p-close" aria-label="Close report">×</button></div>';
    if (p.un) {
      h += '<div class="badges"><span class="badge b-mute">Not visited by SMU yet</span></div><p class="note">This plant is in PSPA\'s list. SMU has no visit to it on file.</p>';
    } else {
      var v = p.V;
      h += '<div class="badges"><span class="badge b-mute">' + (p.v.length > 1 ? pl(p.v.length, 'visit', 'visits') + ', ' + dl(p.v[0].dt) + ' to ' + dl(v.dt) : 'Visited ' + vdate(v)) + '</span>' + (p.r.length ? '<span class="badge b-info">In ' + pl(p.r.length, 'SMU report', 'SMU reports') + '</span>' : '') + '</div>';
      if (p.la === null || p.la === undefined) h += '<p class="note">This plant has no map location, so it has no pin.</p>';
      if (p.r.length) {
        h += '<div class="p-sec"><h3>Reported to the department</h3>' + p.r.slice().reverse().map(function (i) {
          var r = REP[i], c = RC.filter(function (x) { return x.rid === i; })[0];
          var b = '<div class="rep"><div class="rep-h">SMU report of ' + wl(r.w) + '</div>';
          b += '<ul class="p-iss">' + r.it.map(function (it) { var t = RTI[it[1]]; return '<li>' + esc(GL[it[0]].label) + ' <span class="tbadge badge ' + t.badge + '">' + esc(t.label) + '</span></li>'; }).join('') + '</ul>';
          if (r.x !== null) b += '<small class="rlab">Department\'s response' + (r.rd ? ', ' + dl(r.rd) + (r.rdx ? ' (dated before the report in the file)' : '') : '') + '</small><p class="rresp" title="Click to read all of it">' + esc(TX[r.x]) + '</p>';
          else if (r.na) b += '<small class="rlab">Department\'s response' + (r.rd ? ', ' + dl(r.rd) : '') + '</small><p class="note">The response says only "N/A" for this plant.</p>';
          else b += '<p class="note">No department response in the file yet.</p>';
          if (c && c.nxV) {
            var res = c.res.map(function (x) { return esc(GL[x[0]].label) + ' &ndash; ' + (x[1] === null ? 'could not be checked' : x[1] ? '<span class="bad">still there</span>' : '<span class="ok">gone</span>'); });
            b += '<p class="note"><b>Visited again ' + vdate(c.nxV) + ':</b> ' + res.join('; ') + '.</p>';
          } else b += '<p class="note">Not visited again since' + (fullRange() ? '' : ', in the dates chosen') + '.</p>';
          return b + '</div>';
        }).join('') + '</div>';
      }
      h += '<div class="p-sec"><h3 style="margin-top:4px">Latest visit, ' + vdate(v) + '</h3>' + visitBlock(v) + '</div>';
      p.v.slice(0, -1).reverse().forEach(function (e) { h += '<details class="visit"><summary>Earlier visit, ' + vdate(e) + ' &middot; ' + STL[e.st].toLowerCase() + '</summary>' + visitBlock(e) + '</details>'; });
    }
    var panel = $('panel');
    panel.innerHTML = h; panel.scrollTop = 0;
    $('p-close').addEventListener('click', function () { state.sel = null; placeRing(null); renderHint(); });
    if (!quiet && page.clientWidth <= 1100) panel.scrollIntoView({ block: 'start' });
  }

  /* page */
  function renderAll(fit) {
    var list = scoped(), o = summarise(list);
    $('f-clear').hidden = !state.district && fullRange();
    if (DK.min) { $('f-from').value = keyToISO(state.from); $('f-to').value = keyToISO(state.to); }
    nameForPdf();
    renderFlow(o); renderImpact(o); renderIssues(o); renderChanged(o); renderRevisit(list, o); renderDistricts(); renderMap(fit);
    if (state.sel) {
      var p = OI[state.sel];
      if (!p || !inScope(p.dn) || (!p.un && !p.V)) { state.sel = null; placeRing(null); renderHint(); } else openPlant(p, false, true);
    }
  }
  // What the page shows, for the name of the PDF made with Export to PDF ("Lahore, visits from ...").
  function nameForPdf() {
    var place = state.district ? state.district : 'all districts', dates = !DK.min || fullRange() ? '' : ', visits from ' + rangeText();
    $('pdfExport').setAttribute('data-pdf-what', place + dates);
  }
  // Tells the records table below to show the same district and dates.
  function filtersChanged() {
    document.dispatchEvent(new CustomEvent('psrp:filters', { detail: {
      district: state.district,
      from: !DK.min || state.from === DK.min ? '' : keyToISO(state.from),
      to: !DK.max || state.to === DK.max ? '' : keyToISO(state.to)
    } }));
  }
  function fillDistricts() {
    var names = DN.slice().sort();
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
    a = Math.max(a || DK.min, DK.min); b = Math.min(b || DK.max, DK.max);
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
  $('f-from').addEventListener('change', function () { if (loaded && DK.min && usableDate(this.value)) setRange(this.value ? isoToKey(this.value) : DK.min, state.to); });
  $('f-to').addEventListener('change', function () { if (loaded && DK.min && usableDate(this.value)) setRange(state.from, this.value ? isoToKey(this.value) : DK.max); });
  $('f-clear').addEventListener('click', function () {
    state.district = ''; state.from = DK.min; state.to = DK.max;
    fillDistricts();
    if (loaded) { derive(); renderAll(true); }
    filtersChanged();
  });
  $('f-find').addEventListener('change', function () { state.filter = this.value; if (state.filter) { state.show = 'all'; syncSeg(); } renderMap(true); });
  document.querySelector('#mapsec .seg').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b || !loaded) return;
    state.show = b.dataset.show; if (state.show === 'u') { state.filter = ''; $('f-find').value = ''; } syncSeg(); renderMap(state.show === 'u' || !!state.district);
  });
  $('psrpFindings').addEventListener('click', function (e) {
    if (!loaded) return;
    var t = e.target.closest('.rresp');
    if (t) { t.classList.toggle('open'); return; }
    t = e.target.closest('[data-open]');
    if (t) { var p = OI[t.dataset.open]; if (p) { openPlant(p, true); $('map').scrollIntoView({ block: 'center' }); } return; }
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
  // A photo Drive can't show small keeps its "Photo 1" link instead.
  $('panel').addEventListener('error', function (e) {
    var a = e.target.tagName === 'IMG' && e.target.closest('.thumb');
    if (a) { a.classList.replace('thumb', 'nothumb'); e.target.remove(); }
  }, true);
  /* Hover layer */
  var tip = $('tip');
  $('psrpFindings').addEventListener('mouseleave', function () { tip.hidden = true; });
  $('psrpFindings').addEventListener('mousemove', function (e) {
    var t = e.target.closest('[data-tip]');
    if (!t || !t.getAttribute('data-tip')) { tip.hidden = true; return; }
    tip.innerHTML = t.getAttribute('data-tip'); tip.hidden = false;
    var x = Math.min(e.clientX + 14, window.innerWidth - tip.offsetWidth - 8), y = e.clientY - tip.offsetHeight - 10;
    tip.style.left = Math.max(8, x) + 'px'; tip.style.top = (y < 4 ? e.clientY + 16 : y) + 'px';
  });
  $('q').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase(), box = $('results');
    if (q.length < 2 || !loaded) { box.hidden = true; return; }
    var hits = scoped().concat(UNV.filter(function (p) { return inScope(p.dn); })).filter(function (p) { return (p.n + ' ' + p.k + ' ' + p.dn + ' ' + p.t).toLowerCase().indexOf(q) >= 0; });
    hits.sort(function (a, b) { return (b.r.length ? 1 : 0) - (a.r.length ? 1 : 0) || (a.un ? 1 : 0) - (b.un ? 1 : 0) || a.dn.localeCompare(b.dn); });
    box.innerHTML = hits.slice(0, 12).map(function (p) {
      return '<button type="button" data-hit="' + esc(p.k) + '">' + esc(plabel(p)) + '<small>' + esc(p.dn) + (p.t ? ' &middot; ' + esc(p.t) : '') + ' &middot; ' + (p.un ? 'not visited yet' : STL[p.V.st].toLowerCase() + ', ' + dl(p.V.dt)) + (p.r.length ? ' &middot; reported' : '') + '</small></button>';
    }).join('') || '<button type="button" disabled>No plant found</button>';
    box.hidden = false;
  });
  $('results').addEventListener('click', function (e) {
    var b = e.target.closest('[data-hit]'); if (!b) return;
    var p = OI[b.dataset.hit]; if (!p) return;
    openPlant(p, true); $('results').hidden = true; $('q').value = '';
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
  // The search box keeps the design's wording where it fits, and says the same thing more briefly where
  // it shares its line with the Download button.
  var search = $('q'), searchWords = [search.placeholder, 'Plant, code or tehsil'], measure = document.createElement('canvas').getContext('2d');
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
    META = data.meta; DIS = data.districts; KEYS = data.keys; TX = data.texts; REP = data.reports;
    DN = DIS.map(function (d) { return d.n; });
    TOT = {}; DIS.forEach(function (d) { TOT[d.n] = d.tot; });
    KK = {}; KEYS.forEach(function (k, i) { KK[k.k] = i; });
    OTHER = KEYS.map(function (k, i) { return { i: i, k: k.k, label: k.l, g: k.g }; }).filter(function (k) { return ['private', 'misuse', 'other', 'closed', 'notfound'].indexOf(k.g) >= 0 || ['contractor', 'leak', 'nocover', 'dilap', 'vessel', 'rats', 'nofilter', 'pfa', 'bore', 'theft', 'resbill', 'tapstolen'].indexOf(k.k) >= 0; })
      .filter(function (k) { return ['notfound', 'closed', 'other', 'partial'].indexOf(k.k) < 0; });
    // Visits come once; the date filter picks from them. Reports and responses don't change with dates.
    ALL = data.plants.map(function (p) {
      p.dn = DN[p.d];
      p.av = p.v.map(function (a) { return { dt: a[0], src: a[1], st: a[2], tt: a[3], tw: a[4], keys: a[5], ph: a[6], w1: a[7], id: a[8] }; });
      p.v = p.av; p.V = p.v[p.v.length - 1] || null;
      return p;
    });
    UNV = data.unvisited.map(function (a) { return { k: a[0], n: a[1], t: a[2], d: a[3], dn: DN[a[3]], la: a[4], lo: a[5], v: [], av: [], r: [], un: 1, ml: 1, V: null }; });
    OI = {}; ALL.concat(UNV).forEach(function (p) { OI[p.k] = p; });
    REP.forEach(function (r) { r.p = OI[r.k]; });
    RC = data.recheck.filter(function (c) { return OI[c.k]; });
    RC.forEach(function (c) { c.p = OI[c.k]; c.r = REP[c.rid]; });

    DK.min = isoToKey(META.firstVisit); DK.max = isoToKey(META.lastVisit);
    ALL.forEach(function (p) { p.av.forEach(function (v) { var k = isoToKey(v.dt); if (k < DK.min) DK.min = k; if (k > DK.max) DK.max = k; }); });
    state.from = Math.max(state.from || DK.min, DK.min);
    state.to = Math.min(state.to || DK.max, DK.max);
    if (state.from > state.to) { state.from = DK.min; state.to = DK.max; }
    if (DK.min) ['f-from', 'f-to'].forEach(function (id) { $(id).min = keyToISO(DK.min); $(id).max = keyToISO(DK.max); });

    fillDistricts();
    $('f-district').value = state.district;
    var GLIST = GORD.map(function (g) { return { id: g, label: GL[g].label }; });
    $('f-find').innerHTML = '<option value="">Any plant</option><option value="rv">Visited more than once</option><option value="r2">Reported more than once</option>' +
      group('Found at the latest visit', TILES.map(function (g) { return { id: g, label: GL[g].label }; }), 'f:') + group('Reported to the department', GLIST, 'c:', 'Reported: ') + group('Department response', RT, 't:', 'Response: ') +
      group('Checked again after the report', GLIST, 'g:', 'Checked again: ');

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
