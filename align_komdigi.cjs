const fs = require('fs');

function alignKomdigiText(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');

    // Add text-left to the Komdigi text container
    // For React:
    content = content.replace(/<div className="flex flex-col select-none">(\s*<span)/g, '<div className="flex flex-col select-none text-left">$1');
    
    // For Blade (main.blade.php):
    // <div class="flex-col select-none hidden md:flex">
    content = content.replace(/<div class="flex-col select-none hidden md:flex">/g, '<div class="flex-col select-none hidden md:flex text-left">');
    
    // For Blade (app.blade.php):
    // <div class="flex flex-col select-none">
    content = content.replace(/<div class="flex flex-col select-none">/g, '<div class="flex flex-col select-none text-left">');

    fs.writeFileSync(filePath, content, 'utf8');
}

alignKomdigiText('resources/js/components/Logos.tsx');
alignKomdigiText('resources/views/layouts/main.blade.php');
alignKomdigiText('resources/views/layouts/app.blade.php');

console.log('Text aligned left');
