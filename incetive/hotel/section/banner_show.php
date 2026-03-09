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

if ($id > 0) {
    $sql_media = "
        SELECT inc_media_id, media_type, media_url, position, is_active
        FROM incentive.inc_media
        WHERE inc_id = $1
        ORDER BY position ASC, inc_media_id ASC
    ";
    $res_media = pg_query_params($conn, $sql_media, [$id]);
    $media = pg_fetch_all($res_media) ?: [];

    // 1. Procura primeiro uma mídia ativa e com URL
    foreach ($media as $m) {
        if (
            ($m['is_active'] === 't' || $m['is_active'] === true) 
            && !empty($m['media_url'])
        ) {
            $main_media = $m;
            break;
        }
    }

    // 2. Se não achou ativa → pega qualquer uma com URL (fallback)
    if (!$main_media && count($media) > 0) {
        foreach ($media as $m) {
            if (!empty($m['media_url'])) {
                $main_media = $m;
                break;
            }
        }
    }
}
?>

<?php

 $url = $main_media['media_url'];

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

        foreach ($media as $m) {
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

        <?php if (count($media) > 0) : ?>
            <p>More <?php echo count($media); ?> Photos</p>
        <?php endif; ?>
    </div>
</div>