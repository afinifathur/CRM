<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_date',
        'product_id',
        'stock_pcs',
        'stock_kg',
        'import_batch_id',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'stock_pcs' => 'integer',
        'stock_kg' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
