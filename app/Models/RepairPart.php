<?php

namespace App\Models;

use Database\Factories\RepairPartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static RepairPartFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\RepairPartFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\RepairPartFactory>
 */
class RepairPart extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'repair_order_id',
        'no',
        'name',
        'quantity',
        'unit_price',
        'metadata',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<RepairOrder, $this>
     */
    public function repairOrder()
    {
        return $this->belongsTo(RepairOrder::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function ownerProject()
    {
        return $this->belongsTo(Project::class, 'owned_by_project_id');
    }

    protected static function newFactory(): RepairPartFactory
    {
        return RepairPartFactory::new();
    }
}
