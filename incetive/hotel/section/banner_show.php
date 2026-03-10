<?php

require_once __DIR__ . '/../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$media = [];
$main_media = null;
$mock_image = '../img/carnaval_02_bg.jpg';
$banner_media = [];

if ($id > 0) {
    $sql_media = "
        SELECT inc_media_id, media_type, media_url, position, is_active
        FROM incentive.inc_media
        WHERE inc_id = $1
        ORDER BY position ASC, inc_media_id ASC
    ";
    $res_media = pg_query_params($conn, $sql_media, [$id]);
    $media = pg_fetch_all($res_media) ?: [];

    // filtra apenas banners/videos (pos 0..3) ativos e com URL
    foreach ($media as $m) {
        $type = strtolower($m['media_type'] ?? '');
        $pos = (int)($m['position'] ?? -1);
        $active = ($m['is_active'] === 't' || $m['is_active'] === true);
        if ($active && in_array($type, ['banner', 'video'], true) && $pos >= 0 && $pos <= 3 && !empty($m['media_url'])) {
            $banner_media[] = $m;
        }
    }

    // main: prioridade para position 0, sen?o primeiro dispon?vel
    foreach ($banner_media as $m) {
        if ((int)($m['position'] ?? -1) === 0) {
            $main_media = $m;
            break;
        }
    }
    if (!$main_media && count($banner_media) > 0) {
        $main_media = $banner_media[0];
    }
}
?>

<?php

 $url = $main_media ? $main_media['media_url'] : '';

if (strpos($url, 'watch?v=') !== false) {
    $url = str_replace('watch?v=', 'embed/', $url);
}

?>

<div class="galeria_hotel">
    <div class="galeria_box01">
        <?php if ($main_media) : ?>
            
            <?php if (strtolower($main_media['media_type']) === 'video') : ?>
              <iframe height="315"
    src="<?php echo h($url); ?>"
    title="Vídeo do hotel"
    frameborder="0"
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
    allowfullscreen>
</iframe>
            
            <?php else : // gallery, banner, main, qualquer outro → trata como imagem ?>
                <img src="<?php echo h($main_media['media_url']); ?>" 
                     alt="Imagem principal do hotel" 
                     onerror="this.src='<?php echo h($mock_image); ?>'">
            <?php endif; ?>
        
        <?php else : ?>
            <!-- Sem nenhuma mídia válida -->
            <img src="<?php echo h($mock_image); ?>" alt="Imagem placeholder">
        <?php endif; ?>
    </div>

    <div class="galeria_box02">
        <?php
        $thumbs = 0;
        $shown_ids = $main_media ? [$main_media['inc_media_id']] : [];

        foreach ($banner_media as $m) {
            if (
                in_array($m['inc_media_id'], $shown_ids) || 
                empty($m['media_url']) ||
                $thumbs >= 3
            ) {
                continue;
            }

            // Opcional: pular vídeos nas thumbs se quiser
            if (strtolower($m['media_type']) === 'video') {
                continue;
            }

            $thumbs++;
            $shown_ids[] = $m['inc_media_id'];
            ?>
            <img src="<?php echo h($m['media_url']); ?>" 
                 alt="Foto complementar" 
                 onerror="this.src='<?php echo h($mock_image); ?>'">
            <?php
        }

        // Preenche com mocks se não tiver thumbs suficientes
        while ($thumbs < 3) {
            $thumbs++;
            ?>
            <img src="<?php echo h($mock_image); ?>" alt="Foto complementar">
            <?php
        }
        ?>

        <?php if (count($banner_media) > 0) : ?>
            <p>More <?php echo count($banner_media); ?> Photos</p>
        <?php endif; ?>
    </div>
</div>
