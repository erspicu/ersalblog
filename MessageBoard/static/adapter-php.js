/**
 * PHP / SQLite Adapter - Multi-site Support
 */
(function() {
    class PHPAdapter {
        constructor(localConfig) {
            this.apiUrl = localConfig.api_url;
            this.siteId = localConfig.site_id || '';
        }

        async fetch(pageId, page = 1, perPage = 10) {
            const url = new URL(this.apiUrl, window.location.href);
            url.searchParams.set('site_id', this.siteId);
            url.searchParams.set('page_id', pageId);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', perPage);
            
            const response = await fetch(url.href);
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                console.error('PHP JSON Error:', text);
                throw new Error('資料讀取失敗');
            }
        }

        async save(data) {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    site_id: this.siteId, 
                    page_title: data.page_title,
                    ...data 
                })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.message || '儲存失敗');
            return result;
        }
    }
    window.PHPAdapter = PHPAdapter;
})();
