<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SSM_Scanner {

    private $colors      = [];
    private $fonts       = [];
    private $css_vars    = [];
    private $seen_files  = [];

    /* ── Public API ─────────────────────────────────────────────── */

    public function scan( $force = false ) {
        if ( ! $force ) {
            $cached = [
                'colors' => get_transient( 'ssm_scanned_colors' ),
                'fonts'  => get_transient( 'ssm_scanned_fonts' ),
                'vars'   => get_transient( 'ssm_scanned_vars' ),
            ];
            if ( false !== $cached['colors'] ) return $cached;
        }

        $this->scan_theme();
        $this->scan_page_builders();
        $this->scan_post_content();
        $this->scan_registered_styles();

        $colors   = $this->compile_colors();
        $fonts    = $this->compile_fonts();
        $vars     = $this->css_vars;

        set_transient( 'ssm_scanned_colors', $colors, 12 * HOUR_IN_SECONDS );
        set_transient( 'ssm_scanned_fonts',  $fonts,  12 * HOUR_IN_SECONDS );
        set_transient( 'ssm_scanned_vars',   $vars,   12 * HOUR_IN_SECONDS );

        return compact( 'colors', 'fonts', 'vars' );
    }

    /* ── Scanners ───────────────────────────────────────────────── */

    private function scan_theme() {
        $theme = wp_get_theme();
        $base  = $theme->get_stylesheet_directory();

        foreach ( $this->find_css_files( $base ) as $file ) {
            $this->parse_file( $file );
        }

        // Parent theme
        $parent_base = $theme->get_template_directory();
        if ( $parent_base !== $base ) {
            foreach ( $this->find_css_files( $parent_base ) as $file ) {
                $this->parse_file( $file );
            }
        }
    }

    private function scan_page_builders() {
        // Elementor CSS
        $el_dir = WP_CONTENT_DIR . '/uploads/elementor/css/';
        if ( is_dir( $el_dir ) ) {
            foreach ( glob( $el_dir . '*.css' ) as $file ) {
                $this->parse_file( $file );
            }
        }

        // Elementor global CSS option
        $el_css = get_option( 'elementor_css' );
        if ( $el_css ) $this->parse_css( $el_css );

        // Divi
        $divi_css = get_option( 'et_divi_dynamic_css_option' );
        if ( $divi_css ) $this->parse_css( $divi_css );

        // Beaver Builder
        $bb_dir = WP_CONTENT_DIR . '/uploads/bb-plugin/cache/';
        if ( is_dir( $bb_dir ) ) {
            foreach ( glob( $bb_dir . '*.css' ) as $file ) {
                $this->parse_file( $file );
            }
        }
    }

    private function scan_post_content() {
        $posts = get_posts( [
            'post_type'   => [ 'post', 'page' ],
            'post_status' => 'publish',
            'numberposts' => 100,
        ] );

        foreach ( $posts as $post ) {
            // Inline style attrs
            if ( preg_match_all( '/style=["\']([^"\']+)["\']/', $post->post_content, $m ) ) {
                foreach ( $m[1] as $inline ) {
                    $this->extract_colors( $inline );
                    $this->extract_fonts( $inline );
                }
            }

            // Elementor per-page CSS
            $el_css = get_post_meta( $post->ID, '_elementor_css', true );
            if ( ! empty( $el_css['css'] ) ) {
                $this->parse_css( $el_css['css'] );
            }

            // Divi per-page CSS
            $divi_css = get_post_meta( $post->ID, '_et_pb_custom_css', true );
            if ( $divi_css ) $this->parse_css( $divi_css );
        }
    }

    private function scan_registered_styles() {
        global $wp_styles;
        if ( ! $wp_styles ) return;

        foreach ( $wp_styles->registered as $style ) {
            $src = $style->src ?? '';
            if ( empty( $src ) ) continue;

            // Google Fonts
            if ( strpos( $src, 'fonts.googleapis.com' ) !== false ) {
                $this->extract_google_fonts( $src );
                continue;
            }

            $path = $this->url_to_path( $src );
            if ( $path && file_exists( $path ) ) {
                $this->parse_file( $path );
            }
        }
    }

    /* ── Parsing ────────────────────────────────────────────────── */

    private function parse_file( $path ) {
        if ( in_array( $path, $this->seen_files, true ) ) return;
        $this->seen_files[] = $path;

        if ( ! file_exists( $path ) ) return;
        $size = filesize( $path );
        if ( $size > 500000 ) return; // skip files > 500 KB

        $css = file_get_contents( $path );
        if ( $css ) $this->parse_css( $css );
    }

    private function parse_css( $css ) {
        // Strip comments
        $css = preg_replace( '/\/\*.*?\*\//s', '', $css );

        $this->extract_colors( $css );
        $this->extract_fonts( $css );
        $this->extract_css_vars( $css );
    }

    private function extract_colors( $css ) {
        // Hex 6-digit
        preg_match_all( '/#([0-9a-fA-F]{6})\b/', $css, $m );
        foreach ( $m[0] as $c ) $this->colors[] = strtolower( $c );

        // Hex 3-digit → expand
        preg_match_all( '/#([0-9a-fA-F]{3})\b/', $css, $m );
        foreach ( $m[1] as $c ) {
            $this->colors[] = '#' . $c[0] . $c[0] . $c[1] . $c[1] . $c[2] . $c[2];
        }

        // rgb()
        preg_match_all( '/rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)/i', $css, $m );
        foreach ( $m[1] as $i => $r ) {
            $this->colors[] = $this->rgb_hex( $m[1][$i], $m[2][$i], $m[3][$i] );
        }

        // rgba()
        preg_match_all( '/rgba\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*[\d.]+\s*\)/i', $css, $m );
        foreach ( $m[1] as $i => $r ) {
            $this->colors[] = $this->rgb_hex( $m[1][$i], $m[2][$i], $m[3][$i] );
        }
    }

    private function extract_fonts( $css ) {
        preg_match_all( '/font-family\s*:\s*([^;{}!]+)/i', $css, $m );
        foreach ( $m[1] as $value ) {
            foreach ( explode( ',', $value ) as $part ) {
                $font = trim( trim( $part ), "\"' " );
                $generic = [ 'serif', 'sans-serif', 'monospace', 'cursive', 'fantasy',
                             'system-ui', 'inherit', 'initial', 'unset', 'none', 'var' ];
                if ( $font && strlen( $font ) < 60 && ! in_array( strtolower( $font ), $generic, true ) ) {
                    $this->fonts[] = $font;
                }
            }
        }
    }

    private function extract_css_vars( $css ) {
        // Custom property declarations: --name: value
        preg_match_all( '/(--[\w-]+)\s*:\s*([^;}{]+)/i', $css, $m );
        foreach ( $m[1] as $i => $name ) {
            $val = trim( $m[2][$i] );
            if ( $this->is_color_value( $val ) ) {
                $this->css_vars[ $name ] = $val;
            }
        }
    }

    private function extract_google_fonts( $url ) {
        if ( preg_match( '/family=([^&]+)/i', $url, $match ) ) {
            foreach ( explode( '|', urldecode( $match[1] ) ) as $family ) {
                $name = preg_replace( '/[:\d+,wght]+.*$/', '', $family );
                $name = str_replace( '+', ' ', trim( $name ) );
                if ( $name ) $this->fonts[] = $name;
            }
        }
    }

    /* ── Compilation ────────────────────────────────────────────── */

    private function compile_colors() {
        $counts = array_count_values( $this->colors );
        arsort( $counts );
        $result = [];
        foreach ( $counts as $hex => $count ) {
            if ( ! preg_match( '/^#[0-9a-f]{6}$/', $hex ) ) continue;
            $result[] = [
                'hex'   => $hex,
                'count' => $count,
                'name'  => $this->color_name( $hex ),
                'light' => $this->is_light( $hex ),
            ];
        }
        return array_slice( $result, 0, 60 );
    }

    private function compile_fonts() {
        $counts = array_count_values( $this->fonts );
        arsort( $counts );
        $result = [];
        foreach ( $counts as $family => $count ) {
            $result[] = [
                'family'    => $family,
                'count'     => $count,
                'is_google' => $this->maybe_google_font( $family ),
            ];
        }
        return array_values( $result );
    }

    /* ── Helpers ────────────────────────────────────────────────── */

    private function find_css_files( $dir ) {
        $files = [];
        if ( ! is_dir( $dir ) ) return $files;
        $main = $dir . '/style.css';
        if ( file_exists( $main ) ) $files[] = $main;
        foreach ( [ 'css', 'assets/css', 'dist', 'dist/css', 'build', 'public' ] as $sub ) {
            $path = $dir . '/' . $sub;
            if ( is_dir( $path ) ) {
                foreach ( glob( $path . '/*.css' ) ?: [] as $f ) $files[] = $f;
                foreach ( glob( $path . '/**/*.css' ) ?: [] as $f ) $files[] = $f;
            }
        }
        return array_unique( $files );
    }

    private function url_to_path( $url ) {
        $url  = strtok( $url, '?' );
        $base = get_site_url();
        if ( strpos( $url, $base ) === 0 ) {
            return ABSPATH . ltrim( str_replace( $base, '', $url ), '/' );
        }
        // Relative paths starting with /wp-content or /wp-includes
        if ( preg_match( '/^\/(wp-content|wp-includes)/', $url ) ) {
            return ABSPATH . ltrim( $url, '/' );
        }
        return false;
    }

    private function rgb_hex( $r, $g, $b ) {
        return sprintf( '#%02x%02x%02x', (int)$r, (int)$g, (int)$b );
    }

    private function is_color_value( $val ) {
        return preg_match( '/#[0-9a-fA-F]{3,6}|rgb[a]?\(|hsl[a]?\(/', $val );
    }

    private function is_light( $hex ) {
        $hex = ltrim( $hex, '#' );
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
        return ( $r * 299 + $g * 587 + $b * 114 ) / 1000 > 128;
    }

    private function color_name( $hex ) {
        $hex = ltrim( $hex, '#' );
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );
        $brightness = ( $r * 299 + $g * 587 + $b * 114 ) / 1000;

        if ( $brightness < 20 )  return 'Near Black';
        if ( $brightness > 240 ) return 'Near White';

        $max  = max( $r, $g, $b );
        $min  = min( $r, $g, $b );
        $diff = $max - $min;

        if ( $diff < 20 ) return 'Gray';

        $hue = 0;
        if ( $max === $r ) $hue = 60 * ( ( $g - $b ) / $diff );
        elseif ( $max === $g ) $hue = 60 * ( 2 + ( $b - $r ) / $diff );
        else $hue = 60 * ( 4 + ( $r - $g ) / $diff );
        if ( $hue < 0 ) $hue += 360;

        if ( $hue < 15 || $hue >= 345 ) return 'Red';
        if ( $hue < 45 )  return 'Orange';
        if ( $hue < 70 )  return 'Yellow';
        if ( $hue < 150 ) return 'Green';
        if ( $hue < 195 ) return 'Teal';
        if ( $hue < 255 ) return 'Blue';
        if ( $hue < 285 ) return 'Indigo';
        if ( $hue < 320 ) return 'Purple';
        return 'Pink';
    }

    private function maybe_google_font( $name ) {
        static $list = [
            'Roboto','Open Sans','Lato','Montserrat','Oswald','Raleway','PT Sans',
            'Merriweather','Ubuntu','Nunito','Playfair Display','Poppins',
            'Source Sans Pro','Lobster','Inconsolata','Inter','DM Sans',
            'Plus Jakarta Sans','Bebas Neue','Noto Sans','Work Sans','Mukta',
            'Heebo','Mulish','Barlow','Quicksand','Titillium Web','Dosis',
            'Karla','Libre Baskerville','Crimson Text','Josefin Sans','Cabin',
        ];
        return in_array( $name, $list, true );
    }
}
