#!/bin/bash
# =============================================================
# Sibedas Production Deployment Script
# Target: root@72.60.196.21 | Domain: sibedaspbg.cloud
# =============================================================

set -e

# ── Config ────────────────────────────────────────────────────
VPS_IP="72.60.196.21"
VPS_USER="root"
APP_DIR="/opt/sibedas"
DOMAIN="sibedaspbg.cloud"
EMAIL="admin@sibedaspbg.cloud"
LOCAL_DIR="$(cd "$(dirname "$0")/.." && pwd)"

# ── Colors ────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}[INFO]${NC} $*"; }
success() { echo -e "${GREEN}[OK]${NC}   $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $*"; }
error()   { echo -e "${RED}[ERR]${NC}  $*"; exit 1; }

echo -e "${GREEN}"
echo "╔══════════════════════════════════════════════╗"
echo "║     Sibedas — Production Deployment          ║"
echo "║     VPS: $VPS_IP                   ║"
echo "║     Domain: $DOMAIN              ║"
echo "╚══════════════════════════════════════════════╝"
echo -e "${NC}"

# ── Pre-flight checks ─────────────────────────────────────────
info "Checking local requirements..."
command -v rsync >/dev/null 2>&1 || error "rsync is required. Install it first."
command -v ssh   >/dev/null 2>&1 || error "ssh is required."

# Make sure pre-built assets exist
if [ ! -d "$LOCAL_DIR/public/build" ]; then
    warn "public/build not found. Building frontend assets..."
    cd "$LOCAL_DIR"
    npm ci --no-optional && npm run build
    cd - > /dev/null
fi

# ── Step 1: Upload project files to VPS ───────────────────────
info "Step 1/6: Uploading files to VPS (rsync)..."
ssh "$VPS_USER@$VPS_IP" "mkdir -p $APP_DIR"

rsync -avz --progress \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='*.zip' \
    --exclude='struktur.txt' \
    --exclude='docker-compose.local.yml' \
    --exclude='docs/' \
    --exclude='tests/' \
    --exclude='storage/framework/' \
    --exclude='storage/logs/' \
    --exclude='storage/pail/' \
    --exclude='bootstrap/cache/' \
    "$LOCAL_DIR/" \
    "$VPS_USER@$VPS_IP:$APP_DIR/"

# Upload .env separately (excluded from rsync above)
info "Uploading .env..."
scp "$LOCAL_DIR/.env" "$VPS_USER@$VPS_IP:$APP_DIR/.env"

# Upload Google credentials if present
GCLOUD_CRED="$LOCAL_DIR/teak-banner-450003-s8-ea05661d9db0.json"
if [ -f "$GCLOUD_CRED" ]; then
    info "Uploading Google service account credentials..."
    ssh "$VPS_USER@$VPS_IP" "mkdir -p $APP_DIR/storage/app"
    scp "$GCLOUD_CRED" "$VPS_USER@$VPS_IP:$APP_DIR/storage/app/teak-banner-450003-s8-ea05661d9db0.json"
fi

success "Files uploaded."

# ── Step 2: Install Docker & Docker Compose on VPS ───────────
info "Step 2/6: Ensuring Docker is installed on VPS..."
ssh "$VPS_USER@$VPS_IP" bash << 'REMOTE'
set -e
if ! command -v docker &>/dev/null; then
    echo "Installing Docker..."
    apt-get update -qq
    apt-get install -y -qq curl ca-certificates gnupg
    curl -fsSL https://get.docker.com | sh
    systemctl enable docker
    systemctl start docker
    echo "Docker installed."
else
    echo "Docker already installed: $(docker --version)"
fi

if ! docker compose version &>/dev/null; then
    echo "Installing Docker Compose plugin..."
    apt-get install -y -qq docker-compose-plugin
    echo "Docker Compose installed."
else
    echo "Docker Compose already installed: $(docker compose version)"
fi

# Install certbot (for SSL)
if ! command -v certbot &>/dev/null; then
    echo "Installing certbot..."
    apt-get install -y -qq certbot
fi
REMOTE
success "Docker ready."

# ── Step 3: Prepare VPS directory structure ───────────────────
info "Step 3/6: Preparing directory structure on VPS..."
ssh "$VPS_USER@$VPS_IP" bash << REMOTE
set -e
cd $APP_DIR

# Create required directories
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p bootstrap/cache
mkdir -p public

# Create storage symlink for public/storage if not already a symlink
if [ ! -L public/storage ] && [ ! -e public/storage ]; then
    ln -sf ../storage/app/public public/storage
    echo "Created public/storage symlink"
