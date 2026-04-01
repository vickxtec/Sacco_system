<div class="page-header">
    <div>
        <h1 class="page-title">Loans</h1>
        <p class="page-subtitle">Manage loan applications, approvals and repayments</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openLoanApplication()">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            New Application
        </button>
    </div>
</div>

<div class="filter-bar">
    <div class="search-wrap">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
        <input type="text" id="loan-search" placeholder="Search by name, loan no..." oninput="loadLoans()">
    </div>
    <select id="loan-status-filter" onchange="loadLoans()" class="filter-select">
        <option value="">All Status</option>
        <option value="Pending">Pending</option>
        <option value="Approved">Approved</option>
        <option value="Active">Active</option>
        <option value="Completed">Completed</option>
        <option value="Defaulted">Defaulted</option>
        <option value="Rejected">Rejected</option>
    </select>
</div>

<div class="table-card">
    <div id="loans-table-container">
        <div class="loading-state"><div class="spinner"></div><span>Loading loans...</span></div>
    </div>
</div>

<script>
async function loadLoans() {
    const search = document.getElementById('loan-search').value;
    const status = document.getElementById('loan-status-filter').value;
    document.getElementById('loans-table-container').innerHTML = '<div class="loading-state"><div class="spinner"></div><span>Loading...</span></div>';

    const res = await fetch(`?api=loans&action=list&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`);
    const data = await res.json();

    if (!data.success || !data.data.length) {
        document.getElementById('loans-table-container').innerHTML = '<div class="empty-state"><p>No loans found</p></div>';
        return;
    }

    const fmt = n => parseFloat(n||0).toLocaleString('en-KE', {minimumFractionDigits: 0});
    const statusActions = (l) => {
        let btns = `<button class="btn-icon" title="View" onclick="viewLoan(${l.id})"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg></button>`;
        if (l.status === 'Pending') {
            btns += `<button class="btn-action approve" onclick="approveRejectLoan(${l.id}, 'Approved')">Approve</button>`;
            btns += `<button class="btn-action reject" onclick="approveRejectLoan(${l.id}, 'Rejected')">Reject</button>`;
        }
        if (l.status === 'Approved') {
            btns += `<button class="btn-action disburse" onclick="disburseLoan(${l.id}, ${l.term_months})">Disburse</button>`;
        }
        if (l.status === 'Active') {
            btns += `<button class="btn-action repay" onclick="openRepayment(${l.id}, '${l.full_name}', '${l.loan_no}', ${l.balance}, ${l.monthly_payment})">Repay</button>`;
        }
        return btns;
    };

    const rows = data.data.map(l => `
        <tr>
            <td><code>${l.loan_no}</code></td>
            <td>
                <div class="member-cell">
                    <div class="member-avatar">${l.full_name.split(' ').map(n=>n[0]).slice(0,2).join('')}</div>
                    <div><strong>${l.full_name}</strong><small>${l.member_no}</small></div>
                </div>
            </td>
            <td><span class="badge badge-type">${l.loan_type}</span></td>
            <td>KES ${fmt(l.principal)}</td>
            <td>${l.interest_rate}%</td>
            <td>${l.term_months} mo.</td>
            <td>KES ${fmt(l.monthly_payment)}</td>
            <td class="${parseFloat(l.balance) > 0 && l.status === 'Active' ? 'text-danger' : ''}">KES ${fmt(l.balance)}</td>
            <td><span class="badge badge-${(l.status||'').toLowerCase()}">${l.status}</span></td>
            <td><div class="action-btns">${statusActions(l)}</div></td>
        </tr>
    `).join('');

    document.getElementById('loans-table-container').innerHTML = `
        <div class="table-meta">Showing <strong>${data.data.length}</strong> loans</div>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Loan No.</th><th>Member</th><th>Type</th><th>Principal</th><th>Rate</th><th>Term</th><th>Monthly</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>
        </div>
    `;
}

