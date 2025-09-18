<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Farcaster App Meta -->
    <meta property="fc:frame" content="vNext">
    <meta property="fc:frame:image" content="{{ url('images/aviator-farcaster-preview.png') }}">
    <meta property="fc:frame:button:1" content="🚀 Play Aviator">
    <meta property="fc:frame:button:1:action" content="link">
    <meta property="fc:frame:button:1:target" content="{{ url('/farcaster-app') }}">
    
    <!--====== Title ======-->
    <title>🚀 Aviator - Farcaster Gaming</title>

    <!--====== Favicon ======-->
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}" type="image/png" />

    <!-- Farcaster Auth Kit -->
    <script src="https://unpkg.com/@farcaster/auth-kit@latest/dist/auth-kit.umd.js"></script>
    
    <!--====== Your existing styles ======-->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="{{ asset('css/jquery.mCustomScrollbar.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/toastr.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />

    <!-- Farcaster-specific styles -->
    <style>
        /* Farcaster integration styles */
        .farcaster-header {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1000;
        }
        
        .farcaster-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .farcaster-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .farcaster-balance {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .farcaster-social-panel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50px;
            padding: 12px;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
            z-index: 1001;
        }
        
        .farcaster-social-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .farcaster-social-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }
        
        .farcaster-notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 1002;
        }
        
        .farcaster-notification.show {
            transform: translateX(0);
        }
        
        .farcaster-payment-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1003;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .farcaster-payment-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .farcaster-payment-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }
        
        /* Mobile optimizations for Farcaster */
        @media (max-width: 768px) {
            .farcaster-header {
                padding: 8px 12px;
            }
            
            .farcaster-header h3 {
                font-size: 16px;
            }
            
            .farcaster-social-panel {
                bottom: 15px;
                right: 15px;
                padding: 8px;
            }
            
            .main-wrapper {
                padding-top: 0;
            }
        }
        
        /* Override some existing styles for better Farcaster integration */
        body {
            background: #1a1a2e;
        }
        
        .main-wrapper {
            padding-top: 60px; /* Account for Farcaster header */
        }
        
        /* Success animations */
        @keyframes farcasterPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .farcaster-success {
            animation: farcasterPulse 0.6s ease-in-out;
        }
    </style>
</head>

