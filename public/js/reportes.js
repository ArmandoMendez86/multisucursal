// Archivo: /public/js/reportes.js

document.addEventListener("DOMContentLoaded", function () {
  // --- DataTables server-side for Sales Report ---
  let ventasDataTable = null;

  function initSalesDataTable() {
    const tabla = $('#tablaVentas');
    if (ventasDataTable) {
      ventasDataTable.ajax.reload();
      return;
    }
    ventasDataTable = tabla.DataTable({
      serverSide: true,
      processing: true,
      searching: true, // DataTables search box
      lengthMenu: [10, 25, 50, 100],
      pageLength: 15,
      ajax: {
        url: `${BASE_URL}/getSalesReportPaginated`,
        type: 'GET',
        data: function (d) {
          // CORREGIDO: Ya no se leen los inputs de fecha que no existen.
          // Los filtros de fecha se pueden manejar en el backend si es necesario,
          // o se pueden volver a agregar al HTML. Por ahora, se eliminan del request.
          const userSel = document.getElementById('user-filter-select');
          d.user_id = userSel ? (userSel.value === 'all' ? '' : userSel.value) : '';
        }
      },
      columns: [
        { data: 'fecha', "render": (data) => moment(data).format('DD/MM/YYYY, h:mm a'), title: 'Fecha' },
        { data: 'id', title: 'Ticket ID' },
        { data: 'cliente', title: 'Cliente' },
        { data: 'usuario', title: 'Vendedor' },
        {
          data: 'total',
          title: 'Total',
          className: 'dt-right',
          render: function (val, type) {
            if (type === 'display' || type === 'filter') {
              const n = parseFloat(val || 0);
              const f = n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
              return `<span class="text-green-400 font-semibold">$${f}</span>`;
            }
            return val;
          }
        },
        {
          data: 'estado',
          title: 'Estado',
          render: function (val) {
            const chip =
              val === 'Completada' ? 'bg-emerald-600/20 text-emerald-300' :
                val === 'Cancelada' ? 'bg-red-600/20 text-red-300' :
                  'bg-amber-600/20 text-amber-300';
            return `<span class="px-2 py-0.5 rounded-full text-xs ${chip}">${val}</span>`;
          }
        },
        { data: 'acciones', title: 'Acciones', orderable: false, searchable: false, className: 'text-center' }
      ],

      order: [[0, 'desc']],
      dom: 'Bfrtip',
      buttons: [
        { extend: 'csvHtml5', text: 'CSV', title: 'reporte_ventas' },
        { extend: 'excelHtml5', text: 'Excel', title: 'reporte_ventas' },
        { extend: 'pdfHtml5', text: 'PDF', title: 'reporte_ventas', orientation: 'landscape', pageSize: 'A4' },
        { extend: 'print', text: 'Imprimir' }
      ],
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
      }
    });
    
    $(document).on('click', '#tablaVentas .print-ticket-btn', function (e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      if (id) { handlePrintTicket(parseInt(id, 10)); }
    });
    $(document).on('click', '#tablaVentas .print-ticket-win-btn', async function (e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      if (id) { await handlePrintTicketViaDialog(parseInt(id, 10)); }
    });
    $(document).on('click', '#tablaVentas .view-pdf-btn', function (e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      if (id) { handleViewPdf(parseInt(id, 10)); }
    });
    $(document).on('click', '#tablaVentas .cancel-sale-btn', async function (e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      if (id) { await cancelSale(parseInt(id, 10)); }
    });
  }

  // CORREGIDO: Se eliminan las referencias a los inputs de fecha que ya no existen en el HTML.
  // const startDateInput = document.getElementById("start-date");
  // const endDateInput = document.getElementById("end-date");
  const cashCutDateInput = document.getElementById("cash-cut-date");
  const initialCashInput = document.getElementById("initial-cash");
  const generateCashCutBtn = document.getElementById("generate-cash-cut-btn");
  const printCashCutBtn = document.getElementById("print-cash-cut-btn");
  const cashCutResultsContainer = document.getElementById("cash-cut-results");
  const userFilterSelect = document.getElementById('user-filter-select');

  let currentCashCutData = null;
  let currentInitialCash = 0;
  let configuredPrinter = null;

  async function fetchPrinterConfig() {
    try {
      const response = await fetch(`${BASE_URL}/getPrinterConfig`);
      const result = await response.json();
      if (result.success && result.data.impresora_tickets) {
        configuredPrinter = result.data.impresora_tickets;
      }
    } catch (error) {
      console.error("No se pudo cargar la configuración de la impresora.", error);
    }
  }

  async function loadUsersForFilter() {
    if (!userFilterSelect) return;
    try {
      const response = await fetch(`${BASE_URL}/getBranchUsers`);
      const result = await response.json();
      if (result.success) {
        result.data.forEach(user => {
          const option = document.createElement('option');
          option.value = user.id;
          option.textContent = user.nombre;
          userFilterSelect.appendChild(option);
        });
      }
    } catch (error) {
      console.error('Error al cargar usuarios para el filtro:', error);
    }
  }

  if (typeof connectQz === "function") {
    connectQz();
  }

  const ticketWidth = 32;
  const removeAccents = (str) => str ? str.normalize("NFD").replace(/[\u0300-\u036f]/g, "") : "";
  const formatCurrencyForTicket = (value) => "$" + parseFloat(value).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const formatLine = (left, right = "", width = ticketWidth) => {
    const leftClean = removeAccents(left);
    const rightClean = removeAccents(right);
    const padding = Math.max(0, width - leftClean.length - rightClean.length);
    return leftClean + " ".repeat(padding) + rightClean + "\x0A";
  };
  const formatCentered = (text, width = ticketWidth) => {
    const textClean = removeAccents(text);
    const padding = Math.max(0, Math.floor((width - textClean.length) / 2));
    return " ".repeat(padding) + textClean + " ".repeat(width - textClean.length - padding) + "\x0A";
  };

  async function printTicket(printerName, ticketData) {
    if (!qzTrayConnected) {
      showToast("No se puede imprimir: QZ Tray está desconectado.", "error");
      return;
    }
    const config = qz.configs.create(printerName);
    let dataToPrint = [
      "\x1B" + "\x40",
      "\x1B" + "\x74" + "\x11",
      "\x1B" + "\x61" + "\x31",
      "\x1B" + "\x21" + "\x10",
      removeAccents(ticketData.venta.sucursal_nombre) + "\x0A",
      "\x1B" + "\x21" + "\x00",
      removeAccents(ticketData.venta.sucursal_direccion) + "\x0A",
      "Tel: " + ticketData.venta.sucursal_telefono + "\x0A",
      "\x0A",
      "\x1B" + "\x61" + "\x30",
      formatLine("Ticket:", "#" + ticketData.venta.id.toString().padStart(6, "0")),
      formatLine("Fecha:", new Date(ticketData.venta.fecha).toLocaleString("es-MX")),
      formatLine("Cliente:", ticketData.venta.cliente),
      formatLine("Vendedor:", ticketData.venta.vendedor),
      "-".repeat(ticketWidth) + "\x0A",
      formatLine("Cant Descripcion", "Total"),
      "-".repeat(ticketWidth) + "\x0A",
    ];
    ticketData.items.forEach((item) => {
      const qtyPart = `${item.cantidad} `;
      const pricePart = formatCurrencyForTicket(item.subtotal);
      const maxNameLength = ticketWidth - qtyPart.length - pricePart.length - 1;
      let productName = item.producto_nombre;
      if (productName.length > maxNameLength) {
        productName = productName.substring(0, maxNameLength - 3) + "...";
      }
      const mainLine = qtyPart + productName;
      dataToPrint.push(formatLine(mainLine, pricePart));
      if (item.sku) {
        dataToPrint.push(formatLine(`  SKU: ${item.sku}`));
      }
    });
    dataToPrint.push("-".repeat(ticketWidth) + "\x0A");
    dataToPrint.push("\x1B" + "\x61" + "\x32");
    dataToPrint.push("\x1B" + "\x21" + "\x08");
    dataToPrint.push(formatLine("TOTAL: ", formatCurrencyForTicket(ticketData.venta.total)));
    dataToPrint.push("\x1B" + "\x21" + "\x00");
    dataToPrint.push("\x0A");
    dataToPrint.push("\x1B" + "\x61" + "\x31");
    dataToPrint.push(removeAccents("¡Gracias por su compra!") + "\x0A");
    dataToPrint.push("\x0A" + "\x0A" + "\x0A");
    dataToPrint.push("\x1D" + "\x56" + "\x41" + "\x03");
    try {
      await qz.print(config, dataToPrint);
      showToast("Ticket enviado a la impresora.", "success");
    } catch (err) {
      console.error("Error al imprimir:", err);
      showToast("Error al enviar el ticket a la impresora.", "error");
    }
  }

  async function fetchCashCut() {
    const date = cashCutDateInput.value;
    if (!date) {
      showToast("Por favor, seleccione una fecha para el corte de caja.", "error");
      return;
    }
    cashCutResultsContainer.innerHTML = `<p class="text-[var(--color-text-secondary)] col-span-full">Calculando corte de caja...</p>`;
    let urlParams = `date=${date}`;
    if (userFilterSelect) {
      urlParams += `&user_id=${userFilterSelect.value}`;
    }
    try {
      const initialCashResponse = await fetch(`${BASE_URL}/getMontoApertura?date=${date}`);
      const initialCashResult = await initialCashResponse.json();
      if (initialCashResult.success) {
        currentInitialCash = parseFloat(initialCashResult.monto_inicial || 0);
        initialCashInput.value = currentInitialCash.toFixed(2);
        if (currentInitialCash > 0) {
          initialCashInput.readOnly = true;
          initialCashInput.classList.add('opacity-75', 'cursor-not-allowed');
        } else {
          initialCashInput.readOnly = false;
          initialCashInput.classList.remove('opacity-75', 'cursor-not-allowed');
        }
      } else {
        showToast(initialCashResult.message || 'Error al obtener monto de apertura de caja.', 'error');
        currentInitialCash = 0;
        initialCashInput.value = '0.00';
        initialCashInput.readOnly = false;
        initialCashInput.classList.remove('opacity-75', 'cursor-not-allowed');
      }
      const response = await fetch(`${BASE_URL}/getCashCut?${urlParams}`);
      const result = await response.json();
      if (result.success) {
        currentCashCutData = result.data;
        renderCashCut(currentCashCutData, date, currentInitialCash);
      } else {
        cashCutResultsContainer.innerHTML = `<p class="text-red-500 col-span-full">${result.message}</p>`;
        currentCashCutData = null;
      }
    } catch (error) {
      console.error("Error fetching cash cut or initial cash:", error);
      cashCutResultsContainer.innerHTML = `<p class="text-red-500 col-span-full">No se pudo conectar con el servidor para el corte de caja.</p>`;
      currentCashCutData = null;
      currentInitialCash = 0;
      initialCashInput.value = '0.00';
      initialCashInput.readOnly = false;
      initialCashInput.classList.remove('opacity-75', 'cursor-not-allowed');
    }
  }

  async function fetchDetailedData(endpoint, date) {
    let urlParams = `date=${date}`;
    if (userFilterSelect) {
      urlParams += `&user_id=${userFilterSelect.value}`;
    }
    try {
      const response = await fetch(`${endpoint}?${urlParams}`);
      const result = await response.json();
      return result.success ? result.data : [];
    } catch (error) {
      console.error(`Error fetching detailed data from ${endpoint}:`, error);
      return [];
    }
  }

  async function cancelSale(saleId) {
    const confirmed = await showConfirm(`¿Está seguro de que desea CANCELAR la venta #${saleId.toString().padStart(6, "0")}?`);
    if (!confirmed) {
      showToast("Cancelación de venta abortada.", "info");
      return;
    }
    try {
      const response = await fetch(`${BASE_URL}/cancelSale`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_venta: saleId })
      });
      const result = await response.json();
      if (result.success) {
        showToast(result.message, "success");
        if (ventasDataTable) { ventasDataTable.ajax.reload(null, false); }
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      console.error("Error cancelling sale:", error);
      showToast("Error de conexión al cancelar la venta.", "error");
    }
  }

  async function handlePrintTicket(saleId) {
    try {
      const response = await fetch(`${BASE_URL}/getTicketDetails?id=${saleId}`);
      const result = await response.json();
      if (result.success) {
        printTicket(configuredPrinter, result.data);
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      console.error("Error fetching ticket details:", error);
      showToast("Error al obtener los detalles del ticket para imprimir.", "error");
    }
  }

  function escapeHtml(str = "") {
    return String(str).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
  }

  function formatCurrencyMXN(value) {
    return "$" + parseFloat(value || 0).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function buildTicketHtml(ticketData) {
    const venta = ticketData.venta || {};
    const items = ticketData.items || [];
    const itemsRows = items.map(it => `
      <tr>
        <td class="qty">${escapeHtml(String(it.cantidad))}</td>
        <td class="desc">${escapeHtml(it.producto_nombre || "")}${it.sku ? `<div class="sku">SKU: ${escapeHtml(it.sku)}</div>` : ""}</td>
        <td class="monto">${formatCurrencyMXN(it.subtotal)}</td>
      </tr>`).join("");
    return `
    <div class="ticket">
      <h1>${escapeHtml(venta.sucursal_nombre || "")}</h1>
      <div class="sub">${escapeHtml(venta.sucursal_direccion || "")}<br>Tel: ${escapeHtml(venta.sucursal_telefono || "")}</div>
      <hr>
      <div class="row"><span>Ticket:</span><span>#${String(venta.id || 0).padStart(6, "0")}</span></div>
      <div class="row"><span>Fecha:</span><span>${new Date(venta.fecha).toLocaleString("es-MX")}</span></div>
      <div class="row"><span>Cliente:</span><span>${escapeHtml(venta.cliente || "")}</span></div>
      <div class="row"><span>Vendedor:</span><span>${escapeHtml(venta.vendedor || "")}</span></div>
      <hr>
      <table class="items">
        <thead><tr><th class="qty">Cant</th><th class="desc">Descripción</th><th class="monto">Total</th></tr></thead>
        <tbody>${itemsRows || `<tr><td colspan="3" class="center">Sin artículos</td></tr>`}</tbody>
      </table>
      <hr>
      <div class="row total"><span>TOTAL</span><span>${formatCurrencyMXN(venta.total)}</span></div>
      <p class="center gracias">¡Gracias por su compra!</p>
    </div>`;
  }

  function openPrintDialogWithTicket(html, ventaId) {
    const w = window.open("", "PRINT", "width=420,height=640");
    if (!w) {
      showToast("Popup bloqueado. Permite ventanas emergentes para imprimir.", "error");
      return;
    }
    w.document.write(`
    <!doctype html><html><head><meta charset="utf-8"><title>Ticket #${String(ventaId).padStart(6, "0")}</title><style>@page{size:58mm auto;margin:0;}@media print{html,body{margin:0;padding:0;}}html,body{background:#fff;}.ticket{width:58mm;box-sizing:border-box;padding:4mm 3mm;font-family:Tahoma,Arial,Helvetica,sans-serif;font-size:12px;color:#000;font-weight:600;text-rendering:optimizeLegibility;}h1{margin:0 0 2px 0;font-family:"Arial Black",Tahoma,Arial,Helvetica,sans-serif;font-size:15px;font-weight:900;text-align:center;letter-spacing:.2px;text-transform:uppercase;text-shadow:0 0 .3px #000,0 0 .3px #000;-webkit-text-stroke:.15px #000;}.sub{text-align:center;margin-bottom:6px;}.row{display:flex;justify-content:space-between;gap:8px;}.total{font-weight:700;font-size:13px;}hr{border:0;border-top:1px dashed #000;margin:6px 0;}.center{text-align:center;}.gracias{margin-top:8px;}table.items{width:100%;border-collapse:collapse;}table.items th{text-align:left;font-weight:700;border-bottom:1px solid #000;padding-bottom:2px;}table.items td{vertical-align:top;}.qty{width:10mm;white-space:nowrap;}.desc{width:auto;padding:0 4px;}.desc .sku{font-size:11px;margin-top:2px;opacity:.8;}.monto{text-align:right;white-space:nowrap;width:18mm;}</style></head><body>${html}<script>window.onload=function(){try{window.focus();window.print();}catch(e){}};<\/script></body></html>`);
    w.document.close();
  }

  async function handlePrintTicketViaDialog(saleId) {
    try {
      const resp = await fetch(`${BASE_URL}/getTicketDetails?id=${saleId}`);
      const result = await resp.json();
      if (!result.success) {
        showToast(result.message || "No se pudo obtener el ticket.", "error");
        return;
      }
      const html = buildTicketHtml(result.data);
      openPrintDialogWithTicket(html, saleId);
    } catch (err) {
      console.error("Error en impresión (Windows):", err);
      showToast("Error al preparar el ticket para impresión.", "error");
    }
  }

  function handleViewPdf(saleId) {
    const pdfUrl = `${BASE_URL}/generateQuote?id=${saleId}`;
    window.open(pdfUrl, '_blank');
  }

  function renderCashCut(data, date, initialCash) {
    const formatCurrency = (value) => `$${parseFloat(value || 0).toFixed(2)}`;
    const totalIngresosEfectivo = parseFloat(data.ventas_efectivo || 0) + parseFloat(data.abonos_clientes || 0);
    const balanceFinal = initialCash + totalIngresosEfectivo - parseFloat(data.total_gastos || 0);

    cashCutResultsContainer.innerHTML = `
            <div class="bg-[var(--color-bg-primary)] p-4 rounded-lg shadow-inner flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-3 flex items-center"><i class="fas fa-arrow-alt-circle-down text-green-400 mr-2"></i> Ingresos</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center"><span>Caja Inicial:</span><span class="font-mono text-blue-300">${formatCurrency(initialCash)}</span></div>
                        <div class="flex justify-between items-center"><span>Ventas en Efectivo:</span><span class="font-mono text-green-300">${formatCurrency(data.ventas_efectivo)}</span></div>
                        <div class="flex flex-col">
                            <div class="flex justify-between items-center cursor-pointer hover:text-gray-200" id="toggle-abonos"><span>Abonos de Clientes:</span><span class="font-mono text-green-300">${formatCurrency(data.abonos_clientes)} <i class="fas fa-chevron-down ml-2 transition-transform duration-300"></i></span></div>
                            <div id="abonos-detail" class="mt-2 pl-4 text-xs text-[var(--color-text-secondary)] hidden"><p>Cargando abonos...</p></div>
                        </div>
                        <hr class="border-[var(--color-border)] my-2">
                        <div class="flex justify-between items-center font-bold text-base"><span>Total Ingresos en Caja:</span><span class="font-mono text-green-400">${formatCurrency(totalIngresosEfectivo)}</span></div>
                    </div>
                </div>
            </div>
            <div class="bg-[var(--color-bg-primary)] p-4 rounded-lg shadow-inner flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-3 flex items-center"><i class="fas fa-arrow-alt-circle-up text-red-400 mr-2"></i> Egresos</h3>
                    <div class="space-y-3 text-sm mb-4">
                        <div class="flex flex-col">
                            <div class="flex justify-between items-center cursor-pointer hover:text-gray-200" id="toggle-gastos"><span>Total de Gastos:</span><span class="font-mono text-red-300">${formatCurrency(data.total_gastos)} <i class="fas fa-chevron-down ml-2 transition-transform duration-300"></i></span></div>
                            <div id="gastos-detail" class="mt-2 pl-4 text-xs text-[var(--color-text-secondary)] hidden"><p>Cargando gastos...</p></div>
                        </div>
                    </div>
                    <hr class="border-[var(--color-border)] my-2">
                    <div class="flex justify-between items-center pt-4 text-lg font-bold"><span class="text-[var(--color-text-primary)]">Balance Final en Caja:</span><span class="font-mono ${balanceFinal >= 0 ? "text-green-400" : "text-red-400"}">${formatCurrency(balanceFinal)}</span></div>
                </div>
            </div>
            <div class="col-span-full bg-[var(--color-bg-primary)] p-4 rounded-lg shadow-inner mt-4">
                <h3 class="text-md font-semibold text-[var(--color-text-secondary)] mb-3 flex items-center"><i class="fas fa-info-circle text-blue-400 mr-2"></i> Otros Totales (Informativos)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-2 text-xs">
                    <div class="flex justify-between items-center"><span>Total Ventas (Todos los métodos):</span><span class="font-mono text-[var(--color-text-secondary)]">${formatCurrency(data.total_ventas)}</span></div>
                    <div class="flex justify-between items-center"><span>Ventas con Tarjeta:</span><span class="font-mono text-[var(--color-text-secondary)]">${formatCurrency(data.ventas_tarjeta)}</span></div>
                    <div class="flex justify-between items-center"><span>Ventas por Transferencia:</span><span class="font-mono text-[var(--color-text-secondary)]">${formatCurrency(data.ventas_transferencia)}</span></div>
                    <div class="flex justify-between items-center"><span>Ventas a Crédito:</span><span class="font-mono text-[var(--color-text-secondary)]">${formatCurrency(data.ventas_credito)}</span></div>
                </div>
            </div>`;

    const toggleGastos = document.getElementById("toggle-gastos");
    const gastosDetail = document.getElementById("gastos-detail");
    const toggleAbonos = document.getElementById("toggle-abonos");
    const abonosDetail = document.getElementById("abonos-detail");

    if (toggleGastos && gastosDetail) {
      toggleGastos.addEventListener("click", async () => {
        gastosDetail.classList.toggle("hidden");
        toggleGastos.querySelector("i").classList.toggle("rotate-180");
        if (!gastosDetail.classList.contains("hidden") && gastosDetail.dataset.loaded !== "true") {
          const expenses = await fetchDetailedData(`${BASE_URL}/getDetailedExpenses`, date);
          gastosDetail.innerHTML = "";
          if (expenses.length > 0) {
            expenses.forEach((exp) => {
              const p = document.createElement("p");
              p.className = "flex justify-between items-center py-1 border-b border-[var(--color-border)] last:border-b-0";
              p.innerHTML = `<span>${new Date(exp.fecha).toLocaleDateString('es-MX')} - ${exp.descripcion.substring(0, 30)}${exp.descripcion.length > 30 ? "..." : ""}</span><span class="font-mono text-red-300">${formatCurrency(exp.monto)}</span>`;
              gastosDetail.appendChild(p);
            });
          } else {
            gastosDetail.innerHTML = '<p class="text-center py-2">No se encontraron gastos.</p>';
          }
          gastosDetail.dataset.loaded = "true";
        }
      });
    }

    if (toggleAbonos && abonosDetail) {
      toggleAbonos.addEventListener("click", async () => {
        abonosDetail.classList.toggle("hidden");
        toggleAbonos.querySelector("i").classList.toggle("rotate-180");
        if (!abonosDetail.classList.contains("hidden") && abonosDetail.dataset.loaded !== "true") {
          const payments = await fetchDetailedData(`${BASE_URL}/getDetailedClientPayments`, date);
          abonosDetail.innerHTML = "";
          if (payments.length > 0) {
            payments.forEach((pay) => {
              const p = document.createElement("p");
              p.className = "flex justify-between items-center py-1 border-b border-[var(--color-border)] last:border-b-0";
              p.innerHTML = `<span>${new Date(pay.fecha).toLocaleString('es-MX')} - ${pay.cliente_nombre} (${pay.metodo_pago})</span><span class="font-mono text-green-300">${formatCurrency(pay.monto)}</span>`;
              abonosDetail.appendChild(p);
            });
          } else {
            abonosDetail.innerHTML = '<p class="text-center py-2">No se encontraron abonos.</p>';
          }
          abonosDetail.dataset.loaded = "true";
        }
      });
    }
  }

  async function printCashCutReport() {
    if (!configuredPrinter) {
      showToast("Impresora no configurada.", "error");
      return;
    }
    if (!currentCashCutData) {
      showToast("No hay datos de corte de caja para imprimir.", "error");
      return;
    }
    const config = qz.configs.create(configuredPrinter);
    const dateForPrint = cashCutDateInput.value;
    const detailedExpenses = await fetchDetailedData(`${BASE_URL}/getDetailedExpenses`, dateForPrint);
    const detailedClientPayments = await fetchDetailedData(`${BASE_URL}/getDetailedClientPayments`, dateForPrint);
    const formatCurrency = (value) => `$${parseFloat(value || 0).toFixed(2)}`;
    const formatInputDateToDDMMYYYY = (dateString) => {
      const parts = dateString.split('-');
      return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : dateString;
    };
    const formatDBDateTimeToDDMMYYYYHHMM = (dateString) => {
      const date = new Date(dateString);
      const day = date.getDate().toString().padStart(2, '0');
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const year = date.getFullYear();
      const hours = date.getHours().toString().padStart(2, '0');
      const minutes = date.getMinutes().toString().padStart(2, '0');
      return `${day}/${month}/${year} ${hours}:${minutes}`;
    };
    const totalIngresosEfectivo = parseFloat(currentCashCutData.ventas_efectivo || 0) + parseFloat(currentCashCutData.abonos_clientes || 0);
    const balanceFinal = currentInitialCash + totalIngresosEfectivo - parseFloat(currentCashCutData.total_gastos || 0);
    let dataToPrint = [
      "\x1B@","\x1Bt\x11","\x1Ba1","\x1B!\x10",removeAccents("CORTE DE CAJA") + "\x0A","\x1B!\x00",
      "\x0A",
      "\x1Ba0",
      formatLine("Fecha del Corte:", formatInputDateToDDMMYYYY(dateForPrint)),
      formatLine("Generado el:", formatDBDateTimeToDDMMYYYYHHMM(new Date())),
      "-".repeat(ticketWidth) + "\x0A",
      "\x1B!\x08",formatLine("INGRESOS"),"\x1B!\x00",
      formatLine(" (+) Caja Inicial:", formatCurrency(currentInitialCash)),
      formatLine(" (+) Ventas en Efectivo:", formatCurrency(currentCashCutData.ventas_efectivo)),
      formatLine(" (+) Abonos de Clientes:", formatCurrency(currentCashCutData.abonos_clientes)),
      "-".repeat(ticketWidth) + "\x0A",
      "\x1B!\x08",formatLine("Total Ingresos en Caja:", formatCurrency(totalIngresosEfectivo)),"\x1B!\x00","\x0A",
      "\x1B!\x08",formatLine("EGRESOS"),"\x1B!\x00",
      formatLine(" (-) Total de Gastos:", formatCurrency(currentCashCutData.total_gastos)),"\x0A",
      "-".repeat(ticketWidth) + "\x0A",
      "\x1B!\x10",formatLine("BALANCE FINAL EN CAJA:", formatCurrency(balanceFinal)),"\x1B!\x00","\x0A",
      "=".repeat(ticketWidth) + "\x0A",
      "\x1Ba1","\x1B!\x08",removeAccents("DETALLE DE GASTOS") + "\x0A","\x1B!\x00","\x1Ba0",
      "=".repeat(ticketWidth) + "\x0A",
      formatLine("Fecha      Descripcion", "Monto"),
      "-".repeat(ticketWidth) + "\x0A",
    ];
    if (detailedExpenses.length > 0) {
      detailedExpenses.forEach((exp) => {
        const desc = exp.descripcion.substring(0, ticketWidth - 10 - formatCurrency(exp.monto).length - 2);
        dataToPrint.push(formatLine(`${formatInputDateToDDMMYYYY(exp.fecha).padEnd(10)} ${removeAccents(desc)}`, formatCurrency(exp.monto)));
      });
    } else {
      dataToPrint.push(formatCentered("No se encontraron gastos."));
    }
    dataToPrint.push("-".repeat(ticketWidth) + "\x0A");
    dataToPrint.push(
      "=".repeat(ticketWidth) + "\x0A","\x1Ba1","\x1B!\x08",removeAccents("DETALLE DE ABONOS") + "\x0A","\x1B!\x00","\x1Ba0",
      "=".repeat(ticketWidth) + "\x0A",
      formatLine("Fecha/Hora Cliente (Metodo)", "Monto"),
      "-".repeat(ticketWidth) + "\x0A"
    );
    if (detailedClientPayments.length > 0) {
      detailedClientPayments.forEach((pay) => {
        const clientInfo = `${pay.cliente_nombre} (${pay.metodo_pago})`;
        const clientInfoTruncated = clientInfo.substring(0, ticketWidth - 18 - formatCurrency(pay.monto).length - 2);
        dataToPrint.push(formatLine(`${formatDBDateTimeToDDMMYYYYHHMM(pay.fecha).substring(0, 16).padEnd(16)} ${removeAccents(clientInfoTruncated)}`, formatCurrency(pay.monto)));
      });
    } else {
      dataToPrint.push(formatCentered("No se encontraron abonos."));
    }
    dataToPrint.push("-".repeat(ticketWidth) + "\x0A");
    dataToPrint.push(
      "=".repeat(ticketWidth) + "\x0A","\x1Ba1","\x1B!\x08",removeAccents("OTROS TOTALES") + "\x0A","\x1B!\x00","\x1Ba0",
      formatLine("Total Ventas (Todos):", formatCurrency(currentCashCutData.total_ventas)),
      formatLine("Ventas con Tarjeta:", formatCurrency(currentCashCutData.ventas_tarjeta)),
      formatLine("Ventas por Transferencia:", formatCurrency(currentCashCutData.ventas_transferencia)),
      formatLine("Ventas a Credito:", formatCurrency(currentCashCutData.ventas_credito)),
      "\x0A","\x1Ba1",removeAccents("¡Reporte generado!") + "\x0A","\x0A\x0A\x0A","\x1DVA\x03"
    );
    try {
      await qz.print(config, dataToPrint);
      showToast("Corte de caja enviado a la impresora.", "success");
    } catch (err) {
      console.error("Error al imprimir el corte de caja:", err);
      showToast("Error al enviar el corte de caja a la impresora.", "error");
    }
  }

  generateCashCutBtn.addEventListener("click", fetchCashCut);
  printCashCutBtn.addEventListener("click", printCashCutReport);
  if (userFilterSelect) {
    userFilterSelect.addEventListener('change', fetchCashCut);
  }

  const now = new Date();
  const todayFormatted = `${now.getFullYear()}-${(now.getMonth() + 1).toString().padStart(2, "0")}-${now.getDate().toString().padStart(2, "0")}`;
  
  // CORREGIDO: Se verifica si los elementos existen antes de asignarles valor.
  const startDateInputRef = document.getElementById("start-date");
  const endDateInputRef = document.getElementById("end-date");
  if(startDateInputRef) startDateInputRef.value = todayFormatted;
  if(endDateInputRef) endDateInputRef.value = todayFormatted;
  
  cashCutDateInput.value = todayFormatted;

  fetchCashCut();
  loadUsersForFilter();
  fetchPrinterConfig();
  initSalesDataTable();
});
