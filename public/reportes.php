<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema POS</title>
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

        /* Estilo para el contenedor de la tabla con scroll */
        .table-scroll-container {
            max-height: 400px;
            /* Altura máxima para el scroll, ajusta según necesidad */
            overflow-y: auto;
            /* Habilita el scroll vertical */
            border-radius: 0.5rem;
            /* Bordes redondeados */
            background-color: #1e293b;
            /* Fondo similar al de la tarjeta */
        }

        /* Estilo para el Ticket ID */
        .ticket-id-cell {
            color: #818cf8;
            /* Un color azul claro para hacerlo intuitivo */
            font-weight: 600;
            /* Un poco más de énfasis */
        }
    </style>
    <!-- DataTables + Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

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
            <h1 class="text-3xl font-bold text-white mb-8">Reportes y Análisis</h1>

            <!-- Sección de Reporte de Ventas -->
            <div class="bg-[#1e293b] p-6 rounded-lg mb-8">
                <h2 class="text-xl font-semibold text-white mb-4">Reporte de Ventas</h2>
                <!-- Filtros -->
                <div class="flex flex-wrap items-end gap-4 mb-6" style="display: none !important;">
                    <div>
                        <label for="start-date" class="block text-sm font-medium text-gray-300 mb-1">Fecha de
                            Inicio</label>
                        <input type="date" id="start-date"
                            class="bg-gray-700 text-white rounded-md p-2 border border-gray-600">
                    </div>
                    <div>
                        <label for="end-date" class="block text-sm font-medium text-gray-300 mb-1">Fecha de Fin</label>
                        <input type="date" id="end-date"
                            class="bg-gray-700 text-white rounded-md p-2 border border-gray-600">
                    </div>
                    <button id="generate-report-btn"
                        class="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold py-2 px-4 rounded-lg">Generar
                        Reporte</button>
                    <button id="export-csv-btn"
                        class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                        <i class="fas fa-file-csv mr-2"></i> Exportar a CSV
                    </button>
                </div>

                <!-- Tabla de Reporte -->

                <div class="bg-[#1e293b] rounded-lg shadow overflow-auto mb-8 p-4">
                    <table id="tablaVentas" class="min-w-full">
                        <thead class="bg-gray-800 text-xs text-gray-400 uppercase sticky top-0">
                            <tr>
                                <th class="py-3 px-6 text-left">Fecha</th>
                                <th class="py-3 px-6 text-left">Ticket ID</th>
                                <th class="py-3 px-6 text-left">Cliente</th>
                                <th class="py-3 px-6 text-left">Vendedor</th>
                                <th class="py-3 px-6 text-right">Total</th>
                                <th class="py-3 px-6 text-left">Estado</th>
                                <th class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="report-table-body" class="divide-y divide-gray-700 text-xs">
                            <!--   <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">Seleccione un rango de fechas y
                                genere un reporte.</td>
                        </tr> -->
                            <!-- Ejemplo de fila con Ticket ID de color. En tu JS, cuando generes las filas,
                                 asegúrate de añadir la clase 'ticket-id-cell' a la celda del Ticket ID. -->
                            <!-- <tr>
                                <td class="py-3 px-6 text-left">2024-07-24</td>
                                <td class="py-3 px-6 text-left ticket-id-cell">TICKET-00123</td>
                                <td class="py-3 px-6 text-left">Cliente Ejemplo</td>
                                <td class="py-3 px-6 text-left">Vendedor A</td>
                                <td class="py-3 px-6 text-right">$150.00</td>
                                <td class="py-3 px-6 text-left">Completada</td>
                                <td class="py-3 px-6 text-center">
                                    <button class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded-lg text-xs">
                                        Cancelar
                                    </button>
                                </td>
                            </tr> -->
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Sección de Corte de Caja -->
            <div class="bg-[#1e293b] p-6 rounded-lg">
                <h2 class="text-xl font-semibold text-white mb-4">Corte de Caja Diaria</h2>
                <!-- Filtros para el Corte de Caja -->
                <div class="flex flex-wrap items-end gap-4 mb-6">
                    <div>
                        <label for="cash-cut-date" class="block text-sm font-medium text-gray-300 mb-1">Fecha del
                            Corte</label>
                        <input type="date" id="cash-cut-date"
                            class="bg-gray-700 text-white rounded-md p-2 border border-gray-600">
                    </div>
                    <div>
                        <label for="initial-cash" class="block text-sm font-medium text-gray-300 mb-1">Caja
                            Inicial</label>
                        <input type="number" id="initial-cash" value="0.00" step="0.01"
                            class="bg-gray-700 text-white rounded-md p-2 border border-gray-600 w-32">
                    </div>
                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador'): ?>
                        <div>
                            <label for="user-filter-select" class="block text-sm font-medium text-gray-300 mb-1">Filtrar por
                                Usuario</label>
                            <select id="user-filter-select"
                                class="bg-gray-700 text-white rounded-md p-2 border border-gray-600">
                                <option value="all">General (Toda la Sucursal)</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <button id="generate-cash-cut-btn"
                        class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg">Generar Corte de
                        Caja</button>
                    <button id="print-cash-cut-btn"
                        class="bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                        <i class="fas fa-print mr-2"></i> Imprimir Corte
                    </button>
                </div>
                <!-- Aquí irán los resultados del corte -->
                <div id="cash-cut-results" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <p class="text-gray-400 col-span-full">Seleccione una fecha para generar el corte de caja.</p>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/js-sha256@0.9.0/src/sha256.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2/qz-tray.min.js"></script>
    <script src="js/qz-tray-handler.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    <script src="js/reportes.js"></script>
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