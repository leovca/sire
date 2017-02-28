@extends('layout.base')

@section('title')
    @if(Route::is('showAllocatedAssetBoard'))
        Quadro de Reservas
    @else
        Alocar Recurso
    @endif
@endsection

@section('page_icon')
    @if(Route::is('showAllocatedAssetBoard'))
        <i class="fa fa-calendar-check-o"></i>
    @else
        <i class="fa fa-calendar-plus-o"></i>
    @endif
@endsection

@section('description')
    @if(Route::is('showAllocatedAssetBoard'))
        Esse é o quadro contendo todas as reservas.
    @else
        Selecione os horários em que deseja alocar ou desalocar o recurso.
    @endif
@endsection

@section('content')
    <div class='row'>
        <div class='col-md-10 col-md-offset-1 text-center'>
            <h2>{!! $recurso->nome !!}</h2>
            <br />

            @if(!Route::is('showAllocatedAssetBoard'))
                <form class="form" action="{{ route('storeAllocation') }}" method="post" >
                    {{csrf_field()}}
                    <input type="hidden" name="id" value="{{ $recurso->id }}" />
            @endif

            @for($i=0; $i < 3; ++$i)
                <div class="box box-primary-ufop collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="text-center">
                            <a href="#" style="color: black;" data-widget="collapse">
                                @if($i == 0)
                                    <i class="fa fa-sun-o"></i> Turno Matutino
                                    @php
                                        $qtdAulas = $regras->quantidade_horarios_matutino;
                                        $turno = 'm';
                                        $inicio = $regras->horario_inicio_matutino;
                                    @endphp
                                @elseif($i == 1)
                                    <i class="fa fa-cloud"></i> Turno Vespertino
                                    @php
                                        $qtdAulas = $regras->quantidade_horarios_vespertino;
                                        $turno = 'v';
                                        $inicio = $regras->horario_inicio_vespertino;
                                    @endphp
                                @else
                                    <i class="fa fa-moon-o"></i> Turno Noturno
                                    @php
                                        $qtdAulas = $regras->quantidade_horarios_noturno;
                                        $turno = 'n';
                                        $inicio = $regras->horario_inicio_noturno;
                                    @endphp
                                @endif
                            </a>
                        </h3>
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                        </div><!-- /.box-tools -->
                    </div>

                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover text-center">
                                <tr>
                                    <th>Horário</th>

                                    {{-- Coloca no cabeçalho da tabela todas as datas com o nome do dia --}}
                                    @for($d=0; $d < count($datas); $d++)
                                        <th>
                                            <?php setlocale(LC_ALL, "pt_BR", "pt_BR.iso-8859-1", "pt_BR.utf8", "portuguese") ?>
                                            {!! ucfirst(strftime("%A", DateTime::createFromFormat('d/m/Y', $datas[$d])->getTimestamp())) !!} ({{ $datas[$d] }})
                                        </th>
                                    @endfor
                                </tr>
                                {{-- Para cada dia da semana --}}

                                {{-- Inicialização das variáveis para definição de horário --}}
                                @php
                                    $intervalo = 0;
                                    $addTime = 0;
                                    $initTime = strtotime($inicio);
                                @endphp

                                @for($j=1; $j <= $qtdAulas; ++$j)
                                    <tr>
                                        {{-- Adicionar o addTime ao tempo de inicio--}}
                                        <?php $endTime = strtotime("+50 minutes", $initTime); ?>

                                        <td>{{ date('H:i', $initTime) }} - {{ date('H:i', $endTime) }}</td>

                                        @php
                                            $initTime = $endTime;
                                            ++$intervalo;

                                            if(($intervalo % 2) == 0) $initTime = strtotime("+20 minutes", $initTime); // Adicionar 20 minutos de intervalo a cada duas aulas
                                            if($intervalo == 4 && $turno == 'v') $initTime = strtotime("-15 minutes", $initTime); // exceto para o horário entre o intervalo de 1h do turno vespertino e noturno
                                        @endphp

                                        @for($k=0; $k < $quantidadeDias; ++$k)
                                            <?php $status = false; ?>

                                            {{-- Verifica se alguma alocacao pertence aquele horario daquele turno  --}}
                                            <td>
                                                @foreach($reservas as $reserva)

                                                    {{-- Se for igual então está reservado --}}
                                                    @if($reserva->horario == $j && $reserva->turno == $turno && $reserva->data == \Carbon\Carbon::now()->addDays($k)->format('Y-m-d'))

                                                        {{-- Tratamento para não motrar o e-mail e nome dos administradores --}}
                                                        @if($reserva->usuario->nivel == 1)
                                                            @php
                                                                $reserva->usuario->email = "suporteinformatica@decea.ufop.br";
                                                                $reserva->usuario->nome = "NTI";
                                                            @endphp
                                                        @endif

                                                        <a target="_blank" href="mailto:{!! $reserva->usuario->email !!}?subject=[UFOP-ICEA] Alocação do recurso {!! $recurso->nome !!}">{!! $reserva->usuario->nome !!}</a>

                                                        @if(!Route::is('showAllocatedAssetBoard'))
                                                            @if(auth()->user()->isAdmin() || $reserva->usuario->cpf == auth()->user()->cpf)
                                                                <br />
                                                                <a class="btn btn-warning btn-xs" style="color: white" href="{{ route('deleteAllocation', $reserva->id) }}"><i class="fa fa-times"></i> Desalocar</a>
                                                            @endif
                                                        @endif

                                                        <?php $status = true; ?>

                                                        @break
                                                    @endif
                                                @endforeach

                                                @if(!$status)
                                                    @if(Route::is('showAllocatedAssetBoard'))
                                                        <span class="text-success"><b>Livre</b></span>
                                                    @else
                                                        <input type="checkbox" name="reservas[]" value='{"horario": {{ $j }}, "turno": "{{ $turno }}", "dia": "{{ \Carbon\Carbon::now()->addDays($k)->format('Y-m-d') }}"}'> Alocar
                                                    @endif
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                @endfor
                            </table>
                        </div>
                    </div>
                </div>
            @endfor

            @if(!Route::is('showAllocatedAssetBoard'))
                    <div class="text-center">
                        <a href="{{ route('selectAllocation') }}" class="btn btn-ufop" role="button"><i class="fa fa-arrow-left"></i> Voltar</a>
                        <button type="reset" class="btn btn-warning">Limpar <i class='fa fa-eraser'></i></button>
                        <button type="button" class="btn btn-danger" onClick="history.back()">Cancelar <i class='fa fa-times'></i></button>
                        <button type="submit" class="btn btn-success">Confirmar <i class='fa fa-check'></i></button>
                    </div>
                </form>
            @endif
        </div><!-- /.col -->
    </div><!-- /.row -->
    @if(Route::is('showAllocatedAssetBoard'))
        <div class="row">
            <div class="text-center">
                <button type="button" class="btn btn-warning" onclick="history.back()"><i class="fa fa-arrow-left"></i> Voltar</button>
            </div>
        </div>
    @endif
@endsection
