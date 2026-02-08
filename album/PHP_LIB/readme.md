# PHP Libraries for Album Service

此目錄存放相簿服務所需的第三方 PHP 函式庫。

## 1. exif-tools (Optional)
若您的伺服器環境不支援官方 `exif` 擴充套件，您可以手動安裝純 PHP 實作的 `exif-tools` 作為替代方案。

### 安裝方式：
1. 下載 [1tomany/exif-tools](https://github.com/1tomany/exif-tools) (或其他相容的 PHP EXIF 解析庫)。
2. 將解壓縮後的檔案放入 `exif-tools/` 目錄中。
3. `make_album.php` 會自動偵測並載入。

*注意：若無此套件且無官方 EXIF 支援，系統將自動回退至前端 JS 解析模式。*
