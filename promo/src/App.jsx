import React from 'react';
import { Container, Row, Col, Card, Badge, Button, Nav, Navbar, ListGroup } from 'react-bootstrap';
import { motion } from 'framer-motion';
import { 
  Camera, 
  Cpu, 
  Database, 
  Zap, 
  History, 
  Download, 
  ArrowRight, 
  CheckCircle,
  Code2,
  Layers,
  Sparkles,
  Terminal,
  ShieldCheck,
  Globe,
  Settings,
  FileCode,
  Smartphone
} from 'lucide-react';
import './App.css';

const FeatureCard = ({ icon: Icon, title, text, delay }) => (
  <motion.div
    initial={{ opacity: 0, y: 20 }}
    whileInView={{ opacity: 1, y: 0 }}
    transition={{ duration: 0.5, delay }}
    viewport={{ once: true }}
    className="h-100"
  >
    <Card className="h-100 border-0 shadow-sm feature-card">
      <Card.Body className="p-4 p-xxl-5">
        <div className="icon-wrapper mb-4">
          <Icon size={32} className="text-primary" />
        </div>
        <Card.Title className="fw-bold mb-3 h4">{title}</Card.Title>
        <Card.Text className="text-muted">{text}</Card.Text>
      </Card.Body>
    </Card>
  </motion.div>
);

const TechBadge = ({ children }) => (
  <Badge bg="light" text="dark" className="border me-2 mb-2 px-3 py-2 fw-normal">
    {children}
  </Badge>
);

