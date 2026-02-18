<?php

namespace Granola\Components\PartnerContactForm;

function filter_args(array $args): ?array
{
    $args = array_merge([
        'classes' => [],
        'heading' => \_x('Contact us', 'Partner contact form heading', 'granola'),
        'hubspot_script' => '',
        'details' => [],
    ], $args);

    if (empty($args['hubspot_script']) && empty($args['is_preview'])) {
        return null;
    }

    $args['classes'] = array_merge([
        'partner-contact-form',
        'wp-block',
        'alignfull',
    ], $args['classes']);

    if (!empty($args['heading'])) {
        $args['heading'] = [
            'content' => $args['heading'],
            'classes' => [
                'partner-contact-form__heading',
                'is-style-typestyle-h3',
            ],
        ];
    }

    $post_id = get_the_ID();

    $phone = \get_field('phone', $post_id);
    if (!empty($phone)) {
        $args['details'][] = [
            'label' => \_x('Phone', 'Partner contact form table row heading', 'granola'),
            'value' => \Granola\Component::get('link', [
                'content' => $phone,
                'url' => 'tel:' . $phone,
            ]),
        ];
    }

    $email = \get_field('email', $post_id);
    if (!empty($email)) {
        $args['details'][] = [
            'label' => \_x('Email', 'Partner contact form table row heading', 'granola'),
            'value' => \Granola\Component::get('link', [
                'content' => $email,
                'url' => 'mailto:' . $email,
            ]),
        ];
    }

    $website = \get_field('website', $post_id);
    if (!empty($website)) {
        $website_parts = parse_url($website);
        $website_parts['path'] = rtrim($website_parts['path'], '/');

        $args['details'][] = [
            'label' => \_x('Website', 'Partner contact form table row heading', 'granola'),
            'value' => \Granola\Component::get('link', [
                'content' => $website_parts['host'] . $website_parts['path'],
                'url' => 'https://' . $website_parts['host'] . ($website_parts['path'] ?? ''),
                'attributes' => [
                    'rel' => 'noopener noreferrer',
                ]
            ]),
        ];
    }

    $address = \get_field('address', $post_id);
    if (!empty($address)) {
        $args['details'][] = [
            'label' => \_x('Address', 'Partner contact form table row heading', 'granola'),
            'value' => $address['address'] ?? '',
        ];
    }

    // -------------------------------------------------------------------------
    // Set up default placeholders in preview if none is provided
    // -------------------------------------------------------------------------
    if (!empty($args['is_preview'])) {
        if (empty($args['details'])) {
            $args['details'] = [
                [
                    'label' => \_x('Phone', 'Partner contact form table row heading', 'granola'),
                    'value' => \__('Enter Phone details', 'granola'),
                ],
                [
                    'label' => \_x('Email', 'Partner contact form table row heading', 'granola'),
                    'value' => \__('Enter Email details', 'granola'),
                ],
                [
                    'label' => \_x('Website', 'Partner contact form table row heading', 'granola'),
                    'value' => \__('Enter Website details', 'granola'),
                ],
                [
                    'label' => \_x('Address', 'Partner contact form table row heading', 'granola'),
                    'value' => \__('Enter Address details', 'granola'),
                ],
            ];
        }

        if (empty($args['hubspot_script'])) {
            $args['hubspot_script'] = \__('Add hubspot form here', 'granola');
        }
    }

    return $args;
}
