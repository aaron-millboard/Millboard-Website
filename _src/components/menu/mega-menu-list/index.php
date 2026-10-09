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

    <?php if (!empty($args['parent_title'])) { ?>
        <?php
        // The drawer's pane header. Hidden above the header breakpoint, where
        // the panel drops from the nav and needs neither.
        //
        // The back button is a real button rather than the chevron that opened
        // the pane, because on a phone that chevron is off the top of the
        // screen. SiteHeader.js points it at the same toggler, so there is one
        // piece of state, not two.
        ?>
        <div class="mega-menu-list__pane-head">
            <button type="button" class="mega-menu-list__back" data-mega-menu-back>
                <svg class="mega-menu-list__back-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="m15 5-7 7 7 7"></path>
                </svg>
                <?= esc_html__('Back', 'granola'); ?>
            </button>

            <p class="mega-menu-list__pane-title"><?= esc_html($args['parent_title']); ?></p>
        </div>
    <?php } ?>

    <?php
    // The drawer staggers the rows of a pane as they arrive, and it needs them
    // numbered in the order they are printed. A panel is several lists, so
    // nth-child restarts partway down and cannot do it; this counts once,
    // straight through, and every row and heading takes the next number.
    //
    // It starts at one because the pane's head -- Back and the section title --
    // is row zero.
    $row = 0;
    $next_row = static function () use (&$row) {
        return ++$row;
    };
    ?>

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
                        'row_index' => $next_row(),
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
                        <p class="mega-menu-list__group-title" style="--mbh-drawer--row-index: <?= (int) $next_row(); ?>">
                            <?= esc_html($group['item']->title); ?>
                        </p>

                        <ul class="mega-menu-list__group-links">
                            <?php foreach ($group['links'] as $link) { ?>
                                <?= \Granola\Component::get('menu/mega-menu-item', [
                                    'item' => $link,
                                    'shape' => 'rail',
                                    'row_index' => $next_row(),
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
                                    'row_index' => $next_row(),
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
                        'row_index' => $next_row(),
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
                            'row_index' => $next_row(),
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
            <div class="mega-menu-list__cta" style="--mbh-drawer--row-index: <?= (int) $next_row(); ?>">
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
