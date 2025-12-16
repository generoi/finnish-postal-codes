# Finnish Postal Codes

A Composer package containing all Finnish postal codes and city names in Finnish, Swedish, and English. Data is sourced from [posti.fi](https://www.posti.fi).

## Installation

```bash
composer require generoi/finnish-postal-codes
```

## Usage

### Basic Usage

```php
use FinnishPostalCodes\PostalCodes;
use FinnishPostalCodes\Language;

// Create instance with language
$sv = PostalCodes::sv();
$city = $sv->getCity('00900'); // Returns "HELSINGFORS"

$fi = PostalCodes::fi();
$city = $fi->getCity('00900'); // Returns "HELSINKI"

// Get full record
$record = $fi->getRecord('00900');
echo $record->postcode_fi_name; // "HELSINKI"
echo $record->municipal_name_fi; // "Helsinki"
echo $record->ad_area_fi; // "Helsinki-Uusimaa"

// Check if postal code exists
if ($fi->exists('00900')) {
    // ...
}

// Get all postal codes
$allPostcodes = $fi->getAllPostcodes();

// Iterate over all records (memory efficient)
foreach ($fi->getFull() as $postcode => $record) {
    // Process each record
}
```

### Direct File Access

If you need raw arrays, you can require the PHP files directly:

```php
$fiData = require 'vendor/generoi/finnish-postal-codes/data/php/postcodes-fi.php';
```

## Data Update

To update the postal code data, run:

```bash
composer run fetch
```

Or directly:

```bash
php scripts/fetch.php
```

Exit codes:
- `0` - Success/Updated
- `1` - Error
- `2` - No change (already up to date)

## Data Files

The package includes data in multiple formats:

- **JSON**: `data/json/postcodes-{fi|sv|en|full}.json`
- **PHP**: `data/php/postcodes-{fi|sv|en|full}.php` (can be required directly)

## License

MIT

