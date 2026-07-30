<?php

namespace Theme\WooCommerce;

class OrderEssentials
{
    private const ENDPOINT = 'order-essentials';
    private const SESSION_PROJECT_TYPE = 'millboard_order_essentials_project_type';
    private const SESSION_FFL = 'millboard_order_essentials_ffl';
    private const SESSION_ACOUSTIC_PADS = 'millboard_order_essentials_acoustic_pads';

    /**
     * Finished floor level lookups, from the internal calculator sections 5.6 and
     * 5.7. These are engineering constants rather than merchandising settings, so
     * they live in code with a filter for overrides rather than in ACF.
     */

    /** 5.6 post height: [FFL min (exclusive), FFL max (inclusive), postH in metres] */
    private const POST_HEIGHT_TABLE = [
        [1, 330, 0.6],
        [330, 490, 0.75],
        [490, 740, 1.0],
        [740, 1140, 1.5],
    ];

    /**
     * 5.7 DuoLift, per joist config: [FFL min, FFL max, joints per 10 supports,
     * risers per 10 supports]. Cradles and feet are always 1 per 10 supports.
     */
    private const DUOLIFT_TABLES = [
        'pp50' => [
            [97, 142, 0, 0], [143, 162, 1, 0], [163, 207, 1, 1], [208, 252, 1, 2],
            [253, 297, 1, 2], [298, 342, 1, 3], [343, 387, 1, 4], [388, 432, 1, 5],
        ],
        'pp125' => [
            [172, 217, 0, 0], [218, 237, 1, 0], [238, 282, 1, 1], [283, 327, 1, 2],
            [328, 372, 1, 2], [373, 417, 1, 3], [418, 462, 1, 4], [463, 507, 1, 5],
        ],
        'ds51' => [
            [98, 143, 0, 0], [144, 163, 1, 0], [164, 208, 1, 1], [209, 253, 1, 2],
            [254, 298, 1, 2], [299, 343, 1, 3], [344, 388, 1, 4], [389, 433, 1, 5],
        ],
        'ds99' => [
            [146, 191, 0, 0], [192, 211, 1, 0], [212, 256, 1, 1], [257, 301, 1, 2],
            [302, 346, 1, 2], [347, 391, 1, 3], [392, 436, 1, 4], [437, 481, 1, 5],
        ],
    ];

    /** 5.7 supports per m2: config => [residential, commercial] */
    private const SUPPORT_MULTIPLIERS = [
        'pp50' => [6.5, 9.0],
        'pp125' => [3.0, 5.5],
        'ds51' => [3.84, 6.68],
        'ds99' => [2.32, 3.8],
    ];

    /** The joist SKU that identifies each config. */
    private const CONFIG_JOIST_SKUS = [
        'pp50' => 'P0505B240',
        'pp125' => 'P1205B300',
        'ds51' => 'K5168J360',
        'ds99' => 'K9968J360',
    ];

    /** DuoLift component SKUs (5.7). */
    private const DUOLIFT_SKUS = [
        'cradles' => 'PMCP010',
        'joints' => 'PMLP010',
        'risers' => 'PMRP010',
        'feet' => 'PMFP010',
        'acoustic' => 'PMAP010',
    ];

    private const POST_SKU = 'P1010B300';
    private const SESSION_ESSENTIALS_DECLINED = 'millboard_order_essentials_declined';
    private const CHECKOUT_ACK_FIELD = 'millboard_order_essentials_ack';
    private const QUERY_ADDED_ESSENTIALS = 'millboard_essentials_added';

    public static function init(): void
    {
        \add_action('init', [__CLASS__, 'register_endpoint']);
        \add_action('template_redirect', [__CLASS__, 'maybe_redirect_cart_to_order_essentials'], 8);
        \add_action('template_redirect', [__CLASS__, 'maybe_handle_cart_submission']);
        \add_filter('granola/component/site-main', [__CLASS__, 'filter_site_main_on_order_essentials'], 20);
        \add_filter('the_content', [__CLASS__, 'strip_order_essentials_page_blocks'], 99);
        \add_action('woocommerce_checkout_after_terms_and_conditions', [__CLASS__, 'render_checkout_acknowledgement_field']);
        \add_action('woocommerce_checkout_process', [__CLASS__, 'validate_checkout_acknowledgement']);
        \add_action('woocommerce_checkout_create_order', [__CLASS__, 'capture_order_essentials_meta'], 20, 2);
        \add_action('woocommerce_admin_order_data_after_order_details', [__CLASS__, 'render_admin_order_essentials_meta']);
        \add_action('woocommerce_add_to_cart', [__CLASS__, 'clear_declined_essentials_on_add']);
        \add_action('woocommerce_cart_item_removed', [__CLASS__, 'clear_declined_essentials_if_cart_empty']);
        \add_action('woocommerce_cart_emptied', [__CLASS__, 'clear_declined_essentials']);
    }

    /**
     * Remove the inherited Basket page header from site-main when rendering the dedicated Order Essentials step.
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public static function filter_site_main_on_order_essentials(array $args): array
    {
        if (!self::is_order_essentials_request()) {
            return $args;
        }

        unset($args['header']);

        return $args;
    }

    /**
     * On the Order Essentials endpoint, strip all blocks from the cart page
     * content and return only the WooCommerce cart shortcode output, so the
     * extra HTML/ACF blocks the cart page contains do not appear.
     */
    public static function strip_order_essentials_page_blocks(string $content): string
    {
        if (!self::is_order_essentials_request()) {
            return $content;
        }

        return \do_shortcode('[woocommerce_cart]');
    }

    public static function register_endpoint(): void
    {
        \add_rewrite_endpoint(self::ENDPOINT, EP_PAGES);
    }

    public static function is_order_essentials_request(): bool
    {
        if (!\function_exists('is_cart') || !\is_cart()) {
            return false;
        }

        $endpoint_value = \get_query_var(self::ENDPOINT, null);

        return $endpoint_value !== null;
    }

    public static function get_order_essentials_url(): string
    {
        return \wc_get_endpoint_url(self::ENDPOINT, '', \wc_get_cart_url());
    }

    public static function get_order_essentials_added_url(): string
    {
        return \add_query_arg(self::QUERY_ADDED_ESSENTIALS, '1', self::get_order_essentials_url());
    }

    private static function should_show_added_modal(): bool
    {
        return isset($_GET[self::QUERY_ADDED_ESSENTIALS])
            && (string) \wp_unslash($_GET[self::QUERY_ADDED_ESSENTIALS]) === '1';
    }

