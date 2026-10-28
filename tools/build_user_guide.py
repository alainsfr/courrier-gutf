"""Build the printable guide from the same Markdown displayed in the app.

Requires reportlab. Run: python tools/build_user_guide.py
Screenshots in public/docs contain fictional demonstration data only.
"""
from pathlib import Path
import re
from html import escape
from reportlab.lib import colors
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import ParagraphStyle
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate, Frame, PageTemplate, NextPageTemplate, PageBreak,
    Paragraph, Spacer, Table, TableStyle, Image, KeepTogether,
)
from reportlab.platypus.tableofcontents import TableOfContents

ROOT = Path(__file__).resolve().parents[1]
DEST = ROOT / 'public/docs/manuel-utilisation.pdf'
NAVY = colors.HexColor('#17384b')
ORANGE = colors.HexColor('#dc633a')
MUTED = colors.HexColor('#64748b')
LIGHT = colors.HexColor('#f1f5f9')
W, H = A4
MARGIN = 44
CONTENT = W - MARGIN * 2 - 12

windows_fonts = Path('C:/Windows/Fonts')
linux_fonts = Path('/usr/share/fonts/truetype/dejavu')
for name, win, nix in [
    ('Guide', 'segoeui.ttf', 'DejaVuSans.ttf'),
    ('Guide-Bold', 'segoeuib.ttf', 'DejaVuSans-Bold.ttf'),
    ('Guide-Italic', 'segoeuii.ttf', 'DejaVuSans-Oblique.ttf'),
    ('Guide-BoldItalic', 'segoeuiz.ttf', 'DejaVuSans-BoldOblique.ttf'),
]:
    font = windows_fonts / win if (windows_fonts / win).exists() else linux_fonts / nix
    pdfmetrics.registerFont(TTFont(name, str(font)))
pdfmetrics.registerFontFamily('Guide', normal='Guide', bold='Guide-Bold', italic='Guide-Italic', boldItalic='Guide-BoldItalic')

body = ParagraphStyle('Body', fontName='Guide', fontSize=10, leading=14.7, spaceAfter=8, textColor=colors.HexColor('#334155'))
h2 = ParagraphStyle('Chapter', parent=body, fontName='Guide-Bold', fontSize=17, leading=22, spaceBefore=0, spaceAfter=16, textColor=NAVY, keepWithNext=True)
h3 = ParagraphStyle('Subheading', parent=body, fontName='Guide-Bold', fontSize=11, leading=15, spaceBefore=9, spaceAfter=6, textColor=NAVY, keepWithNext=True)
small = ParagraphStyle('Small', parent=body, fontSize=8.2, leading=11.5, textColor=MUTED)
cell = ParagraphStyle('Cell', parent=body, fontSize=8.5, leading=12, spaceAfter=0)
bullet = ParagraphStyle('Bullet', parent=body, leftIndent=15, firstLineIndent=-13, spaceAfter=6)

def rich(text):
    value = escape(text).replace('\u2014', '-').replace('\u2013', '-')
    value = re.sub(r'\*\*(.+?)\*\*', r'<b>\1</b>', value)
    value = re.sub(r'`(.+?)`', r'<font color="#17384b">\1</font>', value)
    return value

def p(text, style=body):
    return Paragraph(rich(text), style)

class GuideDoc(BaseDocTemplate):
    def afterFlowable(self, flowable):
        if isinstance(flowable, Paragraph) and flowable.style.name == 'Chapter':
            title = flowable.getPlainText()
            key = 'chapter-' + title[:2]
            self.canv.bookmarkPage(key)
            self.canv.addOutlineEntry(title, key, 0)
            self.notify('TOCEntry', (0, title, self.page, key))

