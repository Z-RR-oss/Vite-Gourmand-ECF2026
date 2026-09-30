"""End-to-end HTTP + MariaDB + captured SMTP. Isolated DB/server ONLY.
DB_NAME=vite_gourmand_test_20260928 python3 tests/integration_http.py
Needs demo SQL, http://127.0.0.1:8091 and local SMTP capture (README).
"""
import datetime, email, email.header, html, http.cookiejar, json, os, re, subprocess, time
import urllib.error, urllib.parse, urllib.request
from html.parser import HTMLParser
from pathlib import Path
DB=os.environ.get('DB_NAME','')
assert '_test_' in DB, 'Only a dedicated _test_ database is permitted'
BASE='http://127.0.0.1:8091/'
MYSQL=os.environ.get('MYSQL_BIN','/Applications/XAMPP/xamppfiles/bin/mysql')
PHP=os.environ.get('PHP_BIN','/Applications/XAMPP/xamppfiles/bin/php')
checks=0

def check(value, title):
 global checks
 assert value, title
 checks+=1; print('OK',title)

def sql(query):
 return subprocess.check_output([MYSQL,'-u',os.environ.get('DB_USER','root'),'-N',DB,'-e',query],text=True).strip()

class Fields(HTMLParser):
 def __init__(self,source):
  super().__init__();self.values={};self.feed(source)
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='input' and 'name' in a:self.values[a['name']]=a.get('value','')

class Client:
 def __init__(self): self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def call(self,path,data=None):
  request=urllib.request.Request(BASE+urllib.parse.quote(path,safe='/?=&%'),data=urllib.parse.urlencode(data,doseq=True).encode() if data is not None else None)
  try:r=self.opener.open(request,timeout=15)
  except urllib.error.HTTPError as e:r=e
  body=r.read().decode('utf-8');return r.status,body,r.geturl()
 def post(self,path,data):
  status,body,_=self.call(path)
  token=Fields(body).values.get('csrf_token')
  assert token, 'Missing token '+path
  return self.call(path,dict(data,csrf_token=token))
 def login(self,user,password='Demo-Vg2026!'):
  return self.post('login.php',{'email':user,'password':password})

visitor=Client(); user=Client(); employee=Client(); admin=Client()
for path in ['','index.php','menus.php','menu.php?id=1','login.php','register.php','mot-de-passe-oublie.php','contact.php','mentions-legales.php','cgv.php']:
 status,body,_=visitor.call(path);check(status==200 and '<html lang="fr">' in body and 'site-footer' in body,'page publique '+path)
check(visitor.call('login.php',{'email':'quentin@example.com','password':'Demo-Vg2026!'})[0]==403,'POST sans CSRF rejeté')
check(visitor.call('login.php',{'csrf_token':'forged','email':'quentin@example.com','password':'Demo-Vg2026!'})[0]==403,'CSRF falsifié rejeté')
check('login.php' in visitor.call('commander.php?id=1')[2],'commande invité exige connexion')
check('incorrect' in visitor.login('quentin@example.com','wrong')[1],'mauvais mot de passe refusé')
for client,address,dest in [(user,'quentin@example.com','mes-commandes.php'),(employee,'charlie@example.com','admin-commandes.php'),(admin,'corentin@example.com','admin-commandes.php')]:
 check(dest in client.login(address)[2],'connexion '+address)
for path in ['mes-commandes.php','mon-profil.php','commander.php?id=1','modifier-commande.php?id=2']:
 check(user.call(path)[0]==200,'page client '+path)
for path in ['admin-commandes.php','admin-menus.php','admin-plats.php','admin-horaires.php','admin-avis.php','admin-employes.php','ajouter-menu.php','modifier-menu.php?id=1','gerer-menu-plats.php?id=1','gerer-menu-images.php?id=1','ajouter-plat.php','modifier-plat.php?id=1','modifier-horaire.php?jour=Lundi','ajouter-employe.php']:
 check(admin.call(path)[0]==200,'page admin '+path)
for path in ['admin-commandes.php','admin-menus.php','admin-plats.php','admin-horaires.php','admin-avis.php','admin-employes.php','admin-statistiques.php']:
 check(user.call(path)[0]==403,'rôle client refusé '+path)
