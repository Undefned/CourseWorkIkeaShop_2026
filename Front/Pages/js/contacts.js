(function () {
  const form = document.getElementById('contact-lead-form');
  const status = document.getElementById('contact-lead-status');
  if (form && status) {
    wireLeadForm(form, 'contact_short', status);
  }
})();