def paint(canvas, doc):
    width, height = canvas._pagesize
    canvas.saveState()
    if doc.page == 1:
        canvas.setFillColor(NAVY)
        canvas.rect(0, 0, width, height, fill=1, stroke=0)
        canvas.setFillColor(ORANGE)
        canvas.roundRect(44, height-104, 48, 48, 14, fill=1, stroke=0)
        canvas.setFillColor(colors.white)
        canvas.setFont('Guide-Bold', 24)
        canvas.drawCentredString(68, height-89, 'C')
        canvas.setFont('Guide-Bold', 15)
        canvas.drawString(108, height-77, 'COURRIER GUTF')
        canvas.setFont('Guide', 10)
        canvas.drawString(108, height-96, 'Gestion et suivi des courriers')
        canvas.setFillColor(colors.HexColor('#ffb399'))
        canvas.setFont('Guide-Bold', 10)
        canvas.drawString(44, 592, 'GUIDE PRATIQUE - ÉDITION DU 28 OCTOBRE 2026')
        canvas.setFillColor(colors.white)
        canvas.setFont('Guide-Bold', 33)
        canvas.drawString(44, 535, 'Manuel')
        canvas.drawString(44, 490, 'd’utilisation')
        canvas.setFont('Guide', 13)
        canvas.drawString(44, 447, 'Agents, responsables et administrateurs')
        canvas.setStrokeColor(colors.HexColor('#486575'))
        canvas.line(44, 405, width-44, 405)
        for i, label in enumerate(['Enregistrer et affecter', 'Prendre en charge et traiter', 'Terminer et archiver']):
            y=350-i*56
            canvas.setFillColor(ORANGE)
            canvas.circle(57,y+3,13,fill=1,stroke=0)
            canvas.setFillColor(colors.white)
            canvas.setFont('Guide-Bold',10)
            canvas.drawCentredString(57,y,str(i+1))
            canvas.setFont('Guide',13)
            canvas.drawString(83,y,label)
        canvas.setFont('Guide',10)
        canvas.drawString(44,126,'Un bureau destinataire, du traitement à l’archivage.')
        canvas.setFillColor(colors.HexColor('#bdd0db'))
        canvas.setFont('Guide',8)
        canvas.drawString(44,72,'Procédures, rôles, captures, dépannage et aide-mémoire.')
        canvas.drawString(44,55,'Les illustrations présentent uniquement des données fictives.')
    else:
        canvas.setStrokeColor(colors.HexColor('#e2e8f0'))
        canvas.line(44,height-40,width-44,height-40)
        canvas.setFont('Guide-Bold',8)
        canvas.setFillColor(NAVY)
        canvas.drawString(44,height-29,'COURRIER GUTF')
        canvas.setFont('Guide',8)
        canvas.setFillColor(MUTED)
        canvas.drawRightString(width-44,height-29,'Manuel d’utilisation | 28 octobre 2026')
        canvas.line(44,37,width-44,37)
        canvas.drawString(44,24,'Exemples et captures de démonstration')
        canvas.drawRightString(width-44,24,f'{doc.page:02d}')
        canvas.linkRect('', 'contents', (44,16,245,34), relative=0, thickness=0)
    canvas.restoreState()

doc = GuideDoc(str(DEST), pagesize=A4, title='COURRIER GUTF - Manuel d’utilisation', author='COURRIER GUTF', leftMargin=44,rightMargin=44,topMargin=57,bottomMargin=49)
doc.addPageTemplates([
    PageTemplate(id='portrait', frames=[Frame(44,49,W-88,H-106,id='body')], pagesize=A4, onPage=paint),
    PageTemplate(id='landscape', frames=[Frame(44,49,H-88,W-106,id='figure')], pagesize=landscape(A4), onPage=paint),
])

text=(ROOT/'resources/docs/manuel-utilisation.md').read_text(encoding='utf-8-sig')
parts=re.split(r'^## ', text, flags=re.M)[1:]
chapters={int(part[:2]):part.split('\n',1) for part in parts}

def table(rows):
    count=len(rows[0])
    widths=([CONTENT*.31, CONTENT*.69] if count==2 else [CONTENT*.25,CONTENT*.32,CONTENT*.43])
    data=[[p(value,cell) for value in row] for row in rows]
    result=Table(data,colWidths=widths,repeatRows=1,hAlign='LEFT')
    result.setStyle(TableStyle([
        ('BACKGROUND',(0,0),(-1,0),LIGHT),('VALIGN',(0,0),(-1,-1),'TOP'),
        ('BOX',(0,0),(-1,-1),.5,colors.HexColor('#cbd5e1')),
        ('INNERGRID',(0,0),(-1,-1),.4,colors.HexColor('#e2e8f0')),
        ('LEFTPADDING',(0,0),(-1,-1),8),('RIGHTPADDING',(0,0),(-1,-1),8),
        ('TOPPADDING',(0,0),(-1,-1),7),('BOTTOMPADDING',(0,0),(-1,-1),7),
    ]))
    return [result,Spacer(1,10)]

