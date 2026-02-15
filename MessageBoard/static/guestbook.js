/**
 * MessageBoard Loader & Core Service - 循序載入修復版
 */
(function() {
    let basePath = '';
    let scriptParamSite = '';
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
        if (scripts[i].src && scripts[i].src.indexOf('guestbook.js') !== -1) {
            const src = scripts[i].src;
            basePath = src.substring(0, src.lastIndexOf('/') + 1);
            const url = new URL(src);
            scriptParamSite = url.searchParams.get('site');
            break;
        }
    }

    class MessageBoard {
        constructor(options = {}) {
            this.container = document.getElementById('esmessageboard-root');
            if (!this.container) return;

            // 1. 最底層的內部預設值
            this.internalDefaults = {
                mode: 'local',
                theme: 'default',
                lang: 'zh_TW',
                per_page: 5,
                gas_url: '',
                api_url: '',
                admin: { name: 'Admin', label: '站長' }
            };

            this.options = options;
            this.state = { page: 1, parent_id: 0 };
            this.init();
        }

        async init() {
            // 2. 循序載入所有資源
            await this.loadAllResources();
            
            // 3. 偵測環境與合併最終設定 (此時 window.MBConfig 與 window.MB_LANG 均已就緒)
            const blogOverrides = this.getBlogOverrides();
            this.config = this.deepMerge(
                this.internalDefaults, 
                window.MBConfig || {}, 
                blogOverrides, 
                this.options
            );

            this.context = this.detectContext();

            // 4. 初始化適配器
            if (this.config.mode === 'gas') {
                this.adapter = new window.GASAdapter({ url: this.config.gas_url, site_id: this.context.site });
            } else {
                if (!this.config.api_url) this.config.api_url = new URL('../api/message.php', basePath).href;
                this.adapter = new window.PHPAdapter({ api_url: this.config.api_url, site_id: this.context.site });
            }

            // 5. 渲染
            this.renderBaseLayout();
            await this.loadMessages();
        }

        async loadAllResources() {
            const ver = '?v=' + new Date().getTime();
            
            // A. 第一步：必須先拿到 config.js，否則不知道要載入哪個語系
            if (!window.MBConfig_Loaded) {
                await this.loadScript(basePath + '../config/config.js' + ver);
                window.MBConfig_Loaded = true;
            }

            // 臨時暫存目前的 lang 設定，決定下一步要載入什麼
            const currentLang = (window.MBConfig && window.MBConfig.lang) ? window.MBConfig.lang : this.internalDefaults.lang;
            const currentTheme = (window.MBConfig && window.MBConfig.theme) ? window.MBConfig.theme : this.internalDefaults.theme;

            // B. 第二步：並行載入剩餘資源 (語系、樣式、適配器)
            const themeSuffix = currentTheme === 'default' ? '' : `-${currentTheme}`;
            const secondaryResources = [
                this.loadScript(basePath + `../langs/plugin-${currentLang}.js` + ver),
                this.loadScript(basePath + 'adapter-gas.js' + ver),
                this.loadScript(basePath + 'adapter-php.js' + ver)
            ];

            // 載入 CSS
            if (!document.getElementById('mb-css')) {
                const link = document.createElement('link');
                link.id = 'mb-css'; link.rel = 'stylesheet'; link.href = `${basePath}guestbook${themeSuffix}.css${ver}`;
                document.head.appendChild(link);
            }

            await Promise.all(secondaryResources);
        }

        loadScript(src) {
            return new Promise((resolve) => {
                const s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = () => { console.warn('Failed to load:', src); resolve(); };
                document.head.appendChild(s);
            });
        }

        getBlogOverrides() {
            const overrides = {};
            if (typeof AppConfig !== 'undefined' && AppConfig.guestbook_per_page) {
                overrides.per_page = parseInt(AppConfig.guestbook_per_page);
            }
            return overrides;
        }

        deepMerge(target, ...sources) {
            sources.forEach(source => {
                for (let key in source) {
                    if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                        target[key] = this.deepMerge(target[key] || {}, source[key]);
                    } else if (source[key] !== undefined && source[key] !== '') {
                        target[key] = source[key];
                    }
                }
            });
            return target;
        }

        detectContext() {
            const urlParams = new URLSearchParams(window.location.search);
            const getMeta = (p) => {
                const el = document.querySelector(`meta[property="${p}"], meta[name="${p}"]`);
                return el ? el.getAttribute('content') : null;
            };
            const siteId = scriptParamSite || getMeta('og:site_name') || window.location.hostname.replace(/\./g, '_');
            const pageTitle = getMeta('og:title') || document.title;
            let pageId = urlParams.get('page');
            if (!pageId) {
                const path = window.location.pathname;
                pageId = path.split('/').pop().replace('.html', '') || 'index';
            }
            return { site: siteId, page_id: pageId, page_title: pageTitle };
        }

        renderBaseLayout() {
            const L = window.MB_LANG || {};
            this.container.innerHTML = `
                <div class="mb-wrapper theme-${this.config.theme}">
                    <div class="mb-form-area" id="mb-form">
                        <div id="mb-reply-hint" style="display:none; margin-bottom:10px; color:#4a90e2; font-size:0.9em;">
                            ${L.reply_hint || 'Reply to: '}<span id="mb-reply-to"></span> <a href="javascript:void(0)" id="mb-cancel-reply" style="margin-left:10px; color:#e74c3c;">${L.cancel_reply || 'Cancel'}</a>
                        </div>
                        <input type="text" id="mb-name" placeholder="${L.placeholder_name || 'Name'}" class="mb-input">
                        <textarea id="mb-content" placeholder="${L.placeholder_content || 'Content'}" class="mb-input mb-textarea"></textarea>
                        <button id="mb-submit" class="mb-btn">${L.submit_btn_text || 'Submit'}</button>
                    </div>
                    <div id="mb-list" class="mb-list-area"><p class="mb-status">${L.status_loading || 'Loading...'}</p></div>
                    <div id="mb-pagination" class="mb-pagination-area"></div>
                </div>
            `;
            document.getElementById('mb-submit').addEventListener('click', () => this.submitComment());
            document.getElementById('mb-cancel-reply').addEventListener('click', () => this.cancelReply());
        }

        async loadMessages(page = 1) {
            this.state.page = page;
            const listEl = document.getElementById('mb-list');
            const L = window.MB_LANG || {};
            listEl.innerHTML = `<p class="mb-status">${L.status_loading || 'Loading...'}</p>`;
            try {
                const res = await this.adapter.fetch(this.context.page_id, page, this.config.per_page);
                const threads = this.organizeThreads(res.messages || [], res.pagination?.active_parents || []);
                this.renderThreads(threads);
                this.renderPagination(res.pagination);
            } catch (err) { listEl.innerHTML = `<p class="mb-error">Error: ${err.message}</p>`; }
        }

        organizeThreads(allMessages, activeParentIds) {
            const activeIds = activeParentIds.map(String);
            const threads = {};
            allMessages.forEach(msg => {
                if (String(msg.parent_id) === "0") {
                    if (activeIds.includes(String(msg.id))) threads[msg.id] = { head: msg, discussion: [] };
                }
            });
            allMessages.forEach(msg => {
                if (String(msg.parent_id) !== "0") {
                    const rootId = this.findRootId(msg, allMessages);
                    if (threads[rootId]) threads[rootId].discussion.push(msg);
                }
            });
            return activeIds.map(id => {
                const thread = threads[id];
                if (thread) thread.discussion.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                return thread;
            }).filter(t => t);
        }

        findRootId(msg, allMessages) {
            let current = msg; let safety = 0;
            while (String(current.parent_id) !== "0" && safety < 10) {
                const parent = allMessages.find(m => String(m.id) === String(current.parent_id));
                if (!parent) break;
                current = parent; safety++;
            }
            return current.id;
        }

        renderThreads(threads) {
            const listEl = document.getElementById('mb-list');
            const L = window.MB_LANG || {};
            if (threads.length === 0) { listEl.innerHTML = `<p class="mb-empty">${L.status_empty || 'No comments.'}</p>`; return; }
            listEl.innerHTML = threads.map(thread => {
                const head = thread.head;
                const isAdmin = head.name === this.config.admin.name;
                let html = `<div class="mb-thread-box"><div class="mb-topic ${isAdmin ? 'mb-is-admin' : ''}"><div class="mb-item-meta"><span class="mb-author">${head.name}${isAdmin ? ` <span class="mb-badge">${L.badge_admin || 'Admin'}</span>` : ''}</span><span class="mb-date">${head.created_at}</span><a href="javascript:void(0)" class="mb-reply-link" onclick="window.esMessageBoardInstance.prepareReply('${head.id}', '${head.name.replace(/'/g, "\\'")}')">${L.btn_join || 'Join'}</a></div><div class="mb-item-content">${head.content}</div></div><div class="mb-discussion">`;
                html += thread.discussion.map(reply => {
                    const isReplyAdmin = reply.name === this.config.admin.name;
                    return `<div class="mb-reply-item ${isReplyAdmin ? 'mb-is-admin' : ''}"><div class="mb-item-meta"><span class="mb-author">${reply.name}${isReplyAdmin ? ` <span class="mb-badge">${L.badge_admin || 'Admin'}</span>` : ''}</span><span class="mb-date">${reply.created_at}</span><a href="javascript:void(0)" class="mb-reply-link" onclick="window.esMessageBoardInstance.prepareReply('${head.id}', '${reply.name.replace(/'/g, "\\'")}')">${L.btn_reply || 'Reply'}</a></div><div class="mb-item-content">${reply.content}</div></div>`;
                }).join('');
                return html + `</div></div>`;
            }).join('');
        }

        renderPagination(pagin) {
            const paginEl = document.getElementById('mb-pagination');
            if (!pagin || pagin.total_pages <= 1) { paginEl.innerHTML = ''; return; }
            let html = '<div class="mb-pagin-list">';
            if (pagin.current_page > 1) html += `<button class="mb-page-btn" onclick="window.esMessageBoardInstance.loadMessages(${pagin.current_page - 1})">&laquo;</button>`;
            for (let i = 1; i <= pagin.total_pages; i++) { html += `<button class="mb-page-btn ${i === pagin.current_page ? 'active' : ''}" onclick="window.esMessageBoardInstance.loadMessages(${i})">${i}</button>`; }
            if (pagin.current_page < pagin.total_pages) html += `<button class="mb-page-btn" onclick="window.esMessageBoardInstance.loadMessages(${pagin.current_page + 1})">&raquo;</button>`;
            paginEl.innerHTML = html + '</div>';
        }

        prepareReply(id, name) {
            this.state.parent_id = id;
            document.getElementById('mb-reply-hint').style.display = 'block';
            document.getElementById('mb-reply-to').innerText = name;
            document.getElementById('mb-content').focus();
            document.getElementById('mb-form').scrollIntoView({ behavior: 'smooth' });
        }

        cancelReply() { this.state.parent_id = 0; document.getElementById('mb-reply-hint').style.display = 'none'; }

        async submitComment() {
            const L = window.MB_LANG || {};
            const name = document.getElementById('mb-name').value.trim();
            const content = document.getElementById('mb-content').value.trim();
            if (!name || !content) return alert(L.msg_no_name_content || 'Required fields missing');
            const btn = document.getElementById('mb-submit');
            const originalBtnText = btn.innerText;
            btn.disabled = true; btn.innerHTML = `<span class="mb-spinner"></span> ${L.msg_sending || 'Sending...'}`;
            this.container.classList.add('mb-loading');
            try {
                await this.adapter.save({ ...this.context, name, content, parent_id: this.state.parent_id });
                document.getElementById('mb-content').value = ''; this.cancelReply(); await this.loadMessages(1);
            } catch (err) { alert((L.msg_fail || 'Fail: ') + err.message); } finally { 
                btn.disabled = false; btn.innerText = originalBtnText; this.container.classList.remove('mb-loading');
            }
        }
    }
    window.esMessageBoardInstance = new MessageBoard();
})();
