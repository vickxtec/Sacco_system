<?php if ($_SESSION['user_role'] !== 'admin'): ?>
<div class="empty-state"><p>Access denied. Admin only.</p></div>
<?php return; endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">Generate financial summaries and member reports for the SACCO.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="loadReports()">Refresh</button>
        <button class="btn btn-primary" onclick="window.print()">Print Report</button>
    </div>
</div>

<div class="stats-grid" id="reports-stats">
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
</div>

<div class="report-section">
    <div class="report-card">
        <h3>Member Report</h3>
        <div id="member-report"></div>
    </div>
    <div class="report-card">
        <h3>Savings Report</h3>
        <div id="savings-report"></div>
    </div>
    <div class="report-card">
        <h3>Loan Report</h3>
        <div id="loan-report"></div>
    </div>
</div>

<script>
async function loadReports() {
    const res = await fetch('?api=dashboard');
    const data = await res.json();
    if (!data.success) {
        document.getElementById('reports-stats').innerHTML = '<div class="empty-state"><p>Unable to load reports.</p></div>';
        return;
    }
    const m = data.members;
    const s = data.savings;
    const l = data.loans;
    document.getElementById('reports-stats').innerHTML = `
        <div class="stat-card blue">
            <div class="stat-info">
                <span class="stat-value">${m.total_members}</span>
                <span class="stat-label">Total Members</span>
                <span class="stat-sub">${m.active_members} active</span>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-info">
                <span class="stat-value">KES ${fmtNum(s.total_savings)}</span>
                <span class="stat-label">Total Savings</span>
                <span class="stat-sub">${s.total_accounts} accounts</span>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-info">
                <span class="stat-value">KES ${fmtNum(l.total_disbursed)}</span>
                <span class="stat-label">Loans Disbursed</span>
                <span class="stat-sub">${l.active_loans} active</span>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-info">
                <span class="stat-value">KES ${fmtNum(l.total_repayments || 0)}</span>
                <span class="stat-label">Total Repayments</span>
                <span class="stat-sub">${l.pending_loans} pending loans</span>
            </div>
        </div>
    `;

    document.getElementById('member-report').innerHTML = `
        <p>Total Share Capital: <strong>KES ${fmtNum(m.total_shares)}</strong></p>
        <p>New members this month: <strong>${m.new_this_month}</strong></p>
    `;
    document.getElementById('savings-report').innerHTML = `
        <p>Total active savings accounts: <strong>${s.total_accounts}</strong></p>
        <p>Deposits today: <strong>KES ${fmtNum(s.deposits_today || 0)}</strong></p>
        <p>Withdrawals today: <strong>KES ${fmtNum(s.withdrawals_today || 0)}</strong></p>
    `;
    document.getElementById('loan-report').innerHTML = `
        <p>Outstanding loan balance: <strong>KES ${fmtNum(l.total_outstanding)}</strong></p>
        <p>Pending applications: <strong>${l.pending_loans}</strong></p>
        <p>Defaulted loans: <strong>${l.defaulted}</strong></p>
    `;
}

function fmtNum(n) {
    return parseFloat(n || 0).toLocaleString('en-KE', {minimumFractionDigits: 0});
}

loadReports();
</script>
