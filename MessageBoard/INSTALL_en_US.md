# MessageBoard Service Installation Guide

This guide will help you configure MessageBoard, including Local SQLite mode and Cloud Google Apps Script (GAS) mode.

---

## 1. Quick Start (Local SQLite Mode)

If you plan to store comments on your own server:

1.  **Check Permissions**: Ensure the `MessageBoard/data/` directory is writable (777).
2.  **Set Admin**: Edit `MessageBoard/config/config.php` to set your admin username and password.
3.  **Configure Plugin**: Edit `MessageBoard/config/config.js`:
    *   Set `mode` to `'local'`.
4.  **Done**: The system will automatically create database files when the first comment is submitted.

---

## 2. Cloud Deployment (GAS Mode)

If you are using a serverless environment or want to store data in Google Sheets:

### Step 1: Create GAS Script
1.  Go to [Google Apps Script Dashboard](https://script.google.com/home).
2.  Click "New Project" and name it `MB_Backend`.
3.  Paste the content of `MessageBoard/gas/Code.gs` and save.

### Step 2: Deploy as Web App
1.  Click **Deploy** > **New Deployment**.
2.  Select type: **Web App**.
3.  Set as follows:
    *   **Execute as**: **Me** (Your Google Account).
    *   **Who has access**: **Anyone**.
4.  Click Deploy and complete the Google authorization (Advanced > Go to project > Allow).
5.  **Copy the "Web App URL"**.

### Step 3: Update Config
1.  Edit `MessageBoard/config/config.js`:
    *   Set `mode` to `'gas'`.
    *   Paste the URL into `gas_url`.

---

## 3. Blog Integration

MessageBoard is fully integrated with this blog system:

1.  Open `config.js` in the blog root.
2.  Set plugin path: `guestbook_plugin: 'MessageBoard/static/guestbook.js'`.
3.  Set pagination: `guestbook_per_page: 5`.
4.  Rebuild Blog: Run `php make_html.php -f` in the root directory.

---

## 4. Admin Panel Usage

Access `your-site/MessageBoard/admin/`:

*   **Login**: Use credentials from `config.php`.
*   **Mode Selection**: Choose to manage SQLite or GAS data during login.
*   **Message Management**:
    *   Filter comments by "Site" and "Page".
    *   **Deletion**: Direct deletion in SQLite mode; use Google Sheets or the remote delete feature in GAS mode.
*   **System Settings**: Adjust plugin language, theme (Default/Dark), and pagination directly from the dashboard.

---

## 5. Troubleshooting

*   **"Unexpected token '<'" Error**: Usually caused by a 404 HTML response. Verify `api_url` or `gas_url` in `config.js`.
*   **Changes not reflecting**: Browser cache issue. Use `Ctrl + F5` to force refresh.
*   **GAS not loading**: Ensure "Who has access" is set to "Anyone" and the script is properly authorized.
