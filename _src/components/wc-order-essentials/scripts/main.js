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

                if (checkbox) {
                    item.classList.toggle('is-selected', checkbox.checked);
                }

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
            const adjustButton = event.target && event.target.closest('[data-essentials-qty-adjust]');

            if (adjustButton) {
                const control = adjustButton.closest('[data-essentials-qty-control]');
                const qtyInput = control ? control.querySelector('[data-essentials-qty]') : null;

                if (qtyInput) {
                    const parsedValue = Number.parseInt(String(qtyInput.value || '0'), 10);
                    const currentValue = Number.isNaN(parsedValue) ? 0 : parsedValue;
                    const parsedStep = Number.parseInt(String(qtyInput.step || '1'), 10);
                    const step = Number.isNaN(parsedStep) || parsedStep < 1 ? 1 : parsedStep;
                    const parsedMin = Number.parseInt(String(qtyInput.min || '0'), 10);
                    const min = Number.isNaN(parsedMin) ? 0 : parsedMin;
                    const parsedMax = Number.parseInt(String(qtyInput.max || ''), 10);
                    const max = Number.isNaN(parsedMax) ? null : parsedMax;
                    const shouldIncrement = adjustButton.dataset.essentialsQtyAdjust === 'increment';

                    let nextValue = shouldIncrement ? currentValue + step : currentValue - step;
                    nextValue = Math.max(min, nextValue);

                    if (max !== null) {
                        nextValue = Math.min(max, nextValue);
                    }

                    qtyInput.value = String(nextValue);
                    qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
                    qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                return;
            }

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
