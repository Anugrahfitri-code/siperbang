import re

file_path = 'd:\\Project\\siperbang\\resources\\views\\components\\sidebar.blade.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

def replace_link(match):
    full_match = match.group(0)
    if 'onclick' in full_match: return full_match
    
    text = match.group(2).strip()
    onclick = ''
    
    if text == 'Kelola Pengguna':
        onclick = "onclick=\"localStorage.setItem('superadminTab', 'users');\""
    elif text == 'Daftar Tindakan' or text == 'Beranda Tindakan':
        onclick = "onclick=\"localStorage.setItem('officerTab', 'dashboard'); localStorage.setItem('superadminTab', 'dashboard'); localStorage.setItem('requesterTab', 'dashboard');\""
    elif text == 'Pengecekan & Pemenuhan':
        onclick = "onclick=\"localStorage.setItem('officerTab', 'checking'); localStorage.setItem('superadminTab', 'checking');\""
    elif text == 'OCR Kuitansi & Pajak':
        onclick = "onclick=\"localStorage.setItem('officerTab', 'ocr'); localStorage.setItem('superadminTab', 'ocr');\""
    elif text == 'Rekap Laporan Excel':
        onclick = "onclick=\"localStorage.setItem('officerTab', 'report'); localStorage.setItem('superadminTab', 'report');\""
    elif text == 'BON Digital / Ajukan Baru':
        onclick = "onclick=\"localStorage.setItem('requesterTab', 'bon'); localStorage.setItem('superadminTab', 'bon');\""
    elif text == 'Pantau Pengajuan' or text == 'Status Pengajuan':
        onclick = "onclick=\"localStorage.setItem('requesterTab', 'monitoring'); localStorage.setItem('superadminTab', 'monitoring');\""
    elif text == 'Katalog Stok Gudang':
        onclick = "onclick=\"localStorage.setItem('requesterTab', 'stock'); localStorage.setItem('superadminTab', 'stock_catalog');\""
    elif text == 'Histori & Audit Log':
        onclick = "onclick=\"localStorage.setItem('officerTab', 'history'); localStorage.setItem('requesterTab', 'history'); localStorage.setItem('superadminTab', 'history');\""
    
    if onclick:
        return full_match.replace('href="/"', f'href="/" {onclick}')
    return full_match

new_content = re.sub(r'<a href="/"(.*?)<span class="truncate">(.*?)</span>(.*?)</a>', replace_link, content, flags=re.DOTALL)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print('Sidebar links updated successfully!')
