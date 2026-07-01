(() => {
    const initOrderEssentialsSummary = () => {
        const form = document.querySelector('.cart__order-essentials-page .woocommerce-cart-form');

        if (!form) {
            return;
        }

        const countEl = form.querySelector('[data-essentials-summary-count]');
        const labelEl = form.querySelector('[data-essentials-summary-label]');
        const totalEl = form.querySelector('[data-essentials-summary-total]');

        if (!countEl || !labelEl || !totalEl) {
            return;
        }

        const locale = document.documentElement.lang || 'en-GB';
        const currencyCode = form.dataset.currencyCode || 'GBP';

        const formatCurrency = new Intl.NumberFormat(locale, {
            style: 'currency',
            currency: currencyCode
        });

        const updateSummary = () => {
            let selectedCount = 0;
            let total = 0;

            form.querySelectorAll('.cart__order-essentials__item').forEach((item) => {
                const checkbox = item.querySelector('[data-essentials-select]');
                const qtyInput = item.querySelector('[data-essentials-qty]');

                if (!checkbox || !qtyInput || !checkbox.checked) {
                    return;
                }

                const parsedQty = Number.parseInt(String(qtyInput.value || '0'), 10);
                const qty = Number.isNaN(parsedQty) ? 0 : Math.max(0, parsedQty);

                if (qty < 1) {
                    return;
                }

                const parsedUnitPrice = Number.parseFloat(String(qtyInput.dataset.unitPrice || '0'));
                const unitPrice = Number.isNaN(parsedUnitPrice) ? 0 : parsedUnitPrice;

                selectedCount += 1;
                total += unitPrice * qty;
            });

            countEl.textContent = String(selectedCount);
            labelEl.textContent = selectedCount === 1
                ? (labelEl.dataset.singular || 'selected item')
                : (labelEl.dataset.plural || 'selected items');
            totalEl.textContent = formatCurrency.format(total);
        };

        const queueUpdate = () => {
            window.requestAnimationFrame(updateSummary);
        };

        form.addEventListener('change', queueUpdate);
        form.addEventListener('input', queueUpdate);
        form.addEventListener('click', (event) => {
            if (event.target && event.target.closest('label')) {
                queueUpdate();
            }
        });

        updateSummary();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOrderEssentialsSummary);
        return;
    }

    initOrderEssentialsSummary();
})();
