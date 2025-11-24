Backend + Monitoring (nginx, PHP, MariaDB, Prometheus, Grafana)
Описание

Этот проект — минимальный рабочий бэкенд с CRUD API (две сущности: users и posts) и встроенной поддержкой метрик для Prometheus. Всё запускается в Docker Compose: nginx + php-fpm + mariadb + prometheus + grafana.

Что включено

Nginx (порт 8080) проксирует запросы в PHP-FPM.

PHP 8.2 FPM — простое REST API (/users, /posts).

MariaDB 10.x — инициализация схемы в mariadb/init.sql.

Prometheus — собирает /metrics с приложения.

Grafana — автоматически провиженится datasource и базовый дашборд.


## Запуск

```bash
docker-compose up -d

## Создать user-a
curl -X POST http://localhost:8080/users -d '{"name":"Test","email":"test@example.com"}' -H "Content-Type: application/json"

{
id: 2,
name: "Test",
email: "test@test.test",
created_at: "2025-11-24 17:33:45"
}

## Удалить
curl -X DELETE http://localhost:8080/users/1

## 
curl -X POST http://localhost:8080/posts  -H "Content-Type: application/json"  -d '{  "user_id": 2, "title": "My test post", "body": "This is test post."}'

{
id: 1,
user_id: 2,
title: "My test post",
body: "This is test post.",
created_at: "2025-11-24 18:09:27"
}

