@extends('admin.layout')
@section('title', 'Crypto Management')

@section('content')
<div class="content-wrapper">
    <div class="row">
        <div class="col-md-12 grid-margin">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="font-weight-bold mb-0">🔐 Crypto Transaction Management</h4>
                    <small class="text-muted">Monitor and manage SOL & USDT transactions</small>
                </div>
                <div class="d-flex">
                    <button type="button" class="btn btn-primary btn-sm me-2" onclick="refreshAllData()">
                        <i class="ti-reload"></i> Refresh All
                    </button>
                    <button type="button" class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#crypto-settings-modal">
                        <i class="ti-settings"></i> Settings
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" onclick="startMonitoring()">
                        <i class="ti-control-play"></i> Start Monitoring
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="row">
        <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-9">
                            <div class="d-flex align-items-center align-self-start">
                                <h3 class="mb-0" id="total-deposits">$0</h3>
                                <p class="text-success ml-2 mb-0 font-weight-medium">Total Deposits</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="icon icon-box-success">
                                <span class="mdi mdi-arrow-top-right icon-item"></span>
                            </div>
                        </div>
                    </div>
                    <h6 class="text-muted font-weight-normal" id="deposits-count">0 transactions</h6>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-9">
                            <div class="d-flex align-items-center align-self-start">
                                <h3 class="mb-0" id="total-withdrawals">$0</h3>
                                <p class="text-danger ml-2 mb-0 font-weight-medium">Total Withdrawals</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="icon icon-box-danger">
                                <span class="mdi mdi-arrow-bottom-left icon-item"></span>
                            </div>
                        </div>
                    </div>
                    <h6 class="text-muted font-weight-normal" id="withdrawals-count">0 transactions</h6>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-9">
                            <div class="d-flex align-items-center align-self-start">
                                <h3 class="mb-0" id="pending-approvals">0</h3>
                                <p class="text-warning ml-2 mb-0 font-weight-medium">Pending Approvals</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="icon icon-box-warning">
                                <span class="mdi mdi-clock-outline icon-item"></span>
                            </div>
                        </div>
                    </div>
                    <h6 class="text-muted font-weight-normal">Requiring manual review</h6>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-9">
                            <div class="d-flex align-items-center align-self-start">
                                <h3 class="mb-0" id="active-wallets">0</h3>
                                <p class="text-info ml-2 mb-0 font-weight-medium">Active Wallets</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="icon icon-box-info">
                                <span class="mdi mdi-wallet icon-item"></span>
                            </div>
                        </div>
                    </div>
                    <h6 class="text-muted font-weight-normal">Users with crypto wallets</h6>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Tabs --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="cryptoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab">
                                📊 All Transactions
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals" type="button" role="tab">
                                ⏳ Pending Approvals
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="wallets-tab" data-bs-toggle="tab" data-bs-target="#wallets" type="button" role="tab">
                                👛 User Wallets
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                🔒 Security Logs
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rates-tab" data-bs-toggle="tab" data-bs-target="#rates" type="button" role="tab">
                                💹 Exchange Rates
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="cryptoTabContent">
                        {{-- Transactions Tab --}}
                        <div class="tab-pane fade show active" id="transactions" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Recent Crypto Transactions</h5>
                                <div>
                                    <select class="form-select form-select-sm" id="transaction-filter">
                                        <option value="">All Transactions</option>
                                        <option value="deposit">Deposits Only</option>
                                        <option value="withdrawal">Withdrawals Only</option>
                                        <option value="SOL">SOL Only</option>
                                        <option value="USDT">USDT Only</option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover" id="transactions-table">
                                    <thead>
                                        <tr>
                                            <th>TX ID</th>
                                            <th>User</th>
                                            <th>Type</th>
                                            <th>Currency</th>
                                            <th>Amount</th>
                                            <th>USD Value</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactions-tbody">
                                        <tr>
                                            <td colspan="9" class="text-center">Loading transactions...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Approvals Tab --}}
                        <div class="tab-pane fade" id="approvals" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Withdrawal Approvals Required</h5>
                                <button class="btn btn-success btn-sm" onclick="bulkApprove()">
                                    <i class="ti-check"></i> Bulk Approve
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover" id="approvals-table">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="select-all-approvals"></th>
                                            <th>Request ID</th>
                                            <th>User</th>
                                            <th>Currency</th>
                                            <th>Amount</th>
                                            <th>Destination</th>
                                            <th>Risk Score</th>
                                            <th>Requested</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="approvals-tbody">
                                        <tr>
                                            <td colspan="9" class="text-center">Loading approvals...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Wallets Tab --}}
                        <div class="tab-pane fade" id="wallets" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>User Crypto Wallets</h5>
                                <button class="btn btn-info btn-sm" onclick="exportWallets()">
                                    <i class="ti-download"></i> Export List
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover" id="wallets-table">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>Email</th>
                                            <th>Wallet Address</th>
                                            <th>SOL Balance</th>
                                            <th>USDT Balance</th>
                                            <th>Total USD</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="wallets-tbody">
                                        <tr>
                                            <td colspan="8" class="text-center">Loading wallets...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Security Tab --}}
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Security & Fraud Detection</h5>
                                <button class="btn btn-warning btn-sm" onclick="runSecurityScan()">
                                    <i class="ti-shield"></i> Run Security Scan
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover" id="security-table">
                                    <thead>
                                        <tr>
                                            <th>Event ID</th>
                                            <th>User</th>
                                            <th>Event Type</th>
                                            <th>Risk Level</th>
                                            <th>Details</th>
                                            <th>IP Address</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="security-tbody">
                                        <tr>
                                            <td colspan="8" class="text-center">Loading security logs...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Exchange Rates Tab --}}
                        <div class="tab-pane fade" id="rates" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Live Exchange Rates</h5>
                                <button class="btn btn-primary btn-sm" onclick="updateExchangeRates()">
                                    <i class="ti-reload"></i> Update Rates
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h3 id="sol-price">$0.00</h3>
                                            <p class="text-muted">SOL/USD</p>
                                            <small class="text-success" id="sol-change">+0.00%</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h3 id="usdt-price">$1.00</h3>
                                            <p class="text-muted">USDT/USD</p>
                                            <small class="text-success" id="usdt-change">+0.00%</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <canvas id="price-chart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Crypto Settings Modal --}}
