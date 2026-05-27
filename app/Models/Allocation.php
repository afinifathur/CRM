<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_line_id',
        'sales_order_line_id',
        'allocated_qty',
        'allocation_method',
        'notes',
    ];

    protected $casts = [
        'allocated_qty' => 'decimal:4',
    ];

    public function shipmentLine(): BelongsTo
    {
        return $this->belongsTo(ShipmentLine::class);
    }

    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class);
    }

    protected static function booted()
    {
        // Enforce safety rules prior to record creation
        static::creating(function (Allocation $allocation) {
            $soLine = $allocation->salesOrderLine;
            
            if (!$soLine) {
                throw new \InvalidArgumentException("Sales Order Line not found for allocation.");
            }

            // Strictly check for overallocation
            if (bccomp($allocation->allocated_qty, $soLine->outstanding_qty, 4) > 0) {
                throw new \LogicException(
                    "Cannot allocate {$allocation->allocated_qty} pcs. Only {$soLine->outstanding_qty} pcs outstanding on SO Line #{$soLine->id}."
                );
            }

            // Prevent negative outstanding
            $newOutstanding = bcsub($soLine->outstanding_qty, $allocation->allocated_qty, 4);
            if (bccomp($newOutstanding, '0', 4) < 0) {
                throw new \LogicException("Allocation would cause negative outstanding qty.");
            }
        });

        // Atomically update quantities and statuses after creation
        static::created(function (Allocation $allocation) {
            $soLine = $allocation->salesOrderLine;
            
            $soLine->increment('allocated_qty', (float) $allocation->allocated_qty);
            $soLine->decrement('outstanding_qty', (float) $allocation->allocated_qty);

            $soLine->status = bccomp($soLine->outstanding_qty, '0', 4) === 0 ? 'completed' : 'partially_shipped';
            $soLine->save();
        });

        // Atomically restore quantities on deletion
        static::deleting(function (Allocation $allocation) {
            $soLine = $allocation->salesOrderLine;
            
            if ($soLine) {
                $soLine->decrement('allocated_qty', (float) $allocation->allocated_qty);
                $soLine->increment('outstanding_qty', (float) $allocation->allocated_qty);

                $soLine->status = bccomp($soLine->outstanding_qty, $soLine->ordered_qty, 4) === 0 ? 'open' : 'partially_shipped';
                $soLine->save();
            }
        });
    }
}
