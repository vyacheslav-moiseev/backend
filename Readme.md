# Monitoring System · Prometheus + Grafana + Node Exporter + Alertmanager

![Go](https://img.shields.io/badge/Go-1.23-00ADD8?logo=go&logoColor=white)
![Prometheus](https://img.shields.io/badge/Prometheus-E6522C?logo=prometheus&logoColor=white)
![Grafana](https://img.shields.io/badge/Grafana-F46800?logo=grafana&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?logo=docker&logoColor=white)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)


Backend API + Full Monitoring Stack
PHP 8 + Nginx + MariaDB + Prometheus + Grafana + Docker Compose
Полностью рабочий, проверенный на macOS (M1/M2/M3) и Linux проект тестового задания

docker compose up -d
→ http://localhost:8080     — API
→ http://localhost:9090     — Prometheus (все таргеты зелёные)
→ http://localhost:3000     — Grafana (admin / admin)


Компонент,Статус,Комментарий
CRUD API (users + posts),Done,"Полный REST, валидация, ошибки"
Метрики из PHP,Done,"http_requests_total, db_queries_total"
Nginx status,Done,Через nginx-prometheus-exporter
Prometheus,Done,"Все нужные job’ы, работает на macOS"
Grafana + готовый дашборд,Done,Автоматически подтягивается при старте
Всё в Docker Compose,Done,Одна команда — всё поднимается
Работает на Apple Silicon,Done,Проверено на M2 Pro

## Быстрый старт

```bash
# 1. Клонируем репозиторий
git clone https://github.com/vyacheslav-moiseev/backend.git
cd monitoring

# 2. Запускаем всё
docker compose up -d

# 3. Готово! Открываем дашборды



Проверка, что всё работает

# Генерируем нагрузку
for i in {1..100}; do
curl -s http://localhost:8080/users >/dev/null
curl -s http://localhost:8080/posts >/dev/null
done

# Проверяем метрики
curl -s http://localhost:8080/metrics | head


Особенности и почему всё работает на macOS

Используется host.docker.internal + extra_hosts в Prometheus → достаёт метрики с хоста
/metrics прокидывается прямо в index.php → никаких 404 и text/html
Nginx status через официальный nginx-prometheus-exporter
MariaDB exporter отключён за ненадобностью (можно включить позже)
Grafana дашборд подтягивается автоматически через provisioning


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


## ТЗ

Бэкэнд приложения
В docker развернуть nginx, php >8, mariadb >10
Реализовать API приложения с CRUD операциями в БД, не менее двух сущностей
Дополнительно:
прикрутить метрики prometheus, на выбор исполнителя, метрики должны иметь смысл
развернуть prometheus & grafana
настроить экспорт метрик в prometheus
настроить визуализацию метрик в grafana