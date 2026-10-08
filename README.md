# Woo Donate - Stellar Donations and Tips

WordPress plugin that accepts **XLM/USDC donations** on Stellar. Standalone:
no WooCommerce required. Donors give through a shortcode form, payments are
matched by unique memo, and confirmed donors appear on a donor wall.

## Shortcodes

- `[stellar_donate]` - donation form (amount, asset, optional name).
- `[stellar_donor_wall limit="20"]` - confirmed donors, newest first.

## Requirements

- PHP 7.4+, WordPress 6.0+
- A Stellar wallet (testnet for testing, mainnet for live)

## Install steps

1. Clone or download this repo into `wp-content/plugins/woo-donate/`.
2. Activate **Woo Donate** in WP Admin - Plugins.
3. Go to Settings - Stellar Donations: set wallet address, asset, network.
4. Add `[stellar_donate]` and `[stellar_donor_wall]` to any page.

## How matching works

Each donation gets a unique memo (`DON-{id}-{6char}`). A cron job checks
Horizon every 10 minutes and confirms donations whose amount + asset + memo
match. Expired ones (past the payment window) are marked expired.

## One-command local demo (Docker)

```bash
docker compose up -d
# open http://localhost:8081, finish the WP installer
# Plugins - activate Woo Donate; Settings - Stellar Donations to configure
```

## Tests

```bash
composer install && composer test   # offline PHPUnit suite
```

## Contributing

See CONTRIBUTING.md. Good first issues are labeled for newcomers.

## License

MIT - see LICENSE.
