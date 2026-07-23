const fs = require('fs');

let main = fs.readFileSync('resources/views/layouts/main.blade.php', 'utf8');
main = main.replace('<span class="text-2xs font-medium tracking-wide mt-1 leading-none uppercase text-[#7A7A7A]">', '<span class="text-xs font-medium tracking-wide mt-1 leading-none uppercase text-[#7A7A7A]">');
main = main.replace('<span class="text-2xs text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">', '<span class="text-xs text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">');
fs.writeFileSync('resources/views/layouts/main.blade.php', main, 'utf8');

let sidebar = fs.readFileSync('resources/views/components/sidebar.blade.php', 'utf8');
sidebar = sidebar.replaceAll(`{{ $isExcelActive ? 'bg-amber-50 text-amber-700 border-amber-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700' }}`, `{{ $isExcelActive ? 'bg-emerald-50 text-emerald-700 border-emerald-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-emerald-50/70 hover:text-emerald-700' }}`);
fs.writeFileSync('resources/views/components/sidebar.blade.php', sidebar, 'utf8');
console.log('Fixed blade styles');
