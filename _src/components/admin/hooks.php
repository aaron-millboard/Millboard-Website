<?php

namespace Granola\Components\Admin;

\add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_bar_styles');
\add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_bar_styles');

\add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_styles');
