@extends('layout.master')

{{-- Page Title --}}
@section('title', 'Editar informações')

{{-- CSS Style Link --}}
@section('styleLinks')
<link href="{{asset('css/inputs.css')}}" rel="stylesheet">
@endsection

{{-- Page Content --}}
@section('content')


<div class="container-fluid my-4">

    <div class="bg-white shadow-sm mb-4 p-4 ">


        <div class="row">

            <div class="col">
                <div class="title">
                    <h4><strong>@if ($agent->tipo=="Agente")
                        Editar Informações do Agente <span class="active">{{$agent->nome}} {{$agent->apelido}}<span>
                    @else
                        Editar Informações do Subagente <span class="active">{{$agent->nome}} {{$agent->apelido}}<span>
                    @endif</strong></h4>
                </div>
            </div>
        </div>


        <hr>

        <form method="POST" action="{{route('agents.update',$agent)}}" class="form-group needs-validation "
        id="form_agent" enctype="multipart/form-data" novalidate>
            @csrf
            @method("PUT")
            @include('agents.partials.add-edit')

    </div>

    <div class="row mt-4">
        {{-- Butões Submit / Cancelar --}}
        <div class="col text-right">
            <button type="submit" class="btn btn-sm btn-success px-2 m-1 mr-2" name="submit" id="buttonSubmit"><i class="fas fa-check-circle mr-2"></i>Guardar
                Informações</button>
            <a href="{{route('agents.index')}}" class="btn btn-sm btn-secondary m-1 px-2">Cancelar</a>
        </div>

    </div>

    </form>
</div>



@endsection


{{-- Scripts --}}
@section('scripts')

{{-- script contem: datatable configs, input configs, validações --}}
<script src="{{asset('/js/agent.js')}}"></script>

{{-- script permite definir se um input recebe só numeros OU so letras --}}
<script src="{{asset('/js/jquery-key-restrictions.min.js')}}"></script>

<script>
    
    $(document).ready(function() {
        bsCustomFileInput.init();
        $(".needs-validation").submit(function(event) {
            var nif = $('#NIF').val();
            var num_doc = $('#num_doc').val();
            var email = $('#email').val();

            var uniques = num_doc + "_" + nif + "_" + email;
            
            var link = "/api/unique/agente/{{$agent->slug}}/"+uniques;
            $.ajax({
                method:"GET",
                url:link
            })
            .done(function(response){
                if(response != null){
                    if(response.email == true){
                        alert("Já existe um agente/subagente com esse email");
                    }
                    if(response.nif == true){
                        alert("Já existe um agente/subagente com esse nif");
                    }
                    if(response.numdoc == true){
                        alert("Já existe um agente/subagente com o mesmo numero de documento");
                    }
                    if(response.user == true && response.email == false){
                        alert("Já existe um user com esse email");
                    }
                }
            })
        });
    });
</script>
@endsection
