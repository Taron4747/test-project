<?php
namespace App\Imports;

use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use App\Events\RowImported;
use App\Models\ImportedRow;

class RowsImport implements OnEachRow
{
    protected $imported = 0;
    protected $errors = [];
    protected $importId;

    public function __construct($importId)
    {
        $this->importId = $importId;
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $rowData = $row->toArray();

        $mappedRow = [
            'id' => $rowData[0] ?? null,
            'name' => $rowData[1] ?? null,
            'date' => $rowData[2] ?? null,
        ];
        $validator = validator($mappedRow, [
            'id' => 'required|numeric|gt:0|unique:imported_rows,id',
            'name' => 'required|string',
            'date' => 'required|date_format:d.m.Y'
        ]);

        if ($validator->fails()) {
            $this->errors[] = "{$rowIndex} - " . implode(", ", $validator->errors()->all());
        } else {
            ImportedRow::create([
                'id' => $mappedRow['id'],
                'name' => $mappedRow['name'],
                'date' => \Carbon\Carbon::createFromFormat('d.m.Y', $mappedRow['date'])->format('Y-m-d'),
            ]);

            $this->imported++;
            event(new RowImported($rowData));
        }

        // Update progress in Redis
        Redis::incr("import_progress:{$this->importId}:processed");
    }

    public function getImportedCount()
    {
        return $this->imported;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}


