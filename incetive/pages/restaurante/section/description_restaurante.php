<?php
/**
 * section/description_restaurante.php
 * Descrição completa do restaurante
 * Fonte: incentive.restaurants.description
 */

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$name        = '';
$description = '';
$address     = '';

if ($id > 0) {
    require_once __DIR__ . '/../../../util/connection.php';

    $sql = "
        SELECT name, description, short_description, address, city_code
        FROM incentive.restaurants
        WHERE id = $1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        $name        = $row['name'] ?? '';
        $description = $row['description'] ?? $row['short_description'] ?? '';
        $address     = $row['address'] ?? '';
    }
}
?>

<?php if ($name): ?>
<script>
    // Atualiza o h2 do nome na página principal
    var el = document.getElementById('restaurante_name');
    if (el) el.textContent = <?php echo json_encode($name); ?>;
</script>
<?php endif; ?>

<div class="hotel_description">
    <div class="line"></div>
    <h3>Restaurant Description</h3>

    <?php if ($description): ?>
        <div class="description_text">
            <?php echo nl2br(h($description)); ?>
        </div>
    <?php else: ?>
        <p class="no_description">No description available.</p>
    <?php endif; ?>
</div>

<hr class="section_divider">

<style>
.hotel_description {
    margin-bottom: 32px;
}
.hotel_description .line {
    width: 36px;
    height: 3px;
    background: #e8a020;
    margin-bottom: 12px;
    border-radius: 2px;
}
.hotel_description h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0 0 14px;
}
.description_text {
    font-size: 0.9rem;
    color: #444;
    line-height: 1.7;
}
.description_text p,
.description_text br + br {
    margin-bottom: 12px;
}
.no_description {
    color: #999;
    font-style: italic;
}
.section_divider {
    border: none;
    border-top: 1px solid #e0e0e0;
    margin: 0 0 36px;
}
</style>