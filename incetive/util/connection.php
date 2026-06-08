<?php

require_once __DIR__ . '/../env.php';
date_default_timezone_set('America/Sao_Paulo');

// Carrega variaveis do .env
$env = loadEnv(__DIR__ . '/../.env');

$required = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required as $key) {
    if (!isset($env[$key]) || $env[$key] === '') {
        die("Erro: variavel {$key} nao definida no .env");
    }
}

// Monta string de conexao PostgreSQL
$connString = sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    $env['DB_HOST'],
    $env['DB_PORT'],
    $env['DB_NAME'],
    $env['DB_USER'],
    $env['DB_PASS']
);

// Conecta ao banco
$conn = pg_connect($connString);

// Verifica conexao
if (!$conn) {
    die("Erro ao conectar ao PostgreSQL: " . pg_last_error());
}

// Define URLs globais do sistema
if (!defined('BASE_URL') && isset($env['BASE_URL'])) {
    define('BASE_URL', rtrim($env['BASE_URL'], '/'));
}
if (!defined('API_URL') && isset($env['API_URL'])) {
    define('API_URL', rtrim($env['API_URL'], '/'));
}

