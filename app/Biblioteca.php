<?php

namespace App;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;

class Biblioteca extends Model
{
    use HasSlug;
    protected $table = 'biblioteca';
    protected $primaryKey = 'idBiblioteca';

    protected $fillable = [
        'acesso',
        'descricao',
        'ficheiro',
        'tipo',
        'tamanho',
        'link'
    ];

    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('descricao')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
