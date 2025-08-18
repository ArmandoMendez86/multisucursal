<!-- Archivo: /public/parciales/admin_usuarios.php -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-white">Gestión de Usuarios</h2>
    <button id="add-usuario-btn" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-4 rounded-lg flex items-center">
        <i class="fas fa-user-plus mr-2"></i> Nuevo Usuario
    </button>
</div>
<div class="bg-[#1e293b] rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-700">
            <thead class="bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Sucursal</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody id="usuarios-table-body" class="divide-y divide-gray-700"></tbody>
        </table>
    </div>
</div>

<!-- Modal para Usuarios -->
<div id="usuario-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <!-- Contenido del modal (formulario para crear/editar usuario) -->
</div>
