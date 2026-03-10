<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLUMAR - Venues</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo_novo_hotelshow.css">

<style>
    /* Filters Bar - Principal */
    .filters-bar {
        background-color: #d4af37;
    }
    .filters-text-bar,
    .stars-label,
    .star-checkbox label {
        color: #ffffff;
    }
    .apply-filter-btn {
        border-color: rgba(255, 255, 255, 0.9);
        color: #ffffff;
    }
    .apply-filter-btn:hover {
        background-color: #ffffff;
        color: #7a5c00;
    }
</style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">BLUMAR</div>
        <div class="header-right">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 60 30'%3E%3Crect fill='%23B22234' width='60' height='30'/%3E%3Cpath d='M0,3.5h60 M0,10.5h60 M0,17.5h60 M0,24.5h60' stroke='%23FFF' stroke-width='2'/%3E%3Crect fill='%233C3B6E' width='24' height='17.5'/%3E%3C/svg%3E" alt="US Flag" class="flag">
            <button class="back-btn">Back to Main Site</button>
        </div>
    </header>

    <!-- Venues Section -->
    <section class="hotels-section">
        <div class="hotels-title">
            <h1>Venues</h1>
            <span class="hotels-subtitle">Rio de Janeiro's venues selection</span>
        </div>
        <div class="header-actions">
            <div class="change-city">Change the city</div>
            <div class="filters-text">
                <span>Filters</span>
                <svg class="filters-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 4h18v2H3V4zm3 7h12v2H6v-2zm4 7h4v2h-4v-2z"/>
                </svg>
            </div>
        </div>
    </section>

    <!-- Breadcrumb -->
    <div class="breadcrumb-section">
        <div class="breadcrumb">
            <a href="#">Incentive Area</a> › <a href="#">Venues</a> › <strong>Rio de Janeiro</strong>
        </div>
    </div>

    <!-- Filters Bar (aparece quando clica em Filters) -->
    <div class="filters-bar" id="filtersBar" style="display: none;">
    <div class="filters-left">
        <div class="filters-text-bar">
            <span>Filters</span>
            <svg class="filters-icon" viewBox="0 0 24 24" fill="white">
                <path d="M3 4h18v2H3V4zm3 7h12v2H6v-2zm4 7h4v2h-4v-2z"/>
            </svg>
        </div>
        <select class="location-select" id="locationSelect" required>
            <option value="" disabled selected>select the location</option>
            <option value="Copacabana">Copacabana</option>
            <option value="Ipanema">Ipanema</option>
            <option value="Leblon">Leblon</option>
            <option value="Barra">Barra da Tijuca</option>
        </select>
    </div>
    
    <div class="stars-filter">
        <span class="stars-label">Stars</span>
        <div class="star-checkbox">
            <input type="checkbox" id="star5" value="5">
            <label for="star5">5</label>
        </div>
        <div class="star-checkbox">
            <input type="checkbox" id="star4" value="4">
            <label for="star4">4</label>
        </div>
        <div class="star-checkbox">
            <input type="checkbox" id="star3" value="3">
            <label for="star3">3</label>
        </div>
    </div>

    <button class="apply-filter-btn" id="applyFilters">Apply Filter</button>
</div>

    <!-- Filter Pills (aparece quando filtros são aplicados) -->
    <div class="filter-pills-section" id="filterPillsSection" style="display: none;">
        <div class="filter-pills" id="filterPills">
            <span class="filter-status">Filters applied</span>
            <div id="pillsContainer"></div>
            <button class="pill-btn clear-btn" id="clearAllFilters">Clear Filters</button>
        </div>
    </div>

    <!-- Venues Grid (dinamico) -->
    <div class="hotels-grid" id="venuesGrid">
        <div class="hotel-card">
            <div class="hotel-content">
                <p>Carregando venues...</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const VENUES_API_URL = 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api/venues.php';
        const PLACEHOLDER_IMAGE = 'img/hotel_01.png';

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function pickVenueImage(venue) {
            if (Array.isArray(venue.imagens) && venue.imagens.length > 0) {
                const primary = venue.imagens.find(img => img.is_primary) || venue.imagens[0];
                return primary && primary.image_url ? primary.image_url : PLACEHOLDER_IMAGE;
            }
            return PLACEHOLDER_IMAGE;
        }

        function renderVenues(venues) {
            if (!Array.isArray(venues) || venues.length === 0) {
                $('#venuesGrid').html('<div class="hotel-card"><div class="hotel-content"><p>Nenhum venue encontrado.</p></div></div>');
                return;
            }

            const html = venues.map(v => {
                const name = escapeHtml(v.name || v.nome || '-');
                const city = escapeHtml(v.city_name || v.city || '-');
                const desc = escapeHtml(v.short_description || v.especialidade || '');
                const image = escapeHtml(pickVenueImage(v));
                const detailsUrl = v.cod_venues ? `venue_show.php?id=${encodeURIComponent(v.cod_venues)}` : '#';

                return `
                    <div class="hotel-card">
                        <img src="${image}" alt="${name}" class="hotel-image" onerror="this.src='${PLACEHOLDER_IMAGE}'">
                        <div class="hotel-content">
                            <div class="hotel-tag">&mdash;</div>
                            <div class="hotel-location">${city}</div>
                            <div class="hotel-type">Venue</div>
                            <h2 class="hotel-name">${name}</h2>
                            <div class="hotel-details">
                                <div>Capacity: ${escapeHtml(v.capacity_max || '-')}</div>
                            </div>
                            <p class="hotel-description">${desc}</p>
                            <a class="read-more-btn" href="${detailsUrl}">Read More</a>
                        </div>
                    </div>
                `;
            }).join('');

            $('#venuesGrid').html(html);
        }

        function loadVenues() {
            $.ajax({
                url: VENUES_API_URL,
                method: 'GET',
                dataType: 'json',
                data: {
                    request: 'listar_venues',
                    filtro_nome: '',
                    filtro_ativo: 'all',
                    cidade: '',
                    limit: 200
                }
            }).done(function (data) {
                renderVenues(Array.isArray(data) ? data : []);
            }).fail(function () {
                $('#venuesGrid').html('<div class="hotel-card"><div class="hotel-content"><p>Erro ao carregar venues.</p></div></div>');
            });
        }

        $(document).ready(function () {
            loadVenues();
        });
    </script>
    <?php include 'footer_show.php'; ?>
</body>
</html>
