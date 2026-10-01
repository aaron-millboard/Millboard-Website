<?php

/**
 * Advice prose.
 *
 * The article's body, the full width of the content, in the design's type:
 * light headings in spaced capitals, hairline-ruled lists, an olive rule
 * beside quotes. Only the article's own headings, paragraphs, lists, quotes,
 * tables and images are restyled; a block placed inside it (the FAQ
 * accordion, a set of cards, a gallery) keeps its own look.
 */

?>
<section <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="advice-prose__inner">
        <?= $args['innerblocks_tag']; ?>
    </div>
</section>
