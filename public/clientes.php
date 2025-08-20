<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .modal-overlay { background-color: rgba(0, 0, 0, 0.75); }
        .modal-body { max-height: 70vh; }
        ::-webkit-scrollbar { 
            width: 8px; 
            height: 8px; /* Añadido para el scroll horizontal */
        }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #4a5568; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #718096; }

        body {
            font-family: 'Inter', sans-serif;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.75);
        }

        .modal-body {
            max-height: 70vh;
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

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

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
                <h1 class="text-3xl font-bold text-white">Gestión de Clientes</h1>
                <button id="add-client-btn" class="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold py-2 px-4 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i> Añadir Cliente
                </button>
            </div>

            <div class="bg-[#1e293b] rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto mb-8 p-4">
                    <table id="clientesTable" class="min-w-full">
                        <thead class="bg-gray-800 text-xs text-gray-400 uppercase">
                            <tr>
                                <th class="py-3 px-6 text-left">Nombre</th>
                                <th class="py-3 px-6 text-left">Teléfono</th>
                                <th class="py-3 px-6 text-left">Email</th>
                                <th class="py-3 px-6 text-right">Deuda Actual</th>
                                <th class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="clients-table-body" class="divide-y divide-gray-700">
                            <tr>
                                <td colspan="5" class="text-center py-10 text-gray-500">Cargando clientes...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="client-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-4xl">
            <div class="p-6 border-b border-gray-700 flex justify-between items-center">
                <h2 id="modal-title" class="text-2xl font-bold text-white">Añadir Nuevo Cliente</h2>
                <button id="close-modal-btn" class="text-gray-400 hover:text-white">&times;</button>
            </div>
            <form id="client-form" class="p-6">
                <div class="modal-body overflow-y-auto pr-4">
                    <input type="hidden" id="client-id" name="id">

                    <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-600 pb-2">Datos Personales</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-300 mb-1">Nombre Completo</label>
                            <input type="text" id="nombre" name="nombre" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" required>
                        </div>
                        <div>
                            <label for="rfc" class="block text-sm font-medium text-gray-300 mb-1">RFC</label>
                            <input type="text" id="rfc" name="rfc" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                        </div>
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-300 mb-1">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                            <input type="email" id="email" name="email" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-600 pb-2">Direcciones</h3>
                    <div id="addresses-container" class="space-y-4 mb-4">
                        </div>
                    <button type="button" id="add-address-btn" class="text-sm bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i>Añadir Dirección
                    </button>

                    <h3 class="text-lg font-semibold text-white my-4 border-b border-gray-600 pb-2">Crédito</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" id="tiene_credito" name="tiene_credito" class="h-4 w-4 text-[#4f46e5] bg-gray-700 border-gray-600 rounded focus:ring-[#4f46e5]">
                                <span class="ml-2 text-sm text-gray-300">Habilitar línea de crédito</span>
                            </label>
                        </div>
                        <div id="limite-credito-container" class="hidden">
                            <label for="limite_credito" class="block text-sm font-medium text-gray-300 mb-1">Límite de Crédito ($)</label>
                            <input type="number" step="0.01" id="limite_credito" name="limite_credito" value="0.00" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-white mb-4 border-b border-gray-600 pb-2">Precios Especiales por Producto</h3>
                    <div class="mb-4">
                        <input type="text" id="product-search-input" placeholder="Buscar producto por nombre o SKU..." class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                    </div>
                    <div id="special-prices-container" class="space-y-3">
                        <p class="text-center text-gray-500">Cargando productos...</p>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-700 flex justify-end">
                    <button type="button" id="cancel-btn" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg mr-2">Cancelar</button>
                    <button type="submit" class="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold py-2 px-4 rounded-lg">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <div id="payment-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6 border-b border-gray-700 flex justify-between items-center">
                <h2 id="payment-modal-title" class="text-2xl font-bold text-white">Registrar Abono</h2>
                <button id="close-payment-modal-btn" class="text-gray-400 hover:text-white">&times;</button>
            </div>
            <form id="payment-form">
                <div class="p-6">
                    <input type="hidden" id="payment-client-id" name="id_cliente">
                    <div class="space-y-4">
                        <div>
                            <label for="payment-client-name" class="block text-sm font-medium text-gray-300 mb-1">Cliente</label>
                            <input type="text" id="payment-client-name" class="w-full bg-gray-800 text-white rounded-md p-2 border border-gray-600" readonly>
                        </div>
                        <div>
                            <label for="payment-client-debt" class="block text-sm font-medium text-gray-300 mb-1">Deuda Actual</label>
                            <input type="text" id="payment-client-debt" class="w-full bg-gray-800 text-white rounded-md p-2 border border-gray-600" readonly>
                        </div>
                        <div>
                            <label for="monto_abono" class="block text-sm font-medium text-gray-300 mb-1">Monto a Abonar</label>
                            <input type="number" step="0.01" id="monto_abono" name="monto" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" required>
                        </div>
                        <div>
                            <label for="metodo_pago_abono" class="block text-sm font-medium text-gray-300 mb-1">Método de Pago</label>
                            <select id="metodo_pago_abono" name="metodo_pago" class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" required>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Tarjeta">Tarjeta</option>
                                <option value="Transferencia">Transferencia</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-700 flex justify-end">
                    <button type="button" id="cancel-payment-btn" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg mr-2">Cancelar</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg">Registrar Abono</button>
                </div>
            </form>
        </div>
    </div>


    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    
    <!-- jQuery and DataTables + Buttons -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <script src="js/clientes.js"></script>
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