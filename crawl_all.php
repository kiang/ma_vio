<?php

require_once 'vendor/autoload.php';

use Crawler\MaVIOCrawler;

$crawler = new MaVIOCrawler();

echo "MA VIO Data Crawler\n";
echo "==================\n\n";

$progressCallback = function($current, $total, $id, $name) {
    $percentage = round(($current / $total) * 100, 1);
    echo sprintf("[%d/%d] (%s%%) Crawling %s (%s)...\n", 
        $current, $total, $percentage, $name, $id);
};

$allData = $crawler->crawlAllCerRefIds($progressCallback);

$totalRows = count($allData['rows']);
echo "\nCrawl completed! Found " . $totalRows . " records\n";

if ($totalRows > 0) {
    $csvFilename = 'data/cases.csv';
    
    if ($crawler->exportToCSV($allData, $csvFilename)) {
        echo "✓ CSV saved to " . $csvFilename . "\n";
        echo "File size: " . number_format(filesize($csvFilename)) . " bytes\n";
    } else {
        echo "✗ CSV export failed!\n";
        exit(1);
    }
} else {
    echo "No data retrieved.\n";
    exit(1);
}