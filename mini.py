import os
import subprocess
import sys
import shutil

# --- 設定排除清單 ---
# 1. 要忽略的目錄 (這些目錄下的檔案完全不處理)
DIRS_TO_IGNORE = ['.git', 'node_modules', 'backup', '.vscode', '.idea', 'admin/assets', 'langs', 'PHP_LIB', 'album', 'pic']

# 2. 要忽略的特定檔案 (檔名)
SKIP_FILES = ['config.js', 'config.example.js', 'exif.js']

IS_WINDOWS = os.name == 'nt'

def check_requirements():
    """檢查是否已安裝必要的 Node.js 工具"""
    missing = []
    # Windows 下必須使用 shell=True 才能找到 .cmd 指令
    try:
        subprocess.run(['terser', '--version'], shell=IS_WINDOWS, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    except:
        missing.append("terser")

    try:
        subprocess.run(['cleancss', '--version'], shell=IS_WINDOWS, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    except:
        missing.append("clean-css-cli")
    
    # ... (其餘維持不變)
        return False
    return True

def cleanup_extra_files(root_dir):
    """清理先前可能誤產生的 min 檔案"""
    print("🧹 正在檢查並清理誤產生的檔案...")
    
    for root, dirs, files in os.walk(root_dir):
        # 排除目錄邏輯
        for d in list(dirs):
            full_path = os.path.join(root, d)
            rel_path = os.path.relpath(full_path, root_dir).replace(os.sep, '/')
            if d in DIRS_TO_IGNORE or rel_path in DIRS_TO_IGNORE:
                dirs.remove(d)

        for filename in files:
            if filename in SKIP_FILES:
                # 計算對應的 min 檔名
                if filename.endswith('.js'):
                    min_filename = filename.replace('.js', '.min.js')
                elif filename.endswith('.css'):
                    min_filename = filename.replace('.css', '.min.css')
                else:
                    continue
                
                min_path = os.path.join(root, min_filename)
                if os.path.exists(min_path):
                    print(f"   🗑️  移除誤產生的檔案: {min_path}")
                    os.remove(min_path)

def minify_files():
    # 設定要掃描的根目錄
    root_dir = '.'
    
    # 先執行清理
    cleanup_extra_files(root_dir)

    print("🔍 正在檢查環境...")
    print(f"🚀 開始掃描目錄: {os.path.abspath(root_dir)}")
    print(f"ℹ️  排除目錄: {DIRS_TO_IGNORE}")
    print(f"ℹ️  排除檔案: {SKIP_FILES}")

    count_js = 0
    count_css = 0

    for dirpath, dirnames, filenames in os.walk(root_dir):
        for d in list(dirnames):
            full_subdir_path = os.path.join(dirpath, d)
            rel_path = os.path.relpath(full_subdir_path, root_dir).replace(os.sep, '/')
            
            should_ignore = False
            if d in DIRS_TO_IGNORE: should_ignore = True
            if not should_ignore and rel_path in DIRS_TO_IGNORE: should_ignore = True
            if not should_ignore:
                for ignored in DIRS_TO_IGNORE:
                    if rel_path.startswith(ignored + '/'):
                        should_ignore = True
                        break
            if should_ignore: dirnames.remove(d)

        for filename in filenames:
            full_path = os.path.join(dirpath, filename)
            
            # --- 1. 處理 JS 檔案 ---
            if filename.endswith('.js') and not filename.endswith('.min.js'):
                if filename in SKIP_FILES: continue

                output_path = full_path.replace('.js', '.min.js')
                print(f"📦 正在壓縮 JS: {filename}...")
                
                try:
                    # 修正：根據 OS 決定是否使用 shell
                    cmd = ['terser', full_path, '-o', output_path, '--compress', '--mangle']
                    subprocess.run(cmd, shell=IS_WINDOWS, check=True)
                    count_js += 1
                except Exception as e:
                    print(f"   ❌ 錯誤: {e}")

            # --- 2. 處理 CSS 檔案 ---
            elif filename.endswith('.css') and not filename.endswith('.min.css'):
                if filename in SKIP_FILES: continue

                output_path = full_path.replace('.css', '.min.css')
                print(f"🎨 正在壓縮 CSS: {filename}...")
                
                try:
                    # 修正：根據 OS 決定是否使用 shell
                    cmd = ['cleancss', '-o', output_path, full_path]
                    subprocess.run(cmd, shell=IS_WINDOWS, check=True)
                    count_css += 1
                except Exception as e:
                    print(f"   ❌ 錯誤: {e}")

    print("-" * 30)
    print(f"🎉 作業結束！共壓縮了 {count_js} 個 JS 檔, {count_css} 個 CSS 檔。")

if __name__ == "__main__":
    check_requirements()
    minify_files()