<?php

namespace App\Http\Controllers\Initiatives\HigherEducation;

use App\Dashboards\Initiatives\HigherEducation\HonhaarDashboard;
use App\Http\Controllers\CallCenter\CallCenterUploadController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Initiatives\SchoolEducation\Concerns\ManagesPsrpVisits;
use App\Models\CallCenterUpload;
use App\Models\Initiative;
use App\Models\Initiatives\HigherEducation\HonhaarCall;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;

// CM Punjab Honhaar Scholarship, a call center campaign: the "Honhaar Scholarship Call Center Feedback" design,
// worked out on the server from the uploads (HonhaarDashboard), with the four uploads and every call below it.
class HonhaarScholarshipController extends Controller
{
    // Filters, CSV download and the figures' download work as on the other initiative pages.
    use ManagesPsrpVisits;

    private const TABLE = 'hed_honhaar_scholarship';

    // Every column of our calls file, in its order, under its own headings, then whether the page counts the
    // call. CNIC and phone numbers are shown partly hidden. "sort" names the column a shown value sorts by.
    private const RECORD_COLUMNS = [
        'source_sr' => ['label' => 'Sr. No', 'type' => 'number'],
        'call_date' => ['label' => 'Call Date', 'type' => 'date', 'csv_format' => 'Y-m-d'],
        'applicant_name' => ['label' => 'Name of Applicant', 'type' => 'text'],
        'cnic_shown' => ['label' => 'CNIC', 'type' => 'text', 'sort' => 'cnic'],
        'contact_shown' => ['label' => 'Contact No', 'type' => 'text', 'sort' => 'contact_no'],
        'alt_contact_shown' => ['label' => 'Alt Contact No', 'type' => 'text', 'sort' => 'alt_contact_no'],
        'contact_valid' => ['label' => 'Contact Valid?', 'type' => 'text'],
        'degree_program' => ['label' => 'Degree Program', 'type' => 'text'],
        'amount_fees' => ['label' => 'Amount (Fees)', 'type' => 'number'],
        'institute_name' => ['label' => 'Institute Name', 'type' => 'text'],
        'division' => ['label' => 'Division', 'type' => 'text'],
        'district' => ['label' => 'District', 'type' => 'text'],
        'call_status' => ['label' => 'Call Status', 'type' => 'text'],
        'first_cheque_received' => ['label' => 'First Cheque/Installment Received?', 'type' => 'text'],
        'receiving_scholarship' => ['label' => 'Receiving Scholarship (under scheme)?', 'type' => 'text'],
        'bribe_paid' => ['label' => 'Bribe Paid to Dept/Institution?', 'type' => 'text'],
        'year_of_degree' => ['label' => 'Year of Degree', 'type' => 'text'],
        'satisfied' => ['label' => 'Satisfied with Programme?', 'type' => 'text'],
        'remarks' => ['label' => 'Remarks', 'type' => 'text'],
        'any_other_issue' => ['label' => 'Any Other Issue', 'type' => 'text'],
        'counted_text' => ['label' => 'Counted on this page', 'type' => 'text', 'sort' => 'copy_of_id'],
    ];

    // Everything kept for a call, shown when its row is opened.
    private const RECORD_DETAILS = [
        'source_sr' => 'Sr. No',
        'call_date' => 'Call Date',
        'applicant_name' => 'Name of Applicant',
        'cnic_shown' => 'CNIC',
        'contact_shown' => 'Contact No',
        'alt_contact_shown' => 'Alt Contact No',
        'contact_valid' => 'Contact Valid?',
        'degree_program' => 'Degree Program',
        'amount_fees' => 'Amount (Fees)',
        'institute_name' => 'Institute Name',
        'division' => 'Division',
        'district' => 'District',
        'call_status' => 'Call Status',
        'first_cheque_received' => 'First Cheque/Installment Received?',
        'receiving_scholarship' => 'Receiving Scholarship (under scheme)?',
        'bribe_paid' => 'Bribe Paid to Dept/Institution?',
        'year_of_degree' => 'Year of Degree',
        'satisfied' => 'Satisfied with Programme?',
        'remarks' => 'Remarks',
        'any_other_issue' => 'Any Other Issue',
        'counted_text' => 'Counted on this page',
        'home_district' => 'District on this page',
        'file_name' => 'Uploaded in file',
    ];

    // The questions answered Yes or No.
    private const YES_NO_COLUMNS = ['first_cheque_received', 'receiving_scholarship', 'bribe_paid', 'satisfied'];

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    // Sent by the page when only the records table needs refreshing.
    private const RECORDS_HEADER = 'X-Psrp-Records';

    public function __construct(private HonhaarDashboard $dashboard)
    {
    }

