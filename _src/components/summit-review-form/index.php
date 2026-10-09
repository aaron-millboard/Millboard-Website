<?php
/**
 * Summit pre-arrival form.
 *
 * Six steps a delegate completes before arriving. The questions are fixed here;
 * only the HubSpot wiring and the document links are editable. The script posts
 * to HubSpot's form submission endpoint, so it is required to submit.
 */

$docs = [
    'nda' => (string) ($args['nda_url'] ?? ''),
    'hs' => (string) ($args['health_safety_url'] ?? ''),
    'comp' => (string) ($args['competition_law_url'] ?? ''),
    'privacy' => (string) ($args['privacy_url'] ?? ''),
];

if (empty($args['has_form'])) { ?>
    <section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
        <p class="summit-review__error">
            <?= esc_html__('Set the HubSpot form GUID for this block. Without it the form does not appear on the page.', 'granola'); ?>
        </p>
    </section>
    <?php
    return;
}

// French pages swap their copy in through the gettext filter for the length of
// this render only; UK and INT never touch it.
$is_fr = !empty($args['is_fr']);
$translate = null;
if ($is_fr) {
    $translate = \Theme\Summit\ReviewStrings::gettext_filter();
    \add_filter('gettext', $translate, 10, 3);
}

$doc_link = static function (string $url, string $label): void {
    if ($url === '') {
        return;
    }
    echo '<a class="summit-review__doc" href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html(__($label, 'granola')) . '</a>';
};

$check = static function (string $name, string $text): void { ?>
    <label class="summit-review__choice">
        <span class="summit-review__box">
            <input type="checkbox" name="<?= esc_attr($name); ?>" data-required>
            <span class="summit-review__star" aria-hidden="true">*</span>
        </span>
        <?= esc_html(__($text, 'granola')); ?>
    </label>
<?php };

$radio = static function (string $name, string $value, string $text): void { ?>
    <label class="summit-review__choice">
        <input type="radio" name="<?= esc_attr($name); ?>" value="<?= esc_attr($value); ?>">
        <?= esc_html(__($text, 'granola')); ?>
    </label>
<?php };

$step = static function (string $n, string $title, string $key): void { ?>
    <div class="summit-review__step-head">
        <h2 class="summit-review__step-title"><?= esc_html(__($title, 'granola')); ?></h2>
        <span class="summit-review__done" data-done="<?= esc_attr($key); ?>" hidden><?= esc_html__('Complete', 'granola'); ?></span>
    </div>
<?php };

$yes_text = "Yes, I'd like to let you know about something";

