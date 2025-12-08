document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const productsTbody = document.getElementById('products-tbody');
    const addProductBtn = document.getElementById('add-product-btn');
    const productModal = document.getElementById('product-modal');
    const modalTitle = document.getElementById('modal-title');
    const productForm = document.getElementById('product-form');
    const cancelBtn = document.getElementById('cancel-btn');
    const productIdInput = document.getElementById('product-id');
    const searchInput = document.getElementById('search');
    const categoryFilter = document.getElementById('category-filter');
    const priceFilter = document.getElementById('price-filter');
    const priceValue = document.getElementById('price-value');
    const paginationContainer = document.getElementById('pagination');
    const selectAllCheckbox = document.getElementById('select-all');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const addCategoryBtn = document.getElementById('add-category-btn');
    const exportCsvBtn = document.getElementById('export-csv-btn');

    const API_URL = 'api.php';
    let currentPage = 1;
    let debounceTimer;

    // --- Modal Handling ---
    const showModal = (title) => {
        modalTitle.textContent = title;
        productModal.classList.remove('hidden');
        productModal.classList.add('flex');
        productForm.reset();
        productIdInput.value = '';

        const fields = productForm.querySelectorAll('input, textarea');
        fields.forEach(field => field.readOnly = false);

        const fileInputs = productForm.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => input.closest('div').style.display = 'block');

        const modalFooter = productForm.querySelector('.flex.justify-end');
        modalFooter.innerHTML = `
            <button type="button" id="cancel-btn" class="bg-slate-600 hover:bg-slate-500 text-white font-bold py-2 px-4 rounded transition">
                Cancel
            </button>
            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                Save Product
            </button>
        `;
        document.getElementById('cancel-btn').addEventListener('click', hideModal);

    };

    const hideModal = () => {
        productModal.classList.add('hidden');
        productModal.classList.remove('flex');
        productForm.reset();
        productIdInput.value = '';
    };

    // --- API Functions ---
    const getProducts = async (page = 1) => {
        currentPage = page;
        const search = searchInput.value;
        const category = categoryFilter.value;
        const maxPrice = priceFilter.value;
        
        const url = new URL(API_URL, window.location.origin + window.location.pathname.replace('products.php', ''));
        url.searchParams.append('action', 'get_products');
        url.searchParams.append('page', currentPage);
        if (search) url.searchParams.append('search', search);
        if (category) url.searchParams.append('category', category);
        if (maxPrice < priceFilter.max) url.searchParams.append('max_price', maxPrice);

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (data.status === 'success') {
                renderProducts(data.products);
                renderPagination(data.pagination);
                if (data.categories) {
                    populateCategoryFilter(data.categories);
                }
            } else {
                alert(`Error: ${data.message}`);
            }
        } catch (error) {
            console.error('Failed to fetch products:', error);
        }
    };

    const saveProduct = async (formData) => {
        try {
            const response = await fetch(`${API_URL}?action=save_product`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                hideModal();
                getProducts();
            } else {
                alert(`Error: ${data.message}`);
            }
        } catch (error) {
            console.error('Failed to save product:', error);
        }
    };
    
    const deleteProduct = async (id) => {
        if (!confirm('Are you sure you want to delete this product?')) return;
        
        const formData = new FormData();
        formData.append('id', id);
        
        try {
            const response = await fetch(`${API_URL}?action=delete_product`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                getProducts();
            } else {
                alert(`Error: ${data.message}`);
            }
        } catch (error) {
            console.error('Failed to delete product:', error);
        }
    };

    const bulkDeleteProducts = async (ids) => {
        if (!confirm(`Are you sure you want to delete ${ids.length} products?`)) return;

        const formData = new FormData();
        ids.forEach(id => formData.append('ids[]', id));

        try {
            const response = await fetch(`${API_URL}?action=bulk_delete`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.status === 'success') {
                getProducts();
            } else {
                alert(`Error: ${data.message}`);
            }
        } catch (error) {
            console.error('Failed to bulk delete products:', error);
        }
    };

    // --- Rendering ---
    const renderProducts = (products) => {
        productsTbody.innerHTML = '';
        if (products.length === 0) {
            productsTbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-slate-500">No products found.</td></tr>';
            return;
        }
        products.forEach(product => {
            const tr = document.createElement('tr');
            tr.classList.add('product-row');
            tr.dataset.id = product.id;
            tr.innerHTML = `
                <td class="px-6 py-4">
                    <input type="checkbox" class="product-checkbox rounded bg-slate-900 border-slate-600 text-amber-500 focus:ring-amber-500/50" data-id="${product.id}">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                    ${product.image_main ? `<img src="../${product.image_main}" alt="${escapeHTML(product.name)}" class="h-10 w-10 object-cover rounded-md">` : ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">${escapeHTML(product.name)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">${escapeHTML(product.category)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">₦${parseFloat(product.price).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                    <label class="flex items-center cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" ${product.is_featured ? 'checked' : ''} class="sr-only toggle-featured" data-id="${product.id}">
                            <div class="block bg-slate-600 w-10 h-6 rounded-full"></div>
                            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
                        </div>
                    </label>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="text-blue-400 hover:text-blue-300 mr-4 edit-btn" data-id="${product.id}">Edit</button>
                    <button class="text-red-500 hover:text-red-400 ml-4 delete-btn" data-id="${product.id}">Delete</button>
                </td>
            `;
            productsTbody.appendChild(tr);
        });
        updateBulkDeleteButton();
    };

    const renderPagination = (pagination) => {
        paginationContainer.innerHTML = '';
        if (pagination.total_pages <= 1) return;

        for (let i = 1; i <= pagination.total_pages; i++) {
            const button = document.createElement('button');
            button.textContent = i;
            button.className = `px-3 py-1 rounded ${i === pagination.page ? 'bg-amber-500 text-slate-900' : 'bg-slate-700 hover:bg-slate-600'}`;
            button.addEventListener('click', () => {
                getProducts(i);
            });
            paginationContainer.appendChild(button);
        }
    };

    const populateCategoryFilter = (categories) => {
        const currentCategory = categoryFilter.value;
        while (categoryFilter.options.length > 1) {
            categoryFilter.remove(1);
        }
        categories.forEach(category => {
            const option = new Option(category, category);
            categoryFilter.add(option);
        });
        categoryFilter.value = currentCategory;
    };

    const updateBulkDeleteButton = () => {
        const selected = document.querySelectorAll('.product-checkbox:checked').length;
        if (selected > 0) {
            bulkDeleteBtn.classList.remove('hidden');
        } else {
            bulkDeleteBtn.classList.add('hidden');
        }
    };

    // --- Event Listeners ---
    addProductBtn.addEventListener('click', () => showModal('Add New Product'));
    cancelBtn.addEventListener('click', hideModal);

    productForm.addEventListener('submit', (e) => {
        e.preventDefault();
        saveProduct(new FormData(productForm));
    });

    productsTbody.addEventListener('click', async (e) => {
        const productRow = e.target.closest('.product-row');
        if (productRow && !e.target.closest('button, input')) {
            const id = productRow.dataset.id;
            showProductPopup(id);
        }
        if (e.target.classList.contains('edit-btn')) {
            const id = e.target.dataset.id;
            openEditModal(id);
        }
        if (e.target.classList.contains('delete-btn')) {
            deleteProduct(e.target.dataset.id);
        }
        if (e.target.classList.contains('product-checkbox')) {
            updateBulkDeleteButton();
        }
        if (e.target.classList.contains('toggle-featured')) {
            const id = e.target.dataset.id;
            const formData = new FormData();
            formData.append('id', id);
            fetch(`${API_URL}?action=toggle_featured`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'error') {
                    alert(`Error: ${data.message}`);
                    // Revert the checkbox state if there was an error
                    e.target.checked = !e.target.checked;
                }
            })
            .catch(error => {
                console.error('Failed to toggle featured status:', error);
                alert('An error occurred while toggling featured status.');
                // Revert the checkbox state on network error
                e.target.checked = !e.target.checked;
            });
        }
    });

    const showProductPopup = async (id) => {
        try {
            const response = await fetch(`${API_URL}?action=get_product&id=${id}`);
            const data = await response.json();
            if (data.status === 'success') {
                const p = data.product;
                showModal('View Product');
                productForm.reset();
                productIdInput.value = p.id;
                document.getElementById('name').value = p.name;
                document.getElementById('category').value = p.category;
                document.getElementById('price').value = p.price;
                document.getElementById('description').value = p.description;

                // Make fields read-only
                const fields = productForm.querySelectorAll('input, textarea');
                fields.forEach(field => field.readOnly = true);

                // Hide file inputs
                const fileInputs = productForm.querySelectorAll('input[type="file"]');
                fileInputs.forEach(input => input.closest('div').style.display = 'none');


                const modalFooter = productForm.querySelector('.flex.justify-end');
                modalFooter.innerHTML = `
                    <button type="button" id="close-popup-btn" class="bg-slate-600 hover:bg-slate-500 text-white font-bold py-2 px-4 rounded transition">
                        Close
                    </button>
                    <button type="button" id="edit-popup-btn" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                        Edit
                    </button>
                `;

                document.getElementById('close-popup-btn').addEventListener('click', hideModal);
                document.getElementById('edit-popup-btn').addEventListener('click', () => {
                    fields.forEach(field => field.readOnly = false);
                    fileInputs.forEach(input => input.closest('div').style.display = 'block');
                    modalFooter.innerHTML = `
                        <button type="button" id="cancel-btn" class="bg-slate-600 hover:bg-slate-500 text-white font-bold py-2 px-4 rounded transition">
                            Cancel
                        </button>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                            Save Product
                        </button>
                    `;
                    document.getElementById('cancel-btn').addEventListener('click', hideModal);
                });

            } else {
                alert(`Error: ${data.message}`);
            }
        } catch (error) {
            console.error('Failed to fetch product details:', error);
        }
    };

    // Open modal directly in edit mode
    const openEditModal = async (id) => {
        try {
            const response = await fetch(`${API_URL}?action=get_product&id=${id}`);
            const data = await response.json();
            if (data.status === 'success') {
                const p = data.product;
                showModal('Edit Product');
                productForm.reset();
                productIdInput.value = p.id;
                document.getElementById('name').value = p.name;
                document.getElementById('category').value = p.category;
                document.getElementById('price').value = p.price;
                document.getElementById('description').value = p.description;

                // Ensure fields are editable
                const fields = productForm.querySelectorAll('input, textarea');
                fields.forEach(field => field.readOnly = false);

                // Show file inputs
                const fileInputs = productForm.querySelectorAll('input[type="file"]');
                fileInputs.forEach(input => input.closest('div').style.display = 'block');

                // Replace modal footer with Save/Cancel
                const modalFooter = productForm.querySelector('.flex.justify-end');
                modalFooter.innerHTML = `
                    <button type="button" id="cancel-btn" class="bg-slate-600 hover:bg-slate-500 text-white font-bold py-2 px-4 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                        Save Product
                    </button>
                `;
                document.getElementById('cancel-btn').addEventListener('click', hideModal);
            } else {
                alert(`Error: ${data.message}`);
            }
        } catch (error) {
            console.error('Failed to fetch product details for edit:', error);
        }
    };

    selectAllCheckbox.addEventListener('change', (e) => {
        document.querySelectorAll('.product-checkbox').forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
        updateBulkDeleteButton();
    });
    
    bulkDeleteBtn.addEventListener('click', () => {
        const selectedIds = [...document.querySelectorAll('.product-checkbox:checked')].map(cb => cb.dataset.id);
        if (selectedIds.length > 0) {
            bulkDeleteProducts(selectedIds);
        }
    });

    searchInput.addEventListener('keyup', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { getProducts(1); }, 300);
    });

    addCategoryBtn.addEventListener('click', async () => {
        const newCategoryName = prompt('Enter new category name:');
        if (newCategoryName) {
            // Make API call to add new category
            try {
                const formData = new FormData();
                formData.append('category_name', newCategoryName);
                const response = await fetch(`${API_URL}?action=add_category`, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.status === 'success') {
                    alert('Category added successfully!');
                    // Re-fetch products to update category filter and select new category
                    await getProducts();
                    categoryFilter.value = newCategoryName; // Select the newly added category
                } else {
                    alert(`Error adding category: ${data.message}`);
                }
            } catch (error) {
                console.error('Error adding category:', error);
                alert('An error occurred while adding the category.');
            }
        }
    });

    exportCsvBtn.addEventListener('click', async () => {
        try {
            const response = await fetch(`${API_URL}?action=get_products&limit=all`);
            const data = await response.json();
            if (data.status === 'success') {
                const products = data.products;
                let csvContent = "data:text/csv;charset=utf-8,";
                csvContent += "ID,Name,Category,Price,Featured\n";
                products.forEach(p => {
                    csvContent += `${p.id},"${escapeCSV(p.name)}","${escapeCSV(p.category)}",${p.price},${p.is_featured ? 'Yes' : 'No'}\n`;
                });
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "products.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                alert(`Error: ${data.message}`);
            }
        } catch (error) {
            console.error('Failed to export CSV:', error);
        }
    });

    categoryFilter.addEventListener('change', () => {
        getProducts(1);
    });
    priceFilter.addEventListener('input', () => { priceValue.textContent = `₦${priceFilter.value}`; });
    priceFilter.addEventListener('change', () => { getProducts(1); });

    // --- Utility ---
    const escapeHTML = (str) => {
        if (str === null || str === undefined) return '';
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    };

    const escapeCSV = (str) => {
        if (str === null || str === undefined) return '';
        return str.replace(/"/g, '""');
    };

    // --- Initial Load ---
    if(priceValue) priceValue.textContent = `₦${priceFilter.value}`;
    if(productsTbody) getProducts();
});
