FROM nginx:alpine

RUN apk add --no-cache bash

# Remove default nginx config
RUN rm -f /etc/nginx/conf.d/*.conf

# Copy nginx config templates
COPY conf.d/http.conf /etc/nginx/templates/http.conf
COPY conf.d/https.conf /etc/nginx/templates/https.conf

# Copy our pre-built HTTP-only config
COPY conf.d/default.conf /etc/nginx/conf.d/default.conf

# Copy entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80 443

ENTRYPOINT ["/entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
