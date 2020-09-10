<?php
namespace App\Http\Controllers;

use App\User;
use App\Agente;
use App\Cliente;
use App\Produto;
use App\Responsabilidade;
use Illuminate\Http\Request;
use App\Jobs\SendWelcomeEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\StoreAgenteRequest;
use App\Http\Requests\UpdateAgenteRequest;


class AgenteController extends Controller
{
    public function index()
    {
        if((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null) || (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null)){
            $agents =null;
            if(Auth()->user()->tipo == 'admin'){
                $agents = Agente::all();
            }else{
                $agents = Agente::where('idAgenteAssociado','=',Auth()->user()->idAgente)->get();
            }
            if($agents || $agents->toArray()){
                $totalagents = $agents->count();
            }else{
                $totalagents = 0;
            }
        }else{
            abort(403);
        }
    return view('agents.list', compact('agents', 'totalagents'));
    }

    public function create()
    {
        if(Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null && Auth()->user()->admin->superAdmin){
            $agent = new Agente;
            $listagents = Agente::whereNull('idAgenteAssociado')->get();
            return view('agents.add',compact('agent','listagents'));
        }else{
            abort(403);
        }
    }

    public function store(StoreAgenteRequest $requestAgent, StoreUserRequest $requestUser)
    {
        if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null && Auth()->user()->admin->superAdmin){

            /* obtem os dados para criar o agente */
            $agent = new Agente;
            $fields = $requestAgent->validated();
            $agent->fill($fields);
            if($agent->tipo == "Agente"){
                $agent->exepcao = false;
            }

            /* obtem os dados para criar o utilizador */
            $user = new User;
            $fieldsUser = $requestUser->validated();
            $user->fill($fieldsUser);

            /* Criação de SubAgente */
            $agent->idAgenteAssociado= $requestAgent->idAgenteAssociado;

            $agent->save();

            /* Fotografia do agente */
            if ($requestAgent->hasFile('fotografia')) {
                $photo = $requestAgent->file('fotografia');
                $profileImg = $agent->idAgente .'.'. $photo->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('agent-documents/'.$agent->idAgente.'/', $photo, $profileImg);
                $agent->fotografia = $profileImg;
                $agent->save();
            }



            /* Documento de identificação */
            if ($requestAgent->hasFile('img_doc')) {
                $docfile = $requestAgent->file('img_doc');
                $docImg = $agent->idAgente. '_DocID'.  '.' . $docfile->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('agent-documents/'.$agent->idAgente.'/', $docfile, $docImg);
                $agent->img_doc = $docImg;
                $agent->save();
            }



            /* Criação de utilizador */

            $user->tipo = "agente";
            $user->idAgente = $agent->idAgente;
            $user->slug = post_slug($agent->nome.' '.$agent->apelido);
            $user->auth_key = strtoupper(random_str(5));
            $password = random_str(64);
            $user->password = Hash::make($password);

            $user->save();

            /* Envia o e-mail para ativação */
            $name = $agent->nome.' '.$agent->apelido;
            $email = $agent->email;
            $auth_key = $user->auth_key;
            dispatch(new SendWelcomeEmail($email, $name, $auth_key));

            return redirect()->route('agents.index')->with('success', 'Registo criado com sucesso. Aguarda Ativação');

        }else{
            abort(403);
        }
    }


    public function show(Agente $agent)
    {
        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)||
            (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null &&
            (Auth()->user()->idAgente == $agent->idAgenteAssociado||Auth()->user()->idAgente == $agent->idAgente))){

            /* Só os administradores podem ver os perfis dos agentes */
            /* Cada agente só pode ver o seu perfil *//*
            if(Auth::user()->tipo == "agente" && Auth::user()->idAgente != $agent->idAgente){
                abor(403);
            }/** */

            /* Lista de sub-agentes do $agente */
            $listagents = Agente::
            where('idAgenteAssociado', '=',$agent->idAgente)
            ->get();

            if ($listagents->isEmpty()) {
                $listagents=null;
            }


    /*       caso seja um sub-agente, obtem o agente que o adicionou */
            if($agent->tipo=="Subagente"){
                $mainAgent=Agente::
                where('idAgente', '=',$agent->idAgenteAssociado)
                ->first();
            }else{
                $mainAgent=null;
            }

            $telefone2 = $agent->telefone2;
            $IBAN = $agent->IBAN;


            /* lista de alunos do agente Através de produtos  */
        $clients = Cliente::
            selectRaw("cliente.*")
            ->join('produto', 'cliente.idCliente', '=', 'produto.idCliente')
            ->where('produto.idAgente', '=', $agent->idAgente)
            ->orWhere('produto.idSubAgente', '=', $agent->idAgente)
            ->groupBy('cliente.idCliente')
            ->orderBy('cliente.idCliente','asc')
            ->get();


            if ($clients->isEmpty()) {
            /* lista de alunos do agente associação na ficha de cliente  */
            $clients = Cliente::
            where('idAgente', '=', $agent->idAgente)
            ->get();
            }

            if ($clients->isEmpty()) {
                $clients=null;
            }


            /* Valor total das comissões */

            /* Caso seja do tipo Agente */
            if ($agent->tipo=="Agente"){
                $comissoes = Responsabilidade::
                where('idAgente', '=', $agent->idAgente)
                ->sum('valorAgente');

            }elseif($agent->tipo=="Subagente"){
                $comissoes = Responsabilidade::
                where('idSubAgente', '=', $agent->idAgente)
                ->sum('valorSubAgente');
            }

            return view('agents.show',compact("agent" ,'listagents','mainAgent','telefone2','IBAN','clients','comissoes'));
        }else{
            abort(403);
        }

    }


   /**
    * Prepares document for printing the specified agent.
    *
    * @param  \App\Agente  $agent
    * @return \Illuminate\Http\Response
    */
    public function print(Agente $agent)
    {
        if ((Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null)||
            (Auth()->user()->tipo == 'agente' && Auth()->user()->idAgente != null &&
            (Auth()->user()->idAgente == $agent->idAgenteAssociado||Auth()->user()->idAgente == $agent->idAgente))){
            return view('agents.print',compact("agent"));
        }else{
            abor(403);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Agente  $agent
     * @return \Illuminate\Http\Response
     */
    public function edit(Agente $agent)
    {
        if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null && Auth()->user()->admin->superAdmin){
            /* lista dos agentes principais */
            $listagents = Agente::
            whereNull('idAgenteAssociado')
            ->get();

            return view('agents.edit', compact('agent','listagents'));
        }else{
            /* não tem permissões */
            abort(403);
        }
    }





    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Agente  $agent
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAgenteRequest $request, Agente $agent)
    {
        if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null && Auth()->user()->admin->superAdmin){

            $fields = $request->validated();

            $agent->fill($fields);

            /* Definição de exeçao */
            if($agent->tipo == "Agente"){
                $agent->exepcao = false;
            }

            /* Registo antigo: para verificar se existem ficheiros para apagar/substituir */
            $oldfile=Agente::
            where('idAgente', '=',$agent->idAgente)
            ->first();


            /* Fotografia */
            if ($request->hasFile('fotografia')) {

                /* Verifica se o ficheiro antigo existe e apaga do storage*/
                if(Storage::disk('public')->exists('agent-documents/'.$agent->idAgente.'/' . $oldfile->fotografia)){
                    Storage::disk('public')->delete('agent-documents/'.$agent->idAgente.'/' . $oldfile->fotografia);
                }

            /* Guarda a nova fotografia */
                $photo = $request->file('fotografia');
                $profileImg = $agent->idAgente .'.'. $photo->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('agent-documents/'.$agent->idAgente.'/', $photo, $profileImg);
                $agent->fotografia = $profileImg;
            }





            /* Documento de identificação */
            if ($request->hasFile('img_doc')) {

            /* Verifica se o ficheiro antigo existe e apaga do storage*/
            if(Storage::disk('public')->exists('agent-documents/'.$agent->idAgente.'/' . $oldfile->img_doc)){
                Storage::disk('public')->delete('agent-documents/'.$agent->idAgente.'/' . $oldfile->img_doc);
            }

                $docfile = $request->file('img_doc');
                $docImg = $agent->idAgente. '_DocID'.  '.' . $docfile->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('agent-documents/'.$agent->idAgente.'/', $docfile, $docImg);
                $agent->img_doc = $docImg;
            }


            // Caso se mude o  agente para subagente, garante que nenhum agente não tem id de subagente
            if($request->idAgenteAssociado == null){
                $agente = Agente::where('idAgente', $agent->idAgente)->get();
                $agente->idAgenteAssociado = null;
                $agente->save();
            }


            // data em que foi modificado
            $t=time();
            $agent->updated_at == date("Y-m-d",$t);
            $agent->save();

            /* update do user->email */
            $utilizador = User::where('idAgente', $agent->idAgente)->get();
            $utilizador->email = $agent->email;
            $utilizador->save();


            return redirect()->route('agents.index')->with('success', 'Dados do agente modificados com sucesso');
            }else{
                /* não tem permissões */
                abort(403);
            }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Agente  $agent
     * @return \Illuminate\Http\Response
     */
    public function destroy(Agente $agent)
    {
        if (Auth()->user()->tipo == 'admin' && Auth()->user()->idAdmin != null && Auth()->user()->admin->superAdmin){

            /* "Apaga" dos agentes */
            $agent->delete();


            /* Apaga subagentes se o seu agente for apagado */
            $subagents =Agente::where('idAgenteAssociado', $agent->idAgente)
            ->get();

            /* apaga a lista de subagentes do agente que esta a ser apagado */
            if (!$subagents->isEmpty()) {
                foreach ($subagents as $subagent) {
                    $subagent->deleted_at = $agent->deleted_at;
                    $subagent->save();
                }
            }



            /* "Apaga" dos utilizadores */
            $utilizador = User::where('idAgente', $agent->idAgente)->get();
            $utilizador->deleted_at = $agent->deleted_at;
            $utilizador->save();


            /* "Apaga" dos utilizadores os subagentes que tiveram o seu agente apagado */

            /* apaga a lista de subagentes do agente que esta a ser apagado */
            if (!$subagents->isEmpty()) {
                foreach ($subagents as $subagent) {
                    $subagent->deleted_at = $agent->deleted_at;
                    $subagent->save();
                }
            }


            return redirect()->route('agents.index')->with('success', 'Agente eliminado com sucesso');
        }else{
            abort(403);
        }
    }
}
