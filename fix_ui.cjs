const fs = require('fs');

function processFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');

    // Remove border-l from Komdigi text container
    content = content.replace('className="flex flex-col select-none border-l border-gray-300 pl-2"', 'className="flex flex-col select-none"');
    content = content.replace('class="flex flex-col select-none border-l border-gray-300 pl-2"', 'class="flex flex-col select-none"');
    // For main.blade.php which might have hidden sm:flex
    content = content.replace('class="flex-col select-none border-l border-gray-300 pl-2 hidden md:flex"', 'class="flex-col select-none hidden md:flex"');

    // Replace SIPERBANG text spans
    const reactSpans = `<span className="text-[#0055A5]">S</span>
            <span className="text-[#B90015]">I</span>
            <span className="text-[#0055A5]">PERB</span>
            <span className="text-[#F2B818]">A</span>
            <span className="text-[#4A4A4A]">NG</span>`;
    
    const reactReplacement = `<span className="text-[#0055A5]">SIPERBANG</span>`;

    const bladeSpans = `<span class="text-[#0055A5]">S</span>
                                <span class="text-[#B90015]">I</span>
                                <span class="text-[#0055A5]">PERB</span>
                                <span class="text-[#F2B818]">A</span>
                                <span class="text-[#4A4A4A]">NG</span>`;

    const bladeSpansApp = `<span class="text-[#0055A5]">S</span>
                            <span class="text-[#B90015]">I</span>
                            <span class="text-[#0055A5]">PERB</span>
                            <span class="text-[#F2B818]">A</span>
                            <span class="text-[#4A4A4A]">NG</span>`;
                            
    const bladeReplacement = `<span class="text-[#0055A5]">SIPERBANG</span>`;

    // A simpler way: regex to replace all spans inside the title div
    // Let's do it with regex
    content = content.replace(
        /<span class(Name)?="text-\[\#[A-Z0-9]+\]">.*?<\/span>\s*<span class(Name)?="text-\[\#[A-Z0-9]+\]">.*?<\/span>\s*<span class(Name)?="text-\[\#[A-Z0-9]+\]">.*?<\/span>\s*<span class(Name)?="text-\[\#[A-Z0-9]+\]">.*?<\/span>\s*<span class(Name)?="text-\[\#[A-Z0-9]+\]">.*?<\/span>/gs,
        '<span class$1="text-[#0055A5]">SIPERBANG</span>'
    );

    fs.writeFileSync(filePath, content, 'utf8');
}

processFile('resources/js/components/Logos.tsx');
processFile('resources/views/layouts/main.blade.php');
processFile('resources/views/layouts/app.blade.php');

console.log('UI fixes applied');
