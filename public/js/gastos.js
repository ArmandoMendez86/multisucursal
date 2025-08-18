// Archivo: /public/js/gastos.js

document.addEventListener('DOMContentLoaded', function() {
    // --- Referencias a elementos del DOM ---
    const addExpenseBtn = document.getElementById('add-expense-btn');
    const expenseModal = document.getElementById('expense-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const expenseForm = document.getElementById('expense-form');
    const expensesTableBody = document.getElementById('expenses-table-body');
    const modalTitle = document.getElementById('modal-title');

    // --- Funciones para manejar el Modal ---
    const showModal = () => expenseModal.classList.remove('hidden');
    const hideModal = () => expenseModal.classList.add('hidden');

    function prepareNewExpenseForm() {
        expenseForm.reset();
        document.getElementById('expense-id').value = '';
        modalTitle.textContent = 'Registrar Nuevo Gasto';
        showModal();
    }

    // --- Lógica de la API ---
    async function fetchExpenses() {
        expensesTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-gray-500">Cargando gastos...</td></tr>`;
        try {
            const response = await fetch(`${BASE_URL}/getExpenses`);
            const result = await response.json();
            renderExpenses(result.success ? result.data : []);
        } catch (error) {
            expensesTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-red-500">No se pudo conectar con el servidor.</td></tr>`;
        }
    }

    function renderExpenses(expenses) {
        if (!expenses || expenses.length === 0) {
            expensesTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-gray-500">No hay gastos registrados.</td></tr>`;
            return;
        }
        expensesTableBody.innerHTML = '';
        expenses.forEach(expense => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-800';
            const formattedDate = new Date(expense.fecha).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
            tr.innerHTML = `
                <td class="py-3 px-6 text-sm">${formattedDate}</td>
                <td class="py-3 px-6 text-sm font-semibold text-white">${expense.categoria_gasto}</td>
                <td class="py-3 px-6 text-sm">${expense.descripcion}</td>
                <td class="py-3 px-6 text-right text-sm font-mono text-red-400">$${parseFloat(expense.monto).toFixed(2)}</td>
                <td class="py-3 px-6 text-center">
                    <button data-id="${expense.id}" class="edit-btn text-blue-400 hover:text-blue-300 mr-3" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                    <button data-id="${expense.id}" class="delete-btn text-red-500 hover:text-red-400" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                </td>
            `;
            expensesTableBody.appendChild(tr);
        });
    }

    async function handleEditExpense(id) {
        try {
            const response = await fetch(`${BASE_URL}/getExpense?id=${id}`);
            const result = await response.json();
            if (result.success) {
                const expense = result.data;
                document.getElementById('expense-id').value = expense.id;
                document.getElementById('categoria_gasto').value = expense.categoria_gasto;
                document.getElementById('descripcion').value = expense.descripcion;
                document.getElementById('monto').value = expense.monto;
                modalTitle.textContent = 'Editar Gasto';
                showModal();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudieron obtener los datos del gasto.', 'error');
        }
    }

    async function handleDeleteExpense(id) {
        const confirmed = await showConfirm('¿Estás seguro de que quieres eliminar este gasto?');
        if (!confirmed) return;
        try {
            const response = await fetch(`${BASE_URL}/deleteExpense`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();
            if (result.success) {
                showToast('Gasto eliminado exitosamente.', 'success');
                fetchExpenses();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudo eliminar el gasto.', 'error');
        }
    }

    async function handleFormSubmit(event) {
        event.preventDefault();
        const formData = new FormData(expenseForm);
        const expenseData = Object.fromEntries(formData.entries());
        const expenseId = expenseData.id;

        const url = expenseId ? `${BASE_URL}/updateExpense` : `${BASE_URL}/createExpense`;
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(expenseData)
            });
            const result = await response.json();
            if (result.success) {
                hideModal();
                fetchExpenses();
                showToast(`Gasto ${expenseId ? 'actualizado' : 'registrado'} exitosamente.`, 'success');
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudo conectar con el servidor.', 'error');
        }
    }
    
    // --- Asignación de Eventos ---
    addExpenseBtn.addEventListener('click', prepareNewExpenseForm);
    closeModalBtn.addEventListener('click', hideModal);
    cancelBtn.addEventListener('click', hideModal);
    expenseForm.addEventListener('submit', handleFormSubmit);

    expensesTableBody.addEventListener('click', function(event) {
        const editButton = event.target.closest('.edit-btn');
        const deleteButton = event.target.closest('.delete-btn');
        if (editButton) {
            handleEditExpense(editButton.dataset.id);
        } else if (deleteButton) {
            handleDeleteExpense(deleteButton.dataset.id);
        }
    });

    // --- Carga Inicial ---
    fetchExpenses();
});
