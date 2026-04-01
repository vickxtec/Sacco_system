<div class="page-header">
    <div>
        <h1 class="page-title">Members</h1>
        <p class="page-subtitle">Manage SACCO membership and member records</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openAddMember()">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Add Member
        </button>
    </div>
</div>

<div class="filter-bar">
    <div class="search-wrap">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
        <input type="text" id="member-search" placeholder="Search by name, ID, phone..." oninput="loadMembers()">
    </div>
    <select id="status-filter" onchange="loadMembers()" class="filter-select">
        <option value="">All Status</option>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
        <option value="Suspended">Suspended</option>
    </select>
</div>

<div class="table-card">
    <div id="members-table-container">
        <div class="loading-state"><div class="spinner"></div><span>Loading members...</span></div>
    </div>
</div>

<script>
async function loadMembers() {
    const search = document.getElementById('member-search').value;
    const status = document.getElementById('status-filter').value;
    document.getElementById('members-table-container').innerHTML = '<div class="loading-state"><div class="spinner"></div><span>Loading...</span></div>';

    const res = await fetch(`?api=members&action=list&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`);
    const data = await res.json();

    if (!data.success || !data.data.length) {
        document.getElementById('members-table-container').innerHTML = '<div class="empty-state"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0z"/></svg><p>No members found</p><button class="btn btn-primary" onclick="openAddMember()">Add First Member</button></div>';
        return;
    }

    const rows = data.data.map(m => `
        <tr>
            <td><code>${m.member_no}</code></td>
            <td>
                <div class="member-cell">
                    <div class="member-avatar">${m.full_name.split(' ').map(n=>n[0]).slice(0,2).join('')}</div>
                    <div>
                        <strong>${m.full_name}</strong>
                        <small>${m.email || '—'}</small>
                    </div>
                </div>
            </td>
            <td>${m.id_number}</td>
            <td>${m.phone}</td>
            <td>${m.gender || '—'}</td>
            <td>KES ${parseFloat(m.share_capital||0).toLocaleString()}</td>
            <td>${m.joined_date}</td>
            <td><span class="badge badge-${(m.status||'').toLowerCase()}">${m.status}</span></td>
            <td>
                <div class="action-btns">
                    <button class="btn-icon" title="View" onclick="viewMember(${m.id})"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg></button>
                    <button class="btn-icon edit" title="Edit" onclick="editMember(${m.id})"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg></button>
                    <button class="btn-icon" title="${m.status === 'Active' ? 'Deactivate' : 'Activate'}" onclick="toggleMemberStatus(${m.id}, '${m.status}')">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v4h4a1 1 0 110 2h-4v4a1 1 0 11-2 0v-4H5a1 1 0 110-2h4V4a1 1 0 011-1z"/></svg>
                    </button>
                    <button class="btn-icon delete" title="Delete" onclick="deleteMember(${m.id}, '${m.full_name}')"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg></button>
                </div>
            </td>
        </tr>
    `).join('');

    document.getElementById('members-table-container').innerHTML = `
        <div class="table-meta">Showing <strong>${data.data.length}</strong> members</div>
        <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Member No.</th><th>Name</th><th>ID Number</th><th>Phone</th>
                    <th>Gender</th><th>Share Capital</th><th>Joined</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
        </div>
    `;
}

function openAddMember() {
    openModal('Add New Member', `
        <form onsubmit="submitMember(event)" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label>ID/Passport Number *</label>
                    <input type="text" name="id_number" required placeholder="National ID">
                </div>
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" required placeholder="+254712345678">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="member@email.com">
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Join Date *</label>
                    <input type="date" name="joined_date" required value="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="form-group">
                    <label>Share Capital (KES)</label>
                    <input type="number" name="share_capital" value="5000" min="0">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Address</label>
                    <textarea name="address" rows="2" placeholder="Physical address"></textarea>
                </div>
                <div class="form-group">
                    <label>Passport Photo</label>
                    <input type="file" name="photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label>ID Photo Front</label>
                    <input type="file" name="id_front" accept="image/*">
                </div>
                <div class="form-group">
                    <label>ID Photo Back</label>
                    <input type="file" name="id_back" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Signature</label>
                    <input type="file" name="signature" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Register Member</button>
            </div>
        </form>
    `);
}

async function submitMember(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch('?api=members&action=create', { method: 'POST', body: formData });
    const result = await res.json();
    if (result.success) {
        toast(result.message || `Member registered! No: ${result.member_no}`, 'success');
        closeModal();
        loadMembers();
    } else {
        toast(result.error || 'Failed to register member', 'error');
    }
}

