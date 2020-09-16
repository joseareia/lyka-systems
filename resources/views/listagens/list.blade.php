@extends('layout.master')
<!-- Page Title -->
@section('title', 'Listagens')
<!-- Page Content -->
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0 text-gray-800">Pesquisa nas listagens</h1>
        <div>
            <a href="#" data-toggle="modal" data-target="#infoModal" class="btn btn-secondary btn-icon-split btn-sm" title="Informações">
                <span class="icon text-white-50">
                    <i class="fas fa-info-circle"></i>
                </span>
                <span class="text">Informações</span>
            </a>
        </div>
    </div>
    <!-- Approach -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pesquisa avançada nas listagens da Estudar Portugal.</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="pais" class="text-gray-900">País:</label><br>
                    <select id="pais" class="form-control custom-select" onChange="GetCountries()">
                        <option value="null" selected="" hidden="">Selecione país</option>
                        <option value="null">Todos</option>
                        @if($paises)
                        @foreach($paises as $pais)
                        <option value="{{$pais}}">{{$pais}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cidade" class="text-gray-900">Cidade:</label><br>
                    <select id="cidade" class="form-control custom-select butCity" onChange="GetList()" readonly>
                        <option value="null" selected="" hidden="">Selecione cidade</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="agente" class="text-gray-900">Agente:</label><br>
                    <select id="agente" class="form-control custom-select" onChange="GetList()">
                        <option value="null" selected="" hidden="">Selecione agente</option>
                        <option value="null">Todos</option>
                        @if($agentes)
                        @foreach($agentes as $agente)
                        <option value="{{$agente->idAgente}}">{{$agente->nome.' '.$agente->apelido.' => '.$agente->email}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="subagente" class="text-gray-900">Descrição:</label><br>
                    <select id="subagente" class="form-control custom-select" onChange="GetList()">
                        <option value="null" selected="" hidden="">Selecione subagente</option>
                        <option value="null">Todos</option>
                        @if($subagentes)
                        @foreach($subagentes as $subagente)
                        <option value="{{$subagente->idAgente}}">{{$subagente->nome.' '.$subagente->apelido.' => '.$subagente->email}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-md-3">
                    <label for="universidade" class="text-gray-900">Descrição:</label><br>
                    <select id="universidade" class="form-control custom-select" onChange="GetList()">
                        <option value="null" selected="" hidden="">Selecione universidade</option>
                        <option value="null">Todos</option>
                        @if($universidades)
                        @foreach($universidades as $universidade)
                        <option value="{{$universidade->idUniversidade}}">{{$universidade->nome.' -> '.$universidade->email}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="curso" class="text-gray-900">Descrição:</label><br>
                    <select id="curso" class="form-control custom-select" onChange="GetList()">
                        <option value="null" selected="" hidden="">Selecione tipo de curso</option>
                        <option value="null">Todos</option>
                        @if($cursos)
                        @foreach($cursos as $curso)
                        <option value="{{$curso}}">{{$curso}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="institutoOrigem" class="text-gray-900">Instituto origem:</label><br>
                    <select id="institutoOrigem" class="form-control custom-select" onChange="GetList()">
                        <option value="null" selected="" hidden="">Selecione instituto origem</option>
                        <option value="null">Todos</option>
                        @if($institutos)
                        @foreach($institutos as $instituto)
                        <option value="{{$instituto}}">{{$instituto}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="atividade" class="text-gray-900">Atividade:</label><br>
                    <select id="atividade" class="form-control custom-select" onChange="GetList()">
                        <option value="null" selected="" hidden="">Selecione atividade</option>
                        <option value="null">Todos</option>
                        <option value="Ativo">Ativo</option>
                        <option value="Proponente">Proponente</option>
                        <option value="Inativo">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="lista">
                <table class="table table-bordered table-striped" id="table" width="100%">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>N.º Passaporte</th>
                            <th>País</th>
                            <th>Estado</th>
                            <th style="max-width:80px; min-width:80px;">Opções</th>
                        </tr>
                    </thead>

                    <tbody id="table-body">
                        <tr id="clonar">
                            <td class="align-middle">
                                <a class="routa-show name_link" href="#"></a>
                            </td>
                            <td class="numPassaporte align-middle"></td>
                            <td class="paisNaturalidade align-middle"></td>
                            <td class="align-middle">
                                <span class="span-estado"></span>
                            </td>
                            <td class="text-center align-middle">
                                <a href="#" class="butao-show btn btn-sm btn-outline-primary" title="Ver ficha completa"><i class="far fa-eye"></i></a>
                            </td>
                        </tr>
                        @foreach ($clientes as $cliente)
                        <tr>
                            <td class="align-middle">
                                <a class="routa-show name_link" href="{{route('clients.show',$cliente)}}">{{$cliente->nome ." ". $cliente->apelido}}</a>
                            </td>

                            <td class="numPassaporte align-middle">{{$cliente->numPassaporte}}</td>

                            <td class="paisNaturalidade align-middle">{{$cliente->paisNaturalidade}}</td>

                            <td class="align-middle">
                                <span class="span-estado">{{$cliente->estado}}</span>
                            </td>
                            <td class="text-center align-middle">
                                <a href="{{route('clients.show',$cliente)}}" class="butao-show btn btn-sm btn-outline-primary" title="Ver ficha completa"><i class="far fa-eye"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <option value="null" id="clonecity"></option>
    </div>
</div>
<!-- End of container-fluid -->

<!-- Modal for more information -->
<div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header pl-4 pb-1 pt-4">
                <h5 class="modal-title text-gray-800 font-weight-bold">Para que serve?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" id="close-button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-gray-800 pl-4 pr-5">
                Nesta secção pode fazer uma pesquisa mais profunda, para encontrar o estudante que prentede. Basta, para isso, preencher os campos que precisa.
            </div>
            <div class="modal-footer mt-3">
                <a data-dismiss="modal" class="mr-4 font-weight-bold" id="close-option">Fechar</a>
                <button type="button" data-dismiss="modal" class="btn btn-primary font-weight-bold mr-2">Entendido!</button>
            </div>
        </div>
    </div>
</div>
<!-- End of Modal for more information  -->

<!-- Begin of Scripts -->
@section('scripts')
<script>
    $(document).ready(function() {
        $('#table').DataTable({
            "language": {
                "sEmptyTable": "Não foi encontrado nenhum registo",
                "sLoadingRecords": "A carregar...",
                "sProcessing": "A processar...",
                "sLengthMenu": "Mostrar _MENU_ registos",
                "sZeroRecords": "Não foram encontrados resultados",
                "sInfo": "Mostrando _END_ de _TOTAL_ registos",
                "sInfoEmpty": "Mostrando de 0 de 0 registos",
                "sInfoFiltered": "(filtrado de _MAX_ registos no total)",
                "sInfoPostFix": "",
                "sSearch": "Procurar:",
                "sUrl": "",
                "oPaginate": {
                    "sFirst": "Primeiro",
                    "sPrevious": "Anterior",
                    "sNext": "Seguinte",
                    "sLast": "Último"
                },
                "oAria": {
                    "sSortAscending": ": Ordenar colunas de forma ascendente",
                    "sSortDescending": ": Ordenar colunas de forma descendente"
                }
            }
        });
    });

    var clone = $('#clonar').clone();
    $('#clonar').remove();
    var clonecity = $('#clonecity').clone();
    $('#clonecity').remove();

    function GetCountries() {

        GetList();
        $('.butCity').children('option:not(:first)').remove();
        var pais = null;

        if ($('#pais').val() != "null") {
            pais = $('#pais').val();
        }
        if (pais) {
            $('.butCity').attr("readonly", false);
            var link = '/../api/listagem/cidades/' + pais;
            $.ajax({
                    method: "GET",
                    url: link
                })
                .done(function(response) {
                    if (response != null) {
                        for (i = 0; i < response.results.length; i++) {
                            var CloneCidade = clonecity.clone();
                            $(CloneCidade).text(response.results[i]);
                            $(CloneCidade).attr(response.results[i]);
                            $('.butCity').append(CloneCidade);
                        }
                    }
                })
        } else {
            $('.butCity').attr("readonly", true);
        }
    }

    function GetList() {
        $('#table-body').html("");

        var lista = null;

        if ($('#pais').val() != "null") {
            lista = "pais-" + $('#pais').val();
        } else {
            lista = "pais-null";
        }

        if ($('#cidade').val() != "null") {
            lista += "_cidade-" + $('#cidade').val();
        } else {
            lista += "_cidade-null";
        }

        if ($('#agente').val() != "null") {
            lista += "_agente-" + $('#agente').val();
        } else {
            lista += "_agente-null";
        }

        if ($('#subagente').val() != "null") {
            lista += "_subagente-" + $('#subagente').val();
        } else {
            lista += "_subagente-null";
        }

        if ($('#universidade').val() != "null") {
            lista += "_universidade-" + $('#universidade').val();
        } else {
            lista += "_universidade-null";
        }

        if ($('#curso').val() != "null") {
            lista += "_curso-" + $('#curso').val();
        } else {
            lista += "_curso-null";
        }

        if ($('#institutoOrigem').val() != "null") {
            lista += "_institutoOrigem-" + $('#institutoOrigem').val();
        } else {
            lista += "_institutoOrigem-null";
        }

        if ($('#atividade').val() != "null") {
            lista += "_atividade-" + $('#atividade').val();
        } else {
            lista += "_atividade-null";
        }

        var link = '/../api/listagem/' + lista;
        $.ajax({
                method: "GET",
                url: link
            })
            .done(function(response) {
                if (response != null) {
                    for (i = 0; i < response.results.length; i++) {
                        var resultClone = clone.clone();

                        $('.routa-show', resultClone).attr('href', "clientes/" + response.results[i].slug);
                        $('.routa-show', resultClone).text(response.results[i].nome + " " + response.results[i].apelido);

                        $('.numPassaporte', resultClone).text(response.results[i].numPassaporte);

                        $('.paisNaturalidade', resultClone).text(response.results[i].paisNaturalidade);

                        if (response.results[i].estado == "Inativo") {
                            $('.span-estado', resultClone).text('Inativo');
                            $('.span-estado', resultClone).attr('class', 'span-estado text-danger');
                        } else {
                            if (response.results[i].estado == "Ativo") {
                                $('.span-estado', resultClone).text('Ativo');
                                $('.span-estado', resultClone).attr('class', 'span-estado text-success');
                            } else {
                                $('.span-estado', resultClone).text('Proponente');
                                $('.span-estado', resultClone).attr('class', 'span-estado text-info');
                            }
                        }

                        $('.butao-show', resultClone).attr('href', "clientes/" + response.results[i].slug);


                        $('#table-body').append(resultClone);
                    }
                }
            })
    }
</script>
@endsection
<!-- End of Scripts -->
@endsection
<!-- End of Page Content -->
