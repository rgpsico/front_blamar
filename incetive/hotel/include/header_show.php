<?php
require_once __DIR__ . '/../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($hotel_name) || $hotel_name === '') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        $sql = "SELECT inc_name FROM incentive.inc_program WHERE inc_id = $1 LIMIT 1";
        $res = pg_query_params($conn, $sql, [$id]);
        if ($res && pg_num_rows($res) > 0) {
            $row = pg_fetch_assoc($res);
            $hotel_name = $row['inc_name'] ?? '';
        }
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
    <title><?php echo isset($hotel_name) && $hotel_name !== '' ? h($hotel_name) : 'Hotel'; ?></title>
</head>
<body>

    <header>
        <div class="container">
            <div class="logo_topo">
                <img src="../img/logo_blumar.png" alt="">
            </div>
            <div class="menu_interno">
                <a href="hotel_list.php">
                    <button type="button">Back to main site</button>
                </a>
            </div>
        </div>
    </header>

    <section id="header_page">
        <div class="container">
            <div class="page_title">
                <h2>Hotels</h2>
                <h3><?php echo isset($city_name) && $city_name !== '' ? h($city_name) : 'City'; ?></h3>
            </div>
            <div class="chose_city"></div>
        </div>
        <div class="container_max ct02">
            <div class="container">
                <div class="breadcrumb">
                    <a href="../index.html">Incentive Area</a><i class="material-icons">&#xe315;</i>
                    <a href="../hotel/hotel_list.php">Hotel</a><i class="material-icons">&#xe315;</i>
                    <a href=""><?php echo isset($city_name) && $city_name !== '' ? h($city_name) : 'City'; ?></a><i class="material-icons">&#xe315;</i>
                    <a href=""><?php echo isset($hotel_name) && $hotel_name !== '' ? h($hotel_name) : 'Hotel'; ?></a>
                </div>
            </div>
        </div>
    </section>
