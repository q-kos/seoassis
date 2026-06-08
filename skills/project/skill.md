# Скилл: Работа с репозиторием seoassis

## Назначение

Помогает Claude Code работать с проектом SEO Ассистент: понимать структуру, запускать сервис, вносить изменения, проверять ошибки и публиковать на GitHub.

---

## Структура проекта

```
seoassis/
├── index.php                  # Единственная HTML-страница, точка входа
├── .env                       # API-ключ (не в git)
├── .env.example               # Шаблон конфига
├── assets/
│   ├── css/style.css          # Все стили
│   └── js/app.js              # Весь фронтенд (fetch-запросы, рендер отчётов)
└── src/
    ├── ClaudeClient.php       # Запросы к Claude API (анализ + генерация мета)
    ├── PageFetcher.php        # Загрузка HTML страниц через cURL
    ├── SiteScanner.php        # Обход страниц сайта по внутренним ссылкам
    ├── analyze.php            # Старый эндпоинт (не используется фронтендом)
    └── api/
        ├── audit.php          # POST: SEO-аудит страницы → JSON
        ├── scan.php           # POST: сканирование сайта → JSON
        └── extract-meta.php   # POST: извлечение + улучшение мета-тегов → JSON
```

**Как устроен запрос:**
`app.js` → `fetch('/src/api/*.php')` → PHP читает `.env`, вызывает `ClaudeClient` или `SiteScanner` → JSON-ответ → рендер в `index.php`

---

## Запуск локального сервера

```powershell
# Запустить
php -S localhost:8080 -t "C:\Users\KOS\seoassis"

# Или через ярлык на рабочем столе (запускает launch.ps1)
```

Приложение: **http://localhost:8080**

Остановить: завершить процесс `php` в диспетчере задач или закрыть терминал.

**Зависимости PHP** (должны быть включены в `C:\php\php.ini`):
- `extension=curl`
- `extension=mbstring`
- `curl.cainfo = "C:\php\cacert.pem"`

---

## Проверка ошибок

При ошибке в браузере вида `Unexpected token '<'` — PHP вернул HTML вместо JSON.

**Диагностика через PowerShell:**
```powershell
# Проверить конкретный эндпоинт
Invoke-WebRequest -Uri "http://localhost:8080/src/api/audit.php" -Method POST -Body "url=https://example.com" -UseBasicParsing | Select-Object -ExpandProperty Content
```

**Типичные причины:**
| Ошибка | Причина | Решение |
|---|---|---|
| `Call to undefined function curl_init()` | cURL не включён | Раскомментировать `extension=curl` в `php.ini` |
| `Call to undefined function mb_strlen()` | mbstring не включён | Раскомментировать `extension=mbstring` в `php.ini` |
| `SSL certificate` | Нет CA-сертификатов | Прописать `curl.cainfo` в `php.ini` |
| `API ключ не настроен` | Нет файла `.env` | Создать `.env` с ключом |
| `Claude вернул невалидный JSON` | Claude обернул ответ в markdown | Метод `extractJson()` в `ClaudeClient.php` должен это чистить |

После изменений в `php.ini` — перезапустить сервер.

---

## Внесение изменений

### Добавить новый инструмент (вкладку)
1. `index.php` — добавить кнопку в `<nav class="nav-tabs">` и секцию `<section class="tool-panel">`
2. `src/api/` — создать новый эндпоинт по образцу `audit.php`
3. `assets/js/app.js` — добавить функцию вызова эндпоинта и рендера результата

### Изменить логику анализа
- Системный промпт Claude: `ClaudeClient.php` → метод `systemPrompt()`
- Параметры проверки мета-тегов: `ClaudeClient.php` → метод `generateMeta()`
- Логика обхода сайта: `SiteScanner.php`

### Изменить стили
- Всё в одном файле: `assets/css/style.css`

---

## Обновление README

`README.md` в корне проекта. Обновлять при:
- Добавлении нового инструмента (раздел «Возможности»)
- Изменении требований к PHP или зависимостям (раздел «Требования»)
- Изменении структуры файлов (раздел «Структура проекта»)

Писать коротко и по делу. Язык — русский.

---

## Публикация на GitHub

Репозиторий: **https://github.com/q-kos/seoassis**

```bash
# Проверить что изменилось
git status
git diff

# Зафиксировать изменения
git add .
git commit -m "короткое описание что изменилось"

# Опубликовать
git push
```

**Важно перед коммитом:**
- `.env` не должен попасть в коммит (он в `.gitignore`)
- Реальные ключи не должны быть в `.env.example` — только `your_api_key_here`
- GitHub блокирует push если обнаружит паттерн API-ключа

---

## Размещение на хостинге

Хостинг: российский shared (Beget, Timeweb). Нет root-доступа по SSH.

1. Загрузить файлы через FTP или файловый менеджер панели управления
2. Создать `.env` в корне сайта с реальным ключом
3. Убедиться что PHP 8.0+ и включён модуль cURL на хостинге
4. `.htaccess` не требуется — приложение работает на чистом PHP без роутинга
