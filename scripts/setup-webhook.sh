#!/bin/bash
# Run this once on VPS to setup auto-deploy webhook
# Usage: bash /var/www/SIBEDAS/sibedas_dev/scripts/setup-webhook.sh

APP_DIR="/var/www/SIBEDAS/sibedas_dev"
WEBHOOK_DIR="/var/www/SIBEDAS/webhook"
WEBHOOK_SECRET="sibedas-webhook-secret"

echo "=== Setting up GitHub webhook receiver ==="

# 1. Init git in app dir if not already
cd "$APP_DIR"
if [ ! -d ".git" ]; then
    echo "Initializing git..."
    git init
    git remote add origin https://github.com/nauvalZulfikar/SIBEDAS.git
    git fetch origin master
    git checkout master
else
    echo "Git already initialized"
    git remote set-url origin https://github.com/nauvalZulfikar/SIBEDAS.git
fi

# 2. Copy webhook.php to a public location
mkdir -p "$WEBHOOK_DIR"
cp "$APP_DIR/scripts/webhook.php" "$WEBHOOK_DIR/index.php"

# 3. Set webhook secret as env var
echo "export WEBHOOK_SECRET=$WEBHOOK_SECRET" >> /etc/environment
echo "WEBHOOK_SECRET=$WEBHOOK_SECRET" >> /etc/environment

# 4. Add nginx location block for /webhook
NGINX_CONF="/etc/nginx/conf.d/default.conf"
if ! grep -q "location /webhook" "$NGINX_CONF" 2>/dev/null; then
    # Find the nginx container and add webhook location
    docker exec sibedas_nginx sh -c "
cat >> /etc/nginx/conf.d/default.conf << 'NGINX'

location /webhook {
    root /var/www/SIBEDAS/webhook;
    fastcgi_pass app:9000;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME /var/www/SIBEDAS/webhook/index.php;
    fastcgi_param WEBHOOK_SECRET $WEBHOOK_SECRET;
}
NGINX
    nginx -s reload
    " 2>/dev/null || echo "Could not update nginx conf automatically"
fi

# 5. Mount webhook dir into nginx container
echo ""
echo "=== Setup complete ==="
echo ""
echo "Next steps:"
echo "1. Add this GitHub webhook:"
echo "   URL: https://sibedaspbg.cloud/webhook"
echo "   Secret: $WEBHOOK_SECRET"
echo "   Content type: application/json"
echo "   Events: Just the push event"
echo ""
echo "2. Make sure /var/www/SIBEDAS/webhook/ is accessible by nginx container"
echo "   Add to docker-compose.yml nginx volumes:"
echo "   - /var/www/SIBEDAS/webhook:/var/www/SIBEDAS/webhook:ro"
echo ""
echo "3. Test with: curl -X POST https://sibedaspbg.cloud/webhook"
