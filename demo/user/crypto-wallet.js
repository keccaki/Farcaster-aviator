/**
 * Aviator Crypto Wallet Integration
 * Professional crypto functionality that maintains game compatibility
 * Supports SOL and USDT deposits/withdrawals with Solana blockchain
 */

class AviatorCryptoWallet {
    constructor() {
        this.apiBaseUrl = '/api/crypto';
        this.currentUser = null;
        this.walletData = null;
        this.balances = { SOL: 0, USDT: 0 };
        this.exchangeRates = { SOL: 0, USDT: 0 };
        this.depositMonitorInterval = null;
        this.qrCodeGenerator = null;
        
        this.init();
    }

    async init() {
        try {
            await this.loadUserData();
            await this.loadWalletData();
            await this.loadExchangeRates();
            this.setupEventListeners();
            this.startDepositMonitoring();
            
            console.log('🚀 Aviator Crypto Wallet initialized successfully');
        } catch (error) {
            console.error('❌ Crypto wallet initialization failed:', error);
            this.showError('Crypto wallet initialization failed. Please refresh the page.');
        }
    }

    // ================================
    // CORE WALLET OPERATIONS
    // ================================

    async loadUserData() {
        try {
            const response = await fetch('/get_user_details');
            const data = await response.json();
            
            if (data.isSuccess) {
                this.currentUser = data.data;
            } else {
                throw new Error('Failed to load user data');
            }
        } catch (error) {
            console.error('Failed to load user data:', error);
            throw error;
        }
    }