async function viewMember(id) {
    const res = await fetch(`?api=members&action=get&id=${id}`);
    const data = await res.json();
    if (!data.success) return;
    const m = data.data;
    openModal('Member Details', `
        <div class="detail-card">
            <div class="detail-avatar">${m.full_name.split(' ').map(n=>n[0]).slice(0,2).join('')}</div>
            <h3>${m.full_name}</h3>
            <span class="badge badge-${(m.status||'').toLowerCase()}">${m.status}</span>
        </div>
        <div class="detail-grid">
            <div class="detail-item"><label>Member No.</label><span><code>${m.member_no}</code></span></div>
            <div class="detail-item"><label>ID Number</label><span>${m.id_number}</span></div>
            <div class="detail-item"><label>Phone</label><span>${m.phone}</span></div>
            <div class="detail-item"><label>Email</label><span>${m.email || '—'}</span></div>
            <div class="detail-item"><label>Gender</label><span>${m.gender || '—'}</span></div>
            <div class="detail-item"><label>Date of Birth</label><span>${m.dob || '—'}</span></div>
            <div class="detail-item"><label>Joined Date</label><span>${m.joined_date}</span></div>
            <div class="detail-item"><label>Share Capital</label><span>KES ${parseFloat(m.share_capital||0).toLocaleString()}</span></div>
        </div>
        ${m.address ? `<div class="detail-item"><label>Address</label><p>${m.address}</p></div>` : ''}
    `);
}

async function editMember(id) {
    const res = await fetch(`?api=members&action=get&id=${id}`);
    const data = await res.json();
    if (!data.success) return;
    const m = data.data;
    openModal('Edit Member', `
        <form onsubmit="updateMember(event, ${id})" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required value="${m.full_name}">
                </div>
                <div class="form-group"><label>ID Number *</label><input type="text" name="id_number" required value="${m.id_number}"></div>
                <div class="form-group"><label>Phone *</label><input type="tel" name="phone" required value="${m.phone}"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="${m.email||''}"></div>
                <div class="form-group"><label>Date of Birth</label><input type="date" name="dob" value="${m.dob||''}"></div>
                <div class="form-group"><label>Gender</label>
                    <select name="gender">
                        ${['Male','Female','Other'].map(g=>`<option value="${g}" ${m.gender===g?'selected':''}>${g}</option>`).join('')}
                    </select>
                </div>
                <div class="form-group"><label>Share Capital</label><input type="number" name="share_capital" value="${m.share_capital}"></div>
                <div class="form-group"><label>Status</label>
                    <select name="status">
                        ${['Active','Inactive','Suspended'].map(s=>`<option value="${s}" ${m.status===s?'selected':''}>${s}</option>`).join('')}
                    </select>
                </div>
                <div class="form-group full-width"><label>Address</label><textarea name="address" rows="2">${m.address||''}</textarea></div>
                <div class="form-group">
                    <label>Passport Photo</label>
                    <input type="file" name="photo" accept="image/*">
                </div>
                <div class="form-group">
                    <label>ID Photo Front</label>
                    <input type="file" name="id_front" accept="image/*">
                </div>
                <div class="form-group">
                    <label>ID Photo Back</label>
                    <input type="file" name="id_back" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Signature</label>
                    <input type="file" name="signature" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Member</button>
            </div>
        </form>
    `);
}

async function updateMember(e, id) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch(`?api=members&action=update&id=${id}`, { method: 'POST', body: formData });
    const result = await res.json();
    if (result.success) { toast('Member updated successfully', 'success'); closeModal(); loadMembers(); }
    else toast(result.error || 'Update failed', 'error');
}

async function deleteMember(id, name) {
    if (!confirm(`Delete member "${name}"? This action cannot be undone.`)) return;
    const res = await fetch(`?api=members&action=delete&id=${id}`, { method: 'POST' });
    const result = await res.json();
    if (result.success) { toast('Member deleted', 'success'); loadMembers(); }
    else toast(result.error || 'Delete failed', 'error');
}

async function toggleMemberStatus(id, currentStatus) {
    const nextStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
    const res = await fetch(`?api=members&action=update&id=${id}`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({status: nextStatus})
    });
    const result = await res.json();
    if (result.success) {
        toast(`Member ${nextStatus.toLowerCase()} successfully`, 'success');
        loadMembers();
    } else {
        toast(result.error || 'Status update failed', 'error');
    }
}

loadMembers();
</script>