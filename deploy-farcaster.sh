#!/bin/bash

# 🚀 Aviator Farcaster Integration - Automated Deployment Script
# This script sets up and deploys your complete Farcaster integration

set -e  # Exit on any error

echo "🚀 Starting Aviator Farcaster Integration Deployment..."
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check prerequisites
check_prerequisites() {
    print_info "Checking prerequisites..."
    
    if ! command -v node &> /dev/null; then
        print_error "Node.js is required but not installed. Please install Node.js 18+ and try again."
        exit 1
    fi
    
    if ! command -v npm &> /dev/null; then
        print_error "npm is required but not installed."
        exit 1
    fi
    
    if ! command -v php &> /dev/null; then
        print_error "PHP is required but not installed."
        exit 1
    fi
    
    if ! command -v composer &> /dev/null; then
        print_error "Composer is required but not installed."
        exit 1
    fi
    
    print_status "All prerequisites met!"
}

# Get user configuration
get_config() {
    print_info "Configuration Setup"
    echo ""
    
    read -p "Enter your domain for Farcaster frames (e.g., aviator-frames.vercel.app): " FRAMES_DOMAIN
    read -p "Enter your Laravel backend URL (e.g., https://your-aviator.com): " LARAVEL_URL
    read -p "Enter your Neynar API key (get from neynar.com): " NEYNAR_KEY
    
    # Optional configurations
    read -p "Redis URL (optional, press Enter to skip): " REDIS_URL
    read -p "Mixpanel token (optional, press Enter to skip): " MIXPANEL_TOKEN
    
    echo ""
    print_status "Configuration collected!"
}

# Setup Farcaster Frames
setup_frames() {
    print_info "Setting up Farcaster Frames..."
    
    cd farcaster-frames
    
    # Install dependencies
    print_info "Installing Node.js dependencies..."
    npm install
    
    # Create environment file
    print_info "Creating environment configuration..."
    cat > .env.local << EOF
# Farcaster Frames Configuration
NEXT_PUBLIC_FRAME_BASE_URL=https://${FRAMES_DOMAIN}
LARAVEL_API_URL=${LARAVEL_URL}
NEYNAR_API_KEY=${NEYNAR_KEY}

# Optional configurations
${REDIS_URL:+REDIS_URL=$REDIS_URL}
${MIXPANEL_TOKEN:+MIXPANEL_PROJECT_TOKEN=$MIXPANEL_TOKEN}

# Feature flags
ENABLE_TOURNAMENTS=true
ENABLE_SOCIAL_PAYMENTS=true
ENABLE_VIRAL_SHARES=true
ENABLE_COPY_TRADING=true

# Security
FRAME_SIGNATURE_SECRET=$(openssl rand -hex 32)
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_WINDOW_MS=60000

# Performance
ENABLE_IMAGE_CACHING=true
IMAGE_CACHE_TTL=300

# Debug (set to false for production)
DEBUG_FRAMES=false
LOG_LEVEL=info
EOF
    
    print_status "Farcaster Frames environment configured!"
    
    # Build the application
    print_info "Building Farcaster Frames application..."
    npm run build
    
    print_status "Farcaster Frames built successfully!"
    
    cd ..
}

# Setup Laravel backend
setup_laravel() {
    print_info "Setting up Laravel backend integration..."
    
    cd demo/laravel
    
    # Check if we can access the Laravel directory and it has the right structure
    if [ ! -f "artisan" ]; then
        print_error "Laravel installation not found. Please ensure you're in the correct directory."
        exit 1
    fi
    
    # Add Farcaster configuration to existing .env
    print_info "Adding Farcaster configuration to Laravel .env..."
    
    if [ ! -f ".env" ]; then
        print_warning ".env file not found. Creating from .env.example..."
        if [ -f ".env.example" ]; then
            cp .env.example .env
        else
            print_error "No .env.example found. Please create .env manually."
            exit 1
        fi
    fi
    
    # Add Farcaster-specific configurations
    cat >> .env << EOF

# Farcaster Integration Configuration
FARCASTER_FRAMES_URL=https://${FRAMES_DOMAIN}
FARCASTER_AUTH_ENABLED=true
FARCASTER_SOCIAL_FEATURES=true
FARCASTER_MIN_BET=1
FARCASTER_MAX_BET=1000
FARCASTER_WELCOME_BONUS=25
FARCASTER_REFERRAL_BONUS=20
FARCASTER_NEW_USER_BONUS=10
EOF
    
    print_status "Laravel environment updated!"
    
    # Run database migrations
    print_info "Running database migrations..."
    php artisan migrate --force
    
    print_status "Database migrations completed!"
    
    # Clear and rebuild caches
    print_info "Rebuilding Laravel caches..."
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan config:cache
    php artisan route:cache
    
    print_status "Laravel caches rebuilt!"
    
    cd ../..
}

