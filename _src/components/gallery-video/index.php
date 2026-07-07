<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="gallery-video__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading'])) { ?>
            <div class="gallery-video__header">
                <?php if (!empty($args['preheading'])) { ?>
                    <div class="gallery-video__preheading is-style-typestyle-h6">
                        <?= wp_kses_post($args['preheading']); ?>
                    </div>
                <?php } ?>
                
                <?php if (!empty($args['heading'])) { ?>
                    <h2 class="gallery-video__heading">
                        <?= wp_kses_post($args['heading']); ?>
                    </h2>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['items']) && is_array($args['items'])) { ?>
            <?php $gallery_ref = !empty($args['ref']) ? $args['ref'] : \wp_unique_prefixed_id('gallery-video-'); ?>
            <div class="gallery-video__content">

                <?php if (count($args['items']) > 1) { ?>
                    <div class="gallery-video__sidebar-wrapper">
                        <div class="gallery-video__sidebar">
                            <?php foreach ($args['items'] as $index => $item) { ?>
                                <button
                                    type="button"
                                    class="gallery-video__thumbnail<?= $index === 0 ? ' gallery-video__thumbnail--active' : ''; ?>"
                                    data-video-index="<?= esc_attr($index); ?>"
                                    aria-controls="<?= esc_attr($gallery_ref . '-video-' . ($index + 1)); ?>"
                                    aria-pressed="<?= $index === 0 ? 'true' : 'false'; ?>"
                                    aria-label="<?= esc_attr(sprintf(__('Play video %d', 'granola'), $index + 1)); ?>"
                                >
                                    <?php if (!empty($item['thumbnail_data'])) { ?>
                                        <?= \Granola\Component::get('image', $item['thumbnail_data']); ?>
                                    <?php } ?>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>

                <div class="gallery-video__player">
                    <div class="gallery-video__stage">
                        <?php foreach ($args['items'] as $index => $item) { ?>
                            <div 
                                class="gallery-video__video<?= $index === 0 ? ' gallery-video__video--active' : ''; ?>" 
                                data-video-index="<?= esc_attr($index); ?>"
                                id="<?= esc_attr($gallery_ref . '-video-' . ($index + 1)); ?>"
                                aria-hidden="<?= $index === 0 ? 'false' : 'true'; ?>"
                            >
                                <div class="gallery-video__cover">
                                    <?php if (!empty($item['cover_image_data'])) { ?>
                                        <?= \Granola\Component::get('image', $item['cover_image_data']); ?>
                                    <?php } ?>
                                    
                                    <button 
                                        class="gallery-video__play-button" 
                                        data-embed-url="<?= esc_attr($item['embed_url']); ?>"
                                        aria-label="<?= esc_attr(__('Play video', 'granola')); ?>"
                                    >
                                        <?= \Granola\SVG::get('icons-custom/play.svg'); ?>
                                    </button>
                                </div>

                                <div class="gallery-video__iframe">
                                    <iframe 
                                        src=""
                                        title="<?= esc_attr(sprintf(__('Video %d', 'granola'), $index + 1)); ?>"
                                        loading="lazy"
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen
                                    ></iframe>
                                </div>
                            </div>
                        <?php } ?>
                    </div>

                    <?php foreach ($args['items'] as $index => $item) { ?>
                        <?php if (!empty($item['caption']) || !empty($item['subcaption'])) { ?>
                            <div class="gallery-video__meta gallery-video__meta--panel<?= $index === 0 ? ' gallery-video__meta--active' : ''; ?>" data-video-index="<?= esc_attr($index); ?>">
                                <div class="gallery-video__captions nflm is-style-typestyle-small is-style-typestyle-meta">
                                    <?php if (!empty($item['caption'])) { ?>
                                        <div class="gallery-video__caption">
                                            <?= wp_kses_post($item['caption']); ?>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php if (!empty($item['subcaption'])) { ?>
                                        <div class="gallery-video__subcaption">
                                            <?= wp_kses_post($item['subcaption']); ?>
                                        </div>
                                    <?php } ?>
                                </div>
                                
                                <button 
                                    class="gallery-video__meta__play" 
                                    data-embed-url="<?= esc_attr($item['embed_url']); ?>"
                                    aria-label="<?= esc_attr(__('Play video', 'granola')); ?>"
                                >
                                    <span class="gallery-video__meta__play__icon">
                                        <?= \Granola\SVG::get('icons-custom/play.svg'); ?>
                                    </span>
                                    <span class="gallery-video__meta__play__text"><?= sprintf(__('Play video', 'granola')); ?></span>
                                </button>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
