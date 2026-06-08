<?php
/**
 * section/banner_restaurante.php
 * Galeria de imagens do restaurante
 * Fonte: incentive.restaurant_images via api_restaurante_incentive.php
 */

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$images    = [];
$main_img  = null;
$thumb_imgs = [];
$mock_image = '../../img/hotel_01.png';

if ($id > 0) {
    require_once __DIR__ . '/../../../util/connection.php';

    $sql = "
        SELECT id, image_url, is_cover, position
        FROM incentive.restaurant_images
        WHERE restaurant_id = $1
        ORDER BY is_cover DESC, position ASC, id ASC
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    $images = $res ? (pg_fetch_all($res) ?: []) : [];

    foreach ($images as $img) {
        if (empty($img['image_url'])) continue;
        if (!$main_img) {
            $main_img = $img;
        } else {
            $thumb_imgs[] = $img;
        }
    }
}

$total_photos = count($images);
?>

<div class="galeria_hotel">
    <!-- Imagem principal -->
    <div class="galeria_box01">
        <?php if ($main_img): ?>
            <img src="<?php echo h($main_img['image_url']); ?>"
                 alt="Imagem principal do restaurante"
                 onerror="this.src='<?php echo h($mock_image); ?>'"
                 class="galeria_main_img">
        <?php else: ?>
            <img src="<?php echo h($mock_image); ?>" alt="Imagem placeholder" class="galeria_main_img">
        <?php endif; ?>
    </div>

    <!-- Thumbs laterais -->
    <div class="galeria_box02">
        <?php
        $shown = 0;
        foreach ($thumb_imgs as $img) {
            if ($shown >= 2) break;
            ?>
            <img src="<?php echo h($img['image_url']); ?>"
                 alt="Foto do restaurante"
                 onerror="this.src='<?php echo h($mock_image); ?>'">
            <?php
            $shown++;
        }
        // Preenche com mock se não tiver thumbs
        while ($shown < 2) {
            echo '<img src="' . h($mock_image) . '" alt="Foto complementar">';
            $shown++;
        }
        ?>

        <!-- Último thumb com overlay "More X photos" -->
        <div class="galeria_more_wrap" <?php if ($total_photos > 0) echo 'style="cursor:pointer;" onclick="openGalleryModal()"'; ?>>
            <?php
            $last_img = count($thumb_imgs) >= 3 ? $thumb_imgs[2] : ($main_img ?: null);
            $last_src = $last_img ? $last_img['image_url'] : $mock_image;
            ?>
            <img src="<?php echo h($last_src); ?>"
                 alt="Mais fotos"
                 onerror="this.src='<?php echo h($mock_image); ?>'">
            <?php if ($total_photos > 3): ?>
                <div class="galeria_more_overlay">
                    <p>More <?php echo $total_photos - 3; ?> Photos</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.galeria_hotel {
    display: flex;
    gap: 10px;
    margin-bottom: 28px;
    height: 290px;
}

.galeria_box01 {
    flex: 1 1 68%;
    overflow: hidden;
    border-radius: 4px;
}
.galeria_box01 .galeria_main_img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.galeria_box02 {
    flex: 0 0 30%;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.galeria_box02 img {
    width: 100%;
    flex: 1;
    object-fit: cover;
    border-radius: 4px;
    display: block;
}

/* Último thumb com overlay */
.galeria_more_wrap {
    position: relative;
    flex: 1;
    border-radius: 4px;
    overflow: hidden;
}
.galeria_more_wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 0;
}
.galeria_more_overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.52);
    display: flex;
    align-items: center;
    justify-content: center;
}
.galeria_more_overlay p {
    color: #fff;
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0;
    text-align: center;
}
</style>