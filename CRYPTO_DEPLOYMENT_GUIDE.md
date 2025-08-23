# 🚀 Crypto Payment System Deployment Guide

## Overview
This guide will help you deploy the crypto payment system safely without breaking your existing Aviator game. The system is designed to run alongside traditional payment methods.

## 📋 Prerequisites

### System Requirements
- PHP 8.0+
- Laravel 8.0+
- MySQL 5.7+
- Composer
- Node.js (for frontend integration)

### Required Extensions
- `php-curl`
- `php-json`
- `php-openssl`
- `php-bcmath`

## 🔧 Step-by-Step Deployment

### Step 1: Backup Your System
```bash
# Backup database
mysqldump -u your_user -p your_database > aviator_backup_$(date +%Y%m%d).sql

# Backup application files
tar -czf aviator_backup_$(date +%Y%m%d).tar.gz /path/to/your/app
```

### Step 2: Run Database Migrations
```bash
cd /path/to/your/laravel/app
php artisan migrate --path=database/migrations/2024_01_01_000001_create_crypto_wallets_table.php
php artisan migrate --path=database/migrations/2024_01_01_000002_create_crypto_transactions_table.php
php artisan migrate --path=database/migrations/2024_01_01_000003_create_crypto_security_logs_table.php
php artisan migrate --path=database/migrations/2024_01_01_000004_create_withdrawal_approvals_table.php
php artisan migrate --path=database/migrations/2024_01_01_000005_create_crypto_exchange_rates_table.php
php artisan migrate --path=database/migrations/2024_01_01_000006_extend_existing_tables_for_crypto.php
```

### Step 3: Configure Environment Variables
Add these variables to your `.env` file:

```bash
# Copy from .env.crypto.example
cp .env.crypto.example .env.crypto
cat .env.crypto >> .env

# Generate master seed (KEEP THIS SECRET!)
SOLANA_MASTER_SEED=$(openssl rand -hex 32)
echo "SOLANA_MASTER_SEED=${SOLANA_MASTER_SEED}" >> .env
```

### Step 4: Configure Application
```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Regenerate caches
php artisan config:cache
php artisan route:cache
```

### Step 5: Test the System
```bash
# Test database connection
php artisan tinker
>>> \App\Models\CryptoWallet::count()
>>> \App\Models\CryptoTransaction::count()

# Test API endpoints
curl -X GET http://your-domain.com/api/crypto/supported-currencies
```

## 🔐 Security Configuration

### 1. Environment Variables
```bash
# Required security settings
CRYPTO_ENABLED=false  # Keep disabled until fully tested
CRYPTO_AUTO_APPROVAL_LIMIT=100.0
CRYPTO_MANUAL_APPROVAL_LIMIT=1000.0
CRYPTO_DAILY_WITHDRAWAL_LIMIT=10000.0
CRYPTO_FRAUD_DETECTION=true
```

### 2. Master Seed Security
```bash
# Generate secure master seed
SOLANA_MASTER_SEED=$(openssl rand -hex 64)

# Set proper permissions
chmod 600 .env
chown www-data:www-data .env
```

### 3. Database Security
```sql
-- Create dedicated crypto user
CREATE USER 'crypto_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE ON your_database.crypto_* TO 'crypto_user'@'localhost';
FLUSH PRIVILEGES;
```

## 🧪 Testing Phase

### 1. Enable Test Mode
```bash
# In .env
CRYPTO_TEST_MODE=true
MOCK_BLOCKCHAIN=true
SIMULATE_CONFIRMATIONS=true
```

### 2. Test User Wallet Creation
```php
// In tinker
$service = app(\App\Services\SolanaWalletService::class);
$result = $service->generateWalletForUser(1); // Test user ID
var_dump($result);
```

### 3. Test API Endpoints
```bash
# Test wallet creation
curl -X GET "http://your-domain.com/api/crypto/wallet" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Test balance check
curl -X GET "http://your-domain.com/api/crypto/balance" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🌐 Frontend Integration

### 1. Add Crypto UI Components
Create these files in your frontend:

```html
<!-- Add to your deposit page -->
<div id="crypto-deposit-section" style="display: none;">
    <h3>Crypto Deposit</h3>
    <div id="crypto-wallet-address"></div>
    <div id="crypto-qr-code"></div>
    <div id="crypto-balance"></div>
</div>

<!-- Add to your withdrawal page -->
<div id="crypto-withdrawal-section" style="display: none;">
    <h3>Crypto Withdrawal</h3>
    <form id="crypto-withdrawal-form">
        <select id="crypto-currency">
            <option value="SOL">Solana (SOL)</option>
            <option value="USDT">Tether (USDT)</option>
        </select>
        <input type="text" id="withdrawal-address" placeholder="Destination Address">
        <input type="number" id="withdrawal-amount" placeholder="Amount">
        <input type="password" id="withdrawal-password" placeholder="Password">
        <button type="submit">Withdraw</button>
    </form>
</div>
```

### 2. Add JavaScript Integration
```javascript
// Add to your main.js
class CryptoWallet {
    constructor() {
        this.apiBase = '/api/crypto';
        this.init();
    }

    async init() {
        if (await this.isCryptoEnabled()) {
            this.showCryptoSections();
            this.loadWalletInfo();
            this.setupEventListeners();
        }
    }

