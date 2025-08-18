// Archivo: /public/js/reporte_global.js
$(document).ready(function () {
    const apiURL = `${BASE_URL}/getGlobalSalesReport`;

    // --- INICIO DE LA CORRECCIÓN ---
    // Se mueve la inicialización del plugin DENTRO del bloque ready,
    // para asegurar que todas las librerías (jQuery, DataTables, Moment) ya se cargaron.
    $.fn.dataTable.moment('DD/MM/YYYY, h:mm a');
    // --- FIN DE LA CORRECCIÓN ---

    // 1. INICIALIZACIÓN DE DATERANGEPICKER (Sin cambios)
    const dateRangeInput = $('#daterange-filter');
    dateRangeInput.daterangepicker({
        opens: 'left',
        autoUpdateInput: false,
        locale: {
            "format": "DD/MM/YYYY", "separator": " - ", "applyLabel": "Aplicar", "cancelLabel": "Limpiar",
            "fromLabel": "Desde", "toLabel": "Hasta", "customRangeLabel": "Personalizado", "weekLabel": "S",
            "daysOfWeek": ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
            "monthNames": ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
            "firstDay": 1
        },
        ranges: {
            'Hoy': [moment(), moment()], 'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Últimos 7 días': [moment().subtract(6, 'days'), moment()], 'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este Mes': [moment().startOf('month'), moment().endOf('month')],
            'Mes Pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    // 2. FILTRO PERSONALIZADO PARA DATATABLES (Sin cambios)
    $.fn.dataTable.ext.search.push(
        function (settings, data, dataIndex) {
            const dateRangeValue = dateRangeInput.val();
            if (!dateRangeValue) {
                return true;
            }
            const picker = dateRangeInput.data('daterangepicker');
            const minDate = picker.startDate.clone().startOf('day');
            const maxDate = picker.endDate.clone().endOf('day');
            const dateStr = data[0];
            const dateParts = dateStr.split(' ')[0].split('/');
            const rowDate = moment(dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0], "YYYY-MM-DD");
            return rowDate.isBetween(minDate, maxDate, 'day', '[]');
        }
    );

    const table = $('#global-sales-table').DataTable({
        "processing": true,
        "ajax": { "url": apiURL, "dataSrc": "data" },
        "columns": [
            { "data": "fecha", "render": (data) => moment(data).format('DD/MM/YYYY, h:mm a') },
            { "data": "id", "className": "ticket-id-cell", "render": (data) => '#' + String(data).padStart(6, '0') },
            { "data": "sucursal_nombre" }, { "data": "cliente_nombre" }, { "data": "usuario_nombre" },
            { "data": "total", "className": "text-right" },
            { "data": "estado", "render": (data) => `<span class="${data === 'Completada' ? 'text-green-400' : 'text-red-500'} font-semibold">${data}</span>` },
            { "data": "id", "className": "text-center", "orderable": false, "searchable": false, "render": (data) => `<button class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-1 px-3 rounded-lg text-xs view-pdf-btn" data-id="${data}" title="Ver PDF"><i class="fas fa-file-pdf"></i></button>` }
        ],
        "columnDefs": [{ "targets": 5, "render": (data) => `$${parseFloat(data).toFixed(2)}` }],
        "order": [[0, 'desc']],
        "language": { "url": "js/es.json" },
        "responsive": true,
        "footerCallback": function (row, data, start, end, display) {
            const api = this.api();

            const floatVal = (i) => {
                if (typeof i === 'string') {
                    // Limpia el valor de cualquier caracter que no sea un dígito o un punto.
                    return parseFloat(i.replace(/[^\d.-]/g, '')) || 0;
                }
                return typeof i === 'number' ? i : 0;
            };

            // Calcula el total solo para las filas visibles que están "Completadas"
            let totalCompletadas = 0;
            api.rows({ page: 'current' }).every(function () {
                const rowData = this.data();
                // Verifica que el estado sea "Completada" antes de sumar
                if (rowData.estado === 'Completada') {
                    totalCompletadas += floatVal(rowData.total);
                }
            });

            // Actualiza el pie de la tabla con el nuevo total calculado
            $(api.column(5).footer()).html(`$${totalCompletadas.toFixed(2)}`);
        },
        dom:
            "<'dt-top-controls'<'left-controls'l><'center-controls'B><'right-controls'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'dt-bottom-controls'<'left-controls'i><'right-controls'p>>",
        buttons: [
            /* {
                extend: 'copyHtml5',
                text: '<i class="far fa-copy"></i> Copiar',
                titleAttr: 'Copiar a portapapeles',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }, */
            {
                extend: 'excelHtml5',
                text: '<i class="far fa-file-excel"></i> Excel',
                titleAttr: 'Exportar a Excel',
                title: 'Reporte_Global_de_Ventas',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="far fa-file-pdf"></i> PDF',
                titleAttr: 'Exportar a PDF',
                title: 'Reporte Global de Ventas',
                orientation: 'landscape', // Hoja horizontal para que quepan las columnas
                pageSize: 'A4',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimir',
                titleAttr: 'Imprimir tabla',
                title: 'Reporte Global de Ventas',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }
        ],
        "lengthMenu": [
            [10, 25, 50, -1], // Valores internos para DataTables
            [10, 25, 50, "Todos"] // Texto que verá el usuario
        ]
    });

    // 3. EVENTOS DEL DATERANGEPICKER (Sin cambios)
    dateRangeInput.on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        table.draw();
    });
    dateRangeInput.on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
        table.draw();
    });

    // Delegación de eventos para los botones de PDF (sin cambios)
    $('#global-sales-table tbody').on('click', '.view-pdf-btn', function () {
        const saleId = $(this).data('id');
        window.open(`${BASE_URL}/generateQuote?id=${saleId}`, '_blank');
    });
});