    public function show(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $filters = $this->filters($request);
        $table = $this->tableOptions($request);

        $records = [
            'initiative' => $initiative,
            'filters' => $filters,
            'table' => $table,
            'totals' => ['visits' => HonhaarCall::count()],
            'dataChecks' => $this->dataCheckCounts($filters),
            'records' => $this->callRecords($filters, $table)
                ->paginate($table['per_page'])
                ->withQueryString()
                ->fragment('psrp-records'),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'exportUrl' => route('initiatives.honhaar-scholarship.export', ['initiative' => $initiative] + $request->except(['page', 'per_page'])),
            'searchHint' => 'Name, CNIC, phone, institute or district',
            'detailFields' => self::RECORD_DETAILS,
        ];

        if ($request->hasHeader(self::RECORDS_HEADER)) {
            return response()
                ->view('call_center.shared.records', $records)
                ->header('Vary', self::RECORDS_HEADER);
        }

        return response()
            ->view('initiatives.higher_education.honhaar_scholarship.show', $records + [
                'topbar' => $this->topbar($initiative),
                'files' => $initiative->filesByType(),
                'uploads' => $this->uploads($request, $initiative),
            ])
            ->header('Vary', self::RECORDS_HEADER);
    }

    // The design's figures for all of Punjab and each district, for the chosen dates.
    public function data(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $filters = $this->filters($request);
        $dashboard = $this->dashboard;

        return $this->dashboardData($request, new class($dashboard, $filters['from'], $filters['to'])
        {
            public function __construct(private HonhaarDashboard $dashboard, private string $from, private string $to)
            {
            }

            public function data(): array
            {
                return $this->dashboard->data($this->from, $this->to);
            }

            public function version(): string
            {
                return $this->dashboard->version().'-'.$this->from.'-'.$this->to;
            }
        });
    }

    public function export(Request $request, Initiative $initiative)
    {
        $this->ensureThisInitiative($initiative);

        $table = $this->tableOptions($request);

        return $this->downloadCsv(
            $this->callRecords($this->filters($request), $table)->with('copyOf:id,source_sr,initiative_file_id')->cursor(),
            $table['columns'],
            'honhaar-scholarship-calls-'.now()->format('Y-m-d').'.csv'
        );
    }

    // The design's four cards, numbered 1 to 4 by the page's styles, with the design's lines under them. They
    // show all of Punjab here, and the page's script keeps them in step with its filters.
    private function topbar(Initiative $initiative): array
    {
        $data = $this->dashboard->data();
        $cards = $this->dashboard->headline($data);
        $all = $data['agg']['ALL'];

        $labels = [
            'dep' => "Students in the department's list",
            'stu' => 'Students called',
            'fb' => 'Students who gave feedback',
            'sat' => 'Satisfaction rate',
        ];

        $extra = [
            'stu' => new HtmlString('<b>'.number_format($all['calls']).'</b> calls made in all &middot; <b>'.number_format($all['students'] - $all['ncalls'][0]).'</b> students were called more than once'),
            'fb' => new HtmlString('<b>'.number_format($all['chg'][0]).'</b> students gave feedback twice or more; their latest call is counted'),
        ];

        $stats = [];
        foreach ($labels as $key => $label) {
            [$value, $info] = $cards[$key];

            $stats[] = array_filter([
                'label' => $label,
                'value' => $value,
                'display' => $key === 'sat' ? ($value === null ? '—' : number_format($value, 1).'%') : null,
                'info' => $info,
                'extra' => $extra[$key] ?? null,
                'id' => 'c-'.$key,
            ], fn ($item) => $item !== null);
        }

        return [
            'title' => $initiative->name,
            // The design's opening paragraph, in short.
            'description' => 'Launched in 2024, the Honhaar Scholarship pays the full tuition fee of merit-selected, Punjab-domiciled undergraduates. '
                .'The call center asked scholarship students whether the money reached them, whether anyone took a bribe, and whether they are satisfied.',
            'stats' => $stats,
        ];
    }

    // The background uploads still being read, and the latest result not yet shown to whoever uploaded it.
    private function uploads(Request $request, Initiative $initiative): array
    {
        $running = CallCenterUpload::where('initiative_id', $initiative->id)
            ->unfinished()
            ->latest('id')
            ->first();

        $result = CallCenterUpload::where('initiative_id', $initiative->id)
            ->where('uploaded_by', $request->user()->id)
            ->whereIn('status', [CallCenterUpload::STATUS_DONE, CallCenterUpload::STATUS_FAILED])
            ->whereNull('result_seen_at')
            ->latest('id')
            ->first();

        $result?->update(['result_seen_at' => now()]);

        return [
            'running' => $running === null || $running->hasStalled() ? null : CallCenterUploadController::status($running),
            'result' => $result,
        ];
    }

    private function ensureThisInitiative(Initiative $initiative): void
    {
        abort_unless($initiative->is_active && $initiative->table_name === self::TABLE, 404);
    }

