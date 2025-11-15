# Q-Bab Burger Website - POS & Kasse System

Modern burger restaurant website with integrated Point of Sale (POS) system and Fiskaly TSE integration for German KassenSichV compliance.

## 🍔 Features

### Customer-Facing
- **Online Menu & Ordering**: Browse menu, customize orders, add to cart
- **Location Finder**: Multiple restaurant locations
- **User Accounts**: Profile management, order history
- **Contact & FAQ**: Customer support features
- **Multi-language Support**: German/English

### Admin/POS Features
- **Digital Cash Register (Kasse)**: Complete POS system
- **Fiskaly TSE Integration**: German fiscal compliance (KassenSichV)
- **Receipt Generation**: TSE-signed receipts
- **Order Management**: Track and process orders
- **Payment Processing**: Stripe integration

## 🔧 Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Payment**: Stripe API
- **Fiscal Compliance**: Fiskaly TSE API
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: Apache/Nginx compatible

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx web server
- SSL certificate (for production)
- Fiskaly account (for TSE)
- Stripe account (for payments)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/YOUR_USERNAME/qbab-burger-website.git
cd qbab-burger-website
```

### 2. Configure Environment
Copy and configure the environment file:
```bash
cp .env.example .env
```

Edit `.env` with your credentials:
```env
# Database
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_username
DB_PASS=your_password

# Fiskaly TSE
FISKALY_API_KEY=your_api_key
FISKALY_API_SECRET=your_api_secret
FISKALY_TSS_ID=your_tss_id
FISKALY_CLIENT_ID=your_client_id

# Optional: Middleware (if using Middleware TSE)
FISKALY_USE_MIDDLEWARE=false
FISKALY_MIDDLEWARE_URL=http://localhost:8000

# Stripe
STRIPE_PUBLIC_KEY=your_stripe_public_key
STRIPE_SECRET_KEY=your_stripe_secret_key
```

### 3. Database Setup
Import the database schema:
```bash
mysql -u your_username -p your_database < database/schema.sql
```

### 4. Set Permissions
```bash
chmod 755 -R .
chmod 644 .env
```

### 5. Configure Web Server

**Apache (.htaccess included)**
- Ensure mod_rewrite is enabled
- Point DocumentRoot to project directory

**Nginx**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/qbab-burger-website;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🧪 Testing Fiskaly TSE Integration

### Quick Test
1. **Debug Test**: `https://yourdomain.com/api/kasse/fiskaly-debug.php`
   - Checks authentication and TSS status
   
2. **Initialize TSS**: `https://yourdomain.com/api/kasse/initialize-tss.php`
   - Initializes TSS if needed

3. **Complete Flow Test**: `https://yourdomain.com/api/kasse/test-complete-flow.php`
   - Tests full transaction signing flow

### Expected Results
✅ Authentication successful  
✅ TSS state: INITIALIZED  
✅ Transaction signing working  
✅ Receipt generation with TSE signature  

## 📚 Documentation

- [**Fiskaly Setup Guide**](FISKALY_SETUP.md) - Complete TSE integration guide
- [**Quick Start TSE**](QUICK_START_TSE.md) - Fast setup for TSE
- [**Middleware Setup**](FISKALY_MIDDLEWARE_SETUP.md) - Middleware TSE installation
- [**Middleware Quick Start**](MIDDLEWARE_QUICK_START.md) - Quick middleware guide
- [**Kasse Deployment**](KASSE_DEPLOYMENT_GUIDE.md) - POS system deployment
- [**Authentication Fix**](FISKALY_AUTH_FIX.md) - JWT authentication details

## 🔐 Security Notes

### Production Checklist
- [ ] Use HTTPS (SSL certificate installed)
- [ ] Secure `.env` file (chmod 644, not web-accessible)
- [ ] Use production Fiskaly credentials (not test keys)
- [ ] Enable PHP security headers
- [ ] Implement rate limiting
- [ ] Regular security updates
- [ ] Database credentials rotation
- [ ] Backup sensitive TSS data

### Sensitive Files (Never Commit)
- `.env` - Environment variables
- `*_adminpuk.json` - TSS admin PUK
- `database/config.php` - Database credentials
- Any files with API keys/secrets

## 🐛 Troubleshooting

### Common Issues

**1. Fiskaly Authentication Error (HTTP 401)**
- Verify API credentials in `.env`
- Check if using Bearer token (not Basic Auth)
- See [FISKALY_AUTH_FIX.md](FISKALY_AUTH_FIX.md)

**2. E_USE_MIDDLEWARE Error (HTTP 432)**
- Your TSS is Middleware TSE type
- Install middleware or request Cloud TSE conversion
- See [MIDDLEWARE_QUICK_START.md](MIDDLEWARE_QUICK_START.md)

**3. TSS State Issues**
- Check current state: `/api/kasse/check-tss-status.php`
- Initialize if needed: `/api/kasse/initialize-tss.php`

**4. Database Connection Error**
- Verify database credentials in `.env`
- Check if database exists
- Ensure MySQL/MariaDB is running

## 📞 Support

### Fiskaly TSE Issues
- **Fiskaly Dashboard**: https://dashboard.fiskaly.com
- **Developer Docs**: https://developer.fiskaly.com
- **Support Email**: support@fiskaly.com

### Stripe Payment Issues
- **Stripe Dashboard**: https://dashboard.stripe.com
- **Documentation**: https://stripe.com/docs

## 📄 License

[Specify your license here]

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 🔄 Changelog

### Latest Updates
- ✅ Fixed Fiskaly authentication (Basic Auth → Bearer JWT)
- ✅ Added middleware support for Middleware TSE
- ✅ Improved error handling and logging
- ✅ Added comprehensive test endpoints
- ✅ Updated documentation

## 👥 Authors

Q-Bab Development Team

---

**For detailed setup instructions, see the documentation files in the root directory.**