async function openLoanApplication() {
    const res = await fetch('?api=members&action=list&status=Active');
    const data = await res.json();
    const members = data.data || [];

    openModal('Loan Application', `
        <form onsubmit="submitLoan(event)">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Member *</label>
                    <select name="member_id" required>
                        <option value="">-- Select Member --</option>
                        ${members.map(m => `<option value="${m.id}">${m.full_name} (${m.member_no})</option>`).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label>Loan Type *</label>
                    <select name="loan_type">
                        <option>Personal</option><option>Business</option><option>Emergency</option><option>Development</option><option>School Fees</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Principal (KES) *</label>
                    <input type="number" name="principal" required min="1000" placeholder="Loan amount" oninput="calcLoan()">
                </div>
                <div class="form-group">
                    <label>Interest Rate (% p.a.) *</label>
                    <input type="number" name="interest_rate" required value="12" step="0.5" min="1" oninput="calcLoan()">
                </div>
                <div class="form-group">
                    <label>Term (months) *</label>
                    <input type="number" name="term_months" required value="12" min="1" max="60" oninput="calcLoan()">
                </div>
                <div class="form-group">
                    <label>Application Date</label>
                    <input type="date" name="applied_date" value="${new Date().toISOString().split('T')[0]}">
                </div>
                <div class="form-group">
                    <label>Guarantor *</label>
                    <select name="guarantor_id" required>
                        <option value="">-- Select guarantor --</option>
                        ${members.map(m => `<option value="${m.id}">${m.full_name} (${m.member_no})</option>`).join('')}
                    </select>
                </div>
                <div class="form-group full-width">
                    <label>Collateral Security *</label>
                    <textarea name="collateral_security" rows="2" placeholder="Describe collateral security" required></textarea>
                </div>
                <div class="form-group full-width">
                    <label>Purpose</label>
                    <textarea name="purpose" rows="2" placeholder="Loan purpose"></textarea>
                </div>
            </div>
            <div class="loan-calc-result" id="loan-calc">
                <div class="calc-item"><span>Monthly Payment</span><strong id="calc-monthly">—</strong></div>
                <div class="calc-item"><span>Total Payable</span><strong id="calc-total">—</strong></div>
                <div class="calc-item"><span>Total Interest</span><strong id="calc-interest">—</strong></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </div>
        </form>
    `);
}

let calcTimer;
async function calcLoan() {
    clearTimeout(calcTimer);
    calcTimer = setTimeout(async () => {
        const f = document.querySelector('#modal form');
        if (!f) return;
        const principal = f.querySelector('[name=principal]').value;
        const rate = f.querySelector('[name=interest_rate]').value;
        const months = f.querySelector('[name=term_months]').value;
        if (!principal || !rate || !months) return;

        const res = await fetch(`?api=loans&action=calculate&principal=${principal}&interest_rate=${rate}&term_months=${months}`);
        const data = await res.json();
        if (data.success) {
            const fmt = n => 'KES ' + parseFloat(n).toLocaleString();
            document.getElementById('calc-monthly').textContent = fmt(data.monthly_payment);
            document.getElementById('calc-total').textContent = fmt(data.total_payable);
            document.getElementById('calc-interest').textContent = fmt(data.total_interest);
        }
    }, 400);
}

async function submitLoan(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    if (!data.guarantor_id) delete data.guarantor_id;
    const res = await fetch('?api=loans&action=apply', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) { toast(`Loan application submitted! No: ${result.loan_no}`, 'success'); closeModal(); loadLoans(); }
    else toast(result.error || 'Failed', 'error');
}

async function approveRejectLoan(id, status) {
    const action = status === 'Approved' ? 'approve' : 'reject';
    if (!confirm(`Are you sure you want to ${action} this loan?`)) return;
    const res = await fetch(`?api=loans&action=status&id=${id}`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({status}) });
    const result = await res.json();
    if (result.success) { toast(`Loan ${status.toLowerCase()} successfully`, 'success'); loadLoans(); }
    else toast(result.error || 'Action failed', 'error');
}

