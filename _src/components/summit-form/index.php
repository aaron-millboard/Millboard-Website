<?php
/**
 * Summit registration form.
 *
 * One block serves all four audiences. Which questions appear is decided by the
 * audience set on the block, read from \Theme\Summit\Audiences so the form can
 * never offer something the gate would reject:
 *
 *   UK   picks one day (4th or 5th), plus workshops and a factory tour
 *   INT  no date question, automatically the 3rd and 4th
 *   US   no date question, automatically all three days
 *   FR   asked whether they can attend, plus two contact-consent questions
 *
 * The form works without JavaScript only as far as showing itself; submission
 * goes through the REST gate, so the script is required to submit. That is a
 * deliberate trade: the rules cannot be enforced client-side, and a no-JS POST
 * fallback would need a second, duplicate code path for the same checks.
 */

$audience = (string) ($args['audience'] ?? '');
$asks = (array) ($args['asks'] ?? []);
$dates = (array) ($args['dates'] ?? []);
$workshops = (array) ($args['workshops'] ?? []);
$assigned_days = (array) ($args['assigned_days'] ?? []);
$privacy_url = trim((string) ($args['privacy_url'] ?? ''));

// The block cannot work without knowing its audience. filter_args returns null
// on the front end in that case, so this only ever shows in the editor.
if (empty($args['has_audience'])) { ?>
    <section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
        <p class="summit-form__error">
            <?= esc_html__(
                'Set which audience this page is for (UK, INT, US or FR) before this form will appear. It decides both the questions asked and which days the registration takes.',
                'granola'
            ); ?>
        </p>
    </section>
    <?php
    return;
}

// The consent sentence mentions the privacy policy; link it if the editor gave
// us a URL, otherwise leave the text alone rather than inventing a link.
$consent_text = (string) ($args['consent_text'] ?? '');
if ($privacy_url !== '' && $consent_text !== '') {
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

        <form class="summit-form__form" data-summit-form data-summit-audience="<?= esc_attr($audience); ?>" novalidate>

            <?php /* Whole-form messages: not invited, wrong page, company full, day full. */ ?>
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

            <?php /* FR: whether they can come at all. A "no" is recorded and
                     takes no seat, so it skips both caps. */ ?>
            <?php if (!empty($asks['attending'])) { ?>
                <fieldset class="summit-form__fieldset">
                    <legend class="summit-form__label">
                        Serez-vous présent(e) ?<span class="summit-form__required" aria-hidden="true">*</span>
                    </legend>
                    <ul class="summit-form__options">
                        <li class="summit-form__option">
                            <input class="summit-form__radio" type="radio" id="summit-attending-yes" name="attending"
                                   value="<?= esc_attr($args['fr_attending_yes']); ?>">
                            <label for="summit-attending-yes"><?= esc_html($args['fr_attending_yes']); ?></label>
                        </li>
                        <li class="summit-form__option">
                            <input class="summit-form__radio" type="radio" id="summit-attending-no" name="attending"
                                   value="<?= esc_attr($args['fr_attending_no']); ?>">
                            <label for="summit-attending-no"><?= esc_html($args['fr_attending_no']); ?></label>
                        </li>
                    </ul>
                    <p class="summit-form__field-error" data-summit-error="attending" hidden></p>
                </fieldset>
            <?php } ?>

            <?php /* UK only: they attend one day and choose which. */ ?>
            <?php if (!empty($asks['preferred_date'])) { ?>
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
            <?php } ?>

            <?php /* INT, US and FR are assigned their days, so tell them which
                     rather than leaving them to guess. */ ?>
            <?php if (!empty($assigned_days)) { ?>
                <p class="summit-form__note summit-form__note--days">
                    <?= esc_html(sprintf(
                        /* translators: %s is a list of dates, e.g. "3rd November and 4th November". */
                        __('Your invitation covers %s.', 'granola'),
                        // wp_sprintf's %l joins a list with the locale's own
                        // "and", so French reads "et" without extra work.
                        \wp_sprintf('%l', $assigned_days)
                    )); ?>
                </p>
            <?php } ?>

            <?php if (!empty($asks['workshops'])) { ?>
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
            <?php } ?>

            <?php if (!empty($asks['factory_tour'])) { ?>
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
            <?php } ?>

            <?php /* FR consent pair, replacing the opt-out checkbox the other
                     three carry. Hardcoded French: these questions only ever
                     render on the fr-fr page. */ ?>
            <?php if (!empty($asks['email_contact'])) { ?>
                <fieldset class="summit-form__fieldset">
                    <legend class="summit-form__label">
                        Souhaitez-vous recevoir des informations par e-mail ?<span class="summit-form__required" aria-hidden="true">*</span>
                    </legend>
                    <ul class="summit-form__options">
                        <li class="summit-form__option">
                            <input class="summit-form__radio" type="radio" id="summit-email-contact-yes" name="email_contact" value="yes">
                            <label for="summit-email-contact-yes">Oui</label>
                        </li>
                        <li class="summit-form__option">
                            <input class="summit-form__radio" type="radio" id="summit-email-contact-no" name="email_contact" value="no">
                            <label for="summit-email-contact-no">Non</label>
                        </li>
                    </ul>
                    <p class="summit-form__field-error" data-summit-error="email_contact" hidden></p>
                </fieldset>
            <?php } ?>

            <?php if (!empty($asks['phone_contact'])) { ?>
                <fieldset class="summit-form__fieldset">
                    <legend class="summit-form__label">
                        Souhaitez-vous être contacté par téléphone ?<span class="summit-form__required" aria-hidden="true">*</span>
                    </legend>
                    <ul class="summit-form__options">
                        <li class="summit-form__option">
                            <input class="summit-form__radio" type="radio" id="summit-phone-contact-yes" name="phone_contact" value="yes">
                            <label for="summit-phone-contact-yes">Oui</label>
                        </li>
                        <li class="summit-form__option">
                            <input class="summit-form__radio" type="radio" id="summit-phone-contact-no" name="phone_contact" value="no">
                            <label for="summit-phone-contact-no">Non</label>
                        </li>
                    </ul>
                    <p class="summit-form__field-error" data-summit-error="phone_contact" hidden></p>
                </fieldset>
            <?php } ?>

            <?php if (!empty($asks['opt_out'])) { ?>
                <?php if ($consent_text !== '') { ?>
                    <?php /* Only the privacy-policy anchor is introduced above, so
                             the allowed tag list is deliberately narrow. */ ?>
                    <p class="summit-form__consent"><?= wp_kses($consent_text, ['a' => ['href' => [], 'target' => [], 'rel' => []]]); ?></p>
                <?php } ?>

                <div class="summit-form__option summit-form__option--consent">
                    <input class="summit-form__checkbox" type="checkbox" id="summit-opt-out" name="opt_out_marketing" value="1">
                    <label for="summit-opt-out">
                        <?= esc_html__('I\'d like to not to receive other marketing communications from Millboard.', 'granola'); ?>
                    </label>
                </div>
            <?php } ?>

            <?php /* Off-screen honeypot. Bots complete every field they can find;
                     a value here is answered with a bland success so there is
                     nothing to tune against. Hidden from assistive tech. */ ?>
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
            <h3 class="summit-form__success-heading" data-summit-success-heading><?= esc_html($args['success_heading']); ?></h3>
            <p class="summit-form__success-text" data-summit-success-text><?= esc_html($args['success_text']); ?></p>
        </div>

    </div>
</section>
