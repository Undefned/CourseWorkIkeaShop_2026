(function () {
  const chipRow = document.querySelector('.chip-row');
  const form = document.getElementById('request-lead-form');
  const status = document.getElementById('request-lead-status');

  // Single-select behaviour for the "Тип объекта" chip group.
  if (chipRow) {
    chipRow.addEventListener('click', function (e) {
      const chip = e.target.closest('.chip');
      if (!chip) return;
      chipRow.querySelectorAll('.chip').forEach(function (c) {
        c.setAttribute('aria-pressed', 'false');
        c.setAttribute('aria-checked', 'false');
      });
      chip.setAttribute('aria-pressed', 'true');
      chip.setAttribute('aria-checked', 'true');
    });
  }

  if (form && status) {
    wireLeadForm(form, 'measurement_request', status, function () {
      const selectedChip = chipRow ? chipRow.querySelector('.chip[aria-pressed="true"]') : null;
      return {
        email: form.querySelector('#email')?.value || '',
        project_type: selectedChip ? selectedChip.dataset.type || '' : '',
        area: form.querySelector('#area')?.value || '',
      };
    });
  }
})();
