<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="partner-contact-form__inner">
        <div class="partner-contact-form__grid">
            <div class="partner-contact-form__column partner-contact-form__column--content">
                <div class="partner-contact-form__header">
                    <?php if (!empty($args['heading'])) { ?>
                        <?= \Granola\Component::get('heading', $args['heading']); ?>
                    <?php } ?>

                    <?php if (!empty($args['details'])) { ?>
                        <table class="partner-contact-form__table">
                            <tbody>
                                <?php foreach ($args['details'] as $key => $row) { ?>
                                    <tr>
                                        <th><?= esc_html($row['label']); ?></th>
                                        <td><?= wp_kses_post($row['value']); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } ?>
                </div>
            </div>

            <div class="partner-contact-form__column partner-contact-form__column--form">
                <div class="partner-contact-form__form">
                    <?= $args['hubspot_script']; ?>
                </div>
            </div>
        </div>
    </div>
</div>
