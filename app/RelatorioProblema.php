<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class RelatorioProblema extends Model
{
    use Notifiable;

    protected $table = 'RelatorioProblema';

    protected $primaryKey = 'idRelatorioProblema';

    protected $fillable = [
        'nome',
        'email',
        'telemovel',
        'screenshot',
        'relatorio',
        'estado'
    ];
}
