const SIDEBAR_API_URL = 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api/api_incentives.php';

function pickBestNote(notes) {
    if (!Array.isArray(notes) || notes.length === 0) return null;
    
    const byLang = (lang) => notes.find(n => 
        n && n.note && String(n.language || '').toUpperCase() === lang
    );
    
    return byLang('EN') || byLang('PT') || notes.find(n => n && n.note) || null;
}

function buildMapsUrl(contact) {
    if (!contact) return null;

    // Prioridade 1: coordenadas (se preenchidas no banco → embed perfeito)
    if (contact.latitude != null && contact.longitude != null) {
        return `https://www.google.com/maps?q=${encodeURIComponent(contact.latitude)},${encodeURIComponent(contact.longitude)}&z=15`;
    }

    // Prioridade 2: google_maps_url (pode ser curto ou completo)
    let url = contact.google_maps_url?.trim?.();
    if (url) {
        // Se for short link (maps.app.goo.gl ou goo.gl) → retorna para o <a> apenas
        if (url.includes('maps.app.goo.gl') || url.includes('goo.gl')) {
            return url;
        }
        // Se parecer URL completa com q= ou /place/ → pode tentar embed
        if (url.includes('q=') || url.includes('/place/') || url.includes('embed')) {
            return url;
        }
    }

    // Prioridade 3: fallback com endereço (melhor que nada)
    const addressParts = [
        contact.address,
        contact.postal_code,
        contact.city_name || 'Rio de Janeiro', // fallback se city_name não vier
        contact.state_code || 'RJ',
        'Brasil'
    ].filter(Boolean);

    if (addressParts.length >= 2) { // pelo menos rua + cidade ou CEP
        const query = encodeURIComponent(addressParts.join(', '));
        return `https://www.google.com/maps?q=${query}&z=15`;
    }

    return null;
}

function updateSidebar(program, relations) {
    // Estrelas
    const starEl = document.getElementById('sidebarStarRating');
    if (starEl) {
        starEl.textContent = program?.star_rating ?? '--';
    }

    // Quartos
    const roomsEl = document.getElementById('sidebarTotalRooms');
    if (roomsEl) {
        roomsEl.textContent = program?.total_rooms ?? '--';
    }

    // Nota pessoal
    const noteEl = document.getElementById('sidebarPersonalNote');
    if (noteEl) {
        const note = pickBestNote(relations?.notes || []);
        noteEl.textContent = note?.note?.trim() || 'No notes available.';
    }

    // Mapa e link
    const mapFrame = document.getElementById('sidebarMapFrame');
    const mapLink = document.getElementById('sidebarMapLink');

    if (mapFrame && mapLink) {
        const mapUrl = buildMapsUrl(relations?.hotel_contact || {});

        if (mapUrl) {
            const isShortLink = mapUrl.includes('maps.app.goo.gl') || mapUrl.includes('goo.gl');
            const canEmbed = !isShortLink && (mapUrl.includes('q=') || mapUrl.includes('/place/'));

            // Define src do iframe
            mapFrame.src = canEmbed ? `${mapUrl}&output=embed` : 'about:blank';

            // Se for short link ou sem embed possível → adiciona mensagem explicativa
            if (!canEmbed && isShortLink) {
                // Evita duplicar a mensagem se já existir
                if (!document.getElementById('map-fallback-msg')) {
                    mapFrame.insertAdjacentHTML('afterend',
                        '<p id="map-fallback-msg" class="text-muted small mt-2 text-center">' +
                        'Pré-visualização do mapa não disponível para links curtos.<br>' +
                        'Clique abaixo para abrir no Google Maps.' +
                        '</p>'
                    );
                }
            }

            // Configura o link clicável
            mapLink.href = mapUrl;
            mapLink.textContent = 'Ver no Google Maps';
            mapLink.target = '_blank';
            mapLink.rel = 'noopener noreferrer';
        } else {
            // Sem URL alguma
            mapFrame.src = 'about:blank';
            mapLink.href = '#';
            mapLink.textContent = 'Mapa não disponível';
            mapLink.style.color = '#999';
            mapLink.style.pointerEvents = 'none';
        }
    }
}

function loadSidebarIncentive(id) {
    if (!id || isNaN(Number(id))) return;

    const url = `${SIDEBAR_API_URL}?request=buscar_incentive&id=${encodeURIComponent(id)}`;

    fetch(url, { cache: 'no-cache' })
        .then(res => {
            if (!res.ok) throw new Error(`Erro HTTP: ${res.status}`);
            return res.json();
        })
        .then(payload => {
            if (!payload?.success || payload?.error) {
                console.warn('API retornou erro:', payload?.error || 'success=false');
                return;
            }

            const data = payload.data || payload;
            const program = data.program || data;
            const relations = data.relations || data;

            updateSidebar(program, relations);
        })
        .catch(err => {
            console.error('Falha ao carregar sidebar:', err);
            const noteEl = document.getElementById('sidebarPersonalNote');
            if (noteEl) noteEl.textContent = 'Erro ao carregar dados do hotel.';
        });
}

// Inicializa ao carregar a página
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    if (id) {
        loadSidebarIncentive(id);
    } else {
        console.warn('Parâmetro ?id= não encontrado na URL');
    }
});