<?php
require_once 'vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

$registry = new CollectorRegistry(new InMemory());

$counter = $registry->getOrRegisterCounter('', 'http_requests_total', 'Total HTTP requests', ['endpoint', 'method', 'status']);
$dbCounter = $registry->getOrRegisterCounter('', 'db_queries_total', 'Total DB queries');

// При каждом запросе к /metrics увеличиваем счётчики (чтобы точно было что показать)
$counter->inc(['endpoint' => '/users', 'method' => 'GET', 'status' => '200']);
$counter->inc(['endpoint' => '/posts', 'method' => 'GET', 'status' => '200']);
$dbCounter->inc(['table' => 'users', 'operation' => 'select']);
$dbCounter->inc(['table' => 'posts', 'operation' => 'select']);

header('Content-Type: ' . RenderTextFormat::MIME_TYPE);
$renderer = new RenderTextFormat();
echo $renderer->render($registry->getMetricFamilySamples());