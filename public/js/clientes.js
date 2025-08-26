document.addEventListener("DOMContentLoaded", function () {
  // --- CONFIG ---
  const NOMINATIM_EMAIL = "armando.mendez.dev@gmail.com"; // requerido por OSM
  const NOMINATIM_BASE = "https://nominatim.openstreetmap.org/search";
  const CP_API_BASE = "https://api.zippopotam.us/MX";

  // --- ESTADO/UI GLOBAL ---
  let dataTableInstance;
  let montoAbonoAn;
  let limiteCreditoAn;
  let searchTimeout;
  let specialPriceInstances = [];
  let nominatimAbortController = null;

  // --- DOM ROOTS ---
  const clientModal = document.getElementById("client-modal");
  const clientForm = document.getElementById("client-form");
  const specialPricesContainer = document.getElementById("special-prices-container");
  const productSearchInput = document.getElementById("product-search-input");
  const productSearchResults = document.getElementById("product-search-results");
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabContents = document.querySelectorAll(".tab-content");

  // --- DATATABLE ---
  dataTableInstance = jQuery('#clientesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: `${BASE_URL}/listClients`, type: 'POST' },
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
    columnDefs: [{
      targets: 3,
      render: function (data, type) {
        if (type === 'display') {
          const n = parseFloat(data) || 0;
          const fmt = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);
          return `<span class="font-mono ${n > 0 ? 'text-red-500' : 'text-green-500'}">${fmt}</span>`;
        }
        return data;
      }
    }],
    dom: "<'flex justify-between'lf><'clear'><'flex justify-center mb-8'B>rtip",
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

  // --- AutoNumeric ---
  const autoNumericOptions = {
    currencySymbol: '$', currencySymbolPlacement: 'p',
    decimalCharacter: '.', digitGroupSeparator: ',',
    decimalPlaces: 2, minimumValue: '0'
  };
  const autoNumericPriceOptions = { ...autoNumericOptions, currencySymbol: '' };

  // --- Tabs ---
  function switchTab(target) {
    tabContents.forEach(c => c.classList.remove('active'));
    tabButtons.forEach(b => b.classList.remove('active'));
    document.getElementById(`tab-content-${target}`).classList.add('active');
    document.querySelector(`.tab-button[data-tab="${target}"]`).classList.add('active');
  }
  tabButtons.forEach(b => b.addEventListener('click', () => switchTab(b.dataset.tab)));

  // --- Modal cliente ---
  const showModal = () => clientModal.classList.remove("hidden");
  const hideModal = () => {
    specialPriceInstances.forEach(i => i.remove());
    specialPriceInstances = [];
    clientModal.classList.add("hidden");
  };

  function prepareNewClientForm() {
    clientForm.reset();
    if (limiteCreditoAn) limiteCreditoAn.clear();
    document.getElementById("client-id").value = "";
    document.getElementById("modal-title").textContent = "Añadir Nuevo Cliente";
    document.getElementById("limite-credito-container").classList.add("hidden");
    document.getElementById("addresses-container").innerHTML = "";
    addAddressRow();
    specialPricesContainer.innerHTML = `<p class="text-center text-[var(--color-text-secondary)] placeholder-text">Busque y añada productos para asignar precios especiales.</p>`;
    productSearchInput.value = ''; productSearchResults.innerHTML = ''; productSearchResults.classList.add('hidden');
    switchTab('personales'); showModal();
  }

  async function handleEditClient(id) {
    try {
      const r = await fetch(`${BASE_URL}/getClient?id=${id}`);
      const result = await r.json();
      if (!result.success) { showToast(result.message, "error"); return; }
      const c = result.data;
      document.getElementById("client-id").value = c.id;
      document.getElementById("nombre").value = c.nombre;
      document.getElementById("rfc").value = c.rfc;
      document.getElementById("telefono").value = c.telefono;
      document.getElementById("email").value = c.email;
      document.getElementById("tiene_credito").checked = c.tiene_credito == 1;
      document.getElementById("limite-credito-container").classList.toggle("hidden", c.tiene_credito != 1);
      if (limiteCreditoAn) limiteCreditoAn.set(c.limite_credito);
      document.getElementById("modal-title").textContent = "Editar Cliente";

      specialPricesContainer.innerHTML = '';
      if (c.precios_especiales?.length) {
        c.precios_especiales.forEach(sp => addProductToSpecialList({
          id: sp.id_producto, nombre: sp.nombre, sku: sp.sku, precio_menudeo: sp.precio_menudeo
        }, sp.precio_especial));
      } else {
        specialPricesContainer.innerHTML = `<p class="text-center text-[var(--color-text-secondary)] placeholder-text">Busque y añada productos para asignar precios especiales.</p>`;
      }

      document.getElementById("addresses-container").innerHTML = "";
      if (c.direcciones?.length) c.direcciones.forEach(a => addAddressRow(a));
      else addAddressRow();

      switchTab('personales'); showModal();
    } catch { showToast("No se pudieron obtener los datos del cliente.", "error"); }
  }

  // --- Guardado ---
  async function handleFormSubmit(e) {
    e.preventDefault();
    const fd = new FormData(clientForm);
    let clientData = {};
    for (let [k, v] of fd.entries()) {
      if (!k.startsWith('direccion') && !k.startsWith('ciudad') && !k.startsWith('estado') && !k.startsWith('codigo_postal') && !k.startsWith('principal')) clientData[k] = v;
    }
    clientData.tiene_credito = document.getElementById("tiene_credito").checked;
    // asegura número, nunca cadena vacía
    if (clientData.tiene_credito) {
      const v = limiteCreditoAn ? limiteCreditoAn.getNumericString() : "";
      clientData.limite_credito = v && !isNaN(v) ? v : "0";
    } else {
      clientData.limite_credito = "0";
    }


    clientData.direcciones = [];
    document.querySelectorAll("#addresses-container .address-row").forEach(row => {
      // Selección manual o de lista
      const ciudadSel = row.querySelector('[name="ciudad"]');
      const estadoSel = row.querySelector('[name="estado"]');
      const ciudad = ciudadSel.value === "__manual__" ? row.querySelector('input[name="ciudad_manual"]').value.trim() : ciudadSel.value.trim();
      const estado = estadoSel.value === "__manual__" ? row.querySelector('input[name="estado_manual"]').value.trim() : estadoSel.value.trim();

      // Extras → se agregan al texto para no tocar el backend
      const dirEl = row.querySelector('textarea[name="direccion"]');
      const colonia = row.querySelector('[name="colonia"]')?.value.trim() || '';
      const municipio = row.querySelector('input[name="municipio"]')?.value.trim() || '';
      const entre1 = row.querySelector('input[name="entre1"]')?.value.trim() || '';
      const entre2 = row.querySelector('input[name="entre2"]')?.value.trim() || '';
      const refs = row.querySelector('input[name="referencias"]')?.value.trim() || '';

      let direccion = dirEl.value.trim();
      const notas = [];
      if (colonia) notas.push(`Col. ${colonia}`);
      if (municipio) notas.push(`Mun. ${municipio}`);
      if (entre1 || entre2) notas.push(`Entre ${entre1}${entre1 && entre2 ? ' y ' : ''}${entre2}`);
      if (refs) notas.push(`Refs ${refs}`);
      if (notas.length) direccion = `${direccion}${direccion ? ' ' : ''}[${notas.join(' | ')}]`;

      if (!direccion) return;
      clientData.direcciones.push({
        direccion,
        ciudad,
        estado,
        codigo_postal: row.querySelector('input[name="codigo_postal"]').value.trim(),
        principal: row.querySelector('input[name="principal"]').checked ? 1 : 0,
      });
    });

    clientData.precios = {};
    specialPriceInstances.forEach(inst => {
      const val = inst.getNumericString();
      if (val && parseFloat(val) > 0) clientData.precios[inst.domElement.dataset.productId] = val;
    });

    const url = clientData.id ? `${BASE_URL}/updateClient` : `${BASE_URL}/createClient`;
    try {
      const r = await fetch(url, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(clientData) });
      const result = await r.json();
      if (result.success) { hideModal(); dataTableInstance.ajax.reload(null, false); showToast(`Cliente ${clientData.id ? "actualizado" : "creado"} exitosamente.`, "success"); }
      else showToast(result.message, "error");
    } catch { showToast("No se pudo conectar con el servidor.", "error"); }
  }

  async function handleDeleteClient(id) {
    const ok = await showConfirm("¿Estás seguro? Esta acción es irreversible."); if (!ok) return;
    try {
      const r = await fetch(`${BASE_URL}/deleteClient`, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id }) });
      const result = await r.json();
      if (result.success) { showToast("Cliente eliminado exitosamente.", "success"); dataTableInstance.ajax.reload(null, false); }
      else showToast(result.message, "error");
    } catch { showToast("No se pudo eliminar el cliente.", "error"); }
  }

  // --- Utilidades dirección ---
  function populateSelect(sel, values, placeholder = "Selecciona…") {
    sel.innerHTML = "";
    const ph = document.createElement("option");
    ph.value = ""; ph.textContent = placeholder; ph.disabled = true; ph.selected = true;
    sel.appendChild(ph);
    values.forEach(v => {
      const opt = document.createElement("option");
      opt.value = v; opt.textContent = v; sel.appendChild(opt);
    });
    const manual = document.createElement("option");
    manual.value = "__manual__"; manual.textContent = "Otra… (escribir)";
    sel.appendChild(manual);
  }
  function setSelectValue(sel, value) {
    if (!value) return;
    let found = Array.from(sel.options).some(o => o.value === value);
    if (!found) {
      const opt = document.createElement("option");
      opt.value = value; opt.textContent = value;
      sel.insertBefore(opt, sel.lastChild); // antes de "Otra…"
    }
    sel.value = value;
  }

  async function fetchZippopotam(cp) {
    const r = await fetch(`${CP_API_BASE}/${encodeURIComponent(cp)}`);
    if (!r.ok) return null;
    return r.json();
  }
  async function fetchNominatimByCP(cp) {
    try {
      if (nominatimAbortController) nominatimAbortController.abort();
      nominatimAbortController = new AbortController();
      const params = new URLSearchParams({
        format: "jsonv2",
        postalcode: cp,                 // SOLO structured, sin 'q'
        countrycodes: "mx",
        addressdetails: "1",
        limit: "1",
        dedupe: "1",
        "accept-language": "es",
        email: NOMINATIM_EMAIL || ""
      });
      const r = await fetch(`${NOMINATIM_BASE}?${params.toString()}`, { signal: nominatimAbortController.signal });
      if (!r.ok) return null;
      const data = await r.json();
      if (!Array.isArray(data) || !data.length) return null;
      const a = data[0].address || {};
      return {
        city: a.city || a.town || a.village || a.municipality || a.county || "",
        state: a.state || a.region || "",
        county: a.county || "",
      };
    } catch { return null; }
  }

  async function buildFromCP(cp, row, preselect = {}) {
    const coloniaSel = row.querySelector('select[name="colonia"]');
    const ciudadSel = row.querySelector('select[name="ciudad"]');
    const estadoSel = row.querySelector('select[name="estado"]');
    const municipioInput = row.querySelector('input[name="municipio"]');

    // 1) Colonias y estado desde Zippopotam
    const z = await fetchZippopotam(cp);
    let colonias = [];
    let estadoZ = "";
    if (z?.places?.length) {
      estadoZ = z.places[0].state || "";
      colonias = [...new Set(z.places.map(p => p['place name']).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es'));
    }
    populateSelect(coloniaSel, colonias, colonias.length ? "Selecciona colonia…" : "Sin colonias");
    populateSelect(estadoSel, estadoZ ? [estadoZ] : [], "Selecciona estado…");
    if (estadoZ) setSelectValue(estadoSel, preselect.estado || estadoZ);

    // 2) Ciudad y municipio desde Nominatim
    const n = await fetchNominatimByCP(cp);
    const ciudadN = n?.city || "";
    const municipioN = n?.county || "";
    populateSelect(ciudadSel, ciudadN ? [ciudadN] : [], "Selecciona ciudad…");
    if (ciudadN) setSelectValue(ciudadSel, preselect.ciudad || ciudadN);
    if (municipioInput && municipioN && !municipioInput.value) municipioInput.value = municipioN;

    // Preselección de colonia si viene en texto
    if (preselect.colonia) setSelectValue(coloniaSel, preselect.colonia);
  }

  function attachCpWorkflow(row, data = {}) {
    const cpInput = row.querySelector('input[name="codigo_postal"]');
    const ciudadSel = row.querySelector('select[name="ciudad"]');
    const estadoSel = row.querySelector('select[name="estado"]');

    // Mostrar inputs manuales si elige "Otra…"
    const ciudadManual = row.querySelector('input[name="ciudad_manual"]');
    const estadoManual = row.querySelector('input[name="estado_manual"]');
    function toggleManual(sel, manualInput) {
      manualInput.classList.toggle('hidden', sel.value !== "__manual__");
    }
    ciudadSel.addEventListener('change', () => toggleManual(ciudadSel, ciudadManual));
    estadoSel.addEventListener('change', () => toggleManual(estadoSel, estadoManual));

    cpInput.setAttribute('maxlength', '5');
    let t;
    cpInput.addEventListener('input', () => {
      clearTimeout(t);
      const raw = (cpInput.value || "").replace(/\D/g, '');
      if (raw.length === 5) {
        t = setTimeout(() => buildFromCP(raw, row, {
          ciudad: data.ciudad || "",
          estado: data.estado || "",
          colonia: data.colonia || ""
        }), 200);
      }
    });

    // Si viene con datos precargados (editar)
    const raw = (cpInput.value || "").replace(/\D/g, '');
    if (raw.length === 5) {
      buildFromCP(raw, row, { ciudad: data.ciudad, estado: data.estado, colonia: data.colonia });
    }
  }

  // --- Autocompletado libre OSM para textarea (opcional) ---
  async function queryNominatimFree(text) {
    try {
      if (nominatimAbortController) nominatimAbortController.abort();
      nominatimAbortController = new AbortController();
      const params = new URLSearchParams({
        format: "jsonv2",
        q: text,
        addressdetails: "1",
        limit: "6",
        countrycodes: "mx",
        dedupe: "1",
        "accept-language": "es",
        email: NOMINATIM_EMAIL || ""
      });
      const r = await fetch(`${NOMINATIM_BASE}?${params.toString()}`, { signal: nominatimAbortController.signal });
      if (!r.ok) return [];
      const data = await r.json();
      return Array.isArray(data) ? data : [];
    } catch { return []; }
  }
  function attachAddressAutocomplete(row) {
    const wrapper = row.querySelector('.direccion-wrapper');
    const textarea = row.querySelector('textarea[name="direccion"]');
    const box = row.querySelector('.address-suggestions');
    if (!textarea || !box) return;
    let t;
    const hide = () => { box.classList.add('hidden'); box.innerHTML = ''; };
    const show = () => box.classList.remove('hidden');
    textarea.addEventListener('input', () => {
      const q = textarea.value.trim();
      clearTimeout(t);
      if (q.length < 3) { hide(); return; }
      t = setTimeout(async () => {
        const items = await queryNominatimFree(q);
        if (!items.length) { hide(); return; }
        box.innerHTML = items.map(it => {
          const a = it.address || {};
          const label = it.display_name;
          const city = a.city || a.town || a.village || a.municipality || a.county || '';
          const state = a.state || a.region || '';
          const postcode = a.postcode || '';
          return `<div class="addr-item p-2 hover:bg-[var(--color-bg-primary)] cursor-pointer text-sm" data-json='${JSON.stringify({ label, city, state, postcode }).replace(/'/g, "&apos;")}'>${label}</div>`;
        }).join('');
        show();
      }, 450);
    });
    box.addEventListener('click', (e) => {
      const el = e.target.closest('.addr-item'); if (!el) return;
      const p = JSON.parse(el.dataset.json.replace(/&apos;/g, "'"));
      textarea.value = p.label;
      const ciudadSel = row.querySelector('select[name="ciudad"]');
      const estadoSel = row.querySelector('select[name="estado"]');
      const cpInput = row.querySelector('input[name="codigo_postal"]');
      if (p.city) { populateSelect(ciudadSel, [p.city]); setSelectValue(ciudadSel, p.city); }
      if (p.state) { populateSelect(estadoSel, [p.state]); setSelectValue(estadoSel, p.state); }
      if (p.postcode && !cpInput.value) cpInput.value = p.postcode;
      hide();
    });
    document.addEventListener('click', (e) => { if (!wrapper.contains(e.target)) hide(); });
  }

  // --- UI de dirección (con selects) ---
  function addAddressRow(data = {}) {
    const row = document.createElement("div");
    row.className = "address-row bg-[var(--color-bg-primary)] p-4 rounded-lg space-y-3";
    const uniqueId = `principal-${Date.now()}-${Math.random()}`;
    row.innerHTML = `
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2 relative direccion-wrapper">
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Dirección Completa</label>
          <textarea name="direccion" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" rows="3" required>${data.direccion || ""}</textarea>
          <div class="address-suggestions hidden absolute left-0 right-0 max-h-56 overflow-auto bg-[var(--color-bg-secondary)] border border-[var(--color-border)] rounded-md shadow-md z-50"></div>
        </div>

        <div class="relative">
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Código Postal</label>
          <input type="text" name="codigo_postal" value="${data.codigo_postal || ""}" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" placeholder="Ej. 39030">
          <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Escribe 5 dígitos. Se cargarán colonias y ciudad/estado.</p>
        </div>

        <div>
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Ciudad</label>
          <select name="ciudad" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
          <input type="text" name="ciudad_manual" placeholder="Escribe ciudad…" class="mt-2 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] hidden">
        </div>

        <div>
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Estado</label>
          <select name="estado" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
          <input type="text" name="estado_manual" placeholder="Escribe estado…" class="mt-2 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] hidden">
        </div>

        <div>
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Colonia / Asentamiento</label>
          <select name="colonia" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
        </div>

        <div>
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Municipio / Alcaldía</label>
          <input type="text" name="municipio" value="${data.municipio || ""}" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
        </div>

        <div class="md:col-span-2">
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Entre calles</label>
          <div class="grid grid-cols-2 gap-2">
            <input type="text" name="entre1" placeholder="Calle 1" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
            <input type="text" name="entre2" placeholder="Calle 2" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
          </div>
        </div>

        <div class="md:col-span-2">
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Referencias</label>
          <input type="text" name="referencias" placeholder="Punto de referencia, color de fachada, etc." class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
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
    document.getElementById("addresses-container").appendChild(row);

    // Placeholders iniciales
    populateSelect(row.querySelector('select[name="ciudad"]'), [], "Selecciona ciudad…");
    populateSelect(row.querySelector('select[name="estado"]'), [], "Selecciona estado…");
    populateSelect(row.querySelector('select[name="colonia"]'), [], "Selecciona colonia…");

    // Prefill si viene data
    if (data.ciudad) setSelectValue(row.querySelector('select[name="ciudad"]'), data.ciudad);
    if (data.estado) setSelectValue(row.querySelector('select[name="estado"]'), data.estado);

    // Flujos
    attachCpWorkflow(row, data);
    attachAddressAutocomplete(row);
  }

  // --- Precios especiales ---
  function createSpecialPriceRow(product, specialPrice = '') {
    const el = document.createElement("div");
    el.className = "grid grid-cols-12 gap-3 items-center product-row p-2 bg-[var(--color-bg-primary)] rounded";
    el.dataset.productId = product.id;
    el.innerHTML = `
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
      </div>`;
    return el;
  }
  function addProductToSpecialList(product, specialPrice = '') {
    if (document.querySelector(`#special-prices-container .product-row[data-product-id='${product.id}']`)) return;
    const row = createSpecialPriceRow(product, specialPrice);
    const ph = specialPricesContainer.querySelector('.placeholder-text'); if (ph) ph.remove();
    specialPricesContainer.appendChild(row);
    const input = row.querySelector('.special-price-input');
    const inst = new AutoNumeric(input, autoNumericPriceOptions);
    specialPriceInstances.push(inst);
  }
  productSearchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    const term = productSearchInput.value.trim();
    if (term.length < 2) { productSearchResults.innerHTML = ""; productSearchResults.classList.add("hidden"); return; }
    searchTimeout = setTimeout(async () => {
      try {
        const r = await fetch(`${BASE_URL}/searchProductsSimple?term=${encodeURIComponent(term)}`);
        const result = await r.json();
        productSearchResults.innerHTML = "";
        if (result.success && result.data.length) {
          result.data.forEach(p => {
            const item = document.createElement("div");
            item.className = "p-3 hover:bg-[var(--color-bg-primary)] cursor-pointer text-sm";
            item.innerHTML = `<p class="font-medium">${p.nombre}</p><p class="text-xs text-[var(--color-text-secondary)]">SKU: ${p.sku}</p>`;
            item.dataset.product = JSON.stringify(p);
            productSearchResults.appendChild(item);
          });
          productSearchResults.classList.remove("hidden");
        } else {
          productSearchResults.innerHTML = `<div class="p-3 text-sm text-[var(--color-text-secondary)]">No se encontraron productos.</div>`;
          productSearchResults.classList.remove("hidden");
        }
      } catch { productSearchResults.classList.add("hidden"); }
    }, 300);
  });
  productSearchResults.addEventListener("click", (e) => {
    const t = e.target.closest('[data-product]'); if (!t) return;
    addProductToSpecialList(JSON.parse(t.dataset.product));
    productSearchInput.value = ""; productSearchResults.innerHTML = ""; productSearchResults.classList.add("hidden");
  });
  specialPricesContainer.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-special-price-btn'); if (!btn) return;
    const row = btn.closest('.product-row');
    const input = row.querySelector('.special-price-input');
    const idx = specialPriceInstances.findIndex(inst => inst.domElement === input);
    if (idx > -1) { specialPriceInstances[idx].remove(); specialPriceInstances.splice(idx, 1); }
    row.remove();
    if (specialPricesContainer.children.length === 0) {
      specialPricesContainer.innerHTML = `<p class="text-center text-[var(--color-text-secondary)] placeholder-text">Busque y añada productos para asignar precios especiales.</p>`;
    }
  });

  // --- Abonos ---
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
    if (montoAbonoAn) montoAbonoAn.remove();
    montoAbonoAn = new AutoNumeric(montoAbonoInput, { ...autoNumericOptions, maximumValue: deudaFloat.toFixed(2) });
    montoAbonoInput.placeholder = `Máximo: ${deudaFloat.toFixed(2)}`;
    showPaymentModal();
  }
  async function handlePaymentSubmit(e) {
    e.preventDefault();
    const paymentData = {
      id_cliente: document.getElementById("payment-client-id").value,
      monto: montoAbonoAn.getNumericString(),
      metodo_pago: document.getElementById("metodo_pago_abono").value
    };
    const maxDeuda = parseFloat(montoAbonoAn.getOption('maximumValue'));
    if (!paymentData.monto || parseFloat(paymentData.monto) <= 0) { showToast("El monto debe ser un número positivo.", "error"); return; }
    if (parseFloat(paymentData.monto) > maxDeuda) { showToast(`El abono no puede ser mayor que la deuda de $${maxDeuda.toFixed(2)}.`, "error"); return; }
    try {
      const r = await fetch(`${BASE_URL}/registrarAbono`, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(paymentData) });
      const result = await r.json();
      if (r.ok && result.success) { hidePaymentModal(); dataTableInstance.ajax.reload(null, false); showToast("Abono registrado.", "success"); }
      else showToast(result.message || "No se pudo registrar el abono.", "error");
    } catch { showToast("No se pudo conectar con el servidor.", "error"); }
  }

  // --- Listeners globales ---
  document.getElementById("add-client-btn").addEventListener("click", prepareNewClientForm);
  document.getElementById("close-modal-btn").addEventListener("click", hideModal);
  document.getElementById("cancel-btn").addEventListener("click", hideModal);
  clientForm.addEventListener("submit", handleFormSubmit);
  document.getElementById("add-address-btn").addEventListener("click", () => addAddressRow());
  document.getElementById("close-payment-modal-btn").addEventListener("click", hidePaymentModal);
  document.getElementById("cancel-payment-btn").addEventListener("click", hidePaymentModal);
  paymentForm.addEventListener("submit", handlePaymentSubmit);

  document.getElementById("addresses-container").addEventListener("click", (e) => {
    if (!e.target.closest(".remove-address-btn")) return;
    if (document.querySelectorAll("#addresses-container .address-row").length > 1) e.target.closest(".address-row").remove();
    else showToast("Debe haber al menos una dirección.", "error");
  });
  document.getElementById("tiene_credito").addEventListener("change", function () {
    document.getElementById("limite-credito-container").classList.toggle("hidden", !this.checked);
  });
  jQuery('#clientesTable tbody').on('click', '.edit-btn', function () {
    const data = dataTableInstance.row(jQuery(this).parents('tr')).data(); handleEditClient(data.id);
  });
  jQuery('#clientesTable tbody').on('click', '.delete-btn', function () {
    const data = dataTableInstance.row(jQuery(this).parents('tr')).data(); handleDeleteClient(data.id);
  });
  jQuery('#clientesTable tbody').on('click', '.payment-btn', function () {
    const data = dataTableInstance.row(jQuery(this).parents('tr')).data(); handleOpenPaymentModal(data.id, data.nombre, data.deuda_actual);
  });

  // --- Init ---
  limiteCreditoAn = new AutoNumeric('#limite_credito', autoNumericOptions);
});
