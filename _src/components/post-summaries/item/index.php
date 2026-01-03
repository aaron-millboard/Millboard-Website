<article <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?= \Granola\Component::get('heading', $args['heading']); ?>

    <?php if (!empty($args['content'])) { ?>
        <div class="post-summary__content">
            <?= wp_kses_post($args['content']); ?>
        </div>
    <?php } ?>
</article>
