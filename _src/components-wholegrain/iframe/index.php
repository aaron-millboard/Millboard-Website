<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="iframe__inner">
        <div class="iframe__content">
            <?php if (!empty($args['content'])) { ?>
                <?= wp_kses_post($args['content']); ?>
            <?php } ?>
        </div>
    </div>
</div>
