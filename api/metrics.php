<?php
require __DIR__ . '/vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

$registry = new CollectorRegistry(new InMemory());

// Метрики CRUD операций
$counterUsers = $registry->getOrRegisterCounter('api', 'users_requests_total', 'Total requests to users', ['method','endpoint']);
$counterPosts = $registry->getOrRegisterCounter('api', 'posts_requests_total', 'Total requests to posts', ['method','endpoint']);

// Для примера можно инкрементировать случайно (в реальном коде в контроллерах)
$counterUsers->inc(['GET','/users']);
$counterPosts->inc(['POST','/posts']);

// Экспорт метрик
header('Content-Type: ' . RenderTextFormat::MIME_TYPE);
$renderer = new RenderTextFormat();
echo $renderer->render($registry->getMetricFamilySamples());
