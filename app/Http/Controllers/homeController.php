<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Venta;
use App\Models\Compra;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class homeController extends Controller
{
    public function index(){
        if(!Auth::check()){
            return view('welcome');
        }


        //graficas para ventas
        $ventas=Venta::all()->count();
        $ventasData = Venta::select([
            DB::raw('DAY(fecha_hora) as vdayfecha'),
            DB::raw('MONTH(fecha_hora) as vmonthfecha'),
            DB::raw('YEAR(fecha_hora) as vyearfecha'),
            DB::raw('COUNT(*) as vtotal')
        ])
        ->groupBy('vdayfecha','vmonthfecha','vyearfecha')
        ->orderBy('vdayfecha','asc')
        ->get();  
        //arrays 
        foreach($ventasData as $venta) {
            $vdayfecha[] = $venta->vdayfecha;
            $vmonthfecha[] = $venta->vmonthfecha;
            $vyearfecha[] = $venta->vyearfecha;
            $vtotal[] = $venta->vtotal;
        }

        //formato para charts js
        $ventaLabel = "'Historial de ventas'";
        $ventaDFecha = implode(',', $vdayfecha);
        $ventaMFecha = implode(',', $vmonthfecha);
        $ventaYFecha = implode(',', $vyearfecha);
        $ventaTotal = implode(',',$vtotal);



        //graficas para compras
        $compras=Compra::all()->count();
        $comprasData = Compra::select([
            DB::raw('DAY(fecha_hora) as cdayfecha'),
            DB::raw('MONTH(fecha_hora) as cmonthfecha'),
            DB::raw('YEAR(fecha_hora) as cyearfecha'),
            DB::raw('COUNT(*) as ctotal') 
        ])
        ->groupBy('cdayfecha','cmonthfecha','cyearfecha')
        ->orderBy('cdayfecha','asc')
        ->get();  

      
        //arrays 
        foreach($comprasData as $compra) {
            $cdayfecha[] = $compra->cdayfecha;
            $cmonthfecha[] = $compra->cmonthfecha;
            $cyearfecha[] = $compra->cyearfecha;
            $ctotal[] = $compra->ctotal;
        }

        //formato para charts js
        $compraLabel = "'Historial de compras'";
        $compraDFecha = implode(',', $cdayfecha,);
        $compraMFecha = implode(',', $cmonthfecha,);
        $compraYFecha = implode(',', $cyearfecha,);
        $compraTotal = implode(',',$ctotal);



        

        //grafica para productos 
        $productos = Producto::all()->count();
        $productoData = Producto::select([
            DB::raw('(nombre) as nombre'),
            DB::raw('(stock) as stock'),
        ])
        ->groupBy('nombre','stock')
        ->orderBy('nombre','asc')
        ->get(); 
        
        //preparar el array
        foreach($productoData as $producto){
            $productoNombre[] ="'".$producto->nombre."'";
            $productoTotal[] = $producto->stock;
        }
        

        //formato para charts js
        $productoLabel = implode(',', $productoNombre);
        $productoTotal = implode(',',$productoTotal);

        return view('panel.index', 
        compact('ventas','ventaLabel','ventaDFecha','ventaMFecha','ventaYFecha','ventaTotal', 'compras','compraLabel','compraDFecha','compraMFecha','compraYFecha','compraTotal','productoLabel','productoTotal')
    );
    }

}
