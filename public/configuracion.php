<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 8px;
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

        <!-- Contenido Principal -->
        <main class="flex-1 p-8 overflow-y-auto">
            <!-- MODIFICADO: Encabezado ahora se oculta en el breakpoint 'lg' -->
            <header class="lg:hidden flex items-center justify-between bg-[#1e293b] p-4 shadow-md flex-shrink-0">
                <button id="mobile-menu-button" class="text-white focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-lg font-bold text-white">Punto de Venta</h1>
                <div class="w-8"></div>
            </header>
            <h1 class="text-3xl font-bold text-white mb-8">Configuración de la Sucursal</h1>

            <div class="bg-[#1e293b] p-6 rounded-lg max-w-2xl mx-auto">
                <div id="admin-only-message" class="hidden bg-yellow-900 border border-yellow-700 text-yellow-300 px-4 py-3 rounded-lg mb-6">
                    <p><i class="fas fa-exclamation-triangle mr-2"></i>Esta sección solo puede ser editada por un Administrador.</p>
                </div>
                <form id="config-form" class="space-y-6">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-300 mb-1">Nombre de la Sucursal</label>
                        <input type="text" id="nombre" name="nombre" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" required>
                    </div>
                    <div>
                        <label for="direccion" class="block text-sm font-medium text-gray-300 mb-1">Dirección</label>
                        <textarea id="direccion" name="direccion" rows="3" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-300 mb-1">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                            <input type="email" id="email" name="email" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                        </div>
                    </div>
                    <div>
                        <label for="logo_url" class="block text-sm font-medium text-gray-300 mb-1">URL del Logo</label>
                        <input type="text" id="logo_url" name="logo_url" placeholder="https://ejemplo.com/logo.png" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                    </div>
                    <div class="pt-4 flex justify-end">
                        <button type="submit" id="save-config-btn" class="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold py-2 px-6 rounded-lg">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/configuracion.js"></script>
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