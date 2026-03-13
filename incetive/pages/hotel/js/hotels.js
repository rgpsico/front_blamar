const API_BASE_URL = 'https://www.blumar.com.br/client_area_incentive/api/hotels.php';
const DEFAULT_IMAGE = 'img/hotel_01.png';

// Seletores corrigidos
const $citySelect = $('.select_hotel');
const $starsSelect = $('.select_stars');

// Array para armazenar todos os hotéis
let allHotels = [];

function setLoading(message) {
    $('.container .box_hotels').parent().html(`<div style="text-align:center;padding:40px;">${message}</div>`);
}

function formatText(value, fallback = '-') {
    return (value == null || value === '') ? fallback : String(value).trim();
}

function createHotelCard(hotel) {
    const city = formatText(hotel.cidade, 'Rio de Janeiro');
    const category = formatText(hotel.categoria || hotel.classificacao, 'Hotel');
    const name = formatText(hotel.nome || hotel.codigo, 'Hotel sem nome');
    const stars = hotel.estrelas > 0 ? hotel.estrelas : '-';
    const rooms = hotel.quartos > 0 ? hotel.quartos : '-';
    
    // Lógica de imagem
    let imgSrc = DEFAULT_IMAGE;
    if (hotel.imagem_fachada) {
        imgSrc = hotel.imagem_fachada;
    } else if (hotel.htlimgfotofachada) {
        imgSrc = 'https://www.blumar.com.br/' + hotel.htlimgfotofachada;
    } else if (hotel.fotofachada_tbn && hotel.fotofachada_tbn !== 'https://www.blumar.com.br/tese') {
        imgSrc = hotel.fotofachada_tbn;
    }
    console.log(`Imagem para hotel ${name}: ${imgSrc}`);
    // Descrição
    const description = formatText(hotel.descricao, 'Breve descritivo do Hotel, algo em torno de 3 linhas para que não fique um texto muito extenso.');

    // Construindo o HTML do card
    const cardHTML = `
        <div class="box_hotels">
            <img src="${imgSrc}" alt="${name}" onerror="this.src='${DEFAULT_IMAGE}'">
            <div class="box_hotels_conteudo">
                <div class="line"></div>
                <h3>${city}<br><span>${category}</span></h3>
                <h4>${name}</h4>
                <p>Stars: ${stars}
                No. Rooms: ${rooms}
                Capacity: -
                </p>
                <p class="hotels_description">${description}</p>
                <a href="#hotel/${hotel.frncod || hotel.codigo || ''}">
                    <button>Read More</button>
                </a>
            </div>
        </div>
    `;

    return cardHTML;
}

function renderHotels(hotels) {
    const $container = $('#hotels_result .container');
    
    if (!Array.isArray(hotels) || hotels.length === 0) {
        $container.html('<div style="text-align:center;padding:40px;">Nenhum hotel encontrado.</div>');
        return;
    }

    // Limpa o container e adiciona os novos hotéis
    $container.empty();
    
    hotels.forEach(hotel => {
        $container.append(createHotelCard(hotel));
    });

    console.log(`${hotels.length} hotéis renderizados`);
}

function loadAllHotelsAndCities() {
    setLoading('Carregando hotéis...');
    
    $.ajax({
        url: API_BASE_URL,
        method: 'GET',
        dataType: 'json',
        data: { 
            request: 'listar_hoteis',
            limit: 1000 
        }
    })
    .done((hotels) => {
        console.log('Total de hotéis carregados:', hotels.length);
        
        if (!Array.isArray(hotels) || hotels.length === 0) {
            setLoading('Nenhum hotel retornado pela API.');
            return;
        }

        // Armazena todos os hotéis
        allHotels = hotels;

        // Filtra hotéis do Rio de Janeiro
        const rioHotels = hotels.filter(h => 
            (h.cidade || '').toUpperCase().includes('RIO')
        );

        console.log('Hotéis do Rio de Janeiro:', rioHotels.length);

        if (rioHotels.length > 0) {
            renderHotels(rioHotels);
        } else {
            // Se não encontrar Rio, mostra os primeiros 50
            console.log('Nenhum hotel do Rio encontrado. Mostrando todos disponíveis.');
            renderHotels(hotels.slice(0, 50));
        }

        // Popula select de cidades
        const citiesSet = new Set();
        hotels.forEach(h => {
            const city = (h.cidade || '').trim();
            if (city.length > 2) {
                citiesSet.add(city);
            }
        });

        const sortedCities = [...citiesSet].sort();
        
        $citySelect.empty().append('<option value="">Selecione a localização</option>');
        
        sortedCities.forEach(city => {
            $citySelect.append(`<option value="${city}">${city}</option>`);
        });

        // Seleciona "RIO DE JANEIRO" por padrão se existir
        const rioOption = sortedCities.find(c => c.toUpperCase().includes('RIO'));
        if (rioOption) {
            $citySelect.val(rioOption);
        }
    })
    .fail((jqXHR, textStatus, error) => {
        console.error('Erro na API:', jqXHR.status, textStatus, error);
        setLoading('Falha ao conectar com a API de hotéis.');
    });
}

function filterHotels() {
    const selectedCity = $citySelect.val();
    const selectedStars = $starsSelect.val();

    let filtered = allHotels;

    // Filtro por cidade
    if (selectedCity) {
        filtered = filtered.filter(h => 
            (h.cidade || '').toUpperCase().includes(selectedCity.toUpperCase())
        );
    }

    // Filtro por estrelas
    if (selectedStars && selectedStars !== 'Selecione') {
        const stars = parseInt(selectedStars);
        filtered = filtered.filter(h => h.estrelas === stars);
    }

    renderHotels(filtered);
}

// Inicialização quando o documento estiver pronto
$(function() {
    console.log('Iniciando carregamento de hotéis...');
    
    // Carrega todos os hotéis e popula as cidades
    loadAllHotelsAndCities();

    // Event listeners para os filtros
    $citySelect.on('change', filterHotels);
    $starsSelect.on('change', filterHotels);
});