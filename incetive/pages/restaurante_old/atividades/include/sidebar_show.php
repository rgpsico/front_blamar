<?php

require_once __DIR__ . '/../../../util/connection.php';
require_once __DIR__ . '/../../includes/url_helpers.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id            = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_favourite  = false;
$price_range   = null;
$price_label   = '';
$capacidade_min = null;
$capacidade_max = null;
$mapa_google   = null;
$nota_equipe   = null;

if ($id > 0) {
    $sql = "
        SELECT is_favourite, price_range, capacidade_min, capacidade_max,
               mapa_google, nota_equipe
        FROM incentive.activities
        WHERE id = \$1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row            = pg_fetch_assoc($res);
        $is_favourite   = ($row['is_favourite'] === 't');
        $price_range    = $row['price_range'] !== null ? (int)$row['price_range'] : null;
        $price_label    = $price_range ? str_repeat('$', $price_range) : '';
        $capacidade_min = $row['capacidade_min'] !== null ? (int)$row['capacidade_min'] : null;
        $capacidade_max = $row['capacidade_max'] !== null ? (int)$row['capacidade_max'] : null;
        $mapa_google    = $row['mapa_google']  ?? null;
        $nota_equipe    = $row['nota_equipe']  ?? null;
    }
}

// Mapa padrão (Blumar) caso não tenha cadastrado
$default_map = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d20779.12253490345!2d-43.19895375358977!3d-22.977295056677026!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9bd5a9a6a41ee5%3A0x5f178c915cb77d2c!2sBlumar%20Turismo!5e0!3m2!1spt-BR!2sbr!4v1771510608551!5m2!1spt-BR!2sbr';
?>

<div class="featurs_right">

    <div class="featurs_inner">

        <!-- Favourite -->
        <?php if ($is_favourite) : ?>
        <div class="selos">
            <div class="activity_favourite">
                <span class="activity_star">&#9733;</span>
                <span class="activity_star_label">FAVORITE</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Price Range + Capacidade -->
        <?php if ($price_label || ($capacidade_min && $capacidade_max)) : ?>
        <div class="avaliacao">
            <?php if ($price_label) : ?>
            <div class="avalia_01">
                <span class="sb_price_label_txt">Price Range</span>
                <p class="sb_price_cifrao"><?php echo h($price_label); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($capacidade_min && $capacidade_max) : ?>
            <div class="avalia_02">
                <i class="material-icons" style="font-size:1.2em;color:#555;">group</i>
                <p><?php echo h($capacidade_min); ?> to <?php echo h($capacidade_max); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Personal Note -->
        <div class="description">
            <h4><strong>Personal note</strong><br>from the team</h4>
            <p><?php echo $nota_equipe ? nl2br(h($nota_equipe)) : 'No notes available.'; ?></p>
        </div>

        <!-- Mapa -->
        <div class="map">
            <iframe
                src="<?php echo h($mapa_google ?: $default_map); ?>"
                width="100%"
                style="border:0; height:8vw;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>

    </div>
</div>

<style>
.activity_favourite {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 0 4px;
}
.activity_star {
    font-size: 2.6em;
    color: #e6a817;
    line-height: 1;
}
.activity_star_label {
    font-size: 0.6em;
    letter-spacing: 0.14em;
    color: #aaa;
    font-weight: 600;
    margin-top: 2px;
}
.sb_price_label_txt {
    font-size: 0.72em;
    color: #999;
    display: block;
    margin-bottom: 2px;
}
.sb_price_cifrao {
    font-size: 1.2em;
    font-weight: 700;
    color: #2c6e9e;
    letter-spacing: 0.06em;
    margin: 0;
}
</style>