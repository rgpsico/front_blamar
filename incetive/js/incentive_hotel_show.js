const API_DEFAULT = 'api_incentives.php';
const INCENTIVES_API_URL = window.INCENTIVES_API_URL || API_DEFAULT;
const FALLBACK_IMAGE = 'img/hotel_01.png';

function setText(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}

function isActive(item) {
  return item && item.is_active !== false;
}

function normalizeMedia(media) {
  if (!Array.isArray(media)) return [];
  return media.filter((m) => m && m.media_url && isActive(m));
}

function getMediaByType(media, type) {
  return media.filter((m) => m.media_type === type && m.media_url && isActive(m));
}

function pickMainMedia(media) {
  const video = getMediaByType(media, 'video')[0];
  if (video) return { type: 'video', url: video.media_url };

  const banner = getMediaByType(media, 'banner')[0];
  if (banner) return { type: 'image', url: banner.media_url };

  const gallery = getMediaByType(media, 'gallery')[0];
  if (gallery) return { type: 'image', url: gallery.media_url };

  const any = media.find((m) => m.media_url);
  if (any) {
    return {
      type: any.media_type === 'video' ? 'video' : 'image',
      url: any.media_url
    };
  }

  return { type: 'image', url: FALLBACK_IMAGE };
}

function buildGalleryItems(media) {
  const gallery = getMediaByType(media, 'gallery');
  const banner = getMediaByType(media, 'banner');

  let items = [];
  if (gallery.length) items = gallery;
  else if (banner.length) items = banner;
  else items = normalizeMedia(media);

  if (!items.length) {
    return [{ url: FALLBACK_IMAGE, label: 'Hotel' }];
  }

  return items.map((m) => ({
    url: m.media_url,
    label: m.media_type || 'Foto'
  }));
}

function updateMainMedia(media, name) {
  const mainMedia = document.getElementById('hotelMainMedia');
  if (!mainMedia) return;

  const main = pickMainMedia(media);
  if (main.type === 'video') {
    mainMedia.innerHTML = `
      <iframe
        width="100%"
        height="422"
        src="${main.url}"
        title="${name || 'Hotel video'}"
        frameborder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        referrerpolicy="strict-origin-when-cross-origin"
        allowfullscreen
      ></iframe>
    `;
  } else {
    mainMedia.innerHTML = `
      <img
        src="${main.url}"
        alt="${name || 'Hotel'}"
        style="width:100%; height:422px; object-fit:cover; border-radius:8px;"
        onerror="this.src='${FALLBACK_IMAGE}'"
      >
    `;
  }
}

function updateThumbs(items) {
  const t1 = document.getElementById('thumb1');
  const t2 = document.getElementById('thumb2');
  const t3 = document.getElementById('thumb3');

  const safeItems = items.length ? items : [{ url: FALLBACK_IMAGE }];

  if (t1) t1.src = safeItems[0] ? safeItems[0].url : FALLBACK_IMAGE;
  if (t2) t2.src = safeItems[1] ? safeItems[1].url : FALLBACK_IMAGE;
  if (t3) t3.src = safeItems[2] ? safeItems[2].url : FALLBACK_IMAGE;

  [t1, t2, t3].forEach((imgEl) => {
    if (!imgEl) return;
    imgEl.onerror = function () {
      this.src = FALLBACK_IMAGE;
    };
  });
}

function updateGalleryModal(items) {
  const galleryGrid = document.querySelector('#galleryModal .gallery-grid');
  if (!galleryGrid) return;

  const html = items
    .map(
      (item) => `
        <div class="col-4 col-sm-4 col-md-4">
          <img src="${item.url}" alt="${item.label || 'Foto'}" loading="lazy" onerror="this.src='${FALLBACK_IMAGE}'">
        </div>
      `
    )
    .join('');

  galleryGrid.innerHTML = html;
}

