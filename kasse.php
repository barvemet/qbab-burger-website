<?php
/**
 * Q-Bab Burger - Kassa/POS System
 * Dedicated Point of Sale interface optimized for touchscreen
 *
 * @author Q-Bab Development Team
 */

session_start();

// Check if cashier is logged in
$cashierLoggedIn = isset($_SESSION['cashier_name']) && isset($_SESSION['kasse_session_id']);
$cashierName = $_SESSION['cashier_name'] ?? '';
$sessionId = $_SESSION['kasse_session_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Q-Bab Kassa System</title>
    <link rel="stylesheet" href="assets/css/kasse.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ff6b35">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>
    <!-- Login Screen -->
    <div id="loginScreen" class="screen" style="display: <?php echo $cashierLoggedIn ? 'none' : 'flex'; ?>">
        <div class="login-container">
            <div class="logo">
                <h1>🍔 Q-BAB</h1>
                <p>Kassa System</p>
            </div>
            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="cashierName">Kassierer Name</label>
                    <input type="text" id="cashierName" name="cashierName" required autofocus>
                </div>
                <div class="form-group">
                    <label for="openingCash">Anfangsbestand (€)</label>
                    <input type="number" id="openingCash" name="openingCash" step="0.01" value="100.00" required>
                </div>
                <button type="submit" class="btn btn-primary btn-large">Schicht Starten</button>
            </form>
            <div class="system-status">
                <span id="connectionStatus" class="status-indicator">
                    <span class="dot"></span> Verbindung prüfen...
                </span>
            </div>
        </div>
    </div>

    <!-- Main POS Interface -->
    <div id="mainScreen" class="screen" style="display: <?php echo $cashierLoggedIn ? 'flex' : 'none'; ?>">
        <!-- Header -->
        <header class="pos-header">
            <div class="header-left">
                <h1>🍔 Q-BAB Kassa</h1>
                <span id="sessionInfo" class="session-info">
                    Kassierer: <strong><?php echo htmlspecialchars($cashierName); ?></strong>
                </span>
            </div>
            <div class="header-right">
                <span id="clockDisplay" class="clock"></span>
                <span id="onlineStatus" class="status-badge online">
                    <span class="pulse"></span> Online
                </span>
                <button id="endSessionBtn" class="btn btn-danger">Schicht Beenden</button>
            </div>
        </header>

        <!-- Main Content -->
        <div class="pos-main">
            <!-- Left Panel - Products -->
            <div class="products-panel">
                <!-- Category Tabs -->
                <div class="category-tabs" id="categoryTabs">
                    <button class="category-tab active" data-category="all">
                        Alle
                    </button>
                </div>

                <!-- Product Grid -->
                <div class="products-grid" id="productsGrid">
                    <div class="loading">Produkte laden...</div>
                </div>
            </div>

            <!-- Right Panel - Cart & Checkout -->
            <div class="cart-panel">
                <!-- Cart Header -->
                <div class="cart-header">
                    <h2>Bestellung</h2>
                    <button id="clearCartBtn" class="btn btn-sm btn-danger" style="display: none;">
                        Löschen
                    </button>
                </div>

                <!-- Cart Items -->
                <div class="cart-items" id="cartItems">
                    <div class="empty-cart">
                        <div class="empty-icon">🛒</div>
                        <p>Warenkorb ist leer</p>
                        <small>Wähle Produkte aus</small>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Zwischensumme:</span>
                        <span id="subtotalAmount">0,00 €</span>
                    </div>
                    <div class="summary-row">
                        <span>MwSt. (19%):</span>
                        <span id="taxAmount">0,00 €</span>
                    </div>
                    <div class="summary-row total">
                        <span>Gesamt:</span>
                        <span id="totalAmount">0,00 €</span>
                    </div>
                </div>

                <!-- Payment Buttons -->
                <div class="payment-buttons">
                    <button id="payWithCashBtn" class="btn btn-success btn-large" disabled>
                        💵 Bargeld
                    </button>
                    <button id="payWithCardBtn" class="btn btn-primary btn-large" disabled>
                        💳 Karte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal - Cash -->
    <div id="cashPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>💵 Barzahlung</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="payment-display">
                    <div class="amount-to-pay">
                        <label>Zu zahlen:</label>
                        <div class="amount" id="amountToPay">0,00 €</div>
                    </div>
                    <div class="amount-given">
                        <label>Gegeben:</label>
                        <input type="number" id="cashGiven" class="cash-input" step="0.01" min="0">
                    </div>
                    <div class="amount-change">
                        <label>Rückgeld:</label>
                        <div class="amount" id="changeAmount">0,00 €</div>
                    </div>
                </div>

                <!-- Quick Cash Buttons -->
                <div class="quick-cash-buttons">
                    <button class="quick-cash" data-amount="5">5 €</button>
                    <button class="quick-cash" data-amount="10">10 €</button>
                    <button class="quick-cash" data-amount="20">20 €</button>
                    <button class="quick-cash" data-amount="50">50 €</button>
                    <button class="quick-cash exact-amount">Genau</button>
                </div>
            </div>
            <div class="modal-footer">
                <button id="cancelCashPayment" class="btn btn-secondary">Abbrechen</button>
                <button id="completeCashPayment" class="btn btn-success btn-large" disabled>
                    Zahlung Abschließen
                </button>
            </div>
        </div>
    </div>

    <!-- Payment Modal - Card -->
    <div id="cardPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>💳 Kartenzahlung</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="payment-display">
                    <div class="amount-to-pay">
                        <label>Zu zahlen:</label>
                        <div class="amount" id="cardAmountToPay">0,00 €</div>
                    </div>
                    <div class="card-instruction">
                        <div class="card-icon">💳</div>
                        <p>Bitte Karte einstecken oder auflegen</p>
                        <small>Warte auf Zahlungsbestätigung...</small>
                    </div>
                    <div class="processing-animation">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="cancelCardPayment" class="btn btn-secondary">Abbrechen</button>
                <button id="completeCardPayment" class="btn btn-success btn-large">
                    Zahlung Bestätigen
                </button>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>✅ Bestellung Abgeschlossen</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="success-message">
                    <div class="success-icon">✓</div>
                    <h3>Zahlung Erfolgreich</h3>
                    <p>Bestellnummer: <strong id="orderNumber"></strong></p>
                </div>
                <iframe id="receiptFrame" style="width: 100%; height: 500px; border: 1px solid #ddd;"></iframe>
            </div>
            <div class="modal-footer">
                <button id="printReceiptBtn" class="btn btn-primary">🖨️ Beleg Drucken</button>
                <button id="newOrderBtn" class="btn btn-success">Neue Bestellung</button>
            </div>
        </div>
    </div>

    <!-- Product Modal (for extras selection) -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="productModalTitle">Produkt</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="productModalBody">
                    <!-- Dynamic content -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-close btn btn-secondary">Abbrechen</button>
                <button id="addToCartBtn" class="btn btn-success">Hinzufügen</button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast" class="toast"></div>

    <!-- Scripts -->
    <script src="assets/js/kasse.js"></script>
</body>
</html>
