@section('title',$title)
@section('keywords',$keywords)
@section('description',$description)
@include('frontend.layouts.header')
@include('frontend.layouts.search')
<div class="darkredBg">
   <div class="container">
      <div class="row">
         <div class="col-lg-3 col-sm-3 text-center border-white-right border-sm-bottom py-2">
            <div class="feature-box">
               <i class="fa fa-check-square-o fa-2x text-white"></i>
            <br />
            <span class="text-white">One-Touch Bookings</span>
            </div>
         </div>
         <div class="col-lg-3 col-sm-3 text-center border-white-right border-sm-bottom py-2">
            <div class="feature-box">
               <i class="fa fa-user fa-2x text-white"></i>
               <br />
               <span class="text-white">Experienced Chauffeur</span>
            </div>
         </div>
         <div class="col-lg-3 col-sm-3 text-center border-white-right border-sm-bottom py-2">
            <div class="feature-box">
               <i class="fa fa-car fa-2x text-white"></i>
               <br />
               <span class="text-white">Vast Fleet</span>
            </div>
         </div>
         <div class="col-lg-3 col-sm-3 text-center border-white-right border-sm-bottom py-2">
            <div class="feature-box">
               <i class="fa fa-credit-card fa-2x text-white" aria-hidden="true"></i>
               <br />
               <span class="text-white">Pay Online</span>
            </div>
         </div>
      </div>
   </div>
</div>
<!--top Destinations -->
<div class="container-fluid p-40 top-city section-container">
   <div class="container">
      <div class="row text-center">
         <div class="col-lg-12">
            <h2 class="sub-heading spancolor mb-3">Explore India with pocket friendly rides</h2>
            <p class="mt-2 GreyTxtColor">Avail discounts on every booking</p>
         </div>
         <div class="col-lg-12">
            <div id="" class="carousel slide" data-ride="">
               <div class="carousel-inner">
                  <div class="carousel-item active">
                     <div class="row">
                        <div class="col-lg-3 col-sm-6 pt-1">
                           <a target="_blank" href="#">
                              <div class="city-item roundbox mh-100">
                                 <div class="city-img">
                                    <img class="d-block w-100" src="{{url('frontend/images/top-routs/Bangalore-mtctravel.jpg')}}" alt="MTC top destinations">
                                 </div>
                                 <div class="city-name">
                                    <p class="text-center font-weight-bold py-3">Taxi in Banglore</p>
                                 </div>
                              </div>
                           </a>
                        </div>
                        <div class="col-lg-3 col-sm-6 pt-1">
                           <a target="_blank" href="#">
                              <div class="city-item roundbox mh-100">
                                 <div class="city-img">
                                    <img class="d-block w-100" src="{{url('frontend/images/top-routs/delhi-mtctravel.jpg')}}" alt="MTC top destinations">
                                 </div>
                                 <div class="city-name">
                                    <p class="text-center font-weight-bold py-3">Taxi in Delhi</p>
                                 </div>
                              </div>
                           </a>
                        </div>
                        <div class="col-lg-3 col-sm-6 pt-1">
                           <a target="_blank" href="#">
                              <div class="city-item roundbox mh-100">
                                 <div class="city-img">
                                    <img class="d-block w-100" src="{{url('frontend/images/top-routs/chennai-mtctravel.jpg')}}" alt="MTC top destinations">
                                 </div>
                                 <div class="city-name">
                                    <p class="text-center font-weight-bold py-3">Taxi in Chennai</p>
                                 </div>
                              </div>
                           </a>
                        </div>
                        <div class="col-lg-3 col-sm-6 pt-1">
                           <a target="_blank" href="#">
                              <div class="city-item roundbox mh-100">
                                 <div class="city-img">
                                    <img class="d-block w-100" src="{{url('frontend/images/top-routs/mumbai-mtctravels.jpg')}}" alt="MTC top destinations">
                                 </div>
                                 <div class="city-name">
                                    <p class="text-center font-weight-bold py-3">Taxi in Mumbai</p>
                                 </div>
                              </div>
                           </a>
                        </div>
                     </div>
                     <div class="row hide-sm">
                        <div class="col-lg-3 col-sm-6 pt-1">
                           <a target="_blank" href="#">
                              <div class="city-item roundbox mh-100">
                                 <div class="city-img">
                                    <img class="d-block w-100" src="{{url('frontend/images/top-routs/lucknow-mtctravel.jpg')}}" alt="MTC top destinations">
                                 </div>
                                 <div class="city-name">
                                    <p class="text-center font-weight-bold py-3">Taxi in Luckow</p>
                                 </div>
                              </div>
                           </a>
                        </div>
                        <div class="col-lg-3 col-sm-6 pt-1">
                           <a target="_blank" href="#">
                              <div class="city-item roundbox mh-100">
                                 <div class="city-img">
                                    <img class="d-block w-100" src="{{url('frontend/images/top-routs/hydarabad-mtctravel.jpg')}}" alt="MTC top destinations">
                                 </div>
                                 <div class="city-name">
                                    <p class="text-center font-weight-bold py-3">Taxi in Heydrabad</p>
                                 </div>
                              </div>
                           </a>
                        </div>
                        <div class="col-lg-3 col-sm-6 pt-1">
                           <a target="_blank" href="#">
                              <div class="city-item roundbox mh-100">
                                 <div class="city-img">
                                    <img class="d-block w-100" src="{{url('frontend/images/top-routs/agra-mtctravel.jpg')}}" alt="MTC top destinations">
                                 </div>
                                 <div class="city-name">
                                    <p class="text-center font-weight-bold py-3">Taxi in Agra</p>
                                 </div>
                              </div>
                           </a>
                        </div>
                        <div class="col-lg-3 col-sm-6 pt-1">
                           <a target="_blank" href="#">
                              <div class="city-item roundbox mh-100">
                                 <div class="city-img">
                                    <img class="d-block w-100" src="{{url('frontend/images/top-routs/goa-mtctravel.jpg')}}" alt="MTC top destinations">
                                 </div>
                                 <div class="city-name">
                                    <p class="text-center font-weight-bold py-3">Taxi in Goa</p>
                                 </div>
                              </div>
                           </a>
                        </div>
                     </div>
                  </div>
               </div>
               <!--  <a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-slide="prev">
                  <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                  <span class="sr-only">Previous</span>
                  </a>
                  <a class="carousel-control-next" href="#carouselExampleControls" role="button" data-slide="next">
                  <span class="carousel-control-next-icon" aria-hidden="true"></span>
                  <span class="sr-only">Next</span>
                  </a> -->
            </div>
         </div>
         <div class="col-lg-3 pt-4 m-auto">
            <a href="explore-top-cities.php" class="readmore d-block btn btn-secondary text-center mx-auto">Find More Cities</a>
         </div>
      </div>
   </div>
