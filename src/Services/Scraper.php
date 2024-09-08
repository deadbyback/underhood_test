<?php

namespace App\Services;

use App\Models\Trademark;
use App\Storage\FileStorage;

class Scraper
{
    private ?string $csrfToken;
    private int $pagesCount;
    private string $keyword;

    private const SEARCH_URL = 'https://search.ipaustralia.gov.au/trademarks/search/doSearch';

    /**
     * Scraper constructor.
     */
    public function __construct()
    {
        $this->csrfToken = (new CSRFGetter())->getCSRFToken();
    }

    /**
     * Search for trademarks using the given keyword.
     *
     * @param string $keyword The keyword to search for.
     */
    public function search(string $keyword): void
    {
        if ($this->csrfToken === null) {
            echo 'Error: Empty CSRF token!';
            return;
        }

        $this->keyword = $keyword;
        $paginator = new Paginator($this->keyword);
        $this->pagesCount = $paginator->getPagesCount();

        if ($this->pagesCount === 0) {
            echo 'Error: Empty pages!';
            return;
        }

        echo 'Results: ' . $paginator->getItemsCount();

        $postData = $this->buildPostData();
        $headers = $this->buildHeaders();
        $this->sendSearchRequest($postData, $headers);
    }

    /**
     * Send a search request using cURL.
     *
     * @param array $postData The data to be sent via POST.
     * @param array $headers The request headers.
     */
    private function sendSearchRequest(array $postData, array $headers): void
    {
        $response = $this->executeCurlRequest(self::SEARCH_URL, $postData, $headers);
        if (!$response) return;

        $httpCode = curl_getinfo($response['ch'], CURLINFO_HTTP_CODE);
        curl_close($response['ch']);

        $data = $this->handleResponse($response['response'], $httpCode);

        $this->storeResults($data);
        echo 'Import have been finished!';
    }

    /**
     * Execute a cURL request.
     *
     * @param string $url The URL to send the request to.
     * @param array $postData The data to be sent via POST.
     * @param array $headers The request headers.
     * @return array|null The cURL handle and response, or null on failure.
     */
    private function executeCurlRequest(string $url, array $postData, array $headers): ?array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
            return null;
        }

        return ['ch' => $ch, 'response' => $response];
    }

    /**
     * Handle the HTTP response.
     *
     * @param string $response The response body.
     * @param int $httpCode The HTTP status code.
     * @return array The processed data.
     */
    private function handleResponse(string $response, int $httpCode): array
    {
        $data = [];

        if ($httpCode === 302) {
            if (preg_match('/location: (.*)\r\n/', $response, $matches)) {
                $redirectUrl = trim($matches[1]);
                for ($i = 0; $i < $this->pagesCount; $i++) {
                    $data = array_merge($data, Parser::parse($this->generateUrl($redirectUrl, $i)));
                }
            }
        } elseif ($httpCode === 200) {
            $data = Parser::parseHtml($response);
        }

        return $data;
    }

    /**
     * Store the results data.
     *
     * @param array $data The data to store.
     */
    private function storeResults(array $data): void
    {
        $fileStorage = new FileStorage();

        foreach ($data as $item) {
            /** @var Trademark $item */
            echo $item->toJson() . PHP_EOL;
            $params = [
                'suffix' => $this->keyword,
            ];
            $fileStorage->save($item, $item->getShortClassName(), $params);
        }
    }

    /**
     * Build the POST data for the search request.
     *
     * @return array The POST data.
     */
    private function buildPostData(): array
    {
        return [
            '_csrf' => $this->csrfToken,
            'wv[0]' => $this->keyword,
            'wt[0]' => 'PART',
            'weOp[0]' => 'AND',
            'wv[1]' => '',
            'wt[1]' => 'PART',
            'weOp[1]' => 'AND',
            'wrOp' => 'AND',
            '_sw' => 'on',
            'classList' => '',
            'ct' => 'A',
            'status' => '',
            'dateType' => 'LODGEMENT_DATE',
            'fromDate' => '',
            'toDate' => '',
            'ia' => '',
            'gsd' => '',
            'endo' => '',
            'nameField[0]' => 'OWNER',
            'name[0]' => '',
            'attorney' => '',
            'oAcn' => '',
            'idList' => '',
            'ir' => '',
            'publicationFromDate' => '',
            'publicationToDate' => '',
            'i' => '',
            'c' => '',
            'originalSegment' => '',
        ];
    }

    /**
     * Build the request headers.
     *
     * @return array The request headers.
     */
    private function buildHeaders(): array
    {
        return [
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-language: ru,en;q=0.9,en-GB;q=0.8,en-US;q=0.7',
            'cache-control: max-age=0',
            'content-type: application/x-www-form-urlencoded',
            'cookie: XSRF-TOKEN=' . $this->csrfToken,
            'origin: https://search.ipaustralia.gov.au',
            'referer: https://search.ipaustralia.gov.au/trademarks/search/advanced',
            'sec-ch-ua: "Chromium";v="128", "Not;A=Brand";v="24", "DuckDuckGo";v="128"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: document',
            'sec-fetch-mode: navigate',
            'sec-fetch-site: same-origin',
            'sec-fetch-user: ?1',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        ];
    }

    /**
     * Generate a URL with a page number.
     *
     * @param string $redirectUrl The base redirect URL.
     * @param int $pageNumber The page number to append.
     * @return string The generated URL.
     */
    private function generateUrl(string $redirectUrl, int $pageNumber): string
    {
        return $pageNumber === 0 ? $redirectUrl : $redirectUrl . "&p={$pageNumber}";
    }
}