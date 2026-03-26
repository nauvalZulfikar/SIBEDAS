#!/bin/bash

# Reverse Proxy Setup Script for Sibedas PBG Web
# Wrapper script untuk setup reverse proxy dan SSL

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Reverse Proxy Setup untuk Sibedas PBG Web ===${NC}"
echo ""

# Function to show usage
show_usage() {
    echo "Usage: $0 {setup|ssl|status|help}"
    echo ""
    echo "Commands:"
    echo "  setup  - Setup reverse proxy dan SSL (default)"
    echo "  ssl    - Setup SSL certificate only"
    echo "  status - Show current status"
    echo "  help   - Show this help message"
    echo ""
    echo "Environment variables:"
    echo "  DOMAIN       - Domain name (default: sibedas.yourdomain.com)"
    echo "  EMAIL        - Email address for Let's Encrypt (default: admin@yourdomain.com)"
    echo "  SSL_TYPE     - Type of SSL (letsencrypt or self-signed, default: self-signed)"
    echo ""
    echo "Examples:"
    echo "  $0 setup"
    echo "  DOMAIN=myapp.com EMAIL=admin@myapp.com SSL_TYPE=letsencrypt $0 setup"
    echo "  $0 ssl"
    echo "  $0 status"
}

# Function to check prerequisites
check_prerequisites() {
    echo -e "${BLUE}Checking prerequisites...${NC}"
    
    # Check if .env exists
    if [ ! -f .env ]; then
        echo -e "${RED}Error: .env file not found${NC}"
        echo -e "${YELLOW}Please create .env file with required environment variables${NC}"
        exit 1
    fi
    
    # Check if scripts directory exists
    if [ ! -d scripts ]; then
        echo -e "${RED}Error: scripts directory not found${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}Prerequisites check passed!${NC}"
}

# Function to setup reverse proxy
setup_reverse_proxy() {
    echo -e "${BLUE}Setting up reverse proxy...${NC}"
    
    # Run deployment script
    ./scripts/deploy-production.sh deploy
    
    echo -e "${GREEN}Reverse proxy setup completed!${NC}"
}

# Function to setup SSL only
setup_ssl() {
    echo -e "${BLUE}Setting up SSL certificate...${NC}"
    
    # Run SSL setup script
    ./scripts/setup-ssl.sh setup
    
    echo -e "${GREEN}SSL setup completed!${NC}"
}

# Function to show status
show_status() {
    echo -e "${BLUE}=== Current Status ===${NC}"
    
    # Check if containers are running
    if command -v docker-compose &> /dev/null; then
        echo -e "${YELLOW}Container Status:${NC}"
        docker-compose ps 2>/dev/null || echo "Docker Compose not available"
    fi
    
    # Check SSL certificate
    if [ -f scripts/setup-ssl.sh ]; then
        echo -e "${YELLOW}SSL Certificate Status:${NC}"
        ./scripts/setup-ssl.sh check 2>/dev/null || echo "SSL check failed"
    fi
    
    # Show environment info
    if [ -f .env ]; then
        echo -e "${YELLOW}Environment Variables:${NC}"
        grep -E "^(DOMAIN|EMAIL|SSL_TYPE|APP_URL)=" .env 2>/dev/null || echo "No environment variables found"
    fi
}

# Main script logic
case "${1:-setup}" in
    "setup")
        check_prerequisites
        setup_reverse_proxy
        ;;
    "ssl")
        check_prerequisites
        setup_ssl
        ;;
    "status")
        show_status
        ;;
    "help"|"-h"|"--help")
        show_usage
        ;;
    *)
        echo -e "${RED}Unknown command: $1${NC}"
        echo ""
        show_usage
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}Setup completed successfully!${NC}"
echo -e "${BLUE}For more information, see: docs/README-Reverse-Proxy-SSL.md${NC}" 