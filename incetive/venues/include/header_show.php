<?php
require_once __DIR__ . '/../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$venue_name = '';
$city_name = '';

if ($id > 0) {
    $sql = "
        SELECT nome, city
        FROM conteudo_internet.venues
        WHERE cod_venues = $1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        $venue_name = $row['nome'] ?? '';
        $city_name = $row['city'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="../css/estilo.css">
    <link rel="stylesheet" href="../css/estilo_mobile.css">
    <title><?php echo $venue_name !== '' ? h($venue_name) : 'Venue'; ?></title>
</head>
<body>

    <header>
        <div class="container">
            <div class="logo_topo">
                <img src="../img/logo_blumar.png" alt="">
            </div>
            <div class="menu_interno">
                <a href="venues.php">
                    <button type="button" style="cursor: pointer;">Back to main site</button>
                </a>
            </div>
        </div>
    </header>

    <section id="header_page">
        <div class="container">
            <div class="page_title">
                <h2>Venues</h2>
                <h3><?php echo isset($city_name) && $city_name !== '' ? h($city_name) : 'City'; ?></h3>
            </div>
            <div class="chose_city"></div>
        </div>
        <div class="container_max ct02">
            <div class="container">
                <div class="breadcrumb">
                    <a href="../index.html">Incentive Area</a><i class="material-icons">&#xe315;</i>
                    <a href="venues.php">Venues</a><i class="material-icons">&#xe315;</i>
                    <a href=""><?php echo isset($city_name) && $city_name !== '' ? h($city_name) : 'City'; ?></a><i class="material-icons">&#xe315;</i>
                    <a href=""><?php echo $venue_name !== '' ? h($venue_name) : 'Venue'; ?></a>
                </div>
            </div>
        </div>
    </section>
