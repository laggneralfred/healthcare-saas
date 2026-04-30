<?php

namespace App\Services;

class CsvColumnMapper
{
    public const PATIENT_FIELDS = [
        'first_name'    => 'First Name',
        'last_name'     => 'Last Name',
        'email'         => 'Email',
        'phone'         => 'Phone',
        'dob'           => 'Date of Birth',
        'gender'        => 'Gender',
        'preferred_language' => 'Preferred Language',
        'address_line_1' => 'Address Line 1',
        'address_line_2' => 'Address Line 2',
        'city'          => 'City',
        'state'         => 'State',
        'postal_code'   => 'Postal Code',
        'country'       => 'Country',
        'emergency_contact_name' => 'Emergency Contact Name',
        'occupation'    => 'Occupation',
    ];

    private const SYNONYMS = [
        'firstname'           => 'first_name',
        'first'               => 'first_name',
        'fname'               => 'first_name',
        'given_name'          => 'first_name',
        'forename'            => 'first_name',
        'given'               => 'first_name',
        'lastname'            => 'last_name',
        'last'                => 'last_name',
        'lname'               => 'last_name',
        'surname'             => 'last_name',
        'family_name'         => 'last_name',
        'family'              => 'last_name',
        'e_mail'              => 'email',
        'email_address'       => 'email',
        'telephone'           => 'phone',
        'mobile'              => 'phone',
        'cell'                => 'phone',
        'phone_number'        => 'phone',
        'tel'                 => 'phone',
        'date_of_birth'       => 'dob',
        'birth_date'          => 'dob',
        'birthdate'           => 'dob',
        'birthday'            => 'dob',
        'born'                => 'dob',
        'birth'               => 'dob',
        'sex'                 => 'gender',
        'language'            => 'preferred_language',
        'preferred_language'  => 'preferred_language',
        'patient_language'    => 'preferred_language',
        'primary_language'    => 'preferred_language',
        'address'             => 'address_line_1',
        'street'              => 'address_line_1',
        'street_address'      => 'address_line_1',
        'addr'                => 'address_line_1',
        'apt'                 => 'address_line_2',
        'suite'               => 'address_line_2',
        'unit'                => 'address_line_2',
        'address2'            => 'address_line_2',
        'town'                => 'city',
        'suburb'              => 'city',
        'province'            => 'state',
        'region'              => 'state',
        'zip'                 => 'postal_code',
        'zip_code'            => 'postal_code',
        'zipcode'             => 'postal_code',
        'postcode'            => 'postal_code',
        'post_code'           => 'postal_code',
        'postal'              => 'postal_code',
        'emergency_contact'   => 'emergency_contact_name',
        'emergency'           => 'emergency_contact_name',
    ];

    /**
     * Suggest a patient field mapping for each header.
     *
     * Returns array indexed by header index:
     *   ['field' => 'first_name'|null, 'confidence' => 'high'|'low'|'none']
     */
    public function suggest(array $headers): array
    {
        $suggestions = [];
        foreach ($headers as $i => $header) {
            $suggestions[$i] = $this->suggestForHeader($header);
        }
        return $suggestions;
    }

    private function suggestForHeader(string $header): array
    {
        // Normalise: lowercase, collapse whitespace/hyphens to underscore
        $normalized = strtolower(trim(preg_replace('/[\s\-]+/', '_', $header)));

        // Level 1: exact match against known field keys
        if (array_key_exists($normalized, self::PATIENT_FIELDS)) {
            return ['field' => $normalized, 'confidence' => 'high'];
        }

        // Level 2: exact synonym match
        if (array_key_exists($normalized, self::SYNONYMS)) {
            return ['field' => self::SYNONYMS[$normalized], 'confidence' => 'high'];
        }

        // Level 3: levenshtein ≤ 2 against field keys + synonyms
        $best      = null;
        $bestDist  = 3; // threshold
        $allKeys   = array_merge(
            array_keys(self::PATIENT_FIELDS),
            array_keys(self::SYNONYMS)
        );

        foreach ($allKeys as $candidate) {
            $dist = levenshtein($normalized, $candidate);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best     = array_key_exists($candidate, self::PATIENT_FIELDS)
                    ? $candidate
                    : self::SYNONYMS[$candidate];
            }
        }

        if ($best !== null) {
            return ['field' => $best, 'confidence' => 'low'];
        }

        return ['field' => null, 'confidence' => 'none'];
    }

    /** Returns options array suitable for HTML <select>: ['' => '(Skip)', 'first_name' => 'First Name', ...] */
    public static function fieldOptions(): array
    {
        return ['' => '(Skip)'] + self::PATIENT_FIELDS;
    }
}