fi

# Set permissions
chmod -R 775 storage bootstrap/cache
chmod +x docker/nginx/entrypoint.sh
chmod +x docker/startup.sh

echo "Directory structure ready."
ls -la $APP_DIR
REMOTE
success "Directory structure prepared."

# ── Step 4: Build and start Docker containers ─────────────────
info "Step 4/6: Building and starting Docker containers..."
ssh "$VPS_USER@$VPS_IP" bash << REMOTE
set -e
cd $APP_DIR

# Stop existing containers gracefully
echo "Stopping existing containers..."
docker compose down --remove-orphans 2>/dev/null || true

# Build images
echo "Building Docker images (this may take 3-5 minutes)..."
docker compose build --no-cache

# Start database first and wait
echo "Starting database..."
docker compose up -d db
echo "Waiting for database to initialize (up to 2 minutes)..."
sleep 15

# Start app (runs startup.sh: migrations, optimize, then supervisord)
echo "Starting app container..."
docker compose up -d app
echo "Waiting for app to finish setup (up to 2 minutes)..."
sleep 30

# Show status
docker compose ps
REMOTE
success "Containers started."

# ── Step 5: Verify app is running ─────────────────────────────
info "Step 5/6: Verifying app container health..."
ssh "$VPS_USER@$VPS_IP" bash << REMOTE
set -e
cd $APP_DIR

# Wait for app healthcheck
max_wait=60
count=0
while ! docker compose exec -T app php -v > /dev/null 2>&1; do
    count=\$((count + 1))
    if [ \$count -gt \$max_wait ]; then
        echo "App container not responding. Logs:"
        docker compose logs --tail=50 app
        exit 1
    fi
    echo "Waiting for app... (\$count/\$max_wait)"
    sleep 3
done
echo "App is responding!"

# Show recent logs
echo ""
echo "=== App logs (last 20 lines) ==="
docker compose logs --tail=20 app
REMOTE
success "App is healthy."

# ── Step 6: Setup SSL with Let's Encrypt ─────────────────────
info "Step 6/6: Setting up SSL certificate..."
ssh "$VPS_USER@$VPS_IP" bash << REMOTE
set -e
cd $APP_DIR

DOMAIN="$DOMAIN"
EMAIL="$EMAIL"
CERT_FILE="/etc/letsencrypt/live/\$DOMAIN/fullchain.pem"

if [ -f "\$CERT_FILE" ]; then
    echo "SSL certificate already exists for \$DOMAIN"
else
    echo "Obtaining Let's Encrypt certificate for \$DOMAIN..."
    echo "Stopping nginx temporarily for standalone certificate challenge..."

    # Stop nginx so certbot can bind to port 80
    docker compose stop nginx 2>/dev/null || true

    # Get certificate (standalone mode)
    certbot certonly \
        --standalone \
        --email "\$EMAIL" \
        --agree-tos \
        --no-eff-email \
        --non-interactive \
        -d "\$DOMAIN"

    echo "Certificate obtained!"
fi

# Start nginx (entrypoint will detect cert and use HTTPS config)
echo "Starting nginx with HTTPS config..."
docker compose up -d nginx
sleep 5

echo "=== All containers status ==="
docker compose ps

# Set up automatic certificate renewal via cron
CRON_JOB="0 3 * * * certbot renew --quiet --pre-hook 'docker compose -f $APP_DIR/docker-compose.yml stop nginx' --post-hook 'docker compose -f $APP_DIR/docker-compose.yml start nginx' >> /var/log/certbot-renew.log 2>&1"
( crontab -l 2>/dev/null | grep -v certbot; echo "\$CRON_JOB" ) | crontab -
echo "Certbot auto-renewal cron configured."
REMOTE
success "SSL setup complete."

# ── Done ──────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}"
echo "╔══════════════════════════════════════════════╗"
echo "║         DEPLOYMENT COMPLETE!                 ║"
echo "╠══════════════════════════════════════════════╣"
echo "║  Site:   https://$DOMAIN     ║"
echo "║  Login:  https://$DOMAIN/login    ║"
echo "╠══════════════════════════════════════════════╣"
echo "║  Useful commands (run on VPS):               ║"
echo "║  cd $APP_DIR                      ║"
echo "║  docker compose ps                           ║"
echo "║  docker compose logs app                     ║"
echo "║  docker compose logs nginx                   ║"
echo "║  docker compose exec app php artisan ...     ║"
echo "╚══════════════════════════════════════════════╝"
echo -e "${NC}"
