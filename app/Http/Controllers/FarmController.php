<?php

namespace App\Http\Controllers;

use App\Models\AddOfferNotif;
use App\Models\Notification;
use App\Models\RegisterFarmRequestNotif;
use Illuminate\Http\Request;
use App\Traits\validationTrait;
use Validator;
use App\Models\Farm;
use App\Models\PurchaseOffer;
use App\Models\DetailPurchaseOffer;
use App\Models\RowMaterial;
use App\systemServices\notificationServices;
use App\Models\salesPurchasingRequset;


use Auth;
use Carbon\Carbon;

class FarmController extends Controller
{
    use validationTrait;

    protected $notificationService;

    public function __construct()
    {
        $this->notificationService = new notificationServices();
    }
    public function displayFarms(Request $request)
    {
        $Farm = Farm::with('governate')->where('approved_at', '!=', null)->get();

        return response()->json($Farm, 200);
    }

    public function displayPurchaseOffers(Request $request)
    {
        $offer_id = salesPurchasingRequset::pluck('offer_id');
        $PurchaseOffer = PurchaseOffer::doesnthave('requestSales')->with('detailpurchaseOrders', 'farm')
        ->whereDate('created_at', Carbon::today())->orderBy('id', 'DESC')->get();

        $totalPrice = 0;
        foreach($PurchaseOffer as $_PurchaseOffer){
            $detailPurchaseOffer = $_PurchaseOffer["detailpurchaseOrders"];
            foreach($detailPurchaseOffer as $_detailPurchaseOffer){
                $priceForType = $_detailPurchaseOffer->price * $_detailPurchaseOffer->amount;
                $_detailPurchaseOffer->PriceForOffer = $priceForType;
                $totalPrice += $priceForType;
            }

        $_PurchaseOffer->priceForTotalOffer = $totalPrice;
        $totalPrice = 0;
        }


        return response()->json($PurchaseOffer, 200);
    }

    //2. last 48 h
    public function displayPurchaseOffersLast48H(Request $request)
    {

        $PurchaseOffer = \DB::table('purchase_offers AS t1')
            ->select('t1.*', 'farms.username')
            ->join('farms', 'farms.id', '=', 't1.farm_id')
            ->leftJoin('sales_purchasing_requests AS t2', 't2.offer_id', '=', 't1.id')
            ->whereNull('t2.offer_id')
            ->where('t1.created_at', '>=', Carbon::now()->subHours(48)->toDateTimeString())
            ->get();

        return response()->json($PurchaseOffer);

    }

    public function SoftDeleteFarm(Request $request, $FarmId)
    {
        Farm::find($FarmId)->delete();
        return response()->json(["status" => true, "message" => "تم حذف المزرعة بنجاح"]);
    }

    public function restoreFarm(Request $request, $FarmId)
    {
        Farm::onlyTrashed()->find($FarmId)->restore();
        return response()->json(["status" => true, "message" => "تم استرجاع المزرعة بنجاح"]);
    }

    public function displayFarmTrashed(Request $request)
    {
        $FarmTrashed = Farm::onlyTrashed()->get();
        return response()->json($FarmTrashed, 200);
    }


