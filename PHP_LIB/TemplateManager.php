<?php

/**
 * 微型樣板管理器 (Micro Template Manager)
 * 負責樣板載入、解析與變數替換
 * PHP 5.x Compatible
 */
class TemplateManager {
    protected $sourceHtml = '';
    protected $subTemplates = array();

    /**
     * 載入主樣板檔案
     */
    public function load($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Template file not found: " . $filePath);
        }
        $this->sourceHtml = file_get_contents($filePath);
        $this->parseSubTemplates();
    }

    /**
     * 解析 <template id="xxx"> 區塊
     */
    protected function parseSubTemplates() {
        // 使用 Regex 抓取 <template id="...">...</template>
        if (preg_match_all('/<template\s+id="([^"]+)"[^>]*>(.*?)<\/template>/is', $this->sourceHtml, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $id = $match[1];
                $innerHTML = $match[2];
                $this->subTemplates[$id] = $innerHTML;
            }
        }
    }

    /**
     * 取得原始主樣板內容
     */
    public function getSource() {
        return $this->sourceHtml;
    }

    /**
     * 取得特定 ID 的子樣板內容
     */
    public function getSubTemplate($id) {
        return isset($this->subTemplates[$id]) ? $this->subTemplates[$id] : '';
    }

    /**
     * 核心渲染方法：將數據替換入樣板字串
     * @param string $templateContent 樣板字串
     * @param array $data 鍵值對資料 array('title' => 'Hello') 會替換 {{title}}
     * @return string
     */
    public function render($templateContent, $data) {
        if (empty($data)) {
            return $templateContent;
        }

        $search = array();
        $replace = array();

        foreach ($data as $key => $value) {
            // 支援直接傳入 {{key}} 或 key，這裡統一處理
            $searchKey = (strpos($key, '{{') === 0) ? $key : '{{' . $key . '}}';
            $search[] = $searchKey;
            $replace[] = $value;
        }

        return str_replace($search, $replace, $templateContent);
    }

    /**
     * 列表渲染方法：針對陣列資料重複渲染同一個子樣板
     * @param string $templateId 子樣板 ID
     * @param array $dataList 資料列表 (二維陣列)
     * @return string 串接好的 HTML
     */
    public function renderList($templateId, $dataList) {
        $html = '';
        $template = $this->getSubTemplate($templateId);
        
        if (empty($template) || empty($dataList)) {
            return '';
        }

        foreach ($dataList as $item) {
            $html .= $this->render($template, $item);
        }
        return $html;
    }

    /**
     * 工具：移除 HTML 中的特定標籤區塊 (如 <template>)
     */
    public function removeTags($html, $tagName) {
        return preg_replace('/<' . $tagName . '\b[^>]*>.*?<\/' . $tagName . '>\s*/is', '', $html);
    }
}