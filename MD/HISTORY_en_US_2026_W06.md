# Vibe Coding History (2026_W06)

Recorded the development journey through Vibe Coding with Gemini CLI.

---

## 2026-02-02 (English)

### [23:11] Configuration Schema Optimization
- **Task**: Restructured `config.php` and synchronized `config.example.php`.

### [23:32] Static Path Standardisation
- **Task**: Modified `make_html.php` to output static articles to `post/` directory.

---

## 2026-02-03 (English)

### [00:05] API Architecture Refactoring
- **Task**: Moved `api_*.php` to `api/` directory.

### [00:25] Multi-CSS Theme Implementation
- **Task**: Created `blog-dark.css` and supported dynamic theme loading.

---

## 2026-02-04 (English)

### [12:00] SSG Pipeline Revamp
- **Task**: Optimized `make_html.php` using placeholders.

### [14:05] Regex-based Template Parsing
- **Task**: Rewrote core parsing logic using Regex, eliminating `DOMDocument` dependencies.

---

## 2026-02-05 (English)

### [10:15] Front-end Multi-language Support
- **Task**: Implemented frontend i18n with dynamic configuration.

### [12:30] Pure Static JSON API Mode
- **Task**: Implemented client-side routing and unified `data.json` generation.

---

## 2026-02-06 (English)

### [23:30] i18n Structure Flattening
- **Task**: Moved all language files to the `langs/` root.

### [23:45] Global PHP 5.x Compatibility
- **Task**: Executed large-scale syntax downgrading.

---

## 2026-02-07 (English)

### [14:15] Script Tag Protection in Content
- **Task**: Prevent `<script>` content in articles from executing.

### [14:40] Comprehensive API Refactoring
- **Task**: Eliminate code duplication and unify API logic.

### [15:30] SSG & Pagination Refactoring
- **Task**: Unify build logic and implement high-performance pagination.

---

## [v2026.02.08.20.36] - 2026-02-08 (English)

### Album Service Deep Integration
- **Path Flexibility**: Introduced `$album_path` in `config.php`, supporting flexible relative path settings.
- **Health Detection**: Implemented album service health check logic and dashboard integration.
- **Editor Integration**: Integrated album picker with dynamic paths and API-side path correction.
- **Settings Page Overhaul**: Refactored settings to separate backend and frontend configurations.
- **Stability Fixes**: Fixed division-by-zero, regex replacement logic, and static path correction.