</div>
<!--end top Destinations -->
<!--services categories -->
<div class="container-fluid pb-5 section-container">
   <div class="container">
      <div class="row mt-4">
         <div class="col-lg-12">
            <h3 class="sub-heading my-0">A car for every occasion</h3>
            <p> MTC offers Outstation Cab, inter-city cabs, and local cabs in India.</p>
         </div>
      </div>
      <div class="row justify-content-center mt-4">
         <div class="col-lg-4 col-md-6 col-sm-12 my-2">
            <div class="box-model">
               <div class="img-holder">
                  <div class="type-1 share-img" style="background-image: url({{url('frontend/images/service-img/mtc-outstation-services.jpg')}});">
                  </div>
                  <div class="overlay"></div>
                 <!--  <div class="share-type">
                     <img src="{{url('frontend/images/icon/car.png')}}">
                  </div> -->
               </div>
               <div class="info-container">
                  <h2 class="heading">Outstation Trips</h2>
                  <p class="comm-info">
                     MTC Travels is an emerging outstation cab provider in India. We endeavor to make the cab booking a simple and delightful experience through our online cab booking portal. We guarantee you of sanitized vehicles in great condition, with courteous chauffeurs 24/7. 
                  </p>
                  <div class="benifits-list">
                     <div class="indiv-item">
                        <img src="{{url('frontend/images/icon/ac-icon.svg')}}" class="icon-holder">
                        <span class="lab">AC<br>Cabs</span>
                     </div>
                     <div class="indiv-item">
                        <img src="{{url('frontend/images/icon/pocket-frendly.svg')}}" class="icon-holder">
                        <span class="lab">Pocket<br>Friendly</span>
                     </div>
                     <div class="indiv-item">
                        <img src="{{url('frontend/images/icon/safe-ride.svg')}}" class="icon-holder">
                        <span class="lab">Safe<br>Rides</span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-lg-4 col-md-6 col-sm-12 my-2">
            <div class="box-model">
               <div class="img-holder">
                  <div class="type-1 share-img" style="background-image: url({{url('frontend/images/service-img/airport-transfer.jpg')}});">
                  </div>
                  <div class="overlay"></div>
                 <!--  <div class="share-type">
                     <img src="{{url('frontend/images/icon/car.png')}}">
                  </div> -->
               </div>
               <div class="info-container">
                  <h2 class="heading">Airport Transfer</h2>
                  <p class="comm-info">
                     We are constantly centered on being as India's best online cab services for Airport transfer employ with courteous chauffeurs. We keep airport transfer least expensive in many cities as we keep our limits low and quality highest guaranteeing that our cabs are examined consistently.
                  </p>
                  <div class="benifits-list">
                     <div class="indiv-item">
                        <img src="{{url('frontend/images/icon/cashless-rides.svg')}}" class="icon-holder">
                        <span class="lab">Cashless<br>Rides</span>
                     </div>
                     <div class="indiv-item">
                        <img src="{{url('frontend/images/icon/ac-icon.svg')}}" class="icon-holder">
                        <span class="lab">Pocket<br>Friendly</span>
                     </div>
                     <div class="indiv-item">
                        <img src="{{url('frontend/images/icon/safe-ride.svg')}}" class="icon-holder">
                        <span class="lab">safe<br>Rides</span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="col-lg-4 col-md-6 col-sm-12 my-2">
            <div class="box-model">
               <div class="img-holder">
                  <div class="type-1 share-img" style="background-image: url({{url('frontend/images/service-img/mtc-local-services.jpg')}});">
                  </div>
                  <div class="overlay"></div>
                 <!--  <div class="share-type">
                     <img src="{{url('frontend/images/icon/car.png')}}">
                  </div> -->
               </div>
               <div class="info-container">
                  <h2 class="heading">Local Rides</h2>
                  <p class="comm-info">
                     We accept that your time ought to be all yours and our experienced chauffeurs will assure that you reach your place very much refreshed and new of yourself; you are additionally picked and dropped in a convenient style so you can capitalize on your brief local rides.
                  </p>
                  <div class="benifits-list">
                     <div class="indiv-item">
                        <img src="{{url('frontend/images/icon/ac-icon.svg')}}" class="icon-holder">
                        <span class="lab">AC<br>Cabs</span>
                     </div>
                     <div class="indiv-item">
                        <img src="{{url('frontend/images/icon/cashless-rides.svg')}}" class="icon-holder">
                        <span class="lab">Cashless<br>rides</span>
                     </div>
                     <div class="indiv-item">
                        <img src="{{url('frontend/images/icon/online-booking.svg')}}" class="icon-holder">
                        <span class="lab">Online<br>Booking</span>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
