# `scripts/` — Operational Scripts

## Quick guide: which script to use?

| Need to... | Use this |
|------------|----------|
| Deploy to production | **GitHub Actions** (`.github/workflows/deploy.yml`) — push to `master` |
| Manual deploy from local | Global `sibedas-deployer` agent (`~/.claude/agents/sibedas-deployer.md`) |
| First-time VPS setup | `setup-reverse-proxy.sh`, `setup-ssl.sh`, `setup-webhook.sh` (one-time only) |
| Local dev DB import | `import-sibedas-database.sh` |
| Build prod assets locally | `build-and-zip.sh` |
| Local Docker dev stack | `setup-local.sh` |
| Populate kecamatan stats | `populate_kecamatan.py` (ad-hoc, not deploy) |
| Daily DB backup (VPS cron) | `backup-db.sh` (gzipped, 14-day rotation) |

## Production target

- **Host**: `root@72.60.196.21`
- **App path**: `/root/projects/sibedaspbg/` *(NOT `/opt/sibedas`, NOT `/var/www/SIBEDAS/`)*
- **Domain**: `sibedaspbg.aureonforge.com` (and `sibedaspbg.cloud` via host nginx)
- **Container nginx**: ports 9080/9443 — reverse-proxied by host nginx
- **DB container**: `sibedas_db` (MariaDB) — kept alive across deploys (use `docker compose up -d`, NOT `down`)

## Common deploy gotchas

1. **`npm ci` is broken** on this repo (rollup optional-deps bug). Use `npm install` before `npm run build`.
2. **rsync exclusions**: `.env`, `.env.agent`, `.agent.yaml`, `.agent-memory.md` — never sync these.
3. **Windows rsync**: lives at `C:\Users\Lenovo\bin\rsync.exe` (manual install, fragile).

## Legacy scripts (`legacy/`)

The following scripts are **broken** (wrong paths, never updated for current prod) and live under `legacy/` for reference only. Each starts with `exit 1` so accidental invocation fails fast:

- `legacy/deploy.sh` — targets non-existent `/opt/sibedas`
- `legacy/deploy-production.sh` — placeholder domain `sibedas.yourdomain.com`
- `legacy/auto-deploy.sh` — targets non-existent `/var/www/SIBEDAS/`
- `legacy/watch-deploy.sh` — targets non-existent `/var/www/SIBEDAS/`

## Webhook receiver

`webhook.php` runs on the VPS host nginx. It writes a `deploy.flag` file picked up by cron. **Replaced by GitHub Actions** as of 2026-04-28 — kept for any host still running it.
