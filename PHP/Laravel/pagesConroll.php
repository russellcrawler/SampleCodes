<?php

namespace MTC\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use MTC\Http\Controllers\Controller;
use MTC\Http\Requests\EnquiryRequest;
use MTC\Mail\SendEnquiryMail;
use MTC\City;
use Session;
class PagesController extends Controller
{

	const CAR_TYPE = [
       "1"=>"Sedan",
       "2"=>"Suv",
       "3"=>"Prime suv"
    ]; 

    /*
	*@return home page
    */
	public function index(){
	$title="India's emerging Car Rental Services, Outstation taxi Booking in India, Local City Tour, Airport Transfer. MTC Car Hire";
	$keywords= "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
	$description= "MTC Car Hire provides excellent Cab Rental Services for Business Travellers, Package Tours, Airport and Hotels Transfers, Taxi for City use.";

return view('frontend.index',compact('title','keywords','description'));
}


	/*
	*@return attach taxi page
    */
	public function attachTaxi(){

	$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
	$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
	$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
	$CityList= City::orderByRaw('city_name ASC')->get();
	return view('frontend.attach-taxi',compact('CityList','title','keywords','description'));

	}

	/*
	*@return attach enquiry page
    */
	public function enquiry(Request $request){
		if($request->isMethod('get')){

	$CityList= City::orderByRaw('city_name ASC')->get();

	$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
	$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
	$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";

	return view('frontend.enquiry', compact('CityList','title','keywords','description'));


		}else{
        	$this->validate($request, [
            	'name'  => 'required',
            	'mobile' => 'required|digits:10' , //'required|regex:/(01)[0-9]{9}/',
            	'email' => 'required|email',
        	]);
        	$data = $request->all();
        	$data['car_type'] = $request->car_type ? self::CAR_TYPE[$request->car_type] : 'N/A'; 
			$this->sendEnquiryEmail($data);
			return redirect('enquiry')->with('success', 'We will touch you soon.');
		}
		
	}

	/*
	*@return attach sign-in and sign-up page
    */
	public function signIn(){
		return view('frontend.sign-in');
	}
	/*
	*@return attach register page
    */
	public function signUp(){
		return view('frontend.sign-up');
	}
	/*
	*@return attach about-use page
    */
	public function aboutUs(){

	$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
	$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
	$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
	return view('frontend.about-us',compact('title','keywords','description'));

	}
	/*
	*@return attach contact-us page
    */
	public function contactUs(){

		$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.contact-us',compact('title','keywords','description'));

	}
	/*
	*@return attach feedback page
    */
	public function feedback(){

		$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.feedback',compact('title','keywords','description'));
	}
	/*
	*@return attach faqs page
    */
	public function faqs(){
		$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.faqs',compact('title','keywords','description'));
	}

	/*
	*@return attach privacy policy page
    */
	public function privacyPolicy(){
		$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.privacy-policy',compact('title','keywords','description'));
	}

	/*
	*@return attach disclaimer policy page
    */
	public function disclaimer(){
			$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.disclaimer',compact('title','keywords','description'));
	}

	/*
	*@return attach term and condition page
    */
	public function termCondition(){
		$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.term-condition',compact('title','keywords','description'));
	}

	/*
	*@return attach vehicle guide page
    */
	public function vehicleGuide(){
		$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.vehicle-guide',compact('title','keywords','description'));
	}

	/*
	*@return attach covid-19 guidelines page
    */
	public function covidGuideLines(){
		$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.covid-19-guide-line',compact('title','keywords','description'));
	}


	/*
	*@return attach cab services guidelines page
    */
	public function indianCarRental(){

		$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.cab-services',compact('title','keywords','description'));

	}
	/*
	*@return attach cab services guidelines page
    */
	public function services(){
			$title="Cab Booking India Outstation, Local City Tour, Airport Transfer. MTC Travel Solutions";
		$keywords=   "Online Cab Booking, Car Rental India, Taxi Booking India, Corporate Taxi Booking, Transfer Taxi";
		$description= "MTC Travel Solutions India provides excellent Cab Rental Services for Business Travellers,Package Tours,Airport,Railway,Hotels Transfers,Taxi for City use.";
		return view('frontend.services',compact('title','keywords','description'));
	}


	public function sendEnquiryEmail($request){
		try{
			$details = $request;
			$details['subject'] = 'Customer Enquiry';
    		\Mail::to(env("ENQUIRY_EMAIL"))->send(new SendEnquiryMail($details));
		}
		catch(\Exception $e){
			\Log::info('Enquiry_email_exception',['__trace' => $e->getTraceAsString()]);
		}
		
	}


	public function selectCity(Request $request){
		$cities =  City::where(function($query) use ($request) {
			$query->where('city_name', 'like', '%'.$request->pickup_city.'%')
			->orWhere('city_name','like','%'.$request->drop_city.'%');
		})->get();
		//$cities =  City::limit(10)->get();
		$data = [];
		foreach ($cities as $key => $city) {
			$data[]['city'] = $city->city_name;
		}

		return ['data' => $data];
	}

}
