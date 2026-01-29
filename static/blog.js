function toTop() {
    window.document.body.scrollTop = 0;
    window.document.documentElement.scrollTop = 0;
}

function ui_init() {
    var toggler = document.getElementsByClassName("caret");
    var i;
    for (i = 0; i < toggler.length; i++) {
        toggler[i].addEventListener("click", function () {
            this.parentElement.querySelector(".nested").classList.toggle("active");
            this.classList.toggle("caret-down");
        });
    }
}

/**
 * 針對 HTML 內容中的圖片進行載入優化
 * @param {string} htmlContent - 原始 HTML 字串
 * @param {boolean} forceLazy - 是否強制全部 Lazy Loading (只有第一篇文章傳 false，其餘 true)
 * @return {string} - 優化後的 HTML 字串
 */
function optimize_content_images(htmlContent, forceLazy) {
    if (!htmlContent) return "";

    var parser = new DOMParser();
    var doc = parser.parseFromString(htmlContent, "text/html");
    var images = doc.querySelectorAll("img");

    images.forEach(function (img, index) {
        // 只有當「不強制 Lazy」且是「該區塊的第一張圖」時，才設為 Eager
        if (!forceLazy && index === 0) {
            img.setAttribute("loading", "eager");
            img.setAttribute("fetchpriority", "high");
        } else {
            // 其他情況 (非第一張圖，或是第二篇以後的文章) 全部 Lazy
            img.setAttribute("loading", "lazy");
            img.removeAttribute("fetchpriority"); // 確保移除可能存在的優先權屬性
        }
    });

    return doc.body.innerHTML;
}

var isFirstImage = true;

/**
 * 針對 HTML 內容中的圖片進行載入優化 (Flag 狀態版)
 * 邏輯：檢查傳入的 status 物件，如果還沒找到第一張圖，就設為 Eager 並更新狀態；否則全部 Lazy。
 * * @param {string} htmlContent - 原始 HTML 字串
 * @param {object} status - 狀態物件，格式為 { isFirstImageFound: false }
 * @return {string} - 優化後的 HTML 字串
 */
function optimize_content_images(htmlContent) {
    if (!htmlContent) return "";

    var parser = new DOMParser();
    var doc = parser.parseFromString(htmlContent, "text/html");
    var images = doc.querySelectorAll("img");

    images.forEach(function (img) {
        // 檢查全域狀態：是否已經找到過第一張圖了？
        if (isFirstImage === true) {
            // 找到了！這是網頁載入後遇到的「第一張圖片」
            img.setAttribute("loading", "eager");
            img.setAttribute("fetchpriority", "high");

            // 【關鍵】立刻把旗標設為 true，鎖死狀態
            // 之後不管是在這篇文章，還是下一篇文章，這行永遠不會再執行
            isFirstImage = false;
        } else {
            // 旗標已經是 true，代表 quota 用完了，剩下的全部 Lazy
            img.setAttribute("loading", "lazy");
            img.removeAttribute("fetchpriority");
        }
    });

    return doc.body.innerHTML;
}

/**
 * 通用樣板生成器
 * @param {Array|Object} data - 資料來源 (陣列 或 物件)
 * @param {string} templateId - Template 的 ID
 * @param {string|null} containerId - (選填) 要插入的容器 ID。若為 null，則只回傳字串。
 * @param {Function} callback - 替換邏輯函式 (接收 htmlString, value, key)，需回傳處理後的 html。
 * @returns {string} 最終生成的 HTML 字串
 */
function renderTemplateGenerator(data, templateId, containerId, callback) {
    // 1. 檢查資料是否存在
    if (!data) return "";

    // 2. 取得樣板字串
    const tmplEl = document.getElementById(templateId);
    if (!tmplEl) {
        console.warn("Template not found:", templateId);
        return "";
    }
    const templateStr = tmplEl.innerHTML;

    // 3. 準備迭代的陣列 (如果是物件，就抓 Keys)
    const isArray = Array.isArray(data);
    const items = isArray ? data : Object.keys(data);

    let finalHtml = "";

    // 4. 開始迴圈
    items.forEach(function (item) {
        // 統一參數：如果是陣列，val=item, key=index；如果是物件，val=data[key], key=key
        var val = isArray ? item : data[item];
        var key = isArray ? null : item;

        // 呼叫使用者的替換邏輯
        finalHtml += callback(templateStr, val, key);
    });

    // 5. 如果有指定容器，直接插入 DOM
    if (containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.insertAdjacentHTML("beforeend", finalHtml);
        }
    }

    // 6. 回傳字串 (方便巢狀結構使用)
    return finalHtml;
}

