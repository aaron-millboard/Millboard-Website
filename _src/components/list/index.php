<ul <?= \Granola\Helpers::build_attributes($args['attributes']); ?>>
    <?php foreach ($args['items'] as $item) { ?>
        <li class="<?= \Granola\Helpers::build_classes($item['classes']); ?>">
            <?= \Granola\Component::get($item['el'], $item['component_args']); ?>
        </li>
    <?php } ?>
</ul>
