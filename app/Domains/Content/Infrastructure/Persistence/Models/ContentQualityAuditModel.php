<?php

namespace App\Domains\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentQualityAuditModel extends Model
{
    use HasFactory;

    protected $table = 'content_quality_audits';

    protected $fillable = [
        'organization_id',
        'content_post_id',
        'score',
        'brand_alignment_score',
        'hook_strength_score',
        'clarity_score',
        'safety_compliance_score',
        'strengths',
        'warnings',
        'suggestions',
        'passed_restrictions',
        'metadata',
    ];

    protected $casts = [
        'score' => 'integer',
        'brand_alignment_score' => 'integer',
        'hook_strength_score' => 'integer',
        'clarity_score' => 'integer',
        'safety_compliance_score' => 'integer',
        'strengths' => 'array',
        'warnings' => 'array',
        'suggestions' => 'array',
        'passed_restrictions' => 'boolean',
        'metadata' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(ContentPostModel::class, 'content_post_id');
    }
}
