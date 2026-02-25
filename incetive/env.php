<?php
/**
 * Loader simples de .env compatível com PHP 7.2
 * Uso: $env = loadEnv(__DIR__ . '/.env');
 */

function loadEnv($path)
{
    if (!is_file($path)) {
        throw new RuntimeException("Arquivo .env não encontrado em: " . $path);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException("Não foi possível ler o arquivo .env em: " . $path);
    }

    $env = [];

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignora vazio e comentários
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        // Permite "export KEY=VALUE"
        if (strpos($line, 'export ') === 0) {
            $line = trim(substr($line, 7));
        }

        // Precisa ter "="
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        if ($key === '') {
            continue;
        }

        // Remove aspas se tiver
        if (strlen($val) >= 2) {
            $first = $val[0];
            $last  = $val[strlen($val) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $val = substr($val, 1, -1);
            }
        }

        // Suporte básico a \n em strings
        $val = str_replace('\n', "\n", $val);

        $env[$key] = $val;

        // Opcional: popula getenv/$_ENV/$_SERVER
        if (getenv($key) === false) {
            putenv($key . '=' . $val);
        }
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }

    return $env;
}
