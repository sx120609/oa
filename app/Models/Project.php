<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static ProjectFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ProjectFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\ProjectFactory>
 */
class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'no',
        'name',
        'status',
        'description',
        'metadata',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<Project, $this>
     */
    public function ownerProject()
    {
        return $this->belongsTo(self::class, 'owned_by_project_id');
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'owned_by_project_id');
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function children()
    {
        return $this->hasMany(self::class, 'owned_by_project_id');
    }

    protected static function newFactory(): ProjectFactory
    {
        return ProjectFactory::new();
    }
}