<!--end services categories -->
<!--why choose us-->
<div class="container-fluid pt-2 pb-5">
   <div class="container">
      <div class="row text-center fea-sec justify-content-center">
         <div class="col-lg-12">
            <h2 class="sub-heading spancolor mb-5">Why Chose MTC Travel?</h2>
         </div>
         <div class="col-lg-2 col-md-2">
            <div class="icon-box">
               <img src="{{url('frontend/images/featureIcons/clean-car.png')}}" alt="sanatised cars" class="feature-icon">
            </div> 
            <p> 100% Sanitized Cars </p> 
         </div>
         <div class="col-lg-2 col-md-2">
            <div class="icon-box">
               <img class="feature-icon" src="{{url('frontend/images/featureIcons/billing.png')}}" alt="Transparent billing">
            </div>
            <p> Easy Payment </p>
         </div>
         <div class="col-lg-2 col-md-2">
            <div class="icon-box">
               <img class="feature-icon" src="{{url('frontend/images/featureIcons/relaible-ser.png')}}" alt="trusted services">
            </div>
            <p> Trusted Services </p>
         </div>
         <div class="col-lg-2 col-md-2">
            <div class="icon-box">
               <img class="feature-icon" src="{{url('frontend/images/featureIcons/Courteous.png')}}" alt="Experienced Chauffeur">
            </div>
            <p>Experienced Chauffeur </p>
         </div>
         <div class="col-lg-2 col-md-2">
            <div class="icon-box">
               <img class="feature-icon" src="{{url('frontend/images/featureIcons/road-trip.png')}}" alt="comfitable trip">
            </div>
            <p> Comfortable Trips </p>
         </div>
          <div class="col-lg-2 col-md-2">
            <div class="icon-box">
               <img class="feature-icon" src="{{url('frontend/images/icon/safe-ride.svg')}}" alt="comfitable trip">
            </div>
            <p> Safety Commited </p>
         </div>
      </div>
   </div>
