<?php
// Archivo: /public/reporte_global.php
require_once __DIR__ . '/../parciales/verificar_sesion.php';

// Solo los administradores pueden ver esta página
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Super') {
    header('Location: pos.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Global de Ventas - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #d1d5db;
        }

        .dataTables_wrapper .dataTables_length select {
            background-color: #374151;
            border-color: #4b5563;
        }

        .dataTables_wrapper .dataTables_filter input {
            background-color: #374151;
            border-color: #4b5563;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #d1d5db !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: #6b7280 !important;
        }

        table.dataTable {
            border-color: #4b5563;
        }

        table.dataTable thead th,
        table.dataTable tfoot th {
            border-bottom-color: #4b5563 !important;
        }

        table.dataTable tbody td {
            border-top-color: #4b5563 !important;
        }

        .ticket-id-cell {
            color: #818cf8;
            font-weight: 600;
        }

        tfoot {
            background-color: #111827;
            font-weight: bold;
        }

        /* --- ESTILOS COMPLETOS PARA DATERANGEPICKER (TEMA OSCURO) --- */
        .daterangepicker {
            background-color: #1e293b;
            border-color: #4b5563;
        }

        /* --- Estilos para la lista de rangos predefinidos --- */
        .daterangepicker .ranges li {
            color: #d1d5db;
            background-color: #374151;
            border: 1px solid #4b5563;
            border-radius: 4px;
            margin-bottom: 4px;
        }

        .daterangepicker .ranges li:hover {
            background-color: #4f46e5;
            color: #ffffff;
        }

        .daterangepicker .ranges li.active {
            background-color: #4338ca;
            color: #ffffff;
        }

        .daterangepicker .calendar-table {
            background-color: #1e293b;
        }

        .daterangepicker .month {
            color: #e5e7eb;
        }

        .daterangepicker th,
        .daterangepicker td {
            color: #d1d5db;
        }

        .daterangepicker td.available:hover {
            background-color: #374151;
            color: #ffffff;
        }

        .daterangepicker td.off,
        .daterangepicker td.off.in-range,
        .daterangepicker td.off.start-date,
        .daterangepicker td.off.end-date {
            background-color: transparent;
            color: #6b7280;
        }

        .daterangepicker td.in-range {
            background-color: rgba(79, 70, 229, 0.4);
            color: #e5e7eb;
        }

        .daterangepicker td.active,
        .daterangepicker td.active:hover {
            background-color: #4f46e5;
            color: #ffffff;
        }

        .daterangepicker .drp-buttons .btn {
            background-color: #374151;
            border-color: #4b5563;
            color: #d1d5db;
        }

        .daterangepicker .drp-buttons .applyBtn {
            background-color: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff;
        }

        .daterangepicker .drp-buttons .applyBtn:hover {
            background-color: #4338ca;
        }

        .daterangepicker:after,
        .daterangepicker:before {
            border-bottom-color: #1e293b !important;
        }

        .dt-top-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        /* Contenedor para los controles inferiores */
        .dt-bottom-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        /* Alineación específica para cada sección */
        .dt-top-controls .left-controls {
            text-align: left;
        }

        .dt-top-controls .center-controls {
            text-align: center;
        }

        .dt-top-controls .right-controls {
            text-align: right;
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
            <h1 class="text-3xl font-bold text-white mb-8">Reporte Global de Ventas</h1>

            <div class="bg-[#1e293b] p-4 rounded-lg mb-6">
                <h3 class="text-lg font-semibold text-white mb-3">Filtros Personalizados</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="daterange-filter" class="block text-sm font-medium text-gray-300 mb-1">Rango de
                            Fechas</label>
                        <div class="relative">
                            <i
                                class="fas fa-calendar-alt absolute top-1/2 left-3 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="daterange-filter"
                                class="bg-gray-700 text-white rounded-md p-2 pl-10 border border-gray-600 w-full cursor-pointer"
                                placeholder="Selecciona un rango de fechas">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-[#1e293b] p-6 rounded-lg">
                <table id="global-sales-table" class="display responsive nowrap text-sm" style="width:100%">
                    <thead class="bg-gray-800 text-xs text-gray-400 uppercase">
                        <tr>
                            <th>Fecha</th>
                            <th>Ticket ID</th>
                            <th>Sucursal</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot class="text-white">
                        <tr>
                            <th colspan="5" class="text-right">Total de la Vista Actual:</th>
                            <th class="text-right"></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="js/datatable-moment-plugin.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/reporte_global.js"></script>
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