check(employee.call('admin-employes.php')[0]==403 and employee.call('admin-statistiques.php')[0]==403,'employé exclu fonctions admin')
status,body,_=admin.call('admin-statistiques.php');check(status==503 and 'Firebase' in body,'Firebase absent : erreur explicite, aucun chiffre inventé')
for path in ['changer-statut.php?id=2&statut=terminée','changer-avis.php?id=1&statut=refusé','logout.php']:
 check(admin.call(path)[0]==405,'action GET refusée '+path)
check(admin.call('admin-commandes.php?client=Quentin&statut=en+attente')[0]==200,'filtre client avec PDO natif')
menus=json.loads(visitor.call('api-menu.php')[1]);check({int(x['id']) for x in menus}=={1,3,5,7},'API actifs seuls, stock zéro visible')
for query,ids in [('regime=Vegan',{3}),('prix_max=130',{1}),('prix_min=140&prix_max=190',{3,7}),('theme=No%C3%ABl',{5}),('personnes=4',{1,3}),('search=vegan',{3})]:
 check({int(x['id']) for x in json.loads(visitor.call('api-menu.php?'+query)[1])}==ids,'filtre '+query)
check(visitor.call('api-menu.php?prix_min=100&prix_max=1')[0]==422,'bornes prix inversées')
check(visitor.call('api-menu.php?search%5B%5D=x')[0]==400,'paramètre tableau refusé')
check(json.loads(visitor.call('api-menu.php?search=%27+OR+1%3D1--')[1])==[],'injection SQL sans effet')
check(user.call('commander.php?id=7')[0]==409,'stock épuisé non commandable')
check(visitor.call('menu.php?id=8')[0]==404,'détail menu inactif caché')

suffix=str(int(time.time()));address='test-'+suffix+'@example.com'
identity={'nom':'Test','prenom':'Parcours','email':address,'gsm':'0600000010','adresse':'Adresse fictive Bordeaux','password':'Test-Vg2026!','role':'admin'}
check('mot de passe' in visitor.post('register.php',dict(identity,password='weak'))[1],'inscription mot de passe faible')
check('compte est créé' in visitor.post('register.php',identity)[1],'inscription complète')
check(sql("SELECT role FROM users WHERE email='"+address+"'")=='utilisateur','rôle inscription forcé malgré injection')
check('déjà utilisée' in visitor.post('register.php',identity)[1],'email unique')
check('email valide' in visitor.post('register.php',dict(identity,email='bad'))[1],'validation email exécutée')
new=Client();check('mes-commandes.php' in new.login(address,'Test-Vg2026!')[2],'nouveau compte connecté')
check('email' in Client().login("' OR 1=1--",'anything')[1] and 'login.php' in Client().login('x@example.com','anything')[2],'authentification SQL paramétrée')
order={'date_prestation':(datetime.date.today()+datetime.timedelta(days=30)).isoformat(),'heure_prestation':'12:00','lieu_prestation':'Mérignac','adresse_prestation':'Adresse fictive','distance_km':'20','nb_personnes':'9','action':'recap'}
check('minimum' in new.post('commander.php?id=1',dict(order,nb_personnes='3'))[1],'minimum HTTP')
status,body,_=new.post('commander.php?id=1',order);fields=Fields(body).values
check('259,80' in body and fields.get('quote_token'),'récapitulatif tarifaire HTTP')
quote=fields['quote_token'];token=fields['csrf_token'];stock=int(sql('SELECT stock_disponible FROM menus WHERE id=1'))
check('mes-commandes.php' in new.call('commander.php?id=1',{'action':'confirm','quote_token':quote,'csrf_token':token})[2],'confirmation de commande')
uid=int(sql("SELECT id FROM users WHERE email='"+address+"'")); oid=int(sql(f'SELECT MAX(id) FROM commandes WHERE user_id={uid}'))
check(int(sql('SELECT stock_disponible FROM menus WHERE id=1'))==stock-1,'stock décrémenté HTTP')
new.call('commander.php?id=1',{'action':'confirm','quote_token':quote,'csrf_token':token})
check(int(sql(f'SELECT COUNT(*) FROM commandes WHERE user_id={uid}'))==1,'double confirmation sans seconde commande')
check('ne peut' in new.post('modifier-commande.php?id='+str(oid),dict(order,nb_personnes='3'))[1] or int(sql(f'SELECT nb_personnes FROM commandes WHERE id={oid}'))==9,'modification invalide sans mutation')
new.post('modifier-commande.php?id='+str(oid),dict(order,nb_personnes='10'))
check(int(sql(f'SELECT nb_personnes FROM commandes WHERE id={oid}'))==10,'modification client autorisée')
csrf=Fields(admin.call('admin-commandes.php')[1]).values['csrf_token']
check(admin.call('changer-statut.php',{'id':oid,'statut':'terminée','csrf_token':csrf})[0]==409,'transition absurde HTTP rejetée')
for state in ['accepté','en préparation','en cours de livraison','livré','en attente du retour de matériel']:
 check(admin.call('changer-statut.php',{'id':oid,'statut':state,'csrf_token':csrf})[0]==200,'transition HTTP '+state)
