import os
import re

source_dir = r"D:\Project"
target_dir = r"D:\Project\siperbang"
md_files = [
    "SIPERBANG_KODE_01_BACKEND_CONTROLLERS_MODELS_SERVICES.md",
    "SIPERBANG_KODE_02_CONFIG_DATABASE.md",
    "SIPERBANG_KODE_03_OCR_PYTHON.md",
    "SIPERBANG_KODE_04_FRONTEND_REACT.md",
    "SIPERBANG_KODE_05_ROUTES_TESTS.md"
]

file_marker_pattern_1 = re.compile(r"^===== FILE:\s*(.+?)\s*=====")
file_marker_pattern_2 = re.compile(r"^##\s+`(.+?)`")

def process_md_file(md_path):
    with open(md_path, 'r', encoding='utf-8') as f:
        content = f.read()

    lines = content.split('\n')
    current_file = None
    current_content = []

    def save_current_file():
        if current_file and current_content:
            text = '\n'.join(current_content).strip()
            
            # Remove leading markdown code block markers
            if text.startswith('```'):
                first_nl = text.find('\n')
                if first_nl != -1:
                    text = text[first_nl+1:]
            
            # Remove trailing markdown code block markers
            if text.endswith('```'):
                # find the last newline before ```
                last_nl = text.rfind('\n', 0, -3)
                if last_nl != -1:
                    text = text[:last_nl+1]
                else:
                    text = text[:-3]
            
            text = text.strip()
            
            full_path = os.path.join(target_dir, current_file)
            os.makedirs(os.path.dirname(full_path), exist_ok=True)
            with open(full_path, 'w', encoding='utf-8') as out_f:
                out_f.write(text + "\n")
            print(f"Written: {full_path}")

    for line in lines:
        match1 = file_marker_pattern_1.search(line)
        match2 = file_marker_pattern_2.search(line)
        match = match1 or match2
        
        if match:
            save_current_file()
            current_file = match.group(1).strip()
            current_content = []
        elif current_file is not None:
            current_content.append(line)
            
    save_current_file()

for md_file in md_files:
    md_path = os.path.join(source_dir, md_file)
    if os.path.exists(md_path):
        print(f"Processing {md_file}...")
        process_md_file(md_path)
    else:
        print(f"File not found: {md_path}")
