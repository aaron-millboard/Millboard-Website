<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <div class="mega-menu-list__inner">
        <?php foreach ($args['columns'] as $column_index => $column_items) { ?>
            <div class="mega-menu-list__column">
                <ul class="mega-menu-list__items menu-list--depth-<?= $args['depth']; ?>">
                    <?php foreach ($column_items as $item) { ?>
                        <?= \Granola\Component::get('menu/mega-menu-item', [
                            'item' => $item,
                            'depth' => $args['depth'],
                            'max_depth' => $args['max_depth'],
                        ]); ?>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>

        <?php if (!empty($args['widget'])) { ?>
            <div class="mega-menu-list__column mega-menu-list__column--widget">
                <?php if (!empty($args['widget']['image'])) { ?>
                    <div class="mega-menu-list__widget__image">
                        <?= wp_get_attachment_image($args['widget']['image'], 'medium'); ?>
                    </div>
                <?php } ?>

                <?php if (!empty($args['widget']['cta'])) { ?>
                    <div class="mega-menu-list__widget__cta">
                        <?= \Granola\Component::get('link', [
                            'url' => $args['widget']['cta']['url'],
                            'content' => $args['widget']['cta']['title'],
                            'target' => $args['widget']['cta']['target'],
                            'classes' => ['mega-menu-list__widget__cta__button'],
                        ]); ?>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

    <?php if (!empty($args['cta'])) { ?>
        <div class="mega-menu-list__cta">
            <?= \Granola\Component::get('link', [
                'url' => $args['cta']['url'],
                'content' => $args['cta']['title'],
                'target' => $args['cta']['target'],
                'classes' => ['g-button', 'g-button--primary'],
            ]); ?>
        </div>
    <?php } ?>
</div>