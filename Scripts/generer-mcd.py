"""Generate the conceptual model as a vector PDF and editable SVG views.

Development dependency: reportlab. Run with ``python3 Scripts/generer-mcd.py``.
Coordinates are in points on A3 landscape pages; no database access is needed.
"""
from pathlib import Path

from reportlab.graphics import renderPDF, renderSVG
from reportlab.graphics.shapes import Drawing, Line, PolyLine, Rect, String
from reportlab.lib.colors import HexColor, white
from reportlab.lib.pagesizes import A3, landscape
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen.canvas import Canvas

ROOT = Path(__file__).resolve().parents[1]
WIDTH, HEIGHT = landscape(A3)
INK, ACCENT, GREEN, PAPER = map(HexColor, ['#292c25', '#672f3e', '#526b57', '#f7f3eb'])
BODY, BOLD = 'Helvetica', 'Helvetica-Bold'
FONT = Path('/System/Library/Fonts/Supplemental/Arial.ttf')
if FONT.exists():
    pdfmetrics.registerFont(TTFont('McdBody', str(FONT)))
    pdfmetrics.registerFont(TTFont('McdBold', str(FONT.with_name('Arial Bold.ttf'))))
    BODY, BOLD = 'McdBody', 'McdBold'


def text(drawing, x, y, value, size=12, bold=False, color=INK, anchor='start'):
    drawing.add(String(x, y, value, fontName=BOLD if bold else BODY,
                       fontSize=size, fillColor=color, textAnchor=anchor))


def page(number, title, subtitle):
    drawing = Drawing(WIDTH, HEIGHT)
    drawing.add(Rect(0, 0, WIDTH, HEIGHT, fillColor=white, strokeColor=None))
    text(drawing, 45, 790, 'VITE & GOURMAND  /  MODÈLE CONCEPTUEL DE DONNÉES', 12, True, ACCENT)
    text(drawing, 45, 748, title, 29, True)
    text(drawing, 45, 721, subtitle, 12)
    drawing.add(Line(45, 48, WIDTH - 45, 48, strokeColor=GREEN, strokeWidth=1))
    text(drawing, 45, 28, '01 octobre 2026 | Identifiants soulignés | Cardinalités près de chaque entité', 10)
    text(drawing, WIDTH - 45, 28, f'{number} / 3', 10, anchor='end')
    return drawing


def entity(drawing, x, y, width, title, attributes, step=17):
    height = 48 + len(attributes) * step
    drawing.add(Rect(x, y, width, height, fillColor=white, strokeColor=ACCENT, strokeWidth=1.4))
    drawing.add(Rect(x, y + height - 33, width, 33, fillColor=ACCENT, strokeColor=None))
    text(drawing, x + 12, y + height - 22, title, 12, True, white)
    for index, attribute in enumerate(attributes):
        baseline = y + height - 53 - index * step
        text(drawing, x + 12, baseline, attribute, 11)
        if index == 0:
            length = pdfmetrics.stringWidth(attribute, BODY, 11)
            drawing.add(Line(x + 12, baseline - 2, x + 12 + length, baseline - 2, strokeColor=INK))
    return height


def association(drawing, x, y, width, title, attribute=None):
    height = 64 if attribute else 48
    drawing.add(Rect(x, y, width, height, rx=22, ry=22, fillColor=PAPER, strokeColor=GREEN, strokeWidth=1.3))
    text(drawing, x + width / 2, y + height - 29, title, 11, True, anchor='middle')
    if attribute:
        text(drawing, x + width / 2, y + 14, attribute, 10, anchor='middle')


def link(drawing, points, cardinality, label):
    drawing.add(PolyLine([coordinate for point in points for coordinate in point],
                         strokeColor=GREEN, strokeWidth=1.4))
    # Explicit label positions keep cardinalities clear of connecting lines.
    text(drawing, *label, cardinality, 12, True, GREEN)


