<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_code',
        'product_name',
    ];

    public function salesOrderLines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function shipmentLines(): HasMany
    {
        return $this->hasMany(ShipmentLine::class);
    }

    public function stockSnapshots(): HasMany
    {
        return $this->hasMany(StockSnapshot::class);
    }
}
