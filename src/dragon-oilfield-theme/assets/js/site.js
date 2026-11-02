/* Native WordPress navigation handles the mobile menu. This script enables smooth section focus after in-page navigation. */
document.addEventListener('click', function (event) {
  var link = event.target.closest('a[href^="#"]');
  if (!link) return;
  var id = link.getAttribute('href').slice(1);
  if (!id) return;
  var target = document.getElementById(id);
  if (target) {
    window.setTimeout(function () { target.setAttribute('tabindex', '-1'); target.focus({ preventScroll: true }); }, 350);
  }
});