    public static function maybe_redirect_cart_to_order_essentials(): void
    {
        if (!\function_exists('is_cart') || !\is_cart() || self::is_order_essentials_request()) {
            return;
        }

        if (!\function_exists('WC') || !\WC()->cart instanceof \WC_Cart || \WC()->cart->is_empty()) {
            return;
        }

        if (self::has_declined_essentials()) {
            return;
        }

        $context = self::get_cart_step_context();

        if (empty($context['has_recommendations'])) {
            return;
        }

        \wp_safe_redirect(self::get_order_essentials_url());
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_cart_step_context(): array
    {
        $project_type = self::get_project_type();
        $recommendations = self::get_recommendations($project_type);
        $outstanding_count = 0;

        foreach ($recommendations as $recommendation) {
            $outstanding_count += (int) ($recommendation['missing_qty'] ?? 0);
        }

        // Only products with boards_per_sqm contribute to area, so passing no target
        // exclusions here cannot skew it: fixings never carry that field.
        $source_lines = (\function_exists('WC') && \WC()->cart instanceof \WC_Cart && !\WC()->cart->is_empty())
            ? self::get_source_cart_lines([])
            : [];
        $ffl = self::get_ffl();
        $ffl_needed = self::ffl_is_needed($source_lines);
        $config = self::detect_subframe_config($source_lines);

        return [
            'project_type' => $project_type,
            'recommendations' => $recommendations,
            'has_recommendations' => !empty($recommendations),
            'has_outstanding_recommendations' => $outstanding_count > 0,
            'recommendation_source_label' => self::get_recommendation_source_label(),
            'disclaimer_url' => 'https://millboard.com/en-us/installation-guides/',
            'show_added_modal' => self::should_show_added_modal(),
            // Derived project area in m2, from the boards in the basket.
            'project_area' => round(self::get_derived_project_area($source_lines), 2),
            'subframe_config' => $config,
            // The finished floor level cannot be inferred, so it is asked for. When
            // the basket contains DuoLift components or posts and no FFL has been
            // given, those quantities cannot be worked out and the step should
            // prompt rather than silently omit them.
            'ffl' => $ffl,
            'ffl_needed' => $ffl_needed,
            'ffl_missing' => $ffl_needed && $ffl < 1,
            'ffl_out_of_range' => self::ffl_is_out_of_range($source_lines),
            'acoustic_pads' => self::acoustic_pads_enabled(),
        ];
    }

    private static function get_recommendation_source_label(): string
    {
        if (!\function_exists('WC') || !\WC()->cart instanceof \WC_Cart || \WC()->cart->is_empty()) {
            return '';
        }

        $matrix = self::get_matrix();

        if (empty($matrix)) {
            return '';
        }

        $matched_source_ids = [];
        $single_source_label = '';
        $target_ids = [];

        foreach ($matrix as $rule) {
            $target_id = (int) ($rule['target_product_id'] ?? 0);

            if ($target_id > 0) {
                $target_ids[$target_id] = true;
            }
        }

        foreach (\WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'] ?? null;

            if (!$product instanceof \WC_Product || self::is_sample_product($product)) {
                continue;
            }

            $source_id = self::resolve_source_product_id($product);

            if ($source_id < 1 || isset($target_ids[$source_id])) {
                continue;
            }

            $source_category_slugs = self::get_product_category_slugs($source_id);
            $source_quantity = (int) ($cart_item['quantity'] ?? 0);

            if ($source_quantity < 1) {
                continue;
            }

            foreach ($matrix as $rule) {
                if (!self::rule_matches_source($rule, $source_id, $source_category_slugs)) {
                    continue;
                }

                $matched_source_ids[$source_id] = true;

                if (count($matched_source_ids) > 1) {
                    return __('the products in your basket', 'granola');
                }

                $label = '';

                $rule_product_ids = isset($rule['source_product_ids']) && \is_array($rule['source_product_ids'])
                    ? $rule['source_product_ids']
                    : [];

                if (!empty($rule_product_ids) && \in_array($source_id, $rule_product_ids, true)) {
                    $source_product = \wc_get_product($source_id);

                    if ($source_product instanceof \WC_Product) {
                        $name = trim((string) $source_product->get_name());

                        if ($name !== '') {
                            $label = $name;
                        }
                    }
                }

                $rule_category_slugs = isset($rule['source_category_slugs']) && \is_array($rule['source_category_slugs'])
                    ? $rule['source_category_slugs']
                    : [];
                $matched_category_slugs = !empty($rule_category_slugs)
                    ? array_values(array_intersect($rule_category_slugs, $source_category_slugs))
                    : [];

                if ($label === '' && !empty($matched_category_slugs)) {
                    $category_name = self::get_category_name_by_slug((string) $matched_category_slugs[0]);

                    if ($category_name !== '') {
                        $label = $category_name;
                    }
                }

                if ($label === '') {
                    $fallback_name = trim((string) $product->get_name());

                    if ($fallback_name !== '') {
                        $label = $fallback_name;
                    }
                }

                if ($single_source_label === '' && $label !== '') {
                    $single_source_label = $label;
                }

                break;
            }
        }

        return $single_source_label;
    }

    private static function get_category_name_by_slug(string $slug): string
    {
        if ($slug === '') {
            return '';
        }

        $term = \get_term_by('slug', $slug, 'product_cat');

        if ($term instanceof \WP_Term) {
            $name = trim((string) $term->name);

            if ($name !== '') {
                return $name;
            }
        }

        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    public static function maybe_handle_cart_submission(): void
    {
        if (!self::is_order_essentials_request() || strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            return;
        }

        if (!isset($_POST['woocommerce-cart-nonce']) || !\wp_verify_nonce((string) \wp_unslash($_POST['woocommerce-cart-nonce']), 'woocommerce-cart')) {
            return;
        }

        if (isset($_POST['millboard_order_essentials_project_type'])) {
            self::set_project_type((string) \wp_unslash($_POST['millboard_order_essentials_project_type']));
        }

        // The FFL field is only rendered alongside the acoustic pad checkbox, so its
        // presence marks a settings submission. An unticked checkbox is not posted at
        // all, hence reading it only when the FFL came with it - otherwise the option
        // would be cleared by every "add to basket" post.
        if (isset($_POST[self::SESSION_FFL])) {
            self::set_ffl((int) \wc_stock_amount(\wp_unslash((string) $_POST[self::SESSION_FFL])));
            self::set_acoustic_pads(isset($_POST[self::SESSION_ACOUSTIC_PADS]));
        }

        $add_all = isset($_POST['millboard_add_all_essentials']);
        $add_selected = isset($_POST['millboard_add_selected_essentials']);
        $single_add_product_id = isset($_POST['millboard_add_essential_item'])
            ? (int) max(0, \wc_stock_amount(\wp_unslash((string) $_POST['millboard_add_essential_item'])))
            : 0;
        $single_remove_product_id = isset($_POST['millboard_remove_essential_item'])
            ? (int) max(0, \wc_stock_amount(\wp_unslash((string) $_POST['millboard_remove_essential_item'])))
            : 0;

        $continue_without_essentials = isset($_POST['millboard_continue_to_basket']);

        if (!$add_all && !$add_selected && $single_add_product_id < 1 && $single_remove_product_id < 1 && !$continue_without_essentials) {
            return;
        }

        if ($continue_without_essentials) {
            self::mark_essentials_declined();
            \wp_safe_redirect(\wc_get_cart_url());
            exit;
        }

        if (!\function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return;
        }

        $project_type = self::get_project_type();
        $recommendations = self::get_recommendations($project_type);
        $items_added = 0;

        if ($single_add_product_id > 0) {
            $quantity_map = isset($_POST['millboard_essentials_qty']) && \is_array($_POST['millboard_essentials_qty'])
                ? $_POST['millboard_essentials_qty']
                : [];

            $quantity = 1;

            if (isset($quantity_map[$single_add_product_id])) {
                $quantity = (int) max(0, \wc_stock_amount(\wp_unslash((string) $quantity_map[$single_add_product_id])));
            } else {
                foreach ($recommendations as $recommendation) {
                    if ((int) ($recommendation['product_id'] ?? 0) === $single_add_product_id) {
                        $quantity = (int) max(1, (int) ($recommendation['missing_qty'] ?? 1));
                        break;
                    }
                }
            }

            if ($quantity < 1) {
                $quantity = 1;
            }

            if (self::add_product_to_cart($single_add_product_id, $quantity)) {
                \wc_add_notice(\__('Item has been added to your basket.', 'granola'), 'success');
                \wp_safe_redirect(self::get_order_essentials_added_url());
            } else {
                \wc_add_notice(\__('Unable to add this item to your basket.', 'granola'), 'error');
                \wp_safe_redirect(self::get_order_essentials_url());
            }

            exit;
        }

        if ($single_remove_product_id > 0) {
            if (self::remove_product_from_cart($single_remove_product_id)) {
                \wc_add_notice(\__('Item has been removed from your basket.', 'granola'), 'success');
            } else {
                \wc_add_notice(\__('Item was not found in your basket.', 'granola'), 'notice');
            }

            \wp_safe_redirect(self::get_order_essentials_url());
            exit;
        }

        if ($add_all) {
            foreach ($recommendations as $recommendation) {
                $missing_qty = (int) ($recommendation['missing_qty'] ?? 0);

                if ($missing_qty < 1) {
                    continue;
                }

                if (self::add_product_to_cart((int) $recommendation['product_id'], $missing_qty)) {
                    $items_added++;
                }
            }
        }

        if ($add_selected) {
            $selected_map = isset($_POST['millboard_essentials_selected']) && \is_array($_POST['millboard_essentials_selected'])
                ? $_POST['millboard_essentials_selected']
                : [];

            $quantity_map = isset($_POST['millboard_essentials_qty']) && \is_array($_POST['millboard_essentials_qty'])
                ? $_POST['millboard_essentials_qty']
                : [];

            foreach ($recommendations as $recommendation) {
                $product_id = (int) ($recommendation['product_id'] ?? 0);

                if ($product_id < 1 || !isset($selected_map[$product_id])) {
                    continue;
                }

                $raw_quantity = $quantity_map[$product_id] ?? $recommendation['missing_qty'] ?? 0;
                $quantity = (int) max(0, \wc_stock_amount(\wp_unslash((string) $raw_quantity)));

                if ($quantity < 1) {
                    continue;
                }

                if (self::add_product_to_cart($product_id, $quantity)) {
                    $items_added++;
                }
            }
        }

        if ($items_added > 0) {
            \wc_add_notice(\__('Essentials have been added to your basket.', 'granola'), 'success');
            \wp_safe_redirect(self::get_order_essentials_added_url());
        } else {
            \wc_add_notice(\__('No essentials were added. Please select at least one item.', 'granola'), 'notice');
            \wp_safe_redirect(self::get_order_essentials_url());
        }

        exit;
    }

    public static function render_checkout_acknowledgement_field(): void
    {
        if (!self::has_outstanding_recommendations()) {
            return;
        }

        $field_name = self::CHECKOUT_ACK_FIELD;
        $checked = isset($_POST[$field_name]);
        ?>
        <p class="form-row validate-required millboard-order-essentials-ack">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="<?php echo esc_attr($field_name); ?>">
                <input
                    type="checkbox"
                    class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
                    name="<?php echo esc_attr($field_name); ?>"
                    id="<?php echo esc_attr($field_name); ?>"
                    value="1"
                    <?php checked($checked, true); ?>
                />
                <span>
                    <?php esc_html_e('I understand I am proceeding without all recommended fixings and accept responsibility for any shortfalls or surplus materials.', 'granola'); ?>
                </span>
                <abbr class="required" title="<?php esc_attr_e('required', 'woocommerce'); ?>">*</abbr>
            </label>
        </p>
        <?php
    }

    public static function validate_checkout_acknowledgement(): void
    {
        if (!self::has_outstanding_recommendations()) {
            return;
        }

        if (!isset($_POST[self::CHECKOUT_ACK_FIELD])) {
            \wc_add_notice(\__('Please acknowledge that you are proceeding without all recommended essentials.', 'granola'), 'error');
        }
    }

    public static function capture_order_essentials_meta(\WC_Order $order, array $data): void
    {
        $project_type = self::get_project_type();
        $recommendations = self::get_recommendations($project_type);
        $outstanding = array_values(array_filter($recommendations, static function (array $item): bool {
            return (int) ($item['missing_qty'] ?? 0) > 0;
        }));

        $acknowledged = isset($data[self::CHECKOUT_ACK_FIELD]);

        $order->update_meta_data('_millboard_order_essentials_project_type', $project_type);
        $order->update_meta_data('_millboard_order_essentials_acknowledged', $acknowledged ? 'yes' : 'no');

        if (!empty($outstanding)) {
            $meta_rows = [];

            foreach ($outstanding as $item) {
                $meta_rows[] = [
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'product_name' => (string) ($item['product_name'] ?? ''),
                    'recommended_qty' => (int) ($item['recommended_qty'] ?? 0),
                    'in_cart_qty' => (int) ($item['in_cart_qty'] ?? 0),
                    'missing_qty' => (int) ($item['missing_qty'] ?? 0),
                ];
            }

            $order->update_meta_data('_millboard_order_essentials_outstanding', \wp_json_encode($meta_rows));
        }
    }

    public static function render_admin_order_essentials_meta(\WC_Order $order): void
    {
        $project_type = (string) $order->get_meta('_millboard_order_essentials_project_type', true);
        $acknowledged = (string) $order->get_meta('_millboard_order_essentials_acknowledged', true);
        $outstanding_json = (string) $order->get_meta('_millboard_order_essentials_outstanding', true);

        if ($project_type === '' && $acknowledged === '' && $outstanding_json === '') {
            return;
        }

        echo '<div class="order_data_column">';
        echo '<h4>' . esc_html__('Order essentials', 'granola') . '</h4>';

        if ($project_type !== '') {
            echo '<p><strong>' . esc_html__('Project type:', 'granola') . '</strong> ' . esc_html(ucfirst($project_type)) . '</p>';
        }

        if ($acknowledged !== '') {
            echo '<p><strong>' . esc_html__('Proceeded without all essentials:', 'granola') . '</strong> ' . esc_html($acknowledged === 'yes' ? \__('Yes', 'granola') : \__('No', 'granola')) . '</p>';
        }

        if ($outstanding_json !== '') {
            $outstanding_items = json_decode($outstanding_json, true);

            if (\is_array($outstanding_items) && !empty($outstanding_items)) {
                echo '<p><strong>' . esc_html__('Outstanding recommendations at checkout:', 'granola') . '</strong></p>';
                echo '<ul>';

                foreach ($outstanding_items as $item) {
                    $name = isset($item['product_name']) ? (string) $item['product_name'] : '';
                    $qty = isset($item['missing_qty']) ? (int) $item['missing_qty'] : 0;

                    if ($name === '' || $qty < 1) {
                        continue;
                    }

                    echo '<li>' . esc_html($name . ' x ' . $qty) . '</li>';
                }

                echo '</ul>';
            }
        }

        echo '</div>';
    }

    public static function has_outstanding_recommendations(): bool
    {
        $recommendations = self::get_recommendations(self::get_project_type());

        foreach ($recommendations as $recommendation) {
            if ((int) ($recommendation['missing_qty'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_recommendations(?string $project_type = null): array
    {
        if (!\function_exists('WC') || !\WC()->cart instanceof \WC_Cart || \WC()->cart->is_empty()) {
            return [];
        }

        $project_type = self::normalise_project_type($project_type ?? self::get_project_type());
        $matrix = self::get_matrix();

        if (empty($matrix)) {
            return [];
        }

        $target_requirements = [];
        $target_rules = [];
        $target_ids = [];

        foreach ($matrix as $rule) {
            $target_id = (int) $rule['target_product_id'];

            if ($target_id < 1) {
                continue;
            }

            $target_ids[$target_id] = true;
            $target_rules[$target_id] = $rule;
        }

        // Resolve the basket once, then iterate RULES on the outside. The loop
        // order matters: a per-unit rule must accumulate across every matching
        // basket line, whereas a per-m2 or per-project rule must contribute
        // exactly once no matter how many lines match it.
        $source_lines = self::get_source_cart_lines($target_ids);
        $project_area = self::get_derived_project_area($source_lines);
        $waste_multiplier = self::get_waste_multiplier();

        foreach ($matrix as $rule) {
            $target_id = (int) $rule['target_product_id'];
            $multiplier = $project_type === 'commercial'
                ? (float) $rule['commercial_multiplier']
                : (float) $rule['residential_multiplier'];

            if ($target_id < 1 || $multiplier <= 0) {
                continue;
            }

            if (!self::rule_conditions_met($rule, $source_lines)) {
                continue;
            }

            $matched = false;
            $matched_quantity = 0;

            foreach ($source_lines as $line) {
                if (!self::rule_matches_source($rule, $line['source_id'], $line['category_slugs'])) {
                    continue;
                }

                $matched = true;
                $matched_quantity += (int) $line['quantity'];
            }

            if (!$matched) {
                continue;
            }

            switch (self::normalise_basis($rule['basis'] ?? '')) {
                // Rate per square metre of derived project area. Area comes from
                // the boards in the basket via their boards_per_sqm field, which
                // is how the internal calculator converts lengths to area.
                case 'per_sqm':
                    $required = $multiplier * $project_area;
                    break;

                // A fixed quantity once per order, e.g. the DuoFix guide kit or a
                // tin of touch-up paint.
                case 'per_project':
                    $required = $multiplier;
                    break;

                // Rate per unit of the source product.
                default:
                    $required = $multiplier * $matched_quantity;
                    break;
            }

            if ($required <= 0) {
                continue;
            }

            if (!empty($rule['apply_waste'])) {
                $required *= $waste_multiplier;
            }

            $target_requirements[$target_id] = ($target_requirements[$target_id] ?? 0) + $required;
        }

        // FFL-dependent quantities (DuoLift components and posts) are table lookups
        // rather than flat rates, so they are computed rather than configured. They
        // need a $target_rules entry too: the output loop below drops any target
        // without one, which would silently discard them.
        foreach (self::get_ffl_requirements($source_lines, $project_area, $project_type) as $ffl_target_id => $ffl_quantity) {
            if ($ffl_quantity <= 0) {
                continue;
            }

            $target_requirements[$ffl_target_id] = ($target_requirements[$ffl_target_id] ?? 0) + $ffl_quantity;

            if (!isset($target_rules[$ffl_target_id])) {
                $target_rules[$ffl_target_id] = ['target_product_id' => $ffl_target_id, 'source' => 'ffl_lookup'];
            }
        }

        if (empty($target_requirements)) {
            return [];
        }

        $recommendations = [];

        foreach ($target_requirements as $target_id => $raw_required_quantity) {
            $rule = $target_rules[$target_id] ?? null;

            if (!\is_array($rule)) {
                continue;
            }

            $product = \wc_get_product($target_id);

            if (!$product instanceof \WC_Product) {
                continue;
            }

            $recommended_qty = self::apply_rounding((float) $raw_required_quantity);

            if ($recommended_qty < 1) {
                continue;
            }

            $in_cart_qty = self::get_cart_quantity_for_product($target_id);
            $missing_qty = max(0, $recommended_qty - $in_cart_qty);

            $recommendations[] = [
                'product_id' => $target_id,
                'product_name' => $product->get_name(),
                'product_url' => $product->is_visible() ? $product->get_permalink() : '',
                'product_image' => $product->get_image('woocommerce_thumbnail'),
                'recommended_qty' => $recommended_qty,
                'in_cart_qty' => $in_cart_qty,
                'missing_qty' => $missing_qty,
                'default_add_qty' => $missing_qty > 0 ? $missing_qty : 1,
            ];
        }

        usort($recommendations, static function (array $a, array $b): int {
            return ((int) $b['missing_qty']) <=> ((int) $a['missing_qty']);
        });

        return $recommendations;
    }

    private static function get_project_type(): string
    {
        if (\function_exists('WC') && \WC()->session instanceof \WC_Session) {
            $session_value = \WC()->session->get(self::SESSION_PROJECT_TYPE);

            if (\is_string($session_value) && $session_value !== '') {
                return self::normalise_project_type($session_value);
            }
        }

        return self::normalise_project_type(self::get_default_project_type());
    }

    private static function set_project_type(string $project_type): void
    {
        $project_type = self::normalise_project_type($project_type);

        if (!\function_exists('WC') || !\WC()->session instanceof \WC_Session) {
            return;
        }

        \WC()->session->set(self::SESSION_PROJECT_TYPE, $project_type);
    }

    private static function normalise_project_type(string $project_type): string
    {
        return strtolower($project_type) === 'commercial' ? 'commercial' : 'residential';
    }

    private static function get_default_project_type(): string
    {
        $default = \function_exists('get_field') ? \get_field('order_essentials_default_project_type', 'option') : null;

        if (!\is_string($default) || $default === '') {
            $default = 'residential';
        }

        return self::normalise_project_type($default);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function get_matrix(): array
    {
        $rules = \apply_filters('millboard_order_essentials_matrix', []);

        if (!\is_array($rules)) {
            $rules = [];
        }

        $rules = array_merge($rules, self::get_acf_repeater_rules());

        $normalised = [];

        foreach ($rules as $rule) {
            if (!\is_array($rule)) {
                continue;
            }

            $source = isset($rule['source']) && \is_array($rule['source']) ? $rule['source'] : $rule;
            $source_product_ids = self::normalise_int_array($source['product_ids'] ?? $rule['source_product_ids'] ?? []);
            $source_category_slugs = self::normalise_slug_array($source['category_slugs'] ?? $rule['source_category_slugs'] ?? []);
            $target_product_id = self::normalise_int_value($rule['target_product_id'] ?? 0);

            if ($target_product_id < 1 || (empty($source_product_ids) && empty($source_category_slugs))) {
                continue;
            }

            $residential_multiplier = isset($rule['residential_multiplier']) ? (float) $rule['residential_multiplier'] : 0.0;
            $commercial_multiplier = isset($rule['commercial_multiplier'])
                ? (float) $rule['commercial_multiplier']
                : $residential_multiplier;

            if ($residential_multiplier <= 0 && $commercial_multiplier <= 0) {
                continue;
            }

            $normalised[] = [
                'source_product_ids' => $source_product_ids,
                'source_category_slugs' => $source_category_slugs,
                'target_product_id' => $target_product_id,
                'residential_multiplier' => $residential_multiplier,
                'commercial_multiplier' => $commercial_multiplier,
                // How the multiplier is applied: per unit of the source (default),
                // per m2 of derived project area, or once per project.
                'basis' => self::normalise_basis((string) ($rule['basis'] ?? '')),
                'apply_waste' => !empty($rule['apply_waste']),
                'requires_category_slugs' => self::normalise_slug_array($rule['requires_category_slugs'] ?? []),
                'excludes_category_slugs' => self::normalise_slug_array($rule['excludes_category_slugs'] ?? []),
            ];
        }

        return $normalised;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function get_acf_repeater_rules(): array
    {
        if (!\function_exists('get_field')) {
            return [];
        }

        $sources = \get_field('order_essentials_recommendation_sources', 'option');

        if (!\is_array($sources)) {
            return [];
        }

        $rules = [];

        foreach ($sources as $source) {
            if (!\is_array($source)) {
                continue;
            }

            $recommendations = isset($source['recommendations']) && \is_array($source['recommendations'])
                ? $source['recommendations']
                : [];

            foreach ($recommendations as $recommendation) {
                if (!\is_array($recommendation)) {
                    continue;
                }

                $rules[] = [
                    'source_product_ids' => $source['source_product_ids'] ?? [],
                    'source_category_slugs' => $source['source_category_slugs'] ?? [],
                    'target_product_id' => $recommendation['target_product_id'] ?? 0,
                    'residential_multiplier' => $recommendation['residential_multiplier'] ?? 0,
                    'commercial_multiplier' => $recommendation['commercial_multiplier'] ?? 0,
                    'basis' => $recommendation['basis'] ?? 'per_unit',
                    'apply_waste' => !empty($recommendation['apply_waste']),
                    // Conditions live on the SOURCE row: they describe the basket as
                    // a whole, not an individual recommendation.
                    'requires_category_slugs' => $source['requires_category_slugs'] ?? [],
                    'excludes_category_slugs' => $source['excludes_category_slugs'] ?? [],
                ];
            }
        }

        return $rules;
    }

    /**
     * Finished floor level in mm, as supplied by the customer on the essentials
     * step. There is no way to infer this from a basket, which is why it is asked
     * for: every DuoLift component count and post height depends on it.
     */
    public static function get_ffl(): int
    {
        if (!\function_exists('WC') || !\WC()->session instanceof \WC_Session) {
            return 0;
        }

        return max(0, (int) \WC()->session->get(self::SESSION_FFL, 0));
    }

    private static function set_ffl(int $ffl): void
    {
        if (!\function_exists('WC') || !\WC()->session instanceof \WC_Session) {
            return;
        }

        \WC()->session->set(self::SESSION_FFL, max(0, $ffl));
    }

    public static function acoustic_pads_enabled(): bool
    {
        if (!\function_exists('WC') || !\WC()->session instanceof \WC_Session) {
            return false;
        }

        return (string) \WC()->session->get(self::SESSION_ACOUSTIC_PADS, '') === '1';
    }

    private static function set_acoustic_pads(bool $enabled): void
    {
        if (!\function_exists('WC') || !\WC()->session instanceof \WC_Session) {
            return;
        }

        \WC()->session->set(self::SESSION_ACOUSTIC_PADS, $enabled ? '1' : '0');
    }

    /**
     * 5.6: postH in metres for an FFL. The lookup is deliberately
     * "FFL > min AND FFL <= max", and an FFL of 0 or outside 1-1140mm has no
     * match, in which case the calculator shows a warning rather than guessing.
     */
    private static function lookup_post_height(int $ffl): ?float
    {
        if ($ffl < 1) {
            return null;
        }

        foreach (self::POST_HEIGHT_TABLE as $row) {
            if ($ffl > $row[0] - ($row[0] === 1 ? 1 : 0) && $ffl <= $row[1]) {
                return (float) $row[2];
            }
        }

        return null;
    }

    /**
     * 5.7: the DuoLift row for a config and FFL, as
     * ['joints' => int, 'risers' => int] per 10 supports. Null when the FFL falls
     * outside the config's supported range.
     *
     * @return array<string, int>|null
     */
    private static function lookup_duolift_row(string $config, int $ffl): ?array
    {
        $table = self::DUOLIFT_TABLES[$config] ?? null;

        if (!$table || $ffl < 1) {
            return null;
        }

        foreach ($table as $row) {
            if ($ffl >= $row[0] && $ffl <= $row[1]) {
                return ['joints' => (int) $row[2], 'risers' => (int) $row[3]];
            }
        }

        return null;
    }

    /**
     * Which subframe system is in the basket. The customer's choice of system is
     * not derivable from boards, but it IS knowable once they have added joists,
     * so no extra question is needed for it.
     *
     * @param array<int, array<string, mixed>> $source_lines
     */
    private static function detect_subframe_config(array $source_lines): ?string
    {
        foreach (self::CONFIG_JOIST_SKUS as $config => $joist_sku) {
            $joist_id = \wc_get_product_id_by_sku($joist_sku);

            if (!$joist_id) {
                continue;
            }

            foreach ($source_lines as $line) {
                if ((int) $line['source_id'] === (int) $joist_id) {
                    return $config;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $source_lines
     */
    private static function basket_has_category(array $source_lines, string $slug): bool
    {
        foreach ($source_lines as $line) {
            if (\in_array($slug, (array) $line['category_slugs'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $source_lines
     */
    private static function basket_has_sku(array $source_lines, string $sku): bool
    {
        $id = \wc_get_product_id_by_sku($sku);

        if (!$id) {
            return false;
        }

        foreach ($source_lines as $line) {
            if ((int) $line['source_id'] === (int) $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * FFL-dependent quantities: DuoLift components (5.7) and posts (5.6 with the
     * 5.4/5.5 multipliers). These are table lookups rather than flat rates, so they
     * cannot be expressed as matrix rules and are computed here.
     *
     * Returns [product_id => required quantity]. Empty when the customer has not
     * supplied an FFL, or when their basket contains no DuoLift or post products.
     *
     * @param array<int, array<string, mixed>> $source_lines
     * @return array<int, float>
     */
    private static function get_ffl_requirements(array $source_lines, float $area, string $project_type): array
    {
        $ffl = self::get_ffl();

        if ($ffl < 1 || $area <= 0) {
            return [];
        }

        $config = self::detect_subframe_config($source_lines);

        if ($config === null) {
            return [];
        }

        $commercial = ($project_type === 'commercial');
        $waste = self::get_waste_multiplier();
        $required = [];

        // ---- DuoLift components, only if the basket shows a DuoLift build ----
        if (self::basket_has_category($source_lines, 'duolift')) {
            $row = self::lookup_duolift_row($config, $ffl);
            $multipliers = self::SUPPORT_MULTIPLIERS[$config] ?? null;

            if ($row !== null && $multipliers !== null) {
                $raw_supports = $area * (float) ($commercial ? $multipliers[1] : $multipliers[0]) * $waste;
                $per_ten = $raw_supports / 10;

                $map = [
                    'cradles' => $per_ten,
                    'feet' => $per_ten,
                    'joints' => $row['joints'] > 0 ? $per_ten : 0.0,
                    'risers' => $row['risers'] > 0 ? ($raw_supports * $row['risers'] / 10) : 0.0,
                ];

                // Acoustic pads are UK only, opt-in, and the FFL used for their
                // lookup is reduced by 3mm before matching.
                if (self::acoustic_pads_enabled() && self::lookup_duolift_row($config, $ffl - 3) !== null) {
                    $map['acoustic'] = $per_ten;
                }

                foreach ($map as $key => $quantity) {
                    if ($quantity <= 0) {
                        continue;
                    }

                    $id = \wc_get_product_id_by_sku(self::DUOLIFT_SKUS[$key] ?? '');

                    if ($id) {
                        $required[(int) $id] = ($required[(int) $id] ?? 0) + $quantity;
                    }
                }
            }
        }

        // ---- Posts, for the PP125-with-posts and DS99P builds ----
        if (self::basket_has_sku($source_lines, self::POST_SKU) && \in_array($config, ['pp125', 'ds99'], true)) {
            $post_height = self::lookup_post_height($ffl);

            if ($post_height !== null) {
                if ($config === 'pp125') {
                    $factor = $commercial ? 1.4 : 1.0;
                } else {
                    $factor = $commercial ? 0.98 : 0.61;
                }

                $posts = $area * $factor * $post_height / 3 * $waste;
                $id = \wc_get_product_id_by_sku(self::POST_SKU);

                if ($id) {
                    // "Min 1 post always" per 5.4 / 5.5.
                    $required[(int) $id] = max(1.0, $posts);
                }
            }
        }

        return $required;
    }

    /**
     * What the basket needs an FFL for. Both callers need this, and they must agree:
     * if they disagree the step can prompt for a value it then ignores, or worse
     * accept one and quietly recommend nothing.
     *
     * @param array<int, array<string, mixed>> $source_lines
     * @return array{config: string|null, duolift: bool, posts: bool, needed: bool}
     */
    private static function get_ffl_needs(array $source_lines): array
    {
        $config = self::detect_subframe_config($source_lines);

        if ($config === null) {
            return ['config' => null, 'duolift' => false, 'posts' => false, 'needed' => false];
        }

        $duolift = self::basket_has_category($source_lines, 'duolift');
        $posts = self::basket_has_sku($source_lines, self::POST_SKU)
            && \in_array($config, ['pp125', 'ds99'], true);

        return [
            'config' => $config,
            'duolift' => $duolift,
            'posts' => $posts,
            'needed' => $duolift || $posts,
        ];
    }

    /**
     * Whether the basket needs an FFL that has not been given yet, so the step can
     * prompt for it instead of silently omitting DuoLift components or posts.
     *
     * @param array<int, array<string, mixed>> $source_lines
     */
    private static function ffl_is_needed(array $source_lines): bool
    {
        return self::get_ffl_needs($source_lines)['needed'];
    }

    /**
     * True when an FFL has been supplied but no lookup can use it, so the step warns
     * instead of returning nothing.
     *
     * Checked PER NEED, not across both tables: an FFL of 900mm is a valid post
     * height but is outside every DuoLift band, so on a DuoLift build it must warn
     * even though the post lookup would have succeeded.
     *
     * @param array<int, array<string, mixed>> $source_lines
     */
    private static function ffl_is_out_of_range(array $source_lines): bool
    {
        $ffl = self::get_ffl();

        if ($ffl < 1) {
            return false;
        }

        $needs = self::get_ffl_needs($source_lines);

        if (!$needs['needed']) {
            return false;
        }

        if ($needs['duolift'] && self::lookup_duolift_row((string) $needs['config'], $ffl) === null) {
            return true;
        }

        if ($needs['posts'] && self::lookup_post_height($ffl) === null) {
            return true;
        }

        return false;
    }

    /**
     * Resolve the basket once into the shape the matrix needs, so the rule loop
     * does not re-resolve products or re-read taxonomies per rule.
     *
     * Samples are excluded (ordering a sample must not recommend a project's
     * worth of fixings) and so is anything that is itself a recommendation
     * target, which would otherwise recommend against itself.
     *
     * @param array<int, bool> $target_ids
     * @return array<int, array<string, mixed>>
     */
    private static function get_source_cart_lines(array $target_ids): array
    {
        $lines = [];

        foreach (\WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'] ?? null;

            if (!$product instanceof \WC_Product) {
                continue;
            }

            if (self::is_sample_product($product)) {
                continue;
            }

            $source_id = self::resolve_source_product_id($product);

            if ($source_id < 1 || isset($target_ids[$source_id])) {
                continue;
            }

            $quantity = (int) ($cart_item['quantity'] ?? 0);

            if ($quantity < 1) {
                continue;
            }

            $lines[] = [
                'source_id' => $source_id,
                'quantity' => $quantity,
                'category_slugs' => self::get_product_category_slugs($source_id),
            ];
        }

        return $lines;
    }

    /**
     * Derive the project area in m2 from the boards in the basket.
     *
     * Uses each product's `boards_per_sqm` field, which is the same figure the
     * product calculator uses and the inverse of the internal calculator's
     * boards-per-m2 multiplier, so 100 Enhanced Grain 176mm boards at 1.54
     * resolves to 64.94 m2 exactly as the calculator's lengths mode does.
     *
     * Products without the field contribute nothing rather than guessing from raw
     * dimensions, because a board's footprint is not its effective coverage.
     *
     * @param array<int, array<string, mixed>> $source_lines
     */
    private static function get_derived_project_area(array $source_lines): float
    {
        $area = 0.0;

        foreach ($source_lines as $line) {
            $boards_per_sqm = 0.0;

            if (\function_exists('get_field')) {
                $boards_per_sqm = (float) \get_field('boards_per_sqm', $line['source_id']);
            }

            if ($boards_per_sqm <= 0) {
                $boards_per_sqm = (float) \get_post_meta($line['source_id'], 'boards_per_sqm', true);
            }

            if ($boards_per_sqm <= 0) {
                continue;
            }

            $area += ((int) $line['quantity']) / $boards_per_sqm;
        }

        return $area;
    }

    /**
     * The calculator applies a waste allowance (10% by default) to board and
     * subframe quantities. Rules opt in per recommendation via `apply_waste`.
     */
    private static function get_waste_multiplier(): float
    {
        $percent = null;

        if (\function_exists('get_field')) {
            $percent = \get_field('order_essentials_waste_percent', 'option');
        }

        if (!\is_numeric($percent)) {
            $percent = 10;
        }

        $percent = max(0.0, (float) $percent);

        return 1 + ($percent / 100);
    }

    private static function normalise_basis(string $basis): string
    {
        $basis = strtolower(trim($basis));

        return \in_array($basis, ['per_sqm', 'per_project'], true) ? $basis : 'per_unit';
    }

    /**
     * Optional whole-basket conditions on a rule, for the calculator's conditional
     * rates: the DuoSpan fascia rate applies only with a DuoSpan subframe, and the
     * DuoFix guide kit only when 126mm accent boards are NOT in the order.
     *
     * @param array<string, mixed> $rule
     * @param array<int, array<string, mixed>> $source_lines
     */
    private static function rule_conditions_met(array $rule, array $source_lines): bool
    {
        $requires = self::normalise_slug_array($rule['requires_category_slugs'] ?? []);
        $excludes = self::normalise_slug_array($rule['excludes_category_slugs'] ?? []);

        if (empty($requires) && empty($excludes)) {
            return true;
        }

        $basket_slugs = [];

        foreach ($source_lines as $line) {
            foreach ((array) $line['category_slugs'] as $slug) {
                $basket_slugs[$slug] = true;
            }
        }

        $basket_slugs = array_keys($basket_slugs);

        if (!empty($requires) && empty(array_intersect($requires, $basket_slugs))) {
            return false;
        }

        if (!empty($excludes) && !empty(array_intersect($excludes, $basket_slugs))) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $rule
     * @param array<int, string> $source_category_slugs
     */
    private static function rule_matches_source(array $rule, int $source_id, array $source_category_slugs): bool
    {
        $rule_product_ids = isset($rule['source_product_ids']) && \is_array($rule['source_product_ids'])
            ? $rule['source_product_ids']
            : [];

        $rule_category_slugs = isset($rule['source_category_slugs']) && \is_array($rule['source_category_slugs'])
            ? $rule['source_category_slugs']
            : [];

        if (!empty($rule_product_ids) && \in_array($source_id, $rule_product_ids, true)) {
            return true;
        }

        if (!empty($rule_category_slugs) && !empty(array_intersect($rule_category_slugs, $source_category_slugs))) {
            return true;
        }

        return false;
    }

    private static function resolve_source_product_id(\WC_Product $product): int
    {
        if ($product->is_type('variation')) {
            return (int) $product->get_parent_id();
        }

        return (int) $product->get_id();
    }

    /**
     * @return array<int, string>
     */
    private static function get_product_category_slugs(int $product_id): array
    {
        $terms = \get_the_terms($product_id, 'product_cat');

        if (!\is_array($terms) || empty($terms)) {
            return [];
        }

        $slugs = [];

        foreach ($terms as $term) {
            if (!$term instanceof \WP_Term) {
                continue;
            }

            $slug = (string) $term->slug;

            if ($slug === '') {
                continue;
            }

            $slugs[] = $slug;
        }

        return array_values(array_unique($slugs));
    }

    private static function get_cart_quantity_for_product(int $product_id): int
    {
        if (!\function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return 0;
        }

        $quantity = 0;

        foreach (\WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'] ?? null;

            if (!$product instanceof \WC_Product) {
                continue;
            }

            $item_product_id = (int) $product->get_id();
            $item_parent_id = $product->is_type('variation') ? (int) $product->get_parent_id() : 0;

            if ($item_product_id !== $product_id && $item_parent_id !== $product_id) {
                continue;
            }

            $quantity += (int) ($cart_item['quantity'] ?? 0);
        }

        return $quantity;
    }

    private static function is_sample_product(\WC_Product $product): bool
    {
        if ($product->is_type('simple')) {
            return false;
        }

        return Utils::is_sample($product) === true;
    }

    private static function add_product_to_cart(int $product_id, int $quantity): bool
    {
        if ($product_id < 1 || $quantity < 1 || !\function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return false;
        }

        $product = \wc_get_product($product_id);

        if (!$product instanceof \WC_Product) {
            return false;
        }

        $variation_id = 0;
        $variation_data = [];

        if ($product->is_type('variable')) {
            $default_variant = Utils::get_default_product_variant($product);

            if ($default_variant instanceof \WC_Product_Variation) {
                $variation_id = (int) $default_variant->get_id();
                $variation_data = $default_variant->get_attributes();
            }
        }

        if ($product->is_sold_individually()) {
            $quantity = 1;
        }

        $result = \WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data);

        return is_string($result) && $result !== '';
    }

    private static function remove_product_from_cart(int $product_id): bool
    {
        if ($product_id < 1 || !\function_exists('WC') || !\WC()->cart instanceof \WC_Cart) {
            return false;
        }

        $removed = false;

        foreach (\WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'] ?? null;

            if (!$product instanceof \WC_Product) {
                continue;
            }

            $item_product_id = (int) $product->get_id();
            $item_parent_id = $product->is_type('variation') ? (int) $product->get_parent_id() : 0;

            if ($item_product_id !== $product_id && $item_parent_id !== $product_id) {
                continue;
            }

            if (\WC()->cart->remove_cart_item($cart_item_key)) {
                $removed = true;
            }
        }

        return $removed;
    }

    private static function apply_rounding(float $quantity): int
    {
        if ($quantity <= 0) {
            return 0;
        }

        // Guard the ceil() boundary. These rates routinely land exactly on a whole
        // number - a 100 board order is 64.935... m2, and 64.935... x 1.68 x 1.1 is
        // exactly 120 - but the same product computed in a different order is
        // 120.00000000000001 in binary floating point, because 1.12 x 1.5 is
        // 1.6800000000000002 rather than 1.68. Ceiling that raw would charge the
        // customer a whole extra pack for 1.4e-14 of drift, so treat a value within
        // a hair of an integer as that integer.
        $nearest = round($quantity);

        if ($nearest >= 1 && abs($quantity - $nearest) < 1e-9) {
            return (int) $nearest;
        }

        return (int) ceil($quantity);
    }

    /**
     * @param mixed $values
     * @return array<int, int>
     */
    private static function normalise_int_array($values): array
    {
        if (!\is_array($values)) {
            $values = [$values];
        }

        $normalised = [];

        foreach ($values as $value) {
            $int_value = self::normalise_int_value($value);

            if ($int_value > 0) {
                $normalised[] = $int_value;
            }
        }

        return array_values(array_unique($normalised));
    }

    /**
     * @param mixed $values
     * @return array<int, string>
     */
    private static function normalise_slug_array($values): array
    {
        if (!\is_array($values)) {
            $values = [$values];
        }

        $normalised = [];

        foreach ($values as $value) {
            if ($value instanceof \WP_Term) {
                $slug = (string) $value->slug;
            } elseif (\is_array($value) && isset($value['slug'])) {
                $slug = (string) $value['slug'];
            } elseif (\is_array($value) && isset($value['term_id'])) {
                $term = \get_term((int) $value['term_id'], 'product_cat');
                $slug = $term instanceof \WP_Term ? (string) $term->slug : '';
            } elseif (\is_numeric($value)) {
                $term = \get_term((int) $value, 'product_cat');
                $slug = $term instanceof \WP_Term ? (string) $term->slug : '';
            } elseif (\is_object($value) && isset($value->slug)) {
                $slug = (string) $value->slug;
            } elseif (\is_scalar($value)) {
                $slug = (string) $value;
            } else {
                $slug = '';
            }

            $slug = \sanitize_title($slug);

            if ($slug !== '') {
                $normalised[] = $slug;
            }
        }

        return array_values(array_unique($normalised));
    }

    /**
     * @param mixed $value
     */
    private static function normalise_int_value($value): int
    {
        if ($value instanceof \WP_Post) {
            return (int) $value->ID;
        }

        if ($value instanceof \WC_Product) {
            return (int) $value->get_id();
        }

        if (\is_array($value) && isset($value['ID'])) {
            return (int) $value['ID'];
        }

        if (\is_array($value) && isset($value['id'])) {
            return (int) $value['id'];
        }

        if (\is_object($value) && isset($value->ID)) {
            return (int) $value->ID;
        }

        if (\is_object($value) && isset($value->id)) {
            return (int) $value->id;
        }

        if (!\is_scalar($value)) {
            return 0;
        }

        return (int) $value;
    }

    /**
     * Remember that the shopper explicitly chose to continue without the
     * recommended essentials. This persists for the life of the cart/session
     * (it is NOT a one-shot flag) so that simply reloading the basket, or
     * removing an item, doesn't re-trigger the prompt they already dismissed.
     */
    private static function mark_essentials_declined(): void
    {
        if (!\function_exists('WC') || !\WC()->session instanceof \WC_Session) {
            return;
        }

        \WC()->session->set(self::SESSION_ESSENTIALS_DECLINED, true);
    }

    private static function has_declined_essentials(): bool
    {
        if (!\function_exists('WC') || !\WC()->session instanceof \WC_Session) {
            return false;
        }

        return (bool) \WC()->session->get(self::SESSION_ESSENTIALS_DECLINED, false);
    }

    /**
     * Must stay PUBLIC: init() registers this directly as the
     * 'woocommerce_cart_emptied' callback. While it was private, WordPress threw
     * an uncaught TypeError from class-wp-hook.php every time the cart was
     * emptied - including the empty_cart() WooCommerce performs after a completed
     * order - which surfaced as "There has been a critical error on this website".
     */
    public static function clear_declined_essentials(): void
    {
        if (!\function_exists('WC') || !\WC()->session instanceof \WC_Session) {
            return;
        }

        \WC()->session->set(self::SESSION_ESSENTIALS_DECLINED, false);
    }

    /**
     * Adding something new to the cart can change what's recommended, so
     * the "I don't want essentials" decision no longer applies - let the
     * prompt resurface next time the shopper hits the cart page.
     */
    public static function clear_declined_essentials_on_add(): void
    {
        self::clear_declined_essentials();
    }

    /**
     * If removing an item empties the cart entirely, reset the decline flag
     * so a fresh shopping session starts clean rather than carrying over a
     * decision from a completely different basket.
     */
    public static function clear_declined_essentials_if_cart_empty(): void
    {
        if (!\function_exists('WC') || !\WC()->cart instanceof \WC_Cart || !\WC()->cart->is_empty()) {
            return;
        }

        self::clear_declined_essentials();
    }
}