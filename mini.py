import os
import subprocess
import sys
import shutil

def check_requirements():
    """檢查是否已安裝必要的 Node.js 工具"""
    missing = []
    # 在 Windows 上，shutil.which 可能找不到 .cmd，所以我們直接試跑看看
    try:
        subprocess.run(['terser', '--version'], shell=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    except:
        missing.append("terser")

    try:
        subprocess.run(['cleancss', '--version'], shell=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    except:
        missing.append("clean-css-cli")

    if missing:
        print("❌ 錯誤：缺少必要的壓縮工具。")
        print("請先開啟終端機執行以下指令安裝：")
        if "terser" in missing:
            print("   npm install -g terser")
        if "clean-css-cli" in missing:
            print("   npm install -g clean-css-cli")
        return False
    return True

def minify_files():
    # 設定要掃描的根目錄 ('.' 代表當前目錄)
    root_dir = '.'
    
    # --- ★ 設定要排除的檔案清單 (新增功能) ---
    # 這些檔案將不會被壓縮，也不會產生 .min.js
    # 您可以在這裡加入 'config.js', 'settings.js' 等
    SKIP_FILES = ['config.js'] 
    
    # 檢查工具
    print("🔍 正在檢查環境...")
    
    print(f"🚀 開始掃描目錄: {os.path.abspath(root_dir)}")
    print(f"ℹ️  排除清單: {SKIP_FILES}")

    count_js = 0
    count_css = 0

    for dirpath, _, filenames in os.walk(root_dir):
        # 忽略 .git, node_modules 等目錄
        if 'node_modules' in dirpath or '.git' in dirpath:
            continue

        for filename in filenames:
            full_path = os.path.join(dirpath, filename)
            
            # --- 1. 處理 JS 檔案 ---
            # 條件：是 .js 檔，且不是 .min.js
            if filename.endswith('.js') and not filename.endswith('.min.js'):
                
                # ★ 修改點：檢查檔名是否在排除清單中
                if filename in SKIP_FILES:
                    print(f"⏩ 跳過排除檔案: {filename}")
                    continue

                output_path = full_path.replace('.js', '.min.js')
                print(f"📦 正在壓縮 JS: {filename}...")
                
                try:
                    # shell=True 是 Windows 執行的關鍵
                    cmd = ['terser', full_path, '-o', output_path, '--compress', '--mangle']
                    result = subprocess.run(cmd, shell=True, check=True, capture_output=True, text=True)
                    print(f"   ✅ 完成 -> {os.path.basename(output_path)}")
                    count_js += 1
                except subprocess.CalledProcessError as e:
                    print(f"   ❌ 失敗: {e.stderr}")
                except Exception as e:
                    print(f"   ❌ 錯誤: {e}")

            # --- 2. 處理 CSS 檔案 ---
            # 條件：是 .css 檔，且不是 .min.css
            elif filename.endswith('.css') and not filename.endswith('.min.css'):
                output_path = full_path.replace('.css', '.min.css')
                print(f"🎨 正在壓縮 CSS: {filename}...")
                
                try:
                    # shell=True 是 Windows 執行的關鍵
                    cmd = ['cleancss', '-o', output_path, full_path]
                    result = subprocess.run(cmd, shell=True, check=True, capture_output=True, text=True)
                    print(f"   ✅ 完成 -> {os.path.basename(output_path)}")
                    count_css += 1
                except subprocess.CalledProcessError as e:
                    print(f"   ❌ 失敗: {e.stderr}")
                except Exception as e:
                    print(f"   ❌ 錯誤: {e}")

    print("-" * 30)
    print(f"🎉 作業結束！共壓縮了 {count_js} 個 JS 檔, {count_css} 個 CSS 檔。")

if __name__ == "__main__":
    minify_files()