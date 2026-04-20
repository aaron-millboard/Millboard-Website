<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="timeline__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading'])) { ?>
            <div class="timeline__header">
                <?php if (!empty($args['preheading'])) { ?>
                    <div class="timeline__preheading">
                        <?= wp_kses_post($args['preheading']); ?>
                    </div>
                <?php } ?>
                
                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="timeline__heading">
                        <?= wp_kses_post($args['heading']); ?>
                    </h2>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="timeline__content">
            <div class="timeline__line-container">
                <div class="timeline__line timeline__line--default"></div>
                <div class="timeline__line timeline__line--active"></div>
            </div>

            <ul class="timeline__list">
                <?php foreach ($args['items'] as $index => $item) { ?>
                    <li 
                        class="timeline__item" 
                        id="<?= esc_attr($item['id']); ?>"
                        data-index="<?= esc_attr($index); ?>"
                    >
                        <div class="timeline__item-marker">
                            <div class="timeline__item-marker--ring"></div>
                            <div class="timeline__item-marker--bg"></div>
                        </div>
                        
                        <?php if (!empty($item['year'])) { ?>
                            <div class="timeline__item__year">
                                <?= esc_html($item['year']); ?>
                            </div>
                        <?php } ?>

                        <div class="timeline__item__details">

                            <?php if (!empty($item['image'])) { ?>
                                <div class="timeline__item__details__image">
                                    <?= \Granola\Component::get('image', $item['image']); ?>
                                </div>
                            <?php } ?>

                            <?php if (!empty($item['description'])) { ?>
                                <div class="timeline__item__details__description">
                                    <?= wp_kses_post($item['description']); ?>
                                </div>
                            <?php } ?>

                        </div>

                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>

    <nav class="timeline__nav">
        <div class="timeline__nav__inner">
            <div class="timeline__nav__items">
                <?php foreach ($args['items'] as $index => $item) { ?>
                    
                        <button 
                            class="timeline__nav__item<?= $index === 0 ? ' timeline__nav__item--active' : ''; ?>" 
                            data-target="<?= esc_attr($item['id']); ?>"
                            data-index="<?= esc_attr($index); ?>"
                            aria-label="Go to <?= esc_attr($item['year'] ?? 'timeline item ' . ($index + 1)); ?>"
                        >
                            <?= esc_html($item['year'] ?? ($index + 1)); ?>
                        </button>
                
                <?php } ?>
            </div>
            
            <button 
                class="timeline__nav__item timeline__nav__item--skip" 
                data-skip="true"
                aria-label="Skip timeline"
            >
                Skip timeline
            </button>

            <div class="timeline__nav__arrows">
                <button 
                    class="timeline__nav__arrow timeline__nav__arrow--prev" 
                    aria-label="Previous timeline item"
                >
                    <span class="timeline__nav__arrow-icon">&larr;</span>
                </button>
                
                <button 
                    class="timeline__nav__arrow timeline__nav__arrow--next" 
                    aria-label="Next timeline item"
                >
                    <span class="timeline__nav__arrow-icon">&rarr;</span>
                </button>
            </div>
        </div>
    </nav>
</div>
