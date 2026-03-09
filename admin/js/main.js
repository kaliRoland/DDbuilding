document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const productsTbody = document.getElementById('products-tbody');
    const addProductBtn = document.getElementById('add-product-btn');
    const addProductBtns = document.querySelectorAll('#add-product-btn');
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
    const imageInputs = document.querySelectorAll('#product-form input[type="file"]');

    const MAX_FILE_BYTES = 2 * 1024 * 1024; // 2MB per file
    const MAX_TOTAL_BYTES = 8 * 1024 * 1024; // 8MB total (matches typical post_max_size)
    const formatBytes = (bytes) => {
        const mb = bytes / (1024 * 1024);
        return `${mb.toFixed(2)}MB`;
    };
    const validateImageSizes = () => {
        let total = 0;
        let tooLarge = false;
        imageInputs.forEach(input => {
            if (input.files && input.files.length > 0) {
                const file = input.files[0];
                total += file.size;
                if (file.size > MAX_FILE_BYTES) {
                    tooLarge = true;
                    alert(`"${file.name}" is ${formatBytes(file.size)}. Max per image is ${formatBytes(MAX_FILE_BYTES)}.`);
                    input.value = '';
                }
            }
        });
        if (total > MAX_TOTAL_BYTES) {
            alert(`Total upload size is ${formatBytes(total)}. Please keep it under ${formatBytes(MAX_TOTAL_BYTES)}.`);
            return false;
        }
        return !tooLarge;
    };

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

        // Ensure all form controls (inputs, textareas, selects) are editable by default
        const fields = productForm.querySelectorAll('input, textarea, select');
        fields.forEach(field => {
            if (field.tagName.toLowerCase() === 'select') field.disabled = false;
            field.readOnly = false;
        });

        // Populate category select for the modal
        fetchCategories().then(categories => populateModalCategorySelect(categories));

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
        
        const params = new URLSearchParams({
            action: 'get_products',
            page: currentPage
        });
        if (search) params.append('search', search);
        if (category) params.append('category', category);
        if (maxPrice < priceFilter.max) params.append('max_price', maxPrice);
        
        const url = `api.php?${params.toString()}`;

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

    // Fetch categories (returns array of objects {id,name,parent_id}) for modal selects
    const fetchCategories = async () => {
        try {
            const response = await fetch(`${API_URL}?action=get_categories`);
            const data = await response.json();
            if (data.status === 'success') return data.categories;
        } catch (error) {
            console.error('Failed to fetch categories:', error);
        }
        return [];
    };

    const saveProduct = async (formData) => {
        try {
            const response = await fetch(`${API_URL}?action=save_product`, {
                method: 'POST',
                body: formData
            });
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error('Failed to save product: server returned non-JSON:', text);
                alert('Server returned an unexpected response. See console for details.');
                return;
            }

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
            productsTbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-slate-500">No products found.</td></tr>';
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
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">${escapeHTML(product.main_category || product.category)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">${escapeHTML(product.subcategory || '')}</td>
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
    // Use delegated handler so links/buttons (even if added multiple places) open the modal
    document.addEventListener('click', (e) => {
        const target = e.target.closest('#add-product-btn, .add-product-btn');
        if (target) {
            e.preventDefault();
            showModal('Add New Product');
        }
    });
    if (cancelBtn) cancelBtn.addEventListener('click', hideModal);

    if (productForm) {
        productForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (!validateImageSizes()) return;
            saveProduct(new FormData(productForm));
        });
    }

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
                // populate category select (main + sub) and select current
                const categories = await fetchCategories();
                populateModalCategorySelect(categories);
                // set category_id and selects based on product's category_id or category name
                const catIdInput = document.getElementById('category_id');
                const mainSel = document.getElementById('main_category_id');
                const subSel = document.getElementById('subcategory_id');
                if (p.category_id) {
                    catIdInput.value = p.category_id;
                    // find category object to determine parent
                    const catObj = categories.find(c => String(c.id) === String(p.category_id));
                    if (catObj) {
                        if (catObj.parent_id) {
                            mainSel.value = catObj.parent_id;
                            // trigger populate subs
                            mainSel.dispatchEvent(new Event('change'));
                            subSel.value = catObj.name;
                            const sel = Array.from(subSel.options).find(o => o.value === catObj.name);
                            if (sel && sel.dataset && sel.dataset.id) catIdInput.value = sel.dataset.id;
                        } else {
                            mainSel.value = catObj.id;
                            catIdInput.value = catObj.id;
                        }
                    }
                } else if (p.category) {
                    // fallback by name
                    // try to find by name among categories
                    const byName = categories.find(c => c.name === p.category);
                    if (byName) {
                        catIdInput.value = byName.id;
                        if (byName.parent_id) {
                            mainSel.value = byName.parent_id;
                            mainSel.dispatchEvent(new Event('change'));
                            subSel.value = byName.name;
                            const sel = Array.from(subSel.options).find(o => o.value === byName.name);
                            if (sel && sel.dataset && sel.dataset.id) catIdInput.value = sel.dataset.id;
                        } else {
                            mainSel.value = byName.id;
                            catIdInput.value = byName.id;
                        }
                    }
                }
                document.getElementById('price').value = p.price;
                // brand and tags
                if (document.getElementById('brand')) document.getElementById('brand').value = p.brand || '';
                if (document.getElementById('tags')) document.getElementById('tags').value = p.tags || '';
                document.getElementById('description').value = p.description;
                document.getElementById('is_featured').checked = p.is_featured == 1 ? true : false;

                // Make fields read-only (include selects)
                const fields = productForm.querySelectorAll('input, textarea, select');
                fields.forEach(field => {
                    if (field.tagName.toLowerCase() === 'select') field.disabled = true;
                    else field.readOnly = true;
                });

                // Hide file inputs
                const fileInputs = productForm.querySelectorAll('input[type="file"]');
                fileInputs.forEach(input => input.closest('div').style.display = 'none');

                // Populate specifications if present
                try {
                    const specsContainer = document.getElementById('specifications-container');
                    if (specsContainer) {
                        specsContainer.innerHTML = '';
                        const specs = (typeof p.specifications === 'string') ? JSON.parse(p.specifications || '[]') : (p.specifications || []);
                        if (specs.length === 0) {
                            specsContainer.innerHTML = `<div class="spec-row grid grid-cols-2 gap-4 mb-2">\n                                <input type="text" name="spec_title[]" placeholder="Specification Title" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">\n                                <input type="text" name="spec_detail[]" placeholder="Specification Detail" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">\n                            </div>`;
                        } else {
                            specs.forEach(s => {
                                const row = document.createElement('div');
                                row.className = 'spec-row grid grid-cols-2 gap-4 mb-2';
                                row.innerHTML = `<input type="text" name="spec_title[]" value="${escapeHTML(s.title)}" placeholder="Specification Title" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">\n                                    <input type="text" name="spec_detail[]" value="${escapeHTML(s.detail)}" placeholder="Specification Detail" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">`;
                                specsContainer.appendChild(row);
                            });
                        }
                    }
                } catch (err) {
                    console.error('Failed to parse specifications:', err);
                }


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
                    fields.forEach(field => {
                        if (field.tagName.toLowerCase() === 'select') field.disabled = false;
                        else field.readOnly = false;
                    });
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
                const categories = await fetchCategories();
                populateModalCategorySelect(categories);
                const catIdInput = document.getElementById('category_id');
                const mainSel = document.getElementById('main_category_id');
                const subSel = document.getElementById('subcategory_id');
                if (p.category_id) {
                    catIdInput.value = p.category_id;
                    const catObj = categories.find(c => String(c.id) === String(p.category_id));
                    if (catObj) {
                        if (catObj.parent_id) {
                            mainSel.value = catObj.parent_id;
                            mainSel.dispatchEvent(new Event('change'));
                            subSel.value = catObj.name;
                            const sel = Array.from(subSel.options).find(o => o.value === catObj.name);
                            if (sel && sel.dataset && sel.dataset.id) catIdInput.value = sel.dataset.id;
                        } else {
                            mainSel.value = catObj.id;
                            catIdInput.value = catObj.id;
                        }
                    }
                } else if (p.category) {
                    const byName = categories.find(c => c.name === p.category);
                    if (byName) {
                        catIdInput.value = byName.id;
                        if (byName.parent_id) {
                            mainSel.value = byName.parent_id;
                            mainSel.dispatchEvent(new Event('change'));
                            subSel.value = byName.id;
                        } else {
                            mainSel.value = byName.id;
                        }
                    }
                }
                document.getElementById('price').value = p.price;
                if (document.getElementById('brand')) document.getElementById('brand').value = p.brand || '';
                if (document.getElementById('tags')) document.getElementById('tags').value = p.tags || '';
                document.getElementById('description').value = p.description;
                document.getElementById('is_featured').checked = p.is_featured == 1 ? true : false;

                // Ensure fields are editable (include selects)
                const fields = productForm.querySelectorAll('input, textarea, select');
                fields.forEach(field => {
                    if (field.tagName.toLowerCase() === 'select') field.disabled = false;
                    else field.readOnly = false;
                });

                // Show file inputs
                const fileInputs = productForm.querySelectorAll('input[type="file"]');
                fileInputs.forEach(input => input.closest('div').style.display = 'block');

                // Populate specifications for edit
                try {
                    const specsContainer = document.getElementById('specifications-container');
                    if (specsContainer) {
                        specsContainer.innerHTML = '';
                        const specs = (typeof p.specifications === 'string') ? JSON.parse(p.specifications || '[]') : (p.specifications || []);
                        if (specs.length === 0) {
                            specsContainer.innerHTML = `<div class="spec-row grid grid-cols-2 gap-4 mb-2">\n                                <input type="text" name="spec_title[]" placeholder="Specification Title" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">\n                                <input type="text" name="spec_detail[]" placeholder="Specification Detail" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">\n                            </div>`;
                        } else {
                            specs.forEach(s => {
                                const row = document.createElement('div');
                                row.className = 'spec-row grid grid-cols-2 gap-4 mb-2';
                                row.innerHTML = `<input type="text" name="spec_title[]" value="${escapeHTML(s.title)}" placeholder="Specification Title" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">\n                                    <input type="text" name="spec_detail[]" value="${escapeHTML(s.detail)}" placeholder="Specification Detail" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">`;
                                specsContainer.appendChild(row);
                            });
                        }
                    }
                } catch (err) {
                    console.error('Failed to parse specifications for edit:', err);
                }

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

    const populateModalCategorySelect = (categories) => {
        // categories: array of {id,name,parent_id}
        const mainSel = document.getElementById('main_category_id');
        const subSel = document.getElementById('subcategory_id');
        if (!mainSel || !subSel) return;

        // build map of main categories
        const mains = categories.filter(c => c.parent_id === null || c.parent_id === '0' || c.parent_id === 0);
        // Clear mains
        while (mainSel.options.length > 1) mainSel.remove(1);
        mains.forEach(m => {
            const opt = new Option(m.name, m.id);
            mainSel.add(opt);
        });

        // When main changes, populate subs
        mainSel.addEventListener('change', () => {
            const pid = mainSel.value;
            // clear subs
            while (subSel.options.length > 1) subSel.remove(1);
            if (!pid) {
                document.getElementById('category_id').value = '';
                return;
            }
            categories.forEach(c => {
                if (String(c.parent_id) === String(pid)) {
                    const opt = new Option(c.name, c.name);
                    opt.dataset.id = c.id;
                    subSel.add(opt);
                }
            });
            // set category_id to main by default (will be overridden if sub selected)
            document.getElementById('category_id').value = pid;
        });

        // When sub selected, set category_id hidden
        subSel.addEventListener('change', () => {
            const sel = subSel.options[subSel.selectedIndex];
            if (sel && sel.dataset && sel.dataset.id) {
                document.getElementById('category_id').value = sel.dataset.id;
            } else {
                // if no sub selected but main selected, set category_id to main
                const mid = mainSel.value;
                document.getElementById('category_id').value = mid || '';
            }
        });

        // If there is already a selected value in the form, try to preserve it
        const existingCatId = document.getElementById('category_id').value;
        if (existingCatId) {
            // find parent
            const catObj = categories.find(c => String(c.id) === String(existingCatId));
                if (catObj) {
                if (catObj.parent_id) {
                    mainSel.value = catObj.parent_id;
                    // trigger populate subs
                    const event = new Event('change');
                    mainSel.dispatchEvent(event);
                    subSel.value = catObj.name;
                    const sel = Array.from(subSel.options).find(o => o.value === catObj.name);
                    if (sel && sel.dataset && sel.dataset.id) document.getElementById('category_id').value = sel.dataset.id;
                } else {
                    mainSel.value = catObj.id;
                    document.getElementById('category_id').value = catObj.id;
                }
            }
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

    imageInputs.forEach(input => {
        input.addEventListener('change', validateImageSizes);
    });

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
