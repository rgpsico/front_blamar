<?php
require_once __DIR__ . '/../session_middleware.php';
// requireAuthenticatedSession();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLUMAR - Venues</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/estilo_novo_hotelshow.css">
    <style>
        .filters-bar { background-color: #d4af37; }
        .filters-text-bar, .stars-label, .star-checkbox label { color: #ffffff; }
        .apply-filter-btn { border-color: rgba(255, 255, 255, 0.9); color: #ffffff; }
        .apply-filter-btn:hover { background-color: #ffffff; color: #7a5c00; }
        #venuesGrid { align-items: stretch; }
        #venuesGrid .hotel-card { display: flex; flex-direction: column; height: 100%; }
        #venuesGrid .hotel-content { display: flex; flex-direction: column; flex: 1 1 auto; }
        #venuesGrid .hotel-description { flex-grow: 1; }
        #venuesGrid .read-more-btn { margin-top: auto; align-self: flex-start; }
        #venuesGrid .vcard-topline { width: 20px; height: 3px; background: #e69928; border-radius: 2px; margin: 10px 0 8px; }
        #venuesGrid .vcard-city { font-size: 16px; color: #666; line-height: 1.2; }
        #venuesGrid .vcard-type { font-size: 28px; color: #333; line-height: 1.15; margin-bottom: 10px; }
        #venuesGrid .vcard-name { margin: 0 0 10px; }
        #venuesGrid .vcard-meta { font-size: 13px; color: #6b6b6b; line-height: 1.5; margin-bottom: 10px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/include/header_venues.php'; ?>

    <div class="filters-bar" id="filtersBar" style="display: flex;"> <div class="filters-left">
            <div class="filters-text-bar">
                <span>Filters</span>
                <svg class="filters-icon" viewBox="0 0 24 24" fill="white" width="24" height="24">
                    <path d="M3 4h18v2H3V4zm3 7h12v2H6v-2zm4 7h4v2h-4v-2z"/>
                </svg>
            </div>
            <select class="location-select" id="locationSelect">
                <option value="" selected>All Locations</option>
                </select>
        </div>

        <button class="apply-filter-btn" id="applyFilters">Apply Filter</button>
    </div>

    <div class="filter-pills-section" id="filterPillsSection" style="display: none;">
        <div class="filter-pills" id="filterPills">
            <span class="filter-status">Filters applied</span>
            <div id="pillsContainer"></div>
            <button class="pill-btn clear-btn" id="clearAllFilters">Clear Filters</button>
        </div>
    </div>

    <div class="hotels-grid" id="venuesGrid">
        <div class="hotel-card">
            <div class="hotel-content">
                <p>Carregando venues...</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/eventos_venues.js"></script>
    <script src="../js/venues.js"></script> <?php include 'footer_show.php'; ?>
</body>
</html>