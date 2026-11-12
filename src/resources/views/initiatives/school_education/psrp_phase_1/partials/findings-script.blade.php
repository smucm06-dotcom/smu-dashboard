<script>
// Everything above the records table is worked out here from one download,
// so changing a filter is instant and does not reload the page.
(function () {
  'use strict';

  var page = document.getElementById('psrpPage');
  var ALL = [], VIEW = [], BY_EMIS = {}, TWICE = 0;

  var ISSUES = [
    { id: 'danger', label: 'Dangerous classrooms', why: 'Risk to children' },
    { id: 'water', label: 'No clean drinking water', why: 'Basic need missing' },
    { id: 'salary', label: 'Staff salary not paid', why: 'Reported unpaid at the visit' },
    { id: 'teach', label: 'No teacher present', why: 'Students enrolled, no teacher at visit' },
    { id: 'fees', label: 'Students charged fees', why: 'Students were being charged' },
    { id: 'firstaid', label: 'No first aid' },
    { id: 'cls', label: 'Classrooms in poor condition', flag: 1 },
    { id: 'wash', label: 'Washrooms in poor condition', flag: 1 },
    { id: 'sch', label: 'School not clean' },
    { id: 'books', label: 'No free textbooks' },
    { id: 'furniture', label: 'No furniture for students' },
    { id: 'wall', label: 'No boundary wall' }
  ];
  var ISSUE = {}; ISSUES.forEach(function (i) { ISSUE[i.id] = i; });

  // A sharp, lasting fall in "Dilapidated" ratings part way through the visits, found from the
  // visits themselves: the month where the share drops the most, when the earlier share is at
  // least 15%, the later one a third of that or less, and each side has 20 or more ratings.
  // Null when the visits show no such fall, so the note and its marks only show when it is true.
  var dilShift = null;
  function ratingShift(visits, fields) {
    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], by = {};
    visits.forEach(function (v) {
      var b = by[String(v.k).slice(0, 6)] = by[String(v.k).slice(0, 6)] || {};
      fields.forEach(function (f) { if (!v[f]) return; var c = b[f] = b[f] || [0, 0]; c[1]++; if (v[f] === 'Dilapidated') c[0]++; });
    });
    var months = Object.keys(by).sort(), best = null;
    for (var i = 1; i < months.length; i++) {
      var side = { e: {}, l: {} }, tot = { e: [0, 0], l: [0, 0] };
      months.forEach(function (m, j) {
        var s = j < i ? 'e' : 'l';
        fields.forEach(function (f) { var c = by[m][f] || [0, 0], t = side[s][f] = side[s][f] || [0, 0]; t[0] += c[0]; t[1] += c[1]; tot[s][0] += c[0]; tot[s][1] += c[1]; });
      });
      if (tot.e[1] < 20 || tot.l[1] < 20) continue;
      var e = tot.e[0] / tot.e[1], l = tot.l[0] / tot.l[1];
      if (!best || e - l > best.e - best.l) best = { e: e, l: l, i: i, side: side };
    }
    if (!best || best.e < 0.15 || best.l > best.e / 3) return null;
    var label = function (m) { return MONTHS[Number(m.slice(4, 6)) - 1] + ' ' + m.slice(0, 4); };
    var share = function (c) { return c && c[1] ? Math.round(c[0] / c[1] * 100) + '%' : '–'; };
    return { upTo: label(months[best.i - 1]), from: label(months[best.i]), early: function (f) { return share(best.side.e[f]); }, late: function (f) { return share(best.side.l[f]); } };
  }
  var HIGHLIGHT = ['danger', 'water', 'salary', 'teach', 'fees'];
  var OUTCOMES = [
    { id: 'penalty', label: 'Penalty imposed', sub: 'The operator was penalised', badge: 'b-crit' },
    { id: 'warning', label: 'Warning issued', sub: 'Warning letter sent to the operator', badge: 'b-warn' },
    { id: 'fixed', label: 'Operator says fixed', sub: 'Fixed or being fixed, by the operator\'s own statement', badge: 'b-info' },
    { id: 'inquiry', label: 'Under inquiry', sub: 'Investigation still running', badge: 'b-warn' },
    { id: 'cleared', label: 'No action needed', sub: 'Department closed it, often on operator evidence', badge: 'b-good' },
    { id: 'na', label: 'Not applicable', sub: 'Not handed over, or outside the programme', badge: 'b-mute' }
  ];
  var OUT = {}; OUTCOMES.forEach(function (o) { OUT[o.id] = o; });

  var $ = function (id) { return document.getElementById(id); };
  var state = { district: $('f-district').value, from: $('f-from').value, to: $('f-to').value, show: 'all', issue: '', sel: null, sort: 'cov', dir: 1 };
  var loaded = false;

  function esc(s) { return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function n(x) { return Number(x).toLocaleString('en-US'); }
  function pct(a, b) { if (!b) return 0; var p = a / b * 100; return p > 0 && p < 0.5 ? '<1' : Math.round(p); }
  function pl(c, one, many) { return n(c) + ' ' + (c === 1 ? one : many); }
  function iss(s) { return s.v ? s.v.i : []; }
  function scoped() { return state.district ? VIEW.filter(function (s) { return s.d === state.district; }) : VIEW; }
  function hasDates() { return !!(state.from || state.to); }

  /* Date range */
  function inRange(iso) { return (!state.from || iso >= state.from) && (!state.to || iso <= state.to); }
  function visitInRange(s) {
    if (!s.v) return null;
    if (inRange(s.v.iso)) return s.v;
    return (s.vv || []).filter(function (v) { return inRange(v.iso); })[0] || null;
  }
  // With dates chosen, a school counts as visited only if it has a visit in them, and a reply
  // counts only for those schools. Schools not on the department's list appear only when visited.
  function applyDates() {
    VIEW = [];
    BY_EMIS = {};
    ALL.forEach(function (s) {
      var item = s;
      if (hasDates()) {
        var v = visitInRange(s);
        if (v) {
          if (v !== s.v) item = Object.assign({}, s, { v: v });
        } else if (s.off) {
          return;
        } else if (s.v || s.r) {
          item = Object.assign({}, s, { hid: s.v ? 1 : 0 });
          delete item.v; delete item.vv; delete item.r;
        }
      }
      VIEW.push(item);
      BY_EMIS[item.e] = item;
    });
    markers = {};
    DROWS = null;
  }
  function longDate(iso) {
    var p = iso.split('-');
    return Number(p[2]) + ' ' + ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][Number(p[1]) - 1] + ' ' + p[0];
  }
  function scopeText() {
    var dates = state.from && state.to ? ', visits from ' + longDate(state.from) + ' to ' + longDate(state.to)
      : state.from ? ', visits from ' + longDate(state.from) + ' onwards'
      : state.to ? ', visits up to ' + longDate(state.to) : '';
    return 'Everything below shows ' + (state.district ? state.district + ' only' : 'all districts') + dates;
  }

  // What the page shows, in the filter bar. Where the one-row bar has no room for the whole
  // sentence it is said more briefly, and hovering always shows all of it.
  function fitScope() {
    var el = $('scope'), text = scopeText();
    el.textContent = text;
    el.title = text;
    if (el.clientWidth && el.scrollWidth > el.clientWidth + 1) el.textContent = text.replace(/^Everything below shows /, 'Showing ');
  }

  /* Summary */
  function summarise(list) {
    var o = { visited: 0, withIssues: 0, issues: 0, clean: 0, replied: 0, addrSchools: 0, addrIssues: 0, outs: {}, count: {}, pen: {}, sed: 0, clearedWith: 0, enr: 0, han: 0, up: 0, down: 0, cmp: 0, up2: 0, proc: 0, dil: 0, withoutDil: 0, notHanded: 0 };
    OUTCOMES.forEach(function (x) { o.outs[x.id] = 0; });
    ISSUES.forEach(function (x) { o.count[x.id] = 0; o.pen[x.id] = 0; });
    list.forEach(function (s) {
      var v = s.v, r = s.r, i = iss(s);
      if (r) {
        o.replied++; o.outs[r.o]++; o.sed += r.sed;
        r.pen.forEach(function (k) { o.pen[k]++; });
        if (r.o === 'cleared' && i.length) o.clearedWith++;
      }
      if (!v) return;
      o.visited++;
      o.issues += i.length;
      if (i.length) o.withIssues++; else o.clean++;
      i.forEach(function (k) { o.count[k]++; });
      if (r && r.o !== 'na' && i.length) { o.addrSchools++; o.addrIssues += i.length; }
      if (v.ho === 0) o.notHanded++;
      if (v.cc === 'Dilapidated' || v.wc === 'Dilapidated') o.dil++;
      if (i.some(function (k) { return !((k === 'cls' && v.cc === 'Dilapidated') || (k === 'wash' && v.wc === 'Dilapidated')); })) o.withoutDil++;
      if (v.en > 0 && v.ha > 0) { o.cmp++; o.enr += v.en; o.han += v.ha; if (v.en > v.ha) o.up++; if (v.en < v.ha) o.down++; }
      if (s.ls === 'Under Process') { o.proc++; if (v.ho === 1) o.up2++; }
    });
    return o;
  }

  var ICONS = {
    't-crit': '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 10v4.5"/><path d="M12 17.5v.01"/>',
    't-info': '<path d="M12 3l7.5 3v5.5c0 4.6-3.1 7.9-7.5 9.5-4.4-1.6-7.5-4.9-7.5-9.5V6L12 3Z"/><path d="m8.8 12 2.3 2.3 4.2-4.3"/>',
    't-warn': '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.2 2"/>',
    't-good': '<circle cx="12" cy="12" r="9"/><path d="m8 12.3 2.8 2.7 5.2-5.6"/>'
  };
  function renderSummary(o) {
    var where = state.district ? ' in ' + esc(state.district) : '';
    if (!o.visited) {
      $('verdict').innerHTML = 'No SMU visits are recorded' + where + (hasDates() ? ' in these dates.' : ' yet.');
    } else {
      $('verdict').innerHTML = 'Field teams found issues at <b>' + pct(o.withIssues, o.visited) + '% of the schools they visited</b>' + where + '. ' + (o.addrIssues ? 'The department\'s replies cover <b>' + pct(o.addrIssues, o.issues) + '% of those issues</b>' + (o.addrIssues / o.issues < 0.5 ? ', so most still have no recorded action.' : '.') : '<b>None of those issues has a department outcome on file.</b>');
    }
    var open = o.issues - o.addrIssues;
    $('stats').innerHTML = [
      ['t-crit', 'Issues found', n(o.issues), 'At ' + pl(o.withIssues, 'school', 'schools') + ', ' + pct(o.withIssues, o.visited) + '% of schools visited'],
      ['t-info', 'Issues addressed', n(o.addrIssues), 'At ' + pl(o.addrSchools, 'school', 'schools') + ' where the department replied with an outcome'],
      ['t-warn', 'Issues with no recorded action', n(open), 'At ' + pl(o.withIssues - o.addrSchools, 'school', 'schools') + ' with no department outcome on file'],
      ['t-good', 'Schools with no issues', n(o.clean), pct(o.clean, o.visited) + '% of schools visited']
    ].map(function (c) {
      return '<div class="card stat ' + c[0] + '"><div class="stat-top"><span class="stat-ico"><svg viewBox="0 0 24 24" aria-hidden="true">' + ICONS[c[0]] + '</svg></span><div class="stat-lbl">' + c[1] + '</div></div><div class="stat-num">' + c[2] + '</div><div class="stat-sub">' + c[3] + '</div></div>';
    }).join('');
    var p = pct(o.addrIssues, o.issues);
    $('progress').innerHTML = '<div class="progress-h"><b>Share of issues addressed so far</b><span>' + n(o.addrIssues) + ' of ' + n(o.issues) + ' issues (' + p + '%)</span></div><div class="track" role="img" aria-label="' + p + ' percent of issues addressed"><i style="width:' + (o.issues ? o.addrIssues / o.issues * 100 : 0) + '%"></i></div>';

    $('out-sub').textContent = pl(o.replied, 'reply', 'replies') + ' linked to Phase 1 schools' + where.replace(/&amp;/g, '&') + '. Each school is counted once, under its main outcome.' + (hasDates() ? ' Only schools visited in these dates are counted.' : '');
    $('outs').innerHTML = [
      ['penalty', o.outs.penalty + o.outs.warning, 'Penalty or warning', 'The operator was penalised or warned'],
      ['fixed', o.outs.fixed, OUT.fixed.label, 'By the operator\'s statement, not re-checked'],
      ['inquiry', o.outs.inquiry, OUT.inquiry.label, OUT.inquiry.sub],
      ['cleared', o.outs.cleared, OUT.cleared.label, n(o.clearedWith) + ' of these had issues on the visit form'],
      ['na', o.outs.na, OUT.na.label, OUT.na.sub]
    ].map(function (c) {
      return '<div class="card out"><div class="out-num">' + n(c[1]) + '</div><div class="out-lbl">' + esc(c[2]) + '</div><div class="out-sub">' + esc(c[3]) + '</div></div>';
    }).join('');

    $('caveat').innerHTML = '<b>"Addressed" does not mean fixed on the ground.</b> It means the department recorded an outcome. Only ' + n(TWICE) + ' schools were visited twice, so no fix has been confirmed by a return visit. Return visits are the way to prove what really changed.';

    var growth = o.han ? Math.round((o.enr - o.han) / o.han * 100) : 0;
    $('since').innerHTML =
      '<div class="card"><h3>Students, since handover</h3><div class="since-num">' + n(o.han) + ' &rarr; ' + n(o.enr) + (o.han ? ' (' + (growth >= 0 ? '+' : '') + growth + '%)' : '') + '</div>' +
      '<p class="since-sub">Enrolment rose at ' + n(o.up) + ' schools and fell at ' + n(o.down) + '. Based on ' + n(o.cmp) + ' visited schools with both figures. This shows change since handover, not an effect of the visits.</p></div>' +
      '<div class="card"><h3>Handover progress</h3><div class="since-num">' + n(o.up2) + ' of ' + n(o.proc) + ' schools</div>' +
      '<p class="since-sub">These were still "Under Process" on the department list and were found handed over when visited. ' + pl(o.notHanded, 'visited school was', 'visited schools were') + ' not handed over at the time of the visit.</p></div>';
  }

  /* Issues */
  function selectIssue(id) {
    state.issue = id; state.show = 'all'; $('f-issue').value = id; syncSeg(); renderMap(true);
    $('mapsec').scrollIntoView();
  }
  function renderIssues(o, list) {
    dilShift = ratingShift(list.filter(function (s) { return s.v; }).map(function (s) { return { k: s.v.iso.replace(/-/g, ''), cc: s.v.cc, wc: s.v.wc }; }), ['cc', 'wc']);
    $('tiles').innerHTML = HIGHLIGHT.map(function (id) {
      var i = ISSUE[id];
      return '<button type="button" class="tile" data-issue="' + id + '"><b>' + n(o.count[id]) + '</b><span>' + esc(i.label) + '</span><small>' + esc(i.why) + ' &middot; ' + pct(o.count[id], o.visited) + '% of visited</small></button>';
    }).join('');
    var ranked = ISSUES.slice().sort(function (a, b) { return o.count[b.id] - o.count[a.id]; });
    var max = Math.max.apply(null, ranked.map(function (i) { return o.count[i.id]; }).concat([1]));
    $('irows').innerHTML = '<div class="irow irow-h"><span>Issue</span><span class="hb"></span><span class="num">Schools</span><span class="num">Penalties</span></div>' + ranked.map(function (i) {
      var c = o.count[i.id];
      return '<button type="button" class="irow" data-issue="' + i.id + '"><span>' + esc(i.label) + (i.flag && dilShift ? ' <span class="flag" title="See the note below">*</span>' : '') + '</span><span class="bar"><i style="width:' + (c / max * 100) + '%"></i></span><span class="num">' + n(c) + ' <span class="pct">' + pct(c, o.visited) + '%</span></span><span class="num">' + (o.pen[i.id] ? n(o.pen[i.id]) : '<span class="pct">&ndash;</span>') + '</span></button>';
    }).join('');
    var notes = dilShift ? '<p class="note"><span class="flag">*</span> Check before relying on these two. Visits up to ' + dilShift.upTo + ' rated ' + dilShift.early('cc') + ' of classrooms and ' + dilShift.early('wc') + ' of washrooms "Dilapidated"; from ' + dilShift.from + ' the share was ' + dilShift.late('cc') + ' and ' + dilShift.late('wc') + '. Different schools were visited in each period, so this may be a change in how the form was filled, not an improvement. Leaving those ratings out, issues appear at ' + n(o.withoutDil) + ' schools instead of ' + n(o.withIssues) + '.</p>' : '';
    if (o.sed) notes += '<p class="note">For ' + pl(o.sed, 'school', 'schools') + ', the department said the boundary wall is the School Education Department\'s responsibility, not the operator\'s.</p>';
    $('inotes').innerHTML = notes;
    var top = list.filter(function (s) { return iss(s).length; }).sort(function (a, b) { return iss(b).length - iss(a).length || a.n.localeCompare(b.n); }).slice(0, 10);
    $('top').innerHTML = top.map(function (s) {
      return '<button type="button" data-open="' + esc(s.e) + '"><span>' + esc(s.n) + '<small>' + esc(s.d) + (s.r ? ' &middot; ' + esc(OUT[s.r.o].label) : ' &middot; No reply on file') + '</small></span><b>' + iss(s).length + ' issues</b></button>';
    }).join('') || '<p class="sub">No issues recorded.</p>';
  }

  /* Districts */
  function districtRows() {
    var by = {};
    VIEW.forEach(function (s) { (by[s.d] = by[s.d] || []).push(s); });
    return Object.keys(by).map(function (d) {
      var o = summarise(by[d]);
      var top = ISSUES.slice().sort(function (a, b) { return o.count[b.id] - o.count[a.id]; })[0];
      return { d: d, total: by[d].length, visited: o.visited, cov: o.visited / by[d].length, rate: o.visited ? o.withIssues / o.visited : 0, per: o.visited ? o.issues / o.visited : 0, top: o.count[top.id] ? top.label : '', topN: o.count[top.id], replied: o.replied, pen: o.outs.penalty + o.outs.warning };
    });
  }
  var DCOLS = [
    { k: 'd', t: 'District' }, { k: 'total', t: 'Schools on list', r: 1 }, { k: 'visited', t: 'Visited', r: 1 }, { k: 'cov', t: 'Share visited' },
    { k: 'rate', t: 'Visited schools with issues', r: 1 }, { k: 'per', t: 'Issues per school', r: 1 }, { k: 'top', t: 'Most common issue' },
    { k: 'replied', t: 'Replies', r: 1 }, { k: 'pen', t: 'Penalties or warnings', r: 1 }
  ];
  var DROWS = null;
  var ISSUE_FLAG = {}; ISSUES.forEach(function (i) { if (i.flag) ISSUE_FLAG[i.label] = 1; });
  function insight(title, body) { return '<div class="card insight"><small>' + title + '</small>' + body + '</div>'; }
  function renderDistricts() {
    DROWS = DROWS || districtRows();
    var k = state.sort, dir = state.dir;
    var rows = DROWS.slice().sort(function (a, b) {
      var x = a[k], y = b[k];
      return (typeof x === 'string' ? x.localeCompare(y) : x - y) * dir || a.d.localeCompare(b.d);
    });
    $('dt').innerHTML = '<thead><tr>' + DCOLS.map(function (c) {
      var on = c.k === k;
      return '<th scope="col" class="' + (c.r ? 'r' : '') + '" aria-sort="' + (on ? (dir > 0 ? 'ascending' : 'descending') : 'none') + '"><button type="button" data-sort="' + c.k + '">' + c.t + (on ? (dir > 0 ? ' ↑' : ' ↓') : '') + '</button></th>';
    }).join('') + '</tr></thead><tbody>' + rows.map(function (r) {
      return '<tr data-district="' + esc(r.d) + '"' + (r.d === state.district ? ' class="on"' : '') + '>' +
        '<td><button type="button" class="dname" data-district="' + esc(r.d) + '">' + esc(r.d) + '</button></td><td class="r">' + n(r.total) + '</td><td class="r">' + n(r.visited) + '</td>' +
        '<td><div class="cov"><div class="bar"><i style="width:' + r.cov * 100 + '%"></i></div><span>' + Math.round(r.cov * 100) + '%</span></div></td>' +
        '<td class="r">' + (r.visited ? Math.round(r.rate * 100) + '%' : '&ndash;') + '</td><td class="r">' + (r.visited ? r.per.toFixed(1) : '&ndash;') + '</td>' +
        '<td>' + esc(r.top) + (r.top && ISSUE_FLAG[r.top] && dilShift ? ' <span class="flag">*</span>' : '') + '</td><td class="r">' + n(r.replied) + '</td><td class="r">' + n(r.pen) + '</td></tr>';
    }).join('') + '</tbody>';
    var big = DROWS.filter(function (r) { return r.total >= 30; });
    var minCov = big.slice().sort(function (a, b) { return a.cov - b.cov; }).slice(0, 3);
    var reliable = DROWS.filter(function (r) { return r.visited >= 30; });
    var maxRate = reliable.slice().sort(function (a, b) { return b.per - a.per; })[0];
    var maxRep = DROWS.slice().sort(function (a, b) { return b.replied - a.replied; })[0];
    $('insights').innerHTML =
      insight('Fewest schools visited', minCov.length ? minCov.map(function (r) { return '<b>' + esc(r.d) + '</b> ' + Math.round(r.cov * 100) + '%'; }).join(', ') : 'Not enough schools to compare') +
      insight('Most issues per visited school', maxRate ? '<b>' + esc(maxRate.d) + '</b>, ' + maxRate.per.toFixed(1) + ' issues per school across ' + n(maxRate.visited) + ' visits' : 'Not enough visits to compare') +
      insight('Most department replies', maxRep && maxRep.replied ? '<b>' + esc(maxRep.d) + '</b>, ' + n(maxRep.replied) + ' replies including ' + n(maxRep.pen) + ' penalties or warnings' : 'No replies on file');
  }

  /* Map */
  var map = L.map('map', { preferCanvas: true, minZoom: 5, maxZoom: 18, zoomSnap: 0.5 });
  map.attributionControl.setPrefix(false); // No "Leaflet" or flag in the corner, only the basemap's credit.
  var tileBase = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_';
  L.tileLayer(tileBase + 'Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', attribution: 'Basemap &copy; Esri, HERE, Garmin, OpenStreetMap contributors' }).addTo(map);
  L.tileLayer(tileBase + 'Reference/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', pane: 'overlayPane' }).addTo(map);
  var css = getComputedStyle($('psrpFindings'));
  var C = { good: css.getPropertyValue('--good').trim(), crit: css.getPropertyValue('--crit').trim(), surf: css.getPropertyValue('--surface').trim(), ink: css.getPropertyValue('--ink').trim() };
  var renderer = L.canvas({ padding: 0.3, tolerance: 5 });
  var pins = L.layerGroup().addTo(map), markers = {};
  var ring = null, focusPin = null;
  var PUNJAB = [[27.7, 69.3], [34.1, 75.5]];
  function radius() { var z = map.getZoom() || 7; return z < 7 ? 2.5 : z < 8 ? 3 : z < 10 ? 4 : z < 12 ? 5.5 : 7; }
  function makeMarker(s) {
    var opt = s.v
      ? { radius: radius(), renderer: renderer, stroke: true, color: C.surf, weight: 0.8, fillColor: C.good, fillOpacity: 1 }
      : { radius: radius(), renderer: renderer, stroke: true, color: C.crit, weight: 1.8, fillColor: C.surf, fillOpacity: 1 };
    var m = L.circleMarker([s.la, s.lo], opt);
    m.bindTooltip(function () { return esc(s.n) + (s.v ? ' (visited)' : ' (not visited)'); }, { direction: 'top', offset: [0, -4] });
    m.on('click', function () { openSchool(s, false); });
    return m;
  }
  function matchesMap(s) {
    if (state.show === 'v' && !s.v) return false;
    if (state.show === 'n' && s.v) return false;
    if (state.issue && iss(s).indexOf(state.issue) < 0) return false;
    return true;
  }
  function renderMap(fit) {
    pins.clearLayers();
    var list = scoped().filter(matchesMap);
    var shownV = 0, shownN = 0, unmapped = 0, pts = [], visitIds = [];
    list.sort(function (a, b) { return (b.v ? 1 : 0) - (a.v ? 1 : 0); });
    list.forEach(function (s) {
      if (s.la === undefined) { unmapped++; return; }
      if (s.v) { shownV++; visitIds.push(s.v.id); } else shownN++;
      var m = markers[s.e] || (markers[s.e] = makeMarker(s));
      pins.addLayer(m);
      pts.push([s.la, s.lo]);
    });
    // Only a chosen issue can be downloaded, and the file holds exactly the schools on the map.
    $('map-download').hidden = !(state.issue && visitIds.length);
    if (state.issue) {
      $('dl-ids').value = visitIds.join(',');
      $('dl-issue').value = ISSUE[state.issue].label;
      $('dl-label').textContent = 'Download ' + pl(visitIds.length, 'school', 'schools');
    }
    var label = state.issue ? ' with "' + esc(ISSUE[state.issue].label) + '"' : '';
    $('legend').innerHTML = (state.show !== 'n' ? '<div class="lg"><span class="pin v"></span>Visited <span class="lg-n">' + n(shownV) + ' pins' + label + '</span></div>' : '') +
      (state.show !== 'v' && !state.issue ? '<div class="lg"><span class="pin n"></span>Not visited <span class="lg-n">' + n(shownN) + ' pins</span></div>' : '');
    $('map-note').textContent = (unmapped ? n(unmapped) + (unmapped === 1 ? ' school' : ' schools') + ' in this view have no usable coordinates, so they are not on the map. Search finds them. ' : '') +
      (state.issue ? (pts.length ? 'Issue filter is on, so not-visited schools are hidden.' : 'No school in this view has this issue.') : '');
    if (fit) {
      var zone = pts.length ? pts : scoped().filter(function (s) { return s.la !== undefined; }).map(function (s) { return [s.la, s.lo]; });
      if (zone.length && (state.district || state.issue)) map.fitBounds(L.latLngBounds(zone).pad(0.08), { maxZoom: 12 });
      else map.fitBounds(PUNJAB);
    }
    if (state.sel) placeRing(BY_EMIS[state.sel] || null);
  }
  map.on('zoomend', function () { var r = radius(); Object.keys(markers).forEach(function (k) { markers[k].setRadius(r); }); if (focusPin) focusPin.setRadius(r); });
  // The school opened always shows inside its ring, even when the map's choices hide its pin.
  function placeRing(s) {
    if (ring) { map.removeLayer(ring); ring = null; }
    if (focusPin) { map.removeLayer(focusPin); focusPin = null; }
    if (s && s.la !== undefined) {
      if (!markers[s.e] || !pins.hasLayer(markers[s.e])) focusPin = makeMarker(s).addTo(map);
      ring = L.circleMarker([s.la, s.lo], { radius: 12, color: C.ink, weight: 2.5, fill: false, interactive: false, renderer: renderer }).addTo(map);
    }
  }
  function syncSeg() {
    document.querySelectorAll('.seg button').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.show === state.show)); });
  }

  /* School report panel */
  function yesNo(val, goodWhen) {
    if (val === null || val === undefined) return '<span class="na">Not recorded</span>';
    var isGood = val === goodWhen;
    return '<span class="' + (isGood ? 'ok' : 'bad') + '">' + (isGood ? '✓ ' : '✗ ') + (val === 1 ? 'Yes' : 'No') + '</span>';
  }
  function cond(val) { return val ? '<span class="' + (val === 'Clean' ? 'ok' : 'bad') + '">' + (val === 'Clean' ? '✓ ' : '✗ ') + esc(val) + '</span>' : '<span class="na">Not recorded</span>'; }
  function neutral(val) { return val === null || val === undefined ? '<span class="na">Not recorded</span>' : (val === 1 ? 'Yes' : val === 0 ? 'No' : esc(val)); }
  function count(val) { return val === null || val === undefined ? '<span class="na">Not recorded</span>' : n(val); }
  function rows(list) { return '<table class="rt"><tbody>' + list.map(function (r) { return '<tr><td>' + r[0] + '</td><td>' + r[1] + '</td></tr>'; }).join('') + '</tbody></table>'; }

  function renderHint() {
    $('panel').innerHTML = '<div class="panel-hint"><h3>School report</h3><p>Click a pin on the map, or search above, to see the school\'s report here.</p><ul>' +
      '<li><b>Green pin:</b> visited. You will see the visit findings and any department reply.</li>' +
      '<li><b>Red ring:</b> not visited yet. You will see what the department list says about the school.</li></ul></div>';
  }
  function openSchool(s, focus, keepPlace) {
    state.sel = s.e;
    placeRing(s);
    if (focus && s.la !== undefined) map.setView([s.la, s.lo], Math.max(map.getZoom(), 12));
    var v = s.v, r = s.r, h = '';
    h += '<div class="p-top"><div><div class="p-name">' + esc(s.n) + '</div><div class="p-meta">EMIS ' + esc(s.e) + ' &middot; ' + esc(s.d) + (s.t ? ' &middot; ' + esc(s.t) : '') + '</div>' +
      (s.o ? '<div class="p-meta">Operator: ' + esc(s.o) + (s.c ? ' (' + esc(s.c) + ')' : '') + '</div>' : '') + '</div>' +
      '<button type="button" class="x" id="p-close" aria-label="Close report">×</button></div>';
    h += '<div class="badges">' + (v ? '<span class="badge b-good">● Visited ' + esc(v.dt) + '</span>' : '<span class="badge b-crit">○ Not visited' + (s.hid ? ' in these dates' : '') + '</span>') +
      (r ? '<span class="badge ' + OUT[r.o].badge + '">' + esc(OUT[r.o].label) + '</span>' : (v ? '<span class="badge b-mute">No reply on file</span>' : '')) + '</div>';
    if (s.la === undefined) h += '<p class="note">This school has no usable coordinates, so it has no pin on the map.</p>';
    if (s.off) h += '<p class="note">This EMIS code is not on the department\'s Phase 1 list.</p>';
    if (v) {
      h += '<div class="p-sec"><h3>' + (v.i.length ? pl(v.i.length, 'issue', 'issues') + ' found' : 'No issues found') + '</h3>' +
        (v.i.length ? '<div class="chips">' + v.i.map(function (k) { return '<span class="chip">' + esc(ISSUE[k].label) + '</span>'; }).join('') + '</div>' : '') + '</div>';
    }
    if (r) {
      h += '<div class="p-sec"><h3>Department reply' + (r.dt ? ', ' + esc(r.dt) : '') + '</h3><div class="reply">' +
        (r.rm ? '<p><small>Sent to the department</small>' + esc(r.rm) + '</p>' : '') +
        '<p><small>Department said</small>' + esc(r.tx) + '</p></div></div>';
    }
    if (v) {
      h += '<div class="p-sec"><h3>Visit findings</h3>' + rows([
        ['School handed over', yesNo(v.ho, 1)], ['Staff salary paid', yesNo(v.sal, 1)], ['Students charged fees', yesNo(v.fee, 0)],
        ['Free textbooks', yesNo(v.bk, 1)], ['Clean drinking water', yesNo(v.wat, 1)], ['First aid', yesNo(v.fa, 1)],
        ['Furniture for students', yesNo(v.fu, 1)], ['Boundary wall', yesNo(v.wall, 1)],
        ['Dangerous classrooms', v.dg === null ? '<span class="na">Not recorded</span>' : '<span class="' + (v.dg > 0 ? 'bad' : 'ok') + '">' + (v.dg > 0 ? '✗ ' : '✓ ') + n(v.dg) + '</span>'],
        ['Classrooms', cond(v.cc)], ['Washrooms', cond(v.wc)], ['Overall cleanliness', cond(v.sc)],
        ['One grade per classroom', neutral(v.gr)], ['School council set up', neutral(v.cou)], ['Council meetings', count(v.mt)]
      ]) + '</div>';
      h += '<div class="p-sec"><h3>School numbers</h3>' + rows([
        ['Students enrolled now', count(v.en)], ['Students at handover', count(v.ha)], ['Students present on visit day', count(v.pr)],
        ['Teachers', count(v.te)], ['Teachers present at visit', count(v.tp)], ['Classrooms', count(v.cr)], ['Washrooms', count(v.wr)],
        ['Teachers in PEF training', count(v.tt)], ['Teachers nominated for CPDP', count(v.tn)]
      ]) + '</div>';
      if (v.nv > 1) h += '<p class="note">This school was visited ' + v.nv + ' times. The latest visit' + (hasDates() ? ' in these dates' : '') + ' is shown.</p>';
    } else {
      h += '<div class="p-sec"><h3>From the department list</h3>' + rows([
        ['Handover status', esc(s.ls || 'Not recorded')], ['Category', esc(s.c || 'Not recorded')]
      ]) + (s.ll ? '<p class="note">Remark: ' + esc(s.ll) + '</p>' : '') + '<p class="note">' + (s.hid ? 'This school was visited outside these dates. Clear the dates to see that report.' : 'No SMU visit report is on file for this school.') + '</p></div>';
    }
    var panel = $('panel');
    panel.innerHTML = h;
    panel.scrollTop = 0;
    $('p-close').addEventListener('click', function () { state.sel = null; placeRing(null); renderHint(); });
    if (!keepPlace && page.clientWidth <= 1100) panel.scrollIntoView({ block: 'start' });
  }
  // A school that has just left the filtered view takes its report with it.
  function syncPanel() {
    if (!state.sel) return;
    var s = BY_EMIS[state.sel];
    if (s && (!state.district || s.d === state.district)) openSchool(s, false, true);
    else { state.sel = null; placeRing(null); renderHint(); }
  }

  /* Page */
  /* The four cards in the banner, for what the filters select, counted as the figures below:
     schools on the department's list, visits in the dates (a school visited twice counts twice),
     and schools answered by the department, where their reply counts. Reports Sent to Department
     counts our uploaded report PDFs, which cover every district and date, so it is left as drawn. */
  function setCard(id, value) { var el = $(id); if (el) el.textContent = n(value); }
  function renderTopbar(list) {
    var visits = 0;
    ALL.forEach(function (s) {
      if (!s.v || (state.district && s.d !== state.district)) return;
      [s.v].concat(s.vv || []).forEach(function (v) { if (inRange(v.iso)) visits++; });
    });
    setCard('c-total', list.filter(function (s) { return !s.off; }).length);
    setCard('c-visited', visits);
    setCard('c-replied', list.filter(function (s) { return s.r && String(s.r.tx || '').trim() !== ''; }).length);
  }

  function renderAll(fit) {
    var list = scoped(), o = summarise(list);
    fitScope();
    $('f-clear').hidden = !state.district && !hasDates();
    renderTopbar(list);
    renderSummary(o);
    renderIssues(o, list);
    renderDistricts();
    renderMap(fit);
  }
  function filtersChanged(fit) {
    if (loaded) { syncPanel(); renderAll(fit); }
    document.dispatchEvent(new CustomEvent('psrp:filters', { detail: { district: state.district, from: state.from, to: state.to } }));
  }
  function setDistrict(d) {
    state.district = d;
    $('f-district').value = d;
    filtersChanged(true);
  }
  function setDates(from, to) {
    // Dates entered the wrong way round still mean the same range.
    if (from && to && from > to) { var swap = from; from = to; to = swap; }
    state.from = from; state.to = to;
    $('f-from').value = from; $('f-to').value = to;
    if (loaded) applyDates();
    filtersChanged(false);
  }
  // Typing a date passes through years like 0002, which are not worth acting on.
  function usableDate(value) { return value === '' || value >= '2000-01-01'; }
  function datesEdited() {
    var from = $('f-from').value, to = $('f-to').value;
    if (usableDate(from) && usableDate(to)) setDates(from, to);
  }
  function openById(id) {
    var s = BY_EMIS[id];
    if (s) openSchool(s, true);
  }

  $('psrpFilters').addEventListener('submit', function (e) { e.preventDefault(); });
  $('f-district').addEventListener('change', function () { setDistrict(this.value); });
  $('f-from').addEventListener('change', datesEdited);
  $('f-to').addEventListener('change', datesEdited);
  $('f-clear').addEventListener('click', function () {
    state.district = ''; $('f-district').value = '';
    setDates('', '');
    if (loaded) renderMap(true);
  });
  $('f-issue').innerHTML = '<option value="">Any issue or none</option>' + ISSUES.map(function (i) { return '<option value="' + i.id + '">' + esc(i.label) + '</option>'; }).join('');
  $('f-issue').addEventListener('change', function () { state.issue = this.value; if (state.issue) { state.show = 'all'; syncSeg(); } renderMap(true); });
  document.querySelector('.seg').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    state.show = b.dataset.show;
    if (state.show === 'n') { state.issue = ''; $('f-issue').value = ''; }
    syncSeg(); renderMap(false);
  });
  $('psrpFindings').addEventListener('click', function (e) {
    var t = e.target.closest('[data-issue]');
    if (t) { selectIssue(t.dataset.issue); return; }
    t = e.target.closest('[data-open]');
    if (t) { openById(t.dataset.open); $('map').scrollIntoView({ block: 'center' }); return; }
    t = e.target.closest('[data-sort]');
    if (t) {
      var k = t.dataset.sort;
      if (state.sort === k) state.dir = -state.dir; else { state.sort = k; state.dir = (k === 'd' || k === 'top' || k === 'cov') ? 1 : -1; }
      renderDistricts(); return;
    }
    t = e.target.closest('tr[data-district], .dname');
    if (t) { setDistrict(t.dataset.district); $('changed').scrollIntoView(); }
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.search')) $('results').hidden = true;
  });
  $('q').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase(), box = $('results');
    if (q.length < 2) { box.hidden = true; return; }
    var hits = VIEW.filter(function (s) { return s.n.toLowerCase().indexOf(q) >= 0 || s.e.indexOf(q) >= 0; }).slice(0, 12);
    box.innerHTML = hits.map(function (s) {
      return '<button type="button" data-hit="' + esc(s.e) + '">' + esc(s.n) + '<small>EMIS ' + esc(s.e) + ' &middot; ' + esc(s.d) + (s.v ? ' &middot; Visited' : ' &middot; Not visited') + '</small></button>';
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
  // The links stay beside the filters while their text and gaps fit (see the shared filter bar styles).
  function measureLinks() {
    if (!links || !links.offsetWidth) return;
    var list = links.querySelectorAll('a'), total = (parseFloat(getComputedStyle(links).columnGap) || 0) * (list.length - 1);
    list.forEach(function (a) { var r = document.createRange(); r.selectNodeContents(a); total += r.getBoundingClientRect().width; });
    $('psrpFilters').style.setProperty('--nav-text', Math.ceil(total) + 2 + 'px');
  }
  function setScrollOffset() {
    measureLinks();
    var sticky = getComputedStyle(bar).position === 'sticky';
    document.documentElement.style.scrollPaddingTop = (banner.offsetHeight + (sticky ? bar.offsetHeight : 0) + 12) + 'px';
    fitScope();
  }
  setScrollOffset();
  var offsetWatcher = new ResizeObserver(setScrollOffset);
  offsetWatcher.observe(bar);
  offsetWatcher.observe(banner);
  window.addEventListener('resize', setScrollOffset);
  new ResizeObserver(function () { map.invalidateSize(); }).observe($('map'));

  function start(data) {
    ALL = data.schools;
    TWICE = data.twice;

    var names = Array.from(new Set(ALL.map(function (s) { return s.d; }))).sort();
    if (state.district && names.indexOf(state.district) < 0) names.push(state.district);
    $('f-district').innerHTML = '<option value="">All districts</option>' + names.map(function (d) { return '<option>' + esc(d) + '</option>'; }).join('');
    $('f-district').value = state.district;

    var dates = [];
    ALL.forEach(function (s) {
      if (s.v) dates.push(s.v.iso);
      (s.vv || []).forEach(function (v) { dates.push(v.iso); });
    });
    if (dates.length) {
      dates.sort();
      ['f-from', 'f-to'].forEach(function (id) { $(id).min = dates[0]; $(id).max = dates[dates.length - 1]; });
    }

    loaded = true;
    applyDates();
    renderHint();
    map.invalidateSize();
    renderAll(true);

    // The figures have just pushed everything down, so go back to any section linked to.
    var target = location.hash ? document.getElementById(location.hash.slice(1)) : null;
    if (target) { setScrollOffset(); target.scrollIntoView({ behavior: 'instant' }); }
  }
  function load() {
    $('verdict').textContent = 'Loading the figures…';
    fetch(page.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
      .then(function (response) {
        if (!response.ok) throw new Error('The figures failed to load: ' + response.status);
        return response.json();
      })
      .then(start)
      .catch(function (error) {
        console.error(error);
        $('verdict').innerHTML = 'The figures could not be loaded. <button type="button" class="link" id="f-retry">Try again</button>';
        $('f-retry').addEventListener('click', load);
      });
  }

  map.fitBounds(PUNJAB);
  fitScope();
  $('f-clear').hidden = !state.district && !hasDates();
  renderHint();
  load();
  window.addEventListener('load', function () { if (!loaded) { map.invalidateSize(); map.fitBounds(PUNJAB); } });
})();
</script>
