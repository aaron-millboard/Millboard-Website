<nav <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?= \Granola\Component::get('heading', $args['heading']); ?>

    <div class="comments-navigation__links">
        <div class="comments-navigation__previous">
            <?php \previous_comments_link(
                \__('Older Comments', 'granola')
            ); ?>
        </div>

        <div class="comments-navigation__next">
            <?php \next_comments_link(
                \__('Newer Comments', 'granola')
            );?>
        </div>
    </div>
</nav>
