<?php
declare(strict_types=1);

/**
 * [stellar_donor_wall] shortcode: lists confirmed donations newest first.
 * Attributes: limit="20".
 */
final class Woo_Donor_Wall
{
    public static function render(array $atts = []): string
    {
        $limit = max(1, min(100, intval($atts['limit'] ?? 20)));
        $donations = Woo_Donate_Store::confirmed($limit);
        if ($donations === []) {
            return '<p class="woo-donor-wall-empty">'
                . esc_html__('No donations yet. Be the first!', 'woo-donate')
                . '</p>';
        }
        ob_start();
        echo '<ul class="woo-donor-wall">';
        foreach ($donations as $d) {
            $name = $d['donor'] !== '' ? $d['donor'] : __('Anonymous', 'woo-donate');
            printf(
                '<li><strong>%s</strong> donated <strong>%s %s</strong></li>',
                esc_html($name),
                esc_html((string) $d['amount']),
                esc_html($d['asset'])
            );
        }
        echo '</ul>';
        return (string) ob_get_clean();
    }
}
