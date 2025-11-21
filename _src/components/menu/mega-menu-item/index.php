<li <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <a href="<?= esc_url($args['link']['url']); ?>" 
       class="mega-menu-item__card"
       <?php if (!empty($args['link']['target'])) { ?>target="<?= esc_attr($args['link']['target']); ?>"<?php } ?>
       <?php if (!empty($args['link']['attributes']['title'])) { ?>title="<?= esc_attr($args['link']['attributes']['title']); ?>"<?php } ?>>
        
        <?php if (!empty($args['item_image'])) { ?>
            <div class="mega-menu-item__image">
                <?= wp_get_attachment_image($args['item_image'], 'thumbnail'); ?>
            </div>
        <?php } ?>

        <div class="mega-menu-item__content">
            <span class="mega-menu-item__title">
                <?= esc_html($args['link']['content']); ?>
            </span>

            <?php if (!empty($args['description'])) { ?>
                <span class="mega-menu-item__description">
                    <?= esc_html($args['description']); ?>
                </span>
            <?php } ?>
        </div>
    </a>

    <?php if (!empty($args['has_children'])) { ?>
        <ul class="mega-menu-item__children menu-list menu-list--depth-<?= $args['depth'] + 1; ?>">
            <?php foreach ($args['item']->children as $child) { ?>
                <li class="menu-item menu-item--depth-<?= $args['depth'] + 1; ?>">
                    <?= \Granola\Component::get('link', [
                        'url' => $child->url,
                        'content' => $child->title,
                        'target' => $child->target ?: null,
                    ]); ?>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>
</li>
