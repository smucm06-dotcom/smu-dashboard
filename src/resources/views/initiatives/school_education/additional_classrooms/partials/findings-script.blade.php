<script>
// Everything above the records table, and the cards in the banner, is worked out here from one
// download, so changing a filter is instant and does not reload the page. This is the
// Additional Classrooms design's own code, inside the page frame the Special Education page uses.
// The date filter applies to visits only: reports and the department's responses always count.
(function () {
  'use strict';

  var page = document.getElementById('psrpPage');
  var ALL = [], META = { minKey: 0, maxKey: 0, respMax: '' }, EXTRA = [], loaded = false;

  var RT = [
    { id: 'fixed', label: 'Says fixed or acted', sub: 'Repair done or work restarted', sw: 'r-fixed', badge: 'b-good' },
    { id: 'disputed', label: 'Explained, no action', sub: 'A reason given instead of a fix', sw: 'r-disp', badge: 'b-warn' },
    { id: 'notans', label: 'Not answered', sub: 'The response does not address it', sw: 'r-nota', badge: 'b-crit' },
    { id: 'none', label: 'No response yet', sub: 'Nothing on file', sw: 'r-none', badge: 'b-crit' }
  ];
  var RTI = {}; RT.forEach(function (t) { RTI[t.id] = t; });
  var STATUS = [
    { id: 'on', label: 'Work going on', sub: 'Building under way', cls: 'st-on', badge: 'b-good' },
    { id: 'stop', label: 'Work stopped', sub: 'Started, then halted', cls: 'st-stop', badge: 'b-warn' },
    { id: 'not', label: 'Not started', sub: 'No work on site yet', cls: 'st-not', badge: 'b-crit' },
    { id: 'done', label: 'Completed', sub: 'All approved rooms built', cls: 'st-done', badge: 'b-good' }
  ];
  var STI = {}; STATUS.forEach(function (t) { STI[t.id] = t; });
  var STAGE = [
    { id: 'none', label: 'Not started' }, { id: 'found', label: 'Foundation being laid' },
    { id: 'walls', label: 'Walls going up' }, { id: 'roof', label: 'Roof being laid' }, { id: 'finish', label: 'Finishing work' }
  ];
  var SGI = {}; STAGE.forEach(function (t) { SGI[t.id] = t; });
  var CAUSE = [
    { id: 'funds', label: 'Funds delayed or not released' },
    { id: 'contr', label: 'Contractor slow or not performing' },
    { id: 'rain', label: 'Rain, flooding or bad weather' },
    { id: 'approval', label: 'Administrative approval pending' },
    { id: 'bricks', label: 'Poor-quality bricks or materials' },
    { id: 'materials', label: 'Building materials not available' },
    { id: 'nocontr', label: 'No contractor appointed' },
    { id: 'land', label: 'Land or space issue' },
    { id: 'other', label: 'Other reasons' }
  ];
  var CAI = {}; CAUSE.forEach(function (t) { CAI[t.id] = t; });
  // "Not recorded" is for an answer that is missing or none of these; it shows only when used.
  var INSP = [{ id: 'week', label: 'Every week' }, { id: 'month', label: 'Every month' }, { id: 'occ', label: 'Occasionally' }, { id: 'never', label: 'Never inspected' }, { id: 'nr', label: 'Not recorded' }];
  var INI = {}; INSP.forEach(function (t) { INI[t.id] = t; });

  function spc(v) { return v.en !== null && v.en !== undefined && v.cr ? v.en / v.cr : null; }
  var FIND = [
    { id: 'stop', label: 'Work stopped or not started', why: 'Approved rooms not being built', f: function (v) { return v.st !== 'on' && v.st !== 'done'; } },
    { id: 'qual', label: 'Construction quality not satisfactory', why: 'As judged by the field team', f: function (v) { return v.q === 0; } },
    { id: 'insp', label: 'Site never inspected by officials', why: 'No one checking the work', f: function (v) { return v.ins === 'never'; } },
    { id: 'lab', label: 'Work going on, but no labour on site', why: 'Active site standing idle', f: function (v) { return v.st === 'on' && v.lab === 0; } },
    { id: 'wash', label: 'Washrooms dirty or dilapidated', why: 'Hygiene', f: function (v) { return !!v.wc && v.wc !== 'Clean'; } },
    { id: 'cls', label: 'Classrooms dirty or dilapidated', why: 'Learning space', f: function (v) { return !!v.cc && v.cc !== 'Clean'; } },
    { id: 'water', label: 'No clean drinking water', why: 'Basic need missing', f: function (v) { return v.wa === 0; } },
    { id: 'crowd', label: 'More than 50 students per classroom', why: 'Why the new rooms are needed', base: function (v) { return spc(v) !== null; }, f: function (v) { var x = spc(v); return x !== null && x > 50; } },
    { id: 'slow', label: 'Problem slowing an active site', why: 'Work goes on, but slowly', f: function (v) { return !!v.oc; } },
    { id: 'dirty', label: 'School not clean overall', why: 'Upkeep', f: function (v) { return !!v.oa && v.oa !== 'Clean'; } },
    { id: 'wall', label: 'No boundary wall', why: 'Safety risk', f: function (v) { return v.bw === 0; } }
  ];
  var FI = {}; FIND.forEach(function (f) { FI[f.id] = f; });
  var TILES = ['stop', 'qual', 'insp', 'lab', 'wash', 'cls', 'water', 'crowd'];
  // What an issue in an SMU report is about, as the server groups it. Only the groups with an issue
  // the department says it fixed are named, so nothing is claimed that the responses don't say.
  var TOPIC = [
    { c: 'wall', t: 'the boundary wall' }, { c: 'safety', t: 'fire safety' }, { c: 'quality', t: 'construction quality' },
    { c: 'work', t: 'stopped or slow work' }, { c: 'clean', t: 'cleanliness' }, { c: 'building', t: 'the building' },
    { c: 'cost', t: 'construction costs' }, { c: 'other', t: 'other points' }
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
  function dateText(ds) {
    if (!ds.length) return '';
    var m = ds.map(ml), same = m.every(function (x) { return x === m[0]; });
    if (same && ds.length <= 3) return 'on ' + andList(ds.map(function (d) { return String(Number(d.split('-')[2])); })) + ' ' + m[0];
    var a = m[0].split(' '), b = m[m.length - 1].split(' ');
    return 'from ' + (a[1] === b[1] ? a[0] : m[0]) + ' to ' + m[m.length - 1];
  }

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

  var state = { zone: $('f-zone').value, district: $('f-district').value, from: isoToKey($('f-from').value), to: isoToKey($('f-to').value), show: 'all', filter: '', sel: null, sort: 'visited', dir: -1 };
  function scoped() { return ALL.filter(function (s) { return (!state.zone || s.z === state.zone) && (!state.district || s.d === state.district); }); }
  function where() { return state.district ? ' in ' + state.district : state.zone ? ' in the ' + state.zone + ' zone' : ''; }
  function reportsOf(list, noExtra) {
    var out = [];
    list.forEach(function (s) { s.r.forEach(function (r) { out.push({ r: r, s: s }); }); });
    if (!noExtra) EXTRA.forEach(function (r) { if ((!state.zone || r.z === state.zone) && (!state.district || r.d === state.district)) out.push({ r: r, s: null }); });
    return out.sort(function (a, b) { return a.r.vd < b.r.vd ? -1 : a.r.vd > b.r.vd ? 1 : 0; });
  }

  /* Summary */
  function summarise(list, noExtra) {
    var o = { total: list.length, ap: 0, visited: 0, never: 0, visits: 0, revisited: 0, dates: {}, apv: 0, fc: 0, uc: 0, fac: 0,
      find: {}, base: {}, st: {}, sg: {}, cause: {}, ins: {}, reports: 0, reported: 0, vac: 0, vacDates: {}, replied: 0, noReply: 0, items: 0, types: {}, lag: null, vds: {}, did: {} };
    FIND.forEach(function (f) { o.find[f.id] = 0; o.base[f.id] = 0; });
    STATUS.forEach(function (t) { o.st[t.id] = 0; }); STAGE.forEach(function (t) { o.sg[t.id] = 0; });
    CAUSE.forEach(function (t) { o.cause[t.id] = { stop: 0, slow: 0 }; }); INSP.forEach(function (t) { o.ins[t.id] = 0; });
    RT.forEach(function (t) { o.types[t.id] = 0; });
    list.forEach(function (s) {
      o.ap += s.ap || 0;
      if (!s.V) { if (!s.r.length) o.never++; return; }
      var v = s.V;
      o.visited++; o.visits += s.v.length; if (s.v.length > 1) o.revisited++;
      s.v.forEach(function (x) { o.dates[x.dt] = 1; });
      o.apv += v.ap || 0; o.fc += v.fc || 0; o.uc += v.uc || 0;
      if (v.fac && v.fac !== 'No facility available') o.fac++;
      FIND.forEach(function (f) { if (!f.base || f.base(v)) o.base[f.id]++; });
      s.f.forEach(function (id) { o.find[id]++; });
      o.st[v.st]++; o.sg[v.sg]++; o.ins[v.ins]++; if (v.vac) { o.vac++; o.vacDates[v.dt] = 1; }
      if (v.why) o.cause[v.why].stop++;
      if (v.oc) o.cause[v.oc].slow++;
    });
    var lags = [], reps = reportsOf(list, noExtra);
    o.reports = reps.length;
    reps.forEach(function (x) {
      var r = x.r, any = false;
      if (r.vd) o.vds[r.vd] = 1;
      r.it.forEach(function (it) { o.items++; o.types[it.t]++; if (it.t !== 'none') any = true; if (it.t === 'fixed') o.did[it.c] = 1; });
      if (any) { o.replied++; if (r.vd && r.rd) lags.push(days(r.vd, r.rd)); } else o.noReply++;
    });
    o.reported = reps.length;
    o.lag = median(lags);
    o.dates = Object.keys(o.dates).sort(); o.vds = Object.keys(o.vds).sort(); o.vacDates = Object.keys(o.vacDates).sort();
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
    setCard('c-visited', o.visited, o.visited ? pct(o.visited, o.total) + '% of total schools, ' + dateText(o.dates) : 'None yet');
    var said = [];
    if (o.lag !== null) said.push((o.replied > 1 ? 'Usually within ' : 'Within ') + pl(o.lag, 'day', 'days') + ' of the visit');
    if (o.noReply) said.push(n(o.noReply) + ' with no response');
    setCard('c-replied', o.replied, o.reports ? said.join('. ') : '');
    setCard('c-revisit', o.revisited, o.revisited ? pl(o.revisited, 'school', 'schools') + ' checked a second time' : 'No school visited twice yet');
  }

  /* The two Impact cards, at the top of "What changed after the SMU visits" */
  function renderImpact(o) {
    var did = TOPIC.filter(function (d) { return o.did[d.c]; }).map(function (d) { return d.t; });
    var lagged = reportsOf(scoped()).filter(function (x) { return x.r.vd && x.r.rd; }).map(function (x) { return days(x.r.vd, x.r.rd); });
    $('imp-1').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (o.items ? n(o.types.fixed) + ' of ' + n(o.items) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (o.items ? 'issues SMU reported were fixed or acted on, says the department' : 'No SMU report with a department response here yet') + '</b><small>' +
      (o.items ? 'At ' + pl(o.reported, 'school', 'schools') + (o.vds.length ? ' SMU visited in ' + andList(Array.from(new Set(o.vds.map(ml)))) : '') + '.' + (did.length ? ' Fixed or acted on: ' + esc(andList(did)) + '.' : '') + ' ' +
        (o.lag !== null && lagged.length ? 'The department responded within ' + pl(Math.max.apply(null, lagged), 'day', 'days') + '.' : '') : '') + '</small></div>';
    $('imp-2').innerHTML = '<span class="imp-tag">Key finding</span><div class="imp-num">' + (o.visited ? n(o.fc) + ' of ' + n(o.apv) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (o.visited ? 'approved classrooms finished at the schools SMU visited' : 'No school visited here yet') + '</b><small>' +
      (o.visited ? 'The most common stage was "' + esc(topStage(o).label.toLowerCase()) + '" (' + n(o.sg[topStage(o).id]) + ' of ' + n(o.visited) + ' schools).' + (o.fac ? '' : ' None of the new rooms has furniture or other facilities yet.') : '') + '</small></div>';
  }
  function topStage(o) { return STAGE.slice().sort(function (a, b) { return o.sg[b.id] - o.sg[a.id]; })[0]; }

  /* Issues */
  function renderIssues(o) {
    $('tiles').innerHTML = TILES.map(function (id) {
      var f = FI[id], c = o.find[id], b = o.base[id];
      var sub = f.base ? n(c) + ' of ' + pl(b, 'school', 'schools') + ' where students were counted' : pct(c, o.visited) + '% of visited';
      return '<button type="button" class="tile" data-filter="f:' + f.id + '"><b>' + n(c) + '</b><span>' + esc(f.label) + '</span><small>' + esc(f.why) + ' &middot; ' + (o.visited ? sub : 'no visits') + '</small></button>';
    }).join('');
    $('inotes').textContent = o.vac ? 'Students and teachers were not counted at the ' + pl(o.vac, 'school', 'schools') + ' visited ' + dateText(o.vacDates) + ' (the visit file has no figures for them). The construction checks from those visits are counted.' : '';
  }

  function irow(label, c, max, of, filter) {
    return '<button type="button" class="irow"' + (filter ? ' data-filter="' + filter + '"' : '') + '><span>' + esc(label) + '</span><span class="bar"><i style="width:' + (max ? c / max * 100 : 0) + '%"></i></span><span class="num"><b>' + n(c) + '</b></span><span class="num pct">' + pct(c, of) + '%</span></button>';
  }
  function irowHead(a, b) { return '<div class="irow irow-h"><span>' + a + '</span><span></span><span class="num">' + b + '</span><span class="num">Share</span></div>'; }

  /* Construction */
  function renderConstruction(o) {
    $('prog').innerHTML = '<h3>Classrooms finished</h3><div class="progress-h"><span><span class="prog-big">' + (o.visited ? n(o.fc) + ' of ' + n(o.apv) : '&ndash;') + '</span> approved classrooms finished at the schools visited</span><span>' +
      (o.visited ? n(o.uc) + ' still under construction &middot; ' + pct(o.fc, o.apv) + '% done' : '') + '</span></div><div class="track" role="img" aria-label="' + o.fc + ' of ' + o.apv + ' finished"><i style="width:' + (o.apv ? o.fc / o.apv * 100 : 0) + '%"></i></div>';
    var tot = o.visited || 1;
    $('st-bar').innerHTML = STATUS.map(function (t) {
      var c = o.st[t.id]; if (!c) return '';
      return '<i class="' + t.cls + '" style="width:' + (c / tot * 100) + '%" data-tip="<b>' + esc(t.label) + '</b>' + pl(c, 'school', 'schools') + ', ' + pct(c, tot) + '%"></i>';
    }).join('');
    $('st-bar').setAttribute('aria-label', STATUS.map(function (t) { return t.label + ' ' + o.st[t.id]; }).join(', '));
    var sts = STATUS.filter(function (t) { return t.id !== 'done' || o.st.done; });
    $('st-outs').className = 'outs ' + (sts.length > 3 ? 'four' : 'three');
    $('st-outs').innerHTML = sts.map(function (t) {
      return '<button type="button" class="card out" data-filter="s:' + t.id + '"><div class="out-num">' + n(o.st[t.id]) + '</div><div class="out-lbl"><span class="sw ' + t.cls + '"></span>' + esc(t.label) + '</div><div class="out-sub">' + esc(t.sub) + '</div></button>';
    }).join('');
    var mx = Math.max.apply(null, STAGE.map(function (t) { return o.sg[t.id]; }).concat([1]));
    $('sg-rows').innerHTML = irowHead('Stage', 'Schools') + STAGE.map(function (t) { return irow(t.label, o.sg[t.id], mx, o.visited, 'g:' + t.id); }).join('');
    var insp = INSP.filter(function (t) { return t.id !== 'nr' || o.ins.nr; });
    var mi = Math.max.apply(null, insp.map(function (t) { return o.ins[t.id]; }).concat([1]));
    $('ins-rows').innerHTML = irowHead('Officials visit the site', 'Schools') + insp.map(function (t) { return irow(t.label, o.ins[t.id], mi, o.visited, 'i:' + t.id); }).join('');
    var rows = CAUSE.map(function (c) { var a = o.cause[c.id]; return { c: c, a: a, t: a.stop + a.slow }; }).filter(function (x) { return x.t; }).sort(function (a, b) { return b.t - a.t || b.a.stop - a.a.stop; });
    var cm = rows.length ? rows[0].t : 1;
    $('cause-t').innerHTML = rows.length ? '<thead><tr><th>Cause</th><th class="r">Work stopped</th><th class="r">Work slowed</th><th>Schools</th></tr></thead><tbody>' + rows.map(function (x) {
      return '<tr data-filter="c:' + x.c.id + '"><td class="lab">' + esc(x.c.label) + '</td><td class="r">' + (x.a.stop ? n(x.a.stop) : '&ndash;') + '</td><td class="r">' + (x.a.slow ? n(x.a.slow) : '&ndash;') + '</td>' +
        '<td><div class="cov"><div class="bar"><i style="width:' + x.t / cm * 100 + '%"></i></div><span>' + n(x.t) + '</span></div></td></tr>';
    }).join('') + '</tbody>' : '<tbody><tr><td class="empty" style="padding:12px 18px">No stopped or slowed site in this selection.</td></tr></tbody>';
  }

  /* What changed */
  function renderChanged(o, list) {
    $('ans-sub').textContent = o.items ? 'Each of the ' + n(o.items) + ' issues SMU reported is counted once, by the kind of response it got. Click a group to see those schools on the map.' : 'No reports in this selection.';
    var tot = o.items || 1;
    $('segbar').innerHTML = RT.map(function (t) {
      var c = o.types[t.id]; if (!c) return '';
      return '<i class="' + t.sw + '" style="width:' + (c / tot * 100) + '%" data-tip="<b>' + esc(t.label) + '</b>' + pl(c, 'issue', 'issues') + ', ' + pct(c, tot) + '%"></i>';
    }).join('');
    $('segbar').setAttribute('aria-label', RT.map(function (t) { return t.label + ' ' + o.types[t.id]; }).join(', '));
    $('outs').innerHTML = RT.map(function (t) {
      return '<button type="button" class="card out" data-filter="t:' + t.id + '"><div class="out-num">' + n(o.types[t.id]) + '</div><div class="out-lbl"><span class="sw ' + t.sw + '"></span>' + esc(t.label) + '</div><div class="out-sub">' + esc(t.sub) + '</div></button>';
    }).join('');
    var reps = reportsOf(list);
    $('acts').className = 'acts' + (reps.length === 1 ? ' one' : '');
    $('acts').innerHTML = reps.length ? reps.map(function (x) {
      var r = x.r, s = x.s, name = s ? s.n : r.n, d = s ? s.d : r.d;
      return '<div class="card act-col"><h3>' + esc(name) + '</h3><p>' + esc(d) + (r.vd ? ' &middot; visited ' + dl(r.vd) : '') + (r.vm ? ' (' + dl(r.vm) + ' in the monitoring data)' : '') +
        (r.rd ? ' &middot; response ' + dl(r.rd) + (r.vd ? ', ' + pl(days(r.vd, r.rd), 'day', 'days') + ' later' : '') : '') + '</p>' +
        r.it.map(itemHtml).join('') + (s ? (s.la === null || s.la === undefined ? '' : '<button type="button" class="link" data-open="' + esc(s.k) + '">Open on the map</button>') : '<p class="note">Not in the department file, so it has no pin.</p>') + '</div>';
    }).join('') : '<p class="empty">No department response in this selection.</p>';
    var visitedNoResp = list.filter(function (s) { return s.V && !s.r.length; }).length;
    var noVisit = reps.filter(function (x) { return !x.s || !x.s.av.length; }).map(function (x) { return esc(x.s ? x.s.n : x.r.n); });
    var lastVisit = META.maxKey ? keyToISO(META.maxKey) : '';
    $('caveat').innerHTML = '<b>These are the department\'s own statements.</b> SMU has not checked them on a new visit yet. ' +
      (noVisit.length ? andList(noVisit) + (noVisit.length === 1 ? '\'s visit is' : '\'s visits are') + ' not in the monitoring data, so its construction checks are not shown. ' : '') +
      (visitedNoResp ? (visitedNoResp === 1 ? 'The other school visited' : 'The other ' + n(visitedNoResp) + ' schools visited') + esc(where()) + ' have no department response yet' +
        (META.respMax && lastVisit && META.respMax < lastVisit ? '; the latest response is dated ' + dl(META.respMax) + ', and visits run to ' + dl(lastVisit) : '') + '.' : '');
  }
  function itemHtml(it) {
    var t = RTI[it.t];
    return '<div class="ritem"><div class="ri-h"><span class="ri-is"><small class="rlab">Issue</small>' + esc(it.i) + '</span><span class="badge ' + t.badge + '">' + esc(t.label) + '</span></div>' +
      (it.cx ? '<p class="rcx">SMU also noted: ' + esc(it.cx) + '</p>' : '') +
      (it.x ? '<small class="rlab">Department\'s response</small><p class="rtx" title="Click to read the full response">' + esc(it.x) + '</p>' : '<p class="rtx na">' + (it.t === 'notans' ? 'The response does not address this point.' : 'No response on file.') + '</p>') + '</div>';
  }

  /* Districts */
  function districtRows() {
    var by = {};
    ALL.forEach(function (s) { if (state.zone && s.z !== state.zone) return; (by[s.d] = by[s.d] || []).push(s); });
    return Object.keys(by).map(function (d) {
      var L = by[d], o = summarise(L, true);
      var top = FIND.slice().sort(function (a, b) { return o.find[b.id] - o.find[a.id]; })[0];
      return { d: d, z: L[0].z, total: o.total, ap: o.ap, visited: o.visited, cov: o.visited / o.total, stop: o.find.stop, qual: o.find.qual, never: o.find.insp,
        resp: L.filter(function (s) { return s.r.length; }).length, top: o.visited && o.find[top.id] ? top.label : '' };
    });
  }
  var DCOLS = [
    { k: 'd', t: 'District' }, { k: 'z', t: 'Zone' }, { k: 'total', t: 'Total schools', r: 1 }, { k: 'ap', t: 'Classrooms approved', r: 1 }, { k: 'visited', t: 'Visited', r: 1 },
    { k: 'cov', t: 'Share visited' }, { k: 'stop', t: 'Work stopped or not started', r: 1 }, { k: 'qual', t: 'Quality not satisfactory', r: 1 }, { k: 'never', t: 'Never inspected', r: 1 },
    { k: 'resp', t: 'Department responses', r: 1 }, { k: 'top', t: 'Most common finding' }
  ];
  function renderDistricts() {
    var D = districtRows(), k = state.sort, dir = state.dir;
    var rows = D.slice().sort(function (a, b) { var x = a[k], y = b[k]; return (typeof x === 'string' ? x.localeCompare(y) : x - y) * dir || b.total - a.total || a.d.localeCompare(b.d); });
    function cnt(r, key) { return r.visited ? '<td class="r' + (r[key] && r[key] / r.visited >= 0.5 ? ' dn' : '') + '">' + n(r[key]) + '</td>' : '<td class="r">&ndash;</td>'; }
    $('dt').innerHTML = '<thead><tr>' + DCOLS.map(function (c) {
      var on = c.k === k;
      return '<th scope="col" class="' + (c.r ? 'r' : '') + '" aria-sort="' + (on ? (dir > 0 ? 'ascending' : 'descending') : 'none') + '"><button type="button" data-sort="' + c.k + '">' + c.t + (on ? (dir > 0 ? ' ↑' : ' ↓') : '') + '</button></th>';
    }).join('') + '</tr></thead><tbody>' + rows.map(function (r) {
      return '<tr data-district="' + esc(r.d) + '"' + (r.d === state.district ? ' class="on"' : '') + '><td><button type="button" class="dname" data-district="' + esc(r.d) + '">' + esc(r.d) + '</button></td><td>' + esc(r.z) + '</td><td class="r">' + n(r.total) + '</td><td class="r">' + n(r.ap) + '</td><td class="r">' + n(r.visited) + '</td>' +
        '<td><div class="cov"><div class="bar"><i style="width:' + r.cov * 100 + '%"></i></div><span>' + Math.round(r.cov * 100) + '%</span></div></td>' + cnt(r, 'stop') + cnt(r, 'qual') + cnt(r, 'never') +
        '<td class="r">' + (r.resp ? n(r.resp) : '&ndash;') + '</td><td>' + esc(r.top) + '</td></tr>';
    }).join('') + '</tbody>';
    var none = D.filter(function (r) { return !r.visited; }).sort(function (a, b) { return b.total - a.total; });
    var vis = D.filter(function (r) { return r.visited >= 3; });
    var stop = vis.slice().sort(function (a, b) { return b.stop / b.visited - a.stop / a.visited || b.visited - a.visited; })[0];
    var insp = vis.slice().sort(function (a, b) { return b.never / b.visited - a.never / a.visited || b.visited - a.visited; })[0];
    $('insights').innerHTML =
      '<div class="card insight"><small>No school visited yet</small>' + (none.length ? none.slice(0, 5).map(function (r) { return '<b>' + esc(r.d) + '</b> (' + n(r.total) + ')'; }).join(', ') + (none.length > 5 ? ' and ' + n(none.length - 5) + ' more districts' : '') : 'Every district here has a visit') + '</div>' +
      '<div class="card insight"><small>Most sites with work stopped</small>' + (!stop ? 'Too few visits (3 or more needed)' : stop.stop ? '<b>' + esc(stop.d) + '</b>, ' + n(stop.stop) + ' of ' + n(stop.visited) + ' schools visited' : 'Work going on at every site here') + '</div>' +
      '<div class="card insight"><small>Most sites never inspected by officials</small>' + (!insp ? 'Too few visits (3 or more needed)' : insp.never ? '<b>' + esc(insp.d) + '</b>, ' + n(insp.never) + ' of ' + n(insp.visited) + ' schools visited' : 'Every site here has been inspected') + '</div>';
  }

  /* Map */
  var map = L.map('map', { preferCanvas: true, minZoom: 5, maxZoom: 18, zoomSnap: 0.5 });
  map.attributionControl.setPrefix(false); // No "Leaflet" or flag in the corner, only the basemap's credit.
  var tileBase = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_';
  L.tileLayer(tileBase + 'Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', attribution: 'Basemap &copy; Esri, HERE, Garmin, OpenStreetMap contributors' }).addTo(map);
  L.tileLayer(tileBase + 'Reference/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', pane: 'overlayPane' }).addTo(map);
  var css = getComputedStyle($('psrpFindings'));
  var C = { good: css.getPropertyValue('--good').trim(), crit: css.getPropertyValue('--crit').trim(), surf: css.getPropertyValue('--surface').trim(), ink: css.getPropertyValue('--ink').trim(), deep: css.getPropertyValue('--deep').trim() };
  var renderer = L.canvas({ padding: 0.3, tolerance: 5 });
  var pins = L.layerGroup().addTo(map);
  var ring = null, focusPin = null, drawn = {};
  var PUNJAB = [[27.7, 69.3], [34.1, 75.5]];
  function radius() { var z = map.getZoom() || 7; return z < 7 ? 3 : z < 8 ? 3.5 : z < 10 ? 4.5 : z < 12 ? 6 : 7.5; }
  // A school visited only outside the chosen dates counts as not visited in them.
  function status(s) { return s.V ? 'v' : s.r.length && !s.av.length ? 'r' : 'n'; }
  function makeMarker(s) {
    var st = status(s), r = radius();
    var opt = st === 'r' ? { radius: r + 1, renderer: renderer, stroke: true, color: C.good, weight: 2.2, fillColor: C.deep, fillOpacity: 1 }
      : st === 'v' ? { radius: r, renderer: renderer, stroke: true, color: C.surf, weight: 0.8, fillColor: C.good, fillOpacity: 1 }
      : { radius: r, renderer: renderer, stroke: true, color: C.crit, weight: 1.8, fillColor: C.surf, fillOpacity: 1 };
    var m = L.circleMarker([s.la, s.lo], opt);
    m.bindTooltip(function () {
      return esc(s.n) + (st === 'n' ? ' (not visited)' : st === 'r' ? ' (visited ' + dl(s.r[0].vd) + ', department responded; visit not in the monitoring data)' : ' (visited ' + dl(s.V.dt) + ', ' + STI[s.V.st].label.toLowerCase() + ')');
    }, { direction: 'top', offset: [0, -4] });
    m.on('click', function () { openSchool(s, false); });
    return m;
  }
  function responded(s) { return s.r.some(function (r) { return r.it.some(function (it) { return it.t !== 'none'; }); }); }
  function matchesFilter(s) {
    var f = state.filter, v = s.V;
    if (!f) return true;
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 'f:') return s.f.indexOf(id) >= 0;
    if (k === 's:') return !!v && v.st === id;
    if (k === 'g:') return !!v && v.sg === id;
    if (k === 'c:') return !!v && (v.why === id || v.oc === id);
    if (k === 'i:') return !!v && v.ins === id;
    if (k === 't:') return !!s.ts[id];
    if (f === 'rep') return responded(s);
    if (f === 'norep') return !!v && !s.r.length;
    return true;
  }
  function filterLabel(f) {
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 'f:') return FI[id].label;
    if (k === 's:') return STI[id].label;
    if (k === 'g:') return 'Stage: ' + SGI[id].label;
    if (k === 'c:') return 'Held back by: ' + CAI[id].label;
    if (k === 'i:') return 'Officials inspect: ' + INI[id].label;
    if (k === 't:') return 'Response: ' + RTI[id].label;
    if (f === 'rep') return 'Department responded';
    if (f === 'norep') return 'Visited, no department response yet';
    return '';
  }
  function matchesMap(s) {
    var st = status(s);
    if (state.show === 'v' && st === 'n') return false;
    if (state.show === 'n' && st !== 'n') return false;
    return matchesFilter(s);
  }
  function renderMap(fit) {
    pins.clearLayers();
    drawn = {};
    var list = scoped().filter(matchesMap);
    var cnt = { v: 0, r: 0, n: 0 }, unmapped = 0, pts = [], visitIds = [];
    var order = { n: 0, v: 1, r: 2 };
    list.sort(function (a, b) { return order[status(a)] - order[status(b)]; });
    list.forEach(function (s) {
      if (s.la === null || s.la === undefined) { unmapped++; return; }
      var st = status(s);
      cnt[st]++; pins.addLayer(makeMarker(s)); drawn[s.k] = 1; pts.push([s.la, s.lo]);
      // The visit the school's findings come from: its latest in the chosen dates.
      if (st === 'v') visitIds.push(s.V.id);
    });
    // As on the other pages: only a chosen finding, status or response can be downloaded, and
    // the file holds exactly the visited schools on the map, one row each.
    $('map-download').hidden = !(state.filter && visitIds.length);
    if (state.filter) {
      $('dl-ids').value = visitIds.join(',');
      $('dl-issue').value = filterLabel(state.filter);
      $('dl-label').textContent = 'Download ' + pl(visitIds.length, 'school', 'schools');
    }
    fitSearchHint(); // The button takes room from the search box, or gives it back.
    var label = state.filter ? ' <span class="lg-n">with "' + esc(filterLabel(state.filter)) + '"</span>' : '';
    var keep = function (c) { return !state.filter || c > 0; };
    $('legend').innerHTML = (state.show !== 'n' && keep(cnt.v) ? '<div class="lg"><span class="pin v"></span>Visited, in the monitoring data <span class="lg-n">' + n(cnt.v) + '</span></div>' : '') +
      (state.show !== 'n' && keep(cnt.r) && (cnt.r || EXTRA.length || ALL.some(function (s) { return status(s) === 'r'; })) ? '<div class="lg"><span class="pin r"></span>Department responded, visit not in the data <span class="lg-n">' + n(cnt.r) + '</span></div>' : '') +
      (state.show !== 'v' && keep(cnt.n) ? '<div class="lg"><span class="pin n"></span>Not visited <span class="lg-n">' + n(cnt.n) + '</span></div>' : '') + (label ? '<div class="lg">' + label + '</div>' : '');
    $('map-note').textContent = (unmapped ? pl(unmapped, 'school', 'schools') + ' in this view ' + (unmapped === 1 ? 'has' : 'have') + ' no map location, so ' + (unmapped === 1 ? 'it is' : 'they are') + ' not on the map. Search finds ' + (unmapped === 1 ? 'it' : 'them') + '. ' : '') +
      (state.filter ? (pts.length || unmapped ? '' : 'No school in this view matches "' + filterLabel(state.filter) + '".') : '');
    if (fit) {
      var zone = pts.length ? pts : scoped().filter(function (s) { return s.la !== null; }).map(function (s) { return [s.la, s.lo]; });
      if (zone.length && (state.district || state.zone || state.filter)) map.fitBounds(L.latLngBounds(zone).pad(0.08), { maxZoom: 12 });
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
  function cond(val) { return val ? '<span class="' + (val === 'Clean' ? 'ok' : 'bad') + '">' + (val === 'Clean' ? '✓ ' : '✗ ') + esc(val) + '</span>' : '<span class="na">Not recorded</span>'; }
  function frac(p, t, warn) {
    if (t === null || t === undefined || p === null || p === undefined) return '<span class="na">Not recorded</span>';
    var r = t ? Math.min(p, t) / t : null;
    return '<span class="' + (r !== null && r < warn ? 'bad' : '') + '">' + n(p) + ' of ' + n(t) + (r !== null ? ' (' + Math.round(r * 100) + '%)' : '') + '</span>';
  }
  function rowsTable(list) { return '<table class="rt"><tbody>' + list.map(function (r) { return '<tr><td>' + r[0] + '</td><td>' + r[1] + '</td></tr>'; }).join('') + '</tbody></table>'; }
  function txtCell(t, bad) { return t ? '<span class="' + (bad ? 'bad' : '') + '" style="white-space:normal">' + esc(t) + '</span>' : '<span class="na">&ndash;</span>'; }
  function constructionRows(v) {
    var rows = [['Classrooms approved', v.ap === null ? '<span class="na">Not recorded</span>' : n(v.ap)], ['Finished', '<span class="' + (v.fc ? 'ok' : 'bad') + '">' + n(v.fc || 0) + ' of ' + n(v.ap || 0) + '</span>'], ['Under construction', v.uc === null ? '<span class="na">Not recorded</span>' : n(v.uc)],
      ['Status', '<span class="' + (v.st === 'on' || v.st === 'done' ? 'ok' : 'bad') + '">' + STI[v.st].label + '</span>']];
    if (v.st !== 'on') rows.push(['Reason given', txtCell(v.wraw, true)]);
    else rows.push(['Problem slowing work', v.ob === 1 ? txtCell(v.oraw, true) : v.ob === 0 ? '<span class="ok">✓ None reported</span>' : '<span class="na">Not recorded</span>']);
    rows.push(['Stage reached', SGI[v.sg].label], ['Labour on site', yesNo(v.lab, 1)],
      ['Officials inspect the site', '<span class="' + (v.ins === 'never' ? 'bad' : '') + '">' + INI[v.ins].label + '</span>'],
      ['Construction quality satisfactory', yesNo(v.q, 1)], ['Facilities in the new rooms', txtCell(v.fac === 'No facility available' ? 'None yet' : v.fac, false)]);
    return rowsTable(rows);
  }
  function schoolRows(v) {
    var x = spc(v), vac = '<span class="na">Not counted at this visit</span>';
    return rowsTable([
      ['School type', esc(v.ty || '–')], ['Students present', v.vac ? vac : frac(v.sp, v.en, 0.6)], ['Teachers present', v.vac ? vac : frac(v.tp, v.tt, 0.75)], ['Other staff present', v.vac ? vac : frac(v.np, v.nt, 0.75)],
      ['Existing classrooms and washrooms', v.cr === null ? (v.vac ? '<span class="na">Not checked at this visit</span>' : '<span class="na">Not recorded</span>') : n(v.cr) + ' and ' + (v.wr === null ? '–' : n(v.wr))],
      ['Students per classroom', x === null ? (v.vac ? vac : '<span class="na">Not recorded</span>') : '<span class="' + (x > 50 ? 'bad' : '') + '">' + Math.round(x) + '</span>'],
      ['Classrooms', cond(v.cc)], ['Washrooms', cond(v.wc)], ['Overall cleanliness', cond(v.oa)], ['Clean drinking water', yesNo(v.wa, 1)], ['Boundary wall', yesNo(v.bw, 1)]
    ]);
  }
  function renderHint() {
    $('panel').innerHTML = '<div class="panel-hint"><h3>School report</h3><p>Click a pin on the map, or search above, to see the school\'s report here.</p><ul>' +
      '<li><b>Green pin:</b> visited by SMU. You will see how far construction has come and the state of the school.</li>' +
      '<li><b>Dark pin with a green ring:</b> the department responded to an SMU report, but that visit is not in the monitoring data. You will see the issues and the response.</li>' +
      '<li><b>Red ring:</b> in the department file, not visited yet.</li></ul></div>';
  }
  function openSchool(s, focus, quiet) {
    state.sel = s.k; placeRing(s);
    if (focus && s.la !== null && s.la !== undefined) map.setView([s.la, s.lo], Math.max(map.getZoom(), 12));
    var v = s.V;
    var h = '<div class="p-top"><div><div class="p-name">' + esc(s.n) + '</div><div class="p-meta">EMIS ' + esc(s.e) + ' &middot; ' + esc(s.d) + (s.z ? ' &middot; ' + esc(s.z) + ' zone' : '') + (s.lv ? ' &middot; ' + esc(s.lv) : '') + '</div></div><button type="button" class="x" id="p-close" aria-label="Close report">×</button></div>';
    h += '<div class="badges">' + (v ? '<span class="badge b-good">● Visited ' + dl(v.dt) + '</span><span class="badge ' + STI[v.st].badge + '">' + STI[v.st].label + '</span>' + (v.vac ? '<span class="badge b-mute">Students and staff not counted</span>' : '')
      : s.av.length ? '<span class="badge b-mute">Not visited in the dates chosen</span>'
      : s.r.length ? '<span class="badge b-warn">Visited ' + dl(s.r[0].vd) + ', not in the monitoring data</span>' : '<span class="badge b-crit">○ Not visited</span>') +
      (s.r.length ? '<span class="badge ' + (responded(s) ? 'b-info' : 'b-warn') + '">' + (responded(s) ? 'Department responded' : 'No department response yet') + '</span>' : (v ? '<span class="badge b-mute">No department response yet</span>' : '')) + '</div>';
    if (s.la === null || s.la === undefined) h += '<p class="note">This school has no map location, so it has no pin.</p>';
    if (!v && s.av.length) h += '<p class="note">Visited ' + andList(s.av.map(function (x) { return dl(x.dt); })) + ', outside the dates chosen in the filter bar.</p>';
    if (v) {
      h += '<div class="p-sec"><h3>' + (s.f.length ? pl(s.f.length, 'issue', 'issues') + ' found at the visit' : 'No issues found at the visit') + '</h3>' +
        (s.f.length ? '<div class="chips">' + s.f.map(function (k) { return '<span class="chip">' + esc(FI[k].label) + '</span>'; }).join('') + '</div>' : '') + '</div>';
    }
    if (s.r.length) {
      h += '<div class="p-sec"><h3>Issues reported and the department\'s response</h3>' + s.r.slice().reverse().map(function (r) {
        return '<div class="rep"><div class="rep-h">SMU visit of ' + dl(r.vd) + (r.vm ? ' (' + dl(r.vm) + ' in the monitoring data)' : '') + '<br>' + (r.rd ? 'Response dated ' + dl(r.rd) + (r.vd ? ', ' + pl(days(r.vd, r.rd), 'day', 'days') + ' later' : '') : 'No response date on file') + '</div>' + r.it.map(itemHtml).join('') + '</div>';
      }).join('') + '</div>';
    }
    if (v) {
      h += '<div class="p-sec"><h3>Construction, ' + dl(v.dt) + '</h3>' + constructionRows(v) + '</div>';
      h += '<div class="p-sec"><h3>The school on the visit day</h3>' + schoolRows(v) + '</div>';
    } else if (!s.r.length && !s.av.length) h += '<p class="note">No SMU visit report is on file for this school. ' + pl(s.ap, 'classroom is', 'classrooms are') + ' approved here.</p>';
    else if (!s.av.length) h += '<p class="note">' + pl(s.ap, 'classroom is', 'classrooms are') + ' approved here. The construction checks from this visit are not in the monitoring data yet.</p>';
    var panel = $('panel');
    panel.innerHTML = h; panel.scrollTop = 0;
    $('p-close').addEventListener('click', function () { state.sel = null; placeRing(null); renderHint(); });
    if (!quiet && page.clientWidth <= 1100) panel.scrollIntoView({ block: 'start' });
  }

  /* Page */
  function renderAll(fit) {
    var list = scoped(), o = summarise(list);
    $('f-clear').hidden = !state.district && !state.zone && fullRange();
    if (META.minKey) { $('f-from').value = keyToISO(state.from); $('f-to').value = keyToISO(state.to); }
    fitScope();
    renderFlow(o); renderImpact(o); renderIssues(o); renderConstruction(o); renderChanged(o, list); renderDistricts(); renderMap(fit);
    if (state.sel) {
      var s = ALL.filter(function (x) { return x.k === state.sel; })[0];
      if (s && list.indexOf(s) < 0) { state.sel = null; placeRing(null); renderHint(); } else if (s) openSchool(s, false, true);
    }
  }
  // What the page shows, in the filter bar. Where the one-line bar has no room for the whole
  // sentence it is said more briefly, and hovering always shows all of it.
  function fitScope() {
    var el = $('scope'), place = state.district ? state.district : state.zone ? state.zone + ' zone' : 'all of Punjab', dates = !META.minKey || fullRange() ? '' : ', visits from ' + rangeText();
    el.textContent = 'Everything below shows ' + place + dates;
    el.title = el.textContent;
    if (el.clientWidth && el.scrollWidth > el.clientWidth + 1) el.textContent = 'Showing ' + place + dates;
  }
  // Tells the records table below to show the same zone, district and dates.
  function filtersChanged() {
    document.dispatchEvent(new CustomEvent('psrp:filters', { detail: {
      zone: state.zone, district: state.district,
      from: !META.minKey || state.from === META.minKey ? '' : keyToISO(state.from),
      to: !META.maxKey || state.to === META.maxKey ? '' : keyToISO(state.to)
    } }));
  }
  function fillDistricts() {
    var names = Array.from(new Set(ALL.filter(function (s) { return !state.zone || s.z === state.zone; }).map(function (s) { return s.d; }))).sort();
    if (state.district && names.indexOf(state.district) < 0) names.push(state.district);
    $('f-district').innerHTML = '<option value="">All districts</option>' + names.map(function (d) { return '<option' + (d === state.district ? ' selected' : '') + '>' + esc(d) + '</option>'; }).join('');
  }
  function setDistrict(d) {
    state.district = d;
    if (d) { var s = ALL.filter(function (x) { return x.d === d; })[0]; if (s && state.zone && s.z !== state.zone) { state.zone = ''; $('f-zone').value = ''; fillDistricts(); } }
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
  $('f-zone').addEventListener('change', function () { state.zone = this.value; state.district = ''; fillDistricts(); if (loaded) renderAll(true); filtersChanged(); });
  $('f-district').addEventListener('change', function () { setDistrict(this.value); });
  $('f-from').addEventListener('change', function () { if (loaded && META.minKey && usableDate(this.value)) setRange(this.value ? isoToKey(this.value) : META.minKey, state.to); });
  $('f-to').addEventListener('change', function () { if (loaded && META.minKey && usableDate(this.value)) setRange(state.from, this.value ? isoToKey(this.value) : META.maxKey); });
  $('f-clear').addEventListener('click', function () {
    state.zone = ''; state.district = ''; state.from = META.minKey; state.to = META.maxKey;
    $('f-zone').value = ''; fillDistricts();
    if (loaded) { derive(); renderAll(true); }
    filtersChanged();
  });
  $('f-find').addEventListener('change', function () { state.filter = this.value; if (state.filter) { state.show = 'all'; syncSeg(); } renderMap(true); });
  document.querySelector('#mapsec .seg').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    state.show = b.dataset.show;
    if (state.show === 'n') { state.filter = ''; $('f-find').value = ''; }
    syncSeg(); renderMap(false);
  });
  $('psrpFindings').addEventListener('click', function (e) {
    var t = e.target.closest('.rtx');
    if (t) { if (!t.classList.contains('na')) t.classList.toggle('open'); return; }
    t = e.target.closest('[data-open]');
    if (t) { openById(t.dataset.open); $('map').scrollIntoView({ block: 'center' }); return; }
    t = e.target.closest('[data-filter]');
    if (t) { selectFilter(t.dataset.filter); return; }
    t = e.target.closest('[data-sort]');
    if (t) { var k = t.dataset.sort; if (state.sort === k) state.dir = -state.dir; else { state.sort = k; state.dir = (k === 'd' || k === 'z' || k === 'top' || k === 'cov') ? 1 : -1; } renderDistricts(); return; }
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
    var hits = ALL.filter(function (s) { return s.n.toLowerCase().indexOf(q) >= 0 || String(s.e).indexOf(q) >= 0; }).slice(0, 12);
    box.innerHTML = hits.map(function (s) {
      return '<button type="button" data-hit="' + esc(s.k) + '">' + esc(s.n) + '<small>EMIS ' + esc(s.e) + ' &middot; ' + esc(s.d) + ' &middot; ' + (s.V ? 'Visited, ' + STI[s.V.st].label.toLowerCase() : s.av.length ? 'Visited outside the dates chosen' : s.r.length ? 'Department responded' : 'Not visited') + '</small></button>';
    }).join('') || '<button type="button" disabled>No school found</button>';
    box.hidden = false;
  });
  $('results').addEventListener('click', function (e) {
    var b = e.target.closest('[data-hit]'); if (!b) return;
    openById(b.dataset.hit); $('results').hidden = true; $('q').value = '';
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
    if (loaded) fitScope();
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

    // Visits come once; the date filter picks from them. Response types don't change with dates.
    ALL.forEach(function (s) {
      s.av = s.v; s.ts = {};
      s.r.forEach(function (r) { r.it.forEach(function (it) { s.ts[it.t] = 1; }); });
    });

    var zones = Array.from(new Set(ALL.map(function (s) { return s.z; }).filter(Boolean))).sort();
    if (state.zone && zones.indexOf(state.zone) < 0) zones.push(state.zone);
    $('f-zone').innerHTML = '<option value="">All zones</option>' + zones.map(function (z) { return '<option>' + esc(z) + '</option>'; }).join('');
    $('f-zone').value = state.zone;
    fillDistricts();
    $('f-district').value = state.district;
    $('f-find').innerHTML = '<option value="">Any school</option><option value="rep">Department responded</option><option value="norep">Visited, no department response yet</option>' +
      group('Found at the visit', FIND, 'f:') + group('Is work going on?', STATUS, 's:') + group('Stage reached', STAGE, 'g:', 'Stage: ') +
      group('What is holding work back', CAUSE, 'c:') + group('Officials inspect the site', INSP.filter(function (t) { return t.id !== 'nr'; }), 'i:', 'Inspected: ') + group('Department response', RT, 't:', 'Response: ');

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
