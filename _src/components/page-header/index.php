<header <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="page-header__inner">

        <!-- Breadcrumbs -->
        <?php if (!empty($args['show_breadcrumbs'])) { ?>
            <div class="page-header__breadcrumbs">
                <?= \Granola\Component::get('breadcrumbs'); ?>
            </div>
        <?php } ?>

        <div class="page-header__wrapper">

            <div class="page-header__content page-header__content--first">

                <?php if(!empty($args['preheading']) || !empty($args['heading'])) { ?>

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

                    <?php if (!empty($args['image'])) { ?>
                        <div class="page-header__image">
                            <div class="page-header__image-inner img-fit">
                                <?= \Granola\Component::get('image', $args['image']); ?>
                            </div>
                        </div>
                    <?php } ?>

                <?php } ?>

            </div>

            <div class="page-header__spacer"></div>

            <div class="page-header__content page-header__content--last">

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

    <div class="page-header__background">
        <div class="page-header__background-left"></div>
        <div class="page-header__background-top"></div>
        <div class="page-header__background-bottom"></div>
    </div>

</header>
