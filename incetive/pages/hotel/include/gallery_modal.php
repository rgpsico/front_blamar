<?php

require_once __DIR__ . '/../../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$media = [];
$mock_image = '../../img/carnaval_02_bg.jpg';

if ($id > 0) {
    $sql_media = "
        SELECT media_type, media_url, position
        FROM incentive.inc_media
        WHERE inc_id = $1
        ORDER BY position ASC, inc_media_id ASC
    ";
    $res_media = pg_query_params($conn, $sql_media, [$id]);
    $media = pg_fetch_all($res_media) ?: [];
}

$gallery_title = isset($hotel_name) && $hotel_name !== '' ? h($hotel_name) : 'Hotel';
?>

<section id="hotel_galeria">
    <div class="hotel_galeria_content">
        <div class="title_gallery">
            <h3><strong><?php echo $gallery_title; ?></strong> | Photos Galery</h3>
            <i class="material-icons">&#xe5cd;</i>
        </div>
        <div class="container_photos">
            <?php
            $count = 0;
            foreach ($media as $m) {
                if (empty($m['media_url'])) {
                    continue;
                }
                if (strtolower($m['media_type']) === 'video') {
                    continue;
                }
                $count++;
                ?>
                <img src="<?php echo h($m['media_url']); ?>" alt="Foto do hotel" onerror="this.src='<?php echo h($mock_image); ?>'">
                <?php
            }
            if ($count === 0) {
                for ($i = 0; $i < 6; $i++) {
                    ?>
                    <img src="<?php echo h($mock_image); ?>" alt="Foto do hotel">
                    <?php
                }
            }
            ?>
        </div>
    </div>
</section>

