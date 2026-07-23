<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php if ($args['total_slides'] > 1 && !empty($args['show_navigation']) || !empty($args['show_pips']) || !empty($args['show_counter'])) { ?>
        <div class="slider__ui">
            <?php if (!empty($args['show_navigation'])) { ?>
                <?php foreach ($args['navigation'] as $index => $navigation) { ?>
                    <div class="slider__navigation-container">
                        <?= \Granola\Component::get('button', $navigation); ?>
                    </div>

                    <?php if ($args['show_counter'] && $index === 0) { ?>
                        <div class="slider__counter" aria-live="polite">
                            <span class="current">1</span> / <span class="total"><?= $args['total_slides'] ?></span>
                        </div>
                    <?php } ?>
                <?php } ?>
            <?php } ?>

            <?php if ($args['show_pips']) { ?>
                <ul class="slider__pips list-reset--hard" aria-label="Slide navigation">
                    <?php foreach ($args['pips'] as $pip) { ?>
                        <li><?= \Granola\Component::get('button', $pip); ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    <?php } ?>

    <?php // Carousel track: a div, not a ul. Slides carry role="group"/aria-roledescription
          // (added by Slider.js), which overrides listitem semantics, so ul/li here would
          // trip WCAG 1.3.1 (list — "ul must only directly contain li"). ?>
    <div <?= \Granola\Helpers::build_attributes($args['track_attributes']); ?>>
        <?php foreach ($args['slides'] as $slide) { ?>
            <?= $slide['card']; ?>
        <?php } ?>
    </div>

    <div class="slider__screen-reader-live-region visually-hidden" aria-live="polite"></div>
</div>
