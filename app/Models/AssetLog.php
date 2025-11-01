<?php

namespace App\Models;

use Database\Factories\AssetLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static AssetLogFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AssetLogFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AssetLogFactory>
 */
class AssetLog extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'asset_id',
        'no',
        'event',
        'from_status',
        'to_status',
        'changes',
        'source',
        'request_id',
        'description',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'changes' => 'array',
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

    protected static function newFactory(): AssetLogFactory
    {
        return AssetLogFactory::new();
    }
}
