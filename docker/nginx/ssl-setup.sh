#!/bin/bash

# SSL Setup Script for Sibedas PBG Web
# This script handles SSL certificate generation and renewal

set -e

DOMAIN="${DOMAIN:-sibedas.yourdomain.com}"
EMAIL="${EMAIL:-admin@yourdomain.com}"
SSL_DIR="/etc/nginx/ssl"
CERT_FILE="$SSL_DIR/sibedas.crt"
KEY_FILE="$SSL_DIR/sibedas.key"

# Function to generate self-signed certificate
generate_self_signed() {
    echo "Generating self-signed SSL certificate for $DOMAIN..."
    
    # Create SSL directory if it doesn't exist
    mkdir -p "$SSL_DIR"
    
    # Generate self-signed certificate
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$KEY_FILE" \
        -out "$CERT_FILE" \
        -subj "/C=ID/ST=Jakarta/L=Jakarta/O=Sibedas/OU=IT/CN=$DOMAIN/emailAddress=$EMAIL"
    
    echo "Self-signed certificate generated successfully!"
}

# Function to setup Let's Encrypt certificate
setup_letsencrypt() {
    echo "Setting up Let's Encrypt certificate for $DOMAIN..."
    
    # Check if certbot is available
    if ! command -v certbot &> /dev/null; then
        echo "Certbot not found. Installing..."
        apk add --no-cache certbot certbot-nginx
    fi
    
    # Stop nginx temporarily
    nginx -s stop || true
    
    # Get certificate
    certbot certonly --standalone \
        --email "$EMAIL" \
        --agree-tos \
        --no-eff-email \
        -d "$DOMAIN"
    
    # Copy certificates to nginx ssl directory
    cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem "$CERT_FILE"
    cp /etc/letsencrypt/live/$DOMAIN/privkey.pem "$KEY_FILE"
    
    # Set proper permissions
    chmod 644 "$CERT_FILE"
    chmod 600 "$KEY_FILE"
    
    # Start nginx
    nginx
    
    echo "Let's Encrypt certificate setup completed!"
}

# Function to renew Let's Encrypt certificate
renew_certificate() {
    echo "Renewing Let's Encrypt certificate..."
    
    certbot renew --quiet
    
    # Copy renewed certificates
    cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem "$CERT_FILE"
    cp /etc/letsencrypt/live/$DOMAIN/privkey.pem "$KEY_FILE"
    
    # Reload nginx
    nginx -s reload
    
    echo "Certificate renewal completed!"
}

# Function to check certificate validity
check_certificate() {
    if [ -f "$CERT_FILE" ] && [ -f "$KEY_FILE" ]; then
        echo "Certificate files exist."
        echo "Certificate details:"
        openssl x509 -in "$CERT_FILE" -text -noout | grep -E "(Subject:|Not Before|Not After)"
        return 0
    else
        echo "Certificate files not found."
        return 1
    fi
}

# Main script logic
case "${1:-setup}" in
    "setup")
        if [ "$SSL_TYPE" = "letsencrypt" ]; then
            setup_letsencrypt
        else
            generate_self_signed
        fi
        ;;
    "renew")
        renew_certificate
        ;;
    "check")
        check_certificate
        ;;
    "self-signed")
        generate_self_signed
        ;;
    "letsencrypt")
        setup_letsencrypt
        ;;
    *)
        echo "Usage: $0 {setup|renew|check|self-signed|letsencrypt}"
        echo ""
        echo "Environment variables:"
        echo "  DOMAIN: Domain name (default: sibedas.yourdomain.com)"
        echo "  EMAIL: Email address for Let's Encrypt (default: admin@yourdomain.com)"
        echo "  SSL_TYPE: Type of SSL (letsencrypt or self-signed, default: self-signed)"
        exit 1
        ;;
esac 