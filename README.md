# MA VIO Data Crawler

A PHP crawler for collecting gender-related violation data from the Taiwan Ministry of Health and Welfare's MA VIO database.

## Features

- Crawls all 11 CER_REF_ID categories (西醫, 中醫, 牙醫, 藥局, 護理機構, etc.)
- Exports combined data to fixed CSV location
- Extracts judgment URLs from court documents
- Handles CSRF tokens and cookies automatically
- Progress tracking during crawl process
- UTF-8 BOM support for proper Excel compatibility

## Installation

```bash
composer install
```

## Usage

```bash
php crawl_all.php
```

This will:
1. Loop through all CER_REF_ID categories
2. Collect data from each category
3. Extract judgment URLs from court links
4. Combine all results into a single CSV file
5. Save to `data/cases.csv`

## Output

**File**: `data/cases.csv`

**Headers**: `類別,醫事人員,專科別,執業縣市,性別事件相關案件資訊`

**Sample**:
```csv
類別,醫事人員,專科別,執業縣市,性別事件相關案件資訊
西醫,林朝順,麻醉科,新北市,https://judgment.judicial.gov.tw/FJUD/data.aspx?ty=JD&id=TPDM,113%2c%e4%be%b5%e8%a8%b4%2c2%2c20241021%2c1
```

## CER_REF_ID Categories

| ID | Category |
|----|----------|
| A | 西醫 |
| B | 中醫 |
| C | 牙醫 |
| D | 藥局 |
| E | 護理機構 |
| F | 醫事檢驗 |
| G | 醫事放射 |
| H | 物理治療 |
| I | 職能治療 |
| J | 居家護理 |
| D,E | 藥局,護理機構 |