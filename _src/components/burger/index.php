<button <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['aria-label'])) { ?>
        <span class="visually-hidden"><?= esc_html($args['aria-label']); ?></span>
    <?php } ?>
    <span class="burger__line burger__line--1"></span>
    <span class="burger__line burger__line--2"></span>
    <span class="burger__line burger__line--3"></span>
</button>
