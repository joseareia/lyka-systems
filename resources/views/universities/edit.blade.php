@extends('layout.master')

{{-- Titulo da Página --}}
@section('title', 'Editar Universidade')

{{-- Estilos de CSS --}}
@section('styleLinks')
<link href="{{asset('css/inputs.css')}}" rel="stylesheet">
@endsection

{{-- Conteudo da Página --}}
@section('content')

<div class="container-fluid my-4">

    <div class="bg-white shadow-sm mb-4 p-4 ">


        <div class="row">

            <div class="col">
                <div class="title">
                    <h4><strong>Editar Informações da Universidade</strong></h4>
                </div>
            </div>
        </div>

        <hr>


        <form method="POST" action="{{route('universities.update',$university)}}" class="form-group needs-validation"
            id="form_university" enctype="multipart/form-data" novalidate>
            @csrf
            @method("PUT")
            @include('universities.partials.add-edit')

    </div>
    <div class="text-right mt-4">

        <button type="submit" class="btn btn-sm btn-success px-2 mr-2" name="ok" id="buttonSubmit"><i class="fas fa-check-circle mr-2"></i>Guardar Informações</button>
        <a href="{{route('universities.index')}}" class="btn btn-sm btn-secondary px-2">Cancelar</a>

        </form>
    </div>
</div>

@endsection



{{-- Scripts --}}
@section('scripts')

{{-- script contem: datatable configs, input configs, validações --}}
<script src="{{asset('/js/university.js')}}"></script>

{{-- script permite definir se um input recebe só numeros OU so letras --}}
<script src="{{asset('/js/jquery-key-restrictions.min.js')}}"></script>

<script>
    
    $(document).ready(function() {
        bsCustomFileInput.init();
        $(".needs-validation").submit(function(event) {
            var nif = $('#inputNIF').val();
            
            var link = "/api/unique/uni/{{$university->slug}}/"+nif;
            $.ajax({
                method:"GET",
                url:link
            })
            .done(function(response){
                if(response != null){
                    if(response.nif == true){
                        alert("Já existe uma universidade com esse nif");
                    }
                }
            })
        });
    });
</script>
@endsection
