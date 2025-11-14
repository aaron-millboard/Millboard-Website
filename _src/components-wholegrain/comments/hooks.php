<?php

namespace Granola\Components\Comments;

\add_filter('granola/partial/assets/components/comments', __NAMESPACE__ . '\\filter_args');

\add_filter('comment_form_default_fields', __NAMESPACE__ . '\\remove_comment_form_website_field');
