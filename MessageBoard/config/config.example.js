/**
 * MessageBoard Global Configuration - Example
 */
window.MBConfig = {
    // 運行模式: 'local' 或 'gas'
    mode: 'local',

    // 語系設定: 'zh_TW' 或 'en_US'
    lang: 'zh_TW',

    // 每頁顯示留言主題數
    per_page: 5,

    // GAS 服務網址
    gas_url: 'https://script.google.com/macros/s/AKfycby5bQ4WG9-Tac4lX6ilvoA11DTI9aGy-KH2GgOAmBOZYg-JRDMJDZGaknW-ZI1sz1kbKA/exec',

    // 本地 API 網址 (留空則自動推算)
    api_url: '',

    // 介面主題: 'default' (明亮現代), 'dark' (深色高對比)
    theme: 'default',

    // 管理員標記 (用於自動比對姓名顯示站長標籤)
    admin: {
        name: 'Baxermux',
        label: '站長'
    }
};
