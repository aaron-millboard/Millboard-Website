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

    <ul class="taxonomy-filters__list flex-list">
        <?php foreach ($args['items'] as $item) { ?>
            <li class="taxonomy-filters__item-wrap">
                <?= \Granola\Component::get('link', $item); ?>
            </li>
        <?php } ?>
    </ul>
</div>
