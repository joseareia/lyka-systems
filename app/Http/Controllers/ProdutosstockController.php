<?php
namespace App\Http\Controllers;

use App\FaseStock;
use App\ProdutoStock;
use App\TipoProduto;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreProdutosstockRequest;

class ProdutosstockController extends Controller
{
    public function __construct()
    {
        $this->middleware('superadmin');
    }

    public function index()
    {
        $produtoStocks = ProdutoStock::all();
        return view('produtostock.list', compact('produtoStocks'));
    }

    public function create()
    {
        $anosAcademicos = null;
        $anoAtual =  date("Y");

        for($i = 0; $i <= 5; $i++){
            $anosAcademicos[] = ($anoAtual-1+$i)."/".($anoAtual+$i);
        }

        $produtostock = new ProdutoStock();
        $tiposproduto = TipoProduto::all();
        return view('produtostock.add', compact('produtostock','anosAcademicos','tiposproduto'));
    }

    public function store(StoreProdutosstockRequest $requestProduto)
    {
        $produtoFields = $requestProduto->validated();
        $produtoStock = new ProdutoStock();
        $produtoStock->fill($produtoFields);
        $produtoStock->save();
        return redirect()->route('produtostock.index')->with('success', 'Produto adicionado com sucesso!');
    }

    public function edit(ProdutoStock $produtostock)
    {
        $anosAcademicos = null;
        $anoAtual =  date("Y");

        for($i = 0; $i <= 5; $i++){
            $anosAcademicos[] = ($anoAtual-1+$i)."/".($anoAtual+$i);
        }

        $tiposproduto = TipoProduto::all();

        return view('produtostock.edit', compact('produtostock', 'anosAcademicos','tiposproduto'));
    }

    public function update(StoreProdutosstockRequest $request, ProdutoStock $produtostock)
    {
        $fields = $request->validated();
        $produtostock->fill($fields);
        $produtostock->save();
        return redirect()->route('produtostock.index')->with('success', 'Produto atualizado com sucesso!');
    }

    public function show(FaseStock $faseStocks,ProdutoStock $produtostock)
    {
        $faseStocks = FaseStock::where('idProdutoStock', $produtostock->idProdutoStock)->get();
        return view('produtostock.show', compact('produtostock', 'faseStocks'));
    }

    public function destroy(ProdutoStock $produtostock)
    {
        $fasesStock = $produtostock->faseStock;
        if($fasesStock){
            foreach($fasesStock as $fasestock){
                $documentsStock = $fasestock->docStock;
                if($documentsStock){
                    foreach($documentsStock as $docstock){
                        $docstock->delete();
                    }
                }
                $fasestock->delete();
            }
        }
        $produtostock->delete();
        return redirect()->route('produtostock.index')->with('success', 'Produto eliminado com sucesso!');
    }
}
