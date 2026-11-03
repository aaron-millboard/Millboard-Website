<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?> data-sample-inspector="<?= \esc_attr(\wp_json_encode($args['config'])); ?>">
    <div class="sample-inspector__inner">
        <hr class="sample-inspector__rule" aria-hidden="true">

        <h2 class="sample-inspector__heading"><?= \esc_html($args['heading']); ?></h2>

        <div class="sample-inspector__copy"><?= \wp_kses_post(\wpautop($args['intro'])); ?></div>

        <?php
        // The script puts the viewer in here once the block is near the screen,
        // so the 2 MB model is only fetched when someone scrolls to it.
        ?>
        <div class="sample-inspector__stage" data-sample-inspector-stage>
            <p class="sample-inspector__loading"><?= \esc_html__('Loading 3D sample', 'granola'); ?></p>
        </div>

        <noscript>
            <p class="sample-inspector__noscript"><?= \esc_html__('The 3D sample needs JavaScript to run.', 'granola'); ?></p>
        </noscript>
    </div>
</section>
