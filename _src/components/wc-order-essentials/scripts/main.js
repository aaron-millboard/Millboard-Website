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

        const modal = form.querySelector('[data-essentials-modal]');
        const modalPanels = modal ? modal.querySelectorAll('[data-essentials-modal-panel]') : [];
        const modalAck = modal ? modal.querySelector('[data-essentials-modal-ack]') : null;
        const modalSubmit = modal ? modal.querySelector('[data-essentials-modal-submit]') : null;

        const updateModalSubmit = () => {
            if (!modalSubmit || !modalAck) {
                return;
            }

            modalSubmit.disabled = !modalAck.checked;
        };

        const removeAddedModalQuery = () => {
            const url = new URL(window.location.href);

            if (!url.searchParams.has('millboard_essentials_added')) {
                return;
            }

            url.searchParams.delete('millboard_essentials_added');
            window.history.replaceState({}, '', url.toString());
        };

        const openModal = (panelName) => {
            if (!modal) {
                return;
            }

            modalPanels.forEach((panel) => {
                const isActivePanel = panel.dataset.essentialsModalPanel === panelName;
                panel.hidden = !isActivePanel;

                if (isActivePanel) {
                    const title = panel.querySelector('.cart__order-essentials-modal__title');

                    if (title && title.id) {
                        modal.querySelector('[role="dialog"]').setAttribute('aria-labelledby', title.id);
                    }
                }
            });

            if (modalAck) {
                modalAck.checked = false;
            }

            updateModalSubmit();
            modal.classList.add('is-active');
            modal.setAttribute('aria-hidden', 'false');
            removeAddedModalQuery();
        };

        const closeModal = () => {
            if (!modal) {
                return;
            }

            modal.classList.remove('is-active');
            modal.setAttribute('aria-hidden', 'true');
        };

        form.addEventListener('change', queueUpdate);
        form.addEventListener('input', queueUpdate);
        form.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target : null;

            if (!target) {
                return;
            }

            const modalOpener = target.closest('[data-essentials-open-modal]');

            if (modalOpener && modal) {
                event.preventDefault();
                openModal(modalOpener.dataset.essentialsOpenModal || 'continue');
                return;
            }

            if (target.closest('[data-essentials-close-modal]')) {
                event.preventDefault();
                closeModal();
                return;
            }

            if (target.closest('label')) {
                queueUpdate();
            }
        });

        if (modalAck) {
            modalAck.addEventListener('change', updateModalSubmit);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal && modal.classList.contains('is-active')) {
                closeModal();
            }
        });

        updateSummary();

        if (modal && modal.classList.contains('is-active')) {
            openModal('added');
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOrderEssentialsSummary);
        return;
    }

    initOrderEssentialsSummary();
})();