def catalogue():
    d = page(1, 'Catalogue et composition', 'Entités métier et associations : les tables de liaison SQL ne sont pas des entités supplémentaires.')
    entity(d, 65, 400, 265, 'MENU', ['Identifiant menu', 'Titre', 'Description', 'Prix forfaitaire', 'Nombre minimum de convives', 'Thème', 'Régime', 'Stock disponible', 'Conditions', 'Délai de commande', 'Actif'])
    entity(d, 800, 460, 265, 'PLAT', ['Identifiant plat', 'Nom', 'Description', 'Type de plat'])
    association(d, 450, 490, 210, 'COMPOSER', 'Ordre d’affichage')
    link(d, [(330, 522), (450, 522)], '0,N', (348, 534))
    link(d, [(660, 522), (800, 522)], '0,N', (754, 534))
    association(d, 110, 285, 175, 'ILLUSTRER')
    entity(d, 65, 100, 265, 'IMAGE', ['Identifiant image', 'Chemin de l’image', 'Texte alternatif'])
    link(d, [(197, 400), (197, 333)], '0,N', (209, 375))
    link(d, [(197, 285), (197, 199)], '1,1', (209, 212))
    association(d, 845, 285, 175, 'CONTENIR')
    entity(d, 800, 117, 265, 'ALLERGÈNE', ['Identifiant allergène', 'Nom'])
    link(d, [(932, 460), (932, 333)], '0,N', (944, 430))
    link(d, [(932, 285), (932, 199)], '0,N', (944, 212))
    text(d, 395, 370, 'Lecture : un menu peut utiliser plusieurs plats;', 12)
    text(d, 395, 351, 'un plat peut appartenir à plusieurs menus.', 12)
    text(d, 395, 226, 'Les minima à zéro autorisent un catalogue', 12)
    text(d, 395, 207, 'en cours de préparation et des plats non affectés.', 12)
    text(d, 395, 178, 'Une image appartient à un seul menu.', 12)
    text(d, 395, 149, 'Thème et régime restent des propriétés du menu,', 12)
    text(d, 395, 130, 'conformément au périmètre implémenté.', 12)
    return d


def orders():
    d = page(2, 'Commandes, avis et historique', 'Une commande concerne un seul menu. Son prix et ses informations de prestation sont conservés lors de la commande.')
    entity(d, 45, 443, 245, 'UTILISATEUR', ['Identifiant utilisateur', 'Nom', 'Prénom', 'Email', 'Empreinte du mot de passe', 'Rôle', 'Téléphone', 'Adresse', 'Actif', 'Date de création'], step=16)
    entity(d, 470, 332, 265, 'COMMANDE', ['Identifiant commande', 'Nombre de personnes', 'Prix total', 'Date de prestation', 'Heure de prestation', 'Lieu de prestation', 'Adresse de prestation', 'Distance', 'Frais de livraison', 'Remise', 'Statut', 'Mode de contact pour annulation', 'Motif d’annulation', 'Date d’annulation', 'Début d’attente du matériel', 'Date de retour du matériel', 'Matériel retourné', 'Frais de retard', 'Notification de retard envoyée', 'Date de notification de retard', 'Date de création', 'Date de modification'], step=14)
    entity(d, 935, 508, 210, 'MENU (VUE 1)', ['Identifiant menu', 'Attributs : voir vue 1'])
    association(d, 330, 535, 100, 'PASSER')
    link(d, [(290, 559), (330, 559)], '0,N', (294, 575))
    link(d, [(430, 559), (470, 559)], '1,1', (437, 575))
    association(d, 780, 535, 120, 'CONCERNER')
    link(d, [(735, 559), (780, 559)], '1,1', (742, 575))
    link(d, [(900, 559), (935, 559)], '0,N', (902, 575))
    entity(d, 45, 95, 245, 'AVIS', ['Identifiant avis', 'Note', 'Commentaire', 'Statut de validation', 'Date de création'])
    association(d, 100, 320, 140, 'RÉDIGER')
    link(d, [(170, 443), (170, 368)], '0,N', (182, 414))
    link(d, [(170, 320), (170, 228)], '1,1', (182, 243))
    association(d, 330, 145, 110, 'ÉVALUER')
    link(d, [(290, 169), (330, 169)], '1,1', (293, 184))
    link(d, [(470, 350), (455, 350), (455, 169), (440, 169)], '0,1', (420, 363))
    entity(d, 825, 95, 290, 'ÉVÉNEMENT DE STATUT', ['Identifiant événement', 'Statut', 'Date de modification'])
    association(d, 850, 320, 185, 'CONSERVER')
    link(d, [(735, 410), (942, 410), (942, 368)], '0,N', (746, 423))
    link(d, [(942, 320), (942, 194)], '1,1', (954, 209))
    text(d, 500, 265, 'Contraintes complémentaires', 12, True, ACCENT)
    text(d, 500, 242, 'Un seul avis au maximum par commande.', 11)
    text(d, 500, 223, 'L’auteur de l’avis doit être le client', 11)
    text(d, 500, 204, 'de la commande, et celle-ci doit être terminée.', 11)
    text(d, 500, 165, 'L’auteur des changements de statut', 11)
    text(d, 500, 146, 'est représenté dans la vue 3.', 11)
    return d


