document.addEventListener("DOMContentLoaded", function () {
  // --- VARIABLES Y REFERENCIAS GLOBALES ---
  let dataTableInstance;
  let montoAbonoAn;
  let limiteCreditoAn;
  let searchTimeout;
  let specialPriceInstances = [];

  // --- Referencias a elementos del DOM ---
  const clientModal = document.getElementById("client-modal");
  const clientForm = document.getElementById("client-form");
  const specialPricesContainer = document.getElementById("special-prices-container");
  const productSearchInput = document.getElementById("product-search-input");
  const productSearchResults = document.getElementById("product-search-results");
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabContents = document.querySelectorAll(".tab-content");

  // --- INICIALIZACIÓN DE DATATABLE ---
  dataTableInstance = jQuery('#clientesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: `${BASE_URL}/listClients`,
      type: 'POST'
    },
    columns: [
      { data: 'nombre' },
      { data: 'telefono' },
      { data: 'email' },
      { data: 'deuda_actual', className: 'text-right' },
      { data: 'acciones', orderable: false, searchable: false, className: 'text-center' }
    ],
    lengthChange: true,
    pageLength: 15,
    lengthMenu: [[15, 30, 60, -1], [15, 30, 60, "Todos"]],
    columnDefs: [
      {
        targets: 3,
        render: function (data, type, row) {
          if (type === 'display') {
            const number = parseFloat(data) || 0;
            const hasDebt = number > 0;
            const formattedCurrency = new Intl.NumberFormat('es-MX', {
              style: 'currency',
              currency: 'MXN'
            }).format(number);
            const colorClass = hasDebt ? 'text-red-500' : 'text-green-500';
            return `<span class="font-mono ${colorClass}">${formattedCurrency}</span>`;
          }
          return data;
        }
      }
    ],
    dom: "<'flex justify-between'lf>" + "<'clear'>" + "<'flex justify-center mb-8'B>" + 'rtip',
    buttons: [
      { extend: 'copyHtml5', text: 'Copiar', exportOptions: { columns: [0, 1, 2, 3] } },
      { extend: 'excelHtml5', title: 'Clientes', exportOptions: { columns: [0, 1, 2, 3] } },
      { extend: 'csvHtml5', title: 'Clientes', exportOptions: { columns: [0, 1, 2, 3] } },
      { extend: 'pdfHtml5', title: 'Clientes', exportOptions: { columns: [0, 1, 2, 3] } },
      { extend: 'print', text: 'Imprimir', exportOptions: { columns: [0, 1, 2, 3] } }
    ],
    language: { url: 'js/es.json' },
    order: [[0, 'asc']]
  });

  // --- OPCIONES PARA AUTONUMERIC ---
  const autoNumericOptions = {
      currencySymbol: '$',
      currencySymbolPlacement: 'p',
      decimalCharacter: '.',
      digitGroupSeparator: ',',
      decimalPlaces: 2,
      minimumValue: '0'
  };
  
  const autoNumericPriceOptions = {
      ...autoNumericOptions,
      currencySymbol: ''
  };

  // --- LÓGICA DE PESTAÑAS ---
  function switchTab(targetTab) {
    tabContents.forEach(content => content.classList.remove('active'));
    tabButtons.forEach(button => button.classList.remove('active'));
    document.getElementById(`tab-content-${targetTab}`).classList.add('active');
    document.querySelector(`.tab-button[data-tab="${targetTab}"]`).classList.add('active');
  }

  tabButtons.forEach(button => {
      button.addEventListener('click', () => switchTab(button.dataset.tab));
  });

  // --- FUNCIONES DEL MODAL PRINCIPAL ---
  const showModal = () => clientModal.classList.remove("hidden");
  const hideModal = () => {
    specialPriceInstances.forEach(instance => instance.remove());
    specialPriceInstances = [];
    clientModal.classList.add("hidden");
  }

  function prepareNewClientForm() {
    clientForm.reset();
    if (limiteCreditoAn) limiteCreditoAn.clear();
    document.getElementById("client-id").value = "";
    document.getElementById("modal-title").textContent = "Añadir Nuevo Cliente";
    document.getElementById("limite-credito-container").classList.add("hidden");
    document.getElementById("addresses-container").innerHTML = "";
    addAddressRow();
    
    specialPricesContainer.innerHTML = `<p class="text-center text-[var(--color-text-secondary)] placeholder-text">Busque y añada productos para asignar precios especiales.</p>`;
    productSearchInput.value = '';
    productSearchResults.innerHTML = '';
    productSearchResults.classList.add('hidden');
    
    switchTab('personales');
    showModal();
  }

  async function handleEditClient(id) {
    try {
      const response = await fetch(`${BASE_URL}/getClient?id=${id}`);
      const result = await response.json();
      if (result.success) {
        const client = result.data;
        document.getElementById("client-id").value = client.id;
        document.getElementById("nombre").value = client.nombre;
        document.getElementById("rfc").value = client.rfc;
        document.getElementById("telefono").value = client.telefono;
        document.getElementById("email").value = client.email;
        document.getElementById("tiene_credito").checked = client.tiene_credito == 1;
        document.getElementById("limite-credito-container").classList.toggle("hidden", client.tiene_credito != 1);
        if (limiteCreditoAn) limiteCreditoAn.set(client.limite_credito);
        document.getElementById("modal-title").textContent = "Editar Cliente";
        
        specialPricesContainer.innerHTML = '';
        if (client.precios_especiales && client.precios_especiales.length > 0) {
            client.precios_especiales.forEach(sp => {
                addProductToSpecialList({
                    id: sp.id_producto,
                    nombre: sp.nombre,
                    sku: sp.sku,
                    precio_menudeo: sp.precio_menudeo
                }, sp.precio_especial);
            });
        } else {
             specialPricesContainer.innerHTML = `<p class="text-center text-[var(--color-text-secondary)] placeholder-text">Busque y añada productos para asignar precios especiales.</p>`;
        }

        document.getElementById("addresses-container").innerHTML = "";
        if (client.direcciones && client.direcciones.length > 0) {
          client.direcciones.forEach((addr) => addAddressRow(addr));
        } else {
          addAddressRow();
        }
        
        switchTab('personales');
        showModal();
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      showToast("No se pudieron obtener los datos del cliente.", "error");
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
    clientData.tiene_credito = document.getElementById("tiene_credito").checked;
    if (limiteCreditoAn) {
        clientData.limite_credito = limiteCreditoAn.getNumericString();
    }

    const clientId = clientData.id;
    clientData.direcciones = [];
    document.querySelectorAll("#addresses-container .address-row").forEach((row) => {
      const direccion = row.querySelector('textarea[name="direccion"]').value;
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
    specialPriceInstances.forEach(instance => {
        const value = instance.getNumericString();
        if (value && parseFloat(value) > 0) {
            clientData.precios[instance.domElement.dataset.productId] = value;
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

  async function handleDeleteClient(id) {
    const confirmed = await showConfirm("¿Estás seguro? Esta acción es irreversible.");
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
        dataTableInstance.ajax.reload(null, false);
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      showToast("No se pudo eliminar el cliente.", "error");
    }
  }

  // --- LÓGICA DE PRECIOS ESPECIALES (CON TEMA DINÁMICO) ---
  function createSpecialPriceRow(product, specialPrice = '') {
    const productDiv = document.createElement("div");
    productDiv.className = "grid grid-cols-12 gap-3 items-center product-row p-2 bg-[var(--color-bg-primary)] rounded";
    productDiv.dataset.productId = product.id;

    productDiv.innerHTML = `
        <div class="col-span-5">
            <p class="text-sm font-medium">${product.nombre}</p>
            <p class="text-xs text-[var(--color-text-secondary)]">SKU: ${product.sku}</p>
        </div>
        <div class="col-span-3 text-right">
            <p class="text-xs text-[var(--color-text-secondary)]">Normal:</p>
            <p class="text-sm">${AutoNumeric.format(product.precio_menudeo, autoNumericOptions)}</p>
        </div>
        <div class="col-span-3">
            <input type="text" placeholder="Especial" value="${specialPrice}" data-product-id="${product.id}" class="special-price-input w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)] text-right">
        </div>
        <div class="col-span-1 text-right">
             <button type="button" class="remove-special-price-btn text-red-500 hover:text-red-400 text-sm" title="Quitar"><i class="fas fa-times-circle"></i></button>
        </div>
    `;
    return productDiv;
  }

  function addProductToSpecialList(product, specialPrice = '') {
    const existingRow = document.querySelector(`#special-prices-container .product-row[data-product-id='${product.id}']`);
    if (existingRow) return;

    const productRow = createSpecialPriceRow(product, specialPrice);
    const placeholder = specialPricesContainer.querySelector('.placeholder-text');
    if (placeholder) placeholder.remove();
    
    specialPricesContainer.appendChild(productRow);
    const input = productRow.querySelector('.special-price-input');
    const anInstance = new AutoNumeric(input, autoNumericPriceOptions);
    specialPriceInstances.push(anInstance);
  }

  productSearchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    const searchTerm = productSearchInput.value.trim();
    if (searchTerm.length < 2) {
        productSearchResults.innerHTML = "";
        productSearchResults.classList.add("hidden");
        return;
    }
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`${BASE_URL}/searchProductsSimple?term=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();
            productSearchResults.innerHTML = "";
            if (result.success && result.data.length > 0) {
                result.data.forEach(product => {
                    const resultItem = document.createElement("div");
                    resultItem.className = "p-3 hover:bg-[var(--color-bg-primary)] cursor-pointer text-sm";
                    resultItem.innerHTML = `<p class="font-medium">${product.nombre}</p><p class="text-xs text-[var(--color-text-secondary)]">SKU: ${product.sku}</p>`;
                    resultItem.dataset.product = JSON.stringify(product);
                    productSearchResults.appendChild(resultItem);
                });
                productSearchResults.classList.remove("hidden");
            } else {
                productSearchResults.innerHTML = `<div class="p-3 text-sm text-[var(--color-text-secondary)]">No se encontraron productos.</div>`;
                productSearchResults.classList.remove("hidden");
            }
        } catch (error) {
            productSearchResults.classList.add("hidden");
        }
    }, 300);
  });

  productSearchResults.addEventListener("click", (event) => {
    const target = event.target.closest('[data-product]');
    if (target) {
        addProductToSpecialList(JSON.parse(target.dataset.product));
        productSearchInput.value = "";
        productSearchResults.innerHTML = "";
        productSearchResults.classList.add("hidden");
    }
  });

  specialPricesContainer.addEventListener('click', function(event) {
    const removeBtn = event.target.closest('.remove-special-price-btn');
    if (removeBtn) {
        const rowToRemove = removeBtn.closest('.product-row');
        const inputToRemove = rowToRemove.querySelector('.special-price-input');
        const instanceIndex = specialPriceInstances.findIndex(inst => inst.domElement === inputToRemove);
        if (instanceIndex > -1) {
            specialPriceInstances[instanceIndex].remove();
            specialPriceInstances.splice(instanceIndex, 1);
        }
        rowToRemove.remove();
        if (specialPricesContainer.children.length === 0) {
            specialPricesContainer.innerHTML = `<p class="text-center text-[var(--color-text-secondary)] placeholder-text">Busque y añada productos para asignar precios especiales.</p>`;
        }
    }
  });

  // --- LÓGICA DE DIRECCIONES (CON TEMA DINÁMICO) ---
  function addAddressRow(data = {}) {
    const addressRow = document.createElement("div");
    addressRow.className = "address-row bg-[var(--color-bg-primary)] p-4 rounded-lg space-y-3";
    const uniqueId = `principal-${Date.now()}-${Math.random()}`;
    addressRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-[var(--color-text-secondary)]">Dirección Completa</label>
                <textarea name="direccion" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" rows="3" required>${data.direccion || ""}</textarea>
            </div>
            <div>
                <label class="text-sm font-medium text-[var(--color-text-secondary)]">Ciudad</label>
                <input type="text" name="ciudad" value="${data.ciudad || ""}" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
            </div>
            <div>
                <label class="text-sm font-medium text-[var(--color-text-secondary)]">Estado</label>
                <input type="text" name="estado" value="${data.estado || ""}" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
            </div>
            <div>
                <label class="text-sm font-medium text-[var(--color-text-secondary)]">Código Postal</label>
                <input type="text" name="codigo_postal" value="${data.codigo_postal || ""}" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
            </div>
            <div class="md:col-span-2 flex items-center justify-between mt-2">
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="principal" id="${uniqueId}" class="h-4 w-4 text-[var(--color-accent)] bg-[var(--color-bg-primary)] border-[var(--color-border)]" ${data.principal == 1 ? "checked" : ""}>
                    <span class="ml-2 text-sm">Marcar como principal</span>
                </label>
                <button type="button" class="remove-address-btn text-red-500 hover:text-red-400 text-sm"><i class="fas fa-trash-alt mr-1"></i>Eliminar</button>
            </div>
        </div>
    `;
    document.getElementById("addresses-container").appendChild(addressRow);
  }

  // --- LÓGICA DE ABONOS ---
  const paymentModal = document.getElementById("payment-modal");
  const paymentForm = document.getElementById("payment-form");
  const showPaymentModal = () => paymentModal.classList.remove("hidden");
  const hidePaymentModal = () => paymentModal.classList.add("hidden");

  function handleOpenPaymentModal(id, nombre, deuda) {
    paymentForm.reset();
    document.getElementById("payment-client-id").value = id;
    document.getElementById("payment-client-name").value = nombre;
    const deudaActualInput = document.getElementById("payment-client-debt");
    const montoAbonoInput = document.getElementById("monto_abono");
    const deudaFloat = parseFloat(deuda);
    
    const deudaAn = new AutoNumeric(deudaActualInput, { ...autoNumericOptions, readOnly: true });
    deudaAn.set(deudaFloat);

    if (montoAbonoAn) montoAbonoAn.remove(); // Usar remove() para limpiar completamente
    montoAbonoAn = new AutoNumeric(montoAbonoInput, { ...autoNumericOptions, maximumValue: deudaFloat.toFixed(2) });
    montoAbonoInput.placeholder = `Máximo: ${deudaFloat.toFixed(2)}`;
    showPaymentModal();
  }

  async function handlePaymentSubmit(event) {
    event.preventDefault();
    const paymentData = {
      id_cliente: document.getElementById("payment-client-id").value,
      monto: montoAbonoAn.getNumericString(),
      metodo_pago: document.getElementById("metodo_pago_abono").value
    };
    const maxDeuda = parseFloat(montoAbonoAn.getOption('maximumValue'));
    if (!paymentData.monto || parseFloat(paymentData.monto) <= 0) {
      showToast("El monto debe ser un número positivo.", "error");
      return;
    }
    if (parseFloat(paymentData.monto) > maxDeuda) {
      showToast(`El abono no puede ser mayor que la deuda de $${maxDeuda.toFixed(2)}.`, "error");
      return;
    }
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
      showToast("No se pudo conectar con el servidor.", "error");
    }
  }

  // --- EVENT LISTENERS ---
  document.getElementById("add-client-btn").addEventListener("click", prepareNewClientForm);
  document.getElementById("close-modal-btn").addEventListener("click", hideModal);
  document.getElementById("cancel-btn").addEventListener("click", hideModal);
  clientForm.addEventListener("submit", handleFormSubmit);
  document.getElementById("add-address-btn").addEventListener("click", () => addAddressRow());
  document.getElementById("close-payment-modal-btn").addEventListener("click", hidePaymentModal);
  document.getElementById("cancel-payment-btn").addEventListener("click", hidePaymentModal);
  paymentForm.addEventListener("submit", handlePaymentSubmit);

  document.getElementById("addresses-container").addEventListener("click", function (event) {
    if (event.target.closest(".remove-address-btn")) {
      if (document.querySelectorAll("#addresses-container .address-row").length > 1) {
        event.target.closest(".address-row").remove();
      } else {
        showToast("Debe haber al menos una dirección.", "error");
      }
    }
  });

  document.getElementById("tiene_credito").addEventListener("change", function () {
    document.getElementById("limite-credito-container").classList.toggle("hidden", !this.checked);
  });

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
    handleOpenPaymentModal(data.id, data.nombre, data.deuda_actual);
  });

  // --- INICIALIZACIÓN DE AUTONUMERIC ---
  limiteCreditoAn = new AutoNumeric('#limite_credito', autoNumericOptions);
});
