<?php

namespace App\Imports\Initiatives\HigherEducation;

use App\Imports\CallCenter\RowImport;
use App\Imports\CallCenter\UploadProgress;
use App\Models\InitiativeFile;
use Illuminate\Support\Facades\DB;

// CM Punjab Honhaar Scholarship: the department's list of scholarship students (Phase-II 20K + Active
// Students 28287, compiled). Every column is kept as typed. The new list replaces the earlier one in the same
// transaction, so a failed upload keeps the earlier list.
class HonhaarStudentListImport extends RowImport
{
    protected function table(): string
    {
        return 'hed_honhaar_scholarship_students';
    }

    protected function keyColumn(): string
    {
        return 'source_sr';
    }

    protected function columns(): array
    {
        return [
            'source_sr' => ['headings' => ['Sr. No', 'Sr No', 'Sr #', 'S. No'], 'type' => 'int', 'required' => true],
            'source_file' => ['headings' => ['Source File'], 'type' => 'text', 'required' => true],
            'source_file_sn' => ['headings' => ['SN in Source File'], 'type' => 'int'],
            'student_district' => ['headings' => ['Student District'], 'type' => 'text', 'max' => 100, 'required' => true],
            'institute_district' => ['headings' => ['Institute District'], 'type' => 'text', 'max' => 100],
            'institute' => ['headings' => ['Institute'], 'type' => 'text', 'required' => true],
            'name' => ['headings' => ['Name'], 'type' => 'text'],
            'contact' => ['headings' => ['Contact #', 'Contact No'], 'type' => 'text', 'max' => 50, 'required' => true],
            'cnic' => ['headings' => ['CNIC'], 'type' => 'text', 'max' => 50, 'required' => true],
            'application_id' => ['headings' => ['Application ID'], 'type' => 'text', 'max' => 50],
            'program' => ['headings' => ['Program / Discipline'], 'type' => 'text'],
            'gender' => ['headings' => ['Gender'], 'type' => 'text', 'max' => 50],
            'father_name' => ['headings' => ['Father Name'], 'type' => 'text'],
            'tuition_fee' => ['headings' => ['Tuition Fee excl. Boarding, Lodging & Security (PKR)'], 'type' => 'text', 'max' => 100],
            'total_amount' => ['headings' => ['Total Amount (PKR)'], 'type' => 'text', 'max' => 100],
            'status' => ['headings' => ['Status'], 'type' => 'text', 'max' => 100],
            'institute_type' => ['headings' => ['Institute Type'], 'type' => 'text', 'max' => 100],
            'same_number_rows' => ['headings' => ['Same Number in Rows'], 'type' => 'int'],
            'student_district_from' => ['headings' => ['Student District From'], 'type' => 'text'],
            'institute_district_from' => ['headings' => ['Institute District From'], 'type' => 'text'],
            'district_as_in_file' => ['headings' => ['District (as in file)'], 'type' => 'text'],
            'contact_as_in_file' => ['headings' => ['Contact (as in file)'], 'type' => 'text', 'max' => 100],
            'data_notes' => ['headings' => ['Data Notes'], 'type' => 'text', 'max' => 65000],
        ];
    }

    // A total row at the bottom of the list is not a student.
    protected function isData(array $row): bool
    {
        return ! (is_string($row['source_file']) && preg_match('/^(grand\s+)?total$/i', $row['source_file']) === 1);
    }

    // Returns the earlier lists it replaced, whose stored files are removed once the upload is saved.
    public function import(string $path, string $extension, InitiativeFile $file, UploadProgress $progress): array
    {
        $result = parent::import($path, $extension, $file, $progress);

        $older = InitiativeFile::query()
            ->where('initiative_id', $file->initiative_id)
            ->where('type', InitiativeFile::TYPE_DEPARTMENT_LIST)
            ->where('id', '<>', $file->id)
            ->get(['id', 'original_name', 'stored_path']);

        // Their students go with them (cascade), and the calls are linked to the new list next.
        DB::table('initiative_files')->whereIn('id', $older->pluck('id'))->delete();

        return $result + [
            'replaced' => $older->pluck('original_name')->all(),
            'removePaths' => $older->pluck('stored_path')->all(),
        ];
    }
}
