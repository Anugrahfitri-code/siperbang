const fs = require('fs');

function resizeKomdigiLogo(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');

    // Replace the container size specifically for the Komdigi logo
    // Using regex to find the w-12 h-12 block that contains komdigi-logo.png
    const reactRegex = /<div className="relative w-12 h-12 flex-shrink-0">\s*<img\s*src="\/images\/komdigi-logo\.png"/g;
    content = content.replace(reactRegex, '<div className="relative w-9 h-9 flex-shrink-0">\n        <img\n          src="/images/komdigi-logo.png"');

    const bladeRegex = /<div class="relative w-12 h-12 flex-shrink-0">\s*<img src="\{\{ asset\('images\/komdigi-logo\.png'\) \}\}"/g;
    content = content.replace(bladeRegex, '<div class="relative w-9 h-9 flex-shrink-0">\n                            <img src="{{ asset(\'images/komdigi-logo.png\') }}"');

    fs.writeFileSync(filePath, content, 'utf8');
}

resizeKomdigiLogo('resources/js/components/Logos.tsx');
resizeKomdigiLogo('resources/views/layouts/main.blade.php');
resizeKomdigiLogo('resources/views/layouts/app.blade.php');

console.log('Komdigi logo resized');
