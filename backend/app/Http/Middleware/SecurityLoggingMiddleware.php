<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // セキュリティ関連のイベントをログ記録
        $this->logSecurityEvents($request, $response);

        return $response;
    }

    /**
     * セキュリティイベントのログ記録
     */
    private function logSecurityEvents(Request $request, Response $response): void
    {
        $statusCode = $response->getStatusCode();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $userId = $request->user()?->id;
        $route = $request->route()?->getName() ?? $request->path();

        $context = [
            'ip' => $ip,
            'user_agent' => $userAgent,
            'user_id' => $userId,
            'route' => $route,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ];

        // 認証失敗 (401)
        if ($statusCode === 401) {
            Log::warning('Authentication failed', $context);
        }

        // 権限エラー (403)
        if ($statusCode === 403) {
            Log::warning('Authorization failed', $context);
        }

        // レート制限 (429)
        if ($statusCode === 429) {
            Log::warning('Rate limit exceeded', $context);
        }

        // サーバーエラー (5xx)
        if ($statusCode >= 500) {
            Log::error('Server error occurred', array_merge($context, [
                'status_code' => $statusCode,
            ]));
        }

        // 疑わしいリクエスト検出
        $this->detectSuspiciousActivity($request, $context);
    }

    /**
     * 疑わしい活動の検出
     */
    private function detectSuspiciousActivity(Request $request, array $context): void
    {
        // SQLインジェクション試行の検出
        $suspiciousPatterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/<script[^>]*>/i',
            '/javascript:/i',
        ];

        $requestData = json_encode($request->all());
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $requestData) || preg_match($pattern, $request->fullUrl())) {
                Log::alert('Suspicious activity detected', array_merge($context, [
                    'pattern' => $pattern,
                    'request_data' => $requestData,
                ]));
                break;
            }
        }

        // 異常な大量リクエストの検出
        if (strlen($requestData) > 100000) { // 100KB以上
            Log::warning('Large request detected', array_merge($context, [
                'request_size' => strlen($requestData),
            ]));
        }
    }
}