<div class="modal fade" id="crypto-settings-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">⚙️ Crypto System Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="crypto-settings-form">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Deposit Settings</h6>
                            <div class="mb-3">
                                <label class="form-label">Minimum SOL Deposit</label>
                                <input type="number" class="form-control" id="min-sol-deposit" step="0.00001" value="0.01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Minimum USDT Deposit</label>
                                <input type="number" class="form-control" id="min-usdt-deposit" step="0.01" value="1.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Withdrawal Settings</h6>
                            <div class="mb-3">
                                <label class="form-label">Auto-Approval Limit (USD)</label>
                                <input type="number" class="form-control" id="auto-approval-limit" value="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Multi-Sig Threshold (USD)</label>
                                <input type="number" class="form-control" id="multisig-threshold" value="1000">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Security Settings</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enable-fraud-detection" checked>
                                <label class="form-check-label">Enable Fraud Detection</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enable-ip-tracking" checked>
                                <label class="form-check-label">Enable IP Tracking</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Monitoring Settings</h6>
                            <div class="mb-3">
                                <label class="form-label">Monitoring Interval (seconds)</label>
                                <input type="number" class="form-control" id="monitoring-interval" value="30">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveCryptoSettings()">Save Settings</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
class CryptoAdminManager {
    constructor() {
        this.apiBaseUrl = '/api/crypto/admin';
        this.refreshInterval = null;
        this.init();
    }

    async init() {
        await this.loadAllData();
        this.setupEventListeners();
        this.startAutoRefresh();
    }

    async loadAllData() {
        await Promise.all([
            this.loadStats(),
            this.loadTransactions(),
            this.loadApprovals(),
            this.loadWallets(),
            this.loadSecurityLogs(),
            this.loadExchangeRates()
        ]);
    }