function updateGalleryCount(items) {
  const galleryCount = document.getElementById('galleryCount');
  if (!galleryCount) return;
  const total = items.length || 0;
  galleryCount.innerHTML = `<i class="fas fa-camera me-2"></i> View ${total} photos`;
}

function renderRoomCategories(roomCategories) {
  const list = document.getElementById('roomCategoriesList');
  if (!list) return;

  if (!Array.isArray(roomCategories) || roomCategories.length === 0) {
    list.innerHTML = '<li><i class="fas fa-check"></i> No room categories available.</li>';
    return;
  }

  const html = roomCategories
    .filter((r) => r && r.room_name && r.is_active !== false)
    .map((r) => {
      const qty = r.quantity !== null && r.quantity !== undefined ? ` (${r.quantity})` : '';
      const notes = r.notes ? ` - ${r.notes}` : '';
      return `<li><i class="fas fa-check"></i> ${r.room_name}${qty}${notes}</li>`;
    })
    .join('');

  list.innerHTML = html || '<li><i class="fas fa-check"></i> No room categories available.</li>';
}

function renderFacilities(facilities) {
  const container = document.getElementById('hotelFacilities');
  if (!container) return;

  if (!Array.isArray(facilities) || facilities.length === 0) {
    container.innerHTML = `
      <div class="col-md-3">
        <ul class="facility-list">
          <li><i class="fas fa-check"></i> No facilities available.</li>
        </ul>
      </div>
    `;
    return;
  }

  const items = facilities
    .filter((f) => f && f.name && f.is_active !== false)
    .map((f) => `<li><i class="fas fa-check"></i> ${f.name}</li>`);

  const columns = 3;
  const perCol = Math.ceil(items.length / columns) || 1;
  const colsHtml = [];

  for (let i = 0; i < columns; i += 1) {
    const slice = items.slice(i * perCol, (i + 1) * perCol);
    if (!slice.length) continue;
    colsHtml.push(`
      <div class="col-md-3">
        <ul class="facility-list">
          ${slice.join('')}
        </ul>
      </div>
    `);
  }

  container.innerHTML = colsHtml.join('');
}

function renderRoomFacilities(facilities) {
  const list = document.getElementById('roomFacilitiesList');
  if (!list) return;

  if (!Array.isArray(facilities) || facilities.length === 0) {
    list.innerHTML = '<li><i class="fas fa-circle" style="font-size:6px; vertical-align:middle;"></i> No facilities available.</li>';
    return;
  }

  const items = facilities
    .filter((f) => f && f.name && f.is_active !== false)
    .slice(0, 4)
    .map((f) => `<li><i class="fas fa-circle" style="font-size:6px; vertical-align:middle;"></i> ${f.name}</li>`)
    .join('');

  list.innerHTML = items || '<li><i class="fas fa-circle" style="font-size:6px; vertical-align:middle;"></i> No facilities available.</li>';
}

function renderDining(dining) {
  const container = document.getElementById('diningList');
  if (!container) return;

  if (!Array.isArray(dining) || dining.length === 0) {
    container.innerHTML = `
      <div class="dining-item">
        <img src="${FALLBACK_IMAGE}" class="dining-img" alt="Dining">
        <div>
          <h5 class="fw-bold fs-6">Dining</h5>
          <p class="small text-muted">No dining information available.</p>
        </div>
      </div>
    `;
    return;
  }

  const html = dining
    .filter((d) => d && d.name && d.is_active !== false)
    .map((d) => {
      const info = [
        d.cuisine ? `Cuisine: ${d.cuisine}` : null,
        d.capacity ? `Capacity: ${d.capacity}` : null,
        d.schedule ? `Schedule: ${d.schedule}` : null
      ]
        .filter(Boolean)
        .join(' | ');

      const extra = info ? `<div class="small text-muted">${info}</div>` : '';

      return `
        <div class="dining-item">
          <img src="${d.image_url || FALLBACK_IMAGE}" class="dining-img" alt="${d.name}" onerror="this.src='${FALLBACK_IMAGE}'">
          <div>
            <h5 class="fw-bold fs-6">${d.name}</h5>
            <p class="small text-muted">${d.description || ''}</p>
            ${extra}
          </div>
        </div>
      `;
    })
    .join('');

  container.innerHTML = html || container.innerHTML;
}

