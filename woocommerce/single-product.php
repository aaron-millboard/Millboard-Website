<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

while (have_posts()) :
    the_post();
    woocommerce_output_all_notices();
    wc_get_template_part('content', 'single-product');
endwhile;
