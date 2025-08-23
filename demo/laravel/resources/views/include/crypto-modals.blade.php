{{-- Crypto Deposit Modal --}}
<div class="modal fade l-modal w-480" id="crypto-deposit-modal" tabindex="-1" aria-labelledby="crypto-deposit-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header login-header justify-content-center">
                <span class="material-symbols-outlined absolute-btn text-dark f-18 bold-icon m-0"
                    data-bs-dismiss="modal" aria-label="Close">
                    close
                </span>
                <h5 class="modal-title pt-2">Crypto Deposit</h5>
            </div>
            <div class="modal-body pt-1">
                <div class="crypto-deposit-content">
                    <!-- Currency Info -->
                    <div class="text-center mb-3">
                        <h6>Deposit <span id="crypto-deposit-currency">SOL</span></h6>
                        <small class="text-muted">Network: <span id="crypto-deposit-network">Solana</span></small>
                    </div>

                    <!-- QR Code -->
                    <div class="text-center mb-3" id="crypto-qr-code">
                        <div class="qr-code-placeholder">
                            Generating QR Code...
                        </div>
                    </div>

                    <!-- Deposit Address -->
                    <div class="mb-3">
                        <label class="form-label text-dark">Deposit Address</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="crypto-deposit-address" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copy-deposit-address">
                                <i class="material-symbols-outlined">content_copy</i>
                            </button>
                        </div>
                        <small class="text-muted">
                            Address: <span id="crypto-deposit-address-display"></span>
                        </small>
                    </div>

                    <!-- Deposit Instructions -->
                    <div class="alert alert-info">
                        <h6><i class="material-symbols-outlined">info</i> Important Instructions:</h6>
                        <ul class="mb-0 small">
                            <li>Only send <strong><span id="crypto-deposit-currency-2">SOL</span></strong> to this address</li>
                            <li>Minimum deposit: <span id="crypto-min-deposit">0.01 SOL</span></li>
                            <li>Deposits are automatically credited within 1-2 minutes</li>
                            <li>Do not send from exchanges that don't support Solana</li>
                        </ul>
                    </div>

                    <!-- Current Balances -->
                    <div class="row">
                        <div class="col-6">
                            <div class="balance-card">
                                <div class="small text-muted">SOL Balance</div>
                                <div class="fw-bold" id="crypto-sol-balance">0 SOL</div>
                                <div class="small text-muted" id="sol-rate">$0.00</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="balance-card">
                                <div class="small text-muted">USDT Balance</div>
                                <div class="fw-bold" id="crypto-usdt-balance">0 USDT</div>
                                <div class="small text-muted" id="usdt-rate">$0.00</div>
                            </div>
                        </div>
                    </div>

                    <!-- Total USD Value -->
                    <div class="text-center mt-3">
                        <div class="fw-bold">Total Crypto Value: <span id="crypto-total-usd">$0.00</span></div>
                        <button class="btn btn-sm btn-outline-primary mt-2" id="refresh-crypto-balance">
                            <i class="material-symbols-outlined">refresh</i> Refresh Balance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Crypto Withdrawal Modal --}}
