<?php

namespace App\Models;

use Database\Factories\StockInFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static StockInFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\StockInFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\StockInFactory>
 */
class StockIn extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'asset_id',
        'no',
        'status',
        'location',
        'received_at',
        'details',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'received_at' => 'date',
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
     * @return BelongsTo<Project, $this>
     */
    public function ownerProject()
    {
        return $this->belongsTo(Project::class, 'owned_by_project_id');
    }

    protected static function newFactory(): StockInFactory
    {
        return StockInFactory::new();
    }
}
