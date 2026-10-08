<?php
declare(strict_types=1);

/**
 * Pure-PHP Stellar helpers. No WordPress needed, fully unit-testable.
 */
final class Stellar_Donate_Utils
{
    /** Memo text max length on Stellar is 28 bytes. */
    public const MEMO_MAX_LENGTH = 28;

    /**
     * Validate a Stellar wallet address (StrKey format check).
     * Real check: starts with G, 56 chars, base32 alphabet.
     */
    public static function is_valid_address(string $address): bool
    {
        return (bool) preg_match('/^G[A-Z2-7]{55}$/', trim($address));
    }

    /**
     * Unique memo per donation: DON-{postId}-{6 random chars}, always <= 28.
     */
    public static function generate_memo(int $donation_id): string
    {
        $rand = strtoupper(substr(md5(uniqid((string) $donation_id, true)), 0, 6));
        $memo = sprintf('DON-%d-%s', $donation_id, $rand);
        return substr($memo, 0, self::MEMO_MAX_LENGTH);
    }

    /**
     * Horizon base URL for a network.
     */
    public static function horizon_url(string $network): string
    {
        return $network === 'public'
            ? 'https://horizon.stellar.org'
            : 'https://horizon-testnet.stellar.org';
    }

    /**
     * True when a Horizon payment record pays for this donation.
     * Matches destination + amount + asset + memo.
     *
     * @param array<string,mixed> $payment Horizon payment record.
     */
    public static function payment_matches(
        array $payment,
        string $expected_address,
        string $expected_asset,
        string $expected_memo,
        float $expected_amount
    ): bool {
        if (($payment['to'] ?? '') !== $expected_address) {
            return false;
        }
        $asset_ok = $expected_asset === 'XLM'
            ? ($payment['asset_type'] ?? '') === 'native'
            : ($payment['asset_code'] ?? '') === $expected_asset;
        if (!$asset_ok) {
            return false;
        }
        if (($payment['memo'] ?? '') !== $expected_memo) {
            return false;
        }
        return abs(floatval($payment['amount'] ?? 0) - $expected_amount) < 0.0000001;
    }
}
