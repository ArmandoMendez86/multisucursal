<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Punto de Venta - Sistema POS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");

    body {
      font-family: "Inter", sans-serif;
    }

    .text-xxs {
      font-size: 0.65rem;
      line-height: 0.8rem;
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

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, auto));
      gap: 0.75rem;
      padding: 0.25rem;
      overflow-y: auto;
      flex: 1;

      /* <- añade esto */
      align-content: start;
      /* evita que las filas se estiren para llenar el alto */
      align-items: start;
      /* evita que los ítems se estiren dentro de la celda */
    }

    .product-card {
      background-color: #1e293b;
      padding: 0.75rem;
      border-radius: 0.75rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      border: 1px solid #334155;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
    }

    .product-card:hover {
      background-color: #334155;
      transform: translateY(-3px);
      box-shadow: 0 6px 10px rgba(0, 0, 0, 0.2);
    }

    .product-card.out-of-stock {
      opacity: 0.6;
      background-color: #1a202c;
      cursor: not-allowed;
    }

    .product-card.out-of-stock:hover {
      transform: none;
      background-color: #1a202c;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .product-card-stock.zero-stock {
      color: #ef4444;
      font-weight: 600;
    }

    .product-card-image {
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 0.5rem;
      margin: 0 auto 0.5rem auto;
      border: 1px solid #4a5568;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .product-card-name {
      font-weight: bold;
      color: white;
      font-size: 0.875rem;
      margin-bottom: 0.2rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .product-card-stock {
      font-size: 0.75rem;
      color: #cbd5e1;
      margin-bottom: 0.2rem;
    }

    .product-card-price {
      font-size: 1.125rem;
      font-family: "Inter", monospace;
      color: #4ade80;
      font-weight: bold;
    }

    .cart-item {
      display: flex;
      align-items: center;
      padding: 0.6rem;
      border-bottom: 1px solid #334155;
      background-color: #1e293b;
      border-radius: 0.5rem;
      margin-bottom: 0.4rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      transition: background-color 0.2s ease-in-out;
    }

    .cart-item:hover {
      background-color: #2d3748;
    }

    .cart-item-image {
      width: 40px;
      height: 40px;
      object-fit: cover;
      border-radius: 0.375rem;
      margin-right: 0.5rem;
      border: 1px solid #4a5568;
    }

    .quantity-controls {
      display: flex;
      align-items: center;
      gap: 0.1rem;
      background-color: #334155;
      border-radius: 0.5rem;
      overflow: hidden;
    }

    .quantity-controls button {
      padding: 0.2rem 0.5rem;
      background-color: #4a5568;
      color: white;
      font-weight: bold;
      transition: background-color 0.2s;
      font-size: 0.8rem;
    }

    .quantity-controls button:hover {
      background-color: #6b7280;
    }

    .quantity-controls span {
      padding: 0.2rem 0.4rem;
      color: white;
      font-size: 0.8rem;
    }

    .modal-overlay {
      background-color: rgba(0, 0, 0, 0.75);
    }

    .price-type-btn {
      color: #d1d5db;
      transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
      border: none;
      background-color: transparent;
      cursor: pointer;
    }

    .price-type-btn.active-price-type {
      background-color: #4f46e5;
      color: white;
      box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    }

    .price-type-btn:not(.active-price-type):hover {
      background-color: #374151;
    }

    .select2-container--default .select2-selection--single {
      background-color: #1e293b !important;
      border: 1px solid #4a5568 !important;
      border-radius: 0.375rem !important;
      height: 42px !important;
      display: flex !important;
      align-items: center !important;
      padding-left: 0.75rem !important;
      padding-right: 0.75rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: #cbd5e1 !important;
      line-height: 40px !important;
      padding-left: 0 !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 40px !important;
      right: 8px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow b {
      border-color: #cbd5e1 transparent transparent transparent !important;
    }

    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
      border-color: transparent transparent #cbd5e1 transparent !important;
    }

    .select2-dropdown {
      background-color: #1e293b !important;
      border: 1px solid #4a5568 !important;
      border-radius: 0.375rem !important;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
      background-color: #4f46e5 !important;
      color: white !important;
    }

    .select2-container--default .select2-results__option--selectable {
      color: #cbd5e1 !important;
    }

    .select2-search--dropdown .select2-search__field {
      background-color: #0f172a !important;
      border: 1px solid #4a5568 !important;
      color: #cbd5e1 !important;
      padding: 0.5rem !important;
      border-radius: 0.25rem !important;
    }

    .select2-results__message {
      color: #cbd5e1 !important;
      background-color: #1e293b !important;
      padding: 0.5rem;
    }

    .action-buttons-container {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .action-buttons-container button,
    .action-buttons-container a {
      padding: 0.3rem 0.75rem;
      font-size: 0.75rem;
      line-height: 1rem;
      border-radius: 0.5rem;
      font-weight: 700;
      text-align: center;
      transition: background-color 0.2s ease-in-out;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .action-buttons-container .load-sale-btn {
      color: #9ca3af;
      font-size: 15px;
    }

    .action-buttons-container .pdf-sale-btn {
      color: #9ca3af;
      font-size: 15px;
    }

    .action-buttons-container .delete-sale-btn {
      background-color: transparent;
      color: #9ca3af;
      padding: 0.3rem;
      font-size: 15px;
    }

    .action-buttons-container .delete-sale-btn:hover {
      color: white;
    }

    /* Estilos para que el interruptor (toggle switch) se active visualmente */
    #toggle-negative-stock:checked~.dot {
      transform: translateX(1rem);
      /* Mueve el círculo a la derecha */
    }

    #toggle-negative-stock:checked~div>.dot {
      transform: translateX(1rem);
      /* Mueve el círculo a la derecha */
    }

    #toggle-negative-stock:checked~.block {
      background-color: #22c55e;
      /* Cambia el fondo a verde (Tailwind green-500) */
    }
  </style>
