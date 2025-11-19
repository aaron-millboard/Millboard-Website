<header <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="page-header__inner">

        <!-- Breadcrumbs -->
        <?php if (!empty($args['show_breadcrumbs'])) { ?>
            <div class="page-header__breadcrumbs">
                <?= \Granola\Component::get('breadcrumbs'); ?>
            </div>
        <?php } ?>

        <div class="page-header__wrapper">

            <div class="page-header__image-wrapper">
                <?php if (!empty($args['image'])) { ?>
                    <div class="page-header__image">
                        <div class="page-header__image-inner img-fit">
                            <?= \Granola\Component::get('image', $args['image']); ?>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="page-header__content page-header__content--first">

                <div class="page-header__header">

                    <?php if (!empty($args['preheading'])) { ?>
                        <div class="page-header__preheading">
                            <?= wp_kses_post($args['preheading']); ?>
                        </div>
                    <?php } ?>

                    <?php if (!empty($args['heading'])) { ?>
                        <?= \Granola\Component::get('heading', $args['heading']); ?>
                    <?php } ?>

                </div>

            <?php
                // We are closing the first content div here if the type is 'page' and opening a new one for the rest of the content
                if($args['type'] == 'page'):
            ?>
                </div>
                <div class="page-header__content page-header__content--last">
            <?php endif; ?>

                <?php if (!empty($args['description'])) { ?>
                    <div class="page-header__description">
                        <?= wp_kses_post($args['description']['content']); ?>
                    </div>
                <?php } ?>

                <?php if (!empty($args['cta'])) { ?>
                    <?= \Granola\Component::get('link', $args['cta']); ?>
                <?php } ?>

            </div>

        </div>

    </div>

    <?php if (!empty($args['bg_gradient'])) { ?>
        <div class="page-header__background">
            <div class="page-header__background-left"></div>
            <div class="page-header__background-top"></div>
            <div class="page-header__background-bottom"></div>
        </div>
    <?php } ?>

</header>
