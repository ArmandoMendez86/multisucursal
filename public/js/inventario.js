// Archivo: /public/js/inventario.js

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

    // --- Variables Globales ---
    let productsDataTable;
    let historyDataTable;
    const USER_ROLE = document.body.dataset.userRole || 'user';

    const showModal = (modalElement) => {
        if (modalElement) modalElement.classList.remove('hidden');
    };
    const hideModal = (modalElement) => {
        if (modalElement) modalElement.classList.add('hidden');
    };

    function initializeProductsDataTable() {
        productsDataTable = $('#productsTable').DataTable({
            ajax: { url: `${BASE_URL}/getProducts`, dataSrc: 'data' },
            columns: [
                { data: 'sku' },
                { data: 'nombre', className: 'font-semibold text-white' },
                { data: 'codigos_barras', defaultContent: 'N/A' },
                { data: 'categoria_nombre', defaultContent: 'N/A' },
                {
                    data: 'stock',
                    className: 'text-center',
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
                    render: (data) => `$${parseFloat(data).toFixed(2)}`
                },
                {
                    data: 'id',
                    className: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: (data) => `
                        <button data-id="${data}" class="edit-btn text-blue-400 hover:text-blue-300 mr-3" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <button data-id="${data}" class="delete-btn text-red-500 hover:text-red-400" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                        `
                }
            ],
            responsive: true,
            paging: true,
            searching: true,
            info: true,
            lengthChange: true, // 1. Activa el selector
            pageLength: 10,     // Opcional: define la cantidad de filas por defecto
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            language: { search: "Buscar:", searchPlaceholder: "Buscar producto...", zeroRecords: "No se encontraron productos", emptyTable: "No hay productos", info: "Mostrando _START_ a _END_ de _TOTAL_ productos", infoEmpty: "Mostrando 0 a 0 de 0 productos", paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" } },
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
            lengthChange: true, // 1. Activa el selector
            pageLength: 10,     // Opcional: define la cantidad de filas por defecto
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            language: { search: "Buscar:", searchPlaceholder: "Buscar movimiento...", zeroRecords: "No se encontraron movimientos", emptyTable: "No hay movimientos", info: "Mostrando _START_ a _END_ de _TOTAL_ movimientos", infoEmpty: "Mostrando 0 a 0 de 0 movimientos", paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" } },
            dom: '<"flex flex-col md:flex-row justify-between items-center mb-4 gap-4" <"flex items-center" l> <"ml-auto" f> > rt <"flex justify-between items-center mt-4"ip>'

        });
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

    function prepareNewProductForm() {
        productForm.reset();
        document.getElementById('product-id').value = '';
        modalTitle.innerHTML = '<i class="fas fa-box-open mr-3"></i>Añadir Nuevo Producto';
        barcodesContainer.innerHTML = '';
        addBarcodeField();
        cloneSection.classList.remove('hidden');
        cloneControls.classList.add('hidden');
        populateCloneSelect();
        showModal(productModal);
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
    /**
     * Populates the product selector for the cloning function.
     */
    async function populateCloneSelect() {
        if (!cloneSourceProductSelect) return;

        try {
            // Reutilizamos el endpoint que ya obtiene una lista simple de productos.
            const response = await fetch(`${BASE_URL}/getProductosParaPreciosEspeciales`);
            const result = await response.json();

            if (result.success) {
                cloneSourceProductSelect.innerHTML = '<option value="" disabled selected>Selecciona un producto para clonar...</option>';
                // Ordenamos los productos alfabéticamente para el selector
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

    /**
     * Loads the data of a selected product into the form for cloning.
     */
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

                // Llenamos el formulario con los datos del producto base
                document.getElementById('nombre').value = `${product.nombre} (Copia)`;
                document.getElementById('id_categoria').value = product.id_categoria;
                document.getElementById('id_marca').value = product.id_marca;
                document.getElementById('precio_menudeo').value = product.precio_menudeo;
                document.getElementById('precio_mayoreo').value = product.precio_mayoreo;
                document.getElementById('stock_minimo').value = product.stock_minimo;
                document.getElementById('descripcion').value = product.descripcion;

                // IMPORTANTE: Limpiamos los campos que deben ser únicos o reiniciarse
                document.getElementById('product-id').value = ''; // Esto asegura que se cree un NUEVO producto
                document.getElementById('sku').value = ''; // El usuario debe ingresar un nuevo SKU
                document.getElementById('stock').value = 0; // Los productos clonados inician con 0 stock

                // Limpiamos y recreamos los campos de códigos de barras
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

    async function handleFormSubmit(event) {
        event.preventDefault();
        const formData = new FormData(productForm);
        const productData = Object.fromEntries(formData.entries());
        const productId = productData.id;
        productData.stock = parseInt(productData.stock) || 0;
        productData.stock_minimo = parseInt(productData.stock_minimo) || 0;
        const barcodeInputs = barcodesContainer.querySelectorAll('.barcode-input');
        productData.codigos_barras = Array.from(barcodeInputs).map(input => input.value.trim()).filter(Boolean);
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
                historyDataTable.ajax.reload(null, false);
                showToast(`Producto ${productId ? 'actualizado' : 'creado'} exitosamente.`, 'success');
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudo conectar con el servidor.', 'error');
        }
    }

    async function handleEditProduct(id) {
        if (cloneSection) cloneSection.classList.add('hidden');
        try {
            const response = await fetch(`${BASE_URL}/getProduct?id=${id}`);
            const result = await response.json();
            if (result.success) {
                const product = result.data;
                document.getElementById('product-id').value = product.id;
                document.getElementById('nombre').value = product.nombre;
                document.getElementById('sku').value = product.sku;
                document.getElementById('id_categoria').value = product.id_categoria;
                document.getElementById('id_marca').value = product.id_marca;

                document.getElementById('stock').value = product.stock ?? 0;
                document.getElementById('stock_minimo').value = product.stock_minimo ?? 0;
                document.getElementById('precio_menudeo').value = product.precio_menudeo ?? 0;
                document.getElementById('precio_mayoreo').value = product.precio_mayoreo ?? 0;
                document.getElementById('descripcion').value = product.descripcion ?? '';

                document.getElementById('stock_minimo').value = product.stock_minimo;
                document.getElementById('precio_menudeo').value = product.precio_menudeo;
                document.getElementById('precio_mayoreo').value = product.precio_mayoreo;
                document.getElementById('descripcion').value = product.descripcion;
                barcodesContainer.innerHTML = '';
                if (product.codigos_barras && Array.isArray(product.codigos_barras)) {
                    product.codigos_barras.forEach(code => addBarcodeField(code));
                } else {
                    addBarcodeField();
                }
                modalTitle.innerHTML = '<i class="fas fa-pencil-alt mr-3"></i>Editar Producto';
                showModal(productModal);
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudieron obtener los datos del producto.', 'error');
        }
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

        const requestBody = {
            id_producto: productId,
            cantidad_movida: quantityChange,
            motivo: reason,
            stock_anterior: currentStock
        };

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

        const categoryData = {
            id: categoryId,
            nombre: categoryName,
            descripcion: categoryDescription
        };

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
                prepareCategoryFormForAdd(); // Limpia el formulario
                fetchCategories(); // Refresca la lista de categorías en el modal
                fetchCatalogs(); // Refresca los selectores en el modal de productos
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

    // --- INICIO: Corrección para Editar y Eliminar Marcas ---

    /**
     * Prepara el formulario del modal para editar una marca existente.
     * @param {string} id - El ID de la marca.
     * @param {string} name - El nombre actual de la marca.
     */
    function handleEditBrand(id, name) {
        brandIdInput.value = id;
        brandNameInput.value = name;
        saveBrandBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios';
        saveBrandBtn.classList.remove('bg-green-600', 'hover:bg-green-500');
        saveBrandBtn.classList.add('bg-blue-600', 'hover:bg-blue-500');
        cancelBrandEditBtn.classList.remove('hidden');
        brandNameInput.focus();
    }

    /**
     * Envía la solicitud para eliminar una marca.
     * @param {string} id - El ID de la marca a eliminar.
     */
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
                fetchBrands(); // Refresca la lista de marcas
                fetchCatalogs(); // Refresca los selectores en el modal de productos
            } else {
                showToast(`Error: ${result.message}`, 'error');
            }
        } catch (error) {
            showToast('No se pudo eliminar la marca.', 'error');
        }
    }

    // Event delegation para los botones de editar y eliminar en la tabla de marcas
    if (brandsTableBody) {
        brandsTableBody.addEventListener('click', function (event) {
            const editButton = event.target.closest('.edit-brand-btn');
            if (editButton) {
                handleEditBrand(editButton.dataset.id, editButton.dataset.name);
            }

            const deleteButton = event.target.closest('.delete-brand-btn');
            if (deleteButton) {
                handleDeleteBrand(deleteButton.dataset.id);
            }
        });
    }

    // --- FIN: Corrección ---


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
        }
    });

    closeAdjustModalBtn.addEventListener('click', () => hideModal(adjustStockModal));
    cancelAdjustBtn.addEventListener('click', () => hideModal(adjustStockModal));
    confirmAdjustBtn.addEventListener('click', handleConfirmAdjustStock);

    if (manageCategoriesBtn) {
        manageCategoriesBtn.addEventListener('click', () => {
            prepareCategoryFormForAdd();
            fetchCategories();
            showModal(categoryModal);
        });
    }
    if (closeCategoryModalBtn) {
        closeCategoryModalBtn.addEventListener('click', () => hideModal(categoryModal));
    }
    if (categoryForm) {
        categoryForm.addEventListener('submit', handleCategoryFormSubmit);
    }
    if (cancelCategoryEditBtn) {
        cancelCategoryEditBtn.addEventListener('click', () => prepareCategoryFormForAdd());
    }
    if (categoriesTableBody) {
        categoriesTableBody.addEventListener('click', function (event) {
            const editButton = event.target.closest('.edit-category-btn');
            if (editButton) {
                handleEditCategory(editButton.dataset.id, editButton.dataset.name, editButton.dataset.description);
            }
            const deleteButton = event.target.closest('.delete-category-btn');
            if (deleteButton) {
                handleDeleteCategory(deleteButton.dataset.id);
            }
        });
    }

    if (manageBrandsBtn) {
        manageBrandsBtn.addEventListener('click', () => {
            prepareBrandFormForAdd();
            fetchBrands();
            showModal(brandModal);
        });
    }
    if (closeBrandModalBtn) {
        closeBrandModalBtn.addEventListener('click', () => hideModal(brandModal));
    }
    if (brandForm) {
        brandForm.addEventListener('submit', handleBrandFormSubmit);
    }

    if (toggleCloneBtn) {
        toggleCloneBtn.addEventListener('click', () => cloneControls.classList.toggle('hidden'));
    }
    if (loadCloneDataBtn) {
        loadCloneDataBtn.addEventListener('click', handleCloneProduct);
    }

    // --- Carga Inicial ---
    initializeProductsDataTable();
    initializeHistoryDataTable();
    fetchCatalogs();
});
