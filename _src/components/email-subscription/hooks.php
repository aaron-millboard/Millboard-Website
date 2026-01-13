<?php

namespace Granola\Components\EmailSubscription;

\add_filter('granola/component/email-subscription', __NAMESPACE__ . '\\filter_args');

\add_filter('gform_submit_button', function ($button, $form) {
    $form_id = absint(get_field('gravity_form_id', 'options') ?: 0);
    if ($form['id'] !== $form_id) {
        return $button;
    }

    $button_attributes = [];
    $button_text = \__('Submit', 'granola');
    $button_xml = simplexml_load_string($button);

    // Collate the existing button attributes into an array.
    foreach ($button_xml->Attributes() as $key => $value) {
        // Store the "value" attribute separately to use as button content.
        if ($key === 'value') {
            $button_text = $value;
        } else {
            $button_attributes[$key] = (string) $value;
        }
    }

    // Add class attribute if it's missing.
    if (empty($button_attributes['class'])) {
        $button_attributes['class'] = '';
    }

    // Append Granola button class.
    $button_attributes['class'] .= ' g-button';

    return \Granola\Component::get('button', [
        'content' => $button_text,
        'attributes' => $button_attributes,
    ]);
}, 10, 2);
