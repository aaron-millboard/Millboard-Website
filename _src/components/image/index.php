<?= wp_kses_post(
    wp_get_attachment_image(
        $args['attachment_id'],
        $args['size'],
        false,
        $args['attributes']
    )
);
