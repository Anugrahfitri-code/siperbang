const fs = require('fs');

function increaseKomdigiGap(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');

    // In Logos.tsx:
    // <div className={`flex items-center gap-2 ${className}`}>
    content = content.replace(/<div className={`flex items-center gap-2 \$\{className\}`}>\s*<div className="relative w-9 h-9/g, '<div className={`flex items-center gap-3 ${className}`}>\n      <div className="relative w-9 h-9');

    // In main.blade.php:
    // <div class="hidden md:flex items-center gap-2">
    content = content.replace(/<div class="hidden md:flex items-center gap-2">\s*<div class="relative w-9 h-9/g, '<div class="hidden md:flex items-center gap-3">\n                        <div class="relative w-9 h-9');

    // In app.blade.php:
    // <div class="hidden md:flex items-center gap-2">
    content = content.replace(/<div class="hidden md:flex items-center gap-2">\s*<div class="relative w-9 h-9/g, '<div class="hidden md:flex items-center gap-3">\n                      <div class="relative w-9 h-9');

    // Just in case it's slightly different, also do a generic replacement for the blade files around the komdigi block
    content = content.replace(/class="hidden md:flex items-center gap-2"/g, 'class="hidden md:flex items-center gap-3"');
    
    // Also check if any generic gap-2 exists right before KomdigiLogo in Blade
    // E.g. <div class="flex items-center gap-2"> (if not hidden md:flex)
    content = content.replace(/class="flex items-center gap-2"([\s\S]*?<img src="\{\{ asset\('images\/komdigi-logo\.png'\) \}\}")/g, 'class="flex items-center gap-3"$1');
    content = content.replace(/className="flex items-center gap-2"([\s\S]*?<img\s*src="\/images\/komdigi-logo\.png")/g, 'className="flex items-center gap-3"$1');


    fs.writeFileSync(filePath, content, 'utf8');
}

increaseKomdigiGap('resources/js/components/Logos.tsx');
increaseKomdigiGap('resources/views/layouts/main.blade.php');
increaseKomdigiGap('resources/views/layouts/app.blade.php');

console.log('Komdigi gap increased to gap-3');
