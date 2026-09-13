(function () {
  const LIMIT = 6;
  const listEl = document.getElementById('project-list');
  const featuredSlot = document.getElementById('featured-project-slot');
  const showMoreWrap = document.querySelector('.show-more');
  const showMoreBtn = document.getElementById('show-more-btn');
  const filterButtons = document.querySelectorAll('.filter-btn');

  const state = { type: '', page: 1, total: 0 };

  function projectMeta(item) {
    const typeName = item.type ? item.type.name : '';
    const area = item.area_m2 ? `${item.area_m2} м²` : '';
    return [typeName, area].filter(Boolean).join(' · ');
  }

  function tileHtml(item, tall) {
    const img = item.cover_url || 'https://placehold.co/664x420';
    return `
      <a class="project-tile${tall ? ' tall' : ''}" href="project.html?slug=${encodeURIComponent(item.slug)}">
        <div class="thumb"><img src="${img}" alt="${escapeHtml(item.title)}"></div>
        <div class="label">
          <h4>${escapeHtml(item.title)}</h4>
          <span class="meta">${escapeHtml(projectMeta(item))}</span>
        </div>
      </a>`;
  }

  function renderRows(items, append) {
    const rowsHtml = [];
    for (let i = 0; i < items.length; i += 2) {
      const pair = items.slice(i, i + 2);
      rowsHtml.push(`<div class="project-row">${pair.map((p) => tileHtml(p, false)).join('')}</div>`);
    }
    listEl.insertAdjacentHTML(append ? 'beforeend' : 'afterbegin', rowsHtml.join(''));
  }

  async function loadFeatured() {
    try {
      const { data } = await apiGet('/projects.php?featured=1&limit=1');
      const item = data[0];
      if (!item) return;
      featuredSlot.innerHTML = `
        <a class="featured-project" href="project.html?slug=${encodeURIComponent(item.slug)}">
          <img src="${item.cover_url || 'https://placehold.co/980x520'}" alt="${escapeHtml(item.title)}">
          <div class="featured-card">
            <span class="eyebrow">Избранный проект</span>
            <h3>${escapeHtml(item.title)}</h3>
            <span class="meta">${escapeHtml(projectMeta(item))}${item.style ? ' · ' + escapeHtml(item.style) : ''}</span>
            <p class="desc">${escapeHtml(item.intro || '')}</p>
            <span class="btn btn-outline-dark">Смотреть проект</span>
          </div>
        </a>`;
    } catch (e) {
      // Featured block is a nice-to-have — fail silently, list below still works.
    }
  }

  async function loadPage(reset) {
    if (reset) {
      state.page = 1;
      listEl.innerHTML = '';
    }
    const url = `/projects.php?type=${encodeURIComponent(state.type)}&page=${state.page}&limit=${LIMIT}`;
    const { data, meta } = await apiGet(url);
    state.total = meta.total;
    renderRows(data, !reset);
    showMoreWrap.style.display = state.page < meta.pages ? '' : 'none';
  }

  filterButtons.forEach((btn) => {
    btn.addEventListener('click', function () {
      filterButtons.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      state.type = btn.dataset.type || '';
      loadPage(true).catch(() => {
        listEl.innerHTML = '<p class="muted">Не удалось загрузить проекты.</p>';
      });
    });
  });

  showMoreBtn.addEventListener('click', function () {
    state.page += 1;
    loadPage(false).catch(() => {
      state.page -= 1;
    });
  });

  loadFeatured();
  loadPage(true).catch(() => {
    listEl.innerHTML = '<p class="muted">Не удалось загрузить проекты.</p>';
  });
})();