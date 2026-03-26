#!/bin/bash
set -e

echo '[nginx-entrypoint] Using pre-configured conf.d/default.conf (HTTP-only, SSL terminated at host nginx)'

echo '[nginx-entrypoint] Testing nginx configuration...'
nginx -t

echo '[nginx-entrypoint] Starting nginx...'
exec "$@"
