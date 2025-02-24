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

    public function generateErrorReport(array $errors, $importId)
    {
        $content = "";
        foreach ($errors as $error) {
            $content .= $error . "\n";
        }
        $directoryPath = base_path('error-files');

        if (!\File::exists($directoryPath)) {
            \File::makeDirectory($directoryPath, 0755, true);
        }

        $filePath = $directoryPath . '/result'.$importId.'.txt';

        \File::put($filePath, $content);



            // Git commit
            $this->commitErrorReport();
        }

    protected function commitErrorReport( )
    {
        

        $commands = implode(' && ', [
            'cd ' . base_path(),
            'git config user.name "Taron Gyulumyan"',
            'git config user.email "tarongyulumyan@gmail.com"',
            'git status',
            'git add .',
            'git commit -a -m "Add result.txt with validation errors"',
            'git push origin main'
        ]);
        
        exec($commands . ' 2>&1', $output, $returnVar);
        
        if ($returnVar !== 0) {
            \Log::error("Git command failed", ['output' => implode("\n", $output)]);
        }
    
    }

    protected function getRowCount($filePath)
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->getHighestRow() - 1; // Exclude header
    }
}