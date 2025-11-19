<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <nav <?= \Granola\Helpers::build_attributes($args['inner_attributes']); ?>>
        <div class="anchor-links__inner has-<?= $args['background']; ?>-background-color">
            <?php if (!empty($args['heading'])) { ?>
                <?= \Granola\Component::get('heading', $args['heading']); ?>
            <?php } ?>

            <ul
                id="<?= esc_attr($args['expander_id']); ?>"
                class="anchor-links__items js-expandable-element list-reset--hard"
                aria-hidden="true"
                hidden
            >
                <?php foreach ($args['items'] as $key => $item) { ?>
                    <li class="anchor-links__item">
                        <?= \Granola\Component::get('link', $item); ?>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </nav>
</div>