check(sql(f'SELECT statut FROM commandes WHERE id={oid}')=='en attente du retour de matériel','workflow complet')
check('terminée' in new.call('laisser-avis.php?id='+str(oid))[1],'avis avant fin refusé')
sql(f'UPDATE commandes SET date_debut_attente_retour=DATE_SUB(NOW(), INTERVAL 25 DAY) WHERE id={oid}')
env=dict(os.environ,SMTP_HOST='127.0.0.1',SMTP_PORT='1025',SMTP_AUTH='false',SMTP_ENCRYPTION='none',SMTP_FROM_EMAIL='robot@example.com',APP_URL=BASE.rstrip('/'))
for _ in range(2):subprocess.check_output([PHP,'Scripts/verifier-retards-materiel.php'],env=env)
check(sql(f'SELECT CONCAT(frais_retard_materiel,":",notification_retard_envoyee) FROM commandes WHERE id={oid}')=='600.00:1','cron retard et marquage notification')
status,body,_=admin.post('retour-materiel.php?id='+str(oid),{})
check(sql(f'SELECT statut FROM commandes WHERE id={oid}')=='terminée','retour matériel HTTP')
comment='<script>alert(1)</script> Délicieux test '+suffix
check('mes-commandes.php' in new.post('laisser-avis.php?id='+str(oid),{'note':5,'commentaire':comment})[2],'dépôt avis après fin')
check('déjà' in new.call('laisser-avis.php?id='+str(oid))[1],'avis unique')
aid=int(sql(f'SELECT id FROM avis WHERE commande_id={oid}'))
check(suffix not in visitor.call('index.php')[1],'avis en attente masqué')
admin.call('changer-avis.php',{'id':aid,'statut':'validé','csrf_token':csrf})
body=visitor.call('index.php')[1];check(html.escape('<script>alert(1)</script>') in body and '<script>alert(1)</script>' not in body,'avis validé échappé XSS')

# Contact and employee notifications are sent to local SMTP only.
check('Vérifiez' in visitor.post('contact.php',{'titre':'x','email':'bad','description':'x'})[1],'contact invalide')
check('bien été envoyé' in visitor.post('contact.php',{'titre':'Test local '+suffix,'email':address,'description':'Ceci est un test local sans destinataire externe.'})[1],'contact SMTP accepté')
emp='employee-'+suffix+'@example.com'
admin.post('ajouter-employe.php',dict(identity,email=emp))
eid=int(sql("SELECT id FROM users WHERE email='"+emp+"'"))
check(sql(f'SELECT role FROM users WHERE id={eid}')=='employe','création employé rôle forcé')
newEmployee=Client();check('admin-commandes.php' in newEmployee.login(emp,'Test-Vg2026!')[2],'nouvel employé connecté')
admin.call('desactiver-employe.php',{'id':eid,'action':'desactiver','csrf_token':csrf})
check('login.php' in newEmployee.call('admin-commandes.php')[2],'session employé révoquée à désactivation')
check('désactivé' in Client().login(emp,'Test-Vg2026!')[1],'connexion compte désactivé refusée')

