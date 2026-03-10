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
$mock_image = '../img/venues_bg.jpg';
$main_media = '';
$thumbs = [];

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
        $gallery = [];
        foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5'] as $field) {
            $candidate = venueMediaUrl($row[$field] ?? '');
            if ($candidate !== '') {
                $gallery[] = $candidate;
            }
        }
        if (count($gallery) > 0) {
            $main_media = $gallery[0];
            $thumbs = array_slice($gallery, 1, 3);
        }
    }
}
?>

<div class="galeria_hotel">
    <div class="galeria_box01">
        <img src="<?php echo h($main_media !== '' ? $main_media : $mock_image); ?>"
             alt="Main venue image"
             onerror="this.src='<?php echo h($mock_image); ?>'">
    </div>

    <div class="galeria_box02">
        <?php
        $thumbCount = 0;
        foreach ($thumbs as $thumb) {
            $thumbCount++;
            ?>
            <img src="<?php echo h($thumb); ?>"
                 alt="Venue gallery image"
                 onerror="this.src='<?php echo h($mock_image); ?>'">
            <?php
        }

        while ($thumbCount < 3) {
            $thumbCount++;
            ?>
            <img src="<?php echo h($mock_image); ?>" alt="Venue gallery image">
            <?php
        }
        ?>

        <?php if ($main_media !== '' || count($thumbs) > 0) : ?>
            <p>More Photos</p>
        <?php endif; ?>
    </div>
</div>
