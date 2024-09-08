<?php
namespace App;

use App\Services\Scraper;

class Application
{
    private string $searchTerm;
    private static ?Scraper $scraperInstance = null;

    /**
     * Configures the application with the search term.
     *
     * @param string $searchTerm The term to search for.
     */
    public function configure(string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }

    /**
     * Runs the application.
     */
    public function run(): void
    {
        $scraper = $this->getScraperInstance();
        $this->searchWithScraper($scraper);
    }

    /**
     * Returns the single instance of the Scraper.
     *
     * @return Scraper The single instance of the Scraper.
     */
    private function getScraperInstance(): Scraper
    {
        if (self::$scraperInstance === null) {
            self::$scraperInstance = new Scraper();
        }
        return self::$scraperInstance;
    }

    /**
     * Conducts a search using the scraper.
     *
     * @param Scraper $scraper The Scraper instance to use for searching.
     */
    private function searchWithScraper(Scraper $scraper): void
    {
        $scraper->search($this->searchTerm);
    }
}
?>