<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CSVImportService
{
    /**
     * Parse a date value in various formats.
     *
     * Supports: MM/DD/YYYY, DD/MM/YYYY, YYYY-MM-DD, M/D/YYYY, D/M/YYYY, etc.
     *
     * @param mixed $value The date value to parse
     * @return Carbon|null
     */
    public static function parseDate($value): ?Carbon
    {
        if (!$value || is_null($value)) {
            return null;
        }

        $value = trim($value);

        if (empty($value)) {
            return null;
        }

        // Try YYYY-MM-DD format first (most unambiguous)
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $value);
            } catch (\Exception) {
                // Fall through to other formats
            }
        }

        // Try MM/DD/YYYY or M/D/YYYY
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
            // Assume MM/DD/YYYY format (US standard)
            try {
                return Carbon::createFromFormat('m/d/Y', $value);
            } catch (\Exception) {
                // Fall through to other formats
            }
        }

        // Try DD.MM.YYYY or D.M.YYYY
        if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $value)) {
            try {
                return Carbon::createFromFormat('d.m.Y', $value);
            } catch (\Exception) {
                // Fall through
            }
        }

        // Try parsing with multiple possible formats
        $formats = [
            'm/d/Y',        // MM/DD/YYYY
            'd/m/Y',        // DD/MM/YYYY
            'Y-m-d',        // YYYY-MM-DD
            'Y/m/d',        // YYYY/MM/DD
            'd.m.Y',        // DD.MM.YYYY
            'Y.m.d',        // YYYY.MM.DD
            'm-d-Y',        // MM-DD-YYYY
            'd-m-Y',        // DD-MM-YYYY
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Exception) {
                continue;
            }
        }

        // If all else fails, try the generic parsing
        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Format a phone number by stripping non-digits and returning in standard format.
     *
     * @param mixed $value The phone number to format
     * @return string|null
     */
    public static function formatPhone($value): ?string
    {
        if (!$value || is_null($value)) {
            return null;
        }

        $value = trim($value);

        if (empty($value)) {
            return null;
        }

        // Strip all non-digit characters
        $digits = preg_replace('/\D/', '', $value);

        // Keep the phone number if it has digits
        if (!empty($digits)) {
            return $digits;
        }

        return null;
    }

    /**
     * Generate a CSV template with standard headers.
     *
     * @return string CSV template as a string
     */
    public static function generateTemplate(): string
    {
        $headers = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'dob',
            'gender',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'country',
        ];

        return implode(',', $headers) . "\n";
    }

    /**
     * Parse an uploaded CSV file and map columns according to columnMap.
     *
     * @param UploadedFile $file The uploaded CSV file
     * @param array $columnMap Mapping of CSV column index to patient field name
     *                          e.g., [0 => 'first_name', 1 => 'last_name', 2 => 'email']
     * @return array Array of parsed rows with mapped field names
     * @throws \Exception
     */
    public static function parseUpload(UploadedFile $file, array $columnMap): array
    {
        $rows = [];

        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new \Exception('Could not open CSV file');
        }

        $rowIndex = 0;

        try {
            while (($data = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty(array_filter($data))) {
                    continue;
                }

                $row = [];

                // Map columns according to columnMap
                foreach ($columnMap as $csvIndex => $fieldName) {
                    if ($fieldName && isset($data[$csvIndex])) {
                        $row[$fieldName] = trim($data[$csvIndex]);
                    }
                }

                if (!empty($row)) {
                    $rows[] = $row;
                }

                $rowIndex++;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Validate email format.
     *
     * @param string $email
     * @return bool
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
