<?php

namespace App\Models;

use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssetLog> $logs
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RepairOrder> $repairOrders
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Attachment> $attachments
 *
 * @method static AssetFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AssetFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AssetFactory>
 */
#[UseFactory(AssetFactory::class)]
class Asset extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PURCHASED = 'purchased';

    public const STATUS_IN_STOCK = 'in_stock';

    public const STATUS_IN_USE = 'in_use';

    public const STATUS_UNDER_REPAIR = 'under_repair';

    public const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'no',
        'name',
        'asset_tag',
        'status',
        'category',
        'serial_number',
        'specification',
        'metadata',
        'purchased_at',
        'current_user_id',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'specification' => 'array',
        'metadata' => 'array',
        'purchased_at' => 'date',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function currentUser()
    {
        return $this->belongsTo(User::class, 'current_user_id');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function ownerProject()
    {
        return $this->belongsTo(Project::class, 'owned_by_project_id');
    }

    /**
     * @return HasMany<AssetLog, $this>
     */
    public function logs()
    {
        return $this->hasMany(AssetLog::class);
    }

    /**
     * @return HasMany<RepairOrder, $this>
     */
    public function repairOrders()
    {
        return $this->hasMany(RepairOrder::class);
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    protected static function newFactory(): AssetFactory
    {
        return AssetFactory::new();
    }
}
