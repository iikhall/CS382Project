<?php
declare(strict_types=1);

/**
 * Page/endpoint access guards. Include AFTER includes/db.php.
 *
 *   require_login();          guard any authenticated page
 *   require_admin();          guard admin-only pages
 *   api_require_login();      JSON 401 for AJAX endpoints
 *   api_require_admin();      JSON 403 for AJAX endpoints
 *   base_url('login.php');    project-root relative URL (works under vhost or /CS382PROJECT/)
 */

function base_url(string $path = ''): string
{
    // Project root = parent of /includes. Map it to a web path via DOCUMENT_ROOT.
    $projectFs = str_replace('\\', '/', dirname(__DIR__));
    $docRoot   = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
    $base      = $docRoot !== '' && str_starts_with($projectFs, $docRoot)
        ? substr($projectFs, strlen($docRoot))
        : '';
    $base = '/' . trim($base, '/');
    $base = $base === '/' ? '' : $base;
    return $base . '/' . ltrim($path, '/');
}

function require_login(): void
{
    if (!User::isLoggedIn()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (!User::isAdmin()) {
        header('Location: ' . base_url('dashboard.php'));
        exit;
    }
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function api_require_login(): void
{
    if (!User::isLoggedIn()) {
        json_response(['ok' => false, 'error' => 'Not authenticated.'], 401);
    }
}

function api_require_admin(): void
{
    api_require_login();
    if (!User::isAdmin()) {
        json_response(['ok' => false, 'error' => 'Admin access required.'], 403);
    }
}
