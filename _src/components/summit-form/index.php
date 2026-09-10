<?php
/**
 * Summit registration form.
 *
 * The form works without JavaScript only as far as showing itself; submission
 * goes through the REST gate, so the script is required to submit. That is a
 * deliberate trade: the two rules cannot be enforced client-side, and a
 * no-JS POST fallback would need a second, duplicate code path for the same
 * checks. The noscript block tells the visitor what to do instead.
 */

$dates = $args['dates'] ?? [];
$workshops = $args['workshops'] ?? [];
$privacy_url = trim((string) ($args['privacy_url'] ?? ''));

// The consent sentence mentions the privacy policy; link it if the editor gave
// us a URL, otherwise leave the text alone rather than inventing a link.
$consent_text = (string) ($args['consent_text'] ?? '');
if ($privacy_url !== '') {
    $consent_text = str_replace(
        'privacy policy',
        '<a href="' . esc_url($privacy_url) . '">privacy policy</a>',
        $consent_text
    );
}
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="summit-form__inner">

        <?php if (!empty($args['heading'])) { ?>
            <h2 class="summit-form__heading"><?= esc_html($args['heading']); ?></h2>
        <?php } ?>

        <?php if (!empty($args['description'])) { ?>
            <p class="summit-form__description"><?= esc_html($args['description']); ?></p>
        <?php } ?>

        <?php if (!empty($args['intro'])) { ?>
            <p class="summit-form__intro"><?= esc_html($args['intro']); ?></p>
        <?php } ?>

        <noscript>
            <p class="summit-form__error" role="alert">
                <?= esc_html__(
                    'This registration form needs JavaScript enabled. Please turn it on and reload the page, or reply to your invitation and we will register you.',
                    'granola'
                ); ?>
            </p>
        </noscript>

        <form class="summit-form__form" data-summit-form novalidate>

            <?php /* Errors that apply to the whole form: not invited, company full. */ ?>
            <div class="summit-form__notice" data-summit-notice role="alert" hidden></div>

            <div class="summit-form__row">
                <div class="summit-form__field">
                    <label class="summit-form__label" for="summit-first-name">
                        <?= esc_html__('First name', 'granola'); ?><span class="summit-form__required" aria-hidden="true">*</span>
                    </label>
                    <input class="summit-form__input" id="summit-first-name" name="first_name" type="text" autocomplete="given-name" required>
                    <p class="summit-form__field-error" data-summit-error="first_name" hidden></p>
                </div>

                <div class="summit-form__field">
                    <label class="summit-form__label" for="summit-last-name">
                        <?= esc_html__('Last name', 'granola'); ?><span class="summit-form__required" aria-hidden="true">*</span>
                    </label>
                    <input class="summit-form__input" id="summit-last-name" name="last_name" type="text" autocomplete="family-name" required>
                    <p class="summit-form__field-error" data-summit-error="last_name" hidden></p>
                </div>
            </div>

            <div class="summit-form__field">
                <label class="summit-form__label" for="summit-email">
                    <?= esc_html__('Business Email', 'granola'); ?><span class="summit-form__required" aria-hidden="true">*</span>
                </label>
                <input class="summit-form__input" id="summit-email" name="email" type="email" autocomplete="email" required
                       aria-describedby="summit-email-hint">
                <p class="summit-form__hint" id="summit-email-hint">
                    <?= esc_html__('Please use the address your invitation was sent to.', 'granola'); ?>
                </p>
                <p class="summit-form__field-error" data-summit-error="email" hidden></p>
            </div>

            <div class="summit-form__field">
                <label class="summit-form__label" for="summit-company">
                    <?= esc_html__('Company name', 'granola'); ?><span class="summit-form__required" aria-hidden="true">*</span>
                </label>
                <input class="summit-form__input" id="summit-company" name="company_typed" type="text" autocomplete="organization" required>
                <p class="summit-form__field-error" data-summit-error="company_typed" hidden></p>
            </div>

            <div class="summit-form__field">
                <label class="summit-form__label" for="summit-date">
                    <?= esc_html__('Preferred date', 'granola'); ?><span class="summit-form__required" aria-hidden="true">*</span>
                </label>
                <select class="summit-form__select" id="summit-date" name="preferred_date" required>
                    <option value=""><?= esc_html__('Please Select', 'granola'); ?></option>
                    <?php foreach ($dates as $date) { ?>
                        <option value="<?= esc_attr($date); ?>"><?= esc_html($date); ?></option>
                    <?php } ?>
                </select>
                <p class="summit-form__field-error" data-summit-error="preferred_date" hidden></p>
            </div>

            <?php if (!empty($args['date_note'])) { ?>
                <p class="summit-form__note"><?= esc_html($args['date_note']); ?></p>
            <?php } ?>

            <fieldset class="summit-form__fieldset">
                <legend class="summit-form__label">
                    <?= esc_html__('Which workshops would you like to attend:', 'granola'); ?><span class="summit-form__required" aria-hidden="true">*</span>
                </legend>
                <ul class="summit-form__options">
                    <?php foreach ($workshops as $i => $workshop) { ?>
                        <li class="summit-form__option">
                            <input class="summit-form__checkbox" type="checkbox"
                                   id="summit-workshop-<?= esc_attr((string) $i); ?>"
                                   name="workshops[]" value="<?= esc_attr($workshop); ?>">
                            <label for="summit-workshop-<?= esc_attr((string) $i); ?>"><?= esc_html($workshop); ?></label>
                        </li>
                    <?php } ?>
                </ul>
                <p class="summit-form__field-error" data-summit-error="workshops" hidden></p>
            </fieldset>

            <fieldset class="summit-form__fieldset">
                <legend class="summit-form__label">
                    <?= esc_html__('Would you like a factory tour?', 'granola'); ?><span class="summit-form__required" aria-hidden="true">*</span>
                </legend>
                <ul class="summit-form__options">
                    <li class="summit-form__option">
                        <input class="summit-form__radio" type="radio" id="summit-tour-yes" name="factory_tour" value="Yes">
                        <label for="summit-tour-yes"><?= esc_html__('Yes', 'granola'); ?></label>
                    </li>
                    <li class="summit-form__option">
                        <input class="summit-form__radio" type="radio" id="summit-tour-no" name="factory_tour" value="No">
                        <label for="summit-tour-no"><?= esc_html__('No', 'granola'); ?></label>
                    </li>
                </ul>
                <p class="summit-form__field-error" data-summit-error="factory_tour" hidden></p>
            </fieldset>

            <?php if ($consent_text !== '') { ?>
                <?php /* Only the privacy-policy anchor is introduced above, so the
                         allowed tag list is deliberately narrow. */ ?>
                <p class="summit-form__consent"><?= wp_kses($consent_text, ['a' => ['href' => [], 'target' => [], 'rel' => []]]); ?></p>
            <?php } ?>

            <div class="summit-form__option summit-form__option--consent">
                <input class="summit-form__checkbox" type="checkbox" id="summit-opt-out" name="opt_out_marketing" value="1">
                <label for="summit-opt-out">
                    <?= esc_html__('I\'d like to not to receive other marketing communications from Millboard.', 'granola'); ?>
                </label>
            </div>

            <?php /* Off-screen honeypot. Bots complete every field they can find;
                     a value here is answered with a bland success so there is
                     nothing to tune against. Not labelled for humans, and
                     hidden from assistive tech. */ ?>
            <div class="summit-form__honeypot" aria-hidden="true">
                <label for="summit-company-website">Company website</label>
                <input id="summit-company-website" name="company_website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <button class="summit-form__submit" type="submit" data-summit-submit>
                <span data-summit-submit-label><?= esc_html($args['submit_label']); ?></span>
            </button>

        </form>

        <?php /* Replaces the form on success rather than sitting under it, so
                 nobody re-submits the same registration. */ ?>
        <div class="summit-form__success" data-summit-success role="status" hidden>
            <h3 class="summit-form__success-heading"><?= esc_html($args['success_heading']); ?></h3>
            <p class="summit-form__success-text"><?= esc_html($args['success_text']); ?></p>
        </div>

    </div>
</section>
