/**
 * Woo Donate - frontend helpers.
 * Copy buttons for address/memo. No framework needed.
 */
(function () {
    'use strict';

    // Copy-to-clipboard for any .woo-donate-copy button with data-copy.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.woo-donate-copy');
        if (!btn) {
            return;
        }
        var text = btn.getAttribute('data-copy') || '';
        var done = function () {
            var original = btn.textContent;
            var strings = window.WooDonate || {};
            btn.textContent = strings.copied || 'Copied!';
            setTimeout(function () {
                btn.textContent = original;
            }, 2000);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done, done);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
            } catch (err) {
                /* clipboard unavailable - still show feedback */
            }
            document.body.removeChild(ta);
            done();
        }
    });
})();
