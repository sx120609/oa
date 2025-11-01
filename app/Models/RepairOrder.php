<?php

namespace App\Models;

use Database\Factories\RepairOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RepairPart> $parts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Worklog> $worklogs
 *
 * @method static RepairOrderFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\RepairOrderFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\RepairOrderFactory>
 */
class RepairOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_CREATED = 'created';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_DIAGNOSED = 'diagnosed';

    public const STATUS_WAITING_PARTS = 'waiting_parts';

    public const STATUS_REPAIRING = 'repairing';

    public const STATUS_QA = 'qa';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_SCRAPPED = 'scrapped';

    protected $fillable = [
        'asset_id',
        'technician_id',
        'no',
        'status',
        'reported_at',
        'closed_at',
        'summary',
        'details',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'closed_at' => 'datetime',
        'details' => 'array',
    ];

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function ownerProject()
    {
        return $this->belongsTo(Project::class, 'owned_by_project_id');
    }

    /**
     * @return HasMany<RepairPart, $this>
     */
    public function parts()
    {
        return $this->hasMany(RepairPart::class);
    }

    /**
     * @return HasMany<Worklog, $this>
     */
    public function worklogs()
    {
        return $this->hasMany(Worklog::class);
    }

    protected static function newFactory(): RepairOrderFactory
    {
        return RepairOrderFactory::new();
    }
}
