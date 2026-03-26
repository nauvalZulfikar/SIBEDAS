#!/bin/bash

echo "🗃️ Import Database from sibedas.sql"
echo "===================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if sibedas.sql exists
check_sql_file() {
    if [[ ! -f "../sibedas.sql" ]]; then
        print_error "sibedas.sql file not found!"
        print_error "Please make sure sibedas.sql is in the project root directory."
        exit 1
    fi
    
    print_success "Found sibedas.sql file"
}

# Import for local development
import_local() {
    print_status "Importing database for LOCAL DEVELOPMENT..."
    
    # Stop containers if running
    print_status "Stopping containers..."
    docker-compose -f ../docker-compose.local.yml down
    
    # Remove existing database volume to force fresh import
    print_warning "Removing old database volume for fresh import..."
    docker volume rm sibedas-pbg-web_sibedas_dbdata_local 2>/dev/null || true
    
    # Start database container first
    print_status "Starting database container..."
    docker-compose -f ../docker-compose.local.yml up -d db
    
    # Wait for database to be ready
    print_status "Waiting for database to be ready..."
    sleep 20
    
    # Verify sibedas.sql was imported automatically
    print_status "Verifying database import..."
    if docker-compose -f ../docker-compose.local.yml exec -T db mysql -uroot -proot -e "USE sibedas; SELECT COUNT(*) FROM users;" 2>/dev/null; then
        print_success "✅ Database imported successfully from sibedas.sql!"
        
        # Show table summary
        print_status "Database tables summary:"
        docker-compose -f ../docker-compose.local.yml exec -T db mysql -uroot -proot -e "
        USE sibedas; 
        SELECT 'users' as table_name, COUNT(*) as count FROM users
        UNION SELECT 'advertisements', COUNT(*) FROM advertisements  
        UNION SELECT 'business_or_industries', COUNT(*) FROM business_or_industries
        UNION SELECT 'customers', COUNT(*) FROM customers
        UNION SELECT 'cache', COUNT(*) FROM cache
        UNION SELECT 'sessions', COUNT(*) FROM sessions
        UNION SELECT 'jobs', COUNT(*) FROM jobs;"
        
    else
        print_error "❌ Database import failed or data not found!"
        exit 1
    fi
    
    # Start all containers
    print_status "Starting all containers..."
    docker-compose -f ../docker-compose.local.yml up -d
    
    # Wait for app to be ready
    sleep 15
    
    # Clear caches to ensure fresh start
    print_status "Clearing application caches..."
    docker-compose -f ../docker-compose.local.yml exec -T app php artisan config:clear
    docker-compose -f ../docker-compose.local.yml exec -T app php artisan cache:clear
    docker-compose -f ../docker-compose.local.yml exec -T app php artisan view:clear
    
    print_success "✅ Local development setup completed with sibedas.sql data!"
    print_status "Access your application at: http://localhost:8000"
}

