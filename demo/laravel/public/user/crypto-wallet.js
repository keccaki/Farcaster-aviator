/**
 * Aviator Crypto Wallet Manager
 * Handles Solana SOL & USDT-SPL deposits/withdrawals with real-time monitoring
 * @version 1.0.0
 * @author Aviator Game Development Team
 */

class AviatorCryptoWallet {
    constructor() {
        this.apiBaseUrl = '/api/crypto';
        this.isMonitoring = false;
        this.monitoringInterval = null;
        this.lastDepositCheck = Date.now();
        this.currentWallet = null;
        this.exchangeRates = { SOL: 0, USDT: 0 };
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    /**
     * Initialize crypto wallet system
     */
    async init() {
        try {
            console.log('🚀 Initializing Aviator Crypto Wallet...');
            
            // Load exchange rates
            await this.loadExchangeRates();
            
            // Setup event listeners
            this.setupEventListeners();
            console.log('📡 Event listeners setup complete');
            
            // Start deposit monitoring if user is logged in
            if (this.isUserLoggedIn()) {
                console.log('👤 User is logged in, loading wallet...');
                await this.loadUserWallet();
                this.startDepositMonitoring();
            } else {
                console.log('🚪 User not logged in, skipping wallet load');
            }
            
            console.log('✅ Crypto Wallet initialized successfully');
        } catch (error) {
            console.error('❌ Failed to initialize crypto wallet:', error);
            this.showError('Failed to initialize crypto wallet system');
        }
    }

    /**
     * Setup all event listeners
     */
    setupEventListeners() {
        // Crypto deposit button
        $(document).on('click', '[data-crypto-action="deposit"]', (e) => {
            e.preventDefault();
            console.log('🎯 Crypto deposit button clicked');
            this.openDepositModal();
        });

        // Crypto withdrawal button
        $(document).on('click', '[data-crypto-action="withdraw"]', (e) => {
            e.preventDefault();
            console.log('🎯 Crypto withdrawal button clicked');
            this.openWithdrawalModal();
        });

        // Generate wallet button
        $(document).on('click', '#generateCryptoWallet', () => this.generateWallet());

        // Copy address button
        $(document).on('click', '.copy-crypto-address', (e) => this.copyToClipboard(e));

        // Process withdrawal
        $(document).on('click', '#processCryptoWithdrawal', () => this.processWithdrawal());

        // Refresh balance
        $(document).on('click', '#refreshCryptoBalance', () => this.refreshBalance());

        // View transaction history
        $(document).on('click', '#viewCryptoHistory', () => this.viewTransactionHistory());

        // Currency selection change
        $(document).on('change', '#cryptoCurrency', (e) => this.onCurrencyChange(e.target.value));

        // Modal close cleanup
        $(document).on('hidden.bs.modal', '#cryptoDepositModal, #cryptoWithdrawModal', () => {
            this.stopDepositMonitoring();
        });
    }

    /**
     * Check if user is logged in
     */
    isUserLoggedIn() {
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        const hasUserId = userIdMeta !== null && userIdMeta.getAttribute('content');
        console.log('🔐 User login check:', { userIdMeta, hasUserId });
        return hasUserId;
    }

    /**
     * Load user's crypto wallet
     */
    async loadUserWallet() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/wallet`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                this.currentWallet = await response.json();
                this.updateWalletDisplay();
            }
        } catch (error) {
            console.error('Failed to load wallet:', error);
        }
    }

    /**
     * Get authentication token
     */
    getAuthToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    /**
     * Load current exchange rates
     */
    async loadExchangeRates() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/rates`);
            if (response.ok) {
                this.exchangeRates = await response.json();
            }
        } catch (error) {
            console.warn('Failed to load exchange rates:', error);
        }
    }

    /**
     * Open crypto deposit modal
     */
    async openDepositModal() {
        try {
            console.log('🚀 Opening crypto deposit modal...');
            
            // Check if user is logged in
            if (!this.isUserLoggedIn()) {
                this.showError('Please login first to use crypto deposits');
                // Try to open login modal if it exists
                const loginModal = document.getElementById('login-modal');
                if (loginModal) {
                    new bootstrap.Modal(loginModal).show();
                }
                return;
            }

            // Ensure wallet exists
            if (!this.currentWallet) {
                await this.loadUserWallet();
            }

            if (!this.currentWallet) {
                this.showInfo('Generating your crypto wallet...');
                await this.generateWallet();
                if (!this.currentWallet) {
                    this.showError('Failed to generate crypto wallet');
                    return;
                }
            }

            // Show deposit modal
            const depositModal = document.getElementById('cryptoDepositModal');
            if (!depositModal) {
                this.showError('Crypto deposit modal not found');
                console.error('❌ cryptoDepositModal element not found');
                return;
            }
            
            const modal = new bootstrap.Modal(depositModal);
            modal.show();
            console.log('✅ Deposit modal shown');

            // Update wallet info in modal
            this.updateDepositModalContent();
            
            // Start real-time monitoring
            this.startDepositMonitoring();

        } catch (error) {
            this.showError('Failed to open deposit modal');
            console.error('❌ Deposit modal error:', error);
        }
    }

    /**
     * Update deposit modal content
     */
    updateDepositModalContent() {
        if (!this.currentWallet) return;

        // Update wallet addresses
        $('#solanaAddress').text(this.currentWallet.solana_address);
        $('#usdtAddress').text(this.currentWallet.solana_address); // Same address for SPL tokens

        // Generate QR codes
        this.generateQRCode('#solanaQR', this.currentWallet.solana_address);
        this.generateQRCode('#usdtQR', this.currentWallet.solana_address);

        // Update balances
        $('#cryptoSolBalance').text(`${this.currentWallet.sol_balance || '0.00'} SOL`);
        $('#cryptoUsdtBalance').text(`${this.currentWallet.usdt_balance || '0.00'} USDT`);
    }

    /**
     * Generate QR code
     */
    generateQRCode(selector, address) {
        try {
            const qrContainer = document.querySelector(selector);
            if (qrContainer && window.QRCode) {
                qrContainer.innerHTML = '';
                new QRCode(qrContainer, {
                    text: address,
                    width: 200,
                    height: 200,
                    colorDark: "#000000",
                    colorLight: "#ffffff"
                });
            }
        } catch (error) {
            console.warn('QR Code generation failed:', error);
        }
    }

    /**
     * Start deposit monitoring
     */
    startDepositMonitoring() {
        if (this.isMonitoring) return;

        this.isMonitoring = true;
        console.log('🔍 Starting deposit monitoring...');

        this.monitoringInterval = setInterval(async () => {
            await this.checkForNewDeposits();
        }, 30000); // Check every 30 seconds

        // Initial check
        this.checkForNewDeposits();
    }

    /**
     * Stop deposit monitoring
     */
    stopDepositMonitoring() {
        if (this.monitoringInterval) {
            clearInterval(this.monitoringInterval);
            this.monitoringInterval = null;
        }
        this.isMonitoring = false;
        console.log('⏹️ Stopped deposit monitoring');
    }

    /**
     * Check for new deposits
     */
    async checkForNewDeposits() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/check-deposits`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    since: this.lastDepositCheck
                })
            });

            if (response.ok) {
                const result = await response.json();
                
                if (result.new_deposits && result.new_deposits.length > 0) {
                    this.handleNewDeposits(result.new_deposits);
                }

                // Update wallet data
                if (result.wallet) {
                    this.currentWallet = result.wallet;
                    this.updateWalletDisplay();
                }

                this.lastDepositCheck = Date.now();
            }
        } catch (error) {
            console.error('Deposit check failed:', error);
        }
    }

    /**
     * Handle new deposits
     */
    handleNewDeposits(deposits) {
        deposits.forEach(deposit => {
            // Show success notification
            this.showSuccess(`New ${deposit.currency} deposit received: ${deposit.amount}`);
            
            // Add visual effect to balance
            this.animateBalanceUpdate();
            
            // Update game balance if it's the main game
            if (window.updateBalance && typeof window.updateBalance === 'function') {
                window.updateBalance();
            }
        });
    }

    /**
     * Animate balance update
     */
    animateBalanceUpdate() {
        const balanceElements = [
            '#cryptoSolBalance',
            '#cryptoUsdtBalance', 
            '.user-balance',
            '#mainBalance'
        ];

        balanceElements.forEach(selector => {
            const element = document.querySelector(selector);
            if (element) {
                element.style.transition = 'all 0.3s ease';
                element.style.backgroundColor = '#4CAF50';
                element.style.color = 'white';
                element.style.transform = 'scale(1.05)';
                
                setTimeout(() => {
                    element.style.backgroundColor = '';
                    element.style.color = '';
                    element.style.transform = '';
                }, 1000);
            }
        });
    }

    /**
     * Update wallet display across the site
     */
    updateWalletDisplay() {
        if (!this.currentWallet) return;

        // Update balance displays
        $('#cryptoSolBalance').text(`${this.currentWallet.sol_balance || '0.00'} SOL`);
        $('#cryptoUsdtBalance').text(`${this.currentWallet.usdt_balance || '0.00'} USDT`);
        
        // Update header crypto balance if exists
        $('.crypto-balance-display').text(
            `SOL: ${this.currentWallet.sol_balance || '0.00'} | USDT: ${this.currentWallet.usdt_balance || '0.00'}`
        );
    }

    /**
     * Open withdrawal modal
     */
    openWithdrawalModal() {
        if (!this.currentWallet) {
            this.showError('Please generate a crypto wallet first');
            return;
        }

        const modal = new bootstrap.Modal(document.getElementById('cryptoWithdrawModal'));
        modal.show();
        
        // Update available balances
        $('#availableSol').text(this.currentWallet.sol_balance || '0.00');
        $('#availableUsdt').text(this.currentWallet.usdt_balance || '0.00');
    }

    /**
     * Process withdrawal
     */
    async processWithdrawal() {
        try {
            const currency = $('#withdrawCurrency').val();
            const amount = parseFloat($('#withdrawAmount').val());
            const address = $('#withdrawAddress').val().trim();

            // Validation
            if (!currency || !amount || !address) {
                this.showError('Please fill in all withdrawal fields');
                return;
            }

            if (amount <= 0) {
                this.showError('Withdrawal amount must be greater than 0');
                return;
            }

            // Check balance
            const availableBalance = currency === 'SOL' ? 
                parseFloat(this.currentWallet.sol_balance || 0) : 
                parseFloat(this.currentWallet.usdt_balance || 0);

            if (amount > availableBalance) {
                this.showError(`Insufficient ${currency} balance`);
                return;
            }

            // Validate Solana address
            if (!this.isValidSolanaAddress(address)) {
                this.showError('Invalid Solana wallet address');
                return;
            }

            // Show loading state
            $('#processCryptoWithdrawal').prop('disabled', true).text('Processing...');

            const response = await fetch(`${this.apiBaseUrl}/withdraw`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    currency: currency,
                    amount: amount,
                    to_address: address
                })
            });

            const result = await response.json();

            if (response.ok) {
                this.showSuccess(result.message || 'Withdrawal request submitted successfully');
                
                // Close modal and refresh wallet
                bootstrap.Modal.getInstance(document.getElementById('cryptoWithdrawModal')).hide();
                await this.loadUserWallet();
                
                // Clear form
                $('#withdrawAmount, #withdrawAddress').val('');
            } else {
                this.showError(result.message || 'Withdrawal failed');
            }

        } catch (error) {
            console.error('Withdrawal error:', error);
            this.showError('Withdrawal processing failed');
        } finally {
            $('#processCryptoWithdrawal').prop('disabled', false).text('Process Withdrawal');
        }
    }

    /**
     * Validate Solana address
     */
    isValidSolanaAddress(address) {
        // Basic Solana address validation (Base58, 32-44 characters)
        const solanaAddressRegex = /^[1-9A-HJ-NP-Za-km-z]{32,44}$/;
        return solanaAddressRegex.test(address);
    }

    /**
     * Generate new crypto wallet
     */
    async generateWallet() {
        try {
            $('#generateCryptoWallet').prop('disabled', true).text('Generating...');

            const response = await fetch(`${this.apiBaseUrl}/wallet/generate`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok) {
                this.currentWallet = result.wallet;
                this.showSuccess('Crypto wallet generated successfully');
                this.updateWalletDisplay();
            } else {
                this.showError(result.message || 'Failed to generate wallet');
            }

        } catch (error) {
            console.error('Wallet generation error:', error);
            this.showError('Failed to generate crypto wallet');
        } finally {
            $('#generateCryptoWallet').prop('disabled', false).text('Generate Wallet');
        }
    }

    /**
     * Copy text to clipboard
     */
    async copyToClipboard(event) {
        try {
            const button = event.currentTarget;
            const textToCopy = button.getAttribute('data-copy') || 
                              button.previousElementSibling?.textContent?.trim() ||
                              button.parentElement?.querySelector('input')?.value;

            if (!textToCopy) {
                this.showError('Nothing to copy');
                return;
            }

            await navigator.clipboard.writeText(textToCopy);
            
            // Visual feedback
            const originalText = button.textContent;
            button.textContent = 'Copied!';
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('btn-success');
            }, 2000);

            this.showSuccess('Address copied to clipboard');

        } catch (error) {
            console.error('Copy failed:', error);
            this.showError('Failed to copy to clipboard');
        }
    }

    /**
     * Refresh crypto balance
     */
    async refreshBalance() {
        try {
            $('#refreshCryptoBalance').addClass('fa-spin');
            await this.loadUserWallet();
            this.showSuccess('Balance refreshed');
        } catch (error) {
            this.showError('Failed to refresh balance');
        } finally {
            setTimeout(() => {
                $('#refreshCryptoBalance').removeClass('fa-spin');
            }, 1000);
        }
    }

    /**
     * View transaction history
     */
    async viewTransactionHistory() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/transactions`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Content-Type': 'application/json'
                }
            });

            if (response.ok) {
                const transactions = await response.json();
                this.displayTransactionHistory(transactions);
            } else {
                this.showError('Failed to load transaction history');
            }

        } catch (error) {
            console.error('Transaction history error:', error);
            this.showError('Failed to load transaction history');
        }
    }

    /**
     * Display transaction history
     */
    displayTransactionHistory(transactions) {
        const historyContainer = $('#cryptoTransactionHistory');
        
        if (!transactions || transactions.length === 0) {
            historyContainer.html('<p class="text-center text-muted">No transactions found</p>');
            return;
        }

        let historyHtml = '<div class="table-responsive"><table class="table table-striped">';
        historyHtml += '<thead><tr><th>Date</th><th>Type</th><th>Currency</th><th>Amount</th><th>Status</th></tr></thead><tbody>';

        transactions.forEach(tx => {
            const date = new Date(tx.created_at).toLocaleDateString();
            const statusClass = tx.status === 'completed' ? 'success' : 
                               tx.status === 'failed' ? 'danger' : 'warning';
            
            historyHtml += `
                <tr>
                    <td>${date}</td>
                    <td>${tx.type.toUpperCase()}</td>
                    <td>${tx.currency}</td>
                    <td>${tx.amount}</td>
                    <td><span class="badge bg-${statusClass}">${tx.status}</span></td>
                </tr>
            `;
        });

        historyHtml += '</tbody></table></div>';
        historyContainer.html(historyHtml);

        // Show history modal
        const modal = new bootstrap.Modal(document.getElementById('cryptoHistoryModal'));
        modal.show();
    }

    /**
     * Handle currency change
     */
    onCurrencyChange(currency) {
        // Update UI based on selected currency
        if (currency === 'SOL') {
            $('#solanaDetails').show();
            $('#usdtDetails').hide();
        } else {
            $('#solanaDetails').hide();
            $('#usdtDetails').show();
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        if (window.toastr) {
            toastr.success(message);
        } else {
            alert(message);
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        if (window.toastr) {
            toastr.error(message);
        } else {
            alert('Error: ' + message);
        }
    }

    /**
     * Show info message
     */
    showInfo(message) {
        if (window.toastr) {
            toastr.info(message);
        } else {
            alert(message);
        }
    }
}

// Initialize crypto wallet when script loads
window.aviatorCryptoWallet = new AviatorCryptoWallet();

// Export for external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AviatorCryptoWallet;
} 