<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaMayorRequest;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Producto;
use App\Models\VentaMayor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ventaMayorController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-ventaMayor|crear-ventaMayor|mostrar-ventaMayor|eliminar-ventaMayor', ['only' => ['index']]);
        $this->middleware('permission:crear-ventaMayor', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-ventaMayor', ['only' => ['show']]);
        $this->middleware('permission:eliminar-ventaMayor', ['only' => ['destroy']]);
    }
    /** 
     * Display a listing of the resource.
     */
    public function index()
    {
        $ventasMayor = VentaMayor::with(['comprobante','cliente.persona','user'])
        ->where('estado',1)
        ->latest()
        ->get();

        // dd($ventasMayor);

        return view('ventaMayor.index',compact('ventasMayor'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $subquery = DB::table('compra_producto')
            ->select('producto_id', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('producto_id');

        $productos = Producto::join('compra_producto as cpr', function ($join) use ($subquery) {
            $join->on('cpr.producto_id', '=', 'productos.id')
                ->whereIn('cpr.created_at', function ($query) use ($subquery) {
                    $query->select('max_created_at')
                        ->fromSub($subquery, 'subquery')
                        ->whereRaw('subquery.producto_id = cpr.producto_id');
                });
        })
            ->select('productos.nombre', 'productos.id', 'productos.stock', 'cpr.precio_ventaMayor')
            ->where('productos.estado', 1)
            ->where('productos.stock', '>', 0)
            ->get();

        $clientes = Cliente::whereHas('persona', function ($query) {
            $query->where('estado', 1);
        })->get();
        $comprobantes = Comprobante::all();

        return view('ventaMayor.create', compact('productos', 'clientes', 'comprobantes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVentaMayorRequest $request)
    {
        // dd($request->all(), $request->validated());
        try{
            DB::beginTransaction();

            //Llenar mi tabla venta
            $ventaMayor = VentaMayor::create($request->validated());

            // dd($ventaMayor);

            //Llenar mi tabla ventaMayor_producto
            //1. Recuperar los arrays
            $arrayProducto_id = $request->get('arrayidproducto');
            $arrayCantidad = $request->get('arraycantidad');
            $arrayPrecioVentaMayor = $request->get('arrayprecioventaMayor');
            $arrayDescuento = $request->get('arraydescuento');

            //2.Realizar el llenado
            $siseArray = count($arrayProducto_id);
            $cont = 0;

            // dd("get values");
            
            while($cont < $siseArray){
                // dd("pre sync");
                $ventaMayor->productos()->syncWithoutDetaching([
                    $arrayProducto_id[$cont] => [
                        'cantidad' => $arrayCantidad[$cont],
                        'precio_ventaMayor' => $arrayPrecioVentaMayor[$cont],
                        'descuento' => $arrayDescuento[$cont]
                    ]
                ]);

                // dd("sync");

                //Actualizar stock
                $producto = Producto::find($arrayProducto_id[$cont]);
                $stockActual = $producto->stock;
                $cantidad = intval($arrayCantidad[$cont]);

                // dd("pre update");
                DB::table('productos')
                ->where('id',$producto->id)
                ->update([
                    'stock' => $stockActual - $cantidad
                ]);

                $cont++; 
            }

            DB::commit();
        }catch(Exception $e){
         //   dd($e);
            DB::rollBack();
        }

        return redirect()->route('ventasMayor.index')->with('success','Venta exitosa');
    }

    /**
     * Display the specified resource.
     */
    // public function show(VentaMayor $ventaMayor)
    public function show(Request $request)
    {
      //  dd($request);
        return view('ventaMayor.show',compact('ventaMayor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        VentaMayor::where('id',$id)
        ->update([
            'estado' => 0
        ]);

        return redirect()->route('ventasMayor.index')->with('success','Venta eliminada');
    }
}
