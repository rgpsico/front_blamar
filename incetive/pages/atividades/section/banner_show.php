<?php

require_once __DIR__ . '/../../../util/connection.php';

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id         = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$fotos      = [];
$foto_capa  = null;
$foto_thumbs = [];
$mock_image = '../../img/carnaval_02_bg.jpg';
$BASE_IMG   = 'http://www.blumar.com.br/global/main_site/images/incentive_activities/';

if ($id > 0) {
    $sql_fotos = "
        SELECT id, url, is_capa, ordem
        FROM incentive.activities_fotos
        WHERE activity_id = \$1
        ORDER BY ordem ASC, id ASC
    ";
    $res_fotos = pg_query_params($conn, $sql_fotos, [$id]);
    $fotos     = pg_fetch_all($res_fotos) ?: [];

    foreach ($fotos as $f) {
        if ($f['is_capa'] === 't' && !$foto_capa) {
            $foto_capa = $f;
        } else {
            $foto_thumbs[] = $f;
        }
    }

    // Se não tiver capa marcada, usa a primeira foto
    if (!$foto_capa && count($fotos) > 0) {
        $foto_capa   = $fotos[0];
        $foto_thumbs = array_slice($fotos, 1);
    }
}

?>

<div class="galeria_hotel">
    <div class="galeria_box01">
        <?php if ($foto_capa) : ?>
            <img src="<?php echo h($foto_capa['url']); ?>"
                 alt="Imagem principal"
                 onerror="this.src='<?php echo h($mock_image); ?>'">
        <?php else : ?>
            <img src="<?php echo h($mock_image); ?>" alt="Imagem placeholder">
        <?php endif; ?>
    </div>

    <div class="galeria_box02">
        <?php
        $thumbs_mostradas = 0;
        $max_thumbs       = 3; // 2 thumbs + 1 slot "More photos"

        foreach ($foto_thumbs as $ft) :
            if ($thumbs_mostradas >= $max_thumbs) break;
            $thumbs_mostradas++;
        ?>
            <img src="<?php echo h($ft['url']); ?>"
                 alt="Foto complementar"
                 onerror="this.src='<?php echo h($mock_image); ?>'">
        <?php endforeach; ?>

        <?php
        // Preenche slots vazios com mock se não houver fotos suficientes
        while ($thumbs_mostradas < $max_thumbs) :
            $thumbs_mostradas++;
        ?>
            <img src="<?php echo h($mock_image); ?>" alt="Foto complementar">
        <?php endwhile; ?>

        <?php
        $total_extras = count($foto_thumbs) - $max_thumbs;
        if ($total_extras > 0) :
        ?>
            <p>More <?php echo $total_extras; ?> Photos</p>
        <?php endif; ?>
    </div>
</div>