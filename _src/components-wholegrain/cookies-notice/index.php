<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="cookies-notice__banner has-blue-background-color">
        <?php if (!empty($args['heading'])) { ?>
            <?= \Granola\Component::get('heading', [
                'content' => $args['heading'],
                'classes' => ['cookies-notice__heading'],
            ]); ?>
        <?php } ?>

        <div class="cookies-notice__description">
            <?= wp_kses_post($args['content']); ?>
        </div>

        <?= \Granola\Component::get('cookies-preferences', [
            'classes' => ['cookies-notice__preferences'],
            'groups_expanded' => false,
        ]); ?>
    </div>
</div>
