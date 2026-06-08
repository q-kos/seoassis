// ── Tab navigation ──────────────────────────────────────────────────────────
document.querySelectorAll('.nav-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.nav-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tool-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});

// ── Helpers ──────────────────────────────────────────────────────────────────
function show(el)  { if (el) el.classList.add('visible'); }
function hide(el)  { if (el) el.classList.remove('visible'); }
function showEl(el){ if (el) el.classList.remove('hidden'); }
function hideEl(el){ if (el) el.classList.add('hidden'); }

function setAlert(el, msg) {
  el.textContent = msg;
  show(el);
}

// ── 1. SEO AUDIT ─────────────────────────────────────────────────────────────
let auditData = null;
let auditUrl  = '';

async function runAudit() {
  const urlInput = document.getElementById('auditUrl');
  const url = urlInput.value.trim();
  const errorEl = document.getElementById('auditError');

  hide(errorEl);
  hide(document.getElementById('auditReport'));

  if (!url) { setAlert(errorEl, 'Введи URL страницы'); return; }

  const btn     = document.getElementById('auditBtn');
  const btnText = document.getElementById('auditBtnText');
  const spinner = document.getElementById('auditSpinner');

  btn.disabled = true;
  btnText.textContent = 'Проверяю…';
  showEl(spinner);
  show(document.getElementById('auditProgress'));
  document.getElementById('auditProgressLabel').textContent = 'Загружаю страницу и анализирую…';

  auditUrl = url;

  try {
    const fd = new FormData();
    fd.append('url', url);
    const res  = await fetch('src/api/audit.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.error) { setAlert(errorEl, data.error); return; }

    auditData = data;
    renderReport(data, url);
    show(document.getElementById('auditReport'));
    document.getElementById('auditReport').scrollIntoView({ behavior: 'smooth' });

  } catch (e) {
    setAlert(errorEl, 'Ошибка соединения: ' + e.message);
  } finally {
    btn.disabled = false;
    btnText.textContent = 'Проверить';
    hideEl(spinner);
    hide(document.getElementById('auditProgress'));
  }
}

document.getElementById('auditUrl').addEventListener('keydown', e => {
  if (e.key === 'Enter') runAudit();
});

function renderReport(data, url) {
  const score = data.score ?? 0;
  const circle = document.getElementById('scoreCircle');
  const cls = score >= 80 ? 'high' : score >= 50 ? 'mid' : 'low';
  circle.className = 'score-circle ' + cls;
  document.getElementById('scoreNum').textContent = score;
  document.getElementById('scoreSummary').textContent = data.summary ?? '';

  // Critical
  const critEl = document.getElementById('criticalSection');
  if (data.critical && data.critical.length) {
    critEl.innerHTML = '<div class="section-title">🔴 Критические проблемы</div>'
      + data.critical.map(i => issueCard(i, 'critical')).join('');
  } else {
    critEl.innerHTML = '';
  }

  // Warnings
  const warnEl = document.getElementById('warningsSection');
  if (data.warnings && data.warnings.length) {
    warnEl.innerHTML = '<div class="section-title">🟡 Замечания</div>'
      + data.warnings.map(i => issueCard(i, 'warning')).join('');
  } else {
    warnEl.innerHTML = '';
  }

  // OK
  const okEl = document.getElementById('okSection');
  if (data.ok && data.ok.length) {
    okEl.innerHTML = '<div class="section-title">✅ Всё в порядке</div>'
      + '<ul class="ok-list">' + data.ok.map(s => `<li>${esc(s)}</li>`).join('') + '</ul>';
  } else {
    okEl.innerHTML = '';
  }
}

function issueCard(item, type) {
  const label = type === 'critical' ? 'Критично' : 'Замечание';
  return `<div class="issue-card ${type}">
    <div class="issue-badge ${type}">${label}</div>
    <div class="issue-title">${esc(item.title ?? '')}</div>
    <div class="issue-reason">${esc(item.reason ?? '')}</div>
    <div class="issue-fix">${esc(item.fix ?? '')}</div>
  </div>`;
}

function resetAudit() {
  hide(document.getElementById('auditReport'));
  document.getElementById('auditUrl').value = '';
  auditData = null;
}

function downloadReport() {
  if (!auditData) return;
  const html = buildReportHtml(auditData, auditUrl);
  const blob  = new Blob([html], { type: 'text/html;charset=utf-8' });
  const a     = document.createElement('a');
  const date  = new Date().toISOString().slice(0, 10);
  const domain = auditUrl.replace(/^https?:\/\//, '').replace(/[^a-z0-9]/gi, '-').toLowerCase();
  a.href     = URL.createObjectURL(blob);
  a.download = `${date}-${domain}-seo-report.html`;
  a.click();
}

function buildReportHtml(data, url) {
  const score = data.score ?? 0;
  const cls   = score >= 80 ? 'high' : score >= 50 ? 'mid' : 'low';
  const date  = new Date().toLocaleDateString('ru-RU');

  const critHtml = (data.critical || []).length
    ? '<h2>Критические проблемы</h2>' + (data.critical||[]).map(i => `
        <div class="issue high">
          <div class="badge high">Критично</div>
          <div class="title">${esc(i.title)}</div>
          <div class="why">${esc(i.reason)}</div>
          <div class="fix">${esc(i.fix)}</div>
        </div>`).join('') : '';

  const warnHtml = (data.warnings || []).length
    ? '<h2>Замечания</h2>' + (data.warnings||[]).map(i => `
        <div class="issue mid">
          <div class="badge mid">Замечание</div>
          <div class="title">${esc(i.title)}</div>
          <div class="why">${esc(i.reason)}</div>
          <div class="fix">${esc(i.fix)}</div>
        </div>`).join('') : '';

  const okHtml = (data.ok || []).length
    ? '<h2>Всё в порядке</h2><ul class="ok-list">' + (data.ok||[]).map(s => `<li>${esc(s)}</li>`).join('') + '</ul>' : '';

  return `<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8">
<title>SEO-отчёт — ${esc(url)}</title>
<style>
body{font-family:Arial,sans-serif;max-width:860px;margin:40px auto;padding:0 20px;color:#2C1A0E;background:#FBF7F2}
.header{border-bottom:2px solid #C8721A;padding-bottom:16px;margin-bottom:24px}
.header h1{margin:0 0 4px;font-size:22px}
.meta-info{color:#7A6050;font-size:14px}
.summary{background:#F5E8D5;border-left:4px solid #C8721A;padding:12px 16px;margin-bottom:24px;font-size:15px}
.score{display:inline-block;font-size:32px;font-weight:700;margin-bottom:24px}
.score.high{color:#4E7E28}.score.mid{color:#B8720A}.score.low{color:#B83030}
h2{font-size:17px;margin:28px 0 12px;border-bottom:1px solid #E8D4BC;padding-bottom:6px}
.issue{margin-bottom:16px;padding:12px 14px;border-radius:4px}
.issue.high{background:#FDEAEA;border-left:4px solid #B83030}
.issue.mid{background:#FEF4E0;border-left:4px solid #B8720A}
.issue .badge{display:inline-block;font-size:11px;font-weight:700;padding:2px 7px;border-radius:3px;margin-bottom:6px;text-transform:uppercase;color:#fff}
.issue .badge.high{background:#B83030}.issue .badge.mid{background:#B8720A}
.issue .title{font-weight:700;margin-bottom:4px}
.issue .why{color:#7A6050;font-size:14px;margin-bottom:4px}
.issue .fix{font-size:14px}
.issue .fix::before{content:"→ ";color:#C8721A;font-weight:700}
.ok-list{list-style:none;padding:0}
.ok-list li{padding:5px 0;color:#4E7E28;font-size:14px}
.ok-list li::before{content:"✓ "}
</style></head><body>
<div class="header"><h1>SEO-аудит: ${esc(url)}</h1><div class="meta-info">Дата: ${date}</div></div>
<div class="summary">${esc(data.summary ?? '')}</div>
<div class="score ${cls}">Оценка: ${score} / 100</div>
${critHtml}${warnHtml}${okHtml}
</body></html>`;
}

// ── 2. META EDITOR ────────────────────────────────────────────────────────────
const TITLE_MIN = 50, TITLE_MAX = 70;
const DESC_MIN  = 120, DESC_MAX  = 170;

function updateMeta() {
  const title = document.getElementById('titleInput').value;
  const desc  = document.getElementById('descInput').value;

  updateField('title', title, TITLE_MIN, TITLE_MAX, 80);
  updateField('desc',  desc,  DESC_MIN,  DESC_MAX, 200);

  document.getElementById('serpTitle').textContent = title || 'Заголовок страницы';
  document.getElementById('serpDesc').textContent  = desc  || 'Здесь будет отображаться описание…';
}

function updateField(name, text, min, max, barMax) {
  const len     = text.length;
  const countEl = document.getElementById(name === 'title' ? 'titleCount' : 'descCount');
  const statusEl= document.getElementById(name === 'title' ? 'titleStatus': 'descStatus');
  const barEl   = document.getElementById(name === 'title' ? 'titleBar'   : 'descBar');

  countEl.textContent = len + ' симв.';

  const pct = Math.min(100, (len / barMax) * 100);
  barEl.style.width = pct + '%';

  if (len === 0) {
    statusEl.textContent = '—';
    statusEl.className   = 'status-badge neutral';
    barEl.style.background = 'var(--border)';
  } else if (len >= min && len <= max) {
    statusEl.textContent = '✅ Оптимально';
    statusEl.className   = 'status-badge ok';
    barEl.style.background = 'var(--success)';
  } else if (len < min) {
    statusEl.textContent = `⚠ Коротко (${len} < ${min})`;
    statusEl.className   = 'status-badge warn';
    barEl.style.background = 'var(--warning)';
  } else {
    statusEl.textContent = `⚠ Длинно (${len} > ${max})`;
    statusEl.className   = 'status-badge bad';
    barEl.style.background = 'var(--error)';
  }
}

function clearMeta() {
  document.getElementById('titleInput').value = '';
  document.getElementById('descInput').value  = '';
  updateMeta();
}

// ── 3. SCANNER ───────────────────────────────────────────────────────────────
let scanData = [];

async function runScan() {
  const url = document.getElementById('scanUrl').value.trim();
  const errorEl = document.getElementById('scanError');
  hide(errorEl);
  hide(document.getElementById('scanResults'));

  if (!url) { setAlert(errorEl, 'Введи URL сайта'); return; }

  const btn     = document.getElementById('scanBtn');
  const btnText = document.getElementById('scanBtnText');
  const spinner = document.getElementById('scanSpinner');

  btn.disabled = true;
  btnText.textContent = 'Сканирую…';
  showEl(spinner);
  show(document.getElementById('scanProgress'));
  document.getElementById('scanProgressLabel').textContent = 'Обходим страницы сайта…';

  try {
    const fd = new FormData();
    fd.append('url', url);
    const res  = await fetch('src/api/scan.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.error) { setAlert(errorEl, data.error); return; }

    scanData = data.pages || [];
    renderScanStats(data.stats);
    renderScanTable(scanData);
    show(document.getElementById('scanResults'));
    document.getElementById('scanResults').scrollIntoView({ behavior: 'smooth' });

  } catch (e) {
    setAlert(errorEl, 'Ошибка: ' + e.message);
  } finally {
    btn.disabled = false;
    btnText.textContent = 'Сканировать';
    hideEl(spinner);
    hide(document.getElementById('scanProgress'));
  }
}

document.getElementById('scanUrl').addEventListener('keydown', e => {
  if (e.key === 'Enter') runScan();
});

function renderScanStats(stats) {
  if (!stats) return;
  const el = document.getElementById('scanStats');
  el.innerHTML = [
    { num: stats.total,       label: 'Страниц' },
    { num: stats.no_title,    label: 'Нет Title' },
    { num: stats.no_desc,     label: 'Нет Desc' },
    { num: stats.short_title + stats.long_title, label: 'Title ≠ норма' },
    { num: stats.short_desc  + stats.long_desc,  label: 'Desc ≠ норма' },
    { num: stats.errors,      label: 'Ошибки' },
  ].map(s => `<div class="stat-box"><div class="stat-num">${s.num}</div><div class="stat-label">${s.label}</div></div>`).join('');
}

function renderScanTable(pages) {
  const tbody = document.getElementById('scanTableBody');
  tbody.innerHTML = pages.map((p, i) => {
    if (p.error) {
      return `<tr class="row-error">
        <td>${i + 1}</td>
        <td><a class="scan-url" href="${esc(p.url)}" target="_blank">${esc(p.url)}</a></td>
        <td colspan="4" style="color:var(--error);font-size:13px">Ошибка: ${esc(p.error)}</td>
      </tr>`;
    }
    const tStatus = metaStatus(p.title_len, 50, 70);
    const dStatus = metaStatus(p.desc_len,  120, 170);
    const tBadge  = statusBadge(p.title,  p.title_len, 50, 70);
    const dBadge  = statusBadge(p.desc,   p.desc_len,  120, 170);
    return `<tr>
      <td>${i + 1}</td>
      <td><a class="scan-url" href="${esc(p.url)}" target="_blank" title="${esc(p.url)}">${esc(trimUrl(p.url))}</a></td>
      <td class="td-meta"><span class="meta-text" title="${esc(p.title || '')}">${esc(p.title || '—')}</span></td>
      <td>${p.title ? p.title_len : '—'} ${tBadge}</td>
      <td class="td-meta"><span class="meta-text" title="${esc(p.desc || '')}">${esc(p.desc || '—')}</span></td>
      <td>${p.desc ? p.desc_len : '—'} ${dBadge}</td>
    </tr>`;
  }).join('');
}

function statusBadge(text, len, min, max) {
  if (!text)              return '<span class="status-badge bad">нет</span>';
  if (len >= min && len <= max) return '<span class="status-badge ok">✓</span>';
  if (len < min)          return '<span class="status-badge warn">кор.</span>';
  return                         '<span class="status-badge warn">дл.</span>';
}

function metaStatus(len, min, max) {
  if (!len) return 'missing';
  if (len >= min && len <= max) return 'ok';
  return 'warn';
}

function trimUrl(url) {
  return url.replace(/^https?:\/\//, '').replace(/\/$/, '');
}

function filterScan() {
  const filter = document.getElementById('scanFilter').value;
  let filtered = scanData;
  if (filter === 'no_title')  filtered = scanData.filter(p => !p.error && !p.title);
  if (filter === 'no_desc')   filtered = scanData.filter(p => !p.error && !p.desc);
  if (filter === 'problems')  filtered = scanData.filter(p => p.error || !p.title || !p.desc
    || (p.title && (p.title_len < 50 || p.title_len > 70))
    || (p.desc  && (p.desc_len  < 120 || p.desc_len > 170)));
  renderScanTable(filtered);
}

function downloadScanCsv() {
  if (!scanData.length) return;
  const rows = [['URL','Title','Title симв.','Description','Desc симв.','Статус']];
  scanData.forEach(p => {
    const tStatus = !p.title ? 'нет' : (p.title_len >= 50 && p.title_len <= 70 ? 'ok' : 'не в норме');
    const dStatus = !p.desc  ? 'нет' : (p.desc_len  >= 120 && p.desc_len <= 170 ? 'ok' : 'не в норме');
    rows.push([p.url, p.title||'', p.title_len||'', p.desc||'', p.desc_len||'', p.error ? 'ошибка' : `Title:${tStatus} Desc:${dStatus}`]);
  });
  const csv  = rows.map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
  const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
  const a    = document.createElement('a');
  a.href     = URL.createObjectURL(blob);
  a.download = 'seo-scan-' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
}

// ── 4. EXTRACT META ───────────────────────────────────────────────────────────
let extractedData = null;

async function runExtract() {
  const url = document.getElementById('extractUrl').value.trim();
  const errorEl = document.getElementById('extractError');
  hide(errorEl);
  hide(document.getElementById('extractResult'));
  hide(document.getElementById('aiSuggestion'));

  if (!url) { setAlert(errorEl, 'Введи URL страницы'); return; }

  const btn     = document.getElementById('extractBtn');
  const btnText = document.getElementById('extractBtnText');
  const spinner = document.getElementById('extractSpinner');

  btn.disabled = true;
  btnText.textContent = 'Загружаю…';
  showEl(spinner);
  show(document.getElementById('extractProgress'));

  try {
    const fd = new FormData();
    fd.append('url', url);
    fd.append('action', 'extract');
    const res  = await fetch('src/api/extract-meta.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.error) { setAlert(errorEl, data.error); return; }

    extractedData = { ...data, url };
    renderExtract(data, url);
    show(document.getElementById('extractResult'));
    document.getElementById('extractResult').scrollIntoView({ behavior: 'smooth' });

  } catch (e) {
    setAlert(errorEl, 'Ошибка: ' + e.message);
  } finally {
    btn.disabled = false;
    btnText.textContent = 'Извлечь';
    hideEl(spinner);
    hide(document.getElementById('extractProgress'));
  }
}

document.getElementById('extractUrl').addEventListener('keydown', e => {
  if (e.key === 'Enter') runExtract();
});

function renderExtract(data, url) {
  document.getElementById('extractTitleText').textContent = data.title || '(не задан)';
  document.getElementById('extractDescText').textContent  = data.desc  || '(не задан)';

  setExtractBadge('extractTitleCount', 'extractTitleStatus', data.title, data.title_len, 50, 70);
  setExtractBadge('extractDescCount',  'extractDescStatus',  data.desc,  data.desc_len,  120, 170);

  document.getElementById('extractSerpTitle').textContent = data.title || 'Заголовок не задан';
  document.getElementById('extractSerpUrl').textContent   = url.replace(/^https?:\/\//, '');
  document.getElementById('extractSerpDesc').textContent  = data.desc  || 'Описание не задано';
}

function setExtractBadge(countId, statusId, text, len, min, max) {
  const cEl = document.getElementById(countId);
  const sEl = document.getElementById(statusId);
  cEl.textContent = text ? len + ' симв.' : '';
  if (!text) {
    sEl.textContent = 'Не задан'; sEl.className = 'status-badge bad';
  } else if (len >= min && len <= max) {
    sEl.textContent = '✅ Оптимально'; sEl.className = 'status-badge ok';
  } else if (len < min) {
    sEl.textContent = `⚠ Коротко (${len})`; sEl.className = 'status-badge warn';
  } else {
    sEl.textContent = `⚠ Длинно (${len})`; sEl.className = 'status-badge warn';
  }
}

async function runImprove() {
  if (!extractedData) return;

  const btn     = document.getElementById('improveBtn');
  const btnText = document.getElementById('improveBtnText');
  const spinner = document.getElementById('improveSpinner');
  const errorEl = document.getElementById('improveError');
  hide(errorEl);
  hide(document.getElementById('aiSuggestion'));

  btn.disabled = true;
  btnText.textContent = 'Генерирую…';
  showEl(spinner);

  try {
    const fd = new FormData();
    fd.append('url', extractedData.url);
    fd.append('action', 'improve');
    const res  = await fetch('src/api/extract-meta.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.error)    { setAlert(errorEl, data.error); return; }
    if (data.ai_error) { setAlert(errorEl, data.ai_error); return; }

    const sg = data.suggestions;
    if (!sg) { setAlert(errorEl, 'Нет ответа от AI'); return; }

    renderSuggestions(sg);
    show(document.getElementById('aiSuggestion'));

  } catch (e) {
    setAlert(errorEl, 'Ошибка: ' + e.message);
  } finally {
    btn.disabled = false;
    btnText.textContent = '✨ Улучшить через AI';
    hideEl(spinner);
  }
}

function renderSuggestions(sg) {
  const titlesEl = document.getElementById('aiTitleOptions');
  const descsEl  = document.getElementById('aiDescOptions');

  titlesEl.innerHTML = (sg.titles || []).map(t =>
    `<div class="ai-option" onclick="copyText(this, '${esc(t).replace(/'/g,"\\'")}')">
      <div class="opt-label">Title · ${t.length} симв.</div>${esc(t)}
    </div>`).join('');

  descsEl.innerHTML = (sg.descs || []).map(d =>
    `<div class="ai-option" onclick="copyText(this, '${esc(d).replace(/'/g,"\\'")}')">
      <div class="opt-label">Description · ${d.length} симв.</div>${esc(d)}
    </div>`).join('');
}

function copyText(el, text) {
  navigator.clipboard.writeText(text).then(() => {
    const orig = el.style.background;
    el.style.background = 'var(--primary-light)';
    el.style.borderColor = 'var(--primary)';
    setTimeout(() => { el.style.background = orig; el.style.borderColor = ''; }, 800);
  });
}

function copyToMeta() {
  if (!extractedData) return;
  document.getElementById('titleInput').value = extractedData.title || '';
  document.getElementById('descInput').value  = extractedData.desc  || '';
  updateMeta();
  document.querySelectorAll('.nav-tab')[1].click();
}

// ── Utils ─────────────────────────────────────────────────────────────────────
function esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
