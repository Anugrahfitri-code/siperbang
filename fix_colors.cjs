const fs = require('fs');

function processFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');

    // Replace single color SIPERBANG with multi-color
    const reactReplacement = `<span className="text-[#0055A5]">S</span>
            <span className="text-[#00A1E4]">I</span>
            <span className="text-[#013A70]">PERB</span>
            <span className="text-[#00A1E4]">A</span>
            <span className="text-[#0055A5]">NG</span>`;

    const bladeReplacement = `<span class="text-[#0055A5]">S</span>
                                <span class="text-[#00A1E4]">I</span>
                                <span class="text-[#013A70]">PERB</span>
                                <span class="text-[#00A1E4]">A</span>
                                <span class="text-[#0055A5]">NG</span>`;

    if (filePath.endsWith('.tsx')) {
        content = content.replace(
            /<span className="text-\[\#0055A5\]">SIPERBANG<\/span>/g,
            reactReplacement
        );
    } else {
        content = content.replace(
            /<span class="text-\[\#0055A5\]">SIPERBANG<\/span>/g,
            bladeReplacement
        );
    }

    fs.writeFileSync(filePath, content, 'utf8');
}

processFile('resources/js/components/Logos.tsx');
processFile('resources/views/layouts/main.blade.php');
processFile('resources/views/layouts/app.blade.php');

console.log('Siperbang colors applied');
