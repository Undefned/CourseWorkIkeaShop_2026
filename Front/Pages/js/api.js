// Shared helpers for the TSOGZ front end. Loaded before each page's own script.

// All pages live in Front/Pages/, the API lives in Backend/Api/.
// From /~s408229/CourseWorkIkeaShop_2026/Front/Pages/foo.html
//   ../../Backend/Api/<route>  →  /~s408229/CourseWorkIkeaShop_2026/Backend/Api/<route>
const API_BASE = '../../Backend/Api';

function apiUrl(path) {
  if (!path) return API_BASE;
  if (/^https?:\/\//i.test(path)) return path;
  const clean = path.startsWith('/') ? path.slice(1) : path;
  return `${API_BASE}/${clean}`;
}

async function apiGet(path) {
  const res = await fetch(apiUrl(path), { headers: { Accept: 'application/json' } });
  const json = await res.json().catch(() => null);
  if (!res.ok) {
    const message = json && json.error ? json.error.message : 'Ошибка запроса';
    throw new Error(message);
  }
  return json;
}

async function apiPost(path, body) {
  const res = await fetch(apiUrl(path), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(body),
  });
  const json = await res.json().catch(() => null);
  if (!res.ok) {
    const error = json && json.error ? json.error : { message: 'Ошибка запроса', fields: {} };
    const err = new Error(error.message);
    err.fields = error.fields || {};
    err.status = res.status;
    throw err;
  }
  return json;
}

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value == null ? '' : String(value);
  return div.innerHTML;
}

function formatPrice(price, currency) {
  const amount = Number(price);
  if (!Number.isFinite(amount)) return '';
  const formatted = new Intl.NumberFormat('ru-RU').format(amount);
  return `${formatted} ${currency === 'RUB' || !currency ? '₽' : currency}`;
}

/**
 * Wires a lead form (contacts.html or request.html) to POST /api/leads.
 * `extra()` returns any fields beyond name/phone/email/message — e.g. the
 * selected project_type chip and area on request.html.
 */
function wireLeadForm(form, formType, statusEl, extra) {
  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    statusEl.textContent = '';
    statusEl.className = 'form-status';

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    const payload = {
      form_type: formType,
      name: form.querySelector('#name')?.value || '',
      phone: form.querySelector('#phone')?.value || '',
      message: form.querySelector('#message')?.value || '',
      ...(extra ? extra() : {}),
    };

    try {
      const result = await apiPost('/leads.php', payload);
      statusEl.textContent = result.message || 'Заявка принята.';
      statusEl.className = 'form-status form-status--ok';
      form.reset();
    } catch (err) {
      const fieldMessages = err.fields ? Object.values(err.fields) : [];
      statusEl.textContent = fieldMessages[0] || err.message || 'Не удалось отправить заявку.';
      statusEl.className = 'form-status form-status--error';
    } finally {
      submitBtn.disabled = false;
    }
  });
}