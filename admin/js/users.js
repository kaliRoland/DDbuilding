document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const usersTbody = document.getElementById('users-tbody');
    const addUserBtn = document.getElementById('add-user-btn');
    console.log('addUserBtn element:', addUserBtn); // Added console log
    const userModal = document.getElementById('user-modal');
    const modalTitle = document.getElementById('modal-title');
    const userForm = document.getElementById('user-form');
    const cancelBtn = document.getElementById('cancel-btn');
    const userIdInput = document.getElementById('user-id');
    const passwordInput = document.getElementById('password'); // Moved here for scope

    const API_URL = 'api/users.php';

    // --- Modal Handling ---
    const showModal = (title, isEditing = false) => {
        console.log('showModal called:', title, 'isEditing:', isEditing);
        modalTitle.textContent = title;
        userModal.classList.remove('hidden');
        userModal.classList.add('flex');
        userForm.reset(); // Clear previous data
        userIdInput.value = '';
        passwordInput.value = ''; // Clear password field

        if (!isEditing) {
            passwordInput.setAttribute('required', 'required');
        } else {
            passwordInput.removeAttribute('required');
        }
        console.log('userModal classes after show:', userModal.classList);
    };

    const hideModal = () => {
        console.log('hideModal called');
        userModal.classList.add('hidden');
        userModal.classList.remove('flex');
        userForm.reset();
        userIdInput.value = '';
        passwordInput.removeAttribute('required'); // Ensure password is not required when modal is hidden
        console.log('userModal classes after hide:', userModal.classList);
    };

    // --- API Functions ---
    const getUsers = async () => {
        try {
            const response = await fetch(`${API_URL}?action=get_users`);
            const data = await response.json();

            if (data.status === 'success') {
                renderUsers(data.users);
            } else {
                alert(`Error: ${data.message}`);
            }
        } catch (error) {
            console.error('Failed to fetch users:', error);
        }
    };

    const saveUser = async (formData) => {
        try {
            const response = await fetch(`${API_URL}?action=save_user`, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Network response was not ok: ${response.status} ${response.statusText} - ${errorText}`);
            }

            const data = await response.json();

            if (data.status === 'success') {
                hideModal();
                getUsers();
            } else {
                alert(`Error: ${data.message}`);
                console.error('API Error:', data.message);
            }
        } catch (error) {
            console.error('Failed to save user:', error);
            alert(`An error occurred while saving user: ${error.message}`);
        }
    };

    const deleteUser = async (id) => {
        if (!confirm('Are you sure you want to delete this user?')) return;

        const formData = new FormData();
        formData.append('id', id);

        try {
            const response = await fetch(`${API_URL}?action=delete_user`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                getUsers();
            } else {
                alert(`Error: ${data.message}`);
            }
        } catch (error) {
            console.error('Failed to delete user:', error);
        }
    };

    // --- Rendering ---
    const renderUsers = (users) => {
        usersTbody.innerHTML = '';
        if (users.length === 0) {
            usersTbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-slate-500">No users found.</td></tr>';
            return;
        }
        users.forEach(user => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">${escapeHTML(user.username)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">${escapeHTML(user.email)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">${escapeHTML(user.role)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">${user.created_at}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="text-amber-400 hover:text-amber-300 edit-btn" data-id="${user.id}">Edit</button>
                    <button class="text-red-500 hover:text-red-400 ml-4 delete-btn" data-id="${user.id}">Delete</button>
                </td>
            `;
            usersTbody.appendChild(tr);
        });
    };

    // --- Event Listeners ---
    if (addUserBtn) { // Check if addUserBtn exists before adding event listener
        addUserBtn.addEventListener('click', () => showModal('Add New User', false));
    }
    cancelBtn.addEventListener('click', hideModal);

    userForm.addEventListener('submit', (e) => {
        e.preventDefault();
        saveUser(new FormData(userForm));
    });

    usersTbody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('edit-btn')) {
            const id = e.target.dataset.id;
            try {
                const response = await fetch(`${API_URL}?action=get_user&id=${id}`);
                const data = await response.json();
                if (data.status === 'success') {
                    const u = data.user;
                    showModal('Edit User', true);
                    userIdInput.value = u.id;
                    document.getElementById('username').value = u.username;
                    document.getElementById('email').value = u.email;
                    document.getElementById('role').value = u.role;
                } else {
                    alert(`Error: ${data.message}`);
                }
            } catch (error) {
                console.error('Failed to fetch user details:', error);
            }
        }
        if (e.target.classList.contains('delete-btn')) {
            deleteUser(e.target.dataset.id);
        }
    });

    // --- Utility ---
    const escapeHTML = (str) => {
        if (str === null || str === undefined) return '';
        const p = document.createElement('p');
        p.appendChild(document.createTextNode(str));
        return p.innerHTML;
    };

    // --- Initial Load ---
    getUsers();
});