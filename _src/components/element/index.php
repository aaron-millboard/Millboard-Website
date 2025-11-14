<<?= esc_html($args['el']); ?> <?= \Granola\Helpers::build_attributes($args['attributes']); ?>><?php
if (isset($args['content'])) {
    echo trim($args['content_filter']($args['content']));
    ?></<?= esc_html($args['el']); ?>>
<?php }
