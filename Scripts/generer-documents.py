"""Generate pedagogical PDFs and final-state wireframes. Dev-only: reportlab, Pillow.
Run from any directory with a Python environment providing those two packages.
Screenshots in Livrables-jury/Mockups must already exist; no browser is automated here.
"""
from pathlib import Path
import sys
from html import escape
from PIL import Image as PILImage, ImageDraw, ImageFont
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib import colors
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Image, PageBreak
from reportlab.lib.styles import ParagraphStyle

ROOT = Path(__file__).resolve().parents[1]
DOC = ROOT / 'docs'
DELIVERABLES = ROOT / 'Livrables-jury'
FONT = Path('/System/Library/Fonts/Supplemental/Arial.ttf')
if FONT.exists():
    pdfmetrics.registerFont(TTFont('Body', str(FONT)))
    pdfmetrics.registerFont(TTFont('Bold', str(FONT.with_name('Arial Bold.ttf'))))
    BODY, BOLD = 'Body', 'Bold'
else:
    BODY, BOLD = 'Helvetica', 'Helvetica-Bold'
BURGUNDY, CREAM, INK = '#672f3e', '#f7f3eb', '#292c25'

def wireframe(name, mobile):
    w,h = (390,844) if mobile else (1440,1000)
    im = PILImage.new('RGB',(w,h),'white'); d=ImageDraw.Draw(im)
    font = ImageFont.truetype(str(FONT),14 if mobile else 24) if FONT.exists() else ImageFont.load_default()
    svg=[f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}">',f'<rect width="{w}" height="{h}" fill="white"/>']
    def box(x,y,bw,bh,text,fill='#f0f0f0'):
        d.rectangle((x,y,x+bw,y+bh),fill=fill,outline='#777',width=1)
        d.text((x+12,y+12),text,font=font,fill='#333')
        svg.append(f'<rect x="{x}" y="{y}" width="{bw}" height="{bh}" fill="{fill}" stroke="#777"/><text x="{x+12}" y="{y+30}" font-size="{14 if mobile else 24}" font-family="Arial">{escape(text)}</text>')
    if mobile:
        box(20,20,350,65,'Logo / Menu')
        if name=='accueil':
            box(20,105,350,120,'Promesse / titre / présentation')
            box(20,245,350,52,'Action : découvrir les menus')
            box(20,317,350,230,'Illustration de la table')
            box(20,567,350,105,'Histoire / preuves / avis')
            box(20,692,350,130,'Menus puis footer / horaires')
        elif name=='detail-menu':
            box(20,105,350,210,'Galerie du menu')
            box(20,335,350,115,'Titre / prix / minimum / stock')
            box(20,470,350,110,'Conditions mises en évidence')
            box(20,600,350,52,'Commander')
            box(20,672,350,150,'Plats / allergènes / footer')
        else:
            box(20,105,350,145,'Titre / texte de contact')
            for y,t in [(270,'Objet'),(350,'Email'),(430,'Description du projet')]:box(20,y,350,60 if y<430 else 160,t)
            box(20,610,350,52,'Envoyer le message')
            box(20,682,350,140,'Horaires / liens légaux')
    else:
        box(60,30,1320,90,'Logo / navigation / espace personnel')
        if name=='accueil':
            box(60,150,620,350,'Titre / présentation / appel à action')
            box(720,150,660,350,'Illustration de la table')
            box(60,530,1320,100,'Histoire et professionnalisme')
            box(60,660,1320,170,'Menus / filtres / avis validés')
        elif name=='detail-menu':
            box(60,150,640,480,'Galerie principale')
            box(740,150,640,140,'Titre / prix / minimum / stock')
            box(740,320,640,160,'Conditions et délai')
            box(740,510,640,80,'Commander')
            box(60,660,1320,170,'Entrées / plats / desserts / allergènes')
        else:
            box(60,150,570,550,'Présentation / informations pratiques')
            for y,t,bh in [(150,'Objet',100),(280,'Email',100),(410,'Description',190),(630,'Envoyer',70)]:box(690,y,690,bh,t)
            box(60,730,1320,100,'Informations sur les données et erreurs')
        box(60,860,1320,100,'Footer : contact / liens légaux / horaires lundi-dimanche')
    file=f'{name}-'+('mobile' if mobile else 'desktop')
    dest=DELIVERABLES/'Wireframes';dest.mkdir(exist_ok=True)
    im.save(dest/(file+'.png')); (dest/(file+'.svg')).write_text(''.join(svg)+'</svg>')

if '--manual-only' not in sys.argv:
    for name in ['accueil','detail-menu','contact']:
        for mobile in [False,True]:wireframe(name,mobile)