const queryString = window.location.search;
const pathName = window.location.pathname;

if (pathName.endsWith("blog_list.html")) {
    var commentEl = document.getElementById("blog_comment");
    if (commentEl) commentEl.style.display = "none";
}

if (pathName.endsWith("blog.html")) {
    var commentEl = document.getElementById("blog_comment");
    if (commentEl) commentEl.style.display = "none";

    // 使用 fetch 替代 $.ajax
    fetch(AppConfig["api_type"] + ".php" + queryString)
        .then(function (response) {
            if (!response.ok) {
                throw new Error("HTTP error " + response.status);
            }
            return response.json();
        })
        .then(function (res) {
            renderTemplateGenerator(res.category, "template_category", "category_list_all", function (html, val) {
                // html 是樣板原始碼, val 是陣列中的單一物件
                html = html.replace(/{{name}}/g, val.name);
                html = html.replace(/{{count}}/g, val.count);
                return html;
            });

            // ==============================================
            // Part C: 日期歸檔處理 (修正版)
            // ==============================================

            // 1. 建立目錄結構 (年與月)
            if (res.dates_count) {
                var myUL = document.getElementById("myUL");

                // 手動抓取樣板字串 (只抓一次，效能最佳化)
                var tmpStrYear = document.getElementById("tmpl_date_year").innerHTML;
                var tmpStrMonth = document.getElementById("tmpl_date_month").innerHTML;

                Object.keys(res.dates_count).forEach(function (key) {
                    var val = res.dates_count[key]; // 文章數量
                    var render_tmp = "";

                    // --- 情況 A: 年份 (key 長度為 4, e.g., "2026") ---
                    if (key.length == 4) {
                        render_tmp = tmpStrYear;
                        render_tmp = render_tmp.replace(/{{year}}/g, key);
                        render_tmp = render_tmp.replace(/{{count}}/g, val);

                        myUL.insertAdjacentHTML("beforeend", render_tmp);
                    }

                    // --- 情況 B: 月份 (key 長度為 6, e.g., "202601") ---
                    if (key.length == 6) {
                        var year = key.substring(0, 4);
                        var mon = key.substring(4, 6);

                        render_tmp = tmpStrMonth;
                        render_tmp = render_tmp.replace(/{{mon}}/g, mon);
                        render_tmp = render_tmp.replace(/{{key}}/g, key);
                        render_tmp = render_tmp.replace(/{{count}}/g, val);

                        // 尋找對應的年份容器
                        var yearEl = document.getElementById("year_" + year);
                        if (yearEl) {
                            yearEl.insertAdjacentHTML("beforeend", render_tmp);
                        }
                    }
                });
            }

            // 2. 填入文章列表 (該月份下的文章) - 這裡就可以完美使用通用函式了！
            if (res.date_post) {
                // 這裡的 key 是 "YYYYMM" (例如 202601)
                Object.keys(res.date_post).forEach(function (key) {
                    var postsArray = res.date_post[key];
                    var containerId = "year_mon_" + key; // 對應剛才建立的月份 ul ID

                    // 使用通用函式生成該月份的文章列表
                    renderTemplateGenerator(postsArray, "template_date_post_item", containerId, function (html, post) {
                        // post 是單一文章物件
                        // 假設 post_index 格式為 YYYYMMDDxx，取 6~8 位為日期
                        var day = post.post_index.substring(6, 8);

                        html = html.replace(/{{day}}/g, day);
                        html = html.replace(/{{link}}/g, post.post_index);
                        html = html.replace(/{{title}}/g, post.title); // 注意：確認您的資料來源 title 欄位名稱

                        return html;
                    });
                });
            }

            // 這裡 data 是物件，key 是標籤名，val 是數量
            renderTemplateGenerator(res.tags, "template_tag_item", "tag_list_all", function (html, val, key) {
                html = html.replace(/{{name}}/g, key); // key 是標籤名稱
                html = html.replace(/{{count}}/g, val); // val 是數量
                return html;
            });

            renderTemplateGenerator(res.posts, "tmpl_post_main", "post_body", function (mainHtml, post) {
                // 1. 生成 [內部] 標籤 HTML (利用函式只回傳字串的特性)
                // 這裡 data 直接給 post.post_tags，container 給 null
                var tagsInner = renderTemplateGenerator(
                    post.post_tags,
                    "tmpl_post_tag_item",
                    null,
                    function (tHtml, tVal) {
                        return tHtml.replace(/{{name}}/g, tVal);
                    },
                );

                // 套用 [外部] 標籤容器 (如果有標籤的話)
                var tagsBlock = "";
                if (tagsInner) {
                    // 這裡我們也可以用 renderTemplateGenerator，但因為只有一筆，手動 replace 比較快
                    var contTmpl = document.getElementById("tmpl_post_tag_container").innerHTML;
                    tagsBlock = contTmpl.replace(/{{items}}/g, tagsInner);
                }

                // 2. 生成 [內部] 分類 HTML
                var catsInner = renderTemplateGenerator(
                    post.post_category,
                    "tmpl_post_cat_item",
                    null,
                    function (cHtml, cVal) {
                        return cHtml.replace(/{{name}}/g, cVal);
                    },
                );

                // 套用 [外部] 分類容器
                var catsBlock = "";
                if (catsInner) {
                    var contTmpl = document.getElementById("tmpl_post_cat_container").innerHTML;
                    catsBlock = contTmpl.replace(/{{items}}/g, catsInner);
                }

                // 3. 替換主文章欄位
                mainHtml = mainHtml.replace(/{{link}}/g, post.post_index);
                mainHtml = mainHtml.replace(/{{time}}/g, post.post_time);
                mainHtml = mainHtml.replace(/{{title}}/g, post.post_title);

                // 5. 【關鍵應用】圖片優化
                // 將 globalImgStatus 傳進去，函式會自動判斷並更新它
                var optimizedContent = optimize_content_images(post.post_content);

                // 替換優化後的內容
                mainHtml = mainHtml.replace(/{{content}}/g, optimizedContent);

                // 替換區塊
                mainHtml = mainHtml.replace(/{{tags_block}}/g, tagsBlock);
                mainHtml = mainHtml.replace(/{{category_block}}/g, catsBlock);

                return mainHtml;
            });

            // FB 重繪 (保持原樣)
            if (typeof FB !== "undefined") {
                FB.XFBML.parse(document.getElementById("post_body"));
            }

            var loadingEl = document.getElementById("loading");
            if (loadingEl) loadingEl.style.display = "none";

            //set_image();
            ui_init();
            init_fb_like();
        })
        .catch(function (err) {
            alert(err);
        });
} else {
    // 隱藏元素的純 JS 寫法
    var elsToHide = ["AllTagList", "AllDateList", "loading", "AllcategoryList"];
    elsToHide.forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.style.display = "none";
    });
    init_fb_like();
}

