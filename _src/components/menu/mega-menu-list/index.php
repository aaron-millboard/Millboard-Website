<?php

/**
 * The mega menu panel.
 *
 * Two layouts, chosen in functions.php from the menu's own content: picture
 * tiles with a rail of links beside them, or a plain grid of titled links with
 * a sentence under each.
 */

?>
<div <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <div class="mega-menu-list__inner">

        <?php if ($args['has_cards']) { ?>
            <?php
            // A list, not a div. The whole panel sits inside the menu's own
            // <li>, and the parser closes an open <li> when it meets another
            // one with only divs in between -- a div is explicitly not a
            // boundary in that step of the spec. As a div this hoisted all
            // thirteen cards out of the panel and into the menu above it.
            ?>
            <ul class="mega-menu-list__cards">
                <?php foreach ($args['cards'] as $item) { ?>
                    <?= \Granola\Component::get('menu/mega-menu-item', [
                        'item' => $item,
                        'shape' => 'card',
                        'depth' => $args['depth'],
                    ]); ?>
                <?php } ?>
            </ul>
        <?php } ?>

        <?php if (!empty($args['has_rail'])) { ?>
            <div class="mega-menu-list__rail">
                <?php foreach ($args['groups'] as $group) { ?>
                    <div class="mega-menu-list__group">
                        <?php
                        // The group's own title heads the column. It is a
                        // heading rather than a link because the design gives
                        // it no target, and a link that goes nowhere is worse
                        // than a label.
                        ?>
                        <p class="mega-menu-list__group-title">
                            <?= esc_html($group['item']->title); ?>
                        </p>

                        <ul class="mega-menu-list__group-links">
                            <?php foreach ($group['links'] as $link) { ?>
                                <?= \Granola\Component::get('menu/mega-menu-item', [
                                    'item' => $link,
                                    'shape' => 'rail',
                                    'depth' => $args['depth'] + 1,
                                ]); ?>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>

                <?php if (!empty($args['links'])) { ?>
                    <?php
                    // Loose children of a menu that also has cards. They have
                    // nowhere else to go, so they sit in the rail without a
                    // heading rather than being dropped.
                    ?>
                    <div class="mega-menu-list__group">
                        <ul class="mega-menu-list__group-links">
                            <?php foreach ($args['links'] as $link) { ?>
                                <?= \Granola\Component::get('menu/mega-menu-item', [
                                    'item' => $link,
                                    'shape' => 'rail',
                                    'depth' => $args['depth'],
                                ]); ?>
                            <?php } ?>
                        </ul>
                    </div>
                <?php } ?>

            </div>
        <?php } ?>

        <?php if (!$args['has_cards']) { ?>
            <ul class="mega-menu-list__links">
                <?php foreach ($args['links'] as $item) { ?>
                    <?= \Granola\Component::get('menu/mega-menu-item', [
                        'item' => $item,
                        'shape' => 'text',
                        'depth' => $args['depth'],
                    ]); ?>
                <?php } ?>

                <?php
                // A text panel whose menu happens to nest a level deeper still
                // shows those links, flattened, rather than losing them.
                ?>
                <?php foreach ($args['groups'] as $group) { ?>
                    <?php foreach ($group['links'] as $link) { ?>
                        <?= \Granola\Component::get('menu/mega-menu-item', [
                            'item' => $link,
                            'shape' => 'text',
                            'depth' => $args['depth'] + 1,
                        ]); ?>
                    <?php } ?>
                <?php } ?>
            </ul>
        <?php } ?>


        <?php if (!empty($args['cta'])) { ?>
            <?php
            // Spans whichever layout is above it, so a long label has the
            // panel's full width rather than a column's.
            ?>
            <div class="mega-menu-list__cta">
                <?= \Granola\Component::get('link', [
                    'url' => $args['cta']['url'],
                    'content' => $args['cta']['title'],
                    'target' => $args['cta']['target'] ?? null,
                    'classes' => ['g-button', 'g-button--primary'],
                ]); ?>
            </div>
        <?php } ?>

    </div>
</div>
