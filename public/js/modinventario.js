document.addEventListener('DOMContentLoaded', function () {
    // --- Referencias a elementos del DOM ---
    const addProductBtn = document.getElementById('add-product-btn');
    const productModal = document.getElementById('product-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const productForm = document.getElementById('product-form');
    const modalTitle = document.getElementById('modal-title');
    const categoriaSelect = document.getElementById('id_categoria');
    const marcaSelect = document.getElementById('id_marca');
    const barcodesContainer = document.getElementById('barcodes-container');
    const addBarcodeBtn = document.getElementById('add-barcode-btn');
    const cloneSection = document.getElementById('clone-section');
    const toggleCloneBtn = document.getElementById('toggle-clone-btn');
    const cloneControls = document.getElementById('clone-controls');
    const cloneSourceProductSelect = document.getElementById('clone-source-product');
    const loadCloneDataBtn = document.getElementById('load-clone-data-btn');
    const adjustStockModal = document.getElementById('adjust-stock-modal');
    const closeAdjustModalBtn = document.getElementById('close-adjust-modal-btn');
    const cancelAdjustBtn = document.getElementById('cancel-adjust-btn');
    const confirmAdjustBtn = document.getElementById('confirm-adjust-btn');
    const adjustModalTitle = document.getElementById('adjust-modal-title');
    const adjustProductName = document.getElementById('adjust-product-name');
    const adjustProductId = document.getElementById('adjust-product-id');
    const adjustAction = document.getElementById('adjust-action');
    const adjustCurrentStockValue = document.getElementById('adjust-current-stock-value');
    const adjustCurrentStockDisplay = document.getElementById('adjust-current-stock-display');
    const adjustQuantityInput = document.getElementById('adjust-quantity');
    const adjustStockReasonInput = document.getElementById('adjust-stock-reason');
    const adjustQuantityLabel = document.getElementById('adjust-quantity-label');
    const branchSelector = document.getElementById('adjust-branch-select');
    const manageCategoriesBtn = document.getElementById('manage-categories-btn');
    const categoryModal = document.getElementById('category-modal');
    const closeCategoryModalBtn = document.getElementById('close-category-modal-btn');
    const categoryForm = document.getElementById('category-form');
    const categoryIdInput = document.getElementById('category-id');
    const categoryNameInput = document.getElementById('category-name');
    const categoryDescriptionInput = document.getElementById('category-description');
    const saveCategoryBtn = document.getElementById('save-category-btn');
    const cancelCategoryEditBtn = document.getElementById('cancel-category-edit-btn');
    const categoriesTableBody = document.getElementById('categories-table-body');
    const manageBrandsBtn = document.getElementById('manage-brands-btn');
    const brandModal = document.getElementById('brand-modal');
    const closeBrandModalBtn = document.getElementById('close-brand-modal-btn');
    const brandForm = document.getElementById('brand-form');
    const brandIdInput = document.getElementById('brand-id');
    const brandNameInput = document.getElementById('brand-name');
    const saveBrandBtn = document.getElementById('save-brand-btn');
    const cancelBrandEditBtn = document.getElementById('cancel-brand-edit-btn');
    const brandsTableBody = document.getElementById('brands-table-body');
    const barcodeModal = document.getElementById('barcode-modal');
    const closeBarcodeModalBtn = document.getElementById('close-barcode-modal-btn');
    const barcodeProductName = document.getElementById('barcode-product-name');
    const barcodeDataSelect = document.getElementById('barcode-data-select');
    const barcodeFormatSelect = document.getElementById('barcode-format-select');
    const generateBarcodeBtn = document.getElementById('generate-barcode-btn');
    const barcodeFeedback = document.getElementById('barcode-feedback');
    const barcodeSvg = document.getElementById('barcode-svg');
    const printBarcodeBtn = document.getElementById('print-barcode-btn');
    const barcodePrintArea = document.getElementById('barcode-print-area');

    let productsDataTable;
    let historyDataTable;
    const USER_ROLE = document.body.dataset.userRole || 'user';
    let costoAn, precioMenudeoAn, precioMayoreoAn;
    let dz;
    let imagesTouched = false;

    const showModal = (modalElement) => {
        if (modalElement) modalElement.classList.remove('hidden');
    };
    const hideModal = (modalElement) => {
        if (modalElement) modalElement.classList.add('hidden');
    };

    function initializeDropzone() {
        try { Dropzone.autoDiscover = false; } catch (e) { }
        const dzElement = document.getElementById('product-dropzone');
        if (!dzElement) return;

        if (dzElement.dropzone) {
            try { dzElement.dropzone.destroy(); } catch (e) { }
        }

        dz = new Dropzone(dzElement, {
            url: `${(typeof BASE_URL !== 'undefined' ? BASE_URL : '')}/uploadProductImage`,
            paramName: 'product_images',
            maxFilesize: 10,
            acceptedFiles: 'image/*',
            uploadMultiple: false,
            addRemoveLinks: true,
            dictDefaultMessage: 'Arrastra y suelta tus imágenes o haz clic para seleccionar',
            dictRemoveFile: "x",
            init: function () {
                this.on('success', function (file, response) {
                    try {
                        if (typeof response === 'object' && response !== null) {
                            response = response.fileName || response.name || '';
                        }
                        if (typeof response === 'string') response = response.trim();
                    } catch (e) { }
                    file.serverId = response || null;   // nombre en /img/temp
                    file.isExisting = false;
                });
                this.on('removedfile', function (file) {
                    if (file.serverId && !file.isExisting) {
                        fetch(`${(typeof BASE_URL !== 'undefined' ? BASE_URL : '')}/deleteProductImage`, { method: 'POST', body: file.serverId });
                    }
                });
            }
        });

        // Marcar cambios cuando haya actividad
        if (dz) {
            dz.on('success', () => { imagesTouched = true; });
            dz.on('removedfile', () => { imagesTouched = true; });
            dz.on('queuecomplete', () => { imagesTouched = true; });
        }
    }

    function initializeProductsDataTable() {
        productsDataTable = $('#productsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: `${BASE_URL}/getProductsServerSide`,
                type: 'POST'
            },
            columns: [
                { data: 'sku' },
                { data: 'nombre', className: 'font-semibold' },
                { data: 'codigos_barras', defaultContent: 'N/A' },
                { data: 'categoria_nombre', defaultContent: 'N/A' },
                {
                    data: 'stock',
                    className: 'text-center',
                    orderable: false,
                    render: function (data, type, row) {
                        const stock = data || 0;
                        return `
                        <div class="flex items-center justify-center space-x-2">
                            <button class="adjust-stock-btn text-red-400 hover:text-red-300 font-bold text-lg" data-id="${row.id}" data-name="${row.nombre}" data-currentstock="${stock}" data-action="decrease" title="Restar Stock">-</button>
                            <input type="number" value="${stock}" class="stock-adjust-input bg-gray-700 text-white rounded text-center text-sm" readonly>
                            <button class="adjust-stock-btn text-green-400 hover:text-green-300 font-bold text-lg" data-id="${row.id}" data-name="${row.nombre}" data-currentstock="${stock}" data-action="increase" title="Añadir Stock">+</button>
                        </div>`;
                    }
                },
                {
                    data: 'precio_menudeo',
                    className: 'text-right font-mono',
                    render: function (data, type, row) {
                        if (type === 'display') {
                            const number = parseFloat(data) || 0;
                            const formattedCurrency = new Intl.NumberFormat('es-MX', {
                                style: 'currency',
                                currency: 'MXN'
                            }).format(number);

                            const colorClass = 'text-white-400';
                            return `<span class="font-mono ${colorClass}">${formattedCurrency}</span>`;
                        }
                        return data;
                    }
                },
                {
                    data: 'id',
                    className: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: (data) => `
                        <div class="flex items-center justify-center flex-nowrap">
                            <button data-id="${data}" class="edit-btn text-blue-400 hover:text-blue-300 px-2" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                            <button data-id="${data}" class="barcode-btn text-teal-400 hover:text-teal-300 px-2" title="Generar Código de Barras"><i class="fas fa-barcode"></i></button>
                            <button data-id="${data}" class="delete-btn text-red-500 hover:text-red-400 px-2" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                        </div>
                        `
                }
            ],
            responsive: true,
            paging: true,
            searching: true,
            info: true,
            lengthChange: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            language: {
                search: "Buscar:",
                searchPlaceholder: "Buscar producto...",
                zeroRecords: "No se encontraron productos",
                emptyTable: "No hay productos",
                info: "Mostrando _START_ a _END_ de _TOTAL_ productos",
                infoEmpty: "Mostrando 0 a 0 de 0 productos",
                paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" },
                processing: "Procesando..."
            },
            dom: '<"flex flex-col md:flex-row justify-between items-center mb-4 gap-4" <"flex items-center" l> <"ml-auto" f> > rt <"flex justify-between items-center mt-4"ip>'
        });
    }

    function initializeHistoryDataTable() {
        historyDataTable = $('#historyTable').DataTable({
            ajax: { url: `${BASE_URL}/getInventoryMovements`, dataSrc: 'data' },
            columns: [
                { data: 'fecha', render: (data) => new Date(data).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' }) },
                { data: 'producto_nombre', className: 'font-semibold' },
                { data: 'tipo_movimiento', className: 'capitalize' },
                {
                    data: 'cantidad',
                    className: 'text-center font-mono',
                    render: (data, type, row) => {
                        if (row.stock_nuevo > row.stock_anterior) return `<span class="text-green-400">+${data}</span>`;
                        if (row.stock_nuevo < row.stock_anterior) return `<span class="text-red-400">-${data}</span>`;
                        return data;
                    }
                },
                { data: 'stock_anterior', className: 'text-center' },
                { data: 'stock_nuevo', className: 'text-center' },
                { data: 'motivo', defaultContent: 'N/A' },
                { data: 'usuario_nombre' }
            ],
            order: [[0, 'desc']],
            paging: true,
            responsive: true,
            searching: true,
            info: true,
            lengthChange: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            language: { search: "Buscar:", searchPlaceholder: "Buscar movimiento...", zeroRecords: "No se encontraron movimientos", emptyTable: "No hay movimientos", info: "Mostrando _START_ a _END_ de _TOTAL_ movimientos", infoEmpty: "Mostrando 0 a 0 de 0 movimientos", paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" } },
            dom: '<"flex flex-col md:flex-row justify-between items-center mb-4 gap-4" <"flex items-center" l> <"ml-auto" f> > rt <"flex justify-between items-center mt-4"ip>'
        });
    }

    function prepareNewProductForm() {
        productForm.reset();
        if (costoAn) costoAn.clear();
        if (precioMenudeoAn) precioMenudeoAn.clear();
        if (precioMayoreoAn) precioMayoreoAn.clear();
        document.getElementById('product-id').value = '';
        modalTitle.innerHTML = '<i class="fas fa-box-open mr-3"></i>Añadir Nuevo Producto';
        barcodesContainer.innerHTML = '';
        addBarcodeField();
        cloneSection.classList.remove('hidden');
        cloneControls.classList.add('hidden');
        populateCloneSelect();
        imagesTouched = true;
        if (dz) dz.removeAllFiles(true);
        showModal(productModal);
    }

    async function handleEditProduct(id) {
        imagesTouched = false;
        if (cloneSection) cloneSection.classList.add('hidden');
        try {
            const response = await fetch(`${BASE_URL}/getProduct?id=${id}`);
            const result = await response.json();

            if (!result.success) {
                showToast(`Error: ${result.message}`, 'error');
                return;
            }

            const product = result.data;
            document.getElementById('product-id').value = product.id;
            document.getElementById('nombre').value = product.nombre;
            document.getElementById('sku').value = product.sku;
            document.getElementById('id_categoria').value = product.id_categoria;
            document.getElementById('id_marca').value = product.id_marca;
            document.getElementById('stock').value = product.stock ?? 0;
            document.getElementById('stock_minimo').value = product.stock_minimo ?? 0;
            document.getElementById('descripcion').value = product.descripcion ?? '';

            if (costoAn) costoAn.set(product.costo ?? 0);
            if (precioMenudeoAn) precioMenudeoAn.set(product.precio_menudeo ?? 0);
            if (precioMayoreoAn) precioMayoreoAn.set(product.precio_mayoreo ?? 0);

            barcodesContainer.innerHTML = '';
            if (product.codigos_barras && Array.isArray(product.codigos_barras)) {
                product.codigos_barras.forEach(code => addBarcodeField(code));
            } else {
                addBarcodeField();
            }

            if (dz) dz.removeAllFiles(true);
            const imgs = Array.isArray(product.imagenes) ? product.imagenes : [];
            if (dz) {
                imgs.forEach(function (img) {
                    if (img && img.base64 && img.filename) {
                        const mockFile = { name: img.filename, size: 12345, accepted: true };
                        dz.emit('addedfile', mockFile);
                        dz.emit('thumbnail', mockFile, img.base64);
                        dz.emit('complete', mockFile);
                        mockFile.serverId = img.filename;
                        mockFile.isExisting = true;
                        dz.files.push(mockFile);
                    }
                });
            }

            modalTitle.innerHTML = '<i class="fas fa-pencil-alt mr-3"></i>Editar Producto';
            showModal(productModal);

        } catch (err) {
            console.error('Error en handleEditProduct:', err);
            showToast('No se pudieron obtener los datos del producto.', 'error');
        }
    }

    async function handleFormSubmit(event) {
        event.preventDefault();

        const formData = new FormData(productForm);
        formData.delete('product_images');
        const productData = Object.fromEntries(formData.entries());

        if (costoAn) productData.costo = costoAn.getNumericString();
        if (precioMenudeoAn) productData.precio_menudeo = precioMenudeoAn.getNumericString();
        if (precioMayoreoAn) productData.precio_mayoreo = precioMayoreoAn.getNumericString();

        const productId = document.getElementById('product-id').value;
        productData.id = productId || null;
        productData.stock = parseInt(productData.stock) || 0;
        productData.stock_minimo = parseInt(productData.stock_minimo) || 0;

        const barcodeInputs = barcodesContainer.querySelectorAll('.barcode-input');
        productData.codigos_barras = Array.from(barcodeInputs)
            .map(input => input.value.trim())
            .filter(Boolean);

        if (imagesTouched) {
            const files = dz ? dz.files : [];

            // --- INICIO DE LA DEPURACIÓN Y CORRECCIÓN ---
            console.log("--- INICIO DEPURACIÓN DE IMÁGENES ---");

            productData.imagenes = files.map((fileItem, index) => {
                console.log(`Procesando imagen #${index + 1}:`, fileItem);

                let filename = null;

                // Prioridad 1: Si es un archivo nuevo ya procesado, tendrá un serverId.
                if (fileItem.serverId) {
                    filename = fileItem.serverId;
                    console.log(`  > Tipo: Archivo Nuevo (ya subido). Nombre de archivo (serverId): ${filename}`);
                }
                // Prioridad 2: Si es un archivo existente (origin: 'local'),
                // usamos el método oficial getMetadata() para obtener el nombre de archivo.
                else if (fileItem.origin === 1 && fileItem.getMetadata('originalFilename')) {
                    filename = fileItem.getMetadata('originalFilename');
                    console.log(`  > Tipo: Archivo Existente. Nombre de archivo (metadata): ${filename}`);
                }
                // Prioridad 3: Como último recurso (un archivo nuevo que aún no se sube), usamos el nombre del archivo.
                else if (fileItem.file && fileItem.file.name) {
                    filename = fileItem.file.name;
                    console.log(`  > Tipo: Archivo Nuevo (no subido). Nombre de archivo (file.name): ${filename}`);
                } else {
                    console.log("  > No se pudo determinar el nombre del archivo para este item.");
                }

                return filename;

            }).filter(Boolean); // Elimina cualquier nulo que haya quedado.

            console.log("Lista final de nombres de archivo a enviar:", productData.imagenes);
            console.log("--- FIN DEPURACIÓN DE IMÁGENES ---");
            // --- FIN DE LA DEPURACIÓN Y CORRECCIÓN ---
        }

        const url = productId ? `${BASE_URL}/updateProduct` : `${BASE_URL}/createProduct`;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(productData)
            });
            const result = await response.json();
            if (result.success) {
                hideModal(productModal);
                productsDataTable.ajax.reload(null, false);
                if (historyDataTable) historyDataTable.ajax.reload(null, false);
                showToast(`Producto ${productId ? 'actualizado' : 'creado'} exitosamente.`, 'success');
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (e) {
            console.error("Error al guardar:", e);
            showToast('No se pudo conectar con el servidor.', 'error');
        }
    }


    function addBarcodeField(code = '') {
        const div = document.createElement('div');
        div.className = 'flex items-center mb-2';
        div.innerHTML = `
            <input type="text" class="barcode-input flex-grow bg-gray-700 text-white border border-gray-600 rounded-l-md p-2 focus:ring-blue-500 focus:border-blue-500" value="${code}" placeholder="Código de barras">
            <button type="button" class="remove-barcode-btn bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-3 rounded-r-md"><i class="fas fa-trash"></i></button>
        `;
        barcodesContainer.appendChild(div);
    }

    async function fetchCatalogs() {
        try {
            const [catResponse, marcaResponse] = await Promise.all([
                fetch(`${BASE_URL}/getCategorias`),
                fetch(`${BASE_URL}/getMarcas`)
            ]);
            const catResult = await catResponse.json();
            if (catResult.success) {
                populateSelect(categoriaSelect, catResult.data, 'Selecciona una categoría');
            }
            const marcaResult = await marcaResponse.json();
            if (marcaResult.success) {
                populateSelect(marcaSelect, marcaResult.data, 'Selecciona una marca');
            }
        } catch (error) {
            showToast('Error al cargar catálogos.', 'error');
        }
    }

    async function populateCloneSelect() {
        if (!cloneSourceProductSelect) return;
        try {
            const response = await fetch(`${BASE_URL}/getProductosParaPreciosEspeciales`);
            const result = await response.json();
            if (result.success) {
                cloneSourceProductSelect.innerHTML = '<option value="" disabled selected>Selecciona un producto para clonar...</option>';
                const sortedProducts = [...result.data].sort((a, b) => a.nombre.localeCompare(b.nombre));
                sortedProducts.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = `${product.nombre} (SKU: ${product.sku})`;
                    cloneSourceProductSelect.appendChild(option);
                });
            } else {
                cloneSourceProductSelect.innerHTML = '<option value="">Error al cargar productos</option>';
                showToast(result.message, 'error');
            }
        } catch (error) {
            cloneSourceProductSelect.innerHTML = '<option value="">Error de conexión</option>';
            showToast('No se pudo conectar para obtener la lista de productos.', 'error');
        }
    }

    function populateSelect(selectElement, data, defaultText) {
        if (!selectElement) return;
        selectElement.innerHTML = `<option value="" disabled selected>${defaultText}</option>`;
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.nombre;
            selectElement.appendChild(option);
        });
    }

    async function handleDeleteProduct(id) {
        const confirmed = await showConfirm('¿Estás seguro de que quieres eliminar este producto? Esta acción no se puede deshacer.');
        if (!confirmed) return;
        try {
            const response = await fetch(`${BASE_URL}/deleteProduct`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();
            if (result.success) {
                showToast('Producto eliminado exitosamente.', 'success');
                productsDataTable.ajax.reload(null, false);
                historyDataTable.ajax.reload(null, false);
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudo eliminar el producto.', 'error');
        }
    }
    async function handleCloneProduct() {
        const sourceId = cloneSourceProductSelect.value;
        if (!sourceId) {
            showToast('Por favor, selecciona un producto para clonar.', 'info');
            return;
        }
        try {
            const response = await fetch(`${BASE_URL}/getProduct?id=${sourceId}`);
            const result = await response.json();
            if (result.success) {
                const product = result.data;
                document.getElementById('nombre').value = `${product.nombre} (Copia)`;
                document.getElementById('id_categoria').value = product.id_categoria;
                document.getElementById('id_marca').value = product.id_marca;
                document.getElementById('stock_minimo').value = product.stock_minimo;
                document.getElementById('descripcion').value = product.descripcion;

                if (costoAn) costoAn.set(product.costo);
                if (precioMenudeoAn) precioMenudeoAn.set(product.precio_menudeo);
                if (precioMayoreoAn) precioMayoreoAn.set(product.precio_mayoreo);

                document.getElementById('product-id').value = '';
                document.getElementById('sku').value = '';
                document.getElementById('stock').value = 0;
                barcodesContainer.innerHTML = '';
                if (product.codigos_barras && Array.isArray(product.codigos_barras)) {
                    product.codigos_barras.forEach(code => addBarcodeField(code));
                } else {
                    addBarcodeField();
                }
                modalTitle.innerHTML = `<i class="fas fa-copy mr-3"></i>Clonando: ${product.nombre}`;
                showToast('Datos cargados. Modifica los campos necesarios y guarda.', 'info');
                cloneControls.classList.add('hidden');
                document.getElementById('nombre').focus();
            } else {
                showToast(`Error al cargar datos para clonar: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudieron obtener los datos del producto para clonar.', 'error');
        }
    }


    async function prepareBarcodeModal(id) {
        try {
            const response = await fetch(`${BASE_URL}/getProduct?id=${id}`);
            const result = await response.json();
            if (result.success) {
                const product = result.data;
                barcodeProductName.textContent = product.nombre;
                barcodeDataSelect.innerHTML = '';
                if (product.sku) {
                    const skuOption = document.createElement('option');
                    skuOption.value = product.sku;
                    skuOption.textContent = `SKU: ${product.sku}`;
                    barcodeDataSelect.appendChild(skuOption);
                }
                if (product.codigos_barras && Array.isArray(product.codigos_barras)) {
                    product.codigos_barras.forEach(code => {
                        if (code) {
                            const option = document.createElement('option');
                            option.value = code;
                            option.textContent = `Código: ${code}`;
                            barcodeDataSelect.appendChild(option);
                        }
                    });
                }
                barcodeFeedback.textContent = '';
                barcodeSvg.innerHTML = '';
                printBarcodeBtn.disabled = true;
                showModal(barcodeModal);
            } else {
                showToast(`Error al obtener datos del producto: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudieron obtener los datos del producto.', 'error');
        }
    }

    function generateBarcode() {
        const data = barcodeDataSelect.value;
        const format = barcodeFormatSelect.value;
        barcodeFeedback.textContent = '';
        barcodeSvg.innerHTML = '';
        printBarcodeBtn.disabled = true;
        if (!data) {
            barcodeFeedback.textContent = 'No hay un código seleccionado para generar.';
            return;
        }
        let isValid = true;
        let validationMessage = '';
        if (format === 'EAN13') {
            if (!/^\d{12,13}$/.test(data)) {
                isValid = false;
                validationMessage = 'EAN-13 debe ser numérico de 12 o 13 dígitos.';
            }
        }
        if (isValid) {
            try {
                JsBarcode("#barcode-svg", data, {
                    format: format,
                    lineColor: "#000",
                    width: 2,
                    height: 80,
                    displayValue: true,
                    fontOptions: "bold",
                    fontSize: 18
                });
                printBarcodeBtn.disabled = false;
                const descEl = document.getElementById('barcode-desc');
                if (descEl) {
                    const desc = document.getElementById('descripcion')?.value?.trim() || '';
                    descEl.textContent = desc;      // muestra la descripción bajo el número
                }

            } catch (e) {
                barcodeFeedback.textContent = `Error: ${e.message.replace('JsBarcode: ', '')}`;
            }
        } else {
            barcodeFeedback.textContent = validationMessage;
        }
    }

    function printBarcode() {
        const svgElement = barcodeSvg.cloneNode(true);
        barcodePrintArea.innerHTML = '';
        barcodePrintArea.appendChild(svgElement);
        window.print();
    }

    async function fetchSucursales() {
        if (USER_ROLE !== 'Super' || !branchSelector) return;
        try {
            const response = await fetch(`${BASE_URL}/getSucursales`);
            const result = await response.json();
            if (result.success) {
                branchSelector.innerHTML = '<option value="" disabled selected>Selecciona una sucursal</option>';
                result.data.forEach(sucursal => {
                    const option = document.createElement('option');
                    option.value = sucursal.id;
                    option.textContent = sucursal.nombre;
                    branchSelector.appendChild(option);
                });
            } else {
                showToast('No se pudieron cargar las sucursales.', 'error');
            }
        } catch (error) {
            showToast('Error de red al cargar sucursales.', 'error');
        }
    }

    function prepareAdjustStockModal(productId, productName, currentStock, action) {
        adjustProductId.value = productId;
        adjustProductName.textContent = productName;
        adjustAction.value = action;
        adjustCurrentStockValue.value = currentStock;
        adjustCurrentStockDisplay.textContent = currentStock;
        adjustQuantityInput.value = '';
        adjustStockReasonInput.value = '';
        if (USER_ROLE === 'Super') {
            fetchSucursales();
        }
        if (action === 'increase') {
            adjustModalTitle.textContent = 'Abastecer Producto';
            adjustQuantityLabel.textContent = 'Cantidad a Añadir';
            confirmAdjustBtn.className = 'bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg';
            confirmAdjustBtn.textContent = 'Añadir Stock';
        } else {
            adjustModalTitle.textContent = 'Restar de Stock';
            adjustQuantityLabel.textContent = 'Cantidad a Restar';
            confirmAdjustBtn.className = 'bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-4 rounded-lg';
            confirmAdjustBtn.textContent = 'Restar Stock';
        }
        showModal(adjustStockModal);
        adjustQuantityInput.focus();
    }

    async function handleConfirmAdjustStock() {
        const productId = adjustProductId.value;
        const quantityChange = parseInt(adjustQuantityInput.value);
        const reason = adjustStockReasonInput.value.trim();
        const currentStock = parseInt(adjustCurrentStockValue.value);
        if (isNaN(quantityChange) || quantityChange <= 0) {
            showToast('La cantidad debe ser un número mayor que cero.', 'error');
            return;
        }
        if (!reason) {
            showToast('Por favor, ingresa un motivo para el ajuste de stock.', 'error');
            return;
        }
        const requestBody = { id_producto: productId, cantidad_movida: quantityChange, motivo: reason, stock_anterior: currentStock };
        if (USER_ROLE === 'Super') {
            if (!branchSelector.value) {
                showToast('Debes seleccionar una sucursal para abastecer.', 'error');
                return;
            }
            requestBody.id_sucursal = branchSelector.value;
        }
        const action = adjustAction.value;
        let newStock;
        if (action === 'increase') {
            newStock = currentStock + quantityChange;
            requestBody.tipo_movimiento = 'entrada';
        } else {
            if (quantityChange > currentStock) {
                showToast('No se puede restar más stock del que hay disponible.', 'error');
                return;
            }
            newStock = currentStock - quantityChange;
            requestBody.tipo_movimiento = 'salida';
        }
        requestBody.new_stock = newStock;
        const confirmed = await showConfirm(`¿Confirmas el ajuste? El nuevo stock será ${newStock}.`);
        if (!confirmed) return;
        try {
            const response = await fetch(`${BASE_URL}/adjustStock`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestBody)
            });
            const result = await response.json();
            if (result.success) {
                showToast('Stock ajustado y movimiento registrado.', 'success');
                hideModal(adjustStockModal);
                productsDataTable.ajax.reload(null, false);
                historyDataTable.ajax.reload(null, false);
            } else {
                showToast(`Error al ajustar stock: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('Error de conexión al ajustar stock.', 'error');
        }
    }

    // --- Lógica para Categorías y Marcas ---
    function prepareCategoryFormForAdd() {
        if (categoryForm) {
            categoryForm.reset();
            categoryIdInput.value = '';
            saveCategoryBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Añadir Categoría';
            saveCategoryBtn.classList.remove('bg-blue-600', 'hover:bg-blue-500');
            saveCategoryBtn.classList.add('bg-green-600', 'hover:bg-green-500');
            cancelCategoryEditBtn.classList.add('hidden');
        }
    }

    async function fetchCategories() {
        if (!categoriesTableBody) return;
        categoriesTableBody.innerHTML = `<tr><td colspan="3" class="text-center py-5 text-gray-500">Cargando categorías...</td></tr>`;
        try {
            const response = await fetch(`${BASE_URL}/getCategorias`);
            const result = await response.json();
            if (result.success) {
                renderCategories(result.data);
                populateSelect(categoriaSelect, result.data, 'Selecciona una categoría');
            } else {
                categoriesTableBody.innerHTML = `<tr><td colspan="3" class="text-center py-5 text-red-500">${result.message}</td></tr>`;
            }
        } catch (error) {
            showToast('Error al cargar categorías.', 'error');
        }
    }

    function renderCategories(categoriesToRender) {
        if (!categoriesTableBody) return;
        categoriesTableBody.innerHTML = '';
        categoriesToRender.forEach(category => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-800';
            tr.innerHTML = `
                <td class="py-3 px-6 text-sm font-semibold text-white">${category.nombre}</td>
                <td class="py-3 px-6 text-sm">${category.descripcion || 'Sin descripción'}</td>
                <td class="py-3 px-6 text-center">
                    <button data-id="${category.id}" data-name="${category.nombre}" data-description="${category.descripcion || ''}" class="edit-category-btn text-blue-400 hover:text-blue-300 mr-3" title="Editar Categoría"><i class="fas fa-pencil-alt"></i></button>
                    <button data-id="${category.id}" class="delete-category-btn text-red-500 hover:text-red-400" title="Eliminar Categoría"><i class="fas fa-trash-alt"></i></button>
                </td>
            `;
            categoriesTableBody.appendChild(tr);
        });
    }

    async function handleBrandFormSubmit(event) {
        event.preventDefault();
        const brandId = brandIdInput.value;
        const brandName = brandNameInput.value.trim();
        if (!brandName) {
            showToast('El nombre de la marca es obligatorio.', 'error');
            return;
        }
        const brandData = { id: brandId, nombre: brandName };
        const url = brandId ? `${BASE_URL}/updateMarca` : `${BASE_URL}/createMarca`;
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(brandData)
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Marca ${brandId ? 'actualizada' : 'añadida'} exitosamente.`, 'success');
                prepareBrandFormForAdd();
                fetchBrands();
                fetchCatalogs();
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudo conectar con el servidor para gestionar marcas.', 'error');
        }
    }

    function prepareBrandFormForAdd() {
        if (brandForm) {
            brandForm.reset();
            brandIdInput.value = '';
            saveBrandBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Añadir Marca';
            saveBrandBtn.classList.remove('bg-blue-600', 'hover:bg-blue-500');
            saveBrandBtn.classList.add('bg-green-600', 'hover:bg-green-500');
            cancelBrandEditBtn.classList.add('hidden');
        }
    }

    async function fetchBrands() {
        if (!brandsTableBody) return;
        brandsTableBody.innerHTML = `<tr><td colspan="2" class="text-center py-5 text-gray-500">Cargando marcas...</td></tr>`;
        try {
            const response = await fetch(`${BASE_URL}/getMarcas`);
            const result = await response.json();
            if (result.success) {
                renderBrands(result.data);
                populateSelect(marcaSelect, result.data, 'Selecciona una marca');
            } else {
                brandsTableBody.innerHTML = `<tr><td colspan="2" class="text-center py-5 text-red-500">${result.message}</td></tr>`;
            }
        } catch (error) {
            showToast('Error al cargar marcas.', 'error');
        }
    }

    function renderBrands(brandsToRender) {
        if (!brandsTableBody) return;
        brandsTableBody.innerHTML = '';
        brandsToRender.forEach(brand => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-800';
            tr.innerHTML = `
                <td class="py-3 px-6 text-sm font-semibold text-white">${brand.nombre}</td>
                <td class="py-3 px-6 text-center">
                    <button data-id="${brand.id}" data-name="${brand.nombre}" class="edit-brand-btn text-blue-400 hover:text-blue-300 mr-3" title="Editar Marca"><i class="fas fa-pencil-alt"></i></button>
                    <button data-id="${brand.id}" class="delete-brand-btn text-red-500 hover:text-red-400" title="Eliminar Marca"><i class="fas fa-trash-alt"></i></button>
                </td>
            `;
            brandsTableBody.appendChild(tr);
        });
    }

    async function handleCategoryFormSubmit(event) {
        event.preventDefault();
        const categoryId = categoryIdInput.value;
        const categoryName = categoryNameInput.value.trim();
        const categoryDescription = categoryDescriptionInput.value.trim();
        if (!categoryName) {
            showToast('El nombre de la categoría es obligatorio.', 'error');
            return;
        }
        const categoryData = { id: categoryId, nombre: categoryName, descripcion: categoryDescription };
        const url = categoryId ? `${BASE_URL}/updateCategoria` : `${BASE_URL}/createCategoria`;
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(categoryData)
            });
            const result = await response.json();
            if (result.success) {
                showToast(`Categoría ${categoryId ? 'actualizada' : 'añadida'} exitosamente.`, 'success');
                prepareCategoryFormForAdd();
                fetchCategories();
                fetchCatalogs();
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudo conectar con el servidor para gestionar categorías.', 'error');
        }
    }

    function handleEditCategory(id, name, description) {
        categoryIdInput.value = id;
        categoryNameInput.value = name;
        categoryDescriptionInput.value = description;
        saveCategoryBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        saveCategoryBtn.classList.remove('bg-green-600', 'hover:bg-green-500');
        saveCategoryBtn.classList.add('bg-blue-600', 'hover:bg-blue-500');
        cancelCategoryEditBtn.classList.remove('hidden');
        categoryNameInput.focus();
    }

    async function handleDeleteCategory(id) {
        const confirmed = await showConfirm('¿Seguro que quieres eliminar esta categoría? Los productos asociados quedarán sin categoría.');
        if (!confirmed) return;
        try {
            const response = await fetch(`${BASE_URL}/deleteCategoria`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();
            if (result.success) {
                showToast('Categoría eliminada exitosamente.', 'success');
                fetchCategories();
                fetchCatalogs();
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudo eliminar la categoría.', 'error');
        }
    }

    function handleEditBrand(id, name) {
        brandIdInput.value = id;
        brandNameInput.value = name;
        saveBrandBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        saveBrandBtn.classList.remove('bg-green-600', 'hover:bg-green-500');
        saveBrandBtn.classList.add('bg-blue-600', 'hover:bg-blue-500');
        cancelBrandEditBtn.classList.remove('hidden');
        brandNameInput.focus();
    }

    async function handleDeleteBrand(id) {
        const confirmed = await showConfirm('¿Estás seguro de que quieres eliminar esta marca? Los productos asociados quedarán sin marca.');
        if (!confirmed) return;
        try {
            const response = await fetch(`${BASE_URL}/deleteMarca`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();
            if (result.success) {
                showToast('Marca eliminada exitosamente.', 'success');
                fetchBrands();
                fetchCatalogs();
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudo eliminar la marca.', 'error');
        }
    }

    // --- Asignación de Eventos ---
    addProductBtn.addEventListener('click', prepareNewProductForm);
    closeModalBtn.addEventListener('click', () => hideModal(productModal));
    cancelBtn.addEventListener('click', () => hideModal(productModal));
    productForm.addEventListener('submit', handleFormSubmit);
    addBarcodeBtn.addEventListener('click', () => addBarcodeField());
    barcodesContainer.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-barcode-btn');
        if (removeButton) removeButton.closest('.flex').remove();
    });

    $('#productsTable tbody').on('click', 'button', function (event) {
        const target = $(event.currentTarget);
        const id = target.data('id');
        if (target.hasClass('edit-btn')) {
            handleEditProduct(id);
        } else if (target.hasClass('delete-btn')) {
            handleDeleteProduct(id);
        } else if (target.hasClass('adjust-stock-btn')) {
            const name = target.data('name');
            const currentStock = parseInt(target.data('currentstock') || '0');
            const action = target.data('action');
            prepareAdjustStockModal(id, name, currentStock, action);
        } else if (target.hasClass('barcode-btn')) {
            prepareBarcodeModal(id);
        }
    });

    closeAdjustModalBtn.addEventListener('click', () => hideModal(adjustStockModal));
    cancelAdjustBtn.addEventListener('click', () => hideModal(adjustStockModal));
    confirmAdjustBtn.addEventListener('click', handleConfirmAdjustStock);
    if (closeBarcodeModalBtn) closeBarcodeModalBtn.addEventListener('click', () => hideModal(barcodeModal));
    if (generateBarcodeBtn) generateBarcodeBtn.addEventListener('click', generateBarcode);
    if (printBarcodeBtn) printBarcodeBtn.addEventListener('click', printBarcode);
    if (manageCategoriesBtn) {
        manageCategoriesBtn.addEventListener('click', () => {
            prepareCategoryFormForAdd();
            fetchCategories();
            showModal(categoryModal);
        });
    }
    if (closeCategoryModalBtn) closeCategoryModalBtn.addEventListener('click', () => hideModal(categoryModal));
    if (categoryForm) categoryForm.addEventListener('submit', handleCategoryFormSubmit);
    if (cancelCategoryEditBtn) cancelCategoryEditBtn.addEventListener('click', () => prepareCategoryFormForAdd());
    if (categoriesTableBody) {
        categoriesTableBody.addEventListener('click', function (event) {
            const editButton = event.target.closest('.edit-category-btn');
            if (editButton) handleEditCategory(editButton.dataset.id, editButton.dataset.name, editButton.dataset.description);
            const deleteButton = event.target.closest('.delete-category-btn');
            if (deleteButton) handleDeleteCategory(deleteButton.dataset.id);
        });
    }
    if (manageBrandsBtn) {
        manageBrandsBtn.addEventListener('click', () => {
            prepareBrandFormForAdd();
            fetchBrands();
            showModal(brandModal);
        });
    }
    if (closeBrandModalBtn) closeBrandModalBtn.addEventListener('click', () => hideModal(brandModal));
    if (brandForm) brandForm.addEventListener('submit', handleBrandFormSubmit);
    if (brandsTableBody) {
        brandsTableBody.addEventListener('click', function (event) {
            const editButton = event.target.closest('.edit-brand-btn');
            if (editButton) handleEditBrand(editButton.dataset.id, editButton.dataset.name);
            const deleteButton = event.target.closest('.delete-brand-btn');
            if (deleteButton) handleDeleteBrand(deleteButton.dataset.id);
        });
    }
    if (toggleCloneBtn) toggleCloneBtn.addEventListener('click', () => cloneControls.classList.toggle('hidden'));
    if (loadCloneDataBtn) loadCloneDataBtn.addEventListener('click', handleCloneProduct);

    // --- Carga Inicial ---
    initializeDropzone();
    initializeProductsDataTable();
    initializeHistoryDataTable();
    fetchCatalogs();

    const autoNumericOptions = {
        currencySymbol: '',
        decimalCharacter: '.',
        digitGroupSeparator: ',',
        decimalPlaces: 2,
        minimumValue: '0'
    };

    costoAn = new AutoNumeric('#costo', autoNumericOptions);
    precioMenudeoAn = new AutoNumeric('#precio_menudeo', autoNumericOptions);
    precioMayoreoAn = new AutoNumeric('#precio_mayoreo', autoNumericOptions);
});
