<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_type',
        'source_filename',
        'imported_at',
        'total_rows',
        'inserted_rows',
        'skipped_rows',
        'notes',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'total_rows' => 'integer',
        'inserted_rows' => 'integer',
        'skipped_rows' => 'integer',
    ];

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function stockSnapshots(): HasMany
    {
        return $this->hasMany(StockSnapshot::class);
    }
}
