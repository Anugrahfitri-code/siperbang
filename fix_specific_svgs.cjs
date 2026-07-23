const fs = require('fs');

const mappings = {
  'Excel & Kode Persediaan': '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/>',
  'Excel &amp; Kode Persediaan': '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/>',
  'BON Digital / Ajukan Baru': '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/>',
};

let content = fs.readFileSync('resources/views/components/sidebar.blade.php', 'utf8');

const parts = content.split('<span class="truncate">');
let new_content = parts[0];

for (let i = 1; i < parts.length; i++) {
  const part = parts[i];
  
  const match = part.match(/^([^<]+)/);
  if (match) {
    const label = match[1].trim();
    
    if (mappings[label]) {
      const svg_inner = mappings[label];
      
      const lastSvgIndex = new_content.lastIndexOf('<svg');
      if (lastSvgIndex !== -1) {
        const before_svg = new_content.substring(0, lastSvgIndex);
        const svg_chunk = new_content.substring(lastSvgIndex);
        
        const endSvgIndex = svg_chunk.indexOf('</svg>');
        if (endSvgIndex !== -1) {
          const after_svg = svg_chunk.substring(endSvgIndex + 6);
          
          const new_svg = `<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">\n                            ${svg_inner}\n                        </svg>`;
          
          new_content = before_svg + new_svg + after_svg;
        }
      }
    }
  }
  new_content += '<span class="truncate">' + part;
}

fs.writeFileSync('resources/views/components/sidebar.blade.php', new_content, 'utf8');

// Also update Sidebar.tsx
let tsx = fs.readFileSync('resources/js/components/Sidebar.tsx', 'utf8');
tsx = tsx.replace('WalletCards,', 'ReceiptText,');
// Because there might be multiple WalletCards, we just replace it where label="BON Digital / Ajukan Baru"
tsx = tsx.replace(/<WalletCards([\s\S]*?)label="BON Digital \/ Ajukan Baru"/g, '<ReceiptText$1label="BON Digital / Ajukan Baru"');
fs.writeFileSync('resources/js/components/Sidebar.tsx', tsx, 'utf8');

console.log('Fixed specific SVGs');
