#!/bin/bash

# Production Deployment Script for Sibedas PBG Web
# This script deploys the application with reverse proxy and SSL support

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

echo -e "${BLUE}=== Production Deployment for Sibedas PBG Web ===${NC}"
echo -e "Domain: ${GREEN}$DOMAIN${NC}"
echo -e "Email: ${GREEN}$EMAIL${NC}"
echo -e "SSL Type: ${GREEN}$SSL_TYPE${NC}"
echo ""

# Function to check prerequisites
check_prerequisites() {
    echo -e "${BLUE}Checking prerequisites...${NC}"
    
    # Check if Docker is installed
    if ! command -v docker &> /dev/null; then
        echo -e "${RED}Error: Docker is not installed${NC}"
        exit 1
    fi
    
    # Check if Docker Compose is installed
    if ! command -v docker-compose &> /dev/null; then
        echo -e "${RED}Error: Docker Compose is not installed${NC}"
        exit 1
    fi
    
    # Check if .env file exists
    if [ ! -f .env ]; then
        echo -e "${RED}Error: .env file not found${NC}"
        echo -e "${YELLOW}Please create .env file with required environment variables${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}Prerequisites check passed!${NC}"
}

# Function to backup existing data
backup_data() {
    echo -e "${BLUE}Creating backup of existing data...${NC}"
    
    BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Backup database
    if docker ps | grep -q sibedas_db; then
        echo -e "${YELLOW}Backing up database...${NC}"
        docker exec sibedas_db mysqldump -u root -p"${MYSQL_ROOT_PASSWORD:-root}" sibedas > "$BACKUP_DIR/database.sql" || true
    fi
    
    # Backup volumes
    echo -e "${YELLOW}Backing up volumes...${NC}"
    docker run --rm -v sibedas_app_storage:/data -v "$(pwd)/$BACKUP_DIR":/backup alpine tar czf /backup/app_storage.tar.gz -C /data . || true
    docker run --rm -v sibedas_dbdata:/data -v "$(pwd)/$BACKUP_DIR":/backup alpine tar czf /backup/dbdata.tar.gz -C /data . || true
    
    echo -e "${GREEN}Backup created in $BACKUP_DIR${NC}"
}

# Function to stop existing containers
stop_containers() {
    echo -e "${BLUE}Stopping existing containers...${NC}"
    
    docker-compose down --remove-orphans || true
    
    echo -e "${GREEN}Containers stopped!${NC}"
}

# Function to build and start containers
deploy_containers() {
    echo -e "${BLUE}Building and starting containers...${NC}"
    
    # Build images
    echo -e "${YELLOW}Building Docker images...${NC}"
    docker-compose build --no-cache
    
    # Start containers
    echo -e "${YELLOW}Starting containers...${NC}"
    docker-compose up -d
    
    # Wait for containers to be healthy
    echo -e "${YELLOW}Waiting for containers to be healthy...${NC}"
    sleep 30
    
    # Check container status
    if ! docker-compose ps | grep -q "Up"; then
        echo -e "${RED}Error: Some containers failed to start${NC}"
        docker-compose logs
        exit 1
    fi
    
    echo -e "${GREEN}Containers deployed successfully!${NC}"
}

# Function to setup SSL
setup_ssl() {
    echo -e "${BLUE}Setting up SSL certificate...${NC}"
    
    # Wait for nginx proxy to be ready
    echo -e "${YELLOW}Waiting for reverse proxy to be ready...${NC}"
    sleep 10
    
    # Setup SSL
    if [ "$SSL_TYPE" = "letsencrypt" ]; then
        echo -e "${YELLOW}Setting up Let's Encrypt certificate...${NC}"
        echo -e "${YELLOW}Make sure your domain $DOMAIN points to this server${NC}"
        read -p "Press Enter to continue..."
        
        docker exec sibedas_nginx_proxy /usr/local/bin/ssl-setup.sh letsencrypt
    else
        echo -e "${YELLOW}Setting up self-signed certificate...${NC}"
        docker exec sibedas_nginx_proxy /usr/local/bin/ssl-setup.sh self-signed
    fi
    
    echo -e "${GREEN}SSL setup completed!${NC}"
}

# Function to run post-deployment tasks
post_deployment() {
    echo -e "${BLUE}Running post-deployment tasks...${NC}"
    
    # Clear Laravel caches
    echo -e "${YELLOW}Clearing Laravel caches...${NC}"
    docker exec sibedas_app php artisan config:clear || true
    docker exec sibedas_app php artisan route:clear || true
    docker exec sibedas_app php artisan view:clear || true
    docker exec sibedas_app php artisan cache:clear || true
    
    # Optimize Laravel
    echo -e "${YELLOW}Optimizing Laravel...${NC}"
    docker exec sibedas_app php artisan optimize || true
    
    # Check application health
    echo -e "${YELLOW}Checking application health...${NC}"
    sleep 5
    
    if curl -f -s "http://localhost/health-check" > /dev/null; then
        echo -e "${GREEN}Application is healthy!${NC}"
    else
        echo -e "${YELLOW}Warning: Health check failed, but deployment completed${NC}"
    fi
}

# Function to show deployment status
show_status() {
    echo -e "${BLUE}=== Deployment Status ===${NC}"
    
    echo -e "${YELLOW}Container Status:${NC}"
    docker-compose ps
    
    echo -e "${YELLOW}SSL Certificate Status:${NC}"
    docker exec sibedas_nginx_proxy /usr/local/bin/ssl-setup.sh check || true
    
    echo -e "${YELLOW}Application URLs:${NC}"
    echo -e "  HTTP:  ${GREEN}http://$DOMAIN${NC}"
    echo -e "  HTTPS: ${GREEN}https://$DOMAIN${NC}"
    
    echo -e "${YELLOW}Logs:${NC}"
    echo -e "  Application: ${GREEN}docker-compose logs app${NC}"
    echo -e "  Reverse Proxy: ${GREEN}docker-compose logs nginx-proxy${NC}"
    echo -e "  Database: ${GREEN}docker-compose logs db${NC}"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 {deploy|status|backup|ssl}"
    echo ""
    echo "Commands:"
    echo "  deploy  - Full deployment with SSL setup"
    echo "  status  - Show deployment status"
    echo "  backup  - Create backup of existing data"
    echo "  ssl     - Setup SSL certificate only"
    echo ""
    echo "Environment variables:"
    echo "  DOMAIN       - Domain name (default: sibedas.yourdomain.com)"
    echo "  EMAIL        - Email address for Let's Encrypt (default: admin@yourdomain.com)"
    echo "  SSL_TYPE     - Type of SSL (letsencrypt or self-signed, default: self-signed)"
    echo ""
    echo "Examples:"
    echo "  DOMAIN=myapp.com EMAIL=admin@myapp.com SSL_TYPE=letsencrypt $0 deploy"
    echo "  $0 status"
    echo "  $0 ssl"
}

# Main script logic
case "${1:-deploy}" in
    "deploy")
        check_prerequisites
        backup_data
        stop_containers
        deploy_containers
        setup_ssl
        post_deployment
        show_status
        ;;
    "status")
        show_status
        ;;
    "backup")
        backup_data
        ;;
    "ssl")
        setup_ssl
        ;;
    *)
        show_usage
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}Deployment completed successfully!${NC}"
echo -e "${BLUE}Your application is now accessible at: https://$DOMAIN${NC}" 