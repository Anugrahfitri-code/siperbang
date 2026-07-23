const fs = require('fs');

function decreaseKomdigiGap(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');

    // Revert gap-3 back to gap-2 for KomdigiLogo
    // In Logos.tsx:
    content = content.replace(/<div className={`flex items-center gap-3 \$\{className\}`}>\s*<div className="relative w-9 h-9/g, '<div className={`flex items-center gap-2 ${className}`}>\n      <div className="relative w-9 h-9');

    // In main.blade.php & app.blade.php:
    content = content.replace(/class="hidden md:flex items-center gap-3"/g, 'class="hidden md:flex items-center gap-2"');
    content = content.replace(/class="flex items-center gap-3"([\s\S]*?<img src="\{\{ asset\('images\/komdigi-logo\.png'\) \}\}")/g, 'class="flex items-center gap-2"$1');
    content = content.replace(/className="flex items-center gap-3"([\s\S]*?<img\s*src="\/images\/komdigi-logo\.png")/g, 'className="flex items-center gap-2"$1');

    fs.writeFileSync(filePath, content, 'utf8');
}

decreaseKomdigiGap('resources/js/components/Logos.tsx');
decreaseKomdigiGap('resources/views/layouts/main.blade.php');
decreaseKomdigiGap('resources/views/layouts/app.blade.php');

console.log('Komdigi gap decreased to gap-2');