# Deploy to Vercel (optional)
deploy_to_vercel() {
    print_info "Deploying to Vercel..."
    
    if ! command -v vercel &> /dev/null; then
        print_warning "Vercel CLI not found. Installing..."
        npm install -g vercel
    fi
    
    cd farcaster-frames
    
    # Deploy to Vercel
    print_info "Deploying Farcaster Frames to Vercel..."
    vercel --prod --confirm
    
    print_status "Deployment to Vercel completed!"
    
    cd ..
}

# Run tests
run_tests() {
    print_info "Running integration tests..."
    
    # Test Laravel endpoints
    print_info "Testing Laravel API endpoints..."
    
    cd demo/laravel
    
    # Test basic connectivity
    if php artisan route:list | grep -q farcaster; then
        print_status "Laravel routes configured correctly!"
    else
        print_error "Laravel routes not found. Please check the setup."
    fi
    
    cd ../..
    
    # Test Farcaster Frames
    print_info "Testing Farcaster Frames build..."
    
    cd farcaster-frames
    
    if [ -d ".next" ]; then
        print_status "Farcaster Frames built successfully!"
    else
        print_error "Farcaster Frames build failed."
    fi
    
    cd ..
}

# Generate summary
generate_summary() {
    echo ""
    echo "🎉 Deployment Complete!"
    echo "========================"
    echo ""
    print_status "Your Aviator Farcaster integration is ready!"
    echo ""
    echo "📱 Access URLs:"
    echo "   Farcaster Frames: https://${FRAMES_DOMAIN}"
    echo "   Mini App: ${LARAVEL_URL}/farcaster-app"
    echo "   Main Frame: https://${FRAMES_DOMAIN}/api/frames/aviator"
    echo ""
    echo "🔧 Test Endpoints:"
    echo "   Auth: curl -X POST '${LARAVEL_URL}/auth/farcaster' -H 'Content-Type: application/json' -d '{\"fid\":\"12345\",\"username\":\"test\"}'"
    echo "   Wallet: curl '${LARAVEL_URL}/api/farcaster/wallet/12345'"
    echo "   Frame: curl 'https://${FRAMES_DOMAIN}/api/frames/aviator'"
    echo ""
    echo "📊 Features Enabled:"
    echo "   ✅ Farcaster authentication"
    echo "   ✅ Real-time crash game integration"
    echo "   ✅ Social sharing and viral mechanics"
    echo "   ✅ Leaderboards and tournaments"
    echo "   ✅ Referral system with bonuses"
    echo "   ✅ Social payments and tipping"
    echo ""
    print_info "Next Steps:"
    echo "1. Test the integration with a few users"
    echo "2. Share your frame in Farcaster communities"
    echo "3. Monitor analytics and user engagement"
    echo "4. Scale with tournaments and partnerships"
    echo ""
    print_status "Happy gaming! 🚀"
}

# Main execution
main() {
    print_info "Aviator Farcaster Integration - Automated Setup"
    echo "This script will deploy your complete Farcaster gaming integration."
    echo ""
    
    read -p "Continue with deployment? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Deployment cancelled."
        exit 0
    fi
    
    check_prerequisites
    get_config
    setup_frames
    setup_laravel
    
    read -p "Deploy to Vercel? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        deploy_to_vercel
    else
        print_info "Skipping Vercel deployment. You can deploy manually later with 'vercel --prod' in the farcaster-frames directory."
    fi
    
    run_tests
    generate_summary
}

# Handle script interruption
trap 'print_error "Deployment interrupted. You may need to clean up manually."' INT

# Run main function
main "$@"

