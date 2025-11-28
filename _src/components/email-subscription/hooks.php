<?php

namespace Granola\Components\EmailSubscription;

\add_filter('granola/component/email-subscription', __NAMESPACE__ . '\\filter_args');

\add_filter('gform_submit_button', function ($button, $form) {

    $form_id = absint(get_field('gravity_form_id', 'options') ?: 0);
    if ($form['id'] !== $form_id) {
        return $button;
    }
    $button_xml        = simplexml_load_string($button);
    $button_attributes = '';
    $button_text       = 'Submit';

    // combine the existing button attributes into a new string
    foreach ($button_xml->Attributes() as $key => $value) :
        // exclude the "type" and "value" attributes
        if ($key !== 'type' && $key !== 'value') :
            $button_attributes .= sprintf(' %s=\'%s\'', $key, $value);
        endif;

        // store the "value" attributes value
        if ($key === 'value') :
            $button_text = $value;
        endif;
    endforeach;

    // Chevron SVG icon
    $icon = \Granola\SVG::get('icons/chevron-right.svg');

    ob_start();
    ?>
        <button <?= $button_attributes; ?> aria-label="<?= esc_attr($button_text); ?>">
            <?= $icon; ?>
        </button>
        <?php
        $new_button = ob_get_clean();

        return $new_button;
}, 10, 2);