    private function tableOptions(Request $request): array
    {
        $sort = $this->text($request, 'sort');
        $check = $this->text($request, 'check');
        $perPage = (int) $this->text($request, 'per_page');

        return [
            'tab' => 'visits',
            'columns' => self::RECORD_COLUMNS,
            'sort' => array_key_exists($sort, self::RECORD_COLUMNS) ? $sort : 'source_sr',
            'dir' => $this->text($request, 'dir') === 'desc' ? 'desc' : 'asc',
            'search' => mb_substr($this->text($request, 'search'), 0, 100),
            'check' => array_key_exists($check, $this->dataChecks()) ? $check : '',
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 25,
        ];
    }

    // Calls worth a second look, as SQL with its bindings.
    private function dataChecks(): array
    {
        $yesNo = implode(' OR ', array_map(fn ($column) => "({$column} IS NOT NULL AND {$column} NOT IN (?, ?))", self::YES_NO_COLUMNS));
        $remarks = array_keys(HonhaarDashboard::REMARKS);
        $statuses = ['c', 'nc', 'powered off', 'w', 'concerned person not available', 'left university', 'call hang up', 'no response from citizen', 'connected', 'not connected', 'not answered', 'no answer', 'switched off', 'phone switched off', 'wrong number', 'not available', 'hang up', 'no response'];

        return [
            'copies' => [
                'label' => 'Not counted: repeats earlier rows',
                'rule' => 'copy_of_id IS NOT NULL',
                'bindings' => [],
            ],
            'not_listed' => [
                'label' => 'Not counted: not in the department list',
                'rule' => 'copy_of_id IS NULL AND student_id IS NULL',
                'bindings' => [],
            ],
            'other_answer' => [
                'label' => 'Answer not Yes or No',
                'rule' => $yesNo,
                'bindings' => array_merge(...array_fill(0, count(self::YES_NO_COLUMNS), ['Yes', 'No'])),
            ],
            'other_remark' => [
                'label' => 'Remarks not one of the clean labels',
                'rule' => 'remarks IS NOT NULL AND remarks NOT IN ('.implode(',', array_fill(0, count($remarks), '?')).')',
                'bindings' => $remarks,
            ],
            'other_issue' => [
                'label' => 'Remarks "Other issue": problem only in Any Other Issue',
                'rule' => 'remarks = ?',
                'bindings' => ['Other issue (see Any Other Issue)'],
            ],
            'other_status' => [
                'label' => 'Call Status not recognised',
                'rule' => 'call_status IS NOT NULL AND LOWER(call_status) NOT IN ('.implode(',', array_fill(0, count($statuses), '?')).')',
                'bindings' => $statuses,
            ],
            'no_status' => [
                'label' => 'No Call Status',
                'rule' => 'call_status IS NULL',
                'bindings' => [],
            ],
            'no_date' => [
                'label' => 'No Call Date',
                'rule' => 'call_date IS NULL',
                'bindings' => [],
            ],
        ];
    }

    private function dataCheckCounts(array $filters): array
    {
        $checks = $this->dataChecks();
        $totals = [];
        $bindings = [];

        foreach ($checks as $key => $check) {
            $totals[] = "SUM(CASE WHEN ({$check['rule']}) THEN 1 ELSE 0 END) AS {$key}";
            array_push($bindings, ...$check['bindings']);
        }

        $counts = $this->calls($filters)->toBase()->selectRaw(implode(', ', $totals), $bindings)->first();

        return collect($checks)
            ->map(fn ($check, $key) => ['label' => $check['label'], 'count' => (int) ($counts->{$key} ?? 0)])
            ->all();
    }

    // The page's filters: the district a call counts in, and its date. A date chosen leaves out undated calls.
    private function calls(array $filters): Builder
    {
        return HonhaarCall::query()
            ->when($filters['district'] !== '', fn ($query) => $query->where('home_district', $filters['district']))
            ->when($filters['from'] !== '', fn ($query) => $query->where('call_date', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->where('call_date', '<=', $filters['to']));
    }

    private function callRecords(array $filters, array $table): Builder
    {
        $search = addcslashes($table['search'], '\\%_');
        $check = $table['check'] === '' ? null : $this->dataChecks()[$table['check']];
        $sort = self::RECORD_COLUMNS[$table['sort']]['sort'] ?? $table['sort'];

        return $this->calls($filters)
            ->with(['copyOf:id,source_sr,initiative_file_id', 'file:id,original_name'])
            ->when($table['search'] !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    foreach (['applicant_name', 'cnic', 'contact_no', 'institute_name', 'home_district', 'district', 'remarks'] as $column) {
                        $query->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->when($check !== null, fn ($query) => $query->whereRaw('('.$check['rule'].')', $check['bindings']))
            ->orderBy($sort, $table['dir'])
            ->orderBy('id');
    }
}
