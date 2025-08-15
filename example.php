<?php

require_once 'vendor/autoload.php';

use Crawler\MaVIOCrawler;

$crawler = new MaVIOCrawler();

echo "Starting MA VIO Crawler...\n";
echo "==========================\n\n";

echo "1. Fetching search page to get verification token...\n";
$searchPage = $crawler->getSearchPage();
if ($searchPage['success']) {
    echo "   ✓ Token obtained: " . substr($searchPage['token'], 0, 50) . "...\n\n";
} else {
    echo "   ✗ Error: " . $searchPage['error'] . "\n";
    exit(1);
}

echo "2. Performing search with CER_REF_ID = 'D,E'...\n";
$searchResult = $crawler->searchData();

if ($searchResult['success']) {
    echo "   ✓ Search completed successfully!\n\n";
    
    echo "3. Parsing results...\n";
    $data = $searchResult['data'];
    echo "   Found " . $data['total_rows'] . " rows of data\n\n";
    
    if ($data['total_rows'] > 0) {
        echo "4. Sample data (first 5 rows):\n";
        echo "   " . str_repeat("-", 80) . "\n";
        
        $maxRows = min(5, $data['total_rows']);
        for ($i = 0; $i < $maxRows; $i++) {
            echo "   Row " . ($i + 1) . ": ";
            echo implode(" | ", array_slice($data['rows'][$i], 0, 5)) . "\n";
        }
        echo "   " . str_repeat("-", 80) . "\n\n";
    }
    
    echo "5. Saving results...\n";
    $crawler->saveHtmlToFile($searchResult['html'], 'output/search_results.html');
    echo "   ✓ HTML saved to output/search_results.html\n";
    
    $jsonData = $crawler->extractDataAsJson($searchResult);
    file_put_contents('output/search_results.json', $jsonData);
    echo "   ✓ JSON data saved to output/search_results.json\n\n";
    
    if (!empty($data['pagination'])) {
        echo "6. Pagination info:\n";
        foreach ($data['pagination'] as $page) {
            echo "   - " . $page['text'] . " -> " . $page['href'] . "\n";
        }
    }
    
} else {
    echo "   ✗ Search failed: " . $searchResult['error'] . "\n";
    exit(1);
}

echo "\n";
echo "==========================\n";
echo "Example: Search with custom parameters\n";
echo "==========================\n\n";

$customSearch = $crawler->searchWithCustomParams('D,E');
if ($customSearch['success']) {
    echo "Custom search successful!\n";
    echo "Results: " . $customSearch['data']['total_rows'] . " rows found\n";
} else {
    echo "Custom search failed: " . $customSearch['error'] . "\n";
}

echo "\nCrawler execution completed!\n";