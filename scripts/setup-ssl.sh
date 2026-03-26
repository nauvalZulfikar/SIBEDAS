#!/bin/bash

# SSL Setup Script for Sibedas PBG Web
# This script sets up SSL certificates for the reverse proxy

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="${DOMAIN:-sibedas.yourdomain.com}"
EMAIL="${EMAIL:-admin@yourdomain.com}"
SSL_TYPE="${SSL_TYPE:-self-signed}"

echo -e "${BLUE}=== SSL Setup for Sibedas PBG Web ===${NC}"
echo -e "Domain: ${GREEN}$DOMAIN${NC}"
echo -e "Email: ${GREEN}$EMAIL${NC}"
echo -e "SSL Type: ${GREEN}$SSL_TYPE${NC}"
echo ""

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo -e "${RED}Error: Docker is not running${NC}"
        exit 1
    fi
}

# Function to check if containers are running
check_containers() {
    if ! docker ps | grep -q sibedas_nginx_proxy; then
        echo -e "${YELLOW}Warning: Reverse proxy container is not running${NC}"
        echo -e "${YELLOW}Starting containers first...${NC}"
        docker-compose up -d
        sleep 10
    fi
}

# Function to setup self-signed certificate
setup_self_signed() {
    echo -e "${BLUE}Setting up self-signed SSL certificate...${NC}"
    
    docker exec sibedas_nginx_proxy /usr/local/bin/ssl-setup.sh self-signed
    
    echo -e "${GREEN}Self-signed certificate setup completed!${NC}"
    echo -e "${YELLOW}Note: Self-signed certificates will show security warnings in browsers${NC}"
}

# Function to setup Let's Encrypt certificate
setup_letsencrypt() {
    echo -e "${BLUE}Setting up Let's Encrypt SSL certificate...${NC}"
    
    # Check if domain is accessible
    echo -e "${YELLOW}Important: Make sure your domain $DOMAIN points to this server${NC}"
    echo -e "${YELLOW}and ports 80 and 443 are accessible from the internet${NC}"
    read -p "Press Enter to continue..."
    
    docker exec sibedas_nginx_proxy /usr/local/bin/ssl-setup.sh letsencrypt
    
    echo -e "${GREEN}Let's Encrypt certificate setup completed!${NC}"
}

# Function to check certificate status
check_certificate() {
    echo -e "${BLUE}Checking certificate status...${NC}"
    
    docker exec sibedas_nginx_proxy /usr/local/bin/ssl-setup.sh check
}

# Function to renew certificate
renew_certificate() {
    echo -e "${BLUE}Renewing SSL certificate...${NC}"
    
    docker exec sibedas_nginx_proxy /usr/local/bin/ssl-setup.sh renew
    
    echo -e "${GREEN}Certificate renewal completed!${NC}"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 {setup|check|renew|self-signed|letsencrypt}"
    echo ""
    echo "Commands:"
    echo "  setup        - Setup SSL certificate (default: self-signed)"
    echo "  check        - Check certificate status"
    echo "  renew        - Renew Let's Encrypt certificate"
    echo "  self-signed  - Setup self-signed certificate"
    echo "  letsencrypt  - Setup Let's Encrypt certificate"
    echo ""
    echo "Environment variables:"
    echo "  DOMAIN       - Domain name (default: sibedas.yourdomain.com)"
    echo "  EMAIL        - Email address for Let's Encrypt (default: admin@yourdomain.com)"
    echo "  SSL_TYPE     - Type of SSL (letsencrypt or self-signed, default: self-signed)"
    echo ""
    echo "Examples:"
    echo "  DOMAIN=myapp.com EMAIL=admin@myapp.com $0 letsencrypt"
    echo "  $0 self-signed"
    echo "  $0 check"
}

# Main script logic
case "${1:-setup}" in
    "setup")
        check_docker
        check_containers
        if [ "$SSL_TYPE" = "letsencrypt" ]; then
            setup_letsencrypt
        else
            setup_self_signed
        fi
        ;;
    "check")
        check_docker
        check_containers
        check_certificate
        ;;
    "renew")
        check_docker
        check_containers
        renew_certificate
        ;;
    "self-signed")
        check_docker
        check_containers
        setup_self_signed
        ;;
    "letsencrypt")
        check_docker
        check_containers
        setup_letsencrypt
        ;;
    *)
        show_usage
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}SSL setup completed successfully!${NC}"
echo -e "${BLUE}You can now access your application at: https://$DOMAIN${NC}" 