<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>. Here's what's happening today.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="loadDashboard()">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>
            Refresh
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid" id="stats-grid">
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
    <div class="stat-card loading-card"><div class="loading-pulse"></div></div>
</div>

<!-- Charts & Quick Actions Row -->
<div class="dashboard-grid">
    <div class="dashboard-card">
        <div class="card-header">
            <h3>Portfolio Overview</h3>
            <span class="card-label">Total Assets</span>
        </div>
        <div id="portfolio-display" class="portfolio-bars">
            <div class="loading-pulse" style="height:120px"></div>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h3>Quick Actions</h3>
        </div>
        <div class="quick-actions">
            <a href="?page=members" class="quick-action-btn qa-members">
                <div class="qa-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zm8-2a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v1h8v-1z"/></svg>
                </div>
                <span>Register Member</span>
            </a>
            <a href="?page=savings" class="quick-action-btn qa-savings">
                <div class="qa-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/></svg>
                </div>
                <span>New Deposit</span>
            </a>
            <a href="?page=loans" class="quick-action-btn qa-loans">
                <div class="qa-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                </div>
                <span>Loan Application</span>
            </a>
            <a href="?page=loans" class="quick-action-btn qa-repay">
                <div class="qa-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </div>
                <span>Loan Repayment</span>
            </a>
        </div>
    </div>

    <div class="dashboard-card span-2">
        <div class="card-header">
            <h3>Recent Activity</h3>
            <a href="?page=loans" class="card-link">View all loans →</a>
        </div>
        <div id="recent-loans-table">
            <div class="loading-pulse" style="height:180px"></div>
        </div>
    </div>
</div>

<script>
async function loadDashboard() {
    const dashboardRes = await fetch('?api=dashboard');
    const dashboardData = await dashboardRes.json();
    if (!dashboardData.success) return;

    const m = dashboardData.members, s = dashboardData.savings, l = dashboardData.loans;

    const totalAssets = (parseFloat(s.total_savings||0) + parseFloat(l.total_disbursed||0));

    document.getElementById('stats-grid').innerHTML = `
        <div class="stat-card blue">
            <div class="stat-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zm8 0a3 3 0 11-6 0 3 3 0 016 0zm-4.07 11c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg></div>
            <div class="stat-info">
                <span class="stat-value">${m.total_members}</span>
                <span class="stat-label">Total Members</span>
                <span class="stat-sub">+${m.new_this_month} this month</span>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg></div>
            <div class="stat-info">
                <span class="stat-value">${m.active_members}</span>
                <span class="stat-label">Active Members</span>
                <span class="stat-sub">of ${m.total_members} total</span>
            </div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg></div>
            <div class="stat-info">
                <span class="stat-value">KES ${fmtNum(s.total_savings)}</span>
                <span class="stat-label">Total Savings</span>
                <span class="stat-sub">${s.total_accounts} accounts</span>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg></div>
            <div class="stat-info">
                <span class="stat-value">KES ${fmtNum(l.total_outstanding)}</span>
                <span class="stat-label">Loans Outstanding</span>
                <span class="stat-sub">${l.active_loans} active loans</span>
            </div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg></div>
            <div class="stat-info">
                <span class="stat-value">${l.pending_loans}</span>
                <span class="stat-label">Pending Loans</span>
                <span class="stat-sub">Awaiting approval</span>
            </div>
        </div>
        <div class="stat-card purple">
            <div class="stat-icon"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zm6-4a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zm6-3a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg></div>
            <div class="stat-info">
                <span class="stat-value">KES ${fmtNum(m.total_shares)}</span>
                <span class="stat-label">Share Capital</span>
                <span class="stat-sub">Total member equity</span>
            </div>
        </div>
    `;

    // Portfolio bars
    const savingsW = totalAssets > 0 ? (s.total_savings / totalAssets * 100) : 50;
    const loansW = totalAssets > 0 ? (l.total_disbursed / totalAssets * 100) : 50;
    document.getElementById('portfolio-display').innerHTML = `
        <div class="portfolio-total">KES ${fmtNum(totalAssets)}</div>
        <div class="portfolio-bar-wrap">
            <div class="portfolio-bar-label"><span>Savings</span><span>KES ${fmtNum(s.total_savings)}</span></div>
            <div class="portfolio-track"><div class="portfolio-fill savings-fill" style="width:${savingsW}%"></div></div>
        </div>
        <div class="portfolio-bar-wrap">
            <div class="portfolio-bar-label"><span>Loans Disbursed</span><span>KES ${fmtNum(l.total_disbursed)}</span></div>
            <div class="portfolio-track"><div class="portfolio-fill loans-fill" style="width:${loansW}%"></div></div>
        </div>
        <div class="portfolio-bar-wrap">
            <div class="portfolio-bar-label"><span>Share Capital</span><span>KES ${fmtNum(m.total_shares)}</span></div>
            <div class="portfolio-track"><div class="portfolio-fill shares-fill" style="width:${m.total_shares/totalAssets*100}%"></div></div>
        </div>
    `;

    // Update nav badges
    document.getElementById('members-count').textContent = m.total_members;
    document.getElementById('loans-pending').textContent = l.pending_loans > 0 ? l.pending_loans : '';

    // Load recent pending loans
    const lRes = await fetch('?api=loans&action=list&status=Pending&limit=5');
    const lData = await lRes.json();
    const loans = (lData.data || []);

    if (loans.length === 0) {
        document.getElementById('recent-loans-table').innerHTML = '<div class="empty-state"><p>No pending loans</p></div>';
        return;
    }

    document.getElementById('recent-loans-table').innerHTML = `
        <table class="data-table">
            <thead><tr><th>Loan No.</th><th>Member</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                ${loans.map(l => `
                <tr>
                    <td><code>${l.loan_no}</code></td>
                    <td><strong>${l.full_name}</strong><br><small>${l.member_no}</small></td>
                    <td>${l.loan_type}</td>
                    <td>KES ${fmtNum(l.principal)}</td>
                    <td><span class="badge badge-${l.status.toLowerCase()}">${l.status}</span></td>
                    <td>${l.applied_date}</td>
                </tr>`).join('')}
            </tbody>
        </table>
    `;
}

function fmtNum(n) {
    return parseFloat(n||0).toLocaleString('en-KE', {minimumFractionDigits: 0, maximumFractionDigits: 0});
}

loadDashboard();
</script>