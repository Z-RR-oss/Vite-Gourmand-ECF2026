"""Régressions HTTP : erreurs accessibles, mutations refusées et méthodes autorisées.

N'utilise que les comptes fictifs d'une base _test_ et le serveur local 8091.
"""
import http.cookiejar
import os
import re
import urllib.error
import urllib.parse
import urllib.request

assert '_test_' in os.environ.get('DB_NAME', ''), 'Base de recette obligatoire'
BASE = 'http://127.0.0.1:8091/'
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
checks = 0


def check(condition, title):
    global checks
    assert condition, title
    checks += 1
    print('OK', title)


def call(path, data=None, method=None):
    request = urllib.request.Request(BASE + path, data=None if data is None else urllib.parse.urlencode(data, doseq=True).encode(), method=method)
    try:
        response = opener.open(request, timeout=15)
    except urllib.error.HTTPError as error:
        response = error
    return response.status, response.read().decode(), response.geturl()


def post(path, data):
    _, body, _ = call(path)
    token = re.search(r'name="csrf_token" value="([^"]+)"', body).group(1)
    return call(path, dict(data, csrf_token=token))


check(post('login.php', {'email': 'corentin@example.com', 'password': 'Demo-Vg2026!'})[0] == 200, 'compte administrateur fictif')
for path in ['modifier-menu.php', 'modifier-plat.php', 'supprimer-menu.php', 'supprimer-plat.php', 'gerer-menu-plats.php']:
    for value in ['1.5', '1e1', '-1', 'abc']:
        status, body, _ = call(path + '?id=' + value)
        check(status == 400 and '<main' in body and '<html lang="fr">' in body, path + ': ID invalide et erreur structurée')
    check(call(path + '?id=999999999')[0] == 404, path + ': ressource absente')
for path in ['ajouter-menu.php', 'modifier-menu.php?id=1', 'ajouter-plat.php', 'modifier-plat.php?id=1', 'modifier-horaire.php?jour=Lundi']:
    check(call(path, method='PUT')[0] == 405, path + ': méthode non autorisée')
for path in ['ajouter-menu.php', 'modifier-menu.php?id=1']:
    status, body, _ = post(path, {'titre': 'Titre conservé', 'description': 'Description conservée', 'prix': '1.001', 'nb_personnes_min': '4abc', 'theme': 'Classique', 'regime': 'Classique', 'stock_disponible': '2', 'delai_commande_heures': '48'})
    check(status == 422, path + ': statut de validation')
    check('value="Titre conservé"' in body and 'Description conservée' in body, path + ': saisies conservées')
    check('href="#prix"' in body and 'href="#nb_personnes_min"' in body, path + ': résumé lié aux champs')
    check('aria-invalid="true" aria-describedby="prix-error"' in body and 'id="prix-error"' in body, path + ': erreur associée au prix')
for path in ['ajouter-plat.php', 'modifier-plat.php?id=1']:
    status, body, _ = post(path, {'nom': 'Plat conservé', 'type_plat': 'invalide', 'allergenes': '1'})
    check(status == 422 and 'value="Plat conservé"' in body, path + ': rejet et conservation')
    check('<fieldset id="allergenes"' in body and '<legend>Allergènes existants</legend>' in body, path + ': groupe nommé')
    check('href="#allergenes"' in body and 'id="allergenes-error"' in body, path + ': erreur des allergènes liée')
check(call('desactiver-employe.php')[0] == 405, 'désactivation uniquement par POST')
check(call('modifier-horaire.php?jour%5B%5D=Lundi')[0] == 400, 'jour tableau refusé sans erreur technique')
check(post('modifier-horaire.php?jour=Lundi', {'heure_ouverture[]': '09:00', 'heure_fermeture': '18:00'})[0] == 400, 'heure tableau refusée sans erreur technique')
print(f'{checks} contrôles HTTP qualité réussis.')
