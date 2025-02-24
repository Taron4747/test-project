<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RowsImport;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExcelImportService
{
    public function import($filePath, $importId)
    {
        $import = new RowsImport($importId);

        // Set total rows for progress tracking
        $totalRows = $this->getRowCount($filePath);
        Redis::set("import_progress:{$importId}:total", $totalRows);
        Redis::set("import_progress:{$importId}:processed", 0);

        Excel::import($import, $filePath);

        // Clear progress after completion
        Redis::del(["import_progress:{$importId}:total", "import_progress:{$importId}:processed"]);

        return [
            'imported' => $import->getImportedCount(),
            'errors' => $import->getErrors()
        ];
    }

    public function generateErrorReport(array $errors)
    {
        $content = "";
        foreach ($errors as $error) {
            $content .= $error . "\n";
        }

        Storage::put('result.txt', $content);

        // Git commit
        $this->commitErrorReport();
    }

    protected function commitErrorReport()
    {
        $filePath = realpath(storage_path('app/private/result.txt'));

        $commands = [
            'git add -f '.$filePath ,
            'git commit -m "Add result.txt with validation errors"'
        ];

      foreach ($commands as $command) {
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            \Log::error("Git command failed: {$command}", ['output' => $output]);
        }
    }
    }

    protected function getRowCount($filePath)
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->getHighestRow() - 1; // Exclude header
    }
}