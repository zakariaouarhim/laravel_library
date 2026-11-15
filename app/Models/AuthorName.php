<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A plain-string alternate spelling of an author's name (no author row of
// its own). Feeds Author::allNames() → book search index + author search.
class AuthorName extends Model
{
    protected $table = 'author_names';

    protected $fillable = ['author_id', 'name'];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}
