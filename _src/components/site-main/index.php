<main <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <<?= esc_html($args['inner_el']); ?> class="site-main__inner">

        <?php if (!empty($args['header']) || !empty($args['content'])) { ?>
            <div class="site-main__content blocks">
                <?php if (!empty($args['header'])) { ?>
                    <?= $args['header']; ?>
                <?php } ?>

                <?php if (!empty($args['content'])) { ?>
                    <?= $args['content']; ?>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['footer'])) { ?>
            <footer class="site-main__footer">
                <?= $args['footer']; ?>
            </footer>
        <?php } ?>
    </<?= esc_html($args['inner_el']); ?>>
</main>
