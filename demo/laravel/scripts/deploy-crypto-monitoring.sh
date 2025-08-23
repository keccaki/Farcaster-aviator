#!/bin/bash

# Aviator Crypto Deposit Monitoring Deployment Script
# This script sets up the automated deposit monitoring service

set -e

echo "🚀 Deploying Aviator Crypto Deposit Monitoring System..."

# Configuration
PROJECT_ROOT="/var/www/aviator/laravel"
SERVICE_FILE="crypto-deposit-monitor.service"
SERVICE_NAME="crypto-deposit-monitor"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_requirements() {
    log_info "Checking system requirements..."
    
    # Check if PHP is installed
    if ! command -v php &> /dev/null; then
        log_error "PHP is not installed. Please install PHP 8.1 or higher."
        exit 1
    fi
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    log_info "PHP version: $PHP_VERSION"
    
    # Check if Laravel project exists
    if [ ! -f "$PROJECT_ROOT/artisan" ]; then
        log_error "Laravel project not found at $PROJECT_ROOT"
        exit 1
    fi
    
    # Check if systemd is available
    if ! command -v systemctl &> /dev/null; then
        log_warning "systemctl not found. Systemd service will not be installed."
    fi
    
    log_success "Requirements check passed"
}

run_migrations() {
    log_info "Running crypto database migrations..."
    
    cd "$PROJECT_ROOT"
    
    # Run migrations
    php artisan migrate --force
    
    if [ $? -eq 0 ]; then
        log_success "Database migrations completed"
    else
        log_error "Database migrations failed"
        exit 1
    fi
}

test_crypto_commands() {
    log_info "Testing crypto monitoring commands..."
    
    cd "$PROJECT_ROOT"
    
    # Test the command exists
    if php artisan crypto:monitor-deposits --help &> /dev/null; then
        log_success "Crypto monitoring command is available"
    else
        log_error "Crypto monitoring command not found"
        exit 1
    fi
    
    # Test one-time monitoring run
    log_info "Running test monitoring cycle..."
    php artisan crypto:monitor-deposits --once
    
    if [ $? -eq 0 ]; then
        log_success "Test monitoring cycle completed"
    else
        log_warning "Test monitoring cycle had issues (this may be normal if no wallets exist)"
    fi
}

install_systemd_service() {
    if ! command -v systemctl &> /dev/null; then
        log_warning "Skipping systemd service installation (systemd not available)"
        return 0
    fi
    
    log_info "Installing systemd service..."
    
    # Copy service file
    if [ -f "$PROJECT_ROOT/$SERVICE_FILE" ]; then
        sudo cp "$PROJECT_ROOT/$SERVICE_FILE" "/etc/systemd/system/"
        sudo chmod 644 "/etc/systemd/system/$SERVICE_FILE"
        
        # Update the service file with correct paths
        sudo sed -i "s|/var/www/aviator/laravel|$PROJECT_ROOT|g" "/etc/systemd/system/$SERVICE_FILE"
        
        # Reload systemd
        sudo systemctl daemon-reload
        
        # Enable the service
        sudo systemctl enable "$SERVICE_NAME"
        
        log_success "Systemd service installed and enabled"
        
        # Show service status
        log_info "Service status:"
        sudo systemctl status "$SERVICE_NAME" --no-pager || true
        
    else
        log_error "Service file $SERVICE_FILE not found in $PROJECT_ROOT"
        exit 1
    fi
}

setup_logging() {
    log_info "Setting up logging configuration..."
    
    # Create logs directory if it doesn't exist
    LOG_DIR="$PROJECT_ROOT/storage/logs"
    if [ ! -d "$LOG_DIR" ]; then
        mkdir -p "$LOG_DIR"
    fi
    
    # Set proper permissions
    sudo chown -R www-data:www-data "$LOG_DIR"
    sudo chmod -R 755 "$LOG_DIR"
    
    log_success "Logging configuration completed"
}

show_usage_instructions() {
    log_success "🎉 Crypto deposit monitoring system deployed successfully!"
    echo
    echo "📋 Usage Instructions:"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo
    echo "Manual Commands:"
    echo "  • Test monitoring:          php artisan crypto:monitor-deposits --once"
    echo "  • Continuous monitoring:    php artisan crypto:monitor-deposits"
    echo "  • Custom interval:          php artisan crypto:monitor-deposits --interval=60"
    echo "  • Monitor specific wallets: php artisan crypto:monitor-deposits --wallets=addr1,addr2"
    echo
    if command -v systemctl &> /dev/null; then
        echo "Systemd Service Commands:"
        echo "  • Start service:    sudo systemctl start $SERVICE_NAME"
        echo "  • Stop service:     sudo systemctl stop $SERVICE_NAME"
        echo "  • Restart service:  sudo systemctl restart $SERVICE_NAME"
        echo "  • View status:      sudo systemctl status $SERVICE_NAME"
        echo "  • View logs:        sudo journalctl -u $SERVICE_NAME -f"
        echo
        echo "🔧 To start monitoring automatically:"
        echo "     sudo systemctl start $SERVICE_NAME"
    fi
    echo
    echo "📊 Monitor logs:"
    echo "  • Laravel logs:     tail -f $PROJECT_ROOT/storage/logs/laravel.log"
    echo "  • System logs:      sudo journalctl -u $SERVICE_NAME -f"
    echo
    echo "🌐 API Endpoints:"
    echo "  • Manual monitoring: POST /api/crypto/monitor-deposits"
    echo "  • Wallet balance:    GET /api/crypto/balance"
    echo "  • Transaction history: GET /api/crypto/transactions"
    echo
    echo "⚙️  Configuration:"
    echo "  • Update monitoring interval in: $PROJECT_ROOT/.env"
    echo "  • Solana RPC endpoint: SOLANA_RPC_ENDPOINT"
    echo "  • Required confirmations: SOLANA_REQUIRED_CONFIRMATIONS"
    echo
}

# Main deployment flow
main() {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "🏦 Aviator Crypto Deposit Monitoring - Deployment Script"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo
    
    # Step 1: Check requirements
    check_requirements
    echo
    
    # Step 2: Run migrations
    run_migrations
    echo
    
    # Step 3: Test commands
    test_crypto_commands
    echo
    
    # Step 4: Setup logging
    setup_logging
    echo
    
    # Step 5: Install systemd service
    install_systemd_service
    echo
    
    # Step 6: Show usage instructions
    show_usage_instructions
}

# Check if running as root for systemd operations
if [[ $EUID -eq 0 ]] && [[ "$1" != "--allow-root" ]]; then
   log_error "This script should not be run as root (except for systemd operations)"
   log_info "Run as: ./deploy-crypto-monitoring.sh"
   exit 1
fi

# Run main function
main "$@" 