def parse(markdown):
    result=[]; figures=[]; lines=markdown.strip().splitlines(); index=0
    while index < len(lines):
        line=lines[index].strip();index+=1
        if not line: continue
        image=re.fullmatch(r'!\[(.*?)\]\((.*?)\)',line)
        if image:
            figures.append((image[1],ROOT/'public'/image[2].lstrip('/')));continue
        if line.startswith('### '):result.append(p(line[4:],h3));continue
        if line.startswith('|'):
            rows=[]
            while True:
                values=[v.strip() for v in line.strip('|').split('|')]
                if not all(re.fullmatch(r'[-: ]+',v) for v in values):rows.append(values)
                if index>=len(lines) or not lines[index].strip().startswith('|'):break
                line=lines[index].strip();index+=1
            result.extend(table(rows));continue
        item=re.match(r'^(\d+\.|-) (.+)',line)
        if item:
            prefix='•' if item[1]=='-' else item[1]
            result.append(p(prefix+' '+item[2],bullet));continue
        paragraph=[line]
        while index<len(lines) and lines[index].strip() and not re.match(r'^(### |\||!\[|\d+\. |\- )',lines[index]):
            paragraph.append(lines[index].strip());index+=1
        result.append(p(' '.join(paragraph)))
    return result,figures

story=[Spacer(1,1),PageBreak()]
story.append(Paragraph('<a name="contents"/>Sommaire',h2.clone('ContentsTitle')))
story.append(p('Cliquez sur un chapitre pour y accéder. Les signets du lecteur PDF permettent également de naviguer.',small))
toc=TableOfContents()
toc.levelStyles=[ParagraphStyle('ContentsEntry',fontName='Guide',fontSize=9,leading=12,spaceBefore=3,spaceAfter=0,textColor=NAVY,leftIndent=0,firstLineIndent=0)]
story.append(toc)
story.append(Spacer(1,20))
story.append(p('Mode d’emploi : commencez par les chapitres 1 à 4, puis suivez le parcours correspondant à votre rôle. Les chapitres 17 à 19 s’adressent principalement aux responsables. Le dépannage et l’aide-mémoire sont en fin de guide.',small))

groups=[[1],[2],[3,4],[5],[6],[7],[8,9],[10,11],[12,13],[14,15],[16,17],[18,19],[20],[21],[22]]
figure_number=0
for group in groups:
    story.extend([NextPageTemplate('portrait'),PageBreak()])
    figures=[]
    for number in group:
        title,markdown=chapters[number]
        if number!=group[0]:story.append(Spacer(1,12))
        story.append(p(title,h2))
        content,images=parse(markdown)
        story.extend(content);figures.extend(images)
    for caption,path in figures:
        figure_number+=1
        mobile='mobile' in path.stem
        story.extend([NextPageTemplate('portrait' if mobile else 'landscape'),PageBreak()])
        story.append(p(f'Illustration {figure_number:02d} - {caption}',h3))
        story.append(p('Capture de la version décrite dans le manuel. Comptes, références et pièces fictifs.',small))
        image=Image(str(path))
        maxw,maxh=(230,570) if mobile else (H-100,W-235)
        scale=min(maxw/image.imageWidth,maxh/image.imageHeight)
        image.drawWidth=image.imageWidth*scale;image.drawHeight=image.imageHeight*scale
        image.hAlign='CENTER'
        story.append(image)
        story.append(Spacer(1,8))
        story.append(p('Sur téléphone, les actions et informations principales précèdent la visionneuse.' if mobile else 'Le contenu complet de la page reste accessible par défilement dans l’application.',small))

DEST.parent.mkdir(parents=True,exist_ok=True)
doc.multiBuild(story)
print(DEST)