function App() {
  return (
    <div className="app-wrapper">
      {/* Navigation */}
      <Navbar bg="white" expand="lg" sticky="top" className="shadow-sm py-3 d-flex justify-content-center">
        <Container className="custom-container">
          <Navbar.Brand href="#home" className="fw-bold d-flex align-items-center fs-4">
            <Camera className="me-2 text-primary" size={28} /> ErsalBlog
          </Navbar.Brand>
          <Navbar.Toggle aria-controls="basic-navbar-nav" />
          <Navbar.Collapse id="basic-navbar-nav">
            <Nav className="ms-auto fw-medium">
              <Nav.Link href="#motivation">開發動機</Nav.Link>
              <Nav.Link href="#architecture">技術架構</Nav.Link>
              <Nav.Link href="#vibe-coding">Vibe Coding</Nav.Link>
              <Nav.Link href="#specs">系統規範</Nav.Link>
              <Nav.Link href="#roadmap">未來藍圖</Nav.Link>
              <Button variant="primary" className="ms-lg-3 rounded-pill px-4 shadow-sm">立即預覽</Button>
            </Nav>
          </Navbar.Collapse>
        </Container>
      </Navbar>

      {/* Hero Section */}
      <section id="home" className="hero-section py-5">
        <Container className="custom-container">
          <Row className="align-items-center min-vh-80 py-5">
            <Col lg={6} className="mb-5 mb-lg-0">
              <motion.div
                initial={{ opacity: 0, x: -50 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.8 }}
              >
                <Badge bg="primary-soft" text="primary" className="mb-3 px-3 py-2 rounded-pill fs-6">
                  <Sparkles size={16} className="me-2" /> 2026 Vibe Coding 實踐計畫
                </Badge>
                <h1 className="display-2 fw-extrabold mb-4 lh-sm">
                  ErsalBlog: <br />
                  <span className="text-primary">極簡、專業、自由</span> <br />
                  的攝影專屬部落格
                </h1>
                <p className="lead text-muted mb-5 fs-4 lh-base">
                  從「匠人手工打磨」轉化為「AI 協作 Vibe Coding」。<br />
                  這不只是一個 Blog，這是一個關於效率、意志與極致美學的技術實踐。
                </p>
                <div className="d-flex flex-wrap gap-3">
                  <Button variant="primary" size="lg" className="rounded-pill px-5 py-3 d-flex align-items-center shadow">
                    查看 GitHub <ArrowRight size={20} className="ms-2" />
                  </Button>
                  <Button variant="outline-dark" size="lg" className="rounded-pill px-5 py-3" href="https://www.baxermux.org/ersalblog" target="_blank">
                    進入攝影部落格
                  </Button>
                </div>
              </motion.div>
            </Col>
            <Col lg={6} className="position-relative">
              <motion.div
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ duration: 1 }}
                className="hero-image-container"
              >
                <div className="code-card p-4 p-xxl-5 shadow-2xl rounded-4 bg-dark text-white border-0">
                  <div className="d-flex gap-2 mb-4 border-bottom border-secondary pb-3">
                    <div className="dot dot-red"></div>
                    <div className="dot dot-yellow"></div>
                    <div className="dot dot-green"></div>
                    <span className="ms-auto text-secondary small font-monospace">vibe_engine.sh</span>
                  </div>
                  <pre className="m-0 font-monospace fs-5">
                    <code>{`# 匠人精神與 AI 協作的完美融合
gemini-cli > "為我的攝影作品打造
              一個極致加速的後台"

[DONE] 混合 SSG/SPA 渲染器已就緒
[DONE] SQLite/MySQL 數據橋接完成
[DONE] 多語系介面 (ZH/EN) 自動生成
[INFO] 平均回應速度: < 50ms`}</code>
                  </pre>
                </div>
                {/* Decorative floating badges */}
                <motion.div 
                  animate={{ y: [0, -20, 0] }}
                  transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
                  className="floating-badge bg-white shadow p-3 rounded-3 position-absolute top-0 end-0 d-none d-xxl-block"
                >
                  <CheckCircle className="text-success me-2" /> 效能優化 100%
                </motion.div>
              </motion.div>
            </Col>
          </Row>
        </Container>
      </section>

      {/* Motivation Section */}
      <section id="motivation" className="py-5 bg-white">
        <Container className="custom-container py-5">
          <Row className="justify-content-center text-center mb-5">
            <Col lg={10} xxl={8}>
              <h2 className="display-4 fw-bold mb-4">為什麼選擇「自造」？</h2>
              <p className="lead text-muted fs-4">
                在廣告橫行與框架臃腫的時代，我們重新定義部落格的純粹性。
                將原本用於照片整理的 Side Project，透過 Vibe Coding 昇華為專業級工具。
              </p>
            </Col>
          </Row>
          <Row className="g-5 py-4">
            <Col md={4}>
              <div className="text-center">
                <div className="mb-4 text-primary d-inline-block p-4 bg-primary-soft rounded-circle"><ShieldCheck size={48} /></div>
                <h3 className="fw-bold">意志下的自由</h3>
                <p className="text-muted fs-5">從 UI 到數據流，一切皆在掌控之中，絕無廣告與追蹤腳本。</p>
              </div>
            </Col>
            <Col md={4}>
              <div className="text-center">
                <div className="mb-4 text-primary d-inline-block p-4 bg-primary-soft rounded-circle"><Zap size={48} /></div>
                <h3 className="fw-bold">匠人精神 2.0</h3>
                <p className="text-muted fs-5">最初的極簡手工打磨，奠定了現在高效率 AI 開發的穩固基石。</p>
              </div>
            </Col>
            <Col md={4}>
              <div className="text-center">
                <div className="mb-4 text-primary d-inline-block p-4 bg-primary-soft rounded-circle"><Smartphone size={48} /></div>
                <h3 className="fw-bold">全屏響應</h3>
                <p className="text-muted fs-5">從手機到 4K 螢幕，皆能完美呈現攝影作品的細節與層次。</p>
              </div>
            </Col>
          </Row>
        </Container>
      </section>

      {/* Deep Dive Architecture */}
      <section id="architecture" className="py-5 bg-light">
        <Container className="custom-container py-5">
          <Row className="align-items-center g-5">
            <Col lg={5}>
              <h2 className="display-5 fw-bold mb-4">技術架構深探</h2>
              <p className="text-muted fs-5 mb-5">
                我們不追求技術的堆砌，而是追求最優解。ErsalBlog 採用混合式儲存與渲染，讓您在不同主機環境下都能游刃有餘。
              </p>
              <ListGroup variant="flush" className="bg-transparent mb-5">
                <ListGroup.Item className="bg-transparent px-0 py-3 border-bottom d-flex">
                  <Badge bg="primary" className="me-3 p-2 mt-1 rounded-circle"><CheckCircle size={14} /></Badge>
                  <div>
                    <h5 className="fw-bold mb-1">混合數據層 (Hybrid Data)</h5>
                    <p className="text-muted mb-0 small">支援 index_post.txt 文字檔索引，亦可一鍵同步至 MySQL 或 SQLite。</p>
                  </div>
                </ListGroup.Item>
                <ListGroup.Item className="bg-transparent px-0 py-3 border-bottom d-flex">
                  <Badge bg="primary" className="me-3 p-2 mt-1 rounded-circle"><CheckCircle size={14} /></Badge>
                  <div>
                    <h5 className="fw-bold mb-1">渲染引擎 (SSG + SPA)</h5>
                    <p className="text-muted mb-0 small">利用 PHP 預生成 HTML 提升 SEO，並透過 Vanilla JS 達成 SPA 的流暢感。</p>
                  </div>
                </ListGroup.Item>
                <ListGroup.Item className="bg-transparent px-0 py-3 border-bottom d-flex">
                  <Badge bg="primary" className="me-3 p-2 mt-1 rounded-circle"><CheckCircle size={14} /></Badge>
                  <div>
                    <h5 className="fw-bold mb-1">自動化工具鏈</h5>
                    <p className="text-muted mb-0 small">內建 mini.py 腳本，自動處理資產壓縮與效能優化。</p>
                  </div>
                </ListGroup.Item>
              </ListGroup>
            </Col>
            <Col lg={7}>
              <Row className="g-4">
                <Col md={6}>
                  <FeatureCard 
                    icon={Camera} 
                    title="攝影導向功能" 
                    text="整合 exif.js，自動解析光圈、快門、ISO 與 GPS，專業攝影師的標配。" 
                    delay={0.1}
                  />
                </Col>
                <Col md={6}>
                  <FeatureCard 
                    icon={Globe} 
                    title="完整多語系" 
                    text="後台管理系統支援繁中與英文自動切換，並具備版本自動更新機制。" 
                    delay={0.2}
                  />
                </Col>
                <Col md={6}>
                  <FeatureCard 
                    icon={Settings} 
                    title="GUI 網站設定" 
                    text="透過圖形介面直接管理 config.js，無需手動修改程式碼。" 
                    delay={0.3}
                  />
                </Col>
                <Col md={6}>
                  <FeatureCard 
                    icon={FileCode} 
                    title="進階編輯器" 
                    text="本地化部署 TinyMCE 6，支援自定義分頁與視覺化草稿管理。" 
                    delay={0.4}
                  />
                </Col>
              </Row>
            </Col>
          </Row>
        </Container>
      </section>

      {/* Vibe Coding Section */}
      <section id="vibe-coding" className="py-5 bg-dark text-white overflow-hidden position-relative">
        <div className="abstract-shape shape-1 opacity-25"></div>
        <Container className="custom-container py-5">
          <Row className="align-items-center">
            <Col lg={6} className="mb-5 mb-lg-0">
              <h2 className="display-4 fw-bold mb-4">Vibe Coding 實踐成果</h2>
              <p className="lead mb-5 opacity-75 fs-4">
                這是一個測試與練習「Vibe Coding」的完美案例。我們將開發者的「創意」與 AI 的「執行力」深度結合，
                在短短數日內完成了原本需耗時數月的系統進化。
              </p>
              <div className="d-flex flex-wrap gap-2 mb-4">
                <TechBadge>Gemini CLI</TechBadge>
                <TechBadge>Prompt Engineering</TechBadge>
                <TechBadge>Automated Logging</TechBadge>
                <TechBadge>Context Mapping</TechBadge>
              </div>
              <ul className="list-unstyled">
                <li className="mb-4 d-flex align-items-start">
                  <div className="p-2 bg-primary rounded-3 me-3"><Terminal size={20} /></div>
                  <div>
                    <h5 className="fw-bold">高效率產出</h5>
                    <p className="opacity-75 small">數小時內建立完整的資料備份、還原與跨主機遷移工具。</p>
                  </div>
                </li>
                <li className="mb-4 d-flex align-items-start">
                  <div className="p-2 bg-primary rounded-3 me-3"><Code2 size={20} /></div>
                  <div>
                    <h5 className="fw-bold">文件與代碼同步</h5>
                    <p className="opacity-75 small">自動更新 ARCHITECTURE.md 與 HISTORY.md，保持開發紀錄透明。</p>
                  </div>
                </li>
              </ul>
            </Col>
            <Col lg={6}>
              <div className="p-4 bg-black rounded-4 border border-secondary shadow-lg">
                <div className="d-flex justify-content-between mb-4 border-bottom border-secondary pb-3">
                  <div className="d-flex align-items-center">
                    <div className="pulse-dot me-2"></div>
                    <span className="small text-secondary fw-bold">VIBE RUNTIME: ACTIVE</span>
                  </div>
                  <Badge bg="primary">v2026.02.03</Badge>
                </div>
                <div className="font-monospace small overflow-auto custom-scrollbar" style={{ maxHeight: '400px' }}>
                  <p className="text-info m-0"># 開始執行「更新」巨集指令...</p>
                  <p className="m-0 text-secondary">[LOG] 讀取 HISTORY.md 並同步時區 (UTC+8)...</p>
                  <p className="m-0 text-secondary">[LOG] 偵測到 WSL2 環境，優化 Git 同步策略...</p>
                  <p className="m-0 text-success">[SUCCESS] 後台多語系語系檔 zh_TW/en_US 同步完成。</p>
                  <p className="m-0 text-warning">[WARN] 偵測到 4K 螢幕，觸發佈局優化指令...</p>
                  <p className="m-0 text-secondary">[LOG] 正在調整 App.css Breakpoints...</p>
                  <p className="m-0 text-info"># 執行 npm run build...</p>
                  <p className="m-0 text-success"># 專案部署就緒。Vibe Coding 成果驗證成功。</p>
                  <div className="typing-cursor mt-2"></div>
                </div>
              </div>
            </Col>
          </Row>
        </Container>
      </section>

      {/* Technical Specs */}
      <section id="specs" className="py-5">
        <Container className="custom-container py-5">
          <h2 className="fw-bold text-center mb-5">技術規範 (Technical Specs)</h2>
          <Row className="g-4 text-center">
            <Col sm={6} lg={3}>
              <div className="p-4 border rounded-4 h-100">
                <h6 className="text-secondary mb-3">Backend</h6>
                <h4 className="fw-bold">PHP 7.4+</h4>
                <p className="small text-muted mb-0">Fully PHP 8.x Compatible</p>
              </div>
            </Col>
            <Col sm={6} lg={3}>
              <div className="p-4 border rounded-4 h-100">
                <h6 className="text-secondary mb-3">Database</h6>
                <h4 className="fw-bold">SQLite / MySQL</h4>
                <p className="small text-muted mb-0">Hybrid Data Provider</p>
              </div>
            </Col>
            <Col sm={6} lg={3}>
              <div className="p-4 border rounded-4 h-100">
                <h6 className="text-secondary mb-3">Frontend</h6>
                <h4 className="fw-bold">Vanilla JS / React</h4>
                <p className="small text-muted mb-0">Zero Bloat Execution</p>
              </div>
            </Col>
            <Col sm={6} lg={3}>
              <div className="p-4 border rounded-4 h-100">
                <h6 className="text-secondary mb-3">OS Target</h6>
                <h4 className="fw-bold">Linux / WSL2</h4>
                <p className="small text-muted mb-0">Cross-Platform Support</p>
              </div>
            </Col>
          </Row>
        </Container>
      </section>

      {/* Roadmap */}
      <section id="roadmap" className="py-5 bg-light">
        <Container className="custom-container py-5">
          <Row className="justify-content-center text-center mb-5">
            <Col lg={8}>
              <h2 className="display-5 fw-bold mb-4">未來發展目標</h2>
              <p className="text-muted fs-5">這只是一個開始，我們的藍圖依然在擴張。</p>
            </Col>
          </Row>
          <Row className="g-4">
            <Col md={6}>
              <Card className="h-100 border-0 shadow-sm rounded-4">
                <Card.Body className="p-4 p-xxl-5">
                  <div className="d-flex align-items-center mb-4">
                    <div className="p-3 bg-primary-soft text-primary rounded-3 me-3"><Settings /></div>
                    <h4 className="fw-bold mb-0">核心進化</h4>
                  </div>
                  <ul className="fs-5 text-muted lh-lg">
                    <li>伺服器端高效能分頁機制</li>
                    <li>自動 WebP 縮圖生成引擎</li>
                    <li>進階媒體管理庫與標籤系統</li>
                  </ul>
                </Card.Body>
              </Card>
            </Col>
            <Col md={6}>
              <Card className="h-100 border-0 shadow-sm rounded-4">
                <Card.Body className="p-4 p-xxl-5">
                  <div className="d-flex align-items-center mb-4">
                    <div className="p-3 bg-primary-soft text-primary rounded-3 me-3"><Globe /></div>
                    <h4 className="fw-bold mb-0">生態整合</h4>
                  </div>
                  <ul className="fs-5 text-muted lh-lg">
                    <li>Flickr / Google Sheets 資料同步</li>
                    <li>地圖攝影點位展示 (Geotagging Map)</li>
                    <li>社群媒體自動分享與評論系統</li>
                  </ul>
                </Card.Body>
              </Card>
            </Col>
          </Row>
        </Container>
      </section>

      {/* CTA Footer */}
      <footer className="py-5 bg-white border-top">
        <Container className="custom-container text-center py-5">
          <h2 className="fw-bold mb-4">啟動您的意志自由</h2>
          <p className="text-muted mb-5 fs-5">專案目前開源於 GitHub，誠摯邀請您參與這場 Vibe Coding 實驗。</p>
          <div className="d-flex justify-content-center flex-wrap gap-4 mb-5">
            <Button variant="dark" size="lg" className="rounded-pill px-5 py-3 fs-5" href="https://github.com/erspicu/ersalblog" target="_blank">
              GitHub 儲存庫
            </Button>
            <Button variant="primary" size="lg" className="rounded-pill px-5 py-3 fs-5 shadow">
              下載安裝指南
            </Button>
          </div>
          <div className="mt-5 pt-5 border-top border-light">
            <p className="text-secondary mb-0">
              &copy; 2026 BaxerMux ErsalBlog Project. <br />
              Vibe Coded with Gemini CLI & Dedication to Photography.
            </p>
          </div>
        </Container>
      </footer>
    </div>
  );
}

export default App;
