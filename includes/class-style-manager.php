<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SSM_Style_Manager {

    private static $option_key  = 'ssm_saved_styles';
    private static $css_key     = 'ssm_output_css';
    private static $backup_key  = 'ssm_backup';

    /* ── Element definitions ────────────────────────────────────── */

    public static function get_element_definitions() {
        return [
            'body'       => [ 'label' => 'Body / Paragraph', 'icon' => 'P',  'hint' => 'body, p, span, li',
                              'defs'  => [ 'weight' => '400', 'line_height' => '1.6',  'desktop' => '16', 'tablet' => '15', 'mobile' => '14' ] ],
            'h1'         => [ 'label' => 'Heading 1',        'icon' => 'H1', 'hint' => 'h1',
                              'defs'  => [ 'weight' => '800', 'line_height' => '1.15', 'desktop' => '48', 'tablet' => '38', 'mobile' => '30' ] ],
            'h2'         => [ 'label' => 'Heading 2',        'icon' => 'H2', 'hint' => 'h2',
                              'defs'  => [ 'weight' => '700', 'line_height' => '1.2',  'desktop' => '36', 'tablet' => '28', 'mobile' => '24' ] ],
            'h3'         => [ 'label' => 'Heading 3',        'icon' => 'H3', 'hint' => 'h3',
                              'defs'  => [ 'weight' => '700', 'line_height' => '1.25', 'desktop' => '28', 'tablet' => '22', 'mobile' => '20' ] ],
            'h4'         => [ 'label' => 'Heading 4',        'icon' => 'H4', 'hint' => 'h4',
                              'defs'  => [ 'weight' => '600', 'line_height' => '1.3',  'desktop' => '22', 'tablet' => '18', 'mobile' => '17' ] ],
            'h5'         => [ 'label' => 'Heading 5',        'icon' => 'H5', 'hint' => 'h5',
                              'defs'  => [ 'weight' => '600', 'line_height' => '1.35', 'desktop' => '18', 'tablet' => '16', 'mobile' => '15' ] ],
            'h6'         => [ 'label' => 'Heading 6',        'icon' => 'H6', 'hint' => 'h6',
                              'defs'  => [ 'weight' => '600', 'line_height' => '1.4',  'desktop' => '16', 'tablet' => '14', 'mobile' => '13' ] ],
            'link'       => [ 'label' => 'Links',            'icon' => 'A',  'hint' => 'a, a:hover',
                              'defs'  => [ 'weight' => '' ] ],
            'blockquote' => [ 'label' => 'Blockquote',       'icon' => '"',  'hint' => 'blockquote',
                              'defs'  => [ 'weight' => '400', 'line_height' => '1.7',  'desktop' => '20', 'tablet' => '18', 'mobile' => '16' ] ],
            'code'       => [ 'label' => 'Code / Pre',       'icon' => '<>', 'hint' => 'code, pre',
                              'defs'  => [ 'weight' => '400', 'line_height' => '1.5',  'desktop' => '14', 'tablet' => '13', 'mobile' => '12' ] ],
            'button'     => [ 'label' => 'Button',           'icon' => 'B',  'hint' => 'button, .btn',
                              'defs'  => [ 'weight' => '500', 'line_height' => '1',    'desktop' => '15', 'tablet' => '14', 'mobile' => '14' ] ],
            'nav'        => [ 'label' => 'Navigation',       'icon' => '≡',  'hint' => 'nav a, .menu a',
                              'defs'  => [ 'weight' => '500', 'desktop' => '15', 'tablet' => '14', 'mobile' => '13' ] ],
            'caption'    => [ 'label' => 'Caption / Small',  'icon' => 'sm', 'hint' => 'small, figcaption',
                              'defs'  => [ 'weight' => '400', 'desktop' => '12', 'tablet' => '12', 'mobile' => '11' ] ],
            'list'       => [ 'label' => 'List Items',       'icon' => '•',  'hint' => 'ul, ol, li',
                              'defs'  => [] ],
            'label'      => [ 'label' => 'Label / Input',    'icon' => 'L',  'hint' => 'label, input, select',
                              'defs'  => [ 'weight' => '400', 'desktop' => '14', 'tablet' => '13', 'mobile' => '13' ] ],
        ];
    }

    public static function get_element_color_definitions() {
        return [
            'body'      => [ 'label' => 'Body Text',    'icon' => 'P',   'sel' => 'body' ],
            'primary'   => [ 'label' => 'Primary',      'icon' => '★',   'sel' => null,  'is_var' => true ],
            'secondary' => [ 'label' => 'Secondary',    'icon' => '◆',   'sel' => null,  'is_var' => true ],
            'h1'        => [ 'label' => 'Heading 1',    'icon' => 'H1',  'sel' => 'h1, .h1, .wp-block-heading h1' ],
            'h2'        => [ 'label' => 'Heading 2',    'icon' => 'H2',  'sel' => 'h2, .h2, .wp-block-heading h2' ],
            'h3'        => [ 'label' => 'Heading 3',    'icon' => 'H3',  'sel' => 'h3, .h3, .wp-block-heading h3' ],
            'h4'        => [ 'label' => 'Heading 4',    'icon' => 'H4',  'sel' => 'h4, .h4, .wp-block-heading h4' ],
            'h5'        => [ 'label' => 'Heading 5',    'icon' => 'H5',  'sel' => 'h5, .h5, .wp-block-heading h5' ],
            'h6'        => [ 'label' => 'Heading 6',    'icon' => 'H6',  'sel' => 'h6, .h6, .wp-block-heading h6' ],
            'p'         => [ 'label' => 'Paragraph',    'icon' => '¶',   'sel' => 'p' ],
            'a'         => [ 'label' => 'Links (a)',    'icon' => 'A',   'sel' => 'a' ],
            'i'         => [ 'label' => 'Italic (i)',   'icon' => 'I',   'sel' => 'i' ],
            'em'        => [ 'label' => 'Emphasis (em)','icon' => 'em',  'sel' => 'em' ],
        ];
    }

    private static function get_selectors() {
        return [
            'body'       => 'body, p, div, span, li, td, th, input, textarea, select',
            'h1'         => 'h1, .h1, .wp-block-heading h1, .elementor-heading-title.elementor-size-xxl',
            'h2'         => 'h2, .h2, .wp-block-heading h2, .elementor-heading-title.elementor-size-xl',
            'h3'         => 'h3, .h3, .wp-block-heading h3, .elementor-heading-title.elementor-size-large',
            'h4'         => 'h4, .h4, .wp-block-heading h4, .elementor-heading-title.elementor-size-medium',
            'h5'         => 'h5, .h5, .wp-block-heading h5',
            'h6'         => 'h6, .h6, .wp-block-heading h6',
            'link'       => 'a',
            'blockquote' => 'blockquote, blockquote p, .wp-block-quote, .wp-block-pullquote',
            'code'       => 'code, pre, pre code, .wp-block-code',
            'button'     => 'button, .btn, .button, input[type="submit"], input[type="button"], .wp-block-button__link, .elementor-button, .et_pb_button',
            'nav'        => 'nav a, nav li a, .menu-item > a, .navbar a, .elementor-nav-menu a, header nav a',
            'caption'    => 'small, figcaption, caption, .wp-caption-text, .wp-block-image figcaption',
            'list'       => 'ul, ol, li, .wp-block-list',
            'label'      => 'label, .form-label',
        ];
    }

    /* ── Read ───────────────────────────────────────────────────── */

    public static function get_saved_styles() {
        $defaults = [
            'colors'         => [],
            'css_vars'       => [],
            'typography'     => [ 'elements' => [] ],
            'palette'        => [],
            'element_colors' => [],
            'custom_css'     => '',
        ];
        $saved = get_option( self::$option_key, [] );
        return array_replace_recursive( $defaults, $saved );
    }

    public static function get_output_css() {
        return get_option( self::$css_key, '' );
    }

    public static function get_all_used_fonts() {
        $styles = self::get_saved_styles();
        $fonts  = [];
        foreach ( $styles['typography']['elements'] ?? [] as $el ) {
            if ( ! empty( $el['font'] ) ) $fonts[] = $el['font'];
        }
        return array_unique( $fonts );
    }

    /* ── Write ──────────────────────────────────────────────────── */

    public static function save_styles( $raw ) {
        $styles = self::sanitize( $raw );
        update_option( self::$option_key, $styles );
        $css = self::build_css( $styles );
        update_option( self::$css_key, $css );
        return [ 'styles' => $styles, 'css' => $css ];
    }

    public static function reset() {
        delete_option( self::$option_key );
        delete_option( self::$css_key );
        delete_transient( 'ssm_scanned_colors' );
        delete_transient( 'ssm_scanned_vars' );
    }

    /* ── Backup ─────────────────────────────────────────────────── */

    public static function take_backup() {
        $styles = get_option( self::$option_key, [] );
        $css    = get_option( self::$css_key, '' );
        update_option( self::$backup_key, [
            'styles'    => $styles,
            'css'       => $css,
            'timestamp' => time(),
        ] );
    }

    public static function get_backup() {
        return get_option( self::$backup_key, null );
    }

    public static function restore_backup() {
        $backup = get_option( self::$backup_key, null );
        if ( ! $backup || ! isset( $backup['styles'] ) ) return false;
        update_option( self::$option_key, $backup['styles'] );
        update_option( self::$css_key, $backup['css'] ?? '' );
        return true;
    }

    /* ── Sanitize ───────────────────────────────────────────────── */

    private static function sanitize( $raw ) {
        $out = self::get_saved_styles();

        // Color replacements
        if ( ! empty( $raw['colors'] ) && is_array( $raw['colors'] ) ) {
            $out['colors'] = [];
            foreach ( $raw['colors'] as $old => $new ) {
                $old = sanitize_hex_color( $old );
                $new = sanitize_hex_color( $new );
                if ( $old && $new ) $out['colors'][ $old ] = $new;
            }
        }

        // CSS variable overrides
        if ( ! empty( $raw['css_vars'] ) && is_array( $raw['css_vars'] ) ) {
            $out['css_vars'] = [];
            foreach ( $raw['css_vars'] as $var => $val ) {
                $var = sanitize_text_field( $var );
                $val = sanitize_text_field( $val );
                if ( preg_match( '/^--[\w-]+$/', $var ) && $val ) {
                    $out['css_vars'][ $var ] = $val;
                }
            }
        }

        // Per-element typography
        if ( ! empty( $raw['typography']['elements'] ) && is_array( $raw['typography']['elements'] ) ) {
            $out['typography']['elements'] = [];
            $props = [ 'font', 'weight', 'line_height', 'letter_spacing', 'desktop', 'tablet', 'mobile', 'color' ];
            $defs  = self::get_element_definitions();

            foreach ( $raw['typography']['elements'] as $key => $el ) {
                $key = sanitize_key( $key );
                if ( ! isset( $defs[ $key ] ) ) continue;
                $clean = [];
                foreach ( $props as $p ) {
                    if ( ! isset( $el[ $p ] ) ) continue;
                    if ( $p === 'color' ) {
                        $hex = sanitize_hex_color( $el[ $p ] );
                        if ( $hex ) $clean[ $p ] = $hex;
                    } else {
                        $clean[ $p ] = sanitize_text_field( $el[ $p ] );
                    }
                }
                if ( $clean ) $out['typography']['elements'][ $key ] = $clean;
            }
        }

        // Color palette
        if ( ! empty( $raw['palette'] ) && is_array( $raw['palette'] ) ) {
            $out['palette'] = [];
            foreach ( $raw['palette'] as $item ) {
                $name = sanitize_text_field( $item['name'] ?? '' );
                $hex  = sanitize_hex_color( $item['hex'] ?? '' );
                if ( $hex ) $out['palette'][] = compact( 'name', 'hex' );
            }
        }

        // Element colors
        if ( ! empty( $raw['element_colors'] ) && is_array( $raw['element_colors'] ) ) {
            $out['element_colors'] = [];
            $defs = self::get_element_color_definitions();
            foreach ( $raw['element_colors'] as $key => $hex ) {
                $key = sanitize_key( $key );
                if ( ! isset( $defs[ $key ] ) ) continue;
                $hex = sanitize_hex_color( $hex );
                if ( $hex ) $out['element_colors'][ $key ] = $hex;
            }
        }

        if ( isset( $raw['custom_css'] ) ) {
            $out['custom_css'] = strip_tags( $raw['custom_css'] );
        }

        return $out;
    }

    /* ── CSS Generation ─────────────────────────────────────────── */

    public static function build_css( $styles ) {
        $css = "/* Site Style Manager — generated CSS */\n\n";

        // ── CSS Variables ──────────────────────────────────────────
        $all_vars = [];
        foreach ( $styles['palette'] as $item ) {
            $slug = '--ssm-' . sanitize_title( $item['name'] );
            $all_vars[ $slug ] = $item['hex'];
        }
        foreach ( $styles['css_vars'] as $var => $val ) {
            $all_vars[ $var ] = $val;
        }

        // Color replacements — override any CSS custom properties whose value
        // matches a mapped "old" color, replacing it with the "new" color.
        if ( ! empty( $styles['colors'] ) ) {
            $scanned_vars = get_transient( 'ssm_scanned_vars' ) ?: [];
            foreach ( $styles['colors'] as $old_hex => $new_hex ) {
                foreach ( $scanned_vars as $var_name => $var_val ) {
                    $normalized = self::normalize_color_hex( $var_val );
                    if ( $normalized && strtolower( $normalized ) === strtolower( $old_hex ) ) {
                        $all_vars[ $var_name ] = $new_hex;
                    }
                }
            }
        }

        if ( $all_vars ) {
            $css .= ":root {\n";
            foreach ( $all_vars as $k => $v ) $css .= "  {$k}: {$v};\n";
            $css .= "}\n\n";
        }

        // ── Typography with breakpoints ────────────────────────────
        $elements  = $styles['typography']['elements'] ?? [];
        $selectors = self::get_selectors();

        $desktop_css = '';
        $tablet_css  = '';
        $mobile_css  = '';

        foreach ( $elements as $key => $el ) {
            if ( empty( $selectors[ $key ] ) ) continue;
            $sel = $selectors[ $key ];

            $base = [];
            if ( ! empty( $el['font'] ) )           $base[] = "  font-family: '{$el['font']}', sans-serif !important;";
            if ( ! empty( $el['weight'] ) )         $base[] = "  font-weight: {$el['weight']} !important;";
            if ( ! empty( $el['line_height'] ) )    $base[] = "  line-height: {$el['line_height']} !important;";
            if ( ! empty( $el['letter_spacing'] ) ) $base[] = "  letter-spacing: {$el['letter_spacing']} !important;";
            if ( ! empty( $el['color'] ) )          $base[] = "  color: {$el['color']} !important;";
            if ( ! empty( $el['desktop'] ) ) {
                $sz = trim( $el['desktop'] );
                if ( is_numeric( $sz ) ) $sz .= 'px';
                $base[] = "  font-size: {$sz} !important;";
            }

            if ( $base ) {
                $desktop_css .= "{$sel} {\n" . implode( "\n", $base ) . "\n}\n";
            }

            if ( ! empty( $el['tablet'] ) ) {
                $sz = trim( $el['tablet'] );
                if ( is_numeric( $sz ) ) $sz .= 'px';
                $tablet_css .= "  {$sel} { font-size: {$sz} !important; }\n";
            }
            if ( ! empty( $el['mobile'] ) ) {
                $sz = trim( $el['mobile'] );
                if ( is_numeric( $sz ) ) $sz .= 'px';
                $mobile_css .= "  {$sel} { font-size: {$sz} !important; }\n";
            }
        }

        $css .= $desktop_css;

        if ( $tablet_css ) {
            $css .= "\n/* Tablet — max-width: 1024px */\n@media (max-width: 1024px) {\n{$tablet_css}}\n";
        }
        if ( $mobile_css ) {
            $css .= "\n/* Mobile — max-width: 767px */\n@media (max-width: 767px) {\n{$mobile_css}}\n";
        }

        // ── Element Colors (site frontend only) ───────────────────
        if ( ! empty( $styles['element_colors'] ) ) {
            $ec_defs  = self::get_element_color_definitions();
            $var_block = '';
            $col_css   = '';

            foreach ( $styles['element_colors'] as $key => $hex ) {
                if ( empty( $ec_defs[ $key ] ) || ! $hex ) continue;
                $def = $ec_defs[ $key ];
                if ( ! empty( $def['is_var'] ) ) {
                    $var_block .= "  --ssm-{$key}: {$hex};\n";
                } else {
                    $col_css .= "{$def['sel']} { color: {$hex} !important; }\n";
                }
            }

            if ( $var_block ) {
                $css .= "\n/* Element color variables */\n:root {\n{$var_block}}\n";
            }
            if ( $col_css ) {
                $css .= "\n/* Element colors */\n{$col_css}";
            }
        }

        // ── Custom CSS ─────────────────────────────────────────────
        if ( ! empty( $styles['custom_css'] ) ) {
            $css .= "\n/* Custom CSS */\n" . $styles['custom_css'] . "\n";
        }

        return $css;
    }

    public static function build_preview_css( $raw ) {
        return self::build_css( self::sanitize( $raw ) );
    }

    private static function normalize_color_hex( $val ) {
        $val = trim( $val );
        if ( preg_match( '/^#([0-9a-fA-F]{6})$/', $val ) ) {
            return strtolower( $val );
        }
        if ( preg_match( '/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $val, $m ) ) {
            return '#' . sprintf( '%02x%02x%02x', (int) $m[1], (int) $m[2], (int) $m[3] );
        }
        return null;
    }
}
