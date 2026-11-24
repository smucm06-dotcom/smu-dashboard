<?php

namespace App\Models\Initiatives\HigherEducation;

use App\Models\InitiativeFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One row of the call center's feedback files for the CM Punjab Honhaar Scholarship.
class HonhaarCall extends Model
{
    protected $table = 'hed_honhaar_scholarship';

    protected $guarded = ['id'];

    protected $casts = [
        'call_date' => 'date',
        'source_sr' => 'integer',
        'amount_fees' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(InitiativeFile::class, 'initiative_file_id');
    }

    public function copyOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'copy_of_id');
    }

    // Shown partly hidden on screen and in downloads; the full numbers are kept for matching calls to students.
    public function getCnicShownAttribute(): ?string
    {
        return self::hide($this->cnic, 5, 1);
    }

    public function getContactShownAttribute(): ?string
    {
        return self::hide($this->contact_no, 4, 2);
    }

    public function getAltContactShownAttribute(): ?string
    {
        return self::hide($this->alt_contact_no, 4, 2);
    }

    public function getFileNameAttribute(): ?string
    {
        return $this->relationLoaded('file') ? $this->file?->original_name : $this->file()->value('original_name');
    }

    // Whether the page counts the call, and why not.
    public function getCountedTextAttribute(): string
    {
        if ($this->copy_of_id !== null) {
            $copyOf = $this->relationLoaded('copyOf') ? $this->copyOf : null;

            return $copyOf !== null
                ? 'No: repeats Sr. No '.number_format($copyOf->source_sr).($copyOf->initiative_file_id === $this->initiative_file_id ? '' : ' of an earlier file')
                : 'No: repeats earlier rows';
        }

        return $this->student_id === null ? 'No: not in the department list' : 'Yes';
    }

    // Keeps the first and last digits, e.g. 35202-*******-1 for a CNIC or 0300****67 for a phone number.
    public static function hide(?string $value, int $first, int $last): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (strlen($digits) <= $first + $last) {
            return str_repeat('*', strlen($value));
        }

        $hidden = substr($digits, 0, $first).str_repeat('*', strlen($digits) - $first - $last).substr($digits, -$last);

        // A 13-digit CNIC reads as it is usually written.
        return strlen($digits) === 13 && $first === 5
            ? substr($hidden, 0, 5).'-'.substr($hidden, 5, 7).'-'.substr($hidden, 12)
            : $hidden;
    }
}
