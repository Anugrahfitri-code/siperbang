import os

# Update main.blade.php header text sizes
with open("resources/views/layouts/main.blade.php", "r", encoding="utf-8") as f:
    content = f.read()

# Fix Siperbang text size
content = content.replace(
    '<span class="text-2xs font-medium tracking-wide mt-1 leading-none uppercase text-[#7A7A7A]">',
    '<span class="text-xs font-medium tracking-wide mt-1 leading-none uppercase text-[#7A7A7A]">'
)

# Fix Komdigi text size
content = content.replace(
    '<span class="text-2xs text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">',
    '<span class="text-xs text-[#7A7A7A] font-semibold tracking-tight leading-tight mt-0.5">'
)

with open("resources/views/layouts/main.blade.php", "w", encoding="utf-8") as f:
    f.write(content)

# Update sidebar.blade.php
with open("resources/views/components/sidebar.blade.php", "r", encoding="utf-8") as f:
    sidebar = f.read()

# Replace Excel & Kode Persediaan color from amber to emerald
sidebar = sidebar.replace(
    "{{ $isExcelActive ? 'bg-amber-50 text-amber-700 border-amber-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-amber-50/70 hover:text-amber-700' }}",
    "{{ $isExcelActive ? 'bg-emerald-50 text-emerald-700 border-emerald-600 border-l-4' : 'border-transparent border-l-4 bg-transparent text-slate-500 hover:bg-emerald-50/70 hover:text-emerald-700' }}"
)

with open("resources/views/components/sidebar.blade.php", "w", encoding="utf-8") as f:
    f.write(sidebar)

print("Updated text and colors.")
