<?php

namespace Theme\WooCommerce;

class QuoteShare
{
    private const RESTORE_LINK_TEXT = 'Add items to basket';

    public static function init(): void
    {
        \add_action('admin_post_millboard_quote_submit', [__CLASS__, 'handle_submit']);
        \add_action('admin_post_nopriv_millboard_quote_submit', [__CLASS__, 'handle_submit']);
        \add_action('template_redirect', [__CLASS__, 'maybe_restore_quote']);
        \add_action('acf/init', [__CLASS__, 'register_acf_fields']);
    }

    public static function is_quote_share_enabled(): bool
    {
        if (!\function_exists('get_field')) {
            return false;
        }

        $visibility = (string) \get_field('millboard_show_quote_share', 'option');
        return $visibility === 'yes';
    }

    public static function register_acf_fields(): void
    {
        if (!\function_exists('acf_add_local_field_group')) {
            return;
        }

        \acf_add_local_field_group([
            'key' => 'group_quote_share_settings',
            'title' => \__('Quote Share', 'granola'),
            'fields' => [
                [
                    'key' => 'field_show_quote_share',
                    'label' => \__('Show quote share button', 'granola'),
                    'name' => 'millboard_show_quote_share',
                    'type' => 'button_group',
                    'choices' => [
                        'yes' => \__('Yes', 'granola'),
                        'no' => \__('No', 'granola'),
                    ],
                    'default_value' => 'no',
                    'return_format' => 'value',
                ],
                [
                    'key' => 'field_quote_hubspot_portal_id',
                    'label' => \__('HubSpot Portal ID', 'granola'),
                    'name' => 'millboard_quote_hubspot_portal_id',
                    'type' => 'text',
                    'instructions' => \__('Your HubSpot account/portal ID (numeric).', 'granola'),
                    'required' => 0,
                    'default_value' => '',
                    'placeholder' => '12345678',
                ],
                [
                    'key' => 'field_quote_hubspot_form_guid',
                    'label' => \__('HubSpot Form GUID', 'granola'),
                    'name' => 'millboard_quote_hubspot_form_guid',
                    'type' => 'text',
                    'instructions' => \__('The GUID of the custom HubSpot form to submit quote data to.', 'granola'),
                    'required' => 0,
                    'default_value' => '',
                    'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-options-quote-share',
                    ],
                ],
            ],
            'position' => 'normal',
            'style' => 'default',
            'active' => true,
        ]);
    }

    public static function handle_submit(): void
    {
        if (!isset($_POST['millboard_quote_nonce']) || !\wp_verify_nonce(\sanitize_text_field(\wp_unslash($_POST['millboard_quote_nonce'])), 'millboard_quote_submit')) {
            self::redirect_with_notice(\__('Unable to process your quote request. Please try again.', 'granola'), 'error');
        }

        $intent = isset($_POST['quote_intent']) ? \sanitize_text_field(\wp_unslash($_POST['quote_intent'])) : '';
        $form_data = self::get_form_data();
        $cart_data = self::get_cart_data();
        $restore_url = self::get_quote_restore_url($cart_data);

        if (!self::has_required_form_fields($form_data)) {
            self::redirect_with_notice(\__('Please complete all required fields.', 'granola'), 'error');
        }

        if (empty($cart_data['lines'])) {
            self::redirect_with_notice(\__('Your basket is empty, so there is no quote to share.', 'granola'), 'error');
        }

        // Best effort: create a quote order so HubSpot for WooCommerce can sync it.
        self::submit_quote_to_hubspot($form_data, $cart_data);

        if ($intent === 'download') {
            self::stream_pdf($form_data, $cart_data, $restore_url);
        }

        if ($intent !== 'email') {
            self::redirect_with_notice(\__('Please choose a valid quote action.', 'granola'), 'error');
        }

        if (!self::is_valid_email_address($form_data['email_address'])) {
            self::redirect_with_notice(\__('Please provide a valid email address.', 'granola'), 'error');
        }

        $pdf_content = self::generate_quote_pdf($form_data, $cart_data, $restore_url);
        $tmp_file = self::create_temp_pdf_file($pdf_content);

        if (empty($tmp_file)) {
            self::redirect_with_notice(\__('Unable to generate the quote PDF. Please try again.', 'granola'), 'error');
        }

        $email_success = self::send_quote_email($form_data, $cart_data, $tmp_file, $restore_url);

        if (\file_exists($tmp_file)) {
            \unlink($tmp_file);
        }

        if ($email_success) {
            self::redirect_with_notice(\__('Your quote has been emailed successfully.', 'granola'), 'success');
        }

        self::redirect_with_notice(\__('Unable to send your quote email. Please try again.', 'error'));
    }

    private static function create_temp_pdf_file(string $pdf_content): ?string
    {
        // Guard against attaching a non-PDF file if PDF generation fails unexpectedly.
        if (\strpos($pdf_content, '%PDF-') !== 0) {
            return null;
        }

        $temp_dir = \trailingslashit(\get_temp_dir());
        $filename = 'millboard-quote-' . \wp_generate_password(12, false, false) . '.pdf';
        $tmp_file = $temp_dir . $filename;

        if (\file_put_contents($tmp_file, $pdf_content) === false) {
            return null;
        }

        return $tmp_file;
    }

    private static function generate_quote_pdf(array $form_data, array $cart_data, string $restore_url = ''): string
    {
        $html = self::render_quote_pdf_html($form_data, $cart_data, $restore_url);
        $pdf_content = self::generate_pdf_from_html($html);

        if (is_string($pdf_content) && $pdf_content !== '') {
            return $pdf_content;
        }

        // Fallback for environments where Dompdf is unavailable.
        return self::generate_pdf(self::build_pdf_lines($form_data, $cart_data, $restore_url), $restore_url);
    }

    private static function render_quote_pdf_html(array $form_data, array $cart_data, string $restore_url = ''): string
    {
        $logo_src = self::get_brand_logo_src_for_pdf();
        $font_face_css = self::get_pdf_font_face_css();
        $terms_urls = self::get_quote_terms_urls();
        $rows = '';

        foreach ($cart_data['lines'] as $line) {
            $item_name = $line;
            $item_qty = '';

            if (\preg_match('/^(.*)\s+x\s+(\d+)$/i', $line, $matches) === 1) {
                $item_name = \trim((string) $matches[1]);
                $item_qty = 'x' . (string) $matches[2];
            }

            $rows .= '<tr><td class="cart-table__name">' . \esc_html($item_name) . '</td><td class="cart-table__qty">' . \esc_html($item_qty) . '</td></tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td class="cart-table__name">' . \esc_html__('No items available', 'granola') . '</td><td class="cart-table__qty"></td></tr>';
        }

        $sales_notes_html = '';
        if (!empty($form_data['sales_notes'])) {
            $sales_notes_html = '<div class="quote-meta__row"><span class="quote-meta__label">' . \esc_html__('Sales notes', 'granola') . '</span><span class="quote-meta__value">' . \nl2br(\esc_html($form_data['sales_notes'])) . '</span></div>';
        }

        $logo_html = '';
        if ($logo_src !== '') {
            $logo_html = '<div class="quote-header__logo-wrap"><img class="quote-header__logo" src="' . \esc_attr($logo_src) . '" alt="' . \esc_attr(\wp_specialchars_decode(\get_bloginfo('name'), ENT_QUOTES)) . '"></div>';
        }

        $restore_link_html = '';
        if ($restore_url !== '') {
            $restore_link_html = '<div class="quote-actions"><a href="' . \esc_url($restore_url) . '" class="g-button">' . \esc_html__(self::RESTORE_LINK_TEXT, 'granola') . '</a></div>';
        }

        $line_count = \count($cart_data['lines']);
        $sales_notes_penalty = !empty($form_data['sales_notes']) ? 36 : 0;
        $terms_margin_top = \max(72, 260 - ($line_count * 22) - $sales_notes_penalty);

        $terms_links_html = '<div class="quote-terms" style="margin-top:' . (int) $terms_margin_top . 'px;">'
            . '<p class="quote-terms__title">' . \esc_html__('Terms and conditions', 'granola') . '</p>';

        foreach ($terms_urls as $terms_label => $terms_url) {
            if (!is_string($terms_label) || $terms_label === '' || !is_string($terms_url) || $terms_url === '') {
                continue;
            }

            $terms_links_html .= '<p class="quote-terms__link"><a href="' . \esc_url($terms_url) . '">' . \esc_html($terms_label) . '</a></p>';
        }

        $terms_links_html .= '</div>';

        return '<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        ' . $font_face_css . '
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "F37 Ginger", Helvetica, sans-serif; color: #222; font-size: 12px; font-weight: 400; background: #ffffff; }
        .quote-page { width: 100%; background: #ffffff; }
        .quote-header { text-align: center; padding: 26px 0 14px; background: #F9F7F1; }
        .quote-header__logo-wrap { margin: 0 0 8px; text-align: center; }
        .quote-header__logo { width: 127px; height: 24px; object-fit: contain; object-position: center center; }
        .quote-header__title { margin: 0 0 4px; font-size: 16px; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 400; }
        .quote-header__date { margin: 0; font-size: 11px; color: #444; }
        .quote-divider { border-top: 2px solid #8a9623; margin: 0; }
        .quote-inner { width: 80%; margin: 28px auto 0; }
        .quote-meta { margin: 0 0 30px; border: 1px solid #9e9e9e; background: transparent; }
        .quote-meta__row { display: table; width: 100%; border-bottom: 1px solid #9e9e9e; }
        .quote-meta__row:last-child { border-bottom: 0; }
        .quote-meta__label, .quote-meta__value { display: table-cell; padding: 9px 14px; vertical-align: top; }
        .quote-meta__label { width: 34%; font-weight: 400; text-transform: uppercase; font-size: 11px; letter-spacing: 0.06em; border-right: 1px solid #9e9e9e; background: rgba(249, 247, 241, 0.50); }
        .quote-meta__value { font-size: 13px; }
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .cart-table thead th { text-align: left; padding: 9px 10px; font-size: 12px; font-weight: 400; color: #fff; background: #62554D; }
        .cart-table thead th:last-child { width: 60px; }
        .cart-table td { border-bottom: 1px solid #c9c9c9; padding: 11px 0; font-size: 14px; background: #ffffff; }
        .cart-table__name { letter-spacing: 0.02em; background: #ffffff; padding-left: 10px; padding-right: 10px; }
        .cart-table__qty { text-align: right; width: 60px; }
        .quote-total { display: table; width: 100%; border-top: 2px solid #585858; margin-top: 8px; }
        .quote-total__label, .quote-total__value { display: table-cell; padding: 12px 0 2px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .quote-total__value { text-align: right; }
        .quote-total-note { margin: 0; font-size: 11px; color: #444; }
        .quote-actions { margin-top: 20px; }
        .g-button { display: inline-block; padding: 10px 14px; border: 1px solid #5d5d5d; background: #5d5d5d; color: #fff; text-decoration: none; text-transform: uppercase; font-size: 11px; font-weight: 400; letter-spacing: 0.08em; font-family: "F37 Ginger", Helvetica, sans-serif; }
        .quote-terms { }
        .quote-terms__title { margin: 0 0 4px; font-size: 18px; font-weight: 400; text-transform: uppercase; letter-spacing: 0.08em; }
        .quote-terms__link { margin: 0 0 2px; font-size: 12px; }
        a { color: #222; text-decoration: none; overflow-wrap: anywhere; word-break: normal; }
        .g-button { color: #fff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="quote-page">
        <div class="quote-header">
            ' . $logo_html . '
            <h1 class="quote-header__title">' . \esc_html__('Your Millboard Quote', 'granola') . '</h1>
            <p class="quote-header__date">' . \esc_html(sprintf(\__('Date: %s', 'granola'), \wp_date('Y-m-d H:i'))) . '</p>
        </div>
        <div class="quote-divider"></div>

        <div class="quote-inner">

        <div class="quote-meta">
            <div class="quote-meta__row"><span class="quote-meta__label">' . \esc_html__('Company', 'granola') . '</span><span class="quote-meta__value">' . \esc_html($form_data['company_name']) . '</span></div>
            <div class="quote-meta__row"><span class="quote-meta__label">' . \esc_html__('Contact', 'granola') . '</span><span class="quote-meta__value">' . \esc_html($form_data['contact_name']) . '</span></div>
            <div class="quote-meta__row"><span class="quote-meta__label">' . \esc_html__('Email', 'granola') . '</span><span class="quote-meta__value">' . \esc_html($form_data['email_address']) . '</span></div>
            <div class="quote-meta__row"><span class="quote-meta__label">' . \esc_html__('Phone', 'granola') . '</span><span class="quote-meta__value">' . \esc_html($form_data['phone_number']) . '</span></div>
            <div class="quote-meta__row"><span class="quote-meta__label">' . \esc_html__('Customer reference', 'granola') . '</span><span class="quote-meta__value">' . \esc_html($form_data['customer_reference_number']) . '</span></div>
            ' . $sales_notes_html . '
        </div>

        <table class="cart-table" cellspacing="0">
            <thead>
                <tr>
                    <th>' . \esc_html__('Your items', 'granola') . '</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                ' . $rows . '
            </tbody>
        </table>

        <div class="quote-total">
            <span class="quote-total__label">' . \esc_html__('Total', 'granola') . '</span>
            <span class="quote-total__value">' . \esc_html((string) $cart_data['total']) . '</span>
        </div>
        <p class="quote-total-note">' . \esc_html__('Prices are valid for 30 calendar days', 'granola') . '</p>

        ' . $restore_link_html . '
        ' . $terms_links_html . '
        </div>
    </div>
</body>
</html>';
    }

    private static function get_pdf_font_face_css(): string
    {
        if (!\function_exists('get_stylesheet_directory') || !\function_exists('get_stylesheet_directory_uri')) {
            return '';
        }

        $stylesheet_dir = \trailingslashit((string) \get_stylesheet_directory());
        $stylesheet_uri = \trailingslashit((string) \get_stylesheet_directory_uri());

        $light_src = self::get_pdf_font_src_with_fallbacks([
            $stylesheet_dir . 'assets/static/f37-ginger-light.woff2',
            $stylesheet_dir . '_src/static/f37-ginger-light.woff2',
        ], [
            $stylesheet_uri . 'assets/static/f37-ginger-light.woff2',
            $stylesheet_uri . '_src/static/f37-ginger-light.woff2',
        ]);

        $regular_src = self::get_pdf_font_src_with_fallbacks([
            $stylesheet_dir . 'assets/static/f37-ginger.woff2',
            $stylesheet_dir . '_src/static/f37-ginger.woff2',
        ], [
            $stylesheet_uri . 'assets/static/f37-ginger.woff2',
            $stylesheet_uri . '_src/static/f37-ginger.woff2',
        ]);

        $bold_src = self::get_pdf_font_src_with_fallbacks([
            $stylesheet_dir . 'assets/static/f37-ginger-bold.woff2',
            $stylesheet_dir . '_src/static/f37-ginger-bold.woff2',
        ], [
            $stylesheet_uri . 'assets/static/f37-ginger-bold.woff2',
            $stylesheet_uri . '_src/static/f37-ginger-bold.woff2',
        ]);

        if ($light_src === '' && $regular_src === '' && $bold_src === '') {
            return '';
        }

        return '@font-face {'
            . 'font-family: "F37 Ginger";'
            . 'font-style: normal;'
            . 'font-weight: 300;'
            . 'src: ' . $light_src . ';'
            . '}'
            . '@font-face {'
            . 'font-family: "F37 Ginger";'
            . 'font-style: normal;'
            . 'font-weight: 400;'
            . 'src: ' . $regular_src . ';'
            . '}'
            . '@font-face {'
            . 'font-family: "F37 Ginger";'
            . 'font-style: normal;'
            . 'font-weight: 700;'
            . 'src: ' . $bold_src . ';'
            . '}';
    }

    private static function get_pdf_font_src_with_fallbacks(array $file_paths, array $fallback_uris): string
    {
        foreach ($file_paths as $file_path) {
            if (!is_string($file_path) || $file_path === '' || !\file_exists($file_path) || !\is_readable($file_path)) {
                continue;
            }

            $font_bytes = \file_get_contents($file_path);
            if (is_string($font_bytes) && $font_bytes !== '') {
                return 'url("data:font/woff2;base64,' . \base64_encode($font_bytes) . '") format("woff2")';
            }
        }

        foreach ($fallback_uris as $fallback_uri) {
            if (is_string($fallback_uri) && $fallback_uri !== '') {
                return 'url("' . \esc_url($fallback_uri) . '") format("woff2")';
            }
        }

        return '';
    }

    private static function get_brand_logo_src_for_pdf(): string
    {
        if (\function_exists('get_theme_mod') && \function_exists('get_attached_file')) {
            $custom_logo_id = (int) \get_theme_mod('custom_logo');

            if ($custom_logo_id > 0) {
                $logo_path = (string) \get_attached_file($custom_logo_id);

                $custom_logo_data_uri = self::get_inline_asset_data_uri($logo_path);
                if ($custom_logo_data_uri !== '') {
                    return $custom_logo_data_uri;
                }
            }
        }

        if (\function_exists('get_stylesheet_directory')) {
            $stylesheet_dir = \trailingslashit((string) \get_stylesheet_directory());
            $fallback_logo_paths = [
                $stylesheet_dir . 'assets/images/logo.svg',
                $stylesheet_dir . 'assets/images/logo-alt.svg',
                $stylesheet_dir . '_src/images/logo.svg',
                $stylesheet_dir . '_src/images/logo-alt.svg',
                $stylesheet_dir . 'assets/images/icon-512.png',
            ];

            foreach ($fallback_logo_paths as $fallback_logo_path) {
                $fallback_logo_data_uri = self::get_inline_asset_data_uri($fallback_logo_path);
                if ($fallback_logo_data_uri !== '') {
                    return $fallback_logo_data_uri;
                }
            }
        }

        return self::get_brand_logo_url();
    }

    private static function get_inline_asset_data_uri(string $file_path): string
    {
        if ($file_path === '' || !\file_exists($file_path) || !\is_readable($file_path)) {
            return '';
        }

        $mime_type = (string) \wp_check_filetype($file_path)['type'];
        if ($mime_type === '') {
            $extension = \strtolower((string) \pathinfo($file_path, PATHINFO_EXTENSION));

            if ($extension === 'svg') {
                $mime_type = 'image/svg+xml';
            } elseif ($extension === 'webp') {
                $mime_type = 'image/webp';
            } elseif ($extension === 'jpg' || $extension === 'jpeg') {
                $mime_type = 'image/jpeg';
            } else {
                $mime_type = 'image/png';
            }
        }

        $asset_bytes = \file_get_contents($file_path);
        if (!is_string($asset_bytes) || $asset_bytes === '') {
            return '';
        }

        return 'data:' . $mime_type . ';base64,' . \base64_encode($asset_bytes);
    }

    private static function get_brand_logo_url(): string
    {
        if (!\function_exists('get_theme_mod') || !\function_exists('wp_get_attachment_image_url')) {
            return '';
        }

        $custom_logo_id = (int) \get_theme_mod('custom_logo');
        if ($custom_logo_id < 1) {
            return '';
        }

        return (string) \wp_get_attachment_image_url($custom_logo_id, 'full');
    }

    private static function get_brand_logo_url_for_email(): string
    {
        if (\function_exists('get_theme_mod') && \function_exists('get_attached_file') && \function_exists('wp_get_attachment_image_url')) {
            $custom_logo_id = (int) \get_theme_mod('custom_logo');

            if ($custom_logo_id > 0) {
                $logo_path = (string) \get_attached_file($custom_logo_id);
                $logo_url = (string) \wp_get_attachment_image_url($custom_logo_id, 'full');
                $extension = \strtolower((string) \pathinfo($logo_path, PATHINFO_EXTENSION));

                if ($logo_url !== '' && $extension !== 'svg') {
                    return $logo_url;
                }
            }
        }

        if (\function_exists('get_stylesheet_directory') && \function_exists('get_stylesheet_directory_uri')) {
            $stylesheet_dir = \trailingslashit((string) \get_stylesheet_directory());
            $stylesheet_uri = \trailingslashit((string) \get_stylesheet_directory_uri());
            $fallback_logo_files = [
                'assets/images/logo.png',
                'assets/images/logo.jpg',
                'assets/images/logo.jpeg',
                'assets/images/logo.webp',
                '_src/images/logo.png',
                '_src/images/logo.jpg',
                '_src/images/logo.jpeg',
                '_src/images/logo.webp',
                'assets/images/icon-512.png',
                '_src/images/icon-512.png',
            ];

            foreach ($fallback_logo_files as $fallback_logo_file) {
                if (\file_exists($stylesheet_dir . $fallback_logo_file)) {
                    return $stylesheet_uri . $fallback_logo_file;
                }
            }
        }

        return self::get_brand_logo_url();
    }

    private static function get_quote_terms_urls(): array
    {
        if (!\function_exists('home_url')) {
            return [];
        }

        return [
            'Business Terms (B2B)' => (string) \home_url('/trust-centre/millboard-uk-business-terms-and-conditions-of-sale-b2b/'),
            'Consumer Terms (B2C)' => (string) \home_url('/trust-centre/millboard-uk-consumer-terms-and-conditions-of-sale-b2c/'),
        ];
    }

    private static function generate_pdf_from_html(string $html): ?string
    {
        if (!\class_exists('\\Dompdf\\Dompdf') || !\class_exists('\\Dompdf\\Options')) {
            return null;
        }

        try {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'F37 Ginger');
            $options->set('isFontSubsettingEnabled', true);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->output();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private static function get_short_restore_link_text(string $restore_url): string
    {
        $parsed_url = \wp_parse_url($restore_url);

        if (!is_array($parsed_url) || empty($parsed_url['host'])) {
            return \mb_strimwidth($restore_url, 0, 72, '...');
        }

        $path = isset($parsed_url['path']) ? (string) $parsed_url['path'] : '/';
        $query = isset($parsed_url['query']) ? '?' . (string) $parsed_url['query'] : '';
        $short_url = $parsed_url['host'] . $path . $query;

        return \mb_strimwidth($short_url, 0, 72, '...');
    }

    private static function get_form_data(): array
    {
        return [
            'company_name' => isset($_POST['company_name']) ? \sanitize_text_field(\wp_unslash($_POST['company_name'])) : '',
            'contact_name' => isset($_POST['contact_name']) ? \sanitize_text_field(\wp_unslash($_POST['contact_name'])) : '',
            'email_address' => isset($_POST['email_address']) ? \sanitize_email(\wp_unslash($_POST['email_address'])) : '',
            'phone_number' => isset($_POST['phone_number']) ? \sanitize_text_field(\wp_unslash($_POST['phone_number'])) : '',
            'customer_reference_number' => isset($_POST['customer_reference_number']) ? \sanitize_text_field(\wp_unslash($_POST['customer_reference_number'])) : '',
            'sales_notes' => isset($_POST['sales_notes']) ? \sanitize_textarea_field(\wp_unslash($_POST['sales_notes'])) : '',
        ];
    }

    private static function get_cart_data(): array
    {
        if (!function_exists('WC') || empty(WC()->cart)) {
            return self::get_posted_cart_snapshot();
        }

        if (WC()->cart->is_empty()) {
            return self::get_posted_cart_snapshot();
        }

        $lines = [];
        $items = [];

        foreach (WC()->cart->get_cart() as $cart_item) {
            if (empty($cart_item['data']) || !$cart_item['data'] instanceof \WC_Product) {
                continue;
            }

            $product = $cart_item['data'];
            $quantity = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 0;

            if ($quantity < 1) {
                continue;
            }

            $lines[] = sprintf('%s x %d', \wp_strip_all_tags($product->get_name()), $quantity);
            $items[] = [
                'product_id' => isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0,
                'variation_id' => isset($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0,
                'quantity' => $quantity,
                'variation' => isset($cart_item['variation']) && is_array($cart_item['variation']) ? $cart_item['variation'] : [],
            ];
        }

        if (empty($lines)) {
            return self::get_posted_cart_snapshot();
        }

        $total = \wp_strip_all_tags(\html_entity_decode(WC()->cart->get_total(), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return [
            'items' => $items,
            'lines' => $lines,
            'total' => $total,
        ];
    }

    private static function get_posted_cart_snapshot(): array
    {
        if (empty($_POST['quote_snapshot'])) {
            return [
                'items' => [],
                'lines' => [],
                'total' => '',
            ];
        }

        $encoded_snapshot = \sanitize_text_field(\wp_unslash($_POST['quote_snapshot']));
        $json_snapshot = \base64_decode($encoded_snapshot, true);

        if ($json_snapshot === false) {
            return [
                'items' => [],
                'lines' => [],
                'total' => '',
            ];
        }

        $snapshot = \json_decode($json_snapshot, true);

        if (!is_array($snapshot)) {
            return [
                'items' => [],
                'lines' => [],
                'total' => '',
            ];
        }

        $items = [];
        $lines = [];

        if (!empty($snapshot['items']) && is_array($snapshot['items'])) {
            foreach ($snapshot['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $product_id = isset($item['product_id']) ? (int) $item['product_id'] : 0;
                $variation_id = isset($item['variation_id']) ? (int) $item['variation_id'] : 0;
                $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

                if ($product_id < 1 || $quantity < 1) {
                    continue;
                }

                $variation = [];
                if (!empty($item['variation']) && is_array($item['variation'])) {
                    foreach ($item['variation'] as $key => $value) {
                        $variation[(string) $key] = \sanitize_text_field((string) $value);
                    }
                }

                $items[] = [
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'quantity' => $quantity,
                    'variation' => $variation,
                ];
            }
        }

        if (!empty($snapshot['lines']) && is_array($snapshot['lines'])) {
            foreach ($snapshot['lines'] as $line) {
                $clean_line = \sanitize_text_field((string) $line);

                if ($clean_line === '') {
                    continue;
                }

                $lines[] = $clean_line;
            }
        }

        $total = !empty($snapshot['total']) ? \sanitize_text_field((string) $snapshot['total']) : '';

        return [
            'items' => $items,
            'lines' => $lines,
            'total' => $total,
        ];
    }

    private static function build_pdf_lines(array $form_data, array $cart_data, string $restore_url = ''): array
    {
        $lines = [
            \__('Millboard Quote', 'granola'),
            sprintf(\__('Date: %s', 'granola'), \wp_date('Y-m-d H:i')),
            '',
            sprintf(\__('Company: %s', 'granola'), $form_data['company_name']),
            sprintf(\__('Contact: %s', 'granola'), $form_data['contact_name']),
            sprintf(\__('Email: %s', 'granola'), $form_data['email_address']),
            sprintf(\__('Phone: %s', 'granola'), $form_data['phone_number']),
            sprintf(\__('Customer Reference: %s', 'granola'), $form_data['customer_reference_number']),
            '',
            \__('Items', 'granola'),
        ];

        foreach ($cart_data['lines'] as $item_line) {
            $lines[] = '- ' . $item_line;
        }

        $lines[] = '';
        $lines[] = sprintf(\__('Quote Total: %s', 'granola'), $cart_data['total']);

        if (!empty($form_data['sales_notes'])) {
            $lines[] = '';
            $lines[] = \__('Sales Notes', 'granola');
            $lines[] = $form_data['sales_notes'];
        }

        if (!empty($restore_url)) {
            $lines[] = '';
            $lines[] = \__(self::RESTORE_LINK_TEXT, 'granola');
        }

        return self::wrap_pdf_lines($lines);
    }

    private static function wrap_pdf_lines(array $lines): array
    {
        $wrapped = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'http://') || str_starts_with($line, 'https://')) {
                $wrapped[] = $line;
                continue;
            }

            if (\strlen($line) <= 95) {
                $wrapped[] = $line;
                continue;
            }

            $parts = \explode("\n", \wordwrap($line, 95, "\n", true));
            foreach ($parts as $part) {
                $wrapped[] = $part;
            }
        }

        return $wrapped;
    }

    private static function generate_pdf(array $lines, string $restore_url = ''): string
    {
        $link_text = \__(self::RESTORE_LINK_TEXT, 'granola');
        $link_line_index = self::get_pdf_link_line_index($lines, $link_text);

        $stream = "BT\n/F1 12 Tf\n14 TL\n50 790 Td\n";

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $stream .= "T*\n";
            }

            if ($link_line_index !== null && $index === $link_line_index) {
                // Make the CTA line look like a conventional hyperlink.
                $stream .= "0 0 1 rg\n";
            }

            $stream .= '(' . self::escape_pdf_text($line) . ") Tj\n";

            if ($link_line_index !== null && $index === $link_line_index) {
                $stream .= "0 0 0 rg\n";
            }
        }

        $stream .= "ET";

        if ($link_line_index !== null) {
            $line_y = 790 - ($link_line_index * 14);
            $underline_y = max(0, $line_y - 1);
            $underline_x_start = 50;
            $underline_x_end = min(560, $underline_x_start + max(120, (\strlen($link_text) * 5.2)));
            $stream .= "\n0 0 1 RG\n0.8 w\n" . $underline_x_start . " " . $underline_y . " m\n" . $underline_x_end . " " . $underline_y . " l\nS\n0 0 0 RG";
        }
        $page_object = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R";

        if ($link_line_index !== null) {
            $page_object .= " /Annots [6 0 R]";
        }

        $page_object .= " >>\nendobj\n";

        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = $page_object;
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[] = "5 0 obj\n<< /Length " . \strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";

        if ($link_line_index !== null) {
            $line_y = 790 - ($link_line_index * 14);
            $x_start = 50;
            $x_end = min(560, $x_start + max(120, (\strlen($link_text) * 5.2)));
            $y_bottom = max(0, $line_y - 2);
            $y_top = min(842, $line_y + 12);

            $objects[] = "6 0 obj\n<< /Type /Annot /Subtype /Link /Rect [" . $x_start . ' ' . $y_bottom . ' ' . $x_end . ' ' . $y_top . "] /Border [0 0 0] /A << /S /URI /URI (" . self::escape_pdf_text($restore_url) . ") >> >>\nendobj\n";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = \strlen($pdf);
            $pdf .= $object;
        }

        $xref_position = \strlen($pdf);
        $pdf .= "xref\n0 " . (\count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $object_number => $offset) {
            if ($object_number === 0) {
                continue;
            }

            $pdf .= \sprintf('%010d 00000 n ' . "\n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . (\count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref_position . "\n%%EOF";

        return $pdf;
    }

    private static function get_pdf_link_line_index(array $lines, string $link_text): ?int
    {
        if ($link_text === '') {
            return null;
        }

        foreach ($lines as $index => $line) {
            if ($line === $link_text) {
                return $index;
            }
        }

        return null;
    }

    private static function escape_pdf_text(string $text): string
    {
        $text = \str_replace(["\\r", "\\n"], ' ', $text);
        return \str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private static function submit_quote_to_crm_perks(array $form_data, array $cart_data): bool
    {
        if (!\class_exists('vxc_hubspot') || !\function_exists('wc_create_order')) {
            return false;
        }

        $order = \wc_create_order([
            'created_via' => 'quote-share',
        ]);

        if (\is_wp_error($order) || !$order instanceof \WC_Order) {
            return false;
        }

        self::populate_order_from_form($order, $form_data);
        self::populate_order_items_from_cart($order, $cart_data);
        self::populate_order_quote_meta($order, $form_data, $cart_data);

        $order->calculate_totals();
        $order->save();

        global $vxc_hubspot;

        if (!is_object($vxc_hubspot) || !\method_exists($vxc_hubspot, 'push')) {
            return false;
        }

        $result = $vxc_hubspot->push($order->get_id(), '');

        if (is_array($result)) {
            return ($result['class'] ?? '') !== 'error';
        }

        return $result !== false;
    }

    private static function populate_order_from_form(\WC_Order $order, array $form_data): void
    {
        $contact_parts = \preg_split('/\s+/', trim($form_data['contact_name']));
        $first_name = '';
        $last_name = '';

        if (is_array($contact_parts) && !empty($contact_parts)) {
            $first_name = array_shift($contact_parts) ?: '';
            $last_name = !empty($contact_parts) ? implode(' ', $contact_parts) : '';
        }

        $order->set_billing_first_name($first_name);
        $order->set_billing_last_name($last_name);
        $order->set_billing_company($form_data['company_name']);
        $order->set_billing_email($form_data['email_address']);
        $order->set_billing_phone($form_data['phone_number']);
    }

    private static function populate_order_items_from_cart(\WC_Order $order, array $cart_data): void
    {
        if (!empty($cart_data['items']) && is_array($cart_data['items'])) {
            foreach ($cart_data['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $product_id = isset($item['product_id']) ? (int) $item['product_id'] : 0;
                $variation_id = isset($item['variation_id']) ? (int) $item['variation_id'] : 0;
                $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

                if ($quantity < 1) {
                    continue;
                }

                $target_id = $variation_id > 0 ? $variation_id : $product_id;
                if ($target_id < 1) {
                    continue;
                }

                $product = \wc_get_product($target_id);

                if (!$product instanceof \WC_Product) {
                    continue;
                }

                $order->add_product($product, $quantity);
            }

            return;
        }

        if (!\function_exists('WC') || empty(\WC()->cart)) {
            return;
        }

        foreach (\WC()->cart->get_cart() as $cart_item) {
            if (empty($cart_item['data']) || !$cart_item['data'] instanceof \WC_Product) {
                continue;
            }

            $quantity = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 0;

            if ($quantity < 1) {
                continue;
            }

            $order->add_product($cart_item['data'], $quantity);
        }
    }

    private static function populate_order_quote_meta(\WC_Order $order, array $form_data, array $cart_data): void
    {
        $order->update_meta_data('_quote_share_submission', '1');
        $order->update_meta_data('customer_reference_number', $form_data['customer_reference_number']);
        $order->update_meta_data('sales_notes', $form_data['sales_notes']);
        $order->update_meta_data('quote_items', implode(' | ', $cart_data['lines']));
        $order->update_meta_data('quote_total', $cart_data['total']);

        if (!empty($form_data['sales_notes'])) {
            $order->set_customer_note($form_data['sales_notes']);
        }
    }

    private static function submit_quote_to_hubspot(array $form_data, array $cart_data): bool
    {
        if (!\function_exists('get_field')) {
            return false;
        }

        $portal_id = \trim((string) \get_field('millboard_quote_hubspot_portal_id', 'option'));
        $form_guid = \trim((string) \get_field('millboard_quote_hubspot_form_guid', 'option'));

        if ($portal_id === '' || $form_guid === '') {
            return false;
        }

        $items_text = \implode('; ', $cart_data['lines']);

        $fields = [
            ['name' => 'company',                    'value' => $form_data['company_name']],
            ['name' => 'firstname',                  'value' => $form_data['contact_name']],
            ['name' => 'email',                      'value' => $form_data['email_address']],
            ['name' => 'phone',                      'value' => $form_data['phone_number']],
            ['name' => 'customer_reference_number',  'value' => $form_data['customer_reference_number']],
            ['name' => 'sales_notes',                'value' => $form_data['sales_notes']],
            ['name' => 'quote_items',                'value' => $items_text],
            ['name' => 'quote_total',                'value' => $cart_data['total']],
        ];

        $context = [];
        if (!empty($_COOKIE['hubspotutk'])) {
            $context['hutk'] = \sanitize_text_field($_COOKIE['hubspotutk']);
        }
        $page_url = \wp_unslash($_SERVER['HTTP_REFERER'] ?? '');
        if ($page_url) {
            $context['pageUri'] = \esc_url_raw($page_url);
        }

        $endpoint = \sprintf(
            'https://api.hsforms.com/submissions/v3/integration/submit/%s/%s',
            \rawurlencode($portal_id),
            \rawurlencode($form_guid)
        );

        $body = ['fields' => $fields];
        if (!empty($context)) {
            $body['context'] = $context;
        }

        $response = \wp_remote_post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => \wp_json_encode($body),
            'timeout' => 15,
        ]);

        if (\is_wp_error($response)) {
            return false;
        }

        $status_code = (int) \wp_remote_retrieve_response_code($response);
        return $status_code >= 200 && $status_code < 300;
    }

    private static function send_quote_email(array $form_data, array $cart_data, string $attachment_path, string $restore_url = ''): bool
    {
        $to = $form_data['email_address'];
        $subject = \__('Your Millboard quote', 'granola');

        $html_message = self::build_quote_email_html($form_data, $cart_data, $restore_url);
        $text_message = self::build_quote_email_text($form_data, $cart_data, $restore_url);

        return \wp_mail(
            $to,
            $subject,
            $html_message,
            [
                'Content-Type: text/html; charset=UTF-8',
                'X-Alt-Body: ' . $text_message,
            ],
            [$attachment_path]
        );
    }

    private static function build_quote_email_text(array $form_data, array $cart_data, string $restore_url = ''): string
    {
        $message = [
            \__('Thanks for requesting a quote. Your quote summary is below and attached as a PDF.', 'granola'),
            '',
            sprintf(\__('Company: %s', 'granola'), $form_data['company_name']),
            sprintf(\__('Contact: %s', 'granola'), $form_data['contact_name']),
            sprintf(\__('Customer reference: %s', 'granola'), $form_data['customer_reference_number']),
            '',
            \__('Items:', 'granola'),
        ];

        foreach ($cart_data['lines'] as $line) {
            $message[] = '- ' . $line;
        }

        $message[] = '';
        $message[] = sprintf(\__('Quote Total: %s', 'granola'), $cart_data['total']);

        if (!empty($form_data['sales_notes'])) {
            $message[] = '';
            $message[] = \__('Sales Notes:', 'granola');
            $message[] = $form_data['sales_notes'];
        }

        if (!empty($restore_url)) {
            $message[] = '';
            $message[] = \__(self::RESTORE_LINK_TEXT, 'granola') . ':';
            $message[] = $restore_url;
        }

        return implode("\n", $message);
    }

    private static function build_quote_email_html(array $form_data, array $cart_data, string $restore_url = ''): string
    {
        $site_name = \wp_specialchars_decode(\get_bloginfo('name'), ENT_QUOTES);
        $logo_url = self::get_brand_logo_url_for_email();
        $terms_urls = self::get_quote_terms_urls();

        $rows_html = '';
        foreach ($cart_data['lines'] as $line) {
            $item_name = $line;
            $item_qty = '';

            if (\preg_match('/^(.*)\s+x\s+(\d+)$/i', $line, $matches) === 1) {
                $item_name = \trim((string) $matches[1]);
                $item_qty = 'x' . (string) $matches[2];
            }

            $rows_html .= '<tr>'
                . '<td style="padding:11px 10px;border-bottom:1px solid #c9c9c9;background:#ffffff;color:#222222;font-size:14px;line-height:1.5;letter-spacing:0.02em;">' . \esc_html($item_name) . '</td>'
                . '<td style="padding:11px 0;border-bottom:1px solid #c9c9c9;background:#ffffff;color:#222222;font-size:14px;line-height:1.5;text-align:right;width:60px;">' . \esc_html($item_qty) . '</td>'
                . '</tr>';
        }

        if ($rows_html === '') {
            $rows_html = '<tr>'
                . '<td style="padding:11px 10px;border-bottom:1px solid #c9c9c9;background:#ffffff;color:#222222;font-size:14px;line-height:1.5;" colspan="2">' . \esc_html__('No items available', 'granola') . '</td>'
                . '</tr>';
        }

        $restore_html = '';
        if (!empty($restore_url)) {
            $restore_html = '<table role="presentation" cellspacing="0" cellpadding="0" style="margin-top:20px;"><tr><td style="background:#5d5d5d;border:1px solid #5d5d5d;padding:0;">'
                . '<a href="' . \esc_url($restore_url) . '" style="display:inline-block;padding:10px 14px;color:#ffffff;text-decoration:none;text-transform:uppercase;font-size:11px;font-weight:400;letter-spacing:0.08em;font-family:Helvetica,sans-serif;">'
                . \esc_html(\__(self::RESTORE_LINK_TEXT, 'granola'))
                . '</a>'
                . '</td></tr></table>';
        }

        $sales_notes_html = '';
        if (!empty($form_data['sales_notes'])) {
            $sales_notes_html = '<tr>'
                . '<td style="width:34%;padding:9px 14px;vertical-align:top;border-right:1px solid #9e9e9e;background:rgba(249, 247, 241, 0.50);font-weight:400;text-transform:uppercase;font-size:11px;letter-spacing:0.06em;">' . \esc_html(__('Sales notes', 'granola')) . '</td>'
                . '<td style="padding:9px 14px;vertical-align:top;font-size:13px;color:#222222;">' . nl2br(\esc_html($form_data['sales_notes'])) . '</td>'
                . '</tr>';
        }

        $terms_html = '';
        foreach ($terms_urls as $terms_label => $terms_url) {
            if (!is_string($terms_label) || $terms_label === '' || !is_string($terms_url) || $terms_url === '') {
                continue;
            }

            $terms_html .= '<p style="margin:0 0 2px;font-size:12px;line-height:1.5;"><a href="' . \esc_url($terms_url) . '" style="color:#222222;text-decoration:none;">' . \esc_html($terms_label) . '</a></p>';
        }

        $logo_html = '';
        if ($logo_url !== '') {
            $logo_html = '<img src="' . \esc_url($logo_url) . '" alt="' . \esc_attr($site_name) . '" style="width:127px;height:24px;display:block;margin:0 auto 8px;object-fit:contain;">';
        }

        return '<!doctype html>'
            . '<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="margin:0;padding:0;background:#ffffff;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#ffffff;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;">'
            . '<tr><td style="padding:26px 0 14px;background:#F9F7F1;color:#222222;text-align:center;">'
            . $logo_html
            . '<h1 style="margin:0 0 4px;font-size:16px;line-height:1.3;font-weight:400;text-transform:uppercase;letter-spacing:0.08em;color:#222222;">' . \esc_html(\__('Your Millboard Quote', 'granola')) . '</h1>'
            . '<p style="margin:0;font-size:11px;line-height:1.5;color:#444444;">' . \esc_html(sprintf(\__('Date: %s', 'granola'), \wp_date('Y-m-d H:i'))) . '</p>'
            . '</td></tr>'
            . '<tr><td style="border-top:2px solid #8a9623;font-size:0;line-height:0;">&nbsp;</td></tr>'
            . '<tr><td style="padding:28px 64px 24px;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #9e9e9e;border-collapse:collapse;margin:0 0 30px;">'
            . '<tr>'
            . '<td style="width:34%;padding:9px 14px;vertical-align:top;border-right:1px solid #9e9e9e;border-bottom:1px solid #9e9e9e;background:rgba(249, 247, 241, 0.50);font-weight:400;text-transform:uppercase;font-size:11px;letter-spacing:0.06em;">' . \esc_html(\__('Company', 'granola')) . '</td>'
            . '<td style="padding:9px 14px;vertical-align:top;border-bottom:1px solid #9e9e9e;font-size:13px;color:#222222;">' . \esc_html($form_data['company_name']) . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="width:34%;padding:9px 14px;vertical-align:top;border-right:1px solid #9e9e9e;border-bottom:1px solid #9e9e9e;background:rgba(249, 247, 241, 0.50);font-weight:400;text-transform:uppercase;font-size:11px;letter-spacing:0.06em;">' . \esc_html(\__('Contact', 'granola')) . '</td>'
            . '<td style="padding:9px 14px;vertical-align:top;border-bottom:1px solid #9e9e9e;font-size:13px;color:#222222;">' . \esc_html($form_data['contact_name']) . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="width:34%;padding:9px 14px;vertical-align:top;border-right:1px solid #9e9e9e;border-bottom:1px solid #9e9e9e;background:rgba(249, 247, 241, 0.50);font-weight:400;text-transform:uppercase;font-size:11px;letter-spacing:0.06em;">' . \esc_html(\__('Email', 'granola')) . '</td>'
            . '<td style="padding:9px 14px;vertical-align:top;border-bottom:1px solid #9e9e9e;font-size:13px;color:#222222;">' . \esc_html($form_data['email_address']) . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="width:34%;padding:9px 14px;vertical-align:top;border-right:1px solid #9e9e9e;border-bottom:1px solid #9e9e9e;background:rgba(249, 247, 241, 0.50);font-weight:400;text-transform:uppercase;font-size:11px;letter-spacing:0.06em;">' . \esc_html(\__('Phone', 'granola')) . '</td>'
            . '<td style="padding:9px 14px;vertical-align:top;border-bottom:1px solid #9e9e9e;font-size:13px;color:#222222;">' . \esc_html($form_data['phone_number']) . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td style="width:34%;padding:9px 14px;vertical-align:top;border-right:1px solid #9e9e9e;' . ($sales_notes_html !== '' ? 'border-bottom:1px solid #9e9e9e;' : '') . 'background:rgba(249, 247, 241, 0.50);font-weight:400;text-transform:uppercase;font-size:11px;letter-spacing:0.06em;">' . \esc_html(\__('Customer reference', 'granola')) . '</td>'
            . '<td style="padding:9px 14px;vertical-align:top;' . ($sales_notes_html !== '' ? 'border-bottom:1px solid #9e9e9e;' : '') . 'font-size:13px;color:#222222;">' . \esc_html($form_data['customer_reference_number']) . '</td>'
            . '</tr>'
            . $sales_notes_html
            . '</table>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-bottom:14px;">'
            . '<thead><tr>'
            . '<th style="text-align:left;padding:9px 10px;font-size:12px;font-weight:400;color:#ffffff;background:#62554D;">' . \esc_html(\__('Your items', 'granola')) . '</th>'
            . '<th style="text-align:left;padding:9px 10px;font-size:12px;font-weight:400;color:#ffffff;background:#62554D;width:60px;">&nbsp;</th>'
            . '</tr></thead>'
            . '<tbody>'
            . $rows_html
            . '</tbody></table>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-top:8px;">'
            . '<tr>'
            . '<td style="padding:12px 0 2px;border-top:2px solid #585858;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:#222222;">' . \esc_html(\__('Total', 'granola')) . '</td>'
            . '<td style="padding:12px 0 2px;border-top:2px solid #585858;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:#222222;text-align:right;">' . \esc_html((string) $cart_data['total']) . '</td>'
            . '</tr>'
            . '</table>'
            . '<p style="margin:0;font-size:11px;line-height:1.5;color:#444444;">' . \esc_html(\__('Prices are valid for 30 calendar days', 'granola')) . '</p>'
            . $restore_html
            . '<div style="margin-top:72px;">'
            . '<p style="margin:0 0 4px;font-size:18px;line-height:1.3;font-weight:400;text-transform:uppercase;letter-spacing:0.08em;color:#222222;">' . \esc_html(\__('Terms and conditions', 'granola')) . '</p>'
            . $terms_html
            . '</div>'
            . '</td></tr>'
            . '<tr><td style="padding:14px 28px;border-top:1px solid #e5e7eb;background:#f9fafb;color:#6b7280;font-size:12px;line-height:1.5;text-align:center;">'
            . \esc_html($site_name)
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private static function stream_pdf(array $form_data, array $cart_data, string $restore_url = ''): void
    {
        $pdf_content = self::generate_quote_pdf($form_data, $cart_data, $restore_url);
        $filename = 'millboard-quote-' . \gmdate('Ymd-His') . '.pdf';

        while (\ob_get_level() > 0) {
            \ob_end_clean();
        }

        \status_header(200);
        \nocache_headers();
        \header('Content-Type: application/pdf');
        \header('Content-Disposition: attachment; filename="' . $filename . '"');
        \header('Content-Transfer-Encoding: binary');
        \header('Accept-Ranges: none');
        \header('Content-Length: ' . \strlen($pdf_content));

        echo $pdf_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private static function get_quote_restore_url(array $cart_data): string
    {
        if (empty($cart_data['items']) || !is_array($cart_data['items'])) {
            return '';
        }

        $payload = [
            'items' => $cart_data['items'],
        ];

        $encoded_payload = self::encode_restore_payload($payload);
        $signature = self::sign_restore_payload($encoded_payload);

        return (string) \add_query_arg(
            [
                'quote_restore_data' => $encoded_payload,
                'quote_restore_sig' => $signature,
            ],
            \wc_get_cart_url()
        );
    }

    public static function maybe_restore_quote(): void
    {
        if (\is_admin() || !\function_exists('is_cart') || !\is_cart()) {
            return;
        }

        if (empty($_GET['quote_restore_data']) || empty($_GET['quote_restore_sig'])) {
            return;
        }

        $encoded_payload = \sanitize_text_field(\wp_unslash($_GET['quote_restore_data']));
        $provided_signature = \sanitize_text_field(\wp_unslash($_GET['quote_restore_sig']));

        if ($encoded_payload === '' || $provided_signature === '') {
            return;
        }

        $expected_signature = self::sign_restore_payload($encoded_payload);

        if (!\hash_equals($expected_signature, $provided_signature)) {
            if (\function_exists('wc_add_notice')) {
                \wc_add_notice(\__('Quote link is invalid.', 'granola'), 'error');
            }

            \wp_safe_redirect(\wc_get_cart_url());
            exit;
        }

        $restore_data = self::decode_restore_payload($encoded_payload);

        if (!is_array($restore_data) || empty($restore_data['items']) || !is_array($restore_data['items'])) {
            if (\function_exists('wc_add_notice')) {
                \wc_add_notice(\__('Quote link is invalid.', 'granola'), 'error');
            }

            \wp_safe_redirect(\wc_get_cart_url());
            exit;
        }

        if (!\function_exists('WC') || empty(\WC()->cart)) {
            if (\function_exists('wc_load_cart')) {
                \wc_load_cart();
            }
        }

        if (empty(\WC()->cart)) {
            \wp_safe_redirect(\wc_get_cart_url());
            exit;
        }

        $items_to_restore = self::sanitize_restore_items($restore_data['items']);

        if (empty($items_to_restore)) {
            if (\function_exists('wc_add_notice')) {
                \wc_add_notice(\__('Quote contains no valid items to restore.', 'granola'), 'error');
            }

            \wp_safe_redirect(\wc_get_cart_url());
            exit;
        }

        $existing_items = self::build_items_snapshot_from_live_cart();
        \WC()->cart->empty_cart();

        $added = 0;
        foreach ($items_to_restore as $item) {
            $cart_item_key = \WC()->cart->add_to_cart($item['product_id'], $item['quantity'], $item['variation_id'], $item['variation']);
            if ($cart_item_key) {
                $added++;
            }
        }

        if ($added < 1 && !empty($existing_items)) {
            foreach ($existing_items as $item) {
                \WC()->cart->add_to_cart($item['product_id'], $item['quantity'], $item['variation_id'], $item['variation']);
            }
        }

        if (\function_exists('wc_add_notice')) {
            if ($added > 0) {
                \wc_add_notice(\__('Quote restored to your basket.', 'granola'), 'success');
            } else {
                \wc_add_notice(\__('Unable to restore quote items.', 'granola'), 'error');
            }
        }

        \wp_safe_redirect(\wc_get_cart_url());
        exit;
    }

    private static function sanitize_restore_items(array $items): array
    {
        $sanitized = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $product_id = isset($item['product_id']) ? (int) $item['product_id'] : 0;
            $variation_id = isset($item['variation_id']) ? (int) $item['variation_id'] : 0;
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;

            if ($product_id < 1 || $quantity < 1) {
                continue;
            }

            $target_id = $variation_id > 0 ? $variation_id : $product_id;
            $product = \wc_get_product($target_id);

            if (!$product instanceof \WC_Product || !$product->exists()) {
                continue;
            }

            $variation = [];
            if (!empty($item['variation']) && is_array($item['variation'])) {
                foreach ($item['variation'] as $key => $value) {
                    $variation[(string) $key] = \sanitize_text_field((string) $value);
                }
            }

            $sanitized[] = [
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $quantity,
                'variation' => $variation,
            ];
        }

        return $sanitized;
    }

    private static function is_valid_email_address(string $email): bool
    {
        $email = trim($email);

        if ($email === '' || strlen($email) > 254) {
            return false;
        }

        if (!\is_email($email)) {
            return false;
        }

        return (bool) preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $email);
    }

    private static function has_required_form_fields(array $form_data): bool
    {
        $required_fields = [
            'company_name',
            'contact_name',
            'email_address',
            'phone_number',
            'customer_reference_number',
        ];

        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $form_data) || trim((string) $form_data[$field]) === '') {
                return false;
            }
        }

        return true;
    }

    private static function build_items_snapshot_from_live_cart(): array
    {
        if (!\function_exists('WC') || empty(\WC()->cart)) {
            return [];
        }

        $items = [];

        foreach (\WC()->cart->get_cart() as $cart_item) {
            if (empty($cart_item['data']) || !$cart_item['data'] instanceof \WC_Product) {
                continue;
            }

            $quantity = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 0;
            if ($quantity < 1) {
                continue;
            }

            $items[] = [
                'product_id' => isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0,
                'variation_id' => isset($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0,
                'quantity' => $quantity,
                'variation' => isset($cart_item['variation']) && is_array($cart_item['variation']) ? $cart_item['variation'] : [],
            ];
        }

        return $items;
    }

    private static function encode_restore_payload(array $payload): string
    {
        $json = \wp_json_encode($payload);

        if (!is_string($json) || $json === '') {
            return '';
        }

        return \rtrim(\strtr(\base64_encode($json), '+/', '-_'), '=');
    }

    private static function decode_restore_payload(string $encoded_payload): ?array
    {
        if ($encoded_payload === '') {
            return null;
        }

        $normalized = \strtr($encoded_payload, '-_', '+/');
        $padding = \strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= \str_repeat('=', 4 - $padding);
        }

        $decoded = \base64_decode($normalized, true);

        if ($decoded === false) {
            return null;
        }

        $payload = \json_decode($decoded, true);

        return is_array($payload) ? $payload : null;
    }

    private static function sign_restore_payload(string $encoded_payload): string
    {
        return \hash_hmac('sha256', $encoded_payload, (string) \wp_salt('auth'));
    }

    private static function redirect_with_notice(string $message, string $type = 'notice'): void
    {
        if (function_exists('wc_add_notice')) {
            \wc_add_notice($message, $type);
        }

        \wp_safe_redirect(\wc_get_cart_url());
        exit;
    }
}
