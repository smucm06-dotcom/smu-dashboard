<script>
/* CM Punjab Honhaar Scholarship: the design's own script, fed by the server's figures (HonhaarDashboard)
   instead of its embedded data. The server sends all of Punjab and every district for the chosen dates, so
   the district filter is instant and a new date range is fetched. */
(function () {
  var page = document.getElementById('psrpPage');
  var $ = function (id) { return document.getElementById(id); };
  var fmt = function (n) { return Number(n).toLocaleString('en-US'); };
  var pct = function (a, b, d) { return b ? (a / b * 100).toFixed(d === undefined ? 1 : d) + '%' : '&ndash;'; };
  var rate = function (g) { return g.fb ? (g.fb - g.iss) / g.fb * 100 : null; };
  var esc = function (s) { return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var MN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var dl = function (iso) { if (!iso) return ''; var p = iso.split('-'); return Number(p[2]) + ' ' + MN[Number(p[1]) - 1] + ' ' + p[0]; };

  var SHORT = { notrecv: 'Not received yet', second: '2nd cheque not received', uni: 'University did not cooperate', less: 'Paid less than full amount',
    bribe: 'Paid a bribe', name: 'Name issue', inelig: 'Declared not eligible', bank: 'Bank or cheque problem', fee: 'Fee asked / money taken back', delay: 'Delays and other' };
  var TPL = { 'Public university': 'Public universities', 'Private university': 'Private universities', 'HED college': 'Government colleges (HED)', 'Medical college': 'Medical colleges' };
  var DATA = null, M = null, AGG = null, ISS = [], BRIBE = -1, FEW = 30;
  var state = { district: '', from: '', to: '' };
  var loaded = false, running = null;

  (function () {
    var q = new URLSearchParams(location.search);
    state.district = q.get('district') || '';
    state.from = q.get('from') || '';
    state.to = q.get('to') || '';
  })();

  function topIdx(a) { var b = -1, v = 0; a.forEach(function (n, i) { if (n > v) { v = n; b = i; } }); return b; }
  function bar(v, m, cls) { return '<div class="trk' + (cls ? ' ' + cls : '') + '"><i style="width:' + (m ? v / m * 100 : 0).toFixed(2) + '%"></i></div>'; }
  function cur() { return state.district && AGG[state.district] ? state.district : 'ALL'; }

  /* ---------- the banner's four cards ---------- */
  function setText(id, html) { var el = $(id); if (el) el.innerHTML = html; }
  function renderCards(g) {
    var L = M.lists, ln = Object.keys(L);
    setText('c-dep', fmt(g.dep));
    setText('c-dep-sub', cur() === 'ALL'
      ? esc(ln.map(function (k) { return k.replace(/^CM /, '').replace(/^Honhaar /, '').replace(/ \(20K\)$| \d+$/, '') + ' ' + fmt(L[k][0]); }).join(' + '))
      : 'Students from this district in the lists');
    setText('c-stu', fmt(g.students));
    setText('c-stu-sub', pct(g.students, g.dep, 0) + ' of the list. Each student counted once');
    setText('c-stu-x', '<b>' + fmt(g.calls) + '</b> calls made in all &middot; <b>' + fmt(g.students - g.ncalls[0]) + '</b> students were called more than once');
    setText('c-fb', fmt(g.fb));
    setText('c-fb-sub', pct(g.fb, g.students, 0) + ' of students called. Most of the rest did not pick up or had the phone switched off');
    setText('c-fb-x', '<b>' + fmt(g.chg[0]) + '</b> students gave feedback twice or more; their latest call is counted');
    var r = rate(g);
    setText('c-sat', r === null ? '&ndash;' : r.toFixed(1) + '%');
  }

  /* ---------- page ---------- */
  function render() {
    var g = AGG[cur()];
    renderCards(g);
    renderUnis(); renderCalls();

    /* satisfaction */
    var ok = g.fb - g.iss;
    $('s-bar').innerHTML = g.fb ? '<i class="g" style="flex:' + ok + '"></i><i class="c" style="flex:' + g.iss + '"></i>' : '';
    $('s-bar').setAttribute('aria-label', fmt(ok) + ' students with no issue and ' + fmt(g.iss) + ' with an issue');
    $('s-ok').textContent = fmt(ok); $('s-iss').textContent = fmt(g.iss);

    /* impact of the follow-up calls */
    var c = g.chg;
    $('imp-1').innerHTML = '<span class="imp-tag">Scholarship reached after the call</span><div class="imp-num">' + fmt(c[3]) + '</div>' +
      '<div class="imp-txt"><b>students said the scholarship had not reached them, and on a later call said it had</b><small>' + pct(c[3], c[10], 0) + ' of the ' + fmt(c[10]) + ' students who said "not received" and were asked again.</small></div>';
    $('imp-2').innerHTML = '<span class="imp-tag">Problems solved by the next call</span><div class="imp-num">' + fmt(c[7]) + '</div>' +
      '<div class="imp-txt"><b>students had a problem on an earlier call that was gone by their latest call</b><small>' + pct(c[7], c[9], 0) + ' of the ' + fmt(c[9]) + ' students who reported a problem and gave feedback again. ' + fmt(c[8]) + ' other students reported a new problem on a later call.</small></div>';
    $('imp-note').textContent = 'The call files do not record what action was taken after a problem was reported, so these changes cannot be credited to the calls alone; some payments were already on their way. What the calls do give is a student-by-student check on whether the money arrived.';

    /* issues */
    var bribeBase = g.bribe[0] + g.bribe[1];
    var shr = function (i) { var b = i === BRIBE ? bribeBase : g.fb; return b ? g.cat[i] / b : 0; };
    var ord = ISS.map(function (x, i) { return i; }).sort(function (a, b) { return shr(b) - shr(a) || g.cat[b] - g.cat[a]; });
    var mx = Math.max.apply(null, ord.map(function (i) { return g.cat[i] / (i === BRIBE ? bribeBase || 1 : g.fb || 1); })) || 1;
    $('iss-rows').innerHTML = ord.map(function (i) {
      var x = ISS[i], n = g.cat[i], base = i === BRIBE ? bribeBase : g.fb, sh = base ? n / base : 0;
      var desc = x.desc ? '<small>' + esc(x.desc) + '</small>' : '';
      return '<div class="br' + (n ? '' : ' zero') + '" data-tip="' + esc(x.label + ': ' + fmt(n) + ' of ' + fmt(base) + (i === BRIBE ? ' students asked' : ' students')) + '">' +
        '<div class="lb">' + esc(x.label) + desc + '</div><div class="trk"><i style="width:' + (sh / mx * 100).toFixed(2) + '%"></i></div>' +
        '<div class="vl"><b>' + fmt(n) + '</b><span>' + pct(n, base) + '</span></div></div>';
    }).join('');

    /* call outcomes (the connected rows together) */
    var o = g.out.slice(), labs = M.outs.slice();
    o[0] += o[1]; o.splice(1, 1); labs.splice(1, 1);
    var oi = labs.map(function (x, i) { return i; }).filter(function (i) { return o[i] > 0; }).sort(function (a, b) { return o[b] - o[a]; });
    var om = Math.max.apply(null, o) || 1;
    $('out-rows').innerHTML = oi.length ? oi.map(function (i) {
      return '<div class="br sm" data-tip="' + esc(labs[i] + ': ' + fmt(o[i]) + ' calls') + '"><div class="lb">' + esc(labs[i]) + '</div><div class="trk a"><i style="width:' + (o[i] / om * 100).toFixed(2) + '%"></i></div><div class="vl"><b>' + fmt(o[i]) + '</b><span>' + pct(o[i], g.calls, 0) + '</span></div></div>';
    }).join('') : '<p class="note">No calls in this selection.</p>';

    /* received */
    var rv = g.recv, rt = rv[0] + rv[1];
    $('rv-bar').innerHTML = rt ? '<i style="flex:' + rv[0] + '"></i><i style="flex:' + rv[1] + '"></i>' : '';
    $('rv-bar').setAttribute('aria-label', fmt(rv[0]) + ' yes, ' + fmt(rv[1]) + ' no');
    $('rv-key').innerHTML = '<span><i class="sw g"></i>Yes <b>' + fmt(rv[0]) + '</b> (' + pct(rv[0], rt) + ')</span><span><i class="sw c"></i>No <b>' + fmt(rv[1]) + '</b> (' + pct(rv[1], rt) + ')</span>';

    /* year of degree */
    var y = g.year, yt = y.reduce(function (a, b) { return a + b; }, 0), ym = Math.max.apply(null, y) || 1;
    $('yr-rows').innerHTML = yt ? M.years.map(function (lab, i) {
      return '<div class="br sm' + (y[i] ? '' : ' zero') + '"><div class="lb">' + esc(lab) + '</div><div class="trk a"><i style="width:' + (y[i] / ym * 100).toFixed(2) + '%"></i></div><div class="vl"><b>' + fmt(y[i]) + '</b><span>' + pct(y[i], yt, 0) + '</span></div></div>';
    }).join('') : '<p class="note">No student in this selection was asked this question.</p>';
  }

  /* ---------- universities and colleges ---------- */
  var uMode = 'iss', uAll = false, TOPN = 15;
  function renderUnis() {
    var g = AGG[cur()], IN = M.insts, T = M.itypes, UMIN = cur() === 'ALL' ? 30 : 10;
    var called = T.map(function () { return 0; });
    g.inst.forEach(function (r) { called[IN[r[0]][1]] += r[2]; });
    $('types').innerHTML = [0, 2, 3, 1].filter(function (t) { return T[t] !== undefined; }).map(function (t) {
      var x = g.types[t], none = x[0] > 0 && called[t] === 0;
      var body = !x[0] && !called[t] ? 'None in the list for this selection.' :
        none ? '<b>Not called.</b> None of these students is in the call files.' :
        fmt(called[t]) + ' called, ' + fmt(x[1]) + ' gave feedback, satisfaction <b>' + (x[1] ? ((x[1] - x[2]) / x[1] * 100).toFixed(1) + '%' : '&ndash;') + '</b>';
      return '<div class="card' + (none ? ' none' : '') + '"><h3>' + esc(TPL[T[t]] || T[t]) + '</h3><div class="tn">' + fmt(x[0]) + '<small>scholarship students</small></div><p>' + body + '</p></div>';
    }).join('');
    var rows = g.inst.slice(), rt = function (r) { return r[3] ? (r[3] - r[4]) / r[3] : 2; };
    if (uMode === 'iss') rows = rows.filter(function (r) { return r[3] > 0; }).sort(function (a, b) { return b[4] - a[4] || b[3] - a[3]; });
    if (uMode === 'list') rows = rows.filter(function (r) { return r[1] > 0; }).sort(function (a, b) { return b[1] - a[1] || b[2] - a[2]; });
    if (uMode === 'low') rows = rows.filter(function (r) { return r[3] >= UMIN; }).sort(function (a, b) { return rt(a) - rt(b) || b[3] - a[3]; });
    $('u-note').textContent = { iss: 'Sorted by students who reported an issue.', list: 'Sorted by scholarship students in the department\'s list.',
      low: 'Only institutions where at least ' + UMIN + ' students gave feedback.' }[uMode];
    var shown = uAll ? rows : rows.slice(0, TOPN);
    var h = '<thead><tr><th class="r">#</th><th>University / college</th><th class="r">Scholarship students</th><th class="r">Called</th><th class="r">Gave feedback</th><th class="r">Reported an issue</th><th>Satisfaction rate</th><th>Main issue</th></tr></thead><tbody>';
    h += shown.length ? shown.map(function (r, i) {
      var nm = IN[r[0]][0], ty = T[IN[r[0]][1]], ra = r[3] ? (r[3] - r[4]) / r[3] * 100 : null;
      return '<tr' + (r[3] && r[3] < UMIN ? ' class="few"' : '') + '><td class="r">' + (i + 1) + '</td><td class="nm">' + esc(nm) + '<small>' + esc(ty === 'Not known' ? 'Type not in the list' : ty) + '</small></td>' +
        '<td class="r">' + (r[1] ? fmt(r[1]) : '&ndash;') + '</td><td class="r">' + (r[2] ? fmt(r[2]) : (r[1] ? 'Not called' : '&ndash;')) + '</td>' +
        '<td class="r">' + fmt(r[3]) + '</td><td class="r">' + fmt(r[4]) + '</td>' +
        '<td class="sr"><div class="rate">' + bar(ra || 0, 100) + '<b>' + (ra === null ? '&ndash;' : ra.toFixed(1) + '%') + '</b></div></td>' +
        '<td>' + (r[5] < 0 ? '<span style="color:var(--muted)">None reported</span>' : esc(SHORT[ISS[r[5]].k] || ISS[r[5]].label) + '<small>' + fmt(r[6]) + '</small>') + '</td></tr>';
    }).join('') : '<tr><td colspan="8" class="note">No university or college in this selection.</td></tr>';
    $('ut').innerHTML = h + '</tbody>';
    $('u-more').hidden = rows.length <= TOPN;
    $('u-more').textContent = uAll ? 'Show top ' + TOPN + ' only' : 'Show all ' + fmt(rows.length);
  }
  [].forEach.call(document.querySelectorAll('[data-u]'), function (b) {
    b.addEventListener('click', function () {
      if (!loaded) return;
      uMode = b.getAttribute('data-u'); uAll = false;
      [].forEach.call(document.querySelectorAll('[data-u]'), function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      renderUnis();
    });
  });
  $('u-more').addEventListener('click', function () { if (!loaded) return; uAll = !uAll; renderUnis(); });

  /* ---------- repeat calls ---------- */
  function renderCalls() {
    var g = AGG[cur()], nc = g.ncalls, nm = Math.max.apply(null, nc) || 1;
    $('nc-sub').innerHTML = 'Students in the list who were called, by the number of calls each one got.';
    $('nc-rows').innerHTML = ['Called once', 'Called 2 times', 'Called 3 times', 'Called 4 times', 'Called 5 times or more'].map(function (lab, i) {
      return '<div class="br sm"><div class="lb">' + lab + '</div>' + bar(nc[i], nm, 'a') + '<div class="vl"><b>' + fmt(nc[i]) + '</b><span>' + pct(nc[i], g.students, 0) + '</span></div></div>';
    }).join('');
    var c = g.chg;
    $('ch-sub').innerHTML = 'Their answers on each call, compared.';
    var row = function (lab, v, cls) { return '<div class="chr' + (cls ? ' ' + cls : '') + '"><span>' + lab + '</span><b>' + fmt(v) + (cls === 'head' ? ' <small style="color:var(--muted);font-weight:600">' + pct(v, c[0], 0) + '</small>' : '') + '</b></div>'; };
    $('ch-rows').innerHTML = row('Same answers on every call', c[1], 'head') + row('Some answer changed', c[2], 'head') +
      row('Scholarship: "received" first, "not received" later', c[4], 'sub-r') +
      row('Satisfied: "no" first, "yes" later', c[5], 'sub-r') + row('Satisfied: "yes" first, "no" later', c[6], 'sub-r');
  }

  /* ---------- district table ---------- */
  var COLS = [
    { k: 'd', t: 'District', s: function (d) { return d; } },
    { k: 'dep', t: 'In department list', r: 1, s: function (d) { return AGG[d].dep; } },
    { k: 'st', t: 'Students called', r: 1, s: function (d) { return AGG[d].students; } },
    { k: 'fb', t: 'Gave feedback', r: 1, s: function (d) { return AGG[d].fb; } },
    { k: 'iss', t: 'Reported an issue', r: 1, s: function (d) { return AGG[d].iss; } },
    { k: 'rt', t: 'Satisfaction rate', s: function (d) { var r = rate(AGG[d]); return r === null ? 999 : r; } },
    { k: 'top', t: 'Main issue', s: function (d) { var i = topIdx(AGG[d].cat); return i < 0 ? 'zzz' : SHORT[ISS[i].k]; } }
  ];
  var sortK = 'rt', sortDir = 1;
  function drawTable() {
    var rows = DATA.dists.slice().sort(function (a, b) {
      if (a === 'District not known') return 1; if (b === 'District not known') return -1;
      var c = COLS.filter(function (x) { return x.k === sortK; })[0];
      var va = c.s(a), vb = c.s(b);
      if (AGG[a].fb < FEW !== AGG[b].fb < FEW && sortK === 'rt') return AGG[a].fb < FEW ? 1 : -1;
      return (va > vb ? 1 : va < vb ? -1 : 0) * sortDir || (a > b ? 1 : -1);
    });
    var h = '<thead><tr>' + COLS.map(function (c) {
      return '<th' + (c.r ? ' class="r"' : '') + '><button type="button" data-k="' + c.k + '"' + (c.k === sortK ? ' aria-sort="' + (sortDir > 0 ? 'ascending' : 'descending') + '"' : '') + '>' + c.t + '</button></th>';
    }).join('') + '</tr></thead><tbody>';
    h += rows.length ? rows.map(function (d) {
      var g = AGG[d], r = rate(g), few = g.fb < FEW, ti = topIdx(g.cat);
      return '<tr data-d="' + esc(d) + '"' + (few ? ' class="few"' : '') + '>' +
        '<td><button type="button" class="dname">' + esc(d) + '</button>' + (few ? '<span class="few-tag">few students</span>' : '') + '</td>' +
        '<td class="r">' + (g.dep ? fmt(g.dep) : '&ndash;') + '</td>' +
        '<td class="r">' + fmt(g.students) + '</td>' +
        '<td class="r">' + fmt(g.fb) + '<small>' + pct(g.fb, g.students, 0) + '</small></td>' +
        '<td class="r">' + fmt(g.iss) + '</td>' +
        '<td class="sr"><div class="rate"><div class="trk"><i style="width:' + (r === null ? 0 : r).toFixed(1) + '%"></i></div><b>' + (r === null ? '&ndash;' : r.toFixed(1) + '%') + '</b></div></td>' +
        '<td>' + (ti < 0 ? '<span style="color:var(--muted)">None reported</span>' : esc(SHORT[ISS[ti].k]) + '<small>' + fmt(g.cat[ti]) + '</small>') + '</td></tr>';
    }).join('') : '<tr><td colspan="7" class="note">No districts yet: upload the department list.</td></tr>';
    $('dt').innerHTML = h + '</tbody>';
    markRow();
  }
  $('dt').addEventListener('click', function (e) {
    if (!loaded) return;
    var b = e.target.closest('th button');
    if (b) { var k = b.getAttribute('data-k'); if (k === sortK) sortDir = -sortDir; else { sortK = k; sortDir = (k === 'd' || k === 'rt' || k === 'top') ? 1 : -1; } drawTable(); return; }
    var tr = e.target.closest('tbody tr[data-d]');
    if (tr) { var d = tr.getAttribute('data-d'); setDistrict(cur() === d ? '' : d); $('satisfaction').scrollIntoView(); }
  });
  function markRow() { [].forEach.call(document.querySelectorAll('#dt tbody tr'), function (tr) { tr.classList.toggle('on', tr.getAttribute('data-d') === cur() && cur() !== 'ALL'); }); }

  /* ---------- filters ---------- */
  function scopeNote() {
    var lines = [], g = AGG[cur()];
    if (cur() !== 'ALL' && g.fb < FEW) lines.push('Few students in ' + esc(cur()) + ' gave feedback, so read its figures with care.');
    if ((state.from || state.to) && M.dated.undated) lines.push('Calls from ' + (state.from ? dl(state.from) : 'the first call') + ' to ' + (state.to ? dl(state.to) : 'the last call') + '. ' + fmt(M.dated.undated) + ' calls have no call date (the Master file\'s), so they are left out while dates are chosen.');
    $('scope-note').innerHTML = lines.join(' ');
    $('scope-note').hidden = !lines.length;
  }
  function nameForPdf() {
    var place = state.district ? state.district : 'all of Punjab', dates = state.from || state.to ? ', calls ' + (state.from ? 'from ' + dl(state.from) + ' ' : '') + (state.to ? 'to ' + dl(state.to) : '') : '';
    $('pdfExport').setAttribute('data-pdf-what', (place + dates).trim());
  }
  function fillDistricts() {
    var names = DATA.dists.slice();
    if (state.district && names.indexOf(state.district) < 0) names.push(state.district);
    $('f-district').innerHTML = '<option value="">All Punjab</option>' + names.map(function (d) { return '<option' + (d === state.district ? ' selected' : '') + '>' + esc(d) + '</option>'; }).join('');
  }
  // Tells the records table below to show the same district and dates.
  function filtersChanged() {
    document.dispatchEvent(new CustomEvent('psrp:filters', { detail: { district: state.district, from: state.from, to: state.to } }));
  }
  function renderAll() {
    $('f-clear').hidden = !state.district && !state.from && !state.to;
    $('f-district').value = state.district;
    $('f-from').value = state.from; $('f-to').value = state.to;
    fitLinks();
    scopeNote(); nameForPdf(); render(); drawTable();
  }
  function setDistrict(d) {
    state.district = d;
    if (loaded) renderAll();
    filtersChanged();
  }
  function setRange(a, b) {
    if (a && b && a > b) { var t = a; a = b; b = t; }
    state.from = a; state.to = b;
    load();
    filtersChanged();
  }
  // Typing a date passes through years like 0002, which are not worth acting on.
  function usableDate(value) { return value === '' || value >= '2000-01-01'; }
  $('psrpFilters').addEventListener('submit', function (e) { e.preventDefault(); });
  $('f-district').addEventListener('change', function () { setDistrict(this.value); });
  $('f-from').addEventListener('change', function () { if (usableDate(this.value)) setRange(this.value, state.to); });
  $('f-to').addEventListener('change', function () { if (usableDate(this.value)) setRange(state.from, this.value); });
  $('f-clear').addEventListener('click', function () {
    var datesChanged = state.from || state.to;
    state.district = ''; state.from = ''; state.to = '';
    if (datesChanged) load(); else if (loaded) renderAll();
    filtersChanged();
  });

  /* ---------- tooltip ---------- */
  var tip = $('tip');
  $('psrpFindings').addEventListener('mouseleave', function () { tip.hidden = true; });
  $('psrpFindings').addEventListener('mousemove', function (e) {
    var t = e.target.closest('[data-tip]');
    if (!t || !t.getAttribute('data-tip')) { tip.hidden = true; return; }
    tip.textContent = t.getAttribute('data-tip'); tip.hidden = false;
    var x = Math.min(e.clientX + 14, window.innerWidth - tip.offsetWidth - 8), y = e.clientY + 16;
    if (y + tip.offsetHeight > window.innerHeight - 8) y = e.clientY - tip.offsetHeight - 12;
    tip.style.left = Math.max(8, x) + 'px'; tip.style.top = y + 'px';
  });

  /* ---------- section links land below the banner and filter bar ---------- */
  var filterBar = document.querySelector('.filterbar');
  var banner = document.querySelector('.app-topbar');
  var links = filterBar.querySelector('.nav');
  var linkArea = filterBar.querySelector('.fb-meta'), more = $('nav-more'), moreList = $('nav-more-list');
  var navLinks = Array.prototype.slice.call(links.querySelectorAll('a'));
  // The bar keeps one row: the links that do not fit beside the filters go, from the last one back, under
  // "More". Worked out again whenever the room changes (the window, the sidebar, "Clear filters" showing).
  function fitLinks() {
    navLinks.forEach(function (a) { a.hidden = false; });
    moreList.innerHTML = '';
    more.hidden = true;
    more.removeAttribute('open');
    if (getComputedStyle(linkArea).display === 'none' || links.scrollWidth <= links.clientWidth + 1) return;
    more.hidden = false;
    for (var i = navLinks.length - 1; i >= 0; i--) {
      navLinks[i].hidden = true;
      var copy = navLinks[i].cloneNode(true);
      copy.hidden = false;
      moreList.insertBefore(copy, moreList.firstChild);
      if (links.scrollWidth <= links.clientWidth + 1) break;
    }
  }
  moreList.addEventListener('click', function () { more.removeAttribute('open'); });
  document.addEventListener('click', function (e) { if (!more.contains(e.target)) more.removeAttribute('open'); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && more.open) { more.removeAttribute('open'); more.querySelector('summary').focus(); } });
  var fitting = false;
  new ResizeObserver(function () {
    if (fitting) return;
    fitting = true;
    requestAnimationFrame(function () { fitLinks(); fitting = false; });
  }).observe(linkArea);
  if (document.fonts) document.fonts.ready.then(fitLinks);
  // The links stay beside the filters while their text and gaps fit (see the filter bar styles).
  function measureLinks() {
    if (!links.offsetWidth) return;
    var list = links.querySelectorAll('a'), total = (parseFloat(getComputedStyle(links).columnGap) || 0) * (list.length - 1);
    list.forEach(function (a) { var r = document.createRange(); r.selectNodeContents(a); total += r.getBoundingClientRect().width; });
    $('psrpFilters').style.setProperty('--nav-text', Math.ceil(total) + 2 + 'px');
  }
  function setScrollOffset() {
    measureLinks();
    var sticky = getComputedStyle(filterBar).position === 'sticky';
    document.documentElement.style.scrollPaddingTop = ((banner ? banner.offsetHeight : 0) + (sticky ? filterBar.offsetHeight : 0) + 12) + 'px';
  }
  setScrollOffset();
  var offsetWatcher = new ResizeObserver(setScrollOffset);
  offsetWatcher.observe(filterBar);
  if (banner) offsetWatcher.observe(banner);
  window.addEventListener('resize', setScrollOffset);

  /* ---------- the figures ---------- */
  function start(data) {
    DATA = data; M = data.meta; AGG = data.agg; ISS = M.issues; FEW = M.few || 30;
    BRIBE = ISS.map(function (x) { return x.k; }).indexOf('bribe');
    var first = M.dated.first, last = M.dated.last;
    ['f-from', 'f-to'].forEach(function (id) { $(id).disabled = !first; if (first) { $(id).min = first; $(id).max = last; } });
    fillDistricts();
    var wasLoaded = loaded;
    loaded = true;
    var empty = !AGG.ALL.dep && !AGG.ALL.calls;
    $('load-note').hidden = !empty;
    $('load-note').textContent = empty ? 'No figures yet: upload the department list and a call file below.' : '';
    page.removeAttribute('aria-busy');
    renderAll();

    // The figures have just pushed everything down, so go back to any section linked to.
    if (!wasLoaded) {
      var target = location.hash ? document.getElementById(location.hash.slice(1)) : null;
      if (target) { setScrollOffset(); target.scrollIntoView({ behavior: 'instant' }); }
    }
  }
  function load() {
    if (running) running.abort();
    var request = running = new AbortController();
    var q = new URLSearchParams();
    if (state.from) q.set('from', state.from);
    if (state.to) q.set('to', state.to);
    page.setAttribute('aria-busy', 'true');
    if (!loaded) { $('load-note').hidden = false; $('load-note').textContent = 'Loading the figures…'; }
    fetch(page.dataset.url + (q.toString() ? '?' + q.toString() : ''), { signal: request.signal, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
      .then(function (response) {
        if (!response.ok) throw new Error('The figures failed to load: ' + response.status);
        return response.json();
      })
      .then(start)
      .catch(function (error) {
        if (error.name === 'AbortError') return;
        console.error(error);
        $('load-note').hidden = false;
        $('load-note').innerHTML = 'The figures could not be loaded. <button type="button" class="link" id="f-retry">Try again</button>';
        $('f-retry').addEventListener('click', load);
      });
  }

  load();
})();
</script>
