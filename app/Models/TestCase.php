<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestCase extends Model
{
    protected $table = 'test_cases';

    protected $fillable = [
        'test_generation_request_id',
        'title',
        'preconditions',
        'steps',
        'expected_result',
        'priority',
    ];

    protected $casts = [
        'steps' => 'array',
    ];

    public function testGenerationRequest(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(TestGenerationRequest::class);
    }
}
