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

document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.querySelector('.gallery-more-overlay');
  if (overlay) {
    overlay.addEventListener('click', openGalleryModal);
  }
});
