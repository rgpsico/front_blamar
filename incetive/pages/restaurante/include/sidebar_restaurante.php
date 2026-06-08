<?php
/**
 * include/sidebar_restaurante.php
 * Sidebar da página de detalhe do restaurante
 * Exibe: create product link, Favorite, With a View, Capacity, Personal note, Mapa
 */

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$name        = '';
$capacity    = null;
$has_view    = false;
$has_private = false;
$latitude    = null;
$longitude   = null;
$address     = '';

if ($id > 0) {
    require_once __DIR__ . '/../../../util/connection.php';

    $sql = "
        SELECT name, capacity, has_view, has_private_area,
               latitude, longitude, address
        FROM incentive.restaurants
        WHERE id = $1
        LIMIT 1
    ";
    $res = pg_query_params($conn, $sql, [$id]);
    if ($res && pg_num_rows($res) > 0) {
        $row       = pg_fetch_assoc($res);
        $name      = $row['name'] ?? '';
        $capacity  = $row['capacity'];
        $has_view  = ($row['has_view'] === 't' || $row['has_view'] === true);
        $has_private = ($row['has_private_area'] === 't' || $row['has_private_area'] === true);
        $latitude  = $row['latitude'];
        $longitude = $row['longitude'];
        $address   = $row['address'] ?? '';
    }
}

// URL do mapa (embed Google Maps)
$map_url = '';
if ($latitude && $longitude) {
    $map_url = 'https://maps.google.com/maps?q=' . urlencode($latitude . ',' . $longitude)
             . '&output=embed&zoom=15';
} elseif ($address) {
    $map_url = 'https://maps.google.com/maps?q=' . urlencode($address)
             . '&output=embed&zoom=15';
}
?>

<aside class="sidebar_show restaurante_sidebar">

    <!-- Criar product link -->
    <div class="sidebar_card sidebar_create_link">
        <a href="#" class="btn_create_link">
            <i class="material-icons">link</i>
            create product link
        </a>
    </div>

    <!-- Badges: Favorite / With a View -->
    <div class="sidebar_card sidebar_badges">
        <div class="badge_item badge_favorite">
            <div class="badge_icon">
                <i class="material-icons">star</i>
            </div>
            <span>FAVORITE</span>
        </div>

        <?php if ($has_view): ?>
        <div class="badge_item badge_view active">
            <div class="badge_icon badge_icon_view">
                <i class="material-icons">visibility</i>
            </div>
            <span>WITH A VIEW</span>
        </div>
        <?php endif; ?>

        <?php if ($has_private): ?>
        <div class="badge_item badge_private active">
            <div class="badge_icon badge_icon_private">
                <i class="material-icons">meeting_room</i>
            </div>
            <span>PRIVATE AREA</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Capacity -->
    <?php if ($capacity): ?>
    <div class="sidebar_card sidebar_capacity">
        <i class="material-icons">group</i>
        <span><?php echo (int)$capacity; ?></span>
    </div>
    <?php endif; ?>

    <!-- Personal note -->
    <div class="sidebar_card sidebar_note">
        <p class="note_label">Personal note<br>from the team</p>
        <div class="note_content" id="note_content_<?php echo $id; ?>">
            <textarea class="note_textarea" 
                      placeholder="Add a personal note..."
                      data-restaurant-id="<?php echo $id; ?>"
                      rows="5"><?php
            // Aqui você pode carregar nota salva do banco se tiver a tabela
            ?></textarea>
            <button class="btn_save_note" onclick="saveNote(<?php echo $id; ?>)">Save note</button>
        </div>
    </div>

    <!-- Mapa -->
    <?php if ($map_url): ?>
    <div class="sidebar_card sidebar_map">
        <iframe
            src="<?php echo h($map_url); ?>"
            width="100%"
            height="180"
            style="border:0; border-radius:4px;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
        <?php if ($address): ?>
            <a href="https://maps.google.com/?q=<?php echo urlencode($address); ?>"
               target="_blank"
               class="btn_open_map">
               <i class="material-icons">open_in_new</i> Show on map
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</aside>

<style>
/* =============================================
   LAYOUT: container principal (herdado dos hotéis)
============================================= */
#restaurante_result_inner .container {
    display: flex;
    gap: 28px;
    align-items: flex-start;
    padding: 30px 0 60px;
}
.container_description {
    flex: 1 1 0;
    min-width: 0;
}
.restaurante_sidebar {
    flex: 0 0 200px;
    max-width: 200px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: sticky;
    top: 90px;
}

/* =============================================
   CARDS DA SIDEBAR
============================================= */
.sidebar_card {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    padding: 14px;
}

/* Create product link */
.sidebar_create_link {
    text-align: center;
    padding: 10px 14px;
}
.btn_create_link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #2c6e9e;
    text-decoration: none;
    font-weight: 500;
}
.btn_create_link .material-icons { font-size: 1rem; }
.btn_create_link:hover { color: #1a4f78; }

/* Badges */
.sidebar_badges {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
}
.badge_item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: #666;
    text-align: center;
}
.badge_icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
}
.badge_icon .material-icons { font-size: 1.2rem; color: #fff; }

.badge_favorite .badge_icon { background: #2c6e9e; }
.badge_icon_view            { background: #e05080; }
.badge_icon_private         { background: #6a5acd; }

/* Capacity */
.sidebar_capacity {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}
.sidebar_capacity .material-icons { font-size: 1.4rem; color: #888; }

/* Personal note */
.note_label {
    font-size: 0.78rem;
    color: #333;
    font-weight: 600;
    margin: 0 0 10px;
    line-height: 1.4;
}
.note_textarea {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px;
    font-size: 0.78rem;
    color: #444;
    resize: vertical;
    box-sizing: border-box;
    font-family: inherit;
    line-height: 1.5;
}
.note_textarea:focus {
    outline: none;
    border-color: #2c6e9e;
}
.btn_save_note {
    margin-top: 8px;
    width: 100%;
    background: #2c6e9e;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 6px 0;
    font-size: 0.75rem;
    cursor: pointer;
    transition: background 0.2s;
}
.btn_save_note:hover { background: #1a4f78; }

/* Mapa */
.sidebar_map { padding: 10px; }
.btn_open_map {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 8px;
    font-size: 0.75rem;
    color: #2c6e9e;
    text-decoration: none;
}
.btn_open_map .material-icons { font-size: 0.9rem; }
.btn_open_map:hover { color: #1a4f78; }

/* Responsive */
@media (max-width: 800px) {
    #restaurante_result_inner .container {
        flex-direction: column;
    }
    .restaurante_sidebar {
        flex: none;
        max-width: 100%;
        width: 100%;
        position: static;
    }
}
</style>

<script>
function saveNote(restaurantId) {
    var textarea = document.querySelector('.note_textarea[data-restaurant-id="' + restaurantId + '"]');
    if (!textarea) return;
    var note = textarea.value.trim();

    // Aqui você pode fazer um AJAX para salvar a nota
    // Por enquanto apenas confirma visualmente
    var btn = textarea.nextElementSibling;
    var original = btn.textContent;
    btn.textContent = 'Saved!';
    btn.style.background = '#27ae60';
    setTimeout(function() {
        btn.textContent = original;
        btn.style.background = '';
    }, 2000);
}
</script>