def access():
    d = page(3, 'Accès, traçabilité et horaires', 'Les entités répétées entre les vues représentent les mêmes objets ; aucune duplication de données n’est prévue.')
    entity(d, 65, 455, 270, 'UTILISATEUR (VUE 2)', ['Identifiant utilisateur', 'Attributs : voir vue 2'])
    entity(d, 825, 438, 300, 'ÉVÉNEMENT DE STATUT', ['Identifiant événement', 'Statut', 'Date de modification'])
    association(d, 485, 475, 160, 'MODIFIER')
    link(d, [(335, 499), (485, 499)], '0,N', (355, 512))
    link(d, [(645, 499), (825, 499)], '0,1', (780, 512))
    entity(d, 65, 105, 270, 'JETON DE RÉINITIALISATION', ['Identifiant jeton', 'Empreinte du jeton', 'Date d’expiration', 'Utilisé', 'Date de création'])
    association(d, 110, 320, 180, 'DÉTENIR')
    link(d, [(200, 455), (200, 368)], '0,N', (212, 425))
    link(d, [(200, 320), (200, 238)], '1,1', (212, 253))
    entity(d, 825, 105, 300, 'HORAIRE', ['Jour', 'Heure d’ouverture', 'Heure de fermeture', 'Fermé'])
    text(d, 65, 644, 'Lecture des cardinalités', 15, True, ACCENT)
    text(d, 65, 618, '0,N : aucun ou plusieurs  |  1,1 : exactement un  |  0,1 : aucun ou un', 13)
    text(d, 410, 395, 'L’auteur d’un événement peut être absent :', 12)
    text(d, 410, 375, 'par exemple après la suppression d’un compte.', 12)
    text(d, 410, 320, 'Les horaires décrivent une seule entreprise.', 12)
    text(d, 410, 300, 'Chaque jour est unique ; aucun lien artificiel', 12)
    text(d, 410, 280, 'avec les commandes n’est ajouté.', 12)
    text(d, 410, 219, 'Firebase reçoit des statistiques dérivées', 12)
    text(d, 410, 199, 'des commandes, sans identité des clients.', 12)
    text(d, 410, 179, 'Ce stockage ne crée pas de nouvelle entité métier.', 12)
    text(d, 410, 117, 'Règles et correspondance SQL : docs/mcd.md', 11, True, ACCENT)
    return d


def main():
    output = ROOT / 'output/pdf'
    svg_output = ROOT / 'docs/mcd'
    output.mkdir(parents=True, exist_ok=True)
    svg_output.mkdir(parents=True, exist_ok=True)
    pdf = Canvas(str(output / 'mcd-vite-gourmand.pdf'), pagesize=(WIDTH, HEIGHT), invariant=1)
    pdf.setTitle('Vite & Gourmand - Modèle conceptuel de données')
    pdf.setAuthor('Projet Vite & Gourmand')
    for name, drawing in [('01-catalogue', catalogue()), ('02-commandes', orders()), ('03-acces-horaires', access())]:
        renderPDF.draw(drawing, pdf, 0, 0)
        pdf.showPage()
        renderSVG.drawToFile(drawing, str(svg_output / f'{name}.svg'))
    pdf.save()


if __name__ == '__main__':
    main()
