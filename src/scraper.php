<?php

require __DIR__ . '/../src/db.php';

class CarAdScraper
{
    private $url;
    private $limit;

    public function __construct(string $url, int $limit = 10000)
    {
        $this->url = $url;
        $this->limit = $limit;
    }

    public function scrapeAndStoreAds(): void
    {
        global $pdo;

        $html = $this->fetchHtml();
        $ads = $this->extractCarAds($html);
        $this->insertCarAds($ads);
    }

    private function fetchHtml(): string
    {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $html = curl_exec($ch);
        curl_close($ch);

        return $html ?: '';
    }

    private function extractCarAds(string $html): array
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $ads = [];
        $adNodes = $xpath->query('//div[contains(@class, "Card")]');

        foreach ($adNodes as $ad) {
            $ads[] = $this->scrapeAdDetails($xpath, $ad);
            if (count($ads) >= $this->limit) {
                break;
            }
        }

        return $ads;
    }

    private function scrapeAdDetails(DOMXPath $xpath, DOMElement $ad): array
    {
        $titleNode = $xpath->query('.//h3[contains(@class, "Card-heading")]/a', $ad)->item(0);
        $title = $titleNode ? trim($titleNode->textContent) : '';
        $make = explode(' ', $title)[0] ?? '';
        $model = trim(str_replace($make, '', $title));

        $modelYearNode = $xpath->query('.//dl[contains(@class, "Card-carData")]//dt[text()="År:"]/following-sibling::dd', $ad)->item(0);
        $modelYear = $modelYearNode ? (int) $modelYearNode->textContent : 0;

        $imageUrlNode = $xpath->query('.//div[contains(@class, "Card-image")]/img', $ad)->item(0);
        $imageUrl = $imageUrlNode ? $imageUrlNode->getAttribute('data-src') : '';

        $pageUrlNode = $xpath->query('.//h3[contains(@class, "Card-heading")]/a', $ad)->item(0);
        $pageUrl = $pageUrlNode ? $pageUrlNode->getAttribute('href') : '';

        return [
            'title' => $title,
            'description' => 'No description available',
            'make' => $make,
            'model' => $model,
            'model_year' => $modelYear,
            'registration_number' => null,
            'registration_number_from_image' => null,
            'image_url' => $imageUrl,
            'page_url' => $pageUrl,
        ];
    }

    private function insertCarAds(array $ads): void
    {
        global $pdo;

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO ads
            (title, description, make, model, model_year, registration_number, registration_number_from_image, image_url, page_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($ads as $ad) {
            $stmt->execute([
                $ad['title'],
                $ad['description'],
                $ad['make'],
                $ad['model'],
                $ad['model_year'],
                $ad['registration_number'],
                $ad['registration_number_from_image'],
                $ad['image_url'],
                $ad['page_url']
            ]);
        }

        echo "Inserted ads successfully.\n";
    }
}

$url = 'https://bilweb.se/sok?query=&type=1&limit=10000';
$scraper = new CarAdScraper($url);
$scraper->scrapeAndStoreAds();
