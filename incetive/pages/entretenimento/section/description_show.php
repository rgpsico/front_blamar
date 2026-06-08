<?php

require_once __DIR__ . '/../../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id          = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$description = '';

if ($id > 0) {
    $sql = "
        SELECT descricao_longa, descricao_curta
        FROM incentive.activities
        WHERE id = \$1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row         = pg_fetch_assoc($res);
        $description = !empty($row['descricao_longa'])
            ? $row['descricao_longa']
            : ($row['descricao_curta'] ?? '');
    }
}
?>

<div class="hotel_description">
    <div class="line"></div>
    <h3>Activity Description</h3>
    <?php if ($description) : ?>
        <p><?php echo nl2br(h($description)); ?></p>
    <?php else : ?>
        <p>No description available.</p>
    <?php endif; ?>
</div>