# Import for production
import_production() {
    print_status "Importing database for PRODUCTION..."
    
    # Check if .env exists
    if [[ ! -f "../.env" ]]; then
        print_error ".env file not found! Please configure production environment first."
        exit 1
    fi
    
    # Load environment variables
    source ../.env
    
    # Stop containers if running
    print_status "Stopping containers..."
    docker-compose -f ../docker-compose.yml down
    
    # Remove existing database volume to force fresh import
    print_warning "Removing old database volume for fresh import..."
    docker volume rm sibedas-pbg-web_sibedas_dbdata 2>/dev/null || true
    
    # Start database container first
    print_status "Starting database container..."
    docker-compose -f ../docker-compose.yml up -d db
    
    # Wait for database to be ready
    print_status "Waiting for database to be ready..."
    sleep 30
    
    # Verify sibedas.sql was imported automatically
    print_status "Verifying database import..."
    if docker-compose -f ../docker-compose.yml exec -T db mysql -uroot -p${MYSQL_ROOT_PASSWORD} -e "USE ${DB_DATABASE}; SELECT COUNT(*) FROM users;" 2>/dev/null; then
        print_success "✅ Database imported successfully from sibedas.sql!"
        
        # Show table summary
        print_status "Database tables summary:"
        docker-compose -f ../docker-compose.yml exec -T db mysql -uroot -p${MYSQL_ROOT_PASSWORD} -e "
        USE ${DB_DATABASE}; 
        SELECT 'users' as table_name, COUNT(*) as count FROM users
        UNION SELECT 'advertisements', COUNT(*) FROM advertisements  
        UNION SELECT 'business_or_industries', COUNT(*) FROM business_or_industries
        UNION SELECT 'customers', COUNT(*) FROM customers
        UNION SELECT 'cache', COUNT(*) FROM cache
        UNION SELECT 'sessions', COUNT(*) FROM sessions
        UNION SELECT 'jobs', COUNT(*) FROM jobs;"
        
    else
        print_error "❌ Database import failed or data not found!"
        exit 1
    fi
    
    # Start all containers
    print_status "Starting all containers..."
    docker-compose -f ../docker-compose.yml up -d
    
    # Wait for app to be ready
    sleep 30
    
    # Generate app key if needed
    if [[ -z "$APP_KEY" ]] || [[ "$APP_KEY" == "" ]]; then
        print_status "Generating application key..."
        docker-compose -f ../docker-compose.yml exec -T app php artisan key:generate --force
    fi
    
    # Optimize application
    print_status "Optimizing application..."
    docker-compose -f ../docker-compose.yml exec -T app php artisan config:cache
    docker-compose -f ../docker-compose.yml exec -T app php artisan route:cache
    docker-compose -f ../docker-compose.yml exec -T app php artisan view:cache
    
    # Create storage link
    print_status "Creating storage link..."
    docker-compose -f ../docker-compose.yml exec -T app php artisan storage:link
    
    print_success "✅ Production setup completed with sibedas.sql data!"
    print_status "Access your application at: ${APP_URL}"
}

# Manual import to running container
manual_import() {
    print_status "Manual import to running container..."
    
    # Check which containers are running
    if docker-compose -f ../docker-compose.local.yml ps | grep -q "sibedas_db_local"; then
        print_status "Found local development database container"
        print_status "Importing sibedas.sql..."
        docker-compose -f ../docker-compose.local.yml exec -T db mysql -uroot -proot sibedas < ../sibedas.sql
        print_success "✅ Import completed for local development!"
        
        # Clear app caches
        docker-compose -f ../docker-compose.local.yml exec -T app php artisan cache:clear 2>/dev/null || true
        
    elif docker-compose -f ../docker-compose.yml ps | grep -q "sibedas_db"; then
        print_status "Found production database container"
        source ../.env 2>/dev/null || true
        print_status "Importing sibedas.sql..."
        docker-compose -f ../docker-compose.yml exec -T db mysql -uroot -p${MYSQL_ROOT_PASSWORD:-root} ${DB_DATABASE:-sibedas} < ../sibedas.sql
        print_success "✅ Import completed for production!"
        
        # Clear app caches
        docker-compose -f ../docker-compose.yml exec -T app php artisan cache:clear 2>/dev/null || true
        
    else
        print_error "❌ No database container found running!"
        print_error "Please start containers first:"
        print_error "  Local: docker-compose -f ../docker-compose.local.yml up -d"
        print_error "  Production: docker-compose -f ../docker-compose.yml up -d"
        exit 1
    fi
}

# Main execution
main() {
    check_sql_file
    
    echo ""
    echo "🗃️ Choose import method:"
    echo "1) 🔄 Fresh import for LOCAL development (recommended)"
    echo "2) 🔄 Fresh import for PRODUCTION"
    echo "3) 📥 Manual import to running container"
    echo "4) ❌ Cancel"
    echo ""
    
    read -p "Enter your choice (1-4): " choice
    
    case $choice in
        1)
            import_local
            ;;
        2)
            import_production
            ;;
        3)
            manual_import
            ;;
        4)
            print_status "Cancelled"
            exit 0
            ;;
        *)
            print_error "Invalid choice"
            exit 1
            ;;
    esac
    
    echo ""
    print_success "🎉 Database import completed!"
    echo ""
    print_status "📋 Imported data includes:"
    echo "  ✅ All application tables with existing data"
    echo "  ✅ Cache table (for CACHE_DRIVER=database)"
    echo "  ✅ Sessions table (for SESSION_DRIVER=database)"
    echo "  ✅ Jobs & failed_jobs tables (for QUEUE_CONNECTION=database)"
    echo ""
    print_status "🚀 Your application is ready to use with all data!"
}

# Run main function
main "$@" 