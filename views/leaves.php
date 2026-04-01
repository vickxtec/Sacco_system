<?php if ($_SESSION['user_role'] !== 'admin'): ?>
<div class="empty-state"><p>Access denied. Admin only.</p></div>
<?php return; endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Leave Requests</h1>
        <p class="page-subtitle">Create and manage staff leave applications.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openAddLeave()">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            New Leave
        </button>
    </div>
</div>

<div class="filter-bar">
    <div class="search-wrap">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
        <input type="text" id="leave-search" placeholder="Search by user, type or status..." oninput="loadLeaves()">
    </div>
    <select id="leave-status-filter" onchange="loadLeaves()" class="filter-select">
        <option value="">All Status</option>
        <option value="Pending">Pending</option>
        <option value="Approved">Approved</option>
        <option value="Rejected">Rejected</option>
    </select>
</div>

<div class="table-card">
    <div id="leave-table-container">
        <div class="loading-state"><div class="spinner"></div><span>Loading leave requests...</span></div>
    </div>
</div>

<script>
async function loadLeaves() {
    const search = document.getElementById('leave-search').value;
    const status = document.getElementById('leave-status-filter').value;
    document.getElementById('leave-table-container').innerHTML = '<div class="loading-state"><div class="spinner"></div><span>Loading...</span></div>';

    const res = await fetch(`?api=leaves&action=list&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`);
    const data = await res.json();
    if (!data.success || !data.data.length) {
        document.getElementById('leave-table-container').innerHTML = '<div class="empty-state"><p>No leave requests found.</p></div>';
        return;
    }
    const rows = data.data.map(l => `
        <tr>
            <td>${l.applicant_name}</td>
            <td>${l.leave_type}</td>
            <td>${l.start_date}</td>
            <td>${l.end_date}</td>
            <td>${l.status}</td>
            <td>${l.applied_date}</td>
            <td>
                <div class="action-btns">
                    <button class="btn-icon" title="View" onclick="viewLeave(${l.id})"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg></button>
                    ${l.status === 'Pending' ? `<button class="btn-action approve" onclick="changeLeaveStatus(${l.id}, 'Approved')">Approve</button><button class="btn-action reject" onclick="changeLeaveStatus(${l.id}, 'Rejected')">Reject</button>` : ''}
                </div>
            </td>
        </tr>
    `).join('');

    document.getElementById('leave-table-container').innerHTML = `
        <div class="table-meta">Showing <strong>${data.data.length}</strong> leave requests</div>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>User</th><th>Type</th><th>Start</th><th>End</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </div>
    `;
}

function openAddLeave() {
    openModal('Create Leave Request', `
        <form onsubmit="submitLeave(event)">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>User ID *</label>
                    <input type="number" name="user_id" required placeholder="User ID">
                </div>
                <div class="form-group">
                    <label>Leave Type *</label>
                    <select name="leave_type" required>
                        <option value="Annual">Annual</option>
                        <option value="Sick">Sick</option>
                        <option value="Maternity">Maternity</option>
                        <option value="Compassionate">Compassionate</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" name="end_date" required>
                </div>
                <div class="form-group full-width">
                    <label>Reason *</label>
                    <textarea name="reason" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Leave</button>
            </div>
        </form>
    `);
}

async function submitLeave(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch('?api=leaves&action=create', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) { toast(result.message, 'success'); closeModal(); loadLeaves(); }
    else toast(result.error || 'Failed to create leave request', 'error');
}

async function viewLeave(id) {
    const res = await fetch(`?api=leaves&action=get&id=${id}`);
    const result = await res.json();
    if (!result.success) return toast('Unable to load leave request', 'error');
    const l = result.data;
    openModal('Leave Request Details', `
        <div class="detail-grid">
            <div class="detail-item"><label>User</label><span>${l.applicant_name} (${l.applicant_username})</span></div>
            <div class="detail-item"><label>Leave Type</label><span>${l.leave_type}</span></div>
            <div class="detail-item"><label>Start Date</label><span>${l.start_date}</span></div>
            <div class="detail-item"><label>End Date</label><span>${l.end_date}</span></div>
            <div class="detail-item"><label>Status</label><span>${l.status}</span></div>
            <div class="detail-item"><label>Applied</label><span>${l.applied_date}</span></div>
            <div class="detail-item"><label>Processed By</label><span>${l.processed_by_name || '—'}</span></div>
            <div class="detail-item"><label>Processed Date</label><span>${l.processed_date || '—'}</span></div>
            <div class="detail-item full-width"><label>Reason</label><p>${l.reason}</p></div>
        </div>
    `);
}

async function changeLeaveStatus(id, status) {
    const res = await fetch(`?api=leaves&action=status&id=${id}`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({status}) });
    const result = await res.json();
    if (result.success) { toast(result.message, 'success'); loadLeaves(); }
    else toast(result.error || 'Failed to update status', 'error');
}

loadLeaves();
</script>
