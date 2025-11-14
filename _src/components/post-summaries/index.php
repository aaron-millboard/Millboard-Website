<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="post-summaries__inner">
        <?php if (!empty($args['heading'])) { ?>
            <?= \Granola\Component::get('heading', $args['heading']); ?>
        <?php } ?>

        <?php if (!empty($args['items'])) { ?>
            <div class="post-summaries__items">
                <?php foreach ($args['items'] as $key => $item) { ?>
                    <?= \Granola\Component::get('post-summaries/item', $item); ?>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
