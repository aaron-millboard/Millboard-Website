<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (!empty($args['label'])) { ?>
        <div class="taxonomy-filters__label-wrapper">
            <?= \Granola\Component::get('element', [
                'content' => $args['label'],
                'classes' => [
                    'taxonomy-filters__label',
                ],
            ]); ?>
        </div>
    <?php } ?>

    <ul class="taxonomy-filters__list flex-list--auto">
        <?php foreach ($args['items'] as $item) { ?>
            <li class="taxonomy-filters__item-wrap">
                <?php if (!empty($item['image'])) { ?>
                    <a href="<?= esc_url($item['url']); ?>" class="<?= esc_attr(implode(' ', $item['classes'])); ?>">
                        <img src="<?= esc_url($item['image']); ?>" alt="<?= esc_attr($item['image_alt']); ?>" class="taxonomy-filters__image" />
                        <span><?= esc_html($item['title']); ?></span>
                    </a>
                <?php } else { ?>
                    <?= \Granola\Component::get('link', $item); ?>
                <?php } ?>
            </li>
        <?php } ?>
    </ul>
</div>
