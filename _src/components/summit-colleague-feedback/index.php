<?php
/**
 * Summit colleague feedback ("What did you hear?").
 *
 * Internal form from the "Colleague Summit Feedback (Internal)" design. The choices and their
 * stored values live in Theme\Summit\ColleagueFeedback, which is also what the endpoint checks
 * answers against; this template only draws them. English only.
 */

use Theme\Summit\ColleagueFeedback as C;

$ask = static function (string $n, string $text, bool $required = false, string $id = ''): void { ?>
    <span class="summit-colleague__q"<?= $id !== '' ? ' id="' . esc_attr($id) . '"' : ''; ?>>
        <span class="summit-colleague__n"><?= esc_html($n); ?>.</span> <?= esc_html($text); ?><?= $required ? '<span class="summit-colleague__req"> *</span>' : ''; ?>
    </span>
<?php };

$hint = static function (string $text): void { ?>
    <span class="summit-colleague__hint"><?= esc_html($text); ?></span>
<?php };

// One row of choice buttons. Real radio buttons or checkboxes, drawn as buttons.
$chips = static function (string $type, string $name, array $list, string $label_id = ''): void { ?>
    <div class="summit-colleague__chips" role="<?= $type === 'radio' ? 'radiogroup' : 'group'; ?>"<?= $label_id !== '' ? ' aria-labelledby="' . esc_attr($label_id) . '"' : ''; ?>>
        <?php foreach ($list as $label) { ?>
            <label class="summit-colleague__chip">
                <input type="<?= esc_attr($type); ?>" name="<?= esc_attr($name); ?>" value="<?= esc_attr($label); ?>"<?= $label === 'None specifically' ? ' data-none' : ''; ?>>
                <span><?= esc_html($label); ?></span>
            </label>
        <?php } ?>
    </div>
<?php };

$text = static function (string $name, string $placeholder = 'Optional', int $rows = 3, int $max = 2000): void { ?>
    <textarea class="summit-colleague__input" name="<?= esc_attr($name); ?>" rows="<?= (int) $rows; ?>" maxlength="<?= (int) $max; ?>" placeholder="<?= esc_attr($placeholder); ?>"></textarea>
<?php };

