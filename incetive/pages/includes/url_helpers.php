<?php

if (!function_exists('app_base_url')) {
    function app_base_url()
    {
        static $baseUrl = null;

        if ($baseUrl !== null) {
            return $baseUrl;
        }

        $projectRoot = realpath(__DIR__ . '/../../');
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

        if ($projectRoot && $documentRoot) {
            $normalizedProjectRoot = str_replace('\\', '/', $projectRoot);
            $normalizedDocumentRoot = str_replace('\\', '/', $documentRoot);

            if (strpos($normalizedProjectRoot, $normalizedDocumentRoot) === 0) {
                $baseUrl = substr($normalizedProjectRoot, strlen($normalizedDocumentRoot));
                $baseUrl = str_replace('\\', '/', $baseUrl);
                $baseUrl = rtrim($baseUrl, '/');

                return $baseUrl;
            }
        }

        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
        $fallback = preg_replace('#/pages/.*$#', '', $scriptName);
        $baseUrl = rtrim((string)$fallback, '/');

        return $baseUrl;
    }
}

if (!function_exists('app_url')) {
    function app_url($path = '')
    {
        $baseUrl = app_base_url();
        $path = ltrim((string)$path, '/');

        if ($path === '') {
            return $baseUrl !== '' ? $baseUrl . '/' : '/';
        }

        return ($baseUrl !== '' ? $baseUrl : '') . '/' . $path;
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path)
    {
        return app_url($path);
    }
}
