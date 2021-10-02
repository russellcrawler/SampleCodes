<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use validator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\User;
use DB;
use Excel;
use Hash;
use App\models\Notification;
use App\models\DefaultPrice;
use App\models\PersionalDetail;
use App\models\Equipment;
use App\models\State;
use App\models\City;
use App\models\Billing;
use App\models\Shipement;
use App\models\VerifyUser;
use App\models\Messages;
use App\models\Offers;
use App\models\Plans;
use App\models\CarrierLoad;
use App\models\Drivers;
use App\models\shipmentDrivers;

class UserController extends Controller
{
   
    public function manageUsers(Request $request){
       // dd($request->all());
        $term = $request->search;
        $filter = $request->filter;

        if(Auth::user()->role == 1){
            try{
                $plans = Plans::get();
                $state = State::get();
                $city = City::get();
                $data = User::select('id','name','email','company_name','member_ship_plan','status');
                $data = $data->where('role','!=',1);
                $data = $data->orderBy('id','desc');
                if(!empty($request->filter)){
                    $data = $data->where('member_ship_plan',$request->filter);
                }
                $data = $data->where(function($query) use ($term){
                    $query->where('name','LIKE','%'.$term.'%');
                    $query->orWhere('company_name','LIKE','%'.$term.'%');
                    $query->orWhere('email','LIKE','%'.$term.'%');
                });
                $data = $data->paginate(10);
                $data = $data->appends([
                    'filter'=>$filter
                ]);
                $rank = $data->firstItem();
                return view('manage_users',compact('data','rank','filter','term','plans','state','city'));
            }catch(\Exception $e){
                return redirect()->back()->with('error','Something went wrong.');
            }
        }else{
            return redirect('/');
        }
    }

    public function deleteData(Request $request,$id){
        $user = Auth::user();
        if($user->role === 1){ //Admin Role check out
            try{
                $package = User::find($id);
                if($package){
                    $package->delete();
                    return redirect()->back()->with('success','Delete Successfully.');
                }else{
                    return redirect()->back()->with('error','Something went wrong.');
                }
            }catch(\Exception $e){
                return redirect()->back()->with('error','Something went wrong.');
            }
        }else{
            return redirect('/');
        }
    }

    public function changeStatus($id,$status){
        $user = Auth::user();
        if($user->role === 1){ //Admin Role check out
            try{    
                $package = User::find($id);
                if($package){
                    $package->status = $status;
                    $package->save();
                    return redirect()->back()->with('success','Status update Successfully.');
                }else{
                    return redirect()->back()->with('error','Something went wrong.');
                }
            }catch(\Exception $e){
                return redirect()->back()->with('error','Something went wrong.');
            }
        }else{
            return redirect('/');
        }
    }

    public function edit(Request $request){
        //dd($request->all());
        $this->validate($request,[
           'name' => 'required|max:50',
        //    'member_ship_plan' => 'required|in:1,2,3',
          // 'email' => 'required|email|max:50',
           'company_name' => 'required|max:100',
           'id' => 'required|exists:users,id'
        ]);
       if(Auth::user()->role == 1){
           try{
            $package = User::find($request->id);
            $package->name = $request->name;
            $package->company_name = $request->company_name;
            // $package->member_ship_plan = $request->member_ship_plan;
            $package->save();
            return redirect()->back()->with('success','Updated successfully.');
           }catch(\Exception $e){
            return redirect()->back()->with('error','Something went wrong.');
        }
           
       }else{
           return redirect('/');
       }
   }
  