function renderConvention(convention, mapUrl) {
  const descriptionEl = document.getElementById('conventionDescription');
  const tableBody = document.getElementById('conventionRoomsBody');
  const floorPlan = document.getElementById('conventionFloorPlan');

  if (descriptionEl) {
    descriptionEl.textContent = convention && convention.description
      ? convention.description
      : 'No convention details available.';
  }

  if (tableBody) {
    if (!convention || !Array.isArray(convention.rooms) || convention.rooms.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="8" class="text-center text-muted">No rooms registered.</td>
        </tr>
      `;
    } else {
      const rows = convention.rooms
        .map((r) => `
          <tr>
            <td>${r.name || '-'}</td>
            <td>${r.area_m2 ?? '-'}</td>
            <td>${r.height_m ?? '-'}</td>
            <td>${r.capacity_auditorium ?? '-'}</td>
            <td>${r.capacity_classroom ?? '-'}</td>
            <td>${r.capacity_u_shape ?? '-'}</td>
            <td>${r.capacity_banquet ?? '-'}</td>
            <td>${r.capacity_cocktail ?? '-'}</td>
          </tr>
        `)
        .join('');
      tableBody.innerHTML = rows;
    }
  }

  if (floorPlan && mapUrl) {
    floorPlan.src = mapUrl;
  }
}

function updateTotalRooms(convention) {
  const el = document.getElementById('totalRoomsCount');
  if (!el) return;
  if (convention && convention.total_rooms) {
    el.textContent = `${convention.total_rooms} rooms`;
  } else {
    el.textContent = '--- rooms';
  }
}

function renderIncentive(payload) {
  if (!payload) return;
  const program = payload.program || payload;
  const relations = payload.relations || payload;

  const name = program.inc_name || program.hotel_name_snapshot || `Incentive ${program.inc_id || ''}`.trim();
  const city = program.city_name || '';
  const desc = program.inc_description || '';

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

  const media = normalizeMedia(relations.media || []);
  updateMainMedia(media, name);

  const galleryItems = buildGalleryItems(media);
  updateThumbs(galleryItems);
  updateGalleryCount(galleryItems);
  updateGalleryModal(galleryItems);

  renderRoomCategories(relations.room_categories || []);
  renderFacilities(relations.facilities || []);
  renderRoomFacilities(relations.facilities || []);
  renderDining(relations.dining || []);
  const mapItem = getMediaByType(media, 'map')[0] || null;
  renderConvention(relations.convention || null, mapItem ? mapItem.media_url : null);
  updateTotalRooms(relations.convention || null);
}

function loadIncentiveById(id) {
  if (!id) return;
  const url = `${INCENTIVES_API_URL}?request=buscar_incentive&id=${encodeURIComponent(id)}`;
  fetch(url)
    .then((res) => res.json())
    .then((payload) => {
      if (!payload || payload.error || payload.success === false) return;
      const data = payload.data || payload;
      renderIncentive(data);
    })
    .catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');

  if (id && !isNaN(Number(id))) {
    loadIncentiveById(id);
  } else {
    setText('hotelTitle', 'Selecione um incentivo');
    setText('hotelDescription', 'Nenhum incentivo foi selecionado para exibir.');
  }

  const galleryGrid = document.querySelector('#galleryModal .gallery-grid');
  const modalEl = document.getElementById('galleryModal');
  if (modalEl && galleryGrid) {
    modalEl.addEventListener('shown.bs.modal', function () {
      galleryGrid.scrollTop = 0;
    });
  }
});
