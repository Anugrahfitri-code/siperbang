import re

with open(r'd:\SIPERBANG\siperbang\resources\js\components\Sidebar.tsx', 'r', encoding='utf-8') as f:
    content = f.read()

content = re.sub(
    r'w-full flex items-center justify-between px-3\.5 py-(2\.5|3) rounded text-xs font-bold transition-all border',
    r'w-full flex items-center justify-between px-4 py-3 text-sm font-bold transition-all',
    content
)

content = re.sub(
    r'bg-(emerald|indigo)-600 text-white border-\1-600 shadow-xs',
    r'bg-blue-50 text-blue-600 border-l-[3px] border-blue-600 rounded-l-none',
    content
)

content = re.sub(
    r'bg-amber-400 text-slate-900 border-amber-400 shadow-xs',
    r'bg-blue-50 text-blue-600 border-l-[3px] border-blue-600 rounded-l-none',
    content
)

content = re.sub(
    r'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-900',
    r'bg-white text-slate-600 border-l-[3px] border-transparent hover:bg-slate-50 hover:text-slate-900 rounded-l-none',
    content
)

# Remove backdrop
idx1 = content.find('{/* Backdrop */}')
idx2 = content.find('{/* Sidebar Panel */}')
if idx1 != -1 and idx2 != -1:
    content = content[:idx1] + content[idx2:]

# Replace sidebar panel classes
sidebar_panel_regex = r'className={`fixed top-0 left-0 h-full w-72 bg-white shadow-2xl z-50 transform transition-transform duration-300 ease-in-out flex flex-col \$\{\s*isOpen \? "translate-x-0" : "-translate-x-full"\s*\}`}'
new_sidebar_panel = r'className={`relative h-full w-[260px] bg-white border-r border-slate-200 z-40 transform transition-transform duration-300 ease-in-out flex flex-col shrink-0 ${isOpen ? "translate-x-0 ml-0" : "-translate-x-full -ml-[260px]"}`}'
content = re.sub(sidebar_panel_regex, new_sidebar_panel, content, flags=re.DOTALL)

# Remove the Menu Utama header block
header_regex = r'<div className="p-5 flex items-center justify-between border-b border-slate-100">.*?</div>'
content = re.sub(header_regex, '', content, flags=re.DOTALL)

# Replace the footer
footer_regex = r'<div className="p-4 border-t border-slate-100 bg-slate-50 mt-auto">.*?</div>'
new_footer = '''<div className="p-5 border-t border-slate-200 bg-white mt-auto flex flex-row items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-600 shrink-0 border border-slate-200">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="m9 12 2 2 4-4"></path></svg>
          </div>
          <div className="flex flex-col">
            <span className="text-xs font-extrabold text-slate-800 tracking-wider">SIPERBANG</span>
            <span className="text-[10px] text-slate-500 font-medium">v1.0.0</span>
            <span className="text-[10px] text-slate-400 mt-0.5">© 2024 KOMDIGI</span>
          </div>
        </div>'''
content = re.sub(footer_regex, new_footer, content, flags=re.DOTALL)

content = re.sub(r'<([A-Za-z]+) size=\{1[46]\} />', r'<\1 size={20} />', content)

with open(r'd:\SIPERBANG\siperbang\resources\js\components\Sidebar.tsx', 'w', encoding='utf-8') as f:
    f.write(content)

print('Done')
