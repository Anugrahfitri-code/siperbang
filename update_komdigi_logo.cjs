const fs = require('fs');

function processFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');

    if (filePath.endsWith('.tsx')) {
        // Replace Komdigi SVG with Image
        const komdigiRegex = /<div className="relative w-10 h-10 flex-shrink-0">[\s\S]*?<\/svg>\s*<\/div>/g;
        const replacement = `<div className="relative w-12 h-12 flex-shrink-0">
        <img
          src="/images/komdigi-logo.png"
          alt="Logo KOMDIGI"
          className="w-full h-full object-contain select-none pointer-events-none"
        />
      </div>`;
        content = content.replace(komdigiRegex, replacement);
    } else {
        // Blade files
        const komdigiRegex = /<div class="relative w-10 h-10 flex-shrink-0">[\s\S]*?<\/svg>\s*<\/div>/g;
        const replacement = `<div class="relative w-12 h-12 flex-shrink-0">
                            <img src="{{ asset('images/komdigi-logo.png') }}" alt="Logo KOMDIGI" class="w-full h-full object-contain select-none pointer-events-none">
                        </div>`;
        content = content.replace(komdigiRegex, replacement);
    }

    fs.writeFileSync(filePath, content, 'utf8');
}

processFile('resources/js/components/Logos.tsx');
processFile('resources/views/layouts/main.blade.php');
processFile('resources/views/layouts/app.blade.php');

console.log('Komdigi logo updated');
