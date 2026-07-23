const { icons } = require('lucide-react');
const fs = require('fs');

const mappings = {
  'Kelola Pengguna': 'Users',
  'Daftar Tindakan': 'ClipboardList',
  'Pengecekan & Pemenuhan': 'Boxes',
  'Excel & Kode Persediaan': 'FileBarChart2',
  'OCR Kuitansi & Pajak': 'Receipt',
  'Rekap Laporan Excel': 'FileBarChart2',
  'Master Barang': 'Package',
  'BON Digital / Ajukan Baru': 'WalletCards',
  'Pantau Pengajuan': 'ClipboardCheck',
  'Pantau Pengajuan Saya': 'ClipboardCheck',
  'Katalog Stok Gudang': 'ScanSearch',
  'Audit Log Sistem': 'History',
  'Histori & Audit Log': 'History',
  'Histori Pengajuan': 'History'
};

let content = fs.readFileSync('resources/views/components/sidebar.blade.php', 'utf8');

for (const [label, iconName] of Object.entries(mappings)) {
  const i = icons[iconName];
  if (!i) { console.log('not found: ' + iconName); continue; }
  
  // Use a regex that replaces any stroke-width so we can standardize on 1.9, keeping the exact lucide paths.
  // Actually, we can just find the <span class="shrink-0">\n<svg ...>...</svg>\n</span>\n<span class="truncate">LABEL</span>
  // and replace the SVG entirely.
  
  const innerSvg = i.map(node => {
    return `<${node[0]} ${Object.entries(node[1]).map(([k, v]) => `${k}="${v}"`).join(' ')}/>`;
  }).join('');

  const svgStr = `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            ${innerSvg}
                        </svg>`;
                        
  // Regex to match the block:
  // <span class="shrink-0">\s*<svg.*?>.*?</svg>\s*</span>\s*<span class="truncate">LABEL</span>
  const regex = new RegExp(`(<span class="shrink-0">\\s*)<svg[\\s\\S]*?<\\/svg>(\\s*<\\/span>\\s*<span class="truncate">${label.replace('&', '&amp;')}<\\/span>)`, 'gi');
  // Wait, in blade it's just Pengecekan & Pemenuhan, maybe not &amp;
  const regexPlain = new RegExp(`(<span class="shrink-0">\\s*)<svg[\\s\\S]*?<\\/svg>(\\s*<\\/span>\\s*<span class="truncate">${label}<\\/span>)`, 'gi');
  
  content = content.replace(regexPlain, `$1${svgStr}$2`);
  content = content.replace(regex, `$1${svgStr}$2`);
}

// For Petugas Persediaan, the width is 20 sometimes. The blade file actually uses 19 in most places. Let's force replace stroke-width="1.9" and width="19" height="19".
// Wait, looking at Petugas Persediaan block:
// <svg width="20" height="20" ... stroke-width="1.9"
// I will just use width="20" height="20" if they were 20. But replacing with 19 is fine, it standardizes it perfectly!
fs.writeFileSync('resources/views/components/sidebar.blade.php', content, 'utf8');
console.log('Done replacing SVGs.');
