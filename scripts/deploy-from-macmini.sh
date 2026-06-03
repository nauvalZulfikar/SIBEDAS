#!/usr/bin/env bash
# SIBEDAS deploy from Mac Mini → VPS root@72.60.196.21
# Flow: ensure Tailscale up → rsync repo → ssh + docker compose up
set -euo pipefail

REPO_DIR="$(cd "$(dirname "$0")/.." && pwd)"
VPS="root@72.60.196.21"
REMOTE_PATH="/root/projects/sibedaspbg"
TS_CMD="/Applications/Tailscale.app/Contents/MacOS/Tailscale"

cd "$REPO_DIR"

echo "[1/5] Verifying Tailscale is up..."
if ! "$TS_CMD" status >/dev/null 2>&1; then
  echo "  Tailscale down — starting..."
  open -a Tailscale
  sleep 4
  "$TS_CMD" up
fi
"$TS_CMD" status | head -1

echo "[2/5] Pre-flight SSH check..."
if ! ssh -o ConnectTimeout=8 -o BatchMode=yes "$VPS" 'echo ok' >/dev/null 2>&1; then
  echo "  ERROR: cannot reach $VPS via SSH. Check Tailscale + VPS firewall." >&2
  exit 2
fi

echo "[3/5] Rsync repo → $VPS:$REMOTE_PATH ..."
rsync -avz --delete \
  --exclude '.git' \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude 'storage/logs/*' \
  --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude '.env' \
  --exclude '.env.agent' \
  --exclude '.agent.yaml' \
  --exclude '.agent-memory.md' \
  --exclude 'agent.py' \
  --exclude 'agent.log' \
  --exclude 'agent_queue.json' \
  --exclude 'docker-compose.local.yml' \
  --exclude 'profiles/' \
  --exclude 'runs/' \
  --exclude 'sibedas.sql' \
  --exclude '*.sql' \
  "$REPO_DIR/" "$VPS:$REMOTE_PATH/"

echo "[4/5] Rebuild & restart containers on VPS..."
ssh "$VPS" "cd $REMOTE_PATH && docker compose up -d"

echo "[5/5] Post-deploy cache clear..."
ssh "$VPS" "cd $REMOTE_PATH && \
  docker compose exec -T app php artisan view:clear && \
  docker compose exec -T app php artisan cache:clear && \
  docker compose exec -T app php artisan config:clear" || \
  echo "  (cache clear skipped — non-fatal)"

echo
echo "Deploy done — $VPS:$REMOTE_PATH"
