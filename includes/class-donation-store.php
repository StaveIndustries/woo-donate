<?php
declare(strict_types=1);

/**
 * Donation storage on the stellar_donation post type.
 * Statuses: pending (waiting), confirmed (paid), expired, cancelled.
 */
final class Woo_Donate_Store
{
    public const POST_TYPE = 'stellar_donation';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public static function register_post_type(): void
    {
        register_post_type(self::POST_TYPE, [
            'label'       => __('Stellar Donations', 'woo-donate'),
            'public'      => false,
            'show_ui'     => true,
            'supports'    => ['title'],
            'capabilities' => ['manage_options'],
        ]);
    }

    /**
     * Create a pending donation and assign its unique memo.
     *
     * @return int Post ID of the new donation.
     */
    public static function create(float $amount, string $asset, string $donor_name): int
    {
        $id = wp_insert_post([
            'post_type'   => self::POST_TYPE,
            'post_title'  => sprintf(__('Donation #%d', 'woo-donate'), 0),
            'post_status' => 'publish',
            'meta_input'  => [
                '_woo_donate_amount' => $amount,
                '_woo_donate_asset'  => strtoupper($asset),
                '_woo_donate_donor'  => sanitize_text_field($donor_name),
                '_woo_donate_status' => self::STATUS_PENDING,
                '_woo_donate_created' => time(),
            ],
        ]);
        $memo = Stellar_Donate_Utils::generate_memo($id);
        update_post_meta($id, '_woo_donate_memo', $memo);
        wp_update_post([
            'ID'         => $id,
            'post_title' => sprintf(__('Donation #%d', 'woo-donate'), $id),
        ]);
        return $id;
    }

    /** @return array<int,array<string,mixed>> Pending donations oldest first. */
    public static function pending(): array
    {
        $q = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => 100,
            'meta_key'       => '_woo_donate_status',
            'meta_value'     => self::STATUS_PENDING,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ]);
        return array_map([self::class, 'to_array'], $q->posts);
    }

    /** @return array<int,array<string,mixed>> Confirmed donations newest first. */
    public static function confirmed(int $limit = 50): array
    {
        $q = new WP_Query([
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => $limit,
            'meta_key'       => '_woo_donate_status',
            'meta_value'     => self::STATUS_CONFIRMED,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        return array_map([self::class, 'to_array'], $q->posts);
    }

    public static function to_array(WP_Post $post): array
    {
        return [
            'id'      => $post->ID,
            'amount'  => floatval(get_post_meta($post->ID, '_woo_donate_amount', true)),
            'asset'   => get_post_meta($post->ID, '_woo_donate_asset', true),
            'donor'   => get_post_meta($post->ID, '_woo_donate_donor', true),
            'memo'    => get_post_meta($post->ID, '_woo_donate_memo', true),
            'status'  => get_post_meta($post->ID, '_woo_donate_status', true),
            'tx_hash' => get_post_meta($post->ID, '_woo_donate_tx', true),
        ];
    }

    public static function set_status(int $id, string $status, string $tx_hash = ''): void
    {
        update_post_meta($id, '_woo_donate_status', $status);
        if ($tx_hash !== '') {
            update_post_meta($id, '_woo_donate_tx', $tx_hash);
        }
    }
}
