<?php
namespace App\Services;

class Paginator
{
    private const ITEMS_PER_PAGE = 100;
    private const BASE_COUNT_URL = 'https://search.ipaustralia.gov.au/trademarks/search/count?wv%5B0%5D=';
    private const HTTP_STATUS_OK = 200;

    private int $itemsCount = 0;
    private int $pagesCount = 0;

    /**
     * Paginator constructor.
     * Initializes the paginator with the total number of items matching the word.
     *
     * @param string $word The search word.
     */
    public function __construct(string $word)
    {
        $this->itemsCount = $this->getFoundItemsCount($word);
        $this->pagesCount = (int) ceil($this->itemsCount / self::ITEMS_PER_PAGE);
    }

    /**
     * Returns the total number of found items.
     *
     * @return int Number of found items.
     */
    public function getItemsCount(): int
    {
        return $this->itemsCount;
    }

    /**
     * Returns the total number of pages based on the count of items and items per page.
     *
     * @return int Number of pages.
     */
    public function getPagesCount(): int
    {
        return $this->pagesCount;
    }

    /**
     * Retrieves the count of items found for the given word from the API.
     *
     * @param string $word The search word.
     * @return int The count of found items.
     */
    private function getFoundItemsCount(string $word): int
    {
        $url = self::BASE_COUNT_URL . $word;

        try {
            $response = $this->executeCurlRequest($url);
            $data = json_decode($response, true);

            return $data['count'] ?? 0;
        } catch (\Exception $exception) {
            return 0;
        }
    }

    /**
     * Executes a cURL request to the given URL and returns the response.
     *
     * @param string $url The URL to request.
     * @return string The response from the cURL request.
     * @throws \Exception If any cURL or HTTP errors occur.
     */
    private function executeCurlRequest(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        $this->checkCurlError($ch);
        $this->checkHttpError($ch, $url);

        curl_close($ch);

        return $response;
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
        if ($httpCode !== self::HTTP_STATUS_OK) {
            throw new \Exception("HTTP Error: $httpCode while requesting $url");
        }
    }
}