<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    private $linkproduct;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->linkproduct = [
            'http://seller2.local/seller2/itemlist',
            'http://seller1.local/seller1/itemlist'
        ];
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $options = [
            'verify' => false,
            'http_errors' => false,
//            'query' => ['name' => @$request->name], // Data
            'headers' => [
                'content-type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 60
        ];
        $data =[];
        if (!empty($this->linkproduct)){
            foreach ($this->linkproduct as $link){
                $client = new Client();
                $response = $client->request('GET',$link,$options);
                if($response->getStatusCode() ==  200){
                    $result = $response->getBody()->getContents();
                    $result = json_decode($result, true);
                    if (isset($request->name) && $request->name!=''){
                        $result['data'] = collect($result['data'])->filter(function($element) use($request){
                            return false !== stripos($element['item_name'], $request->name) && $element['stock_qty'] > 0;
                        });
                        $result['data'] = $result['data']->toArray();
                    }
                    $data = array_merge(@$result['data'],$data);
                }
            }

            $data = collect($data);
            $data = $data->sortBy('price_of_unit');
            session(['product' => $data]);
            return view('home',[
                'data' => $data
            ]);
        }
    }

    public function purchase(Request $request){
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'product' => 'required',
                'quantity' => 'required|min:1',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => 1,'msg'=>$validator->getMessageBag()->toArray()], 400);
            }
            if (!session('product')){
                return response()->json([
                    'error' => 1,
                    'msg' =>'No products found'
                ], 400);
            }

            $options = [
                'verify' => false,
                'http_errors' => false,
                'query' => ['name' => @$request->name], // Data
                'headers' => [
                    'content-type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 60
            ];
            $products =[];
            if (!empty($this->linkproduct)){
                foreach ($this->linkproduct as $link){
                    $client = new Client();
                    $response = $client->request('GET',$link,$options);
                    if($response->getStatusCode() ==  200){
                        $result = $response->getBody()->getContents();
                        $result = json_decode($result, true);
                        $products = array_merge(@$result['data'],$products);
                    }
                }
                $products = collect($products);
                $products = $products->sortBy('price_of_unit');
                session(['product' => $products]);
            }

            $products = session('product')->keyBy('item_id');
            $product = $products[$request->product];

            if ($product['stock_qty'] < (int)$request->quantity){
                return response()->json([
                    'error' => 1,
                    'msg' =>['The number of products is not enough']
                ], 400);
            }

            $price = $product['price_of_unit']*(int)$request->quantity;

            if ($price > Auth::user()->balance){
                return response()->json([
                    'error' => 1,
                    'msg' =>['Insufficient account balance']
                ], 400);

            }
            //Purchase
            $save = new Purchase();
            $save->user_id = Auth::user()->id;
            $save->item_id = $product['item_id'];
            $save->quantity = (int)$request->quantity;
            $save->price = $price;
            $save->seller_ip = $product['seller_ip'];
            $save->date = Carbon::now();
            $save->save();

            //user
            $user = User::find(Auth::user()->id);
            $user->balance = $user->balance - $price;
            $user->save();

            //product
            $saveproduct = Product::find($product['item_id']);
            $saveproduct->stock_qty = (int)$product['stock_qty'] - (int)$request->quantity;
            $saveproduct->save();

            DB::commit();
            if (isset($save->pur_id)){
                return response()->json([
                    'success' => 1,
                    'msg' =>'Successful purchase',
                    'pur_id'=>$save->pur_id
                ], 200);
            }
            return response()->json([
                'error' => 1,
                'msg' =>['Order failed']
            ], 422);

        }catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'error' => 1,
                'msg' =>$ex->getMessage()
            ], 500);
        }
    }

    public function recharge(Request $request){
        return view('recharge',[]);
    }

    public function saverecharge(Request $request){
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'card_num' => 'required',
                'pin' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 1,'msg'=>$validator->getMessageBag()->toArray()], 400);
            }
            if (@$request->amount < 0){
                return response()->json([
                    'error' => 1,
                    'msg' =>['Invalid amount']
                ], 400);
            }

            $checkcard = Card::where('card_num',$request->card_num)
                ->where('pin',$request->pin)->first();
            if (!$checkcard){
                return response()->json([
                    'error' => 1,
                    'msg' =>['Invalid card']
                ], 400);
            }

            //user
            $user = User::find(Auth::user()->id);
            $user->balance = $user->balance + (int)$request->amount;
            $user->save();

            DB::commit();
            return response()->json([
                'success' => 1,
                'msg' =>'Successful recharge',
            ], 200);

        }catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'error' => 1,
                'msg' =>$ex->getMessage()
            ], 500);
        }
    }

    public function listpurchase(Request $request){
        $data = Purchase::join('users','users.id','purchase.user_id')
            ->select('purchase.*','users.name','users.user_address');

        if (isset($request->pur_id) && $request->pur_id!=''){
            $data = $data->where('purchase.pur_id',$request->pur_id);
        }
        if (isset($request->user_id) && $request->user_id!=''){
            $data = $data->where('purchase.user_id',$request->user_id);
        }

        $data = $data->get();
        $options = [
            'verify' => false,
            'http_errors' => false,
            'query' => ['name' => @$request->name], // Data
            'headers' => [
                'content-type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 60
        ];
        $product =[];

        if (!empty($this->linkproduct)){
            foreach ($this->linkproduct as $link){
                $client = new Client();
                $response = $client->request('GET',$link,$options);
                if($response->getStatusCode() ==  200){
                    $result = $response->getBody()->getContents();
                    $result = json_decode($result, true);
                    $product = array_merge(@$result['data'],$product);
                }
            }
            $product = collect($product);
            $product = $product->sortBy('price_of_unit')->keyBy('item_id');
        }

        return view('purchase',[
            'data'=>$data,
            'products'=>$product
        ]);
    }

    public function delete(Request $request){
        DB::beginTransaction();
        try {
            if(isset($request->id)&& $request->id){
                $data = Purchase::find($request->id);
                if(!$data){
                    return response()->json([
                        'error' => 1,
                        'msg' =>['Order not found']
                    ], 400);
                }
                //user
                $user = User::find($data->user_id);
                $user->balance = $user->balance + (int)$data->price;
                $user->save();

                //product
                $saveproduct = Product::find($data->item_id);
                $saveproduct->stock_qty = $saveproduct->stock_qty + (int)$data->quantity;
                $saveproduct->save();

                $data->delete();

                DB::commit();
                return response()->json([
                    'success' => 1,
                    'msg' =>'Cancellation successful',
                ], 200);
            }else{
                return response()->json([
                    'error' => 1,
                    'msg' =>['Order not found']
                ], 400);
            }
        }catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'error' => 1,
                'msg' =>$ex->getMessage()
            ], 500);
        }
    }
}
