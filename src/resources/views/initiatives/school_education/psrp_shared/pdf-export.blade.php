{{--
    Export to PDF: the page exactly as it is on the screen at that moment, from the banner to the
    end, with the filters, map, opened school and everything else as they are. It is made in the
    browser and downloaded; nothing is sent to or kept on the server. No library is used: the page
    is copied with each element's on-screen style fixed in place, drawn as a picture, and written
    into a PDF here.

    A page using it puts this button where it should show (the end of the breadcrumb row); its
    look is in the shared findings styles:
    <button type="button" class="psrp-pdf-btn" id="pdfExport" data-pdf-skip data-pdf-name="...">…</button>
--}}
<script>
(function () {
  'use strict';

  var button = document.getElementById('pdfExport');
  if (!button) return;

  var SVG_NS = 'http://www.w3.org/2000/svg';
  // Styles that change nothing in a still picture, or that would change its layout. ("will-change"
  // is kept: it keeps the map's tile layer separate, without which the tiles blend away to white.)
  var SKIP = /^(--|transition|animation|container|content-visibility|contain-intrinsic|cursor|pointer-events|user-select|-webkit-user-select|caret-color|scroll-|overscroll|touch-action|view-transition|-webkit-tap-highlight)/;
  var SCALE = 2;            // two image pixels per screen pixel, so text stays sharp
  var MAX_PAGE = 14000;     // tallest PDF page in screen pixels; longer pages are split between sections
  var label = button.querySelector('span') || button;
  var idleText = label.textContent;

  /* Each element's default style, from an empty page, so only what differs is written. */
  var sandbox = null, defaults = {};
  function sandboxDoc() {
    if (!sandbox) {
      sandbox = document.createElement('iframe');
      sandbox.setAttribute('aria-hidden', 'true');
      sandbox.style.cssText = 'position:fixed;left:-10000px;top:0;width:200px;height:200px;border:0;visibility:hidden';
      document.body.appendChild(sandbox);
      sandbox.contentDocument.open();
      sandbox.contentDocument.write('<!DOCTYPE html><html><head><meta charset="utf-8"></head><body></body></html>');
      sandbox.contentDocument.close();
    }
    return sandbox.contentDocument;
  }
  function defaultsOf(el) {
    var key = el.namespaceURI + ' ' + el.localName;
    if (defaults[key]) return defaults[key];
    var doc = sandboxDoc(), holder, probe;
    if (el.namespaceURI === SVG_NS) {
      holder = doc.body.appendChild(doc.createElementNS(SVG_NS, 'svg'));
      probe = el.localName === 'svg' ? holder : holder.appendChild(doc.createElementNS(SVG_NS, el.localName));
    } else {
      holder = probe = doc.body.appendChild(doc.createElement(el.localName));
    }
    var cs = sandbox.contentWindow.getComputedStyle(probe), map = {};
    for (var i = 0; i < cs.length; i++) map[cs[i]] = cs.getPropertyValue(cs[i]);
    holder.remove();
    return (defaults[key] = map);
  }

  /* Pictures and fonts are put inside the copy, as a picture cannot load anything itself. */
  var loaded = {};
  function asDataUrl(url) {
    if (!url || url.indexOf('data:') === 0) return Promise.resolve(url || '');
    if (!loaded[url]) {
      var same = new URL(url, location.href).origin === location.origin;
      loaded[url] = fetch(url, { mode: 'cors', credentials: same ? 'same-origin' : 'omit', cache: 'force-cache' })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.blob(); })
        .then(function (blob) {
          return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            reader.onload = function () { resolve(reader.result); };
            reader.onerror = reject;
            reader.readAsDataURL(blob);
          });
        })
        // A picture that cannot be read is left out rather than stopping the whole PDF.
        .catch(function () { return ''; });
    }
    return loaded[url];
  }
  function inlineUrls(css, base, jobs, apply) {
    var urls = [];
    css.replace(/url\(\s*(['"]?)([^'")]+)\1\s*\)/g, function (m, q, u) { if (u.indexOf('data:') !== 0) urls.push(u); return m; });
    if (!urls.length) { apply(css); return; }
    jobs.push(Promise.all(urls.map(function (u) { return asDataUrl(new URL(u, base).href); })).then(function (data) {
      var i = 0;
      apply(css.replace(/url\(\s*(['"]?)([^'")]+)\1\s*\)/g, function (m, q, u) { return u.indexOf('data:') === 0 ? m : 'url("' + (data[i++] || '') + '")'; }));
    }));
  }
  function fontFaces() {
    var blocks = [];
    Array.prototype.forEach.call(document.styleSheets, function (sheet) {
      var rules = null;
      try { rules = sheet.cssRules; } catch (e) { rules = null; }
      var base = sheet.href || location.href;
      if (rules) {
        Array.prototype.forEach.call(rules, function (rule) { if (rule.type === CSSRule.FONT_FACE_RULE) blocks.push(Promise.resolve([rule.cssText, base])); });
      } else if (sheet.href) {
        // Stylesheets from another site, such as the fonts, are read again here.
        blocks.push(fetch(sheet.href, { mode: 'cors', credentials: 'omit' }).then(function (r) { return r.text(); })
          .then(function (text) { return [(text.match(/@font-face\s*\{[^}]*\}/g) || []).join('\n'), base]; })
          .catch(function () { return ['', base]; }));
      }
    });
    return Promise.all(blocks).then(function (list) {
      var jobs = [], out = [];
      list.forEach(function (item, i) { inlineUrls(item[0], item[1], jobs, function (css) { out[i] = css; }); });
      return Promise.all(jobs).then(function () { return out.join('\n'); });
    });
  }

  /* The copy: each element with its on-screen style, minus what matches its parent and default. */
  function copyOf(source, parentStyle, jobs, pseudo) {
    if (source.nodeType === 3) return document.createTextNode(source.nodeValue);
    if (source.nodeType !== 1 || source.hasAttribute('data-pdf-skip')) return null;
    var tag = source.localName;
    if (/^(script|noscript|template|style|link|iframe|dialog)$/.test(tag)) return null;
    var cs = getComputedStyle(source);
    if (cs.display === 'none') return null;

    var target;
    if (tag === 'canvas') {
      // Map pins and other drawings are kept as they are on the screen.
      target = document.createElement('img');
      try { target.setAttribute('src', source.toDataURL('image/png')); } catch (e) { /* left blank */ }
    } else {
      target = source.cloneNode(false);
    }

    var def = defaultsOf(source), style = [];
    for (var i = 0; i < cs.length; i++) {
      var p = cs[i];
      if (SKIP.test(p)) continue;
      var v = cs.getPropertyValue(p);
      if (v === def[p] && (!parentStyle || v === parentStyle.getPropertyValue(p))) continue;
      style.push(p + ':' + v);
    }
    // The map fades each tile in. A tile caught part way, or held at the start while the window
    // is in the background, is drawn as it ends up: fully shown.
    if (source.classList && source.classList.contains('leaflet-tile-loaded')) style.push('opacity:1');
    inlineUrls(style.join(';'), location.href, jobs, function (css) { target.setAttribute('style', css); });

    ['::before', '::after'].forEach(function (which) {
      var ps = getComputedStyle(source, which), content = ps.getPropertyValue('content');
      if (!content || content === 'none' || content === 'normal') return;
      var name = 'pdfp' + pseudo.length, rule = [];
      for (var j = 0; j < ps.length; j++) if (!SKIP.test(ps[j])) rule.push(ps[j] + ':' + ps.getPropertyValue(ps[j]));
      target.setAttribute('class', ((target.getAttribute('class') || '') + ' ' + name).trim());
      inlineUrls('.' + name + which + '{' + rule.join(';') + '}', location.href, jobs, function (css) { pseudo.push(css); });
    });

    if (tag === 'img') {
      target.removeAttribute('srcset');
      target.removeAttribute('loading');
      jobs.push(asDataUrl(source.currentSrc || source.src).then(function (data) { target.setAttribute('src', data); }));
    } else if (tag === 'input') {
      target.setAttribute('value', source.value);
      if (source.checked) target.setAttribute('checked', ''); else target.removeAttribute('checked');
    } else if (tag === 'option') {
      if (source.selected) target.setAttribute('selected', ''); else target.removeAttribute('selected');
    } else if (tag === 'textarea') {
      target.textContent = source.value;
      return target;
    }

    if (tag !== 'canvas') {
      for (var c = source.firstChild; c; c = c.nextSibling) {
        var copy = copyOf(c, cs, jobs, pseudo);
        if (copy) target.appendChild(copy);
      }
    }
    return target;
  }

  /* Where a long page may be split: between the banner, the bars and the sections. */
  function breakPoints(root, top) {
    var points = {};
    root.querySelectorAll('.app-topbar, .psrp-breadcrumb, .filterbar, .psrp-page section, .psrp-records, .psrp-records > *, .psrp-findings > section > *').forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.height) { points[Math.round(r.top - top)] = 1; points[Math.round(r.bottom - top)] = 1; }
    });
    return Object.keys(points).map(Number).sort(function (a, b) { return a - b; });
  }
  function pageCuts(height, points) {
    var cuts = [0];
    while (height - cuts[cuts.length - 1] > MAX_PAGE) {
      var from = cuts[cuts.length - 1], limit = from + MAX_PAGE;
      var best = points.filter(function (y) { return y > from + MAX_PAGE / 2 && y <= limit; }).pop();
      cuts.push(best || limit);
    }
    cuts.push(height);
    return cuts;
  }

  function loadImage(url) {
    return new Promise(function (resolve, reject) {
      var img = new Image();
      img.onload = function () { (img.decode ? img.decode() : Promise.resolve()).catch(function () {}).then(function () { resolve(img); }); };
      img.onerror = function () { reject(new Error('The page picture could not be drawn.')); };
      img.src = url;
    });
  }
  function jpegOf(img, y, width, height, background) {
    var canvas = document.createElement('canvas');
    canvas.width = Math.round(width * SCALE);
    canvas.height = Math.round(height * SCALE);
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = background;
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, y, width, height, 0, 0, canvas.width, canvas.height);
    return new Promise(function (resolve) { canvas.toBlob(resolve, 'image/jpeg', 0.92); })
      .then(function (blob) { return blob.arrayBuffer(); })
      .then(function (buffer) { return { data: new Uint8Array(buffer), pw: canvas.width, ph: canvas.height, w: width * 0.75, h: height * 0.75 }; });
  }

  /* A PDF with one picture per page, written here (sizes in points: 3 per 4 screen pixels). */
  function pdfOf(pages) {
    var enc = new TextEncoder(), parts = [], size = 0, offsets = [];
    function add(x) { var b = typeof x === 'string' ? enc.encode(x) : x; parts.push(b); size += b.length; }
    function obj(n, head, stream) {
      offsets[n] = size;
      add(n + ' 0 obj\n' + head + (stream ? '\nstream\n' : '\nendobj\n'));
      if (stream) { add(stream); add('\nendstream\nendobj\n'); }
    }
    add('%PDF-1.4\n%âãÏÓ\n');
    obj(1, '<< /Type /Catalog /Pages 2 0 R >>');
    obj(2, '<< /Type /Pages /Kids [' + pages.map(function (p, i) { return (3 + i * 3) + ' 0 R'; }).join(' ') + '] /Count ' + pages.length + ' >>');
    pages.forEach(function (p, i) {
      var n = 3 + i * 3, w = p.w.toFixed(2), h = p.h.toFixed(2), draw = 'q ' + w + ' 0 0 ' + h + ' 0 0 cm /Im' + i + ' Do Q';
      obj(n, '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' + w + ' ' + h + '] /Resources << /XObject << /Im' + i + ' ' + (n + 1) + ' 0 R >> >> /Contents ' + (n + 2) + ' 0 R >>');
      obj(n + 1, '<< /Type /XObject /Subtype /Image /Width ' + p.pw + ' /Height ' + p.ph + ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' + p.data.length + ' >>', p.data);
      obj(n + 2, '<< /Length ' + draw.length + ' >>', enc.encode(draw));
    });
    var count = 3 + pages.length * 3, start = size, xref = 'xref\n0 ' + count + '\n0000000000 65535 f \n';
    for (var k = 1; k < count; k++) xref += String(offsets[k]).padStart(10, '0') + ' 00000 n \n';
    add(xref + 'trailer\n<< /Size ' + count + ' /Root 1 0 R >>\nstartxref\n' + start + '\n%%EOF\n');
    return new Blob(parts, { type: 'application/pdf' });
  }

  function fileName() {
    var scope = document.getElementById('scope'), what = scope ? (scope.title || scope.textContent) : '';
    what = what.replace(/^Everything below shows\s*/i, '').replace(/^Showing\s*/i, '');
    var d = new Date(), date = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    var name = [button.getAttribute('data-pdf-name') || document.title, what, date].filter(Boolean).join(' - ');
    return name.replace(/[\\/:*?"<>|\u0000-\u001f]+/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 180) + '.pdf';
  }

  function makePdf() {
    var root = document.querySelector('.app-main') || document.body;
    var rect = root.getBoundingClientRect(), width = Math.ceil(rect.width), height = Math.ceil(rect.height);
    var background = getComputedStyle(document.body).backgroundColor;
    if (!background || background === 'rgba(0, 0, 0, 0)') background = '#ffffff';
    var points = breakPoints(root, rect.top);
    var jobs = [], pseudo = [];

    return (document.fonts ? document.fonts.ready : Promise.resolve()).then(function () {
      var copy = copyOf(root, null, jobs, pseudo);
      return Promise.all([fontFaces(), Promise.all(jobs)]).then(function (done) {
        // The page sits beside the sidebar on the screen; in the PDF it starts at the left edge.
        copy.style.margin = '0';
        copy.style.width = width + 'px';
        var holder = document.createElement('div');
        holder.setAttribute('style', 'margin:0;width:' + width + 'px;height:' + height + 'px;background:' + background);
        var css = document.createElement('style');
        css.textContent = done[0] + '\n' + pseudo.join('\n');
        holder.appendChild(css);
        holder.appendChild(copy);
        var svg = '<svg xmlns="' + SVG_NS + '" width="' + width + '" height="' + height + '" viewBox="0 0 ' + width + ' ' + height + '">'
          + '<foreignObject x="0" y="0" width="100%" height="100%">' + new XMLSerializer().serializeToString(holder) + '</foreignObject></svg>';
        return loadImage('data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg));
      });
    }).then(function (img) {
      // Fonts inside the picture can arrive just after it loads, so give them a moment.
      return new Promise(function (resolve) { setTimeout(function () { resolve(img); }, 250); });
    }).then(function (img) {
      var cuts = pageCuts(height, points), pages = [];
      var chain = Promise.resolve();
      cuts.slice(0, -1).forEach(function (y, i) {
        chain = chain.then(function () { return jpegOf(img, y, width, cuts[i + 1] - y, background).then(function (page) { pages.push(page); }); });
      });
      return chain.then(function () { return pdfOf(pages); });
    });
  }

  button.addEventListener('click', function () {
    if (button.disabled) return;
    button.disabled = true;
    label.textContent = 'Preparing PDF…';
    var name = fileName();
    makePdf().then(function (blob) {
      var link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = name;
      document.body.appendChild(link);
      link.click();
      setTimeout(function () { URL.revokeObjectURL(link.href); link.remove(); }, 10000);
      label.textContent = idleText;
    }).catch(function (error) {
      console.error(error);
      label.textContent = 'PDF failed, try again';
      setTimeout(function () { label.textContent = idleText; }, 4000);
    }).then(function () {
      button.disabled = false;
    });
  });
})();
</script>
