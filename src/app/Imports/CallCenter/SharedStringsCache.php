<?php

namespace App\Imports\CallCenter;

use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactoryInterface;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyInterface;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\FileBasedStrategy;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\InMemoryStrategy;

// Where the Excel reader keeps the file's texts (names, phone numbers ...) while it reads. OpenSpout's own rule
// allows 12 KB per text, so a call file with 150,000 different texts goes to temporary files on disk and
// reading 50,000 rows takes minutes. A text really takes well under 1 KB, so this keeps them in memory while
// there is room for them, and uses the temporary files only beyond that.
final class SharedStringsCache implements CachingStrategyFactoryInterface
{
    private const KB_PER_STRING = 1;

    // Of the memory left, the share the texts may use.
    private const SHARE = 0.5;

    private const STRINGS_PER_TEMP_FILE = 10000;

    public function createBestCachingStrategy(?int $sharedStringsUniqueCount, string $tempFolder): CachingStrategyInterface
    {
        if ($sharedStringsUniqueCount !== null && $sharedStringsUniqueCount * self::KB_PER_STRING < $this->freeKb() * self::SHARE) {
            return new InMemoryStrategy($sharedStringsUniqueCount);
        }

        return new FileBasedStrategy($tempFolder, self::STRINGS_PER_TEMP_FILE);
    }

    private function freeKb(): float
    {
        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '-1') {
            return 1024 * 1024;
        }

        $bytes = (float) $limit;
        $unit = strtolower(substr($limit, -1));
        $bytes *= match ($unit) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        return max(0, $bytes - memory_get_usage(true)) / 1024;
    }
}
