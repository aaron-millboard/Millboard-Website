<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if (\have_comments()) { ?>
        <?= \Granola\Component::get('heading', $args['heading']); ?>

        <?= \Granola\Component::get('comments-navigation', [
            'attributes' => [
                'id' => 'comment-nav-above',
            ],
        ]); ?>

        <ol class="comments__comment-list">
            <?php \wp_list_comments([
                'style' => 'ol',
                'type' => 'comment',
            ]);?>
        </ol>

        <?= \Granola\Component::get('comments-navigation', [
            'attributes' => [
                'id' => 'comment-nav-below',
            ],
        ]); ?>
    <?php } ?>

    <?php if (!empty($args['closed_message'])) { ?>
        <p class="comments__closed-message">
            <?= \esc_html($args['closed_message']); ?>
        </p>
    <?php } ?>

    <div class="comments__form">
        <?php \comment_form([
            'submit_button' => '<button name="%1$s" type="submit" id="%2$s" class="g-button %3$s"/>%4$s</button>',
        ]); ?>
    </div>
</section>
