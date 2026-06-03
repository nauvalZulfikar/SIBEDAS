# Deploy notes — 2026-05-28

## Changes to deploy

### 1. Fix link Total Potensi Berkas (Dashboard Pimpinan)
File: `resources/views/dashboards/leader.blade.php` line 61

```diff
-            'document_url' => route('pbg-task.index', ['menu_id' => $menus->where('url','pbg-task.index')->first()->id, 'filter' => 'all'])
+            'document_url' => route('pbg-task.index', ['menu_id' => $menus->where('url','pbg-task.index')->first()->id, 'filter' => 'potention'])
```

**Why:** klik tombol "Total Potensi Berkas" buka tabel dgn filter=`all` (semua row 2 tahun, ~2.8k) padahal angka dashboard pakai definisi `potention` (~800). Mismatch +2,000.

### 2. Drop menu "Monitoring Satelit"

```sql
DELETE FROM menus WHERE url = 'dashboard.satellite-monitoring';
-- Verifikasi:
SELECT id, name, url FROM menus WHERE url LIKE '%satel%';
-- Bersihin cache menu di Laravel:
-- php artisan cache:forget menus_all   # atau php artisan cache:clear
```

Local id=47, name="Monitoring Satelit", url="dashboard.satellite-monitoring", parent_id=1. Prod ID bisa beda — match by `url` lebih aman.

Catatan: route + controller + view tetep dibiarin (gak perlu dihapus). Cuma menu sidebar yg di-drop.

## Deploy dari Windows

```bash
# (di Git-Bash Windows, di folder repo lokal)
git pull           # kalau perubahan udah di-commit/push, atau apply patch manual
# Lalu pake sibedas-deployer agent atau:
rsync -avz --exclude '.env' --exclude '.env.agent' --exclude '.agent.yaml' --exclude '.agent-memory.md' \
  ./ root@72.60.196.21:/root/projects/sibedaspbg/
ssh root@72.60.196.21 'cd /root/projects/sibedaspbg && docker compose up -d'
```

Setelah deploy:
```bash
ssh root@72.60.196.21
cd /root/projects/sibedaspbg
docker compose exec app php artisan view:clear
docker compose exec app php artisan cache:clear
# Drop menu:
docker compose exec mariadb mysql -uroot -p sibedas -e "DELETE FROM menus WHERE url = 'dashboard.satellite-monitoring';"
# Verifikasi:
docker compose exec mariadb mysql -uroot -p sibedas -e "SELECT id,name,url FROM menus WHERE url LIKE '%satel%';"
```
