<?php

namespace App\Imports\CallCenter;

use RuntimeException;

// Stops a call center upload: nothing it read is saved. Carries what the result box shows, in the same shape
// as the other uploads' failures (psrp_shared/import-result).
class ImportFailed extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly array $problems = [],
        public readonly int $problemCount = 0,
        public readonly array $headingsFound = [],
    ) {
        parent::__construct($message);
    }

    public function result(): array
    {
        return [
            'failure' => $this->getMessage(),
            'problems' => $this->problems,
            'problemCount' => $this->problemCount,
            'ignoredColumns' => $this->headingsFound,
        ];
    }
}
