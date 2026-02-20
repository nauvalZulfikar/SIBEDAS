# Sibedas PBG Web - Documentation

Dokumentasi lengkap untuk setup dan penggunaan aplikasi Sibedas PBG Web.

## 📋 Table of Contents

1. [Quick Start](#-quick-start)
2. [Architecture](#-architecture)
3. [Environment Setup](#-environment-setup)
4. [Production Deployment](#-production-deployment)
5. [SSL Configuration](#-ssl-configuration)
6. [Monitoring](#-monitoring)
7. [Troubleshooting](#-troubleshooting)

## 🚀 Quick Start

### Prerequisites

-   Docker & Docker Compose
-   Domain name (untuk production)
-   Port 80 dan 443 terbuka (untuk Let's Encrypt)

### Local Development

```bash
# Clone repository
git clone <repository-url>
cd sibedas-pbg-web

# Setup local environment
./scripts/setup-local.sh
```

### Production Deployment

```bash
# Copy environment file
cp env.production.example .env

# Edit environment variables
nano .env

# Deploy dengan reverse proxy dan SSL
./scripts/setup-reverse-proxy.sh setup
```

## 🏗️ Architecture

### Local Development

```
Browser → Port 8000 → Nginx → PHP-FPM → MariaDB
```

### Production dengan Reverse Proxy

```
Internet → Reverse Proxy (80/443) → Internal Nginx → PHP-FPM → MariaDB
```

### Components

-   **Reverse Proxy Nginx**: Entry point, SSL termination, routing
-   **Internal Nginx**: Serves Sibedas application
-   **Application Container**: PHP-FPM with Supervisor (queue & scheduler)
-   **Database Container**: MariaDB with backup import

## ⚙️ Environment Setup

### Required Variables

```bash
# Domain & SSL
DOMAIN=sibedas.yourdomain.com
EMAIL=admin@yourdomain.com
SSL_TYPE=self-signed  # atau letsencrypt

# Database
DB_PASSWORD=your_secure_password
MYSQL_ROOT_PASSWORD=your_root_password

# Laravel
APP_KEY=base64:your_app_key_here
APP_URL=https://sibedas.yourdomain.com
```

### Generate App Key

```bash
docker-compose exec app php artisan key:generate
```

## 🚀 Production Deployment

### Step-by-Step Production Deployment

#### 1. **Server Preparation**

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker & Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Logout and login again for group changes
exit
# SSH back to server
```

#### 2. **Clone Repository**

```bash
# Clone project
git clone <repository-url>
cd sibedas-pbg-web

# Set proper permissions
chmod +x scripts/*.sh
```

#### 3. **Environment Configuration**

```bash
# Copy environment template
cp env.production.example .env

# Edit environment variables
nano .env
```

**Required Environment Variables:**

```bash
# Domain & SSL
DOMAIN=sibedas.yourdomain.com
EMAIL=admin@yourdomain.com
SSL_TYPE=letsencrypt  # atau self-signed untuk testing

# Database
DB_DATABASE=sibedas
DB_USERNAME=sibedas_user
DB_PASSWORD=your_secure_database_password
MYSQL_ROOT_PASSWORD=your_secure_root_password

# Laravel
APP_NAME="Sibedas PBG Web"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your_app_key_here
APP_URL=https://sibedas.yourdomain.com
VITE_APP_URL=https://sibedas.yourdomain.com

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="Sibedas PBG Web"

# Google Sheets API
SPREAD_SHEET_ID=your_google_sheets_id_here
```

#### 4. **Generate Application Key**

```bash
# Generate Laravel app key
php artisan key:generate --show
# Copy the generated key to .env file
```

#### 5. **Deploy Application**

```bash
# Option A: Full deployment with SSL (Recommended)
./scripts/setup-reverse-proxy.sh setup

# Option B: Deploy without SSL first
./scripts/deploy-production.sh deploy
```

#### 6. **SSL Setup (if not done in step 5)**

```bash
# For Let's Encrypt (Production)
DOMAIN=yourdomain.com EMAIL=admin@yourdomain.com ./scripts/setup-ssl.sh letsencrypt

# For Self-Signed (Testing)
./scripts/setup-ssl.sh self-signed
```

#### 7. **Verify Deployment**

```bash
# Check container status
docker-compose ps

# Check application health
curl -f http://localhost/health-check

# Check SSL certificate
./scripts/setup-ssl.sh check

# View logs
docker-compose logs nginx-proxy
docker-compose logs app
```

### Scripts yang Diperlukan

#### **Essential Scripts (Wajib)**

-   `scripts/setup-reverse-proxy.sh` - Setup lengkap reverse proxy dan SSL
-   `scripts/deploy-production.sh` - Deployment production
-   `scripts/setup-ssl.sh` - Setup SSL certificates

#### **Optional Scripts**

-   `scripts/setup-local.sh` - Setup local development
-   `scripts/import-sibedas-database.sh` - Manual database import (otomatis via docker-compose)

#### **Scripts yang Tidak Diperlukan**

-   `scripts/build-and-zip.sh` - Tidak diperlukan karena menggunakan Docker build

### Deployment Commands Summary

```bash
# 1. Setup environment
cp env.production.example .env
nano .env

# 2. Deploy with SSL (Recommended)
./scripts/setup-reverse-proxy.sh setup

# 3. Or deploy step by step
./scripts/deploy-production.sh deploy
./scripts/setup-ssl.sh letsencrypt

# 4. Check status
./scripts/setup-reverse-proxy.sh status
```

## 🔒 SSL Configuration

### Self-Signed Certificate

```bash
SSL_TYPE=self-signed ./scripts/setup-reverse-proxy.sh setup
```

### Let's Encrypt Certificate

```bash
DOMAIN=myapp.com EMAIL=admin@myapp.com SSL_TYPE=letsencrypt ./scripts/setup-reverse-proxy.sh setup
```

### SSL Management

```bash
# Check certificate
./scripts/setup-ssl.sh check

# Renew certificate
./scripts/setup-ssl.sh renew
```

## 📊 Monitoring

### Container Status

```bash
# Check all containers
docker-compose ps

# Check specific service
docker-compose ps app
```

### Logs

```bash
# Application logs
docker-compose logs app

# Reverse proxy logs
docker-compose logs nginx-proxy

# Database logs
docker-compose logs db

# Follow logs
docker-compose logs -f nginx-proxy
```

### Health Checks

```bash
# Application health
curl -f http://localhost/health-check

# SSL certificate
./scripts/setup-ssl.sh check
```

## 🛠️ Troubleshooting

### SSL Issues

```bash
# Check certificate files
docker exec sibedas_nginx_proxy ls -la /etc/nginx/ssl/

# Test SSL connection
openssl s_client -connect yourdomain.com:443

# Check nginx config
docker exec sibedas_nginx_proxy nginx -t
```

### Container Issues

```bash
# Restart services
docker-compose restart

# Check network
docker network ls

# Check volumes
docker volume ls
```

### Database Issues

```bash
# Import database manually
./scripts/import-sibedas-database.sh

# Check database connection
docker exec sibedas_app php artisan db:monitor
```

### Performance Issues

```bash
# Check resource usage
docker stats

# Check nginx access logs
docker exec sibedas_nginx_proxy tail -f /var/log/nginx/sibedas_access.log
```

## 🔧 Maintenance

### Backup

```bash
# Database backup
docker exec sibedas_db mysqldump -u root -p sibedas > backup.sql

# Volume backup
docker run --rm -v sibedas_app_storage:/data -v $(pwd):/backup alpine tar czf /backup/storage.tar.gz -C /data .
```

### Update Application

```bash
# Pull latest changes
git pull

# Rebuild and restart
docker-compose up -d --build
```

### SSL Certificate Renewal

```bash
# Manual renewal
./scripts/setup-ssl.sh renew

# Automatic renewal (cron)
0 12 * * * /path/to/sibedas-pbg-web/scripts/setup-ssl.sh renew
```

## 📁 Project Structure

```
sibedas-pbg-web/
├── docker/                    # Docker configurations
│   ├── nginx/                # Nginx configs
│   ├── mysql/                # MySQL configs
│   └── supervisor/           # Supervisor configs
├── scripts/                  # Deployment scripts
│   ├── setup-local.sh        # Local development
│   ├── setup-reverse-proxy.sh # Reverse proxy setup
│   ├── deploy-production.sh  # Production deployment
│   ├── setup-ssl.sh          # SSL setup
│   └── import-sibedas-database.sh # Database import
├── docs/                     # Documentation
├── docker-compose.yml        # Production compose
├── docker-compose.local.yml  # Local development compose
└── README.md                 # Main README
```

## 🆘 Support

### Common Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs [service]

# Execute commands
docker-compose exec app php artisan [command]

# Check status
./scripts/setup-reverse-proxy.sh status
```

### Getting Help

1. Check logs: `docker-compose logs [service]`
2. Check status: `./scripts/setup-reverse-proxy.sh status`
3. Restart services: `docker-compose restart`
4. Review this documentation

## 📚 Additional Resources

-   [Docker Documentation](https://docs.docker.com/)
-   [Nginx Documentation](https://nginx.org/en/docs/)
-   [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
-   [Laravel Documentation](https://laravel.com/docs/)
