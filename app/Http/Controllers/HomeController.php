<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use View;
use Session;
use DB;
use Carbon\Carbon;
use App;

class HomeController extends Controller
{
  public function getHome()
  {
    $usuario = UserController::checkLogin();

    if($usuario) {
      setlocale(LC_ALL, "pt_BR", "pt_BR.iso-8859-1", "pt_BR.utf8", "portuguese");
	    date_default_timezone_set('America/Sao_Paulo');
      $data = getdate();

      $data['weekday'] = ucfirst(strftime("%A"));
      $data['month'] = ucfirst(strftime("%B"));

      Session::forget("menu");

      $mes = date("m"); // mes atual
      $ano = date("Y"); // ano atual

      if(Session::get('nivel') == 1) { // Se for administrador

        // usuários com mais reservas

        $usuMesAtual = DB::table('tb_usuario')->join("tb_alocacao", "tb_usuario.usuId", "=", "tb_alocacao.usuId")
                                                ->select(DB::raw('usuNome as nome, count(tb_usuario.usuID) as qtd'))
                                                ->where(DB::raw("month(STR_TO_DATE(aloData, '%d/%m/%y'))"), $mes)
                                                ->where(DB::raw("year(STR_TO_DATE(aloData, '%d/%m/%y'))"), $ano)
                                                ->groupby("tb_usuario.usuId")
                                                ->orderby(DB::raw("count(tb_usuario.usuID)"), "desc")
                                                ->take(3)
                                                ->get();

        // recursos com mais reservas
        $recMesAtual = DB::table('tb_equipamento')->join("tb_alocacao", "tb_equipamento.equId", "=", "tb_alocacao.equId")
                                                  ->select(DB::raw('equNome as nome, count(tb_equipamento.equID) as qtd'))
                                                  ->where(DB::raw("month(STR_TO_DATE(aloData, '%d/%m/%y'))"), $mes)
                                                  ->where(DB::raw("year(STR_TO_DATE(aloData, '%d/%m/%y'))"), $ano)
                                                  ->groupby("tb_equipamento.equId")
                                                  ->orderby(DB::raw("count(tb_equipamento.equID)"), "desc")
                                                  ->take(3)
                                                  ->get();
        // Mês anterior
        --$mes;
        if($mes == 0) {
          $mes = 12;
          --$ano;
        }

        $usuMesAnterior = DB::table('tb_usuario')->join("tb_alocacao", "tb_usuario.usuId", "=", "tb_alocacao.usuId")
                                                ->select(DB::raw('usuNome as nome, count(tb_usuario.usuID) as qtd'))
                                                ->where(DB::raw("month(STR_TO_DATE(aloData, '%d/%m/%y'))"), $mes)
                                                ->where(DB::raw("year(STR_TO_DATE(aloData, '%d/%m/%y'))"), $ano)
                                                ->groupby("tb_usuario.usuId")
                                                ->orderby(DB::raw("count(tb_usuario.usuID)"), "desc")
                                                ->take(3)
                                                ->get();

      // recurso com mais reservas
      $recMesAnterior = DB::table('tb_equipamento')->join("tb_alocacao", "tb_equipamento.equId", "=", "tb_alocacao.equId")
                                                   ->select(DB::raw('equNome as nome, count(tb_equipamento.equID) as qtd'))
                                                   ->where(DB::raw("month(STR_TO_DATE(aloData, '%d/%m/%y'))"), $mes)
                                                   ->where(DB::raw("year(STR_TO_DATE(aloData, '%d/%m/%y'))"), $ano)
                                                   ->groupby("tb_equipamento.equId")
                                                   ->orderby(DB::raw("count(tb_equipamento.equID)"), "desc")
                                                   ->take(3)
                                                   ->get();

      // alocações nos últimos meses
      $uso = DB::table('tb_alocacao')->select(DB::raw("MONTH(STR_TO_DATE(aloData, '%d/%m/%y')) as mes, YEAR(STR_TO_DATE(aloData, '%d/%m/%y')) as ano, count(aloData) as qtd"))
                                     ->where(DB::raw("STR_TO_DATE(aloData, '%d/%m/%y')"), ">=", DB::raw("DATE_SUB(CURDATE(), INTERVAL 6 MONTH)"))
                                     ->where(DB::raw("STR_TO_DATE(aloData, '%d/%m/%y')"), "<=" , DB::raw("LAST_DAY(now())"))
                                     ->groupby("mes")
                                     ->orderby("ano")
                                     ->orderby("mes")
                                     ->get();

      $recUso = DB::select("select count(aloData) as qtd, equNome as nome from tb_alocacao natural join tb_equipamento where STR_TO_DATE(aloData, '%d/%m/%y') >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) and STR_TO_DATE(aloData, '%d/%m/%y') <= now() group by nome;");
      //$recSazonal = DB::select("select month(STR_TO_DATE(aloData, '%d/%m/%y')) as mes, year(STR_TO_DATE(aloData, '%d/%m/%y')) as ano, count(*) as qtd, equNome as equipamento from tb_alocacao natural join tb_equipamento where STR_TO_DATE(aloData, '%d/%m/%y') between DATE_SUB(CURDATE(), INTERVAL 6 MONTH) and curdate() group by ano, mes, equId;");
      return View::make("admin.dashboard")->with(["recUso" => $recUso ,'uso' => $uso, 'usuariosAtual' => $usuMesAtual, 'usuariosAnterior' => $usuMesAnterior, 'recursosAtual' => $recMesAtual, 'recursosAnterior' => $recMesAnterior ,'data' => $data, "page_title" => "Início", "page_description" => "Bem-vindo"]);
      } // fim widgets de administração
      else {
        // proximas reservas
        $proximasReservas = DB::table("tb_alocacao")->join("tb_equipamento", "tb_equipamento.equId", "=", "tb_alocacao.equId")
                                                    ->select("equNome as nome", DB::raw("date_format(STR_TO_DATE(aloData, '%d/%m/%y'), '%d de %M de %Y') as data"), "aloAula as aula")
                                                    ->where('tb_alocacao.usuId', Session::get("id"))
                                                    ->where(DB::raw("STR_TO_DATE(aloData, '%d/%m/%y')"), '>=', DB::raw("curdate()"))
                                                    ->orderby('data', 'asc')
                                                    ->take(8)
                                                    ->get();

        // minhas reservas frequentes
        $reservasFrequentes = DB::table("tb_alocacao")->join("tb_equipamento", "tb_equipamento.equId", "=", "tb_alocacao.equId")
                                                      ->select("equNome as equipamentoNOME", "tb_alocacao.equId as equipamentoID", 'aloAula as aula', DB::raw('count(*) as qtd'))
                                                      ->where('tb_alocacao.usuId', Session::get("id"))
                                                      ->groupby('equipamentoNOME')
                                                      ->groupby('aula')
                                                      ->orderby("qtd", "desc")
                                                      ->take(5)
                                                      ->get();
        // recurso que mais aloquei esse mes
        $recursoMaisAlocadoMesAtual = DB::table('tb_equipamento')->join("tb_alocacao", "tb_equipamento.equId", "=", "tb_alocacao.equId")
                                                                 ->select(DB::raw('equNome as nome, count(tb_equipamento.equID) as qtd'))
                                                                 ->where(DB::raw("month(STR_TO_DATE(aloData, '%d/%m/%y'))"), $mes)
                                                                 ->where(DB::raw("year(STR_TO_DATE(aloData, '%d/%m/%y'))"), $ano)
                                                                 ->where('usuId', Session::get("id"))
                                                                 ->groupby("tb_equipamento.equId")
                                                                 ->orderby(DB::raw("count(tb_equipamento.equID)"), "desc")
                                                                 ->first();
        //reservas ativas esse mes
        $reservasMes = DB::table('tb_alocacao')->select(DB::raw('count(*) as qtd'))
                                               ->where(DB::raw("month(STR_TO_DATE(aloData, '%d/%m/%y'))"), $mes)
                                               ->where(DB::raw("year(STR_TO_DATE(aloData, '%d/%m/%y'))"), $ano)
                                               ->where('usuId', Session::get('id'))
                                               ->first();

        --$mes;
        if($mes == 0) {
          $mes = 12;
          --$ano;
        }
        // reservas ativas messado
        $reservasMesPassado = DB::table('tb_alocacao')->select(DB::raw('count(*) as qtd'))
                                                      ->where(DB::raw("month(STR_TO_DATE(aloData, '%d/%m/%y'))"), $mes)
                                                      ->where(DB::raw("year(STR_TO_DATE(aloData, '%d/%m/%y'))"), $ano)
                                                      ->where('usuId', Session::get('id'))
                                                      ->first();

        $recursoMaisAlocadoMesPassado = DB::table('tb_equipamento')->join("tb_alocacao", "tb_equipamento.equId", "=", "tb_alocacao.equId")
                                                                   ->select(DB::raw('equNome as nome, count(tb_equipamento.equID) as qtd'))
                                                                   ->where(DB::raw("month(STR_TO_DATE(aloData, '%d/%m/%y'))"), $mes)
                                                                   ->where(DB::raw("year(STR_TO_DATE(aloData, '%d/%m/%y'))"), $ano)
                                                                   ->where('usuId', Session::get("id"))
                                                                   ->groupby("tb_equipamento.equId")
                                                                   ->orderby(DB::raw("count(tb_equipamento.equID)"), "desc")
                                                                   ->first();

        return View::make('admin.dashboard')->with(['recursoAtual' => $recursoMaisAlocadoMesAtual, 'reservasAtual' => $reservasMes, 'reservasAnterior' => $reservasMesPassado, 'recursoAnterior' => $recursoMaisAlocadoMesPassado ,'data' => $data, 'proximas' => $proximasReservas, 'frequentes' => $reservasFrequentes, "page_title" => "Início", "page_description" => "Bem-vindo"]);
      }


    }
    else return $this->getLogin();
  }

  public function getLogin()
  {
    return View::make("login");
  }

  public function getSobre()
  {
    Session::forget("menu");
    return View::make("about")->with(["page_title" => "<i class='fa fa-question-circle'></i> Sobre", 'page_description' => 'Detalhes sobre o sistema']);
  }
}
