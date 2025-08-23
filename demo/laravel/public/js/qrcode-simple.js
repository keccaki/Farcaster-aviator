/**
 * Simple QR Code Generator for Crypto Wallet Addresses
 * Uses Google Charts API as fallback for QR code generation
 */

window.QRCode = function(container, options) {
    if (!container) return;
    
    const text = options.text || options;
    const size = options.width || options.height || 200;
    
    // Clear container
    if (typeof container === 'string') {
        container = document.querySelector(container);
    }
    
    if (!container) return;
    
    container.innerHTML = '';
    
    // Create QR code using Google Charts API
    const img = document.createElement('img');
    img.src = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(text)}`;
    img.alt = 'QR Code';
    img.style.maxWidth = '100%';
    img.style.height = 'auto';
    img.style.border = '1px solid #ddd';
    img.style.borderRadius = '8px';
    
    container.appendChild(img);
    
    return {
        makeCode: function(newText) {
            img.src = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(newText)}`;
        }
    };
}; 