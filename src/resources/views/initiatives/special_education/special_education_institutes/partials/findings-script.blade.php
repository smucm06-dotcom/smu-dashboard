<script>
// Everything above the records table, and the five cards in the banner, is worked out here from
// one download, so changing a filter is instant and does not reload the page.
// The date filter applies to visits only: reports and the department's responses always count.
(function () {
  'use strict';

  var page = document.getElementById('psrpPage');
  var ALL = [], META = { minKey: 0, maxKey: 0, replyMax: '' }, RTEXT = [], EXTRA = [], CATS = [], CAT = {}, loaded = false;

  var RT = [
    { id: 'fixed', label: 'Says resolved or acted', sub: 'Fixed, or action taken on the case', sw: 'r-fixed', badge: 'b-good' },
    { id: 'directed', label: 'Instructions issued', sub: 'Orders sent to district offices', sw: 'r-dir', badge: 'b-info' },
    { id: 'plan', label: 'Programme or plan cited', sub: 'A province-wide scheme, hiring or purchase', sw: 'r-plan', badge: 'b-mute' },
    { id: 'disputed', label: 'Questioned or explained', sub: 'No such problem, or no budget for it', sw: 'r-disp', badge: 'b-warn' },
    { id: 'notans', label: 'Did not answer', sub: 'The response does not address it', sw: 'r-nota', badge: 'b-crit' },
    { id: 'none', label: 'No response', sub: 'Nothing on file yet', sw: 'r-none', badge: 'b-crit' }
  ];
  var RTI = {}; RT.forEach(function (t) { RTI[t.id] = t; });

  function rate(v, p, t) { if (!v || !v[t] || v[p] === null || v[p] === undefined) return null; return Math.min(v[p], v[t]) / v[t]; }
  var FIND = [
    { id: 'sep', label: 'No separate classroom for each grade', why: 'Grades taught together', f: function (v) { return v.sep === 0; } },
    { id: 'psy', label: 'No psychologist present', why: 'Post empty or absent', f: function (v) { return v.pp === 0; } },
    { id: 'wash', label: 'Washrooms dirty or dilapidated', why: 'Hygiene and safety', f: function (v) { return !!v.wc && v.wc !== 'Clean'; } },
    { id: 'satt', label: 'Fewer than 6 in 10 students present', why: 'Low attendance', f: function (v) { var r = rate(v, 'sp', 'en'); return r !== null && r < 0.6; } },
    { id: 'tatt', label: 'Fewer than 3 in 4 teachers present', why: 'Teacher absence', f: function (v) { var r = rate(v, 'tp', 'tt'); return r !== null && r < 0.75; } },
    { id: 'prin', label: 'Principal not present', why: 'Head absent on visit day', f: function (v) { return v.pr === 0; } },
    { id: 'cls', label: 'Classrooms dirty or dilapidated', why: 'Learning space', f: function (v) { return !!v.cc && v.cc !== 'Clean'; } },
    { id: 'stip', label: 'Stipend not being received', why: 'Monthly support to students', f: function (v) { return v.sti === 0; } },
    { id: 'drv', label: 'Bus driver post vacant', why: 'Pick and drop at risk', f: function (v) { return v.dv === 1; } },
    { id: 'water', label: 'No clean drinking water', why: 'Basic need missing', f: function (v) { return v.wa === 0; } },
    { id: 'aid', label: 'No first aid', why: 'Basic safety', f: function (v) { return v.fa === 0; } },
    { id: 'kit', label: 'Free books or uniform not given', why: 'Free provision', f: function (v) { return v.bk === 0 || v.un === 0; } },
    { id: 'trans', label: 'No free school transport', why: 'Free provision', f: function (v) { return v.tr === 0; } },
    { id: 'fee', label: 'Fee charged from students', why: 'Education should be free', f: function (v) { return v.fee === 1; } },
    { id: 'furn', label: 'No furniture', why: 'Basic need', f: function (v) { return v.fu === 0; } },
    { id: 'wall', label: 'No boundary wall', why: 'Safety risk', f: function (v) { return v.bw === 0; } }
  ];
  var FI = {}; FIND.forEach(function (f) { FI[f.id] = f; });

  function clean(k) { return function (v) { return v[k] ? (v[k] === 'Clean' ? 1 : 0) : null; }; }
  function kit(v) { return v.bk === null || v.un === null ? null : (v.bk && v.un ? 1 : 0); }
  var REV = [
    { k: 'pr', label: 'Principal present', vn: 'principal presence', ok: function (v) { return v.pr; } },
    { k: 'ta', label: 'Teachers present', vn: 'teacher attendance', rate: ['tp', 'tt'] },
    { k: 'sa', label: 'Students present', vn: 'student attendance', rate: ['sp', 'en'] },
    { k: 'sti', label: 'Stipend being received', vn: 'stipend payments', ok: function (v) { return v.sti; } },
    { k: 'wc', label: 'Washrooms clean', vn: 'washroom cleanliness', ok: clean('wc') },
    { k: 'cc', label: 'Classrooms clean', vn: 'classroom cleanliness', ok: clean('cc') },
    { k: 'wa', label: 'Clean drinking water', vn: 'drinking water', ok: function (v) { return v.wa; } },
    { k: 'fa', label: 'First aid available', vn: 'first aid', ok: function (v) { return v.fa; } },
    { k: 'kit', label: 'Free books and uniform given', vn: 'free books and uniforms', ok: kit },
    { k: 'sep', label: 'Separate classroom for each grade', vn: 'separate classrooms', ok: function (v) { return v.sep; } },
    { k: 'fee', label: 'No fee charged', vn: 'free schooling', ok: function (v) { return v.fee === null || v.fee === undefined ? null : 1 - v.fee; } }
  ];
  var ROUNDS = [
    { label: 'Apr to May 2025', from: '2025-04-01', to: '2025-05-31' },
    { label: 'Sep 2025 to May 2026', from: '2025-06-01', to: '2026-06-30' },
    { label: 'Aug to Sep 2026', from: '2026-07-01', to: '2026-12-31' }
  ];
  var PROM = [
    { label: 'Stipend being received', said: 'April 2025: the delay "got resolved" and the last quarter\'s stipend would be paid by 30 April 2025.', ok: function (v) { return v.sti; } },
    { label: 'Teachers present', said: 'Facial attendance app means "no chance of proxy or willful absence".', rate: ['tp', 'tt'] },
    { label: 'Washrooms clean', said: 'District officers told to visit and upload geo-tagged photos; SMU complaints "got resolved".', ok: clean('wc') },
    { label: 'Free books and uniform given', said: 'Textbooks handed to district offices; remaining uniforms after a quality check.', ok: kit },
    { label: 'First aid available', said: 'Divisional offices told to keep first aid kits in schools and vehicles.', ok: function (v) { return v.fa; } },
    { label: 'Clean drinking water', said: 'Field offices told to make sure clean water and electricity are available.', ok: function (v) { return v.wa; } }
  ];

  var ACTS = [
    { g: 'case', rx: /suspended/i, t: 'Officers suspended after students were beaten', s: 'SMU reported it; the department also sent a circular against physical punishment to all field offices.' },
    { g: 'case', rx: /complaints? (by smu )?got resolved/i, t: 'Cleanliness complaints said to be resolved', s: 'The response says the specific complaints SMU pointed out were resolved.' },
    { g: 'case', rx: /incident/i, t: 'Fee complaints inquired', s: 'Heads and district officers denied charging fees; the department asked SMU to share evidence.' },
    { g: 'case', rx: /sought report from concerned head|inquired the matter from the concerned/i, t: 'Reports sought from school heads', s: 'For example, heads said there is no budget for sports or vocational kit.' },
    { g: 'dir', rx: /online|virtual session/i, t: 'Online meetings with all district education officers', s: 'On cleanliness; officers to visit and upload geo-tagged photos in the new monitoring app.' },
    { g: 'dir', rx: /pick ?(and|&) ?drop|pipick/i, t: 'Keep pick and drop running', s: 'Field offices told to settle fuel and vehicle repair budgets.' },
    { g: 'dir', rx: /divis\w* ?head/i, t: 'Settle stipend, uniform and transport budgets', s: 'Divisional offices told to work this out with district education authorities.' },
    { g: 'dir', rx: /ensure cleanliness within/i, t: 'Keep premises clean and fill Class-IV posts', s: 'District and SESI inspection reports to be checked daily (January 2026 response).' },
    { g: 'dir', rx: /re-align/i, t: 'Bus routes to be re-aligned', s: 'DGSE letter of 18 April 2025, to reduce complaints about routes.' },
    { g: 'dir', rx: /first aid kit|ba\w*sc facilities/i, t: 'First aid kits, clean water and electricity', s: 'Kits to be kept in schools and in vehicles.' },
    { g: 'prog', rx: /33003/, t: '33,003 of 41,397 students received stipend', s: 'January 2026 response. Stipend needs 70% monthly attendance; some bank accounts are still being opened.' },
    { g: 'prog', rx: /340 posts/i, t: '340 posts advertised, about 145 staff promoted', s: 'Recruitment requests also sent to the Punjab Public Service Commission.' },
    { g: 'prog', rx: /12114|7,?702|2\.2 million/i, t: '12,114 assistive devices given out with UNICEF', s: 'Hearing aids, wheelchairs, walkers, white canes and glasses; a US$2.2 million grant approved for more.' },
    { g: 'prog', rx: /52556/, t: '52,556 textbooks sent to district offices', s: '30,030 students had received uniforms by the January 2026 response.' },
    { g: 'prog', rx: /41 drivers/i, t: '41 bus drivers recruited and posted', s: 'More driver posts still being filled.' },
    { g: 'prog', rx: /cctv/i, t: 'CCTV cameras being installed', s: '3,104 of 3,468 cameras installed in phase 1 of the scheme.' },
    { g: 'prog', rx: /200 new buses/i, t: '200 new buses proposed', s: 'Plan (PC-1) prepared, hoped for in the next financial year.' },
    { g: 'prog', rx: /48 new schemes/i, t: '13 building schemes running, 48 proposed', s: '93 of 303 schools work in rented buildings.' },
    { g: 'prog', rx: /facial attend/i, t: 'Facial attendance app for staff', s: 'Built with PITB and in use since January 2025, before the SMU visits began.' }
  ];
  var ACTG = [
    { g: 'case', t: 'Action on the reported case', p: 'Steps aimed at a specific school or complaint.' },
    { g: 'dir', t: 'Instructions to all district offices', p: 'Orders the department says it sent across Punjab.' },
    { g: 'prog', t: 'Programmes and progress reported', p: 'Province-wide work the department pointed to.' }
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
  function months(a, b) { return Math.max(1, Math.round(days(a, b) / 30.44)); }
  function median(a) { if (!a.length) return null; a = a.slice().sort(function (x, y) { return x - y; }); var m = Math.floor(a.length / 2); return a.length % 2 ? a[m] : Math.round((a[m - 1] + a[m]) / 2); }
  function txt(it) { return it.x.map(function (i) { return RTEXT[i]; }).join(' '); }

  /* Dates: the filter keeps the visits made between From and To. */
  function keyToISO(k) { var s = String(k); return s.slice(0, 4) + '-' + s.slice(4, 6) + '-' + s.slice(6, 8); }
  function isoToKey(s) { return s ? Number(s.replace(/-/g, '')) : 0; }
  function keyLabel(k) { return dl(keyToISO(k)); }
  function fullRange() { return state.from === META.minKey && state.to === META.maxKey; }
  function rangeText() { return keyLabel(state.from) + ' to ' + keyLabel(state.to); }

  /* Per-institution fields that depend on the visits in the date range */
  function derive() {
    ALL.forEach(function (s) {
      s.v = s.av.filter(function (v) { var k = isoToKey(v.dt); return k >= state.from && k <= state.to; });
      s.vv = s.v.filter(function (v) { return !v.cl; }); /* visits made while the institution was open */
      s.F = s.vv[0] || null; s.L = s.vv.length ? s.vv[s.vv.length - 1] : null; s.last = s.v.length ? s.v[s.v.length - 1] : null;
      s.f = s.L ? FIND.filter(function (x) { return x.f(s.L); }).map(function (x) { return x.id; }) : [];
      s.up = 0; s.dn = 0;
      if (s.vv.length > 1) REV.forEach(function (row) { var x = val(row, s.F), y = val(row, s.L); if (x === null || y === null) return; var d = row.rate ? (y > x + 0.05 ? 1 : y < x - 0.05 ? -1 : 0) : (y > x ? 1 : y < x ? -1 : 0); if (d > 0) s.up++; if (d < 0) s.dn++; });
    });
  }
  function val(row, v) { if (row.rate) return rate(v, row.rate[0], row.rate[1]); var x = row.ok(v); return x === null || x === undefined ? null : (x ? 1 : 0); }

  var state = { zone: $('f-zone').value, district: $('f-district').value, from: isoToKey($('f-from').value), to: isoToKey($('f-to').value), show: 'all', filter: '', sel: null, sort: 'cov', dir: 1 };
  function scoped() { return ALL.filter(function (s) { return (!state.zone || s.z === state.zone) && (!state.district || s.d === state.district); }); }
  function where() { return state.district ? ' in ' + esc(state.district) : state.zone ? ' in the ' + esc(state.zone) + ' zone' : ''; }
  // A response covering several schools is counted once.
  function reportsOf(list, noExtra) {
    var seen = {}, out = [];
    list.forEach(function (s) { s.r.forEach(function (r) { if (!seen[r.id]) { seen[r.id] = 1; out.push({ r: r, s: s }); } }); });
    if (!noExtra && !state.zone && !state.district) EXTRA.forEach(function (r) { if (!seen[r.id]) { seen[r.id] = 1; out.push({ r: r, s: null }); } });
    return out;
  }

  /* Summary */
  function summarise(list, noExtra) {
    var o = { total: list.length, closed: 0, visited: 0, never: 0, visits: 0, revisited: 0, reported: 0, first: '', last: '', find: {}, tp: 0, tt: 0, sp: 0, sen: 0,
      reports: 0, replied: 0, noReply: 0, items: 0, types: {}, cat: {}, lag: null, repMin: '', repMax: '' };
    FIND.forEach(function (f) { o.find[f.id] = 0; });
    RT.forEach(function (t) { o.types[t.id] = 0; });
    CATS.forEach(function (c) { o.cat[c.id] = { inst: {}, n: 0, t: {} }; RT.forEach(function (t) { o.cat[c.id].t[t.id] = 0; }); });
    list.forEach(function (s) {
      if (s.r.length) o.reported++;
      if (!s.v.length) { o.never++; return; }
      o.visited++; o.visits += s.v.length; if (s.vv.length > 1) o.revisited++; o.closed += s.v.length - s.vv.length;
      s.f.forEach(function (id) { o.find[id]++; });
      s.v.forEach(function (v) { if (!o.first || v.dt < o.first) o.first = v.dt; if (v.dt > o.last) o.last = v.dt; });
      var L = s.L;
      if (rate(L, 'tp', 'tt') !== null) { o.tt += L.tt; o.tp += Math.min(L.tp, L.tt); }
      if (rate(L, 'sp', 'en') !== null) { o.sen += L.en; o.sp += Math.min(L.sp, L.en); }
    });
    var lags = [], reps = reportsOf(list, noExtra);
    o.reports = reps.length;
    reps.forEach(function (x) {
      var r = x.r, any = false, key = x.s ? x.s.k : 'x' + r.id;
      if (r.sd) { if (!o.repMin || r.sd < o.repMin) o.repMin = r.sd; if (r.sd > o.repMax) o.repMax = r.sd; }
      r.it.forEach(function (it) {
        if (!it.i) { if (it.t !== 'none') any = true; return; }
        o.items++; o.types[it.t]++; if (it.t !== 'none') any = true;
        it.c.forEach(function (c) { var a = o.cat[c]; if (!a) return; a.n++; a.t[it.t]++; a.inst[r.j ? 'j' + r.id : key] = 1; });
      });
      if (any) { o.replied++; if (r.sd && r.rd) lags.push(days(r.sd, r.rd)); } else o.noReply++;
    });
    o.lag = median(lags);
    return o;
  }

  function revisitStats(list) {
    var R = list.filter(function (s) { return s.vv.length > 1; });
    var o = { list: R, n: R.length, tt0: 0, tt1: 0, ttUp: 0, ttDn: 0, en0: 0, en1: 0, enUp: 0, enDn: 0, gMin: null, gMax: null, rows: [] };
    R.forEach(function (s) {
      var g = months(s.F.dt, s.L.dt);
      o.gMin = o.gMin === null ? g : Math.min(o.gMin, g); o.gMax = Math.max(o.gMax || 0, g);
      if (s.F.tt !== null && s.L.tt !== null) { o.tt0 += s.F.tt; o.tt1 += s.L.tt; if (s.L.tt > s.F.tt) o.ttUp++; else if (s.L.tt < s.F.tt) o.ttDn++; }
      if (s.F.en && s.L.en) { o.en0 += s.F.en; o.en1 += s.L.en; if (s.L.en > s.F.en) o.enUp++; else if (s.L.en < s.F.en) o.enDn++; }
    });
    o.rows = REV.map(function (row) {
      var a = { row: row, n: 0, f: 0, l: 0, fp: 0, ft: 0, lp: 0, lt: 0, up: 0, same: 0, dn: 0 };
      R.forEach(function (s) {
        var x = val(row, s.F), y = val(row, s.L);
        if (x === null || y === null) return;
        a.n++;
        if (row.rate) {
          var fp = Math.min(s.F[row.rate[0]], s.F[row.rate[1]]), ft = s.F[row.rate[1]], lp = Math.min(s.L[row.rate[0]], s.L[row.rate[1]]), lt = s.L[row.rate[1]];
          a.fp += fp; a.ft += ft; a.lp += lp; a.lt += lt;
          if (y > x + 0.05) a.up++; else if (y < x - 0.05) a.dn++; else a.same++;
        } else { a.f += x; a.l += y; if (y > x) a.up++; else if (y < x) a.dn++; else a.same++; }
      });
      return a;
    });
    return o;
  }

  /* The five cards in the banner */
  function setCard(id, num, sub) {
    var el = $(id), info = $(id + '-sub');
    if (el) el.textContent = n(num);
    if (info && sub !== undefined) info.textContent = sub;
  }
  function renderFlow(o, rv) {
    setCard('c-total', o.total);
    setCard('c-visited', o.visited, o.visited ? pct(o.visited, o.total) + '% of total schools, ' + pl(o.visits, 'visit', 'visits') + ' from ' + ml(o.first) + ' to ' + ml(o.last) : 'None yet');
    setCard('c-replied', o.replied, o.reports ? (o.lag !== null ? 'Usually within ' + pl(o.lag, 'day', 'days') + '. ' : '') + n(o.noReply) + ' with no response' : '');
    setCard('c-revisit', rv.n, rv.n ? 'Checked again ' + (rv.gMin === rv.gMax ? rv.gMin : rv.gMin + ' to ' + rv.gMax) + ' months later' : 'None in this selection');
  }

  /* What changed */
  function renderChanged(o, rv, list) {
    $('ans-sub').textContent = o.items ? 'Each of the ' + n(o.items) + ' issues SMU reported is counted once, by the kind of response it got. Click a group to see those schools on the map.' : 'No reports in this selection.';
    var tot = o.items || 1;
    $('segbar').innerHTML = RT.map(function (t) {
      var c = o.types[t.id]; if (!c) return '';
      return '<i class="' + t.sw + '" style="width:' + (c / tot * 100) + '%" data-tip="<b>' + esc(t.label) + '</b>' + n(c) + ' issues, ' + pct(c, tot) + '%"></i>';
    }).join('');
    $('segbar').setAttribute('aria-label', RT.map(function (t) { return t.label + ' ' + o.types[t.id]; }).join(', '));
    $('outs').innerHTML = RT.map(function (t) {
      return '<button type="button" class="card out" data-filter="t:' + t.id + '"><div class="out-num">' + n(o.types[t.id]) + '</div><div class="out-lbl"><span class="sw ' + t.sw + '"></span>' + esc(t.label) + '</div><div class="out-sub">' + esc(t.sub) + '</div></button>';
    }).join('');

    /* actions */
    var reps = reportsOf(list), hit = {};
    ACTS.forEach(function (a, i) { hit[i] = { n: 0, inst: [] }; });
    reps.forEach(function (x) {
      var all = x.r.it.filter(function (it) { return it.x.length; }).map(txt).join(' ');
      ACTS.forEach(function (a, i) { if (a.rx.test(all)) { hit[i].n++; if (x.s) hit[i].inst.push(x.s); } });
    });
    $('acts').innerHTML = ACTG.map(function (g) {
      var items = ACTS.map(function (a, i) { return { a: a, i: i, h: hit[i] }; }).filter(function (x) { return x.a.g === g.g; }).sort(function (a, b) { return b.h.n - a.h.n; });
      var btn = function (x) {
        var one = x.h.inst.length === 1 ? x.h.inst[0] : null;
        var sub = x.a.s + (one && x.a.g === 'case' ? ' (' + esc(one.n) + ')' : '');
        return '<button type="button" class="act' + (x.h.n ? '' : ' zero') + '" ' + (one ? 'data-open="' + esc(one.k) + '"' : 'data-filter="a:' + x.i + '"') + '><span><b>' + esc(x.a.t) + '</b><small>' + esc(sub) + '</small></span><span class="cnt">' + (x.h.n ? pl(x.h.n, 'response', 'responses') : 'none here') + '</span></button>';
      };
      return '<div class="card act-col"><h3>' + g.t + '</h3><p>' + g.p + '</p>' + items.slice(0, 4).map(btn).join('') +
        (items.length > 4 ? '<details class="act-more"><summary>Show ' + (items.length - 4) + ' more</summary>' + items.slice(4).map(btn).join('') + '</details>' : '') + '</div>';
    }).join('');
    $('caveat').innerHTML = '<b>These are the department\'s own statements.</b> SMU has not checked them, except where an school was visited again (next section). Many responses repeat the same province-wide text for every school.' +
      (META.replyMax ? ' Responses stop at ' + dl(META.replyMax) + ', and SMU visits since then have no reports on file.' : '');
  }

  /* Impact cards, in the banner */
  function renderImpact(o, rv, list) {
    var ans = o.items - o.types.none - o.types.notans;
    var susp = reportsOf(list).some(function (x) { return x.r.it.some(function (it) { return it.x.length && /suspended/i.test(txt(it)); }); });
    $('imp-1').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (o.items ? pct(ans, o.items) + '%' : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (o.items ? 'of the issues SMU raised got an answer from the department' : 'No SMU reports here yet') + '</b><small>' +
      (o.items ? 'Answers include orders to all district offices on cleanliness, stipends and transport' + (susp ? ', and officers suspended after students were beaten' : '') + '.' : '') + '</small></div>';
    var t = rv.tt1 - rv.tt0, sti = rv.rows.filter(function (a) { return a.row.k === 'sti'; })[0];
    $('imp-2').innerHTML = '<span class="imp-tag">Impact</span><div class="imp-num">' + (rv.n ? (t >= 0 ? '+' : '−') + n(Math.abs(t)) : '&ndash;') + '</div><div class="imp-txt"><b>' +
      (rv.n ? (t >= 0 ? 'more teachers' : 'fewer teachers') + ' where SMU went back' : 'No school visited again here yet') + '</b><small>' +
      (rv.n ? 'At the schools visited again, teachers on roll went from ' + n(rv.tt0) + ' to ' + n(rv.tt1) + ' and children enrolled from ' + n(rv.en0) + ' to ' + n(rv.en1) + '.' +
        (sti && sti.dn > sti.up ? ' Stipend payments fell at some of the same places; see below.' : '') : '') + '</small></div>';
  }

  /* Visited again */
  function renderRevisit(o, rv, list) {
    $('rev-sub').textContent = rv.n ? pl(rv.n, 'school was', 'schools were') + ' visited a second time' + where().replace(/<[^>]+>/g, '') + '. Comparing the first and the latest visit is the clearest sign of what changed on the ground.' : 'No school' + where().replace(/<[^>]+>/g, '') + ' has been visited twice yet.';
    $('rev-t').innerHTML = rv.n ? '<thead><tr><th>Check</th><th class="r">First visit</th><th class="r">Latest visit</th><th>Change at each school</th></tr></thead><tbody>' + rv.rows.map(function (a) {
      if (!a.n) return '';
      var r = a.row, tot = a.up + a.same + a.dn || 1;
      var f = r.rate ? Math.round(a.fp / a.ft * 100) + '%' : n(a.f) + ' of ' + n(a.n), l = r.rate ? Math.round(a.lp / a.lt * 100) + '%' : n(a.l) + ' of ' + n(a.n);
      var fv = r.rate ? a.fp / a.ft : a.f / a.n, lv = r.rate ? a.lp / a.lt : a.l / a.n, cls = lv > fv + 0.02 ? 'up' : lv < fv - 0.02 ? 'dn' : '';
      return '<tr data-filter="g:' + r.k + '"><td class="lab">' + r.label + (r.rate ? '<small>All schools together, ' + (r.k === 'ta' ? 'teachers' : 'students') + ' present out of those on roll.' + '</small>' : '') + '</td><td class="r">' + f + '</td><td class="r ' + cls + '">' + l + '</td>' +
        '<td><div class="chg"><div class="stack" role="img" aria-label="' + a.up + ' better, ' + a.same + ' same, ' + a.dn + ' worse" data-tip="<b>' + esc(r.label) + '</b>' + n(a.up) + ' better, ' + n(a.same) + ' no change, ' + n(a.dn) + ' worse"><i class="s-more" style="width:' + a.up / tot * 100 + '%"></i><i class="s-same" style="width:' + a.same / tot * 100 + '%"></i><i class="s-less" style="width:' + a.dn / tot * 100 + '%"></i></div><span>' + n(a.up) + ' better, ' + n(a.dn) + ' worse</span></div></td></tr>';
    }).join('') + '</tbody>' : '<tbody><tr><td class="empty">No school visited twice in this selection.</td></tr></tbody>';
    var shut = list.filter(function (s) { return s.v.length > s.vv.length; });
    $('rev-note').innerHTML = (rv.n ? 'Attendance counts as better or worse when it moved by more than 5 points. ' : '') + shut.map(function (s) {
      return '<b>' + esc(s.n) + '</b> was found closed on ' + closedDates(s) + '. That visit is kept on record but left out of every figure, so ' + (s.vv.length > 1 ? 'its open visits are compared instead.' : 'it is not counted as visited again.');
    }).join(' ');

    /* rounds */
    var rows = PROM.map(function (p) {
      var cells = ROUNDS.map(function (rd) {
        var c = { n: 0, ok: 0, p: 0, t: 0 };
        list.forEach(function (s) { s.vv.forEach(function (v) {
          if (v.dt < rd.from || v.dt > rd.to) return;
          if (p.rate) { if (rate(v, p.rate[0], p.rate[1]) === null) return; c.n++; c.p += Math.min(v[p.rate[0]], v[p.rate[1]]); c.t += v[p.rate[1]]; }
          else { var x = p.ok(v); if (x === null || x === undefined) return; c.n++; if (x) c.ok++; }
        }); });
        c.v = c.n < 5 ? null : p.rate ? c.p / c.t : c.ok / c.n;
        return c;
      });
      var a = cells[0].v, b = cells[2].v !== null ? cells[2].v : cells[1].v;
      return '<tr><td class="lab">' + p.label + '</td><td class="said">' + esc(p.said) + '</td>' + cells.map(function (c, i) {
        var cls = i > 0 && a !== null && c.v !== null ? (c.v < a - 0.05 ? 'dn' : c.v > a + 0.05 ? 'up' : '') : '';
        return '<td class="r ' + cls + '">' + (c.v === null ? '&ndash;' : Math.round(c.v * 100) + '%') + '<small>' + (c.n < 5 ? (c.n ? 'too few visits' : 'no visits') : pl(c.n, 'visit', 'visits')) + '</small></td>';
      }).join('') + '</tr>';
    });
    $('rnd-t').innerHTML = '<thead><tr><th>Check</th><th>What the department said</th>' + ROUNDS.map(function (r) { return '<th class="r">' + r.label + '</th>'; }).join('') + '</tr></thead><tbody>' + rows.join('') + '</tbody>';

    $('rv-list').innerHTML = rv.list.slice().sort(function (a, b) { return (b.up - b.dn) - (a.up - a.dn) || a.n.localeCompare(b.n); }).map(function (s) {
      return '<button type="button" data-open="' + esc(s.k) + '"><span>' + esc(s.n) + '<small>' + esc(s.d) + ' &middot; ' + dl(s.F.dt) + ' and ' + dl(s.L.dt) + (s.vv.length > 2 ? ' (' + s.vv.length + ' visits)' : '') + '</small></span><span class="mk"><i class="g">' + s.up + '</i><i class="w">' + s.dn + '</i></span></button>';
    }).join('') || '<p class="empty">None in this selection.</p>';
  }

  /* Issues */
  function renderIssues(o) {
    var top = FIND.slice().sort(function (a, b) { return o.find[b.id] - o.find[a.id]; }).slice(0, 8);
    $('tiles').innerHTML = top.map(function (f) {
      return '<button type="button" class="tile" data-filter="f:' + f.id + '"><b>' + n(o.find[f.id]) + '</b><span>' + esc(f.label) + '</span><small>' + esc(f.why) + ' &middot; ' + pct(o.find[f.id], o.visited) + '% of visited</small></button>';
    }).join('');
    var rows = CATS.map(function (c) { var a = o.cat[c.id]; return { c: c, a: a, inst: Object.keys(a.inst).length }; }).filter(function (x) { return x.a.n; }).sort(function (a, b) { return b.inst - a.inst; });
    $('iss-t').innerHTML = rows.length ? '<thead><tr><th>Issue raised</th><th class="r">Schools</th><th>Department\'s response</th><th class="r">Left unanswered</th></tr></thead><tbody>' + rows.map(function (x) {
      var a = x.a, tot = a.n || 1, open = a.t.none + a.t.notans;
      return '<tr data-filter="c:' + x.c.id + '"><td class="lab">' + esc(x.c.label) + '</td><td class="r">' + n(x.inst) + '</td><td><div class="stack" role="img" aria-label="' + RT.map(function (t) { return t.label + ' ' + a.t[t.id]; }).join(', ') + '" data-tip="<b>' + esc(x.c.label) + '</b>' + RT.filter(function (t) { return a.t[t.id]; }).map(function (t) { return esc(t.label) + ': ' + a.t[t.id]; }).join('<br>') + '">' +
        RT.map(function (t) { return a.t[t.id] ? '<i class="' + t.sw + '" style="width:' + a.t[t.id] / tot * 100 + '%"></i>' : ''; }).join('') + '</div></td><td class="r' + (open / tot > 0.25 ? ' dn' : '') + '">' + (open ? n(open) + ' <span class="pct">of ' + n(a.n) + '</span>' : '&ndash;') + '</td></tr>';
    }).join('') + '</tbody>' : '<tbody><tr><td class="empty" style="padding:12px 18px">No reports in this selection.</td></tr></tbody>';
    $('iss-key').innerHTML = RT.map(function (t) { return '<span><i class="sw ' + t.sw + '"></i>' + esc(t.label) + '</span>'; }).join('');
  }

  /* Districts */
  function districtRows() {
    var by = {};
    ALL.forEach(function (s) { if (state.zone && s.z !== state.zone) return; (by[s.d] = by[s.d] || []).push(s); });
    return Object.keys(by).map(function (d) {
      var L = by[d], o = summarise(L, true);
      var top = FIND.slice().sort(function (a, b) { return o.find[b.id] - o.find[a.id]; })[0];
      var open = o.types.none + o.types.notans;
      return { d: d, z: L[0].z, total: o.total, visited: o.visited, nv: o.never, cov: o.visited / o.total, rv: o.revisited, items: o.items, open: open, ans: o.items ? (o.items - open) / o.items : -1,
        ta: o.tt ? o.tp / o.tt : -1, sa: o.sen ? o.sp / o.sen : -1, top: o.find[top.id] ? top.label : '' };
    });
  }
  var DCOLS = [
    { k: 'd', t: 'District' }, { k: 'z', t: 'Zone' }, { k: 'total', t: 'Total schools', r: 1 }, { k: 'visited', t: 'Visited', r: 1 }, { k: 'cov', t: 'Share visited' },
    { k: 'rv', t: 'Visited again', r: 1 }, { k: 'items', t: 'Issues reported', r: 1 }, { k: 'ans', t: 'Answered', r: 1 }, { k: 'ta', t: 'Teachers present', r: 1 }, { k: 'sa', t: 'Students present', r: 1 }, { k: 'top', t: 'Most common finding' }
  ];
  function pc(x) { return x < 0 ? '&ndash;' : Math.round(x * 100) + '%'; }
  function renderDistricts() {
    var D = districtRows(), k = state.sort, dir = state.dir;
    var rows = D.slice().sort(function (a, b) { var x = a[k], y = b[k]; return (typeof x === 'string' ? x.localeCompare(y) : x - y) * dir || a.d.localeCompare(b.d); });
    $('dt').innerHTML = '<thead><tr>' + DCOLS.map(function (c) {
      var on = c.k === k;
      return '<th scope="col" class="' + (c.r ? 'r' : '') + '" aria-sort="' + (on ? (dir > 0 ? 'ascending' : 'descending') : 'none') + '"><button type="button" data-sort="' + c.k + '">' + c.t + (on ? (dir > 0 ? ' ↑' : ' ↓') : '') + '</button></th>';
    }).join('') + '</tr></thead><tbody>' + rows.map(function (r) {
      return '<tr data-district="' + esc(r.d) + '"' + (r.d === state.district ? ' class="on"' : '') + '><td><button type="button" class="dname" data-district="' + esc(r.d) + '">' + esc(r.d) + '</button></td><td>' + esc(r.z) + '</td><td class="r">' + n(r.total) + '</td><td class="r">' + n(r.visited) + '</td>' +
        '<td><div class="cov"><div class="bar"><i style="width:' + r.cov * 100 + '%"></i></div><span>' + Math.round(r.cov * 100) + '%</span></div></td><td class="r">' + (r.rv ? n(r.rv) : '&ndash;') + '</td><td class="r">' + (r.items ? n(r.items) : '&ndash;') + '</td>' +
        '<td class="r' + (r.ans >= 0 && r.ans < 0.75 ? ' dn' : '') + '">' + pc(r.ans) + '</td><td class="r' + (r.ta >= 0 && r.ta < 0.75 ? ' dn' : '') + '">' + pc(r.ta) + '</td><td class="r' + (r.sa >= 0 && r.sa < 0.6 ? ' dn' : '') + '">' + pc(r.sa) + '</td><td>' + esc(r.top) + '</td></tr>';
    }).join('') + '</tbody>';
    var big = D.filter(function (r) { return r.total >= 5; });
    var minCov = big.slice().sort(function (a, b) { return a.cov - b.cov || b.total - a.total; }).slice(0, 3);
    var open = D.slice().sort(function (a, b) { return b.open - a.open; })[0];
    var lowT = D.filter(function (r) { return r.visited >= 4 && r.ta >= 0; }).sort(function (a, b) { return a.ta - b.ta; })[0];
    var none = D.filter(function (r) { return !r.visited; }).sort(function (a, b) { return b.total - a.total; });
    $('insights').innerHTML =
      (none.length ? '<div class="card insight"><small>No school visited yet</small>' + none.map(function (r) { return '<b>' + esc(r.d) + '</b> (' + n(r.total) + ')'; }).join(', ') + '</div>'
        : '<div class="card insight"><small>Lowest share visited</small>' + (minCov.length ? minCov.map(function (r) { return '<b>' + esc(r.d) + '</b> ' + Math.round(r.cov * 100) + '%'; }).join(', ') : '&ndash;') + '</div>') +
      '<div class="card insight"><small>Most issues still without an answer</small>' + (open && open.open ? '<b>' + esc(open.d) + '</b>, ' + n(open.open) + ' of ' + n(open.items) + ' issues reported' : 'None in this selection') + '</div>' +
      '<div class="card insight"><small>Lowest teacher attendance at the latest visit</small>' + (lowT ? '<b>' + esc(lowT.d) + '</b>, ' + pc(lowT.ta) + ' across ' + n(lowT.visited) + ' schools' : 'Too few visits') + '</div>';
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
  var ring = null;
  var PUNJAB = [[27.7, 69.3], [34.1, 75.5]];
  function radius() { var z = map.getZoom() || 7; return z < 7 ? 3 : z < 8 ? 3.5 : z < 10 ? 4.5 : z < 12 ? 6 : 7.5; }
  function status(s) { return s.vv.length > 1 ? 'r' : s.v.length ? 'v' : 'n'; }
  function makeMarker(s) {
    var st = status(s), r = radius();
    var opt = st === 'r' ? { radius: r + 1, renderer: renderer, stroke: true, color: C.good, weight: 2.2, fillColor: C.deep, fillOpacity: 1 }
      : st === 'v' ? { radius: r, renderer: renderer, stroke: true, color: C.surf, weight: 0.8, fillColor: C.good, fillOpacity: 1 }
      : { radius: r, renderer: renderer, stroke: true, color: C.crit, weight: 1.8, fillColor: C.surf, fillOpacity: 1 };
    var m = L.circleMarker([s.la, s.lo], opt);
    m.bindTooltip(function () { return esc(s.n) + (st === 'n' ? ' (not visited)' : ' (' + pl(s.v.length, 'visit', 'visits') + ', latest ' + dl(s.last.dt) + (s.last.cl ? ', found closed' : '') + ')'); }, { direction: 'top', offset: [0, -4] });
    m.on('click', function () { openSchool(s, false); });
    return m;
  }
  function responded(s) { return s.r.some(function (r) { return r.it.some(function (it) { return it.t !== 'none'; }); }); }
  function matchesFilter(s) {
    var f = state.filter;
    if (!f) return true;
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 'f:') return s.f.indexOf(id) >= 0;
    if (k === 'c:') return !!s.cs[id];
    if (k === 't:') return !!s.ts[id];
    if (k === 'g:') return s.vv.length > 1;
    if (k === 'a:') { var a = ACTS[Number(id)]; return s.r.some(function (r) { return r.it.some(function (it) { return it.x.length && a.rx.test(txt(it)); }); }); }
    if (f === 'rep') return responded(s);
    if (f === 'norep') return s.v.length > 0 && !responded(s);
    return true;
  }
  function filterLabel(f) {
    var k = f.slice(0, 2), id = f.slice(2);
    if (k === 'f:') return FI[id].label;
    if (k === 'c:') return 'Issue reported: ' + (CAT[id] ? CAT[id].label : id);
    if (k === 't:') return 'Response: ' + RTI[id].label;
    if (k === 'g:') return 'Visited again';
    if (k === 'a:') return ACTS[Number(id)].t;
    if (f === 'rep') return 'Department responded';
    if (f === 'norep') return 'Visited, no department response yet';
    return '';
  }
  function matchesMap(s) {
    var st = status(s);
    if (state.show === 'v' && st === 'n') return false;
    if (state.show === 'r' && st !== 'r') return false;
    if (state.show === 'n' && st !== 'n') return false;
    return matchesFilter(s);
  }
  function renderMap(fit) {
    pins.clearLayers();
    var list = scoped().filter(matchesMap);
    var cnt = { v: 0, r: 0, n: 0 }, unmapped = 0, pts = [], visitIds = [];
    var order = { n: 0, v: 1, r: 2 };
    list.sort(function (a, b) { return order[status(a)] - order[status(b)]; });
    list.forEach(function (s) {
      if (s.la === null || s.la === undefined) { unmapped++; return; }
      var st = status(s);
      cnt[st]++; pins.addLayer(makeMarker(s)); pts.push([s.la, s.lo]);
      // The visit the school's findings come from: its latest while open, else its latest.
      if (st !== 'n') visitIds.push((s.L || s.last).id);
    });
    // As on the PSRP pages: only a chosen finding, issue or response can be downloaded, and the
    // file holds exactly the visited schools on the map, one row each.
    $('map-download').hidden = !(state.filter && visitIds.length);
    if (state.filter) {
      $('dl-ids').value = visitIds.join(',');
      $('dl-issue').value = filterLabel(state.filter);
      $('dl-label').textContent = 'Download ' + pl(visitIds.length, 'school', 'schools');
    }
    fitSearchHint(); // The button takes room from the search box, or gives it back.
    var label = state.filter ? ' <span class="lg-n">with "' + esc(filterLabel(state.filter)) + '"</span>' : '';
    $('legend').innerHTML = (state.show === 'all' || state.show === 'v' ? '<div class="lg"><span class="pin v"></span>Visited once <span class="lg-n">' + n(cnt.v) + '</span></div>' : '') +
      (state.show !== 'n' ? '<div class="lg"><span class="pin r"></span>Visited again <span class="lg-n">' + n(cnt.r) + '</span></div>' : '') +
      (state.show === 'all' || state.show === 'n' ? '<div class="lg"><span class="pin n"></span>Not visited <span class="lg-n">' + n(cnt.n) + '</span></div>' : '') + (label ? '<div class="lg">' + label + '</div>' : '');
    $('map-note').textContent = (unmapped ? pl(unmapped, 'school', 'schools') + ' in this view ' + (unmapped === 1 ? 'has' : 'have') + ' no map location, so ' + (unmapped === 1 ? 'it is' : 'they are') + ' not on the map. Search finds ' + (unmapped === 1 ? 'it' : 'them') + '. ' : '') +
      (state.filter ? (pts.length || unmapped ? '' : 'No school in this view matches "' + filterLabel(state.filter) + '".') : '');
    if (fit) {
      var zone = pts.length ? pts : scoped().filter(function (s) { return s.la !== null; }).map(function (s) { return [s.la, s.lo]; });
      if (zone.length && (state.district || state.zone || state.filter)) map.fitBounds(L.latLngBounds(zone).pad(0.08), { maxZoom: 12 });
      else map.fitBounds(PUNJAB);
    }
  }
  map.on('zoomend', function () { if (loaded) renderMap(false); });
  function placeRing(s) {
    if (ring) { map.removeLayer(ring); ring = null; }
    if (s && s.la !== null && s.la !== undefined) ring = L.circleMarker([s.la, s.lo], { radius: 13, color: C.ink, weight: 2.5, fill: false, interactive: false, renderer: renderer }).addTo(map);
  }
  function syncSeg() { document.querySelectorAll('#mapsec .seg button').forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.show === state.show)); }); }

  /* Institution panel */
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
  function closedDates(s) { return s.v.filter(function (v) { return v.cl; }).map(function (v) { return dl(v.dt); }).join(', '); }
  function rowsTable(list) { return '<table class="rt"><tbody>' + list.map(function (r) { return '<tr><td>' + r[0] + '</td><td>' + r[1] + '</td></tr>'; }).join('') + '</tbody></table>'; }
  function visitRows(v) {
    return rowsTable([
      ['Principal present', yesNo(v.pr, 1)], ['Teachers present', frac(v.tp, v.tt, 0.75)], ['Psychologist present', frac(v.pp, v.pt, 1)], ['Admin staff present', frac(v.ap, v.at, 0.75)],
      ['Students present', frac(v.sp, v.en, 0.6)], ['Monthly stipend received', yesNo(v.sti, 1)], ['Fee charged from students', yesNo(v.fee, 0)],
      ['Classrooms and washrooms', (v.cr === null ? '&ndash;' : n(v.cr)) + ' and ' + (v.wr === null ? '&ndash;' : n(v.wr))], ['Separate classroom for each grade', yesNo(v.sep, 1)],
      ['Washrooms', cond(v.wc)], ['Classrooms', cond(v.cc)], ['Free books', yesNo(v.bk, 1)], ['Free uniform', yesNo(v.un, 1)],
      ['Free school transport', v.trn ? '<span class="na">No transport service</span>' : yesNo(v.tr, 1)], ['Bus driver post vacant', yesNo(v.dv, 0)],
      ['Furniture', yesNo(v.fu, 1)], ['First aid', yesNo(v.fa, 1)], ['Clean drinking water', yesNo(v.wa, 1)], ['Boundary wall', yesNo(v.bw, 1)]
    ]);
  }
  function show(row, v) { var x = val(row, v); if (x === null) return '<span class="na">&ndash;</span>'; return row.rate ? Math.round(x * 100) + '%' : (x ? 'Yes' : 'No'); }
  function renderHint() {
    $('panel').innerHTML = '<div class="panel-hint"><h3>School report</h3><p>Click a pin on the map, or search above, to see the school\'s report here.</p><ul>' +
      '<li><b>Green pin:</b> visited once. You will see what the team found and any department response.</li>' +
      '<li><b>Dark pin with a green ring:</b> visited again. You will also see what changed between visits.</li>' +
      '<li><b>Red ring:</b> in the department file, not visited yet.</li></ul></div>';
  }
  // quiet: refreshed after a filter change, so the page is not scrolled to it.
  function openSchool(s, focus, quiet) {
    state.sel = s.k; placeRing(s);
    if (focus && s.la !== null && s.la !== undefined) map.setView([s.la, s.lo], Math.max(map.getZoom(), 12));
    var h = '<div class="p-top"><div><div class="p-name">' + esc(s.n) + '</div><div class="p-meta">EMIS ' + esc(s.e) + ' &middot; ' + esc(s.d) + (s.z ? ' &middot; ' + esc(s.z) + ' zone' : '') + '</div></div><button type="button" class="x" id="p-close" aria-label="Close report">×</button></div>';
    var lastRep = s.r.length ? s.r[s.r.length - 1] : null, replied = lastRep && lastRep.it.some(function (it) { return it.t !== 'none'; });
    h += '<div class="badges">' + (s.v.length ? '<span class="badge b-good">● ' + (s.v.length > 1 ? 'Visited ' + s.v.length + ' times' : 'Visited ' + dl(s.last.dt)) + '</span>' + (s.v.length > s.vv.length ? '<span class="badge b-mute">Found closed on ' + closedDates(s) + '</span>' : '') : (s.r.length ? '<span class="badge b-warn">Reported, but missing from the monitoring data</span>' : '<span class="badge b-crit">○ Not visited</span>')) +
      (s.r.length ? '<span class="badge ' + (replied ? 'b-info' : 'b-warn') + '">' + (replied ? 'Department responded' : 'No department response yet') + '</span>' : (s.v.length ? '<span class="badge b-mute">No department response yet</span>' : '')) + '</div>';
    if (s.la === null || s.la === undefined) h += '<p class="note">This school has no map location in either file, so it has no pin.</p>';
    if (s.av.length > s.v.length) h += '<p class="note">' + pl(s.av.length - s.v.length, 'visit falls', 'visits fall') + ' outside the dates chosen, so ' + (s.av.length - s.v.length === 1 ? 'it is' : 'they are') + ' not shown here.</p>';
    if (s.vv.length > 1) {
      h += '<div class="p-sec"><h3>What changed between visits</h3><table class="cmp"><thead><tr><th>Check</th><th>' + dl(s.F.dt) + '</th><th>' + dl(s.L.dt) + '</th></tr></thead><tbody>' +
        '<tr><td>Teachers on roll</td><td>' + (s.F.tt === null ? '&ndash;' : n(s.F.tt)) + '</td><td class="' + (s.L.tt > s.F.tt ? 'ok' : s.L.tt < s.F.tt ? 'bad' : '') + '">' + (s.L.tt === null ? '&ndash;' : n(s.L.tt)) + '</td></tr>' +
        '<tr><td>Children enrolled</td><td>' + (s.F.en === null ? '&ndash;' : n(s.F.en)) + '</td><td class="' + (s.L.en > s.F.en ? 'ok' : s.L.en < s.F.en ? 'bad' : '') + '">' + (s.L.en === null ? '&ndash;' : n(s.L.en)) + '</td></tr>' +
        REV.map(function (row) {
          var x = val(row, s.F), y = val(row, s.L), d = x === null || y === null ? 0 : row.rate ? (y > x + 0.05 ? 1 : y < x - 0.05 ? -1 : 0) : (y > x ? 1 : y < x ? -1 : 0);
          return '<tr><td>' + row.label + '</td><td>' + show(row, s.F) + '</td><td class="' + (d > 0 ? 'ok' : d < 0 ? 'bad' : '') + '">' + (d > 0 ? '▲ ' : d < 0 ? '▼ ' : '') + show(row, s.L) + '</td></tr>';
        }).join('') + '</tbody></table></div>';
    }
    if (s.L) {
      h += '<div class="p-sec"><h3>' + (s.f.length ? pl(s.f.length, 'issue', 'issues') + ' found at the latest visit' : 'No issues found at the latest visit') + '</h3>' +
        (s.f.length ? '<div class="chips">' + s.f.map(function (k) { return '<span class="chip">' + esc(FI[k].label) + '</span>'; }).join('') + '</div>' : '') + '</div>';
    }
    if (s.r.length) {
      h += '<div class="p-sec"><h3>Issues reported and the department\'s response</h3>' + s.r.slice().reverse().map(function (r) {
        var items = r.it.filter(function (it) { return it.i || it.x.length; });
        return '<div class="rep"><div class="rep-h">SMU report of ' + (r.sd ? dl(r.sd) : 'an unknown date') + (r.vd ? ', on the visit of ' + dl(r.vd) : '') + (r.fu ? ' &middot; follow-up' : '') + '<br>' +
          (r.rd ? 'Response dated ' + dl(r.rd) + (r.sd ? ', ' + pl(days(r.sd, r.rd), 'day', 'days') + ' later' : '') : 'No response date on file') + (r.j ? ' &middot; one response covering ' + r.j + ' ' + esc(s.d) + ' schools' : '') + '</div>' +
          items.map(function (it) {
            var t = RTI[it.t];
            return '<div class="ritem"><div class="ri-h"><span class="ri-is">' + (it.i ? '<small class="rlab">Issue</small>' + esc(it.i) : '<span class="na">Further response</span>') + '</span><span class="badge ' + t.badge + '">' + esc(t.label) + '</span></div>' +
              (it.x.length ? '<small class="rlab">Department\'s response</small>' : '') + it.x.map(function (i) { return '<p class="rtx" title="Click to read the full response">' + esc(RTEXT[i]) + '</p>'; }).join('') + '</div>';
          }).join('') + '</div>';
      }).join('') + '</div>';
    }
    if (s.L) {
      h += '<div class="p-sec"><h3>' + (s.last !== s.L ? 'Latest visit while open, ' : 'Latest visit, ') + dl(s.L.dt) + '</h3>' + visitRows(s.L) + '</div>';
      s.v.filter(function (v) { return v !== s.L; }).reverse().forEach(function (v) {
        h += v.cl ? '<p class="note"><b>Visit of ' + dl(v.dt) + ': the school was closed.</b> The sheet shows ' + n(v.sp) + ' of ' + n(v.en) + ' students and ' + n(v.tp) + ' of ' + n(v.tt) + ' teachers present. Nothing from this visit is counted.</p>'
          : '<details class="visit"><summary>' + (v.dt > s.L.dt ? 'Later' : 'Earlier') + ' visit, ' + dl(v.dt) + '</summary>' + visitRows(v) + '</details>';
      });
    } else if (s.v.length) h += '<p class="note">The school was closed on every SMU visit (' + closedDates(s) + '), so no checks are counted.</p>';
    else h += '<p class="note">No SMU visit report is on file for this school' + (s.av.length ? ' in the dates chosen' : '') + '.</p>';
    var panel = $('panel');
    panel.innerHTML = h; panel.scrollTop = 0;
    $('p-close').addEventListener('click', function () { state.sel = null; placeRing(null); renderHint(); });
    if (!quiet && page.clientWidth <= 1100) panel.scrollIntoView({ block: 'start' });
  }

  /* Page */
  function renderAll(fit) {
    var list = scoped(), o = summarise(list), rv = revisitStats(list);
    $('f-clear').hidden = !state.district && !state.zone && fullRange();
    if (META.minKey) { $('f-from').value = keyToISO(state.from); $('f-to').value = keyToISO(state.to); }
    fitScope();
    renderFlow(o, rv); renderImpact(o, rv, list); renderChanged(o, rv, list); renderRevisit(o, rv, list); renderIssues(o); renderDistricts(); renderMap(fit);
    if (state.sel) {
      var s = ALL.filter(function (x) { return x.k === state.sel; })[0];
      if (s && list.indexOf(s) < 0) { state.sel = null; placeRing(null); renderHint(); } else if (s) openSchool(s, false, true);
    }
  }
  // What the page shows, in the filter bar. Where the one-line bar has no room for the whole
  // sentence it is said more briefly, and hovering always shows all of it.
  function fitScope() {
    var el = $('scope'), place = state.district ? state.district : state.zone ? state.zone + ' zone' : 'all of Punjab', dates = fullRange() ? '' : ', visits from ' + rangeText();
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
  $('f-from').addEventListener('change', function () { if (loaded && usableDate(this.value)) setRange(this.value ? isoToKey(this.value) : META.minKey, state.to); });
  $('f-to').addEventListener('change', function () { if (loaded && usableDate(this.value)) setRange(state.from, this.value ? isoToKey(this.value) : META.maxKey); });
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
    if (t) { t.classList.toggle('open'); return; }
    t = e.target.closest('[data-open]');
    if (t) { openById(t.dataset.open); $('mapsec').scrollIntoView(); return; }
    t = e.target.closest('[data-filter]');
    if (t) { selectFilter(t.dataset.filter); return; }
    t = e.target.closest('[data-sort]');
    if (t) { var k = t.dataset.sort; if (state.sort === k) state.dir = -state.dir; else { state.sort = k; state.dir = (k === 'd' || k === 'z' || k === 'top' || k === 'cov' || k === 'ans' || k === 'ta' || k === 'sa') ? 1 : -1; } renderDistricts(); return; }
    t = e.target.closest('tr[data-district], .dname');
    if (t) { setDistrict(t.dataset.district); $('changed').scrollIntoView(); }
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
      return '<button type="button" data-hit="' + esc(s.k) + '">' + esc(s.n) + '<small>EMIS ' + esc(s.e) + ' &middot; ' + esc(s.d) + (s.v.length ? ' &middot; ' + pl(s.v.length, 'visit', 'visits') : ' &middot; Not visited') + '</small></button>';
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
    $('psrpFilters').style.setProperty('--spe-nav-text', Math.ceil(total) + 2 + 'px');
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

  function start(data) {
    ALL = data.schools; META = data.meta; RTEXT = data.rtext; EXTRA = data.extra; CATS = data.cats;
    CAT = {}; CATS.forEach(function (c) { CAT[c.id] = c; });

    // Visits come once; the date filter picks from them. Topics and response types don't change with dates.
    ALL.forEach(function (s) {
      s.av = s.v; s.cs = {}; s.ts = {};
      s.r.forEach(function (r) { r.it.forEach(function (it) { if (!it.i) return; it.c.forEach(function (c) { s.cs[c] = 1; }); s.ts[it.t] = 1; }); });
    });

    var zones = Array.from(new Set(ALL.map(function (s) { return s.z; }).filter(Boolean))).sort();
    if (state.zone && zones.indexOf(state.zone) < 0) zones.push(state.zone);
    $('f-zone').innerHTML = '<option value="">All zones</option>' + zones.map(function (z) { return '<option>' + esc(z) + '</option>'; }).join('');
    $('f-zone').value = state.zone;
    fillDistricts();
    $('f-district').value = state.district;
    $('f-find').innerHTML = '<option value="">Any school</option><option value="rep">Department responded</option><option value="norep">Visited, no department response yet</option>' +
      '<optgroup label="Found at the latest visit">' + FIND.map(function (f) { return '<option value="f:' + f.id + '">' + esc(f.label) + '</option>'; }).join('') + '</optgroup>' +
      '<optgroup label="Issues reported to the department">' + CATS.map(function (c) { return '<option value="c:' + c.id + '">' + esc(c.label) + '</option>'; }).join('') + '</optgroup>' +
      '<optgroup label="Department response">' + RT.map(function (t) { return '<option value="t:' + t.id + '">Response: ' + esc(t.label) + '</option>'; }).join('') + '</optgroup>';

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
