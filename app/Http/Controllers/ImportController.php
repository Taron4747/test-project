<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ExcelImportService;
use App\Models\ImportedRow;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    protected $importService;

    public function __construct(ExcelImportService $importService)
    {
        $this->importService = $importService;
    }
    public function showUploadForm()
    {
        return view('upload');
    }
    public function handleUpload(Request $request)
    {
        ini_set('max_execution_time', '300');

        $request->validate([
            'file' => 'required|mimes:xlsx|max:10240',
        ]);

        $path = $request->file('file')->store('imports');
        $importId = Str::uuid();

        $result = $this->importService->import(storage_path("app/private/{$path}"), $importId);

        $this->importService->generateErrorReport($result['errors']);

        return response()->json([
            'import_id' => $importId,
            'result' => $result
        ]);
    }

    public function getImportedData()
    {
        $data = ImportedRow::all()
            ->groupBy(function ($item) {
                return $item->date;
            })
            ->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'items' => $items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'date' => $item->date,
                        ];
                    })->values()
                ];
            })
            ->values();

        return response()->json($data);
    }
}