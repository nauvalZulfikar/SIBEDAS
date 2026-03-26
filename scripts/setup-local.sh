#!/bin/bash

# Local Development Setup Script for Sibedas PBG Web
# This script sets up the local development environment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Local Development Setup untuk Sibedas PBG Web ===${NC}"
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
        echo -e "${YELLOW}Warning: .env file not found${NC}"
        echo -e "${YELLOW}Creating from example...${NC}"
        if [ -f env.production.example ]; then
            cp env.production.example .env
        else
            echo -e "${RED}Error: No environment example file found${NC}"
            exit 1
        fi
    fi
    
    echo -e "${GREEN}Prerequisites check passed!${NC}"
}

# Function to setup environment for local development
setup_environment() {
    echo -e "${BLUE}Setting up environment for local development...${NC}"
    
    # Update .env for local development
    sed -i 's/APP_ENV=production/APP_ENV=local/g' .env
    sed -i 's/APP_DEBUG=false/APP_DEBUG=true/g' .env
    sed -i 's/APP_URL=https:\/\/sibedas.yourdomain.com/APP_URL=http:\/\/localhost:8000/g' .env
    sed -i 's/VITE_APP_URL=https:\/\/sibedas.yourdomain.com/VITE_APP_URL=http:\/\/localhost:8000/g' .env
    
    # Update database settings for local
    sed -i 's/DB_USERNAME=sibedas_user/DB_USERNAME=root/g' .env
    sed -i 's/DB_PASSWORD=your_secure_database_password/DB_PASSWORD=root/g' .env
    sed -i 's/MYSQL_ROOT_PASSWORD=your_secure_root_password/MYSQL_ROOT_PASSWORD=root/g' .env
    
    echo -e "${GREEN}Environment configured for local development!${NC}"
}

# Function to start local containers
start_containers() {
    echo -e "${BLUE}Starting local development containers...${NC}"
    
    # Stop any existing containers
    docker-compose -f docker-compose.local.yml down --remove-orphans || true
    
    # Build and start containers
    docker-compose -f docker-compose.local.yml up -d --build
    
    # Wait for containers to be ready
    echo -e "${YELLOW}Waiting for containers to be ready...${NC}"
    sleep 30
    
    # Check container status
    if docker-compose -f docker-compose.local.yml ps | grep -q "Up"; then
        echo -e "${GREEN}Containers started successfully!${NC}"
    else
        echo -e "${RED}Error: Some containers failed to start${NC}"
        docker-compose -f docker-compose.local.yml logs
        exit 1
    fi
}

# Function to setup database
setup_database() {
    echo -e "${BLUE}Setting up database...${NC}"
    
    # Wait for database to be ready
    echo -e "${YELLOW}Waiting for database to be ready...${NC}"
    sleep 10
    
    # Check if database import was successful
    if docker exec sibedas_db_local mysql -uroot -proot sibedas -e "SHOW TABLES LIKE 'users';" 2>/dev/null | grep -q "users"; then
        echo -e "${GREEN}Database imported successfully from sibedas.sql!${NC}"
    else
        echo -e "${YELLOW}Warning: Database import verification failed${NC}"
        echo -e "${YELLOW}You may need to manually import the database${NC}"
    fi
}

# Function to run post-setup tasks
post_setup() {
    echo -e "${BLUE}Running post-setup tasks...${NC}"
    
    # Clear Laravel caches
    echo -e "${YELLOW}Clearing Laravel caches...${NC}"
    docker exec sibedas_app_local php artisan config:clear || true
    docker exec sibedas_app_local php artisan route:clear || true
    docker exec sibedas_app_local php artisan view:clear || true
    docker exec sibedas_app_local php artisan cache:clear || true
    
    # Optimize Laravel
    echo -e "${YELLOW}Optimizing Laravel...${NC}"
    docker exec sibedas_app_local php artisan optimize:clear || true
    
    # Create storage link
    echo -e "${YELLOW}Creating storage link...${NC}"
    docker exec sibedas_app_local php artisan storage:link || true
    
    echo -e "${GREEN}Post-setup tasks completed!${NC}"
}

# Function to show status
show_status() {
    echo -e "${BLUE}=== Local Development Status ===${NC}"
    
    echo -e "${YELLOW}Container Status:${NC}"
    docker-compose -f docker-compose.local.yml ps
    
    echo -e "${YELLOW}Application URLs:${NC}"
    echo -e "  Main App: ${GREEN}http://localhost:8000${NC}"
    echo -e "  Vite Dev: ${GREEN}http://localhost:5173${NC}"
    
    echo -e "${YELLOW}Useful Commands:${NC}"
    echo -e "  View logs: ${GREEN}docker-compose -f docker-compose.local.yml logs -f [service]${NC}"
    echo -e "  Execute commands: ${GREEN}docker-compose -f docker-compose.local.yml exec app php artisan [command]${NC}"
    echo -e "  Stop services: ${GREEN}docker-compose -f docker-compose.local.yml down${NC}"
    echo -e "  Restart services: ${GREEN}docker-compose -f docker-compose.local.yml restart${NC}"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 {setup|status|help}"
    echo ""
    echo "Commands:"
    echo "  setup  - Setup local development environment (default)"
    echo "  status - Show current status"
    echo "  help   - Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 setup"
    echo "  $0 status"
}

# Main script logic
case "${1:-setup}" in
    "setup")
        check_prerequisites
        setup_environment
        start_containers
        setup_database
        post_setup
        show_status
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
echo -e "${GREEN}Local development setup completed successfully!${NC}"
echo -e "${BLUE}You can now access your application at: http://localhost:8000${NC}" 