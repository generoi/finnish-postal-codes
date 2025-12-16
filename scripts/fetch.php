<?php

/**
 * Fetch Finnish postal codes from posti.fi
 * Based on https://github.com/theikkila/postinumerot/blob/master/fetch.py
 *
 * @see http://www.posti.fi/liitteet-yrityksille/ehdot/postinumeropalvelut-palvelukuvaus-ja-kayttoehdot.pdf
 */
const INDEX_URL = 'https://www.posti.fi/webpcode/unzip/';
const DATA_DIR = __DIR__.'/../data';
const JSON_DIR = DATA_DIR.'/json';
const PHP_DIR = DATA_DIR.'/php';

class PostalCodeRecord
{
    public function __construct(
        public string $date,
        public string $postcode,
        public string $postcode_fi_name,
        public string $postcode_sv_name,
        public string $postcode_abbr_fi,
        public string $postcode_abbr_sv,
        public string $valid_from,
        public string $type_code,
        public string $ad_area_code,
        public string $ad_area_fi,
        public string $ad_area_sv,
        public string $municipal_code,
        public string $municipal_name_fi,
        public string $municipal_name_sv,
        public string $municipal_language_ratio_code,
    ) {}
}

/**
 * Get HTTP context for file_get_contents requests
 */
function getHttpContext()
{
    return stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => ['User-Agent: Mozilla/5.0 (compatible; PHP)'],
        ],
    ]);
}

/**
 * Fetch the directory index and find the latest PCF file URL
 */
function findLatestPcfFile(): string
{
    echo "Fetching directory index...\n";

    $index = @file_get_contents(INDEX_URL, false, getHttpContext());
    if ($index === false) {
        echo 'Failed to fetch directory index from '.INDEX_URL."\n";
        exit(1);
    }

    $pattern = '#'.preg_quote(INDEX_URL, '#').'PCF_[0-9]+\.dat#';
    if (! preg_match($pattern, $index, $matches)) {
        echo "Could not find PCF_*.dat file in directory index\n";
        exit(1);
    }

    return $matches[0];
}

/**
 * Download and decode the postal code data file
 */
function downloadPostalCodeData(string $url): array
{
    echo "Downloading postal code data...\n";

    $content = @file_get_contents($url, false, getHttpContext());
    if ($content === false) {
        echo "Failed to download postal code data from {$url}\n";
        exit(1);
    }

    // Split lines first (before UTF-8 conversion) to preserve fixed-width positions
    $lines = explode("\n", $content);

    echo 'Fetched '.count($lines)." lines!\n";

    return $lines;
}

/**
 * Parse a single postal code record from fixed-width format
 * Format: PONOT(8 date)(5 postcode)(30 fi_name)(30 sv_name)(12 abbr_fi)(12 abbr_sv)...
 */
