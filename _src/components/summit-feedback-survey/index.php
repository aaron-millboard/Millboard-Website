<?php
/**
 * Summit feedback survey.
 *
 * Eight questions, from the "Summit Feedback Survey" design. The questions and
 * their stored values live in Theme\Summit\Feedback, which is also what the
 * endpoint checks answers against; this template only draws them. English and
 * French copy sit side by side in $t(english, french) so a reviewer can read
 * one against the other. Labels are translated; stored answers are always English.
 */

use Theme\Summit\Feedback;

$fr = !empty($args['is_fr']);
$t = static fn(string $en, string $french): string => $fr ? $french : $en;
$label = static fn(array $pair): string => $fr ? $pair[1] : $pair[0]; // [value, French] pairs: English label is the value
$audience = $fr ? 'FR' : 'UK';
$phone = $fr ? '+44 24 7643 9943' : '024 7643 9943'; // 024 is not dialable from France
$closing = trim((string) ($args['closing_date'] ?? ''));
$privacy = (string) ($args['privacy_url'] ?? '');
$req = '<span class="summit-feedback__req"> *</span>';

// A 0 to 10 row of radio buttons drawn as pills, with the two end labels underneath.
$scale = static function (string $name, string $q, string $low, string $high): void { ?>
    <div class="summit-feedback__scale" role="radiogroup" aria-labelledby="<?= esc_attr($q); ?>">
        <?php for ($n = 0; $n <= 10; $n++) { ?>
            <label class="summit-feedback__pill">
                <input type="radio" name="<?= esc_attr($name); ?>" value="<?= $n; ?>">
                <span><?= $n; ?></span>
            </label>
        <?php } ?>
    </div>
    <div class="summit-feedback__ends"><span><?= esc_html($low); ?></span><span><?= esc_html($high); ?></span></div>
<?php };

$prod_scale = $fr
    ? ['1 Médiocre', '2 Passable', '3 Bien', '4 Très bien', '5 Excellent']
    : ['1 Poor', '2 Fair', '3 Good', '4 Very good', '5 Excellent'];
