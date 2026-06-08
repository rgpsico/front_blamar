// URL da API de entertainments
const API_BASE_URL = '../../../api/api_entertainments.php';
const DEFAULT_IMAGE = '../../img/hotel_01.png';

// Seletores
const $locationSelect = $('#locationSelect');
const $entertainmentGrid = $('#entertainmentGrid');
const $applyFiltersBtn = $('#applyFilters');

// Array para armazenar todos os entertainments e facilitar o filtro no frontend
let allEntertainments = [];

function setLoading(message) {
    $entertainmentGrid.html(`
        <div class="hotel-card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
            <div class="hotel-content"><p>${message}</p></div>
        </div>
    `);
}

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function createEntertainmentCard(item) {
    const title      = escapeHtml(item.title);
    const type       = escapeHtml(item.type || 'Entertainment');
    const city       = escapeHtml(item.cidade_nome || '-');
    const location   = escapeHtml(item.location_name || '-');
    const priceRange = escapeHtml(item.price_range || '-');
    const shortDesc  = escapeHtml(item.short_desc || '');

    let imgSrc = DEFAULT_IMAGE;
    if (item.cover_image_url) {
        imgSrc = item.cover_image_url;
    }

    return `
        <div class="hotel-card">
            <img src="${imgSrc}" alt="${title}" class="hotel-image" onerror="this.src='${DEFAULT_IMAGE}'">
            <div class="hotel-content">
                <div class="vcard-topline"></div>
                <div class="vcard-city">${city}</div>
                <div class="vcard-type">${type}</div>
                <h2 class="hotel-name vcard-name">${title}</h2>
                <div class="vcard-meta">
                    <div>Location: ${location}</div>
                    <div>Price range: ${priceRange}</div>
                </div>
                <p class="hotel-description">${shortDesc}</p>
            </div>
        </div>
    `;
}

function renderEntertainments(items) {
    if (!Array.isArray(items) || items.length === 0) {
        setLoading('No entertainment found with these filters.');
        return;
    }

    $entertainmentGrid.empty();
    items.forEach(item => $entertainmentGrid.append(createEntertainmentCard(item)));
}

function loadAllEntertainments() {
    setLoading('Loading entertainment...');

    $.ajax({
        url: API_BASE_URL,
        method: 'GET',
        dataType: 'json',
        data: {
            request: 'listar_entertainment',
            filtro_ativo: 'true',
            per_page: 500
        }
    })
    .done((resp) => {
        if (!resp || !Array.isArray(resp.data) || resp.data.length === 0) {
            setLoading('No entertainment returned by the API.');
            return;
        }

        allEntertainments = resp.data;
        renderEntertainments(allEntertainments);

        // Preencher o select de cidades dinamicamente
        const citiesSet = new Set();
        allEntertainments.forEach(item => {
            const city = (item.cidade_nome || '').trim();
            if (city.length > 0) citiesSet.add(city);
        });

        const sortedCities = [...citiesSet].sort();
        $locationSelect.empty().append('<option value="" selected>All Locations</option>');
        sortedCities.forEach(city => {
            $locationSelect.append(`<option value="${city}">${city}</option>`);
        });
    })
    .fail((jqXHR, textStatus, error) => {
        console.error('API error:', jqXHR.status, textStatus, error);
        setLoading('Failed to connect to the entertainment API.');
    });
}

function filterEntertainments() {
    const selectedCity = $locationSelect.val();
    let filtered = allEntertainments;

    if (selectedCity && selectedCity !== '') {
        filtered = filtered.filter(item =>
            (item.cidade_nome || '').toUpperCase().includes(selectedCity.toUpperCase())
        );
    }

    renderEntertainments(filtered);
}

// Inicialização
$(document).ready(function () {
    loadAllEntertainments();
    $applyFiltersBtn.on('click', filterEntertainments);
});
