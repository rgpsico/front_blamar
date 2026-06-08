<?php

require_once __DIR__ . '/../../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$description = '';
$insight = '';

if ($id > 0) {
    $sql = "
        SELECT
            COALESCE(
                NULLIF((
                    SELECT t.descritivo
                    FROM incentive.venues_translations t
                    WHERE t.venue_id = v.venue_id
                      AND t.language = 'en'
                    LIMIT 1
                ), ''),
                NULLIF((
                    SELECT t.descritivo
                    FROM incentive.venues_translations t
                    WHERE t.venue_id = v.venue_id
                      AND t.language = 'pt'
                    LIMIT 1
                ), ''),
                NULLIF((
                    SELECT t.descritivo
                    FROM incentive.venues_translations t
                    WHERE t.venue_id = v.venue_id
                      AND t.language = 'es'
                    LIMIT 1
                ), ''),
                NULLIF(v.especialidade, '')
            ) AS descricao,
            COALESCE(
                NULLIF((
                    SELECT t.insight
                    FROM incentive.venues_translations t
                    WHERE t.venue_id = v.venue_id
                      AND t.language = 'en'
                    LIMIT 1
                ), ''),
                NULLIF((
                    SELECT t.insight
                    FROM incentive.venues_translations t
                    WHERE t.venue_id = v.venue_id
                      AND t.language = 'pt'
                    LIMIT 1
                ), ''),
                NULLIF((
                    SELECT t.insight
                    FROM incentive.venues_translations t
                    WHERE t.venue_id = v.venue_id
                      AND t.language = 'es'
                    LIMIT 1
                ), '')
            ) AS insight
        FROM incentive.venues v
        WHERE v.venue_id = $1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row = pg_fetch_assoc($res);
        $description = $row['descricao'] ?? '';
        $insight = $row['insight'] ?? '';
    }
}
?>

<div class="hotel_description">
    <div class="line"></div>
    <h3>Venue Description</h3>
    <?php if ($description) : ?>
        <p><?php echo nl2br(h($description)); ?></p>
    <?php else : ?>
        <p>No description available.</p>
    <?php endif; ?>

    <?php if ($insight) : ?>
        <p><strong>Blumar insight:</strong> <?php echo nl2br(h($insight)); ?></p>
    <?php endif; ?>
</div>

