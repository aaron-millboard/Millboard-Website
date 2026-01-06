<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

while (have_posts()) :
    the_post();
    wc_get_template_part('content', 'single-product');
endwhile;
