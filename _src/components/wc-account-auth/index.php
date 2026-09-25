<?php

/**
 * Sign in / create account.
 *
 * The split screen from the 2026 account design: a photograph with the brand
 * lockup over it on one side, the form on the other.
 *
 * Both forms are rendered. JavaScript turns them into tabs; without it they
 * stack, each under its own heading, which is a plainer page but a complete
 * one. Nothing about signing in depends on the script.
 */

use function Granola\Components\WC_Account_Auth\get_hero_image_id;
use function Granola\Components\WC_Account_Auth\get_hero_quote;
use function Granola\Components\WC_Account_Auth\get_initial_tab;
use function Granola\Components\WC_Account_Auth\registration_enabled;

$hero_id = get_hero_image_id();
$can_register = registration_enabled();
$initial_tab = get_initial_tab();
$account_url = wc_get_page_permalink('myaccount');

// Trade enquiries go to the contact page until there is a dedicated form.
$contact = get_page_by_path('contact-us');

?>

<div class="mb-auth" data-mb-auth data-mb-auth-initial="<?php echo esc_attr($initial_tab); ?>">

    <div class="mb-auth__hero">
        <?php if ($hero_id) : ?>
            <?php echo wp_get_attachment_image($hero_id, 'hero', false, [
                'class' => 'mb-auth__hero-image',
                'alt' => '',
                'loading' => 'eager',
                'fetchpriority' => 'high',
            ]); ?>
        <?php endif; ?>

        <span class="mb-auth__scrim" aria-hidden="true"></span>

        <div class="mb-auth__hero-content">
            <a class="mb-auth__logo" href="<?php echo esc_url(home_url('/')); ?>">
                <?php echo \Granola\Image::get('logo-lockup-light.svg', [
                    'alt' => get_bloginfo('name'),
                    'loading' => 'eager',
                    'classes' => ['mb-auth__logo-image'],
                ]); ?>
            </a>

            <p class="mb-auth__quote"><?php echo esc_html(get_hero_quote()); ?></p>
        </div>
    </div>

    <div class="mb-auth__panel">
        <div class="mb-auth__inner">

            <?php if ($can_register) : ?>
                <div class="mb-auth__tabs" role="tablist" data-mb-auth-tabs hidden>
                    <button
                        type="button"
                        class="mb-auth__tab"
                        role="tab"
                        id="mb-auth-tab-signin"
                        aria-controls="mb-auth-panel-signin"
                        data-mb-auth-tab="signin"
                    >
                        <?php esc_html_e('Sign in', 'granola'); ?>
                    </button>
                    <button
                        type="button"
                        class="mb-auth__tab"
                        role="tab"
                        id="mb-auth-tab-register"
                        aria-controls="mb-auth-panel-register"
                        data-mb-auth-tab="register"
                    >
                        <?php esc_html_e('Create account', 'granola'); ?>
                    </button>
                </div>
            <?php endif; ?>

            <?php
            // WooCommerce prints its notices here: wrong password, an email
            // already in use, a password reset confirmation.
            do_action('woocommerce_before_customer_login_form');
            ?>

            <section
                class="mb-auth__form-panel"
                id="mb-auth-panel-signin"
                role="tabpanel"
                aria-labelledby="mb-auth-tab-signin"
                data-mb-auth-panel="signin"
            >
                <h1 class="mb-auth__heading"><?php esc_html_e('Welcome back', 'granola'); ?></h1>
                <p class="mb-auth__intro"><?php esc_html_e('Sign in to see your orders, samples and saved addresses.', 'granola'); ?></p>

                <form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

                    <?php do_action('woocommerce_login_form_start'); ?>

                    <div class="mb-account-field mb-auth__field">
                        <label for="username">
                            <?php esc_html_e('Email address', 'granola'); ?>
                            <span class="required" aria-hidden="true">*</span>
                            <span class="visually-hidden"><?php esc_html_e('Required', 'woocommerce'); ?></span>
                        </label>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            autocomplete="username"
                            placeholder="you@example.com"
                            value="<?php echo (!empty($_POST['username']) && is_string($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"
                            required
                            aria-required="true"
                        />
                    </div>

                    <div class="mb-account-field mb-auth__field">
                        <label for="password">
                            <?php esc_html_e('Password', 'woocommerce'); ?>
                            <span class="required" aria-hidden="true">*</span>
                            <span class="visually-hidden"><?php esc_html_e('Required', 'woocommerce'); ?></span>
                        </label>
                        <input type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
                    </div>

                    <?php do_action('woocommerce_login_form'); ?>

                    <div class="mb-auth__row">
                        <label class="mb-auth__remember" for="rememberme">
                            <input name="rememberme" type="checkbox" id="rememberme" value="forever" />
                            <span><?php esc_html_e('Keep me signed in', 'granola'); ?></span>
                        </label>

                        <a class="mb-auth__forgot" href="<?php echo esc_url(wp_lostpassword_url()); ?>">
                            <?php esc_html_e('Forgotten password?', 'granola'); ?>
                        </a>
                    </div>

                    <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>

                    <button type="submit" class="mb-account-btn mb-auth__submit" name="login" value="<?php esc_attr_e('Log in', 'woocommerce'); ?>">
                        <?php esc_html_e('Sign in', 'granola'); ?>
                    </button>

                    <?php do_action('woocommerce_login_form_end'); ?>

                </form>
            </section>

            <?php if ($can_register) : ?>
                <section
                    class="mb-auth__form-panel"
                    id="mb-auth-panel-register"
                    role="tabpanel"
                    aria-labelledby="mb-auth-tab-register"
                    data-mb-auth-panel="register"
                >
                    <h1 class="mb-auth__heading"><?php esc_html_e('Create an account', 'granola'); ?></h1>
                    <p class="mb-auth__intro"><?php esc_html_e('A few details and your projects, samples and addresses stay together.', 'granola'); ?></p>

                    <form
                        method="post"
                        class="woocommerce-form woocommerce-form-register register"
                        action="<?php echo esc_url(add_query_arg('tab', 'register', $account_url)); ?>"
                        <?php do_action('woocommerce_register_form_tag'); ?>
                    >

                        <?php do_action('woocommerce_register_form_start'); ?>

                        <div class="mb-account-fields mb-account-fields--pair mb-auth__field">
                            <div class="mb-account-field">
                                <label for="reg_first_name"><?php esc_html_e('First name', 'granola'); ?></label>
                                <input
                                    type="text"
                                    name="billing_first_name"
                                    id="reg_first_name"
                                    autocomplete="given-name"
                                    value="<?php echo (!empty($_POST['billing_first_name']) && is_string($_POST['billing_first_name'])) ? esc_attr(wp_unslash($_POST['billing_first_name'])) : ''; ?>"
                                />
                            </div>

                            <div class="mb-account-field">
                                <label for="reg_last_name"><?php esc_html_e('Last name', 'granola'); ?></label>
                                <input
                                    type="text"
                                    name="billing_last_name"
                                    id="reg_last_name"
                                    autocomplete="family-name"
                                    value="<?php echo (!empty($_POST['billing_last_name']) && is_string($_POST['billing_last_name'])) ? esc_attr(wp_unslash($_POST['billing_last_name'])) : ''; ?>"
                                />
                            </div>
                        </div>

                        <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
                            <div class="mb-account-field mb-auth__field">
                                <label for="reg_username">
                                    <?php esc_html_e('Username', 'woocommerce'); ?>
                                    <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="username"
                                    id="reg_username"
                                    autocomplete="username"
                                    value="<?php echo (!empty($_POST['username']) && is_string($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>"
                                    required
                                    aria-required="true"
                                />
                            </div>
                        <?php endif; ?>

                        <div class="mb-account-field mb-auth__field">
                            <label for="reg_email">
                                <?php esc_html_e('Email address', 'granola'); ?>
                                <span class="required" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="email"
                                name="email"
                                id="reg_email"
                                autocomplete="email"
                                placeholder="you@example.com"
                                value="<?php echo (!empty($_POST['email']) && is_string($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>"
                                required
                                aria-required="true"
                            />
                        </div>

                        <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
                            <div class="mb-account-field mb-auth__field">
                                <label for="reg_password">
                                    <?php esc_html_e('Password', 'woocommerce'); ?>
                                    <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="password" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
                            </div>
                        <?php else : ?>
                            <p class="mb-auth__note"><?php esc_html_e('A link to set a password will be sent to your email address.', 'granola'); ?></p>
                        <?php endif; ?>

                        <div class="mb-auth__field">
                            <label class="mb-account-check" for="reg_marketing">
                                <input type="checkbox" name="millboard_marketing_opt_in" id="reg_marketing" value="1" />
                                <span><?php esc_html_e('Send me occasional inspiration, new shades and project stories. No more than once a month.', 'granola'); ?></span>
                            </label>
                        </div>

                        <?php do_action('woocommerce_register_form'); ?>

                        <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>

                        <button type="submit" class="mb-account-btn mb-auth__submit" name="register" value="<?php esc_attr_e('Register', 'woocommerce'); ?>">
                            <?php esc_html_e('Create account', 'granola'); ?>
                        </button>

                        <?php do_action('woocommerce_register_form_end'); ?>

                    </form>
                </section>
            <?php endif; ?>

            <hr class="mb-auth__rule">

            <p class="mb-auth__trade">
                <?php
                printf(
                    /* translators: %1$s: opening link tag, %2$s: closing link tag. */
                    esc_html__('Ordering as a trade customer or dealer? %1$sApply for a trade account%2$s and we will be in touch within two working days.', 'granola'),
                    $contact ? '<a href="' . esc_url(get_permalink($contact)) . '">' : '<span>',
                    $contact ? '</a>' : '</span>'
                );
                ?>
            </p>

        </div>
    </div>

</div>

<?php do_action('woocommerce_after_customer_login_form');
