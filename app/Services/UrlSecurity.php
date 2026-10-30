<?php

namespace App\Services;

class UrlSecurity
{
    /**
     * Validate and return a safe internal application URL.
     * Prevents open redirect attacks by ensuring host matches application host.
     */
    public static function getSafeInternalUrl(?string $candidateUrl, string $fallbackUrl): string
    {
        if (empty($candidateUrl)) {
            return $fallbackUrl;
        }

        $candidateUrl = trim($candidateUrl);
        if ($candidateUrl === '') {
            return $fallbackUrl;
        }

        $parsed = parse_url($candidateUrl);
        if ($parsed === false) {
            return $fallbackUrl;
        }

        // Scheme check: only allow http or https or relative URLs
        if (isset($parsed['scheme'])) {
            $scheme = strtolower($parsed['scheme']);
            if ($scheme !== 'http' && $scheme !== 'https') {
                return $fallbackUrl;
            }
        }

        // Host check: if host is provided, it must match current app host or configured APP_URL host
        if (! empty($parsed['host'])) {
            $candidateHost = strtolower($parsed['host']);
            $currentHost = strtolower(request()->getHost());
            $appUrlHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

            if ($candidateHost !== $currentHost && ($appUrlHost === '' || $candidateHost !== $appUrlHost)) {
                return $fallbackUrl;
            }
        }

        // Check for self-referential loop (candidate matches current request full URL exactly)
        if ($candidateUrl === request()->fullUrl()) {
            return $fallbackUrl;
        }

        return $candidateUrl;
    }
}
