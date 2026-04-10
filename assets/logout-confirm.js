(function () {
  if (window.__logoutConfirmInitialized) {
    return;
  }
  window.__logoutConfirmInitialized = true;

  function buildDialog(targetHref) {
    var overlay = document.createElement('div');
    overlay.className = 'dialog-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', 'logoutDialogTitle');
    overlay.hidden = true;
    overlay.style.display = 'none';

    overlay.innerHTML = '' +
      '<div class="dialog-card dialog-card-sm">' +
      '  <div class="dialog-head">' +
      '    <h2 id="logoutDialogTitle" class="dialog-title">Confirm Logout</h2>' +
      '    <button type="button" class="dialog-close" aria-label="Close dialog">x</button>' +
      '  </div>' +
      '  <p class="helper-text">Are you sure you want to log out?</p>' +
      '  <div class="dialog-footer">' +
      '    <button type="button" class="btn-secondary" data-close-logout>Cancel</button>' +
      '    <a class="btn-danger" data-confirm-logout href="' + targetHref + '">Logout</a>' +
      '  </div>' +
      '</div>';

    return overlay;
  }

  function init() {
    var logoutLinks = document.querySelectorAll('a.js-logout-link');
    if (!logoutLinks.length) {
      return;
    }

    var activeHref = logoutLinks[0].getAttribute('href') || 'logout.php';
    var overlay = buildDialog(activeHref);
    document.body.appendChild(overlay);

    var closeButton = overlay.querySelector('[data-close-logout]');
    var xButton = overlay.querySelector('.dialog-close');
    var confirmButton = overlay.querySelector('[data-confirm-logout]');

    function openDialog(href) {
      if (confirmButton) {
        confirmButton.setAttribute('href', href);
      }
      overlay.hidden = false;
      overlay.style.display = 'grid';
      overlay.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
    }

    function closeDialog() {
      overlay.hidden = true;
      overlay.style.display = 'none';
      overlay.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
    }

    logoutLinks.forEach(function (link) {
      link.addEventListener('click', function (event) {
        event.preventDefault();
        var href = link.getAttribute('href') || activeHref;
        openDialog(href);
      });
    });

    if (closeButton) {
      closeButton.addEventListener('click', function (event) {
        event.preventDefault();
        closeDialog();
      });
    }

    if (xButton) {
      xButton.addEventListener('click', function (event) {
        event.preventDefault();
        closeDialog();
      });
    }

    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) {
        closeDialog();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !overlay.hidden) {
        closeDialog();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
    return;
  }

  init();
})();
