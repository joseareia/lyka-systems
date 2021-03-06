<?php

namespace App\Http\Controllers;

use App\Fase;
use App\Produto;
use App\Cliente;
use App\DocAcademico;
use App\DocNecessario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreDocumentoRequest;
use App\Http\Requests\UpdateDocumentoRequest;

class DocAcademicoController extends Controller
{
    public function create(Fase $fase, DocNecessario $docnecessario)
    {
        $produts = null;
        $permissao = false;
        if (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Agente') {
            $produts = Produto::whereRaw('idAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        } elseif (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Subagente') {
            $produts = Produto::whereRaw('idSubAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        }
        if ($produts) {
            $permissao = true;
        }

        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)||
            (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null)|| $permissao) {
            $documento = new DocAcademico;
            $tipoPAT = $docnecessario->tipo;
            $tipo = $docnecessario->tipoDocumento;

            return view('documentos.add', compact('fase', 'tipoPAT', 'tipo', 'documento', 'docnecessario'));
        } else {
            abort(403);
        }
    }

    public function store(StoreDocumentoRequest $request, Fase $fase, DocNecessario $docnecessario)
    {
        $produts = null;
        $permissao = false;
        if (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Agente') {
            $produts = Produto::whereRaw('idAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        } elseif (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Subagente') {
            $produts = Produto::whereRaw('idSubAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        }
        if ($produts) {
            $permissao = true;
        }

        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)||
            (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null)|| $permissao) {
            $fields = $request->all();
            $infoDoc = null;

            for ($i=1;$i<=500;$i++) {
                if (array_key_exists('nome-campo'.$i, $fields)) {
                    if ($fields['nome-campo'.$i]) {
                        $infoDoc[$fields['nome-campo'.$i]] = $fields['valor-campo'.$i];
                    }
                } else {
                    break;
                }
            }

            $documento = new DocAcademico;

            if ($infoDoc) {
                $documento->info = json_encode($infoDoc);
            } else {
                $documento->info = NULL;
            }


            $documento->tipo=$docnecessario->tipoDocumento;
            if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) {
                $documento->verificacao = true;
            } else {
                $documento->verificacao = false;
            }
            $documento->nome = $fields['nome'];
            $documento->idCliente = $fase->produto->cliente->idCliente;
            $documento->idFase = $fase->idFase;

            $source = null;

            if ($fields['img_doc']) {
                $ficheiro = $fields['img_doc'];
                $tipoDoc = str_replace(".", "_", str_replace(" ", "", $documento->tipo));
                $nomeficheiro = 'cliente_'.$fase->produto->cliente->idCliente.'_fase_'.$fase->idFase.'_documento_academico_'.$tipoDoc.'.'.$ficheiro->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('client-documents/'.$fase->produto->cliente->idCliente.'/', $ficheiro, $nomeficheiro);
                /* $source = 'client-documents/'.$fase->produto->cliente->idCliente.'/'.$nomeficheiro; */
            }
            $documento->imagem = $nomeficheiro;
            $documento->save();

            return redirect()->route('clients.show', $fase->client)->with('success', 'Documento '.$docnecessario->tipoDocumento.' adicionado com sucesso!');
        } else {
            abort(403);
        }
    }

    public function createFromClient(StoreDocumentoRequest $request, Cliente $client)
    {
        $checkDocCliente = DocAcademico::where("idCliente", $client->idCliente)->where("tipo", $request->NomeDocumentoAcademico)->get();
        if (count($checkDocCliente) == 0) {
            $produts = null;
            $permissao = false;
            if (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Agente') {
                $produts = Produto::whereRaw('idAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
            } elseif (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Subagente') {
                $produts = Produto::whereRaw('idSubAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
            }
            if ($produts) {
                $permissao = true;
            }

            if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)||
                (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null)|| $permissao) {
                $fields = $request->all();

                $documento = new DocAcademico;
                $tipoPAT = "Academico";
                $docnome = $fields['NomeDocumentoAcademico'];
                $tipo = $docnome;
                $fase = null;

                return view('documentos.add', compact('fase', 'tipoPAT', 'tipo', 'documento', 'docnome', 'client'));
            } else {
                abort(403);
            }
        }else {
            return redirect()->back()->withErrors(['message' => 'O documento com o nome "'.$request->NomeDocumentoAcademico.'" já existe! Por favor, insira outro nome.']);
        }
    }

    public function storeFromClient(StoreDocumentoRequest $request, Cliente $client, String $docnome)
    {
        $produts = null;
        $permissao = false;
        if (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Agente') {
            $produts = Produto::whereRaw('idAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        } elseif (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Subagente') {
            $produts = Produto::whereRaw('idSubAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        }
        if ($produts) {
            $permissao = true;
        }

        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)||
            (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null)|| $permissao) {
            $fields = $request->all();
            $infoDoc = null;

            for ($i=1;$i<=500;$i++) {
                if (array_key_exists('nome-campo'.$i, $fields)) {
                    if ($fields['nome-campo'.$i]) {
                        $infoDoc[$fields['nome-campo'.$i]] = $fields['valor-campo'.$i];
                    }
                } else {
                    break;
                }
            }

            $documento = new DocAcademico;
            $documento->idCliente = $client->idCliente;
            $documento->verificacao = true;
            if ($infoDoc) {
                $documento->info = json_encode($infoDoc);
            } else {
                $documento->info = NULL;
            }


            $documento->tipo=$docnome;
            if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) {
                $documento->verificacao = true;
            } else {
                $documento->verificacao = false;
            }
            $documento->nome = $fields['nome'];
            $documento->idCliente = $client->idCliente;

            $source = null;

            if ($fields['img_doc']) {
                $ficheiro = $fields['img_doc'];
                $tipoDoc = str_replace(".", "_", str_replace(" ", "", $documento->tipo));
                $nomeficheiro = 'cliente_'.$client->idCliente.'_documento_academico_'.$tipoDoc.'.'.$ficheiro->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('client-documents/'.$client->idCliente.'/', $ficheiro, $nomeficheiro);
                /* $source = 'client-documents/'.$fase->produto->cliente->idCliente.'/'.$nomeficheiro; */
            }
            $documento->imagem = $nomeficheiro;
            $documento->save();

            return redirect()->route('clients.show', $client)->with('success', 'Documento '.$docnome.' adicionado com sucesso!');
        } else {
            abort(403);
        }
    }

    public function verify(DocAcademico $documento)
    {
        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)) {
            $infoDoc = (array)json_decode($documento->info);
            $infoKeys = array_keys($infoDoc);
            $tipoPAT = 'Academico';
            $tipo = $documento->tipo;
            return view('documentos.verify', compact('documento', 'infoDoc', 'infoKeys', 'tipo', 'tipoPAT'));
        } else {
            abort(403);
        }
    }

    public function verifica(DocAcademico $documento)
    {
        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)) {
            $documento->verificacao = true;
            $documento->save();
            return redirect()->route('produtos.show', $documento->fase->produto);
        } else {
            abort(403);
        }
    }

    public function edit(DocAcademico $documento, Cliente $client)
    {
        $produts = null;
        $permissao = false;
        if (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Agente') {
            $produts = Produto::whereRaw('idAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        } elseif (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Subagente') {
            $produts = Produto::whereRaw('idSubAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        }
        if ($produts) {
            $permissao = true;
        }

        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)||
            (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null)|| $permissao) {
            $infoDoc = (array)json_decode($documento->info);
            $infoKeys = array_keys($infoDoc);
            $tipoPAT = 'Academico';
            $tipo = $documento->tipo;

            return view('documentos.edit', compact('documento', 'client', 'infoDoc', 'infoKeys', 'tipo', 'tipoPAT'));
        } else {
            abort(403);
        }
    }

    public function update(UpdateDocumentoRequest $request, DocAcademico $documento)
    {
        $produts = null;
        $permissao = false;
        if (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Agente') {
            $produts = Produto::whereRaw('idAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        } elseif (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Subagente') {
            $produts = Produto::whereRaw('idSubAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        }
        if ($produts) {
            $permissao = true;
        }

        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)||
            (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null)|| $permissao) {
            $fields = $request->all();
            $infoDoc = null;

            for ($i=1;$i<=500;$i++) {
                if (array_key_exists('nome-campo'.$i, $fields)) {
                    if ($fields['nome-campo'.$i]) {
                        $infoDoc[$fields['nome-campo'.$i]] = $fields['valor-campo'.$i];
                    }
                } else {
                    break;
                }
            }


            if ($infoDoc) {
                $documento->info = json_encode($infoDoc);
            } else {
                $documento->info = NULL;
            }

            if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) {
                $documento->verificacao = true;
            } else {
                $documento->verificacao = false;
            }
            $documento->nome = $fields['nome'];

            if (array_key_exists('img_doc', $fields)) {
                $source = null;

                if ($fields['img_doc']) {
                    $ficheiro = $fields['img_doc'];
                    $tipoDoc = str_replace(".", "_", str_replace(" ", "", $documento->tipo));
                    $nomeficheiro = 'cliente_'.$fase->produto->cliente->idCliente.'_fase_'.$fase->idFase.'_documento_academico_'.$tipoDoc.'.'.$ficheiro->getClientOriginalExtension();
                    Storage::disk('public')->putFileAs('client-documents/'.$fase->produto->cliente->idCliente.'/', $ficheiro, $nomeficheiro);
                    /*                     $source = 'client-documents/'.$fase->produto->cliente->idCliente.'/'.$nomeficheiro; */
                }
                $documento->imagem = $nomeficheiro;
            }
            $documento->save();
            return redirect()->route('clients.show', $documento->cliente)->with('success', 'Dados do documento "'.$documento->tipo.'" editados com sucesso!');
        } else {
            abort(403);
        }
    }

    public function show(DocAcademico $documento)
    {
        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)) {
            $infoDoc = (array)json_decode($documento->info);
            $infoKeys = array_keys($infoDoc);
            $tipoPAT = 'Academico';
            $tipo = $documento->tipo;
            return view('documentos.show', compact('documento', 'infoDoc', 'infoKeys', 'tipo', 'tipoPAT'));
        } else {
            abort(403);
        }
    }


    public function destroy(DocAcademico $documento)
    {
        if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) {
            $tipo = $documento->tipo;
            Storage::disk('public')->delete('client-documents/'.$documento->idCliente.'/'.$documento->imagem);
            $documento->delete();
            return redirect()->route('clients.show', $documento->cliente)->with('success', 'Documento "'.$tipo.'" eliminado com sucesso');
        } else {
            abort(403);
        }
    }
}
