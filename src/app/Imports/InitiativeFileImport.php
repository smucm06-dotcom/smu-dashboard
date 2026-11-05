<?php

namespace App\Imports;

use App\Models\InitiativeFile;

// Saves the rows of an uploaded file for one initiative. Listed in config/initiative_imports.php.
interface InitiativeFileImport
{
    // Reads and checks the file. Throws RuntimeException with a message for the user if it can't be used.
    // Returns at least "rows" and "sheet"; the controller passes the result to save().
    public function prepare(string $path, string $extension): array;

    // Runs inside a transaction, after the file itself has been kept.
    public function save(array $prepared, InitiativeFile $file): void;
}
