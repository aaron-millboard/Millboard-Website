<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="carbon-badge__inner">
        <div class="carbon-badge__result">
            <?= wp_kses_post($args['placeholder']); ?>
        </div>

        <?= \Granola\Component::get('link', $args['link']); ?>
    </div>
</div>
