/**
 * Q-Bab Kassa System - JavaScript
 * Modern POS system with offline support
 */

class KasseSystem {
    constructor() {
        this.cart = [];
        this.products = [];
        this.categories = [];
        this.currentCategory = 'all';
        this.sessionId = null;
        this.cashierName = '';
        this.isOnline = navigator.onLine;

        this.init();
    }

    async init() {
        // Check session
        this.checkSession();

        // Setup event listeners
        this.setupEventListeners();

        // Start clock
        this.startClock();

        // Check online status
        this.setupOnlineStatusCheck();

        // Load products if logged in
        if (this.sessionId) {
            await this.loadProducts();
        }
    }

    // === Session Management ===

    checkSession() {
        // Check if session exists (from PHP session)
        const sessionData = this.getSessionData();
        if (sessionData) {
            this.sessionId = sessionData.sessionId;
            this.cashierName = sessionData.cashierName;
        }
    }

    getSessionData() {
        // This would be injected by PHP or retrieved via API
        // For now, return null if not logged in
        return null;
    }

    setupEventListeners() {
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // End session button
        const endSessionBtn = document.getElementById('endSessionBtn');
        if (endSessionBtn) {
            endSessionBtn.addEventListener('click', () => this.handleEndSession());
        }

        // Clear cart button
        const clearCartBtn = document.getElementById('clearCartBtn');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', () => this.clearCart());
        }

        // Payment buttons
        const payWithCashBtn = document.getElementById('payWithCashBtn');
        const payWithCardBtn = document.getElementById('payWithCardBtn');

        if (payWithCashBtn) {
            payWithCashBtn.addEventListener('click', () => this.openCashPayment());
        }

        if (payWithCardBtn) {
            payWithCardBtn.addEventListener('click', () => this.openCardPayment());
        }

        // Cash payment
        const cashGiven = document.getElementById('cashGiven');
        if (cashGiven) {
            cashGiven.addEventListener('input', () => this.calculateChange());
        }

