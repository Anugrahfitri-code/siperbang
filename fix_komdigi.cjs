const fs = require('fs');

function standardizeKomdigiText(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');

    // Standardize title KOMDIGI
    // It might be different in app.blade.php or Logos.tsx
    content = content.replace(
        /className="text-sm font-extrabold text-\[\#4A4A4A\] tracking-wider leading-none"|class="text-sm font-extrabold text-\[\#4A4A4A\] tracking-wider leading-none"/g,
        filePath.endsWith('.tsx') 
            ? 'className="text-sm font-extrabold text-[#4A4A4A] tracking-wider leading-none"'
            : 'class="text-sm font-extrabold text-[#4A4A4A] tracking-wider leading-none"'
    );

    // Standardize subtitle Kementerian Komunikasi dan Digital
    // Logos.tsx has text-2xs
    // main.blade.php has text-xs
    // app.blade.php has text-[8px]
    const subtitleRegex = /(className="|class=")(text-2xs|text-xs|text-\[8px\]|text-\[10px\])\s+text-\[\#7A7A7A\]\s+font-semibold\s+tracking-tight\s+leading-tight\s+mt-0\.5"/g;
    
    content = content.replace(subtitleRegex, '$1text-[10px] text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5"');

    fs.writeFileSync(filePath, content, 'utf8');
}

standardizeKomdigiText('resources/js/components/Logos.tsx');
standardizeKomdigiText('resources/views/layouts/main.blade.php');
standardizeKomdigiText('resources/views/layouts/app.blade.php');

console.log('Standardized Komdigi text');
