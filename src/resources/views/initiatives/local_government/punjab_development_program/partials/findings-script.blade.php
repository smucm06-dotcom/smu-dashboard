<script>
// Everything above the records table, and the cards in the banner, is worked out here from one
// download, so changing a filter is instant and does not reload the page. This is the Punjab Development
// Program design's own code, inside the page frame the other initiative pages use.
// The date filter applies to visits, which the file dates by their SMU report. The department's answers
// to the SMU reports always count.
(function () {
  'use strict';

  var $ = function (id) { return document.getElementById(id); };
  var page = $('psrpPage');
  var META = {}, DIS = [], KEYS = [], NF = 0, LET = [], ITEMS = [], ALL = [], OI = {}, loaded = false;
  var DN = [], TOT = {}, KK = {}, LETREP = {}, FORMK = [];
  var MN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  function stOf(nf) { return nf === 0 ? 'G' : nf <= 3 ? 'S' : 'M'; }
  var GRP = [
    { g: 'public', label: 'Keeping the public safe' },
    { g: 'workers', label: 'Workers and the pace of work', also: 'pace' },
    { g: 'site', label: 'Keeping the site clean and green' }
  ];
  var STL = { G: 'No problem found', S: '1 to 3 problems', M: '4 or more problems' };
  var SORD = ['G', 'S', 'M'];
  var RT = [
    { id: 'done', label: 'Done, says the department', sub: 'Debris removed, pipes replaced, operator hired, funds released', sw: 'r-fixed', badge: 'b-good' },
    { id: 'instr', label: 'Instructions given or under way', sub: 'Contractor or staff told to act; work in progress', sw: 'r-dir', badge: 'b-info' },
    { id: 'notans', label: 'Not answered', sub: 'The response skips this point or names no action', sw: 'r-none', badge: 'b-crit' }
  ];
  var RTI = {}; RT.forEach(function (t) { RTI[t.id] = t; });
  var FIXW = { board: 'signboards put up', dust: 'dust controlled', material: 'materials stored', ppe: 'safety gear in use', debris: 'debris removed', quality: 'poor work redone', drain: 'drains cleared', slow: 'causes of delay removed', trees: 'trees protected', traffic: 'traffic managed', excav: 'sites fenced off', manhole: 'manholes covered', uniform: 'workers in uniform', other: 'other problems put right' };
  var SHORT = { manhole: 'manhole covers', board: 'signboards and barriers', excav: 'unfenced dug-up sites', traffic: 'traffic', ppe: 'workers\' safety gear', uniform: 'workers\' uniform', debris: 'debris', dust: 'dust control', material: 'loose materials', trees: 'care of trees', slow: 'pace of work' };
  var OKL = { manhole: 'Manholes covered', board: 'Signboards and barriers in place', excav: 'Dug-up sites fenced off', traffic: 'Traffic managed', ppe: 'Workers in safety gear', uniform: 'Workers in uniform', debris: 'Debris removed', dust: 'Dust controlled', material: 'Materials stored properly', trees: 'Trees and green areas protected', slow: 'Work at a normal pace' };

  /* helpers */
  function esc(s) { return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function n(x) { return Number(x).toLocaleString('en-US'); }
  function nd(x, d) { return Number(x).toLocaleString('en-US', { maximumFractionDigits: d, minimumFractionDigits: 0 }); }
  function pct(a, b) { if (!b) return 0; var p = a / b * 100; return p > 0 && p < 0.5 ? '<1' : p > 99.5 && p < 100 ? '>99' : Math.round(p); }
  function pl(c, one, many) { return n(c) + ' ' + (c === 1 ? one : many); }
  function dl(iso) { if (!iso) return ''; var p = iso.split('-'); return Number(p[2]) + ' ' + MN[Number(p[1]) - 1] + ' ' + p[0]; }
  function ml(iso) { var p = iso.split('-'); return MN[Number(p[1]) - 1] + ' ' + p[0]; }
  function days(a, b) { return Math.round((new Date(b) - new Date(a)) / 864e5); }
  function median(a) { if (!a.length) return null; a = a.slice().sort(function (x, y) { return x - y; }); var m = Math.floor(a.length / 2); return a.length % 2 ? a[m] : Math.round((a[m - 1] + a[m]) / 2); }
  function andList(a) { return a.length < 2 ? a.join('') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1]; }
  function range(a, b) { if (!a || !b) return ''; return ml(a) === ml(b) ? ml(a) : (a.slice(0, 4) === b.slice(0, 4) ? MN[Number(a.split('-')[1]) - 1] : ml(a)) + ' to ' + ml(b); }
  function months(isos) {
    var ks = {}; isos.forEach(function (d) { ks[Number(d.slice(0, 4)) * 12 + Number(d.slice(5, 7)) - 1] = 1; });
    var a = Object.keys(ks).map(Number).sort(function (x, y) { return x - y; }), runs = [];
    a.forEach(function (k) { var r = runs[runs.length - 1]; if (r && k === r[1] + 1) r[1] = k; else runs.push([k, k]); });
    function lab(k, yr) { return MN[k % 12] + (yr ? ' ' + Math.floor(k / 12) : ''); }
    return andList(runs.map(function (r) { return r[0] === r[1] ? lab(r[0], 1) : lab(r[0], Math.floor(r[0] / 12) !== Math.floor(r[1] / 12)) + ' to ' + lab(r[1], 1); }));
  }
  function gapText(d) { return d >= 60 ? 'about ' + Math.round(d / 30) + ' months' : pl(d, 'day', 'days'); }
  function clabel(c) { return c.n + (c.ml ? '' : ' (not in the PDP list)'); }
  function has(v, i) { return v.f.indexOf(i) >= 0; }
  function reportLabel(iso) { return 'SMU report of ' + dl(iso); }
  function hasPin(c) { return c.la !== null && c.la !== undefined; }

  /* dates: visits on or after From and on or before To count */
  function keyToISO(k) { var s = String(k); return s.slice(0, 4) + '-' + s.slice(4, 6) + '-' + s.slice(6, 8); }
  function isoToKey(s) { return s ? Number(s.replace(/-/g, '')) : 0; }
  var DK = { min: 0, max: 0 };
  function fullRange() { return state.from === DK.min && state.to === DK.max; }
  function rangeText() { return dl(keyToISO(state.from)) + ' to ' + dl(keyToISO(state.to)); }
  function notYet() { return fullRange() ? 'not visited yet' : 'not visited in these dates'; }

  var state = { district: $('f-district').value, from: isoToKey($('f-from').value), to: isoToKey($('f-to').value), sort: 'visited', dir: -1, show: 'all', filter: '', sel: null };
  function inScope(d) { return !state.district || d === state.district; }
  // Every PDP city in the district chosen, and the towns outside the list that have a visit in the dates.
  function scoped() { return ALL.filter(function (c) { return inScope(c.dn) && (c.ml || c.v.length); }); }
  function where() { return state.district ? ' in ' + state.district : ''; }

  /* Each city's visits in the dates chosen */
  function derive() {
    ALL.forEach(function (c) {
      c.v = c.av.filter(function (v) { var k = isoToKey(v.dt); return k >= state.from && k <= state.to; });
      c.V = c.v.length ? c.v[c.v.length - 1] : null;
      c.un = !c.v.length;
    });
  }

  /* summary of a selection */
  function summarise(list) {
    var o = { cities: 0, listed: 0, unlisted: 0, visits: 0, first: null, last: null, st: { G: 0, S: 0, M: 0 }, find: {}, base: 0, probs: 0, pop: 0, reps: {} };
    FORMK.forEach(function (i) { o.find[i] = 0; });
    var ds = DN.filter(inScope);
    o.total = ds.reduce(function (a, d) { return a + TOT[d]; }, 0);
    o.nd = ds.filter(function (d) { return TOT[d]; }).length;
    o.sc = META.scope.map(function () { return 0; });
    list.forEach(function (c) {
      if (c.sc) c.sc.forEach(function (x, i) { o.sc[i] += x; });
      if (c.un) return;
      if (c.ml) o.listed++; else o.unlisted++;
      o.visits += c.v.length;
      c.v.forEach(function (v) { o.probs += v.f.length; o.reps[v.dt] = 1; });
      if (!o.first || c.v[0].dt < o.first) o.first = c.v[0].dt;
      if (!o.last || c.V.dt > o.last) o.last = c.V.dt;
      o.base++; o.st[c.V.st]++;
      c.V.f.forEach(function (i) { if (o.find[i] !== undefined) o.find[i]++; });
    });
    o.repList = Object.keys(o.reps).sort();
    o.lets = LET.filter(function (l) { return o.reps[l.rep]; });
    o.noResp = o.repList.filter(function (d) { return LETREP[d] === undefined; });
    /* letter problems: every SMU report counts, whatever the dates */
    var its = ITEMS.filter(function (it) { return inScope(it.c.dn); });
    o.its = its; o.rt = {}; RT.forEach(function (t) { o.rt[t.id] = 0; });
    o.top = {}; KEYS.forEach(function (k, i) { o.top[i] = { n: 0, t: {} }; RT.forEach(function (t) { o.top[i].t[t.id] = 0; }); });
    o.doneK = {};
    its.forEach(function (it) { o.rt[it.cl]++; o.top[it.key].n++; o.top[it.key].t[it.cl]++; if (it.cl === 'done') o.doneK[it.key] = (o.doneK[it.key] || 0) + 1; });
    /* checked again: each problem at a visit, looked at again at the next visit to the same city */
    o.rcI = {}; FORMK.forEach(function (i) { o.rcI[i] = { g: 0, s: 0 }; });
    o.rcGone = 0; o.rcChk = 0; o.rcGaps = []; o.rv = [];
    list.forEach(function (c) {
      if (c.v.length > 1) o.rv.push(c);
      for (var j = 0; j + 1 < c.v.length; j++) {
        var a = c.v[j], b = c.v[j + 1];
        if (a.f.length) o.rcGaps.push(days(a.dt, b.dt));
        a.f.forEach(function (i) { o.rcChk++; if (has(b, i)) o.rcI[i].s++; else { o.rcI[i].g++; o.rcGone++; } });
      }
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
    var pop = o.pop >= 1e6 ? nd(o.pop / 1e6, 2) + ' million people' : n(o.pop) + ' people', sub;
    if (state.district) {
      var names = ALL.filter(function (c) { return c.ml && c.dn === state.district; }).map(function (c) { return c.n; }).sort();
      sub = 'In ' + state.district + ' district: ' + andList(names) + '. For ' + pop;
    } else if (o.total) {
      var per = {}; ALL.forEach(function (c) { if (c.ml && c.dn) per[c.dn] = (per[c.dn] || 0) + 1; });
      var mx = Math.max.apply(null, Object.keys(per).map(function (d) { return per[d]; }));
      var big = Object.keys(per).filter(function (d) { return per[d] === mx; }).sort();
      sub = 'Not districts: these are towns in ' + n(o.nd) + ' of Punjab\'s 41 districts. ' + andList(big) + (big.length === 1 ? ' has ' : ' have ') + mx + ' each. For ' + pop;
    } else sub = 'None yet';
    setCard('c-total', o.total, sub);
    setCard('c-visited', o.listed, o.base ? pct(o.listed, o.total) + '% of the cities, in ' + pl(o.visits, 'site visit', 'site visits') + ' from ' + range(o.first, o.last) + (o.unlisted ? '. Also ' + pl(o.unlisted, 'town', 'towns') + ' not in the PDP list' : '') : 'None yet');
    setCard('c-reports', o.repList.length, o.repList.length ? 'With ' + pl(o.probs, 'problem', 'problems') + ' found at the sites, ' + range(o.repList[0], o.repList[o.repList.length - 1]) : 'No SMU visit here yet');
    var gaps = o.lets.filter(function (l) { return l.days !== null; }).map(function (l) { return n(l.days); });
    setCard('c-replied', o.lets.length, o.repList.length ? 'Of ' + pl(o.repList.length, 'SMU report', 'SMU reports') + (o.lets.length && gaps.length ? ', sent ' + andList(gaps) + ' days after the report' : '') : '');
  }

  /* impact cards */
  function renderImpact(o) {
    var fx = Object.keys(o.doneK).sort(function (a, b) { return o.doneK[b] - o.doneK[a]; }).slice(0, 3).map(function (i) { return FIXW[KEYS[i].k]; }).filter(Boolean);
    $('imp-1').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (o.its.length ? n(o.rt.done) + ' of ' + n(o.its.length) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (o.its.length ? 'problems in SMU\'s reports were put right, says the department' : 'No answered SMU report covers this selection') + '</b><small>' + (fx.length ? 'Mostly ' + andList(fx) + '.' : '') + '</small></div>';
    var rows = FORMK.map(function (i) { var a = o.rcI[i]; return { i: i, gone: a.g, t: a.g + a.s }; }).filter(function (x) { return x.t >= 5; });
    var best = rows.slice().sort(function (a, b) { return b.gone / b.t - a.gone / a.t || b.t - a.t; })[0];
    var worst = rows.slice().sort(function (a, b) { return (b.t - b.gone) - (a.t - a.gone); })[0];
    $('imp-2').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (o.rcChk ? n(o.rcGone) + ' of ' + n(o.rcChk) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (o.rcChk ? 'problems SMU found were gone when it went back to the city' : 'No city with problems visited again here yet') + '</b><small>' +
      (o.rcChk ? (best ? 'Put right most often: ' + SHORT[KEYS[best.i].k] + '. ' : '') + (worst ? 'Still there most often: ' + SHORT[KEYS[worst.i].k] + '.' : '') : '') + '</small></div>';
  }

  /* scope of work */
  function renderPlan(o, list) {
    var s = o.sc, cities = list.filter(function (c) { return c.ml; }).length;
    o.pop = s[0];
    var T = [
      { v: s[1], u: 'km', l: 'Sewer lines', e: '' },
      { v: s[2], u: 'km', l: 'Storm drains', e: '' },
      { v: s[5] + s[6], u: '', l: 'Disposal stations', e: n(s[5]) + ' new, ' + n(s[6]) + ' rehabilitated' },
      { v: s[7], u: 'MGD', l: 'Wastewater treatment', e: 'Million gallons a day' },
      { v: s[3], u: '', l: 'Underground water tanks', e: s[3] ? nd(s[4], 1) + ' million gallons' : '' },
      { v: s[8], u: 'km', l: 'Roads and streets paved', e: '' },
      { v: s[11], u: '', l: 'Street lights', e: '' },
      { v: s[10], u: 'kW', l: 'Solar power', e: '' },
      { v: s[9], u: '', l: 'Machinery', e: '' }
    ];
    $('plan-sub').textContent = 'The department\'s scope of work for ' + (state.district ? 'the PDP ' + (cities === 1 ? 'city' : 'cities') + ' in ' + state.district : 'all the PDP cities') + '. Click a city on the map to see its own.';
    $('plan-g').innerHTML = T.map(function (t) { return '<div><b>' + n(Math.round(t.v)) + (t.u ? '<small>' + t.u + '</small>' : '') + '</b><span>' + t.l + '</span>' + (t.e ? '<em>' + esc(t.e) + '</em>' : '') + '</div>'; }).join('');
    // The design's note, shortened as asked. The date is the department's general response's own.
    var L0 = LET.filter(function (l) { return l.general; })[0];
    $('plan-note').innerHTML = !o.total ? '' : '<b>Work under way so far is mostly sewerage</b>, so SMU\'s checks are on the sewerage sites.' +
      (L0 && L0.rd ? ' In its response of ' + dl(L0.rd) + ', the department said the most critical work is due by 30 June 2026 and the rest by 30 June 2027.' : '');
  }

  /* issues */
  function renderIssues(o) {
    var vis = o.base || 1;
    $('st-bar').innerHTML = SORD.map(function (s) { return o.st[s] ? '<i class="seg-' + s + '" style="width:' + o.st[s] / vis * 100 + '%" data-tip="<b>' + esc(STL[s]) + '</b>' + pl(o.st[s], 'city', 'cities') + ', ' + pct(o.st[s], vis) + '%"></i>' : ''; }).join('');
    $('st-bar').setAttribute('aria-label', SORD.map(function (s) { return STL[s] + ' ' + o.st[s]; }).join(', '));
    var STS = { G: 'All ' + NF + ' checks were fine', S: 'Out of the ' + NF + ' checks', M: 'Out of the ' + NF + ' checks' };
    $('st-outs').innerHTML = SORD.map(function (s) {
      return '<button type="button" class="out" data-filter="s:' + s + '"><div class="out-num">' + n(o.st[s]) + ' <span>' + pct(o.st[s], o.base) + '%</span></div><div class="out-lbl"><span class="sw st-' + s + '"></span>' + esc(STL[s]) + '</div><div class="out-sub">' + esc(STS[s]) + '</div></button>';
    }).join('');
    $('tiles').innerHTML = GRP.map(function (g) {
      var ks = FORMK.filter(function (i) { return KEYS[i].g === g.g || KEYS[i].g === g.also; });
      return '<div class="tgrp"><h3>' + esc(g.label) + '</h3><div class="tiles four">' + ks.map(function (i) {
        var c = o.find[i];
        return '<button type="button" class="tile" data-filter="f:' + i + '"><b>' + n(c) + '</b><span>' + esc(KEYS[i].l) + '</span><small>' + esc(KEYS[i].why) + ' &middot; ' + pct(c, o.base) + '% of ' + n(o.base) + ' visited</small></button>';
      }).join('') + '</div></div>';
    }).join('');
  }

  /* department response */
  function stack(lbl, a, tt) {
    return '<div class="stack" role="img" aria-label="' + RT.map(function (t) { return t.label + ' ' + a[t.id]; }).join(', ') + '" data-tip="<b>' + esc(lbl) + '</b>' + RT.filter(function (t) { return a[t.id]; }).map(function (t) { return esc(t.label) + ': ' + n(a[t.id]); }).join('<br>') + '">' +
      RT.map(function (t) { return a[t.id] ? '<i class="' + t.sw + '" style="width:' + a[t.id] / tt * 100 + '%"></i>' : ''; }).join('') + '</div>';
  }
  function renderChanged(o) {
    var rows = KEYS.map(function (k, i) { return { i: i, a: o.top[i] }; }).filter(function (x) { return x.a.n; }).sort(function (a, b) { return b.a.n - a.a.n; });
    var nc = {}; o.its.forEach(function (it) { nc[it.c.k] = 1; });
    $('iss-t').innerHTML = rows.length ? '<thead><tr><th>Problem reported</th><th class="r">Problems</th><th>Department\'s response</th><th class="r">Done, says the department</th></tr></thead><tbody>' + rows.map(function (x) {
      var a = x.a, tt = a.n || 1;
      return '<tr data-filter="c:' + x.i + '"><td class="lab">' + esc(KEYS[x.i].l) + '</td><td class="r">' + n(a.n) + '</td><td>' + stack(KEYS[x.i].l, a.t, tt) + '</td><td class="r' + (a.t.done / tt < 0.25 ? ' dn' : '') + '">' + n(a.t.done) + ' <span class="pct">' + pct(a.t.done, tt) + '%</span></td></tr>';
    }).join('') + '<tr class="tot"><td class="lab">All problems, in ' + pl(Object.keys(nc).length, 'city', 'cities') + '</td><td class="r">' + n(o.its.length) + '</td><td>' + stack('All problems', o.rt, o.its.length || 1) + '</td><td class="r">' + n(o.rt.done) + ' <span class="pct">' + pct(o.rt.done, o.its.length) + '%</span></td></tr></tbody>'
      : '<tbody><tr><td class="empty" style="padding:12px 18px">No answered SMU report covers this selection.</td></tr></tbody>';
    $('iss-key').innerHTML = RT.map(function (t) { return '<span data-tip="' + esc(t.sub) + '"><i class="sw ' + t.sw + '"></i>' + esc(t.label) + '</span>'; }).join('');
    /* the response letters */
    $('letters').innerHTML = LET.map(function (l, li) {
      var its = o.its.filter(function (it) { return it.li === li; });
      if (state.district && !its.length) return '';
      var cs = {}; its.forEach(function (it) { cs[it.c.k] = 1; });
      // The cities in the letter's own report, whatever dates are chosen, as the letters always count.
      var vis = ALL.filter(function (c) { return c.av.some(function (v) { return v.dt === l.rep; }); }).length;
      var h = '<div class="card pad letter"><h3>' + (l.rep ? reportLabel(l.rep) : 'SMU report') + '</h3><p class="lt-when">SMU visited ' + pl(vis, 'city', 'cities') + ' and wrote about ' + n(Object.keys(cs).length) + (state.district ? ' in ' + state.district : '') + '.' +
        (l.rd ? ' <b>Response of ' + dl(l.rd) + (l.days !== null ? ', ' + pl(l.days, 'day', 'days') + ' later' : '') + '.</b>' : '') + '</p>';
      if (l.general) {
        h += '<p class="sub" style="margin-bottom:0">One general answer for all the cities:</p><p class="rresp" title="Click to read all of it">' + esc(l.general) + '</p>';
      } else {
        var cn = Object.keys(cs).map(function (k) { return OI[k]; });
        h += '<p class="sub" style="margin-bottom:0">A separate answer for each city. Click a city to read it.</p><ul>' + cn.map(function (c) {
          var d = its.filter(function (it) { return it.c === c && it.cl === 'done'; }).length, t = its.filter(function (it) { return it.c === c; }).length;
          return '<li><button type="button" class="link" data-open="' + esc(c.k) + '">' + esc(c.n) + '</button>: ' + n(d) + ' of ' + pl(t, 'problem', 'problems') + ' done, says the department</li>';
        }).join('') + '</ul>';
      }
      return h + '</div>';
    }).join('');
    $('caveat').innerHTML = '<b>These are the department\'s own statements.</b> ' + (o.noResp.length ? pl(o.noResp.length, 'SMU report', 'SMU reports') + (state.district ? (o.noResp.length === 1 ? ' that covers ' : ' that cover ') + state.district : '') + ' (of ' + months(o.noResp) + ') ' + (o.noResp.length === 1 ? 'has' : 'have') + ' no response in the file. ' : '') +
      (META.respLast ? 'The latest response on file is dated ' + dl(META.respLast) + '. ' : '') + 'Each city\'s full response is in its report on the map.';
  }

  /* back at the same cities */
  function renderRevisit(list, o) {
    var rv = o.rv;
    $('rev-sub').textContent = rv.length ? 'SMU went back to ' + pl(rv.length, 'city', 'cities') + where() + ', in later SMU reports. The data gives each visit the date of its SMU report.' : 'No city' + where() + ' has been visited twice' + (fullRange() ? ' yet.' : ' in these dates.');
    var mx = 1, rows = FORMK.map(function (i) { var a = o.rcI[i]; mx = Math.max(mx, a.g + a.s); return { i: i, gone: a.g, s: a.s }; }).filter(function (x) { return x.gone + x.s; })
      .sort(function (a, b) { return (b.gone + b.s) - (a.gone + a.s) || b.gone - a.gone; });
    $('rc-sub').textContent = o.rcChk ? 'Each problem found at a visit, looked at again at SMU\'s next visit to the same city. The next visit came usually ' + gapText(median(o.rcGaps)) + ' later. Click a row to see those cities on the map.' : 'No city with problems visited again in this selection.';
    $('rc-rows').innerHTML = rows.length ? '<div class="rck rck-h"><span>Problem found</span><span></span><span class="num">Gone / checked again</span></div>' + rows.map(function (x) {
      var t = x.gone + x.s;
      return '<button type="button" class="rck" data-filter="g:' + x.i + '"><span>' + esc(KEYS[x.i].l) + '</span><span class="gs" style="width:' + Math.max(18, t / mx * 100) + '%" data-tip="<b>' + esc(KEYS[x.i].l) + '</b>' + n(x.gone) + ' gone, ' + n(x.s) + ' still there"><i class="g" style="width:' + x.gone / t * 100 + '%"></i><i class="s" style="width:' + x.s / t * 100 + '%"></i></span><span class="num"><b>' + n(x.gone) + '</b> of ' + n(t) + ' &middot; ' + pct(x.gone, t) + '%</span></button>';
    }).join('') : '<p class="empty">No city with problems visited again in this selection.</p>';
    /* first and latest visit */
    var f0 = 0, f1 = 0; rv.forEach(function (c) { f0 += c.v[0].f.length; f1 += c.V.f.length; });
    var vg = rv.map(function (c) { return days(c.v[0].dt, c.V.dt); });
    $('cmp-h').textContent = rv.length ? (f1 > f0 ? 'Key finding: more problems at the latest visit than at the first' : f1 < f0 ? 'Fewer problems at the latest visit than at the first' : 'First visit and latest visit') : 'First visit and latest visit';
    $('cmp-sub').textContent = rv.length ? 'At the ' + pl(rv.length, 'city', 'cities') + ' SMU visited more than once, it found ' + pl(f0, 'problem', 'problems') + ' at the first visit and ' + n(f1) + ' at the latest. The two visits were usually ' + gapText(median(vg)) + ' apart.' : '';
    $('cmp-t').innerHTML = rv.length ? '<thead><tr><th>Check</th><th class="r">Fine at the first visit</th><th class="r">Fine at the latest visit</th><th>Change in each city</th></tr></thead><tbody>' + FORMK.map(function (i) {
      var a = { n: 0, f: 0, l: 0, up: 0, same: 0, dn: 0 };
      rv.forEach(function (c) { var x = has(c.v[0], i), y = has(c.V, i); a.n++; if (!x) a.f++; if (!y) a.l++; if (x && !y) a.up++; else if (!x && y) a.dn++; else a.same++; });
      var cls = a.l > a.f ? 'up' : a.l < a.f ? 'dn' : '';
      return '<tr data-filter="v:' + i + '"><td class="lab">' + esc(OKL[KEYS[i].k]) + '</td><td class="r">' + n(a.f) + ' of ' + n(a.n) + '</td><td class="r ' + cls + '">' + n(a.l) + ' of ' + n(a.n) + '</td>' +
        '<td><div class="chg"><div class="stack" role="img" aria-label="' + a.up + ' better, ' + a.same + ' same, ' + a.dn + ' worse" data-tip="<b>' + esc(OKL[KEYS[i].k]) + '</b>' + n(a.up) + ' better, ' + n(a.same) + ' no change, ' + n(a.dn) + ' worse"><i class="s-more" style="width:' + a.up / a.n * 100 + '%"></i><i class="s-same" style="width:' + a.same / a.n * 100 + '%"></i><i class="s-less" style="width:' + a.dn / a.n * 100 + '%"></i></div><span>' + n(a.up) + ' better, ' + n(a.dn) + ' worse</span></div></td></tr>';
    }).join('') + '</tbody>' : '<tbody><tr><td class="empty" style="padding:12px 18px">No city visited twice in this selection.</td></tr></tbody>';
    /* visited again and again */
    var ag = list.filter(function (c) { return c.v.length >= 4; }).sort(function (a, b) { return b.v.length - a.v.length || a.n.localeCompare(b.n); });
    $('again-card').hidden = !ag.length;
    $('again-sub').textContent = ag.length ? pl(ag.length, 'city was', 'cities were') + ' visited four times or more. The numbers are the problems found at each visit, oldest first. Click a city to open its report.' : '';
    $('again').innerHTML = ag.map(function (c) {
      return '<button type="button" data-open="' + esc(c.k) + '"><span>' + esc(clabel(c)) + '<small>' + esc(c.dn) + ' &middot; ' + pl(c.v.length, 'visit', 'visits') + ', ' + range(c.v[0].dt, c.V.dt) + '</small></span><span class="seq">' +
        c.v.map(function (v) { return '<span class="s' + v.st + '" data-tip="<b>' + reportLabel(v.dt) + '</b>' + pl(v.f.length, 'problem', 'problems') + '">' + v.f.length + '</span>'; }).join('') + '</span></button>';
    }).join('');
  }

  /* districts */
  function summarise1(L, d) {
    var r = { d: d, total: TOT[d], visited: 0, all: 0, visits: 0, G: 0, M: 0, ppe: 0, board: 0, g: 0, t: 0, done: 0, items: 0 };
    L.forEach(function (c) {
      if (c.un || !c.ml) return;   /* towns outside the PDP list are left out of the district table */
      r.all++; r.visited++; r.visits += c.v.length;
      if (c.V.st === 'G') r.G++; if (c.V.st === 'M') r.M++;
      if (has(c.V, KK.ppe)) r.ppe++; if (has(c.V, KK.board)) r.board++;
      for (var j = 0; j + 1 < c.v.length; j++) c.v[j].f.forEach(function (i) { r.t++; if (!has(c.v[j + 1], i)) r.g++; });
    });
    ITEMS.forEach(function (it) { if (it.c.dn !== d || !it.c.ml) return; r.items++; if (it.cl === 'done') r.done++; });
    var a = r.all;
    r.cov = r.total ? r.visited / r.total : 0; r.good = a ? r.G / a : -1; r.many = a ? r.M / a : -1; r.ppeR = a ? r.ppe / a : -1; r.boardR = a ? r.board / a : -1;
    r.gone = r.t ? r.g / r.t : -1; r.fixed = r.items ? r.done / r.items : -1;
    return r;
  }
  function districtRows() { var by = {}; ALL.forEach(function (c) { (by[c.dn] = by[c.dn] || []).push(c); }); return DN.filter(function (d) { return TOT[d]; }).map(function (d) { return summarise1(by[d] || [], d); }); }
  var DCOLS = [
    { k: 'd', t: 'District' }, { k: 'total', t: 'PDP cities', r: 1 }, { k: 'visited', t: 'Visited', r: 1 }, { k: 'cov', t: 'Share visited' }, { k: 'visits', t: 'Site visits', r: 1 },
    { k: 'good', t: 'No problem found', r: 1 }, { k: 'many', t: '4 or more problems', r: 1 }, { k: 'ppeR', t: 'Workers without safety gear', r: 1 }, { k: 'boardR', t: 'No signboards or barriers', r: 1 },
    { k: 'fixed', t: 'Done, says the department', r: 1 }, { k: 'gone', t: 'Gone at the next visit', r: 1 }
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
        '<td><div class="cov"><div class="bar"><i style="width:' + Math.min(1, r.cov) * 100 + '%"></i></div><span>' + pct(r.visited, r.total) + '%</span></div></td><td class="r">' + (r.visits ? n(r.visits) : '&ndash;') + '</td>' +
        rate(r, 'good', 0.2, true) + rate(r, 'many', 0.5) + rate(r, 'ppeR', 0.5) + rate(r, 'boardR', 0.5) +
        rate(r, 'fixed', 0.25, true, r.items ? n(r.done) + ' of ' + n(r.items) + ' problems' : '') + rate(r, 'gone', 0.25, true, r.t ? n(r.g) + ' of ' + n(r.t) + ' problems' : '') + '</tr>';
    }).join('') + '</tbody>';
    var none = D.filter(function (r) { return !r.all; }).map(function (r) { return r.d; });
    var unl = ALL.filter(function (c) { return !c.ml && !c.un; });
    $('dt-sub').textContent = (none.length ? andList(none) + ' ' + (none.length === 1 ? 'has' : 'have') + ' no visit to a PDP city' + (fullRange() ? ' yet' : ' in these dates') + '. ' : '') +
      (unl.length ? andList(unl.map(function (c) { return c.n + ' (' + c.dn + ')'; })) + (unl.length === 1 ? ' is' : ' are') + ' not in the PDP list, so ' + (unl.length === 1 ? 'its visit is' : 'their visits are') + ' left out of this table. ' : '');
  }

  /* map */
  var map = L.map('map', { preferCanvas: true, minZoom: 5, maxZoom: 18, zoomSnap: 0.5 });
  map.attributionControl.setPrefix(false); // No "Leaflet" or flag in the corner, only the basemap's credit.
  var tileBase = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_';
  L.tileLayer(tileBase + 'Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', attribution: 'Basemap &copy; Esri, HERE, Garmin, OpenStreetMap contributors' }).addTo(map);
  L.tileLayer(tileBase + 'Reference/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', pane: 'overlayPane' }).addTo(map);
  var css = getComputedStyle($('psrpFindings'));
  function cv(name) { return css.getPropertyValue(name).trim(); }
  var C = { G: cv('--st-g'), S: cv('--st-s'), M: cv('--st-m'), U: cv('--st-u'), surf: cv('--surface'), ink: cv('--ink') };
  var renderer = L.canvas({ padding: 0.3, tolerance: 6 });
  var pins = L.layerGroup().addTo(map);
  var ring = null, focusPin = null, drawn = {};
  var BOUNDS = [[29.3, 70.7], [34.0, 75.4]];
  function radius() { var z = map.getZoom() || 7; return z < 7 ? 6 : z < 9 ? 8 : 10; }
  function makeMarker(c) {
    var r = radius(), m;
    if (c.un) {
      m = L.circleMarker([c.la, c.lo], { radius: Math.max(4, r - 2), renderer: renderer, stroke: true, color: C.U, weight: 2, fillColor: C.surf, fillOpacity: 1 });
      m.bindTooltip(function () { return esc(c.n) + ', ' + esc(c.dn) + ' (' + notYet() + ')'; }, { direction: 'top', offset: [0, -6] });
    } else {
      var st = c.V.st;
      m = L.circleMarker([c.la, c.lo], { radius: r, renderer: renderer, stroke: true, color: C.surf, weight: 1.5, fillColor: C[st], fillOpacity: 1 });
      m.bindTooltip(function () { return esc(c.n) + ', ' + esc(c.dn) + ' (' + STL[st].toLowerCase() + ', ' + reportLabel(c.V.dt) + ')'; }, { direction: 'top', offset: [0, -6] });
    }
    m.on('click', function () { openCity(c, false); });
    return m;
  }
  function matchesFilter(c) {
    var f = state.filter; if (!f) return true;
    var k = f.slice(0, 2), id = f.slice(2);
    if (c.un) return false;
    if (k === 's:') return c.V.st === id;
    if (k === 'f:') return has(c.V, Number(id));
    if (k === 't:') return c.r.some(function (i) { return ITEMS[i].cl === id; });
    if (k === 'c:') return c.r.some(function (i) { return ITEMS[i].key === Number(id); });
    if (k === 'g:') { for (var j = 0; j + 1 < c.v.length; j++) if (has(c.v[j], Number(id))) return true; return false; }
    if (k === 'v:') return c.v.length > 1;
    if (f === 'rv') return c.v.length > 1;
    return true;
  }
  function filterLabel(f) {
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 's:') return STL[id];
    if (k === 'f:') return KEYS[Number(id)].l;
    if (k === 't:') return 'Response: ' + RTI[id].label;
    if (k === 'c:') return 'Reported: ' + KEYS[Number(id)].l;
    if (k === 'g:') return 'Checked again: ' + KEYS[Number(id)].l;
    if (k === 'v:') return 'Visited more than once';
    if (f === 'rv') return 'Visited more than once';
    return '';
  }
  function matchesMap(c) {
    if (state.show === 'u') return !!c.un && c.ml;
    if (c.un) return false;
    if (state.show === 'r' && !c.r.length) return false;
    if (SORD.indexOf(state.show) >= 0 && c.V.st !== state.show) return false;
    return matchesFilter(c);
  }
  function renderMap(fit) {
    pins.clearLayers();
    drawn = {};
    var src = scoped();
    var list = src.filter(matchesMap), cnt = { G: 0, S: 0, M: 0 }, un = 0, unmapped = [], pts = [], visitIds = [];
    list.forEach(function (c) {
      if (!c.un) visitIds.push(c.V.id);
      if (!hasPin(c)) { unmapped.push(c); return; }
      if (c.un) un++; else cnt[c.V.st]++;
      pins.addLayer(makeMarker(c)); drawn[c.k] = 1; pts.push([c.la, c.lo]);
    });
    // As on the other pages: only a chosen finding or response can be downloaded, and the file holds the
    // cities it picks, one row each: the latest visit in the dates chosen, from our visit file.
    $('map-download').hidden = !(state.filter && state.show !== 'u' && visitIds.length);
    if (state.filter) {
      $('dl-ids').value = visitIds.join(',');
      $('dl-issue').value = filterLabel(state.filter);
      $('dl-label').textContent = 'Download ' + pl(visitIds.length, 'city', 'cities');
    }
    fitSearchHint(); // The button takes room from the search box, or gives it back.
    var lg = SORD.filter(function (k) { return cnt[k]; }).map(function (k) { return '<div class="lg"><span class="pin s' + k + '"></span>' + esc(STL[k]) + ' <span class="lg-n">' + n(cnt[k]) + '</span></div>'; }).join('');
    $('legend').innerHTML = lg + (un ? '<div class="lg"><span class="pin su"></span>PDP city, ' + notYet() + ' <span class="lg-n">' + n(un) + '</span></div>' : '') +
      (state.filter && state.show !== 'u' ? '<div class="lg"><span class="lg-n">With "' + esc(filterLabel(state.filter)) + '"</span></div>' : '') +
      (pts.length ? '' : '<div class="lg">' + (unmapped.length ? 'No pin to show: the cities are listed under the map' : 'No city to show') + '</div>');
    // Pins come from the visit file only. A city SMU has never visited has no location in any file, so it
    // is named here, and opens its report like a pin.
    unmapped.sort(function (a, b) { return a.n.localeCompare(b.n); });
    $('map-note').innerHTML = (unmapped.length ? pl(unmapped.length, 'city', 'cities') + ' in this view ' + (unmapped.length === 1 ? 'has' : 'have') + ' no location in the visit file, so ' + (unmapped.length === 1 ? 'it is' : 'they are') + ' not on the map: ' +
      andList(unmapped.map(function (c) { return '<button type="button" class="link" data-open="' + esc(c.k) + '">' + esc(c.n) + '</button>'; })) + '. ' : '') +
      'Pins are where the field team recorded SMU\'s latest visit to the city; SMU visits work sites across the city.';
    if (fit) {
      var zone = pts.length ? pts : src.filter(hasPin).map(function (c) { return [c.la, c.lo]; });
      if (zone.length && (state.district || state.filter)) map.fitBounds(L.latLngBounds(zone).pad(0.3), { maxZoom: 10 });
      else map.fitBounds(BOUNDS);
    }
    if (state.sel) placeRing(OI[state.sel] || null);
  }
  map.on('zoomend', function () { if (loaded) renderMap(false); });
  // The city opened always shows inside its ring, even when the map's choices hide its pin.
  function placeRing(c) {
    if (ring) { map.removeLayer(ring); ring = null; }
    if (focusPin) { map.removeLayer(focusPin); focusPin = null; }
    if (c && hasPin(c)) {
      if (!drawn[c.k]) focusPin = makeMarker(c).addTo(map);
      ring = L.circleMarker([c.la, c.lo], { radius: 15, color: C.ink, weight: 2.5, fill: false, interactive: false, renderer: renderer }).addTo(map);
    }
  }
  function syncSeg() { document.querySelectorAll('#mapsec .seg button').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.show === state.show)); }); }

  /* city panel */
  function renderHint() {
    $('panel').innerHTML = '<div class="panel-hint"><h3>City report</h3><p>Click a pin on the map, or search above, to see the city\'s visits here.</p><ul>' +
      '<li><span class="pin sG" style="display:inline-block;vertical-align:-1px"></span> <b>Green:</b> no problem found at the latest visit.</li>' +
      '<li><span class="pin sS" style="display:inline-block;vertical-align:-1px"></span> <b>Amber:</b> 1 to 3 of the ' + (NF || 11) + ' checks failed.</li>' +
      '<li><span class="pin sM" style="display:inline-block;vertical-align:-1px"></span> <b>Red:</b> 4 or more checks failed.</li>' +
      '<li><span class="pin su" style="display:inline-block;vertical-align:-1px"></span> <b>Hollow:</b> a PDP city SMU has not visited in the dates chosen.</li></ul></div>';
  }
  function visitBlock(v) {
    var h = '<div class="badges"><span class="badge b-st-' + v.st + '">● ' + esc(STL[v.st]) + '</span></div><div class="chips">' +
      (v.f.length ? v.f.map(function (i) { return '<span class="chip">' + esc(KEYS[i].l) + '</span>'; }).join('') : '<span class="chip ok">All ' + NF + ' checks fine</span>') + '</div>';
    if (v.ph.length) h += '<p class="media-h">Photos</p><div class="photos" aria-label="Photos from the visit">' + v.ph.map(function (ref, i) { return media(ref, i, 'Photo'); }).join('') + '</div>';
    if (v.vd.length) h += '<p class="media-h">Videos</p><div class="photos" aria-label="Videos from the visit">' + v.vd.map(function (ref, i) { return media(ref, i, 'Video'); }).join('') + '</div>';
    return h;
  }
  // A photo or video from the visit file, shown small; clicking it opens the file itself in Google Drive. A
  // Drive file is shown through Drive's own small picture of it; if that can't be shown (the file isn't
  // shared, or Drive has no picture of it), the link stays as "Photo 1", "Video 1" and so on.
  function media(ref, i, kind) {
    var drive = !/^https?:/i.test(ref);
    var link = drive ? 'https://drive.google.com/open?id=' + encodeURIComponent(ref) : ref;
    var label = kind + ' ' + (i + 1);
    var small = drive ? '<img src="https://drive.google.com/thumbnail?id=' + encodeURIComponent(ref) + '&amp;sz=w400" alt="' + label + ' from the visit" loading="lazy" referrerpolicy="no-referrer">' : '';
    return '<a class="' + (small ? 'thumb' : 'nothumb') + (kind === 'Video' ? ' video' : '') + '" href="' + esc(link) + '" target="_blank" rel="noopener noreferrer" title="Open ' + label.toLowerCase() + ' in a new tab">' + small + '<span>' + label + '</span></a>';
  }
  function planBlock(c) {
    var s = c.sc; if (!s) return '';
    var P = [[s[1], 'km sewer lines'], [s[2], 'km storm drains'], [s[5] + s[6], 'disposal stations'], [s[7], 'MGD wastewater treatment'], [s[3], 'underground water tanks'], [s[8], 'km roads and streets'], [s[11], 'street lights'], [s[10], 'kW solar power'], [s[9], 'machines']];
    return '<details class="visit"><summary>Scope of work under PDP</summary><div class="p-plan">' + P.filter(function (p) { return p[0]; }).map(function (p) { return '<span><b>' + nd(p[0], 1) + '</b> ' + p[1] + '</span>'; }).join('') + '</div></details>';
  }
  function listedCount() { return DIS.reduce(function (a, d) { return a + d.tot; }, 0); }
  function openCity(c, focus, quiet) {
    state.sel = c.k; placeRing(c);
    if (focus && hasPin(c)) map.setView([c.la, c.lo], Math.max(map.getZoom(), 9));
    var h = '<div class="p-top"><div><div class="p-name">' + esc(c.n) + '</div><div class="p-meta">' + (c.ml ? esc(c.dn) + ' district' + (c.div ? ' &middot; ' + esc(c.div) + ' division' : '') + ' &middot; ' + n(c.sc[0]) + ' people' : (c.dn ? esc(c.dn) + ' district &middot; ' : '') + 'not one of the ' + n(listedCount()) + ' PDP cities in the department file') + '</div></div><button type="button" class="x" id="p-close" aria-label="Close report">×</button></div>';
    if (c.un) {
      h += '<div class="badges"><span class="badge b-mute">' + (fullRange() ? 'Not visited by SMU yet' : 'Not visited by SMU in these dates') + '</span></div><p class="note">' + (c.ml ? 'This city is in the PDP list. ' : '') + 'SMU has no visit to it on file' + (fullRange() ? '' : ' in the dates chosen') + '.' + (hasPin(c) ? '' : ' The visit file has no location for it, so it has no pin.') + '</p>' + planBlock(c);
      if (c.r.length) h += reportsBlock(c);
    } else {
      var v = c.V;
      h += '<div class="badges"><span class="badge b-mute">' + (c.v.length > 1 ? pl(c.v.length, 'visit', 'visits') + ', ' + range(c.v[0].dt, v.dt) : 'Visited once, ' + ml(v.dt)) + '</span>' + (c.r.length ? '<span class="badge b-info">Answered by the department</span>' : '') + '</div>';
      if (!hasPin(c)) h += '<p class="note">The visit file has no usable location for this city, so it has no pin.</p>';
      h += planBlock(c);
      if (c.r.length) h += reportsBlock(c);
      h += '<div class="p-sec"><h3 style="margin-top:4px">Latest visit, ' + reportLabel(v.dt) + '</h3>' + visitBlock(v) + '</div>';
      c.v.slice(0, -1).reverse().forEach(function (e) { h += '<details class="visit"><summary>Earlier visit, ' + reportLabel(e.dt) + ' &middot; ' + STL[e.st].toLowerCase() + '</summary>' + visitBlock(e) + '</details>'; });
      if (!c.ml) h += '<p class="note">This town is not in the department\'s list, so it has no scope of work here.</p>';
    }
    var panel = $('panel');
    panel.innerHTML = h; panel.scrollTop = 0;
    $('p-close').addEventListener('click', function () { state.sel = null; placeRing(null); renderHint(); });
    if (!quiet && page.clientWidth <= 1100) panel.scrollIntoView({ block: 'start' });
  }
  function reportsBlock(c) {
    var byL = {}; c.r.forEach(function (i) { (byL[ITEMS[i].li] = byL[ITEMS[i].li] || []).push(ITEMS[i]); });
    return '<div class="p-sec"><h3>In SMU\'s reports to the department</h3>' + Object.keys(byL).map(Number).sort(function (a, b) { return b - a; }).map(function (li) {
      var l = LET[li], its = byL[li], rt = l.cities[c.k] || l.general;
      var b = '<div class="rep"><div class="rep-h">' + (l.rep ? reportLabel(l.rep) : 'SMU report') + '</div><ul class="p-iss">' + its.map(function (it) { var t = RTI[it.cl]; return '<li>' + esc(KEYS[it.key].l) + ' <span class="tbadge badge ' + t.badge + '">' + esc(t.label) + '</span><small>' + esc(it.tx) + '</small></li>'; }).join('') + '</ul>';
      b += rt ? '<small class="rlab">Department\'s response' + (l.rd ? ', ' + dl(l.rd) : '') + (l.general ? ' (one answer for all cities)' : '') + '</small><p class="rresp" title="Click to read all of it">' + esc(rt) + '</p>' : '<p class="note">The department\'s response does not name this city.</p>';
      var nx = l.rep ? c.v.filter(function (x) { return x.dt > l.rep; })[0] : null;
      b += nx ? '<p class="note"><b>Next SMU visit (' + reportLabel(nx.dt) + '):</b> ' + (nx.f.length ? pl(nx.f.length, 'check', 'checks') + ' failed.' : 'all ' + NF + ' checks fine.') + '</p>' : '<p class="note">Not visited again since' + (fullRange() ? '' : ', in the dates chosen') + '.</p>';
      return b + '</div>';
    }).join('') + '</div>';
  }

  /* page */
  function renderAll(fit) {
    var list = scoped(), o = summarise(list);
    $('f-clear').hidden = !state.district && fullRange();
    if (DK.min) { $('f-from').value = keyToISO(state.from); $('f-to').value = keyToISO(state.to); }
    nameForPdf();
    renderPlan(o, list); renderFlow(o); renderImpact(o); renderIssues(o); renderChanged(o); renderRevisit(list, o); renderDistricts(); renderMap(fit);
    if (state.sel) {
      var c = OI[state.sel];
      if (!c || !inScope(c.dn) || (!c.ml && c.un && !c.r.length)) { state.sel = null; placeRing(null); renderHint(); } else openCity(c, false, true);
    }
  }
  // What the page shows, for the name of the PDF made with Export to PDF ("Vehari, visits from ...").
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
    var names = DN.filter(function (d) { return d; }).slice().sort();
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
  function openFromPage(c) {
    openCity(c, true);
    $('map').scrollIntoView({ block: 'center' });
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
    if (t) { var c = OI[t.dataset.open]; if (c) openFromPage(c); return; }
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
  // A photo or video Drive can't show small keeps its "Photo 1" or "Video 1" link instead.
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
    var hits = scoped().filter(function (c) { return (c.n + ' ' + c.dn).toLowerCase().indexOf(q) >= 0; });
    hits.sort(function (a, b) { return (a.un ? 1 : 0) - (b.un ? 1 : 0) || a.n.localeCompare(b.n); });
    box.innerHTML = hits.slice(0, 12).map(function (c) {
      return '<button type="button" data-hit="' + esc(c.k) + '">' + esc(clabel(c)) + '<small>' + esc(c.dn) + ' &middot; ' + (c.un ? notYet() : STL[c.V.st].toLowerCase() + ', ' + ml(c.V.dt)) + '</small></button>';
    }).join('') || '<button type="button" disabled>No city found</button>';
    box.hidden = false;
  });
  $('results').addEventListener('click', function (e) {
    var b = e.target.closest('[data-hit]'); if (!b) return;
    var c = OI[b.dataset.hit]; if (!c) return;
    $('results').hidden = true; $('q').value = '';
    openFromPage(c);
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
  // The search box keeps the design's wording where it fits, and says it more briefly where it shares its
  // line with the Download button.
  var search = $('q'), searchWords = [search.placeholder, 'City or district'], measure = document.createElement('canvas').getContext('2d');
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

  function group(label, list, key, pre) { return list.length ? '<optgroup label="' + label + '">' + list.map(function (t) { return '<option value="' + key + t.id + '">' + esc((pre || '') + t.label) + '</option>'; }).join('') + '</optgroup>' : ''; }

  function start(data) {
    META = data.meta; DIS = data.districts; KEYS = data.keys; NF = data.nform; LET = data.letters;
    DN = DIS.map(function (d) { return d.n; });
    TOT = {}; DIS.forEach(function (d) { TOT[d.n] = d.tot; });
    KK = {}; KEYS.forEach(function (k, i) { KK[k.k] = i; });
    LETREP = {}; LET.forEach(function (l, i) { if (l.rep) LETREP[l.rep] = i; });
    FORMK = KEYS.slice(0, NF).map(function (k, i) { return i; });
    // Visits come once; the date filter picks from them. The SMU reports' problems don't change with dates.
    ALL = data.cities.map(function (c) {
      c.dn = c.d === null ? '' : DN[c.d];
      c.av = c.v.map(function (a) { return { dt: a[0], f: a[1], ph: a[2], vd: a[3], id: a[4], st: stOf(a[1].length) }; });
      c.v = c.av; c.V = c.v[c.v.length - 1] || null; c.un = !c.v.length;
      return c;
    });
    OI = {}; ALL.forEach(function (c) { OI[c.k] = c; });
    ITEMS = data.items.map(function (a, i) { return { i: i, li: a[0], c: OI[a[1]], key: a[2], tx: a[3], cl: a[4] }; }).filter(function (it) { return it.c; });

    DK.min = isoToKey(META.firstVisit); DK.max = isoToKey(META.lastVisit);
    state.from = Math.max(state.from || DK.min, DK.min);
    state.to = Math.min(state.to || DK.max, DK.max);
    if (state.from > state.to) { state.from = DK.min; state.to = DK.max; }
    if (DK.min) ['f-from', 'f-to'].forEach(function (id) { $(id).min = keyToISO(DK.min); $(id).max = keyToISO(DK.max); });

    fillDistricts();
    $('f-district').value = state.district;
    var FL = FORMK.map(function (i) { return { id: i, label: KEYS[i].l }; });
    var RL = KEYS.map(function (k, i) { return { id: i, label: k.l }; }).filter(function (x) { return ITEMS.some(function (it) { return it.key === x.id; }); });
    $('f-find').innerHTML = '<option value="">Any city</option><option value="rv">Visited more than once</option>' +
      group('Found at the latest visit', FL, 'f:') + group('In SMU\'s answered reports', RL, 'c:', 'Reported: ') + group('Department response', ITEMS.length ? RT : [], 't:', 'Response: ') + group('Found, then checked again', FL, 'g:', 'Checked again: ');

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
