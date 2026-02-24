<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try{
            $query = Order::with(['user', 'item_producto', 'pagos']);
            if($request->estado){
                $query->where('estado', $request->estado);

            }
            //
            if($request->user_id){
                $query->where('user_id', $request->user_id);
            }
            $orders = $query->orderBy('fecha','desc')->get();
            
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al obtener el listado de ordenes',
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            //validamos la data que viene por $request
            $data = $request->validate([
                'user_id'=> 'required|exists:users,id',
                'fecha'=> 'required|date',
                'subtotal' => 'required|numeric|min:0',
                'impuesto'=> 'required|numeric|min:0',
                'total'=> 'required|numeric|min:0',
                'items'=> 'required|array|min:1',
                'items.*.producto_id'=> 'required|exists:productos,id',
                'items.*.cantidad'=> 'required|integer|min:1'
            ]);
            DB::beginTransaction();
            $order = Order::create([
                'correlativo' => $this->generarCorrelativo(), //regenerar método
                'fecha' => $data['fecha'],
                'subtotal' => $data['subtotal'],
                'impuesto' => $data['impuesto'],
                'total'=> $data['total'],
                'estado' => 'PENDIENTE',
                'user_id'=> $data['user_id']
            ]);
            //recorremos [items] para agregar en order_items
            foreach($data['items'] as $item){
                //buscamos el producto en la tabla de productos
                $producto = Producto::findOrFail($item['producto_id']);
                $subt = $producto->precio * $item['cantidad'];
                //creamos cada OrderItem
                OrderItem::create([
                    'cantidad'=> $item['cantidad'],
                    'precio_unitario'=> $producto->precio,
                    'subtotal'=> $subt,
                    'producto_id'=> $producto->id,
                    'order_id' => $order->id
                ]);
            }
            DB::commit();


        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'message'=> 'Error al crear la orden',
                'error' => $e->getMessage()
            ],500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $order = Order::with(['user','items.producto','pagos'])->findOrFail($id);
            return response()->json($order);
        }catch(ModelNotFoundException $e){
            return response()->json([
                'message' => 'No se ha encontrado la orden con ID = ' . $id
            ],404);
        }
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
        //
    }

    //METODO PARA CAMBIAR EL ESTADO DE LA ORDEN
    public function GestionarEstado(Request $request, $id){

        try{
            //obtener la orden de la base de datos
            $order = Order::findOrFail($id);
            //validamos los valosres de los estados
            $data = $request->validate([
                'estado' => 'required|in:PENDIENTE,PAGADA,CANCELADA,REEMBOLSADA,ENTREGADA'
            ]);
            $nuevoEstado = $data['estado'];
            //obtenemos el estado actual de la order
            $estadoActual = $order->estado;
            //definimos reglas de transicion entre estados
            $trancisionesValidas = [
                'PENDIENTE' => ['CANCELADA','PAGADA'],
                'PAGADA' => ['ENTREGADA','REEMBOLSADA'],
                'CANCELADA'=> [],
                'REEMBOLSADA' => []
            ];
            if(!in_array($nuevoEstado, $trancisionesValidas[$estadoActual])){
                return response()->json([
                    'message'=> "No se puede cambiar de $estadoActual a $nuevoEstado"
                ],400);
            };
            
            //seteamos el nuevo estado al objeto $order
            $order->estado = $nuevoEstado;
            //si el etado == 'ENTREGADA', actualizar fecha_despacho
            if($nuevoEstado === 'ENTREGADA'){
                $order->fecha_despacho = now();
            };
            $order->update(); //actualizamos el registro
            return response()->json([
                'message'=> "la orden $order->correlativo, ha cambiado a estado $nuevoEstado",
                'order' => $order
            ]);
            

     }catch(\Exception $e){
        return response()->json([
            'message' => 'Error al actualizar el estado',
            'error' => $e->getMessage()
            
        ]) ;

    }



    }

    //METODO PRIVADO PARA GENERAR EL CORRELATIVO DE CADA ORDEN
    private function generarCorrelativo()
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $ultimo =Order::whereYear('fecha', $year)
        ->whereMonth('fecha', $month)
        ->lockForUpdate()
        ->count();
        $numero = str_pad($ultimo + 1,4,'0', STR_PAD_LEFT);

        return $year . $month . $numero;
    }
}
