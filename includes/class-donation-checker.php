<?php
declare(strict_types=1);

/**
 * Horizon verification: polls recent payments for the shop wallet and marks
 * matching pending donations confirmed (memo + amount + asset).
 */
final class Woo_Donate_Checker
{
    /**
     * Fetch recent payments for an address. Returns [] when Horizon is
     * unreachable so callers can tell "could not check" from "no payments".
     *
     * @return array<int,array<string,mixed>>
     */
    public static function fetch_payments(string $address, string $network = 'testnet', int $limit = 50): array
    {
        $base = Stellar_Donate_Utils::horizon_url($network);
        $url = sprintf(
            '%s/accounts/%s/payments?order=desc&limit=%d',
            $base,
            rawurlencode($address),
            min(200, max(1, $limit))
        );
        if (function_exists('wp_remote_get')) {
            $res = wp_remote_get($url, ['timeout' => 15]);
            if (is_wp_error($res)) {
                return [];
            }
            $data = json_decode(wp_remote_retrieve_body($res), true);
        } else {
            $body = @file_get_contents($url);
            $data = $body === false ? null : json_decode($body, true);
        }
        $out = [];
        foreach (($data['_embedded']['records'] ?? []) as $r) {
            $out[] = [
                'type'       => $r['type'] ?? '',
                'to'         => $r['to'] ?? '',
                'amount'     => $r['amount'] ?? '0',
                'asset_type' => $r['asset_type'] ?? '',
                'asset_code' => $r['asset_code'] ?? '',
                'memo'       => $r['memo'] ?? '',
                'tx_hash'    => $r['transaction_hash'] ?? '',
            ];
        }
        return $out;
    }

    /**
     * Verify all pending donations once. Called by cron and manually.
     */
    public static function verify_pending(): void
    {
        $settings = Woo_Donate_Settings::get();
        $wallet = $settings['wallet_address'];
        if ($wallet === '') {
            return;
        }
        $payments = self::fetch_payments($wallet, $settings['network']);
        if ($payments === []) {
            return;
        }
        foreach (Woo_Donate_Store::pending() as $d) {
            foreach ($payments as $p) {
                if (Stellar_Donate_Utils::payment_matches(
                    $p,
                    $wallet,
                    $d['asset'],
                    $d['memo'],
                    $d['amount']
                )) {
                    Woo_Donate_Store::set_status($d['id'], Woo_Donate_Store::STATUS_CONFIRMED, $p['tx_hash'] ?? '');
                    break;
                }
            }
        }
        self::expire_old();
    }

    /**
     * Expire pending donations past the payment window.
     */
    public static function expire_old(): void
    {
        $settings = Woo_Donate_Settings::get();
        $window = max(5, intval($settings['window_minutes'] ?? 60)) * 60;
        foreach (Woo_Donate_Store::pending() as $d) {
            $post = get_post($d['id']);
            if ($post && (time() - strtotime($post->post_date_gmt . ' GMT')) > $window) {
                Woo_Donate_Store::set_status($d['id'], Woo_Donate_Store::STATUS_EXPIRED);
            }
        }
    }
}
