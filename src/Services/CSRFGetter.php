<?php
namespace App\Services;

/**
 * Class CSRFGetter
 * Service for obtaining a CSRF token from a specific URL.
 */
class CSRFGetter
{
    /**
     * Returns the CSRF token.
     *
     * @return string|null CSRF token or null if the token is not found.
     */
    public function getCSRFToken(): ?string
    {
        return $this->request('https://search.ipaustralia.gov.au/trademarks/search/advanced');
    }

    /**
     * Executes an HTTP request and returns the CSRF token from the headers.
     *
     * @param string $url URL to request.
     * @return string|null CSRF token or null if the token is not found.
     */
    private function request(string $url): ?string
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_NOBODY, 1);

            $output = curl_exec($ch);
            $this->checkCurlError($ch);
            $this->checkHttpError($ch, $url);

            curl_close($ch);

            return $this->extractCsrfTokenFromHeaders($output);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Checks for cURL errors.
     *
     * @param resource $ch cURL resource.
     * @throws \Exception In case of a cURL error.
     */
    private function checkCurlError($ch): void
    {
        if (curl_errno($ch)) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
    }

    /**
     * Checks for HTTP status code errors.
     *
     * @param resource $ch cURL resource.
     * @param string $url URL requested.
     * @throws \Exception In case of an HTTP error.
     */
    private function checkHttpError($ch, string $url): void
    {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new \Exception("HTTP Error: $httpCode while requesting $url");
        }
    }

    /**
     * Extracts the CSRF token from response headers.
     *
     * @param string $headerOutput Headers string.
     * @return string|null CSRF token or null if the token is not found.
     */
    private function extractCsrfTokenFromHeaders(string $headerOutput): ?string
    {
        $headers = explode("\n", rtrim($headerOutput));
        foreach ($headers as $header) {
            [$key, $value] = array_map(
                'trim',
                explode(':', $header, 2) + [null, null]
            );
            if ($key === 'set-cookie') {
                $cookie = explode('=', $value, 2);
                if ($cookie[0] === 'XSRF-TOKEN') {
                    return strtok($cookie[1], ';');
                }
            }
        }

        return null;
    }
}