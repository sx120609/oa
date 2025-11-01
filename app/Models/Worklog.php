<?php

namespace App\Models;

use Database\Factories\WorklogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static WorklogFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\WorklogFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\WorklogFactory>
 */
class Worklog extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'repair_order_id',
        'no',
        'notes',
        'worked_at',
        'details',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'worked_at' => 'datetime',
        'details' => 'array',
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

    protected static function newFactory(): WorklogFactory
    {
        return WorklogFactory::new();
    }
}
