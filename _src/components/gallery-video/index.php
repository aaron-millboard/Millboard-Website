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
            <div class="gallery-video__content">

                <?php if (count($args['items']) > 1) { ?>
                    <div class="gallery-video__sidebar-wrapper">
                        <div class="gallery-video__sidebar">
                            <?php foreach ($args['items'] as $index => $item) { ?>
                                <div 
                                    class="gallery-video__thumbnail<?= $index === 0 ? ' gallery-video__thumbnail--active' : ''; ?>"
                                    data-video-index="<?= esc_attr($index); ?>"
                                    aria-label="Play video <?= esc_attr($index + 1); ?>"
                                >
                                    <?php if (!empty($item['thumbnail_data'])) { ?>
                                        <?= \Granola\Component::get('image', $item['thumbnail_data']); ?>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>

                <div class="gallery-video__player">
                    <?php foreach ($args['items'] as $index => $item) { ?>
                        <div 
                            class="gallery-video__video<?= $index === 0 ? ' gallery-video__video--active' : ''; ?>" 
                            data-video-index="<?= esc_attr($index); ?>"
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
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen
                                ></iframe>
                            </div>

                            <?php if (!empty($item['caption']) || !empty($item['subcaption'])) { ?>
                                <div class="gallery-video__meta">
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
                                        <span class="gallery-video__meta__play__text">Play Video</span>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