$step = static function (string $num, string $title): void { ?>
    <span class="summit-colleague__num"><?= esc_html($num); ?></span>
    <h2 class="summit-colleague__step-title"><?= esc_html($title); ?></h2>
<?php };
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="summit-colleague__inner">

        <?php if (!empty($args['logo_url'])) { ?>
            <img class="summit-colleague__logo" src="<?= esc_url($args['logo_url']); ?>" alt="Millboard Summit" width="92" height="94"
                 loading="eager" decoding="async" fetchpriority="high" data-spai-eager="true" data-no-lazy="1">
        <?php } ?>

        <div data-colleague-thanks hidden class="summit-colleague__thanks" tabindex="-1">
            <div class="summit-colleague__rule"></div>
            <h2 class="summit-colleague__title">Thank you</h2>
            <p class="summit-colleague__lead">Your feedback will be shared with the Summit team and used to shape future events. Any notes on individual attendees will be passed to the relevant team and logged in HubSpot, after which they will be deleted from this form.</p>
            <button type="button" class="summit-colleague__link" data-colleague-again>Submit another response</button>
        </div>

        <form class="summit-colleague__form" data-colleague-form
              data-endpoint="<?= esc_url(rest_url('millboard/v1/summit/colleague-feedback')); ?>"
              data-max="<?= (int) C::MAX_ATTENDEES; ?>"
              data-i18n="<?= esc_attr(wp_json_encode([
                  'sending' => 'Sending…',
                  'ready' => 'Ready to submit.',
                  'todo' => 'Still to answer: ',
                  'error' => 'Sorry, we could not send that. Please try again, or email enquiries@millboard.com.',
              ])); ?>"
              novalidate>

            <header class="summit-colleague__intro">
                <div class="summit-colleague__rule"></div>
                <p class="summit-colleague__badges">
                    <span class="summit-colleague__badge">Internal · Colleagues only</span>
                    <span>Attendee feedback · Colleague submission</span>
                </p>
                <?php /* No title here: the theme's page header prints the page title ("What did you hear?"). */ ?>
                <p class="summit-colleague__lead">Thank you for supporting the Millboard Summit. Please use this form to share the feedback you heard from attendees, whether in conversation on the day or afterwards. Honest feedback, positive and negative, is the most useful.</p>
                <p class="summit-colleague__small">You can submit one response per attendee conversation, or one summary covering several. It should take around <strong>five minutes</strong>.</p>
            </header>

            <div class="summit-colleague__progress" aria-live="polite">
                <div class="summit-colleague__progress-text">
                    <span>Required questions</span>
                    <span><span data-colleague-count>0</span> of <span data-colleague-total>8</span> answered</span>
                </div>
                <div class="summit-colleague__bar"><div class="summit-colleague__bar-fill" data-colleague-bar></div></div>
            </div>

            <!-- 01 About your feedback -->
            <section class="summit-colleague__step">
                <?php $step('01', 'About your feedback'); ?>
                <div class="summit-colleague__body">
                    <label class="summit-colleague__field">
                        <?php $ask('1', 'Your name', true); ?>
                        <input class="summit-colleague__input summit-colleague__input--narrow" type="text" name="name" maxlength="120" autocomplete="name">
                    </label>
                    <div class="summit-colleague__group">
                        <?php $ask('2', 'Your team', true, 'scf-q2'); $chips('radio', 'team', C::TEAMS, 'scf-q2'); ?>
                    </div>
                    <div class="summit-colleague__group">
                        <?php $ask('3', 'Is this response about one attendee or a summary of several?', true, 'scf-q3'); $chips('radio', 'scope', C::SCOPES, 'scf-q3'); ?>
                    </div>
                    <div class="summit-colleague__group">
                        <?php $ask('4', 'Which type of attendee(s) does this feedback relate to?', true, 'scf-q4'); $hint('Select all that apply.'); $chips('checkbox', 'types', C::TYPES, 'scf-q4'); ?>
                    </div>
                    <div class="summit-colleague__group">
                        <?php $ask('5', 'Market', true, 'scf-q5'); $chips('radio', 'market', C::MARKETS, 'scf-q5'); ?>
                    </div>
                </div>
            </section>

            <!-- 02 Overall impressions -->
            <section class="summit-colleague__step">
                <?php $step('02', 'Overall impressions'); ?>
                <div class="summit-colleague__body">
                    <div class="summit-colleague__group">
                        <?php $ask('6', 'Based on what you heard, how would attendees rate the Summit overall?', true, 'scf-q6'); ?>
                        <div class="summit-colleague__scale" role="radiogroup" aria-labelledby="scf-q6">
                            <?php for ($n = 1; $n <= 5; $n++) { ?>
                                <label class="summit-colleague__chip summit-colleague__chip--num">
                                    <input type="radio" name="overall" value="<?= $n; ?>">
                                    <span><?= $n; ?></span>
                                </label>
                            <?php } ?>
                        </div>
                        <div class="summit-colleague__ends"><span>Poor</span><span>Excellent</span></div>
                    </div>
                    <div class="summit-colleague__group">
                        <?php $ask('7', 'How likely are they to attend a future Millboard event?', false, 'scf-q7'); $chips('radio', 'again', C::AGAIN, 'scf-q7'); ?>
                    </div>
                    <label class="summit-colleague__field">
                        <?php $ask('8', 'In a sentence or two, what was the general feeling about the day?'); $text('feeling'); ?>
                    </label>
                </div>
            </section>

            <!-- 03 Content and sessions -->
            <section class="summit-colleague__step">
                <?php $step('03', 'Content and sessions'); ?>
                <div class="summit-colleague__body">
                    <label class="summit-colleague__field">
                        <?php $ask('9', 'Which session or moment did attendees mention most positively?'); $text('best'); ?>
                    </label>
                    <label class="summit-colleague__field">
                        <?php $ask('10', 'Was there anything attendees found less relevant, too long or unclear?'); $text('less'); ?>
                    </label>
                </div>
            </section>

            <!-- 04 Product and brand -->
            <section class="summit-colleague__step">
                <?php $step('04', 'Product and brand'); ?>
                <div class="summit-colleague__body">
                    <div class="summit-colleague__group">
                        <?php $ask('11', 'Which of the new products previewed at the Summit generated the most interest or questions?', false, 'scf-q11'); $hint('Select all that apply.'); $chips('checkbox', 'prods', C::PRODUCTS, 'scf-q11'); ?>
                    </div>
                    <label class="summit-colleague__field">
                        <?php $ask('12', 'Did attendees raise any questions, concerns or misconceptions about Millboard products we should address?'); $text('concerns'); ?>
                    </label>
                    <div class="summit-colleague__group">
                        <?php $ask('13', 'Has the Summit changed how attendees see Millboard as a brand?', false, 'scf-q13'); $chips('radio', 'brand', C::BRAND, 'scf-q13'); ?>
                    </div>
                </div>
            </section>

            <!-- 05 Commercial outcomes -->
            <section class="summit-colleague__step">
                <?php $step('05', 'Commercial outcomes'); ?>
                <div class="summit-colleague__body">
                    <div class="summit-colleague__group">
                        <?php $ask('14', 'Did any conversations lead to a potential opportunity?', false, 'scf-q14'); $chips('radio', 'opp', C::OPP, 'scf-q14'); ?>
                    </div>
                    <div class="summit-colleague__body" data-opp-follow hidden>
                        <div class="summit-colleague__group">
                            <?php $ask('15', 'If yes, has it been logged in HubSpot?', false, 'scf-q15'); $chips('radio', 'logged', C::LOGGED, 'scf-q15'); ?>
                        </div>
                        <label class="summit-colleague__field">
                            <?php $ask('16', 'Briefly describe any opportunities or follow-up actions'); $hint('No personal contact details, please.'); $text('opp_notes'); ?>
                        </label>
                    </div>
                </div>
            </section>

            <!-- 06 Notes on specific attendees -->
            <section class="summit-colleague__step">
                <?php $step('06', 'Notes on specific attendees'); ?>
                <div class="summit-colleague__body">
                    <div class="summit-colleague__callout">
                        <p><strong>Before you write:</strong> write notes as if the attendee might read them, because under UK GDPR they have the right to. Stick to business-relevant facts, no personal opinions, and no contact details or sensitive information (for example, health).</p>
                    </div>
                    <div class="summit-colleague__group">
                        <?php $ask('17', 'Do you have notes about a specific attendee that need to be shared internally?', true, 'scf-q17'); $chips('radio', 'notes', C::YESNO, 'scf-q17'); ?>
                    </div>

                    <div class="summit-colleague__attendees" data-attendees hidden>
                        <?php for ($i = 0; $i < C::MAX_ATTENDEES; $i++) { $p = 'a' . $i . '_'; ?>
                            <div class="summit-colleague__card" data-attendee="<?= $i; ?>"<?= $i > 0 ? ' hidden' : ''; ?>>
                                <p class="summit-colleague__card-title">Attendee <?= $i + 1; ?> of up to <?= (int) C::MAX_ATTENDEES; ?></p>
                                <div class="summit-colleague__grid">
                                    <label class="summit-colleague__field">
                                        <?php $ask('18', 'Attendee name', true); ?>
                                        <input class="summit-colleague__input" type="text" name="<?= $p; ?>name" maxlength="120">
                                    </label>
                                    <label class="summit-colleague__field">
                                        <?php $ask('19', 'Company'); ?>
                                        <input class="summit-colleague__input" type="text" name="<?= $p; ?>company" maxlength="120">
                                    </label>
                                </div>
                                <div class="summit-colleague__group">
                                    <?php $ask('20', 'Attendee type', false, $p . 'q20'); $chips('radio', $p . 'type', C::TYPES, $p . 'q20'); ?>
                                </div>
                                <div class="summit-colleague__group">
                                    <?php $ask('21', 'What does the note relate to?', false, $p . 'q21'); $chips('radio', $p . 'topic', C::TOPICS, $p . 'q21'); ?>
                                </div>
                                <label class="summit-colleague__field">
                                    <?php $ask('22', 'Note', true); $hint('Please keep it factual and professional.'); $text($p . 'note', '', 4); ?>
                                </label>
                                <div class="summit-colleague__group">
                                    <?php $ask('23', 'Who needs to see this?', false, $p . 'q23'); $hint('Select all that apply.'); $chips('checkbox', $p . 'who', C::WHO, $p . 'q23'); ?>
                                    <label class="summit-colleague__field summit-colleague__field--small" data-person hidden>
                                        <span>Please name the person</span>
                                        <input class="summit-colleague__input summit-colleague__input--narrow" type="text" name="<?= $p; ?>person" maxlength="120">
                                    </label>
                                </div>
                                <div class="summit-colleague__group">
                                    <?php $ask('24', 'How urgent is it?', false, $p . 'q24'); $chips('radio', $p . 'urgency', C::URGENCY, $p . 'q24'); ?>
                                </div>
                                <div class="summit-colleague__group">
                                    <?php $ask('25', 'Has this been logged against the contact in HubSpot?', false, $p . 'q25'); $chips('radio', $p . 'hubspot', C::HUBSPOT, $p . 'q25'); ?>
                                </div>
                                <?php if ($i < C::MAX_ATTENDEES - 1) { ?>
                                    <div class="summit-colleague__group summit-colleague__group--more">
                                        <?php $ask('26', 'Do you have notes on another attendee?', false, $p . 'q26'); $chips('radio', $p . 'more', C::YESNO, $p . 'q26'); ?>
                                    </div>
                                <?php } else { ?>
                                    <p class="summit-colleague__small summit-colleague__group--more">That’s the most one form can hold. For more attendees, submit this form, then fill it in again.</p>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </section>

            <!-- 07 Looking ahead -->
            <section class="summit-colleague__step summit-colleague__step--last">
                <?php $step('07', 'Looking ahead'); ?>
                <div class="summit-colleague__body">
                    <label class="summit-colleague__field">
                        <?php $ask('27', 'What would attendees like to see at a future Summit?'); $text('future'); ?>
                    </label>
                    <div class="summit-colleague__group">
                        <label class="summit-colleague__field">
                            <?php $ask('28', 'Any memorable quotes from attendees we could use internally or, with permission, in marketing?'); $text('quotes'); ?>
                        </label>
                        <?php $hint('Was permission given to use these in marketing?'); $chips('radio', 'permission', C::PERMISSION); ?>
                    </div>
                    <label class="summit-colleague__field">
                        <?php $ask('29', 'Anything else to add from your own perspective as a host?'); $text('extra'); ?>
                    </label>
                </div>
            </section>

            <div class="summit-colleague__actions">
                <button type="submit" class="summit-colleague__submit" data-colleague-submit disabled>Submit</button>
                <p class="summit-colleague__small" data-colleague-hint></p>
                <p class="summit-colleague__error" data-colleague-error role="alert" hidden></p>
            </div>
        </form>

        <noscript>
            <p class="summit-colleague__error">This form needs JavaScript. Please enable it, or email enquiries@millboard.com.</p>
        </noscript>

        <p class="summit-colleague__foot">For Millboard colleagues only. Please don’t share this form outside the business.</p>
    </div>
</section>
