<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class AiChunkEmbedding extends Model
{
    use HasNeighbors;

    protected $connection = 'pgsql';

    protected $fillable = [
        'chunk_id',
        'embedding',
        'embedding_model',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => Vector::class,
            'chunk_id' => 'integer',
        ];
    }
}
