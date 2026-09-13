(function () {
  const LIMIT = 6;
  const grid = document.querySelector('.product-section .product-grid');
  const loadMoreWrap = document.querySelector('.load-more');
  const loadMoreBtn = loadMoreWrap ? loadMoreWrap.querySelector('button') : null;
  const chips = document.querySelectorAll('.filter-row .filter-chip');
  const featuredImg = document.querySelector('.featured-collection img');

  const state = { category: '', page: 1 };

  function cardHtml(item) {
    return `
      <a class="product-card" href="product.html?slug=${encodeURIComponent(item.slug)}">
        <img src="${item.cover_url || 'https://placehold.co/432x360'}" alt="${escapeHtml(item.name)}">
        <div>
          <h3>${escapeHtml(item.name)}</h3>
          <p class="meta">${escapeHtml(item.category ? item.category.name : '')} · ${formatPrice(item.price, item.currency)}</p>
        </div>
      </a>`;
  }

  async function loadPage(reset) {
    if (reset) {
      state.page = 1;
      grid.innerHTML = '';
    }
    const url = `/products.php?category=${encodeURIComponent(state.category)}&page=${state.page}&limit=${LIMIT}`;
    const { data, meta } = await apiGet(url);
    grid.insertAdjacentHTML('beforeend', data.map(cardHtml).join(''));
    if (loadMoreWrap) {
      loadMoreWrap.style.display = state.page < meta.pages ? '' : 'none';
    }
  }

  chips.forEach((chip) => {
    chip.addEventListener('click', function () {
      chips.forEach((c) => {
        c.setAttribute('aria-pressed', 'false');
        c.setAttribute('aria-checked', 'false');
      });
      chip.setAttribute('aria-pressed', 'true');
      chip.setAttribute('aria-checked', 'true');
      state.category = chip.dataset.category || '';
      loadPage(true).catch(() => {
        grid.innerHTML = '<p class="muted">Не удалось загрузить каталог.</p>';
      });
    });
  });

  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function () {
      state.page += 1;
      loadPage(false).catch(() => {
        state.page -= 1;
      });
    });
  }

  if (featuredImg) {
    apiGet('/products.php?collection=alva&limit=1')
      .then(({ data }) => {
        if (data[0] && data[0].cover_url) {
          featuredImg.src = data[0].cover_url;
        }
      })
      .catch(() => {});
  }

  loadPage(true).catch(() => {
    grid.innerHTML = '<p class="muted">Не удалось загрузить каталог.</p>';
  });
})();