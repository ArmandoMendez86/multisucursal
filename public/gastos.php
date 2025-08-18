<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.75);
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px; /* Añadido para el scroll horizontal */
        }
        ::-webkit-scrollbar-track {
            background: #1e293b;
        }
        ::-webkit-scrollbar-thumb {
            background: #4a5568;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #718096;
        }
    </style>
</head>

<body class="bg-[#0f172a] text-gray-300">

    <div class="flex h-screen">
       
        <?php include_once '../parciales/navegacion.php'; ?>
      
        <main class="flex-1 p-8 overflow-y-auto">
            <!-- MODIFICADO: Encabezado ahora se oculta en el breakpoint 'lg' -->
            <header class="lg:hidden flex items-center justify-between bg-[#1e293b] p-4 shadow-md flex-shrink-0">
                <button id="mobile-menu-button" class="text-white focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-lg font-bold text-white">Punto de Venta</h1>
                <div class="w-8"></div>
            </header>
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-white">Gestión de Gastos</h1>
                <button id="add-expense-btn" class="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold py-2 px-4 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i> Añadir Gasto
                </button>
            </div>

            <div class="bg-[#1e293b] rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-800 text-xs text-gray-400 uppercase">
                            <tr>
                                <th class="py-3 px-6 text-left">Fecha</th>
                                <th class="py-3 px-6 text-left">Categoría</th>
                                <th class="py-3 px-6 text-left">Descripción</th>
                                <th class="py-3 px-6 text-right">Monto</th>
                                <th class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="expenses-table-body" class="divide-y divide-gray-700">
                            <tr>
                                <td colspan="5" class="text-center py-10 text-gray-500">Cargando gastos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="expense-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-lg">
            <div class="p-6 border-b border-gray-700 flex justify-between items-center">
                <h2 id="modal-title" class="text-2xl font-bold text-white">Registrar Nuevo Gasto</h2>
                <button id="close-modal-btn" class="text-gray-400 hover:text-white">&times;</button>
            </div>
            <form id="expense-form" class="p-6 space-y-4">
                <input type="hidden" id="expense-id" name="id">
                <div>
                    <label for="categoria_gasto" class="block text-sm font-medium text-gray-300 mb-1">Categoría</label>
                    <input type="text" id="categoria_gasto" name="categoria_gasto" placeholder="Ej: Renta, Servicios, Proveedores" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" required>
                </div>
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-300 mb-1">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" required></textarea>
                </div>
                <div>
                    <label for="monto" class="block text-sm font-medium text-gray-300 mb-1">Monto ($)</label>
                    <input type="number" step="0.01" id="monto" name="monto" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" required>
                </div>
                <div class="pt-4 flex justify-end">
                    <button type="button" id="cancel-btn" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg mr-2">Cancelar</button>
                    <button type="submit" class="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold py-2 px-4 rounded-lg">Guardar Gasto</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    <script src="js/gastos.js"></script>
     <script>
    document.addEventListener('DOMContentLoaded', function() {
      const mobileMenuButton = document.getElementById('mobile-menu-button');
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebar-overlay');

      if (mobileMenuButton && sidebar && overlay) {
        mobileMenuButton.addEventListener('click', (e) => {
          e.stopPropagation();
          sidebar.classList.remove('-translate-x-full');
          overlay.classList.remove('hidden');
        });

        overlay.addEventListener('click', () => {
          sidebar.classList.add('-translate-x-full');
          overlay.classList.add('hidden');
        });
      }
    });
  </script>
</body>
</html>