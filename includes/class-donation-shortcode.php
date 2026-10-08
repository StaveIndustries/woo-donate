<?php
declare(strict_types=1);

/**
 * [stellar_donate] shortcode: amount + asset + optional name, creates a
 * pending donation and shows payment instructions (address, amount, memo).
 */
final class Woo_Donate_Shortcode
{
    public static function render_form(array $atts = []): string
    {
        $settings = Woo_Donate_Settings::get();
        $wallet = $settings['wallet_address'];
        if ($wallet === '') {
            return '<p>' . esc_html__('Donations are not configured yet.', 'woo-donate') . '</p>';
        }

        $created_id = 0;
        if (isset($_POST['woo_donate_submit']) && check_admin_referer('woo_donate_form')) {
            $amount = floatval($_POST['woo_donate_amount'] ?? 0);
            $asset = strtoupper(sanitize_text_field($_POST['woo_donate_asset'] ?? $settings['asset']));
            $name = sanitize_text_field($_POST['woo_donate_name'] ?? '');
            if ($amount > 0 && in_array($asset, ['XLM', 'USDC'], true)) {
                $created_id = Woo_Donate_Store::create($amount, $asset, $name);
            }
        }

        ob_start();
        if ($created_id > 0) {
            $d = Woo_Donate_Store::to_array(get_post($created_id));
            ?>
            <div class="woo-donate-instructions">
                <h3><?php esc_html_e('Send your donation', 'woo-donate'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Address:', 'woo-donate'); ?> <code><?php echo esc_html($wallet); ?></code>
                        <button type="button" class="button woo-donate-copy" data-copy="<?php echo esc_attr($wallet); ?>"><?php esc_html_e('Copy', 'woo-donate'); ?></button></li>
                    <li><?php esc_html_e('Amount:', 'woo-donate'); ?> <strong><?php echo esc_html((string) $d['amount'] . ' ' . $d['asset']); ?></strong></li>
                    <li><?php esc_html_e('Memo (required):', 'woo-donate'); ?> <code><?php echo esc_html($d['memo']); ?></code>
                        <button type="button" class="button woo-donate-copy" data-copy="<?php echo esc_attr($d['memo']); ?>"><?php esc_html_e('Copy', 'woo-donate'); ?></button></li>
                </ul>
                <p><?php esc_html_e('Your donation appears below once confirmed on Stellar (checked every few minutes).', 'woo-donate'); ?></p>
            </div>
            <?php
        } else {
            ?>
            <form method="post" class="woo-donate-form">
                <?php wp_nonce_field('woo_donate_form'); ?>
                <p><label><?php esc_html_e('Amount', 'woo-donate'); ?>
                    <input name="woo_donate_amount" type="number" min="0" step="any" required /></label></p>
                <p><label><?php esc_html_e('Asset', 'woo-donate'); ?>
                    <select name="woo_donate_asset">
                        <option value="XLM">XLM</option>
                        <option value="USDC">USDC</option>
                    </select></label></p>
                <p><label><?php esc_html_e('Your name (optional, shown on the donor wall)', 'woo-donate'); ?>
                    <input name="woo_donate_name" type="text" maxlength="60" /></label></p>
                <p><input type="submit" name="woo_donate_submit" class="button button-primary" value="<?php esc_attr_e('Donate with Stellar', 'woo-donate'); ?>" /></p>
            </form>
            <?php
        }
        return (string) ob_get_clean();
    }
}
