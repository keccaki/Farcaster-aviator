// Telegram Payment Adapter for Aviator Game
class TelegramPaymentAdapter {
    constructor() {
        this.tg = window.Telegram.WebApp;
        this.initializePayments();
    }

    initializePayments() {
        // Listen for payment events from the game
        document.addEventListener('aviator:deposit', (e) => {
            this.handleDeposit(e.detail.amount);
        });

        document.addEventListener('aviator:withdraw', (e) => {
            this.handleWithdrawal(e.detail.amount);
        });
    }

    async handleDeposit(amount) {
        try {
            // Show Telegram's payment interface
            this.tg.MainButton.text = `Deposit ${amount} USD`;
            this.tg.MainButton.show();
            
            // Create payment request
            const response = await fetch('/api/telegram/create-invoice', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount,
                    user_id: this.tg.initDataUnsafe?.user?.id,
                    username: this.tg.initDataUnsafe?.user?.username
                })
            });

            const { invoice_url } = await response.json();
            
            // Open Telegram's payment interface
            this.tg.openInvoice(invoice_url);
            
            // Listen for successful payment
            this.tg.onEvent('invoiceClosed', (status) => {
                if (status === 'paid') {
                    // Trigger game's deposit success handler
                    document.dispatchEvent(new CustomEvent('aviator:depositSuccess', {
                        detail: { amount, provider: 'telegram' }
                    }));
                }
            });
        } catch (error) {
            console.error('Telegram payment error:', error);
            this.tg.showPopup({
                title: 'Payment Error',
                message: 'Failed to process payment. Please try again.',
                buttons: [{
                    type: 'ok',
                    text: 'OK'
                }]
            });
        }
    }

    async handleWithdrawal(amount) {
        try {
            const response = await fetch('/api/telegram/withdraw', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount,
                    user_id: this.tg.initDataUnsafe?.user?.id,
                    username: this.tg.initDataUnsafe?.user?.username
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.tg.showPopup({
                    title: 'Withdrawal Successful',
                    message: `${amount} USD will be sent to your Telegram wallet`,
                    buttons: [{
                        type: 'ok',
                        text: 'OK'
                    }]
                });

                // Trigger game's withdrawal success handler
                document.dispatchEvent(new CustomEvent('aviator:withdrawSuccess', {
                    detail: { amount, provider: 'telegram' }
                }));
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Telegram withdrawal error:', error);
            this.tg.showPopup({
                title: 'Withdrawal Error',
                message: 'Failed to process withdrawal. Please try again.',
                buttons: [{
                    type: 'ok',
                    text: 'OK'
                }]
            });
        }
    }

    // Validate Telegram payment data
    validatePaymentData(data) {
        return fetch('/api/telegram/validate-payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
    }
}

// Initialize payment adapter when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.telegramPaymentAdapter = new TelegramPaymentAdapter();
}); 