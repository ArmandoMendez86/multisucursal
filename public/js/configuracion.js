// Archivo: /public/js/configuracion.js

document.addEventListener('DOMContentLoaded', function() {
    // --- Referencias a elementos del DOM ---
    const configForm = document.getElementById('config-form');
    const formFields = ['nombre', 'direccion', 'telefono', 'email', 'logo_url'];
    const saveButton = document.getElementById('save-config-btn');
    const adminMessage = document.getElementById('admin-only-message');

    // --- Lógica de la API ---

    /**
     * Obtiene la configuración actual de la sucursal y la muestra en el formulario.
     */
    async function fetchBranchConfig() {
        try {
            const response = await fetch(`${BASE_URL}/getBranchConfig`);
            const result = await response.json();

            if (result.success) {
                const configData = result.data;
                // Poblar el formulario con los datos recibidos
                formFields.forEach(field => {
                    const input = document.getElementById(field);
                    if (input) {
                        input.value = configData[field] || '';
                    }
                });
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudo cargar la configuración de la sucursal.', 'error');
        }
    }

    /**
     * Envía los datos del formulario a la API para actualizar la configuración.
     * @param {Event} event - El evento de submit del formulario.
     */
    async function handleFormSubmit(event) {
        event.preventDefault();
        const formData = new FormData(configForm);
        const configData = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(`${BASE_URL}/updateBranchConfig`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(configData)
            });

            const result = await response.json();

            if (result.success) {
                showToast('Configuración guardada exitosamente.', 'success');
                // Actualizar el nombre de la sucursal en la sidebar
                const sucursalNombreElem = document.getElementById('sucursal-nombre');
                if (sucursalNombreElem) {
                    sucursalNombreElem.textContent = configData.nombre;
                }
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudo conectar con el servidor para guardar los cambios.', 'error');
        }
    }
    
    /**
     * Habilita o deshabilita el formulario según el rol del usuario.
     */
    function checkUserRole() {
        // Esta función asume que dashboard.js ya ha guardado el rol en sessionStorage
        // o que podemos obtenerlo de alguna manera. Por simplicidad, lo haremos así.
        // Una mejor forma sería que checkSession en dashboard.js devuelva los datos.
        
        // Simulamos obtener el rol. En una app real, esto vendría de la sesión.
        const userRole = sessionStorage.getItem('user_role') || 'Vendedor'; // Valor por defecto

        if (userRole !== 'Administrador') {
            // Deshabilitar todos los campos del formulario y el botón
            formFields.forEach(field => {
                const input = document.getElementById(field);
                if(input) input.disabled = true;
            });
            if(saveButton) saveButton.disabled = true;
            if(adminMessage) adminMessage.classList.remove('hidden');
        }
    }
    
    // --- Asignación de Eventos ---
    configForm.addEventListener('submit', handleFormSubmit);

    // --- Carga Inicial ---
    fetchBranchConfig();
    // checkUserRole(); // Descomentar cuando la lógica de roles esté en dashboard.js
});
