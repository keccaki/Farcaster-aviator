/**
 * Crypto Deposit System for Aviator Game
 * Professional integration with existing payment system
 * Supports SOL and USDT on Solana blockchain
 */

// Exchange rates (will be fetched from API)
let cryptoRates = {
    SOL: 100.0,  // SOL/USD
    USDT: 1.0    // USDT/USD
};

/**
 * Generate crypto deposit address
 */
async function generateCryptoAddress() {
    try {
        // Validate amount
        const amount = parseFloat($("#crypto_amount").val());
        const currency = $("#crypto_currency").val();
        const minAmount = parseFloat($("#crypto_min_amount").val());
        const maxAmount = parseFloat($("#crypto_max_amount").val());

        if (!amount || amount < minAmount || amount > maxAmount) {
            toastr.error(`Please enter amount between $${minAmount} and $${maxAmount}`);
            return;
        }

        // Show loading state
        const button = $('button[onclick="generateCryptoAddress()"]');
        const originalText = button.html();
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Generating...');

        try {
            // Get or create crypto wallet
            const response = await fetch('/api/crypto/wallet', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            let walletData;
            if (response.status === 404) {
                // Create new wallet
                const createResponse = await fetch('/api/crypto/wallet/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                
                if (!createResponse.ok) {
                    throw new Error('Failed to create crypto wallet');
                }
                
                walletData = await createResponse.json();
            } else if (response.ok) {
                walletData = await response.json();
            } else {
                throw new Error('Failed to get crypto wallet');
            }

            if (!walletData.success) {
                throw new Error(walletData.error || 'Failed to get wallet data');
            }

            // Calculate expected crypto amount
            const rate = currency === 'SOL' ? cryptoRates.SOL : cryptoRates.USDT;
            const expectedAmount = (amount / rate).toFixed(currency === 'SOL' ? 6 : 2);

            // Display wallet address and QR code
            displayCryptoAddress(walletData.wallet.address, expectedAmount, currency, amount);
            
            // Start monitoring for deposits
            startDepositMonitoring(walletData.wallet.address, expectedAmount, currency);

        } catch (error) {
            console.error('Crypto address generation error:', error);
            toastr.error(error.message || 'Failed to generate crypto address');
        } finally {
            // Restore button
            button.prop('disabled', false).html(originalText);
        }

    } catch (error) {
        console.error('Generate crypto address error:', error);
        toastr.error('Failed to generate crypto address');
    }
}

/**
 * Display crypto address and QR code
 */
function displayCryptoAddress(address, expectedAmount, currency, usdAmount) {
    // Show address section
    $("#crypto-address-section").show();
    
    // Set address
    $("#crypto-deposit-address").val(address);
    
    // Update expected amount with icon
    $("#crypto-expected-amount").text(expectedAmount);
    const currencyIcon = currency === 'SOL' 
        ? '<img src="images/solana-sol-icon.svg" style="height: 14px; margin-left: 3px;" alt="SOL" />'
        : '<img src="images/usdt-svgrepo-com.svg" style="height: 14px; margin-left: 3px;" alt="USDT" />';
    $("#crypto-expected-currency").html(currency + ' ' + currencyIcon);
    
    // Generate QR code
    generateQRCode(address);
    
    // Update QR currency indicator
    const qrCurrencyIcon = currency === 'SOL' 
        ? '<img src="images/solana-sol-icon.svg" style="height: 12px; margin-right: 3px;" alt="SOL" />'
        : '<img src="images/usdt-svgrepo-com.svg" style="height: 12px; margin-right: 3px;" alt="USDT" />';
    $("#qr-currency-indicator").html(qrCurrencyIcon + 'Scan to pay with ' + currency);
    
    // Show success message
    toastr.success(`Deposit address generated! Send exactly ${expectedAmount} ${currency} to the address above.`);
}

/**
 * Generate QR code for address
 */
function generateQRCode(address) {
    const qrContainer = document.getElementById('crypto-qr-code');
    qrContainer.innerHTML = '';
    
    if (window.QRCode) {
        new QRCode(qrContainer, {
            text: address,
            width: 150,
            height: 150,
            colorDark: "#000000",
            colorLight: "#ffffff"
        });
    } else {
        // Fallback to image-based QR code
        const qrImg = document.createElement('img');
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(address)}`;
        qrImg.alt = 'QR Code';
        qrImg.style.width = '150px';
        qrImg.style.height = '150px';
        qrContainer.appendChild(qrImg);
    }
}

/**
 * Copy address to clipboard
 */
async function copyAddress() {
    try {
        const address = $("#crypto-deposit-address").val();
        
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(address);
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = address;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
        
        // Update button text
        const button = $('.copy-btn');
        const originalText = button.text();
        button.text('Copied!').addClass('btn-success').removeClass('btn-outline-light');
        
        setTimeout(() => {
            button.text(originalText).removeClass('btn-success').addClass('btn-outline-light');
        }, 2000);
        
        toastr.success('Address copied to clipboard!');
        
    } catch (error) {
        console.error('Copy error:', error);
        toastr.error('Failed to copy address');
    }
}

/**
 * Start monitoring for deposits
 */
function startDepositMonitoring(address, expectedAmount, currency) {
    // Clear any existing monitoring
    if (window.cryptoMonitorInterval) {
        clearInterval(window.cryptoMonitorInterval);
    }
    
    let checkCount = 0;
    const maxChecks = 60; // Monitor for 30 minutes (30s intervals)
    
    window.cryptoMonitorInterval = setInterval(async () => {
        checkCount++;
        
        try {
            const response = await fetch('/api/crypto/check-deposits', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    address: address,
                    expected_amount: expectedAmount,
                    currency: currency
                })
            });
            
            if (response.ok) {
                const result = await response.json();
                
                if (result.success && result.deposit_detected) {
                    // Deposit detected!
                    clearInterval(window.cryptoMonitorInterval);
                    handleDepositDetected(result.deposit);
                    return;
                }
            }
            
        } catch (error) {
            console.error('Deposit monitoring error:', error);
        }
        
        // Stop monitoring after max checks
        if (checkCount >= maxChecks) {
            clearInterval(window.cryptoMonitorInterval);
            toastr.info('Deposit monitoring stopped. Please refresh if you made a payment.');
        }
        
    }, 30000); // Check every 30 seconds
    
    toastr.info('Monitoring for deposits... This may take 1-2 minutes after you send the payment.');
}

/**
 * Handle deposit detection
 */
function handleDepositDetected(deposit) {
    toastr.success(`Deposit received! ${deposit.amount} ${deposit.currency} has been credited to your account.`);
    
    // Update wallet balance in UI
    if (window.updateBalance && typeof window.updateBalance === 'function') {
        window.updateBalance();
    }
    
    // Hide the crypto address section
    $("#crypto-address-section").hide();
    
    // Reset form
    $("#crypto_amount").val('');
    
    // Redirect to game or show success message
    setTimeout(() => {
        window.location.href = '/crash';
    }, 3000);
}

/**
 * Fetch current exchange rates
 */
async function fetchCryptoRates() {
    try {
        const response = await fetch('/api/crypto/rates');
        if (response.ok) {
            const rates = await response.json();
            cryptoRates = rates;
        }
    } catch (error) {
        console.warn('Failed to fetch crypto rates:', error);
    }
}

/**
 * Update crypto amount display when currency changes
 */
function updateCryptoAmount() {
    const amount = parseFloat($("#crypto_amount").val());
    const currency = $("#crypto_currency").val();
    
    if (amount && cryptoRates[currency]) {
        const rate = cryptoRates[currency];
        const cryptoAmount = (amount / rate).toFixed(currency === 'SOL' ? 6 : 2);
        
        // Show expected crypto amount with icon in console
        const iconText = currency === 'SOL' ? '◉' : '⊙';
        console.log(`💱 $${amount} USD = ${cryptoAmount} ${currency} ${iconText}`);
    }
}

// Initialize when page loads
$(document).ready(function() {
    // Fetch current exchange rates
    fetchCryptoRates();
    
    // Update crypto amount when fields change
    $("#crypto_amount, #crypto_currency").on('input change', updateCryptoAmount);
    
    // Clear monitoring on page unload
    $(window).on('beforeunload', function() {
        if (window.cryptoMonitorInterval) {
            clearInterval(window.cryptoMonitorInterval);
        }
    });
}); 