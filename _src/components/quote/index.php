<blockquote <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="quote__content is-style-typestyle-h5">
        <?= wp_kses_post($args['quotation']); ?>
    </div>
    <?php if (!empty($args['citation'])) { ?>
        <cite class="quote__citation is-style-typestyle-meta">
            <?= wp_kses_post($args['citation']); ?>
        </cite>
    <?php } ?>
</blockquote>
