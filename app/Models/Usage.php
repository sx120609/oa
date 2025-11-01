<?php

namespace App\Models;

use Database\Factories\UsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static UsageFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\UsageFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\UsageFactory>
 */
class Usage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'usages';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'asset_id',
        'user_id',
        'project_id',
        'no',
        'status',
        'started_at',
        'ended_at',
        'details',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function ownerProject()
    {
        return $this->belongsTo(Project::class, 'owned_by_project_id');
    }

    protected static function newFactory(): UsageFactory
    {
        return UsageFactory::new();
    }
}
