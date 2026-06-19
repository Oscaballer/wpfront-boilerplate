(function () {
    'use strict';

    function mcInitDependentSelect(secSelectId, subSelectId) {
        var secSelect = document.getElementById(secSelectId);
        var subSelect = document.getElementById(subSelectId);

        if (!secSelect || !subSelect) return;

        function loadSubcats(seccionId) {
            if (!seccionId || seccionId === '0') {
                subSelect.innerHTML = '<option value="0">Sin subcategoría</option>';
                subSelect.disabled = false;
                return;
            }

            subSelect.innerHTML = '<option value="0">Cargando...</option>';
            subSelect.disabled = true;

            var body = new URLSearchParams({
                action: 'mc_get_subcats',
                seccion_id: String(seccionId),
                nonce: mcAdminNonce || ''
            });

            fetch(ajaxurl, { method: 'POST', body: body })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var currentVal = subSelect.getAttribute('data-selected') || '0';
                    subSelect.innerHTML = '<option value="0">Sin subcategoría</option>';
                    if (data.success && Array.isArray(data.data)) {
                        data.data.forEach(function (sub) {
                            var opt = document.createElement('option');
                            opt.value = sub.id;
                            opt.textContent = sub.nombre;
                            if (String(sub.id) === currentVal) {
                                opt.selected = true;
                            }
                            subSelect.appendChild(opt);
                        });
                    }
                    subSelect.disabled = false;
                })
                .catch(function () {
                    subSelect.innerHTML = '<option value="0">Sin subcategoría</option>';
                    subSelect.disabled = false;
                });
        }

        if (secSelect.value && secSelect.value !== '0') {
            loadSubcats(secSelect.value);
        }

        secSelect.addEventListener('change', function () {
            loadSubcats(this.value);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        mcInitDependentSelect('prod_id_seccion', 'prod_id_sub_categoria');
    });
})();