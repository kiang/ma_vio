<?php

namespace Crawler;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

class MaVIOCrawler
{
    private Client $client;
    private CookieJar $cookieJar;
    private string $baseUrl = 'https://ma.mohw.gov.tw';
    private array $defaultHeaders;
    
    public function __construct()
    {
        $this->cookieJar = new CookieJar();
        $this->defaultHeaders = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Sec-CH-UA' => '"Not;A=Brand";v="99", "Google Chrome";v="139", "Chromium";v="139"',
            'Sec-CH-UA-Mobile' => '?0',
            'Sec-CH-UA-Platform' => '"Linux"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36'
        ];
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'cookies' => $this->cookieJar,
            'headers' => $this->defaultHeaders,
            'verify' => true,
            'timeout' => 30,
            'allow_redirects' => true
        ]);
    }
    
    public function getSearchPage(): array
    {
        try {
            $response = $this->client->request('GET', '/Accessibility/VIOSearch/MASearchVIO');
            $html = $response->getBody()->getContents();
            
            $crawler = new DomCrawler($html);
            
            $verificationToken = '';
            $tokenInput = $crawler->filter('input[name="__RequestVerificationToken"]');
            if ($tokenInput->count() > 0) {
                $verificationToken = $tokenInput->attr('value');
            }
            
            return [
                'success' => true,
                'token' => $verificationToken,
                'cookies' => $this->getCookiesAsString(),
                'html' => $html
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function searchData(array $params = []): array
    {
        try {
            $searchPageResult = $this->getSearchPage();
            if (!$searchPageResult['success']) {
                return $searchPageResult;
            }
            
            $formData = array_merge([
                'CER_REF_ID' => 'D,E',
                '__RequestVerificationToken' => $searchPageResult['token']
            ], $params);
            
            $response = $this->client->request('POST', '/Accessibility/VIOSearch/VIODataList', [
                'headers' => array_merge($this->defaultHeaders, [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Origin' => $this->baseUrl,
                    'Referer' => $this->baseUrl . '/Accessibility/VIOSearch/MASearchVIO'
                ]),
                'form_params' => $formData
            ]);
            
            $html = $response->getBody()->getContents();
            
            return [
                'success' => true,
                'html' => $html,
                'data' => $this->parseSearchResults($html)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function parseSearchResults(string $html): array
    {
        $crawler = new DomCrawler($html);
        $results = [];
        
        $table = $crawler->filter('table');
        if ($table->count() > 0) {
            $rows = $table->filter('tbody tr');
            $rows->each(function (DomCrawler $row) use (&$results) {
                $columns = $row->filter('td');
                if ($columns->count() > 0) {
                    $rowData = [];
                    $columns->each(function (DomCrawler $col) use (&$rowData) {
                        $rowData[] = trim($col->text());
                    });
                    $results[] = $rowData;
                }
            });
        }
        
        $pagination = [];
        $paginationLinks = $crawler->filter('.pagination a');
        $paginationLinks->each(function (DomCrawler $link) use (&$pagination) {
            $pagination[] = [
                'text' => trim($link->text()),
                'href' => $link->attr('href')
            ];
        });
        
        return [
            'rows' => $results,
            'pagination' => $pagination,
            'total_rows' => count($results)
        ];
    }
    
    public function searchWithCustomParams(string $cerRefId, string $token = null): array
    {
        try {
            if ($token === null) {
                $searchPageResult = $this->getSearchPage();
                if (!$searchPageResult['success']) {
                    return $searchPageResult;
                }
                $token = $searchPageResult['token'];
            }
            
            $formData = [
                'CER_REF_ID' => $cerRefId,
                '__RequestVerificationToken' => $token
            ];
            
            return $this->searchData($formData);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function setCookies(array $cookies): void
    {
        foreach ($cookies as $name => $value) {
            $this->cookieJar->setCookie(new \GuzzleHttp\Cookie\SetCookie([
                'Name' => $name,
                'Value' => $value,
                'Domain' => 'ma.mohw.gov.tw',
                'Path' => '/'
            ]));
        }
    }
    
    private function getCookiesAsString(): string
    {
        $cookies = [];
        foreach ($this->cookieJar as $cookie) {
            $cookies[] = $cookie->getName() . '=' . $cookie->getValue();
        }
        return implode('; ', $cookies);
    }
    
    public function saveHtmlToFile(string $html, string $filename): bool
    {
        try {
            file_put_contents($filename, $html);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function extractDataAsJson(array $searchResult): string
    {
        if (!$searchResult['success']) {
            return json_encode($searchResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        return json_encode($searchResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}