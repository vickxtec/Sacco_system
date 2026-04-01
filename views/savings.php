<div class="page-header">
    <div>
        <h1 class="page-title">Savings Accounts</h1>
        <p class="page-subtitle">Manage member savings accounts and transactions</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openAddAccount()">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            New Account
        </button>
    </div>
</div>

<div class="filter-bar">
    <div class="search-wrap">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
        <input type="text" id="savings-search" placeholder="Search by name, account no..." oninput="debouncedLoadSavings()">
    </div>
</div>

<div class="table-card">
    <div id="savings-table-container">
        <div class="loading-state"><div class="spinner"></div><span>Loading accounts...</span></div>
    </div>
</div>

<script>
let savingsSearchTimer;
const MAX_SAVINGS_ROWS = 50;
const canPrintStatement = <?= $_SESSION['user_role'] === 'admin' ? 'true' : 'false' ?>;

function debouncedLoadSavings() {
    clearTimeout(savingsSearchTimer);
    savingsSearchTimer = setTimeout(loadSavings, 250);
}

async function loadSavings() {
    const search = document.getElementById('savings-search').value.trim();
    const limit = MAX_SAVINGS_ROWS;
    document.getElementById('savings-table-container').innerHTML = '<div class="loading-state"><div class="spinner"></div><span>Loading...</span></div>';

    const res = await fetch(`?api=savings&action=list&search=${encodeURIComponent(search)}&limit=${limit}`);
    const data = await res.json();
    const savings = data.data || [];

    if (!data.success || !savings.length) {
        document.getElementById('savings-table-container').innerHTML = '<div class="empty-state"><p>No savings accounts found</p></div>';
        return;
    }

    const visibleSavings = savings.slice(0, limit);
    const remaining = savings.length - visibleSavings.length;
    const rows = visibleSavings.map(s => `
        <tr>
            <td><code>${s.account_no}</code></td>
            <td>
                <div class="member-cell">
                    <div class="member-avatar">${s.full_name.split(' ').map(n=>n[0]).slice(0,2).join('')}</div>
                    <div><strong>${s.full_name}</strong><small>${s.member_no}</small></div>
                </div>
            </td>
            <td><span class="badge badge-type">${s.account_type}</span></td>
            <td><strong>KES ${parseFloat(s.balance||0).toLocaleString()}</strong></td>
            <td>${s.interest_rate}%</td>
            <td>${s.opened_date}</td>
            <td><span class="badge badge-${(s.status||'').toLowerCase()}">${s.status}</span></td>
            <td>
                <div class="action-btns">
                    <button class="btn-action deposit" onclick="openDeposit(${s.id}, '${s.full_name}', '${s.account_no}')">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                        Deposit
                    </button>
                    <button class="btn-action withdraw" onclick="openWithdraw(${s.id}, '${s.full_name}', '${s.account_no}', ${s.balance})">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                        Withdraw
                    </button>
                    <button class="btn-icon" title="Transactions" onclick="viewTransactions(${s.id}, '${s.account_no}')">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 000 2h14a1 1 0 100-2H3zm0 4a1 1 0 000 2h14a1 1 0 100-2H3zm0 4a1 1 0 000 2h7a1 1 0 100-2H3z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    document.getElementById('savings-table-container').innerHTML = `
        <div class="table-meta">Showing <strong>${visibleSavings.length}</strong>${remaining > 0 ? ` of ${savings.length}` : ''} accounts</div>
        <div class="table-scroll">
        <table class="data-table">
            <thead><tr><th>Account No.</th><th>Member</th><th>Type</th><th>Balance</th><th>Interest Rate</th><th>Opened</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>${rows}</tbody>
        </table>
        </div>
        ${remaining > 0 ? `<div class="table-note">Showing first ${visibleSavings.length} results. Refine your search to reduce load.</div>` : ''}
    `;
}

async function openAddAccount() {
    // Load members for dropdown
    const res = await fetch('?api=members&action=list&status=Active');
    const data = await res.json();
    const members = data.data || [];

    openModal('Open Savings Account', `
        <form onsubmit="submitAccount(event)">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Member *</label>
                    <select name="member_id" required>
                        <option value="">-- Select Member --</option>
                        ${members.map(m => `<option value="${m.id}">${m.full_name} (${m.member_no})</option>`).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label>Account Type</label>
                    <select name="account_type">
                        <option>Regular</option><option>Fixed</option><option>Holiday</option><option>Junior</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Interest Rate (%)</label>
                    <input type="number" name="interest_rate" value="3.5" step="0.1" min="0">
                </div>
                <div class="form-group">
                    <label>Initial Deposit (KES)</label>
                    <input type="number" name="initial_deposit" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>Opening Date</label>
                    <input type="date" name="opened_date" value="${new Date().toISOString().split('T')[0]}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Open Account</button>
            </div>
        </form>
    `);
}

async function submitAccount(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch('?api=savings&action=create', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) { toast(`Account opened! No: ${result.account_no}`, 'success'); closeModal(); loadSavings(); }
    else toast(result.error || 'Failed', 'error');
}

function openDeposit(id, name, accNo) {
    openModal('Make Deposit', `
        <div class="txn-header"><strong>${name}</strong><code>${accNo}</code></div>
        <form onsubmit="submitDeposit(event, ${id})">
            <div class="form-group">
                <label>Amount (KES) *</label>
                <input type="number" name="amount" required min="1" placeholder="Enter amount">
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" placeholder="Transaction notes (optional)">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Confirm Deposit</button>
            </div>
        </form>
    `);
}

async function submitDeposit(e, id) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.savings_id = id;
    const res = await fetch('?api=savings&action=deposit', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) { toast(`Deposit successful! New balance: KES ${parseFloat(result.new_balance).toLocaleString()}`, 'success'); closeModal(); loadSavings(); }
    else toast(result.error || 'Deposit failed', 'error');
}

function openWithdraw(id, name, accNo, balance) {
    openModal('Make Withdrawal', `
        <div class="txn-header"><strong>${name}</strong><code>${accNo}</code></div>
        <div class="balance-display">Available Balance: <strong>KES ${parseFloat(balance).toLocaleString()}</strong></div>
        <form onsubmit="submitWithdraw(event, ${id})">
            <div class="form-group">
                <label>Amount (KES) *</label>
                <input type="number" name="amount" required min="1" max="${balance}" placeholder="Enter amount">
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" placeholder="Transaction notes (optional)">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Withdrawal</button>
            </div>
        </form>
    `);
}

async function submitWithdraw(e, id) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.savings_id = id;
    const res = await fetch('?api=savings&action=withdraw', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) { toast(`Withdrawal successful! New balance: KES ${parseFloat(result.new_balance).toLocaleString()}`, 'success'); closeModal(); loadSavings(); }
    else toast(result.error || 'Withdrawal failed', 'error');
}

async function viewTransactions(id, accNo) {
    const res = await fetch(`?api=savings&action=transactions&id=${id}`);
    const data = await res.json();
    const txns = data.data || [];

    openModal(`Transactions — ${accNo}`, txns.length === 0
        ? '<div class="empty-state"><p>No transactions found</p></div>'
        : `<div class="modal-action-bar">
                <div class="txn-title">Account statement for ${accNo}</div>
                ${canPrintStatement ? `<button type="button" class="btn btn-secondary" onclick="printStatement('${accNo}')">Print Statement</button>` : ''}
           </div>
           <div class="table-scroll"><table class="data-table">
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance After</th><th>Reference</th><th>Notes</th></tr></thead>
            <tbody>
                ${txns.map(t => `
                <tr>
                    <td>${t.transaction_date}</td>
                    <td><span class="badge badge-${t.transaction_type === 'Deposit' ? 'active' : 'inactive'}">${t.transaction_type}</span></td>
                    <td class="${t.transaction_type === 'Withdrawal' ? 'text-danger' : 'text-success'}">
                        ${t.transaction_type === 'Withdrawal' ? '-' : '+'}KES ${parseFloat(t.amount).toLocaleString()}
                    </td>
                    <td>KES ${parseFloat(t.balance_after).toLocaleString()}</td>
                    <td><code>${t.reference}</code></td>
                    <td>${t.notes || '—'}</td>
                </tr>`).join('')}
            </tbody>
        </table></div>`
    );
}

function printStatement(accNo) {
    const table = document.querySelector('#modal-body table.data-table');
    if (!table) {
        toast('No statement available to print', 'error');
        return;
    }

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        toast('Unable to open print window', 'error');
        return;
    }

    const styles = `
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 20px; }
        h1 { margin-bottom: 0.25rem; }
        p { margin: 0.25rem 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f7f7f7; }
        .text-right { text-align: right; }
    `;

    printWindow.document.write(`
        <html>
            <head>
                <title>Statement ${accNo}</title>
                <style>${styles}</style>
            </head>
            <body>
                <h1>Account Statement</h1>
                <p><strong>Account No:</strong> ${accNo}</p>
                <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                ${table.outerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

loadSavings();
</script>