<?php

namespace App\Services;

class CsvColumnMapper
{
    public const PATIENT_FIELDS = [
        'first_name'  => 'First Name',
        'last_name'   => 'Last Name',
        'email'       => 'Email',
        'phone'       => 'Phone',
        'dob'         => 'Date of Birth',
        'gender'      => 'Gender',
        'address'     => 'Address',
        'city'        => 'City',
        'state'       => 'State',
        'postal_code' => 'Postal Code',
    ];

    private const SYNONYMS = [
        'firstname'      => 'first_name',
        'first'          => 'first_name',
        'fname'          => 'first_name',
        'given_name'     => 'first_name',
        'given'          => 'first_name',
        'lastname'       => 'last_name',
        'last'           => 'last_name',
        'lname'          => 'last_name',
        'surname'        => 'last_name',
        'family_name'    => 'last_name',
        'family'         => 'last_name',
        'e_mail'         => 'email',
        'email_address'  => 'email',
        'telephone'      => 'phone',
        'mobile'         => 'phone',
        'cell'           => 'phone',
        'phone_number'   => 'phone',
        'tel'            => 'phone',
        'date_of_birth'  => 'dob',
        'birth_date'     => 'dob',
        'birthdate'      => 'dob',
        'birthday'       => 'dob',
        'birth'          => 'dob',
        'sex'            => 'gender',
        'street'         => 'address',
        'street_address' => 'address',
        'addr'           => 'address',
        'province'       => 'state',
        'region'         => 'state',
        'zip'            => 'postal_code',
        'zip_code'       => 'postal_code',
        'zipcode'        => 'postal_code',
        'postcode'       => 'postal_code',
        'post_code'      => 'postal_code',
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
