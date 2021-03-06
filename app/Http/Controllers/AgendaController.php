<?php

namespace App\Http\Controllers;

use App\User;
use App\Agenda;
use Carbon\Carbon;
use App\Universidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAgendaRequest;
use App\Http\Requests\UpdateAgendaRequest;

class AgendaController extends Controller
{
    public function __construct()
    {
        $this->middleware('agente');
    }

    public function index()
    {
        $events = Agenda::all();
        $universidades = Universidade::select('idUniversidade', 'nome')->get();
        return view('agenda.list', compact('events', 'universidades'));
    }

    public function store(StoreAgendaRequest $request)
    {
        $fields = $request->validated();
        $agenda = new Agenda;
        $agenda->fill($fields);
        $agenda->idUser = Auth()->user()->idUser;
        if ($agenda->data_fim) {
            $data_fim = Carbon::parse($agenda->data_fim)->addDay();
            $agenda->data_fim = $data_fim;
        }
        $agenda->save();
        return redirect()->route('agenda.index')->with('success', 'Evento adicionado com sucesso!');
    }

    public function update(UpdateAgendaRequest $request, Agenda $agenda)
    {
        $fields = $request->validated();
        $agenda->fill($fields);
        $agenda->idUser = Auth()->user()->idUser;
        if ($agenda->data_fim) {
            $data_fim = Carbon::parse($agenda->data_fim)->addDay();
            $agenda->data_fim = $data_fim;
        }
        $agenda->save();
        return redirect()->route('agenda.index')->with('success', 'Evento atualizado com sucesso!');
    }

    public function destroy(Agenda $agenda)
    {
        $agenda->delete();
        return redirect()->back()->with('success', 'Evento eliminado com sucesso!');
    }
}
