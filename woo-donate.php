<?php
/**
 * Plugin Name: Woo Donate - Stellar Donations and Tips
 * Plugin URI: https://github.com/StaveIndustries/woo-donate
 * Description: Accept XLM/USDC donations on Stellar with donor walls. Standalone (no WooCommerce required).
 * Version: 0.1.0
 * Author: StaveIndustries
 * License: MIT
 * Text Domain: woo-donate
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WOO_DONATE_VERSION', '0.1.0');
define('WOO_DONATE_PATH', plugin_dir_path(__FILE__));
define('WOO_DONATE_URL', plugin_dir_url(__FILE__));

require_once WOO_DONATE_PATH . 'includes/class-stellar-utils.php';
require_once WOO_DONATE_PATH . 'includes/class-donation-store.php';
require_once WOO_DONATE_PATH . 'includes/class-donation-settings.php';
require_once WOO_DONATE_PATH . 'includes/class-donation-shortcode.php';
require_once WOO_DONATE_PATH . 'includes/class-donation-checker.php';
require_once WOO_DONATE_PATH . 'includes/class-donor-wall.php';

/** Register the stellar_donation post type and shortcodes. */
function woo_donate_init(): void
{
    Woo_Donate_Store::register_post_type();
    add_shortcode('stellar_donate', ['Woo_Donate_Shortcode', 'render_form']);
    add_shortcode('stellar_donor_wall', ['Woo_Donor_Wall', 'render']);
}
add_action('init', 'woo_donate_init');

/** Admin settings page. */
function woo_donate_admin_menu(): void
{
    add_options_page(
        __('Stellar Donations', 'woo-donate'),
        __('Stellar Donations', 'woo-donate'),
        'manage_options',
        'woo-donate',
        ['Woo_Donate_Settings', 'render_page']
    );
}
add_action('admin_menu', 'woo_donate_admin_menu');

/** Enqueue frontend assets on pages with our shortcodes. */
function woo_donate_enqueue_assets(): void
{
    global $post;
    if (!$post instanceof WP_Post) {
        return;
    }
    if (has_shortcode($post->post_content, 'stellar_donate')
        || has_shortcode($post->post_content, 'stellar_donor_wall')) {
        wp_enqueue_script(
            'woo-donate',
            WOO_DONATE_URL . 'assets/donate.js',
            [],
            WOO_DONATE_VERSION,
            true
        );
        wp_enqueue_style(
            'woo-donate',
            WOO_DONATE_URL . 'assets/donate.css',
            [],
            WOO_DONATE_VERSION
        );
        wp_localize_script('woo-donate', 'WooDonate', [
            'copied' => __('Copied!', 'woo-donate'),
            'copy'   => __('Copy', 'woo-donate'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'woo_donate_enqueue_assets');

/** Cron: verify pending donations every 10 minutes. */
function woo_donate_cron_hook(): void
{
    Woo_Donate_Checker::verify_pending();
}
add_action('woo_donate_verify_event', 'woo_donate_cron_hook');

function woo_donate_activate(): void
{
    if (!wp_next_scheduled('woo_donate_verify_event')) {
        wp_schedule_event(time(), 'ten_minutes', 'woo_donate_verify_event');
    }
}
register_activation_hook(__FILE__, 'woo_donate_activate');

function woo_donate_add_cron_interval(array $schedules): array
{
    $schedules['ten_minutes'] = [
        'interval' => 600,
        'display'  => __('Every 10 minutes', 'woo-donate'),
    ];
    return $schedules;
}
add_filter('cron_schedules', 'woo_donate_add_cron_interval');

function woo_donate_deactivate(): void
{
    $ts = wp_next_scheduled('woo_donate_verify_event');
    if ($ts) {
        wp_unschedule_event($ts, 'woo_donate_verify_event');
    }
}
register_deactivation_hook(__FILE__, 'woo_donate_deactivate');
