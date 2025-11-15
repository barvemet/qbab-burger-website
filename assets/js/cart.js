// Q-Bab Burger - Shopping Cart System
// Uses localStorage for persistence

class ShoppingCart {
    constructor() {
        this.items = this.loadCart();
        this.init();
    }

    init() {
        this.updateCartDisplay();
        // Wait for DOM to be ready before attaching listeners
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.attachEventListeners());
        } else {
            this.attachEventListeners();
        }
    }

    // Load cart from localStorage
    loadCart() {
        const savedCart = localStorage.getItem('qbab_cart');
        return savedCart ? JSON.parse(savedCart) : [];
    }

    // Save cart to localStorage
    saveCart() {
        localStorage.setItem('qbab_cart', JSON.stringify(this.items));
        this.updateCartDisplay();
    }

    // Add item to cart
    addItem(item) {
        const existingItem = this.items.find(i => i.id === item.id);

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({
                id: item.id,
                name: item.name,
                price: parseFloat(item.price),
                image: item.image || '',
                quantity: 1
            });
        }

        this.saveCart();
        this.showNotification(translations.cart_item_added || 'Item added to cart!', 'success');

        // Update cart icon count
        if (typeof updateCartIcon === 'function') {
            updateCartIcon();
        }

        // Update popup content if it exists (but don't auto-show)
        if (typeof updateCartPopup === 'function') {
            updateCartPopup();
        }
    }

    // Add item with extras to cart
    addItemWithExtras(item) {
        // Each item with different extras is a unique cart entry
        this.items.push({
            id: item.id,
            name: item.name,
            price: parseFloat(item.price),
            image: item.image || '',
            quantity: item.quantity || 1,
            extras: item.extras || [],
            cartItemId: Date.now() // Unique ID for this specific cart item
        });

        this.saveCart();
        this.showNotification(translations.cart_item_added || 'Item added to cart!', 'success');

        // Update cart icon count
        if (typeof updateCartIcon === 'function') {
            updateCartIcon();
        }

        // Update popup content if it exists
        if (typeof updateCartPopup === 'function') {
            updateCartPopup();
        }
    }

    // Remove item from cart
    removeItem(itemId) {
        this.items = this.items.filter(item => item.id !== itemId);
        this.saveCart();
        this.renderCartPage();
        this.showNotification(translations.cart_item_removed || 'Item removed from cart', 'info');
        
        // Update cart icon count
        if (typeof updateCartIcon === 'function') {
            updateCartIcon();
        }
    }

    // Update item quantity
    updateQuantity(itemId, newQuantity) {
        const item = this.items.find(i => i.id === itemId);
        
        if (item) {
            if (newQuantity <= 0) {
                this.removeItem(itemId);
            } else {
                item.quantity = newQuantity;
                this.saveCart();
                this.renderCartPage();
                
                // Update cart icon count
                if (typeof updateCartIcon === 'function') {
                    updateCartIcon();
                }
            }
        }
    }

    // Get cart total
    getTotal() {
        return this.items.reduce((total, item) => {
            // Calculate item price
            let itemPrice = item.price;
            
            // Add extras prices if they exist
            if (item.extras && item.extras.length > 0) {
                const extrasTotal = item.extras.reduce((sum, extra) => sum + parseFloat(extra.price), 0);
                itemPrice += extrasTotal;
            }
            
            return total + (itemPrice * item.quantity);
        }, 0);
    }

    // Get cart item count
    getItemCount() {
        return this.items.reduce((count, item) => count + item.quantity, 0);
    }

    // Clear cart
    clearCart() {
        this.items = [];
        this.saveCart();
        this.renderCartPage();
    }

    // Update cart display (icon badge)
    updateCartDisplay() {
        const cartBadges = document.querySelectorAll('.cart-badge');
        const itemCount = this.getItemCount();

        if (cartBadges.length > 0) {
            // Update ALL badge elements on the page
            cartBadges.forEach((badge) => {
                badge.textContent = itemCount.toString();
                badge.innerHTML = itemCount.toString();
            });
        }
    }

    // Render cart page
    renderCartPage() {
        const cartContainer = document.querySelector('.cart-items');
        
        if (!cartContainer) return;

        if (this.items.length === 0) {
            cartContainer.innerHTML = `
                <div class="text-center" style="padding: 50px 0;">
                    <div class="empty-cart-icon">🛒</div>
                    <h3 style="color: #333; margin-bottom: 20px;">${translations.cart_empty || 'Your cart is empty'}</h3>
                    <a href="menu.php" class="btn btn-primary mt-3">${translations.cart_continue_shopping || 'Continue Shopping'}</a>
                </div>
            `;
            
            // Hide summary
            const summary = document.querySelector('.cart-summary');
            if (summary) summary.style.display = 'none';
            return;
        }

        let cartHTML = '';
        
        this.items.forEach(item => {
            // Calculate item total with extras
            let itemPrice = item.price;
            let extrasHTML = '';
            
            if (item.extras && item.extras.length > 0) {
                extrasHTML = '<div class="cart-item-extras">';
                item.extras.forEach(extra => {
                    extrasHTML += `<div class="cart-extra">+ ${extra.name} (${this.formatPrice(extra.price)})</div>`;
                    itemPrice += parseFloat(extra.price);
                });
                extrasHTML += '</div>';
            }
            
            const itemTotal = itemPrice * item.quantity;
            
            cartHTML += `
                <div class="cart-item" data-item-id="${item.id}">
                    ${item.image ? `
                    <div class="cart-item-image">
                        <img src="${item.image}" alt="${item.name}">
                    </div>
                    ` : ''}
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">${this.formatPrice(item.price)}</div>
                        ${extrasHTML}
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn minus" data-item-id="${item.id}">−</button>
                        <span class="quantity-value">${item.quantity}</span>
                        <button class="quantity-btn plus" data-item-id="${item.id}">+</button>
                    </div>
                    <div class="cart-item-total">
                        ${this.formatPrice(itemTotal)}
                    </div>
                    <button class="remove-btn" data-item-id="${item.id}">${translations.cart_remove || 'Remove'}</button>
                </div>
            `;
        });

        cartContainer.innerHTML = cartHTML;
        this.updateCartSummary();
        this.attachCartPageListeners();
    }

    // Update cart summary
    updateCartSummary() {
        const summary = document.querySelector('.cart-summary');
        if (!summary) return;

        summary.style.display = 'block';

        const total = this.getTotal();
        const itemCount = this.getItemCount();

        summary.innerHTML = `
            <h3>${translations.checkout_order_summary || 'Order Summary'}</h3>
            <div class="summary-row">
                <span>${translations.cart_item || 'Items'} (${itemCount}):</span>
                <span>${this.formatPrice(total)}</span>
            </div>
            <div class="summary-row summary-total">
                <span>${translations.cart_total || 'Total'}:</span>
                <span>${this.formatPrice(total)}</span>
            </div>
            <button onclick="handleCheckout()" class="btn btn-primary" style="width: 100%; margin-top: 20px;">
                ${translations.cart_checkout || 'Proceed to Checkout'}
            </button>
            <a href="menu.php" class="btn btn-secondary" style="width: 100%; margin-top: 10px;">
                ${translations.cart_continue_shopping || 'Continue Shopping'}
            </a>
        `;
    }

    // Attach cart page event listeners
    attachCartPageListeners() {
        // Quantity buttons
        document.querySelectorAll('.quantity-btn.plus').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const itemId = parseInt(e.target.dataset.itemId);
                const item = this.items.find(i => i.id === itemId);
                if (item) {
                    this.updateQuantity(itemId, item.quantity + 1);
                }
            });
        });

        document.querySelectorAll('.quantity-btn.minus').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const itemId = parseInt(e.target.dataset.itemId);
                const item = this.items.find(i => i.id === itemId);
                if (item) {
                    this.updateQuantity(itemId, item.quantity - 1);
                }
            });
        });

        // Remove buttons
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const itemId = parseInt(e.target.dataset.itemId);
                this.removeItem(itemId);
            });
        });
    }

    // Attach event listeners for add to cart buttons
    attachEventListeners() {
        // Only attach to buttons that DON'T open modal
        // Buttons with .open-product-modal class should open modal instead
        document.querySelectorAll('.add-to-cart-btn:not(.open-product-modal)').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const itemData = {
                    id: parseInt(e.target.dataset.id),
                    name: e.target.dataset.name,
                    price: e.target.dataset.price,
                    image: e.target.dataset.image
                };
                this.addItem(itemData);
            });
        });
    }

    // Format price
    formatPrice(price) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR'
        }).format(price);
    }

    // Show notification
    showNotification(message, type = 'success') {
        // Remove existing notifications
        const existing = document.querySelector('.notification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : '#f89628'};
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Get cart data for checkout
    getCartData() {
        return {
            items: this.items,
            total: this.getTotal(),
            itemCount: this.getItemCount()
        };
    }
}

// Initialize cart
const cart = new ShoppingCart();

// Make cart globally accessible for product modal
window.cart = cart;

// Toggle Cart Popup (called from header.php inline script or manually)
function toggleCartPopup() {
    const popup = document.getElementById('cartPopup');
    if (!popup) return;

    const isActive = popup.classList.contains('active');

    if (isActive) {
        popup.classList.remove('active');
    } else {
        popup.classList.add('active');
        updateCartPopup();
    }
}

// Update cart icon count
function updateCartIcon() {
    const cartCountElements = document.querySelectorAll('.cart-count');
    const itemCount = cart.getItemCount();
    
    cartCountElements.forEach(element => {
        element.textContent = itemCount;
        element.style.display = itemCount > 0 ? 'block' : 'none';
    });
}

// Update Cart Popup with items
function updateCartPopup() {
    const popupItems = document.getElementById('cartPopupItems');
    const popupTotal = document.querySelector('.cart-popup-total');
    const popup = document.getElementById('cartPopupGlobal') || document.getElementById('cartPopup');

    if (!popupItems) return;
    
    // Update cart icon count
    updateCartIcon();

    if (cart.items.length === 0) {
        popupItems.innerHTML = `
            <div class="cart-popup-empty">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <p>${translations.cart_empty || 'Your cart is empty'}</p>
            </div>
        `;
        if (popupTotal) popupTotal.textContent = cart.formatPrice(0);
        return;
    }

    let itemsHTML = '';
    cart.items.forEach(item => {
        // Calculate item price with extras
        let itemPrice = item.price;
        let extrasHTML = '';
        
        if (item.extras && item.extras.length > 0) {
            item.extras.forEach(extra => {
                extrasHTML += `<div class="cart-popup-extra">+ ${extra.name}</div>`;
                itemPrice += parseFloat(extra.price);
            });
        }
        
        const itemTotal = itemPrice * item.quantity;
        
        itemsHTML += `
            <div class="cart-popup-item">
                ${item.image ? `
                <div class="cart-popup-item-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                ` : ''}
                <div class="cart-popup-item-details">
                    <div class="cart-popup-item-name">${item.name}</div>
                    ${extrasHTML}
                    <div class="cart-popup-item-quantity">${item.quantity} × ${cart.formatPrice(itemPrice)}</div>
                </div>
                <div class="cart-popup-item-price">
                    ${cart.formatPrice(itemTotal)}
                </div>
            </div>
        `;
    });

    popupItems.innerHTML = itemsHTML;
    if (popupTotal) popupTotal.textContent = cart.formatPrice(cart.getTotal());
}

// Handle checkout from popup
function handleCheckoutFromPopup() {
    const popup = document.getElementById('cartPopup');
    popup.classList.remove('active');

    // Check if on cart page already
    if (window.location.pathname.includes('cart.php')) {
        handleCheckout();
    } else {
        window.location.href = 'cart.php';
    }
}

// Close popup when clicking outside
document.addEventListener('click', function(e) {
    const popup = document.getElementById('cartPopup');
    const cartWrapper = document.querySelector('.cart-dropdown-wrapper');

    if (popup && cartWrapper && !cartWrapper.contains(e.target)) {
        popup.classList.remove('active');
    }
});

// Render cart page if on cart.php
if (window.location.pathname.includes('cart.php')) {
    cart.renderCartPage();
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize cart icon count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartIcon();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ShoppingCart;
}
