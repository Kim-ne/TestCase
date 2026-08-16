<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestGenerationRequest extends Model
{
    protected $table = 'test_generation_requests';

    protected $fillable = [
        'input_type',
        'input_text',
        'input_file_path',
        'output_language',
        'provider',
        'status',
        'error_message',
    ];

    public function testCases(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TestCase::class);
    }
}
