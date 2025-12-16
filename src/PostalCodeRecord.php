<?php

namespace FinnishPostalCodes;

class PostalCodeRecord
{
    public function __construct(
        public readonly string $date,
        public readonly string $postcode,
        public readonly string $postcode_fi_name,
        public readonly string $postcode_sv_name,
        public readonly string $postcode_abbr_fi,
        public readonly string $postcode_abbr_sv,
        public readonly string $valid_from,
        public readonly string $type_code,
        public readonly string $ad_area_code,
        public readonly string $ad_area_fi,
        public readonly string $ad_area_sv,
        public readonly string $municipal_code,
        public readonly string $municipal_name_fi,
        public readonly string $municipal_name_sv,
        public readonly string $municipal_language_ratio_code,
    ) {}

    /**
     * Create from array (used when loading from data files)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: $data['date'] ?? '',
            postcode: $data['postcode'] ?? '',
            postcode_fi_name: $data['postcode_fi_name'] ?? '',
            postcode_sv_name: $data['postcode_sv_name'] ?? '',
            postcode_abbr_fi: $data['postcode_abbr_fi'] ?? '',
            postcode_abbr_sv: $data['postcode_abbr_sv'] ?? '',
            valid_from: $data['valid_from'] ?? '',
            type_code: $data['type_code'] ?? '',
            ad_area_code: $data['ad_area_code'] ?? '',
            ad_area_fi: $data['ad_area_fi'] ?? '',
            ad_area_sv: $data['ad_area_sv'] ?? '',
            municipal_code: $data['municipal_code'] ?? '',
            municipal_name_fi: $data['municipal_name_fi'] ?? '',
            municipal_name_sv: $data['municipal_name_sv'] ?? '',
            municipal_language_ratio_code: $data['municipal_language_ratio_code'] ?? '',
        );
    }
}