styles={
    'p':ParagraphStyle('p',fontName=BODY,fontSize=10.5,leading=16,textColor=colors.HexColor(INK),spaceAfter=11),
    'h':ParagraphStyle('h',fontName=BOLD,fontSize=17,leading=22,textColor=colors.HexColor(BURGUNDY),spaceBefore=17,spaceAfter=11,keepWithNext=True),
    'title':ParagraphStyle('title',fontName=BOLD,fontSize=34,leading=40,textColor=colors.HexColor(BURGUNDY),spaceAfter=22),
    'small':ParagraphStyle('small',fontName=BODY,fontSize=9,leading=13,textColor=colors.HexColor('#62665b'),spaceAfter=10),
}
def footer(c,doc):
    c.setFont(BODY,8);c.setFillColor(colors.HexColor('#62665b'))
    c.drawString(44,28,'VITE & GOURMAND  /  MANUEL UTILISATEUR  /  ECF 2026')
    c.drawRightString(A4[0]-44,28,str(doc.page))
story=[Spacer(1,40),Paragraph('Vite & Gourmand',styles['title']),Paragraph('Manuel utilisateur',styles['h']),Paragraph('Les parcours client, employé et administrateur',styles['p']),Spacer(1,15)]
im=PILImage.open(DELIVERABLES/'Mockups/accueil-desktop.png');story.append(Image(str(DELIVERABLES/'Mockups/accueil-desktop.png'),width=500,height=500*im.height/im.width))
story += [Spacer(1,20),Paragraph('Édition de mise en ligne · 30 septembre 2026',styles['p']),Paragraph('Site publié sur alwaysdata, avec MySQL et Firebase réels. Les mots de passe du site public sont remis séparément dans un fichier privé.',styles['small']),PageBreak()]
for block in (DOC/'manuel-utilisateur.md').read_text().split('\n\n'):
    if block.startswith('# '):continue
    if block.startswith('## '):story.append(Paragraph(escape(block[3:]),styles['h']))
    else:story.append(Paragraph(escape(block).replace('\n',' '),styles['p']))
if '--charte-only' not in sys.argv:
    SimpleDocTemplate(str(DOC/'manuel-utilisateur.pdf'),pagesize=A4,rightMargin=44,leftMargin=44,topMargin=40,bottomMargin=50,title='Vite & Gourmand - Manuel utilisateur',author='Vite & Gourmand - Projet ECF').build(story,onFirstPage=footer,onLaterPages=footer)

if '--manual-only' in sys.argv:
    sys.exit(0)

W,H=landscape(A4);c=canvas.Canvas(str(DOC/'charte-graphique.pdf'),pagesize=(W,H));c.setTitle('Vite & Gourmand - Charte, wireframes et maquettes');c.setAuthor('Vite & Gourmand - Projet ECF')
page=0
def start(title,sub):
    global page
    page+=1;c.setFillColor(colors.HexColor(CREAM));c.rect(0,0,W,H,fill=1,stroke=0)
    c.setFillColor(colors.HexColor(BURGUNDY));c.setFont(BOLD,27);c.drawString(38,H-60,title)
    c.setFont(BODY,10);c.setFillColor(colors.HexColor('#62665b'));c.drawString(38,H-85,sub)
    c.setFont(BODY,8);c.drawString(38,22,'VITE & GOURMAND / CHARTE & CONCEPTION / 28 SEPTEMBRE 2026');c.drawRightString(W-38,22,str(page))
def para(text,x,y,width=760,size=12):
    p=Paragraph(escape(text),ParagraphStyle('temp',fontName=BODY,fontSize=size,leading=size*1.5,textColor=colors.HexColor(INK)))
    _,ph=p.wrap(width,800);p.drawOn(c,x,y-ph);return y-ph-16
def pic(path,x,y,w,h):
    c.drawImage(str(path),x,y,width=w,height=h,preserveAspectRatio=True,anchor='c',mask='auto')
