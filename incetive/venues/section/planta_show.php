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
$plan_img = '';
$mock_image = '../img/planta_hotel.png';

if ($id > 0) {
    $sql = "
        SELECT floor_plan_image
        FROM conteudo_internet.venues
        WHERE cod_venues = $1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        $plan_img = venueMediaUrl($row['floor_plan_image'] ?? '');
    }
}
?>

<div class="planta_section">
    <img src="<?php echo h($plan_img !== '' ? $plan_img : $mock_image); ?>"
         alt="Venue floor plan"
         style="max-width: 100%; height: auto; margin-top: 20px;"
         onerror="this.src='<?php echo h($mock_image); ?>'; this.alt='Fallback image loaded';">
</div>
