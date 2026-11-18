<script>
// Everything above the records table, and the cards in the banner, is worked out here from one
// download, so changing a filter is instant and does not reload the page. This is the School Meal
// design's own code, inside the page frame the other initiative pages use.
// The date filter applies to visits only: the SMU letters and the department's responses always count.
(function () {
  'use strict';

  var page = document.getElementById('psrpPage');
  var ALL = [], META = { minKey: 0, maxKey: 0, respMax: '', maxVisits: 0 }, EXTRA = [], loaded = false;

  var RT = [
    { id: 'fixed', label: 'Says fixed or acted', sub: 'Put right, or action taken on staff', sw: 'r-fixed', badge: 'b-good' },
    { id: 'directed', label: 'Instructions issued', sub: 'Orders to district offices or the supplier', sw: 'r-dir', badge: 'b-info' },
    { id: 'plan', label: 'Promised for later', sub: 'Waiting for funds or a repair date', sw: 'r-plan', badge: 'b-mute' },
    { id: 'disputed', label: 'Denied or explained', sub: 'No such problem, or a reason given', sw: 'r-disp', badge: 'b-warn' },
    { id: 'notans', label: 'Did not answer', sub: 'The response skips this point', sw: 'r-nota', badge: 'b-crit' },
    { id: 'none', label: 'No response', sub: 'Nothing on file yet', sw: 'r-none', badge: 'b-crit' }
  ];
  var RTI = {}; RT.forEach(function (t) { RTI[t.id] = t; });

  function rate(a, b) { return a === null || a === undefined || !b ? null : Math.min(a, b) / b; }
  function has(v, c) { return v.iss.indexOf(c) >= 0; }
  // The form's issues are Yes/No columns. One left blank ("Null") is not known, so it counts neither
  // as a problem nor as fine. nq lists them.
  function asked(v, c) { return !v.nq || v.nq.indexOf(c) < 0; }
  function anyOf(v, cs) { return cs.some(function (c) { return has(v, c); }) ? true : cs.every(function (c) { return asked(v, c); }) ? false : null; }
  function askedBase(c) { return function (v) { return asked(v, c); }; }
  function gapBad(v) { if (!v.op || v.rc === null || v.dg === null || v.ex === null || v.sk === null || !v.rc) return null; return Math.abs(v.rc - v.dg - v.ex - v.sk) > 0.05 * v.rc; }
  function dos(v) { return v.op && v.sk !== null && v.me ? v.sk / v.me : null; }
  var FIND = [
    { id: 'nostock', label: 'No milk in stock on the visit day', why: 'Students go without', base: function (v) { return v.op && v.sk !== null; }, f: function (v) { return v.op && v.sk === 0; } },
    { id: 'late', label: 'Milk not delivered on time', why: 'As told by the school', f: function (v) { return v.ot === 0; } },
    { id: 'qual', label: 'Milk quality not satisfactory', why: 'As judged at the visit', f: function (v) { return v.ql === 0; } },
    { id: 'harm', label: 'Students fell ill after the milk', why: 'As told by the school', f: function (v) { return v.hm === 1; } },
    { id: 'exprec', label: 'Expired milk received', why: 'Health risk', f: function (v) { return v.ers === 1; } },
    { id: 'home', label: 'Teachers or influential people take milk home', why: 'Misuse', base: askedBase('home'), f: function (v) { return has(v, 'home'); } },
    { id: 'clean', label: 'School not clean', why: 'Hygiene', base: askedBase('clean'), f: function (v) { return has(v, 'clean'); } },
    { id: 'dump', label: 'No place to dump used milk packs', why: 'Litter', base: askedBase('dump'), f: function (v) { return has(v, 'dump'); } },
    { id: 'store', label: 'Poor storage for milk packs', why: 'Stock at risk', base: askedBase('store'), f: function (v) { return has(v, 'store'); } },
    { id: 'gap', label: 'Stock record does not add up', why: 'Received ≠ given out + expired + in stock, off by over 5%', base: function (v) { return gapBad(v) !== null; }, f: function (v) { return gapBad(v) === true; } },
    { id: 'att', label: 'Fewer than 3 in 4 students present', why: 'Attendance', base: function (v) { return v.op && rate(v.sp, v.en) !== null; }, f: function (v) { var x = v.op ? rate(v.sp, v.en) : null; return x !== null && x < 0.75; } },
    { id: 'unsat', label: 'Rated unsatisfactory overall', why: 'Field team\'s rating', base: function (v) { return !!v.rt; }, f: function (v) { return v.rt === 'U'; } },
    { id: 'expd', label: 'Milk packs expired at the school', why: 'Wasted', f: function (v) { return v.ex > 0; } },
    { id: 'over', label: 'Too much milk stock received', why: 'Risk of expiry', base: askedBase('over'), f: function (v) { return has(v, 'over'); } },
    { id: 'closed', label: 'School closed on the visit day', why: 'No one to give milk', f: function (v) { return v.op === 0; } }
  ];
  var FI = {}; FIND.forEach(function (f) { FI[f.id] = f; });
  var TILES = ['nostock', 'late', 'qual', 'harm', 'exprec', 'home', 'clean', 'dump'];
  var OTHER = ['store', 'gap', 'att', 'unsat', 'expd', 'over', 'closed'];

  // What an issue in an SMU letter is about, as the server groups it, and how a later visit shows
  // whether it is still there.
  var TOPIC = [
    { id: 'stock', short: 'milk shortages and late delivery', label: 'Milk late, short or run out', chk: 'Milk in stock and delivered on time',
      seen: function (v) { return v.op ? (v.sk === 0 || v.ot === 0) : null; }, what: function (v) { return v.sk === 0 ? 'no milk in stock' : 'milk delivered late'; } },
    { id: 'quality', short: 'milk quality problems', label: 'Milk quality, expiry or illness', chk: 'Quality fine, no illness, no expired stock',
      seen: function (v) { return v.ql === null ? null : (v.ql === 0 || v.hm === 1 || v.ers === 1); }, what: function (v) { return v.ql === 0 ? 'quality not satisfactory' : v.hm === 1 ? 'students fell ill' : 'expired stock received'; } },
    { id: 'misuse', short: 'misuse and poor storage', label: 'Milk stored off-site, misused or not given out', chk: 'Milk stored properly, not taken home',
      seen: function (v) { return v.op ? anyOf(v, ['home', 'store']) : null; }, what: function (v) { return has(v, 'home') ? 'milk taken home' : 'poor storage'; } },
    { id: 'clean', short: 'poor cleanliness', label: 'Cleanliness, building and used packs', chk: 'School clean, used packs disposed of',
      seen: function (v) { return v.op ? anyOf(v, ['clean', 'dump']) : null; }, what: function (v) { return has(v, 'clean') ? 'school not clean' : 'no place to dump used packs'; } },
    { id: 'staff', short: 'low attendance', label: 'Teachers, attendance and discipline', chk: 'At least 3 in 4 students and staff present',
      seen: function (v) { var a = v.op ? rate(v.sp, v.en) : null; if (a === null) return null; var b = v.st && v.sx !== null ? v.sx / v.st : 0; return a < 0.75 || b > 0.25; },
      what: function (v) { return rate(v.sp, v.en) < 0.75 ? Math.round(rate(v.sp, v.en) * 100) + '% of students present' : v.sx + ' of ' + v.st + ' staff absent'; } },
    { id: 'furn', short: 'furniture', label: 'Furniture and classroom space', chk: 'Not checked by the visit form', seen: function () { return null; } },
    { id: 'other', short: 'other points', label: 'Other points', chk: 'Not checked by the visit form', seen: function () { return null; } }
  ];
  var TPI = {}; TOPIC.forEach(function (t) { TPI[t.id] = t; });
  function itemSeen(it, v) { if (!it.t || it.nck || !TPI[it.c]) return null; return TPI[it.c].seen(v); }
  var SA = { susp: 'suspensions', show: 'show-cause notices', expl: 'explanation letters', warn: 'warnings' };
  var RATING = [{ id: 'S', label: 'Satisfactory', sub: 'Everything in order', cls: 'rt-S' }, { id: 'A', label: 'Average', sub: 'Some gaps', cls: 'rt-A' }, { id: 'U', label: 'Unsatisfactory', sub: 'Serious problems', cls: 'rt-U' }];
  var RTG = {}; RATING.forEach(function (r) { RTG[r.id] = r; });
  var LAG = [{ id: 'w', label: 'Within a week', f: function (x) { return x <= 7; } }, { id: 'm', label: '8 to 10 days', f: function (x) { return x > 7 && x <= 10; } },
    { id: 'f', label: '11 to 15 days', f: function (x) { return x > 10 && x <= 15; } }, { id: 'l', label: 'More than 15 days', f: function (x) { return x > 15; } }];
  var DOS = [{ id: 'z', label: 'None left', f: function (x) { return x === 0; } }, { id: 'a', label: 'Under 3 days', f: function (x) { return x > 0 && x < 3; } },
    { id: 'b', label: '3 to 15 days', f: function (x) { return x >= 3 && x <= 15; } }, { id: 'c', label: '16 to 30 days', f: function (x) { return x > 15 && x <= 30; } },
    { id: 'd', label: 'Over 30 days (may expire)', f: function (x) { return x > 30; } }];
  var PROM = [
    { k: 'stock', label: 'Milk in stock', ok: function (v) { return v.op && v.sk !== null ? v.sk > 0 : null; } },
    { k: 'ontime', label: 'Milk delivered on time', ok: function (v) { return v.ot === null ? null : v.ot === 1; } },
    { k: 'qual', label: 'Milk quality satisfactory', ok: function (v) { return v.ql === null ? null : v.ql === 1; } },
    { k: 'harm', label: 'No student fell ill', ok: function (v) { return v.hm === null ? null : v.hm === 0; } },
    { k: 'exp', label: 'No expired stock received', ok: function (v) { return v.ers === null ? null : v.ers === 0; } },
    { k: 'clean', label: 'School clean', ok: function (v) { return v.op && asked(v, 'clean') ? !has(v, 'clean') : null; } },
    { k: 'dump', label: 'Used milk packs disposed of', ok: function (v) { return v.op && asked(v, 'dump') ? !has(v, 'dump') : null; } },
    { k: 'store', label: 'Milk packs stored properly', ok: function (v) { return v.op && asked(v, 'store') ? !has(v, 'store') : null; } },
    { k: 'att', label: 'Students present', rate: true }
  ];

  var MN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var $ = function (id) { return document.getElementById(id); };
  function esc(s) { return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function n(x) { return Number(x).toLocaleString('en-US'); }
  function pct(a, b) { if (!b) return 0; var p = a / b * 100; return p > 0 && p < 0.5 ? '<1' : p > 99.5 && p < 100 ? '>99' : Math.round(p); }
  function pl(c, one, many) { return n(c) + ' ' + (c === 1 ? one : many); }
  function dl(iso) { if (!iso) return ''; var p = iso.split('-'); return Number(p[2]) + ' ' + MN[Number(p[1]) - 1] + ' ' + p[0]; }
  function ml(iso) { var p = iso.split('-'); return MN[Number(p[1]) - 1] + ' ' + p[0]; }
  function days(a, b) { return Math.round((new Date(b) - new Date(a)) / 864e5); }
  function median(a) { if (!a.length) return null; a = a.slice().sort(function (x, y) { return x - y; }); var m = Math.floor(a.length / 2); return a.length % 2 ? a[m] : Math.round((a[m - 1] + a[m]) / 2); }
  function andList(a) { return a.length < 2 ? a.join('') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1]; }
  function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
  function dateText(ds) {
    if (!ds.length) return '';
    var m = ds.map(ml), same = m.every(function (x) { return x === m[0]; });
    if (same && ds.length <= 3) return 'on ' + andList(ds.map(function (d) { return String(Number(d.split('-')[2])); })) + ' ' + m[0];
    var a = m[0].split(' '), b = m[m.length - 1].split(' ');
    return same ? 'in ' + m[0] : 'from ' + (a[1] === b[1] ? a[0] : m[0]) + ' to ' + m[m.length - 1];
  }
  // "visited twice" while no school has more visits than that, "more than once" after.
  function twice() { return META.maxVisits > 2 ? 'more than once' : 'twice'; }

  /* Dates: the filter keeps the visits made between From and To. */
  function keyToISO(k) { var s = String(k); return s.slice(0, 4) + '-' + s.slice(4, 6) + '-' + s.slice(6, 8); }
  function isoToKey(s) { return s ? Number(s.replace(/-/g, '')) : 0; }
  function fullRange() { return state.from === META.minKey && state.to === META.maxKey; }
  function rangeText() { return dl(keyToISO(state.from)) + ' to ' + dl(keyToISO(state.to)); }

  /* Per-school fields that depend on the visits in the date range */
  function derive() {
    ALL.forEach(function (s) {
      s.v = s.av.filter(function (v) { var k = isoToKey(v.dt); return k >= state.from && k <= state.to; });
      s.V = s.v.length ? s.v[s.v.length - 1] : null;
      s.f = s.V ? FIND.filter(function (x) { return x.f(s.V); }).map(function (x) { return x.id; }) : [];
    });
  }

  var state = { district: $('f-district').value, from: isoToKey($('f-from').value), to: isoToKey($('f-to').value), show: 'all', filter: '', sel: null, sort: 'visited', dir: -1, all: false, fuAll: false };
  function scoped() { return ALL.filter(function (s) { return !state.district || s.d === state.district; }); }
  function where() { return state.district ? ' in ' + state.district : ''; }
  function named(r) { return r.it.some(function (it) { return it.t; }) ? 1 : 0; }
  function reportsOf(list, noExtra) {
    var out = [];
    list.forEach(function (s) { s.r.forEach(function (r) { out.push({ r: r, s: s }); }); });
    if (!noExtra) EXTRA.forEach(function (r) { if (!state.district || r.d === state.district) out.push({ r: r, s: null }); });
    return out.sort(function (a, b) { return named(b.r) - named(a.r) || (a.r.ld < b.r.ld ? 1 : a.r.ld > b.r.ld ? -1 : (a.r.sr || 0) - (b.r.sr || 0)); });
  }
  // The visit after the department responded, when it is in the dates chosen.
  function followUp(x) {
    var r = x.r, s = x.s; if (!s || !r.fu) return null;
    var v = s.v.filter(function (y) { return y.dt === r.fu; })[0];
    if (!v) return null;
    var res = r.it.filter(function (it) { return it.t; }).map(function (it) { return { it: it, seen: itemSeen(it, v) }; });
    if (!res.length) return null;
    var chk = res.filter(function (z) { return z.seen !== null; });
    return { v: v, res: res, n: chk.length, bad: chk.filter(function (z) { return z.seen; }).length };
  }

  /* Summary */
  function summarise(list, noExtra) {
    var o = { total: list.length, visited: 0, visits: 0, twice: 0, dates: {}, find: {}, base: {}, rt: { S: 0, A: 0, U: 0 }, rated: 0,
      lag: {}, lagN: 0, dos: {}, dosN: 0, rc: 0, dg: 0, ex: 0, sk: 0, pk: 0, en: 0, sp: 0, me: 0, att: 0, bf: 0, bfen: 0, bfn: 0, open: 0,
      reports: 0, items: 0, replied: 0, noReply: 0, types: {}, top: {}, lags: [], lds: {}, sa: {}, saSch: 0, fx: {}, fu: [], noIssue: 0 };
    FIND.forEach(function (f) { o.find[f.id] = 0; o.base[f.id] = 0; });
    LAG.forEach(function (t) { o.lag[t.id] = 0; }); DOS.forEach(function (t) { o.dos[t.id] = 0; });
    RT.forEach(function (t) { o.types[t.id] = 0; });
    TOPIC.forEach(function (t) { o.top[t.id] = { n: 0, sch: {}, t: {} }; RT.forEach(function (r) { o.top[t.id].t[r.id] = 0; }); });
    list.forEach(function (s) {
      if (!s.V) return;
      var v = s.V;
      o.visited++; o.visits += s.v.length; if (s.v.length > 1) o.twice++;
      s.v.forEach(function (x) { o.dates[x.dt] = 1; });
      FIND.forEach(function (f) { if (!f.base || f.base(v)) o.base[f.id]++; });
      s.f.forEach(function (id) { o.find[id]++; });
      if (v.rt) { o.rt[v.rt]++; o.rated++; }
      if (v.lag !== null) { o.lagN++; LAG.forEach(function (t) { if (t.f(v.lag)) o.lag[t.id]++; }); }
      var d = dos(v); if (d !== null) { o.dosN++; DOS.forEach(function (t) { if (t.f(d)) o.dos[t.id]++; }); }
      if (v.op && v.rc !== null && v.dg !== null && v.ex !== null && v.sk !== null) { o.rc += v.rc; o.dg += v.dg; o.ex += v.ex; o.sk += v.sk; o.pk++; }
      if (v.op) {
        o.open++;
        if (rate(v.sp, v.en) !== null) { o.en += v.en; o.sp += Math.min(v.sp, v.en); o.att++; if (v.me !== null) o.me += v.me; }
        if (v.bf && v.en !== null) { o.bf += v.bf; o.bfen += v.en; o.bfn++; }
      }
    });
    var reps = reportsOf(list, noExtra);
    o.reports = reps.length;
    reps.forEach(function (x) {
      var r = x.r, sa = false;
      if (r.ld) o.lds[r.ld] = 1;
      if (r.x) { o.replied++; if (r.ld && r.rd) o.lags.push(days(r.ld, r.rd)); } else o.noReply++;
      var nm = r.it.filter(function (it) { return it.t; });
      if (!nm.length) o.noIssue++;
      nm.forEach(function (it) {
        o.items++; o.types[it.t]++;
        var tp = o.top[it.c] || o.top.other; tp.n++; tp.t[it.t]++; tp.sch[r.id] = 1;
        (it.sa || []).forEach(function (a) { o.sa[a] = (o.sa[a] || 0) + 1; sa = true; });
        if (it.fx) o.fx[it.fx] = (o.fx[it.fx] || 0) + 1;
      });
      if (sa) o.saSch++;
      var f = followUp(x); if (f) o.fu.push({ x: x, f: f });
    });
    o.lag2 = median(o.lags);
    o.dates = Object.keys(o.dates).sort(); o.lds = Object.keys(o.lds).sort();
    o.fuChk = o.fu.filter(function (z) { return z.f.n; });
    o.fuClear = o.fuChk.filter(function (z) { return !z.f.bad; }).length;
    return o;
  }

  /* The cards in the banner. "Reports sent to department" is the number of report PDFs uploaded,
     set by the server, so it is left as it is. */
  function setCard(id, num, sub) {
    var el = $(id), info = $(id + '-sub');
    if (el) el.textContent = n(num);
    if (info && sub !== undefined) info.textContent = sub;
  }
  function renderFlow(o) {
    setCard('c-total', o.total);
    setCard('c-visited', o.visited, o.visited ? pct(o.visited, o.total) + '% of total schools, ' + pl(o.visits, 'visit', 'visits') + ' ' + dateText(o.dates) : 'None yet');
    var said = [];
    if (o.lag2 !== null) said.push((o.replied > 1 ? 'Usually within ' : 'Within ') + pl(o.lag2, 'day', 'days') + ' of the SMU letter');
    if (o.noReply) said.push(n(o.noReply) + ' with no response');
    setCard('c-replied', o.replied, o.reports ? said.join('. ') : '');
    setCard('c-revisit', o.twice, o.twice ? pl(o.twice, 'school', 'schools') + ' checked a second time' : 'No school checked again yet');
  }

  /* The three Impact cards, at the top of "What changed after the SMU visits" */
  function renderImpact(o) {
    var acted = o.types.fixed, txt = '';
    if (o.saSch) txt += 'Action on staff at ' + pl(o.saSch, 'school', 'schools') + ': ' + andList(['expl', 'warn', 'show', 'susp'].filter(function (k) { return o.sa[k]; }).map(function (k) { return SA[k]; })) + '. ';
    var cl = (o.fx.clean || 0) + (o.fx.sop || 0) + (o.fx.room || 0), fx = [];
    if (o.fx.stock) fx.push('milk stock delivered at ' + pl(o.fx.stock, 'school', 'schools'));
    if (cl) fx.push('cleanliness put right at ' + n(cl));
    if (o.fx.post) fx.push('teachers posted at ' + n(o.fx.post));
    if (fx.length) txt += cap(andList(fx)) + '. ';
    if (o.types.directed) txt += (o.types.directed === 1 ? 'One more issue got' : 'Another ' + n(o.types.directed) + ' got') + ' instructions to district offices or the supplier.';
    $('imp-1').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (o.items ? n(acted) + ' of ' + n(o.items) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (o.items ? 'issues SMU reported were fixed or acted on, says the department' : 'No SMU letter here yet') + '</b><small>' + (o.items ? esc(txt) : '') + '</small></div>';
    var fu = o.fuChk, clear = o.fuClear;
    var tops = TOPIC.map(function (t) { var c = 0, b = 0; fu.forEach(function (z) { z.f.res.forEach(function (q) { if (q.it.c === t.id && q.seen !== null) { c++; if (q.seen) b++; } }); }); return { t: t, c: c, b: b }; }).filter(function (x) { return x.c; });
    var good = tops.filter(function (x) { return !x.b; }).sort(function (a, b) { return b.c - a.c; }), worst = tops.filter(function (x) { return x.b; }).sort(function (a, b) { return b.b - a.b; });
    $('imp-2').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (fu.length ? n(clear) + ' of ' + n(fu.length) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (fu.length ? 'schools SMU went back to after the response no longer showed the problem reported' : 'No reported school visited again after the response here yet') + '</b><small>' +
      (fu.length ? (good.length ? 'Gone at every school checked: ' + andList(good.slice(0, 3).map(function (x) { return x.t.short + ' (' + n(x.c) + ')'; })) + '. ' : '') +
        (worst.length ? 'Still seen: ' + andList(worst.slice(0, 2).map(function (x) { return x.t.short + ' at ' + n(x.b) + ' of ' + n(x.c); })) + '.' : '') : '') + '</small></div>';
    var up = o.bfn && o.bf ? (o.bfen - o.bf) / o.bf * 100 : null;
    $('imp-3').innerHTML = '<span class="imp-tag">Key finding</span><div class="imp-num">' + (up !== null ? (up >= 0 ? '+' : '') + up.toFixed(1) + '%' : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (up !== null ? (up >= 0 ? 'more' : 'fewer') + ' students enrolled than before the milk programme began' : 'No enrolment figures here yet') + '</b><small>' +
      (up !== null ? n(o.bf) + ' students before, ' + n(o.bfen) + ' now, at ' + pl(o.bfn, 'school', 'schools') + ' visited, as recorded by the schools.' : '') + '</small></div>';
  }

  function irow(label, c, max, of, filter) {
    return '<button type="button" class="irow"' + (filter ? ' data-filter="' + filter + '"' : '') + '><span>' + esc(label) + '</span><span class="bar"><i style="width:' + (max ? c / max * 100 : 0) + '%"></i></span><span class="num"><b>' + n(c) + '</b></span><span class="num pct">' + pct(c, of) + '%</span></button>';
  }
  function irowHead(a, b) { return '<div class="irow irow-h"><span>' + a + '</span><span></span><span class="num">' + b + '</span><span class="num">Share</span></div>'; }

  /* Issues */
  function renderIssues(o) {
    $('tiles').innerHTML = TILES.map(function (id) {
      var f = FI[id], c = o.find[id], b = o.base[id];
      return '<button type="button" class="tile" data-filter="f:' + f.id + '"><b>' + n(c) + '</b><span>' + esc(f.label) + '</span><small>' + esc(f.why) + ' &middot; ' + (o.visited ? pct(c, f.base ? b : o.visited) + '% of visited' : 'no visits') + '</small></button>';
    }).join('');
    var mx = Math.max.apply(null, OTHER.map(function (id) { return o.find[id]; }).concat([1]));
    $('other-rows').innerHTML = irowHead('Finding', 'Schools') + OTHER.map(function (id) { var f = FI[id]; return irow(f.label, o.find[id], mx, f.base ? o.base[id] : o.visited, 'f:' + id); }).join('');
  }

  /* Supply */
  function renderSupply(o) {
    $('prog').innerHTML = '<h3>Milk given out</h3><div class="progress-h"><span><span class="prog-big">' + (o.pk ? n(o.dg) + ' of ' + n(o.rc) : '&ndash;') + '</span> milk packs received so far were given to students</span><span>' +
      (o.pk ? n(o.sk) + ' in stock &middot; ' + n(o.ex) + ' expired &middot; ' + pct(o.dg, o.rc) + '% given out' : '') + '</span></div><div class="track" role="img" aria-label="' + o.dg + ' of ' + o.rc + ' given out"><i style="width:' + (o.rc ? Math.min(o.dg / o.rc, 1) * 100 : 0) + '%"></i></div>' +
      '<p class="sub" style="margin-top:8px">From the stock register at ' + pl(o.pk, 'school', 'schools') + ', since the programme began at each school.</p>';
    var ml_ = Math.max.apply(null, LAG.map(function (t) { return o.lag[t.id]; }).concat([1]));
    $('lag-rows').innerHTML = irowHead('Milk arrives', 'Schools') + LAG.map(function (t) { return irow(t.label, o.lag[t.id], ml_, o.lagN, ''); }).join('');
    var md = Math.max.apply(null, DOS.map(function (t) { return o.dos[t.id]; }).concat([1]));
    $('dos-sub').textContent = 'School days of milk left, at one pack per student a day.';
    $('dos-rows').innerHTML = irowHead('Milk left for', 'Schools') + DOS.map(function (t) { return irow(t.label, o.dos[t.id], md, o.dosN, t.id === 'z' ? 'f:nostock' : ''); }).join('');
    var tot = o.rated || 1, unrated = o.visited - o.rated;
    $('rt-sub').textContent = o.rated ? pl(o.rated, 'school was', 'schools were') + ' rated' + (unrated ? '; ' + pl(unrated, 'school visited has', 'schools visited have') + ' no rating' : '') + '.' : 'No rated visit in this selection.';
    $('rt-bar').innerHTML = RATING.map(function (t) { var c = o.rt[t.id]; return c ? '<i class="' + t.cls + '" style="width:' + c / tot * 100 + '%" data-tip="<b>' + t.label + '</b>' + pl(c, 'school', 'schools') + ', ' + pct(c, tot) + '%"></i>' : ''; }).join('');
    $('rt-bar').setAttribute('aria-label', RATING.map(function (t) { return t.label + ' ' + o.rt[t.id]; }).join(', '));
    $('rt-outs').innerHTML = RATING.map(function (t) {
      return '<button type="button" class="card out" data-filter="g:' + t.id + '"><div class="out-num">' + n(o.rt[t.id]) + '</div><div class="out-lbl"><span class="sw ' + t.cls + '"></span>' + t.label + '</div><div class="out-sub">' + t.sub + '</div></button>';
    }).join('');
    $('stu').innerHTML = o.att ? '<div><b>' + n(o.en) + '</b><span>students enrolled</span></div><div><b>' + pct(o.sp, o.en) + '%</b><span>present on the visit day</span></div><div><b>' + n(o.me) + '</b><span>entitled to a milk pack</span></div>' : '<p class="empty">No students counted in this selection.</p>';
  }

  /* What changed */
  function renderChanged(o, list) {
    $('ans-sub').textContent = o.items ? 'Each of the ' + n(o.items) + ' issues SMU raised in its letters is counted once, by the kind of response it got. Click a group to see those schools on the map.' : 'No letters in this selection.';
    var tot = o.items || 1;
    $('segbar').innerHTML = RT.map(function (t) {
      var c = o.types[t.id]; if (!c) return '';
      return '<i class="' + t.sw + '" style="width:' + (c / tot * 100) + '%" data-tip="<b>' + esc(t.label) + '</b>' + pl(c, 'issue', 'issues') + ', ' + pct(c, tot) + '%"></i>';
    }).join('');
    $('segbar').setAttribute('aria-label', RT.map(function (t) { return t.label + ' ' + o.types[t.id]; }).join(', '));
    $('outs').innerHTML = RT.map(function (t) {
      return '<button type="button" class="card out" data-filter="t:' + t.id + '"><div class="out-num">' + n(o.types[t.id]) + '</div><div class="out-lbl"><span class="sw ' + t.sw + '"></span>' + esc(t.label) + '</div><div class="out-sub">' + esc(t.sub) + '</div></button>';
    }).join('');
    var rows = TOPIC.map(function (t) { return { t: t, a: o.top[t.id], sch: Object.keys(o.top[t.id].sch).length }; }).filter(function (x) { return x.a.n; }).sort(function (a, b) { return b.a.n - a.a.n; });
    $('iss-t').innerHTML = rows.length ? '<thead><tr><th>Issue raised</th><th class="r">Schools</th><th>Department\'s response</th><th class="r">Denied, explained or unanswered</th></tr></thead><tbody>' + rows.map(function (x) {
      var a = x.a, tt = a.n || 1, open = a.t.disputed + a.t.none + a.t.notans;
      return '<tr data-filter="c:' + x.t.id + '"><td class="lab">' + esc(x.t.label) + '</td><td class="r">' + n(x.sch) + '</td><td><div class="stack" role="img" aria-label="' + RT.map(function (t) { return t.label + ' ' + a.t[t.id]; }).join(', ') + '" data-tip="<b>' + esc(x.t.label) + '</b>' + RT.filter(function (t) { return a.t[t.id]; }).map(function (t) { return esc(t.label) + ': ' + a.t[t.id]; }).join('<br>') + '">' +
        RT.map(function (t) { return a.t[t.id] ? '<i class="' + t.sw + '" style="width:' + a.t[t.id] / tt * 100 + '%"></i>' : ''; }).join('') + '</div></td><td class="r' + (open / tt > 0.5 ? ' dn' : '') + '">' + (open ? n(open) + ' <span class="pct">of ' + n(a.n) + '</span>' : '&ndash;') + '</td></tr>';
    }).join('') + '</tbody>' : '<tbody><tr><td class="empty" style="padding:12px 18px">No letters in this selection.</td></tr></tbody>';
    $('iss-key').innerHTML = RT.map(function (t) { return '<span><i class="sw ' + t.sw + '"></i>' + esc(t.label) + '</span>'; }).join('');

    /* follow-ups */
    var fu = o.fu.slice().sort(function (a, b) { return (a.f.n ? (a.f.bad ? 1 : 0) : 2) - (b.f.n ? (b.f.bad ? 1 : 0) : 2) || (a.x.r.fu < b.x.r.fu ? -1 : 1); });
    $('fu-sub').textContent = fu.length ? pl(fu.length, 'school', 'schools') + ' in the SMU letters ' + (fu.length === 1 ? 'was' : 'were') + ' visited again after the department responded. The later visit shows whether the problem was still there.' : 'No school in the letters' + where() + ' has been visited after the department responded yet' + (fullRange() ? '' : ', in the dates chosen') + '.';
    $('fu-t').innerHTML = fu.length ? '<thead><tr><th>School</th><th>What SMU reported</th><th>Department said</th><th>Visited again</th><th>Problem seen again?</th></tr></thead><tbody>' + fu.map(function (z, i) {
      var r = z.x.r, s = z.x.s, v = z.f.v, nm = z.f.res;
      var seenTxt = nm.map(function (q) {
        var t = TPI[q.it.c];
        if (q.seen === null) return '<span class="res na">' + (q.it.nck ? 'Not checked by the form' : v.op === 0 ? 'School closed' : (!t || t.id === 'furn' || t.id === 'other') ? 'Not checked by the form' : 'Not recorded') + '</span>';
        return q.seen ? '<span class="res bad">✗ Yes: ' + esc(t.what(v)) + '</span>' : '<span class="res ok">✓ No</span>';
      });
      return '<tr data-open="' + esc(s.k) + '"' + (i >= 8 && !state.fuAll ? ' hidden' : '') + '><td class="lab">' + esc(s.n) + '<small>' + esc(s.d) + '</small></td><td>' + nm.map(function (q) { return esc(q.it.i) + '<small>' + esc((TPI[q.it.c] || TPI.other).chk) + '</small>'; }).join('') + '</td><td>' +
        nm.map(function (q) { var t = RTI[q.it.t]; return '<span class="tbadge badge ' + t.badge + '">' + esc(t.label) + '</span>'; }).join('<br>') + '<small>Letter ' + dl(r.ld) + (r.rd ? ', response ' + dl(r.rd) : '') + '</small></td><td>' + dl(r.fu) + '<small>' + pl(days(r.rd || r.ld, r.fu), 'day', 'days') + ' after the response</small></td><td>' + seenTxt.join('<br>') + '</td></tr>';
    }).join('') + '</tbody>' : '<tbody><tr><td class="empty" style="padding:12px 18px">None in this selection.</td></tr></tbody>';
    $('fu-more').hidden = fu.length <= 8;
    $('fu-more').textContent = state.fuAll ? 'Show fewer' : 'Show all ' + n(fu.length) + ' schools';
    $('fu-note').textContent = fu.length ? 'The visit form checks milk stock, delivery, quality, storage, cleanliness and attendance. It does not check furniture or teacher conduct, so those are shown as not checked. "Seen again" uses the same rules as the issues above.' : '';

    /* letters */
    var reps = reportsOf(list);
    $('acts').className = 'acts' + (reps.length === 1 ? ' one' : '');
    $('acts').innerHTML = reps.length ? reps.map(function (x, i) {
      var r = x.r, s = x.s, name = s ? s.n : r.n, d = s ? s.d : r.d;
      return '<div class="card act-col' + (i >= 6 && !state.all ? ' hide' : '') + '"><h3>' + esc(name) + '</h3><p>' + esc(d) + (r.ld ? ' &middot; SMU letter ' + dl(r.ld) : '') + (r.rd ? ' &middot; response ' + dl(r.rd) + (r.ld ? ', ' + pl(days(r.ld, r.rd), 'day', 'days') + ' later' : '') : r.x ? ' &middot; response not dated' : '') + '</p>' +
        reportBody(r) + (s ? (s.la === null || s.la === undefined ? '<p class="note">This school has no map location, so it has no pin.</p>' : '<button type="button" class="link" data-open="' + esc(s.k) + '">Open on the map</button>') : '<p class="note">Not in the department file, so it has no pin.</p>') + '</div>';
    }).join('') : '<p class="empty">No department response in this selection.</p>';
    $('showall').hidden = reps.length <= 6;
    $('showall').textContent = state.all ? 'Show fewer' : 'Show all ' + n(reps.length) + ' letters';
    var visitedNoResp = list.filter(function (s) { return s.V && !s.r.length; }).length;
    var lastVisit = META.maxKey ? keyToISO(META.maxKey) : '';
    $('caveat').innerHTML = '<b>These are the department\'s own statements.</b> ' + (o.fu.length ? 'SMU has checked ' + pl(o.fu.length, 'of these schools', 'of these schools') + ' again (see above); the rest have not been visited since. ' : 'SMU has not checked them on a new visit yet. ') +
      (visitedNoResp ? 'The other ' + n(visitedNoResp) + ' schools visited' + esc(where()) + ' are not in any SMU letter in the response sheet' + (META.respMax && lastVisit && META.respMax < lastVisit ? '; the latest response is dated ' + dl(META.respMax) + ', and visits run to ' + dl(lastVisit) : '') + '.' : '');
  }
  function reportBody(r) {
    var nm = r.it.filter(function (it) { return it.t; });
    var h = nm.length ? nm.map(function (it) {
      var t = RTI[it.t];
      return '<div class="ritem"><div class="ri-h"><span class="ri-is"><small class="rlab">Issue</small>' + esc(it.i) + '</span><span class="badge ' + t.badge + '">' + esc(t.label) + '</span></div></div>';
    }).join('') : '<div class="ritem"><div class="ri-h"><span class="ri-is"><small class="rlab">SMU observation</small>' + esc(r.obs || (r.it[0] && r.it[0].i) || '') + '</span><span class="badge b-good">No issue raised</span></div></div>';
    h += r.x ? '<small class="rlab">Department\'s response</small><p class="rresp" title="Click to read the full response">' + esc(r.x) + '</p>' : '<p class="rresp na">No response on file.</p>';
    if (r.sh && r.sh.length) h += '<p class="shared">The same observation and response cover ' + pl(r.sh.length, 'other school', 'other schools') + ' in the sheet (Sr. ' + r.sh.join(', ') + ').</p>';
    return h;
  }

  /* Revisits */
  function revisitData(list) {
    var L = list.filter(function (s) { return s.v.length > 1; });
    var rows = PROM.map(function (p) {
      var a = { row: p, n: 0, f: 0, l: 0, up: 0, same: 0, dn: 0, fp: 0, ft: 0, lp: 0, lt: 0 };
      L.forEach(function (s) {
        var F = s.v[0], La = s.v[s.v.length - 1];
        if (p.rate) {
          var x = F.op ? rate(F.sp, F.en) : null, y = La.op ? rate(La.sp, La.en) : null; if (x === null || y === null) return;
          a.n++; a.fp += Math.min(F.sp, F.en); a.ft += F.en; a.lp += Math.min(La.sp, La.en); a.lt += La.en;
          if (y > x + 0.05) a.up++; else if (y < x - 0.05) a.dn++; else a.same++;
        } else {
          var f = p.ok(F), l = p.ok(La); if (f === null || l === null) return;
          a.n++; if (f) a.f++; if (l) a.l++;
          if (l && !f) a.up++; else if (f && !l) a.dn++; else a.same++;
        }
      });
      return a;
    });
    L.forEach(function (s) {
      var F = s.v[0], La = s.v[s.v.length - 1]; s.up = 0; s.dn = 0; s.F = F; s.L = La;
      PROM.forEach(function (p) {
        if (p.rate) { var x = F.op ? rate(F.sp, F.en) : null, y = La.op ? rate(La.sp, La.en) : null; if (x === null || y === null) return; if (y > x + 0.05) s.up++; else if (y < x - 0.05) s.dn++; return; }
        var f = p.ok(F), l = p.ok(La); if (f === null || l === null) return; if (l && !f) s.up++; else if (f && !l) s.dn++;
      });
    });
    return { n: L.length, rows: rows, list: L };
  }
  function renderRevisit(list) {
    var rv = revisitData(list);
    $('rv-h').textContent = 'Schools visited ' + twice();
    $('rev-sub').textContent = rv.n ? pl(rv.n, 'school was', 'schools were') + ' visited ' + twice() + ' by SMU field teams' + where() + '. Comparing the first and the latest visit shows what changed on the ground.' : 'No school' + where() + ' has been visited ' + twice() + ' yet' + (fullRange() ? '' : ' in the dates chosen') + '.';
    $('rev-t').innerHTML = rv.n ? '<thead><tr><th>Check</th><th class="r">First visit</th><th class="r">Latest visit</th><th>Change at each school</th></tr></thead><tbody>' + rv.rows.map(function (a) {
      if (!a.n) return '';
      var r = a.row, tt = a.up + a.same + a.dn || 1;
      var f = r.rate ? Math.round(a.fp / a.ft * 100) + '%' : n(a.f) + ' of ' + n(a.n), l = r.rate ? Math.round(a.lp / a.lt * 100) + '%' : n(a.l) + ' of ' + n(a.n);
      var fv = r.rate ? a.fp / a.ft : a.f / a.n, lv = r.rate ? a.lp / a.lt : a.l / a.n, cls = lv > fv + 0.02 ? 'up' : lv < fv - 0.02 ? 'dn' : '';
      return '<tr><td class="lab">' + r.label + (r.rate ? '<small>All schools together, students present out of those enrolled.</small>' : '') + '</td><td class="r">' + f + '</td><td class="r ' + cls + '">' + l + '</td>' +
        '<td><div class="chg"><div class="stack" role="img" aria-label="' + a.up + ' better, ' + a.same + ' same, ' + a.dn + ' worse" data-tip="<b>' + esc(r.label) + '</b>' + n(a.up) + ' better, ' + n(a.same) + ' no change, ' + n(a.dn) + ' worse"><i class="s-more" style="width:' + a.up / tt * 100 + '%"></i><i class="s-same" style="width:' + a.same / tt * 100 + '%"></i><i class="s-less" style="width:' + a.dn / tt * 100 + '%"></i></div><span>' + n(a.up) + ' better, ' + n(a.dn) + ' worse</span></div></td></tr>';
    }).join('') + '</tbody>' : '<tbody><tr><td class="empty" style="padding:12px 18px">No school visited ' + twice() + ' in this selection.</td></tr></tbody>';
    var gaps = rv.list.map(function (s) { return days(s.F.dt, s.L.dt); }), gap = median(gaps);
    var noRating = rv.list.filter(function (s) { return !s.F.rt; }).length;
    $('rev-note').textContent = rv.n ? 'The two visits were ' + (gap >= 60 ? 'usually about ' + Math.round(gap / 30) + ' months apart' : 'usually ' + pl(gap, 'day', 'days') + ' apart') + '. Attendance counts as better or worse when it moved by more than 5 points. The overall rating is not compared' + (noRating * 2 > rv.n ? ', as most first visits have no rating' : '') + '.' : '';
    $('rv-list').innerHTML = rv.list.slice().sort(function (a, b) { return (b.up - b.dn) - (a.up - a.dn) || a.n.localeCompare(b.n); }).map(function (s) {
      return '<button type="button" data-open="' + esc(s.k) + '"><span>' + esc(s.n) + '<small>' + esc(s.d) + ' &middot; ' + dl(s.F.dt) + ' and ' + dl(s.L.dt) + '</small></span><span class="mk"><i class="g">' + s.up + '</i><i class="w">' + s.dn + '</i></span></button>';
    }).join('') || '<p class="empty">None in this selection.</p>';
  }

  /* Districts */
  function districtRows() {
    var by = {};
    ALL.forEach(function (s) { (by[s.d] = by[s.d] || []).push(s); });
    return Object.keys(by).map(function (d) {
      var L = by[d], o = summarise(L, true);
      var top = FIND.filter(function (f) { return f.id !== 'unsat'; }).sort(function (a, b) { return o.find[b.id] - o.find[a.id]; })[0];
      return { d: d, total: o.total, visited: o.visited, cov: o.visited / o.total, nostock: o.find.nostock, late: o.find.late, qual: o.find.qual, clean: o.find.clean,
        rep: L.filter(function (s) { return s.r.length; }).length, top: o.visited && o.find[top.id] ? top.label : '' };
    });
  }
  var DCOLS = [
    { k: 'd', t: 'District' }, { k: 'total', t: 'Total schools', r: 1 }, { k: 'visited', t: 'Visited', r: 1 }, { k: 'cov', t: 'Share visited' },
    { k: 'nostock', t: 'No milk in stock', r: 1 }, { k: 'late', t: 'Milk late', r: 1 }, { k: 'qual', t: 'Quality not satisfactory', r: 1 }, { k: 'clean', t: 'School not clean', r: 1 },
    { k: 'rep', t: 'In SMU letters', r: 1 }, { k: 'top', t: 'Most common finding' }
  ];
  function renderDistricts() {
    var D = districtRows(), k = state.sort, dir = state.dir;
    var rows = D.slice().sort(function (a, b) { var x = a[k], y = b[k]; return (typeof x === 'string' ? x.localeCompare(y) : x - y) * dir || b.total - a.total || a.d.localeCompare(b.d); });
    function cnt(r, key, hi) { return r.visited ? '<td class="r' + (r[key] && r[key] / r.visited >= hi ? ' dn' : '') + '">' + n(r[key]) + '</td>' : '<td class="r">&ndash;</td>'; }
    $('dt').innerHTML = '<thead><tr>' + DCOLS.map(function (c) {
      var on = c.k === k;
      return '<th scope="col" class="' + (c.r ? 'r' : '') + '" aria-sort="' + (on ? (dir > 0 ? 'ascending' : 'descending') : 'none') + '"><button type="button" data-sort="' + c.k + '">' + c.t + (on ? (dir > 0 ? ' ↑' : ' ↓') : '') + '</button></th>';
    }).join('') + '</tr></thead><tbody>' + rows.map(function (r) {
      return '<tr data-district="' + esc(r.d) + '"' + (r.d === state.district ? ' class="on"' : '') + '><td><button type="button" class="dname" data-district="' + esc(r.d) + '">' + esc(r.d) + '</button></td><td class="r">' + n(r.total) + '</td><td class="r">' + n(r.visited) + '</td>' +
        '<td><div class="cov"><div class="bar"><i style="width:' + r.cov * 100 + '%"></i></div><span>' + pct(r.visited, r.total) + '%</span></div></td>' + cnt(r, 'nostock', 0.1) + cnt(r, 'late', 0.15) + cnt(r, 'qual', 0.1) + cnt(r, 'clean', 0.5) +
        '<td class="r">' + (r.rep ? n(r.rep) : '&ndash;') + '</td><td>' + esc(r.top) + '</td></tr>';
    }).join('') + '</tbody>';
    var none = D.filter(function (r) { return !r.visited; }).sort(function (a, b) { return b.total - a.total; });
    var low = D.filter(function (r) { return r.visited; }).sort(function (a, b) { return a.cov - b.cov; })[0];
    var vis = D.filter(function (r) { return r.visited >= 10; });
    var ns = vis.slice().sort(function (a, b) { return b.nostock / b.visited - a.nostock / a.visited || b.visited - a.visited; })[0];
    var lt = vis.slice().sort(function (a, b) { return b.late / b.visited - a.late / a.visited || b.visited - a.visited; })[0];
    var qq = vis.slice().sort(function (a, b) { return b.qual / b.visited - a.qual / a.visited || b.visited - a.visited; })[0];
    $('insights').innerHTML =
      '<div class="card insight"><small>' + (none.length ? 'No school visited yet' : 'Fewest schools visited') + '</small>' + (none.length ? none.map(function (r) { return '<b>' + esc(r.d) + '</b> (' + n(r.total) + ' schools)'; }).join(', ') : low ? '<b>' + esc(low.d) + '</b>, ' + n(low.visited) + ' of ' + n(low.total) + ' schools' : '') + '</div>' +
      '<div class="card insight"><small>Most often no milk in stock</small>' + (!ns ? 'Too few visits (10 or more needed)' : ns.nostock ? '<b>' + esc(ns.d) + '</b>, ' + n(ns.nostock) + ' of ' + n(ns.visited) + ' schools visited' : 'Milk in stock at every school here') + '</div>' +
      '<div class="card insight"><small>Most often milk delivered late</small>' + (!lt ? 'Too few visits (10 or more needed)' : lt.late ? '<b>' + esc(lt.d) + '</b>, ' + n(lt.late) + ' of ' + n(lt.visited) + ' schools visited' : 'Milk on time at every school here') + '</div>' +
      '<div class="card insight"><small>Most often milk quality not satisfactory</small>' + (!qq ? 'Too few visits (10 or more needed)' : qq.qual ? '<b>' + esc(qq.d) + '</b>, ' + n(qq.qual) + ' of ' + n(qq.visited) + ' schools visited' : 'Quality fine at every school here') + '</div>';
  }

  /* Map */
  var map = L.map('map', { preferCanvas: true, minZoom: 5, maxZoom: 18, zoomSnap: 0.5 });
  map.attributionControl.setPrefix(false); // No "Leaflet" or flag in the corner, only the basemap's credit.
  var tileBase = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_';
  L.tileLayer(tileBase + 'Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', attribution: 'Basemap &copy; Esri, HERE, Garmin, OpenStreetMap contributors' }).addTo(map);
  L.tileLayer(tileBase + 'Reference/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', pane: 'overlayPane' }).addTo(map);
  var css = getComputedStyle($('psrpFindings'));
  var C = { good: css.getPropertyValue('--good').trim(), crit: css.getPropertyValue('--crit').trim(), surf: css.getPropertyValue('--surface').trim(), ink: css.getPropertyValue('--ink').trim(), deep: css.getPropertyValue('--deep').trim(), letter: css.getPropertyValue('--letter').trim() };
  var renderer = L.canvas({ padding: 0.3, tolerance: 5 });
  var pins = L.layerGroup().addTo(map);
  var ring = null, focusPin = null, drawn = {};
  var PUNJAB = [[27.7, 69.3], [34.1, 75.5]];
  function radius() { var z = map.getZoom() || 7; return z < 7 ? 3 : z < 8 ? 3.5 : z < 10 ? 4.5 : z < 12 ? 6 : 7.5; }
  // In an SMU letter, visited again, visited once or not visited, in the dates chosen.
  function status(s) { return s.r.length ? 'r' : s.v.length > 1 ? 'a' : s.V ? 'v' : 'n'; }
  function makeMarker(s) {
    var st = status(s), r = radius();
    var opt = st === 'r' ? { radius: r + 1, renderer: renderer, stroke: true, color: C.surf, weight: 1.2, fillColor: C.letter, fillOpacity: 1 }
      : st === 'a' ? { radius: r + 1, renderer: renderer, stroke: true, color: C.good, weight: 2.2, fillColor: C.deep, fillOpacity: 1 }
      : st === 'v' ? { radius: r, renderer: renderer, stroke: true, color: C.surf, weight: 0.8, fillColor: C.good, fillOpacity: 1 }
      : { radius: r, renderer: renderer, stroke: true, color: C.crit, weight: 1.8, fillColor: C.surf, fillOpacity: 1 };
    var m = L.circleMarker([s.la, s.lo], opt);
    m.bindTooltip(function () {
      return esc(s.n) + (s.V ? ' (' + (s.v.length > 1 ? pl(s.v.length, 'visit', 'visits') + ', latest ' : 'visited ') + dl(s.V.dt) + (s.f.length ? ', ' + pl(s.f.length, 'issue', 'issues') : ', no issues') + ')' : ' (not visited' + (s.av.length ? ' in the dates chosen' : '') + ')') + (st === 'r' ? ' · in an SMU letter' : '');
    }, { direction: 'top', offset: [0, -4] });
    m.on('click', function () { openSchool(s, false); });
    return m;
  }
  function matchesFilter(s) {
    var f = state.filter, v = s.V;
    if (!f) return true;
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 'f:') return s.f.indexOf(id) >= 0;
    if (k === 'g:') return !!v && v.rt === id;
    if (k === 't:') return !!s.ts[id];
    if (k === 'c:') return !!s.tc[id];
    if (f === 'fu') return s.r.some(function (r) { return r.fu && s.v.some(function (x) { return x.dt === r.fu; }); });
    if (f === 'rv') return s.v.length > 1;
    return true;
  }
  function filterLabel(f) {
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 'f:') return FI[id].label;
    if (k === 'g:') return 'Rated ' + RTG[id].label.toLowerCase();
    if (k === 't:') return 'Response: ' + RTI[id].label;
    if (k === 'c:') return 'Reported: ' + TPI[id].label;
    if (f === 'fu') return 'Visited again after the response';
    if (f === 'rv') return 'Visited ' + twice();
    return '';
  }
  function matchesMap(s) {
    var st = status(s);
    if (state.show === 'v' && !s.V) return false;
    // Every school visited more than once in the dates chosen. One in an SMU letter keeps its violet pin.
    if (state.show === 'a' && s.v.length < 2) return false;
    if (state.show === 'r' && st !== 'r') return false;
    if (state.show === 'n' && st !== 'n') return false;
    return matchesFilter(s);
  }
  function renderMap(fit) {
    pins.clearLayers();
    drawn = {};
    var list = scoped().filter(matchesMap);
    var cnt = { v: 0, a: 0, r: 0, n: 0 }, unmapped = 0, pts = [], visitIds = [];
    var order = { n: 0, v: 1, a: 2, r: 3 };
    list.sort(function (a, b) { return order[status(a)] - order[status(b)]; });
    list.forEach(function (s) {
      if (s.la === null || s.la === undefined) { unmapped++; return; }
      cnt[status(s)]++; pins.addLayer(makeMarker(s)); drawn[s.k] = 1; pts.push([s.la, s.lo]);
      // The visit the school's findings come from: its latest in the chosen dates.
      if (s.V) visitIds.push(s.V.id);
    });
    // As on the other pages: only a chosen finding or response can be downloaded, and the file
    // holds exactly the visited schools on the map, one row each.
    $('map-download').hidden = !(state.filter && visitIds.length);
    if (state.filter) {
      $('dl-ids').value = visitIds.join(',');
      $('dl-issue').value = filterLabel(state.filter);
      $('dl-label').textContent = 'Download ' + pl(visitIds.length, 'school', 'schools');
    }
    fitSearchHint(); // The button takes room from the search box, or gives it back.
    var label = state.filter ? ' <span class="lg-n">with "' + esc(filterLabel(state.filter)) + '"</span>' : '';
    var keep = function (c) { return !state.filter || c > 0; };
    var show = state.show;
    $('legend').innerHTML = ((show === 'all' || show === 'v') && keep(cnt.v) ? '<div class="lg"><span class="pin v"></span>Visited once <span class="lg-n">' + n(cnt.v) + '</span></div>' : '') +
      ((show === 'all' || show === 'v' || show === 'a') && keep(cnt.a) ? '<div class="lg"><span class="pin a"></span>Visited again <span class="lg-n">' + n(cnt.a) + '</span></div>' : '') +
      (show !== 'n' && (show !== 'a' || cnt.r) && keep(cnt.r) ? '<div class="lg"><span class="pin r"></span>In an SMU letter to the department <span class="lg-n">' + n(cnt.r) + '</span></div>' : '') +
      ((show === 'all' || show === 'n') && keep(cnt.n) ? '<div class="lg"><span class="pin n"></span>Not visited <span class="lg-n">' + n(cnt.n) + '</span></div>' : '') + (label ? '<div class="lg">' + label + '</div>' : '');
    $('map-note').textContent = (unmapped ? pl(unmapped, 'school', 'schools') + ' in this view ' + (unmapped === 1 ? 'has' : 'have') + ' no map location, so ' + (unmapped === 1 ? 'it is' : 'they are') + ' not on the map. Search finds ' + (unmapped === 1 ? 'it' : 'them') + '. ' : '') +
      (state.filter ? (pts.length || unmapped ? '' : 'No school in this view matches "' + filterLabel(state.filter) + '".') : '');
    if (fit) {
      var zone = pts.length ? pts : scoped().filter(function (s) { return s.la !== null; }).map(function (s) { return [s.la, s.lo]; });
      if (zone.length && (state.district || state.filter)) map.fitBounds(L.latLngBounds(zone).pad(0.08), { maxZoom: 12 });
      else map.fitBounds(PUNJAB);
    }
    if (state.sel) placeRing(ALL.filter(function (x) { return x.k === state.sel; })[0] || null);
  }
  map.on('zoomend', function () { if (loaded) renderMap(false); });
  // The school opened always shows inside its ring, even when the map's choices hide its pin.
  function placeRing(s) {
    if (ring) { map.removeLayer(ring); ring = null; }
    if (focusPin) { map.removeLayer(focusPin); focusPin = null; }
    if (s && s.la !== null && s.la !== undefined) {
      if (!drawn[s.k]) focusPin = makeMarker(s).addTo(map);
      ring = L.circleMarker([s.la, s.lo], { radius: 13, color: C.ink, weight: 2.5, fill: false, interactive: false, renderer: renderer }).addTo(map);
    }
  }
  function syncSeg() { document.querySelectorAll('#mapsec .seg button').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.show === state.show)); }); }

  /* School panel */
  function yesNo(val, goodWhen) {
    if (val === null || val === undefined) return '<span class="na">Not recorded</span>';
    var good = val === goodWhen;
    return '<span class="' + (good ? 'ok' : 'bad') + '">' + (good ? '✓ ' : '✗ ') + (val === 1 ? 'Yes' : 'No') + '</span>';
  }
  function num_(x) { return x === null || x === undefined ? '<span class="na">Not recorded</span>' : n(x); }
  function frac(p, t, warn) {
    if (t === null || t === undefined || p === null || p === undefined) return '<span class="na">Not recorded</span>';
    var r = t ? Math.min(p, t) / t : null;
    return '<span class="' + (r !== null && r < warn ? 'bad' : '') + '">' + n(p) + ' of ' + n(t) + (r !== null ? ' (' + Math.round(r * 100) + '%)' : '') + '</span>';
  }
  function rowsTable(list) { return '<table class="rt"><tbody>' + list.map(function (r) { return '<tr><td>' + r[0] + '</td><td>' + r[1] + '</td></tr>'; }).join('') + '</tbody></table>'; }
  function milkRows(v) {
    var d = dos(v), g = gapBad(v);
    return rowsTable([
      ['Milk packs received so far', num_(v.rc)], ['Given out to students', num_(v.dg)], ['Expired', v.ex === null ? num_(null) : '<span class="' + (v.ex > 0 ? 'bad' : '') + '">' + n(v.ex) + '</span>'],
      ['In stock today', v.sk === null ? num_(null) : '<span class="' + (v.sk === 0 ? 'bad' : '') + '">' + n(v.sk) + (d !== null ? ' (' + (d === 0 ? 'none left' : d < 1 ? 'under a day' : Math.round(d) + ' days') + ')' : '') + '</span>'],
      ['Stock record adds up', g === null ? '<span class="na">Not recorded</span>' : g ? '<span class="bad">✗ Off by ' + n(Math.abs(v.rc - v.dg - v.ex - v.sk)) + ' packs</span>' : '<span class="ok">✓ Yes</span>'],
      ['Stock expiry date', v.xd ? dl(v.xd) : '<span class="na">Not recorded</span>'],
      ['Milk delivered on time', yesNo(v.ot, 1)], ['Milk arrives every', v.lag === null ? '<span class="na">Not recorded</span>' : pl(v.lag, 'day', 'days')],
      ['Milk quality satisfactory', yesNo(v.ql, 1)], ['Students fell ill after the milk', yesNo(v.hm, 0)], ['Expired stock received', yesNo(v.ers, 0)]
    ]);
  }
  function schoolRows(v) {
    return rowsTable([
      ['School open', yesNo(v.op, 1)], ['Days open in a week', num_(v.dw)], ['Students enrolled', num_(v.en)], ['Students before the programme', num_(v.bf)],
      ['Students present', frac(v.sp, v.en, 0.75)], ['Entitled to a milk pack', num_(v.me)], ['Staff present', v.st === null || v.st === undefined ? num_(null) : v.sx === null ? '<span class="na">Not clear (' + (v.sxbad ? n(v.sxbad) + ' absent of ' + n(v.st) : 'not recorded') + ')</span>' : frac(v.st - v.sx, v.st, 0.75)]
    ]);
  }
  function visitBlock(v) {
    var f = FIND.filter(function (x) { return x.f(v); });
    var h = '<div class="p-sec"><h3>' + (f.length ? pl(f.length, 'issue', 'issues') + ' found' : 'No issues found') + '</h3>' + (f.length ? '<div class="chips">' + f.map(function (x) { return '<span class="chip">' + esc(x.label) + '</span>'; }).join('') + '</div>' : '') + '</div>';
    if (v.op === 0) return h + '<p class="note">The school was closed when the field team arrived, so nothing else was checked.</p>';
    h += '<div class="p-sec"><h3>Milk at the school</h3>' + milkRows(v) + '</div>';
    h += '<div class="p-sec"><h3>The school on the visit day</h3>' + schoolRows(v) + '</div>';
    if (v.ph.length) h += '<div class="p-sec"><h3>Photos from the visit</h3><div class="photos">' + v.ph.map(function (id, i) { return '<a href="https://drive.google.com/open?id=' + encodeURIComponent(id) + '" target="_blank" rel="noopener noreferrer">Photo ' + (i + 1) + '</a>'; }).join('') + '</div></div>';
    return h;
  }
  function renderHint() {
    $('panel').innerHTML = '<div class="panel-hint"><h3>School report</h3><p>Click a pin on the map, or search above, to see the school\'s report here.</p><ul>' +
      '<li><b>Green pin:</b> visited by SMU. You will see the milk stock, delivery, quality and the state of the school.</li>' +
      '<li><b>Dark pin with a green ring:</b> visited again. You will also see each earlier visit.</li>' +
      '<li><b>Violet pin:</b> named in an SMU letter to the department. You will also see the issues raised and the department\'s response.</li>' +
      '<li><b>Red ring:</b> in the department file, not visited yet.</li></ul></div>';
  }
  function openSchool(s, focus, quiet) {
    state.sel = s.k; placeRing(s);
    if (focus && s.la !== null && s.la !== undefined) map.setView([s.la, s.lo], Math.max(map.getZoom(), 12));
    var v = s.V;
    var h = '<div class="p-top"><div><div class="p-name">' + esc(s.n) + '</div><div class="p-meta">EMIS ' + esc(s.k) + ' &middot; ' + esc(s.d) + (s.th ? ' &middot; ' + esc(s.th) + ' tehsil' : '') + '</div></div><button type="button" class="x" id="p-close" aria-label="Close report">×</button></div>';
    h += '<div class="badges">' + (v ? '<span class="badge b-good">● Visited ' + dl(v.dt) + '</span>' + (s.v.length > 1 ? '<span class="badge b-info">Visited ' + (s.v.length === 2 ? 'twice' : n(s.v.length) + ' times') + '</span>' : '') + (v.rt ? '<span class="badge ' + (v.rt === 'S' ? 'b-good' : v.rt === 'A' ? 'b-warn' : 'b-crit') + '">Rated ' + RTG[v.rt].label.toLowerCase() + '</span>' : '') + (v.ag ? '<span class="badge b-mute">By ' + (v.ag === 'dfp' ? 'district focal person' : 'field agent') + '</span>' : '')
      : s.av.length ? '<span class="badge b-mute">Not visited in the dates chosen</span>' : '<span class="badge b-crit">○ Not visited</span>') + (s.r.length ? '<span class="badge b-info">In ' + (s.r.length > 1 ? n(s.r.length) + ' SMU letters' : 'an SMU letter') + '</span>' : '') + '</div>';
    if (s.la === null || s.la === undefined) h += '<p class="note">This school has no map location, so it has no pin.</p>';
    if (!v && s.av.length) h += '<p class="note">Visited ' + andList(s.av.map(function (x) { return dl(x.dt); })) + ', outside the dates chosen in the filter bar.</p>';
    if (s.r.length) {
      h += '<div class="p-sec"><h3>Issues SMU reported and the department\'s response</h3>' + s.r.slice().reverse().map(function (r) {
        var f = followUp({ r: r, s: s });
        return '<div class="rep"><div class="rep-h">SMU letter of ' + dl(r.ld) + (r.tv ? ', after the visit of ' + dl(r.tv) : '') + '<br>' + (r.rd ? 'Response dated ' + dl(r.rd) + (r.ld ? ', ' + pl(days(r.ld, r.rd), 'day', 'days') + ' later' : '') : r.x ? 'Response not dated' : 'No response on file') + '</div>' + reportBody(r) +
          (f ? '<p class="note"><b>Visited again ' + dl(r.fu) + ':</b> ' + f.res.map(function (q) { return esc(q.it.i) + ' &ndash; ' + (q.seen === null ? 'not checked by the form' : q.seen ? '<span class="bad">seen again (' + esc(TPI[q.it.c].what(f.v)) + ')</span>' : '<span class="ok">not seen again</span>'); }).join('; ') + '.</p>' : '') + '</div>';
      }).join('') + '</div>';
    }
    if (v) {
      h += '<div class="p-sec"><h3 style="margin-top:4px">Visit of ' + dl(v.dt) + '</h3></div>' + visitBlock(v);
      s.v.slice(0, -1).reverse().forEach(function (e) { h += '<details class="visit"><summary>Earlier visit, ' + dl(e.dt) + '</summary>' + visitBlock(e) + '</details>'; });
    } else if (!s.r.length && !s.av.length) h += '<p class="note">No SMU visit report is on file for this school.</p>';
    else if (!s.av.length) h += '<p class="note">The visit behind this letter is not in the monitoring data.</p>';
    var panel = $('panel');
    panel.innerHTML = h; panel.scrollTop = 0;
    $('p-close').addEventListener('click', function () { state.sel = null; placeRing(null); renderHint(); });
    if (!quiet && page.clientWidth <= 1100) panel.scrollIntoView({ block: 'start' });
  }

  /* Page */
  function renderAll(fit) {
    var list = scoped(), o = summarise(list);
    $('f-clear').hidden = !state.district && fullRange();
    if (META.minKey) { $('f-from').value = keyToISO(state.from); $('f-to').value = keyToISO(state.to); }
    nameForPdf();
    renderFlow(o); renderImpact(o); renderIssues(o); renderSupply(o); renderChanged(o, list); renderRevisit(list); renderDistricts(); renderMap(fit);
    if (state.sel) {
      var s = ALL.filter(function (x) { return x.k === state.sel; })[0];
      if (s && list.indexOf(s) < 0) { state.sel = null; placeRing(null); renderHint(); } else if (s) openSchool(s, false, true);
    }
  }
  // What the page shows, for the name of the PDF made with Export to PDF ("Kot Addu, visits from ...").
  function nameForPdf() {
    var place = state.district ? state.district : 'all districts', dates = !META.minKey || fullRange() ? '' : ', visits from ' + rangeText();
    $('pdfExport').setAttribute('data-pdf-what', place + dates);
  }
  // Tells the records table below to show the same district and dates.
  function filtersChanged() {
    document.dispatchEvent(new CustomEvent('psrp:filters', { detail: {
      district: state.district,
      from: !META.minKey || state.from === META.minKey ? '' : keyToISO(state.from),
      to: !META.maxKey || state.to === META.maxKey ? '' : keyToISO(state.to)
    } }));
  }
  function fillDistricts() {
    var names = Array.from(new Set(ALL.map(function (s) { return s.d; }).concat(EXTRA.map(function (r) { return r.d; })))).sort();
    if (state.district && names.indexOf(state.district) < 0) names.push(state.district);
    $('f-district').innerHTML = '<option value="">All districts</option>' + names.map(function (d) { return '<option' + (d === state.district ? ' selected' : '') + '>' + esc(d) + '</option>'; }).join('');
  }
  function setDistrict(d) {
    state.district = d; state.all = false; state.fuAll = false;
    $('f-district').value = d;
    if (loaded) renderAll(true);
    filtersChanged();
  }
  function setRange(a, b) {
    a = Math.max(a || META.minKey, META.minKey); b = Math.min(b || META.maxKey, META.maxKey);
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
  $('f-from').addEventListener('change', function () { if (loaded && META.minKey && usableDate(this.value)) setRange(this.value ? isoToKey(this.value) : META.minKey, state.to); });
  $('f-to').addEventListener('change', function () { if (loaded && META.minKey && usableDate(this.value)) setRange(state.from, this.value ? isoToKey(this.value) : META.maxKey); });
  $('f-clear').addEventListener('click', function () {
    state.district = ''; state.from = META.minKey; state.to = META.maxKey; state.all = false; state.fuAll = false;
    fillDistricts();
    if (loaded) { derive(); renderAll(true); }
    filtersChanged();
  });
  $('f-find').addEventListener('change', function () { state.filter = this.value; if (state.filter) { state.show = 'all'; syncSeg(); } renderMap(true); });
  $('fu-more').addEventListener('click', function () { state.fuAll = !state.fuAll; renderChanged(summarise(scoped()), scoped()); if (!state.fuAll) $('fu-t').scrollIntoView(); });
  $('showall').addEventListener('click', function () { state.all = !state.all; renderChanged(summarise(scoped()), scoped()); if (!state.all) $('acts').scrollIntoView(); });
  document.querySelector('#mapsec .seg').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    state.show = b.dataset.show;
    if (state.show === 'n') { state.filter = ''; $('f-find').value = ''; }
    syncSeg(); renderMap(false);
  });
  $('psrpFindings').addEventListener('click', function (e) {
    var t = e.target.closest('.rresp');
    if (t) { if (!t.classList.contains('na')) t.classList.toggle('open'); return; }
    t = e.target.closest('[data-open]');
    if (t) { openById(t.dataset.open); $('map').scrollIntoView({ block: 'center' }); return; }
    t = e.target.closest('[data-filter]');
    if (t) { selectFilter(t.dataset.filter); return; }
    t = e.target.closest('[data-sort]');
    if (t) { var k = t.dataset.sort; if (state.sort === k) state.dir = -state.dir; else { state.sort = k; state.dir = (k === 'd' || k === 'top' || k === 'cov') ? 1 : -1; } renderDistricts(); return; }
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
  function openById(id) { var s = ALL.filter(function (x) { return String(x.k) === String(id); })[0]; if (s) openSchool(s, true); }
  $('q').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase(), box = $('results');
    if (q.length < 2 || !loaded) { box.hidden = true; return; }
    var hits = ALL.filter(function (s) { return s.n.toLowerCase().indexOf(q) >= 0 || String(s.k).indexOf(q) >= 0; });
    hits.sort(function (a, b) { return (b.V ? 1 : 0) - (a.V ? 1 : 0); });
    box.innerHTML = hits.slice(0, 12).map(function (s) {
      return '<button type="button" data-hit="' + esc(s.k) + '">' + esc(s.n) + '<small>EMIS ' + esc(s.k) + ' &middot; ' + esc(s.d) + ' &middot; ' + (s.V ? 'Visited ' + dl(s.V.dt) : s.av.length ? 'Visited outside the dates chosen' : 'Not visited') + (s.r.length ? ', in an SMU letter' : '') + '</small></button>';
    }).join('') || '<button type="button" disabled>No school found</button>';
    box.hidden = false;
  });
  $('results').addEventListener('click', function (e) {
    var b = e.target.closest('[data-hit]'); if (!b) return;
    openById(b.dataset.hit); $('results').hidden = true; $('q').value = '';
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
  // The search box keeps the PSRP wording where it fits; on laptops, where it shares its line
  // with the Download button, it says the same thing more briefly.
  var search = $('q'), searchWords = [search.placeholder, 'School name or EMIS code'], measure = document.createElement('canvas').getContext('2d');
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
    document.documentElement.style.scrollPaddingTop = (banner.offsetHeight + (sticky ? bar.offsetHeight : 0) + 12) + 'px';
  }
  setScrollOffset();
  var offsetWatcher = new ResizeObserver(setScrollOffset);
  offsetWatcher.observe(bar);
  offsetWatcher.observe(banner);
  window.addEventListener('resize', setScrollOffset);
  new ResizeObserver(function () { map.invalidateSize(); }).observe($('map'));

  function group(label, list, key, pre) { return '<optgroup label="' + label + '">' + list.map(function (t) { return '<option value="' + key + t.id + '">' + esc((pre || '') + t.label) + '</option>'; }).join('') + '</optgroup>'; }

  function start(data) {
    ALL = data.schools; META = data.meta; EXTRA = data.extra;

    // Visits come once; the date filter picks from them. Letters and responses don't change with dates.
    ALL.forEach(function (s) {
      s.av = s.v; s.ts = {}; s.tc = {};
      s.r.forEach(function (r) { r.it.forEach(function (it) { if (it.t) { s.ts[it.t] = 1; s.tc[it.c] = 1; } }); });
    });

    fillDistricts();
    $('f-district').value = state.district;
    // Only the topics an SMU letter raised are offered, as in the table above the map.
    var topics = TOPIC.filter(function (t) { return ALL.some(function (s) { return s.tc[t.id]; }); });
    $('f-find').innerHTML = '<option value="">Any school</option><option value="fu">Visited again after the response</option><option value="rv">Visited ' + twice() + '</option>' +
      group('Found at the visit', FIND, 'f:') + group('Field team\'s rating', RATING, 'g:', 'Rated ') + (topics.length ? group('Reported to the department', topics, 'c:') : '') + group('Department response', RT, 't:', 'Response: ');

    state.from = Math.max(state.from || META.minKey, META.minKey);
    state.to = Math.min(state.to || META.maxKey, META.maxKey);
    if (state.from > state.to) { state.from = META.minKey; state.to = META.maxKey; }
    if (META.minKey) ['f-from', 'f-to'].forEach(function (id) { $(id).min = keyToISO(META.minKey); $(id).max = keyToISO(META.maxKey); });

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

  map.fitBounds(PUNJAB);
  renderHint();
  load();
  window.addEventListener('load', function () { if (!loaded) { map.invalidateSize(); map.fitBounds(PUNJAB); } });
})();
</script>
