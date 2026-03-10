<?php

require_once __DIR__ . '/../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$price_range = '';
$capacity_min = null;
$capacity_max = null;
$personal_note = '';
$product_link_url = '';
$map_embed_url = '';

if ($id > 0) {
    $sql = "
        SELECT
            price_range,
            capacity_min,
            capacity_max,
            COALESCE(NULLIF(insight_en, ''), NULLIF(insight_pt, ''), NULLIF(insight_es, '')) AS insight,
            product_link_url,
            latitude,
            longitude
        FROM conteudo_internet.venues
        WHERE cod_venues = $1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        $price_range = $row['price_range'] ?? '';
        $capacity_min = is_numeric($row['capacity_min'] ?? null) ? (int)$row['capacity_min'] : null;
        $capacity_max = is_numeric($row['capacity_max'] ?? null) ? (int)$row['capacity_max'] : null;
        $personal_note = $row['insight'] ?? '';
        $product_link_url = trim((string)($row['product_link_url'] ?? ''));

        $lat = $row['latitude'] ?? null;
        $lng = $row['longitude'] ?? null;
        if (is_numeric($lat) && is_numeric($lng)) {
            $map_embed_url = 'https://maps.google.com/maps?q=' . urlencode($lat . ',' . $lng) . '&z=15&output=embed';
        }
    }
}

if ($product_link_url !== '' && !preg_match('/^https?:\/\//i', $product_link_url) && strpos($product_link_url, '//') !== 0) {
    $product_link_url = 'https://www.blumar.com.br/' . ltrim($product_link_url, '/');
}
?>

<div class="featurs_right">
    <?php if (!isset($is_public_proposal) || !$is_public_proposal) : ?>
        <?php if ($product_link_url !== '') : ?>
            <a href="<?php echo h($product_link_url); ?>" target="_blank" rel="noopener">
                <button type="button" style="cursor: pointer;">create produt link</button>
            </a>
        <?php endif; ?>
    <?php endif; ?>

    <div class="featurs_inner">
        <div class="avaliacao">
            <div class="avalia_01">
                <p><strong>Price Range</strong><br><?php echo $price_range !== '' ? h($price_range) : '-'; ?></p>
            </div>
            <div class="avalia_02">
                <p>
                    <strong>Capacity</strong><br>
                    <?php
                    if ($capacity_min !== null || $capacity_max !== null) {
                        echo h(($capacity_min !== null ? (string)$capacity_min : '-') . ' to ' . ($capacity_max !== null ? (string)$capacity_max : '-'));
                    } else {
                        echo '-';
                    }
                    ?>
                </p>
            </div>
        </div>
        <div class="description">
            <h4><strong>Personal note</strong><br>from the team</h4>
            <p><?php echo $personal_note !== '' ? nl2br(h($personal_note)) : 'No notes available.'; ?></p>
        </div>
        <div class="map">
            <?php if ($map_embed_url !== '') : ?>
                <iframe src="<?php echo h($map_embed_url); ?>" width="100%" style="border:0; height:8vw;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php else : ?>
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d20779.12253490345!2d-43.19895375358977!3d-22.977295056677026!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9bd5a9a6a41ee5%3A0x5f178c915cb77d2c!2sBlumar%20Turismo!5e0!3m2!1spt-BR!2sbr!4v1771510608551!5m2!1spt-BR!2sbr" width="100%" style="border:0; height:8vw;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php endif; ?>
        </div>
    </div>
</div>
