/* Progressive enhancement: native POST remains available without JavaScript. */
(function () {
  'use strict';
  function setup() {
    document.querySelectorAll('.dragon-lead-form').forEach(function (form) {
      if (form.dataset.dragonReady || !window.fetch || !window.FormData || !window.AbortController) return;
      form.dataset.dragonReady = '1';
      var button = form.querySelector('[type="submit"]');
      var status = form.querySelector('.dragon-inline-status');
      if (!button || !status) return;
      var name = form.elements.namedItem('dragon_name');
      var phone = form.elements.namedItem('dragon_phone');
      var email = form.elements.namedItem('dragon_email');
      var message = form.elements.namedItem('dragon_message');
      var busy = false;
      form.noValidate = true;
      function show(text, success, focus) {
        status.hidden = false;
        status.textContent = text;
        status.classList.toggle('success', !!success);
        status.setAttribute('role', success ? 'status' : 'alert');
        if (focus) {
          status.focus({preventScroll: true});
          status.scrollIntoView({block: 'nearest'});
        }
      }
      form.addEventListener('input', function () {
        [name, phone, email, message].forEach(function (field) { field.setCustomValidity(''); });
      });
      form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (busy) return;
        [name, phone, email, message].forEach(function (field) { field.setCustomValidity(''); });
        if (name.value.trim().length < 2) name.setCustomValidity('Please enter your name (at least two characters).');
        if (!message.value.trim()) message.setCustomValidity('Please enter your enquiry.');
        var number = phone.value.trim();
        var digits = number.replace(/\D/g, '').length;
        if (number && (!/^\+?[0-9(). \-]+$/.test(number) || digits < 7 || digits > 15)) phone.setCustomValidity('Please enter a valid phone number, or leave it blank and provide email.');
        if (!number && !email.value.trim()) phone.setCustomValidity('Please provide a phone number or email address.');
        if (!form.checkValidity()) {
          var invalid = form.querySelector(':invalid');
          show(invalid ? invalid.validationMessage : 'Please check the highlighted fields.', false, false);
          form.reportValidity();
          return;
        }
        busy = true;
        button.disabled = true;
        button.textContent = 'Sending…';
        form.setAttribute('aria-busy', 'true');
        show('Sending your enquiry…', false, false);
        var controller = new AbortController();
        var timer = window.setTimeout(function () { controller.abort(); }, 30000);
        try {
          var data = new FormData(form);
          data.set('dragon_ajax', '1');
          // The hidden input named "action" shadows form.action in some browsers.
          var response = await fetch(form.getAttribute('action'), {
            method: 'POST', body: data, credentials: 'same-origin',
            signal: controller.signal, redirect: 'error', headers: {'Accept': 'application/json'}
          });
          var result = await response.json();
          if (!result || !['success', 'error'].includes(result.result) || typeof result.message !== 'string' ||
              (result.result === 'success' && !response.ok)) throw new Error('Unexpected response');
          if (result.result === 'success') form.reset();
          show(result.message, result.result === 'success', true);
        } catch (error) {
          show('We could not confirm whether your enquiry was received. Your entries are still here. Please contact billy@dragonoilfield.com or call 403-660-3499 rather than sending repeatedly.', false, true);
        } finally {
          window.clearTimeout(timer);
          busy = false;
          button.disabled = false;
          button.textContent = 'Send Enquiry';
          form.removeAttribute('aria-busy');
        }
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup);
  else setup();
}());