    async loadWalletData() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/wallet`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.walletData = data.data;
                await this.updateBalances();
            } else {
                // Create wallet if it doesn't exist
                await this.createWallet();
            }
        } catch (error) {
            console.error('Failed to load wallet data:', error);
            throw error;
        }
    }

    async createWallet() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/wallet`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.walletData = data.data;
                this.showSuccess('🎉 Crypto wallet created! You can now deposit SOL and USDT.');
                await this.updateBalances();
            } else {
                throw new Error(data.message || 'Failed to create wallet');
            }
        } catch (error) {
            console.error('Failed to create wallet:', error);
            throw error;
        }
    }

    async updateBalances() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/balance`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.balances = data.data.balances;
                this.updateBalanceDisplay();
            }
        } catch (error) {
            console.error('Failed to update balances:', error);
        }
    }

    async loadExchangeRates() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/exchange-rates`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.exchangeRates = data.data.rates;
                this.updateExchangeRateDisplay();
            }
        } catch (error) {
            console.error('Failed to load exchange rates:', error);
        }
    }

    // ================================
    // DEPOSIT FUNCTIONALITY
    // ================================

    generateDepositAddress(currency = 'SOL') {
        if (!this.walletData) {
            this.showError('Wallet not initialized. Please refresh the page.');
            return;
        }

        const address = this.walletData.sol_address;
        
        // Update deposit UI
        this.updateDepositUI(address, currency);
        
        // Generate QR code
        this.generateQRCode(address, currency);
        
        // Show deposit modal
        this.showDepositModal(currency);
    }

    updateDepositUI(address, currency) {
        // Update address display
        $('#crypto-deposit-address').val(address);
        $('#crypto-deposit-address-display').text(this.formatAddress(address));
        
        // Update currency info
        $('#crypto-deposit-currency').text(currency);
        $('#crypto-deposit-network').text('Solana');
        
        // Update minimum deposit
        const minDeposit = currency === 'SOL' ? '0.01' : '1';
        $('#crypto-min-deposit').text(`${minDeposit} ${currency}`);
        
        // Set up copy functionality
        this.setupCopyButton(address);
    }

    generateQRCode(address, currency) {
        const qrContainer = $('#crypto-qr-code');
        qrContainer.empty();
        
        // Create QR code data
        const qrData = `solana:${address}`;
        
        // Generate QR code using a simple library or service
        const qrCodeHtml = `
            <div class="qr-code-container">
                <div class="qr-code-placeholder" style="width: 200px; height: 200px; background: #f0f0f0; border: 2px solid #333; display: flex; align-items: center; justify-content: center; font-size: 12px; text-align: center;">
                    <div>
                        <div>QR Code</div>
                        <div style="margin-top: 5px; font-size: 10px;">${this.formatAddress(address)}</div>
                    </div>
                </div>
                <div class="qr-code-info mt-2">
                    <small>Scan to send ${currency} to your wallet</small>
                </div>
            </div>
        `;
        
        qrContainer.html(qrCodeHtml);
    }

    setupCopyButton(address) {
        $('#copy-deposit-address').off('click').on('click', () => {
            navigator.clipboard.writeText(address).then(() => {
                this.showSuccess('✅ Address copied to clipboard!');
                
                // Visual feedback
                const btn = $('#copy-deposit-address');
                const originalText = btn.text();
                btn.text('Copied!').addClass('btn-success');
                
                setTimeout(() => {
                    btn.text(originalText).removeClass('btn-success');
                }, 2000);
            }).catch(() => {
                this.showError('Failed to copy address. Please copy manually.');
            });
        });
    }

    showDepositModal(currency) {
        $('#crypto-deposit-modal').modal('show');
    }

    // ================================
    // WITHDRAWAL FUNCTIONALITY
    // ================================

    async processWithdrawal(currency, amount, toAddress) {
        try {
            // Validate inputs
            if (!this.validateWithdrawal(currency, amount, toAddress)) {
                return false;
            }

            // Show loading
            this.showLoading('Processing withdrawal...');

            const response = await fetch(`${this.apiBaseUrl}/withdraw`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    currency: currency,
                    amount: parseFloat(amount),
                    to_address: toAddress,
                    user_password: $('#withdrawal-password').val() // For security
                })
            });

            const data = await response.json();
            this.hideLoading();

            if (data.success) {
                this.showSuccess(`✅ Withdrawal request submitted! Transaction ID: ${data.data.transaction_id}`);
                this.updateBalances();
                this.closeWithdrawalModal();
                
                // Update game balance
                this.updateGameBalance();
                
                return true;
            } else {
                this.showError(data.message || 'Withdrawal failed');
                return false;
            }
        } catch (error) {
            this.hideLoading();
            console.error('Withdrawal error:', error);
            this.showError('Withdrawal failed. Please try again.');
            return false;
        }
    }

    validateWithdrawal(currency, amount, toAddress) {
        // Validate amount
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount) || numAmount <= 0) {
            this.showError('Please enter a valid amount');
            return false;
        }

        // Check minimum withdrawal
        const minWithdrawal = currency === 'SOL' ? 0.01 : 1;
        if (numAmount < minWithdrawal) {
            this.showError(`Minimum withdrawal is ${minWithdrawal} ${currency}`);
            return false;
        }

        // Check balance
        if (numAmount > this.balances[currency]) {
            this.showError('Insufficient balance');
            return false;
        }

        // Validate address
        if (!this.isValidSolanaAddress(toAddress)) {
            this.showError('Invalid Solana address');
            return false;
        }

        return true;
    }

    isValidSolanaAddress(address) {
        // Basic Solana address validation
        return address && address.length >= 32 && address.length <= 44 && /^[1-9A-HJ-NP-Za-km-z]+$/.test(address);
    }

    closeWithdrawalModal() {
        $('#crypto-withdrawal-modal').modal('hide');
        this.clearWithdrawalForm();
    }

    clearWithdrawalForm() {
        $('#withdrawal-currency').val('');
        $('#withdrawal-amount').val('');
        $('#withdrawal-address').val('');
        $('#withdrawal-password').val('');
    }

    // ================================
    // DEPOSIT MONITORING
    // ================================

    startDepositMonitoring() {
        // Check for new deposits every 30 seconds
        this.depositMonitorInterval = setInterval(async () => {
            await this.checkForNewDeposits();
        }, 30000);
    }

    stopDepositMonitoring() {
        if (this.depositMonitorInterval) {
            clearInterval(this.depositMonitorInterval);
            this.depositMonitorInterval = null;
        }
    }

    async checkForNewDeposits() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/check-deposits`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const data = await response.json();
            
            if (data.success && data.data.new_deposits > 0) {
                // New deposits found!
                this.handleNewDeposits(data.data);
            }
        } catch (error) {
            console.error('Deposit monitoring error:', error);
        }
    }

    handleNewDeposits(depositData) {
        // Update balances
        this.updateBalances();
        
        // Update game balance
        this.updateGameBalance();
        
        // Show notification
        this.showSuccess(`🎉 New deposit received! ${depositData.total_amount} ${depositData.currency}`);
        
        // Add visual effects
        this.addDepositEffects();
    }

    addDepositEffects() {
        // Add glowing effect to balance
        $('#header_wallet_balance').addClass('balance-glow');
        setTimeout(() => {
            $('#header_wallet_balance').removeClass('balance-glow');
        }, 3000);
    }

    // ================================
    // GAME INTEGRATION
    // ================================

    updateGameBalance() {
        // Update the main wallet balance display in header
        fetch('/get_user_details')
            .then(response => response.json())
            .then(data => {
                if (data.isSuccess) {
                    const balance = data.data.wallet_balance || 0;
                    $('#header_wallet_balance').text(`$${balance}`);
                    
                    // Update any other balance displays
                    $('.wallet-balance').text(`$${balance}`);
                }
            })
            .catch(error => console.error('Failed to update game balance:', error));
    }

    // ================================
    // UI INTEGRATION
    // ================================

    setupEventListeners() {
        // Crypto deposit button
        $(document).on('click', '#crypto-deposit-btn', () => {
            this.generateDepositAddress('SOL');
        });

        // Crypto withdrawal button
        $(document).on('click', '#crypto-withdrawal-btn', () => {
            this.showWithdrawalModal();
        });

        // Currency selection
        $(document).on('change', '#deposit-currency-select', (e) => {
            const currency = $(e.target).val();
            this.generateDepositAddress(currency);
        });

        // Withdrawal form submission
        $(document).on('submit', '#crypto-withdrawal-form', async (e) => {
            e.preventDefault();
            
            const currency = $('#withdrawal-currency').val();
            const amount = $('#withdrawal-amount').val();
            const address = $('#withdrawal-address').val();
            
            await this.processWithdrawal(currency, amount, address);
        });

        // Balance refresh
        $(document).on('click', '#refresh-crypto-balance', () => {
            this.updateBalances();
        });
    }

    updateBalanceDisplay() {
        // Update crypto balance displays
        $('#crypto-sol-balance').text(`${this.balances.SOL} SOL`);
        $('#crypto-usdt-balance').text(`${this.balances.USDT} USDT`);
        
        // Update USD equivalent
        const totalUSD = (this.balances.SOL * this.exchangeRates.SOL) + 
                        (this.balances.USDT * this.exchangeRates.USDT);
        $('#crypto-total-usd').text(`$${totalUSD.toFixed(2)}`);
    }

    updateExchangeRateDisplay() {
        $('#sol-rate').text(`$${this.exchangeRates.SOL.toFixed(2)}`);
        $('#usdt-rate').text(`$${this.exchangeRates.USDT.toFixed(2)}`);
    }

    showWithdrawalModal() {
        // Populate withdrawal options
        const withdrawalSelect = $('#withdrawal-currency');
        withdrawalSelect.empty();
        
        if (this.balances.SOL > 0) {
            withdrawalSelect.append(`<option value="SOL">SOL (${this.balances.SOL} available)</option>`);
        }
        if (this.balances.USDT > 0) {
            withdrawalSelect.append(`<option value="USDT">USDT (${this.balances.USDT} available)</option>`);
        }
        
        if (withdrawalSelect.children().length === 0) {
            withdrawalSelect.append('<option value="">No crypto balance available</option>');
        }
        
        $('#crypto-withdrawal-modal').modal('show');
    }

    // ================================
    // UTILITY FUNCTIONS
    // ================================

    formatAddress(address) {
        if (!address) return '';
        return `${address.substring(0, 6)}...${address.substring(address.length - 6)}`;
    }

    showSuccess(message) {
        toastr.success(message);
    }

    showError(message) {
        toastr.error(message);
    }

    showLoading(message = 'Loading...') {
        // You can implement a loading overlay here
        console.log('Loading:', message);
    }

    hideLoading() {
        // Hide loading overlay
        console.log('Loading complete');
    }

    // ================================
    // TRANSACTION HISTORY
    // ================================

    async loadTransactionHistory() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/transactions`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const data = await response.json();
            
            if (data.success) {
                this.displayTransactionHistory(data.data.transactions);
            }
        } catch (error) {
            console.error('Failed to load transaction history:', error);
        }
    }

    displayTransactionHistory(transactions) {
        const container = $('#crypto-transaction-history');
        
        if (transactions.length === 0) {
            container.html('<div class="text-center text-muted">No crypto transactions yet</div>');
            return;
        }

        let html = '<div class="transaction-list">';
        
        transactions.forEach(tx => {
            const statusClass = tx.status === 'completed' ? 'text-success' : 
                              tx.status === 'pending' ? 'text-warning' : 'text-danger';
            
            html += `
                <div class="transaction-item border-bottom py-2">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fw-bold">${tx.type.toUpperCase()} ${tx.currency}</div>
                            <div class="small text-muted">${new Date(tx.created_at).toLocaleString()}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">${tx.amount} ${tx.currency}</div>
                            <div class="small ${statusClass}">${tx.status.toUpperCase()}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.html(html);
    }
}

// ================================
// INTEGRATION WITH EXISTING SYSTEM
// ================================

// Extend the existing deposit function to include crypto options
function addCryptoToExistingDeposit() {
    // Add crypto option to payment gateway selection
    const cryptoGateway = `
        <div class="grid-list" onclick="selectCryptoDeposit()">
            <button class="btn payment-btn" data-tab="crypto">
                <img src="images/app-logo/solana-logo.svg" />
                <div class="PaymentCard_limit">Crypto (SOL/USDT)</div>
            </button>
        </div>
    `;
    
    // Add to existing payment options
    $('.grid-view').append(cryptoGateway);
    
    // Add crypto deposit form
    const cryptoDepositForm = `
        <div class="deposite-box" id="crypto" style="display: none;">
            <div class="d-box">
                <div class="limit-txt">LIMITS: <span>Min 0.01 SOL / 1 USDT</span></div>
                <div class="crypto-options">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="crypto-currency-selector">
                                <select class="form-control" id="crypto-currency">
                                    <option value="SOL">Solana (SOL)</option>
                                    <option value="USDT">Tether (USDT-SPL)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <button class="register-btn rounded-pill d-flex align-items-center w-100 mt-3 orange-shadow" 
                                    onclick="aviatorCrypto.generateDepositAddress($('#crypto-currency').val())">
                                GET DEPOSIT ADDRESS
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="register-btn rounded-pill d-flex align-items-center w-100 mt-3" 
                                    onclick="aviatorCrypto.showWithdrawalModal()">
                                WITHDRAW CRYPTO
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add after existing deposit boxes
    $('.payment-cols').append(cryptoDepositForm);
}

function selectCryptoDeposit() {
    // Hide all other deposit boxes
    $('.deposite-box').hide();
    
    // Show crypto deposit box
    $('#crypto').show();
    
    // Update active button
    $('.payment-btn').removeClass('active');
    $('.payment-btn[data-tab="crypto"]').addClass('active');
}

// ================================
// INITIALIZE ON DOCUMENT READY
// ================================

let aviatorCrypto;

$(document).ready(function() {
    // Initialize crypto wallet
    aviatorCrypto = new AviatorCryptoWallet();
    
    // Add crypto to existing deposit system
    addCryptoToExistingDeposit();
    
    // Add custom CSS for crypto features
    $('head').append(`
        <style>
            .balance-glow {
                animation: balanceGlow 2s ease-in-out;
            }
            
            @keyframes balanceGlow {
                0%, 100% { box-shadow: 0 0 5px rgba(255, 215, 0, 0.3); }
                50% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.8); }
            }
            
            .crypto-currency-selector select {
                background: #1a1a1a;
                color: white;
                border: 1px solid #333;
                border-radius: 8px;
                padding: 10px;
            }
            
            .transaction-item:hover {
                background: rgba(255, 255, 255, 0.05);
            }
            
            .qr-code-container {
                text-align: center;
                padding: 20px;
            }
        </style>
    `);
}); 