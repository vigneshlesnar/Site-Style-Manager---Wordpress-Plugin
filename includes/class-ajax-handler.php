<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SSM_Ajax_Handler {

    public function __construct() {
        $actions = [ 'save_styles', 'reset_styles', 'get_styles', 'preview_css',
                     'store_page_colors', 'store_css_vars',
                     'take_backup', 'restore_backup' ];
        foreach ( $actions as $a ) {
            add_action( "wp_ajax_ssm_{$a}", [ $this, $a ] );
        }
    }

    private function verify() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore
        if ( ! wp_verify_nonce( $nonce, 'ssm_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        }
    }

    public function save_styles() {
        $this->verify();
        $raw = [];
        if ( ! empty( $_POST['styles'] ) ) {
            $raw = is_string( $_POST['styles'] )
                ? json_decode( stripslashes( $_POST['styles'] ), true ) ?: []
                : (array) $_POST['styles'];
        }
        $result = SSM_Style_Manager::save_styles( $raw );
        wp_send_json_success( [ 'message' => 'Styles saved!', 'css' => $result['css'] ] );
    }

    public function reset_styles() {
        $this->verify();
        SSM_Style_Manager::reset();
        wp_send_json_success( [ 'message' => 'Reset complete.' ] );
    }

    public function get_styles() {
        $this->verify();
        wp_send_json_success( [
            'saved'   => SSM_Style_Manager::get_saved_styles(),
            'colors'  => get_transient( 'ssm_scanned_colors' ) ?: [],
            'vars'    => get_transient( 'ssm_scanned_vars' )   ?: [],
        ] );
    }

    public function preview_css() {
        $this->verify();
        $raw = [];
        if ( ! empty( $_POST['styles'] ) ) {
            $raw = is_string( $_POST['styles'] )
                ? json_decode( stripslashes( $_POST['styles'] ), true ) ?: []
                : (array) $_POST['styles'];
        }
        wp_send_json_success( [ 'css' => SSM_Style_Manager::build_preview_css( $raw ) ] );
    }

    public function take_backup() {
        $this->verify();
        SSM_Style_Manager::take_backup();
        $backup = SSM_Style_Manager::get_backup();
        wp_send_json_success( [
            'message'   => 'Backup saved!',
            'timestamp' => $backup['timestamp'],
        ] );
    }

    public function restore_backup() {
        $this->verify();
        $ok = SSM_Style_Manager::restore_backup();
        if ( ! $ok ) {
            wp_send_json_error( [ 'message' => 'No backup found. Take a backup first.' ] );
        }
        wp_send_json_success( [ 'message' => 'Restored from backup.' ] );
    }

    /**
     * Receives DOM-scanned colors from frontend JS and caches them.
     * Called from frontend-badge.js on page load.
     */
    public function store_page_colors() {
        $this->verify();

        $raw_colors = isset( $_POST['colors'] ) ? wp_unslash( $_POST['colors'] ) : '';
        $colors     = is_string( $raw_colors )
            ? json_decode( $raw_colors, true ) ?: []
            : (array) $raw_colors;

        // Merge with any existing scanned colors (union, prefer higher count)
        $existing = get_transient( 'ssm_scanned_colors' ) ?: [];
        $merged   = [];

        // Index existing by hex
        foreach ( $existing as $c ) {
            $merged[ $c['hex'] ] = $c;
        }

        foreach ( $colors as $c ) {
            $hex = sanitize_hex_color( $c['hex'] ?? '' );
            if ( ! $hex ) continue;
            $count = absint( $c['count'] ?? 1 );
            if ( isset( $merged[ $hex ] ) ) {
                $merged[ $hex ]['count'] = max( $merged[ $hex ]['count'], $count );
            } else {
                $merged[ $hex ] = [
                    'hex'   => $hex,
                    'count' => $count,
                    'name'  => sanitize_text_field( $c['name'] ?? '' ),
                    'light' => ! empty( $c['light'] ),
                ];
            }
        }

        // Sort by count descending, keep top 80
        usort( $merged, fn( $a, $b ) => $b['count'] - $a['count'] );
        $merged = array_slice( array_values( $merged ), 0, 80 );

        set_transient( 'ssm_scanned_colors', $merged, 48 * HOUR_IN_SECONDS );

        wp_send_json_success( [ 'stored' => count( $merged ) ] );
    }

    /**
     * Receives CSS variables detected by JS from the rendered page.
     */
    public function store_css_vars() {
        $this->verify();

        $raw_vars = isset( $_POST['vars'] ) ? wp_unslash( $_POST['vars'] ) : '';
        $vars     = is_string( $raw_vars )
            ? json_decode( $raw_vars, true ) ?: []
            : (array) $raw_vars;

        $clean = [];
        foreach ( $vars as $name => $val ) {
            $name = sanitize_text_field( $name );
            $val  = sanitize_text_field( $val );
            if ( preg_match( '/^--[\w-]+$/', $name ) && $val ) {
                $clean[ $name ] = $val;
            }
        }

        set_transient( 'ssm_scanned_vars', $clean, 48 * HOUR_IN_SECONDS );
        wp_send_json_success( [ 'stored' => count( $clean ) ] );
    }
}