?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="summit-feedback__inner">

        <?php if (!empty($args['logo_url'])) { ?>
            <img class="summit-feedback__logo" src="<?= esc_url($args['logo_url']); ?>" alt="Millboard Summit" width="92" height="94"
                 loading="eager" decoding="async" fetchpriority="high" data-spai-eager="true" data-no-lazy="1">
        <?php } ?>

        <div data-feedback-thanks hidden class="summit-feedback__thanks" tabindex="-1">
            <div class="summit-feedback__rule"></div>
            <h2 class="summit-feedback__title"><?= esc_html($t('Thank you', 'Merci')); ?></h2>
            <p class="summit-feedback__lead"><?= esc_html($t(
                'Your feedback has been received. We will use it to improve future events and to shape how we develop and launch our new products.',
                'Vos retours nous sont bien parvenus. Nous nous en servirons pour améliorer nos prochains événements et pour orienter le développement et le lancement de nos nouveaux produits.'
            )); ?></p>
            <p class="summit-feedback__small">
                <?= esc_html($t('Questions?', 'Des questions ?')); ?>
                <a href="mailto:enquiries@millboard.com?subject=Millboard%20Summit%20-%20feedback">enquiries@millboard.com</a>
                <?= esc_html($t('or', 'ou')); ?> <?= esc_html($phone); ?>
            </p>
        </div>

        <form class="summit-feedback__form" data-feedback-form
              data-audience="<?= esc_attr($audience); ?>"
              data-endpoint="<?= esc_url(rest_url('millboard/v1/summit/feedback')); ?>"
              data-i18n="<?= esc_attr(wp_json_encode([
                  'sending' => $t('Sending…', 'Envoi…'),
                  'ready' => $t('Ready to submit.', 'Prêt à envoyer.'),
                  'todo' => $t('Still to answer: ', 'Reste à compléter : '),
                  'error' => $t('Sorry, we could not send that. Please try again, or email enquiries@millboard.com.', 'Désolé, nous n’avons pas pu envoyer vos réponses. Merci de réessayer, ou d’écrire à enquiries@millboard.com.'),
              ], JSON_UNESCAPED_UNICODE)); ?>"
              novalidate>

            <header class="summit-feedback__intro">
                <div class="summit-feedback__rule"></div>
                <?php /* No title here: the theme's page header prints the page title ("How was the Summit?"), as on the pre-arrival form. */ ?>
                <p class="summit-feedback__lead">
                    <?= esc_html($t(
                        'Eight short questions, around 3 minutes. Your answers help us improve future events and shape how we develop and launch our new products.',
                        'Huit questions rapides, environ 3 minutes. Vos réponses nous aident à améliorer nos prochains événements et à orienter le développement et le lancement de nos nouveaux produits.'
                    )); ?>
                    <?php if ($closing !== '') { ?>
                        <?= esc_html($t('The survey closes on', 'Le questionnaire est ouvert jusqu’au')); ?> <strong><?= esc_html($closing); ?></strong>.
                    <?php } ?>
                </p>
            </header>

            <div class="summit-feedback__progress" aria-live="polite">
                <div class="summit-feedback__progress-text">
                    <span><?= esc_html($t('Your progress', 'Votre progression')); ?></span>
                    <span><span data-feedback-count>0</span> <?= esc_html($t('of 8 answered', 'sur 8 complétées')); ?></span>
                </div>
                <div class="summit-feedback__bar"><div class="summit-feedback__bar-fill" data-feedback-bar></div></div>
            </div>

            <!-- 01 Overall -->
            <section class="summit-feedback__step">
                <span class="summit-feedback__num">01</span>
                <div class="summit-feedback__body">
                    <h2 class="summit-feedback__step-title"><?= esc_html($t('Overall', 'Vue d’ensemble')); ?></h2>
                    <p id="sf-q1"><?= esc_html($t('How would you rate the Summit overall?', 'Comment évaluez-vous le Summit dans son ensemble ?')); ?><?= $req; ?></p>
                    <?php $scale('overall', 'sf-q1', $t('Poor', 'Médiocre'), $t('Excellent', 'Excellent')); ?>
                </div>
            </section>

            <!-- 02 What you took away -->
            <section class="summit-feedback__step">
                <span class="summit-feedback__num">02</span>
                <div class="summit-feedback__body">
                    <h2 class="summit-feedback__step-title"><?= esc_html($t('What you took away', 'Ce que vous retenez')); ?></h2>
                    <p><?= esc_html($t('How far do you agree with each statement?', 'Dans quelle mesure êtes-vous d’accord avec chaque affirmation ?')); ?><?= $req; ?></p>
                    <div class="summit-feedback__rows">
                        <?php foreach (Feedback::STATEMENTS as $key => $s) { $id = 'sf-obj-' . $key; ?>
                            <div class="summit-feedback__row">
                                <p id="<?= esc_attr($id); ?>"><?= esc_html($label($s)); ?></p>
                                <div class="summit-feedback__agree" role="radiogroup" aria-labelledby="<?= esc_attr($id); ?>">
                                    <?php foreach (Feedback::AGREE as $a) { ?>
                                        <label class="summit-feedback__pill summit-feedback__pill--wide">
                                            <input type="radio" name="obj_<?= esc_attr($key); ?>" value="<?= esc_attr($a[0]); ?>">
                                            <span><?= esc_html($label($a)); ?></span>
                                        </label>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </section>

            <!-- 03 Relevance -->
            <section class="summit-feedback__step">
                <span class="summit-feedback__num">03</span>
                <div class="summit-feedback__body">
                    <h2 class="summit-feedback__step-title"><?= esc_html($t('Relevance', 'Pertinence')); ?></h2>
                    <p id="sf-q3"><?= esc_html($t('How relevant was the Summit content to your business?', 'Dans quelle mesure le contenu du Summit était-il pertinent pour votre activité ?')); ?><?= $req; ?></p>
                    <?php $scale('relevance', 'sf-q3', $t('Not relevant', 'Pas pertinent'), $t('Highly relevant', 'Très pertinent')); ?>
                </div>
            </section>

            <!-- 04 New products -->
            <section class="summit-feedback__step">
                <span class="summit-feedback__num">04</span>
                <div class="summit-feedback__body">
                    <h2 class="summit-feedback__step-title"><?= esc_html($t('New products', 'Nouveaux produits')); ?></h2>
                    <p><?= esc_html($t('How would you rate the new products we previewed at the Summit?', 'Comment évaluez-vous les nouveaux produits présentés en avant-première lors du Summit ?')); ?><?= $req; ?></p>
                    <p class="summit-feedback__hint"><?= esc_html($t('If you didn’t see a product, choose “Didn’t see”.', 'Si vous n’avez pas vu un produit, choisissez « Pas vu ».')); ?></p>
                    <div class="summit-feedback__products">
                        <?php foreach (Feedback::PRODUCTS as $key => $p) { $id = 'sf-prod-' . $key; $note = $fr ? $p[4] : $p[3]; ?>
                            <div class="summit-feedback__product">
                                <div class="summit-feedback__product-name">
                                    <p id="<?= esc_attr($id); ?>"><?= esc_html($fr ? $p[2] : $p[1]); ?></p>
                                    <?php if ($note !== '') { ?><p class="summit-feedback__note"><?= esc_html($note); ?></p><?php } ?>
                                </div>
                                <div class="summit-feedback__product-rate" role="radiogroup" aria-labelledby="<?= esc_attr($id); ?>">
                                    <div class="summit-feedback__rate">
                                        <div class="summit-feedback__rate-row">
                                            <?php for ($n = 1; $n <= 5; $n++) { ?>
                                                <label class="summit-feedback__pill summit-feedback__pill--round">
                                                    <input type="radio" name="prod_<?= esc_attr($key); ?>" value="<?= $n; ?>" aria-label="<?= esc_attr($prod_scale[$n - 1]); ?>">
                                                    <span><?= $n; ?></span>
                                                </label>
                                            <?php } ?>
                                        </div>
                                        <div class="summit-feedback__ends summit-feedback__ends--small"><span><?= esc_html($t('Poor', 'Médiocre')); ?></span><span><?= esc_html($t('Excellent', 'Excellent')); ?></span></div>
                                    </div>
                                    <label class="summit-feedback__pill summit-feedback__pill--skip">
                                        <input type="radio" name="prod_<?= esc_attr($key); ?>" value="Didn't see">
                                        <span><?= esc_html($t('Didn’t see', 'Pas vu')); ?></span>
                                    </label>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <p id="sf-q4b" class="summit-feedback__split"><?= esc_html($t('Which of these are you most likely to sell, specify or recommend?', 'Lesquels de ces produits êtes-vous le plus susceptible de vendre, de prescrire ou de recommander ?')); ?><?= $req; ?></p>
                    <p class="summit-feedback__hint"><?= esc_html($t('Select all that apply.', 'Sélectionnez toutes les réponses applicables.')); ?></p>
                    <div class="summit-feedback__chips" role="group" aria-labelledby="sf-q4b">
                        <?php foreach (Feedback::PRODUCTS as $p) { ?>
                            <label class="summit-feedback__chip">
                                <input type="checkbox" name="interest" value="<?= esc_attr($p[0]); ?>">
                                <span><?= esc_html($p[0]); ?></span>
                            </label>
                        <?php } ?>
                        <label class="summit-feedback__chip">
                            <input type="checkbox" name="interest" value="<?= esc_attr(Feedback::NONE_OPTION[0]); ?>" data-none>
                            <span><?= esc_html($fr ? Feedback::NONE_OPTION[2] : Feedback::NONE_OPTION[1]); ?></span>
                        </label>
                    </div>

                    <label class="summit-feedback__field summit-feedback__split">
                        <span><?= esc_html($t('Any comments on the new products?', 'Avez-vous des commentaires sur les nouveaux produits ?')); ?></span>
                        <textarea class="summit-feedback__input" name="prod_notes" rows="3" maxlength="2000" placeholder="<?= esc_attr($t('Optional', 'Facultatif')); ?>"></textarea>
                    </label>
                </div>
            </section>

            <!-- 05 Most valuable session -->
            <section class="summit-feedback__step">
                <span class="summit-feedback__num">05</span>
                <div class="summit-feedback__body">
                    <h2 class="summit-feedback__step-title"><?= esc_html($t('Most valuable session', 'Moment le plus utile')); ?></h2>
                    <label class="summit-feedback__field summit-feedback__field--lg">
                        <span><?= esc_html($t('Which part of the Summit was most valuable to you?', 'Quel moment du Summit vous a été le plus utile ?')); ?><?= $req; ?></span>
                        <select class="summit-feedback__input summit-feedback__input--select" name="session">
                            <option value=""><?= esc_html($t('Please select', 'Sélectionner')); ?></option>
                            <?php foreach (Feedback::SESSIONS as $s) { ?>
                                <option value="<?= esc_attr($s[0]); ?>"><?= esc_html($label($s)); ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <label class="summit-feedback__field summit-feedback__other" data-session-other hidden>
                        <span><?= esc_html($t('Please tell us which', 'Précisez lequel')); ?></span>
                        <input class="summit-feedback__input" type="text" name="session_other" maxlength="200">
                    </label>
                </div>
            </section>

            <!-- 06 Next time -->
            <section class="summit-feedback__step">
                <span class="summit-feedback__num">06</span>
                <div class="summit-feedback__body">
                    <h2 class="summit-feedback__step-title"><?= esc_html($t('Next time', 'La prochaine fois')); ?></h2>
                    <p id="sf-q6"><?= esc_html($t('How likely are you to attend a future Millboard Summit?', 'Quelle est la probabilité que vous participiez à un prochain Millboard Summit ?')); ?><?= $req; ?></p>
                    <?php $scale('again', 'sf-q6', $t('Not at all likely', 'Pas du tout probable'), $t('Extremely likely', 'Extrêmement probable')); ?>
                </div>
            </section>

            <!-- 07 One thing -->
            <section class="summit-feedback__step">
                <span class="summit-feedback__num">07</span>
                <div class="summit-feedback__body">
                    <h2 class="summit-feedback__step-title"><?= esc_html($t('One thing', 'Une seule chose')); ?></h2>
                    <label class="summit-feedback__field summit-feedback__field--lg">
                        <span><?= esc_html($t('What one thing would make the next Summit better?', 'Quelle seule chose améliorerait le prochain Summit ?')); ?></span>
                        <textarea class="summit-feedback__input" name="improve" rows="4" maxlength="2000" placeholder="<?= esc_attr($t('Optional', 'Facultatif')); ?>"></textarea>
                    </label>
                </div>
            </section>

            <!-- 08 About you -->
            <section class="summit-feedback__step summit-feedback__step--last">
                <span class="summit-feedback__num">08</span>
                <div class="summit-feedback__body">
                    <h2 class="summit-feedback__step-title"><?= esc_html($t('About you', 'À propos de vous')); ?></h2>
                    <label class="summit-feedback__field summit-feedback__field--lg">
                        <span><?= esc_html($t('Which market do you represent?', 'Quel marché représentez-vous ?')); ?><?= $req; ?></span>
                        <select class="summit-feedback__input summit-feedback__input--select" name="market">
                            <option value=""><?= esc_html($t('Please select', 'Sélectionner')); ?></option>
                            <?php foreach (Feedback::MARKETS as $m) { ?>
                                <option value="<?= esc_attr($m[0]); ?>"><?= esc_html($label($m)); ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <label class="summit-feedback__field summit-feedback__field--lg">
                        <span><?= esc_html($t('Which best describes your business?', 'Quelle description correspond le mieux à votre activité ?')); ?><?= $req; ?></span>
                        <select class="summit-feedback__input summit-feedback__input--select" name="role">
                            <option value=""><?= esc_html($t('Please select', 'Sélectionner')); ?></option>
                            <?php foreach (Feedback::ROLES as $r) { ?>
                                <option value="<?= esc_attr($r[0]); ?>"><?= esc_html($label($r)); ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <p class="summit-feedback__follow"><?= esc_html($t(
                        'Happy for us to follow up on your feedback? Add your details below. This is optional.',
                        'Vous acceptez que nous revenions vers vous au sujet de vos retours ? Indiquez vos coordonnées ci-dessous. C’est facultatif.'
                    )); ?></p>
                    <div class="summit-feedback__grid">
                        <label class="summit-feedback__field">
                            <span><?= esc_html($t('Name', 'Nom')); ?></span>
                            <input class="summit-feedback__input" type="text" name="name" maxlength="120" autocomplete="name">
                        </label>
                        <label class="summit-feedback__field">
                            <span><?= esc_html($t('Email', 'E-mail')); ?></span>
                            <input class="summit-feedback__input" type="email" name="email" maxlength="190" autocomplete="email">
                        </label>
                    </div>
                    <p class="summit-feedback__small">
                        <?= esc_html($t('If you add your details, we will use them only to follow up on this feedback.', 'Si vous indiquez vos coordonnées, nous les utiliserons uniquement pour donner suite à ces retours.')); ?>
                        <?php if ($privacy !== '') { ?>
                            <a href="<?= esc_url($privacy); ?>" target="_blank" rel="noopener"><?= esc_html($t('Read our privacy policy (PDF)', 'Lire notre avis de confidentialité (PDF)')); ?></a>.
                        <?php } ?>
                    </p>
                </div>
            </section>

            <div class="summit-feedback__actions">
                <button type="submit" class="summit-feedback__submit" data-feedback-submit disabled><?= esc_html($t('Submit', 'Envoyer')); ?></button>
                <p class="summit-feedback__small" data-feedback-hint></p>
                <p class="summit-feedback__error" data-feedback-error role="alert" hidden></p>
            </div>
        </form>

        <noscript>
            <p class="summit-feedback__error"><?= esc_html($t('This survey needs JavaScript. Please enable it, or email enquiries@millboard.com.', 'Ce questionnaire nécessite JavaScript. Merci de l’activer, ou d’écrire à enquiries@millboard.com.')); ?></p>
        </noscript>
    </div>
</section>