</div>
<div class="container-fluid py-5 type-slider car-slider">
   <div class="container">
      <div class="row text-center">
         <div class="col-lg-12">
            <h2 class="my-0 spancolor sub-heading"> Exceptional Cars for your Memorable Trips </h2>
            <p class="mt-2 font-sans pt-3">We have a range of cars, so something will perfectly fit your trip</p>
         </div>
      </div>
      <div class="row mt-2">
         <div class="col-lg-12">
            <div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
               <div class="carousel-inner">
                  <div class="carousel-item active">
                     <div class="row text justify-content-center">
                        <div class="col-sm-6 pt-1">
                           <a href="#" class="slide-img" target="_blank">
                              <img class="d-block" src="{{url('frontend/images/slider-img/prime-sedan.png')}}" alt="car for need">
                           </a>
                        </div>
                        <div class="col-sm-6 pt-1">
                           <div class="r-content pt-4">
                              <h2 class="slid-title"> Sedan </h2>
                              <h3 class="slid-subtitle"> Sedans with free Wi-Fi and top drivers </h3>
                              <!-- <p class="slid-desc py-3"> --> 
                                 <ul class="feature-list"> 
                                    <li>Comfortable trips with small families.</li>
                                    <li>24/7 in more Cities of India </li>
                                    <li>Experienced Chauffeur</li>
                                    <li>AC Equipped</li>
                                   </ul>
                              <!-- </p> -->
                           </div>
                           <div class="feature-img">
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Air Conditioned Cab">
                                 <img class="option-img" src="{{url('frontend/images/icon/ac-icon.svg')}}">
                              </div>
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Car Sharing">
                                 <img class="option-img" src="{{url('frontend/images/icon/compact-hatchback.svg')}}" style="height: 25px;">
                              </div>
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Cashless Ride">
                                 <img class="option-img" src="{{url('frontend/images/icon/family-ride.svg')}}">
                              </div>
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Value for Money">
                                 <img class="option-img" src="{{url('frontend/images/icon/value-money.svg')}}">
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="carousel-item">
                     <div class="row">
                        <div class="col-sm-6 pt-1">
                           <a href="#" class="slide-img" target="_blank">
                              <img class="d-block" src="{{url('frontend/images/slider-img/suv.png')}}" alt="car for need">
                           </a>
                        </div>
                        <div class="col-sm-6 pt-1">
                           <div class="r-content">
                              <h2 class="slid-title"> Suv </h2>
                              <h3 class="slid-subtitle"> SUVs with free Wi-Fi and top drivers </h3>
                               <ul class="feature-list"> 
                                    <li> Enjoy first-rate trips with your large families.</li>
                                    <li> Ride with experienced chauffeur</li>
                                    <li> AC Equipped</li>
                                    <li> Luxurious Ride </li>
                                   </ul>
                              <!-- <p class="slid-desc py-4"> A perfect choice of car for your weekend getaways, with plenty of room for everyone including that extra bag. </p> -->
                           </div>
                           <div class="feature-img">
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Air Conditioned Cab">
                                 <img class="option-img" src="{{url('frontend/images/icon/ac-icon.svg')}}">
                              </div>
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Car Sharing">
                                 <img class="option-img" src="{{url('frontend/images/icon/compact-hatchback.svg')}}" style="height: 25px;">
                              </div>
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Cashless Ride">
                                 <img class="option-img" src="{{url('frontend/images/icon/family-ride.svg')}}">
                              </div>
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Value for Money">
                                 <img class="option-img" src="{{url('frontend/images/icon/value-money.svg')}}">
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="carousel-item">
                     <div class="row">
                        <div class="col-sm-6 pt-1">
                           <a href="#" class="slide-img" target="_blank">
                              <img class="d-block" src="{{url('frontend/images/slider-img/prime-suv.png')}}" alt="car for need">
                           </a>
                        </div>
                        <div class="col-sm-6 pt-1">
                           <div class="r-content">
                              <h2 class="slid-title"> Prime Suv </h2>
                              <h3 class="slid-subtitle"> Prime SUVs with free Wi-Fi and top drivers </h3>
                             <!--  <p class="slid-desc py-4"> A perfect choice of car for your weekend getaways, with plenty of room for everyone including that extra bag. </p> -->
                              <ul class="feature-list"> 
                                    <li> Pleasure ride with a large family. </li>
                                    <li> No compromise on luxury</li>
                                    <li> 24/7 in more Cities of India </li>
                                    <li> Top Rated Chauffeur </li>
                                   </ul>
                           </div>
                           <div class="feature-img">
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Air Conditioned Cab">
                                 <img class="option-img" src="{{url('frontend/images/icon/ac-icon.svg')}}">
                              </div>
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Car Sharing">
                                 <img class="option-img" src="{{url('frontend/images/icon/compact-hatchback.svg')}}" style="height: 25px;">
                              </div>
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Cashless Ride">
                                 <img class="option-img" src="{{url('frontend/images/icon/family-ride.svg')}}">
                              </div>
                              <div class="img-block" data-toggle="tooltip" data-placement="top" title="" data-original-title="Value for Money">
                                 <img class="option-img" src="{{url('frontend/images/icon/value-money.svg')}}">
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-slide="prev">
                  <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                  <span class="sr-only">Previous</span>
               </a>
               <a class="carousel-control-next" href="#carouselExampleControls" role="button" data-slide="next">
                  <span class="carousel-control-next-icon" aria-hidden="true"></span>
                  <span class="sr-only">Next</span>
               </a>
            </div>
         </div>
      </div>
   </div>
