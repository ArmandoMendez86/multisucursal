<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .dataTables_wrapper .dataTables_filter input {
            background-color: #374151;
            color: #e5e7eb;
            border: 1px solid #4b5563;
            border-radius: 0.375rem;
            padding: 0.5rem;
            margin-left: 0.5rem;
        }

        .dataTables_wrapper .dataTables_filter label {
            color: #d1d5db;
        }

        .dataTables_wrapper .dataTables_empty {
            text-align: center;
            padding: 2.5rem;
            color: #9ca3af;
        }

        table.dataTable thead th {
            border-bottom: 2px solid #ffffffff !important;
            background-color:#0f172a;
        }

        table.dataTable.no-footer {
            border-bottom: none;
        }

        .dataTables_wrapper .dataTables_processing {
            background: #1e293b;
            color: white;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.75);
        }

        .modal-body {
            max-height: 65vh;
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

        .stock-adjust-input {
            width: 60px;
            text-align: center;
            -moz-appearance: textfield;
        }

        .stock-adjust-input::-webkit-outer-spin-button,
        .stock-adjust-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>

<!-- CAMBIO 1: Añadir el rol del usuario como un atributo de datos -->

<body class="bg-[#0f172a] text-gray-300" data-user-role="<?php echo htmlspecialchars($_SESSION['rol'] ?? 'user'); ?>">

    <div class="flex h-screen">
        <?php include_once '../parciales/navegacion.php'; ?>
        <main class="flex-1 p-8 overflow-y-auto">
            <!-- ... (Cabecera, botones y tabla de productos sin cambios) ... -->
            <header class="lg:hidden flex items-center justify-between bg-[#1e293b] p-4 shadow-md flex-shrink-0">
                <button id="mobile-menu-button" class="text-white focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-lg font-bold text-white">Punto de Venta</h1>
                <div class="w-8"></div>
            </header>

            <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto mb-8 md:justify-end">
                <button id="add-product-btn"
                    class="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold py-2 px-4 rounded-lg flex items-center w-full md:w-auto justify-center">
                    <i class="fas fa-plus mr-2"></i> Añadir Producto
                </button>
                <button id="manage-categories-btn"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-4 rounded-lg flex items-center w-full md:w-auto justify-center">
                    <i class="fas fa-tags mr-2"></i> Gestionar Categorías
                </button>
                <button id="manage-brands-btn"
                    class="bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 px-4 rounded-lg flex items-center w-full md:w-auto justify-center">
                    <i class="fas fa-copyright mr-2"></i> Gestionar Marcas
                </button>
            </div>

            <div class="bg-[#1e293b] rounded-lg shadow overflow-auto mb-8 p-4">
                <h3 class="text-2xl font-bold text-white">
                    <i class="fas fa-box mr-3 text-blue-400"></i> Gestión de Inventario
                </h3>
                <table id="productsTable" class="min-w-full">
                    <thead class="bg-gray-800 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="py-3 px-6 text-left">SKU</th>
                            <th class="py-3 px-6 text-left">Nombre</th>
                            <th class="py-3 px-6 text-left">Códigos de Barras</th>
                            <th class="py-3 px-6 text-left">Categoría</th>
                            <th class="py-3 px-6 text-center">Stock</th>
                            <th class="py-3 px-6 text-right">Precio Menudeo</th>
                            <th class="py-3 px-6 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 text-xs"></tbody>
                </table>
            </div>

            <div class="bg-[#1e293b] rounded-lg shadow overflow-hidden p-6">
                <h3 class="text-2xl font-bold text-white flex items-center text-end">
                    <i class="fas fa-history mr-3 text-blue-400"></i> Historial de Movimientos de Inventario
                </h3>
                <div class="bg-[#1e293b] rounded-lg shadow overflow-auto mb-8 p-4">
                    <table class="min-w-full" id="historyTable">
                        <thead class="bg-gray-800 text-xs text-gray-400 uppercase sticky top-0">
                            <tr>
                                <th class="py-3 px-6 text-left">Fecha</th>
                                <th class="py-3 px-6 text-left">Producto</th>
                                <th class="py-3 px-6 text-left">Tipo</th>
                                <th class="py-3 px-6 text-center">Cantidad</th>
                                <th class="py-3 px-6 text-center">Stock Anterior</th>
                                <th class="py-3 px-6 text-center">Stock Nuevo</th>
                                <th class="py-3 px-6 text-left">Motivo / Ref.</th>
                                <th class="py-3 px-6 text-left">Usuario</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700 text-xs"></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ... (Modal de producto sin cambios) ... -->
    <div id="product-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-2xl transform transition-all duration-300 ease-in-out">
            <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
                <h2 id="modal-title" class="text-xl font-bold text-white flex items-center"><i
                        class="fas fa-box-open mr-3"></i>Añadir Nuevo Producto</h2>
                <button id="close-modal-btn"
                    class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            <form id="product-form">
                <input type="hidden" id="product-id" name="id">

                <div id="clone-section" class="px-6 pt-4 pb-2 bg-[#131c2b] border-b border-gray-700 hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-300">¿Crear a partir de un producto existente?</span>
                        <button type="button" id="toggle-clone-btn"
                            class="text-sm text-[#6366f1] hover:underline">Clonar producto</button>
                    </div>
                    <div id="clone-controls" class="hidden mt-3">
                        <label for="clone-source-product"
                            class="block text-sm font-medium text-gray-400 mb-1">Selecciona un producto para clonar sus
                            datos</label>
                        <div class="flex gap-2">
                            <select id="clone-source-product"
                                class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                                <!-- Opciones se cargarán con JS -->
                            </select>
                            <button type="button" id="load-clone-data-btn"
                                class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-4 rounded-lg flex-shrink-0">Cargar
                                Datos</button>
                        </div>
                    </div>
                </div>

                <div class="p-6 modal-body overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div class="md:col-span-2">
                            <label for="nombre" class="block text-sm font-medium text-gray-300 mb-1">Nombre del
                                Producto</label>
                            <input type="text" id="nombre" name="nombre"
                                class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"
                                required>
                        </div>

                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-300 mb-1">SKU / Código
                                Interno</label>
                            <input type="text" id="sku" name="sku"
                                class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"
                                required>
                        </div>

                        <!-- SECCIÓN DE CÓDIGOS DE BARRAS MODIFICADA -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-300 mb-2">Códigos de Barras</label>
                            <div id="barcodes-container">
                                <!-- Inputs dinámicos aquí -->
                            </div>
                            <button type="button" id="add-barcode-btn"
                                class="mt-2 bg-blue-600 hover:bg-blue-500 text-white font-bold py-1 px-3 rounded-lg text-sm flex items-center">
                                <i class="fas fa-plus mr-2"></i>Añadir Código
                            </button>
                        </div>
                        <!-- FIN DE SECCIÓN MODIFICADA -->

                        <div>
                            <label for="id_categoria"
                                class="block text-sm font-medium text-gray-300 mb-1">Categoría</label>
                            <select id="id_categoria" name="id_categoria"
                                class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"></select>
                        </div>

                        <div>
                            <label for="id_marca" class="block text-sm font-medium text-gray-300 mb-1">Marca</label>
                            <select id="id_marca" name="id_marca"
                                class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"></select>
                        </div>
                    </div>

                    <hr class="border-gray-600 my-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <h3 class="md:col-span-2 text-lg font-semibold text-white mb-2">Precios y Stock</h3>
                        <div>
                            <label for="precio_menudeo" class="block text-sm font-medium text-gray-300 mb-1">Precio
                                Menudeo</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">$</span>
                                <input type="number" step="0.01" id="precio_menudeo" name="precio_menudeo"
                                    class="w-full bg-gray-700 text-white rounded-md p-2 pl-7 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"
                                    required>
                            </div>
                        </div>
                        <div>
                            <label for="precio_mayoreo" class="block text-sm font-medium text-gray-300 mb-1">Precio
                                Mayoreo</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">$</span>
                                <input type="number" step="0.01" id="precio_mayoreo" name="precio_mayoreo"
                                    class="w-full bg-gray-700 text-white rounded-md p-2 pl-7 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"
                                    required>
                            </div>
                        </div>
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-300 mb-1">Stock
                                Inicial</label>
                            <input type="number" id="stock" name="stock" value="0" min="0"
                                class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                        </div>
                        <div>
                            <label for="stock_minimo" class="block text-sm font-medium text-gray-300 mb-1">Stock
                                Mínimo</label>
                            <input type="number" id="stock_minimo" name="stock_minimo" value="5" min="0"
                                class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                        </div>
                    </div>

                    <hr class="border-gray-600 my-6">

                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-300 mb-1">Descripción
                            (Opcional)</label>
                        <textarea id="descripcion" name="descripcion" rows="3"
                            class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-[#131c2b] rounded-b-lg flex justify-end items-center gap-4">
                    <button type="button" id="cancel-btn"
                        class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200">Cancelar</button>
                    <button type="submit"
                        class="bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200">
                        <i class="fas fa-save"></i>
                        Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Ajuste de Stock -->
    <div id="adjust-stock-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-md transform transition-all duration-300 ease-in-out">
            <div class="p-6 border-b border-gray-700 flex justify-between items-center">
                <h2 id="adjust-modal-title" class="text-2xl font-bold text-white">Ajustar Stock</h2>
                <button id="close-adjust-modal-btn" class="text-gray-400 hover:text-white">&times;</button>
            </div>
            <div class="p-6">
                <input type="hidden" id="adjust-product-id">
                <input type="hidden" id="adjust-action">
                <input type="hidden" id="adjust-current-stock-value">
                <div class="mb-4">
                    <p class="text-gray-400">Producto: <span id="adjust-product-name"
                            class="font-bold text-white"></span></p>
                    <p class="text-gray-400">Stock Actual: <span id="adjust-current-stock-display"
                            class="font-bold text-white"></span></p>
                </div>

                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Super'): ?>
                    <div id="branch-selector-container" class="mb-4">
                        <label for="adjust-branch-select" class="block text-sm font-medium text-gray-300 mb-1">Abastecer a
                            Sucursal</label>
                        <select id="adjust-branch-select"
                            class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
                        </select>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label id="adjust-quantity-label" for="adjust-quantity"
                        class="block text-sm font-medium text-gray-300 mb-1">Cantidad</label>
                    <input type="number" id="adjust-quantity"
                        class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"
                        placeholder="0" min="1">
                </div>
                <div class="mb-4">
                    <label for="adjust-stock-reason" class="block text-sm font-medium text-gray-300 mb-1">Motivo del
                        Ajuste</label>
                    <textarea id="adjust-stock-reason" rows="3"
                        class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]"
                        required></textarea>
                </div>
            </div>
            <div class="p-6 border-t border-gray-700 flex justify-end">
                <button type="button" id="cancel-adjust-btn"
                    class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg mr-2">Cancelar</button>
                <button type="button" id="confirm-adjust-btn"
                    class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- ... (Modales de categorías y marcas) ... -->
    <div id="category-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-2xl transform transition-all duration-300 ease-in-out">
            <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
                <h2 id="category-modal-title" class="text-xl font-bold text-white flex items-center"><i
                        class="fas fa-tags mr-3"></i>Gestionar Categorías</h2>
                <button id="close-category-modal-btn"
                    class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 modal-body overflow-y-auto">
                <form id="category-form" class="mb-6 p-4 border border-gray-700 rounded-lg bg-[#131c2b]">
                    <input type="hidden" id="category-id" name="id">
                    <div class="mb-4">
                        <label for="category-name" class="block text-sm font-medium text-gray-300 mb-1">Nombre de la
                            Categoría</label>
                        <input type="text" id="category-name" name="nombre"
                            class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="category-description"
                            class="block text-sm font-medium text-gray-300 mb-1">Descripción (Opcional)</label>
                        <textarea id="category-description" name="descripcion" rows="2"
                            class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancel-category-edit-btn"
                            class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 hidden">Cancelar
                            Edición</button>
                        <button type="submit" id="save-category-btn"
                            class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200">
                            <i class="fas fa-plus-circle"></i>
                            Añadir Categoría
                        </button>
                    </div>
                </form>
                <h3 class="text-lg font-semibold text-white mb-3 flex items-center"><i
                        class="fas fa-list mr-2"></i>Categorías Existentes</h3>
                <div class="max-h-[30vh] overflow-y-auto overflow-x-auto rounded-lg">
                    <table class="min-w-full">
                        <thead class="bg-gray-800 text-xs text-gray-400 uppercase sticky top-0">
                            <tr>
                                <th class="py-3 px-6 text-left">Nombre</th>
                                <th class="py-3 px-6 text-left">Descripción</th>
                                <th class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="categories-table-body" class="divide-y divide-gray-700">
                            <!-- Contenido cargado por JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gestionar Marcas -->
    <div id="brand-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-xl transform transition-all duration-300 ease-in-out">
            <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
                <h2 id="brand-modal-title" class="text-xl font-bold text-white flex items-center"><i
                        class="fas fa-copyright mr-3"></i>Gestionar Marcas</h2>
                <button id="close-brand-modal-btn"
                    class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 modal-body overflow-y-auto">
                <form id="brand-form" class="mb-6 p-4 border border-gray-700 rounded-lg bg-[#131c2b]">
                    <input type="hidden" id="brand-id" name="id">
                    <div class="mb-4">
                        <label for="brand-name" class="block text-sm font-medium text-gray-300 mb-1">Nombre de la
                            Marca</label>
                        <input type="text" id="brand-name" name="nombre"
                            class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-purple-500 focus:border-purple-500"
                            required>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancel-brand-edit-btn"
                            class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200 hidden">Cancelar
                            Edición</button>
                        <button type="submit" id="save-brand-btn"
                            class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200">
                            <i class="fas fa-plus-circle"></i>
                            Añadir Marca
                        </button>
                    </div>
                </form>
                <h3 class="text-lg font-semibold text-white mb-3 flex items-center"><i
                        class="fas fa-list mr-2"></i>Marcas Existentes</h3>
                <div class="max-h-[30vh] overflow-y-auto overflow-x-auto rounded-lg">
                    <table class="min-w-full">
                        <thead class="bg-gray-800 text-xs text-gray-400 uppercase sticky top-0">
                            <tr>
                                <th class="py-3 px-6 text-left">Nombre</th>
                                <th class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="brands-table-body" class="divide-y divide-gray-700">
                            <!-- Contenido cargado por JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    <script src="js/inventario.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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