function parseRecord(string $line): ?PostalCodeRecord
{
    // Parse as latin-1 first to preserve fixed-width positions, then convert individual fields
    // Match fixed-width format using regex (matching Python script approach)
    $pattern = '/^PONOT(?P<date>.{8})(?P<postcode>.{5})(?P<postcode_fi_name>.{30})(?P<postcode_sv_name>.{30})(?P<postcode_abbr_fi>.{12})(?P<postcode_abbr_sv>.{12})(?P<valid_from>.{8})(?P<type_code>.{1})(?P<ad_area_code>.{5})(?P<ad_area_fi>.{30})(?P<ad_area_sv>.{30})(?P<municipal_code>.{3})(?P<municipal_name_fi>.{20})(?P<municipal_name_sv>.{20})(?P<municipal_language_ratio_code>.{1})/';

    if (! preg_match($pattern, $line, $matches)) {
        return null;
    }

    // Extract and trim all fields, converting from latin-1 to UTF-8
    $fields = [];
    foreach ($matches as $key => $value) {
        if (is_string($key)) {
            $fields[$key] = trim(mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1'));
        }
    }

    if (empty($fields['postcode'])) {
        return null;
    }

    return new PostalCodeRecord(
        date: $fields['date'] ?? '',
        postcode: $fields['postcode'],
        postcode_fi_name: $fields['postcode_fi_name'] ?? '',
        postcode_sv_name: $fields['postcode_sv_name'] ?? '',
        postcode_abbr_fi: $fields['postcode_abbr_fi'] ?? '',
        postcode_abbr_sv: $fields['postcode_abbr_sv'] ?? '',
        valid_from: $fields['valid_from'] ?? '',
        type_code: $fields['type_code'] ?? '',
        ad_area_code: $fields['ad_area_code'] ?? '',
        ad_area_fi: $fields['ad_area_fi'] ?? '',
        ad_area_sv: $fields['ad_area_sv'] ?? '',
        municipal_code: $fields['municipal_code'] ?? '',
        municipal_name_fi: $fields['municipal_name_fi'] ?? '',
        municipal_name_sv: $fields['municipal_name_sv'] ?? '',
        municipal_language_ratio_code: $fields['municipal_language_ratio_code'] ?? '',
    );
}

/**
 * Parse records using generator for memory efficiency
 */
function parseRecords(array $lines): \Generator
{
    foreach ($lines as $line) {
        $record = parseRecord($line);
        if ($record !== null) {
            yield $record;
        }
    }
}

/**
 * Export array with all keys as quoted strings
 */
function exportWithStringKeys(array $data, int $indent = 0): string
{
    $indentStr = str_repeat('  ', $indent);
    $nextIndentStr = str_repeat('  ', $indent + 1);

    $lines = ['array ('];

    foreach ($data as $key => $value) {
        $keyStr = var_export((string) $key, true);

        if (is_array($value)) {
            $valueStr = exportWithStringKeys($value, $indent + 1);
            $lines[] = "{$nextIndentStr}{$keyStr} => {$valueStr},";
        } else {
            $valueStr = var_export($value, true);
            $lines[] = "{$nextIndentStr}{$keyStr} => {$valueStr},";
        }
    }

    $lines[] = "{$indentStr})";

    return implode("\n", $lines);
}

/**
 * Process records and save to files
 */
function processAndSave(\Generator $records, string $filename): void
{
    echo "Parsing records...\n";

    if (! is_dir(JSON_DIR)) {
        mkdir(JSON_DIR, 0755, true);
    }

    if (! is_dir(PHP_DIR)) {
        mkdir(PHP_DIR, 0755, true);
    }

    $postcodesFi = [];
    $postcodesSv = [];
    $postcodesEn = [];
    $fullRecords = [];

    foreach ($records as $record) {
        $fullRecords[$record->postcode] = $record;

        if (! empty($record->postcode_fi_name)) {
            $postcodesFi[$record->postcode] = $record->postcode_fi_name;
        }

        if (! empty($record->postcode_sv_name)) {
            $postcodesSv[$record->postcode] = $record->postcode_sv_name;
        }

        if (! empty($record->postcode_fi_name)) {
            $postcodesEn[$record->postcode] = $record->postcode_fi_name;
        }
    }

    echo 'Parsed '.count($fullRecords)." records!\n";
    echo "Saving...\n";

    // Save language-specific files and free memory immediately
    $languageData = [
        'fi' => $postcodesFi,
        'sv' => $postcodesSv,
        'en' => $postcodesEn,
    ];

    foreach ($languageData as $key => $data) {
        file_put_contents(
            JSON_DIR."/postcodes-{$key}.json",
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        file_put_contents(
            PHP_DIR."/postcodes-{$key}.php",
            "<?php\n\nreturn ".exportWithStringKeys($data).";\n"
        );
    }

    unset($languageData, $postcodesFi, $postcodesSv, $postcodesEn);

    // Save full records - convert objects to arrays for JSON encoding, preserving postcode keys
    $fullRecordsArray = [];
    foreach ($fullRecords as $postcode => $record) {
        $fullRecordsArray[$postcode] = (array) $record;
    }

    $jsonContent = json_encode($fullRecordsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
    if ($jsonContent === false) {
        echo 'JSON encoding failed: '.json_last_error_msg()."\n";
        exit(1);
    }

    file_put_contents(
        JSON_DIR.'/postcodes-full.json',
        $jsonContent
    );

    file_put_contents(
        PHP_DIR.'/postcodes-full.php',
        "<?php\n\nreturn ".exportWithStringKeys($fullRecordsArray).";\n"
    );
    unset($fullRecordsArray, $fullRecords);

    file_put_contents(DATA_DIR.'/LAST_UPDATED', date('c')."\n");
    file_put_contents(DATA_DIR.'/SOURCE_FILENAME', $filename."\n");

    echo "DONE!\n";
    echo "Files saved:\n";
    echo '  JSON: '.JSON_DIR."/\n";
    echo '  PHP: '.PHP_DIR."/\n";
}

$pcfUrl = findLatestPcfFile();
echo "Found file: {$pcfUrl}\n";

preg_match('/PCF_[0-9]+\.dat/', $pcfUrl, $matches);
$filename = $matches[0];

// Check if we already have this file
$sourceFile = DATA_DIR.'/SOURCE_FILENAME';
if (file_exists($sourceFile)) {
    $currentFilename = trim(file_get_contents($sourceFile));
    if ($currentFilename === $filename) {
        echo "No update needed - already have {$filename}\n";
        exit(2); // Exit code 2 = no change
    }
}

$lines = downloadPostalCodeData($pcfUrl);
$records = parseRecords($lines);
processAndSave($records, $filename);

exit(0); // Exit code 0 = success/updated
