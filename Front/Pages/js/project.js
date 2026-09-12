(function () {
  const slug = new URLSearchParams(location.search).get('slug');
  const root = document.getElementById('project-root');

  function notFound(message) {
    root.innerHTML = `
      <section class="page-intro">
        <div class="container">
          <h1>Проект не найден</h1>
          <p>${escapeHtml(message || 'Проверьте ссылку или вернитесь в портфолио.')}</p>
          <p><a class="btn btn-outline-dark" href="portfolio.html">К портфолио</a></p>
        </div>
      </section>`;
  }

  function metaLine(item) {
    return [item.type ? item.type.name : '', item.area_m2 ? item.area_m2 + ' м²' : ''].filter(Boolean).join(' · ');
  }

  function relatedCard(item) {
    return `
      <a class="related-card" href="project.html?slug=${encodeURIComponent(item.slug)}">
        <div class="thumb"><img src="${item.cover_url || 'https://placehold.co/432x300'}" alt="${escapeHtml(item.title)}"></div>
        <div class="info">
          <h4>${escapeHtml(item.title)}</h4>
          <span class="meta">${escapeHtml((item.area_m2 ? item.area_m2 + ' м²' : ''))}</span>
        </div>
      </a>`;
  }

  function render(item) {
    document.title = `${item.title} — TSOGZ`;

    const gallery = item.images || [];
    const heroImg = gallery[0] ? gallery[0].url : 'https://placehold.co/740x520';
    const secondImg = gallery[1] ? gallery[1].url : 'https://placehold.co/380x300';
    const bigShot = gallery[2] ? gallery[2].url : 'https://placehold.co/1360x560';
    const pair = [gallery[3], gallery[4]];

    root.innerHTML = `
      <section class="project-hero">
        <div class="project-hero-inner">
          <div class="breadcrumb">
            <a href="index.html" class="dim">Главная</a>
            <span class="dim">/</span>
            <a href="portfolio.html" class="dim">Портфолио</a>
            <span class="dim">/</span>
            <span>${escapeHtml(item.title)}</span>
          </div>
          <h1>${escapeHtml(item.title)}</h1>
          <p>${escapeHtml(item.intro || '')}</p>
        </div>
      </section>

      <section class="meta-bar container">
        <div class="meta-item"><span class="k">ТИП</span><span class="v">${escapeHtml(item.type ? item.type.name : '—')}</span></div>
        <div class="meta-item"><span class="k">ПЛОЩАДЬ</span><span class="v">${escapeHtml(item.area_m2 ? item.area_m2 + ' м²' : '—')}</span></div>
        <div class="meta-item"><span class="k">СТИЛЬ</span><span class="v">${escapeHtml(item.style || '—')}</span></div>
        <div class="meta-item"><span class="k">ГОД</span><span class="v">${escapeHtml(item.year || '—')}</span></div>
        <div class="meta-item"><span class="k">СРОК</span><span class="v">${escapeHtml(item.duration_label || '—')}</span></div>
      </section>

      <section class="about-project">
        <div class="about-wrap">
          <div class="about-img-main"><img src="${heroImg}" alt="${escapeHtml(item.title)} — интерьер"></div>
          <div class="about-img-secondary"><img src="${secondImg}" alt="${escapeHtml(item.title)} — деталь"></div>
          <div class="about-card">
            <span class="eyebrow">О проекте</span>
            <h3>${escapeHtml(item.about_title || '')}</h3>
            <p>${escapeHtml(item.about_body || '')}</p>
          </div>
        </div>
      </section>

      <section class="gallery-section container">
        <div class="gallery-heading"><h2>Кадры проекта</h2></div>
        <div class="gallery-hero-img"><img src="${bigShot}" alt="Кадр проекта"></div>
        <div class="gallery-pair">
          <div><img src="${pair[0] ? pair[0].url : 'https://placehold.co/664x440'}" alt="Кадр проекта"></div>
          <div><img src="${pair[1] ? pair[1].url : 'https://placehold.co/664x440'}" alt="Кадр проекта"></div>
        </div>
      </section>

      <section class="related">
        <div class="container">
          <div class="related-heading"><h2>Смотрите также</h2></div>
          <div class="related-grid">
            ${(item.related || []).map(relatedCard).join('') || '<p class="muted">Похожих проектов пока нет.</p>'}
          </div>
        </div>
      </section>

      <section class="cta">
        <div class="container cta-inner">
          <h2>Начните с бесплатного замера</h2>
          <p>Оставим заявку и подготовим ориентир по стоимости проекта</p>
          <div class="cta-buttons">
            <a href="request.html#request-form" class="btn btn-fill">Записаться на замер</a>
            <a href="contacts.html#contact-form" class="btn btn-outline-light">Консультация</a>
          </div>
        </div>
      </section>`;
  }

  if (!slug) {
    notFound('Не указан проект.');
  } else {
    apiGet(`/api/projects/${encodeURIComponent(slug)}`)
      .then(({ data }) => render(data))
      .catch((err) => notFound(err.message));
  }
})();
