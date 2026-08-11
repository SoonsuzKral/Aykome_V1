# Zemin tablosu satır sayısı + içerik
require = None
import re
html = open(r'C:\Aykome_V1\resources\views\admin\pdf\ruhsat.blade.php', encoding='utf-8').read()

# Kaç data-aykome-surface satırı var?
rows = re.findall(r'data-aykome-surface="([^"]+)"', html)
print(f"Ruhsat blade'de zemin satırları ({len(rows)}):")
for r in rows:
    print("  -", r)
