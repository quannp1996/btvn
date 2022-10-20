<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request as Requests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class JsonController extends BaseController
{
    private $domain;
    public function __construct(){
        $this->domain = [
            'seller1.local' =>'seller_1',
            'seller2.local' =>'seller_2',
        ];
    }

    public function index(Request $request)
    {
        try {
            if(array_key_exists(Request::getHttpHost(),$this->domain)){
                $table = $this->domain[Request::getHttpHost()];
                $data = DB::table('product')->where('seller',$this->domain[Request::getHttpHost()])
                    ->select('product.*', DB::raw('IF(item_id IS NOT NULL, "'.Request::getHttpHost().'",null) AS seller_ip'))->get();
                Storage::disk('public')->put($this->domain[Request::getHttpHost()].'.json', json_encode($data, JSON_UNESCAPED_UNICODE));
                return response()->json([
                    'success' => 1,
                    'msg' =>'Save successfully'
                ], 200);
            }
            return response()->json([
                'error' => 1,
                'msg' =>'No data found'
            ], 200);
        }catch (\Exception $ex) {
            return response()->json([
                'error' => 1,
                'msg' =>$ex->getMessage()
            ], 500);
        }
    }

    public function getProduct(Requests $request){
        try {
            if(array_key_exists(Request::getHttpHost(),$this->domain) &&
                Storage::disk('public')->exists($this->domain[Request::getHttpHost()].'.json')){
                $data = Storage::disk('public')->get($this->domain[Request::getHttpHost()].'.json');
                $data = json_decode($data);
                $data = collect($data);

                return response()->json([
                    'success' => 1,
                    'data' => $data
                ], 200);
            }
            return response()->json([
                'error' => 1,
                'msg' =>'No data found'
            ], 200);
        }catch (\Exception $ex) {
            return response()->json([
                'error' => 1,
                'msg' =>$ex->getMessage()
            ], 500);
        }
    }
}
