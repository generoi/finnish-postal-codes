<?php

namespace FinnishPostalCodes;

class PostalCodes
{
    private static array $cache = [];

    private const DATA_FILES = [
        'fi' => 'postcodes-fi.php',
        'sv' => 'postcodes-sv.php',
        'en' => 'postcodes-en.php',
        'full' => 'postcodes-full.php',
    ];

    public function __construct(
        private readonly Language $language = Language::FI
    ) {}

    public static function fi(): self
    {
        return new self(Language::FI);
    }

    public static function sv(): self
    {
        return new self(Language::SV);
    }

    public static function en(): self
    {
        return new self(Language::EN);
    }

    public function getCity(string $postcode): ?string
    {
        return match ($this->language) {
            Language::FI => self::get('fi')[$postcode] ?? null,
            Language::SV => self::get('sv')[$postcode] ?? null,
            Language::EN => self::get('en')[$postcode] ?? null,
        };
    }

    public function getRecord(string $postcode): ?PostalCodeRecord
    {
        $data = self::get('full')[$postcode] ?? null;

        return $data ? PostalCodeRecord::fromArray($data) : null;
    }

    public function exists(string $postcode): bool
    {
        return isset(self::get('fi')[$postcode]);
    }

    public function getAllPostcodes(): array
    {
        return array_keys(self::get('fi'));
    }

    /**
     * @return \Generator<string, PostalCodeRecord>
     */
    public function getFull(): \Generator
    {
        $data = self::get('full');
        foreach ($data as $postcode => $record) {
            yield $postcode => PostalCodeRecord::fromArray($record);
        }
    }

    private static function get(string $type): array
    {
        if (! isset(self::$cache[$type])) {
            if (! isset(self::DATA_FILES[$type])) {
                throw new \InvalidArgumentException("Invalid data type: {$type}");
            }

            self::$cache[$type] = require __DIR__.'/../data/php/'.self::DATA_FILES[$type];
        }

        return self::$cache[$type];
    }
}
