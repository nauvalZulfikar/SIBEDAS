import requests
import pandas as pd

SIMBG_HOST = "https://simbg.pu.go.id"
EMAIL = "dputr@bandungkab.go.id"
PASSWORD = "LogitechG29"

# Login
print("Logging in...")
login_resp = requests.post(
    f"{SIMBG_HOST}/api/user/v1/auth/login/",
    json={"email": EMAIL, "password": PASSWORD},
    timeout=30
)
print(f"Status: {login_resp.status_code}")
print(f"Response: {login_resp.text[:300]}")
login_resp.raise_for_status()
token_data = login_resp.json()["token"]
token = token_data["access"]
print("Login OK")

# Fetch 5 tasks
headers = {"Authorization": f"Bearer {token}"}
resp = requests.get(
    f"{SIMBG_HOST}/api/pbg/v1/list/?page=1&size=5&sort=ASC&type=task&sort_by=created_at&application_type=1",
    headers=headers,
    timeout=30
)
resp.raise_for_status()
result = resp.json()
data = result["data"]

# Display
df = pd.DataFrame(data)
print(f"\nTotal pages: {result.get('total_page')}, Columns: {list(df.columns)}\n")
pd.set_option('display.max_columns', None)
pd.set_option('display.max_colwidth', 40)
pd.set_option('display.width', 200)
print(df.to_string(index=False))
