document.addEventListener("DOMContentLoaded", function () {
  let allProducts = [];
  let dataTableInstance; // Variable para mantener la instancia de DataTables

  // --- Referencias a elementos del DOM ---
  const addClientBtn = document.getElementById("add-client-btn");
  const clientModal = document.getElementById("client-modal");
  const closeModalBtn = document.getElementById("close-modal-btn");
  const cancelBtn = document.getElementById("cancel-btn");
  const clientForm = document.getElementById("client-form");
  // Se elimina la referencia a clientsTableBody porque DataTables lo manejará
  const tieneCreditoCheckbox = document.getElementById("tiene_credito");
  const limiteCreditoContainer = document.getElementById("limite-credito-container");
  const modalTitle = document.getElementById("modal-title");
  const specialPricesContainer = document.getElementById("special-prices-container");
  const productSearchInput = document.getElementById("product-search-input");
  const addressesContainer = document.getElementById("addresses-container");
  const addAddressBtn = document.getElementById("add-address-btn");
  const paymentModal = document.getElementById("payment-modal");
  const closePaymentModalBtn = document.getElementById("close-payment-modal-btn");
  const cancelPaymentBtn = document.getElementById("cancel-payment-btn");
  const paymentForm = document.getElementById("payment-form");

  // --- INICIALIZACIÓN DE DATATABLES CON SERVER-SIDE ---
  dataTableInstance = jQuery('#clientesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: `${BASE_URL}/listClients`, // Nueva ruta que crearemos
      type: 'POST'
    },
    columns: [
      { data: 'nombre' },
      { data: 'telefono' },
      { data: 'email' },
      { data: 'deuda_actual', className: 'text-right' },
      { data: 'acciones', orderable: false, searchable: false, className: 'text-center' }
    ],
    lengthChange: true, // 1. Activa el selector
    pageLength: 10,     // Opcional: define la cantidad de filas por defecto
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
    // --- MODIFICACIÓN: Añadido para formatear la moneda ---
    columnDefs: [
      {
        targets: 3, // Apunta a la columna "Deuda Actual" (índice 3)
        render: function (data, type, row) {
          // Solo formateamos para la vista ('display')
          if (type === 'display') {
            const number = parseFloat(data) || 0;
            const hasDebt = number > 0;
            // Usamos la API Intl para un formato de moneda correcto y localizado
            const formattedCurrency = new Intl.NumberFormat('es-MX', {
              style: 'currency',
              currency: 'MXN'
            }).format(number); // -> $1,500.00

            const colorClass = hasDebt ? 'text-red-400' : 'text-green-400';
            return `<span class="font-mono ${colorClass}">${formattedCurrency}</span>`;
          }
          // Para ordenar, buscar, etc., usamos el dato original (el número)
          return data;
        }
      }
    ],
    dom: 'Bfrtip',
    buttons: [
      { extend: 'copyHtml5', text: 'Copiar', exportOptions: { columns: [0, 1, 2, 3] } },
      { extend: 'excelHtml5', title: 'Clientes', exportOptions: { columns: [0, 1, 2, 3] } },
      { extend: 'csvHtml5', title: 'Clientes', exportOptions: { columns: [0, 1, 2, 3] } },
      { extend: 'pdfHtml5', title: 'Clientes', exportOptions: { columns: [0, 1, 2, 3] } },
      { extend: 'print', text: 'Imprimir', exportOptions: { columns: [0, 1, 2, 3] } }
    ],
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
    order: [[0, 'asc']]
  });

  // --- Funciones para manejar Modales ---
  const showModal = () => clientModal.classList.remove("hidden");
  const hideModal = () => clientModal.classList.add("hidden");
  const showPaymentModal = () => paymentModal.classList.remove("hidden");
  const hidePaymentModal = () => paymentModal.classList.add("hidden");

  function prepareNewClientForm() {
    clientForm.reset();
    document.getElementById("client-id").value = "";
    modalTitle.textContent = "Añadir Nuevo Cliente";
    limiteCreditoContainer.classList.add("hidden");
    addressesContainer.innerHTML = "";
    addAddressRow();
    renderProductsForSpecialPrices([]);
    loadProductsForSpecialPrices();
    showModal();
  }

  // --- Lógica de la API ---

  async function handleEditClient(id) {
    try {
      loadProductsForSpecialPrices();
      const response = await fetch(`${BASE_URL}/getClient?id=${id}`);
      const result = await response.json();
      if (result.success) {
        const client = result.data;
        document.getElementById("client-id").value = client.id;
        document.getElementById("nombre").value = client.nombre;
        document.getElementById("rfc").value = client.rfc;
        document.getElementById("telefono").value = client.telefono;
        document.getElementById("email").value = client.email;
        tieneCreditoCheckbox.checked = client.tiene_credito == 1;
        limiteCreditoContainer.classList.toggle("hidden", client.tiene_credito != 1);
        document.getElementById("limite_credito").value = client.limite_credito;
        modalTitle.textContent = "Editar Cliente";
        populateSpecialPrices(client.precios_especiales || {});
        addressesContainer.innerHTML = "";
        if (client.direcciones && client.direcciones.length > 0) {
          client.direcciones.forEach((addr) => addAddressRow(addr));
        } else {
          addAddressRow();
        }
        showModal();
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      console.error("Error en handleEditClient:", error);
      showToast("No se pudieron obtener los datos del cliente.", "error");
    }
  }

  async function handleDeleteClient(id) {
    const confirmed = await showConfirm(
      "¿Estás seguro de que quieres eliminar este cliente? Esta acción es irreversible."
    );
    if (!confirmed) return;
    try {
      const response = await fetch(`${BASE_URL}/deleteClient`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: id }),
      });
      const result = await response.json();
      if (result.success) {
        showToast("Cliente eliminado exitosamente.", "success");
        dataTableInstance.ajax.reload(null, false); // Recargar la tabla sin resetear paginación
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      showToast("No se pudo eliminar el cliente.", "error");
    }
  }

  async function handleFormSubmit(event) {
    event.preventDefault();
    const formData = new FormData(clientForm);
    let clientData = {};
    for (let [key, value] of formData.entries()) {
      if (!key.startsWith('direccion') && !key.startsWith('ciudad') && !key.startsWith('estado') && !key.startsWith('codigo_postal') && !key.startsWith('principal')) {
        clientData[key] = value;
      }
    }
    clientData.tiene_credito = tieneCreditoCheckbox.checked;
    const clientId = clientData.id;
    clientData.direcciones = [];
    const addressRows = addressesContainer.querySelectorAll(".address-row");
    addressRows.forEach((row) => {
      const direccion = row.querySelector('input[name="direccion"]').value;
      if (direccion) {
        clientData.direcciones.push({
          direccion: direccion,
          ciudad: row.querySelector('input[name="ciudad"]').value,
          estado: row.querySelector('input[name="estado"]').value,
          codigo_postal: row.querySelector('input[name="codigo_postal"]').value,
          principal: row.querySelector('input[name="principal"]').checked ? 1 : 0,
        });
      }
    });
    clientData.precios = {};
    const priceInputs = specialPricesContainer.querySelectorAll(".special-price-input");
    priceInputs.forEach((input) => {
      if (input.value) {
        clientData.precios[input.dataset.productId] = input.value;
      }
    });
    const url = clientId ? `${BASE_URL}/updateClient` : `${BASE_URL}/createClient`;
    try {
      const response = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(clientData),
      });
      const result = await response.json();
      if (result.success) {
        hideModal();
        dataTableInstance.ajax.reload(null, false);
        showToast(`Cliente ${clientId ? "actualizado" : "creado"} exitosamente.`, "success");
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      showToast("No se pudo conectar con el servidor.", "error");
    }
  }

  function handleOpenPaymentModal(id, nombre, deuda) {
    paymentForm.reset();
    document.getElementById("payment-client-id").value = id;
    document.getElementById("payment-client-name").value = nombre;
    const deudaActualInput = document.getElementById("payment-client-debt");
    const montoAbonoInput = document.getElementById("monto_abono");
    const deudaFloat = parseFloat(deuda);
    deudaActualInput.value = `$${deudaFloat.toFixed(2)}`;
    montoAbonoInput.max = deudaFloat.toFixed(2);
    montoAbonoInput.placeholder = `Máximo: ${deudaFloat.toFixed(2)}`;
    showPaymentModal();
  }

  async function handlePaymentSubmit(event) {
    event.preventDefault();
    const clientId = document.getElementById("payment-client-id").value;
    const montoInput = document.getElementById("monto_abono");
    const monto = parseFloat(montoInput.value);
    const metodoPago = document.getElementById("metodo_pago_abono").value;
    const maxDeuda = parseFloat(montoInput.max);
    if (isNaN(monto) || monto <= 0) {
      showToast("El monto debe ser un número positivo.", "error");
      return;
    }
    if (monto > maxDeuda) {
      showToast(`El abono no puede ser mayor que la deuda de $${maxDeuda.toFixed(2)}.`, "error");
      return;
    }
    const paymentData = {
      id_cliente: clientId,
      monto: monto,
      metodo_pago: metodoPago
    };
    try {
      const response = await fetch(`${BASE_URL}/registrarAbono`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(paymentData),
      });
      const result = await response.json();
      if (response.ok && result.success) {
        hidePaymentModal();
        dataTableInstance.ajax.reload(null, false);
        showToast("Abono registrado exitosamente.", "success");
      } else {
        showToast(result.message || "No se pudo registrar el abono.", "error");
      }
    } catch (error) {
      console.error("Error en handlePaymentSubmit:", error);
      showToast("No se pudo conectar con el servidor.", "error");
    }
  }

  async function loadProductsForSpecialPrices() {
    if (allProducts.length > 0) {
      renderProductsForSpecialPrices(allProducts);
      return;
    }
    try {
      const response = await fetch(`${BASE_URL}/getProductosParaPreciosEspeciales`);
      const result = await response.json();
      if (result.success) {
        allProducts = result.data;
        renderProductsForSpecialPrices(allProducts);
      } else {
        specialPricesContainer.innerHTML = `<p class="text-red-500">Error al cargar productos.</p>`;
      }
    } catch (error) {
      specialPricesContainer.innerHTML = `<p class="text-red-500">No se pudo conectar para cargar productos.</p>`;
    }
  }

  function renderProductsForSpecialPrices(products) {
    if (!products || products.length === 0) {
      specialPricesContainer.innerHTML = `<p class="text-center text-gray-500">No hay productos disponibles.</p>`;
      return;
    }
    specialPricesContainer.innerHTML = "";
    products.forEach((product) => {
      const productDiv = document.createElement("div");
      productDiv.className = "grid grid-cols-12 gap-3 items-center product-row";
      productDiv.dataset.productName = product.nombre.toLowerCase();
      productDiv.dataset.productSku = product.sku.toLowerCase();
      productDiv.innerHTML = `
          <div class="col-span-6"><p class="text-sm font-medium text-white">${product.nombre}</p><p class="text-xs text-gray-400">SKU: ${product.sku}</p></div>
          <div class="col-span-3"><p class="text-sm text-gray-400 text-right">Normal:</p><p class="text-sm text-gray-300 text-right">$${parseFloat(product.precio_menudeo).toFixed(2)}</p></div>
          <div class="col-span-3"><input type="number" step="0.01" placeholder="Especial" data-product-id="${product.id}" class="special-price-input w-full bg-gray-800 text-white rounded-md p-2 border border-gray-600 focus:ring-[#4f46e5] focus:border-[#4f46e5] text-right"></div>
      `;
      specialPricesContainer.appendChild(productDiv);
    });
  }

  function populateSpecialPrices(specialPrices) {
    const priceInputs = specialPricesContainer.querySelectorAll(".special-price-input");
    priceInputs.forEach((input) => {
      const productId = input.dataset.productId;
      if (specialPrices[productId]) {
        input.value = parseFloat(specialPrices[productId]).toFixed(2);
      } else {
        input.value = "";
      }
    });
  }

  function filterProducts() {
    const searchTerm = productSearchInput.value.toLowerCase();
    const productRows = specialPricesContainer.querySelectorAll(".product-row");
    productRows.forEach((row) => {
      const name = row.dataset.productName;
      const sku = row.dataset.productSku;
      const isVisible = name.includes(searchTerm) || sku.includes(searchTerm);
      row.style.display = isVisible ? "grid" : "none";
    });
  }

  function addAddressRow(data = {}) {
    const addressRow = document.createElement("div");
    addressRow.className = "address-row bg-gray-800 p-4 rounded-lg space-y-3";
    const uniqueId = `principal-${Date.now()}-${Math.random()}`;
    addressRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="text-sm font-medium">Dirección Completa</label>
                <input type="text" name="direccion" value="${data.direccion || ""}" class="mt-1 w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600" required>
            </div>
            <div><label class="text-sm font-medium">Ciudad</label><input type="text" name="ciudad" value="${data.ciudad || ""}" class="mt-1 w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600"></div>
            <div><label class="text-sm font-medium">Estado</label><input type="text" name="estado" value="${data.estado || ""}" class="mt-1 w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600"></div>
            <div><label class="text-sm font-medium">Código Postal</label><input type="text" name="codigo_postal" value="${data.codigo_postal || ""}" class="mt-1 w-full bg-gray-700 text-white rounded-md p-2 border border-gray-600"></div>
            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="principal" id="${uniqueId}" class="h-4 w-4 text-[#4f46e5] bg-gray-700 border-gray-600" ${data.principal == 1 ? "checked" : ""}>
                    <span class="ml-2 text-sm">Marcar como principal</span>
                </label>
                <button type="button" class="remove-address-btn text-red-500 hover:text-red-400 text-sm"><i class="fas fa-trash-alt mr-1"></i>Eliminar</button>
            </div>
        </div>
    `;
    addressesContainer.appendChild(addressRow);
  }

  // --- Asignación de Eventos ---
  addClientBtn.addEventListener("click", prepareNewClientForm);
  closeModalBtn.addEventListener("click", hideModal);
  cancelBtn.addEventListener("click", hideModal);
  clientForm.addEventListener("submit", handleFormSubmit);
  addAddressBtn.addEventListener("click", () => addAddressRow());
  closePaymentModalBtn.addEventListener("click", hidePaymentModal);
  cancelPaymentBtn.addEventListener("click", hidePaymentModal);
  paymentForm.addEventListener("submit", handlePaymentSubmit);
  addressesContainer.addEventListener("click", function (event) {
    if (event.target.closest(".remove-address-btn")) {
      if (addressesContainer.querySelectorAll(".address-row").length > 1) {
        event.target.closest(".address-row").remove();
      } else {
        showToast("Debe haber al menos una dirección.", "error");
      }
    }
  });
  tieneCreditoCheckbox.addEventListener("change", function () {
    limiteCreditoContainer.classList.toggle("hidden", !this.checked);
  });

  // Delegación de eventos para la tabla manejada por DataTables
  jQuery('#clientesTable tbody').on('click', '.edit-btn', function () {
    const data = dataTableInstance.row(jQuery(this).parents('tr')).data();
    handleEditClient(data.id);
  });

  jQuery('#clientesTable tbody').on('click', '.delete-btn', function () {
    const data = dataTableInstance.row(jQuery(this).parents('tr')).data();
    handleDeleteClient(data.id);
  });

  jQuery('#clientesTable tbody').on('click', '.payment-btn', function () {
    const data = dataTableInstance.row(jQuery(this).parents('tr')).data();
    // Usamos el dato original 'deuda_actual' que ahora es un número
    handleOpenPaymentModal(data.id, data.nombre, data.deuda_actual);
  });

  productSearchInput.addEventListener("input", filterProducts);
});
