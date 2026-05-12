#!/bin/bash
# =============================================================
# Sibedas — Database backup with rotation
#
# Usage on the VPS:
#   ./scripts/backup-db.sh [output-dir]
#
# Designed to run from cron. Creates a timestamped gzipped dump
# of the `sibedas` MariaDB container and keeps the last N days.
#
# Cron suggestion (daily at 02:30 server time):
#   30 2 * * * /root/projects/sibedaspbg/scripts/backup-db.sh \
#       /root/backups/sibedaspbg >> /var/log/sibedas-backup.log 2>&1
# =============================================================

set -euo pipefail

# ── Config ────────────────────────────────────────────────────
OUT_DIR="${1:-/root/backups/sibedaspbg}"
KEEP_DAYS="${KEEP_DAYS:-14}"
CONTAINER="${CONTAINER:-sibedas_db}"
DB_NAME="${DB_NAME:-sibedas}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root}"

STAMP="$(date +%Y%m%d-%H%M%S)"
OUT_FILE="$OUT_DIR/sibedas-$STAMP.sql.gz"

# ── Safety checks ────────────────────────────────────────────
if ! command -v docker >/dev/null 2>&1; then
    echo "[ERR] docker not found in PATH" >&2
    exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    echo "[ERR] DB container '$CONTAINER' is not running" >&2
    exit 1
fi

mkdir -p "$OUT_DIR"

# ── Dump ─────────────────────────────────────────────────────
echo "[$(date '+%F %T')] Dumping $DB_NAME from $CONTAINER → $OUT_FILE"

docker exec "$CONTAINER" \
    mysqldump --single-transaction --quick --routines --triggers \
              -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    | gzip -c > "$OUT_FILE"

SIZE="$(du -h "$OUT_FILE" | cut -f1)"
echo "[$(date '+%F %T')] OK — $SIZE"

# ── Rotate ───────────────────────────────────────────────────
echo "[$(date '+%F %T')] Rotating: keep last $KEEP_DAYS days in $OUT_DIR"
find "$OUT_DIR" -maxdepth 1 -name 'sibedas-*.sql.gz' -mtime +"$KEEP_DAYS" -print -delete || true

echo "[$(date '+%F %T')] Done."
