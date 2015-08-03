# Тестовый макет Яндекс.Кассы

Для отладки обработчиков [HTTP-уведомлений][1] Яндекс.Кассы.

## Возможности

* Принимает данные из _платёжной формы_
* Позволяет имитировать запросы `checkOrder`, `checkOrder`, `checkOrder`
* Работает по `HTTP` и `HTTPS`
* Подписывает данные методом `MD5`
* Позволяеет вручную модифицировать данные перед отправкой запросов

## Установка

```bash
git clone git@github.com:aminin/yandex-kassa-mock.git
cd yandex-kassa-mock
composer install
bower install
```

## Настройка

Создайте файл `config.php`

```bash
cp config.php.dist config.php
```

и установите в нём свои настройки.

## Запуск

```
cd web
php -S localhost:4567 index.php
```

## Ссылки

[1]: https://money.yandex.ru/doc.xml?id=527069
