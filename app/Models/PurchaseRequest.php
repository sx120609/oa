<?php

namespace App\Models;

use Database\Factories\PurchaseRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static PurchaseRequestFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\PurchaseRequestFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\PurchaseRequestFactory>
 */
class PurchaseRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ORDERED = 'ordered';

    public const STATUS_FULFILLED = 'fulfilled';

    protected $fillable = [
        'asset_id',
        'no',
        'status',
        'title',
        'amount',
        'requested_at',
        'details',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'date',
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

    protected static function newFactory(): PurchaseRequestFactory
    {
        return PurchaseRequestFactory::new();
    }
}
