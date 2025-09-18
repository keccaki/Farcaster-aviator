#!/bin/bash

# 🔍 Aviator Farcaster Deployment Verification Script

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Configuration
read -p "Enter your Farcaster Frames URL (e.g., https://aviator-frames.vercel.app): " FRAMES_URL
read -p "Enter your Laravel backend URL (e.g., https://your-aviator.com): " LARAVEL_URL

echo ""
print_info "🧪 Testing Aviator Farcaster Integration..."
echo "=========================================="

# Test 1: Frame Endpoints
print_info "Testing Frame endpoints..."

if curl -s -f "${FRAMES_URL}/api/frames/aviator" > /dev/null; then
    print_success "Main frame endpoint accessible"
else
    print_error "Main frame endpoint failed"
fi

if curl -s -f "${FRAMES_URL}/api/frames/aviator/betting" > /dev/null; then
    print_success "Betting frame endpoint accessible"
else
    print_error "Betting frame endpoint failed"
fi

if curl -s -f "${FRAMES_URL}/api/frames/aviator/social" > /dev/null; then
    print_success "Social frame endpoint accessible"
else
    print_error "Social frame endpoint failed"
fi

# Test 2: Laravel API
print_info "Testing Laravel API endpoints..."

if curl -s -f "${LARAVEL_URL}/api/farcaster/leaderboard" > /dev/null; then
    print_success "Laravel Farcaster API accessible"
else
    print_error "Laravel Farcaster API failed"
fi

# Test 3: Farcaster Auth
print_info "Testing Farcaster authentication..."

AUTH_RESPONSE=$(curl -s -w "%{http_code}" -X POST "${LARAVEL_URL}/auth/farcaster" \
    -H "Content-Type: application/json" \
    -d '{"fid": 12345, "username": "tester", "displayName": "Test User"}')

if [[ "${AUTH_RESPONSE: -3}" == "200" ]]; then
    print_success "Farcaster authentication working"
else
    print_warning "Farcaster authentication returned: ${AUTH_RESPONSE: -3}"
fi

# Test 4: Frame Validation
print_info "Testing frame structure..."

FRAME_HTML=$(curl -s "${FRAMES_URL}/api/frames/aviator")

if [[ $FRAME_HTML == *"fc:frame"* ]]; then
    print_success "Frame has valid meta tags"
else
    print_error "Frame missing fc:frame meta tags"
fi

if [[ $FRAME_HTML == *"fc:frame:image"* ]]; then
    print_success "Frame has image meta tag"
else
    print_error "Frame missing image meta tag"
fi

if [[ $FRAME_HTML == *"fc:frame:button"* ]]; then
    print_success "Frame has button meta tags"
else
    print_error "Frame missing button meta tags"
fi

echo ""
print_info "🎯 Next Steps for Farcaster Testing:"
echo ""
echo "1. 📱 Open Warpcast Frame Validator:"
echo "   https://warpcast.com/~/developers/frames?url=${FRAMES_URL}/api/frames/aviator"
echo ""
echo "2. 🧪 Use Frame Debugger:"
echo "   https://debugger.framesjs.org"
echo ""
echo "3. 📝 Test with your frame tester:"
echo "   file://$(pwd)/test-frame.html"
echo ""
echo "4. 🔍 Manual Tests:"
echo "   - Create cast with frame URL in Warpcast"
echo "   - Test button interactions"
echo "   - Verify game state updates"
echo "   - Check payment flows"
echo ""

if command -v python3 &> /dev/null; then
    print_info "🌐 Starting local frame tester..."
    echo "Opening test-frame.html in browser..."
    python3 -m http.server 8080 &
    sleep 2
    open "http://localhost:8080/test-frame.html" 2>/dev/null || print_info "Open http://localhost:8080/test-frame.html manually"
    print_info "Press Ctrl+C when done testing"
    wait
else
    print_info "Open test-frame.html in your browser to continue testing"
fi

