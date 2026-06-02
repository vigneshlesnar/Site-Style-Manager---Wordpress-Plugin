/* global ssmFrontend, jQuery */
(function ($) {
  'use strict';

  const cfg     = ssmFrontend;
  const saved   = cfg.saved   || {};
  const preload = cfg.scanned || { colors: [], vars: {} };

  /* ── State ─────────────────────────────────────────────────── */
  const S = {
    open:         false,
    activeTab:    'colors',
    activeBp:     'desktop',         // desktop | tablet | mobile
    selectedHex:  null,
    colorMap:     Object.assign({}, saved.colors    || {}),
    typoEls:      JSON.parse(JSON.stringify(saved.typography?.elements || {})),
    loadedFonts:  {},
    dirty:        false,
    scannedColors: [],
    scannedVars:   {},
  };

  /* ── Font list ─────────────────────────────────────────────── */
  const FONTS = ['','Inter','Roboto','Open Sans','Lato','Montserrat','Poppins','Nunito',
    'DM Sans','Plus Jakarta Sans','Work Sans','Raleway','Josefin Sans','Quicksand',
    'Merriweather','Playfair Display','Libre Baskerville','Lora',
    'Oswald','Bebas Neue','Fira Code','Source Code Pro','Inconsolata'];

  /* ── Element rows shown in badge ──────────────────────────── */
  const ELEMS = [
    { key: 'body',       label: 'Body / Paragraph', tag: 'P'   },
    { key: 'h1',         label: 'Heading 1',         tag: 'H1'  },
    { key: 'h2',         label: 'Heading 2',         tag: 'H2'  },
    { key: 'h3',         label: 'Heading 3',         tag: 'H3'  },
    { key: 'h4',         label: 'Heading 4',         tag: 'H4'  },
    { key: 'h5',         label: 'Heading 5',         tag: 'H5'  },
    { key: 'h6',         label: 'Heading 6',         tag: 'H6'  },
    { key: 'link',       label: 'Links',             tag: 'A'   },
    { key: 'blockquote', label: 'Blockquote',        tag: '❝'   },
    { key: 'code',       label: 'Code / Pre',        tag: '</>' },
    { key: 'button',     label: 'Button',            tag: 'BTN' },
    { key: 'nav',        label: 'Navigation',        tag: 'NAV' },
    { key: 'caption',    label: 'Caption / Small',   tag: 'sm'  },
  ];

  /* ════════════════════════════════════════════════════════════
     DOM — build badge on page load
  ══════════════════════════════════════════════════════════ */
  function build() {
    const $wrap = $('<div id="ssm-badge-wrap">');

    $('<div id="ssm-overlay">').appendTo($wrap).on('click', closePanel);

    // FAB
    $('<button id="ssm-fab" aria-label="Style Manager">').html(
      '<svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>' +
      '<span class="ssm-dot"></span>'
    ).on('click', togglePanel).appendTo($wrap);

    // Panel
    $('<div id="ssm-panel">').append(
      buildHeader(), buildTabs(), buildBody(), buildFooter()
    ).appendTo($wrap);

    $('<div id="ssm-ptost">').appendTo($wrap);
    $('body').append($wrap);
  }

  /* ── Header ────────────────────────────────────────────────── */
  function buildHeader() {
    return $('<div class="ssm-ph">').append(
      $('<div class="ssm-ph-title">').append(
        $('<div class="ssm-ph-icon">').html('<svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8z"/></svg>'),
        $('<div>').append(
          $('<span class="ssm-ph-name">Style Manager</span>'),
          $('<span class="ssm-ph-sub">Live editing mode</span>')
        )
      ),
      $('<button class="ssm-ph-close">').html('<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>').on('click', closePanel)
    );
  }

  /* ── Tabs ──────────────────────────────────────────────────── */
  function buildTabs() {
    const $t = $('<div class="ssm-pt">');
    [['colors','🎨 Colors'],['fonts','Aa Fonts']].forEach(function([id,label]) {
      $('<button class="ssm-ptbtn">').text(label).attr('data-ptab', id)
        .toggleClass('active', id === S.activeTab)
        .on('click', function () { switchTab(id); })
        .appendTo($t);
    });
    return $t;
  }

  /* ── Body ──────────────────────────────────────────────────── */
  function buildBody() {
    return $('<div class="ssm-pb">').append(buildColorsPane(), buildFontsPane());
  }

  /* ── Colors pane ───────────────────────────────────────────── */
  function buildColorsPane() {
    const $p = $('<div class="ssm-pane" id="ssm-pane-colors">').toggleClass('active', S.activeTab === 'colors');

    // Scan bar
    $p.append(
      $('<div class="ssm-scan-bar">').append(
        $('<button class="ssm-scan-btn" id="ssm-scan-btn">').append(
          $('<span class="ssm-scan-icon">').html('<svg viewBox="0 0 20 20" fill="currentColor" width="14"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>'),
          $('<span class="ssm-scan-lbl">').text('Scan Page Colors')
        ).on('click', scanPageColors),
        $('<button class="ssm-rescan-btn" title="Re-scan page">').text('↺').on('click', function () {
          S.scannedColors = []; scanPageColors();
        })
      )
    );

    // Swatch grid
    $p.append(
      $('<div class="ssm-sec-title ssm-colors-title">Page Colors</div>'),
      $('<div class="ssm-color-grid-p" id="ssm-pgrid">').append(
        $('<div class="ssm-color-empty">Click "Scan Page Colors" to detect all colors used on this page.</div>')
      )
    );

    // Replace panel (hidden until swatch clicked)
    const $rep = $('<div class="ssm-rep-row" id="ssm-rep-row">').hide().append(
      $('<div class="ssm-rep-hdr">').append(
        $('<span class="ssm-rep-title">Replace Color</span>'),
        $('<button class="ssm-rep-close">').text('✕').on('click', deselectSwatch)
      ),
      $('<div class="ssm-rep-body">').append(
        $('<div class="ssm-rep-side">').append(
          $('<div class="ssm-rep-lbl">From</div>'),
          $('<span class="ssm-rep-from" id="ssm-rep-from">')
        ),
        $('<div class="ssm-rep-arrow">→</div>'),
        $('<div class="ssm-rep-side">').append(
          $('<div class="ssm-rep-lbl">To</div>'),
          $('<div class="ssm-rep-to-wrap">').append(
            $('<span class="ssm-rep-dot" id="ssm-rep-dot">').on('click', function () {
              nativePicker($('#ssm-rep-hex').val() || '#000000', function (v) {
                $('#ssm-rep-dot').css('background', v);
                $('#ssm-rep-hex').val(v);
                liveApply();
              });
            }),
            $('<input class="ssm-rep-hex" id="ssm-rep-hex" type="text" maxlength="7" placeholder="#000000">').on('input', function () {
              const v = $(this).val();
              if (/^#[0-9a-fA-F]{6}$/.test(v)) { $('#ssm-rep-dot').css('background', v); liveApply(); }
            })
          )
        )
      ),
      $('<div class="ssm-rep-foot">').append(
        $('<button class="ssm-rep-apply">Apply</button>').on('click', function () {
          if (!S.selectedHex) return;
          const nw = $('#ssm-rep-hex').val();
          if (!/^#[0-9a-fA-F]{6}$/.test(nw)) return;
          S.colorMap[S.selectedHex] = nw;
          updateSwatchInGrid(S.selectedHex, nw);
          markDirty(); liveApply(); pToast('Color replaced!');
        }),
        $('<button class="ssm-rep-clear" id="ssm-rep-clear">✕ Clear</button>').on('click', function () {
          if (!S.selectedHex) return;
          delete S.colorMap[S.selectedHex];
          updateSwatchInGrid(S.selectedHex, S.selectedHex);
          liveApply(); markDirty(); deselectSwatch();
        })
      )
    );
    $p.append($rep);

    if (preload.colors && preload.colors.length) renderColorGrid(preload.colors);

    return $p;
  }

  /* ── Fonts pane ────────────────────────────────────────────── */
  function buildFontsPane() {
    const $p = $('<div class="ssm-pane" id="ssm-pane-fonts">').toggleClass('active', S.activeTab === 'fonts');

    // Breakpoint selector
    const $bpRow = $('<div class="ssm-bp-row" id="ssm-bp-row">');
    [['desktop','🖥 Desktop'],['tablet','📱 Tablet ≤1024'],['mobile','📱 Mobile ≤767']].forEach(function([bp,label]) {
      $('<button class="ssm-bp-pill">').text(label).attr('data-bp', bp)
        .toggleClass('active', bp === S.activeBp)
        .on('click', function () {
          S.activeBp = bp;
          $('.ssm-bp-pill').removeClass('active');
          $(this).addClass('active');
          refreshFontPane();
        })
        .appendTo($bpRow);
    });
    $p.append($bpRow);

    const $cards = $('<div id="ssm-el-cards">');
    ELEMS.forEach(function (el) { $cards.append(buildElCard(el)); });
    $p.append($cards);

    return $p;
  }

  function buildElCard(el) {
    const bp      = S.activeBp;
    const saved_v = S.typoEls[el.key] || {};

    const weights = [['','—'],['300','Light'],['400','Regular'],['500','Medium'],['600','SemiBold'],['700','Bold'],['800','ExtraBold']];

    const $fontSel = $('<select class="ssm-p-select ssm-font-sel">');
    FONTS.forEach(function (f) {
      $('<option>').val(f).text(f || '— Inherit —').prop('selected', f === (saved_v.font || '')).appendTo($fontSel);
    });
    $fontSel.on('change', function () {
      const v = $(this).val();
      setTypo(el.key, 'font', v);
      if (v) loadGF(v);
      liveApply(); markDirty();
    });

    const $wSel = $('<select class="ssm-p-select">');
    weights.forEach(function ([v,l]) {
      $('<option>').val(v).text(l).prop('selected', v === (saved_v.weight || '')).appendTo($wSel);
    });
    $wSel.on('change', function () {
      setTypo(el.key, 'weight', $(this).val()); liveApply(); markDirty();
    });

    const $sizeIn = $('<input class="ssm-p-input ssm-size-input" type="text">').val(saved_v[bp] || '')
      .attr('placeholder', getDefaultSize(el.key, bp))
      .on('input', function () { setTypo(el.key, bp, $(this).val()); liveApply(); markDirty(); });

    const $lhIn = $('<input class="ssm-p-input ssm-lh-input" type="text" placeholder="1.5">').val(saved_v.line_height || '')
      .on('input', function () { setTypo(el.key, 'line_height', $(this).val()); liveApply(); markDirty(); });

    return $('<div class="ssm-el-card">').attr('data-el-key', el.key).append(
      $('<div class="ssm-el-hdr">').append(
        $('<span class="ssm-el-tag">').text(el.tag),
        $('<span class="ssm-el-name">').text(el.label)
      ),
      $fontSel,
      $('<div class="ssm-el-row2">').append(
        $('<div class="ssm-el-field">').append($('<span class="ssm-el-flabel">').text('Size'), $sizeIn),
        $('<div class="ssm-el-field">').append($('<span class="ssm-el-flabel">').text('Weight'), $wSel),
        $('<div class="ssm-el-field">').append($('<span class="ssm-el-flabel">').text('Line-H'), $lhIn)
      )
    );
  }

  function refreshFontPane() {
    const bp = S.activeBp;
    $('#ssm-el-cards .ssm-el-card').each(function () {
      const key = $(this).attr('data-el-key');
      const val = (S.typoEls[key] || {})[bp] || '';
      $(this).find('.ssm-size-input')
        .val(val)
        .attr('placeholder', getDefaultSize(key, bp));
    });
  }

  function getDefaultSize(key, bp) {
    const defs = {
      body:       { desktop:'16', tablet:'15', mobile:'14' },
      h1:         { desktop:'48', tablet:'38', mobile:'30' },
      h2:         { desktop:'36', tablet:'28', mobile:'24' },
      h3:         { desktop:'28', tablet:'22', mobile:'20' },
      h4:         { desktop:'22', tablet:'18', mobile:'17' },
      h5:         { desktop:'18', tablet:'16', mobile:'15' },
      h6:         { desktop:'16', tablet:'15', mobile:'14' },
      link:       { desktop:'—',  tablet:'—',  mobile:'—'  },
      blockquote: { desktop:'20', tablet:'18', mobile:'16' },
      code:       { desktop:'14', tablet:'13', mobile:'12' },
      button:     { desktop:'15', tablet:'14', mobile:'14' },
      nav:        { desktop:'15', tablet:'14', mobile:'13' },
      caption:    { desktop:'12', tablet:'12', mobile:'11' },
    };
    return (defs[key] && defs[key][bp]) || '—';
  }

  /* ── Footer ────────────────────────────────────────────────── */
  function buildFooter() {
    return $('<div class="ssm-pf">').append(
      $('<button class="ssm-pf-save" id="ssm-pf-save">').html('<svg viewBox="0 0 20 20" fill="currentColor" width="14"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293z"/></svg> Save').on('click', saveAll),
      $('<a class="ssm-pf-admin" target="_blank">').attr('href', cfg.adminUrl).html('<svg viewBox="0 0 20 20" fill="currentColor" width="12"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg> Admin')
    );
  }

  /* ════════════════════════════════════════════════════════════
     DOM COLOR SCANNER  —  reads computed styles off real page
  ══════════════════════════════════════════════════════════ */
  function scanPageColors() {
    const $btn = $('#ssm-scan-btn').prop('disabled', true);
    $('#ssm-scan-btn .ssm-scan-lbl').text('Scanning…');

    setTimeout(function () {
      const result = collectPageColors();
      S.scannedColors = result.colors;
      S.scannedVars   = result.vars;
      renderColorGrid(result.colors);

      $.post(cfg.ajaxUrl, { action: 'ssm_store_page_colors', nonce: cfg.nonce, colors: JSON.stringify(result.colors) });
      $.post(cfg.ajaxUrl, { action: 'ssm_store_css_vars',    nonce: cfg.nonce, vars:   JSON.stringify(result.vars) });

      $('#ssm-scan-btn .ssm-scan-lbl').text('Scan Page Colors');
      $btn.prop('disabled', false);
      pToast(result.colors.length + ' colors found');
    }, 50);
  }

  function collectPageColors() {
    const colorCounts = {};
    const cssVars     = {};
    const skip = new Set(['rgba(0, 0, 0, 0)', 'transparent', '']);
    const props = ['color', 'backgroundColor', 'borderTopColor', 'outlineColor'];

    // Scan DOM elements (capped at 600 for performance)
    const els = Array.from(document.querySelectorAll('body *')).slice(0, 600);
    els.forEach(function (el) {
      // skip our own badge elements
      if (el.id && (el.id.startsWith('ssm-') || el.closest && el.closest('#ssm-badge-wrap'))) return;
      const cs = window.getComputedStyle(el);
      props.forEach(function (p) {
        const v = cs[p];
        if (!v || skip.has(v)) return;
        const hex = rgbToHex(v);
        if (hex) colorCounts[hex] = (colorCounts[hex] || 0) + 1;
      });
    });

    // Scan CSS custom properties from :root
    try {
      const rootStyle = window.getComputedStyle(document.documentElement);
      Array.from(rootStyle).forEach(function (prop) {
        if (!prop.startsWith('--')) return;
        const val = rootStyle.getPropertyValue(prop).trim();
        if (isColorVal(val)) cssVars[prop] = val;
      });
    } catch (e) {}

    const colors = Object.entries(colorCounts)
      .map(function ([hex, count]) { return { hex, count, name: colorName(hex), light: isLight(hex) }; })
      .sort(function (a, b) { return b.count - a.count; })
      .slice(0, 80);

    return { colors, vars: cssVars };
  }

  function renderColorGrid(colors) {
    const $grid = $('#ssm-pgrid').empty();
    if (!colors.length) {
      $grid.append('<span class="ssm-color-empty">No colors detected. Make sure the page has visible content.</span>');
      return;
    }
    colors.forEach(function (c) {
      const hasMap  = !!S.colorMap[c.hex];
      const showHex = hasMap ? S.colorMap[c.hex] : c.hex;
      const $sw = $('<span class="ssm-pswatch">').css('background', showHex)
        .attr('data-orig', c.hex).toggleClass('selected', hasMap)
        .append($('<span class="ssm-pswatch-tip">').text(c.hex))
        .on('click', function () { selectSwatch(c.hex, $(this)); });
      $grid.append($sw);
    });
  }

  function selectSwatch(hex, $sw) {
    S.selectedHex = hex;
    $('.ssm-pswatch').removeClass('selected');
    $sw.addClass('selected');
    const current = S.colorMap[hex] || hex;
    $('#ssm-rep-from').css('background', hex);
    $('#ssm-rep-dot').css('background', current);
    $('#ssm-rep-hex').val(current);
    $('#ssm-rep-row').show();
  }

  function deselectSwatch() {
    S.selectedHex = null;
    $('.ssm-pswatch').removeClass('selected');
    $('#ssm-rep-row').hide();
  }

  function updateSwatchInGrid(oldHex, newHex) {
    $('[data-orig="' + oldHex + '"]').css('background', newHex).toggleClass('selected', oldHex !== newHex);
  }

  /* ════════════════════════════════════════════════════════════
     LIVE CSS INJECTION
  ══════════════════════════════════════════════════════════ */
  function liveApply() {
    let css = '';

    // ── CSS variable overrides (from scanned root vars + color map)
    const rootVars = {};
    Object.entries(S.scannedVars || preload.vars || {}).forEach(function ([varName, varVal]) {
      const norm = normalHex(varVal);
      if (norm && S.colorMap[norm]) rootVars[varName] = S.colorMap[norm];
    });
    if (Object.keys(rootVars).length) {
      css += ':root {\n';
      Object.entries(rootVars).forEach(function ([k,v]) { css += '  ' + k + ': ' + v + ';\n'; });
      css += '}\n\n';
    }

    // ── Walk CSSOM for direct color replacements
    Object.entries(S.colorMap).forEach(function ([oldHex, newHex]) {
      css += csomOverrides(oldHex, newHex);
    });

    // ── Typography — desktop rules
    const typoDesktop = buildTypoCSS('desktop');
    if (typoDesktop) css += typoDesktop;

    // ── Inject
    let $style = $('#ssm-live-inject');
    if (!$style.length) $style = $('<style id="ssm-live-inject">').appendTo('head');
    $style.text(css);

    // ── Tablet & mobile via dynamic <style> with media queries
    const typoTablet = buildTypoCSS('tablet');
    const typoMobile = buildTypoCSS('mobile');
    let $mq = $('#ssm-live-inject-mq');
    if (!$mq.length) $mq = $('<style id="ssm-live-inject-mq">').appendTo('head');
    let mq = '';
    if (typoTablet) mq += '@media (max-width:1024px) {\n' + typoTablet + '}\n';
    if (typoMobile) mq += '@media (max-width:767px) {\n'  + typoMobile + '}\n';
    $mq.text(mq);
  }

  function buildTypoCSS(bp) {
    const normSz = function (s) { return s && /^\d+(\.\d+)?$/.test(s.trim()) ? s.trim() + 'px' : s; };
    const selMap = {
      body:'body,p,div,span,li,td,th', h1:'h1', h2:'h2', h3:'h3', h4:'h4', h5:'h5', h6:'h6',
      link:'a', blockquote:'blockquote', code:'code,pre', button:'button,.btn,.button,input[type="submit"]',
      nav:'nav a,.menu-item a', caption:'small,figcaption,.wp-caption-text', list:'ul,ol,li', label:'label',
    };
    let css = '';
    Object.entries(S.typoEls).forEach(function ([key, el]) {
      if (!selMap[key]) return;
      const sel   = selMap[key];
      const rules = [];
      if (bp === 'desktop') {
        if (el.font)           rules.push('font-family:\'' + el.font + '\',sans-serif!important');
        if (el.weight)         rules.push('font-weight:' + el.weight + '!important');
        if (el.line_height)    rules.push('line-height:' + el.line_height + '!important');
        if (el.letter_spacing) rules.push('letter-spacing:' + el.letter_spacing + '!important');
        if (el.desktop)        rules.push('font-size:' + normSz(el.desktop) + '!important');
      } else {
        const size = el[bp];
        if (size) rules.push('font-size:' + normSz(size) + '!important');
      }
      if (rules.length) css += sel + '{' + rules.join(';') + '}\n';
    });
    return css;
  }

  function csomOverrides(oldHex, newHex) {
    const oldRgb = hexRgb(oldHex);
    if (!oldRgb) return '';
    let css = '';
    try {
      for (let i = 0; i < document.styleSheets.length; i++) {
        let rules;
        try { rules = document.styleSheets[i].cssRules; } catch (e) { continue; }
        if (!rules) continue;
        for (let j = 0; j < rules.length; j++) {
          const rule = rules[j];
          if (!rule.style || !rule.selectorText) continue;
          const parts = [];
          for (let k = 0; k < rule.style.length; k++) {
            const prop = rule.style[k];
            const val  = rule.style.getPropertyValue(prop);
            if (rgbMatch(val, oldRgb)) parts.push(prop + ':' + newHex);
          }
          if (parts.length) css += rule.selectorText + '{' + parts.join(';') + '!important}\n';
        }
      }
    } catch (e) {}
    return css;
  }

  /* ════════════════════════════════════════════════════════════
     SAVE
  ══════════════════════════════════════════════════════════ */
  function saveAll() {
    const $btn = $('#ssm-pf-save').prop('disabled', true).html('<span class="ssm-spin"></span> Saving…');
    const styles = {
      colors:     S.colorMap,
      css_vars:   {},
      typography: { elements: S.typoEls },
      palette:    saved.palette    || [],
      custom_css: saved.custom_css || '',
    };
    $.post(cfg.ajaxUrl, { action: 'ssm_save_styles', nonce: cfg.nonce, styles: JSON.stringify(styles) })
      .done(function (r) {
        if (r.success) { S.dirty = false; markDirty(); pToast('Saved!'); } else pToast('Error', 'err');
      })
      .fail(() => pToast('Network error', 'err'))
      .always(() => $btn.prop('disabled', false).html('<svg viewBox="0 0 20 20" fill="currentColor" width="14"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293z"/></svg> Save'));
  }

  /* ════════════════════════════════════════════════════════════
     HELPERS
  ══════════════════════════════════════════════════════════ */
  function setTypo(key, prop, val) {
    if (!S.typoEls[key]) S.typoEls[key] = {};
    S.typoEls[key][prop] = val;
  }

  function markDirty() {
    S.dirty = Object.keys(S.colorMap).length > 0 || Object.keys(S.typoEls).length > 0;
    $('#ssm-fab').toggleClass('dirty', S.dirty);
  }

  function switchTab(id) {
    S.activeTab = id;
    $('.ssm-ptbtn').removeClass('active');
    $('[data-ptab="' + id + '"]').addClass('active');
    $('.ssm-pane').removeClass('active');
    $('#ssm-pane-' + id).addClass('active');
  }

  function togglePanel() { S.open ? closePanel() : openPanel(); }
  function openPanel()  { S.open = true;  $('#ssm-fab').addClass('open');  $('#ssm-panel').addClass('open');  $('#ssm-overlay').addClass('show'); }
  function closePanel() { S.open = false; $('#ssm-fab').removeClass('open');$('#ssm-panel').removeClass('open');$('#ssm-overlay').removeClass('show'); }

  function pToast(msg, type) {
    $('#ssm-ptost').text(msg).toggleClass('err', type === 'err').show();
    setTimeout(() => $('#ssm-ptost').fadeOut(350), 3000);
  }

  function nativePicker(initial, cb) {
    const $p = $('<input type="color">').val(initial).css({ position:'absolute', opacity:0, left:'-9999px' });
    $('body').append($p); $p[0].click();
    $p.on('input change', () => cb($p.val()));
    $p.on('blur', () => setTimeout(() => $p.remove(), 200));
  }

  function loadGF(family) {
    if (!family || S.loadedFonts[family]) return;
    S.loadedFonts[family] = true;
    $('<link>').attr({ rel:'stylesheet', href:'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(family) + ':wght@300;400;500;600;700&display=swap' }).appendTo('head');
  }

  /* Color utilities */
  function rgbToHex(rgb) {
    const m = rgb.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
    if (!m) return null;
    const r = +m[1], g = +m[2], b = +m[3];
    if (r === 0 && g === 0 && b === 0) return null; // skip true black from default
    return '#' + [r,g,b].map(v => ('0'+v.toString(16)).slice(-2)).join('');
  }
  function hexRgb(hex) {
    const m = /^#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return m ? { r: parseInt(m[1],16), g: parseInt(m[2],16), b: parseInt(m[3],16) } : null;
  }
  function normalHex(val) {
    val = (val || '').trim();
    if (/^#[0-9a-f]{6}$/i.test(val)) return val.toLowerCase();
    const m = val.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
    if (m) return '#'+[m[1],m[2],m[3]].map(v=>('0'+(+v).toString(16)).slice(-2)).join('');
    return null;
  }
  function rgbMatch(val, rgb) {
    const h = normalHex(val);
    if (!h) return false;
    return h === ('#' + [rgb.r,rgb.g,rgb.b].map(v=>('0'+v.toString(16)).slice(-2)).join(''));
  }
  function isColorVal(val) { return /^#[0-9a-fA-F]{3,6}$|^rgb/.test(val.trim()); }
  function isLight(hex) {
    const rgb = hexRgb(hex); if (!rgb) return true;
    return (rgb.r*299 + rgb.g*587 + rgb.b*114) / 1000 > 128;
  }
  function colorName(hex) {
    const rgb = hexRgb(hex); if (!rgb) return '';
    const { r, g, b } = rgb;
    const br = (r*299+g*587+b*114)/1000;
    if (br < 22)  return 'Black';
    if (br > 238) return 'White';
    if (r===g&&g===b) return 'Gray';
    const max=Math.max(r,g,b),min=Math.min(r,g,b),d=max-min;
    if (d<20) return 'Gray';
    let h = max===r ? 60*((g-b)/d) : max===g ? 60*(2+(b-r)/d) : 60*(4+(r-g)/d);
    if (h<0) h+=360;
    if (h<15||h>=345) return 'Red';
    if (h<45)  return 'Orange';
    if (h<70)  return 'Yellow';
    if (h<150) return 'Green';
    if (h<195) return 'Teal';
    if (h<255) return 'Blue';
    if (h<285) return 'Indigo';
    if (h<320) return 'Purple';
    return 'Pink';
  }

  /* ── Bootstrap ─────────────────────────────────────────────── */
  $(function () {
    build();
    // Apply saved styles on every page load
    if (Object.keys(S.colorMap).length || Object.keys(S.typoEls).length) {
      liveApply();
      markDirty();
    }
    // Pre-load saved fonts
    Object.values(S.typoEls).forEach(el => { if (el.font) loadGF(el.font); });

    // Auto-scan on open (once per session)
    let autoScanned = false;
    $(document).on('click', '#ssm-fab', function () {
      if (!autoScanned && S.activeTab === 'colors') {
        autoScanned = true;
        setTimeout(scanPageColors, 400);
      }
    });
  });

})(jQuery);
