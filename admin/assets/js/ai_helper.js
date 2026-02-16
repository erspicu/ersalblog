/**
 * AI Helper for Blog Post Editing - Standard Gemini 3 Version
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log("AI Helper JS Loaded (Standard Mode)");
    const aiModalEl = document.getElementById('aiHelperModal');
    const btnTrigger = document.getElementById('btn-ai-helper');

    if (!aiModalEl || !btnTrigger) return;

    const aiModal = new bootstrap.Modal(aiModalEl);
    const btnStartAnalyze = document.getElementById('btn-ai-start-analyze');
    const btnApplySelected = document.getElementById('btn-ai-apply-selected');
    
    const initView = document.getElementById('ai-init-view');
    const loadingView = document.getElementById('ai-loading-view');
    const resultView = document.getElementById('ai-result-view');
    const errDiv = document.getElementById('ai-error-msg');
    
    let aiResultData = null;

    btnTrigger.addEventListener('click', () => {
        resetView();
        aiModal.show();
    });

    btnStartAnalyze.addEventListener('click', async () => {
        errDiv.classList.add('d-none');
        
        const selectedTasks = Array.from(document.querySelectorAll('.ai-task-checkbox:checked')).map(cb => cb.value);
        if (selectedTasks.length === 0) {
            alert('請至少選擇一個 AI 任務。');
            return;
        }

        const titleInput = document.querySelector('input[name="post_title"]');
        const title = titleInput ? titleInput.value : '';
        let content = '';
        
        if (window.tinymce && tinymce.get('post_content')) {
            content = tinymce.get('post_content').getContent();
        } else {
            const area = document.getElementById('post_content');
            content = area ? area.value : '';
        }

        if (!content.trim() || content.length < 20) {
            alert('文章內容太短，請輸入更多內容。');
            return;
        }

        const loadingText = document.querySelector('#ai-loading-view p');
        if (selectedTasks.includes('refine')) {
            loadingText.textContent = '由於包含「內文修潤」，分析可能需要 30-60 秒，請耐心等候...';
        } else {
            loadingText.textContent = 'AI 正在分析並優化中，請稍候...';
        }

        showView('loading');

        try {
            const formData = new FormData();
            formData.append('content', `Title: ${title}\n\nContent:\n${content}`);
            formData.append('tasks', selectedTasks.join(','));
            
            const csrfInput = document.querySelector('form input[name="csrf_token"]') || document.querySelector('input[name="csrf_token"]');
            if (csrfInput) {
                formData.append('csrf_token', csrfInput.value);
            }

            const apiUrl = 'api_ai_helper.php';
            console.log("Fetching AI API:", apiUrl);
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            console.log("AI API Raw Response:", text); // 輸出原始回傳內容

            let data;
            try {
                data = JSON.parse(text);
                console.log("AI Parsed JSON Data:", data); // 輸出解析後的數據
            } catch (e) {
                console.error("Failed to parse JSON. Raw response:", text);
                throw new Error("Invalid server response format.");
            }

            if (data.error) {
                errDiv.textContent = data.error;
                errDiv.classList.remove('d-none');
                showView('init');
                return;
            }

            aiResultData = data;
            renderResult(data, selectedTasks);
            showView('result');

        } catch (err) {
            console.error(err);
            errDiv.textContent = '發生錯誤：' + err.message;
            errDiv.classList.remove('d-none');
            showView('init');
        }
    });

    function renderResult(data, tasks) {
        const fields = {
            'title': 'check-apply-title',
            'filename': 'check-apply-filename',
            'desc': 'check-apply-desc',
            'tags': 'check-apply-tags',
            'refine': 'check-apply-content'
        };

        Object.keys(fields).forEach(key => {
            const container = document.getElementById(fields[key]).closest('.list-group-item');
            if (tasks.includes(key)) {
                container.classList.remove('d-none');
                document.getElementById(fields[key]).checked = true;
            } else {
                container.classList.add('d-none');
                document.getElementById(fields[key]).checked = false;
            }
        });

        if (data.title) document.getElementById('ai-res-title').value = data.title;
        if (data.filename) document.getElementById('ai-res-filename').value = data.filename;
        if (data.description) document.getElementById('ai-res-desc').value = data.description;
        if (data.refined_content) document.getElementById('ai-res-content-preview').textContent = data.refined_content;
        
        if (data.tags) {
            const tagsContainer = document.getElementById('ai-res-tags-container');
            tagsContainer.innerHTML = '';
            data.tags.forEach(tag => {
                const span = document.createElement('span');
                span.className = 'badge bg-info text-dark me-1';
                span.textContent = tag;
                tagsContainer.appendChild(span);
            });
            document.getElementById('ai-res-tags-raw').value = data.tags.join(', ');
        }
    }

    btnApplySelected.addEventListener('click', () => {
        if (!aiResultData) return;
        const selectedFields = Array.from(document.querySelectorAll('.ai-apply-checkbox:checked')).map(cb => cb.value);
        
        if (selectedFields.includes('title')) {
            document.querySelector('input[name="post_title"]').value = document.getElementById('ai-res-title').value;
        }
        if (selectedFields.includes('filename')) {
            const input = document.querySelector('input[name="post_filename"]');
            const newVal = document.getElementById('ai-res-filename').value;
            if (!input.value || confirm('確定要套用 AI 建議的檔案名稱嗎？')) input.value = newVal;
        }
        if (selectedFields.includes('description')) {
            document.querySelector('input[name="post_description"]').value = document.getElementById('ai-res-desc').value;
        }
        if (selectedFields.includes('tags')) {
            document.querySelector('input[name="post_tags"]').value = aiResultData.tags.join(', ');
        }
        if (selectedFields.includes('content')) {
            if (window.tinymce && tinymce.get('post_content')) tinymce.get('post_content').setContent(aiResultData.refined_content);
            else document.getElementById('post_content').value = aiResultData.refined_content;
        }
        aiModal.hide();
    });

    function showView(viewName) {
        initView.classList.add('d-none');
        loadingView.classList.add('d-none');
        resultView.classList.add('d-none');
        if (viewName === 'init') initView.classList.remove('d-none');
        if (viewName === 'loading') loadingView.classList.remove('d-none');
        if (viewName === 'result') resultView.classList.remove('d-none');
    }

    function resetView() {
        showView('init');
        aiResultData = null;
        if (errDiv) errDiv.classList.add('d-none');
    }
});
