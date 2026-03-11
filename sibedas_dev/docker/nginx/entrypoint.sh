#!/bin/bash
set -e

DOMAIN="${DOMAIN:-sibedaspbg.cloud}"
CERT_FILE="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
TEMPLATE_DIR="/etc/nginx/templates"
CONF_DIR="/etc/nginx/conf.d"

mkdir -p "$CONF_DIR"

if [ -f "$CERT_FILE" ]; then
    echo "[nginx-entrypoint] SSL certificate found for $DOMAIN — enabling HTTPS"
    cp "$TEMPLATE_DIR/https.conf" "$CONF_DIR/default.conf"
else
    echo "[nginx-entrypoint] No SSL certificate found — starting in HTTP-only mode"
    echo "[nginx-entrypoint] Run: certbot certonly --standalone -d $DOMAIN"
    cp "$TEMPLATE_DIR/http.conf" "$CONF_DIR/default.conf"
fi

# Replace domain placeholder in the active config
sed -i "s/DOMAIN_PLACEHOLDER/${DOMAIN}/g" "$CONF_DIR/default.conf"

echo "[nginx-entrypoint] Testing nginx configuration..."
nginx -t

echo "[nginx-entrypoint] Starting nginx..."
exec "$@"
