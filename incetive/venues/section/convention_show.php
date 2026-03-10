<?php

require_once __DIR__ . '/../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$convention = null;
$convention_rooms = [];
$floor_plan_url = null;
$mock_image = '../img/hotel_belmond.png';

if ($id > 0) {
    // 1. Tenta pegar o floor_plan_url do programa (fallback)
    $sql_fp = "SELECT floor_plan_url FROM incentive.inc_program WHERE inc_id = $1 LIMIT 1";
    $res_fp = pg_query_params($conn, $sql_fp, [$id]);
    if ($res_fp && pg_num_rows($res_fp) > 0) {
        $row_fp = pg_fetch_assoc($res_fp);
        $floor_plan_url = $row_fp['floor_plan_url'] ?? null;
    }

    // 2. Pega os dados da convention
    $sql_conv = "
        SELECT inc_convention_id, description, total_rooms, has_360, imagem_planta_hotel, url360_hotel
        FROM incentive.inc_convention
        WHERE inc_id = $1
        LIMIT 1
    ";
    $res_conv = pg_query_params($conn, $sql_conv, [$id]);
    if ($res_conv && pg_num_rows($res_conv) > 0) {
        $convention = pg_fetch_assoc($res_conv);
    }

    // 3. Se tem convention, pega as salas
    if ($convention) {
        $sql_rooms = "
            SELECT name, capacity_theater, capacity_auditorium, capacity_banquet,
                   capacity_classroom, capacity_u_shape, capacity_cocktail
            FROM incentive.inc_convention_room
            WHERE inc_convention_id = $1
            ORDER BY inc_room_id ASC
        ";
        $res_rooms = pg_query_params($conn, $sql_rooms, [$convention['inc_convention_id']]);
        $convention_rooms = pg_fetch_all($res_rooms) ?: [];
    }
}

// Define a URL da planta e a URL do 360
$plan_img = null;
$url_360 = null;
if ($convention && !empty($convention['imagem_planta_hotel'])) {
    $plan_img = $convention['imagem_planta_hotel'];
} elseif (!empty($floor_plan_url)) {
    $plan_img = $floor_plan_url;
}
if ($convention && !empty($convention['url360_hotel'])) {
    $url_360 = $convention['url360_hotel'];
}
?>

<style>
    .convention ul li {
        text-decoration: none;
        list-style: none;
        margin-top: 10px;
        font-weight: 400;
        color: #303030;
        font-size: 1.05vw;
    }
</style>

<div class="convention">
    <div class="coventio_title">
        <h4>Convention Center and Event Facilities  </h4>
        <?php if (!empty($url_360)) : ?>
            <a href="<?php echo h($url_360); ?>" target="_blank" rel="noopener noreferrer">
                <button style="cursor: pointer;">see here 360 of the halls</button>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($convention && !empty($convention['description'])) : ?>
        <p><?php echo nl2br(h($convention['description'])); ?></p>
    <?php else : ?>
        <p>The hotel also houses a comprehensive convention center, featuring 10 versatile rooms that can host a variety of events:</p>
    <?php endif; ?>

    <p><strong><?php echo $convention ? h($convention['total_rooms']) : '5'; ?></strong> meeting rooms</p>
    <p><strong>1 Theater</strong> with 332 seats divided into:</p>

    <?php if (count($convention_rooms) > 0) : ?>
        <ul>
            <?php foreach ($convention_rooms as $r) : ?>
                <li><?php echo h($r['name']); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <ul>
            <li>Auditorium</li>
            <li>Balcony</li>
            <li>Boxes</li>
            <li>Private boxes</li>
        </ul>
    <?php endif; ?>



    <?php if (!empty($plan_img)) : ?>
        <img src="<?php echo h($plan_img); ?>" 
             alt="Planta do convention center" 
             style="max-width: 100%; height: auto; margin-top: 20px;"
             onerror="this.src='<?php echo h($mock_image); ?>'; this.alt='Imagem de fallback carregada';">
    <?php else : ?>
        <img src="<?php echo h($mock_image); ?>" 
             alt="Imagem placeholder do hotel" 
             style="max-width: 100%; height: auto; margin-top: 20px;">
    <?php endif; ?>
</div>
