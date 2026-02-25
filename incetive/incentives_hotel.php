<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLUMAR - Incentives</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo_novo_hotelshow.css">

    <style>
        /* Filters Bar - Principal */

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

    <!-- Hotels Section -->
    <section class="hotels-section">
        <div class="hotels-title">
            <h1>Incentives</h1>
            <span class="hotels-subtitle">Rio de Janeiro's incentives selection</span>
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
            <a href="#">Incentive Area</a> › <a href="#">Incentives</a> › <strong>Rio de Janeiro</strong>
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

    <!-- Hotels Grid -->
    <div class="hotels-grid">
        <!-- Hotel 1 - Copacabana Palace -->
        <div class="hotel-card">
            <img src="https://images.unsplash.com/photo-1582719508461-905c673771fd?w=500&h=300&fit=crop" alt="Copacabana Palace" class="hotel-image">
            <div class="hotel-content">
                <div class="hotel-tag">—</div>
                <div class="hotel-location">Rio de Janeiro</div>
                <div class="hotel-type">Hotel</div>
                <h2 class="hotel-name">Copacabana Palace</h2>
                <div class="hotel-details">
                    <div>Stars: 5</div>
                    <div>No. Rooms: XXXXXX</div>
                    <div>Capacity:</div>
                </div>
                <p class="hotel-description">
                    Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso. Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso.
                </p>
                <a class="read-more-btn" href="incentive_hotel_show.php?nome=Copacabana%20Palace">Read More</a>
            </div>
        </div>

        <!-- Hotel 2 - Fasano Rio -->
        <div class="hotel-card">
            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&h=300&fit=crop" alt="Fasano Rio" class="hotel-image">
            <div class="hotel-content">
                <div class="hotel-tag">—</div>
                <div class="hotel-location">Rio de Janeiro</div>
                <div class="hotel-type">Hotel</div>
                <h2 class="hotel-name">Fasano Rio</h2>
                <div class="hotel-details">
                    <div>Stars: 5</div>
                    <div>No. Rooms: XXXXXX</div>
                    <div>Capacity:</div>
                </div>
                <p class="hotel-description">
                    Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso. Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso.
                </p>
                <a class="read-more-btn" href="incentive_hotel_show.php?nome=Fasano%20Rio">Read More</a>
            </div>
        </div>

        <!-- Hotel 3 - Fairmont Rio de Janeiro -->
        <div class="hotel-card">
            <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=500&h=300&fit=crop" alt="Fairmont Rio de Janeiro" class="hotel-image">
            <div class="hotel-content">
                <div class="hotel-tag">—</div>
                <div class="hotel-location">Rio de Janeiro</div>
                <div class="hotel-type">Hotel</div>
                <h2 class="hotel-name">Fairmont Rio de Janeiro</h2>
                <div class="hotel-details">
                    <div>Stars: 5</div>
                    <div>No. Rooms: XXXXXX</div>
                    <div>Capacity:</div>
                </div>
                <p class="hotel-description">
                    Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso. Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso.
                </p>
                <a class="read-more-btn" href="incentive_hotel_show.php?nome=Fairmont%20Rio%20de%20Janeiro">Read More</a>
            </div>
        </div>

        <!-- Hotel 4 - Copacabana Palace (Segunda linha) -->
        <div class="hotel-card">
            <img src="https://images.unsplash.com/photo-1582719508461-905c673771fd?w=500&h=300&fit=crop" alt="Copacabana Palace" class="hotel-image">
            <div class="hotel-content">
                <div class="hotel-tag">—</div>
                <div class="hotel-location">Rio de Janeiro</div>
                <div class="hotel-type">Hotel</div>
                <h2 class="hotel-name">Copacabana Palace</h2>
                <div class="hotel-details">
                    <div>Stars: 5</div>
                    <div>No. Rooms: XXXXXX</div>
                    <div>Capacity:</div>
                </div>
                <p class="hotel-description">
                    Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso. Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso.
                </p>
                <a class="read-more-btn" href="incentive_hotel_show.php?nome=Copacabana%20Palace">Read More</a>
            </div>
        </div>

        <!-- Hotel 5 - Fasano Rio (Segunda linha) -->
        <div class="hotel-card">
            <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&h=300&fit=crop" alt="Fasano Rio" class="hotel-image">
            <div class="hotel-content">
                <div class="hotel-tag">—</div>
                <div class="hotel-location">Rio de Janeiro</div>
                <div class="hotel-type">Hotel</div>
                <h2 class="hotel-name">Fasano Rio</h2>
                <div class="hotel-details">
                    <div>Stars: 5</div>
                    <div>No. Rooms: XXXXXX</div>
                    <div>Capacity:</div>
                </div>
                <p class="hotel-description">
                    Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso. Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso.
                </p>
                <a class="read-more-btn" href="incentive_hotel_show.php?nome=Fasano%20Rio">Read More</a>
            </div>
        </div>

        <!-- Hotel 6 - Fairmont Rio de Janeiro (Segunda linha) -->
        <div class="hotel-card">
            <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=500&h=300&fit=crop" alt="Fairmont Rio de Janeiro" class="hotel-image">
            <div class="hotel-content">
                <div class="hotel-tag">—</div>
                <div class="hotel-location">Rio de Janeiro</div>
                <div class="hotel-type">Hotel</div>
                <h2 class="hotel-name">Fairmont Rio de Janeiro</h2>
                <div class="hotel-details">
                    <div>Stars: 5</div>
                    <div>No. Rooms: XXXXXX</div>
                    <div>Capacity:</div>
                </div>
                <p class="hotel-description">
                    Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso. Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso.
                </p>
                <a class="read-more-btn" href="incentive_hotel_show.php?nome=Fairmont%20Rio%20de%20Janeiro">Read More</a>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/eventos_hotels.js"></script>
    <script src="js/incentives_hotels.js"></script>
</body>
</html>
