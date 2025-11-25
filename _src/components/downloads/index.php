<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="downloads__inner">
        <?php if (!empty($args['preheading']) || !empty($args['heading']) || !empty($args['cta'])) { ?>
            <div class="downloads__header">
                <div class="downloads__header-content">
                    <?php if (!empty($args['preheading'])) { ?>
                        <div class="downloads__preheading is-style-typestyle-h6">
                            <?= $args['preheading']; ?>
                        </div>
                    <?php } ?>
                    
                    <?php if (!empty($args['heading'])) { ?>
                        <h2 class="downloads__heading">
                            <?= $args['heading']; ?>
                        </h2>
                    <?php } ?>
                </div>

                <?php if (!empty($args['cta'])) { ?>
                    <?= \Granola\Component::get('link', $args['cta']); ?>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if (!empty($args['files']) && is_array($args['files'])) { ?>
            <div class="downloads__files">
                <?php foreach ($args['files'] as $index => $file) { ?>
                    <?php if (empty($file['url'])) {
                        continue;
                    } ?>
                    
                    <div class="downloads__file">
                        <div class="downloads__file-info">
                            <div class="downloads__file-label">
                                <?= wp_kses_post($file['label'] ?? 'File'); ?>
                            </div>
                            <?php if (!empty($file['size'])) { ?>
                                <div class="downloads__file-size">
                                    <?= esc_html($file['size']); ?>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="downloads__file-actions">
                            <?php
                            foreach ($file['actions'] as $action_args) {
                                $action_args['content'] = $action_args['icon'] . $action_args['content'];
                                echo \Granola\Component::get('link', $action_args);
                            } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
