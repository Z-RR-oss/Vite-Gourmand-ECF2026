"""Recette réservée au Compose local : requêtes HTTP et email capturé par Mailpit."""
import json
import subprocess
import time
import urllib.error
import urllib.request

BASE = "http://127.0.0.1:8088"
checks = 0

def request(path):
    try:
        with urllib.request.urlopen(BASE + path, timeout=10) as response:
            return response.status, response.read().decode("utf-8")
    except urllib.error.HTTPError as error:
        return error.code, error.read().decode("utf-8", "replace")

for path in ["/", "/menus.php", "/login.php", "/contact.php"]:
    status, body = request(path)
    assert status == 200 and "<html" in body and "Fatal error" not in body, path
    checks += 1
for path in ["/Config/database.php", "/composer.lock", "/.env", "/tests/orders.php"]:
    status, _ = request(path)
    assert status in (403, 404), (path, status)
    checks += 1

# Aucun message ne quitte Mailpit. La destination est un domaine de test réservé.
subprocess.run([
    "docker", "compose", "exec", "-T", "web", "php", "-r",
    "require 'Config/mail.php'; exit(envoyerEmail('jury@example.test', 'Recette', "
    "'Recette conteneurs', '<p>Message fictif de recette locale.</p>') ? 0 : 1);"
], check=True)
for attempt in range(10):
    with urllib.request.urlopen("http://127.0.0.1:8025/api/v1/messages", timeout=5) as response:
        inbox = json.load(response)
    if any(m["Subject"] == "Recette conteneurs" for m in inbox["messages"]):
        break
    time.sleep(1)
else:
    raise AssertionError("Le message est absent de Mailpit")
checks += 1
print(f"{checks} contrôles HTTP/isolation/SMTP conteneurs réussis.")
