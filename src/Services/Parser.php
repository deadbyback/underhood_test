<?php

namespace App\Services;

use App\Models\Trademark;

class Parser
{
    private const BASE_URL = 'https://search.ipaustralia.gov.au';
    private const RESULTS_TABLE_XPATH = '//*[@id="resultsTable"]';
    private const NO_IMAGE_FOUND = 'No image found';

    /**
     * Parses the provided URL, fetches the HTML content, and extracts relevant data.
     *
     * @param string $url The URL to fetch and parse.
     * @return array|null An array of parsed data, or null if fetching the page failed.
     */
    public static function parse(string $url): ?array
    {
        $html = self::fetchPage($url);
        if ($html) {
            return self::extractDataFromHtml($html);
        } else {
            echo "Error: Can't load the page! \n";
            return [];
        }
    }

    /**
     * Fetches the HTML content from the given URL.
     *
     * @param string $url The URL to fetch.
     * @return string|null The HTML content, or null if an error occurred.
     */
    private static function fetchPage(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: null;
    }

    /**
     * Extracts data from the provided HTML content.
     *
     * @param string $html The HTML content to parse.
     * @return array An array of parsed data.
     */
    public static function extractDataFromHtml(string $html): array
    {
        $parsedData = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $table = $xpath->query(self::RESULTS_TABLE_XPATH)->item(0);

        if ($table) {
            $rows = $xpath->query('.//tbody/tr', $table);
            foreach ($rows as $row) {
                $columns = $xpath->query('td', $row);
                if ($columns->length > 0) {
                    $imgTag = $columns->item(3)->getElementsByTagName('img')->item(0);
                    $imgUrl = $imgTag ? $imgTag->getAttribute('src') : null;
                    $parsedData[] = self::createTrademarkFromColumns($columns, $imgUrl);
                }
            }
        } else {
            echo "Error: Table with id 'resultsTable' not found.\n";
        }
        return $parsedData;
    }

    /**
     * Gets the cleaned class value from the specified column.
     *
     * @param \DOMNodeList $columns The list of columns from a table row.
     * @param int $index The index of the column containing the class value.
     * @return string The cleaned class value.
     */
    private static function getClassFromColumn(\DOMNodeList $columns, int $index): string
    {
        $class = preg_replace('/[^a-zA-Z0-9 ]/', '', trim($columns->item($index)->nodeValue));
        return empty($class) ? 'All' : $class;
    }

    /**
     * Generates the URL of the details page from the specified columns.
     *
     * @param \DOMNodeList $columns The list of columns from a table row.
     * @return string The full URL of the details page.
     */
    private static function getUrlDetailsPage(\DOMNodeList $columns): string
    {
        $urlDetailsPage = $columns->item(2)->getElementsByTagName('a')->item(0)->getAttribute('href');
        return !empty($urlDetailsPage) ? self::BASE_URL . current(explode('?', $urlDetailsPage)) : '';
    }

    /**
     * Creates a Trademark object from the specified columns and image URL.
     * If $imageUrl is not specified - creates a shift by item positions on the table.
     *
     * @param \DOMNodeList $columns The list of columns from a table row.
     * @param string|null $imageUrl The URL of the image, or null if none.
     * @return Trademark The populated Trademark object.
     */
    private static function createTrademarkFromColumns(\DOMNodeList $columns, ?string $imageUrl): Trademark
    {
        $classIndex = $imageUrl ? 5 : 4;
        $nameIndex = $imageUrl ? 4 : 3;
        $statusIndex = $imageUrl ? 6 : 5;

        $trademark = new Trademark();
        $trademark->initializeProperties([
            'id' => trim($columns->item(0)->nodeValue),
            'number' => trim($columns->item(2)->nodeValue),
            'url_logo' => $imageUrl ?? self::NO_IMAGE_FOUND,
            'name' => trim($columns->item($nameIndex)->nodeValue),
            'class' => self::getClassFromColumn($columns, $classIndex),
            'status' => preg_replace('/[^a-zA-Z0-9 ]/', '', trim($columns->item($statusIndex)->nodeValue)),
            'url_details_page' => self::getUrlDetailsPage($columns)
        ]);

        return $trademark;
    }
}