</div>
<!--Offers Slider End-->
<!--How it works-->
<div class="container-fluid py-5">
   <div class="container">
      <div class="row">
         <div class="col-lg-12 text-center pb-3">
            <h2 class="text-dark sub-heading spancolor">Book us, now</h2>
            <p class="text-dark pt-3">You can easily book online car rental service with us in just 3 Steps:</p>
         </div>
      </div>
      <div class="row mt-3 how-work">
         <div class="col mt-5 d-none d-md-block align-self-center">
            <!--This col is not empty don't delete-->
         </div>
         <div class="col-lg-3 col-md-3  my-lg-0 my-4 text-center border-white rounded p-2 works">
            <div class="WhiteCircle rounded-circle"><i class="fa fa-search fa-2x text-white"></i></div>
            <h4 class="text-dark mt-5 mb-0">Search Car </h4>
            <p class="text-dark mt-2">Let us what sort of your next trip is going to be.</p>
         </div>
         <div class="col mt-5 d-none d-md-block align-self-center">
            <!--This col is not empty don't delete-->
         </div>
         <div class="col-lg-3 col-md-3 my-lg-0 my-4 text-center border-white rounded p-2 works">
            <div class="WhiteCircle rounded-circle"><i class="fa fa-car fa-2x text-white"></i></div>
            <h4 class="text-dark mt-5 mb-0">Choose</h4>
            <p class="text-dark mt-2"> Choose from the wide variety of rental cars available.  </p>
         </div>
         <div class="col mt-5 d-none d-md-block align-self-center">
            <!--This col is not empty don't delete-->
         </div>
         <div class="col-lg-3 col-md-3 my-lg-0 my-4 text-center border-white rounded p-2 works">
            <div class="WhiteCircle rounded-circle"><i class="fa fa-check-circle fa-2x text-white"></i></div>
            <h4 class="text-dark mt-5 mb-0">Pay Online </h4>
            <p class="text-dark mt-2">Confirm your slots by making a secure online payment.</p>
         </div>
         <div class="col mt-5 d-none d-md-block align-self-center">
            <!--This col is not empty don't delete-->
         </div>
      </div>
   </div>
</div>
<!--How it works end-->
<!--support panel-->
<div class="container-fluid light-BGColor why-sec">
   <div class="container">
      <div class="row text-center support-fea justify-content-center">
         <div class="col-lg-2 col-md-3 anchor">
            <img src="{{url('frontend/images/icon/question.png')}}">
            <p>How MTC works?</p>
            <!-- <img class="border-rounded d-block width-100" src="images/safety-feature.png" alt="Grab On Offer 3"> -->
         </div>
         <div class="col-lg-2 col-md-3 anchor">
            <img src="{{url('frontend/images/icon/policy.png')}}">
            <p> Policies </p>
         </div>
         <div class="col-lg-2 col-md-3 anchor">
            <img src="{{url('frontend/images/icon/support.png')}}">
            <p>Help Support </p>
         </div>
         <div class="col-lg-2 col-md-3 anchor">
            <img src="{{url('frontend/images/icon/security.png')}}">
            <p> Safety </p>
         </div>
      </div>
   </div>
</div>
<!--end support panel-->
<!--Featured End-->
<!-- Include Foorter -->
@include('frontend.layouts.footer')
<!-- Include Foorter -->