<?php

require __DIR__ . '/../src/db.php';

class LicensePlateScanner
{
    private $batchSize;
    private $apiKey;
    private $lastRequestTime;
    private $startTime;
    private $maxExecutionTime;

    public function __construct($batchSize = 100, $maxExecutionTime = 300)
    {
        $this->batchSize = $batchSize;
        $this->apiKey = '72ae89e90b4fc33dc1d5062cd37b23fd91b1488d'; //valid until 2025-06-09
        $this->lastRequestTime = time();
        $this->startTime = time();
        $this->maxExecutionTime = $maxExecutionTime;
    }

    public function processAds(): void
    {
        global $pdo;

        $offset = 0;
        while (true) {
            if (time() - $this->startTime > $this->maxExecutionTime) {
                echo "Max execution time reached. Stopping process.\n";
                break;
            }

            $ads = $this->fetchAdsBatch($pdo, $offset);
            if (empty($ads)) {
                echo "All ads processed.\n";
                break;
            }

            $this->processAdsInParallel($ads, $pdo);

            $offset += $this->batchSize;
        }

        echo "Processing complete.\n";
    }

    private function fetchAdsBatch(PDO $pdo, int $offset): array
    {
        $stmt = $pdo->prepare("
            SELECT id, image_url, registration_number_from_image
            FROM ads
            WHERE registration_number_from_image IS NULL
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$this->batchSize, $offset]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function processAdsInParallel(array $ads, PDO $pdo): void
    {
        $multiCurl = [];
        $mh = curl_multi_init();

        foreach ($ads as $ad) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.platerecognizer.com/v1/plate-reader/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Token ' . $this->apiKey
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'upload' => new CURLFile($ad['image_url'])
            ]);
            curl_multi_add_handle($mh, $ch);

            $multiCurl[] = ['adId' => $ad['id'], 'ch' => $ch];
        }

        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
        } while ($active && $status == CURLM_OK);

        foreach ($multiCurl as $value) {
            $response = curl_multi_getcontent($value['ch']);
            if ($response) {
                $data = json_decode($response, true);
                $plateNumber = $this->filterPlateNumber($data['results'][0]['plate'] ?? '');

                if ($plateNumber) {
                    $this->updateRegPlate($value['adId'], $plateNumber, $pdo);
                } else {
                    echo "No plate detected for ad ID {$value['adId']}\n";
                }
            } else {
                echo "Error fetching data for ad ID {$value['adId']}\n";
            }

            curl_multi_remove_handle($mh, $value['ch']);
            curl_close($value['ch']);

            $this->rateLimitRequest();
        }

        curl_multi_close($mh);
    }

    private function filterPlateNumber(string $text): ?string
    {
        if (preg_match('/\b([a-zA-ZåäöÅÄÖ]{3}\d{3})\b/', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/\b([a-zA-ZåäöÅÄÖ]{3}\d{2}[a-zA-ZåäöÅÄÖ])\b/', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    private function updateRegPlate(int $adId, string $regPlate, PDO $pdo): void
    {
        $stmt = $pdo->prepare("
            UPDATE ads
            SET registration_number_from_image = ?
            WHERE id = ?
        ");
        $stmt->execute([$regPlate, $adId]);

        echo "Updated regplate for ad ID $adId: $regPlate\n";
    }

    private function rateLimitRequest(): void
    {
        $currentTime = time();
        $timeElapsed = $currentTime - $this->lastRequestTime;

        if ($timeElapsed < 1) {
            sleep(1 - $timeElapsed);
        }

        $this->lastRequestTime = time();
    }
}

$scanner = new LicensePlateScanner(100);
$scanner->processAds();