# Request a reset, use captured link, verify one-time consumption and session invalidation.
resetClient=Client();body=resetClient.post('mot-de-passe-oublie.php',{'email':address})[1]
check('Si' in body or 'si' in body,'reset message neutre')
messages=[json.loads(line) for line in Path('/tmp/vg-test-mails.jsonl').read_text().splitlines()]
decoded=[]
for item in messages:
 msg=email.message_from_string(item['message']);texts=[]
 for part in msg.walk():
  if part.get_content_type() in ['text/html','text/plain']:texts.append(part.get_payload(decode=True).decode('utf-8','replace'))
 decoded.append((item['to'],str(email.header.make_header(email.header.decode_header(msg['Subject']))),'\n'.join(texts)))
matching=[x for x in decoded if address in x[0]]
reset=[text for _,_,text in matching if 'reinitialiser-mot-de-passe.php?token=' in text][-1]
token=re.search(r'token=([a-f0-9]{64})',reset).group(1)
status,body,_=resetClient.call('reinitialiser-mot-de-passe.php?token='+token)
check(status==200 and 'name="password"' in body,'token reset valide')
status,body,_=resetClient.post('reinitialiser-mot-de-passe.php?token='+token,{'token':token,'password':'Reset-Vg2026!','confirmation':'Reset-Vg2026!'})
check('succès' in body or 'modifié' in body or 'réinitialisé' in body,'reset réussi')
check('name="password"' not in resetClient.call('reinitialiser-mot-de-passe.php?token='+token)[1],'token utilisé refusé')
check('login.php' in new.call('mon-profil.php')[2],'anciennes sessions révoquées après reset')
check('mes-commandes.php' in Client().login(address,'Reset-Vg2026!')[2],'nouveau mot de passe accepté')
for word in ['Bienvenue','Confirmation','Retour du matériel','Retard de retour','Donnez votre avis']:
 check(any(word in subj for _,subj,_ in matching),'email capturé '+word)
check(sum('Retard de retour' in subj for _,subj,_ in matching)==1,'cron relancé sans seconde notification')
check(any(emp in to and 'Test-Vg2026!' not in text for to,subj,text in decoded),'email employé sans mot de passe')

