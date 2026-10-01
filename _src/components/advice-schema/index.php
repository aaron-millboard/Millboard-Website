<?php

/**
 * Advice schema, as the editor shows it. Nothing renders on the page itself;
 * see functions.php for why.
 */

?>
<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <p class="advice-schema__notice">
        <?= esc_html__('Advice schema: adds the stages, categories and authors on this template to the page\'s structured data. Nothing shows on the page.', 'granola'); ?>
    </p>
</div>
