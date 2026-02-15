/**
 * MessageBoard Serverless Backend (GAS) - 極致擴充版
 * 結構：MessageBoard_Data / [Site_ID] / [Page_ID].spreadsheet
 */

const ROOT_FOLDER_NAME = "MessageBoard_Data";
const TIMEZONE = "GMT+8";

function doPost(e) {
  try {
    const params = JSON.parse(e.postData.contents);
    const siteId = params.site_id || "Default_Site";
    const pageId = params.page_id || "index";
    
    // 取得該站點的專屬目錄
    const siteFolder = getOrCreateFolder(getMBRoot(), siteId);
    
    // 取得或建立該頁面的專屬試算表
    const ss = getOrCreateSpreadsheet(siteFolder, pageId);
    const sheet = ss.getSheets()[0];
    
    const id = "msg_" + new Date().getTime();
    const createdAt = Utilities.formatDate(new Date(), TIMEZONE, "yyyy-MM-dd HH:mm:ss");
    
    // 寫入資料 (此時表中只有該頁面的留言，所以不需儲存 page_id 亦可，但為了備份建議保留)
    sheet.appendRow([
      id,
      params.parent_id || 0,
      params.name,
      params.content,
      createdAt
    ]);

    return sendJson({ success: true, id: id });
  } catch (err) {
    return sendJson({ success: false, message: err.toString() });
  }
}

function doGet(e) {
  try {
    const siteId = e.parameter.site_id || "Default_Site";
    const pageId = e.parameter.page_id || "index";
    const page = parseInt(e.parameter.page || 1);
    const perPage = parseInt(e.parameter.per_page || 5);
    
    const root = getMBRoot();
    const siteFolders = root.getFoldersByName(siteId);
    if (!siteFolders.hasNext()) return sendEmpty();
    
    const siteFolder = siteFolders.next();
    const files = siteFolder.getFilesByName(pageId);
    if (!files.hasNext()) return sendEmpty();
    
    const ss = SpreadsheetApp.open(files.next());
    const sheet = ss.getSheets()[0];
    const data = sheet.getDataRange().getValues();
    if (data.length <= 1) return sendEmpty();

    const headers = data.shift();
    const allMessages = data.map(row => {
      let obj = {};
      headers.forEach((h, i) => obj[h] = row[i]);
      // 補上 page_id 回應給前端 (雖然前端本來就知道)
      obj.page_id = pageId;
      return obj;
    });

    // 分頁處理 (由於現在表內全是該頁留言，不需過濾 page_id)
    const parents = allMessages
      .filter(m => String(m.parent_id) === "0" || m.parent_id === 0)
      .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

    const offset = (page - 1) * perPage;
    const pagedParents = parents.slice(offset, offset + perPage);

    return sendJson({
      messages: allMessages,
      pagination: {
        total_parents: parents.length,
        current_page: page,
        per_page: perPage,
        total_pages: Math.ceil(parents.length / perPage),
        active_parents: pagedParents.map(p => p.id)
      }
    });
  } catch (err) {
    return sendJson({ error: true, message: err.toString() });
  }
}

// --- 輔助函式 ---

function getMBRoot() {
  const folders = DriveApp.getFoldersByName(ROOT_FOLDER_NAME);
  return folders.hasNext() ? folders.next() : DriveApp.createFolder(ROOT_FOLDER_NAME);
}

function getOrCreateFolder(parent, folderName) {
  const folders = parent.getFoldersByName(folderName);
  return folders.hasNext() ? folders.next() : parent.createFolder(folderName);
}

function getOrCreateSpreadsheet(folder, fileName) {
  const files = folder.getFilesByName(fileName);
  if (files.hasNext()) {
    return SpreadsheetApp.open(files.next());
  } else {
    const ss = SpreadsheetApp.create(fileName);
    const file = DriveApp.getFileById(ss.getId());
    folder.addFile(file);
    DriveApp.getRootFolder().removeFile(file);
    
    // 初始化表頭
    const sheet = ss.getSheets()[0];
    sheet.appendRow(["id", "parent_id", "name", "content", "created_at"]);
    sheet.setFrozenRows(1);
    return ss;
  }
}

function sendEmpty() {
  return sendJson({ messages: [], pagination: { total_parents: 0, active_parents: [] } });
}

function sendJson(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}
