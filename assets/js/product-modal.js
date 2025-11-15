// Product Customization Modal
class ProductModal {
    constructor() {
        this.extras = { salatbar: [], toppings: [] };
        this.selectedExtras = [];
        this.quantity = 1;
        this.currentProduct = null;
        this.currentLang = document.documentElement.lang || 'de';
        
        this.init();
    }

    async init() {
        // Load extras from server
        await this.loadExtras();
        
        // Create modal HTML
        this.createModal();
        
        // Attach event listeners
        this.attachEventListeners();
    }

    async loadExtras() {
        try {
            const response = await fetch('/api/get-extras.php');
            const data = await response.json();
            
            if (data.success) {
                this.extras = data.data;
            }
        } catch (error) {
            console.error('Failed to load extras:', error);
        }
    }

    createModal() {
        const modalHTML = `
            <div id="productModal" class="product-modal-overlay">
                <div class="product-modal">
                    <div class="product-modal-header">
                        <button class="product-modal-close" onclick="productModal.close()">&times;</button>
                        <img id="modalProductImage" class="product-modal-image" src="" alt="">
                        <h2 id="modalProductName" class="product-modal-title"></h2>
                        <div id="modalProductPrice" class="product-modal-price"></div>
                        <p id="modalProductDescription" class="product-modal-description"></p>
                    </div>

                    <div class="product-modal-body">
                        <!-- Salatbar Section -->
                        <div class="extras-section" id="salatbarSection">
                            <h3 class="extras-section-title">
                                ${this.getTranslation('salatbar_title')}
                            </h3>
                            <div id="salatbarExtras" class="extras-grid"></div>
                        </div>

                        <!-- Toppings Section -->
                        <div class="extras-section" id="toppingsSection">
                            <h3 class="extras-section-title">
                                ${this.getTranslation('toppings_title')}
                            </h3>
                            <div id="toppingsExtras" class="extras-grid"></div>
                        </div>
                    </div>

                    <div class="product-modal-footer">
                        <div class="quantity-selector">
                            <button class="quantity-btn" onclick="productModal.decreaseQuantity()">−</button>
                            <span class="quantity-display" id="modalQuantity">1</span>
                            <button class="quantity-btn" onclick="productModal.increaseQuantity()">+</button>
                        </div>
                        <button class="add-to-cart-final" onclick="productModal.addToCart()">
                            <span>${this.getTranslation('add_to_cart')}</span>
                            <span class="add-to-cart-final-total" id="modalTotalPrice"></span>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    getTranslation(key) {
        const translations = {
            salatbar_title: {
                de: 'Aus der Salatbar',
                en: 'From the Salad Bar',
                tr: 'Salata Barından'
            },
            toppings_title: {
                de: 'Zusätzliche Toppings',
                en: 'Additional Toppings',
                tr: 'Ekstra Soslar'
            },
            add_to_cart: {
                de: 'In den Warenkorb',
                en: 'Add to Cart',
                tr: 'Sepete Ekle'
            }
        };

        return translations[key]?.[this.currentLang] || translations[key]?.de || key;
    }

    attachEventListeners() {
        // Close on overlay click
        document.getElementById('productModal').addEventListener('click', (e) => {
            if (e.target.id === 'productModal') {
                this.close();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.close();
            }
        });
    }

    open(product) {
        this.currentProduct = product;
        this.selectedExtras = [];
        this.quantity = 1;

        // Set product info
        document.getElementById('modalProductImage').src = product.image;
        document.getElementById('modalProductName').textContent = product.name;
        document.getElementById('modalProductPrice').textContent = this.formatPrice(product.price);
        document.getElementById('modalProductDescription').textContent = product.description || '';
        document.getElementById('modalQuantity').textContent = '1';

        // Render extras
        this.renderExtras('salatbar', 'salatbarExtras');
        this.renderExtras('toppings', 'toppingsExtras');

        // Update total
        this.updateTotal();

        // Show modal
        document.getElementById('productModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    close() {
        document.getElementById('productModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    renderExtras(category, containerId) {
        const container = document.getElementById(containerId);
        const extras = this.extras[category] || [];

        if (extras.length === 0) {
            container.innerHTML = '<p class="empty-extras">Keine Extras verfügbar</p>';
            return;
        }

        const langKey = `name_${this.currentLang}`;
        
        container.innerHTML = extras.map(extra => `
            <label class="extra-item" data-extra-id="${extra.id}">
                <input type="checkbox" 
                       class="extra-checkbox" 
                       value="${extra.id}"
                       data-price="${extra.price}"
                       data-name="${extra[langKey]}"
                       onchange="productModal.toggleExtra(this)">
                <div class="extra-info">
                    <div class="extra-name">${extra[langKey]}</div>
                </div>
                <div class="extra-price">${this.formatPrice(extra.price)}</div>
            </label>
        `).join('');
    }

    toggleExtra(checkbox) {
        const extraItem = checkbox.closest('.extra-item');
        
        if (checkbox.checked) {
            extraItem.classList.add('selected');
            this.selectedExtras.push({
                id: parseInt(checkbox.value),
                name: checkbox.dataset.name,
                price: parseFloat(checkbox.dataset.price)
            });
        } else {
            extraItem.classList.remove('selected');
            this.selectedExtras = this.selectedExtras.filter(
                e => e.id !== parseInt(checkbox.value)
            );
        }

        this.updateTotal();
    }

    increaseQuantity() {
        this.quantity++;
        document.getElementById('modalQuantity').textContent = this.quantity;
        this.updateTotal();
    }

    decreaseQuantity() {
        if (this.quantity > 1) {
            this.quantity--;
            document.getElementById('modalQuantity').textContent = this.quantity;
            this.updateTotal();
        }
    }

    updateTotal() {
        if (!this.currentProduct) return;

        const basePrice = parseFloat(this.currentProduct.price);
        const extrasPrice = this.selectedExtras.reduce((sum, extra) => sum + extra.price, 0);
        const total = (basePrice + extrasPrice) * this.quantity;

        document.getElementById('modalTotalPrice').textContent = this.formatPrice(total);
    }

    addToCart() {
        if (!this.currentProduct) return;

        // Prepare cart item
        const cartItem = {
            id: this.currentProduct.id,
            name: this.currentProduct.name,
            price: parseFloat(this.currentProduct.price),
            image: this.currentProduct.image,
            quantity: this.quantity,
            extras: this.selectedExtras
        };

        // Add to cart using existing cart system
        if (window.cart) {
            window.cart.addItemWithExtras(cartItem);
        }

        // Close modal
        this.close();
    }

    formatPrice(price) {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR'
        }).format(price);
    }
}

// Initialize modal when DOM is ready
let productModal;
document.addEventListener('DOMContentLoaded', () => {
    productModal = new ProductModal();
});