function init_fb_like() {
    (function () {
        var newscript = document.createElement("script");
        newscript.type = "text/javascript";
        newscript.async = true;
        newscript.src = "https://connect.facebook.net/zh_TW/sdk.js#xfbml=1&version=v20.0";
        (document.getElementsByTagName("head")[0] || document.getElementsByTagName("body")[0]).appendChild(newscript);
    })();
}

function init_image_event() {
    const modelMapping = {
        "2304FPN6DC": "Xiaomi 15 Ultra",
    };

    function convertDMS_LatLonToDecimal(dmsArray, ref) {
        if (!dmsArray || dmsArray.length !== 3 || isNaN(dmsArray[0]) || isNaN(dmsArray[1]) || isNaN(dmsArray[2])) {
            return null;
        }
        const degrees = dmsArray[0];
        const minutes = dmsArray[1];
        const seconds = dmsArray[2];
        let decimal = degrees + minutes / 60 + seconds / 3600;
        if (ref === "S" || ref === "W") {
            decimal *= -1;
        }
        return decimal;
    }

    document.addEventListener("DOMContentLoaded", () => {
        const images = document.querySelectorAll("img[data-ShowCameraMeta='1']");
        const imagePromises = Array.from(images).map((img) => {
            return new Promise((resolve) => {
                if (img.complete && img.naturalHeight !== 0) {
                    processExif();
                } else {
                    img.addEventListener("load", processExif);
                    img.addEventListener("error", () => {
                        console.error(`圖片載入失敗: ${img.src}`);
                        const errorMessageDiv = document.createElement("div");
                        errorMessageDiv.style.color = "red";
                        errorMessageDiv.style.textAlign = "center";
                        errorMessageDiv.innerHTML = "無法載入圖片或獲取EXIF資訊";
                        if (img.closest("a")) img.closest("a").after(errorMessageDiv);
                        resolve();
                    });
                }

                function processExif() {
                    // EXIF 是一個外部函式庫 (exif-js)，非 jQuery，保留使用。
                    if (typeof EXIF === "undefined") {
                        resolve();
                        return;
                    }
                    EXIF.getData(img, function () {
                        let model = EXIF.getTag(this, "Model");
                        const make = EXIF.getTag(this, "Make");
                        const exposureTime = EXIF.getTag(this, "ExposureTime");
                        const fNumber = EXIF.getTag(this, "FNumber");
                        const isoSpeedRatings = EXIF.getTag(this, "ISOSpeedRatings");
                        const focalLength = EXIF.getTag(this, "FocalLength");
                        const focalLengthIn35mmFilm = EXIF.getTag(this, "FocalLengthIn35mmFilm");
                        const dateTimeOriginal = EXIF.getTag(this, "DateTimeOriginal");

                        const gpsLatitude = EXIF.getTag(this, "GPSLatitude");
                        const gpsLatitudeRef = EXIF.getTag(this, "GPSLatitudeRef");
                        const gpsLongitude = EXIF.getTag(this, "GPSLongitude");
                        const gpsLongitudeRef = EXIF.getTag(this, "GPSLongitudeRef");

                        if (modelMapping[model]) {
                            model = modelMapping[model];
                        }

                        const deviceName = make && model ? `${make} ${model}` : "無法取得設備名稱";

                        let formattedExposureTime;
                        if (exposureTime) {
                            if (exposureTime < 1) {
                                const denominator = Math.round(1 / exposureTime);
                                formattedExposureTime = `1/${denominator}s`;
                            } else {
                                formattedExposureTime = `${exposureTime}s`;
                            }
                        } else {
                            formattedExposureTime = "N/A";
                        }

                        const formattedFNumber = fNumber ? `f/${fNumber}` : "N/A";
                        const formattedISOSpeedRatings = isoSpeedRatings ? `ISO ${isoSpeedRatings}` : "N/A";
                        const formattedFocalLength = focalLength ? `${focalLength}mm` : "N/A";
                        const formattedFocalLengthIn35mmFilm = focalLengthIn35mmFilm
                            ? `(${focalLengthIn35mmFilm}mm 等效)`
                            : "";

                        const formattedDateTime = dateTimeOriginal
                            ? dateTimeOriginal.replace(/(\d{4}):(\d{2}):(\d{2})/, "$1-$2-$3")
                            : "N/A";

                        let gpsLinkHtml = "";
                        const lat = convertDMS_LatLonToDecimal(gpsLatitude, gpsLatitudeRef);
                        const lon = convertDMS_LatLonToDecimal(gpsLongitude, gpsLongitudeRef);

                        if (lat !== null && lon !== null) {
                            const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lon}`;
                            gpsLinkHtml = `<br><a href="${googleMapsUrl}" target="_blank" style="color: blue; text-decoration: underline;">在 Google Maps 上顯示位置</a>`;
                        }

                        const messageDiv = document.createElement("div");
                        messageDiv.style.color = "black";
                        messageDiv.style.fontWeight = "bold";
                        messageDiv.style.textAlign = "center";
                        messageDiv.style.fontSize = "14px";
                        messageDiv.style.marginTop = "5px";
                        messageDiv.innerHTML = `
                                設備名稱: ${deviceName}<br>
                                快門: ${formattedExposureTime} | 光圈: ${formattedFNumber} | ISO: ${formattedISOSpeedRatings}<br>
                                焦距: ${formattedFocalLength} ${formattedFocalLengthIn35mmFilm}<br>
                                拍攝時間: ${formattedDateTime}
                                ${gpsLinkHtml}
                            `;

                        const anchorElement = img.closest("a");
                        if (anchorElement) {
                            let nextSibling = anchorElement.nextSibling;
                            let hrElement = null;
                            while (nextSibling) {
                                if (nextSibling.nodeType === 1 && nextSibling.tagName === "HR") {
                                    hrElement = nextSibling;
                                    break;
                                }
                                nextSibling = nextSibling.nextSibling;
                            }

                            if (hrElement) {
                                hrElement.parentNode.insertBefore(messageDiv, hrElement);
                            } else {
                                anchorElement.after(messageDiv);
                            }
                        }

                        resolve();
                    });
                }
            });
        });

        Promise.all(imagePromises)
            .then(() => {
                console.log("所有符合條件的圖片都已載入並處理 EXIF 資訊完成！");
            })
            .catch((error) => {
                console.error("處理圖片時發生錯誤:", error);
            });
    });
}

init_image_event();
