<?php

require_once __DIR__ . '/../../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$rooms = [];
$program = [];

if ($id > 0) {

    // Dados do programa
    $sql_program = "
        SELECT 
            total_rooms,
            rooms_categories_text
        FROM incentive.inc_program
        WHERE inc_id = $1
    ";

    $res_program = pg_query_params($conn, $sql_program, [$id]);
    $program = pg_fetch_assoc($res_program) ?: [];

    // Categorias de quartos
    $sql_rooms = "
        SELECT room_name, quantity
        FROM incentive.inc_room_category
        WHERE inc_id = $1
        AND is_active = true
        ORDER BY position ASC
    ";

    $res_rooms = pg_query_params($conn, $sql_rooms, [$id]);
    $rooms = pg_fetch_all($res_rooms) ?: [];
}

?>

<div class="rooms_categories">

<h4>Rooms Categories</h4>

<p>
Total Rooms:
<strong><?php echo h($program['total_rooms'] ?? 0); ?> rooms</strong>
split across two buildings.
</p>

<p class="room_divide">
<?php echo nl2br(h($program['rooms_categories_text'] ?? '')); ?>
</p>

<ul class="room_divide02">

<p>The room categories are:</p>

<?php if (count($rooms) === 0): ?>

<li>No room categories registered</li>

<?php else: ?>

<?php foreach ($rooms as $room): ?>

<li>
<?php echo h($room['room_name']); ?>
(<?php echo h($room['quantity']); ?>)
</li>

<?php endforeach; ?>

<?php endif; ?>

</ul>

</div>
