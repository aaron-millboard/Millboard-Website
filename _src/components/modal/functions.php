<?php

namespace Granola\Components\Modal;

function filter_args(array $args): ?array
{
    // -------------------------------------------------------------------------
    // Default arguments.
    // -------------------------------------------------------------------------
    $args = array_merge([
        'classes' => [],
        'attributes' => [],
        'id' => uniqid('modal-'),
        'content' => '',
        'close_click_outside' => false,
        'lock_scroll' => true,
        'overlay_classes' => [],
        'hash' => md5($args['content'] ?? ''),
        'cookie_lifecycle' => 3,
    ], $args);

    // -------------------------------------------------------------------------
    // Required classes.
    // -------------------------------------------------------------------------
    $args['classes'] = array_merge([
        'modal',
    ], $args['classes']);

    // -------------------------------------------------------------------------
    // Overlay classes.
    // -------------------------------------------------------------------------
    $args['overlay_classes'] = array_merge([
        'modal__overlay',
    ], $args['overlay_classes']);

    // Add overlay dismiss class if needed
    if (!empty($args['close_click_outside'])) {
        $args['overlay_classes'][] = 'modal__dismiss';
    }

    // -------------------------------------------------------------------------
    // Attributes.
    // -------------------------------------------------------------------------
    $args['attributes'] = array_merge([
        'role' => 'dialog',
        'aria-modal' => 'true',
        'tabindex' => '-1',
        'id' => $args['id'],
        'data-hash' => $args['hash'],
        'data-cookie' => $args['cookie_lifecycle'],
        'data-lock-scroll' => $args['lock_scroll'] ? 'true' : 'false',
    ], $args['attributes']);

    // -------------------------------------------------------------------------
    // Return the filtered args.
    // -------------------------------------------------------------------------
    return $args;
}