</head>

<body class="bg-[#0f172a] text-gray-300 flex h-screen">

  <?php include_once '../parciales/navegacion.php'; ?>

  <main class="flex-1 flex flex-col overflow-hidden">

    <!-- MODIFICADO: Encabezado ahora se oculta en el breakpoint 'lg' -->
    <header class="lg:hidden flex items-center justify-between bg-[#1e293b] p-4 shadow-md flex-shrink-0">
      <button id="mobile-menu-button" class="text-white focus:outline-none">
        <i class="fas fa-bars text-2xl"></i>
      </button>
      <h1 class="text-lg font-bold text-white">Punto de Venta</h1>
      <div class="w-8"></div>
    </header>


    <div class="flex justify-between">
      <!-- Switch de impresión (visible arriba) -->
      <div class="px-4 py-2 bg-[#0b1220] border-r border-slate-800">
        <div class="flex items-center gap-3">
          <label class="text-sm text-gray-300">Impresión</label>
          <div class="flex items-center bg-[#0f172a] border border-slate-600 rounded-lg overflow-hidden">
            <button id="pm-service" class="px-3 py-1 text-sm bg-slate-700">Servicio</button>
            <button id="pm-qztray" class="px-3 py-1 text-sm">QZ Tray</button>
          </div>
          <button id="btn-qz-connect" class="hidden px-3 py-1 text-sm bg-emerald-600 hover:bg-emerald-500 text-white rounded">
            Conectar QZ
          </button>
          <span id="qz-status" class="text-xs text-gray-400">QZ: <span>Desconectado</span></span>
        </div>
      </div>

      <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Super'): ?>
        <div class="flex items-center space-x-2 border-l border-gray-600 pr-3 p-3">
          <span class="text-sm font-medium text-yellow-400">Vender sin Stock</span>
          <label for="toggle-negative-stock" class="flex items-center cursor-pointer">
            <div class="relative">
              <input type="checkbox" id="toggle-negative-stock" class="sr-only">
              <div class="block bg-gray-600 w-10 h-6 rounded-full"></div>
              <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
            </div>
          </label>
        </div>
      <?php endif; ?>

    </div>

    <!-- MODIFICADO: Contenedor de columnas ahora usa el breakpoint 'lg' -->
    <div class="flex-1 flex lg:flex-row flex-col overflow-y-auto">

      <!-- MODIFICADO: Columna Izquierda ahora usa el breakpoint 'lg' -->
      <div class="lg:w-2/5 w-full flex flex-col p-4">
        <div class="mb-4 flex gap-2">
          <input type="text" id="search-product" placeholder="Buscar producto en esta sucursal..."
            class="w-full bg-gray-700 text-white rounded-md p-3 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" />
          <button id="openCashModalBtn" title="Abrir Caja"
            class="flex-shrink-0 bg-blue-600 hover:bg-blue-500 text-white font-bold p-3 rounded-md text-lg flex items-center justify-center">
            <i class="fas fa-cash-register"></i>
          </button>
          <button id="open-stock-checker-btn" title="Buscar stock en todas las sucursales"
            class="flex-shrink-0 bg-teal-600 hover:bg-teal-500 text-white font-bold p-3 rounded-md text-lg flex items-center justify-center">
            <i class="fas fa-globe"></i>
          </button>
        </div>
        <div id="product-list" class="product-grid "></div>
      </div>

      <!-- MODIFICADO: Columna Derecha ahora usa el breakpoint 'lg' -->
      <div class="lg:w-3/5 w-full bg-[#1e293b] flex flex-col p-4 shadow-lg">

        <div class="mb-4">
          <input type="text" id="search-cart-item" placeholder="Buscar artículo en el carrito..."
            class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" />
        </div>

        <div id="cart-items" class="flex-1 overflow-y-auto border-t border-b border-gray-700 py-2">
          <div class="text-center text-gray-500 py-10">
            El carrito está vacío
          </div>
        </div>

        <div class="py-4 space-y-3 border-b border-gray-700">
          <div class="flex flex-col sm:flex-row  gap-3 items-start sm:items-end">

            <div class="flex-grow w-full">
              <label for="search-client" class="block text-xs font-medium mb-1 text-gray-400">Cliente</label>
              <div class="flex gap-2">
                <select id="search-client" class="w-full">
                  <option value="1" selected>Público en General</option>
                </select>
                <button id="add-new-client-btn" title="Añadir nuevo cliente"
                  class="flex-shrink-0 bg-blue-600 hover:bg-blue-500 text-white font-bold p-2 rounded-md h-[42px] w-[42px] text-sm flex items-center justify-center">
                  <i class="fas fa-user-plus"></i>
                </button>
              </div>
            </div>

            <div class="flex-shrink-0">
              <label class="block text-xs font-medium mb-1 text-gray-400">Tipo de Precio</label>
              <div id="price-type-selector" class="flex items-center bg-gray-800 rounded-lg p-1 border border-gray-700">
                <button data-level="1" class="price-type-btn active-price-type px-4 py-2 text-sm font-semibold rounded-md">P1</button>
                <button data-level="2" class="price-type-btn px-4 py-2 text-sm font-semibold rounded-md">P2</button>
                <button data-level="3" class="price-type-btn px-4 py-2 text-sm font-semibold rounded-md">P3</button>
                <button data-level="4" class="price-type-btn px-4 py-2 text-sm font-semibold rounded-md">P4</button>
                <button data-level="5" class="price-type-btn px-4 py-2 text-sm font-semibold rounded-md">P5</button>
              </div>
              <input type="hidden" id="price-type-value" value="1">
            </div>
          </div>

          <div id="address-selection-container" class="hidden">
            <label for="client-address-select" class="block text-sm font-medium mb-1">Dirección de Envío</label>
            <select id="client-address-select"
              class="w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600"></select>
          </div>

          <button id="open-pending-sales-btn"
            class="w-full text-sm text-blue-400 hover:text-blue-300 font-semibold py-2 px-3 bg-gray-800/50 hover:bg-gray-800 rounded-lg flex items-center justify-center gap-2 mt-2">
            <i class="fas fa-folder-open"></i>
            <span>Ver Ventas Pendientes</span>
          </button>
        </div>


        <div class="py-4 space-y-2">
          <div class="flex items-center justify-between text-sm">
            <label for="toggle-iva" class="font-medium text-gray-300 cursor-pointer">
              <input type="checkbox" id="toggle-iva"
                class="mr-2 h-4 w-4 text-green-600 focus:ring-green-500 rounded border-gray-600" />
              Aplicar IVA (16%)
            </label>
            <span id="cart-tax">$0.00</span>
          </div>
          <div class="flex justify-between text-sm">
            <span>Subtotal</span><span id="cart-subtotal">$0.00</span>
          </div>
          <div class="flex justify-between text-lg font-bold text-white">
            <span>Total</span><span id="cart-total">$0.00</span>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-700">
          <button id="cancel-sale-btn" class="bg-red-600 hover:bg-red-500 text-white font-bold py-3 rounded-lg">
            Cancelar
          </button>
          <button id="save-sale-btn"
            class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg disabled:bg-gray-500 disabled:cursor-not-allowed"
            disabled>
            Guardar
          </button>
          <button id="charge-btn" class="bg-green-600 hover:bg-green-500 text-white font-bold py-3 rounded-lg">
            Cobrar
          </button>
        </div>
      </div>
    </div>
  </main>

  <!-- Modales (sin cambios) -->
  <div id="charge-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <div class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-md">
      <div class="p-6 border-b border-gray-700">
        <h2 class="text-2xl font-bold text-white">Procesar Venta</h2>
      </div>
      <div class="p-6">
        <div class="text-center mb-6">
          <p class="text-gray-400 text-lg">Total a Pagar</p>
          <p id="modal-total" class="text-5xl font-bold text-green-400">
            $0.00
          </p>
        </div>
        <div id="payment-methods-container" class="space-y-4 mb-4"></div>
        <button id="add-payment-method-btn"
          class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 rounded-md mb-4">
          <i class="fas fa-plus mr-2"></i> Añadir Método de Pago
        </button>
        <div class="space-y-2 text-lg">
          <div class="flex justify-between text-gray-300">
            <span>Monto Pagado:</span>
            <span id="modal-amount-paid">$0.00</span>
          </div>
          <div class="flex justify-between font-bold" id="modal-change-row">
            <span>Cambio:</span>
            <span id="modal-change">$0.00</span>
          </div>
          <div class="flex justify-between font-bold text-red-400" id="modal-pending-row">
            <span>Pendiente:</span>
            <span id="modal-pending">$0.00</span>
          </div>
        </div>
      </div>
      <div class="p-6 bg-gray-800 flex justify-end space-x-4 rounded-b-lg">
        <button type="button" id="modal-cancel-btn"
          class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg">
          Cancelar
        </button>
        <button type="button" id="modal-confirm-btn"
          class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-6 rounded-lg">
          Confirmar Venta
        </button>
      </div>
    </div>
  </div>
  <div id="add-client-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <div class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-lg">
      <div class="p-6 border-b border-gray-700 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-white">Añadir Nuevo Cliente</h2>
        <button id="close-add-client-modal-btn" class="text-gray-400 hover:text-white text-2xl">&times;</button>
      </div>
      <div class="p-6">
        <form id="add-client-form" class="space-y-4">
          <div>
            <label for="client-name" class="block text-sm font-medium text-gray-300">Nombre del Cliente <span
                class="text-red-500">*</span></label>
            <input type="text" id="client-name" name="nombre" required
              class="mt-1 block w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
          </div>
          <div>
            <label for="client-rfc" class="block text-sm font-medium text-gray-300">RFC</label>
            <input type="text" id="client-rfc" name="rfc"
              class="mt-1 block w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
          </div>
          <div>
            <label for="client-phone" class="block text-sm font-medium text-gray-300">Teléfono</label>
            <input type="tel" id="client-phone" name="telefono"
              class="mt-1 block w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
          </div>
          <div>
            <label for="client-email" class="block text-sm font-medium text-gray-300">Email</label>
            <input type="email" id="client-email" name="email"
              class="mt-1 block w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
          </div>
          <div class="flex items-center">
            <input type="checkbox" id="client-has-credit" name="tiene_credito" value="1"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-600 rounded">
            <label for="client-has-credit" class="ml-2 block text-sm text-gray-300">Tiene Crédito</label>
          </div>
          <div id="credit-limit-container" class="hidden">
            <label for="client-credit-limit" class="block text-sm font-medium text-gray-300">Límite de Crédito</label>
            <input type="number" step="0.01" id="client-credit-limit" name="limite_credito" value="0.00"
              class="mt-1 block w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]">
          </div>
          <div class="flex justify-end space-x-4 pt-4">
            <button type="button" id="cancel-add-client-btn"
              class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg">
              Cancelar
            </button>
            <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-6 rounded-lg">
              Guardar Cliente
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div id="pending-sales-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <div class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-4xl">
      <div class="p-6 border-b border-gray-700 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-white">Ventas Guardadas</h2>
        <button id="close-pending-sales-modal-btn" class="text-gray-400 hover:text-white text-2xl">&times;</button>
      </div>
      <div class="p-6">
        <input type="text" id="search-pending-sale" placeholder="Buscar por folio o cliente..."
          class="w-full bg-gray-700 text-white rounded-md p-2 mb-4 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" />
        <div class="max-h-[60vh] overflow-y-auto pending-sales-table-wrapper">
          <table class="min-w-full">
            <thead class="bg-gray-800 text-xs text-gray-400 uppercase sticky top-0">
              <tr>
                <th class="py-2 px-4 text-left">Folio</th>
                <th class="py-2 px-4 text-left">Fecha</th>
                <th class="py-2 px-4 text-left">Cliente</th>
                <th class="py-2 px-4 text-right">Total</th>
                <th class="py-2 px-4 text-center w-40">Acciones</th>
              </tr>
            </thead>
            <tbody id="pending-sales-table-body" class="divide-y divide-gray-700">
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div id="stock-checker-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <div class="bg-[#1e293b] rounded-lg shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
      <div class="p-6 border-b border-gray-700 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-white">Consultar Stock en Sucursales</h2>
        <button id="close-stock-checker-modal-btn" class="text-gray-400 hover:text-white text-2xl">&times;</button>
      </div>
      <div class="p-6">
        <input type="text" id="stock-checker-search-input" placeholder="Buscar por nombre, SKU o código de barras..."
          class="w-full bg-gray-700 text-white rounded-md p-3 mb-4 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5]" />
      </div>
      <div id="stock-checker-results" class="flex-1 overflow-y-auto px-6 pb-6">
        <div class="text-center text-gray-500 py-10">
          Introduce un término de búsqueda para ver el stock.
        </div>
      </div>
    </div>
  </div>
  <div id="cashOpeningModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-[#1e293b] rounded-lg shadow-xl p-8 w-full max-w-md">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold text-white">Registrar Apertura de Caja</h3>
        <button id="closeCashModalBtn" class="text-gray-400 hover:text-white text-2xl">&times;</button>
      </div>
      <div id="modal-error-message"
        class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 hidden" role="alert">
        <span class="block sm:inline"></span>
      </div>
      <form id="cashOpeningForm">
        <div class="mb-4">
          <label for="monto_inicial" class="block text-gray-300 text-sm font-bold mb-2">Monto Inicial:</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">$</span>
            <input type="number" id="monto_inicial" name="monto_inicial" required min="0" step="0.01"
              class="shadow appearance-none border rounded w-full py-2 px-3 pl-8 bg-gray-700 text-white border-gray-600 leading-tight focus:outline-none focus:ring-[#4f46e5] focus:border-[#4f46e5]"
              placeholder="0.00">
          </div>
        </div>
        <div class="mb-6">
          <label for="fecha_apertura" class="block text-gray-300 text-sm font-bold mb-2">Fecha de Apertura:</label>
          <input type="date" id="fecha_apertura" name="fecha_apertura" required
            class="shadow appearance-none border rounded w-full py-2 px-3 bg-gray-700 text-white border-gray-600 leading-tight focus:outline-none focus:ring-[#4f46e5] focus:border-[#4f46e5]">
        </div>
        <div class="flex items-center justify-end">
          <button type="button" id="cancelCashOpeningBtn"
            class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg mr-2">
            Cancelar
          </button>
          <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg">
            Registrar Apertura
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal confirmación QZ -->
  <div id="qz-confirm" class="hidden fixed inset-0 bg-black/50 items-center justify-center">
    <div class="bg-white rounded p-4 w-80 text-slate-900">
      <h3 class="font-semibold mb-2">Usar QZ Tray</h3>
      <p class="text-sm mb-4">QZ solicitará permisos del sistema. ¿Deseas continuar?</p>
      <div class="flex justify-end gap-2">
        <button id="qz-cancel" class="px-3 py-1 border rounded">Cancelar</button>
        <button id="qz-accept" class="px-3 py-1 bg-slate-800 text-white rounded">Conectar</button>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <!--   <script src="https://cdn.jsdelivr.net/npm/js-sha256@0.9.0/src/sha256.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2/qz-tray.min.js"></script>
  <script src="js/qz-tray-handler.js"></script> -->
  <script src="js/rutas.js"></script>
  <script src="js/toast.js"></script>
  <script src="js/confirm.js"></script>
  <script src="js/pos.js"></script>
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