    public function registerFarm(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "username" => "required:min:3|max:255|unique:farms,username",
                "password" => "required|min:8|max:15",
                "location" => "required|max:255",
                "mobile_number" => "required",
                "owner" => "required",
                "governorate_id" => "required"
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->all()
            ]);
        }

        $farm = new Farm();
        $farm->username = $request->username;
        $farm->name = $request->name;
        $farm->owner = $request->owner;
        $farm->password = encrypt($request->password);
        $farm->location = $request->location;
        $farm->mobile_number = $request->mobile_number;
        $farm->governorate_id = $request->governorate_id;
        $farm->save();

        //MAKE NEW NOTIFICATION RECORD
        $RegisterFarmRequestNotif = new RegisterFarmRequestNotif();
        $RegisterFarmRequestNotif->from = $farm->id;
        $RegisterFarmRequestNotif->is_read = 0;
        $RegisterFarmRequestNotif->owner = $farm->owner;
        $RegisterFarmRequestNotif->name = $farm->name;
        $RegisterFarmRequestNotif->save();

        //SEND NOTIFICATION REGISTER REQUEST TO SALES MANAGER USING PUSHER
        $data['from'] = $farm->id;
        $data['is_read'] = 0;
        $data['owner'] = $farm->owner;
        $data['name'] = $farm->name;
        $this->notificationService->registerFarmRequestNotification($data);
        ////////////////// SEND THE NOTIFICATION /////////////////////////
        return response()->json(["status" => true, "message" => "انتظار موافقة المدير"]);
    }

    public function LoginFarm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }
        $farm = Farm::where('username', $request->username)->get()->first();
        if ($farm != null && decrypt($farm->password) == $request->password) {

            config(['auth.guards.api.provider' => 'farms']);

            // $user = Farm::select('*')
            //     ->where([['id', '=', auth()->guard('farms')->user()->id]])->get();

            Auth::guard('farms')->login($farm);
            if ($farm->approved_at != Null) {
                $success = $farm;
                $success['token'] = $farm->createToken('api-token', ['farms'])->accessToken;
                return response()->json($success, 200);
            } else {
                return response()->json(["status" => false, "message" => "انتظار موافقة المدير"]);
            }

        } else {
            return response()->json(['error' => ['UserName and Password are Wrong!!.']], 200);
        }
    }

    public function commandAcceptForFarm(Request $request, $farmId)
    {
        $findRequestFarm = Farm::where([['id', '=', $farmId]])
            ->update(array('approved_at' => Carbon::now()->toDateTimeString()));

       //send notification to the farm that it is now in the app
       $data = $this->notificationService->makeNotification(
        'farm-channel',
        'App\\Events\\farmNotification',
        'تم قبولك في تطبيق المصري للدواجن',
        '',
        $request->user()->id,
        '',
        0,
        'مدير المشتريات والمبيعات',
        ''
    );

    $this->notificationService->farmNotification($data);
        return response()->json(["status" => true, "message" => "تمت الموافقة على حساب المزرعة"]);
    }

    public function displayFarmRegisterRequest(Request $request)
    {
        $requestRegister = Farm::where('approved_at', '=', Null)->get(array('id', 'name', 'owner', 'mobile_number', 'location'));
        return response()->json($requestRegister, 200);
    }

    public function addOffer(Request $request)
    {
        $offer = new PurchaseOffer();
        $offer->farm_id = $request->user()->id;
        $offer->save();
        //NOW THE DETAILS
        $totalAmount = 0;
        foreach ($request->details as $_detail) {
            $detailPurchaseOffer = new DetailPurchaseOffer();
            $detailPurchaseOffer->purchase_offers_id = $offer->id;
            $detailPurchaseOffer->amount = $_detail['amount'];
            $detailPurchaseOffer->type = $_detail['type'];
            $detailPurchaseOffer->price = $_detail['price'];
            $detailPurchaseOffer->save();

            $totalAmount += $_detail['amount'];
        }
        $findOffer = PurchaseOffer::find($offer->id)->update(['total_amount' => $totalAmount]);

        //MAKE NEW NOTIFICATION RECORD
        $AddOfferNotif = new AddOfferNotif();
        $AddOfferNotif->from = $request->user()->id;
        $AddOfferNotif->is_read = 0;
        $AddOfferNotif->total_amount = $totalAmount;
        $AddOfferNotif->save();

        //SEND NOTIFICATION ADD OFFER TO SALES MANAGER USING PUSHER
        $data['from'] = $request->user()->id;
        $data['is_read'] = 0;
        $data['total_amount'] = $totalAmount;
        $this->notificationService->addOfferNotification($data);
        ////////////////// SEND THE NOTIFICATION /////////////////////////

        return response()->json(["status" => true, "message" => "تم إضافة العرض بنجاح"]);
    }

    public function displayMyOffers(Request $request)
    {
        $PurchaseOffer = PurchaseOffer::with('detailpurchaseOrders')->where('farm_id', $request->user()->id)->orderBy('id', 'DESC')->get();
        $totalPrice = 0;
        foreach($PurchaseOffer as $_PurchaseOffer){
            $detailPurchaseOffer = $_PurchaseOffer["detailpurchaseOrders"];
            foreach($detailPurchaseOffer as $_detailPurchaseOffer){
                $priceForType = $_detailPurchaseOffer->price * $_detailPurchaseOffer->amount;
                $_detailPurchaseOffer->PriceForOffer = $priceForType;
                $totalPrice += $priceForType;
            }

        $_PurchaseOffer->priceForTotalOffer = $totalPrice;
        $totalPrice = 0;
        }
        return response()->json($PurchaseOffer, 200);
    }


    public function displaySalesRequest(Request $request , $offerId){
        $displaySales = PurchaseOffer::with('requestSales.salesPurchasingRequsetDetail')->where('id',$offerId)->get();

        return response()->json($displaySales, 200);
    }

    public function deleteOffer(Request $request, $offerId)
    {
        PurchaseOffer::find($offerId)->delete();
        DetailPurchaseOffer::where('purchase_offers_id', $offerId)->delete();
        return response()->json(["status" => true, "message" => "تم حذف العرض بنجاح"]);
    }

    public function displayRowMaterial(Request $request)
    {
        $rowMaterial = RowMaterial::get('name');
        return response()->json($rowMaterial, 200);
    }
    public function displayDetailOffer(Request $request, $offer_id)
    {
        $offer_details = DetailPurchaseOffer::where('purchase_offers_id', $offer_id)->get();
        return response()->json($offer_details);
    }

    //////////////////////  users profile and editiona /////////////////////
    public function displayMyProfile(Request $request)
    {
        $user = Farm::find($request->user()->id);
        $myProfileData = [
            'name' => $user->name,
            'owner' => $user->owner,
            'location' => $user->owner,
            'username' => $user->username,
            'governorate_id' => $user->governorate_id,
            'mobile_number' => $user->mobile_number,
            'password' =>  decrypt($user->password)
        ];

        return response()->json($myProfileData);
    }

    public function editMyProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'owner' => 'required',
            'location' => 'required',
            'username' => 'required',
            'mobile_number' => 'required|numeric',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }
        $user = Farm::find($request->user()->id);
        $governate_id = null;
        if($request->governorate_id!=null){
            $governate_id = $request->governorate_id;
        }
        $user->update([
            'username' => $request->username,
            'name' => $request->name,
            'owner' => $request->owner,
            'location' => $request->location,
            'governorate_id' => $governate_id,
            'mobile_number' => $request->mobile_number,
            'password' =>  encrypt($request->password)
        ]);
        return response()->json(["status"=>true, "message"=>"تم تعديل بياناتك بنجاح"]);
    }

    ///////////////////  NOTIFICATIN PART //////////////////
    public function displayFarmNotification(Request $request)
    {
        $notifications = Notification::where([
            ['channel', '=', 'farm-channel'],
            ['is_seen', '=', 0]
        ])->orderBy('created_at', 'DESC')->get();
        $notificationsCount = $notifications->count();
        return response()->json(['notifications' => $notifications, 'notificationsCount' => $notificationsCount]);
    }

    public function displayFarmNotificationSwitchState(Request $request)
    {
        $notifications = Notification::where([
            ['channel', '=', 'farm-channel'],
            ['is_seen', '=', 0],
        ])->orderBy('created_at', 'DESC')->get();

        Notification::where([
            ['channel', '=', 'farm-channel'],
            ['is_seen', '=', 0],
        ])->update(['is_seen' => 1]);
        return response()->json($notifications);
    }



    public function t(Request $request){
        $t = Farm::where(function ($query)  {
            $query->where('location','حمص')
            ->orWhere([['id', '=', 2],['name','نافع الحبال']]);
        })->get();


        return response()->json($t);

    }

}
