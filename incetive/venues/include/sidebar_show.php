<?php

require_once __DIR__ . '/../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('toEmbedGoogleMapsUrl')) {
    function toEmbedGoogleMapsUrl($value)
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }

        if (stripos($raw, '/maps/embed') !== false || stripos($raw, 'output=embed') !== false) {
            return $raw;
        }

        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $raw, $m)) {
            return 'https://maps.google.com/maps?q=' . urlencode($m[1] . ',' . $m[2]) . '&z=15&output=embed';
        }

        if (preg_match('/[?&]q=([^&]+)/', $raw, $m)) {
            $q = urldecode($m[1]);
            if ($q !== '') {
                return 'https://maps.google.com/maps?q=' . urlencode($q) . '&output=embed';
            }
        }

        if (preg_match('/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', $raw, $m)) {
            return 'https://maps.google.com/maps?q=' . urlencode($m[1] . ',' . $m[2]) . '&z=15&output=embed';
        }

        return '';
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
            v.price_range,
            v.capacity_min,
            v.capacity_max,
            COALESCE(
                NULLIF((
                    SELECT t.insight
                    FROM incentive.venues_translations t
                    WHERE t.venue_id = v.venue_id
                      AND t.language = 'en'
                    LIMIT 1
                ), ''),
                NULLIF((
                    SELECT t.insight
                    FROM incentive.venues_translations t
                    WHERE t.venue_id = v.venue_id
                      AND t.language = 'pt'
                    LIMIT 1
                ), ''),
                NULLIF((
                    SELECT t.insight
                    FROM incentive.venues_translations t
                    WHERE t.venue_id = v.venue_id
                      AND t.language = 'es'
                    LIMIT 1
                ), '')
            ) AS insight,
            v.product_link_url,
            l.latitude,
            l.longitude,
            l.google_maps_url
        FROM incentive.venues v
        LEFT JOIN incentive.venues_location l ON l.venue_id = v.venue_id
        WHERE v.venue_id = $1
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

        $google_maps_url = trim((string)($row['google_maps_url'] ?? ''));
        if ($google_maps_url !== '') {
            $map_embed_url = toEmbedGoogleMapsUrl($google_maps_url);
        } else {
            $lat = $row['latitude'] ?? null;
            $lng = $row['longitude'] ?? null;
            if (is_numeric($lat) && is_numeric($lng)) {
                $map_embed_url = 'https://maps.google.com/maps?q=' . urlencode($lat . ',' . $lng) . '&z=15&output=embed';
            }
        }

        if ($map_embed_url === '') {
            $lat = $row['latitude'] ?? null;
            $lng = $row['longitude'] ?? null;
            if (is_numeric($lat) && is_numeric($lng)) {
                $map_embed_url = 'https://maps.google.com/maps?q=' . urlencode($lat . ',' . $lng) . '&z=15&output=embed';
            }
        }
    }
}

if ($product_link_url !== '' && !preg_match('/^https?:\/\//i', $product_link_url) && strpos($product_link_url, '//') !== 0) {
    $product_link_url = 'https://www.blumar.com.br/' . ltrim($product_link_url, '/');
}
?>

<style>
    .featurs_right {
        width: min(100%, 290px);
        margin-left: auto;
    }

    .venues-product-link {
        display: block;
        margin-bottom: 16px;
    }

    .venues-product-link button {
        width: 100%;
        border: 1px solid #e0a24f;
        background: #fff;
        color: #d28c2e;
        border-radius: 6px;
        height: 38px;
        font-size: 18px;
        line-height: 1;
    }

    .venues-product-link.is-disabled button {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .featurs_inner.venues-sidebar-card {
        border: 1px solid #9dc1da;
        border-radius: 14px;
        background: #f7f7f7;
        padding: 14px 14px 16px;
    }

    .venues-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border-bottom: 1px solid #b7cede;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }

    .venues-stat {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 48px;
    }

    .venues-stat:first-child {
        border-right: 1px solid #b7cede;
        padding-right: 10px;
    }

    .venues-stat:last-child {
        padding-left: 10px;
    }

    .venues-stat-label {
        font-size: 12px;
        color: #666;
        line-height: 1.1;
        margin-bottom: 4px;
    }

    .venues-stat-value {
        font-size: 34px;
        color: #76aaca;
        line-height: 0.9;
        font-weight: 500;
    }

    .venues-capacity {
        font-size: 22px;
        color: #76aaca;
        line-height: 1;
    }

    .venues-capacity-value {
        color: #555;
        font-size: 22px;
        line-height: 1;
        white-space: nowrap;
    }

    .venues-note-title {
        text-align: center;
        color: #555;
        font-size: 36px;
        font-weight: 700;
        line-height: 1.2;
        margin-top: 10px;
    }

    .venues-divider {
        border: 0;
        border-top: 1px solid #b7cede;
        margin: 14px 16px;
    }

    .venues-note-text {
        color: #666;
        font-size: 14px;
        line-height: 1.9;
        padding: 0 10px;
        min-height: 120px;
    }

    .venues-map {
        padding: 2px 2px 0;
    }

    .venues-map iframe {
        width: 100%;
        height: 148px;
        border: 0;
        border-radius: 12px;
    }
</style>

<div class="featurs_right">
    <?php if (!isset($is_public_proposal) || !$is_public_proposal) : ?>
        <?php if ($product_link_url !== '') : ?>
            <a class="venues-product-link" href="<?php echo h($product_link_url); ?>" target="_blank" rel="noopener">
                <button type="button" style="cursor: pointer;">create produt link</button>
            </a>
        <?php else : ?>
            <a class="venues-product-link is-disabled" href="javascript:void(0);" aria-disabled="true">
                <button type="button" disabled>create produt link</button>
            </a>
        <?php endif; ?>
    <?php endif; ?>

    <div class="featurs_inner venues-sidebar-card">
        <div class="venues-stats">
            <div class="venues-stat">
                <div>
                    <div class="venues-stat-label">Price Range</div>
                    <div class="venues-stat-value"><?php echo $price_range !== '' ? h($price_range) : '-'; ?></div>
                </div>
            </div>
            <div class="venues-stat">
                <span class="material-icons venues-capacity">groups</span>
                <div class="venues-capacity-value">
                    <?php
                    if ($capacity_min !== null || $capacity_max !== null) {
                        echo h(($capacity_min !== null ? (string)$capacity_min : '-') . ' to ' . ($capacity_max !== null ? (string)$capacity_max : '-'));
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
            </div>
        </div>

        <h4 class="venues-note-title">Personal note<br>from the team</h4>
        <hr class="venues-divider">
        <p class="venues-note-text"><?php echo $personal_note !== '' ? nl2br(h($personal_note)) : 'No notes available.'; ?></p>
        <hr class="venues-divider">

        <div class="venues-map">
            <?php if ($map_embed_url !== '') : ?>
                <iframe src="<?php echo h($map_embed_url); ?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php else : ?>
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d20779.12253490345!2d-43.19895375358977!3d-22.977295056677026!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9bd5a9a6a41ee5%3A0x5f178c915cb77d2c!2sBlumar%20Turismo!5e0!3m2!1spt-BR!2sbr!4v1771510608551!5m2!1spt-BR!2sbr" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php endif; ?>
        </div>
    </div>
</div>