// INT (international distributors) adds travel, Open Diary and two more details.
$is_int = !empty($args['is_int']);
$is_us = !empty($args['is_us']); // US is the UK form plus the agenda link
$is_travel = $is_int || $is_fr; // INT and FR delegates both give arrival flights
$audience = $is_fr ? 'FR' : ($is_int ? 'INT' : ($is_us ? 'US' : 'UK'));
$flights_contact = $is_fr ? 'luderic.geminard@millboard.com' : 'sam.cockeram@millboard.com';
$agenda = (string) ($args['agenda_url'] ?? '');
$phone = ($is_travel || $is_us) ? '+44 24 7643 9943' : '024 7643 9943'; // 024 is not dialable from abroad
$detail_fields = [
    ['firstname', 'First name', 'text', 'given-name', ''],
    ['lastname', 'Last name', 'text', 'family-name', ''],
    ['company', 'Company', 'text', 'organization', ''],
    ['email', 'Email', 'email', 'email', ''],
];
if ($is_int) {
    $detail_fields[] = ['summit_country', 'Country', 'text', 'country-name', ''];
}
if ($is_travel) {
    $detail_fields[] = ['summit_mobile', 'Mobile number', 'tel', 'tel', 'Including country code, e.g. +33'];
}
$depts = ['Operations', 'Technical', 'Events', 'Social Media', 'Digital Marketing', 'Website', 'Customer Care'];
$airports = ['London Heathrow (LHR)', 'Birmingham (BHX)', 'London Gatwick (LGW)', 'Manchester (MAN)', 'Other'];
$travel_fields = [
    ['summit_arrival_airline', 'Airline', 'text', 'e.g. Air France', true],
    ['summit_arrival_flight', 'Flight number', 'text', 'e.g. AF1680', true],
    ['summit_arrival_airport', 'Arrival airport', 'select', '', true],
    ['summit_arrival_date', 'Arrival date', 'date', '', true],
    ['summit_arrival_time', 'Arrival time (UK time)', 'time', '', true],
    ['summit_arrival_from', 'Departing from', 'text', 'e.g. Paris CDG', false],
];
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="summit-review__inner">

        <?php if (!empty($args['logo_url'])) { ?>
            <img class="summit-review__logo" src="<?= esc_url($args['logo_url']); ?>" alt="Millboard Summit" width="92" height="94"
                 loading="eager" decoding="async" fetchpriority="high" data-spai-eager="true" data-no-lazy="1">
        <?php } ?>

        <div data-review-thanks hidden class="summit-review__thanks" tabindex="-1">
            <div class="summit-review__rule"></div>
            <h2 class="summit-review__title" data-review-thanks-title></h2>
            <p class="summit-review__lead"><?= esc_html__('Thank you for confirming your attendance. We look forward to seeing you at the Millboard Summit!', 'granola'); ?></p>
            <p class="summit-review__small">
                <?= esc_html__('Questions?', 'granola'); ?>
                <a href="mailto:enquiries@millboard.com?subject=Millboard%20Summit%202026%20Enquiry">enquiries@millboard.com</a>
                <?= esc_html__('or', 'granola'); ?> <?= esc_html($phone); ?>
            </p>
        </div>

        <form class="summit-review__form" data-review-form
              data-portal="<?= esc_attr($args['hubspot_portal_id']); ?>"
              data-form="<?= esc_attr($args['hubspot_form_guid']); ?>"
              data-audience="<?= esc_attr($audience); ?>"
              data-i18n="<?= esc_attr(wp_json_encode([
                  'sending' => __('Sending…', 'granola'),
                  'thanks' => __('Thank you, ', 'granola'),
                  'ready' => __('Ready to submit.', 'granola'),
                  'todo' => __('Still to complete: ', 'granola'),
                  'details' => __('your details', 'granola'),
                  'travel' => __('your travel', 'granola'),
                  'error' => __('Sorry, we could not send that. Please try again, or email enquiries@millboard.com.', 'granola'),
              ])); ?>"
              data-endpoint="<?= esc_url(rest_url('millboard/v1/summit/review')); ?>" novalidate>

            <header class="summit-review__intro">
                <div class="summit-review__rule"></div>
                <p class="summit-review__lead">
                    <?= esc_html__('Six short steps, all on this page. Please complete them by no later than', 'granola'); ?><br>
                    <strong><?= esc_html($args['deadline']); ?></strong>
                    <?= esc_html__('so we can have everything ready for you.', 'granola'); ?>
                </p>
                <?php if ($is_travel || $is_us) { $doc_link($agenda, 'View the high level agenda (PDF)'); } ?>
            </header>

            <div class="summit-review__progress" aria-live="polite">
                <div class="summit-review__progress-text">
                    <span><?= esc_html__('Your progress', 'granola'); ?></span>
                    <span><span data-review-count>0</span> <?= esc_html__('of 6 complete', 'granola'); ?></span>
                </div>
                <div class="summit-review__bar"><div class="summit-review__bar-fill" data-review-bar></div></div>
            </div>

            <section class="summit-review__details">
                <div class="summit-review__step-head">
                    <h2 class="summit-review__step-title"><?= esc_html__('Your details', 'granola'); ?></h2>
                    <span class="summit-review__small"><strong>*</strong> <?= esc_html__('Required', 'granola'); ?></span>
                </div>
                <div class="summit-review__grid">
                    <?php foreach ($detail_fields as [$name, $label, $type, $auto, $hint]) { ?>
                        <label class="summit-review__field">
                            <span><?= esc_html(__($label, 'granola')); ?><strong> *</strong></span>
                            <input class="summit-review__input" type="<?= esc_attr($type); ?>" name="<?= esc_attr($name); ?>" autocomplete="<?= esc_attr($auto); ?>"<?= $hint !== '' ? ' placeholder="' . esc_attr(__($hint, 'granola')) . '"' : ''; ?>>
                        </label>
                    <?php } ?>
                </div>
            </section>

            <?php if ($is_travel) { ?>
            <section class="summit-review__travel">
                <div class="summit-review__step-head">
                    <h2 class="summit-review__step-title"><?= esc_html__('Your travel', 'granola'); ?></h2>
                    <span class="summit-review__done" data-done="travel" hidden><?= esc_html__('Complete', 'granola'); ?></span>
                </div>
                <p><?= esc_html__('Your arrival details help us plan around your arrival and contact you if anything changes. Transport during the event is arranged by us.', 'granola'); ?></p>
                <label class="summit-review__choice">
                    <input type="checkbox" name="summit_flights_not_booked">
                    <?= esc_html(sprintf(__("I haven't booked my flight yet. I'll send them to %s once booked.", 'granola'), $flights_contact)); ?>
                </label>
                <div class="summit-review__flights" data-flights>
                    <h3 class="summit-review__sub"><?= esc_html__('Arriving in the UK', 'granola'); ?></h3>
                    <div class="summit-review__grid">
                        <?php foreach ($travel_fields as [$name, $label, $type, $hint, $req]) { ?>
                            <label class="summit-review__field">
                                <span><?= esc_html(__($label, 'granola')); ?><?= $req ? '<strong> *</strong>' : ''; ?></span>
                                <?php if ($type === 'select') { ?>
                                    <select class="summit-review__input" name="<?= esc_attr($name); ?>">
                                        <option value=""><?= esc_html__('Select', 'granola'); ?></option>
                                        <?php foreach ($airports as $airport) { ?>
                                            <option value="<?= esc_attr($airport); ?>"><?= esc_html(__($airport, 'granola')); ?></option>
                                        <?php } ?>
                                    </select>
                                <?php } else { ?>
                                    <input class="summit-review__input" type="<?= esc_attr($type); ?>" name="<?= esc_attr($name); ?>"<?= $hint !== '' ? ' placeholder="' . esc_attr(__($hint, 'granola')) . '"' : ''; ?>>
                                <?php } ?>
                            </label>
                        <?php } ?>
                    </div>
                </div>
            </section>
            <?php } ?>

            <section class="summit-review__step">
                <span class="summit-review__num">01</span>
                <div class="summit-review__body">
                    <?php $step('01', 'Non-Disclosure Agreement', 'nda'); ?>
                    <p><?= esc_html__("During the Summit we'll share products and plans ahead of launch. Please read and sign our NDA before attending.", 'granola'); ?></p>
                    <?php $doc_link($docs['nda'], 'Read the NDA (PDF)'); ?>
                    <?php $check('summit_nda_accepted', 'I have read and agree to the Millboard Summit Non-Disclosure Agreement (NDA) on behalf of myself and my business.'); ?>
                </div>
            </section>

            <section class="summit-review__step">
                <span class="summit-review__num">02</span>
                <div class="summit-review__body">
                    <?php $step('02', 'Health and Safety Information', 'hs'); ?>
                    <p><?= esc_html__("For the manufacturing site visit, please wear closed-toe flat shoes and follow your host's guidance at all times. Any PPE needed will be provided on the day.", 'granola'); ?></p>
                    <?php $doc_link($docs['hs'], 'Read the Health and Safety Information (PDF)'); ?>
                    <?php $check('summit_hs_accepted', 'I have read and understood the Health and Safety Information.'); ?>
                </div>
            </section>

            <section class="summit-review__step">
                <span class="summit-review__num">03</span>
                <div class="summit-review__body">
                    <?php $step('03', 'Dietary and accessibility requirements', 'needs'); ?>

                    <h3 class="summit-review__sub"><?= esc_html__('Dietary requirements and allergies', 'granola'); ?></h3>
                    <p><?= esc_html__('Do you have any dietary requirements or allergies?', 'granola'); ?><strong> *</strong></p>
                    <?php $radio('diet', 'no', 'No requirements'); ?>
                    <?php $radio('diet', 'yes', $yes_text); ?>
                    <div class="summit-review__more" data-show-when="diet=yes" hidden>
                        <p><?= esc_html__('Please select all that apply.', 'granola'); ?></p>
                        <div class="summit-review__chips">
                            <?php foreach (['Vegetarian', 'Vegan', 'Gluten free', 'Dairy free', 'Nut allergy', 'Other'] as $chip) { ?>
                                <button type="button" class="summit-review__chip" data-chip="<?= esc_attr($chip); ?>" aria-pressed="false"><?= esc_html(__($chip, 'granola')); ?></button>
                            <?php } ?>
                        </div>
                        <label class="summit-review__field">
                            <?= esc_html__('Anything else we should know?', 'granola'); ?>
                            <textarea class="summit-review__input" rows="3" name="summit_dietary_notes" placeholder="<?= esc_attr__('For example, severity of an allergy', 'granola'); ?>"></textarea>
                        </label>
                    </div>

                    <h3 class="summit-review__sub summit-review__sub--split"><?= esc_html__('Accessibility and mobility', 'granola'); ?></h3>
                    <p><?= esc_html__('We want everyone to be comfortable and fully involved. The site visit includes some walking and time on your feet.', 'granola'); ?></p>
                    <p><?= esc_html__('Do you have any access, mobility or other requirements we can help with during the event?', 'granola'); ?><strong> *</strong></p>
                    <?php $radio('access', 'no', 'No requirements'); ?>
                    <?php $radio('access', 'yes', $yes_text); ?>
                    <label class="summit-review__field" data-show-when="access=yes" hidden>
                        <?= esc_html__('Please tell us what would help', 'granola'); ?>
                        <textarea class="summit-review__input" rows="3" name="summit_accessibility_requirements" placeholder="<?= esc_attr__('For example, step-free access, seating during the tour or a quiet space', 'granola'); ?>"></textarea>
                    </label>
                    <p class="summit-review__small"><?= esc_html__('This information is kept confidential and shared only with the team organising your visit.', 'granola'); ?></p>

                    <div class="summit-review__consent" data-show-when="needs=yes" hidden>
                        <?php $check('summit_health_data_consent', 'I consent to Millboard using the dietary and accessibility information I give to make arrangements for me at the Summit.'); ?>
                    </div>
                </div>
            </section>

            <section class="summit-review__step">
                <span class="summit-review__num">04</span>
                <div class="summit-review__body">
                    <?php $step('04', 'Photography and filming', 'photo'); ?>
                    <p><?= esc_html__("We'll be capturing moments from the Summit for internal and marketing use.", 'granola'); ?></p>
                    <p><?= esc_html__('Please choose one option.', 'granola'); ?><strong> *</strong></p>
                    <?php $radio('summit_photo_consent', 'Yes', "I'm happy to be photographed or filmed and for images of me to be used for the purpose noted above."); ?>
                    <?php $radio('summit_photo_consent', 'No', "Please don't photograph or film me."); ?>
                </div>
            </section>

            <section class="summit-review__step">
                <span class="summit-review__num">05</span>
                <div class="summit-review__body">
                    <?php $step('05', 'Competition Law guidelines', 'comp'); ?>
                    <p><?= esc_html__('The Summit brings together people from across our network, so we ask everyone to agree to our Competition Law guidelines before attending.', 'granola'); ?></p>
                    <?php $doc_link($docs['comp'], 'Read the Competition Law guidelines (PDF)'); ?>
                    <?php $check('summit_competition_law_accepted', 'I have read and agree to the Competition Law guidelines.'); ?>
                </div>
            </section>

            <section class="summit-review__step summit-review__step--last">
                <span class="summit-review__num">06</span>
                <div class="summit-review__body">
                    <?php $step('06', 'Your data', 'data'); ?>
                    <p>
                        <?= esc_html__("We'll use the details on this page only to organise your attendance at the Summit.", 'granola'); ?>
                        <?php if ($docs['privacy'] !== '') { ?>
                            <a href="<?= esc_url($docs['privacy']); ?>" target="_blank" rel="noopener"><?= esc_html__('Read our privacy policy (PDF)', 'granola'); ?></a>.
                        <?php } ?>
                    </p>
                    <?php $check('summit_data_consent', 'I consent to Millboard processing my personal data for the purpose of my attendance at the event.'); ?>
                </div>
            </section>

            <?php if ($is_int) { ?>
            <section class="summit-review__diary">
                <h2 class="summit-review__title summit-review__title--sub"><?= esc_html__('Book your Open Diary sessions', 'granola'); ?></h2>
                <p><?= esc_html__("We are pleased to include an Open Diary session in this year's Global Summit programme. It offers dedicated time with Millboard's specialist teams to discuss topics relevant to your market.", 'granola'); ?></p>
                <dl class="summit-review__facts">
                    <div><dt><?= esc_html__('When', 'granola'); ?></dt><dd><?= esc_html__('Wednesday 4 November', 'granola'); ?></dd></div>
                    <div><dt><?= esc_html__('Format', 'granola'); ?></dt><dd><?= esc_html__('20-minute one-to-one meetings with individual departments', 'granola'); ?></dd></div>
                </dl>
                <p><?= esc_html__('Please tick each department you would like to meet.', 'granola'); ?></p>
                <div class="summit-review__depts">
                    <?php foreach ($depts as $dept) { ?>
                        <label class="summit-review__choice">
                            <input type="checkbox" name="diary_dept" value="<?= esc_attr($dept); ?>">
                            <?= esc_html($dept); ?>
                        </label>
                    <?php } ?>
                </div>
                <div class="summit-review__notes" data-diary-notes hidden>
                    <p><?= esc_html__('To help each team prepare, please add a brief note of the topics you would like to cover.', 'granola'); ?></p>
                    <?php foreach ($depts as $dept) { ?>
                        <label class="summit-review__field" data-diary-note="<?= esc_attr($dept); ?>" hidden>
                            <span><?= esc_html($dept); ?></span>
                            <textarea class="summit-review__input" rows="2" placeholder="<?= esc_attr('Topics to discuss with ' . $dept); ?>"></textarea>
                        </label>
                    <?php } ?>
                </div>
                <p><?= esc_html__('Slots are allocated in order of response, and we will confirm your meeting times before the day.', 'granola'); ?></p>
            </section>
            <?php } ?>

            <div class="summit-review__actions">
                <button type="submit" class="summit-review__submit" data-review-submit disabled><?= esc_html__('Submit', 'granola'); ?></button>
                <p class="summit-review__small" data-review-hint></p>
                <p class="summit-review__error" data-review-error role="alert" hidden></p>
            </div>
        </form>

        <noscript>
            <p class="summit-review__error"><?= esc_html__('This form needs JavaScript. Please enable it, or email enquiries@millboard.com.', 'granola'); ?></p>
        </noscript>
    </div>
</section>
<?php
if ($translate) {
    \remove_filter('gettext', $translate, 10);
}
