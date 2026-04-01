<?php if ($_SESSION['user_role'] !== 'admin'): ?>
<div class="empty-state"><p>Access denied. Admin only.</p></div>
<?php return; endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">System Settings</h1>
        <p class="page-subtitle">Configure loan policies, interest rate defaults, and SACCO behavior.</p>
    </div>
</div>

<div class="settings-card">
    <form id="settings-form" onsubmit="saveSettings(event)">
        <div class="form-grid">
            <div class="form-group">
                <label>Default Loan Interest Rate (%)</label>
                <input type="number" name="default_interest_rate" step="0.1" min="0" required>
            </div>
            <div class="form-group">
                <label>Maximum Loan Multiplier</label>
                <input type="number" name="max_loan_multiplier" step="0.1" min="1" required>
            </div>
            <div class="form-group">
                <label>Minimum Savings Ratio</label>
                <input type="number" name="min_savings_ratio" step="0.01" min="0" max="1" required>
            </div>
            <div class="form-group">
                <label>Maximum Loan Term (months)</label>
                <input type="number" name="max_loan_term_months" min="1" required>
            </div>
            <div class="form-group full-width">
                <label>Loan Policy Note</label>
                <textarea name="loan_policy" rows="4"></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<script>
async function loadSettings() {
    const res = await fetch('?api=settings&action=list');
    const data = await res.json();
    if (!data.success) return toast('Unable to load settings', 'error');
    const map = {};
    data.data.forEach(item => map[item.setting_key] = item.setting_value);
    const form = document.getElementById('settings-form');
    form.elements['default_interest_rate'].value = map.default_interest_rate || '12.00';
    form.elements['max_loan_multiplier'].value = map.max_loan_multiplier || '3';
    form.elements['min_savings_ratio'].value = map.min_savings_ratio || '0.33';
    form.elements['max_loan_term_months'].value = map.max_loan_term_months || '60';
    form.elements['loan_policy'].value = map.loan_policy || '';
}

async function saveSettings(e) {
    e.preventDefault();
    const form = document.getElementById('settings-form');
    const data = Object.fromEntries(new FormData(form));
    const res = await fetch('?api=settings&action=update', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) {
        toast(result.message, 'success');
    } else {
        toast(result.error || 'Failed to save settings', 'error');
    }
}

loadSettings();
</script>
