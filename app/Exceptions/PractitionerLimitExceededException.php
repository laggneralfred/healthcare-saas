<?php

namespace App\Exceptions;

use App\Models\Practice;
use RuntimeException;

class PractitionerLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly Practice $practice,
        public readonly int      $limit,
        public readonly int      $current,
    ) {
        parent::__construct(
            "Practice \"{$practice->name}\" has reached its practitioner limit " .
            "of {$limit} (currently {$current}). " .
            "Upgrade your subscription plan to add more practitioners."
        );
    }
}
