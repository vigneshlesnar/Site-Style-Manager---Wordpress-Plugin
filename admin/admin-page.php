<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'ssm_font_options' ) ) {
    function ssm_font_options( $selected = '' ) {
        $groups = [
            'Sans-Serif' => [ 'Inter','Roboto','Open Sans','Lato','Montserrat','Poppins','Nunito',
                              'DM Sans','Plus Jakarta Sans','Work Sans','Mulish','Barlow','Raleway',
                              'Josefin Sans','Quicksand','Ubuntu','Heebo','Cabin','Karla' ],
            'Serif'      => [ 'Merriweather','Playfair Display','Libre Baskerville','Crimson Text',
                              'EB Garamond','Cormorant Garamond','Lora','PT Serif' ],
            'Display'    => [ 'Oswald','Bebas Neue','Titillium Web','Dosis','Comfortaa' ],
            'Monospace'  => [ 'Fira Code','Source Code Pro','Inconsolata','JetBrains Mono','Space Mono' ],
        ];
        $out = '';
        foreach ( $groups as $group => $list ) {
            $out .= '<optgroup label="' . esc_attr( $group ) . '">';
            foreach ( $list as $f ) {
                $sel = selected( $selected, $f, false );
                $out .= '<option value="' . esc_attr( $f ) . '" ' . $sel . '>' . esc_html( $f ) . '</option>';
            }
            $out .= '</optgroup>';
        }
        return $out;
    }
}