    async loadStats() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/stats`);
            const data = await response.json();
            
            if (data.success) {
                this.updateStatsDisplay(data.data);
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    updateStatsDisplay(stats) {
        $('#total-deposits').text(`$${stats.total_deposits}`);
        $('#deposits-count').text(`${stats.deposits_count} transactions`);
        $('#total-withdrawals').text(`$${stats.total_withdrawals}`);
        $('#withdrawals-count').text(`${stats.withdrawals_count} transactions`);
        $('#pending-approvals').text(stats.pending_approvals);
        $('#active-wallets').text(stats.active_wallets);
    }

    async loadTransactions() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/transactions`);
            const data = await response.json();
            
            if (data.success) {
                this.renderTransactionsTable(data.data.transactions);
            }
        } catch (error) {
            console.error('Failed to load transactions:', error);
        }
    }

    renderTransactionsTable(transactions) {
        const tbody = $('#transactions-tbody');
        tbody.empty();

        if (transactions.length === 0) {
            tbody.append('<tr><td colspan="9" class="text-center">No transactions found</td></tr>');
            return;
        }

        transactions.forEach(tx => {
            const statusBadge = this.getStatusBadge(tx.status);
            const typeIcon = tx.type === 'deposit' ? '⬇️' : '⬆️';
            
            tbody.append(`
                <tr>
                    <td><code>${tx.transaction_hash.substring(0, 8)}...</code></td>
                    <td>${tx.user.email}</td>
                    <td>${typeIcon} ${tx.type.toUpperCase()}</td>
                    <td><strong>${tx.currency}</strong></td>
                    <td>${tx.amount}</td>
                    <td>$${tx.usd_value}</td>
                    <td>${statusBadge}</td>
                    <td>${new Date(tx.created_at).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewTransaction('${tx.id}')">
                            <i class="ti-eye"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    async loadApprovals() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/approvals`);
            const data = await response.json();
            
            if (data.success) {
                this.renderApprovalsTable(data.data.approvals);
            }
        } catch (error) {
            console.error('Failed to load approvals:', error);
        }
    }

    renderApprovalsTable(approvals) {
        const tbody = $('#approvals-tbody');
        tbody.empty();

        if (approvals.length === 0) {
            tbody.append('<tr><td colspan="9" class="text-center">No pending approvals</td></tr>');
            return;
        }

        approvals.forEach(approval => {
            const riskBadge = this.getRiskBadge(approval.risk_score);
            
            tbody.append(`
                <tr>
                    <td><input type="checkbox" value="${approval.id}"></td>
                    <td>${approval.id}</td>
                    <td>${approval.user.email}</td>
                    <td><strong>${approval.currency}</strong></td>
                    <td>${approval.amount}</td>
                    <td><code>${approval.to_address.substring(0, 8)}...</code></td>
                    <td>${riskBadge}</td>
                    <td>${new Date(approval.created_at).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-sm btn-success me-1" onclick="approveWithdrawal('${approval.id}')">
                            <i class="ti-check"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="rejectWithdrawal('${approval.id}')">
                            <i class="ti-close"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge bg-warning">Pending</span>',
            'completed': '<span class="badge bg-success">Completed</span>',
            'failed': '<span class="badge bg-danger">Failed</span>',
            'cancelled': '<span class="badge bg-secondary">Cancelled</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
    }

    getRiskBadge(score) {
        if (score >= 80) return '<span class="badge bg-danger">High Risk</span>';
        if (score >= 50) return '<span class="badge bg-warning">Medium Risk</span>';
        return '<span class="badge bg-success">Low Risk</span>';
    }

    setupEventListeners() {
        // Tab switching
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', (e) => {
            const target = $(e.target).attr('data-bs-target');
            this.onTabChanged(target);
        });

        // Filter changes
        $('#transaction-filter').on('change', () => this.loadTransactions());
    }

    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.loadStats();
            this.loadTransactions();
            this.loadApprovals();
        }, 30000); // Refresh every 30 seconds
    }

    onTabChanged(target) {
        switch(target) {
            case '#transactions':
                this.loadTransactions();
                break;
            case '#approvals':
                this.loadApprovals();
                break;
            case '#wallets':
                this.loadWallets();
                break;
            case '#security':
                this.loadSecurityLogs();
                break;
            case '#rates':
                this.loadExchangeRates();
                break;
        }
    }
}

// Global functions
let cryptoAdmin;

function refreshAllData() {
    cryptoAdmin.loadAllData();
    toastr.success('Data refreshed successfully!');
}

function startMonitoring() {
    fetch('/api/crypto/admin/start-monitoring', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success('Deposit monitoring started!');
            } else {
                toastr.error('Failed to start monitoring');
            }
        });
}

async function approveWithdrawal(id) {
    if (!confirm('Are you sure you want to approve this withdrawal?')) return;
    
    try {
        const response = await fetch(`/api/crypto/admin/approve/${id}`, { method: 'POST' });
        const data = await response.json();
        
        if (data.success) {
            toastr.success('Withdrawal approved successfully!');
            cryptoAdmin.loadApprovals();
            cryptoAdmin.loadStats();
        } else {
            toastr.error(data.message || 'Failed to approve withdrawal');
        }
    } catch (error) {
        toastr.error('Failed to approve withdrawal');
    }
}

async function rejectWithdrawal(id) {
    const reason = prompt('Please provide a reason for rejection:');
    if (!reason) return;
    
    try {
        const response = await fetch(`/api/crypto/admin/reject/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason })
        });
        const data = await response.json();
        
        if (data.success) {
            toastr.success('Withdrawal rejected!');
            cryptoAdmin.loadApprovals();
            cryptoAdmin.loadStats();
        } else {
            toastr.error(data.message || 'Failed to reject withdrawal');
        }
    } catch (error) {
        toastr.error('Failed to reject withdrawal');
    }
}

function saveCryptoSettings() {
    const settings = {
        min_sol_deposit: $('#min-sol-deposit').val(),
        min_usdt_deposit: $('#min-usdt-deposit').val(),
        auto_approval_limit: $('#auto-approval-limit').val(),
        multisig_threshold: $('#multisig-threshold').val(),
        enable_fraud_detection: $('#enable-fraud-detection').is(':checked'),
        enable_ip_tracking: $('#enable-ip-tracking').is(':checked'),
        monitoring_interval: $('#monitoring-interval').val()
    };

    fetch('/api/crypto/admin/settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success('Settings saved successfully!');
            $('#crypto-settings-modal').modal('hide');
        } else {
            toastr.error('Failed to save settings');
        }
    });
}

// Initialize when document is ready
$(document).ready(() => {
    cryptoAdmin = new CryptoAdminManager();
});
</script>
@endsection 