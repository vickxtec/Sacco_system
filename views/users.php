<?php if ($_SESSION['user_role'] !== 'admin'): ?>
<div class="empty-state"><p>Access denied. Admin only.</p></div>
<?php return; endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">System Users</h1>
        <p class="page-subtitle">Create staff accounts and manage roles for admin, manager, teller, and officer.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openAddUser()">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Add Staff
        </button>
    </div>
</div>

<div class="filter-bar">
    <div class="search-wrap">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
        <input type="text" id="user-search" placeholder="Search by username, name or role..." oninput="loadUsers()">
    </div>
    <select id="user-role-filter" onchange="loadUsers()" class="filter-select">
        <option value="">All Roles</option>
        <option value="admin">Admin</option>
        <option value="officer">Officer</option>
        <option value="teller">Teller</option>
    </select>
</div>

<div class="table-card">
    <div id="users-table-container">
        <div class="loading-state"><div class="spinner"></div><span>Loading users...</span></div>
    </div>
</div>

<script>
async function loadUsers() {
    const search = document.getElementById('user-search').value;
    const role = document.getElementById('user-role-filter').value;
    document.getElementById('users-table-container').innerHTML = '<div class="loading-state"><div class="spinner"></div><span>Loading...</span></div>';
    const res = await fetch(`?api=users&action=list&search=${encodeURIComponent(search)}&role=${encodeURIComponent(role)}`);
    const data = await res.json();
    if (!data.success || !data.data.length) {
        document.getElementById('users-table-container').innerHTML = '<div class="empty-state"><p>No users found.</p></div>';
        return;
    }
    const rows = data.data.map(u => `
        <tr>
            <td><code>${u.username}</code></td>
            <td>${u.full_name}</td>
            <td>${u.role}</td>
            <td>${u.created_at}</td>
            <td>
                <div class="action-btns">
                    <button class="btn-icon" title="Edit" onclick="editUser(${u.id})"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg></button>
                    <button class="btn-icon" title="Reset password" onclick="resetUserPassword(${u.id}, ${JSON.stringify(u.username)})"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a1 1 0 00-1 1v2H7a3 3 0 00-3 3v2H3a1 1 0 100 2h1v1a3 3 0 003 3h2v2a1 1 0 102 0v-2h2a3 3 0 003-3v-1h1a1 1 0 100-2h-1V8a3 3 0 00-3-3h-2V3a1 1 0 00-1-1zm-1 6a1 1 0 112 0 1 1 0 01-2 0z"/></svg></button>
                    <button class="btn-icon delete" title="Delete" onclick="deleteUser(${u.id}, ${JSON.stringify(u.username)})"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg></button>
                </div>
            </td>
        </tr>
    `).join('');
    document.getElementById('users-table-container').innerHTML = `
        <div class="table-meta">Showing <strong>${data.data.length}</strong> users</div>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Username</th><th>Name</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function openAddUser() {
    openModal('Create Staff Account', `
        <form onsubmit="submitUser(event)">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="Jane Doe">
                </div>
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required placeholder="jane.doe">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="admin">Admin</option>
                        <option value="officer">Officer</option>
                        <option value="teller">Teller</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    `);
}

async function submitUser(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));
    if (data.password !== data.confirm_password) {
        toast('Passwords do not match', 'error');
        return;
    }
    delete data.confirm_password;
    const res = await fetch('?api=users&action=create', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) {
        toast(result.message, 'success');
        closeModal();
        loadUsers();
    } else {
        toast(result.error || 'Failed to create user', 'error');
    }
}

async function editUser(id) {
    const res = await fetch(`?api=users&action=get&id=${id}`);
    const result = await res.json();
    if (!result.success) return toast('Unable to load user', 'error');
    const user = result.data;
    openModal('Edit User', `
        <form onsubmit="updateUser(event, ${user.id})">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required value="${user.full_name}">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="admin" ${user.role==='admin' ? 'selected' : ''}>Admin</option>
                        <option value="officer" ${user.role==='officer' ? 'selected' : ''}>Officer</option>
                        <option value="teller" ${user.role==='teller' ? 'selected' : ''}>Teller</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    `);
}

async function updateUser(e, id) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));
    const res = await fetch(`?api=users&action=update&id=${id}`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) {
        toast(result.message, 'success');
        closeModal();
        loadUsers();
    } else {
        toast(result.error || 'Update failed', 'error');
    }
}

function resetUserPassword(id, username) {
    openModal(`Reset password for ${username}`, `
        <form onsubmit="submitResetPassword(event, ${id})">
            <div class="form-group">
                <label>New Password *</label>
                <input type="password" name="password" required placeholder="New password">
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required placeholder="Confirm password">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
    `);
}

async function submitResetPassword(e, id) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));
    if (data.password !== data.confirm_password) {
        toast('Passwords do not match', 'error');
        return;
    }
    delete data.confirm_password;
    const res = await fetch(`?api=users&action=reset_password&id=${id}`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) {
        toast(result.message, 'success');
        closeModal();
    } else {
        toast(result.error || 'Password reset failed', 'error');
    }
}

async function deleteUser(id, username) {
    if (!confirm(`Delete user ${username}? This action cannot be undone.`)) return;
    const res = await fetch(`?api=users&action=delete&id=${id}`, { method: 'POST' });
    const result = await res.json();
    if (result.success) {
        toast('User deleted', 'success');
        loadUsers();
    } else {
        toast(result.error || 'Delete failed', 'error');
    }
}

loadUsers();
</script>