$saved_styles    = SSM_Style_Manager::get_saved_styles();
$saved_typo      = $saved_styles['typography']['elements'] ?? [];
$elem_defs       = SSM_Style_Manager::get_element_definitions();
$ec_defs         = SSM_Style_Manager::get_element_color_definitions();
$saved_ec        = $saved_styles['element_colors'] ?? [];
?>
<div class="ssm-wrap" id="ssm-admin-app">

  <!-- HEADER -->
  <div class="ssm-header">
    <div class="ssm-header-left">
      <div class="ssm-logo">
        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="url(#g1)"/><path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9c.83 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.01-.23-.26-.38-.61-.38-.99 0-.83.67-1.5 1.5-1.5H16c2.76 0 5-2.24 5-5 0-4.42-4.03-8-9-8zm-5.5 9c-.83 0-1.5-.67-1.5-1.5S5.67 9 6.5 9 8 9.67 8 10.5 7.33 12 6.5 12zm3-4C8.67 8 8 7.33 8 6.5S8.67 5 9.5 5s1.5.67 1.5 1.5S10.33 8 9.5 8zm5 0c-.83 0-1.5-.67-1.5-1.5S13.67 5 14.5 5s1.5.67 1.5 1.5S15.33 8 14.5 8zm3 4c-.83 0-1.5-.67-1.5-1.5S16.67 9 17.5 9s1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" fill="#fff"/>
        <defs><linearGradient id="g1" x1="2" y1="2" x2="22" y2="22"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs></svg>
      </div>
      <div>
        <h1 class="ssm-title">Site Style Manager</h1>
        <p class="ssm-subtitle">Live-scan webpage colors &amp; control every text element across all breakpoints</p>
      </div>
    </div>
    <div class="ssm-header-right">
      <span class="ssm-badge-version">v1.0.1</span>
      <div class="ssm-backup-wrap">
        <button id="ssm-take-backup" class="ssm-btn ssm-btn-outline">
          <svg viewBox="0 0 20 20" fill="currentColor" width="14"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-2a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd"/><path d="M10 6v2M10 12v2M6 10H4M16 10h-2"/></svg>
          Backup
        </button>
        <span class="ssm-backup-ts" id="ssm-backup-ts"></span>
      </div>
      <a href="<?php echo esc_url( get_site_url() ); ?>" target="_blank" class="ssm-btn ssm-btn-outline">
        <svg viewBox="0 0 20 20" fill="currentColor" width="14"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/></svg>
        View Site
      </a>
    </div>
  </div>

  <!-- TABS -->
  <div class="ssm-tabs">
    <button class="ssm-tab active" data-tab="scanner">
      <svg viewBox="0 0 20 20" fill="currentColor" width="15"><path d="M9 9a2 2 0 114 0 2 2 0 01-4 0z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a4 4 0 11-1.447 7.724A2 2 0 0010 13H9v2H7v-2H5a1 1 0 110-2h.172A4 4 0 0111 5z" clip-rule="evenodd"/></svg>
      Colors
    </button>
    <button class="ssm-tab" data-tab="typography">
      <svg viewBox="0 0 20 20" fill="currentColor" width="15"><path d="M4 3a1 1 0 000 2h4v10a1 1 0 102 0V5h4a1 1 0 100-2H4z"/></svg>
      Typography
    </button>
    <button class="ssm-tab" data-tab="palette">
      <svg viewBox="0 0 20 20" fill="currentColor" width="15"><path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2 2 0 000-2.828L13.485 5.1a2 2 0 00-2.828 0L10 5.757v8.486z" clip-rule="evenodd"/></svg>
      Palette &amp; Variables
    </button>
    <button class="ssm-tab" data-tab="custom-css">
      <svg viewBox="0 0 20 20" fill="currentColor" width="15"><path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
      Custom CSS
    </button>
  </div>

  <div class="ssm-content">

    <!-- ══════════════════════════════════════════════════════════
         COLORS TAB  (JS DOM scanner — reads actual rendered page)
    ═══════════════════════════════════════════════════════════ -->
    <div class="ssm-panel active" id="tab-scanner">

      <!-- Element Colors Card -->
      <div class="ssm-card ssm-elem-colors-card">
        <div class="ssm-card-header">
          <div>
            <h2>Element Colors</h2>
            <p>Set text colors for each element — applied to your site's frontend only, not the admin area.</p>
          </div>
          <button class="ssm-btn ssm-btn-primary" id="ssm-save-elem-colors">Save Colors</button>
        </div>

        <div class="ssm-ec-grid" id="ssm-ec-grid">
          <?php foreach ( $ec_defs as $key => $def ) :
              $hex = $saved_ec[ $key ] ?? '';
          ?>
          <div class="ssm-ec-row">
            <div class="ssm-ec-label">
              <span class="ssm-elem-icon"><?php echo esc_html( $def['icon'] ); ?></span>
              <span class="ssm-ec-name"><?php echo esc_html( $def['label'] ); ?></span>
            </div>
            <div class="ssm-color-field" data-key="<?php echo esc_attr( $key ); ?>">
              <div class="ssm-cf-swatch" style="<?php echo $hex ? 'background:' . esc_attr( $hex ) : ''; ?>"></div>
              <input class="ssm-cf-hex" type="text" value="<?php echo esc_attr( $hex ); ?>" maxlength="7" placeholder="—" autocomplete="off" spellcheck="false">
              <button class="ssm-cf-open" type="button" title="Pick color">
                <svg viewBox="0 0 16 16" fill="currentColor" width="11"><path d="M12.3 2.3a1 1 0 00-1.4 0L2.6 10.6l-.5 3 3-.5 8.2-8.2a1 1 0 000-1.4l-1-1z"/><path d="M1 15h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
              </button>
              <button class="ssm-cf-clear" type="button" title="Clear">
                <svg viewBox="0 0 16 16" fill="currentColor" width="10"><path d="M4.293 4.293a1 1 0 011.414 0L8 6.586l2.293-2.293a1 1 0 111.414 1.414L9.414 8l2.293 2.293a1 1 0 01-1.414 1.414L8 9.414l-2.293 2.293a1 1 0 01-1.414-1.414L6.586 8 4.293 5.707a1 1 0 010-1.414z"/></svg>
              </button>
              <div class="ssm-cf-popup">
                <div class="ssm-cf-presets"></div>
                <div class="ssm-cf-custom-row">
                  <input type="color" class="ssm-cf-native" value="<?php echo esc_attr( $hex ?: '#6366f1' ); ?>">
                  <input type="text" class="ssm-cf-hex-in" maxlength="7" placeholder="#000000" value="<?php echo esc_attr( $hex ); ?>" spellcheck="false">
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Scan card -->
      <div class="ssm-card ssm-scanner-card">
        <div class="ssm-card-header">
          <div>
            <h2>Webpage Color Scanner</h2>
            <p>Reads computed styles from your site's actual rendered DOM — only colors visible on the page are listed.</p>
          </div>
          <div class="ssm-scan-meta" id="ssm-scan-meta"></div>
        </div>

        <div class="ssm-scan-actions">
          <button id="ssm-scan-btn" class="ssm-btn ssm-btn-primary ssm-btn-lg">
            <svg viewBox="0 0 20 20" fill="currentColor" width="17"><path d="M9 9a2 2 0 114 0 2 2 0 01-4 0z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a4 4 0 11-1.447 7.724A2 2 0 0010 13H9v2H7v-2H5a1 1 0 110-2h.172A4 4 0 0111 5z" clip-rule="evenodd"/></svg>
            Scan Site Colors
          </button>
          <button id="ssm-rescan-btn" class="ssm-btn ssm-btn-secondary">
            <svg viewBox="0 0 20 20" fill="currentColor" width="15"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>
            Force Rescan
          </button>
        </div>

        <div class="ssm-scan-how">
          <svg viewBox="0 0 20 20" fill="currentColor" width="14"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
          Loads your homepage in a hidden frame and reads every element's computed <code>color</code>, <code>background-color</code>, and <code>border-color</code>.
        </div>

        <div class="ssm-progress-wrap" id="ssm-progress-wrap" style="display:none">
          <div class="ssm-progress-bar"><div class="ssm-progress-fill" id="ssm-progress-fill"></div></div>
          <span class="ssm-progress-label" id="ssm-progress-label">Loading page…</span>
        </div>
      </div>

      <!-- Results -->
      <div id="ssm-scan-results" style="display:none">
        <div class="ssm-results-header">
          <h3 id="ssm-result-title">Colors on your site</h3>
          <div class="ssm-result-actions">
            <input type="text" id="ssm-color-search" class="ssm-search-input" placeholder="Search hex or name…">
            <button class="ssm-btn ssm-btn-secondary ssm-btn-sm" id="ssm-select-all-colors">Select All</button>
          </div>
        </div>

        <!-- Color swatch grid -->
        <div class="ssm-color-grid-wrap ssm-card">
          <div class="ssm-color-grid" id="ssm-color-grid"></div>
        </div>

        <!-- CSS Variables from page -->
        <div class="ssm-card ssm-mt-1" id="ssm-vars-card" style="display:none">
          <div class="ssm-card-header-sm">
            <h3>CSS Variables (Custom Properties)</h3>
            <span class="ssm-count-badge" id="ssm-var-count">0</span>
          </div>
          <div class="ssm-var-list" id="ssm-var-list"></div>
        </div>

        <!-- Color replacement builder -->
        <div class="ssm-card ssm-mt-1" id="ssm-replacements-card">
          <div class="ssm-card-header">
            <div>
              <h2>Color Replacements</h2>
              <p>Map old colors → new colors. Click a swatch above to add a mapping quickly.</p>
            </div>
            <button class="ssm-btn ssm-btn-primary" id="ssm-add-color-map">+ Add Row</button>
          </div>
          <div class="ssm-color-map-list" id="ssm-color-map-list"></div>
          <div class="ssm-empty-state" id="ssm-colors-empty">
            <svg viewBox="0 0 80 80" fill="none" width="72"><circle cx="40" cy="40" r="36" fill="#f1f5f9"/><path d="M25 40c0-8.3 6.7-15 15-15s15 6.7 15 15-6.7 15-15 15" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round"/><circle cx="40" cy="40" r="5" fill="#6366f1"/></svg>
            <p>Click a swatch above or press <strong>+ Add Row</strong>.</p>
          </div>
        </div>

        <div class="ssm-save-bar">
          <button class="ssm-btn ssm-btn-primary ssm-btn-lg" id="ssm-save-colors">Save Color Changes</button>
          <button class="ssm-btn ssm-btn-ghost" id="ssm-preview-colors">Live Preview</button>
        </div>
      </div>

    </div><!-- /tab-scanner -->


    <!-- ══════════════════════════════════════════════════════════
         TYPOGRAPHY TAB  (all text elements + 3 breakpoints)
    ═══════════════════════════════════════════════════════════ -->
    <div class="ssm-panel" id="tab-typography">

      <div class="ssm-typo-header-bar ssm-card">
        <div class="ssm-typo-header-left">
          <h2>Typography Settings</h2>
          <p>Set font, size, weight, and line-height per element. Sizes apply per breakpoint.</p>
        </div>
        <div class="ssm-bp-switcher" id="ssm-bp-switcher">
          <button class="ssm-bp-btn active" data-bp="desktop">
            <svg viewBox="0 0 20 20" fill="currentColor" width="14"><path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/></svg>
            Desktop
          </button>
          <button class="ssm-bp-btn" data-bp="tablet">
            <svg viewBox="0 0 20 20" fill="currentColor" width="13"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm4 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            Tablet
            <span class="ssm-bp-hint">≤ 1024px</span>
          </button>
          <button class="ssm-bp-btn" data-bp="mobile">
            <svg viewBox="0 0 20 20" fill="currentColor" width="11"><path fill-rule="evenodd" d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            Mobile
            <span class="ssm-bp-hint">≤ 767px</span>
          </button>
        </div>
      </div>

      <div class="ssm-card ssm-typo-card-wrap">
        <div class="ssm-typo-table-wrap">
          <table class="ssm-typo-table" id="ssm-typo-table">
            <thead>
              <tr>
                <th class="col-element">Element</th>
                <th class="col-font">Font Family</th>
                <th class="col-size">
                  <span class="ssm-bp-label" data-bp="desktop">🖥 Desktop Size</span>
                  <span class="ssm-bp-label" data-bp="tablet" style="display:none">📱 Tablet Size</span>
                  <span class="ssm-bp-label" data-bp="mobile" style="display:none">📱 Mobile Size</span>
                </th>
                <th class="col-weight">Weight</th>
                <th class="col-lh">Line-H</th>
                <th class="col-ls">Letter-S</th>
                <th class="col-color">Color</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ( $elem_defs as $key => $def ) :
                $el = $saved_typo[ $key ] ?? [];
                $d  = $def['defs'];
            ?>
              <tr class="ssm-typo-row" data-element="<?php echo esc_attr( $key ); ?>">

                <td class="col-element">
                  <span class="ssm-elem-icon"><?php echo esc_html( $def['icon'] ); ?></span>
                  <div class="ssm-elem-info">
                    <strong><?php echo esc_html( $def['label'] ); ?></strong>
                    <small><?php echo esc_html( $def['hint'] ); ?></small>
                  </div>
                </td>

                <td class="col-font">
                  <select class="ssm-tf ssm-tf-font" data-el="<?php echo esc_attr( $key ); ?>" data-prop="font">
                    <option value="">— Inherit —</option>
                    <?php echo ssm_font_options( $el['font'] ?? '' ); ?>
                  </select>
                </td>

                <td class="col-size">
                  <?php foreach ( [ 'desktop', 'tablet', 'mobile' ] as $bp ) : ?>
                  <input type="text"
                         class="ssm-tf ssm-tf-size ssm-bp-input"
                         data-el="<?php echo esc_attr( $key ); ?>"
                         data-prop="<?php echo $bp; ?>"
                         data-bp="<?php echo $bp; ?>"
                         value="<?php echo esc_attr( $el[ $bp ] ?? '' ); ?>"
                         placeholder="<?php echo esc_attr( $d[ $bp ] ?? '—' ); ?>"
                         <?php echo $bp !== 'desktop' ? 'style="display:none"' : ''; ?>>
                  <?php endforeach; ?>
                </td>

                <td class="col-weight">
                  <select class="ssm-tf" data-el="<?php echo esc_attr( $key ); ?>" data-prop="weight">
                    <option value="">—</option>
                    <?php foreach ( [ '100'=>'Thin','200'=>'ExtraLight','300'=>'Light','400'=>'Regular','500'=>'Medium','600'=>'SemiBold','700'=>'Bold','800'=>'ExtraBold','900'=>'Black' ] as $w => $wl ) :
                        $sel = selected( $el['weight'] ?? ( $d['weight'] ?? '' ), $w, false );
                    ?>
                    <option value="<?php echo $w; ?>" <?php echo $sel; ?>><?php echo $w; ?> — <?php echo $wl; ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>

                <td class="col-lh">
                  <input type="text" class="ssm-tf" data-el="<?php echo esc_attr( $key ); ?>" data-prop="line_height"
                         value="<?php echo esc_attr( $el['line_height'] ?? '' ); ?>"
                         placeholder="<?php echo esc_attr( $d['line_height'] ?? '—' ); ?>">
                </td>

                <td class="col-ls">
                  <input type="text" class="ssm-tf" data-el="<?php echo esc_attr( $key ); ?>" data-prop="letter_spacing"
                         value="<?php echo esc_attr( $el['letter_spacing'] ?? '' ); ?>"
                         placeholder="0">
                </td>

                <td class="col-color">
                  <?php $ec = $el['color'] ?? ''; ?>
                  <div class="ssm-color-field ssm-tf-color" data-typo-key="<?php echo esc_attr( $key ); ?>">
                    <div class="ssm-cf-swatch" style="<?php echo $ec ? 'background:' . esc_attr( $ec ) : ''; ?>"></div>
                    <button class="ssm-cf-open" type="button" title="Pick color">
                      <svg viewBox="0 0 16 16" fill="currentColor" width="10"><path d="M12.3 2.3a1 1 0 00-1.4 0L2.6 10.6l-.5 3 3-.5 8.2-8.2a1 1 0 000-1.4l-1-1z"/></svg>
                    </button>
                    <button class="ssm-cf-clear" type="button" title="Clear color">
                      <svg viewBox="0 0 16 16" fill="currentColor" width="9"><path d="M4.293 4.293a1 1 0 011.414 0L8 6.586l2.293-2.293a1 1 0 111.414 1.414L9.414 8l2.293 2.293a1 1 0 01-1.414 1.414L8 9.414l-2.293 2.293a1 1 0 01-1.414-1.414L6.586 8 4.293 5.707a1 1 0 010-1.414z"/></svg>
                    </button>
                    <div class="ssm-cf-popup">
                      <div class="ssm-cf-presets"></div>
                      <div class="ssm-cf-custom-row">
                        <input type="color" class="ssm-cf-native" value="<?php echo esc_attr( $ec ?: '#333333' ); ?>">
                        <input type="text"  class="ssm-cf-hex-in" maxlength="7" placeholder="#000000" value="<?php echo esc_attr( $ec ); ?>">
                      </div>
                    </div>
                  </div>
                </td>

              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div><!-- .ssm-typo-table-wrap -->
      </div><!-- .ssm-card -->

      <!-- Live preview -->
      <div class="ssm-card ssm-typo-preview-card">
        <div class="ssm-preview-bar">
          <h3>Live Preview</h3>
          <div class="ssm-preview-bp-toggle">
            <button class="ssm-prev-bp active" data-pbp="desktop">Desktop</button>
            <button class="ssm-prev-bp" data-pbp="tablet">Tablet</button>
            <button class="ssm-prev-bp" data-pbp="mobile">Mobile</button>
          </div>
          <div class="ssm-preview-theme-toggle">
            <button class="ssm-theme-btn active" data-theme="light">Light</button>
            <button class="ssm-theme-btn" data-theme="dark">Dark</button>
          </div>
        </div>
        <div class="ssm-preview-viewport" id="ssm-preview-viewport">
          <div class="ssm-typo-preview" id="ssm-typo-preview">
            <h1 class="prev-h1">The Quick Brown Fox Jumps</h1>
            <h2 class="prev-h2">Over the Lazy Dog — Heading 2</h2>
            <h3 class="prev-h3">Typography Specimen H3</h3>
            <h4 class="prev-h4">Subheading Example H4</h4>
            <h5 class="prev-h5">Small heading H5</h5>
            <h6 class="prev-h6">Caption heading H6</h6>
            <p class="prev-body">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>
            <p><a class="prev-link" href="#">This is a link text</a> &nbsp; <small class="prev-small">Small caption text</small></p>
            <blockquote class="prev-blockquote">"Design is not just what it looks like and feels like. Design is how it works." — Steve Jobs</blockquote>
            <p><code class="prev-code">const greeting = "Hello World";</code></p>
            <ul class="prev-list"><li>List item one</li><li>List item two</li><li>List item three</li></ul>
            <div class="prev-buttons">
              <button class="prev-btn-primary">Primary Button</button>
              <button class="prev-btn-secondary">Secondary</button>
            </div>
          </div>
        </div>
      </div>

      <div class="ssm-save-bar">
        <button class="ssm-btn ssm-btn-primary ssm-btn-lg" id="ssm-save-typography">Save Typography</button>
        <button class="ssm-btn ssm-btn-ghost" id="ssm-reset-typography">Reset Defaults</button>
      </div>
    </div><!-- /tab-typography -->


    <!-- ══════════════════════════════════════════════════════════
         PALETTE & VARIABLES
    ═══════════════════════════════════════════════════════════ -->
    <div class="ssm-panel" id="tab-palette">
      <div class="ssm-card">
        <div class="ssm-card-header">
          <div>
            <h2>Global Color Palette</h2>
            <p>Named CSS variables available everywhere as <code>var(--ssm-primary)</code> etc.</p>
          </div>
          <button class="ssm-btn ssm-btn-primary" id="ssm-add-palette-color">+ Add Color</button>
        </div>
        <div class="ssm-palette-grid" id="ssm-palette-grid"></div>
      </div>
      <div class="ssm-card ssm-mt-1">
        <div class="ssm-card-header-sm"><h3>CSS Variable Overrides</h3></div>
        <p class="ssm-hint">Override any <code>--css-variable</code> used by your theme or builder.</p>
        <div class="ssm-var-overrides" id="ssm-var-overrides"></div>
        <button class="ssm-btn ssm-btn-secondary ssm-mt-2" id="ssm-add-var-override">+ Add Override</button>
      </div>
      <div class="ssm-save-bar">
        <button class="ssm-btn ssm-btn-primary ssm-btn-lg" id="ssm-save-palette">Save Palette</button>
      </div>
    </div>


    <!-- ══════════════════════════════════════════════════════════
         CUSTOM CSS
    ═══════════════════════════════════════════════════════════ -->
    <div class="ssm-panel" id="tab-custom-css">
      <div class="ssm-card">
        <div class="ssm-card-header">
          <div><h2>Custom CSS</h2><p>Injected after all styles at priority 999 with full <code>!important</code> access.</p></div>
        </div>
        <div class="ssm-css-editor-wrap">
          <div class="ssm-editor-toolbar">
            <button class="ssm-editor-btn" id="ssm-format-css">Format</button>
            <button class="ssm-editor-btn" id="ssm-clear-css">Clear</button>
            <span class="ssm-editor-info" id="ssm-css-line-count">0 lines</span>
          </div>
          <textarea id="ssm-custom-css-editor" class="ssm-css-editor" spellcheck="false"
            placeholder="/* Use palette variables: var(--ssm-primary), var(--ssm-secondary) */
body { }
@media (max-width: 767px) { }"><?php echo esc_textarea( $saved_styles['custom_css'] ?? '' ); ?></textarea>
        </div>
      </div>
      <div class="ssm-save-bar">
        <button class="ssm-btn ssm-btn-primary ssm-btn-lg" id="ssm-save-css">Save CSS</button>
        <button class="ssm-btn ssm-btn-restore" id="ssm-restore-backup">
          <svg viewBox="0 0 20 20" fill="currentColor" width="14"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>
          Restore Backup
        </button>
        <button class="ssm-btn ssm-btn-danger" id="ssm-reset-all">
          <svg viewBox="0 0 20 20" fill="currentColor" width="14"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
          Reset All Styles
        </button>
      </div>
    </div>

  </div><!-- .ssm-content -->

  <div class="ssm-toast-container" id="ssm-toasts"></div>

  <!-- Hidden scan iframe -->
  <iframe id="ssm-scan-frame" style="position:absolute;left:-9999px;top:0;width:1280px;height:900px;border:none;visibility:hidden;" aria-hidden="true"></iframe>

</div><!-- .ssm-wrap -->