<body>
    <!-- Farcaster Header -->
    <div class="farcaster-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 24px;">🚀</span>
            <h3>Aviator on Farcaster</h3>
        </div>
        
        <div class="farcaster-user-info">
            <div id="farcaster-username">Connecting...</div>
            <div class="farcaster-balance" id="farcaster-balance">$0.00</div>
        </div>
    </div>

    <!-- Main Game Container -->
    <div id="game-container">
        @include('crash') {{-- Include your existing game --}}
    </div>

    <!-- Farcaster Social Panel -->
    <div class="farcaster-social-panel">
        <button class="farcaster-social-btn" onclick="shareWin()" title="Share Win">
            🎉
        </button>
    </div>

    <!-- Farcaster Payment Modal -->
    <div class="farcaster-payment-modal" id="farcaster-payment-modal">
        <div class="farcaster-payment-content">
            <h4>🟣 Farcaster Payment</h4>
            <p id="payment-message">Processing payment...</p>
            <div id="payment-buttons" style="display: none;">
                <button class="btn btn-primary" onclick="confirmPayment()">Confirm</button>
                <button class="btn btn-secondary" onclick="closePaymentModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Farcaster Notification -->
    <div class="farcaster-notification" id="farcaster-notification">
        <div id="notification-message"></div>
    </div>

    <!-- Your existing JS dependencies -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/anime.min.js') }}"></script>
    <script src="{{ asset('js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>

    <!-- Farcaster Integration Script -->
    <script>
        class FarcasterAviatorApp {
            constructor() {
                this.currentUser = null;
                this.authKit = null;
                this.isAuthenticated = false;
                this.balance = 0;
                
                this.initializeFarcaster();
            }

            async initializeFarcaster() {
                try {
                    // Initialize Farcaster Auth Kit
                    this.authKit = new FarcasterAuthKit({
                        relay: 'https://relay.farcaster.xyz',
                        rpcUrl: 'https://mainnet.optimism.io',
                        domain: window.location.hostname,
                        siweUri: window.location.origin,
                        redirectUri: window.location.origin + '/farcaster-app'
                    });

                    // Check if user is already authenticated
                    if (this.authKit.isAuthenticated) {
                        await this.handleAuthentication(this.authKit.profile);
                    } else {
                        // Show authentication prompt
                        await this.showAuthPrompt();
                    }

                    // Setup event listeners
                    this.setupEventListeners();
                    
                } catch (error) {
                    console.error('Farcaster initialization failed:', error);
                    this.showNotification('❌ Farcaster connection failed', 'error');
                }
            }

            async showAuthPrompt() {
                const { message, signature, fid, username, displayName } = await this.authKit.authenticate();
                
                if (fid) {
                    await this.handleAuthentication({ fid, username, displayName });
                }
            }

            async handleAuthentication(profile) {
                try {
                    // Authenticate with Laravel backend
                    const response = await fetch('/auth/farcaster', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        body: JSON.stringify({
                            fid: profile.fid,
                            username: profile.username,
                            displayName: profile.displayName
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.currentUser = result.data;
                        this.isAuthenticated = true;
                        this.balance = result.data.balance;

                        // Update UI
                        this.updateUserInterface();
                        
                        // Show welcome notification
                        const welcomeMsg = result.data.is_new_user 
                            ? '🎉 Welcome to Aviator! $25 bonus added!'
                            : '👋 Welcome back to Aviator!';
                        this.showNotification(welcomeMsg, 'success');

                        // Override existing game functions for Farcaster
                        this.integrateWithExistingGame();

                    } else {
                        throw new Error(result.error || 'Authentication failed');
                    }

                } catch (error) {
                    console.error('Authentication failed:', error);
                    this.showNotification('❌ Authentication failed: ' + error.message, 'error');
                }
            }

            updateUserInterface() {
                if (this.currentUser) {
                    document.getElementById('farcaster-username').textContent = 
                        '@' + (this.currentUser.username || this.currentUser.farcaster_id);
                    
                    this.updateBalance(this.currentUser.balance);
                }
            }

            updateBalance(newBalance) {
                this.balance = newBalance;
                document.getElementById('farcaster-balance').textContent = `$${newBalance.toFixed(2)}`;
                
                // Update existing game balance displays if they exist
                if (window.$ && $('#wallet_balance').length) {
                    $('#wallet_balance').text('$' + newBalance.toFixed(2));
                    $('#header_wallet_balance').text('$' + newBalance.toFixed(2));
                }
            }

            integrateWithExistingGame() {
                // Override existing deposit function
                if (window.handleDeposit) {
                    const originalDeposit = window.handleDeposit;
                    window.handleDeposit = (amount) => {
                        return this.handleFarcasterDeposit(amount);
                    };
                }

                // Override existing bet placement function
                if (window.place_bet_now) {
                    const originalBet = window.place_bet_now;
                    window.place_bet_now = () => {
                        return this.handleFarcasterBet();
                    };
                }

                // Override existing cash out function
                if (window.cashout_all) {
                    const originalCashout = window.cashout_all;
                    window.cashout_all = (multiplier) => {
                        return this.handleFarcasterCashout(multiplier);
                    };
                }

                // Enhance existing game over function
                if (window.gameover) {
                    const originalGameover = window.gameover;
                    window.gameover = (lastint) => {
                        // Call original function
                        const result = originalGameover(lastint);
                        
                        // Add Farcaster-specific handling
                        this.handleGameOver(lastint);
                        
                        return result;
                    };
                }
            }

            async handleFarcasterDeposit(amount) {
                try {
                    this.showPaymentModal(`Deposit $${amount} via Farcaster Pay`);

                    // Simulate Farcaster payment (in production, integrate with actual Farcaster Pay)
                    const paymentResult = await this.simulateFarcasterPayment(amount);

                    if (paymentResult.success) {
                        // Process deposit via Laravel API
                        const response = await fetch('/api/farcaster/deposit', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            body: JSON.stringify({
                                fid: this.currentUser.farcaster_id,
                                amount: amount,
                                transactionHash: paymentResult.txHash,
                                fromAddress: paymentResult.fromAddress,
                                currency: 'USDC'
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.updateBalance(result.data.newBalance);
                            this.showNotification(`💰 $${amount} deposited successfully!`, 'success');
                            this.closePaymentModal();
                        } else {
                            throw new Error(result.error);
                        }
                    }

                } catch (error) {
                    console.error('Farcaster deposit failed:', error);
                    this.showNotification('❌ Deposit failed: ' + error.message, 'error');
                    this.closePaymentModal();
                }
            }

            async handleFarcasterBet() {
                try {
                    // Get bet amount from existing game state
                    const betAmount = parseFloat($('#bet_amount').val() || 10);
                    
                    if (betAmount > this.balance) {
                        this.showNotification('❌ Insufficient balance', 'error');
                        return false;
                    }

                    // Place bet via Laravel API
                    const response = await fetch('/api/farcaster/bet', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        body: JSON.stringify({
                            fid: this.currentUser.farcaster_id,
                            amount: betAmount,
                            roundId: window.current_game_data?.id || 'round_' + Date.now()
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.updateBalance(result.data.newBalance);
                        this.showNotification(`🎯 $${betAmount} bet placed!`, 'success');
                        return true;
                    } else {
                        throw new Error(result.error);
                    }

                } catch (error) {
                    console.error('Farcaster bet failed:', error);
                    this.showNotification('❌ Bet failed: ' + error.message, 'error');
                    return false;
                }
            }

            async handleFarcasterCashout(multiplier) {
                try {
                    // Cash out via Laravel API
                    const response = await fetch('/api/farcaster/cashout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        body: JSON.stringify({
                            fid: this.currentUser.farcaster_id,
                            betId: 'current_bet', // Would be actual bet ID
                            multiplier: multiplier
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.updateBalance(result.data.newBalance);
                        this.showNotification(`🎉 Cashed out at ${multiplier}x! Won $${result.data.winAmount.toFixed(2)}`, 'success');
                        
                        // Trigger social sharing for big wins
                        if (multiplier >= 5.0) {
                            setTimeout(() => this.offerWinShare(multiplier, result.data.winAmount), 2000);
                        }
                        
                        return true;
                    } else {
                        throw new Error(result.error);
                    }

                } catch (error) {
                    console.error('Farcaster cashout failed:', error);
                    this.showNotification('❌ Cashout failed: ' + error.message, 'error');
                    return false;
                }
            }

            handleGameOver(lastint) {
                // Track game over event
                fetch('/api/farcaster/track', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    body: JSON.stringify({
                        fid: this.currentUser.farcaster_id,
                        event: 'game_over',
                        properties: {
                            crash_point: lastint,
                            session_duration: Date.now() - this.sessionStart
                        }
                    })
                });
            }

            async simulateFarcasterPayment(amount) {
                // Simulate payment processing (replace with actual Farcaster Pay integration)
                return new Promise((resolve) => {
                    setTimeout(() => {
                        resolve({
                            success: true,
                            txHash: 'fc_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                            fromAddress: 'farcaster_wallet_' + this.currentUser.farcaster_id,
                            amount: amount
                        });
                    }, 2000);
                });
            }

            offerWinShare(multiplier, winAmount) {
                if (confirm(`🎉 Amazing ${multiplier}x win! Share your success on Farcaster?`)) {
                    this.shareWin(multiplier, winAmount);
                }
            }

            shareWin(multiplier, winAmount) {
                // Generate shareable Farcaster frame
                const shareText = `Just won $${winAmount?.toFixed(2) || 'big'} at ${multiplier || 'high'}x on Aviator! 🚀\n\nPlay now: ${window.location.origin}/farcaster-app`;
                
                // Open Farcaster composer with pre-filled text
                const farcasterUrl = `https://warpcast.com/~/compose?text=${encodeURIComponent(shareText)}`;
                window.open(farcasterUrl, '_blank');

                // Track share event
                fetch('/api/farcaster/track', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    body: JSON.stringify({
                        fid: this.currentUser.farcaster_id,
                        event: 'win_shared',
                        properties: { multiplier, winAmount, platform: 'farcaster' }
                    })
                });
            }

            showPaymentModal(message) {
                document.getElementById('payment-message').textContent = message;
                document.getElementById('farcaster-payment-modal').classList.add('active');
            }

            closePaymentModal() {
                document.getElementById('farcaster-payment-modal').classList.remove('active');
            }

            showNotification(message, type = 'info') {
                const notification = document.getElementById('farcaster-notification');
                const messageEl = document.getElementById('notification-message');
                
                messageEl.textContent = message;
                notification.style.background = type === 'success' ? '#10b981' : 
                                                type === 'error' ? '#ef4444' : '#6366f1';
                notification.classList.add('show');

                setTimeout(() => {
                    notification.classList.remove('show');
                }, 4000);
            }

            setupEventListeners() {
                // Listen for existing game events and enhance them
                document.addEventListener('game:bet_placed', (e) => {
                    console.log('Bet placed via Farcaster:', e.detail);
                });

                document.addEventListener('game:win', (e) => {
                    console.log('Win via Farcaster:', e.detail);
                });

                // Handle payment modal close
                document.getElementById('farcaster-payment-modal').addEventListener('click', (e) => {
                    if (e.target.id === 'farcaster-payment-modal') {
                        this.closePaymentModal();
                    }
                });
            }
        }

        // Global functions for the social panel
        function shareWin() {
            if (window.farcasterApp) {
                window.farcasterApp.shareWin();
            }
        }

        function confirmPayment() {
            // Handle payment confirmation
            console.log('Payment confirmed');
        }

        function closePaymentModal() {
            if (window.farcasterApp) {
                window.farcasterApp.closePaymentModal();
            }
        }

        // Initialize Farcaster app when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            window.farcasterApp = new FarcasterAviatorApp();
        });
    </script>
</body>
</html>

