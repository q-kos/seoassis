# Скилл: Генерация SEO-отчёта

## Назначение

На основе результатов SEO-аудита генерирует HTML-файл отчёта и сохраняет его в папку `reports/`.

## Как запустить

Упомяни "generate-report" или попроси сгенерировать отчёт. Обычно запускается сразу после скилла `seo-audit`, но может принять данные вручную.

## Входные данные

| Параметр | Обязательно | Описание |
|---|---|---|
| Результаты аудита | Да | Вывод скилла `seo-audit` или описание найденных проблем |
| URL сайта | Да | Используется в шапке и имени файла |
| Дата | Нет | По умолчанию — текущая дата |

## Процесс

1. Собрать все найденные проблемы и сгруппировать по приоритету: **Высокий / Средний / Низкий**
2. Посчитать итоговый балл (0–100): начинать с 100, вычитать за каждую проблему по приоритету:
   - Высокий: −10 баллов
   - Средний: −5 баллов
   - Низкий: −2 балла
   - Минимум — 0
3. Написать краткое резюме одной фразой: общий вывод по состоянию страницы
4. Сформировать HTML по шаблону ниже
5. Сохранить файл в `reports/` по формуле: `YYYY-MM-DD-[домен]-seo-report.html`
6. Сообщить путь к файлу

## HTML-шаблон

```html
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SEO-отчёт — [URL]</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 860px; margin: 40px auto; padding: 0 20px; color: #222; }
    .header { border-bottom: 2px solid #333; padding-bottom: 16px; margin-bottom: 24px; }
    .header h1 { margin: 0 0 4px; font-size: 22px; }
    .meta { color: #666; font-size: 14px; }
    .summary { background: #f5f5f5; border-left: 4px solid #333; padding: 12px 16px; margin-bottom: 24px; font-size: 15px; }
    .score { display: inline-block; font-size: 32px; font-weight: bold; margin-bottom: 24px; }
    .score.high { color: #2a9d2a; }
    .score.mid  { color: #e07b00; }
    .score.low  { color: #cc2222; }
    h2 { font-size: 17px; margin: 28px 0 12px; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
    .issue { margin-bottom: 16px; padding: 12px 14px; border-radius: 4px; }
    .issue.high { background: #fff0f0; border-left: 4px solid #cc2222; }
    .issue.mid  { background: #fff8ed; border-left: 4px solid #e07b00; }
    .issue.low  { background: #f5f8ff; border-left: 4px solid #4a7fd4; }
    .issue .title { font-weight: bold; margin-bottom: 4px; }
    .issue .why   { color: #555; font-size: 14px; margin-bottom: 4px; }
    .issue .fix   { font-size: 14px; }
    .issue .fix::before { content: "Исправить: "; font-weight: bold; }
    .badge { display: inline-block; font-size: 11px; font-weight: bold; padding: 2px 7px; border-radius: 3px; margin-bottom: 6px; text-transform: uppercase; }
    .badge.high { background: #cc2222; color: #fff; }
    .badge.mid  { background: #e07b00; color: #fff; }
    .badge.low  { background: #4a7fd4; color: #fff; }
    .ok-list { list-style: none; padding: 0; }
    .ok-list li { padding: 5px 0; color: #2a9d2a; font-size: 14px; }
    .ok-list li::before { content: "✓ "; }
  </style>
</head>
<body>

  <div class="header">
    <h1>SEO-аудит: [URL]</h1>
    <div class="meta">Дата: [ДАТА]</div>
  </div>

  <div class="summary">[КРАТКОЕ РЕЗЮМЕ ОДНОЙ ФРАЗОЙ]</div>

  <div class="score [класс]">Оценка: [БАЛЛ] / 100</div>

  <!-- КРИТИЧЕСКИЕ ПРОБЛЕМЫ (приоритет: Высокий) -->
  <h2>Критические проблемы</h2>

  <div class="issue high">
    <div class="badge high">Высокий</div>
    <div class="title">[Название проблемы]</div>
    <div class="why">[Почему это важно]</div>
    <div class="fix">[Как исправить]</div>
  </div>

  <!-- ЗАМЕЧАНИЯ (приоритет: Средний и Низкий) -->
  <h2>Замечания</h2>

  <div class="issue mid">
    <div class="badge mid">Средний</div>
    <div class="title">[Название проблемы]</div>
    <div class="why">[Почему это важно]</div>
    <div class="fix">[Как исправить]</div>
  </div>

  <div class="issue low">
    <div class="badge low">Низкий</div>
    <div class="title">[Название проблемы]</div>
    <div class="why">[Почему это важно]</div>
    <div class="fix">[Как исправить]</div>
  </div>

  <!-- ВСЁ В ПОРЯДКЕ -->
  <h2>Всё в порядке</h2>
  <ul class="ok-list">
    <li>[Параметр проверен, замечаний нет]</li>
  </ul>

</body>
</html>
```

## Классы для оценки

| Балл | Класс CSS | Интерпретация |
|---|---|---|
| 80–100 | `high` (зелёный) | Хорошее состояние |
| 50–79 | `mid` (оранжевый) | Есть что улучшить |
| 0–49 | `low` (красный) | Требует серьёзной работы |

## Примечания

- Если в аудите нет критических проблем — раздел не включать (не выводить пустой блок)
- Если нет замечаний — аналогично
- Папку `reports/` создать если не существует
- Имя файла строчными буквами, без кириллицы: `2026-06-04-site-ru-seo-report.html`
