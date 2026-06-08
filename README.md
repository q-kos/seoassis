# SEO Ассистент

Веб-приложение для SEO-анализа сайтов. Пользователь вводит URL и получает подробный отчёт по техническим и контентным параметрам страницы. 

## Возможности

- **SEO-аудит** — анализ страницы через Claude AI: title, description, заголовки, Open Graph, Schema.org, canonical, robots и другие параметры. Оценка от 0 до 100 с разбивкой на критические ошибки, предупреждения и норму<br>
![SEO-аудит](/image/4.png)<br><br>
- **Редактор мета-тегов** — проверка длины title и description, превью в поисковой выдаче<br>
![Редактор мета-тегов](/image/1.png)<br><br>
- **Сканер сайта** — обход всех страниц сайта, проверка наличия и длины мета-тегов, выгрузка в CSV<br>
![Сканер сайта](/image/2.png)<br><br>
- **Анализ мета** — извлечение title и description с любой страницы + генерация улучшенных вариантов через AI<br>
![Анализ мета тегов](/image/3.png)
## Стек

- **Backend:** PHP 8.x, cURL
- **Frontend:** Vanilla JS, CSS
- **AI:** Claude API (Anthropic) — модель `claude-haiku-4-5`
- **Хостинг:** shared-хостинг с поддержкой PHP (Beget, Timeweb и аналоги)

## Локальный запуск

### Требования

- PHP 8.x с расширениями `curl` и `mbstring`
- API-ключ Anthropic ([console.anthropic.com](https://console.anthropic.com))

### Настройка PHP на Windows

1. Скачай PHP для Windows: [windows.php.net/download](https://windows.php.net/download/) (Non-Thread Safe, x64)
2. Распакуй в `C:\php`, добавь `C:\php` в переменную PATH
3. Скопируй `C:\php\php.ini-development` → `C:\php\php.ini`
4. В `php.ini` раскомментируй строки:
   ```
   extension=curl
   extension=mbstring
   ```
5. Скачай сертификаты CA: [curl.se/ca/cacert.pem](https://curl.se/ca/cacert.pem) → сохрани в `C:\php\cacert.pem`
6. В `php.ini` пропиши путь к сертификатам:
   ```
   curl.cainfo = "C:\php\cacert.pem"
   ```

### Запуск

1. Клонируй репозиторий:
   ```
   git clone https://github.com/YOUR_USERNAME/seoassis.git
   cd seoassis
   ```
2. Создай файл `.env` в корне проекта:
   ```
   ANTHROPIC_API_KEY=sk-ant-ваш_ключ
   ```
3. Запусти сервер:
   ```
   php -S localhost:8080
   ```
4. Открой в браузере: [http://localhost:8080](http://localhost:8080)

### Ярлык на рабочем столе (Windows)

Запусти `create-shortcut.ps1` правой кнопкой → «Выполнить с помощью PowerShell». После этого приложение запускается двойным кликом на ярлыке.

## Размещение на хостинге

1. Загрузи все файлы на хостинг через FTP или файловый менеджер панели управления
2. Создай файл `.env` в корне сайта с API-ключом
3. Убедись, что PHP-версия 8.0+ и включён модуль cURL

## Структура проекта

```
seoassis/
├── index.php              # Главная страница
├── .env.example           # Шаблон конфигурации
├── assets/
│   ├── css/style.css
│   └── js/app.js
└── src/
    ├── ClaudeClient.php   # Работа с Claude API
    ├── PageFetcher.php    # Загрузка HTML страниц
    ├── SiteScanner.php    # Сканер сайта
    └── api/
        ├── audit.php      # Эндпоинт SEO-аудита
        ├── scan.php       # Эндпоинт сканера
        └── extract-meta.php
```

## Лицензия

MIT
