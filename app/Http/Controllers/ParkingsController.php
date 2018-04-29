<?php

namespace App\Http\Controllers;
use App\Log;
use App\Parking;
use App\Map;
use App\User;
use App\Package;
use App\Package_user;
use App\Photolocation;
use App\Current_map;
use Illuminate\Http\Request;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class ParkingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      if (\Gate::allows('index-userManagers',Auth::user())){
        $park = Parking::all();
        $names = User::all()->pluck('name','id');
        return view('/park.index',['park'=> $park,'names'=>$names]);
      }else if(\Gate::allows('index-parking',Auth::user())){
        $park = Parking::all()->where('id_user','LIKE',Auth::user()->id);
        $names = User::all()->pluck('name','id');
        return view('/park.index',['park'=> $park,'names'=>$names]);
      }else{
          return view('/denieViews.denie');
      }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (Auth::check() && (Auth::user()->level=="parking_owner" || Auth::user()->level=="admin")){
            return view('/park.create');
        }
        else{
          return redirect('/login');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      $request->validate(
          ['location' =>'required',
          'address' =>'required'
        ]);

      $parking=new Parking;
      $parking->location=$request->input('location');
      $parking->id_user = Auth::user()->id;
      $parking->address=$request->input('address');
      if ($request->fileToUpload2!=null){
      $path2 = $request->fileToUpload2->store('/public/photosparking');
      $parking->photo= '/storage/photosparking/'.basename($path2);
      }
      else{
        $parking->photo = '/storage/noimage.png';
      }
      $parking->save();
      $pack = Package_user::all()->where('id_user','like',Auth::user()->id)->first();
      $pack->numbers = $pack->numbers+1;

      $pack->save();

        $log = new Log();
        if (Auth::check()){
           $log->id_user = Auth::user()->id;
        }

        else{
          $log->id_user = '2';
        }
        $users = User::all()->pluck('name','id');
        $log->description = "user ".$log->id_user.' create parking';
        $log->location = $parking->location;
        $log->save();

        return redirect('/parkings');


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Parking  $parking
     * @return \Illuminate\Http\Response
     */
    public function show(Parking $parking)
    {

        $p= Photolocation::all()->where('id_parking','LIKE',$parking->id);
          $map  = Map::all();
          $current_map  = Current_map::all();
         return view('/park.show',['photoslocations'=>$p,'parking'=>$parking,'maps'=>$map,'current_maps'=>$current_map]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Parking  $parking
     * @return \Illuminate\Http\Response
     */
    public function edit(Parking $parking)
    {

          $p=Photolocation::all()->where('id_parking','LIKE',$parking->id);
          return view('park.edit',['parking'=>$parking,'photoslocations'=>$p]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Parking  $parking
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Parking $parking)
    {
      $request->validate(
          ['location' =>'required',
          'address' =>'required'
        ]);
              $parking->location=$request->input('location');
              $parking->address=$request->input('address');
              if ($request->fileToUpload2!=null){
                $path2 = $request->fileToUpload2->store('/public/photosparking');
                $parking->photo= '/storage/photosparking/'.basename($path2);
              }
              $parking->save();
              $log = new Log();
                 $log->id_user = Auth::user()->id;
              $users = User::all()->pluck('name','id');
              $log->description = "user ".$log->id_user.' edit parking';
              $log->location = $parking->location;
              $log->save();
      return redirect('/parkings');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Parking  $parking
     * @return \Illuminate\Http\Response
     */
    public function destroy(Parking $parking)
    {
      if (Auth::user()->level=="admin"){
        $pack = Package_user::all()->where('id_user','like',$parking->id_user)->first();
      }
      else{
        $pack = Package_user::all()->where('id_user','like',Auth::user()->id)->first();
      }
      $pack->numbers = $pack->numbers-1;
      $pack->save();
      $parking->delete();
      $parking->save();


      $log = new Log();
      $log->id_user = Auth::user()->id;
      $users = User::all()->pluck('name','id');
      $log->description = "user ".$log->id_user.' delete parking';
      $log->location = $parking->location;
      $log->save();


    return redirect('/parkings');
    }

    public function addcarpark(Parking $parking)
    {

        return view('/park.addcarpark',['parking'=>$parking]);

    }

    public function updatecarpark(Request $request, Parking $parking)
    {

      $request->validate(
          ['floor' =>'required'
        ]);
            $photolocation=new Photolocation;
            $photolocation->id_parking=$parking->id;

            if ($request->input('list')!=null){
                  $photolocation->canvas=$request->input('list');
            }else{
                $photolocation->canvas='';
            }


            if ($request->fileToUpload!=null){
              $path = $request->fileToUpload->store('/public/photoslocation');
              $photolocation->photo = '/storage/photoslocation/'.basename($path);
              }
              else{
                $photolocation->photo = '/storage/noimage.png';
              }
              $photolocation->floor=$request->input('floor');
              $photolocation->save();


              $s= $request->input('list');
              $str = explode("|", $s);

              for ($i=0; $i <count($str); $i++) {

                  $arstr = explode(",", $str[$i]);

                  if($arstr[0]=='font'){
                      $a=explode(" ",$arstr[1]);


                        for ($j=(int)$a[0]; $j <=(int)$a[2] ; $j++) {
                            $map=new Map;

                            $map->number=$j.substr($a[0],strlen($a[0])-1);
                            $map->id_photo=$photolocation->id;

                            $map->save();

                        }

                  }

              }


        return redirect('/parkings/'.$parking->id.'/edit');
    }


    public function destroyphoto(Photolocation $pho,Request $request)
      {
          $p = Photolocation::findOrFail($request->input('photo_id'));
          $p->delete();
          $log = new Log();
          $log->id_user = Auth::user()->id;
          $users = User::all()->pluck('name','id');
          $log->description = "user ".$log->id_user.' delete parking floor';
          $log->location = $request->location;
          $log->save();
          return redirect('/parkings/'.$request->input('park_id').'/edit');
      }
      public function updatephoto(Request $request, Parking $parking)
      {
        $parking->location=$request->input('location');
        $parking->address=$request->input('address');
        if ($request->fileToUpload2!=null){
          $path2 = $request->fileToUpload2->store('/public/photosparking');
          $parking->photo= '/storage/photosparking/'.basename($path2);
        }
        $parking->save();
        $log = new Log();
        $log->id_user = Auth::user()->id;
        $users = User::all()->pluck('name','id');
        $log->description = "user ".$log->id_user.' add parking floor';
        $log->location = $parking->location;
        $log->save();
        $p=Photolocation::all()->where('id_parking','LIKE',$parking->id);

        return view('park.editphotolocation',['parking'=>$parking,'photoslocations'=>$p]);

      }
      public function editphoto(Parking $parking)
      {
            $p=Photolocation::all()->where('id_parking','LIKE',$parking->id);
            return view('park.editphotolocation',['parking'=>$parking,'photoslocations'=>$p]);
      }

      public function updatemap(Request $request)
      {
        if(Auth::check() && Auth::user()->level=='member'
        && (Package_user::all()->where('id_user','LIKE',Auth::user()->id)->first()->numbers
                                            <
          Package::all()->where('id','LIKE',Package_user::all()->where('id_user','LIKE',Auth::user()->id)->first()->id_package)->first()->limit) ) {

          $map=Map::all()->where('id_photo','LIKE',$request->input('selectmap2'))->where('number','LIKE',$request->input('selectmap'))->first();
          $current_map=new Current_map;
          $current_map->id_user=Auth::user()->id;
          $current_map->id_map=$map->id;
          $pass = str_random(64);
          while ($pass == $current_map->password){
            $pass = str_random(64);
          }
          $current_map->password=$pass;
          $current_map->status='empty';
          $current_map->save();

          $pack = Package_user::all()->where('id_user','like',Auth::user()->id)->first();
          $pack->numbers = $pack->numbers+1;
          $pack->save();

          $log = new Log();
             $log->id_user = Auth::user()->id;
          $users = User::all()->pluck('name','id');
          $log->description = "user ".$log->id_user.' reserve the park ';
          $id_parking = Photolocation::all()->where('id','LIKE',$map->id_photo)->first()->id_parking;
          $loca = Parking::all()->where('id','LIKE',$id_parking)->first()->location;
          $log->location = $loca;
          $log->save();

          return $this->InfoParking();

        }else{
          return  redirect('/package');
        }
      }

      public function readQRcode($token){
        // return view('park.readQRcode');
          if (count(Current_map::all()->where('password','LIKE',$token))) {
            $current = Current_map::all()->where('password','LIKE',$token)->last();
            if ($current->status=="empty")
            {
              $current->status = "full";
              $pass = str_random(64);
              while ($pass == $current->password){
                $pass = str_random(64);
              }
              $current->password = $pass;
              $current->save();
                  $log = new Log();
                     $log->id_user = $current->id_user;
                  $users = User::all()->pluck('name','id');
                  $log->description = "user ".$log->id_user.' enter the park ';
                  $id_photo = Map::all()->pluck('id_photo','id')[$current->id_map];
                  $id_parking = Photolocation::all()->pluck('id_parking','id')[$id_photo];
                  $log->location = Parking::all()->where('id','LIKE',$id_parking)->first()->location;
                  $log->save();
                  return view('park.readQRcode',['word'=>'Enter']);
            }
            else{
              $this->deletereserve($current);
              $log = new Log();
                 $log->id_user = $current->id_user;
              $users = User::all()->pluck('name','id');
              $log->description = "user ".$log->id_user.' leave the park ';
              $id_photo = Map::all()->pluck('id_photo','id')[$current->id_map];
              $id_parking = Photolocation::all()->pluck('id_parking','id')[$id_photo];
              $log->location = Parking::all()->where('id','LIKE',$id_parking)->first()->location;
              $log->save();
              return view('park.readQRcode',['word'=>'Leave']);
            }
          }
          else{
             return view('park.invalidQRcode');
          }

      }
      public function genQRcode(){
        if (Auth::check()) {
                    $qrCode = new QrCode('localhost:8000/readQRcode/'.Current_map::all()->where('id_user','LIKE',Auth::user()->id)->last()->password );
                    header('Content-Type: '.$qrCode->getContentType());
                    // Save it to a file
                    // $qrCode->writeFile(__DIR__.'/qrcode.png');
                    // Create a response object
                    $response = new QrCodeResponse($qrCode);
                    return $response;
        }
        else{
          return view('/login');
        }

      }
      public function InfoParking(){
          $cur_map = Current_map::all()->where('id_user','LIKE',Auth::user()->id)->first();
          if ($cur_map!=null){
            $map = Map::all()->where('id','LIKE',$cur_map->id_map)->first();

            $photo = Photolocation::all()->where('id','LIKE',$map->id_photo)->first();

            $parking = Parking::all()->where('id','LIKE',$photo->id_parking)->first();
            $timeout = Carbon::parse($cur_map->created_at)->addMinutes(30);

              return view('/park.infoparking',['parking'=>$parking,'timeOut'=>$timeout,'map'=>$map,'photolocation'=>$photo,'current_map'=>$cur_map]);
          }
          else{
              return view('/park.infoparking');
          }
      }

      public function deletereserve(Current_map $current_map){
            $current_map->delete();
            $pack= Package_user::all()->where('id_user','LIKE',Auth::user()->id)->first();
            $pack->numbers=$pack->numbers-1;
            $pack->save();
              return view('/park.infoparking');
      }


}
