(function () {
  const slug = new URLSearchParams(location.search).get('slug');
  const root = document.getElementById('product-root');

  function notFound(message) {
    root.innerHTML = `
      <section class="page-intro">
        <div class="container">
          <h1>Товар не найден</h1>
          <p>${escapeHtml(message || 'Проверьте ссылку или вернитесь в каталог.')}</p>
          <p><a class="btn btn-outline-dark" href="showroom.html">В каталог</a></p>
        </div>
      </section>`;
  }

  function relatedCard(item) {
    return `
      <a class="product-card" href="product.html?slug=${encodeURIComponent(item.slug)}">
        <img src="${item.cover_url || 'https://placehold.co/429x360'}" alt="${escapeHtml(item.name)}">
        <div>
          <h3>${escapeHtml(item.name)}</h3>
          <p class="meta">Мебель · ${formatPrice(item.price)}</p>
        </div>
      </a>`;
  }

  function specRow(label, value) {
    if (!value) return '';
    return `<div class="spec-row"><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd></div>`;
  }

  function render(item) {
    document.title = `${item.name} — TSOGZ`;
    const images = item.images && item.images.length ? item.images : [{ url: 'https://placehold.co/760x640', alt: item.name }];

    const thumbs = images
      .map(
        (img, i) => `
      <button type="button" data-full="${img.url}" aria-current="${i === 0 ? 'true' : 'false'}">
        <img src="${img.url}" alt="${escapeHtml(img.alt || item.name)}">
      </button>`
      )
      .join('');

    root.innerHTML = `
      <div class="page-hero" style="padding-bottom: 32px;">
        <nav class="breadcrumbs" aria-label="Хлебные крошки">
          <a href="index.html">Главная</a>
          <span aria-hidden="true">/</span>
          <a href="showroom.html">Шоурум</a>
          <span aria-hidden="true">/</span>
          <span class="current" aria-current="page">${escapeHtml(item.name)}</span>
        </nav>
      </div>

      <main>
        <section class="product-detail">
          <div class="product-gallery">
            <img class="main-image" src="${images[0].url}" alt="${escapeHtml(item.name)}, общий вид">
            <div class="thumb-row" role="group" aria-label="Дополнительные фото">${thumbs}</div>
          </div>

          <div class="product-info">
            <span class="eyebrow">${escapeHtml(item.category ? item.category.name : '')}</span>
            <h1>${escapeHtml(item.name)}</h1>
            <p class="product-price">${formatPrice(item.price, item.currency)}</p>
            <p class="description">${escapeHtml(item.short_description || '')}</p>

            <dl class="spec-list">
              ${specRow('Артикул', item.sku)}
              ${specRow('Категория', item.category ? item.category.name : '')}
              ${specRow('Материал', item.material)}
              ${specRow('Размер', item.dimensions)}
              ${specRow('Наличие', item.availability)}
              ${specRow('Срок', item.lead_time)}
            </dl>

            <div class="btn-row">
              <a href="request.html#request-form" class="btn btn-fill">Запросить в шоуруме</a>
              <a href="contacts.html#contact-form" class="btn btn-outline">Консультация</a>
            </div>

            <p class="product-note">Доставим по городу или заберете из шоурума. Для клиентов студии специальные условия комплектации.</p>
          </div>
        </section>

        ${
          item.long_description
            ? `<section class="info-band info-band--soft">
                 <h2>О товаре</h2>
                 <p>${escapeHtml(item.long_description)}</p>
               </section>`
            : ''
        }

        <section class="related-products">
          <h2>Похожие товары</h2>
          <div class="product-grid">
            ${(item.related || []).map(relatedCard).join('') || '<p class="muted">Похожих товаров пока нет.</p>'}
          </div>
        </section>
      </main>`;

    root.querySelectorAll('.thumb-row button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        root.querySelectorAll('.thumb-row button').forEach((b) => b.setAttribute('aria-current', 'false'));
        btn.setAttribute('aria-current', 'true');
        root.querySelector('.main-image').src = btn.dataset.full;
      });
    });
  }

  if (!slug) {
    notFound('Не указан товар.');
  } else {
    apiGet(`/api/products/${encodeURIComponent(slug)}`)
      .then(({ data }) => render(data))
      .catch((err) => notFound(err.message));
  }
})();
