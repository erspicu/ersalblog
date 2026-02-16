/**
 * MessageBoard Serverless Backend (GAS) - 標題識別版
 */

const CONFIG = {
  ROOT_FOLDER: "MessageBoard_Data",
  SHEET_NAME: "Comments",
  TIMEZONE: "GMT+8"
};

function doPost(e) {
  try {
    const params = JSON.parse(e.postData.contents);
    const action = params.action || 'save';
    
    if (action === 'delete') return handleDelete(params);
    
    // 儲存留言
    const siteId = params.site_id || "Default_Site";
    const pageId = params.page_id || "index";
    const siteFolder = getOrCreateFolder(getMBRoot(), siteId);
    
    const files = siteFolder.getFilesByName(pageId);
    let ss;
    if (files.hasNext()) {
      ss = SpreadsheetApp.open(files.next());
    } else {
      ss = SpreadsheetApp.create(pageId);
      let file = DriveApp.getFileById(ss.getId());
      siteFolder.addFile(file);
      DriveApp.getRootFolder().removeFile(file);
      // 初始化表頭
      ss.getSheets()[0].appendRow(["id", "parent_id", "name", "content", "avatar", "google_sub", "created_at"]);
      ss.setFrozenRows(1);
    }

    // 自動相容檢查：如果現有表頭沒有 avatar，則補上
    const sheet = ss.getSheets()[0];
    const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    if (headers.indexOf("avatar") === -1) {
      sheet.insertColumnAfter(4);
      sheet.getRange(1, 5).setValue("avatar");
      sheet.insertColumnAfter(5);
      sheet.getRange(1, 6).setValue("google_sub");
    }

    // 重新取得最新表頭索引
    const currentHeaders = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    const hMap = {}; currentHeaders.forEach((h, i) => hMap[h] = i + 1);

    // 如果有傳入標題，更新檔案描述 (Description) 以供後台識別
    if (params.page_title) {
      DriveApp.getFileById(ss.getId()).setDescription(params.page_title);
    }
    
    const id = "msg_" + new Date().getTime();
    const createdAt = Utilities.formatDate(new Date(), CONFIG.TIMEZONE, "yyyy-MM-dd HH:mm:ss");
    
    // 準備寫入資料
    const rowData = new Array(currentHeaders.length);
    rowData[hMap["id"]-1] = id;
    rowData[hMap["parent_id"]-1] = params.parent_id || 0;
    rowData[hMap["name"]-1] = params.name;
    rowData[hMap["content"]-1] = params.content;
    rowData[hMap["avatar"]-1] = params.avatar || "";
    rowData[hMap["google_sub"]-1] = params.google_sub || "";
    rowData[hMap["created_at"]-1] = createdAt;

    sheet.appendRow(rowData);
    
    return sendJson({ success: true, id: id });
  } catch (err) {
    return sendJson({ success: false, message: err.toString() });
  }
}

function doGet(e) {
  try {
    const action = e.parameter.action;
    if (action === 'list_sites') return listSites();
    if (action === 'list_pages') return listPages(e.parameter.site_id);
    return handleFetch(e.parameter);
  } catch (err) {
    return sendJson({ error: true, message: err.toString() });
  }
}

function handleFetch(p) {
  const siteId = p.site_id || "Default_Site";
  const pageId = p.page_id || "index";
  const root = getMBRoot();
  const siteFolders = root.getFoldersByName(siteId);
  if (!siteFolders.hasNext()) return sendEmpty();
  const siteFolder = siteFolders.next();
  const files = siteFolder.getFilesByName(pageId);
  if (!files.hasNext()) return sendEmpty();
  
  const ss = SpreadsheetApp.open(files.next());
  const data = ss.getSheets()[0].getDataRange().getValues();
  if (data.length <= 1) return sendEmpty();

  const headers = data.shift();
  const allMessages = data.map(row => {
    let obj = {};
    headers.forEach((h, i) => obj[h] = row[i]);
    return obj;
  });

  const parents = allMessages
    .filter(m => String(m.parent_id) === "0" || m.parent_id === 0)
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

  const page = parseInt(p.page || 1);
  const perPage = parseInt(p.per_page || 5);
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
}

function listPages(siteId) {
  const root = getMBRoot();
  const siteFolders = root.getFoldersByName(siteId);
  if (!siteFolders.hasNext()) return sendJson({ pages: [] });
  
  const files = siteFolders.next().getFiles();
  const pages = [];
  while (files.hasNext()) {
    const f = files.next();
    pages.push({
      id: f.getName(),
      title: f.getDescription() || f.getName() // 優先使用描述 (即網頁標題)
    });
  }
  return sendJson({ pages: pages });
}

function listSites() {
  const root = getMBRoot();
  const folders = root.getFolders();
  const sites = [];
  while (folders.hasNext()) sites.push(folders.next().getName());
  return sendJson({ sites: sites });
}

function handleDelete(params) {
  const siteId = params.site_id;
  const pageId = params.page_id;
  const targetId = params.id;
  const root = getMBRoot();
  const siteFolders = root.getFoldersByName(siteId);
  if (!siteFolders.hasNext()) throw "Site not found";
  const siteFolder = siteFolders.next();
  const files = siteFolder.getFilesByName(pageId);
  if (!files.hasNext()) throw "Page not found";
  
  const ss = SpreadsheetApp.open(files.next());
  const sheet = ss.getSheets()[0];
  const data = sheet.getDataRange().getValues();
  let deletedCount = 0;
  for (let i = data.length - 1; i >= 1; i--) {
    if (String(data[i][0]) === String(targetId) || String(data[i][1]) === String(targetId)) {
      sheet.deleteRow(i + 1);
      deletedCount++;
    }
  }
  return sendJson({ success: true, count: deletedCount });
}

function getMBRoot() {
  const folders = DriveApp.getFoldersByName(CONFIG.ROOT_FOLDER);
  return folders.hasNext() ? folders.next() : DriveApp.createFolder(CONFIG.ROOT_FOLDER);
}

function getOrCreateFolder(parent, folderName) {
  const folders = parent.getFoldersByName(folderName);
  return folders.hasNext() ? folders.next() : parent.createFolder(folderName);
}

function sendEmpty() { return sendJson({ messages: [], pagination: { total_parents: 0, active_parents: [] } }); }
function sendJson(obj) { return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON); }