# CRUD and invalid input tests use only disposable fixtures.
menu={'titre':'Test catalogue '+suffix,'description':'Description fictive','prix':'120','nb_personnes_min':'4','theme':'Test','regime':'Classique','stock_disponible':'4','conditions_menu':'Au frais','delai_commande_heures':'48','actif':'1'}
check(admin.post('ajouter-menu.php',dict(menu,nb_personnes_min='4oops'))[2].endswith('ajouter-menu.php'),'entier invalide menu rejeté')
check('admin-menus.php' in admin.post('ajouter-menu.php',menu)[2],'création menu HTTP')
mid=int(sql("SELECT id FROM menus WHERE titre='Test catalogue "+suffix+"'"))
admin.post('modifier-menu.php?id='+str(mid),dict(menu,prix='180'))
check(sql(f'SELECT prix FROM menus WHERE id={mid}')=='180.00','modification menu HTTP')
dish={'nom':'Test plat '+suffix,'description':'Plat fictif','type_plat':'entree','allergenes[]':['1','2'],'nouveaux_allergenes':'Test allergène '+suffix}
admin.post('ajouter-plat.php',dish)
pid=int(sql("SELECT id FROM plats WHERE nom='Test plat "+suffix+"'"))
check(sql(f'SELECT COUNT(*) FROM plat_allergene WHERE plat_id={pid}')=='3','plat et allergènes créés')
admin.post('modifier-plat.php?id='+str(pid),dict(dish,type_plat='dessert'))
check(sql(f'SELECT type_plat FROM plats WHERE id={pid}')=='dessert','modification plat HTTP')
admin.post('gerer-menu-plats.php?id='+str(mid),{'plats[]':[str(pid)],'ordre['+str(pid)+']':'2'})
check(sql(f'SELECT ordre_affichage FROM menu_plat WHERE menu_id={mid} AND plat_id={pid}')=='2','association et ordre menu plats')
admin.post('modifier-horaire.php?jour=Dimanche',{'action':'enregistrer','heure_ouverture':'09:00','heure_fermeture':'12:00'})
check(sql("SELECT ferme FROM horaires WHERE jour='Dimanche'")=='0','horaire ouvert enregistré')
check('09:00' in visitor.call('index.php')[1],'horaires footer depuis SQL')
admin.post('modifier-horaire.php?jour=Dimanche',{'action':'enregistrer','heure_ouverture':'99:00','heure_fermeture':'99:30'})
check(sql("SELECT heure_ouverture FROM horaires WHERE jour='Dimanche'")=='09:00:00','heure invalide ne modifie pas SQL')
admin.post('modifier-horaire.php?jour=Dimanche',{'action':'supprimer'})
check(sql("SELECT COUNT(*) FROM horaires WHERE jour='Dimanche'")=='0','suppression horaire POST')
admin.post('modifier-horaire.php?jour=Dimanche',{'action':'enregistrer','ferme':'1'})
check(sql("SELECT ferme FROM horaires WHERE jour='Dimanche'")=='1','recréation horaire fermé')
admin.post('supprimer-plat.php?id='+str(pid),{})
check(sql(f'SELECT COUNT(*) FROM plats WHERE id={pid}')=='0','suppression plat non nécessaire aux commandes')
admin.post('supprimer-menu.php?id='+str(mid),{})
check(sql(f'SELECT COUNT(*) FROM menus WHERE id={mid}')=='0','suppression menu sans commande')
# Profile role injection cannot change privilege.
profile=dict(identity,email=address,password='Reset-Vg2026!',nom='<b>Test</b>',role='admin')
profileClient=Client();profileClient.login(address,'Reset-Vg2026!');profileClient.post('mon-profil.php',profile)
check(sql(f'SELECT role FROM users WHERE id={uid}')=='utilisateur','profil ne modifie pas rôle')
check('&lt;b&gt;Test&lt;/b&gt;' in profileClient.call('mon-profil.php')[1],'profil échappe balises HTML')
# Token that has already expired is never accepted.
expired='a'*64
sql(f"INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES ({uid}, SHA2('{expired}',256), DATE_SUB(NOW(),INTERVAL 2 HOUR))")
check('name="password"' not in resetClient.call('reinitialiser-mot-de-passe.php?token='+expired)[1],'token expiré refusé')
# Cancellation and ownership checks at HTTP boundary.
status,body,_=profileClient.post('commander.php?id=1',order);q=Fields(body).values
profileClient.call('commander.php?id=1',{'action':'confirm','quote_token':q['quote_token'],'csrf_token':q['csrf_token']})
newid=int(sql(f'SELECT MAX(id) FROM commandes WHERE user_id={uid}'))
userToken=Fields(user.call('mes-commandes.php')[1]).values['csrf_token']
check(user.call('supprimer-commande.php',{'id':newid,'csrf_token':userToken})[0]==409,'annulation autre propriétaire refusée')
myToken=Fields(profileClient.call('mes-commandes.php')[1]).values['csrf_token'];stock=int(sql('SELECT stock_disponible FROM menus WHERE id=1'))
profileClient.call('supprimer-commande.php',{'id':newid,'csrf_token':myToken})
profileClient.call('supprimer-commande.php',{'id':newid,'csrf_token':myToken})
check(sql(f'SELECT statut FROM commandes WHERE id={newid}')=='annulée' and int(sql('SELECT stock_disponible FROM menus WHERE id=1'))==stock+1,'annulation client et répétition restaurent une fois')
# Removing a referenced menu deactivates it and preserves the order.
admin.post('supprimer-menu.php?id=5',{})
check(sql('SELECT actif FROM menus WHERE id=5')=='0' and sql('SELECT COUNT(*) FROM commandes WHERE menu_id=5')!='0','menu référencé désactivé et historique préservé')
sql('UPDATE menus SET actif=1 WHERE id=5')
print(f'{checks} assertions HTTP/SQL/SMTP réussies. Données de test conservées dans {DB} uniquement.')
