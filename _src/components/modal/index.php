<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>

    <div class="<?= \Granola\Helpers::build_classes($args['overlay_classes']); ?>" aria-hidden="true"></div>

    <div class="modal__inner">
        <div class="modal__content has-background has-brand-5-background-color">

            <?= $args['content']; ?>

            <button class="modal__dismiss">
                Close
                <?= \Granola\SVG::get('icons/cross.svg'); ?>
            </button>
        </div>
    </div>
</div>
