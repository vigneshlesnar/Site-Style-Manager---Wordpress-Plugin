<?php
/**
 * Plugin Name: Site Style Manager
 * Plugin URI:  https://github.com/vigneshlesnar/site-style-manager
 * Description: Scan, customize and live-preview all colors & fonts across your entire WordPress site. Works with every page builder.
 * Version:     1.0.1
 * Author:      Vignesh
 * Author URI:  https://github.com/vigneshlesnar
 * License:     GPL v2 or later
 * Text Domain: site-style-manager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SSM_VERSION',    '1.0.1' );
define( 'SSM_DIR',        plugin_dir_path( __FILE__ ) );
define( 'SSM_URL',        plugin_dir_url( __FILE__ ) );
define( 'SSM_FILE',       __FILE__ );

require_once SSM_DIR . 'includes/class-scanner.php';
require_once SSM_DIR . 'includes/class-style-manager.php';
require_once SSM_DIR . 'includes/class-ajax-handler.php';

final class Site_Style_Manager {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( SSM_FILE,   [ $this, 'activate' ] );
        register_deactivation_hook( SSM_FILE, [ $this, 'deactivate' ] );

        add_action( 'admin_menu',            [ $this, 'admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
        add_action( 'wp_enqueue_scripts',    [ $this, 'frontend_assets' ] );
        add_action( 'wp_head',               [ $this, 'inject_fonts' ], 1 );
        add_action( 'wp_head',               [ $this, 'inject_custom_css' ], 999 );
        add_action( 'wp_footer',             [ $this, 'render_badge' ] );
        add_action( 'wp_footer',             [ $this, 'render_scan_script' ], 999 );

        new SSM_Ajax_Handler();
    }

    public function activate() {
        if ( ! get_option( 'ssm_settings' ) ) {
            update_option( 'ssm_settings', [ 'badge_position' => 'bottom-right' ] );
        }
    }

    public function deactivate() {
        delete_transient( 'ssm_scanned_colors' );
        delete_transient( 'ssm_scanned_fonts' );
    }

    /* ── Admin ─────────────────────────────────────────────────── */

    public function admin_menu() {
        add_menu_page(
            __( 'Site Style Manager', 'site-style-manager' ),
            __( 'Style Manager', 'site-style-manager' ),
            'manage_options',
            'site-style-manager',
            [ $this, 'admin_page' ],
            'dashicons-art',
            58
        );
    }

    public function admin_page() {
        include SSM_DIR . 'admin/admin-page.php';
    }

    public function admin_assets( $hook ) {
        if ( 'toplevel_page_site-style-manager' !== $hook ) return;

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style(
            'ssm-admin',
            SSM_URL . 'admin/css/admin-style.css',
            [],
            SSM_VERSION
        );

        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script(
            'ssm-admin',
            SSM_URL . 'admin/js/admin-script.js',
            [ 'jquery', 'wp-color-picker' ],
            SSM_VERSION,
            true
        );

        wp_localize_script( 'ssm-admin', 'ssmAdmin', [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'ssm_nonce' ),
            'siteUrl'   => get_site_url(),
            'pluginUrl' => SSM_URL,
            'saved'     => SSM_Style_Manager::get_saved_styles(),
            'backup'    => SSM_Style_Manager::get_backup(),
            'scanned'   => [
                'colors' => get_transient( 'ssm_scanned_colors' ) ?: [],
                'fonts'  => get_transient( 'ssm_scanned_fonts' )  ?: [],
                'vars'   => get_transient( 'ssm_scanned_vars' )   ?: [],
            ],
        ] );
    }

    /* ── Frontend ───────────────────────────────────────────────── */

    public function frontend_assets() {
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) return;

        wp_enqueue_style(
            'ssm-badge',
            SSM_URL . 'assets/css/frontend-badge.css',
            [],
            SSM_VERSION
        );
        wp_enqueue_script(
            'ssm-badge',
            SSM_URL . 'assets/js/frontend-badge.js',
            [ 'jquery' ],
            SSM_VERSION,
            true
        );
        wp_localize_script( 'ssm-badge', 'ssmFrontend', [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'ssm_nonce' ),
            'adminUrl'  => admin_url( 'admin.php?page=site-style-manager' ),
            'pluginUrl' => SSM_URL,
            'saved'     => SSM_Style_Manager::get_saved_styles(),
            'scanned'   => [
                'colors' => get_transient( 'ssm_scanned_colors' ) ?: [],
                'fonts'  => get_transient( 'ssm_scanned_fonts' )  ?: [],
                'vars'   => get_transient( 'ssm_scanned_vars' )   ?: [],
            ],
        ] );
    }

    public function inject_fonts() {
        $fonts = SSM_Style_Manager::get_all_used_fonts();
        if ( empty( $fonts ) ) return;

        $families = implode( '&family=', array_map( function ( $f ) {
            return urlencode( $f ) . ':ital,wght@0,300;0,400;0,500;0,600;0,700;1,400';
        }, $fonts ) );

        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link href="https://fonts.googleapis.com/css2?family=' . esc_attr( $families ) . '&display=swap" rel="stylesheet">' . "\n";
    }

    public function inject_custom_css() {
        $css = SSM_Style_Manager::get_output_css();
        if ( $css ) {
            echo '<style id="ssm-custom-styles">' . wp_strip_all_tags( $css ) . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    public function render_scan_script() {
        if ( empty( $_GET['ssm_scan'] ) ) return; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) return;
        ?>
        <script>
        (function () {
            function rgbToHex(rgb) {
                var m = rgb.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
                if (!m) return null;
                return '#' + [m[1], m[2], m[3]].map(function (v) {
                    return ('0' + (+v).toString(16)).slice(-2);
                }).join('');
            }
            function scanAndSend() {
                var colorMap = {}, vars = {};
                var props = ['color', 'backgroundColor', 'borderTopColor', 'outlineColor'];
                var skip = { 'rgba(0, 0, 0, 0)': 1, 'transparent': 1, '': 1 };
                var els = Array.prototype.slice.call(document.querySelectorAll('body *'), 0, 800);
                els.forEach(function (el) {
                    var cs = window.getComputedStyle(el);
                    props.forEach(function (p) {
                        var v = cs[p];
                        if (!v || skip[v]) return;
                        var hex = rgbToHex(v);
                        if (hex && hex !== '#000000' && hex !== '#ffffff') {
                            colorMap[hex] = (colorMap[hex] || 0) + 1;
                        }
                    });
                });
                try {
                    var rs = getComputedStyle(document.documentElement);
                    Array.prototype.forEach.call(Array.from ? Array.from(rs) : Object.keys(rs), function (p) {
                        if (typeof p === 'string' && p.indexOf('--') === 0) {
                            var v = rs.getPropertyValue(p).trim();
                            if (/^#|^rgb/.test(v)) vars[p] = v;
                        }
                    });
                } catch (e) {}
                var colorArr = [];
                for (var hex in colorMap) colorArr.push({ hex: hex, count: colorMap[hex] });
                colorArr.sort(function (a, b) { return b.count - a.count; });
                colorArr = colorArr.slice(0, 80);
                try {
                    window.parent.postMessage({ ssmScan: { colors: colorArr, vars: vars } }, '*');
                } catch (e) {}
            }
            if (document.readyState === 'complete') {
                scanAndSend();
            } else {
                window.addEventListener('load', scanAndSend);
            }
        })();
        </script>
        <?php
    }

    public function render_badge() {
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) return;
        include SSM_DIR . 'templates/badge.php';
    }
}

Site_Style_Manager::instance();