   public function login(){
       return view('login');
   }
   public function loginAdmin(Request $request){
    $this->validate($request,[
        'email'=>'required|email|max:50',
        'password'=>'required'
     ]);
     
     try{
        // $credential = $request->only('email','password');
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password, 'role' => 1])){
            return redirect('dashboard');
        }
        return redirect()->back()->with('error','Please check email and password.');
     }catch(\Exception $e){
        return redirect()->back()->with('error','Something went wrong.');
    }
     
   }
   public function dashBoard(){
    Auth::user();
    try{
        $shipment = Shipement::select('id','status','shipment_status','post_enddate')
         ->orderBy('id','desc')->limit(4)->get();//dd($shipment); 
        $total_shipment = Shipement::count();    
        $active_user = User::count();
        $active_shipment = Shipement::where('shipment_status',1)->count();
        $canceled_shipment = Shipement::where('shipment_status',3)->count();
        $completed_shipment = Shipement::where('shipment_status',2)->count();  
        $nego_shipment = Shipement::where('status',2)->count();
        
        $notification = Notification::where('user_type',1)->orderBy('created_at','desc')->get();
        $messages = Messages::where('user_status',1)->orderBy('message_time','desc')
        ->take(5)
        ->get();
        return view('dashboard',compact('shipment','total_shipment','active_user','canceled_shipment','completed_shipment','active_shipment','nego_shipment','notification','messages'));
    }catch(\Exception $e){
            return redirect()->back()->with('error','Something went wrong.');
        }
    
    }

    // public function manageUsers(){
    //     return view('manage_users');
    //     } 
    
    public function membership(){
        try{
            $plan = Plans::paginate(10);
            $rank = $plan->firstItem();
            return view('manage_membership',compact('rank','plan'));
        }catch(\Exception $e){
            return redirect()->back()->with('error','Something went wrong.');
        }
        
    } 
    
    
   
    
    public function shipment(){
        return view('manage_shipment');
    }
    public function logout() {
        try{
            Auth::logout();
            return redirect('/');
        }catch(\Exception $e){
            return redirect()->back()->with('error','Something went wrong.');
        }
       
      }

     public function notification(){
        try{
            $notification = Notification::where('user_type',1)->orderBy('id','desc')->get();
            return view('notification',compact('notification'));
        }catch(\Exception $e){
            return redirect()->back()->with('error','Something went wrong.');
        }
         
     }
     public function uploadDocument(){
         return view('upload_document');
     }

     public function userVerify($token){
        try{
            $verifyUser = VerifyUser::where('token', $token)->first();
            if(isset($verifyUser)){
                $user_data = User::where('id',$verifyUser->user_id)->first();
                if($user_data){
                    if($user_data->email_verified_at != 1){
                        $update_status = User::where('id',$user_data->id)
                        ->update([
                            'email_verified_at'=> 1
                        ]);
                        return view("email_verified");
                    }else{
                        return "404";
                    }
                }
            }
        }catch(\Exception $e){
            return redirect()->back()->with('error','Something went wrong.');
        }
        
     }

     public function detail($id){
        $user = Auth::user();
        try{
            $data = User::findOrFail($id);
            if($data){
                $profile_data = PersionalDetail::where('user_id',$data->id)->first();
                $billing_data = Billing::where('user_id',$data->id)->first();
                $data->profile_data = $profile_data;
                $data->billing_data = $billing_data;
                if($profile_data){
                    $data->equipment = Equipment::where('id',$profile_data->id)->value('name');
                    $data->state = State::where('id',$profile_data->state)->value('name');
                    $data->city = City::where('id',$profile_data->city)->value('name');
                }else{
                    $data->equipment = "";
                    $data->state = "";
                    $data->city = "";
                }
                if($billing_data){
                    $data->b_state = State::where('id',$billing_data->state)->value('name');
                    $data->b_city = City::where('id',$billing_data->city)->value('name');
                }else{
                    $data->b_state = "";
                    $data->b_city = "";
                }
                
            }
        //  dd($data);
        return view('shipment.user_view',compact('data'));
        }catch(\Exception $e){
            return redirect()->back()->with('error','Something went wrong.');
        }
        
    }

    public function sendLink(Request $request){

        try{
            $user = DB::table('users')->where('email', '=', $request->email)->first();
            if ($user == "") {
                return redirect()->back()->withErrors(['email' => trans('User does not exist')]);
            }
            DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => str_random(60),
            'created_at' => Carbon::now()
            ]);
            $tokenData = DB::table('password_resets')
            ->where('email', $request->email)->first();
            $data['to']=$request->email;
            $data['subject'] = "Customer Reset Password";
            $data['from'] = "";
            $blade = "emails.forget_password";
            if ($this->sendResetEmail($request->email, $tokenData->token)) {
            return redirect()->back()->with('msg', trans('A reset link has been sent to your email address.'));
            } else {
                return redirect()->back()->withErrors(['error' => trans('A Network Error occurred. Please try again.')]);
            }
        }catch(\Exception $e){
            return redirect()->back()->withErrors(['error' => trans('A Network Error occurred. Please try again.')]);
        }
        
   
         
       }

    private function sendResetEmail($email, $token)
    {
      $data['to']=$email;
      $data['subject'] = "Forget Password Link";
      $data['from'] = "";
      $blade = "emails.forget_password";
      $user = DB::table('users')->where('email', $email)->first();
      $link = url('/') . '/password_reset/' . $token . '/' . urlencode($user->email);
      $data['link'] = $link;
      $to = $email;
      $subject = 'Forget Password Link';


       try {
        // Mail::send(['html'=>$blade],$data, function($message) use ($data) {
        //   $message->to($data['to'])->subject($data['subject']);
  
        // });
        $return = app('App\Http\Controllers\MailerController')->composeEmail($to,$subject,$blade,$data);
            return $return;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function passwordReset($token,$email){

        return view('password_reset',compact('token','email'));


    }

    public function resetPasswordByApp(Request $request){
        
        $validatedData = $request->validate([
          'email' => 'required|email|exists:users,email',
          'password' => 'required|required_with:confirm_passsword',
          'token' => 'required'
        ]);
        $password = $request->password;
        $tokenData = DB::table('password_resets')->where('token', $request->token)->first();
        if (!$tokenData) return redirect()->back();
        $user = User::where('email', $tokenData->email)->first();
        if (!$user) return redirect()->back()->withErrors(['email' => 'Email not found']);
        DB::table('password_resets')->where('email', $user->email)
      ->delete();//dd(Hash::make($request->password));
        $user->password = Hash::make($request->password);
        $user->save();
        return redirect()->back()->with('msg','Password reset successfully');
      }

      public function messages($id){
        
        $user = Auth::user();
        if($user->role === 1){ //Admin Role check out
            $user_from = $user->id;
            $data = Shipement::where('status','!=',3)->where('status','!=',4)->find($id);//dd($data);
            if($data != ""){
                $user_to_data = User::select('id','name','company_name')->find($data->user_id);//dd($user_to_data);
                $message_list = Messages::select('id','user_from','user_to','message','message_time','user_status','categorey')
                ->where('shipment_id',$id)
                ->orderBy('id','asc')
                ->take(10)
                ->get();
                return view('message_page',compact('data','user_from','user_to_data','message_list'));

            }else{
                return redirect()->back()->with('error','This shipment can not be chat because this is archieved or completed');
            }
        }else{
            return redirect('/');
        }
          
      }

      public function loadMessages($id){
        
        $user = Auth::user();
        if($user->role === 1){ //Admin Role check out
            $user_from = $user->id;
            $data = CarrierLoad::where('status','!=',3)->where('status','!=',4)->find($id);
            if($data != ""){
                $user_to_data = User::select('id','name','company_name')->find($data->user_id);//dd($user_to_data);
                $message_list = Messages::select('id','user_from','user_to','message','message_time','user_status','categorey')
                ->where('shipment_id',$id)
                ->orderBy('id','asc')
                ->take(10)
                ->get();
                return view('load_message',compact('data','user_from','user_to_data','message_list'));
            }else{
                return redirect()->back()->with('error','This carrier load can not be chat because this is archieved or completed');
            }
            
        }else{
            return redirect('/');
        }
          
      }

      public function insertMsg(Request $request){
          //dd($request->all());
        if(empty($request->txt_msg)){
            return json_encode(array("statusCode"=>201,"error"=>"Please write the message"));
        }
        $chat = '';
        $msg = new Messages();
        $msg->user_from = $request->user_from;
        $msg->user_to = $request->user_to;
        $msg->message = $request->txt_msg;
        $msg->shipment_id = $request->shipment_id;
        $msg->categorey = $request->categorey;
        $msg->message_time = date('d-m-Y H:i:sa'); 
        $msg->save();
        $data = Messages::select('id','user_from','user_to','message','message_time','user_status')->where('shipment_id',$request->shipment_id)
        ->orderBy('id','desc')
        ->take(10)
        ->get();
        if($request->categorey == 1){ //Shipment
            $data1 = Shipement::where('id',$request->shipment_id)->first();
            if($data1->status == 1 || $data1->status == 2){
                $update_status = Shipement::find($request->shipment_id);
                $update_status->status = 2;
                $update_status->save();
            }
        }elseif($request->categorey == 2){ // Load
            $data1 = CarrierLoad::where('id',$request->shipment_id)->first();
            if($data1->status == 1 || $data1->status == 2){
                $update_status = CarrierLoad::find($request->shipment_id);
                $update_status->status = 2;
                $update_status->save();
            }
        }
       
        foreach($data as $this_data){
            if($this_data->user_status == 1){
                $chat .= '<div class="pd-0"><div class="profiles"><div class="pp-infos"><label>Admin</label><br><span>'.$this_data->message_time.'</span></div><p>'.$this_data->message.'</p></div></div>';
            }else{
                $user_name = User::where('id',$this_data->user_from)->value('name');
                $chat .= '<div class="pd-0"><div class="profiles"><div class="pp-infos"><label>'.$user_name.'</label><br><span>'.$this_data->message_time.'</span></div><p>'.$this_data->message.'</p></div></div>';
            }
        }
    //    $chat .= '<div class="pd-0"><div class="profiles"><div class="pp-infos"><label>Admin</label><br><span>'.$data->message_time.'</span></div><p>'.$data->message.'</p></div></div>';
        return json_encode(array("statusCode"=>200,"data"=>$chat));

      }

      public function getMessage(Request $request){
         //dd($request->all()); 
        $user = Auth::user();
        $chat = '';
        $data = Messages::select('id','user_from','user_to','message','message_time','user_status','type','amount')
        ->where('shipment_id',$request->shipment_id)//
        ->where('categorey',$request->categorey)
        ->orderBy('message_time','desc')
        ->take($request->limits)
        ->get()->reverse();//dd($data);
        foreach($data as $this_data){
            if($this_data->user_status == 1 && $this_data->type == 1){
                $chat .= '<div class="pd-0"><div class="profiles"><div class="pp-infos"><label>Admin</label><br><span>'.$this_data->message_time.'</span></div><p>'.$this_data->message.'</p></div></div>';
            }elseif($this_data->type == 2){
                $user_name = User::where('id',$this_data->user_to)->value('name');
                $chat .= '<div class="pd-0"><div class="profiles"><div class="pp-infos"><label>Admin</label><br><span>'.$this_data->message_time.'</span></div><div class="buddy-offer"><div class="row"><div class="col-md-6"><h4>Buddy offer: $'.$this_data->amount.'</h4><p class="p-0">'.$this_data->message.'</p></div></div></div></div></div>';
            }else{
                $user_name = User::where('id',$this_data->user_from)->value('name');
                $chat .= '<div class="pd-0"><div class="profiles"><div class="pp-infos"><label>'.$user_name.'</label><br><span>'.$this_data->message_time.'</span></div><p>'.$this_data->message.'</p></div></div>';
            }
        }
        return json_encode(array("statusCode"=>200,"data"=>$chat));
      }

      public function addoffer(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'note' => 'required|max:100',
            'to_user' => 'required|numeric',
            'from_user' => 'required|numeric',
            'id_shipment' => 'required|numeric',

        ]);
        if ($validator->fails()) {
            return json_encode(array("statusCode"=>401,'error' =>  $validator->errors()->first()));
        }
        $user = Auth::user();
        
        try{
            DB::beginTransaction();
            if($request->categorey == 1){
                $data = Shipement::where('id',$request->id_shipment)->first();
            }elseif($request->categorey == 2){
                $data = CarrierLoad::where('id',$request->id_shipment)->first();
            }
            
            $msg = new Messages();
            $msg->user_from = $request->from_user;
            $msg->user_to = $request->to_user;
            $msg->message = $request->note;
            $msg->shipment_id = $request->id_shipment;
            $msg->amount = $request->amount;
            $msg->type = 2; // offer type
            $msg->message_time = date('d-m-Y H:i:sa'); 
            $msg->categorey = $request->categorey;
            $msg->save();

            $offer = new Offers();
            $offer->message_id = $msg->id;
            $offer->shipment_id = $request->id_shipment;
            $offer->amount = $request->amount;
            $offer->note = $request->note;
            $offer->type = $request->categorey;
            $offer->save();

            $update_message = Messages::where('id',$msg->id)
            ->update(['offer_id'=>$offer->id]);

            $noti = new Notification();
            $noti->content = "Admin sent you a offer of ". $request->amount." for shipment ship_00".$request->id_shipment.".";
            $noti->user_type = 2; //Admin sent for user showing this notification
            $noti->user_id = $data->user_id;
            $noti->save();

            DB::commit();
            return json_encode(array("statusCode"=>200));
        }catch (\Exception $e){
            DB::rollback();
            return json_encode(array("statusCode"=>401,'error'=>'Logged in but access to requested area is forbidden'));
        }
      }

      public function expence(Request $request) {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required',
            'end_date' => 'required',
            'member_ship_plan'=> 'required|in:1,2,3'
        ]);
        $start_date = date('d-m-Y', strtotime($request->start_date));
        $end_date = date('d-m-Y', strtotime($request->end_date));
        $user_array = [];
        $user_data = User::where('member_ship_plan',$request->member_ship_plan)
        ->whereBetween('created_at',[$request->start_date,$request->end_date])->get();
        if(count($user_data) != 0){
            foreach($user_data as $this_data){
                $user_array[] = array(
                    
                );

            }
        }
        //print_r($start_date);die;
        $expence_id = $request->expence_id;
        //print_r($expence_id);die;
        $date_from = $start_date;
        $date_from = strtotime($date_from);
        $date_to = $end_date;
        $date_to = strtotime($date_to);
        $expence1 = [];
        $expence = [];
        $purchase = [];
        for ($i = $date_from;$i <= $date_to;$i+= 86400) {
            $get_date = date("Y-m-d", $i);
            $user_purchase = DB::table('user_purchase')
            ->where('expence_date', $get_date)
            ->where('company_id', $expence_id)
           // ->where('delete_status', 0)
            ->get();
            if (count($user_purchase) != '0') {
                foreach ($user_purchase as $this_user_purchase) {
                    $expence1[] = array(
                        'purchase_no' => $this_user_purchase->vendor_invoice_no,
                        'date' => $this_user_purchase->expence_date, 
                        'vendor' => $this_user_purchase->vendor, 
                        'gstin' => $this_user_purchase->gstin, 
                        'name' => $this_user_purchase->name,
                        'hsn' => "",
                        'discount' => "0", 
                        'amount' => $this_user_purchase->amount, 
                        'cgst' => $this_user_purchase->cgst, 
                        'sgst' => $this_user_purchase->sgst, 
                        'igst' => $this_user_purchase->igst,
                        'cess' => $this_user_purchase->cess, 
                        'total' => $this_user_purchase->amount + $this_user_purchase->cgst + $this_user_purchase->sgst + $this_user_purchase->igst,
                        'type' => "Expence"
                    );
                }
            }
        }
        for ($i = $date_from;$i <= $date_to;$i+= 86400) {
            $get_date = date("Y-m-d", $i);
            $user_purchase = DB::table('purchase_news')
            ->where('date', $get_date)
            ->where('company_id', $expence_id)
            ->where('delete_status', 0)
            ->get();
            if(count($user_purchase) != "0")
            {
                foreach($user_purchase as $this_purchase)
                {
                    $user_purchase_product = DB::table('purchase_products')
                    ->where('purchase_id', $this_purchase->id)
                    ->where('delete_status', 0)
                    ->get();

                    if(count($user_purchase_product) != "0")
                    {
                        foreach($user_purchase_product as $this_product)
                        {
                            $purchase[] = array(
                                'purchase_no' => $this_purchase->purchase_id,
                                'date' => $this_purchase->date,  
                                'vendor' => $this_purchase->vendor_name, 
                                'gstin' => $this_purchase->vendor_gstin, 
                                'name' => $this_product->name,
                                "hsn" => $this_product->hsn,
                                'discount' => $this_product->discount_value,
                                'amount' => $this_product->taxable_value, 
                                'cgst' => $this_product->cgst, 
                                'sgst' => $this_product->sgst, 
                                'igst' => $this_product->igst,
                                'cess' => $this_product->cess_charge,
                                'total' => ($this_product->taxable_value+$this_product->cgst+$this_product->sgst+$this_product->igst+$this_product->cess_charge),
                                'type' => "Purchase"
                            );
                        }
                    }
                }
            }
           
        }//dd($purchase);
        $expence = array_merge($expence1,$purchase);//dd($expence);
        $userList[] = [
            'Purchase No',
            'Date of Expense', 
            'Vendor Name', 
            'Vendor GSTIN', 
            'Nature of Expense',
            'HSN',
            'Discount', 
            'Expense Amount', 
            'CGST', 
            'SGST', 
            'IGST',
            'Cess',
            'Total',
            'Type'
        ];

        foreach ($expence as $this_expence) {
            $userList[] = [
                $this_expence['purchase_no'],
                $this_expence['date'], 
                $this_expence['vendor'], 
                $this_expence['gstin'], 
                $this_expence['name'],
                $this_expence['hsn'],
                $this_expence['discount'], 
                $this_expence['amount'], 
                $this_expence['cgst'], 
                $this_expence['sgst'], 
                $this_expence['igst'],
                $this_expence['cess'], 
                $this_expence['total'],
                $this_expence['type'], 
            ];
        }
        //dd($userList);
        Excel::create('expence', function ($excel) use ($userList) {
            $excel->sheet('expence', function ($sheet) use ($userList) {
                $sheet->fromArray($userList, null, 'A1', true, false);
                $sheet->row(1, function ($row) {
                    $row->setFontWeight('bold');
                });
            });
        })->export('csv');
    }

    public function resetAdminPassword(){
      
        return view('admin_reset_password');
    }
    public function adminResetPassword(Request $request){
        //dd($request->all());
        $this->validate($request,[
            'old_password' => 'required',
            'new_password' => 'required|required_with:password_confirmation|different:old_password',
         ]);
         $user = Auth::user();
         try{
            $users = User::findOrFail($user->id);
            $users->password = Hash::make($request->new_password);
            $users->save();
            return redirect('dashboard')->with("password reset successfully.");

         }catch(\Exception $e){
            return redirect()->back()->with("Something went wrong.");
         }
        
    }

    public function addUser(Request $request){
        $this->validate($request,[
            'name' => 'required|max:200',
            'company_name' => 'required|max:200',
            'email' => 'required|email|unique:users|max:200',
            'password' => 'required|min:6|max:200',
            'member_ship_plan' => 'required|in:1,2,3',
         ]);
        if(Auth::user()->role == 1){
            try{ 
                $input = $request->all();
                $input['password'] = bcrypt($input['password']);
                $user = User::create($input);
                return redirect()->back()->with('success','Add successfully.');
                }catch(\Exception $e){
                    return redirect()->back()->with('error','Something went wrong.');
                }
        }else{
            return redirect('/');
        }
    }

    public function stateCity(Request $request){
        // dd($request->all());
        $this->validate($request,[
           'id'=>'required'
         ]);
         $cities = "";
            $city = City::where('state_id',$request->id)->select('id','name')->get();
            if(count($city) != 0){
                foreach($city as $this_city){
                    $cities .= '<option value="'.$this_city->id.'">'.$this_city->name.'</option>';
                }
            }
        return json_encode(array("statusCode"=>200,"data"=>$cities));   
            
    }
    

     public function createUser(Request $request){
        //  dd($request->all());
        $this->validate($request,[
            'name' => 'required|max:200',
            'company_name' => 'required|max:200',
            'email' => 'required|email|unique:users|max:200',
            'password' => 'required|min:6|max:200',
            'member_ship_plan' => 'required|in:1,2,3',

            'p_name' => 'required|max:100',
            'p_company_name' => 'required|max:100',
            'p_email' => 'required|email|max:100',
            'phone' => 'digits:10',
            'mobile' => 'required|max:15',
            'address1' => 'min:3|max:255',
            'address2' => 'min:3|max:255',
            'country' => 'required|max:100',
            'state' => 'required|max:100',
            'city' => 'required|max:100',
            'zipcode' => 'required|numeric|digits:5',
         ]);
         try{
            DB::beginTransaction();
            $users = new User();
            $users->name = $request->name;
            $users->company_name = $request->company_name;
            $users->email = $request->email;
            $users->password = bcrypt($request->password);
            $users->member_ship_plan = $request->member_ship_plan;
            $users->save();

            $personal_detail = new PersionalDetail();
            $personal_detail->user_id = $users->id;
            $personal_detail->name = $request->p_name;
            $personal_detail->company_name = $request->p_company_name;
            $personal_detail->email = $request->p_email;
            $personal_detail->phone = $request->phone;
            $personal_detail->address1 = $request->address1;
            $personal_detail->address2 = $request->address2;
            $personal_detail->mobile = $request->mobile;
            $personal_detail->country = $request->country;
            $personal_detail->state = $request->state;
            $personal_detail->city = $request->city;
            $personal_detail->zipcode = $request->zipcode;
            $personal_detail->save();

        return redirect()->back()->with('success','User added successfully');
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return redirect()->back()->with('error',"Something went wrong.");
        }
     }

     public function notificationCount(){
        $user_id = Auth::user()->id;
        $notification = DB::table('notification')->where('user_type',1)->orderBy('created_at','desc')->count();
        DB::table('users')->where('id', $user_id)->update(['notification_count'=>$notification]);
        return true;

     }

     public function notification_count(Request $request) {
        $notification = DB::table('notification')->count();
        $user_id = Auth::user()->id;
        $users_noti_count = DB::table('users')->where('id', $user_id)->first();
        $total_left=$notification-$users_noti_count->notification_count;

        return view('layout.notification_count', compact('total_left'));
    }

    public function notification_count_update(Request $request) {
        $notification = DB::table('notification')->count();
       $user_id = Auth::user()->id;
       DB::table('users')->where('id', $user_id)->update(['notification_count'=>$notification]);

       return 1;
    }

    public function sendNotification($user_id,$content,$user_type){
        try{
            $notification = new Notification();
            $notification->user_id = $user_id;
            $notification->content = $content;
            $notification->user_type = $user_type;
            $notification->save(); 
            return true; 
        }catch(\Exception $e){
            return false;
        }
     }

     public function deleteNotification(Request $request){
         //dd($request->all());
        $this->validate($request,[
            'id' => 'required|exists:notification,id',
         ]);
        $user = Auth::user();
        try{
            $notification = Notification::find($request->id);
            $notification->delete();
            return response()->json(['success'=>'','message'=>'Deleted Successfully']);
        }catch(\Exception $e){
            return response()->json(['success'=>false,'data'=>'',"message"=>"Something went wrong."]);
        }
    }

    public function manageDrivers(Request $request){
        // dd($request->all());
         $term = $request->search;
         $filter = $request->filter;
 
         if(Auth::user()->role == 1){
             try{
                 $plans = Plans::get();
                 $state = State::get();
                 $city = City::get();
                 $data = Drivers::orderBy('id','desc');
                 $data = $data->where(function($query) use ($term){
                     $query->where('name','LIKE','%'.$term.'%');
                     $query->orWhere('company_name','LIKE','%'.$term.'%');
                     $query->orWhere('email','LIKE','%'.$term.'%');
                 });
                 $data = $data->paginate(10);
                 $data = $data->appends([
                     'filter'=>$filter
                 ]);
                 $rank = $data->firstItem();
                 return view('driver_list',compact('data','rank','filter','term','plans','state','city'));
             }catch(\Exception $e){
                 return redirect()->back()->with('error','Something went wrong.');
             }
         }else{
             return redirect('/');
         }
     }

     public function addDriver(Request $request){
        //  dd($request->all());
        $user_id = Auth::user();
        $this->validate($request,[
            'name' => 'required|max:255',
            'email' => 'required|email|unique:drivers,email',
            'mobile' => 'required|numeric',
            'password' => 'required|max:255'
         ]);
         //dd($request->all());
        try{
            $data = new Drivers();
            $data->name = $request->name;
            $data->email = $request->email;
            $data->mobile = $request->mobile;
            $data->company_name = $request->company_name;
            $data->password = md5($request->password);
            $data->save();
            return redirect()->back()->with('success','Driver add succesfully.');
        }catch(\Exception $e){
            return redirect()->back()->with('error','Something went wrong.');
        }
        
    }

    public function editDriver(Request $request){
        //  dd($request->all());
        $user_id = Auth::user();
        $this->validate($request,[
            'id'=>'required|exists:drivers,id',
            'name' => 'required|max:255',
            //'email' => 'required|email|unique:drivers,email',
            'mobile' => 'required|numeric',
            'company_name' => 'required|max:255'
         ]);
        try{
            $data = Drivers::find($request->id);
            $data->name = $request->name;
           // $data->email = $request->email;
            $data->mobile = $request->mobile;
            $data->company_name = $request->company_name;
            $data->save();
            return redirect()->back()->with('success','Driver add succesfully.');
        }catch(\Exception $e){
            return redirect()->back()->with('error','Something went wrong.');
        }
        
    }

    public function deleteDriver(Request $request,$id){
        $user = Auth::user();
        if($user->role === 1){ //Admin Role check out
            try{
                $driver_exist = shipmentDrivers::where('driver_id',$id)->exists();
                if($driver_exist){
                    return redirect()->back()->with('error','Driver already exist with shipment.');
                }else{
                    $driver = Drivers::find($id);
                    if($driver){
                        $driver->delete();
                        return redirect()->back()->with('success','Delete Successfully.');
                    }else{
                        return redirect()->back()->with('error','No record found.');
                    }
                }
                
            }catch(\Exception $e){
                return redirect()->back()->with('error','Something went wrong.');
            }
        }else{
            return redirect('/');
        }
    }


     
    

    
}
