FROM nginx:alpine

# Install required packages
RUN apk add --no-cache \
    openssl \
    certbot \
    certbot-nginx \
    bash

# Create SSL directory
RUN mkdir -p /etc/nginx/ssl

# Copy SSL certificates (if they exist)
COPY ssl/ /etc/nginx/ssl/

# Copy Nginx configuration
COPY conf.d/ /etc/nginx/conf.d/

# Create log directories
RUN mkdir -p /var/log/nginx

# Copy SSL setup script
COPY ssl-setup.sh /usr/local/bin/ssl-setup.sh
RUN chmod +x /usr/local/bin/ssl-setup.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD wget --quiet --tries=1 --spider http://localhost/health-check || exit 1

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"] 