    async isCryptoEnabled() {
        try {
            const response = await fetch(`${this.apiBase}/supported-currencies`);
            return response.ok;
        } catch (e) {
            return false;
        }
    }

    async loadWalletInfo() {
        try {
            const response = await fetch(`${this.apiBase}/wallet`);
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('crypto-wallet-address').textContent = data.wallet.address;
                this.updateBalance();
            }
        } catch (e) {
            console.error('Failed to load wallet info:', e);
        }
    }

    async updateBalance() {
        try {
            const response = await fetch(`${this.apiBase}/balance`);
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('crypto-balance').innerHTML = `
                    <p>SOL: ${data.balance.sol_balance}</p>
                    <p>USDT: ${data.balance.usdt_balance}</p>
                    <p>Total USD: $${data.balance.total_usd_value}</p>
                `;
            }
        } catch (e) {
            console.error('Failed to update balance:', e);
        }
    }

    showCryptoSections() {
        document.getElementById('crypto-deposit-section').style.display = 'block';
        document.getElementById('crypto-withdrawal-section').style.display = 'block';
    }

    setupEventListeners() {
        document.getElementById('crypto-withdrawal-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.processWithdrawal();
        });
    }

    async processWithdrawal() {
        const formData = {
            currency: document.getElementById('crypto-currency').value,
            to_address: document.getElementById('withdrawal-address').value,
            amount: document.getElementById('withdrawal-amount').value,
            password: document.getElementById('withdrawal-password').value
        };

        try {
            const response = await fetch(`${this.apiBase}/withdraw`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();
            
            if (data.success) {
                alert('Withdrawal submitted successfully!');
                this.updateBalance();
            } else {
                alert('Withdrawal failed: ' + data.error);
            }
        } catch (e) {
            alert('Withdrawal failed: ' + e.message);
        }
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    new CryptoWallet();
});
```

## 🔄 Gradual Rollout Strategy

### Phase 1: Internal Testing (1-2 weeks)
```bash
# Enable for admin users only
CRYPTO_ENABLED=true
CRYPTO_TEST_MODE=true
```

### Phase 2: Beta Testing (2-4 weeks)
```bash
# Enable for selected users
CRYPTO_ENABLED=true
CRYPTO_TEST_MODE=false
CRYPTO_AUTO_APPROVAL_LIMIT=50.0  # Lower limits
```

### Phase 3: Full Deployment
```bash
# Enable for all users
CRYPTO_ENABLED=true
CRYPTO_TEST_MODE=false
CRYPTO_AUTO_APPROVAL_LIMIT=100.0
```

## 📊 Monitoring & Maintenance

### 1. Set up Monitoring
```bash
# Add to your cron jobs
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1

# Monitor crypto transactions
0 */6 * * * php /path/to/artisan crypto:monitor-deposits
0 */1 * * * php /path/to/artisan crypto:update-exchange-rates
```

### 2. Database Monitoring
```sql
-- Monitor crypto transactions
SELECT 
    DATE(created_at) as date,
    transaction_type,
    currency,
    COUNT(*) as count,
    SUM(amount_usd) as total_usd
FROM crypto_transactions 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at), transaction_type, currency;
```

### 3. Security Monitoring
```sql
-- Monitor high-risk security events
SELECT 
    user_id,
    action,
    risk_score,
    COUNT(*) as event_count
FROM crypto_security_logs 
WHERE risk_score >= 70 
AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY user_id, action, risk_score;
```

## 🚨 Troubleshooting

### Common Issues

1. **Migration Fails**
   ```bash
   # Check database connection
   php artisan tinker
   >>> DB::connection()->getPdo()
   
   # Run migrations one by one
   php artisan migrate --path=database/migrations/specific_migration.php
   ```

2. **API Returns 500 Error**
   ```bash
   # Check logs
   tail -f storage/logs/laravel.log
   
   # Clear caches
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Wallet Generation Fails**
   ```bash
   # Check master seed
   php artisan tinker
   >>> config('solana.wallet.master_seed')
   
   # Verify permissions
   ls -la .env
   ```

### Emergency Rollback
```bash
# Disable crypto payments immediately
echo "CRYPTO_ENABLED=false" >> .env
php artisan config:clear

# Rollback database if needed
mysql -u user -p database < aviator_backup_YYYYMMDD.sql
```

## 📞 Support

### Log Files to Check
- `storage/logs/laravel.log` - Application logs
- `storage/logs/crypto.log` - Crypto-specific logs
- Database slow query log

### Key Metrics to Monitor
- Crypto transaction success rate
- Average deposit/withdrawal processing time
- Security event frequency
- Exchange rate update frequency

## ✅ Go-Live Checklist

- [ ] Database migrations completed
- [ ] Environment variables configured
- [ ] Security settings verified
- [ ] API endpoints tested
- [ ] Frontend integration working
- [ ] Monitoring systems active
- [ ] Backup procedures in place
- [ ] Support team trained
- [ ] Documentation updated
- [ ] Rollback plan ready

---

**🔐 Security Note**: Never commit your `.env` file or master seed to version control. Always use secure, encrypted storage for sensitive configuration data.

**⚠️ Important**: This system handles real money. Test thoroughly in a staging environment before deploying to production. 