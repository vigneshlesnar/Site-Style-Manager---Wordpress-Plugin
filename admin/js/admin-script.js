/* global ssmAdmin, jQuery */
(function ($) {
  'use strict';

  /* ── Config ────────────────────────────────────────────────── */
  const cfg      = ssmAdmin;
  const saved    = cfg.saved   || {};
  const preloaded = cfg.scanned || { colors: [], vars: {} };

  /* ── App state ─────────────────────────────────────────────── */
  const state = {
    scannedColors:  preloaded.colors || [],
    scannedVars:    preloaded.vars   || {},
    activeBp:       'desktop',
    colorMaps:      Object.assign({}, saved.colors         || {}),
    varOverrides:   Object.assign({}, saved.css_vars       || {}),
    typoElements:   JSON.parse(JSON.stringify(saved.typography?.elements || {})),
    palette:        (saved.palette || []).slice(),
    elementColors:  Object.assign({}, saved.element_colors || {}),
    customCss:      saved.custom_css || '',
  };

  /* 36 preset colors for the color picker (6 columns × 6 rows) */
  const SSM_PRESETS = [
    '#111827','#374151','#6b7280','#9ca3af','#d1d5db','#ffffff',
    '#7f1d1d','#b91c1c','#ef4444','#f87171','#fca5a5','#fee2e2',
    '#78350f','#b45309','#f59e0b','#fbbf24','#fcd34d','#fef9c3',
    '#14532d','#15803d','#22c55e','#4ade80','#86efac','#dcfce7',
    '#1e3a5f','#1d4ed8','#3b82f6','#60a5fa','#93c5fd','#dbeafe',
    '#4c1d95','#7c3aed','#8b5cf6','#a78bfa','#c4b5fd','#f5f3ff',
  ];

  /* ── Init ──────────────────────────────────────────────────── */
  $(function () {
    initTabs();
    initBreakpointSwitcher();
    initScanButtons();
    initColorMapUI();
    initColorFields();
    initPaletteUI();
    initCustomCss();
    initSaveBtns();
    loadSavedColors();
    loadSavedVarOverrides();
    updateTypoPreview();

    if (state.scannedColors.length) {
      renderColorGrid(state.scannedColors);
      renderVarList(state.scannedVars);
      $('#ssm-scan-results').show();
      $('#ssm-scan-meta').text('From last scan · ' + state.scannedColors.length + ' colors');
    }
  });

  /* ── Tabs ──────────────────────────────────────────────────── */
  function initTabs() {
    $(document).on('click', '.ssm-tab', function () {
      $('.ssm-tab').removeClass('active');
      $(this).addClass('active');
      $('.ssm-panel').removeClass('active');
      $('#tab-' + $(this).data('tab')).addClass('active');
    });
  }

  /* ── Toast ─────────────────────────────────────────────────── */
  function toast(msg, type = 'success') {
    const icons = { success: '✓', error: '✕', info: 'ℹ' };
    const $t = $('<div class="ssm-toast">').addClass(type).html(`<span>${icons[type]||'·'}</span><span>${msg}</span>`);
    $('#ssm-toasts').append($t);
    setTimeout(() => $t.fadeOut(300, () => $t.remove()), 3400);
  }

  /* ══════════════════════════════════════════════════════════════
     UNIFIED COLOR FIELDS  (element colors + typography colors)
  ════════════════════════════════════════════════════════════ */
  function initColorFields() {
    // Build preset swatches into every color-field popup on the page
    $('.ssm-color-field .ssm-cf-presets').each(function () {
      const $p = $(this);
      SSM_PRESETS.forEach(function (hex) {
        $p.append($('<button class="ssm-cf-preset" type="button">').attr('data-color', hex).css('background', hex));
      });
    });

    // Load saved element colors (Colors tab fields)
    Object.entries(state.elementColors).forEach(function ([key, hex]) {
      const $f = $('.ssm-color-field[data-key="' + key + '"]');
      if ($f.length && hex) applyFieldColor($f, hex);
    });

    // Load saved typography colors (Typography table fields)
    Object.entries(state.typoElements).forEach(function ([key, el]) {
      if (el.color) {
        const $f = $('.ssm-color-field[data-typo-key="' + key + '"]');
        if ($f.length) applyFieldColor($f, el.color);
      }
    });

    // Open / close popup — works for both field types
    $(document).on('click', '.ssm-color-field .ssm-cf-open, .ssm-color-field .ssm-cf-swatch', function (e) {
      e.stopPropagation();
      const $field = $(this).closest('.ssm-color-field');
      const isOpen = $field.find('.ssm-cf-popup').hasClass('active');
      closeAllColorPopups();
      if (!isOpen) $field.find('.ssm-cf-popup').addClass('active');
    });

    // Preset swatch click
    $(document).on('click', '.ssm-cf-preset', function (e) {
      e.stopPropagation();
      const hex    = $(this).data('color');
      const $field = $(this).closest('.ssm-color-field');
      applyFieldColor($field, hex);
      onColorChange($field, hex);
      closeAllColorPopups();
    });

    // Native color-wheel input
    $(document).on('input', '.ssm-color-field .ssm-cf-native', function () {
      const hex    = $(this).val();
      const $field = $(this).closest('.ssm-color-field');
      applyFieldColor($field, hex);
      onColorChange($field, hex);
    });

    // Hex text inside popup
    $(document).on('input', '.ssm-color-field .ssm-cf-hex-in', function () {
      const hex = $(this).val().trim();
      if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
        const $field = $(this).closest('.ssm-color-field');
        applyFieldColor($field, hex);
        onColorChange($field, hex);
      }
    });

    // Main hex input (outside popup, element-color fields only)
    $(document).on('change', '.ssm-color-field .ssm-cf-hex', function () {
      const hex = $(this).val().trim();
      if (/^#[0-9a-fA-F]{6}$/.test(hex)) {
        const $field = $(this).closest('.ssm-color-field');
        applyFieldColor($field, hex);
        onColorChange($field, hex);
      }
    });

    // Clear button
    $(document).on('click', '.ssm-color-field .ssm-cf-clear', function (e) {
      e.stopPropagation();
      clearFieldColor($(this).closest('.ssm-color-field'));
      closeAllColorPopups();
    });

    // Close popup on outside click
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.ssm-color-field').length) closeAllColorPopups();
    });
  }

  /* Dispatch a color change to the right part of state */
  function onColorChange($field, hex) {
    const ecKey    = $field.data('key');
    const typoKey  = $field.data('typo-key');
    if (ecKey)   state.elementColors[ecKey] = hex;
    if (typoKey) {
      if (!state.typoElements[typoKey]) state.typoElements[typoKey] = {};
      state.typoElements[typoKey].color = hex;
      updateTypoPreview();
    }
  }

  /* Sync all visible parts of a color field to a new hex value */
  function applyFieldColor($field, hex) {
    $field.find('.ssm-cf-swatch').css('background', hex);
    $field.find('.ssm-cf-hex').val(hex);
    $field.find('.ssm-cf-hex-in').val(hex);
    $field.find('.ssm-cf-native').val(hex);
    $field.find('.ssm-cf-preset').removeClass('selected')
      .filter('[data-color="' + hex + '"]').addClass('selected');
  }

  /* Reset a color field to "unset" state and remove from state */
  function clearFieldColor($field) {
    $field.find('.ssm-cf-swatch').css('background', '#f1f5f9');
    $field.find('.ssm-cf-hex').val('');
    $field.find('.ssm-cf-hex-in').val('');
    $field.find('.ssm-cf-native').val('#6366f1');
    $field.find('.ssm-cf-preset').removeClass('selected');
    const ecKey   = $field.data('key');
    const typoKey = $field.data('typo-key');
    if (ecKey)   delete state.elementColors[ecKey];
    if (typoKey && state.typoElements[typoKey]) {
      delete state.typoElements[typoKey].color;
      updateTypoPreview();
    }
  }

  function closeAllColorPopups() {
    $('.ssm-cf-popup').removeClass('active');
  }

  /* ══════════════════════════════════════════════════════════════
     SCANNER — iframe-based DOM color scan
  ════════════════════════════════════════════════════════════ */
  function initScanButtons() {
    $('#ssm-scan-btn').on('click',  () => startScan(false));
    $('#ssm-rescan-btn').on('click', () => startScan(true));
  }

  function startScan(force) {
    if (!force && state.scannedColors.length) {
      renderColorGrid(state.scannedColors);
      renderVarList(state.scannedVars);
      $('#ssm-scan-results').show();
      return;
    }

    $('#ssm-scan-btn, #ssm-rescan-btn').prop('disabled', true);
    $('#ssm-progress-wrap').show();
    $('#ssm-scan-results').hide();
    animateProgress(0, 30, 1000);
    $('#ssm-progress-label').text('Loading page…');

    // Listen for postMessage from iframe scanner
    const onMsg = function (e) {
      if (!e.originalEvent || !e.originalEvent.data || !e.originalEvent.data.ssmScan) return;
      window.removeEventListener('message', onMsg._raw);

      const data = e.originalEvent.data.ssmScan;
      animateProgress(80, 100, 400);

      setTimeout(() => {
        state.scannedColors = data.colors || [];
        state.scannedVars   = data.vars   || {};

        renderColorGrid(state.scannedColors);
        renderVarList(state.scannedVars);
        $('#ssm-scan-results').show();
        $('#ssm-progress-wrap').hide();
        $('#ssm-scan-meta').html('Scanned <strong>' + state.scannedColors.length + '</strong> colors just now');

        // Persist to server
        sendScannedToServer(state.scannedColors, state.scannedVars);
        toast('Scan complete — ' + state.scannedColors.length + ' colors found');
      }, 500);
    };
    onMsg._raw = function (e) { onMsg({ originalEvent: e }); };
    window.addEventListener('message', onMsg._raw);

    // Load site homepage in hidden iframe with ?ssm_scan=1
    const $frame = $('#ssm-scan-frame');
    const scanUrl = cfg.siteUrl + (cfg.siteUrl.includes('?') ? '&' : '?') + 'ssm_scan=1&ssm_ts=' + Date.now();
    $frame.attr('src', scanUrl);

    animateProgress(30, 75, 4000);
    $('#ssm-progress-label').text('Scanning page DOM…');

    // Timeout fallback
    setTimeout(() => {
      window.removeEventListener('message', onMsg._raw);
      if (!state.scannedColors.length) {
        $('#ssm-progress-wrap').hide();
        toast('Scan timed out. Try visiting the site with the badge first.', 'error');
      }
      $('#ssm-scan-btn, #ssm-rescan-btn').prop('disabled', false);
    }, 15000);
  }

  function animateProgress(from, to, dur) {
    $('#ssm-progress-fill').stop(true).animate({ width: to + '%' }, dur);
  }

  function sendScannedToServer(colors, vars) {
    $.post(cfg.ajaxUrl, {
      action: 'ssm_store_page_colors',
      nonce:  cfg.nonce,
      colors: JSON.stringify(colors),
    });
    $.post(cfg.ajaxUrl, {
      action: 'ssm_store_css_vars',
      nonce:  cfg.nonce,
      vars:   JSON.stringify(vars),
    });
  }

  /* ── Render color grid ─────────────────────────────────────── */
  function renderColorGrid(colors) {
    const $grid = $('#ssm-color-grid').empty();
    $('#ssm-result-title').text(colors.length + ' Colors Found on Page');

    colors.forEach(function (c) {
      const hasMap = !!state.colorMaps[c.hex];
      const showHex = hasMap ? state.colorMaps[c.hex] : c.hex;

      const $sw = $('<div class="ssm-color-swatch">').append(
        $('<div class="ssm-swatch-inner">').css('background', showHex).toggleClass('selected', hasMap),
        $('<span class="ssm-swatch-hex">').text(c.hex),
        $('<span class="ssm-swatch-name">').text(c.name || ''),
        $('<button class="ssm-swatch-add" title="Add replacement">+</button>').on('click', function (e) {
          e.stopPropagation();
          addColorMap(c.hex, state.colorMaps[c.hex] || c.hex);
          $('html, body').animate({ scrollTop: $('#ssm-color-map-list').offset().top - 80 }, 300);
        })
      ).attr('data-hex', c.hex);
      $grid.append($sw);
    });

    // Search filter
    $('#ssm-color-search').off('input').on('input', function () {
      const q = $(this).val().toLowerCase();
      $('.ssm-color-swatch').each(function () {
        const hex  = $(this).data('hex') || '';
        const name = $(this).find('.ssm-swatch-name').text().toLowerCase();
        $(this).toggle(!q || hex.includes(q) || name.includes(q));
      });
    });
  }

  /* ── Render var list ───────────────────────────────────────── */
  function renderVarList(vars) {
    const keys = Object.keys(vars);
    if (!keys.length) { $('#ssm-vars-card').hide(); return; }
    $('#ssm-vars-card').show();
    $('#ssm-var-count').text(keys.length);
    const $list = $('#ssm-var-list').empty();
    keys.forEach(function (name) {
      const val = vars[name];
      $list.append(
        $('<div class="ssm-var-item" title="Click to add as override">').append(
          $('<span class="ssm-var-dot">').css('background', val),
          $('<span class="ssm-var-name">').text(name),
          $('<span class="ssm-var-val">').text(val)
        ).on('click', function () {
          addVarOverride(name, val);
          $('.ssm-tab[data-tab="palette"]').trigger('click');
        })
      );
    });
  }

  /* ══════════════════════════════════════════════════════════════
     COLOR MAPS
  ════════════════════════════════════════════════════════════ */
  function initColorMapUI() {
    $('#ssm-add-color-map').on('click', () => addColorMap('', ''));
  }

  function loadSavedColors() {
    if (!Object.keys(state.colorMaps).length) return;
    $('#ssm-colors-empty').hide();
    Object.entries(state.colorMaps).forEach(([old, nw]) => addColorMap(old, nw));
  }

  function addColorMap(oldHex, newHex) {
    oldHex = oldHex || '#000000';
    newHex = newHex || '#000000';
    $('#ssm-colors-empty').hide();
    $('#ssm-color-map-list').append(buildMapRow(oldHex, newHex));
  }

  function buildMapRow(oldHex, newHex) {
    return $('<div class="ssm-map-row">').append(
      buildColorInputCell(oldHex, 'old'),
      $('<span class="ssm-map-arrow">→</span>'),
      buildColorInputCell(newHex, 'new'),
      $('<button class="ssm-remove-btn" title="Remove">').html('<svg viewBox="0 0 20 20" fill="currentColor" width="14"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>').on('click', function () {
        const row = $(this).closest('.ssm-map-row');
        const old = row.find('[data-role="old"]').val();
        delete state.colorMaps[old];
        row.remove();
        if (!$('#ssm-color-map-list .ssm-map-row').length) $('#ssm-colors-empty').show();
      })
    );
  }

  function buildColorInputCell(hex, role) {
    const $dot  = $('<span class="ssm-color-dot">').css('background', hex);
    const $inp  = $('<input class="ssm-color-hex-input">').attr({ type: 'text', 'data-role': role, placeholder: '#000000', maxlength: 7 }).val(hex);
    $dot.on('click', function () {
      openPicker(hex, function (v) {
        $dot.css('background', v);
        $inp.val(v);
      });
    });
    $inp.on('input', function () {
      const v = $(this).val();
      if (/^#[0-9a-fA-F]{6}$/.test(v)) $dot.css('background', v);
    });
    return $('<div class="ssm-color-input-wrap">').append($dot, $inp);
  }

  /* ══════════════════════════════════════════════════════════════
     BREAKPOINT SWITCHER
  ════════════════════════════════════════════════════════════ */
  function initBreakpointSwitcher() {
    $(document).on('click', '.ssm-bp-btn', function () {
      const bp = $(this).data('bp');
      state.activeBp = bp;
      $('.ssm-bp-btn').removeClass('active');
      $(this).addClass('active');

      // Show/hide size inputs in table
      $('.ssm-bp-input').hide();
      $('.ssm-bp-input[data-bp="' + bp + '"]').show();

      // Column header label
      $('.ssm-bp-label').hide();
      $('.ssm-bp-label[data-bp="' + bp + '"]').show();

      updateTypoPreview();
    });

    // Preview breakpoint viewport
    $(document).on('click', '.ssm-prev-bp', function () {
      const pbp = $(this).data('pbp');
      $('.ssm-prev-bp').removeClass('active');
      $(this).addClass('active');
      $('#ssm-preview-viewport').attr('class', 'ssm-preview-viewport' + (pbp !== 'desktop' ? ' vp-' + pbp : ''));
    });

    // Preview theme toggle
    $(document).on('click', '.ssm-theme-btn', function () {
      $('.ssm-theme-btn').removeClass('active');
      $(this).addClass('active');
      $('#ssm-typo-preview').toggleClass('dark', $(this).data('theme') === 'dark');
    });
  }

  /* ══════════════════════════════════════════════════════════════
     TYPOGRAPHY TABLE
  ════════════════════════════════════════════════════════════ */
  $(document).on('input change', '.ssm-tf', function () {
    const key  = $(this).data('el');
    const prop = $(this).data('prop');
    const val  = $(this).val().trim();
    if (!key || !prop) return;

    if (!state.typoElements[key]) state.typoElements[key] = {};
    state.typoElements[key][prop] = val;

    // Load Google Font preview immediately
    if (prop === 'font' && val) loadGoogleFont(val);

    updateTypoPreview();
  });

  function updateTypoPreview() {
    const preview = document.getElementById('ssm-typo-preview');
    if (!preview) return;

    const bp    = $('.ssm-prev-bp.active').data('pbp') || 'desktop';
    const elems = state.typoElements;

    const get     = (key, prop) => (elems[key] && elems[key][prop]) || '';
    const normSz  = (s) => s && /^\d+(\.\d+)?$/.test(s.trim()) ? s.trim() + 'px' : s;

    const apply = (selector, key) => {
      const el = preview.querySelector(selector);
      if (!el) return;
      const font   = get(key, 'font');
      const rawSz  = get(key, bp === 'tablet' ? 'tablet' : bp === 'mobile' ? 'mobile' : 'desktop');
      const size   = normSz(rawSz);
      const weight = get(key, 'weight');
      const lh     = get(key, 'line_height');
      const ls     = get(key, 'letter_spacing');
      const color  = get(key, 'color');

      if (font)   el.style.fontFamily    = "'" + font + "', sans-serif";
      if (size)   el.style.fontSize      = size;
      if (weight) el.style.fontWeight    = weight;
      if (lh)     el.style.lineHeight    = lh;
      if (ls)     el.style.letterSpacing = ls;
      if (color)  el.style.color         = color;
    };

    apply('.prev-h1',         'h1');
    apply('.prev-h2',         'h2');
    apply('.prev-h3',         'h3');
    apply('.prev-h4',         'h4');
    apply('.prev-h5',         'h5');
    apply('.prev-h6',         'h6');
    apply('.prev-body',       'body');
    apply('.prev-link',       'link');
    apply('.prev-blockquote', 'blockquote');
    apply('.prev-code',       'code');
    apply('.prev-list',       'list');
    apply('.prev-btn-primary',   'button');
    apply('.prev-btn-secondary', 'button');
  }

  function loadGoogleFont(family) {
    if (!family || family.startsWith('-')) return;
    const id = 'ssm-gf-' + family.replace(/\s+/g, '').toLowerCase();
    if (document.getElementById(id)) return;
    const url = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(family) + ':wght@300;400;500;600;700&display=swap';
    $('<link>').attr({ id, rel: 'stylesheet', href: url }).appendTo('head');
  }

  /* ── Reset typography ──────────────────────────────────────── */
  $('#ssm-reset-typography').on('click', function () {
    if (!confirm('Reset all typography to browser defaults? Saved changes will be lost.')) return;
    state.typoElements = {};
    $('.ssm-tf').val('');
    updateTypoPreview();
    toast('Typography reset', 'info');
  });

  /* ══════════════════════════════════════════════════════════════
     PALETTE
  ════════════════════════════════════════════════════════════ */
  function initPaletteUI() {
    $('#ssm-add-palette-color').on('click', () => addPaletteItem('#6366f1', 'Primary'));

    // Pre-load saved palette
    (saved.palette || []).forEach(p => addPaletteItem(p.hex, p.name));

    // Pre-load saved var overrides
    loadSavedVarOverrides();
    $('#ssm-add-var-override').on('click', () => addVarOverride('', ''));
  }

  function addPaletteItem(hex, name) {
    const $item = $('<div class="ssm-palette-item">');
    const $color = $('<span class="ssm-palette-color">').css('background', hex).data('hex', hex).on('click', function () {
      openPicker($(this).data('hex') || hex, function (v) {
        $color.css('background', v).data('hex', v);
        $item.find('.ssm-palette-hex').text(v);
      });
    });
    const $del = $('<button class="ssm-palette-delete" title="Remove">✕</button>').on('click', () => $item.remove());
    $item.append(
      $color,
      $('<input class="ssm-palette-name-input" type="text" placeholder="Name…">').val(name || ''),
      $('<span class="ssm-palette-hex">').text(hex),
      $del
    );
    $('#ssm-palette-grid').append($item);
  }

  function loadSavedVarOverrides() {
    Object.entries(state.varOverrides).forEach(([k, v]) => addVarOverride(k, v));
  }

  function addVarOverride(varName, val) {
    const $row = $('<div class="ssm-var-override-row">').append(
      $('<input class="ssm-var-name-input" type="text" placeholder="--primary-color">').val(varName),
      buildColorInputCell(val || '#000000', 'var-val'),
      $('<button class="ssm-remove-btn">').html('✕').on('click', function () { $(this).closest('.ssm-var-override-row').remove(); })
    );
    $('#ssm-var-overrides').append($row);
  }

  /* ══════════════════════════════════════════════════════════════
     CUSTOM CSS
  ════════════════════════════════════════════════════════════ */
  function initCustomCss() {
    const $ed = $('#ssm-custom-css-editor');
    $ed.on('input', function () {
      const n = $(this).val().split('\n').length;
      $('#ssm-css-line-count').text(n + ' line' + (n !== 1 ? 's' : ''));
    }).trigger('input');

    $('#ssm-format-css').on('click', function () {
      let css = $ed.val();
      css = css.replace(/\{/g, ' {\n  ').replace(/;\s*/g, ';\n  ').replace(/\}/g, '\n}\n').replace(/\n\s*\n+/g, '\n\n').trim();
      $ed.val(css).trigger('input');
    });
    $('#ssm-clear-css').on('click', function () {
      if (confirm('Clear all custom CSS?')) $ed.val('').trigger('input');
    });
  }

  /* ══════════════════════════════════════════════════════════════
     COLLECT & SAVE
  ════════════════════════════════════════════════════════════ */
  function collectStyles() {
    // Colors
    const colors = {};
    $('#ssm-color-map-list .ssm-map-row').each(function () {
      const old = ($(this).find('[data-role="old"]').val() || '').trim();
      const nw  = ($(this).find('[data-role="new"]').val() || '').trim();
      if (/^#[0-9a-fA-F]{6}$/.test(old) && /^#[0-9a-fA-F]{6}$/.test(nw)) colors[old] = nw;
    });

    // CSS Vars
    const css_vars = {};
    $('#ssm-var-overrides .ssm-var-override-row').each(function () {
      const k = ($(this).find('.ssm-var-name-input').val() || '').trim();
      const v = ($(this).find('[data-role="var-val"]').val() || '').trim();
      if (k && v) css_vars[k] = v;
    });

    // Typography (elements only)
    const typography = { elements: state.typoElements };

    // Palette
    const palette = [];
    $('#ssm-palette-grid .ssm-palette-item').each(function () {
      const name = ($(this).find('.ssm-palette-name-input').val() || '').trim();
      const hex  = $(this).find('.ssm-palette-hex').text().trim();
      if (hex) palette.push({ name: name || 'Color', hex });
    });

    const custom_css = $('#ssm-custom-css-editor').val() || '';

    // Element colors — only fields with data-key (typography color fields use
    // data-typo-key and have no .ssm-cf-hex input, so they must be excluded here)
    const element_colors = {};
    $('.ssm-color-field[data-key]').each(function () {
      const key = $(this).data('key');
      const hex = ($(this).find('.ssm-cf-hex').val() || '').trim();
      if (key && /^#[0-9a-fA-F]{6}$/.test(hex)) element_colors[key] = hex;
    });

    return { colors, css_vars, typography, palette, custom_css, element_colors };
  }

  function saveStyles($btn) {
    const orig = $btn.html();
    $btn.prop('disabled', true).html('<span class="ssm-spinner"></span> Saving…');

    // One-time restore: ignores duplicate calls (safety timer + .always both call this)
    let restored = false;
    function restore() {
      if (restored) return;
      restored = true;
      $btn.prop('disabled', false).html(orig);
    }

    // Hard fallback: button always comes back even if jQuery AJAX hangs completely
    const guard = setTimeout(function () {
      restore();
      toast('Save is taking too long — check your server connection', 'error');
    }, 20000);

    // Collect + serialise BEFORE touching $.ajax so a throw restores the button cleanly
    var payload;
    try {
      payload = JSON.stringify(collectStyles());
    } catch (err) {
      clearTimeout(guard);
      restore();
      toast('Could not prepare save data: ' + err.message, 'error');
      return;
    }

    var req = $.ajax({
      url:      cfg.ajaxUrl,
      type:     'POST',
      dataType: 'json',
      timeout:  15000,
      data: {
        action: 'ssm_save_styles',
        nonce:  cfg.nonce,
        styles: payload,
      },
    });

    req.done(function (r) {
      try {
        if (r && r.success) {
          toast((r.data && r.data.message) ? r.data.message : 'Styles saved!');
        } else {
          var msg = (r && r.data && r.data.message) ? r.data.message : 'Server returned an error';
          toast(msg, 'error');
        }
      } catch (e) { /* never let a toast error re-block the button */ }
    });

    req.fail(function (xhr, status) {
      try {
        var msg = status === 'timeout'    ? 'Save timed out — try again' :
                  status === 'parseerror' ? 'Unexpected server response (check WP debug log)' :
                  xhr.status === 0        ? 'No connection to server' :
                  xhr.status === 403      ? 'Session expired — please refresh the page' :
                  'Save failed (HTTP ' + (xhr.status || status) + ')';
        toast(msg, 'error');
      } catch (e) { /* never let a toast error re-block the button */ }
    });

    req.always(function () {
      clearTimeout(guard);
      restore();
    });
  }

  function initSaveBtns() {
    $('#ssm-save-elem-colors').on('click', function () { saveStyles($(this)); });
    $('#ssm-save-colors').on('click',      function () { saveStyles($(this)); });
    $('#ssm-save-typography').on('click',  function () { saveStyles($(this)); });
    $('#ssm-save-palette').on('click',     function () { saveStyles($(this)); });
    $('#ssm-save-css').on('click',         function () { saveStyles($(this)); });
    $('#ssm-preview-colors').on('click', function () {
      $.post(cfg.ajaxUrl, { action: 'ssm_preview_css', nonce: cfg.nonce, styles: JSON.stringify(collectStyles()) })
        .done(function (r) {
          if (!r.success) return;
          let $el = $('#ssm-preview-inject');
          if (!$el.length) $el = $('<style id="ssm-preview-inject">').appendTo('head');
          // Append a high-specificity reset so the preview doesn't repaint the
          // plugin's own admin UI (the broad selectors in the generated CSS would
          // otherwise override inherited font/color on every div, span, h1, etc.).
          const adminReset = '\n#ssm-admin-app,#ssm-admin-app *{' +
            'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif!important}';
          $el.text(r.data.css + adminReset);
          toast('Live preview injected', 'info');
        });
    });
    $('#ssm-reset-all').on('click', function () {
      if (!confirm('Reset ALL saved styles? This cannot be undone.')) return;
      const $b = $(this).prop('disabled', true);
      $.post(cfg.ajaxUrl, { action: 'ssm_reset_styles', nonce: cfg.nonce })
        .done(() => { toast('All styles reset', 'info'); setTimeout(() => location.reload(), 1200); })
        .fail(() => toast('Reset failed', 'error'))
        .always(() => $b.prop('disabled', false));
    });
    $('#ssm-select-all-colors').on('click', function () {
      $('.ssm-color-swatch:visible').each(function () {
        const hex = $(this).data('hex');
        if (hex && !state.colorMaps[hex]) addColorMap(hex, hex);
      });
    });
  }

  /* ══════════════════════════════════════════════════════════════
     BACKUP / RESTORE
  ════════════════════════════════════════════════════════════ */
  function formatBackupTime(ts) {
    if (!ts) return '';
    const d = new Date(ts * 1000);
    return 'Backed up ' + d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
         + ' ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
  }

  // Show last backup time on load
  (function () {
    const backup = cfg.backup;
    if (backup && backup.timestamp) {
      $('#ssm-backup-ts').text(formatBackupTime(backup.timestamp));
    }
  })();

  $('#ssm-take-backup').on('click', function () {
    const $b = $(this).prop('disabled', true).text('Saving…');
    $.post(cfg.ajaxUrl, { action: 'ssm_take_backup', nonce: cfg.nonce })
      .done(function (r) {
        if (r.success) {
          toast('Backup saved!');
          $('#ssm-backup-ts').text(formatBackupTime(r.data.timestamp));
        } else {
          toast('Backup failed', 'error');
        }
      })
      .fail(() => toast('Network error', 'error'))
      .always(() => $b.prop('disabled', false).html(
        '<svg viewBox="0 0 20 20" fill="currentColor" width="14"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd"/></svg> Backup'
      ));
  });

  $('#ssm-restore-backup').on('click', function () {
    const backup = cfg.backup;
    const ts     = backup && backup.timestamp ? formatBackupTime(backup.timestamp) : 'No backup found';
    if (!backup || !backup.timestamp) {
      toast('No backup found. Click Backup first.', 'error');
      return;
    }
    if (!confirm('Restore styles from backup?\n\n' + ts + '\n\nCurrent unsaved changes will be lost.')) return;

    const $b = $(this).prop('disabled', true).html('<span class="ssm-spinner"></span> Restoring…');
    $.post(cfg.ajaxUrl, { action: 'ssm_restore_backup', nonce: cfg.nonce })
      .done(function (r) {
        if (r.success) {
          toast('Restored! Reloading…', 'info');
          setTimeout(() => location.reload(), 1200);
        } else {
          toast('Restore failed: ' + (r.data?.message || '?'), 'error');
        }
      })
      .fail(() => toast('Network error', 'error'))
      .always(() => $b.prop('disabled', false).html(
        '<svg viewBox="0 0 20 20" fill="currentColor" width="14"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg> Restore Backup'
      ));
  });

  /* ── Native color picker ───────────────────────────────────── */
  function openPicker(initial, cb) {
    const $p = $('<input type="color">').val(initial || '#000000').css({ position: 'absolute', opacity: 0, left: '-9999px' });
    $('body').append($p); $p[0].click();
    $p.on('input change', () => cb($p.val()));
    $p.on('blur', () => setTimeout(() => $p.remove(), 200));
  }

})(jQuery);
