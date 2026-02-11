<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="call-to-action__inner">
        <?php if (!empty($args['heading'])) { ?>
            <?= \Granola\Component::get('heading', [
                'content' => $args['heading'],
                'classes' => ['call-to-action__heading'],
            ]); ?>
        <?php } ?>

        <?php if (!empty($args['image'])) { ?>
            <div class="call-to-action__image">
                <?= \Granola\Component::get('image', $args['image']); ?>
            </div>
        <?php } ?>

        <div class="call-to-action__content">
            <?php if (!empty($args['content'])) { ?>
                <?= wp_kses_post($args['content']); ?>
            <?php } ?>
            

            <?php if (!empty($args['buttons'])) { ?>
                <div class="call-to-action__buttons flex-list">
                    <?php foreach($args['buttons'] as $button) { ?>
                        <?= \Granola\Component::get('link', $button['button']); ?>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
