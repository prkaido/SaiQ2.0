// index.js - comportamiento compartido para formularios de homologacion.
(function() {
  'use strict';

  function filtrarPlanes(programaSelect) {
    var planSelect = document.querySelector('select[name="pla"]');

    if (!programaSelect || !planSelect) {
      return;
    }

    var codigoPrograma = programaSelect.value || '0';
    var prefijo = codigoPrograma.substring(0, 3);

    planSelect.value = '0';

    Array.prototype.forEach.call(planSelect.options, function(option) {
      var planRef = option.getAttribute('data-ref');
      var visible = codigoPrograma === '0' || option.value === '0' || planRef === prefijo;

      option.hidden = !visible;
      option.disabled = !visible;
    });
  }

  function activarDropdowns() {
    var toggles = document.querySelectorAll('[data-toggle="dropdown"]');

    Array.prototype.forEach.call(toggles, function(toggle) {
      toggle.addEventListener('click', function(event) {
        var menu = toggle.parentNode.querySelector('.dropdown-menu');

        if (!menu) {
          return;
        }

        event.preventDefault();
        menu.classList.toggle('open');
      });
    });

    document.addEventListener('click', function(event) {
      Array.prototype.forEach.call(document.querySelectorAll('.dropdown-menu.open'), function(menu) {
        if (!menu.parentNode.contains(event.target)) {
          menu.classList.remove('open');
        }
      });
    });
  }

  function init() {
    var programaSelect = document.getElementById('prog_pca');

    if (programaSelect) {
      programaSelect.addEventListener('change', function() {
        filtrarPlanes(programaSelect);
      });

      filtrarPlanes(programaSelect);
    }

    activarDropdowns();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
