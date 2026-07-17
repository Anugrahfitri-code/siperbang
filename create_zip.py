import os
import zipfile

def create_zip(source_dir, output_filename, exclude_dirs):
    with zipfile.ZipFile(output_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(source_dir):
            dirs[:] = [d for d in dirs if d not in exclude_dirs]
            
            for file in files:
                file_path = os.path.join(root, file)
                arcname = os.path.relpath(file_path, source_dir)
                zipf.write(file_path, arcname)

if __name__ == "__main__":
    source = r"D:\Project\siperbang"
    output = r"D:\Project\siperbang.zip"
    excludes = {"node_modules", ".venv", "venv", ".git", "__pycache__", "cache", "uploads", "temp", "dist", "build", "storage"}
    create_zip(source, output, excludes)
    print("Zip created successfully at", output)
