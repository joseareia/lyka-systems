<?php

namespace App\Http\Controllers;

use PDF;
use DateTime;

use App\Cliente;
use App\Agente;
use App\User;
use App\Universidade;
use App\DocPessoal;
use App\DocAcademico;
use App\DocNecessario;
use App\Responsabilidade;
use App\Produto;
use App\Fase;
use App\ClienteObservacoes;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Requests\StoreClientRequest;

use App\Jobs\SendWelcomeEmail;

class ClientController extends Controller
{
    public function index()
    {
        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) || (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null)) {
            if (Auth::user()->tipo == "admin") {
                $clients = Cliente::all();
                if ($clients->isEmpty()) {
                    $clients = null;
                }
            }else {
                if (Auth::user()->agente->tipo == "Agente") {
                    $clients_associados = Cliente::where('idAgente', Auth::user()->agente->idAgente)->get();

                    $clients_produto = Cliente::selectRaw("cliente.*")
                    ->join('produto', 'cliente.idCliente', 'produto.idCliente')
                    ->where('produto.idAgente', Auth::user()->agente->idAgente)
                    ->groupBy('cliente.idCliente')
                    ->orderBy('cliente.idCliente', 'desc')
                    ->get();

                    $clients = $clients_associados->merge($clients_produto);
                }

                if (Auth::user()->agente->tipo == "Subagente") {
                    $clients_associados = Cliente::where('idAgente', Auth::user()->agente->idAgente)->get();

                    $clients_produto = Cliente::selectRaw("cliente.*")
                    ->join('produto', 'cliente.idCliente', 'produto.idCliente')
                    ->where('produto.idSubAgente', Auth::user()->agente->idAgente)
                    ->groupBy('cliente.idCliente')
                    ->orderBy('cliente.idCliente', 'desc')
                    ->get();

                    $clients = $clients_associados->merge($clients_produto);
                }
            }
            return view('clients.list', compact('clients'));
        }else {
            abort(403);
        }
    }

    public function create()
    {
        if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) {
            $client = new Cliente;
            $agents = Agente::where("tipo", "Agente")->get();
            $subAgentes = Agente::where("tipo", "Subagente")->get();
            $instituicoes = array_unique(Cliente::pluck('nomeInstituicaoOrigem')->toArray());
            $cidadesInstituicoes = array_unique(Cliente::pluck('cidadeInstituicaoOrigem')->toArray());
            return view('clients.add', compact('client', 'agents', 'instituicoes', 'cidadesInstituicoes', 'subAgentes'));
        } else {
            abort(403);
        }
    }

    public function store(StoreClientRequest $requestClient)
    {
        if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) {
            $client = new Cliente;
            $fields = $requestClient->validated();
            $client->fill($fields);
            $client->nomeInstituicaoOrigem = ucwords(mb_strtolower($requestClient->nomeInstituicaoOrigem, 'UTF-8'));
            $client->cidadeInstituicaoOrigem = ucwords(mb_strtolower($requestClient->cidadeInstituicaoOrigem, 'UTF-8'));

            $client->save();

            $strpadIdCliente = str_pad($client->idCliente, 3, "0", STR_PAD_LEFT);
            $clientePerCountry = Cliente::where("paisNaturalidade", $client->paisNaturalidade)->count();
            $clientePerCountry = str_pad($clientePerCountry++, 3, "0", STR_PAD_LEFT);;
            $refClient = strtoupper($strpadIdCliente.'.'.$client->refCliente.'.'.$clientePerCountry);
            $client->refCliente = $refClient;

            if ($requestClient->hasFile('fotografia')) {
                $photo = $requestClient->file('fotografia');
                $profileImg = $client->idCliente .'.'. $photo->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('client-documents/'.$client->idCliente.'/', $photo, $profileImg);
                $client->fotografia = $profileImg;
                $client->save();
            }

            $client->slug = post_slug($client->nome.' '.$client->apelido);

            if ($client->nomeInstituicaoOrigem == "") {
                $client->nomeInstituicaoOrigem = null;
            }

            if ($client->cidadeInstituicaoOrigem == "") {
                $client->cidadeInstituicaoOrigem = null;
            }

            if ($client->nivEstudoAtual == "") {
                $client->nivEstudoAtual = null;
            }

            $client->save();

            /* Criação de documentos Pessoais */
            /* Cria Documento de identificação pessoal se Existir ficheiro para Upload*/
            if ($requestClient->hasFile('img_docOficial')) {
                $doc_id = new DocPessoal;
                $doc_id->idCliente = $client->idCliente;
                $doc_id->tipo = "Doc. Oficial";
                $doc_id->idFase = null;
                $doc_id->dataValidade = $requestClient->validade_docOficial;

                /* Constroi a informação adicional para documento de ID */
                $infoDocId = null;
                $infoDocId['numDoc'] = $requestClient->num_docOficial;
                $doc_id->info = json_encode($infoDocId);

                /* Imagem do documento de identificação Pessoal*/
                $img_doc = $requestClient->file('img_docOficial');
                $nome_imgDocOff = 'cliente_'.$client->idCliente.'_documento_pessoal_Doc_Oficial'.'.'.$img_doc->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('client-documents/'.$client->idCliente.'/', $img_doc, $nome_imgDocOff);
                $doc_id->imagem = $nome_imgDocOff;

                /* Se for o admin a inserir o ficheiro, é marcado como valido */
                if (Auth::user()->tipo == "admin") {
                    $doc_id->verificacao=true;
                }
                $doc_id->save();
            }

            /* Cria Passaporte se Existir ficheiro para Upload*/
            if ($requestClient->hasFile('img_Passaporte')) {
                $passaporte = new DocPessoal;
                $passaporte->idCliente = $client->idCliente;
                $passaporte->tipo = "Passaporte";
                $passaporte->idFase = null;
                $passaporte->dataValidade = $requestClient->dataValidPP;

                /* Constroi a informação adicional para o passaporte */
                $infoPassaporte = null;
                $infoPassaporte['numPassaporte'] = $requestClient->numPassaporte;
                $infoPassaporte['dataValidPP'] = $requestClient->dataValidPP;
                $infoPassaporte['passaportPaisEmi'] =$requestClient->passaportPaisEmi;
                $infoPassaporte['localEmissaoPP'] = $requestClient->localEmissaoPP;

                $passaporte->info = json_encode($infoPassaporte);

                /* Imagem do passaporte*/
                $img_doc = $requestClient->file('img_Passaporte');
                $nome_imgPassaporte = 'cliente_'.$client->idCliente.'_documento_pessoal_Passaporte'.'.'.$img_doc->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('client-documents/'.$client->idCliente.'/', $img_doc, $nome_imgPassaporte);
                $passaporte->imagem = $nome_imgPassaporte;

                /* Guarda passaporte */
                /* Se for o admin a inserir o ficheiro, é marcado como valido */
                if (Auth::user()->tipo == "admin") {
                    $passaporte->verificacao = true;
                }
                $passaporte->save();
            }

            return redirect()->route('clients.show', $client)->with('success', 'Ficha de estudante criada com sucesso!');
        } else {
            abort(403);
        }
    }

    public function show(Cliente $client)
    {
        // Produtos para serem visualizados para o Agente e/ou Subagente
        if (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Agente') {
            $products = Produto::whereRaw('idAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        } elseif (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Subagente') {
            $products = Produto::whereRaw('idSubAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        }

        // Produtos e documentos a serem visualizados pelo Administrador
        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin)) {
            $totalprodutos = null;
            $produtos = $client->produtoSaved;

            if ($produtos->isEmpty()) {
                $produtos = null;
                $totalprodutos = null;
            } else {
                $totalprodutos = 0;
                foreach ($produtos as $produto) {
                    $totalprodutos = $totalprodutos + $produto->valorTotal;
                }
            }

            $agente = Agente::where("idAgente", $client->idAgente)->first();
            $subAgente = Agente::where("idAgente", $client->idSubAgente)->first();

            $passaporte = DocPessoal::where("idCliente", $client->idCliente)
            ->where("tipo", "Passaporte")
            ->orderby("created_at", "desc")
            ->first();

            /* Decode das infos do passaporte */
            if ($passaporte != null) {
                $passaporteData = json_decode($passaporte->info);
            } else {
                $passaporteData = null;
            }

            /* Documentos pessoais */
            $documentosPessoais = DocPessoal::where("idCliente", $client->idCliente)->orderby("created_at", "desc")->get();
            if ($documentosPessoais->isEmpty()) {
                $documentosPessoais = null;
            }

            /* Documentos académicos */
            $documentosAcademicos = DocAcademico::where("idCliente", $client->idCliente)->orderby("created_at", "desc")->get();
            if ($documentosAcademicos->isEmpty()) {
                $documentosAcademicos = null;
            }

            /* Lista de Documentos Necessários */
            $novosDocumentos = DocNecessario::all();

            // Estado financeiro do cliente
            function fasesDivida($client)
            {
                $produtos = Produto::where("idCliente", $client->idCliente)->get();
                $fasesDivida = array();
                foreach ($produtos as $produto) {
                    $fases = Fase::where("idProduto", $produto->idProduto)
                    ->where("estado", "Dívida")
                    ->where("verificacaoPago", false)
                    ->orderBy("dataVencimento", "desc")
                    ->get();
                    foreach ($fases as $fase) {
                        array_push($fasesDivida, $fase);
                    }
                }
                return $fasesDivida;
            }

            function fasesPendentes($client)
            {
                $produtos = Produto::where("idCliente", $client->idCliente)->get();
                $fasesPendentes = array();
                foreach ($produtos as $produto) {
                    $fases = Fase::where("idProduto", $produto->idProduto)
                    ->where("estado", "Pendente")
                    ->where("verificacaoPago", false)
                    ->orderBy("dataVencimento", "desc")
                    ->get();
                    foreach ($fases as $fase) {
                        array_push($fasesPendentes, $fase);
                    }
                }
                return $fasesPendentes;
            }

            function fasesPagas($client)
            {
                $produtos = Produto::where("idCliente", $client->idCliente)->get();
                $fasesPagas = array();
                foreach ($produtos as $produto) {
                    $fases = Fase::where("idProduto", $produto->idProduto)
                    ->where("estado", "Pago")
                    ->where("verificacaoPago", true)
                    ->orderBy("dataVencimento", "desc")
                    ->get();
                    foreach ($fases as $fase) {
                        array_push($fasesPagas, $fase);
                    }
                }
                return $fasesPagas;
            }

            $observacoesCliente = ClienteObservacoes::where("idCliente", $client->idCliente)->get();
            $responsabilidades = Responsabilidade::where("idCliente", $client->idCliente)->get();
            $currentdate = new DateTime();
            $fasesDivida = fasesDivida($client);
            $fasesPendentes = fasesPendentes($client);
            $fasesPagas = fasesPagas($client);

            return view('clients.show', compact("observacoesCliente", "currentdate", "responsabilidades", "client", "fasesDivida", "fasesPendentes", "fasesPagas", "agente", "subAgente", "produtos", "totalprodutos", "passaporteData", 'documentosPessoais', 'documentosAcademicos', 'novosDocumentos'));
        } else {
            abort(403);
        }
    }

    public function edit(Cliente $client)
    {
        $produts = null;
        $permissao = false;
        if (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Agente') {
            $produts = Produto::whereRaw('idAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        } elseif (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Subagente') {
            $produts = Produto::whereRaw('idSubAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        }
        if ($produts && $client->editavel) {
            $permissao = true;
        }

        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) || $permissao) {

            /* Obtem as informações sobre os documentos */
            $docOfficial = DocPessoal::
            where("idCliente", $client->idCliente)
            ->where("tipo", "Doc. Oficial")
            ->orderby("created_at", "desc")/*  Ordena por data: do mais recente para o mais antigo */
            ->first(); /* Seleciona o registo mais recente */

            // Dados do passaporte
            $passaporte = DocPessoal::
            where("idCliente", $client->idCliente)
            ->where("tipo", "Passaporte")
            ->orderby("created_at", "desc")/*  Ordena por data: do mais recente para o mais antigo */
            ->first(); /* Seleciona o registo mais recente */

            if ($passaporte!=null) {
                $passaporteData = json_decode($passaporte->info);
            } else {
                $passaporteData=null;
            }

            /* listas de campos especificos disponiveis */
            $instituicoes = array_unique(Cliente::pluck('nomeInstituicaoOrigem')->toArray());
            $cidadesInstituicoes = array_unique(Cliente::pluck('cidadeInstituicaoOrigem')->toArray());

            /* Se for o administrador a editar */
            if (Auth::user()->tipo == "admin") {
                $agents = Agente::where("tipo", "Agente")->get();
                $subAgentes = Agente::where("tipo", "Subagente")->get();
                return view('clients.edit', compact('client', 'agents', 'subAgentes', 'docOfficial', 'passaporte', 'passaporteData', 'instituicoes', 'cidadesInstituicoes'));
            }

            /* Se for o agente a editar */
            if (Auth::user()->tipo == "agente") {
                if ($client->editavel == 1) {
                    /* SE TIVER PERMISSÔES para alterar informação */
                    return view('clients.edit', compact('client', 'docOfficial', 'passaporte', 'passaporteData', 'instituicoes', 'cidadesInstituicoes'));
                } else {
                    /* SE NÃO TIVER PERMISSÕES para alterar informação */
                    return Redirect::route('clients.show', $client);
                }
            }
        } else {
            abort(403);
        }
    }

    public function update(UpdateClienteRequest $request, Cliente $client)
    {
        $produts = null;
        $permissao = false;
        if (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Agente') {
            $produts = Produto::whereRaw('idAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        } elseif (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null && Auth()->user()->agente->tipo == 'Subagente') {
            $produts = Produto::whereRaw('idSubAgente = '.Auth()->user()->idAgente.' and idCliente = '.$client->idCliente)->get();
        }
        if ($produts && $client->editavel) {
            $permissao = true;
        }

        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) || $permissao) {
            $fields = $request->validated();
            $client->fill($fields);

            /* (Tratamento de strings, casos especificos) */
            $client->nomeInstituicaoOrigem = ucwords(mb_strtolower($request->nomeInstituicaoOrigem, 'UTF-8'));
            $client->cidadeInstituicaoOrigem = ucwords(mb_strtolower($request->cidadeInstituicaoOrigem, 'UTF-8'));

            if ($request->input('deletePhoto') && $client->fotografia) {
                Storage::disk('public')->delete('client-documents/'.$client->idCliente.'/'.$client->fotografia);
                $client->fotografia = null;
            }

            /* Verifica se existem ficheiros antigos e apaga do storage*/
            $oldfile = Cliente::where('idCliente', $client->idCliente)->first();

            /* Fotografia do cliente */
            if ($request->hasFile('fotografia')) {
                /* Verifica se o ficheiro antigo existe e apaga do storage*/
                if (Storage::disk('public')->exists('client-documents/'.$client->idCliente.'/'. $oldfile->fotografia)) {
                    Storage::disk('public')->delete('client-documents/'.$client->idCliente.'/'. $oldfile->fotografia);
                }
                $photo = $request->file('fotografia');
                $profileImg = $client->idCliente .'.'. $photo->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('client-documents/'.$client->idCliente.'/', $photo, $profileImg);
                $client->fotografia = $profileImg;
            }

            if ($client->nomeInstituicaoOrigem == "") {
                $client->nomeInstituicaoOrigem = null;
            }

            if ($client->cidadeInstituicaoOrigem == "") {
                $client->cidadeInstituicaoOrigem = null;
            }

            if ($client->nivEstudoAtual == "") {
                $client->nivEstudoAtual = null;
            }

            $strpadIdCliente = str_pad($client->idCliente, 3, "0", STR_PAD_LEFT);
            $refClient = strtoupper($client->refCliente.'.'.$strpadIdCliente);
            $client->refCliente = $refClient;
            $client->save();


            /* Obtem o DOCpessoal do tipo "Doc. Oficial"  */
            $doc_id = DocPessoal::
            where("idCliente", $client->idCliente)
            ->where("tipo", "Doc. Oficial")
            ->orderby("created_at", "desc")/*  Ordena por data: do mais recente para o mais antigo */
            ->first(); /* Seleciona o registo mais recente */

            /* Constroi a informação adicional para documento de ID */
            $infoDocId = null;
            $infoDocId['numDoc'] = $request->num_docOficial;

            /* Se o Documento de identificação pessoal ainda nao foi criado, cria um novo */
            if ($doc_id == null) {
                $doc_id = new DocPessoal;
                $doc_id->idCliente = $client->idCliente;
                $doc_id->tipo = "Doc. Oficial";
                $doc_id->idFase = null;
                $doc_id->info = json_encode($infoDocId);
                $doc_id->dataValidade = $request->validade_docOficial;
                $doc_id->save();
            }else {
                $doc_id->idCliente = $client->idCliente;
                $doc_id->tipo = "Doc. Oficial";
                $doc_id->idFase = null;
                $doc_id->info = json_encode($infoDocId);
                $doc_id->dataValidade = $request->validade_docOficial;
                $doc_id->save();
            }

            /* Documento de identificação pessoal: Tem imagem?? */
            if ($request->hasFile('img_docOficial')) {

                /* Verifica se já existe DocPessoal e respectiva imagem. Se existir ficheiro novo, apaga o antigo*/
                if ($doc_id) {
                    if (Storage::disk('public')->exists('client-documents/'.$client->idCliente.'/'. $doc_id->imagem)) {
                        Storage::disk('public')->delete('client-documents/'.$client->idCliente.'/'. $doc_id->imagem);
                    }
                }

                /* Imagem do documento de identificação Pessoal*/
                $img_doc = $request->file('img_docOficial');

                $nome_imgDocOff = 'cliente_'.$client->idCliente.'_documento_pessoal_Doc_Oficial'.'.'.$img_doc->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('client-documents/'.$client->idCliente.'/', $img_doc, $nome_imgDocOff);
                $doc_id->imagem = $nome_imgDocOff;
                /* Guarda documento de identificação Pessoal */
                $doc_id->imagem = $nome_imgDocOff;

                /* Se for o admin a inserir o ficheiro, é marcado como valido */
                if (Auth::user()->tipo == "admin") {
                    $doc_id->verificacao=true;
                }
                $doc_id->save();
            }

            /* Imagem do passaporte */
            $passaporte = DocPessoal::
            where("idCliente", $client->idCliente)
            ->where("tipo", "Passaporte")
            ->orderby("created_at", "desc")/*  Ordena por data: do mais recente para o mais antigo */
            ->first(); /* Seleciona o registo mais recente */

            /* Constroi a informação adicional para o passaporte */
            $infoPassaporte = null;
            $infoPassaporte['numPassaporte'] = $request->numPassaporte;
            $infoPassaporte['dataValidPP'] = $request->dataValidPP;
            $infoPassaporte['passaportPaisEmi'] =$request->passaportPaisEmi;
            $infoPassaporte['localEmissaoPP'] = $request->localEmissaoPP;

            /* Se não existir, cria o registo */
            if ($passaporte==null) {
                $passaporte = new DocPessoal;
                $passaporte->idCliente = $client->idCliente;
                $passaporte->tipo = "Passaporte";
                $passaporte->idFase = null;
                $passaporte->info = json_encode($infoPassaporte);
                $passaporte->dataValidade = $request->validade_docOficial;
                $passaporte->save();
            } else {
                $passaporte->idCliente = $client->idCliente;
                $passaporte->tipo = "Passaporte";
                $passaporte->idFase = null;
                $passaporte->info = json_encode($infoPassaporte);
                $passaporte->dataValidade = $request->dataValidPP;
                $passaporte->save();
            }

            /* Tem imagem do passaporte ?? */
            if ($request->hasFile('img_Passaporte')) {
                    /* Verifica se já existe DocPessoal:passaporte e respectiva imagem. Se existir ficheiro novo, apaga o antigo*/
                if ($passaporte) {
                    if (Storage::disk('public')->exists('client-documents/'.$client->idCliente.'/'. $passaporte->imagem)) {
                        Storage::disk('public')->delete('client-documents/'.$client->idCliente.'/'. $passaporte->imagem);
                    }
                }
                /* Imagem do documento de identificação Pessoal*/
                $img_passaport = $request->file('img_Passaporte');

                $nome_imgPassaporte = 'cliente_'.$client->idCliente.'_documento_pessoal_Passaporte'.'.'.$img_passaport->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('client-documents/'.$client->idCliente.'/', $img_passaport, $nome_imgPassaporte);
                $passaporte->imagem = $nome_imgPassaporte;

                /* Guarda o passaporte */
                $passaporte->imagem = $nome_imgPassaporte;

                /* Se for o admin a inserir o ficheiro, é marcado como valido */
                if (Auth::user()->tipo == "admin") {
                    $passaporte->verificacao=true;
                }
                $passaporte->save();
            }
            return redirect()->route('clients.show', $client)->with('success', 'Dados do estudante modificados com sucesso!');
        } else {
            abort(403);
        }
    }

    public function destroy(Cliente $client)
    {
        if (Auth::user()->tipo == "admin" && Auth()->user()->idAdmin != null) {
            $client->delete();
            return redirect()->route('clients.index')->with('success', 'Estudante eliminado com sucesso!');
        } else {
            abort(403);
        }
    }

    public function searchIndex()
    {
        if (Auth::user()->tipo == "admin" && Auth()->user()->idAdmin != null) {
            $paises = array_unique(Cliente::pluck('paisNaturalidade')->toArray());
            $cidadesOrigem = array_unique(Cliente::pluck('cidade')->toArray());
            $instituicoesOrigem = array_unique(Cliente::pluck('nomeInstituicaoOrigem')->toArray());
            $agents= Agente::all();
            $universidades = Universidade::all();
            return view('clients.search', compact('paises', 'cidadesOrigem', 'instituicoesOrigem', 'agents', 'universidades'));
        } else {
            abort(403);
        }
    }

    public function searchResults(Request $request)
    {
        if (Auth::user()->tipo == "admin" && Auth()->user()->idAdmin != null) {
            request()->all();
            $clients= null;
            $nomeCampo= $request->search_options;
            switch ($nomeCampo) {
                case "País de origem":
                    $clients= Cliente::where("paisNaturalidade", $request->paisNaturalidade)->get();
                    $valor=$request->paisNaturalidade;
                break;

                case "Cidade de origem":
                    $clients= Cliente::where("cidade", $request->cidade)->get();
                    $valor=$request->cidade;
                break;

                case "Instituição de origem":
                    $clients= Cliente::where("nomeInstituicaoOrigem", $request->nomeInstituicaoOrigem)->get();
                    $valor=$request->nomeInstituicaoOrigem;
                break;

                case "Agente":
                    $clients= Cliente::where("idAgente", $request->agente)->get();
                    $valor = Agente:: where("idAgente", $request->agente)->first();
                    $valor = $valor->nome.' '.$valor->apelido;
                break;

                case "Universidade":
                    $clients = Cliente::distinct('Cliente.idCliente')
                    ->join('Produto', 'Produto.idCliente', 'Cliente.idCliente')
                    ->where('Produto.idUniversidade1', $request->universidade)
                    ->orWhere('Produto.idUniversidade2', $request->universidade)
                    ->select('Cliente.*')
                    ->get();
                    $valor=$request->universidade;
                break;

                case "Nível de estudos":
                    $clients= Cliente::where("nivEstudoAtual", $request->nivelEstudos)->get();
                    $valor=$request->nivelEstudos;
                break;

                case "Estado de cliente":
                    $clients= Cliente::where("estado", $request->estado)->get();
                    $valor=$request->estado;
                break;
            }

            $paises = array_unique(Cliente::pluck('paisNaturalidade')->toArray());
            $cidadesOrigem = array_unique(Cliente::pluck('cidade')->toArray());
            $instituicoesOrigem = array_unique(Cliente::pluck('nomeInstituicaoOrigem')->toArray());
            $agents= Agente::all();
            $universidades = Universidade::all();

            return view('clients.search', compact('clients', 'nomeCampo', 'valor', 'paises', 'cidadesOrigem', 'instituicoesOrigem', 'agents', 'universidades'));
        } else {
            abort(403);
        }
    }

    public function printFinanceiro(Request $request, Cliente $client)
    {
        $produto = Produto::where("idCliente", $client->idCliente)->where("descricao", $request->produto)->with(["agente"])->first();
        $fasesCobrancas = Fase::where("idProduto", $produto->idProduto)->get();
        $responsabilidades = [];

        foreach ($fasesCobrancas as $fase) {
            array_push($responsabilidades, $fase->responsabilidade);
        }

        $currentdate = new DateTime();
        $pdf = PDF::loadView('clients.print', ['produto' => $produto, 'fases' => $fasesCobrancas, 'cliente' => $client, 'responsabilidades' => $responsabilidades, 'currentdate' => $currentdate])->setPaper('a4', 'portrait');
        return $pdf->stream();
    }

    public function storeObservacoes(Request $request, Cliente $client)
    {
        $obsCliente = new ClienteObservacoes;
        $obsCliente->idCliente = $client->idCliente;
        $obsCliente->titulo = $request->titulo;
        $obsCliente->texto = $request->texto;
        $obsCliente->save();
        return redirect()->route('clients.show', $client)->with('success', 'Observação adicionada com sucesso!');
    }

    public function deleteObservacoes(Request $request, ClienteObservacoes $obsCliente, Cliente $client)
    {
        $obsCliente->delete();
        return redirect()->route('clients.show', $client)->with('success', 'Observação eliminada com sucesso!');
    }

    public function editObservacoes(Request $request, ClienteObservacoes $obsCliente, Cliente $client)
    {
        $obsCliente->titulo = $request->titulo;
        $obsCliente->texto = $request->texto;
        $obsCliente->save();
        return redirect()->route('clients.show', $client)->with('success', 'Observação editada com sucesso!');
    }
}
