const API_DEFAULT = 'https://webdeveloper.blumar.com.br/desenv/roger/conteudo/api/hotels.php';
const HOTELS_API_URL = window.HOTELS_API_URL || API_DEFAULT;


function openGalleryModal() {
  const items = Array.isArray(window.galleryItems) && window.galleryItems.length
    ? window.galleryItems
    : [{ url: 'img/hotel_01.png', label: 'Hotel' }];

  const galleryGrid = document.getElementById('galleryGrid');
  if (galleryGrid) {
    galleryGrid.innerHTML = items
      .map(
        (item) => `
        <div class="gallery-card">
          <img src="${item.url}" alt="${item.label || 'Foto'}" onerror="this.src='img/hotel_01.png'">
          <span class="gallery-caption">${item.label || 'Legenda'}</span>
        </div>
      `
      )
      .join('');
  }

  const modalEl = document.getElementById('galleryModal');
  if (modalEl && window.bootstrap) {
    const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
}


function setText(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}

function updateMedia(hotel, name) {
  const mainMedia = document.getElementById('hotelMainMedia');
  const mainImage =
    hotel.imagem_fachada ||
    hotel.imagem_piscina ||
    hotel.fotoextra ||
    hotel.fotoextra_recep ||
    hotel.ft_resort1 ||
    hotel.ft_resort2 ||
    hotel.ft_resort3;

  if (mainMedia) {
    if (hotel.video_url) {
      mainMedia.innerHTML = `<iframe width="100%" height="422" src="${hotel.video_url}" title="Hotel video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>`;
    } else if (mainImage) {
      mainMedia.innerHTML = `<img src="${mainImage}" alt="${name || 'Hotel'}" style="width:100%; height:422px; object-fit:cover; border-radius:8px;" onerror="this.src='img/hotel_01.png'">`;
    }
  }

  const thumbs = [
    { url: hotel.imagem_piscina, label: 'Piscina' },
    { url: hotel.fotoextra, label: 'Foto' },
    { url: hotel.fotoextra_recep, label: 'Recepção' },
    { url: hotel.ft_resort1, label: 'Resort' },
    { url: hotel.ft_resort2, label: 'Resort' },
    { url: hotel.ft_resort3, label: 'Resort' }
  ].filter((t) => !!t.url);

  if (thumbs.length) {
    const t1 = document.getElementById('thumb1');
    const t2 = document.getElementById('thumb2');
    const t3 = document.getElementById('thumb3');
    if (t1 && thumbs[0]) t1.src = thumbs[0].url;
    if (t2 && thumbs[1]) t2.src = thumbs[1].url;
    if (t3 && thumbs[2]) t3.src = thumbs[2].url;

    [t1, t2, t3].forEach((imgEl) => {
      if (!imgEl) return;
      imgEl.onerror = function () {
        this.src = 'img/hotel_01.png';
      };
    });
  }

  // Atualiza contagem e grid do modal
  const galleryCount = document.getElementById('galleryCount');
  if (galleryCount) {
    const total = thumbs.length || 0;
    galleryCount.innerHTML = `<i class=\"fas fa-camera me-2\"></i> View ${total} photos`;
  }

  const galleryGrid = document.getElementById('galleryGrid');
  if (galleryGrid) {
    const items = thumbs.length ? thumbs : [{ url: 'img/hotel_01.png', label: 'Hotel' }];
    galleryGrid.innerHTML = items
      .map(
        (item) => `
        <div class=\"gallery-card\">
          <img src=\"${item.url}\" alt=\"${item.label}\" onerror=\"this.src='img/hotel_01.png'\" />
          <span class=\"gallery-caption\">${item.label}</span>
        </div>
      `
      )
      .join('');
  }

  // Guarda para uso no clique do modal
  window.galleryItems = thumbs.length ? thumbs : [{ url: 'img/hotel_01.png', label: 'Hotel' }];
}

function renderHotel(hotel) {
  if (!hotel || hotel.error) return;

  const name = hotel.nome || hotel.nome_for || hotel.nome_produto || hotel.codigo;
  const city = hotel.cidade || hotel.cidade_nome;
  const desc = hotel.descricao_ingles || hotel.descricao || hotel.descricao_espanhol;

  if (name) {
    document.title = `Blumar - ${name}`;
    setText('hotelTitle', name);
    setText('hotelNameBreadcrumb', name);
  }

  if (city) {
    setText('hotelCity', city);
    setText('hotelCitySubtitle', `${city} hotels selection`);
  }

  if (desc) {
    setText('hotelDescription', desc);
  }

  updateMedia(hotel, name);
}

function loadHotelById(id) {
  if (!id) return;
  const url = `${HOTELS_API_URL}?request=buscar_hotel&id=${encodeURIComponent(id)}`;
  fetch(url)
    .then((res) => res.json())
    .then((data) => renderHotel(data))
    .catch(() => {});
}

function loadHotelByName(nome) {
  if (!nome) return;
  const url = `${HOTELS_API_URL}?request=listar_hoteis&nome=${encodeURIComponent(nome)}&limit=1`;
  fetch(url)
    .then((res) => res.json())
    .then((data) => {
      const hotel = Array.isArray(data) ? data[0] : data;
      renderHotel(hotel);
    })
    .catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.querySelector('.gallery-more-overlay');
  if (overlay) {
    overlay.addEventListener('click', openGalleryModal);
  }

  const params = new URLSearchParams(window.location.search);
  const frcod = params.get('frcod');
  const id = frcod || params.get('id');
  const nome = params.get('nome');

  if (id && !isNaN(Number(id))) {
    loadHotelById(id);
  } else if (nome) {
    loadHotelByName(nome);
  } else {
    setText('hotelTitle', 'Selecione um hotel');
    setText('hotelDescription', 'Nenhum hotel foi selecionado para exibir.');
  }
});
