/**
 * Google Apps Script Adapter - Optimized for Folder Structure
 */
(function() {
    class GASAdapter {
        constructor(gasConfig) {
            this.apiUrl = gasConfig.url;
            this.siteId = gasConfig.site_id || 'Default_Site';
        }

        async fetch(pageId, page = 1, perPage = 10) {
            const url = new URL(this.apiUrl);
            url.searchParams.set('site_id', this.siteId);
            url.searchParams.set('page_id', pageId);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', perPage);
            
            const response = await fetch(url.href);
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (err) {
                console.error('GAS JSON Error:', text);
                throw new Error('雲端資料讀取失敗');
            }
        }

        async save(data) {
            const payload = {
                site_id: this.siteId,
                ...data
            };

            await fetch(this.apiUrl, {
                method: 'POST',
                mode: 'no-cors',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify(payload)
            });

            return { success: true };
        }
    }
    window.GASAdapter = GASAdapter;
})();
