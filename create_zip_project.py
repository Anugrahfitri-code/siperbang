import os
import zipfile

def create_zip():
    zip_filename = "siperbang_project.zip"
    source_dir = "."
    
    excludes = {
        "node_modules", ".venv", "venv", ".git", "__pycache__", 
        "cache", "uploads", "temp", "dist", "build"
    }

    with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(source_dir):
            # Modify dirs in-place to skip excluded directories
            dirs[:] = [d for d in dirs if d not in excludes]
            
            for file in files:
                if file == zip_filename:
                    continue
                    
                file_path = os.path.join(root, file)
                arcname = os.path.relpath(file_path, source_dir)
                zipf.write(file_path, arcname)

if __name__ == "__main__":
    create_zip()
    print("ZIP file created successfully: siperbang_project.zip")
