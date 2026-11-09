document.addEventListener('DOMContentLoaded', () => {
    // 1. Capturamos los controles
    const selectTipoSala = document.getElementById('filtro-tipo-sala');
    const selectTipoModulo = document.getElementById('filtro-tipo-modulo');
    const inputCapacidad = document.getElementById('buscador-capacidad');
    const checkLibres = document.getElementById('check-solo-libres');

    // 2. Capturamos todas las salas
    const salas = document.querySelectorAll('.sala');

    // 3. Función principal de filtrado
    function filtrar() {
        const valTipoSala = selectTipoSala ? selectTipoSala.value.toUpperCase() : '';
        const valTipoModulo = selectTipoModulo ? selectTipoModulo.value.toUpperCase() : '';
        const valCapacidad = inputCapacidad && inputCapacidad.value !== '' ? parseInt(inputCapacidad.value, 10) : 0;
        const valSoloLibres = checkLibres ? checkLibres.checked : false;

        salas.forEach(sala => {
            const salaTipo = (sala.getAttribute('data-tipo-sala') || '').toUpperCase();
            let modulosVisibles = 0;

            const modulos = sala.querySelectorAll('.modulo');

            modulos.forEach(modulo => {
                const modTipo = (modulo.getAttribute('data-tipo') || '').toUpperCase();
                const modCapacidad = parseInt(modulo.getAttribute('data-capacidad') || '0', 10);
                const modEstado = (modulo.getAttribute('data-estado') || '').toLowerCase();

                let mostrar = true;

                // Regla 1: Tipo de sala
                if (valTipoSala !== '' && salaTipo !== valTipoSala) {
                    mostrar = false;
                }

                // Regla 2: Tipo de módulo
                if (valTipoModulo !== '' && modTipo !== valTipoModulo) {
                    mostrar = false;
                }

                // Regla 3: Capacidad mínima requerida
                if (valCapacidad > 0 && modCapacidad < valCapacidad) {
                    mostrar = false;
                }

                // Regla 4: Solo libres
                if (valSoloLibres && modEstado !== 'libre') {
                    mostrar = false;
                }

                // Aplicar visibilidad al módulo
                if (mostrar) {
                    modulo.style.display = '';
                    modulosVisibles++;
                } else {
                    modulo.style.display = 'none';
                }
            });

            // Si la sala no tiene módulos visibles, ocultamos la sala entera
            if (modulosVisibles > 0) {
                sala.style.display = '';
            } else {
                sala.style.display = 'none';
            }
        });
    }

    // 4. Conectamos los eventos
    if (selectTipoSala) selectTipoSala.addEventListener('change', filtrar);
    if (selectTipoModulo) selectTipoModulo.addEventListener('change', filtrar);
    if (inputCapacidad) inputCapacidad.addEventListener('input', filtrar);
    if (checkLibres) checkLibres.addEventListener('change', filtrar);
});
// --- Refresco automático del estado de las salas ---
setInterval(() => {
    // Evitamos recargar si el gamemaster está escribiendo activamente en un input o select
    const inputActivo = document.activeElement;
    if (inputActivo && (inputActivo.tagName === 'INPUT' || inputActivo.tagName === 'SELECT')) {
        return; 
    }

    // Refresca la página cada 45 segundos para mantener el estado real sincronizado
    location.reload();
}, 45000);