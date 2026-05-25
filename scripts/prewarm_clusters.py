"""
Prewarm the server-side cluster endpoint cache for all zoom levels used by
the dashboard (z6..z13 cluster mode). Each cold response is ~22 s; this
script fires them all in parallel-ish so the daily cron only blocks the
prewarm window, not the user.
"""
import os, subprocess, urllib.request, time

BASE = 'http://127.0.0.1:8002/api/detected-buildings/clusters'
ZOOMS = list(range(6, 14))  # cluster mode covers z<=13

def get_token():
    tok = os.environ.get('SIBEDAS_TOKEN')
    if tok: return tok
    out = subprocess.check_output(
        ['php', 'artisan', 'tinker', '--execute=echo \\App\\Models\\User::find(5)->createToken("clusterwarm")->plainTextToken;'],
        text=True, cwd=os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    )
    return out.strip().splitlines()[-1]

token = get_token()
hdr = {'Authorization': f'Bearer {token}'}

t0 = time.time()
for z in ZOOMS:
    url = f'{BASE}?zoom={z}'
    ts = time.time()
    try:
        req = urllib.request.Request(url, headers=hdr)
        with urllib.request.urlopen(req, timeout=120) as r:
            body = r.read()
            cache_hdr = r.headers.get('X-Cache', '-')
        print(f'  z={z}  {len(body)//1024} KB  {(time.time()-ts):.1f}s  ({cache_hdr})', flush=True)
    except Exception as e:
        print(f'  z={z}  ERROR {e}', flush=True)

print(f'\nDone. total={(time.time()-t0):.1f}s')
