<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/class-stellar-utils.php';

/**
 * Offline tests for memo generation, address validation, payment matching.
 * Never touch the network.
 */
final class StellarDonateUtilsTest extends TestCase
{
    public function test_memo_format_and_uniqueness(): void
    {
        $a = Stellar_Donate_Utils::generate_memo(7);
        $b = Stellar_Donate_Utils::generate_memo(7);
        $this->assertMatchesRegularExpression('/^DON-7-[A-Z0-9]{6}$/', $a);
        $this->assertNotSame($a, $b);
        $this->assertLessThanOrEqual(28, strlen($a));
    }

    public function test_valid_address(): void
    {
        $this->assertTrue(Stellar_Donate_Utils::is_valid_address(
            'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5'
        ));
        $this->assertFalse(Stellar_Donate_Utils::is_valid_address(''));
        $this->assertFalse(Stellar_Donate_Utils::is_valid_address('GABC'));
        $this->assertFalse(Stellar_Donate_Utils::is_valid_address(
            'XBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5'
        ));
    }

    public function test_payment_matches_xlm(): void
    {
        $wallet = 'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5';
        $good = ['to' => $wallet, 'amount' => '10', 'asset_type' => 'native', 'memo' => 'DON-3-ABC123'];
        $this->assertTrue(Stellar_Donate_Utils::payment_matches($good, $wallet, 'XLM', 'DON-3-ABC123', 10.0));
        $this->assertFalse(Stellar_Donate_Utils::payment_matches(
            ['amount' => '9'] + $good, $wallet, 'XLM', 'DON-3-ABC123', 10.0
        ));
        $this->assertFalse(Stellar_Donate_Utils::payment_matches(
            ['memo' => 'DON-3-XXXXXX'] + $good, $wallet, 'XLM', 'DON-3-ABC123', 10.0
        ));
    }

    public function test_payment_matches_usdc(): void
    {
        $wallet = 'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5';
        $memo = 'DON-9-ZZ99ZZ';
        $usdc = ['to' => $wallet, 'amount' => '10.50', 'asset_type' => 'credit_alphanum4', 'asset_code' => 'USDC', 'memo' => $memo];
        $this->assertTrue(Stellar_Donate_Utils::payment_matches($usdc, $wallet, 'USDC', $memo, 10.50));
        $xlm = ['to' => $wallet, 'amount' => '10.50', 'asset_type' => 'native', 'memo' => $memo];
        $this->assertFalse(Stellar_Donate_Utils::payment_matches($xlm, $wallet, 'USDC', $memo, 10.50));
    }

    public function test_horizon_urls(): void
    {
        $this->assertSame('https://horizon-testnet.stellar.org', Stellar_Donate_Utils::horizon_url('testnet'));
        $this->assertSame('https://horizon.stellar.org', Stellar_Donate_Utils::horizon_url('public'));
    }
}
