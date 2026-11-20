<?php

namespace Tests\Feature;

use App\Dashboards\Initiatives\LocalGovernment\PdpCityNames;
use App\Dashboards\Initiatives\LocalGovernment\PdpReportReader;
use Tests\TestCase;

// The Chief Minister Punjab Development Program page's reading rules: city names spelled differently in
// the files, the visit form's checks, and the SMU reports with the department's answers.
class PunjabDevelopmentProgramTest extends TestCase
{
    private const LIST = [
        'alipurchatha' => 'Ali Pur Chatha',
        'khudian' => 'Khudian',
        'jauharabad' => 'Jauharabad',
        'ferozewala' => 'Ferozewala',
        'burewalagaggo' => 'Burewala & Gaggo',
        'tobateksinghsew' => 'Toba Tek Singh (Sew)',
        'gojra' => 'Gojra',
    ];

    public function test_city_names_spelled_differently_in_the_visit_file_are_found_in_the_list(): void
    {
        $this->assertSame('alipurchatha', PdpCityNames::find('Alipur Chatha', self::LIST));
        $this->assertSame('khudian', PdpCityNames::find('Khuddian', self::LIST));
        $this->assertSame('jauharabad', PdpCityNames::find('Joharabad', self::LIST));
        $this->assertSame('ferozewala', PdpCityNames::find('Ferozwala', self::LIST));
        $this->assertSame('burewalagaggo', PdpCityNames::find('Burewala', self::LIST));
        $this->assertSame('tobateksinghsew', PdpCityNames::find('Toba Tek Singh', self::LIST));
        $this->assertSame('tobateksinghsew', PdpCityNames::find('TTS', self::LIST));
    }

    public function test_a_city_not_in_the_list_is_not_matched_to_another(): void
    {
        $this->assertNull(PdpCityNames::find('Jamkey Cheema', self::LIST));
        // Only the same letters count in a search across the whole list.
        $this->assertNull(PdpCityNames::find('Khuddian', self::LIST, false));
        // A lower-case short name is not taken for an abbreviation.
        $this->assertNull(PdpCityNames::find('tts', ['tobateksinghsew' => 'Toba Tek Singh (Sew)']));
    }

    public function test_the_visit_forms_checks_count_the_answer_that_means_a_problem(): void
    {
        $reader = new PdpReportReader;
        $visit = (object) array_fill_keys(array_keys(PdpReportReader::FORM), 'Yes');
        $visit->manholes_without_cover = 'No';
        $visit->slow_or_halted_work = 'No';

        // "Workers using proper safety equipment": No is the problem, so Yes is fine.
        $this->assertSame([], $reader->findings($visit));

        $visit->workers_using_safety_equipment = 'No';
        $visit->manholes_without_cover = 'Yes ';
        $visit->dust_control_measures = 'Not checked';

        $this->assertSame([$reader->index('manhole'), $reader->index('ppe')], $reader->findings($visit));
    }

    public function test_a_report_is_read_city_by_city_and_each_problem_gets_the_answer_given_to_it(): void
    {
        $reader = new PdpReportReader;
        $report = "The team has visited 2 cities of 2 districts.\n"
            ."In Vehari, Construction debris was not removed. Most workers are in ordinary clothes and are not using any safety equipment.\n"
            ."Traffic movement around the construction site was not managed safely.\n"
            ."In TTS, The pace of work is slow. Work has been halted since Eid.\n"
            .'Construction materials were not properly organized and stored at the site.';
        $answer = "In Vehari, Construction debris has been removed now. The contractor has been instructed to provide PPE.\n"
            .'In Toba Tek Singh, The observations were noted.';
        $cities = ['vehari' => 'Vehari', 'tobateksinghsew' => 'Toba Tek Singh (Sew)'];

        $letter = $reader->letter($report, $answer, fn ($name) => PdpCityNames::find($name, $cities) ?? 'x');

        $this->assertSame('The team has visited 2 cities of 2 districts.', $letter['head']);
        $this->assertNull($letter['general']);
        $this->assertSame(['vehari', 'tobateksinghsew'], array_keys($letter['cities']));

        $read = fn ($key) => array_map(fn ($item) => [PdpReportReader::KEYS[$item[0]]['k'], $item[2]], $letter['cities'][$key]['items']);

        // One sentence can name two problems, and the answer's own sentence about each decides.
        $this->assertSame([['debris', 'done'], ['uniform', 'notans'], ['ppe', 'instr'], ['traffic', 'notans']], $read('vehari'));
        // Two sentences in a row on one line about the same problem are one problem; "noted" is no answer.
        $this->assertSame([['slow', 'notans'], ['material', 'notans']], $read('tobateksinghsew'));
        $this->assertCount(2, explode('. ', $letter['cities']['tobateksinghsew']['items'][0][1]));
    }

    public function test_one_general_answer_counts_as_instructions_for_every_problem(): void
    {
        $reader = new PdpReportReader;
        $letter = $reader->letter(
            "In Hazro, The pace of work is slow. The labor was not fully complying with the safety SOPs.",
            'Instructions have been issued to concerned staff and same is being ensured.',
            fn ($name) => strtolower($name)
        );

        $this->assertSame('Instructions have been issued to concerned staff and same is being ensured.', $letter['general']);
        $this->assertSame(['instr', 'instr'], array_column($letter['cities']['hazro']['items'], 2));
    }
}