        // Quick cash buttons
        document.querySelectorAll('.quick-cash').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleQuickCash(e));
        });

        // Complete cash payment
        const completeCashPayment = document.getElementById('completeCashPayment');
        if (completeCashPayment) {
            completeCashPayment.addEventListener('click', () => this.completeCashPayment());
        }

        // Complete card payment
        const completeCardPayment = document.getElementById('completeCardPayment');
        if (completeCardPayment) {
            completeCardPayment.addEventListener('click', () => this.completeCardPayment());
        }

        // Modal close buttons
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                if (modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // Cancel buttons
        document.getElementById('cancelCashPayment')?.addEventListener('click', () => {
            document.getElementById('cashPaymentModal').classList.remove('active');
        });

        document.getElementById('cancelCardPayment')?.addEventListener('click', () => {
            document.getElementById('cardPaymentModal').classList.remove('active');
        });

        // New order button
        document.getElementById('newOrderBtn')?.addEventListener('click', () => {
            document.getElementById('receiptModal').classList.remove('active');
            this.clearCart();
        });

        // Print receipt button
        document.getElementById('printReceiptBtn')?.addEventListener('click', () => {
            const iframe = document.getElementById('receiptFrame');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.print();
            }
        });
    }

    // === Login/Logout ===

    async handleLogin(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const cashierName = formData.get('cashierName');
        const openingCash = parseFloat(formData.get('openingCash'));

        try {
            const response = await fetch('/api/kasse/start-session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cashierName,
                    openingCash
                })
            });

            const data = await response.json();

            if (data.success) {
                this.sessionId = data.sessionId;
                this.cashierName = cashierName;

                // Update UI
                document.getElementById('sessionInfo').innerHTML =
                    `Kassierer: <strong>${cashierName}</strong>`;

                // Switch screens
                document.getElementById('loginScreen').style.display = 'none';
                document.getElementById('mainScreen').style.display = 'flex';

                // Load products
                await this.loadProducts();

                this.showToast('Schicht erfolgreich gestartet!', 'success');
            } else {
                this.showToast('Fehler beim Starten der Schicht', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showToast('Verbindungsfehler', 'error');
        }
    }

    async handleEndSession() {
        if (!confirm('Möchten Sie die Schicht wirklich beenden?')) {
            return;
        }

        try {
            const closingCash = parseFloat(prompt('Kassenstand eingeben (€):') || '0');

            const response = await fetch('/api/kasse/end-session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    sessionId: this.sessionId,
                    closingCash
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Schicht erfolgreich beendet!', 'success');

                // Show summary
                const summary = data.summary;
                alert(`Schicht beendet!\n\nUmsatz: ${summary.totalRevenue}€\nBestellungen: ${summary.totalOrders}\nDifferenz: ${summary.cashDifference}€`);

                // Logout
                this.sessionId = null;
                this.cashierName = '';
                this.cart = [];

                // Switch screens
                document.getElementById('mainScreen').style.display = 'none';
                document.getElementById('loginScreen').style.display = 'flex';
            }
        } catch (error) {
            console.error('End session error:', error);
            this.showToast('Fehler beim Beenden der Schicht', 'error');
        }
    }

    // === Product Management ===

    async loadProducts() {
        try {
            const response = await fetch('/api/kasse/get-products.php');
            const data = await response.json();

            if (data.success) {
                this.categories = data.categories;
                this.products = data.products;

                this.renderCategories();
                this.renderProducts();
            }
        } catch (error) {
            console.error('Error loading products:', error);
            this.showToast('Fehler beim Laden der Produkte', 'error');
        }
    }

    renderCategories() {
        const container = document.getElementById('categoryTabs');
        if (!container) return;

        let html = '<button class="category-tab active" data-category="all">Alle</button>';

        this.categories.forEach(category => {
            html += `
                <button class="category-tab" data-category="${category.id}">
                    ${category.icon} ${category.name_de}
                </button>
            `;
        });

        container.innerHTML = html;

        // Add event listeners
        container.querySelectorAll('.category-tab').forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Update active state
                container.querySelectorAll('.category-tab').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');

                // Filter products
                this.currentCategory = e.target.dataset.category;
                this.renderProducts();
            });
        });
    }

    renderProducts() {
        const container = document.getElementById('productsGrid');
        if (!container) return;

        let filteredProducts = this.products;
        if (this.currentCategory !== 'all') {
            filteredProducts = this.products.filter(p => p.category_id == this.currentCategory);
        }

        if (filteredProducts.length === 0) {
            container.innerHTML = '<div class="loading">Keine Produkte gefunden</div>';
            return;
        }

        let html = '';
        filteredProducts.forEach(product => {
            const emoji = this.getProductEmoji(product.name_de);
            html += `
                <div class="product-card" data-product-id="${product.id}">
                    <div class="product-emoji">${emoji}</div>
                    <div class="product-name">${product.name_de}</div>
                    <div class="product-price">${this.formatPrice(product.price)}</div>
                </div>
            `;
        });

        container.innerHTML = html;

        // Add click handlers
        container.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const productId = parseInt(e.currentTarget.dataset.productId);
                this.addProductToCart(productId);
            });
        });
    }

    getProductEmoji(productName) {
        const emojiMap = {
            'burger': '🍔',
            'cheese': '🧀',
            'chicken': '🍗',
            'beef': '🥩',
            'döner': '🌯',
            'doner': '🌯',
            'kebab': '🌯',
            'pizza': '🍕',
            'pommes': '🍟',
            'fries': '🍟',
            'cola': '🥤',
            'wasser': '💧',
            'water': '💧',
            'salat': '🥗',
            'salad': '🥗',
            'nuggets': '🍗',
            'wrap': '🌯',
            'sandwich': '🥪'
        };

        const name = productName.toLowerCase();
        for (let [key, emoji] of Object.entries(emojiMap)) {
            if (name.includes(key)) {
                return emoji;
            }
        }
        return '🍽️';
    }

    // === Cart Management ===

    addProductToCart(productId) {
        const product = this.products.find(p => p.id === productId);
        if (!product) return;

        // Check if product has extras
        if (product.extras && product.extras.length > 0) {
            // Show modal for extras selection
            this.showProductModal(product);
        } else {
            // Add directly
            const existingItem = this.cart.find(item => item.id === productId && !item.extras);

            if (existingItem) {
                existingItem.quantity++;
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name_de,
                    price: parseFloat(product.price),
                    quantity: 1,
                    tax_rate: product.tax_rate || 19,
                    extras: []
                });
            }

            this.updateCartDisplay();
            this.showToast(`${product.name_de} hinzugefügt`, 'success');
        }
    }

    showProductModal(product) {
        // TODO: Implement product modal for extras selection
        // For now, just add without extras
        this.cart.push({
            id: product.id,
            name: product.name_de,
            price: parseFloat(product.price),
            quantity: 1,
            tax_rate: product.tax_rate || 19,
            extras: []
        });

        this.updateCartDisplay();
        this.showToast(`${product.name_de} hinzugefügt`, 'success');
    }

    updateCartDisplay() {
        const container = document.getElementById('cartItems');
        const clearBtn = document.getElementById('clearCartBtn');
        const payWithCashBtn = document.getElementById('payWithCashBtn');
        const payWithCardBtn = document.getElementById('payWithCardBtn');

        if (!container) return;

        if (this.cart.length === 0) {
            container.innerHTML = `
                <div class="empty-cart">
                    <div class="empty-icon">🛒</div>
                    <p>Warenkorb ist leer</p>
                    <small>Wähle Produkte aus</small>
                </div>
            `;
            if (clearBtn) clearBtn.style.display = 'none';
            if (payWithCashBtn) payWithCashBtn.disabled = true;
            if (payWithCardBtn) payWithCardBtn.disabled = true;
        } else {
            let html = '';
            this.cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                html += `
                    <div class="cart-item">
                        <div class="cart-item-header">
                            <div class="cart-item-name">${item.name}</div>
                            <button class="cart-item-remove" data-index="${index}">×</button>
                        </div>
                        ${item.extras && item.extras.length > 0 ? `
                            <div class="cart-item-extras">
                                ${item.extras.map(e => `+ ${e.name}`).join(', ')}
                            </div>
                        ` : ''}
                        <div class="cart-item-footer">
                            <div class="quantity-controls">
                                <button class="quantity-btn" data-index="${index}" data-action="decrease">−</button>
                                <span class="quantity-display">${item.quantity}</span>
                                <button class="quantity-btn" data-index="${index}" data-action="increase">+</button>
                            </div>
                            <div class="cart-item-price">${this.formatPrice(itemTotal)}</div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;

            // Add event listeners
            container.querySelectorAll('.cart-item-remove').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const index = parseInt(e.target.dataset.index);
                    this.cart.splice(index, 1);
                    this.updateCartDisplay();
                });
            });

            container.querySelectorAll('.quantity-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const index = parseInt(e.target.dataset.index);
                    const action = e.target.dataset.action;

                    if (action === 'increase') {
                        this.cart[index].quantity++;
                    } else if (action === 'decrease') {
                        this.cart[index].quantity--;
                        if (this.cart[index].quantity <= 0) {
                            this.cart.splice(index, 1);
                        }
                    }

                    this.updateCartDisplay();
                });
            });

            if (clearBtn) clearBtn.style.display = 'block';
            if (payWithCashBtn) payWithCashBtn.disabled = false;
            if (payWithCardBtn) payWithCardBtn.disabled = false;
        }

        // Update summary
        this.updateCartSummary();
    }

    updateCartSummary() {
        const subtotal = this.getCartSubtotal();
        const tax = this.getCartTax();
        const total = subtotal + tax;

        document.getElementById('subtotalAmount').textContent = this.formatPrice(subtotal);
        document.getElementById('taxAmount').textContent = this.formatPrice(tax);
        document.getElementById('totalAmount').textContent = this.formatPrice(total);
    }

    getCartSubtotal() {
        return this.cart.reduce((sum, item) => {
            const itemPrice = item.price * item.quantity;
            const extrasPrice = item.extras ? item.extras.reduce((s, e) => s + e.price, 0) * item.quantity : 0;
            return sum + itemPrice + extrasPrice;
        }, 0);
    }

    getCartTax() {
        return this.cart.reduce((sum, item) => {
            const itemPrice = item.price * item.quantity;
            const extrasPrice = item.extras ? item.extras.reduce((s, e) => s + e.price, 0) * item.quantity : 0;
            const itemTotal = itemPrice + extrasPrice;
            const taxRate = item.tax_rate || 19;
            return sum + (itemTotal * taxRate / (100 + taxRate));
        }, 0);
    }

    getCartTotal() {
        return this.getCartSubtotal();
    }

    clearCart() {
        if (this.cart.length === 0) return;

        if (confirm('Warenkorb wirklich leeren?')) {
            this.cart = [];
            this.updateCartDisplay();
        }
    }

    // === Payment ===

    openCashPayment() {
        const total = this.getCartTotal();
        document.getElementById('amountToPay').textContent = this.formatPrice(total);
        document.getElementById('cashGiven').value = '';
        document.getElementById('changeAmount').textContent = this.formatPrice(0);
        document.getElementById('completeCashPayment').disabled = true;

        document.getElementById('cashPaymentModal').classList.add('active');

        // Focus on input
        setTimeout(() => {
            document.getElementById('cashGiven').focus();
        }, 300);
    }

    openCardPayment() {
        const total = this.getCartTotal();
        document.getElementById('cardAmountToPay').textContent = this.formatPrice(total);

        document.getElementById('cardPaymentModal').classList.add('active');
    }

    handleQuickCash(e) {
        const btn = e.target;
        const cashGivenInput = document.getElementById('cashGiven');

        if (btn.classList.contains('exact-amount')) {
            const total = this.getCartTotal();
            cashGivenInput.value = total.toFixed(2);
        } else {
            const amount = parseFloat(btn.dataset.amount);
            cashGivenInput.value = amount.toFixed(2);
        }

        this.calculateChange();
    }

    calculateChange() {
        const total = this.getCartTotal();
        const given = parseFloat(document.getElementById('cashGiven').value) || 0;
        const change = given - total;

        document.getElementById('changeAmount').textContent = this.formatPrice(Math.max(0, change));

        // Enable/disable complete button
        const completeBtn = document.getElementById('completeCashPayment');
        completeBtn.disabled = given < total;
    }

    async completeCashPayment() {
        const cashGiven = parseFloat(document.getElementById('cashGiven').value);
        const total = this.getCartTotal();
        const change = cashGiven - total;

        try {
            const orderData = {
                items: this.cart.map(item => ({
                    id: item.id,
                    name_de: item.name,
                    price: item.price,
                    quantity: item.quantity,
                    extras: item.extras || []
                })),
                totalAmount: total,
                paymentMethod: 'CASH',
                cashAmount: cashGiven,
                changeAmount: change,
                cashierName: this.cashierName
            };

            const response = await fetch('/api/kasse/create-cash-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });

            const data = await response.json();

            if (data.success) {
                // Close payment modal
                document.getElementById('cashPaymentModal').classList.remove('active');

                // Show receipt
                this.showReceipt(data.orderId, data.orderNumber);

                // Clear cart
                this.cart = [];
                this.updateCartDisplay();

                this.showToast('Zahlung erfolgreich!', 'success');
            } else {
                throw new Error(data.message || 'Zahlung fehlgeschlagen');
            }
        } catch (error) {
            console.error('Payment error:', error);
            this.showToast('Fehler bei der Zahlung', 'error');
        }
    }

    async completeCardPayment() {
        const total = this.getCartTotal();

        try {
            const orderData = {
                items: this.cart.map(item => ({
                    id: item.id,
                    name_de: item.name,
                    price: item.price,
                    quantity: item.quantity,
                    extras: item.extras || []
                })),
                totalAmount: total,
                paymentMethod: 'CARD',
                cashAmount: 0,
                changeAmount: 0,
                cashierName: this.cashierName
            };

            const response = await fetch('/api/kasse/create-cash-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });

            const data = await response.json();

            if (data.success) {
                // Close payment modal
                document.getElementById('cardPaymentModal').classList.remove('active');

                // Show receipt
                this.showReceipt(data.orderId, data.orderNumber);

                // Clear cart
                this.cart = [];
                this.updateCartDisplay();

                this.showToast('Zahlung erfolgreich!', 'success');
            } else {
                throw new Error(data.message || 'Zahlung fehlgeschlagen');
            }
        } catch (error) {
            console.error('Payment error:', error);
            this.showToast('Fehler bei der Zahlung', 'error');
        }
    }

    showReceipt(orderId, orderNumber) {
        document.getElementById('orderNumber').textContent = orderNumber;

        const iframe = document.getElementById('receiptFrame');
        iframe.src = `/api/kasse/print-receipt.php?order_id=${orderId}`;

        document.getElementById('receiptModal').classList.add('active');
    }

    // === Utility Functions ===

    formatPrice(amount) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }

    showToast(message, type = 'info') {
        const toast = document.getElementById('toast');
        if (!toast) return;

        toast.textContent = message;
        toast.className = `toast ${type}`;
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    startClock() {
        const updateClock = () => {
            const now = new Date();
            const timeString = now.toLocaleTimeString('de-DE', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });

            const clockDisplay = document.getElementById('clockDisplay');
            if (clockDisplay) {
                clockDisplay.textContent = timeString;
            }
        };

        updateClock();
        setInterval(updateClock, 1000);
    }

    setupOnlineStatusCheck() {
        const updateStatus = () => {
            this.isOnline = navigator.onLine;
            const statusBadge = document.getElementById('onlineStatus');
            const connectionStatus = document.getElementById('connectionStatus');

            if (statusBadge) {
                if (this.isOnline) {
                    statusBadge.className = 'status-badge online';
                    statusBadge.innerHTML = '<span class="pulse"></span> Online';
                } else {
                    statusBadge.className = 'status-badge offline';
                    statusBadge.innerHTML = '<span class="pulse"></span> Offline';
                }
            }

            if (connectionStatus) {
                if (this.isOnline) {
                    connectionStatus.innerHTML = '<span class="dot" style="background: #4caf50;"></span> Online';
                } else {
                    connectionStatus.innerHTML = '<span class="dot" style="background: #f44336;"></span> Offline';
                }
            }
        };

        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);
        updateStatus();
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.kasseSystem = new KasseSystem();
});
