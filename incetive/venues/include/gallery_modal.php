<?php

require_once __DIR__ . '/../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('venueMediaUrl')) {
    function venueMediaUrl($value)
    {
        $url = trim((string)$value);
        if ($url === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $url) || strpos($url, '//') === 0) {
            return $url;
        }
        return 'https://www.blumar.com.br/' . ltrim($url, '/');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$media = [];
$mock_image = '../img/venues_bg.jpg';

if ($id > 0) {
    $sql = "
        SELECT foto1, foto2, foto3, foto4, foto5
        FROM conteudo_internet.venues
        WHERE cod_venues = $1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5'] as $field) {
            $url = venueMediaUrl($row[$field] ?? '');
            if ($url !== '') {
                $media[] = $url;
            }
        }
    }
}

$gallery_title = isset($venue_name) && $venue_name !== '' ? h($venue_name) : 'Venue';
?>

<section id="hotel_galeria">
    <div class="hotel_galeria_content">
        <div class="title_gallery">
            <h3><strong><?php echo $gallery_title; ?></strong> | Photos Gallery</h3>
            <i class="material-icons">&#xe5cd;</i>
        </div>
        <div class="container_photos">
            <?php
            if (count($media) > 0) {
                foreach ($media as $url) {
                    ?>
                    <img src="<?php echo h($url); ?>" alt="Venue photo" onerror="this.src='<?php echo h($mock_image); ?>'">
                    <?php
                }
            } else {
                for ($i = 0; $i < 6; $i++) {
                    ?>
                    <img src="<?php echo h($mock_image); ?>" alt="Venue photo">
                    <?php
                }
            }
            ?>
        </div>
    </div>
</section>
