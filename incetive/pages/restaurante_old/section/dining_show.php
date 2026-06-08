<?php

require_once __DIR__ . '/../../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dining = [];
$mock_image = '../../img/carnaval_02_bg.jpg';

if ($id > 0) {
    $sql = "
        SELECT
            inc_dining_id, name, description, cuisine, capacity, schedule,
            is_michelin, can_be_private, image_url, position, is_active,
            seating_capacity
        FROM incentive.inc_dining
        WHERE inc_id = $1
        ORDER BY position ASC, inc_dining_id ASC
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    $dining = pg_fetch_all($res) ?: [];
}
?>

<div class="dining_experiences">
    <h4>Dining Experiences</h4>
    <?php if (count($dining) === 0) : ?>
        <p>Sem dining cadastrado.</p>
    <?php else : ?>
        <?php foreach ($dining as $d) : ?>
            <div class="dining_experiences_box">
                <div class="dining_experiences_box_inner01">
                    <img src="<?php echo $d['image_url'] ? h($d['image_url']) : h($mock_image); ?>" alt="" onerror="this.src='<?php echo h($mock_image); ?>'">
                </div>
                <div class="dining_experiences_box_inner02">
                    <h5><?php echo h($d['name']); ?></h5>
                    <p><?php echo $d['description'] ? nl2br(h($d['description'])) : 'Sem descricao.'; ?></p>
                    <?php if (!empty($d['schedule'])) : ?>
                        <p><?php echo nl2br(h($d['schedule'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


