<?php

require_once __DIR__ . '/../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$facilities = [];

if ($id > 0) {
    $sql = "
        SELECT name
        FROM incentive.inc_facility
        WHERE inc_id = $1 AND is_active = true
        ORDER BY inc_facility_id ASC
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    $facilities = pg_fetch_all($res) ?: [];
}

// Quebra em colunas (4 itens por coluna)
$columns = [];
if (count($facilities) > 0) {
    $chunkSize = 4;
    for ($i = 0; $i < count($facilities); $i += $chunkSize) {
        $columns[] = array_slice($facilities, $i, $chunkSize);
    }
}
?>

<div class="hotel_facilities">
    <h4>Hotel Facilities</h4>
    <?php if (count($columns) === 0) : ?>
        <ul>
            <li>Sem facilities cadastradas</li>
        </ul>
    <?php else : ?>
        <?php foreach ($columns as $col) : ?>
            <ul>
                <?php foreach ($col as $f) : ?>
                    <li><?php echo h($f['name']); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
