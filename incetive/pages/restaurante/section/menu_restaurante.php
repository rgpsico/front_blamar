<?php
/**
 * section/menu_restaurante.php
 * Cardápio do restaurante: menus → sections → items
 * Fonte: incentive.restaurant_menus / restaurant_menu_sections / restaurant_menu_items
 */

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$menus = [];

if ($id > 0) {
    require_once __DIR__ . '/../../../util/connection.php';

    // Busca todos os menus do restaurante
    $sql_menus = "
        SELECT id, title
        FROM incentive.restaurant_menus
        WHERE restaurant_id = $1
        ORDER BY id ASC
    ";
    $res_menus = pg_query_params($conn, $sql_menus, [$id]);
    $raw_menus = $res_menus ? (pg_fetch_all($res_menus) ?: []) : [];

    foreach ($raw_menus as &$menu) {
        // Sections de cada menu
        $sql_sections = "
            SELECT id, name, position
            FROM incentive.restaurant_menu_sections
            WHERE menu_id = $1
            ORDER BY position ASC, id ASC
        ";
        $res_sections = pg_query_params($conn, $sql_sections, [$menu['id']]);
        $sections = $res_sections ? (pg_fetch_all($res_sections) ?: []) : [];

        foreach ($sections as &$section) {
            // Items de cada section
            $sql_items = "
                SELECT id, name, description, position
                FROM incentive.restaurant_menu_items
                WHERE section_id = $1
                ORDER BY position ASC, id ASC
            ";
            $res_items = pg_query_params($conn, $sql_items, [$section['id']]);
            $section['items'] = $res_items ? (pg_fetch_all($res_items) ?: []) : [];
        }
        unset($section);

        $menu['sections'] = $sections;
        $menus[] = $menu;
    }
    unset($menu);
}

if (empty($menus)) return; // Sem menu, não renderiza nada
?>

<div class="restaurante_menu_wrap">
    <?php foreach ($menus as $menu): ?>
        <div class="menu_block">
            <h3 class="menu_title">Menu<?php echo count($menus) > 1 ? ': ' . h($menu['title']) : ''; ?></h3>

            <?php if (!empty($menu['sections'])): ?>
                <?php foreach ($menu['sections'] as $section): ?>
                    <div class="menu_section">
                        <h4 class="menu_section_title"><?php echo h(strtoupper($section['name'])); ?></h4>

                        <?php if (!empty($section['items'])): ?>
                            <ul class="menu_items_list">
                                <?php foreach ($section['items'] as $item): ?>
                                    <li class="menu_item">
                                        <span class="menu_item_name"><?php echo h($item['name']); ?></span>
                                        <?php if (!empty($item['description'])): ?>
                                            <span class="menu_item_desc"><?php echo h($item['description']); ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no_menu_sections">Menu sem seções cadastradas.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<style>
.restaurante_menu_wrap {
    padding-top: 8px;
}

.menu_block {
    margin-bottom: 40px;
}

.menu_title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 20px;
}

/* Section do menu (STARTER, MAIN COURSE, etc.) */
.menu_section {
    margin-bottom: 18px;
}
.menu_section_title {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: #333;
    margin: 0 0 8px;
    text-transform: uppercase;
}

/* Items */
.menu_items_list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.menu_item {
    font-size: 0.875rem;
    color: #444;
    line-height: 1.6;
    display: flex;
    flex-direction: column;
    gap: 1px;
    padding: 1px 0;
}
.menu_item_name {
    color: #333;
}
.menu_item_desc {
    font-size: 0.82rem;
    color: #777;
    font-style: italic;
}

.no_menu_sections {
    color: #999;
    font-style: italic;
    font-size: 0.87rem;
}
</style>