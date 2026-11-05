<?php
namespace App\Console\Commands;

use App\Models\Department;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportDepartments extends Command
{
    protected $signature = 'departments:import';

    protected $description = 'Import departments from departments.csv';

    public function handle(): int
    {
        $path = base_path('departments.csv');

        if (! is_file($path)) {
            $this->error("CSV file not found: {$path}");
            return self::FAILURE;
        }

        $file = fopen($path, 'r');

        if ($file === false) {
            $this->error('Could not open CSV file.');
            return self::FAILURE;
        }

        try {
            $headers = fgetcsv($file);

            if ($headers === false) {
                throw new RuntimeException('CSV file is empty.');
            }

            $headers = array_map(
                fn ($header) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $header))),
                $headers
            );

            $departmentIndex = array_search('department', $headers, true);
            $codeIndex = array_search('code', $headers, true);

            if ($departmentIndex === false || $codeIndex === false) {
                throw new RuntimeException(
                    'CSV headers must include Department and Code.'
                );
            }

            $departments = [];
            $line = 1;

            while (($row = fgetcsv($file)) !== false) {
                $line++;

                if (count($row) !== count($headers)) {
                    throw new RuntimeException(
                        "Incorrect number of columns on line {$line}."
                    );
                }

                $name = trim($row[$departmentIndex]);
                $code = strtoupper(trim($row[$codeIndex]));

                if ($name === '' || $code === '') {
                    throw new RuntimeException(
                        "Department name or code is empty on line {$line}."
                    );
                }

                if (strlen($name) > 150 || strlen($code) > 20) {
                    throw new RuntimeException(
                        "Department name or code is too long on line {$line}."
                    );
                }

                if (isset($departments[$code])) {
                    throw new RuntimeException(
                        "Duplicate department code '{$code}' in CSV."
                    );
                }

                $departments[$code] = [
                    'name' => $name,
                    'code' => $code,
                ];
            }

            DB::transaction(function () use ($departments) {
                foreach ($departments as $department) {
                    Department::updateOrCreate(
                        ['code' => $department['code']],
                        ['name' => $department['name']]
                    );
                }
            });

            $this->info(count($departments) . ' department rows processed successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            fclose($file);
        }
    }
}