<div class="modal fade l-modal w-480" id="crypto-withdrawal-modal" tabindex="-1" aria-labelledby="crypto-withdrawal-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header login-header justify-content-center">
                <span class="material-symbols-outlined absolute-btn text-dark f-18 bold-icon m-0"
                    data-bs-dismiss="modal" aria-label="Close">
                    close
                </span>
                <h5 class="modal-title pt-2">Crypto Withdrawal</h5>
            </div>
            <div class="modal-body pt-1">
                <form id="crypto-withdrawal-form">
                    @csrf
                    
                    <!-- Currency Selection -->
                    <div class="mb-3">
                        <label for="withdrawal-currency" class="form-label text-dark">Currency</label>
                        <select class="form-control" id="withdrawal-currency" name="currency" required>
                            <option value="">Select currency...</option>
                        </select>
                    </div>

                    <!-- Amount -->
                    <div class="mb-3">
                        <label for="withdrawal-amount" class="form-label text-dark">Amount</label>
                        <div class="login-controls">
                            <input type="number" class="form-control text-indent-0" id="withdrawal-amount" 
                                   name="amount" step="0.00000001" min="0" required>
                        </div>
                        <small class="text-muted">Available: <span id="available-balance">0</span></small>
                    </div>

                    <!-- Destination Address -->
                    <div class="mb-3">
                        <label for="withdrawal-address" class="form-label text-dark">Destination Address</label>
                        <div class="login-controls">
                            <input type="text" class="form-control text-indent-0" id="withdrawal-address" 
                                   name="to_address" placeholder="Enter Solana wallet address" required>
                        </div>
                        <small class="text-muted">Double-check this address. Transfers cannot be reversed.</small>
                    </div>

                    <!-- Security Password -->
                    <div class="mb-3">
                        <label for="withdrawal-password" class="form-label text-dark">Confirm Password</label>
                        <div class="login-controls">
                            <input type="password" class="form-control text-indent-0" id="withdrawal-password" 
                                   name="password" placeholder="Enter your account password" required>
                        </div>
                        <small class="text-muted">Enter your account password for security verification</small>
                    </div>

                    <!-- Withdrawal Fees -->
                    <div class="alert alert-warning">
                        <h6><i class="material-symbols-outlined">warning</i> Withdrawal Information:</h6>
                        <ul class="mb-0 small">
                            <li>Network fee: ~0.000005 SOL (automatically deducted)</li>
                            <li>Minimum withdrawal: 0.01 SOL / 1 USDT</li>
                            <li>Processing time: 1-5 minutes</li>
                            <li>Large withdrawals may require admin approval</li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="register-btn rounded-pill d-flex align-items-center w-100 mt-3 orange-shadow">
                        <i class="material-symbols-outlined me-2">send</i>
                        WITHDRAW
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Crypto Transaction History Modal --}}
<div class="modal fade l-modal w-600" id="crypto-history-modal" tabindex="-1" aria-labelledby="crypto-history-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header login-header justify-content-center">
                <span class="material-symbols-outlined absolute-btn text-dark f-18 bold-icon m-0"
                    data-bs-dismiss="modal" aria-label="Close">
                    close
                </span>
                <h5 class="modal-title pt-2">Crypto Transaction History</h5>
            </div>
            <div class="modal-body pt-1">
                <div class="transaction-filters mb-3">
                    <div class="row">
                        <div class="col-6">
                            <select class="form-control form-control-sm" id="history-filter-type">
                                <option value="">All Transactions</option>
                                <option value="deposit">Deposits Only</option>
                                <option value="withdrawal">Withdrawals Only</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <select class="form-control form-control-sm" id="history-filter-currency">
                                <option value="">All Currencies</option>
                                <option value="SOL">SOL Only</option>
                                <option value="USDT">USDT Only</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="crypto-transaction-history">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div>Loading transactions...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Crypto Balance Widget (for header integration) --}}
<div class="crypto-balance-widget d-none" id="crypto-balance-widget">
    <div class="dropdown">
        <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="material-symbols-outlined">currency_bitcoin</i>
            <span id="header-crypto-balance">$0.00</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header">Crypto Balances</h6></li>
            <li>
                <div class="dropdown-item-text">
                    <div class="d-flex justify-content-between">
                        <span>SOL:</span>
                        <span id="header-sol-balance">0</span>
                    </div>
                </div>
            </li>
            <li>
                <div class="dropdown-item-text">
                    <div class="d-flex justify-content-between">
                        <span>USDT:</span>
                        <span id="header-usdt-balance">0</span>
                    </div>
                </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" onclick="aviatorCrypto.generateDepositAddress('SOL')">
                <i class="material-symbols-outlined">add</i> Deposit Crypto
            </a></li>
            <li><a class="dropdown-item" href="#" onclick="aviatorCrypto.showWithdrawalModal()">
                <i class="material-symbols-outlined">send</i> Withdraw Crypto
            </a></li>
            <li><a class="dropdown-item" href="#" onclick="aviatorCrypto.loadTransactionHistory(); $('#crypto-history-modal').modal('show')">
                <i class="material-symbols-outlined">history</i> Transaction History
            </a></li>
        </ul>
    </div>
</div>

<style>
/* Crypto-specific styling */
.balance-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 10px;
    border-radius: 8px;
    text-align: center;
}

.qr-code-placeholder {
    width: 200px;
    height: 200px;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border-radius: 8px;
}

.crypto-balance-widget .dropdown-menu {
    min-width: 200px;
}

.transaction-item {
    padding: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    transition: background-color 0.2s;
}

.transaction-item:hover {
    background: rgba(255, 255, 255, 0.05);
}

.crypto-options {
    background: rgba(255, 255, 255, 0.05);
    padding: 15px;
    border-radius: 8px;
    margin-top: 10px;
}

/* Loading animation */
.balance-glow {
    animation: balanceGlow 2s ease-in-out;
}

@keyframes balanceGlow {
    0%, 100% { 
        box-shadow: 0 0 5px rgba(255, 215, 0, 0.3);
        transform: scale(1);
    }
    50% { 
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.8);
        transform: scale(1.05);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .qr-code-placeholder {
        width: 150px;
        height: 150px;
    }
    
    .crypto-balance-widget {
        font-size: 0.9em;
    }
}
</style> 