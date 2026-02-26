                <style>
                    /* Caixa principal */
.sidebar-box {
    background-color: #f3f6f8;
    border: 1px solid #b6d0dd;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

/* Ícones topo (Unique / Favorite) */
.top-icons {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-bottom: 20px;
}

.top-icons .icon-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
}

.icon-unique {
    background: linear-gradient(135deg, #d61b4e, #ff4c6d);
}

.icon-favorite {
    background: linear-gradient(135deg, #f2c200, #ffd84d);
}

/* Divisor */
.sidebar-divider {
    border-top: 1px solid #b6d0dd;
    margin: 15px 0;
}

/* Stars e Rooms */
.stats-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-align: center;
    padding: 10px 0;
}

.stats-item {
    flex: 1;
    font-size: 14px;
    color: #5f6f78;
}

.stats-item i {
    font-size: 20px;
    margin-bottom: 5px;
}

.stats-divider {
    width: 1px;
    height: 40px;
    background-color: #b6d0dd;
}

/* Personal note */
.personal-note {
    font-size: 14px;
    color: #5f6f78;
    line-height: 1.6;
    padding: 15px 0;
}

.personal-note strong {
    display: block;
    text-align: center;
    margin-bottom: 10px;
    color: #3a4a52;
}

/* Linha decorativa */
.note-divider {
    width: 60%;
    height: 2px;
    background-color: #b6d0dd;
    margin: 10px auto 20px auto;
}

/* Mapa */
.map-container {
    height: 150px;
    border-radius: 8px;
    overflow: hidden;
    margin-top: 15px;
}

.map-container iframe {
    width: 100%;
    height: 100%;
    border: 0;
}

/* Link maps */
.map-link {
    text-align: center;
    font-size: 13px;
    margin-top: 10px;
}

                </style>
                <div class="col-lg-3">
    <div class="sidebar-box">

        <!-- Top Icons -->
        <div class="top-icons">
            <div class="icon-circle icon-unique">
                <i class="fas fa-gem"></i>
            </div>
            <div class="icon-circle icon-favorite">
                <i class="fas fa-star"></i>
            </div>
        </div>

        <div class="sidebar-divider"></div>

        <!-- Stars / Rooms -->
        <div class="stats-row">
            <div class="stats-item">
                <i class="fas fa-star text-warning"></i><br>
                <span id="sidebarStarRating">--</span>
            </div>

            <div class="stats-divider"></div>

            <div class="stats-item">
                <i class="fas fa-bed text-secondary"></i><br>
                <span id="sidebarTotalRooms">--</span>
            </div>
        </div>

        <div class="sidebar-divider"></div>

        <!-- Personal Note -->
        <div class="personal-note p-2">
            <strong>Personal note from the team</strong>
            <div class="note-divider" style="border-bottom: 1px solid black;"></div>
            <p id="sidebarPersonalNote">No notes available.</p>
        </div>

        <!-- Map -->
        <div class="map-container">
            <iframe
                id="sidebarMapFrame"
                src="https://www.google.com/maps?q=-22.9068,-43.1729&z=14&output=embed"
                loading="lazy"
                allowfullscreen>
            </iframe>
        </div>

        <div class="map-link">
            <a id="sidebarMapLink" href="#" class="text-decoration-none" target="_blank" rel="noopener">View on Google Maps</a>
        </div>

    </div>
</div>