start('La table à partager','Une identité de maison bordelaise : gastronomie, convivialité et sobriété.')
c.saveState();c.translate(52,418);c.scale(1.5,-1.5)
c.setFillColor(colors.HexColor(BURGUNDY));c.circle(32,32,32,fill=1,stroke=0)
c.setStrokeColor(colors.HexColor(CREAM));c.setLineWidth(1);c.circle(32,32,23,stroke=1,fill=0)
c.setLineWidth(2.5);c.setLineCap(1);c.setLineJoin(1)
p=c.beginPath();p.moveTo(20,23);p.lineTo(28,46);p.lineTo(36,23)
p.moveTo(43,28);p.curveTo(32,20,32,51,44,41);p.lineTo(44,33);p.lineTo(38,33);c.drawPath(p)
c.setFillColor(colors.HexColor('#d9b788'));p=c.beginPath();p.moveTo(31,14);p.curveTo(35,9,39,11,40,13);p.curveTo(36,17,32,17,31,14);p.close();c.drawPath(p,fill=1,stroke=0);c.restoreState()
y=para('Un sceau rond, un monogramme et une feuille : le logo associe la signature de la maison et une évocation végétale. La version vectorielle exacte est conservée dans Public/assets/images/embleme.svg.',177,420,600)
y=para('L’univers évite les codes d’une plateforme générique. Grandes compositions éditoriales, titres à empattements, illustrations de table et couleurs inspirées du vin, des nappes et des herbes.',177,y,600)
y=para('Les illustrations SVG du projet sont des créations graphiques locales. Les mockups qui suivent sont des exports de l’interface développée; les wireframes sont une reconstruction documentaire de son organisation finale.',38,235,765)
para('Livrables : 3 wireframes desktop + 3 mobile, et 3 mockups desktop + 3 mobile. Sources et captures : Livrables-jury/Wireframes et Livrables-jury/Mockups.',38,y,765)
c.showPage()
start('Palette et typographies','Des couleurs partagées par le catalogue, les formulaires et les espaces de gestion.')
for i,(label,hexa) in enumerate([('Bordeaux','#672f3e'),('Sauge','#526b57'),('Blé','#d9b788'),('Crème','#f7f3eb'),('Encre','#292c25'),('Texte discret','#62665b')]):
    x=38+i*130;c.setFillColor(colors.HexColor(hexa));c.rect(x,365,112,95,fill=1,stroke=0);c.setFillColor(colors.HexColor(INK));c.setFont(BOLD,10);c.drawString(x,342,label);c.setFont(BODY,10);c.drawString(x,323,hexa)
y=para('Titres : Iowan Old Style, puis Palatino Linotype, Book Antiqua ou Georgia. Texte et contrôles : police système (-apple-system, BlinkMacSystemFont, Segoe UI, sans-serif). Aucune police distante ni aucun suivi tiers.',38,280)
y=para('Titres fluides avec clamp(), corps de 16 px et interligne de 1,65. Les accents bordeaux guident les actions; la sauge structure les informations; le blé reste décoratif. Ne pas utiliser le blé comme texte petit sur crème.',38,y)
para('Le logo est conservé dans ses proportions, avec un espace libre autour. Ne pas l’étirer ni remplacer sa palette. Le sceau est accompagné du nom lisible « Vite & Gourmand » dans la navigation.',38,y)
c.showPage()
start('Composants et usages','Une même grammaire visuelle dans tous les parcours.')
y=para('Boutons : hauteur minimale 46 px, coins de 6 px, action principale bordeaux et secondaire discrète. Les libellés décrivent l’action. Les états désactivés sont visuellement distincts et accompagnés de texte métier.',38,455)
y=para('Formulaires : labels visibles, champs larges, espace entre groupes, validation serveur et messages compréhensibles. Aucune information essentielle dans le seul placeholder. Erreurs et succès associent texte, bordure et couleur.',38,y)
y=para('Navigation : liens selon le rôle, bouton Menu sur petit écran, ouverture indiquée par aria-expanded, fermeture par Échap, lien « Aller au contenu » visible au focus. Focus de 3 px avec décalage de 4 px.',38,y)
y=para('Catalogue : image de couverture, régime, minimum, titre, description et prix de forfait. Conditions du détail mises en évidence. Gestion : panneaux, filtres et badges textuels cohérents, tableaux défilables si nécessaire.',38,y)
y=para('Responsive : colonnes qui se replient, largeur de lecture limitée, contrôles accessibles au toucher. Les tests à 390/768/1 440 px sont consignés dans recette-responsive.json. Audit RGAA pragmatique, sans certification exhaustive.',38,y)
c.showPage()
for kind,folder in [('Wireframe','Wireframes'),('Mockup','Mockups')]:
    for name,title in [('accueil','Accueil'),('detail-menu','Détail du menu'),('contact','Contact')]:
        start(f'{kind} / {title}','Desktop 1 440 × 1 000 px et mobile 390 × 844 px · écran initial, contenu poursuivi au défilement.')
        pic(DELIVERABLES/folder/(name+'-desktop.png'),38,110,560,389)
        pic(DELIVERABLES/folder/(name+'-mobile.png'),628,106,176,381)
        c.setFont(BODY,9);c.setFillColor(colors.HexColor(INK));c.drawString(38,78,'Desktop : hiérarchie et colonnes');c.drawString(628,78,'Mobile : lecture verticale')
        c.showPage()
c.save()
print('Charte graphique et 6 wireframes SVG/PNG créés.' if '--charte-only' in sys.argv else 'PDF créés : manuel-utilisateur.pdf et charte-graphique.pdf; 6 wireframes SVG/PNG.')
