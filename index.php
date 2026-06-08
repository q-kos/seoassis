<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SEO Ассистент</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="app-header">
  <div class="logo">SEO<span>Ассистент</span></div>
  <nav class="nav-tabs" id="navTabs">
    <button class="nav-tab active" data-tab="audit">SEO-аудит</button>
    <button class="nav-tab" data-tab="meta">Мета-теги</button>
    <button class="nav-tab" data-tab="scanner">Сканер сайта</button>
    <button class="nav-tab" data-tab="extract">Анализ мета</button>
  </nav>
</header>

<main class="app-main">

  <!-- ══ 1. SEO-аудит ══ -->
  <section class="tool-panel active" id="tab-audit">
    <h1 class="tool-title">SEO-аудит страницы</h1>
    <p class="tool-desc">Введи URL — получишь подробный отчёт по техническим и контентным параметрам.</p>

    <div class="card">
      <div class="input-row">
        <input type="url" id="auditUrl" placeholder="https://example.com/page" autocomplete="off">
        <button class="btn btn-primary" id="auditBtn" onclick="runAudit()">
          <span id="auditBtnText">Проверить</span>
          <span class="spinner hidden" id="auditSpinner"></span>
        </button>
      </div>
      <div class="alert alert-error" id="auditError"></div>

      <div class="progress-wrap" id="auditProgress">
        <div class="progress-label" id="auditProgressLabel">Загружаю страницу…</div>
        <div class="progress-bar-bg"><div class="progress-bar-fill indeterminate" id="auditProgressBar"></div></div>
      </div>
    </div>

    <div class="report-wrap" id="auditReport">
      <div class="card">
        <div class="score-block">
          <div class="score-circle" id="scoreCircle">
            <span class="score-num" id="scoreNum">—</span>
            <span class="score-of">/ 100</span>
          </div>
          <div class="score-summary" id="scoreSummary"></div>
        </div>

        <div id="criticalSection"></div>
        <div id="warningsSection"></div>
        <div id="okSection"></div>

        <div class="report-actions">
          <button class="btn btn-secondary" onclick="downloadReport()">Скачать HTML-отчёт</button>
          <button class="btn btn-ghost btn-sm" onclick="resetAudit()">Новая проверка</button>
        </div>
      </div>
    </div>
  </section>

  <!-- ══ 2. Мета-теги ══ -->
  <section class="tool-panel" id="tab-meta">
    <h1 class="tool-title">Редактор мета-тегов</h1>
    <p class="tool-desc">Проверь длину и качество Title и Description перед публикацией. Смотри, как страница будет выглядеть в поиске.</p>

    <div class="card">
      <div class="field-group">
        <label>📌 Заголовок (Title) <span class="hint">— отображается в выдаче синей ссылкой</span></label>
        <textarea id="titleInput" rows="2" placeholder="Например: Купить мужские кроссовки — каталог, цены, доставка | BrandShop" oninput="updateMeta()"></textarea>
        <div class="field-meta">
          <div class="char-bar-wrap" style="flex:1"><div class="char-bar-fill" id="titleBar"></div></div>
          <span class="char-count" id="titleCount">0 симв.</span>
          <span class="status-badge neutral" id="titleStatus">—</span>
        </div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Оптимально: 50–70 символов</div>
      </div>

      <div class="field-group">
        <label>📝 Описание (Meta Description) <span class="hint">— текст под заголовком в выдаче</span></label>
        <textarea id="descInput" rows="3" placeholder="Например: Большой выбор мужских кроссовок от 2 990 руб. Доставка по России за 1 день. Более 500 моделей в наличии. Гарантия 1 год." oninput="updateMeta()"></textarea>
        <div class="field-meta">
          <div class="char-bar-wrap" style="flex:1"><div class="char-bar-fill" id="descBar"></div></div>
          <span class="char-count" id="descCount">0 симв.</span>
          <span class="status-badge neutral" id="descStatus">—</span>
        </div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Оптимально: 120–170 символов</div>
      </div>

      <div style="margin-top:8px">
        <button class="btn btn-ghost btn-sm" onclick="clearMeta()">Очистить</button>
      </div>
    </div>

    <div class="card">
      <div class="preview-label">Превью в поисковой выдаче</div>
      <div class="meta-preview">
        <div class="serp-title" id="serpTitle">Заголовок страницы</div>
        <div class="serp-url">https://example.com/page/</div>
        <div class="serp-desc" id="serpDesc">Здесь будет отображаться описание страницы из мета-тега Description. Заполни поле выше, чтобы увидеть превью.</div>
      </div>
    </div>

    <div class="card" style="background:var(--primary-light)">
      <p style="font-size:14px;color:var(--text-muted)"><strong style="color:var(--text)">💡 Рекомендации:</strong> Title 50–70 символов — ключевые слова в начале. Description 120–170 символов — ёмко о пользе страницы + призыв к действию. Яндекс иногда переписывает мета-теги, но правильно заполненные учитываются при ранжировании.</p>
    </div>
  </section>

  <!-- ══ 3. Сканер сайта ══ -->
  <section class="tool-panel" id="tab-scanner">
    <h1 class="tool-title">Сканер мета-тегов сайта</h1>
    <p class="tool-desc">Обходит все страницы сайта и проверяет наличие и длину Title и Description.</p>

    <div class="card">
      <div class="input-row">
        <input type="url" id="scanUrl" placeholder="https://example.com" autocomplete="off">
        <button class="btn btn-primary" id="scanBtn" onclick="runScan()">
          <span id="scanBtnText">Сканировать</span>
          <span class="spinner hidden" id="scanSpinner"></span>
        </button>
      </div>
      <div style="font-size:12px;color:var(--text-muted);margin-top:8px">Сканер обходит все внутренние ссылки сайта. Для больших сайтов может занять несколько минут.</div>
      <div class="alert alert-error" id="scanError"></div>

      <div class="progress-wrap" id="scanProgress">
        <div class="progress-label" id="scanProgressLabel">Сканирую сайт…</div>
        <div class="progress-bar-bg"><div class="progress-bar-fill indeterminate"></div></div>
      </div>
    </div>

    <div class="scan-table-wrap" id="scanResults">
      <div class="scan-stats" id="scanStats"></div>
      <div style="display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap">
        <button class="btn btn-secondary btn-sm" onclick="downloadScanCsv()">Скачать CSV</button>
        <select id="scanFilter" class="btn btn-ghost btn-sm" onchange="filterScan()" style="cursor:pointer;font-family:inherit">
          <option value="all">Все страницы</option>
          <option value="no_title">Нет Title</option>
          <option value="no_desc">Нет Description</option>
          <option value="problems">Только с ошибками</option>
        </select>
      </div>
      <table class="scan-table" id="scanTable">
        <thead>
          <tr>
            <th>#</th>
            <th>URL</th>
            <th>Title</th>
            <th>Симв.</th>
            <th>Description</th>
            <th>Симв.</th>
          </tr>
        </thead>
        <tbody id="scanTableBody"></tbody>
      </table>
    </div>
  </section>

  <!-- ══ 4. Анализ мета с сайта ══ -->
  <section class="tool-panel" id="tab-extract">
    <h1 class="tool-title">Анализ мета-тегов сайта</h1>
    <p class="tool-desc">Извлекает Title и Description с любой страницы, оценивает их и предлагает улучшения через AI.</p>

    <div class="card">
      <div class="input-row">
        <input type="url" id="extractUrl" placeholder="https://example.com/page" autocomplete="off">
        <button class="btn btn-primary" id="extractBtn" onclick="runExtract()">
          <span id="extractBtnText">Извлечь</span>
          <span class="spinner hidden" id="extractSpinner"></span>
        </button>
      </div>
      <div class="alert alert-error" id="extractError"></div>
      <div class="progress-wrap" id="extractProgress">
        <div class="progress-label">Загружаю страницу…</div>
        <div class="progress-bar-bg"><div class="progress-bar-fill indeterminate"></div></div>
      </div>
    </div>

    <div class="extract-result" id="extractResult">
      <div class="meta-row">
        <div class="card">
          <label>📌 Title</label>
          <div id="extractTitleText" style="font-size:15px;word-break:break-word;margin-bottom:10px"></div>
          <div class="field-meta">
            <span class="char-count" id="extractTitleCount"></span>
            <span class="status-badge neutral" id="extractTitleStatus"></span>
          </div>
        </div>
        <div class="card">
          <label>📝 Description</label>
          <div id="extractDescText" style="font-size:14px;color:var(--text-muted);word-break:break-word;margin-bottom:10px"></div>
          <div class="field-meta">
            <span class="char-count" id="extractDescCount"></span>
            <span class="status-badge neutral" id="extractDescStatus"></span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="preview-label">Превью в поиске</div>
        <div class="meta-preview">
          <div class="serp-title" id="extractSerpTitle"></div>
          <div class="serp-url" id="extractSerpUrl"></div>
          <div class="serp-desc" id="extractSerpDesc"></div>
        </div>
      </div>

      <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn btn-primary" id="improveBtn" onclick="runImprove()">
          <span id="improveBtnText">✨ Улучшить через AI</span>
          <span class="spinner hidden" id="improveSpinner"></span>
        </button>
        <button class="btn btn-ghost btn-sm" onclick="copyToMeta()">Скопировать в редактор</button>
      </div>
      <div class="alert alert-error" id="improveError"></div>

      <div class="ai-suggestion" id="aiSuggestion">
        <div class="ai-suggestion-title">Варианты Title — нажми чтобы скопировать</div>
        <div id="aiTitleOptions"></div>
        <div class="ai-suggestion-title" style="margin-top:14px">Варианты Description</div>
        <div id="aiDescOptions"></div>
      </div>
    </div>

  </section>

</main>

<script src="assets/js/app.js"></script>
</body>
</html>
