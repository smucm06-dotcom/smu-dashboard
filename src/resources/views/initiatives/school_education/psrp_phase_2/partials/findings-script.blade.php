<script>
// Everything above the records table is worked out here from one download,
// so changing a filter is instant and does not reload the page.
(function () {
  'use strict';

  var page = document.getElementById('psrpPage');
  var ALL = [], META = { minKey: 0, maxKey: 0, cutKey: 0, lastReply: '', lastReplyVisit: '', penalties: 0 }, loaded = false;

  var ISSUES = [
    { id: 'danger', label: 'Dangerous classrooms', why: 'Risk to children' },
    { id: 'water', label: 'No clean drinking water', why: 'Basic need missing' },
    { id: 'gDesk', label: 'Desks 25%+ below declared', why: 'Counted against handover' },
    { id: 'gChair', label: 'Chairs 25%+ below declared', flag: 1, why: 'Where chairs were declared' },
    { id: 'gWash', label: 'Washrooms 25%+ below declared', why: 'Counted against handover' },
    { id: 'gCls', label: 'Classrooms 25%+ below declared' },
    { id: 'cls', label: 'Classrooms dirty or dilapidated', flag: 2, why: 'Poor condition' },
    { id: 'wash', label: 'Washrooms dirty or dilapidated', flag: 2, why: 'Poor condition' },
    { id: 'sch', label: 'School dirty or dilapidated', flag: 2, why: 'Poor condition' },
    { id: 'wall', label: 'No boundary wall', why: 'Safety risk' },
    { id: 'nohand', label: 'Not handed over at the visit', why: 'Handover not done yet' },
    { id: 'nofurn', label: 'Furniture marked not available', why: 'As marked on the visit form' }
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
  var HIGHLIGHT = ['danger', 'water', 'wash', 'sch', 'cls', 'wall', 'nohand', 'nofurn'];
  var OUTCOMES = [
    { id: 'fixed', label: 'Licensee says fixed', sub: 'Stated as done, not re-checked', badge: 'b-info' },
    { id: 'pending', label: 'Work promised', sub: 'In progress, ordered or "soon"', badge: 'b-warn' },
    { id: 'funds', label: 'Funds pending', sub: 'Waiting on PEF money or payment', badge: 'b-warn' },
    { id: 'directed', label: 'Licensee directed', sub: 'Told to repair or clean', badge: 'b-warn' },
    { id: 'dispute', label: 'Finding questioned', sub: 'Says the visit or record was off', badge: 'b-mute' },
    { id: 'noaction', label: 'No action required', sub: '', badge: 'b-good' },
    { id: 'other', label: 'Unclear or blank', sub: 'No clear outcome in the reply', badge: 'b-mute' }
  ];
  var OUT = {}; OUTCOMES.forEach(function (o) { OUT[o.id] = o; });
  var ITEMS = [
    { k: 'cr', label: 'Classrooms', issue: 'gCls' }, { k: 'wr', label: 'Washrooms', issue: 'gWash' },
    { k: 'de', label: 'Desks', issue: 'gDesk' }, { k: 'ch', label: 'Chairs', issue: 'gChair', flag: 1, onlyDeclared: 1 }
  ];
  var MN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  function keyToISO(k) { var s = String(k); return s.slice(0, 4) + '-' + s.slice(4, 6) + '-' + s.slice(6, 8); }
  function isoToKey(s) { return s ? Number(s.replace(/-/g, '')) : 0; }
  function keyLabel(k) { var s = String(k); return Number(s.slice(6, 8)) + ' ' + MN[Number(s.slice(4, 6)) - 1] + ' ' + s.slice(0, 4); }

  var $ = function (id) { return document.getElementById(id); };
  var state = { district: $('f-district').value, from: isoToKey($('f-from').value), to: isoToKey($('f-to').value), show: 'all', issue: '', sel: null, sort: 'cov', dir: 1 };
  function esc(s) { return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function n(x) { return Number(x).toLocaleString('en-US'); }
  function pct(a, b) { if (!b) return 0; var p = a / b * 100; return p > 0 && p < 0.5 ? '<1' : p > 99.5 && p < 100 ? '>99' : Math.round(p); }
  function pl(c, one, many) { return n(c) + ' ' + (c === 1 ? one : many); }
  function signed(x) { return (x > 0 ? '+' : x < 0 ? '−' : '') + n(Math.abs(x)); }
  function fullRange() { return state.from === META.minKey && state.to === META.maxKey; }
  function inRange(s) { return !!s.v && s.v.dk >= state.from && s.v.dk <= state.to; }
  function vis(s) { return inRange(s) ? s.v : null; }
  function iss(s) { return inRange(s) ? s.v.i : []; }
  function lastR(s) { return s.r ? s.r[s.r.length - 1] : null; }
  function scoped() { return state.district ? ALL.filter(function (s) { return s.d === state.district; }) : ALL; }
  function usesReply(s) { return s.r && (inRange(s) || (!s.v && fullRange())); }

  function scopeText() { return 'Everything below shows ' + (state.district || 'all districts') + ', ' + (fullRange() ? 'all dates' : rangeText()); }
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
    var o = { total: list.length, visited: 0, outside: 0, never: 0, withIssues: 0, issues: 0, clean: 0, count: {}, addr: 0, addrSchools: 0, replied: 0, repliedVisited: 0, outs: {}, days: {}, first: 0, last: 0,
      adc: {}, items: {}, anyLarge: 0, anyLess: 0, exact: 0, exactN: 0, missDesks: 0, waterGap: 0, waterDecl: 0, enb: 0, ena: 0, enN: 0, up: 0, down: 0, same: 0, zero: 0, stuN: 0, stuCr: 0, cond: { cc: 0, wc: 0, sc: 0, wat: 0, wall: 0, safe: 0, ho: 0 }, condN: { cc: 0, wc: 0, sc: 0 },
      self: 0, replyRows: 0, noActionIssue: 0, due: 0, dueR: 0, late: 0, afterN: 0, afterIss: 0, afterWaterSaid: 0, afterWaterNo: 0 };
    OUTCOMES.forEach(function (x) { o.outs[x.id] = 0; });
    ISSUES.forEach(function (x) { o.count[x.id] = 0; o.adc[x.id] = 0; });
    ITEMS.forEach(function (x) { o.items[x.k] = { n: 0, decl: 0, found: 0, less: 0, same: 0, more: 0, large: 0, zeroBase: 0, zeroGain: 0 }; });
    list.forEach(function (s) {
      if (usesReply(s)) {
        var last = lastR(s);
        o.replied++; o.outs[last.o]++;
        s.r.forEach(function (x) { o.replyRows++; o.self += x.s; });
      }
      if (!s.v) { o.never++; return; }
      if (!inRange(s)) { o.outside++; return; }
      var v = s.v, i = v.i;
      o.visited++; o.days[v.dk] = 1;
      if (!o.first || v.dk < o.first) o.first = v.dk;
      if (v.dk > o.last) o.last = v.dk;
      o.issues += i.length; if (i.length) o.withIssues++; else o.clean++;
      i.forEach(function (k) { o.count[k]++; });
      if (v.dk <= META.cutKey) { o.due++; if (s.r) o.dueR++; } else o.late++;
      if (s.r) {
        o.repliedVisited++;
        if (v.ad && v.ad.length) { o.addr += v.ad.length; o.addrSchools++; v.ad.forEach(function (k) { o.adc[k]++; }); }
        if (lastR(s).o === 'noaction' && i.length) o.noActionIssue++;
        var lr = lastR(s);
        if (v.dk > lr.dk) {
          o.afterN++; if (i.length) o.afterIss++;
          if (/water|cooler|pump/i.test(lr.tx)) { o.afterWaterSaid++; if (v.wat === 0) o.afterWaterNo++; }
        }
      }
      /* declared vs found */
      if (s.b) {
        var anyLarge = false, anyLess = false;
        ITEMS.forEach(function (it) {
          var b = s.b[it.k], f = v[it.k], t = o.items[it.k];
          if (b === null || b === undefined || f === null || f === undefined) return;
          if (it.onlyDeclared && !b) { t.zeroBase++; if (f > 0) t.zeroGain++; return; }
          t.n++; t.decl += b; t.found += f;
          if (f < b) { t.less++; anyLess = true; if (it.k === 'de') o.missDesks += b - f; } else if (f > b) t.more++; else t.same++;
          if (b > 0 && (b - f) / b >= 0.25) { t.large++; anyLarge = true; }
        });
        if (anyLarge) o.anyLarge++; if (anyLess) o.anyLess++;
        if (s.b.cr !== null && s.b.wr !== null && v.de !== null) { o.exactN++; if (v.cr >= s.b.cr && v.wr >= s.b.wr && v.de >= s.b.de) o.exact++; }
        if (s.b.wa === 1) { o.waterDecl++; if (v.wat === 0) o.waterGap++; }
      }
      /* impact */
      if (v.eb !== undefined && v.ea !== undefined && v.eb !== null && v.ea !== null) {
        o.enN++; o.enb += v.eb; o.ena += v.ea;
        if (v.ea > v.eb) o.up++; else if (v.ea < v.eb) o.down++; else o.same++;
        if (v.ea === 0) o.zero++;
        if (v.cr) { o.stuN += v.ea; o.stuCr += v.cr; }
      }
      ['cc', 'wc', 'sc'].forEach(function (k) { if (v[k]) { o.condN[k]++; if (v[k] === 'Clean') o.cond[k]++; } });
      if (v.wat === 1) o.cond.wat++; if (v.wall === 1) o.cond.wall++; if (!v.dg) o.cond.safe++; if (v.ho === 1) o.cond.ho++;
    });
    return o;
  }

  var ICONS = {
    't-crit': '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 10v4.5"/><path d="M12 17.5v.01"/>',
    't-info': '<path d="M12 3l7.5 3v5.5c0 4.6-3.1 7.9-7.5 9.5-4.4-1.6-7.5-4.9-7.5-9.5V6L12 3Z"/><path d="m8.8 12 2.3 2.3 4.2-4.3"/>',
    't-warn': '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.2 2"/>',
    't-good': '<circle cx="12" cy="12" r="9"/><path d="m8 12.3 2.8 2.7 5.2-5.6"/>'
  };
  function statCards(arr) {
    return arr.map(function (c) {
      return '<div class="card stat ' + c[0] + '"><div class="stat-top"><span class="stat-ico"><svg viewBox="0 0 24 24" aria-hidden="true">' + ICONS[c[0]] + '</svg></span><div class="stat-lbl">' + c[1] + '</div></div><div class="stat-num">' + c[2] + '</div><div class="stat-sub">' + c[3] + '</div></div>';
    }).join('');
  }
  function where() { return state.district ? ' in ' + esc(state.district) : ''; }
  function rangeText() { return keyLabel(state.from) + ' to ' + keyLabel(state.to); }

  /* Declared vs found */
  function renderVerify(o) {
    $('v-stats').innerHTML = statCards([
      ['t-crit', 'Far below what was declared', n(o.anyLarge), 'Schools with at least one item short by 25% or more'],
      ['t-crit', 'Water declared, not found', n(o.waterGap), 'Declared as having water, but no clean water at the visit'],
      ['t-good', 'Everything was there', n(o.exact), 'Schools with at least the declared classrooms, washrooms and desks']
    ]);
    var rows = ITEMS.map(function (it) {
      var t = o.items[it.k], tot = t.less + t.same + t.more || 1;
      return '<tr data-issue="' + it.issue + '"><td class="lab">' + it.label + (it.flag ? ' <span class="flag">*</span>' : '') + '</td><td class="r">' + n(t.decl) + '</td><td class="r">' + n(t.found) + '</td><td class="r">' + n(t.less) + '</td><td class="r">' + n(t.large) + '</td>' +
        '<td><div class="stack" role="img" aria-label="' + t.less + ' fewer, ' + t.same + ' same, ' + t.more + ' more" data-tip="<b>' + it.label + '</b>' + n(t.less) + ' fewer, ' + n(t.same) + ' same, ' + n(t.more) + ' more"><i class="s-less" style="width:' + t.less / tot * 100 + '%"></i><i class="s-same" style="width:' + t.same / tot * 100 + '%"></i><i class="s-more" style="width:' + t.more / tot * 100 + '%"></i></div></td></tr>';
    }).join('');
    $('vt').innerHTML = '<thead><tr><th>Item</th><th class="r">Declared</th><th class="r">Found</th><th class="r">Schools with fewer</th><th class="r">Short by 25% or more</th><th>Share of schools</th></tr></thead><tbody>' + rows + '</tbody>';
    $('v-notes').innerHTML = '<span class="flag">*</span> Chairs are compared only at schools where chairs were declared' + (o.items.ch.zeroBase ? ' (' + pl(o.items.ch.zeroBase, 'school has', 'schools have') + ' 0 chairs in the handover figures)' : '') + '. A lower count can also mean an item is broken or not in use, so treat it as something to check, not proof of loss.';
  }

  /* Licensee types */
  function renderLicensee() {
    var cat = {};
    scoped().forEach(function (s) { (cat[s.c] = cat[s.c] || []).push(s); });
    var cr = Object.keys(cat).map(function (c) { var x = summarise(cat[c]); return { c: c, t: x.total, v: x.visited, g: x.enb ? (x.ena - x.enb) / x.enb * 100 : null, l: x.visited ? x.anyLarge / x.visited : null, p: x.visited ? x.issues / x.visited : null }; }).sort(function (a, b) { return b.v - a.v; });
    $('ct').innerHTML = '<thead><tr><th>Type</th><th class="r">Visited</th><th class="r">Enrolment change</th><th class="r">Far below declared</th><th class="r">Issues per school</th></tr></thead><tbody>' + cr.map(function (r) {
      return '<tr><td>' + esc(r.c) + '</td><td class="r">' + n(r.v) + ' <span class="pct">of ' + n(r.t) + '</span></td><td class="r">' + (r.g === null ? '&ndash;' : (r.g >= 0 ? '+' : '−') + Math.abs(r.g).toFixed(0) + '%') + '</td><td class="r">' + (r.l === null ? '&ndash;' : Math.round(r.l * 100) + '%') + '</td><td class="r">' + (r.p === null ? '&ndash;' : r.p.toFixed(1)) + '</td></tr>';
    }).join('') + '</tbody>';
  }

  /* Issues */
  function selectFinding(id) {
    state.issue = id; state.show = 'all'; $('f-issue').value = id === 'r:wait' ? '' : id; syncSeg(); renderMap(true);
    $('mapsec').scrollIntoView();
  }
  function renderIssues(o, list) {
    dilShift = ratingShift(list.filter(inRange).map(function (s) { return { k: s.v.dk, cc: s.v.cc, wc: s.v.wc, sc: s.v.sc }; }), ['cc', 'wc', 'sc']);
    $('tiles').innerHTML = HIGHLIGHT.map(function (id) {
      var i = ISSUE[id];
      return '<button type="button" class="tile" data-issue="' + id + '"><b>' + n(o.count[id]) + '</b><span>' + esc(i.label) + (i.flag === 2 && dilShift ? '&nbsp;<span class="flag">&dagger;</span>' : '') + '</span><small>' + esc(i.why) + ' &middot; ' + pct(o.count[id], o.visited) + '% of visited</small></button>';
    }).join('');
    $('inotes').innerHTML = dilShift ? '<span class="flag">&dagger;</span> Early visits rated far more schools "Dilapidated" than later ones (up to ' + dilShift.upTo + ': ' + dilShift.early('cc') + ' of classrooms, ' + dilShift.early('wc') + ' of washrooms and ' + dilShift.early('sc') + ' of schools; from ' + dilShift.from + ': ' + dilShift.late('cc') + ', ' + dilShift.late('wc') + ' and ' + dilShift.late('sc') + '). This may be a change in how the form was filled, not a real improvement.' : '';
    var top = list.filter(function (s) { return iss(s).length; }).sort(function (a, b) { return iss(b).length - iss(a).length || a.n.localeCompare(b.n); }).slice(0, 10);
    $('top').innerHTML = top.map(function (s) {
      var r = lastR(s);
      return '<button type="button" data-open="' + s.e + '"><span>' + esc(s.n) + '<small>' + esc(s.d) + (r ? ' &middot; ' + esc(OUT[r.o].label) : ' &middot; No reply on file') + '</small></span><b>' + iss(s).length + ' issues</b></button>';
    }).join('') || '<p class="sub">No issues recorded.</p>';
  }

  /* Replies */
  function renderReplies(o, list) {
    var w = where(), open = o.issues - o.addr;
    if (!o.visited) {
      $('verdict').innerHTML = 'No SMU visits are recorded' + w + (fullRange() ? '' : ' between ' + rangeText()) + '.';
    } else if (!o.due) {
      $('verdict').innerHTML = 'Field teams found issues at <b>' + pct(o.withIssues, o.visited) + '% of the schools they visited</b>' + w + '. None of these visits falls in the period the reply sheet covers, which is visits up to ' + META.lastReplyVisit + '.';
    } else {
      $('verdict').innerHTML = 'Field teams found issues at <b>' + pct(o.withIssues, o.visited) + '% of the schools they visited</b>' + w + '. The department\'s replies cover <b>' + pct(o.addr, o.issues) + '% of those issues</b>' + (o.self / (o.replyRows || 1) > 0.5 ? ', nearly always on the licensee\'s own statement.' : '.');
    }
    $('stats').innerHTML = statCards([
      ['t-crit', 'Issues found', n(o.issues), 'At ' + pl(o.withIssues, 'school', 'schools')],
      ['t-info', 'Issues addressed', n(o.addr), 'A department reply mentions them, at ' + pl(o.addrSchools, 'school', 'schools')],
      ['t-warn', 'Issues with no recorded action', n(open), 'No reply, or the reply does not mention them'],
      ['t-good', 'Schools with no issues', n(o.clean), 'Nothing wrong found at the visit']
    ]);
    $('out-sub').textContent = 'Each school is counted once, under its latest reply. Click a card to see those schools on the map.';
    $('outs').innerHTML = [
      ['r:fixed', o.outs.fixed, OUT.fixed.label, OUT.fixed.sub],
      ['r:wait', o.outs.pending + o.outs.funds, 'Work or funds pending', n(o.outs.funds) + ' of these are waiting on PEF funds'],
      ['r:directed', o.outs.directed, OUT.directed.label, OUT.directed.sub],
      ['r:dispute', o.outs.dispute, OUT.dispute.label, OUT.dispute.sub],
      ['r:noaction', o.outs.noaction, OUT.noaction.label, n(o.noActionIssue) + ' of these had issues on the visit form']
    ].map(function (c) {
      return '<button type="button" class="card out" data-finding="' + c[0] + '"><div class="out-num">' + n(c[1]) + '</div><div class="out-lbl">' + esc(c[2]) + '</div><div class="out-sub">' + esc(c[3]) + '</div></button>';
    }).join('');
    $('caveat').innerHTML = '<b>"Addressed" does not mean fixed on the ground.</b> It only means a reply mentions the issue, usually on the licensee\'s word. ' + (META.lastReply ? (META.penalties ? '' : 'No reply records a penalty or warning. ') + 'Replies stop at ' + META.lastReply + ', so later visits have none, and only ' + n(o.afterN) + ' schools were visited again after a reply.' : 'No department reply is on file yet.');
    var g = o.enb ? Math.round((o.ena - o.enb) / o.enb * 100) : 0;
    $('since').innerHTML =
      '<div class="card"><h3>Students, since handover</h3><div class="since-num">' + n(o.enb) + ' &rarr; ' + n(o.ena) + (o.enb ? ' (' + (g >= 0 ? '+' : '') + g + '%)' : '') + '</div>' +
      '<p class="since-sub">Rose at ' + n(o.up) + ' schools and fell at ' + n(o.down) + ', out of ' + n(o.enN) + ' with both figures. This shows change since handover, not an effect of the visits.</p></div>' +
      '<div class="card"><h3>Handover progress</h3><div class="since-num">' + n(o.cond.ho) + ' of ' + n(o.visited) + ' schools</div>' +
      '<p class="since-sub">Were found handed over when visited.</p></div>';

  }

  /* Districts */
  function districtRows() {
    var by = {};
    ALL.forEach(function (s) { (by[s.d] = by[s.d] || []).push(s); });
    return Object.keys(by).map(function (d) {
      var o = summarise(by[d]);
      var top = ISSUES.slice().sort(function (a, b) { return o.count[b.id] - o.count[a.id]; })[0];
      return { d: d, total: o.total, visited: o.visited, nv: o.never, cov: o.visited / o.total, rate: o.visited ? o.withIssues / o.visited : -1, short: o.visited ? o.anyLarge / o.visited : -1, per: o.visited ? o.issues / o.visited : -1, en: o.enb ? (o.ena - o.enb) / o.enb : -9, top: o.count[top.id] ? top.label : '', replied: o.replied };
    });
  }
  var DCOLS = [
    { k: 'd', t: 'District' }, { k: 'total', t: 'Schools on list', r: 1 }, { k: 'visited', t: 'Visited', r: 1 }, { k: 'nv', t: 'Not visited', r: 1 }, { k: 'cov', t: 'Share visited' },
    { k: 'per', t: 'Issues per school', r: 1 }, { k: 'short', t: 'Far below declared', r: 1 }, { k: 'en', t: 'Enrolment change', r: 1 }, { k: 'replied', t: 'Replies', r: 1 }, { k: 'top', t: 'Most common issue' }
  ];
  function renderDistricts() {
    var D = districtRows(), k = state.sort, dir = state.dir;
    var rows = D.slice().sort(function (a, b) {
      var x = a[k], y = b[k];
      return (typeof x === 'string' ? x.localeCompare(y) : x - y) * dir || a.d.localeCompare(b.d);
    });
    $('dt').innerHTML = '<thead><tr>' + DCOLS.map(function (c) {
      var on = c.k === k;
      return '<th scope="col" class="' + (c.r ? 'r' : '') + '" aria-sort="' + (on ? (dir > 0 ? 'ascending' : 'descending') : 'none') + '"><button type="button" data-sort="' + c.k + '">' + c.t + (on ? (dir > 0 ? ' ↑' : ' ↓') : '') + '</button></th>';
    }).join('') + '</tr></thead><tbody>' + rows.map(function (r) {
      return '<tr data-district="' + esc(r.d) + '"' + (r.d === state.district ? ' class="on"' : '') + '>' +
        '<td><button type="button" class="dname" data-district="' + esc(r.d) + '">' + esc(r.d) + '</button></td><td class="r">' + n(r.total) + '</td><td class="r">' + n(r.visited) + '</td><td class="r">' + n(r.nv) + '</td>' +
        '<td><div class="cov"><div class="bar"><i style="width:' + r.cov * 100 + '%"></i></div><span>' + Math.round(r.cov * 100) + '%</span></div></td>' +
        '<td class="r">' + (r.visited ? r.per.toFixed(1) : '&ndash;') + '</td><td class="r">' + (r.visited ? Math.round(r.short * 100) + '%' : '&ndash;') + '</td><td class="r ' + (r.en === -9 ? '' : r.en >= 0 ? 'up' : 'dn') + '">' + (r.en === -9 ? '&ndash;' : (r.en >= 0 ? '+' : '−') + Math.abs(r.en * 100).toFixed(0) + '%') + '</td><td class="r">' + n(r.replied) + '</td><td>' + esc(r.top) + '</td></tr>';
    }).join('') + '</tbody>';
    var big = D.filter(function (r) { return r.total >= 30; });
    var minCov = big.slice().sort(function (a, b) { return a.cov - b.cov; }).slice(0, 3);
    var reliable = D.filter(function (r) { return r.visited >= 30; });
    var maxS = reliable.slice().sort(function (a, b) { return b.short - a.short; })[0];
    var maxNv = D.slice().sort(function (a, b) { return b.nv - a.nv; })[0];
    $('insights').innerHTML =
      '<div class="card insight"><small>Fewest schools visited</small>' + minCov.map(function (r) { return '<b>' + esc(r.d) + '</b> ' + Math.round(r.cov * 100) + '%'; }).join(', ') + '</div>' +
      '<div class="card insight"><small>Most schools still not visited</small>' + (maxNv ? '<b>' + esc(maxNv.d) + '</b>, ' + n(maxNv.nv) + ' of ' + n(maxNv.total) + ' schools' : '&ndash;') + '</div>' +
      '<div class="card insight"><small>Most schools far below declared</small>' + (maxS ? '<b>' + esc(maxS.d) + '</b>, ' + Math.round(maxS.short * 100) + '% across ' + n(maxS.visited) + ' visits' : 'Too few visits in these dates') + '</div>';
  }

  /* Map */
  var map = L.map('map', { preferCanvas: true, minZoom: 5, maxZoom: 18, zoomSnap: 0.5 });
  map.attributionControl.setPrefix(false); // No "Leaflet" or flag in the corner, only the basemap's credit.
  var tileBase = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_';
  L.tileLayer(tileBase + 'Base/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', attribution: 'Basemap &copy; Esri, HERE, Garmin, OpenStreetMap contributors' }).addTo(map);
  L.tileLayer(tileBase + 'Reference/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18, maxNativeZoom: 16, referrerPolicy: 'no-referrer', pane: 'overlayPane' }).addTo(map);
  var css = getComputedStyle($('psrpFindings'));
  var C = { good: css.getPropertyValue('--good').trim(), crit: css.getPropertyValue('--crit').trim(), surf: css.getPropertyValue('--surface').trim(), ink: css.getPropertyValue('--ink').trim(), muted: css.getPropertyValue('--muted').trim() };
  var renderer = L.canvas({ padding: 0.3, tolerance: 5 });
  var pins = L.layerGroup().addTo(map), markers = {};
  var ring = null, focusPin = null;
  var PUNJAB = [[27.7, 69.3], [34.1, 75.5]];
  function radius() { var z = map.getZoom() || 7; return z < 7 ? 2.5 : z < 8 ? 3 : z < 10 ? 4 : z < 12 ? 5.5 : 7; }
  function status(s) { return inRange(s) ? 'v' : s.v ? 'o' : 'n'; }
  function makeMarker(s) {
    var st = status(s);
    var opt = st === 'v' ? { radius: radius(), renderer: renderer, stroke: true, color: C.surf, weight: 0.8, fillColor: C.good, fillOpacity: 1 }
      : st === 'o' ? { radius: radius() * 0.8, renderer: renderer, stroke: true, color: C.surf, weight: 0.5, fillColor: C.muted, fillOpacity: 0.55 }
      : { radius: radius(), renderer: renderer, stroke: true, color: C.crit, weight: 1.8, fillColor: C.surf, fillOpacity: 1 };
    var m = L.circleMarker([s.la, s.lo], opt);
    m.bindTooltip(function () { return esc(s.n) + (st === 'v' ? ' (visited ' + esc(s.v.dt) + ')' : st === 'o' ? ' (visited ' + esc(s.v.dt) + ', outside these dates)' : ' (not visited)'); }, { direction: 'top', offset: [0, -4] });
    m.on('click', function () { openSchool(s, false); });
    m.smaller = st === 'o';
    return m;
  }
  function matchesFinding(s) {
    var f = state.issue;
    if (!f) return true;
    if (f === 'r:wait') { var q = lastR(s); return !!(q && usesReply(s) && (q.o === 'pending' || q.o === 'funds')); }
    if (f.indexOf('r:') === 0) { var r = lastR(s); return f === 'r:none' ? (inRange(s) && !s.r) : !!(r && usesReply(s) && r.o === f.slice(2)); }
    return iss(s).indexOf(f) >= 0;
  }
  function matchesMap(s) {
    var st = status(s);
    if (state.show === 'v' && st !== 'v') return false;
    if (state.show === 'n' && st !== 'n') return false;
    return matchesFinding(s);
  }
  function findingLabel(f) { if (f === 'r:wait') return 'Reply: Work or funds pending'; return f.indexOf('r:') === 0 ? (f === 'r:none' ? 'No reply on file' : 'Reply: ' + OUT[f.slice(2)].label) : ISSUE[f].label; }
  function renderMap(fit) {
    pins.clearLayers();
    var list = scoped().filter(matchesMap);
    var cnt = { v: 0, o: 0, n: 0 }, unmapped = 0, pts = [], visitIds = [];
    var order = { n: 0, o: 1, v: 2 };
    list.sort(function (a, b) { return order[status(a)] - order[status(b)]; });
    list.forEach(function (s) {
      if (s.la === undefined) { unmapped++; return; }
      var st = status(s);
      cnt[st]++;
      if (st === 'v') visitIds.push(s.v.id);
      pins.addLayer(markers[s.e] || (markers[s.e] = makeMarker(s)));
      pts.push([s.la, s.lo]);
    });
    // Only a chosen finding can be downloaded, and the file holds exactly the visited schools on the map.
    $('map-download').hidden = !(state.issue && visitIds.length);
    if (state.issue) {
      $('dl-ids').value = visitIds.join(',');
      $('dl-issue').value = findingLabel(state.issue);
      $('dl-label').textContent = 'Download ' + pl(visitIds.length, 'school', 'schools');
    }
    var label = state.issue ? ' with "' + esc(findingLabel(state.issue)) + '"' : '';
    $('legend').innerHTML = (state.show !== 'n' ? '<div class="lg"><span class="pin v"></span>Visited <span class="lg-n">' + n(cnt.v) + ' pins' + label + '</span></div>' : '') +
      (cnt.o ? '<div class="lg"><span class="pin o"></span>Visited, outside these dates <span class="lg-n">' + n(cnt.o) + '</span></div>' : '') +
      (state.show !== 'v' && !state.issue ? '<div class="lg"><span class="pin n"></span>Not visited <span class="lg-n">' + n(cnt.n) + ' pins</span></div>' : '');
    $('map-note').textContent = (unmapped ? n(unmapped) + (unmapped === 1 ? ' school' : ' schools') + ' in this view have no usable coordinates, so they are not on the map. Search finds them. ' : '') +
      (state.issue ? (pts.length ? 'A finding filter is on, so not-visited schools are hidden.' : 'No school in this view matches this finding.') : '');
    if (fit) {
      var zone = pts.length ? pts : scoped().filter(function (s) { return s.la !== undefined; }).map(function (s) { return [s.la, s.lo]; });
      if (zone.length && (state.district || state.issue)) map.fitBounds(L.latLngBounds(zone).pad(0.08), { maxZoom: 12 });
      else map.fitBounds(PUNJAB);
    }
    if (state.sel) placeRing(ALL.filter(function (x) { return x.e === state.sel; })[0] || null);
  }
  map.on('zoomend', function () {
    var r = radius();
    Object.keys(markers).forEach(function (k) { markers[k].setRadius(markers[k].smaller ? r * 0.8 : r); });
    if (focusPin) focusPin.setRadius(r);
  });
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
  function count(val) { return val === null || val === undefined ? '<span class="na">Not recorded</span>' : n(val); }
  function rows(list) { return '<table class="rt"><tbody>' + list.map(function (r) { return '<tr><td>' + r[0] + '</td><td>' + r[1] + '</td></tr>'; }).join('') + '</tbody></table>'; }
  function renderHint() {
    $('panel').innerHTML = '<div class="panel-hint"><h3>School report</h3><p>Click a pin on the map, or search above, to see the school\'s report here.</p><ul>' +
      '<li><b>Green pin:</b> visited. You will see what was declared, what the team found, and any department reply.</li>' +
      '<li><b>Red ring:</b> not visited yet. You will see what the department declared at handover.</li>' +
      '<li><b>Grey pin:</b> visited, but outside the dates you selected.</li></ul></div>';
  }
  function openSchool(s, focus) {
    state.sel = s.e;
    placeRing(s);
    if (focus && s.la !== undefined) map.setView([s.la, s.lo], Math.max(map.getZoom(), 12));
    var v = s.v, h = '', last = lastR(s);
    h += '<div class="p-top"><div><div class="p-name">' + esc(s.n) + '</div><div class="p-meta">EMIS ' + esc(s.e) + ' &middot; ' + esc(s.d) + (s.t ? ' &middot; ' + esc(s.t) : '') + '</div>' +
      (s.o ? '<div class="p-meta">Licensee: ' + esc(s.o) + (s.c ? ' (' + esc(s.c) + ')' : '') + '</div>' : '') + '</div>' +
      '<button type="button" class="x" id="p-close" aria-label="Close report">×</button></div>';
    h += '<div class="badges">' + (v ? '<span class="badge b-good">● Visited ' + esc(v.dt) + '</span>' : '<span class="badge b-crit">○ Not visited</span>') +
      (last ? '<span class="badge ' + OUT[last.o].badge + '">' + esc(OUT[last.o].label) + '</span>' : (v ? '<span class="badge b-mute">No reply on file</span>' : '')) + '</div>';
    if (s.la === undefined) h += '<p class="note">This school has no usable coordinates, so it has no pin on the map.</p>';
    if (s.pm) h += '<p class="note">The handover file says PMIU data was not received for this school, so it has no declared figures.</p>';
    if (v) {
      h += '<div class="p-sec"><h3>' + (v.i.length ? pl(v.i.length, 'issue', 'issues') + ' found' : 'No issues found') + '</h3>' +
        (v.i.length ? '<div class="chips">' + v.i.map(function (k) { return '<span class="chip">' + esc(ISSUE[k].label) + '</span>'; }).join('') + '</div>' : '') + '</div>';
    }
    if (s.b || v) {
      var b = s.b || {};
      var line = function (lab, k) {
        var d = b[k], f = v ? v[k] : null, diff = d !== undefined && d !== null && f !== null && f !== undefined ? f - d : null;
        return '<tr><td>' + lab + '</td><td class="r">' + (d === undefined || d === null ? '&ndash;' : n(d)) + '</td>' + (v ? '<td class="r">' + (f === null || f === undefined ? '&ndash;' : n(f)) + '</td><td class="r ' + (diff < 0 ? 'bad' : diff > 0 ? 'ok' : '') + '">' + (diff === null ? '&ndash;' : signed(diff)) + '</td>' : '') + '</tr>';
      };
      h += '<div class="p-sec"><h3>Declared at handover' + (v ? ' vs found at the visit' : '') + '</h3><table class="dvd"><thead><tr><th>Item</th><th class="r">Declared</th>' + (v ? '<th class="r">Found</th><th class="r">Change</th>' : '') + '</tr></thead><tbody>' +
        line('Classrooms', 'cr') + line('Washrooms', 'wr') + line('Desks', 'de') + line('Chairs', 'ch') + (v ? '<tr><td>Fans</td><td class="r">&ndash;</td><td class="r">' + count(v.fa) + '</td><td class="r">&ndash;</td></tr>' : '') +
        '<tr><td>Students</td><td class="r">' + (v && v.eb !== undefined && v.eb !== null ? n(v.eb) : b.en !== undefined ? n(b.en) : '&ndash;') + '</td>' + (v ? '<td class="r">' + (v.ea !== undefined && v.ea !== null ? n(v.ea) : '&ndash;') + '</td><td class="r ' + (v.ea < v.eb ? 'bad' : v.ea > v.eb ? 'ok' : '') + '">' + (v.ea !== undefined && v.ea !== null && v.eb !== undefined && v.eb !== null ? signed(v.ea - v.eb) : '&ndash;') + '</td>' : '') + '</tr>' +
        '<tr><td>Water</td><td class="r">' + (b.wa === undefined ? '&ndash;' : b.wa ? 'Yes' : 'No') + '</td>' + (v ? '<td class="r">' + (v.wat === 1 ? 'Clean: yes' : v.wat === 0 ? 'Clean: no' : '&ndash;') + '</td><td class="r"></td>' : '') + '</tr></tbody></table></div>';
    }
    if (s.r) {
      s.r.slice().reverse().forEach(function (r, i) {
        h += '<div class="p-sec"><h3>Department reply, ' + esc(r.dt) + '</h3><div class="reply">' +
          (r.rm ? '<p><small>Sent to the department, on the ' + esc(r.vd) + ' visit</small>' + esc(r.rm) + '</p>' : '') +
          '<p><small>Department said</small>' + (r.tx ? esc(r.tx) : '<span class="na">Reply is blank in the sheet</span>') + '</p></div></div>';
      });
    }
    if (v) {
      h += '<div class="p-sec"><h3>Visit findings</h3>' + rows([
        ['School handed over', yesNo(v.ho, 1)], ['Furniture available', yesNo(v.fu, 1)], ['Clean drinking water', yesNo(v.wat, 1)], ['Boundary wall', yesNo(v.wall, 1)],
        ['Dangerous classrooms', v.dg === null ? '<span class="na">Not recorded</span>' : '<span class="' + (v.dg > 0 ? 'bad' : 'ok') + '">' + (v.dg > 0 ? '✗ ' : '✓ ') + n(v.dg) + '</span>'],
        ['Classrooms', cond(v.cc)], ['Washrooms', cond(v.wc)], ['Overall cleanliness', cond(v.sc)]
      ]) + '</div>';
    } else {
      h += '<p class="note">No SMU visit report is on file for this school.</p>';
    }
    var panel = $('panel');
    panel.innerHTML = h;
    panel.scrollTop = 0;
    $('p-close').addEventListener('click', function () { state.sel = null; placeRing(null); renderHint(); });
    if (page.clientWidth <= 1100) panel.scrollIntoView({ block: 'start' });
  }

  /* Page */
  /* The four cards in the banner, for what the filters select, counted as the figures below:
     schools on the department's list, schools visited in the dates, and schools answered by the
     department, where their reply counts. Reports Sent to Department counts our uploaded report
     PDFs, which cover every district and date, so it is left as drawn. */
  function setCard(id, value) { var el = $(id); if (el) el.textContent = n(value); }
  function hasReplyText(s) { return s.r.some(function (r) { return String(r.tx || '').trim() !== ''; }); }
  function renderTopbar(list) {
    var reported = list.filter(usesReply);
    setCard('c-total', list.filter(function (s) { return s.b || s.pm; }).length);
    setCard('c-visited', list.filter(inRange).length);
    setCard('c-replied', reported.filter(hasReplyText).length);
  }

  function renderAll(fit) {
    var list = scoped(), o = summarise(list);
    fitScope();
    $('f-clear').hidden = !state.district && fullRange();
    renderTopbar(list);
    if (META.minKey) { $('f-from').value = keyToISO(state.from); $('f-to').value = keyToISO(state.to); }
    renderVerify(o); renderIssues(o, list); renderReplies(o, list); renderDistricts(); renderLicensee(); renderMap(fit);
    if (state.sel) { var s = ALL.filter(function (x) { return x.e === state.sel; })[0]; if (s && state.district && s.d !== state.district) { state.sel = null; placeRing(null); renderHint(); } }
  }
  function filtersChanged() {
    document.dispatchEvent(new CustomEvent('psrp:filters', { detail: { district: state.district, from: state.from === META.minKey ? '' : keyToISO(state.from), to: state.to === META.maxKey ? '' : keyToISO(state.to) } }));
  }
  function setDistrict(d) { state.district = d; $('f-district').value = d; if (loaded) renderAll(true); filtersChanged(); }
  function setRange(a, b) {
    a = Math.max(a || META.minKey, META.minKey); b = Math.min(b || META.maxKey, META.maxKey);
    if (a > b) { var t = a; a = b; b = t; }
    state.from = a; state.to = b; markers = {};
    if (loaded) renderAll(false);
    filtersChanged();
  }

  $('f-issue').innerHTML = '<option value="">Any finding or none</option><optgroup label="Issues found">' + ISSUES.map(function (i) { return '<option value="' + i.id + '">' + esc(i.label) + '</option>'; }).join('') + '</optgroup><optgroup label="Department reply">' + OUTCOMES.map(function (o) { return '<option value="r:' + o.id + '">Reply: ' + esc(o.label) + '</option>'; }).join('') + '<option value="r:none">Visited, no reply on file</option></optgroup>';
  // Typing a date passes through years like 0002, which are not worth acting on.
  function usableDate(value) { return value === '' || value >= '2000-01-01'; }
  $('psrpFilters').addEventListener('submit', function (e) { e.preventDefault(); });
  $('f-from').addEventListener('change', function () { if (usableDate(this.value)) setRange(this.value ? isoToKey(this.value) : META.minKey, state.to); });
  $('f-to').addEventListener('change', function () { if (usableDate(this.value)) setRange(state.from, this.value ? isoToKey(this.value) : META.maxKey); });
  $('f-district').addEventListener('change', function () { setDistrict(this.value); });
  $('f-clear').addEventListener('click', function () {
    state.district = ''; state.from = META.minKey; state.to = META.maxKey; markers = {};
    $('f-district').value = '';
    if (loaded) renderAll(true);
    filtersChanged();
  });
  $('f-issue').addEventListener('change', function () { state.issue = this.value; if (state.issue) { state.show = 'all'; syncSeg(); } renderMap(true); });
  document.querySelector('.seg').addEventListener('click', function (e) {
    var b = e.target.closest('button'); if (!b) return;
    state.show = b.dataset.show;
    if (state.show === 'n') { state.issue = ''; $('f-issue').value = ''; }
    syncSeg(); renderMap(false);
  });
  $('psrpFindings').addEventListener('click', function (e) {
    var t = e.target.closest('[data-issue]');
    if (t) { selectFinding(t.dataset.issue); return; }
    t = e.target.closest('[data-finding]');
    if (t) { selectFinding(t.dataset.finding); return; }
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
  /* Hover layer */
  var tip = $('tip');
  $('vt').addEventListener('mouseleave', function () { tip.hidden = true; });
  $('vt').addEventListener('mousemove', function (e) {
    var t = e.target.closest('[data-tip]');
    if (!t) { tip.hidden = true; return; }
    tip.innerHTML = t.getAttribute('data-tip'); tip.hidden = false;
    var x = Math.min(e.clientX + 14, window.innerWidth - tip.offsetWidth - 8), y = e.clientY - tip.offsetHeight - 10;
    tip.style.left = x + 'px'; tip.style.top = (y < 4 ? e.clientY + 16 : y) + 'px';
  });
  function openById(id) {
    var s = ALL.filter(function (x) { return String(x.e) === String(id); })[0];
    if (s) openSchool(s, true);
  }
  $('q').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase(), box = $('results');
    if (q.length < 2) { box.hidden = true; return; }
    var hits = ALL.filter(function (s) { return s.n.toLowerCase().indexOf(q) >= 0 || String(s.e).indexOf(q) >= 0; }).slice(0, 12);
    box.innerHTML = hits.map(function (s) {
      return '<button type="button" data-hit="' + s.e + '">' + esc(s.n) + '<small>EMIS ' + s.e + ' &middot; ' + esc(s.d) + (s.v ? ' &middot; Visited ' + esc(s.v.dt) : ' &middot; Not visited') + '</small></button>';
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
    META = data.meta;

    var names = Array.from(new Set(ALL.map(function (s) { return s.d; }))).sort();
    if (state.district && names.indexOf(state.district) < 0) names.push(state.district);
    $('f-district').innerHTML = '<option value="">All districts</option>' + names.map(function (d) { return '<option>' + esc(d) + '</option>'; }).join('');
    $('f-district').value = state.district;

    state.from = Math.max(state.from || META.minKey, META.minKey);
    state.to = Math.min(state.to || META.maxKey, META.maxKey);
    if (META.minKey) ['f-from', 'f-to'].forEach(function (id) { $(id).min = keyToISO(META.minKey); $(id).max = keyToISO(META.maxKey); });

    loaded = true;
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
  renderHint();
  load();
  window.addEventListener('load', function () { if (!loaded) { map.invalidateSize(); map.fitBounds(PUNJAB); } });
})();
</script>
