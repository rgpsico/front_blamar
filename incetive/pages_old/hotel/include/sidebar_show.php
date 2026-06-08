<?php

require_once __DIR__ . '/../../../util/connection.php';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$star_rating = null;
$total_rooms = null;
$google_maps_url = null;
$personal_note = null;

if ($id > 0) {
    $sql_program = "SELECT star_rating, total_rooms FROM incentive.inc_program WHERE inc_id = $1 LIMIT 1";
    $res_program = pg_query_params($conn, $sql_program, [$id]);
    if ($res_program && pg_num_rows($res_program) > 0) {
        $row_program = pg_fetch_assoc($res_program);
        $star_rating = $row_program['star_rating'] !== null ? (int)$row_program['star_rating'] : null;
        $total_rooms = $row_program['total_rooms'] !== null ? (int)$row_program['total_rooms'] : null;
    }

    $sql_contact = "SELECT google_maps_url FROM incentive.inc_hotel_contact WHERE inc_id = $1 LIMIT 1";
    $res_contact = pg_query_params($conn, $sql_contact, [$id]);
    if ($res_contact && pg_num_rows($res_contact) > 0) {
        $row_contact = pg_fetch_assoc($res_contact);
        $google_maps_url = $row_contact['google_maps_url'] ?? null;
    }

    $sql_note = "SELECT note FROM incentive.inc_note WHERE inc_id = $1 ORDER BY inc_note_id ASC LIMIT 1";
    $res_note = pg_query_params($conn, $sql_note, [$id]);
    if ($res_note && pg_num_rows($res_note) > 0) {
        $row_note = pg_fetch_assoc($res_note);
        $personal_note = $row_note['note'] ?? null;
    }
}
?>

<div class="featurs_right">
    <?php if (!isset($is_public_proposal) || !$is_public_proposal) : ?>
        <a href="hotel_show_propusal.php?id=<?php echo h($id); ?>" target="_blank" rel="noopener">
           <button type="button" style="cursor: pointer;">create produt link</button>
        </a>
    <?php endif; ?>

    <div class="featurs_inner">
        <div class="selos">
            <img src="../../img/selo-01.png" alt="">
            <img src="../../img/selo-02.png" alt="">
        </div>
        <div class="avaliacao">
            <div class="avalia_01">
                <img src="../../img/stars.png" alt="">
                <p><?php echo isset($star_rating) && $star_rating ? h($star_rating) : '-'; ?></p>
            </div>
            <div class="avalia_02">
                <img src="../../img/icon_desc_hot_01.png" alt="">
                <p><?php echo isset($total_rooms) && $total_rooms ? h($total_rooms) : '-'; ?></p>
            </div>
        </div>
        <div class="description">
            <h4><strong>  Personal note</strong><br>from the team</h4>
            <p><?php echo $personal_note ? nl2br(h($personal_note)) : 'Sem anotacoes.'; ?></p>
        </div>
        <div class="map">
            <?php if (isset($google_maps_url) && $google_maps_url) : ?>
                <iframe src="<?php echo h($google_maps_url); ?>" width="100%" style="border:0; height:8vw;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php else : ?>
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d20779.12253490345!2d-43.19895375358977!3d-22.977295056677026!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x9bd5a9a6a41ee5%3A0x5f178c915cb77d2c!2sBlumar%20Turismo!5e0!3m2!1spt-BR!2sbr!4v1771510608551!5m2!1spt-BR!2sbr" width="100%" style="border:0; height:8vw;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php endif; ?>
        </div>
    </div>
</div>

