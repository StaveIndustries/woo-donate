<?php
declare(strict_types=1);

/**
 * Settings page under Settings -> Stellar Donations.
 * Options: wallet address, default asset, network, payment window.
 */
final class Woo_Donate_Settings
{
    public static function defaults(): array
    {
        return [
            'wallet_address' => '',
            'asset'          => 'XLM',
            'network'        => 'testnet',
            'window_minutes' => 60,
        ];
    }

    /** @return array<string,mixed> */
    public static function get(): array
    {
        $saved = get_option('woo_donate_settings', []);
        return array_merge(self::defaults(), is_array($saved) ? $saved : []);
    }

    public static function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_POST['woo_donate_save']) && check_admin_referer('woo_donate_save')) {
            $address = trim(sanitize_text_field($_POST['wallet_address'] ?? ''));
            if ($address !== '' && !Stellar_Donate_Utils::is_valid_address($address)) {
                add_settings_error(
                    'woo_donate',
                    'invalid-address',
                    __('Invalid Stellar wallet address. Must start with G and be 56 characters.', 'woo-donate'),
                    'error'
                );
            } else {
                update_option('woo_donate_settings', [
                    'wallet_address' => $address,
                    'asset'          => in_array($_POST['asset'] ?? '', ['XLM', 'USDC'], true) ? $_POST['asset'] : 'XLM',
                    'network'        => ($_POST['network'] ?? '') === 'public' ? 'public' : 'testnet',
                    'window_minutes' => max(5, intval($_POST['window_minutes'] ?? 60)),
                ]);
                add_settings_error('woo_donate', 'saved', __('Settings saved.', 'woo-donate'), 'success');
            }
        }
        settings_errors('woo_donate');
        $s = self::get();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Stellar Donations', 'woo-donate'); ?></h1>
            <?php if ($s['network'] === 'testnet'): ?>
                <div class="notice notice-warning"><p><?php esc_html_e('Testnet is active: donations are play money, not real funds.', 'woo-donate'); ?></p></div>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field('woo_donate_save'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="wallet_address"><?php esc_html_e('Wallet address', 'woo-donate'); ?></label></th>
                        <td><input id="wallet_address" name="wallet_address" class="regular-text" value="<?php echo esc_attr($s['wallet_address']); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="asset"><?php esc_html_e('Default asset', 'woo-donate'); ?></label></th>
                        <td>
                            <select id="asset" name="asset">
                                <option value="XLM" <?php selected($s['asset'], 'XLM'); ?>>XLM</option>
                                <option value="USDC" <?php selected($s['asset'], 'USDC'); ?>>USDC</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="network"><?php esc_html_e('Network', 'woo-donate'); ?></label></th>
                        <td>
                            <select id="network" name="network">
                                <option value="testnet" <?php selected($s['network'], 'testnet'); ?>>Testnet</option>
                                <option value="public" <?php selected($s['network'], 'public'); ?>>Mainnet</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="window_minutes"><?php esc_html_e('Payment window (minutes)', 'woo-donate'); ?></label></th>
                        <td><input id="window_minutes" name="window_minutes" type="number" min="5" value="<?php echo esc_attr((string) $s['window_minutes']); ?>" /></td>
                    </tr>
                </table>
                <p><input type="submit" name="woo_donate_save" class="button-primary" value="<?php esc_attr_e('Save', 'woo-donate'); ?>" /></p>
            </form>
            <h2><?php esc_html_e('Usage', 'woo-donate'); ?></h2>
            <p><code>[stellar_donate]</code> - donation form. <code>[stellar_donor_wall]</code> - confirmed donors.</p>
        </div>
        <?php
    }
}
