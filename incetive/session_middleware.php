<?php

/**
 * Middleware simples de autenticacao por sessao.
 * Baseado no login feito em libera_logon.php.
 */

if (!function_exists('startSessionIfNeeded')) {
    function startSessionIfNeeded()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}

if (!function_exists('isAuthenticatedSession')) {
    function isAuthenticatedSession()
    {
        return !empty($_SESSION['user']) && !empty($_SESSION['login']);
    }
}

if (!function_exists('requireAuthenticatedSession')) {
    function requireAuthenticatedSession($message = 'nao autenticado')
    {
        startSessionIfNeeded();

        if (!isAuthenticatedSession()) {
            echo $message;
            exit;
        }
    }
}