async function disburseLoan(id, term_months) {
    if (!confirm('Disburse this loan? This will mark it as Active.')) return;
    const res = await fetch(`?api=loans&action=status&id=${id}`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({status: 'Active', term_months}) });
    const result = await res.json();
    if (result.success) { toast('Loan disbursed successfully!', 'success'); loadLoans(); }
    else toast(result.error || 'Disbursement failed', 'error');
}

function openRepayment(id, name, loanNo, balance, monthly) {
    const fmt = n => parseFloat(n).toLocaleString();
    openModal('Loan Repayment', `
        <div class="txn-header"><strong>${name}</strong><code>${loanNo}</code></div>
        <div class="balance-display">Outstanding Balance: <strong class="text-danger">KES ${fmt(balance)}</strong></div>
        <form onsubmit="submitRepayment(event, ${id})">
            <div class="form-group">
                <label>Payment Amount (KES) *</label>
                <input type="number" name="amount" required min="1" value="${monthly}" placeholder="Enter amount">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Process Payment</button>
            </div>
        </form>
    `);
}

async function submitRepayment(e, id) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.loan_id = id;
    const res = await fetch('?api=loans&action=repay', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) {
        const msg = result.status === 'Completed' ? 'Loan fully repaid! 🎉' : `Payment processed! Balance: KES ${parseFloat(result.new_balance).toLocaleString()}`;
        toast(msg, 'success');
        closeModal();
        loadLoans();
    } else toast(result.error || 'Payment failed', 'error');
}

async function viewLoan(id) {
    const res = await fetch(`?api=loans&action=get&id=${id}`);
    const data = await res.json();
    if (!data.success) return;
    const l = data.data;
    const fmt = n => 'KES ' + parseFloat(n||0).toLocaleString();
    const pct = l.principal > 0 ? Math.round(((l.principal - l.balance) / l.principal) * 100) : 0;

    openModal('Loan Details', `
        <div class="detail-card">
            <div class="detail-avatar">${l.full_name.split(' ').map(n=>n[0]).slice(0,2).join('')}</div>
            <h3>${l.full_name}</h3>
            <code>${l.loan_no}</code>
            <span class="badge badge-${(l.status||'').toLowerCase()}">${l.status}</span>
        </div>
        <div class="detail-grid">
            <div class="detail-item"><label>Loan Type</label><span>${l.loan_type}</span></div>
            <div class="detail-item"><label>Principal</label><span>${fmt(l.principal)}</span></div>
            <div class="detail-item"><label>Interest Rate</label><span>${l.interest_rate}% p.a.</span></div>
            <div class="detail-item"><label>Term</label><span>${l.term_months} months</span></div>
            <div class="detail-item"><label>Monthly Payment</label><span>${fmt(l.monthly_payment)}</span></div>
            <div class="detail-item"><label>Total Payable</label><span>${fmt(l.total_payable)}</span></div>
            <div class="detail-item"><label>Balance</label><span class="text-danger"><strong>${fmt(l.balance)}</strong></span></div>
            <div class="detail-item"><label>Applied</label><span>${l.applied_date}</span></div>
            ${l.disbursed_date ? `<div class="detail-item"><label>Disbursed</label><span>${l.disbursed_date}</span></div>` : ''}
            ${l.due_date ? `<div class="detail-item"><label>Due Date</label><span>${l.due_date}</span></div>` : ''}
            ${l.guarantor_name ? `<div class="detail-item"><label>Guarantor</label><span>${l.guarantor_name}</span></div>` : ''}
        </div>
        <div class="progress-section">
            <div class="progress-label"><span>Repayment Progress</span><span>${pct}%</span></div>
            <div class="progress-track"><div class="progress-fill" style="width:${pct}%"></div></div>
        </div>
        ${l.purpose ? `<div class="detail-item"><label>Purpose</label><p>${l.purpose}</p></div>` : ''}
    `);
}

loadLoans();
</script>