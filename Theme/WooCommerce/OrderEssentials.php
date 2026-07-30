<?php

namespace Theme\WooCommerce;

class OrderEssentials
{
    private const ENDPOINT = 'order-essentials';
    private const SESSION_PROJECT_TYPE = 'millboard_order_essentials_project_type';
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

        return [
            'project_type' => $project_type,
            'recommendations' => $recommendations,
            'has_recommendations' => !empty($recommendations),
            'has_outstanding_recommendations' => $outstanding_count > 0,
            'recommendation_source_label' => self::get_recommendation_source_label(),
            'disclaimer_url' => 'https://millboard.com/en-us/installation-guides/',
            'show_added_modal' => self::should_show_added_modal(),
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

            $source_category_slugs = self::get_product_category_slugs($source_id);
            $source_quantity = (int) ($cart_item['quantity'] ?? 0);

            if ($source_quantity < 1) {
                continue;
            }

            foreach ($matrix as $rule) {
                if (!self::rule_matches_source($rule, $source_id, $source_category_slugs)) {
                    continue;
                }

                $target_id = (int) $rule['target_product_id'];
                $multiplier = $project_type === 'commercial'
                    ? (float) $rule['commercial_multiplier']
                    : (float) $rule['residential_multiplier'];

                if ($target_id < 1 || $multiplier <= 0) {
                    continue;
                }

                // The multiplier is a RATE PER UNIT of the source product, so it
                // scales with the basket quantity and accumulates across every
                // matching basket line.
                //
                // Previously the multiplier was added once per rule for the whole
                // basket (guarded by an $applied_rules map) and $source_quantity
                // was read but never used. Because the total is ceil()'d, any rate
                // below 1 became a single unit no matter how much was ordered - one
                // box of screws for 500 boards - and the residential and commercial
                // rates gave identical results whenever both were under 1.
                $target_requirements[$target_id] = ($target_requirements[$target_id] ?? 0)
                    + ($multiplier * $source_quantity);
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
                ];
            }
        }

        return $rules;
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