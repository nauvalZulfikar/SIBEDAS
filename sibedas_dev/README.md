# Sibedas PBG Web

Aplikasi web untuk manajemen data PBG (Pendidikan Berkelanjutan Guru) dengan fitur integrasi Google Sheets.

## 🚀 Quick Start

### Prerequisites

-   Docker & Docker Compose
-   Domain name (untuk production)

### Local Development

```bash
git clone <repository-url>
cd sibedas-pbg-web
./scripts/setup-local.sh
# Access: http://localhost:8000
```

### Production Deployment

```bash
# 1. Setup environment
cp env.production.example .env
nano .env

# 2. Deploy with SSL (Recommended)
./scripts/setup-reverse-proxy.sh setup

# 3. Check status
./scripts/setup-reverse-proxy.sh status
# Access: https://yourdomain.com
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

## 🔧 Configuration

### Environment Variables

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

## 🚀 Production Deployment Steps

### 1. Server Preparation

```bash
# Install Docker & Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 2. Clone & Setup

```bash
git clone <repository-url>
cd sibedas-pbg-web
chmod +x scripts/*.sh
cp env.production.example .env
nano .env
```

### 3. Deploy

```bash
# Full deployment with SSL
./scripts/setup-reverse-proxy.sh setup

# Or step by step
./scripts/deploy-production.sh deploy
./scripts/setup-ssl.sh letsencrypt
```

### 4. Verify

```bash
docker-compose ps
./scripts/setup-reverse-proxy.sh status
curl -f http://localhost/health-check
```

## 📊 Monitoring

```bash
# Check status
./scripts/setup-reverse-proxy.sh status

# View logs
docker-compose logs [service]

# Check SSL certificate
./scripts/setup-ssl.sh check
```

## 🛠️ Common Commands

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Restart services
docker-compose restart

# Execute Laravel commands
docker-compose exec app php artisan [command]

# Backup database
docker exec sibedas_db mysqldump -u root -p sibedas > backup.sql
```

## 📁 Scripts

### Essential Scripts

-   `scripts/setup-reverse-proxy.sh` - Setup lengkap reverse proxy dan SSL
-   `scripts/deploy-production.sh` - Deployment production
-   `scripts/setup-ssl.sh` - Setup SSL certificates

### Optional Scripts

-   `scripts/setup-local.sh` - Setup local development
-   `scripts/import-sibedas-database.sh` - Manual database import

## 📚 Documentation

Untuk dokumentasi lengkap, lihat [docs/README.md](docs/README.md)

## 🆘 Support

1. Check logs: `docker-compose logs [service]`
2. Check status: `./scripts/setup-reverse-proxy.sh status`
3. Restart services: `docker-compose restart`
4. Review documentation di folder `docs/`
