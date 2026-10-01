"""Focused HTML/accessibility regression checks on the isolated local fixture.

Run after importing the demo SQL, before integration_http.py changes its fixtures:
DB_NAME=vite_gourmand_test_final_20261001 python3 tests/accessibility_http.py
This suite does not claim full RGAA conformity or simulate a screen reader.
"""
import atexit
import subprocess
import http.cookiejar
import os
import urllib.error
import urllib.parse
import urllib.request
from collections import Counter
from html.parser import HTMLParser

assert '_test_' in os.environ.get('DB_NAME', ''), 'Use a dedicated test database'
BASE = 'http://127.0.0.1:8091/'
CHECKS = 0
MYSQL = os.environ.get('MYSQL_BIN', '/Applications/XAMPP/xamppfiles/bin/mysql')

def sql(query):
    return subprocess.check_output([MYSQL, '-u', os.environ.get('DB_USER', 'root'), '-N', os.environ['DB_NAME'], '-e', query], text=True).strip()

# A completed order without an existing review is needed for the validation test.
review_id = int(sql("INSERT INTO commandes (user_id,menu_id,nb_personnes,prix_total,statut) VALUES (4,1,4,120,'terminée'); SELECT LAST_INSERT_ID();"))
atexit.register(lambda: sql(f'DELETE FROM commandes WHERE id={review_id}'))


class Node:
    def __init__(self, tag='', attrs=()):
        self.tag, self.attrs, self.children = tag, dict(attrs), []

    def text(self):
        return ''.join(child.text() if isinstance(child, Node) else child for child in self.children)


class Document(HTMLParser):
    VOID = {'input', 'img', 'meta', 'link', 'br', 'hr', 'source', 'wbr', 'area', 'base', 'embed', 'param', 'col', 'track'}

    def __init__(self, source):
        super().__init__(convert_charrefs=True)
        self.nodes, self.stack = [], [Node()]
        self.feed(source)

    def handle_starttag(self, tag, attrs):
        node = Node(tag, attrs)
        self.nodes.append(node)
        self.stack[-1].children.append(node)
        if tag not in self.VOID:
            self.stack.append(node)

    def handle_endtag(self, tag):
        for index in range(len(self.stack) - 1, 0, -1):
            if self.stack[index].tag == tag:
                del self.stack[index:]
                break

    def handle_data(self, value):
        self.stack[-1].children.append(value)

    def find(self, tag):
        return [node for node in self.nodes if node.tag == tag]


class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def call(self, path, data=None):
        body = None if data is None else urllib.parse.urlencode(data).encode()
        try:
            response = self.opener.open(BASE + path, data=body, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, Document(response.read().decode())

    def post(self, path, data):
        _, doc = self.call(path)
        token = next(n.attrs['value'] for n in doc.find('input') if n.attrs.get('name') == 'csrf_token')
        return self.call(path, dict(data, csrf_token=token))

    def login(self, email):
        status, _ = self.post('login.php', {'email': email, 'password': 'Demo-Vg2026!'})
        assert status == 200


def check(value, message):
    global CHECKS
    assert value, message
    CHECKS += 1


def inspect(client, path, expected=200):
    status, doc = client.call(path)
    check(status == expected, f'{path}: HTTP {status}')
    check(len(doc.find('h1')) == 1, path + ': one main heading')
    check(len(doc.find('title')) == 1 and doc.find('title')[0].text().strip(), path + ': page title')
    check(doc.find('html')[0].attrs.get('lang') == 'fr', path + ': language')
    ids = [n.attrs['id'] for n in doc.nodes if 'id' in n.attrs]
    check(all(count == 1 for count in Counter(ids).values()), path + ': unique identifiers')
    check(all(ref in ids for node in doc.nodes for attribute in ['aria-describedby', 'aria-labelledby', 'aria-controls'] for ref in node.attrs.get(attribute, '').split()), path + ': valid ARIA references')
    labels = {n.attrs.get('for'): n.text().strip() for n in doc.find('label')}
    for node in doc.nodes:
        if node.tag in {'input', 'select', 'textarea'} and node.attrs.get('type') not in {'hidden', 'submit', 'button'}:
            label = labels.get(node.attrs.get('id'), '') or node.attrs.get('aria-label', '')
            check(bool(label), path + ': labelled field ' + str(node.attrs.get('name')))
            if 'required' in node.attrs:
                check('obligatoire' in label.lower(), path + ': required field ' + str(node.attrs.get('name')))
    check(all('alt' in n.attrs for n in doc.find('img')), path + ': image alternatives')
    check(all('scope' in n.attrs for n in doc.find('th')), path + ': table headers')
    check(len(doc.find('caption')) == len(doc.find('table')), path + ': table titles')
    print('OK', path)
    return doc


visitor, customer, admin = Client(), Client(), Client()
customer.login('quentin@example.com')
admin.login('corentin@example.com')
for path in ['index.php', 'menus.php', 'menu.php?id=1', 'contact.php', 'login.php', 'register.php', 'mot-de-passe-oublie.php', 'reinitialiser-mot-de-passe.php', 'mentions-legales.php', 'cgv.php', 'plan-du-site.php', 'accessibilite.php']:
    inspect(visitor, path)
for path in ['mes-commandes.php', 'mon-profil.php', 'commander.php?id=1', 'modifier-commande.php?id=2', f'laisser-avis.php?id={review_id}']:
    inspect(customer, path)
for path in ['admin-commandes.php', 'admin-menus.php', 'admin-plats.php', 'admin-horaires.php', 'admin-avis.php', 'admin-employes.php', 'ajouter-menu.php', 'modifier-menu.php?id=1', 'ajouter-plat.php', 'modifier-plat.php?id=1', 'modifier-horaire.php?jour=Lundi', 'ajouter-employe.php', 'gerer-menu-plats.php?id=1', 'gerer-menu-images.php?id=1']:
    inspect(admin, path)
inspect(admin, 'admin-statistiques.php', 503)
# Invalid submissions must expose a useful error and retain non-secret values.
for path, client, data in [
    ('contact.php', visitor, {'titre': 'x', 'email': 'bad', 'description': 'x'}),
    ('register.php', visitor, {'nom': '', 'prenom': '', 'email': 'bad', 'password': 'weak'}),
    ('mon-profil.php', customer, {'nom': 'Essai conservé', 'prenom': 'Recette', 'email': 'bad', 'gsm': '0600000000', 'adresse': 'Adresse de recette'}),
    (f'laisser-avis.php?id={review_id}', customer, {'note': '5', 'commentaire': 'x' * 3001}),
]:
    status, doc = client.post(path, data)
    check(status == 200, path + ': recoverable validation error')
    check(any(n.attrs.get('role') == 'alert' and n.text().strip() for n in doc.nodes), path + ': announced error')
    if path == 'mon-profil.php':
        check(any(n.attrs.get('id') == 'nom' and n.attrs.get('value') == 'Essai conservé' for n in doc.find('input')), 'Profile values preserved after rejection')
    if path.startswith('laisser-avis'):
        check(doc.find('textarea')[0].text() == data['commentaire'], 'Review text preserved after rejection')
        check(any(n.attrs.get('value') == '5' and 'selected' in n.attrs for n in doc.find('option')), 'Review rating preserved')
print(f'{CHECKS} HTML/validation checks passed; not a full RGAA audit.')
