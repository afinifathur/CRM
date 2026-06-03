<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportPreviewBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'user_session',
        'status',
        'total_rows',
        'valid_rows',
        'warning_rows',
        'duplicate_rows',
        'preview_payload',
        'raw_header_json',
        'raw_sample_rows_json',
        'temp_file_path',
        'source_filename',
        'snapshot_date',
        'import_batch_id',
    ];

    protected $casts = [
        'preview_payload' => 'array',
        'raw_header_json' => 'array',
        'raw_sample_rows_json' => 'array',
        'snapshot_date' => 'date',
        'total_rows' => 'integer',
        'valid_rows' => 'integer',
        'warning_rows' => 'integer',
        'duplicate_rows' => 'integer',
    ];

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
