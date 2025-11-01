<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static AttachmentFactory factory($count = null, $state = [])
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AttachmentFactory>
 *
 * @phpstan-use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AttachmentFactory>
 */
class Attachment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'no',
        'file_name',
        'disk',
        'path',
        'mime_type',
        'size',
        'attachable_type',
        'attachable_id',
        'meta',
        'owned_by_project_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'meta' => 'array',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable()
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function ownerProject()
    {
        return $this->belongsTo(Project::class, 'owned_by_project_id');
    }

    protected static function newFactory(): AttachmentFactory
    {
        return AttachmentFactory::new();
    }
}
