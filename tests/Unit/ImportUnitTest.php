<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use App\Models\ImportedRow;
use App\Events\RowImported;
use App\Services\ExcelImportService;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;

class ImportUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function validates_and_imports_excel_file()
    {
        Storage::fake('local');

        // Создаём временный Excel-файл с реальными данными
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('B1', 'name');
        $sheet->setCellValue('C1', 'date');
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Test Name');
        $sheet->setCellValue('C2', '20.02.2024');
    
        $tempFilePath = tempnam(sys_get_temp_dir(), 'test_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFilePath);
    
        $file = new UploadedFile($tempFilePath, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    
        $service = new ExcelImportService();
        $result = $service->import($file->path(), 'test-import');
    
        $this->assertArrayHasKey('imported', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    #[Test]
    public function tracks_import_progress_in_redis()
    {
        Redis::flushall();
        $importId = 'test-import';
        Redis::set("import_progress:{$importId}:total", 100);
        Redis::set("import_progress:{$importId}:processed", 50);

        $this->assertEquals(100, Redis::get("import_progress:{$importId}:total"));
        $this->assertEquals(50, Redis::get("import_progress:{$importId}:processed"));
    }

    #[Test]
    public function creates_error_report_for_invalid_rows()
    {
        Storage::fake('local');
        $errors = [
            '2 - Invalid ID',
            '4 - Missing name',
        ];

        Storage::put('result.txt', implode("\n", $errors));

        Storage::assertExists('result.txt');
        $this->assertStringContainsString('2 - Invalid ID', Storage::get('result.txt'));
        $this->assertStringContainsString('4 - Missing name', Storage::get('result.txt'));
    }

    #[Test]
    public function groups_imported_data_by_date()
    {
        ImportedRow::factory()->create([
            'id' => 1,
            'name' => 'Test 1',
            'date' => '2024-01-01',
        ]);

        ImportedRow::factory()->create([
            'id' => 2,
            'name' => 'Test 2',
            'date' => '2024-01-01',
        ]);

        ImportedRow::factory()->create([
            'id' => 3,
            'name' => 'Test 3',
            'date' => '2024-01-02',
        ]);

        // $response = $this->getJson('/api/imported-data');
        $response = $this->getJson(route('imported-data'));

        $response->assertStatus(200)
                 ->assertJson([
                     [
                         'date' => '2024-01-01',
                         'items' => [
                             ['id' => 1, 'name' => 'Test 1', 'date' => '2024-01-01'],
                             ['id' => 2, 'name' => 'Test 2', 'date' => '2024-01-01'],
                         ]
                     ],
                     [
                         'date' => '2024-01-02',
                         'items' => [
                             ['id' => 3, 'name' => 'Test 3', 'date' => '2024-01-02'],
                         ]
                     ]
                 ]);
    }

    #[Test]
    public function dispatches_row_imported_event()
    {
        Event::fake();

        $rowData = [
            'id' => 1,
            'name' => 'Test Row',
            'date' => '01.01.2024',
        ];

        event(new RowImported($rowData));

        Event::assertDispatched(RowImported::class, function ($event) use ($rowData) {
            return $event->row['id'] === $rowData['id'] &&
                   $event->row['name'] === $rowData['name'] &&
                   $event->row['date'] === $rowData['date'];
        });
    }
}
