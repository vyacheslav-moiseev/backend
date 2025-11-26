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

Структура проекта
.
├── www/
│   ├── index.php          ← весь API + /metrics endpoint
│   └── metrics.php        ← (не обязателен, можно удалить)
├── php-fpm/
│   └── Dockerfile
├── nginx/
│   └── default.conf       ← финальный рабочий конфиг
├── prometheus/
│   └── prometheus.yml
├── grafana/
│   └── provisioning/
│       └── dashboards/
│           ├── provider.yml
│           └── backend-dashboard.json ← живой дашборд
├── db/                    ← SQL-дамп для инициализации
├── docker-compose.yml
└── README.md


Как запустить (одна команда)

git clone <твой-репозиторий>
cd backend
docker compose up -d --build

# Ждём ~40 секунд и открываем:
open http://localhost:3000     # Grafana (admin/admin)
open http://localhost:9090     # Prometheus
open http://localhost:8080     # API



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