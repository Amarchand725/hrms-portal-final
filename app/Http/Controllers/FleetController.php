<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserLeave;
use App\Models\VehicleOwner;
use App\Models\VehicleInspection;
use App\Models\userVehicles;
use App\Models\Vehicle;
use App\Models\userAllowance;
use DateTime; 
use DB;
use Illuminate\Validation\Rule;

class FleetController extends Controller
{
    //vehicle manage
    public function manageVehicle()
    {
        $title = 'Manage Vehicles';
        $VehicleOwners=VehicleOwner::where('status',1)->where('deleted_at',NULL)->latest()->get();
        $Vehicles=Vehicle::where('deleted_at',NULL)->latest()->get();
        return view('fleet.manage-vehicle',compact('title','VehicleOwners','Vehicles'));
    }

    public function manageVehiclePost(Request $request)
    {
        $this->validate($request, [
            'vehicle' => 'required',
            'model' => 'required',
            'bodytype' => 'required',
            'assembly' => 'required',
            'modelyear' => 'required',
            'color' => 'required',
            'transmission' => 'required',
            'enginetype' => 'required',
            'enginecapacity' => 'required',
            'mileage' => 'required',
            'registeration_province' => 'required',
            'registeration_city' => 'required',
            'registeration_number' => 'required',
            'engine_number' => 'required',
            'chassis_number' => 'required',
            'owner' => 'required',
            'rent' => 'required',
            'status' => 'required',
        ]);
        try{
            $imageArray=array();
            if($uploadImages=$request->file('images')){
                foreach($uploadImages as $uploadImage){
                    $image = $uploadImage;
                    if(in_array($image->getClientOriginalName(), $request->input('image_names'))){
                        $imageName = rand(). '_vehicle.'. $image->getClientOriginalExtension();
                        $imageStore='upload/vehicle/images/'.$imageName;
                        $image->move(public_path('upload/vehicle/images'), $imageName);
                        array_push($imageArray,$imageStore);
                    }
                }
            }
            $imageArray=implode(",",$imageArray);

            $videoStore='';
            if($request->file('video')){
                $video = $request->file('video');
                $videoName = rand(). '_vehicle.'. $video->getClientOriginalExtension();
                $videoStore='upload/vehicle/video/'.$videoName;
                $video->move(public_path('upload/vehicle/video'), $videoName);
            }

            $insert = array(
                'vehicle' => $request->vehicle,
                'model' => $request->model,
                'bodytype' => $request->bodytype,
                'assembly' => $request->assembly,
                'modelyear' => $request->modelyear,
                'color' => $request->color,
                'transmission' => $request->transmission,
                'enginetype' => $request->enginetype,
                'enginecapacity' => $request->enginecapacity,
                'mileage' => $request->mileage,
                'registeration_province' => $request->registeration_province,
                'registeration_city' => $request->registeration_city,
                'registeration_number' => $request->registeration_number,
                'engine_number' => $request->engine_number,
                'chassis_number' => $request->chassis_number,
                'owner' => $request->owner,
                'rent' => $request->rent,
                'additional' => $request->additional?$request->additional:'',
                'images' => $imageArray?$imageArray:'',
                'video' => $videoStore,
                'status' => $request->status
            );
            Vehicle::create($insert);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function manageVehicleUpdate(Request $request)
    {
        $this->validate($request, [
            'vehicle_update' => 'required',
            'model_update' => 'required',
            'bodytype_update' => 'required',
            'assembly_update' => 'required',
            'modelyear_update' => 'required',
            'color_update' => 'required',
            'transmission_update' => 'required',
            'enginetype_update' => 'required',
            'enginecapacity_update' => 'required',
            'mileage_update' => 'required',
            'registeration_province_update' => 'required',
            'registeration_city_update' => 'required',
            'registeration_number_update' => 'required',
            'engine_number_update' => 'required',
            'chassis_number_update' => 'required',
            'owner_update' => 'required',
            'rent_update' => 'required',
            'status_update' => 'required',
        ]);
        try{

            $imageArray=array();
            if($uploadImages=$request->file('images')){
                foreach($uploadImages as $uploadImage){
                    $image = $uploadImage;
                    if(in_array($image->getClientOriginalName(), $request->input('image_names'))){
                        $imageName = rand(). '_vehicle.'. $image->getClientOriginalExtension();
                        $imageStore='upload/vehicle/images/'.$imageName;
                        $image->move(public_path('upload/vehicle/images'), $imageName);
                        array_push($imageArray,$imageStore);
                    }
                }
                $imageArray=implode(",",$imageArray);
            }
            if($imageArray==''){
                $imageArray=$request->images_old?$request->images_old:'';
            }

            $videoStore=$request->video_old?$request->video_old:'';
            if($request->file('video')){
                $video = $request->file('video');
                $videoName = rand(). '_vehicle.'. $video->getClientOriginalExtension();
                $videoStore='upload/vehicle/video/'.$videoName;
                $video->move(public_path('upload/vehicle/video'), $videoName);
            }

            $update = array(
                'vehicle' => $request->vehicle_update,
                'model' => $request->model_update,
                'bodytype' => $request->bodytype_update,
                'assembly' => $request->assembly_update,
                'modelyear' => $request->modelyear_update,
                'color' => $request->color_update,
                'transmission' => $request->transmission_update,
                'enginetype' => $request->enginetype_update,
                'enginecapacity' => $request->enginecapacity_update,
                'mileage' => $request->mileage_update,
                'registeration_province' => $request->registeration_province_update,
                'registeration_city' => $request->registeration_city_update,
                'registeration_number' => $request->registeration_number_update,
                'engine_number' => $request->engine_number_update,
                'chassis_number' => $request->chassis_number_update,
                'owner' => $request->owner_update,
                'rent' => $request->rent_update,
                'additional' => $request->additional_update?$request->additional_update:'',
                'images' => $imageArray?$imageArray:'',
                'video' => $videoStore,
                'status' => $request->status_update
            );
            Vehicle::find($request->id)->update($update);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function manageVehicleDelete($id)
    {
        Vehicle::find($id)->update(array('deleted_at' => date('Y-m-d H:i:s')));
        return response()->json([
            'status' => true
        ]);
    }
    //vehicle owner
    public function vehicleOwners()
    {
        $title = 'Vehicle Owners';
        $VehicleOwners=VehicleOwner::where('status',1)->where('deleted_at',NULL)->latest()->get();
        return view('fleet.manage-owner',compact('title','VehicleOwners'));
    }

    public function vehicleOwnersPost(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'mobile' => 'required|numeric|min:11',
            'phone' => 'required|numeric|min:11',
            'address' => 'required',
        ]);
        try{
            $insert = array(
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => 1
            );
            VehicleOwner::create($insert);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function vehicleOwnerDelete($id)
    {
        VehicleOwner::find($id)->update(array('deleted_at' => date('Y-m-d H:i:s')));
        return response()->json([
            'status' => true
        ]);
    }

    public function vehicleOwnersedit($id)
    {
        $VehicleOwner = VehicleOwner::where('id',$id)->first();
        return (string) view('fleet.edit_owner', compact('VehicleOwner'));
    }

    public function vehicleOwnersUpdate(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'mobile' => 'required|numeric|min:11',
            'phone' => 'required|numeric|min:11',
            'address' => 'required',
        ]);
        try{
            $update = array(
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => 1
            );
            VehicleOwner::find($request->id)->update($update);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    //vehicle inspection
    public function vehicleInspection()
    {
        $title = 'Vehicle Inspection';
        $VehicleInspections=VehicleInspection::where('deleted_at',NULL)->latest()->get();
        return view('fleet.vehicle-inspection',compact('title','VehicleInspections'));
    }

    public function vehicleInspectionPost(Request $request)
    {
        $this->validate($request, [
            'vehicle' => 'required',
            'deliver' => 'required',
            'receive' => 'required',
        ]);
        try{
            $insert = array(
                'vehicle' => $request->vehicle,
                'deliver' => $request->deliver,
                'receive' => $request->receive,
                'delivery_detail' => $request->delivery_detail?$request->delivery_detail:'',
                'inspection_detail' => $request->inspection_detail?$request->inspection_detail:''
            );
            VehicleInspection::create($insert);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function vehicleInspectionDelete($id)
    {
        VehicleInspection::find($id)->update(array('deleted_at' => date('Y-m-d H:i:s')));
        return response()->json([
            'status' => true
        ]);
    }

    public function vehicleInspectionEdit($id)
    {
        $VehicleInspection = VehicleInspection::where('id',$id)->first();
        return (string) view('fleet.edit_inspection', compact('VehicleInspection'));
    }

    public function vehicleInspectionUpdate(Request $request)
    {
        $this->validate($request, [
            'vehicle' => 'required',
            'deliver' => 'required',
            'receive' => 'required',
        ]);
        try{
            
            $update = array(
                'vehicle' => $request->vehicle,
                'deliver' => $request->deliver,
                'receive' => $request->receive,
                'delivery_detail' => isset($request->delivery_detail)?$request->delivery_detail:'',
                'inspection_detail' => isset($request->inspection_detail)?$request->inspection_detail:''
            );
            VehicleInspection::find($request->id)->update($update);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    //user vehicles
    public function userVehicle()
    {
        $title = 'User Vehicles';
        $userVehicles=userVehicles::where('deleted_at',NULL)->latest()->get();
        return view('fleet.user-vehicles',compact('title','userVehicles'));
    }

    public function userVehiclePost(Request $request)
    {
        $this->validate($request, [
            'user' => 'required',
            'vehicle' => 'required',
            'deliver' => 'required',
        ]);
        try{
            $insert = array(
                'user' => $request->user,
                'vehicle' => $request->vehicle,
                'deliver' => $request->deliver,
                'detail' => $request->detail?$request->detail:''
            );
            userVehicles::create($insert);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function userVehicleDelete($id)
    {
        userVehicles::find($id)->update(array('deleted_at' => date('Y-m-d H:i:s')));
        return response()->json([
            'status' => true
        ]);
    }

    public function userVehicleEdit($id)
    {
        $userVehicles = userVehicles::where('id',$id)->first();
        return (string) view('fleet.edit_userVehicles', compact('userVehicles'));
    }

    public function userVehicleUpdate(Request $request)
    {
        $this->validate($request, [
            'user' => 'required',
            'vehicle' => 'required',
            'deliver' => 'required',
        ]);
        try{
            
            $update = array(
                'user' => $request->user,
                'vehicle' => $request->vehicle,
                'deliver' => $request->deliver,
                'detail' => $request->detail?$request->detail:''
            );
            userVehicles::find($request->id)->update($update);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    //user allowance
    public function userAllowance()
    {
        $title = 'User Vehicle Allowance';
        $userAllowances=userAllowance::where('deleted_at',NULL)->latest()->get();
        return view('fleet.user-allowance',compact('title','userAllowances'));
    }

    public function userAllowancePost(Request $request)
    {
        $this->validate($request, [
            'user' => 'required',
            'vehicle' => 'required',
            'allowance' => 'required',
            'start' => 'required',
        ]);
        try{
            $insert = array(
                'user' => $request->user,
                'vehicle' => $request->vehicle,
                'allowance' => $request->allowance,
                'start' => $request->start,
                'detail' => $request->detail?$request->detail:''
            );
            userAllowance::create($insert);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function userAllowanceDelete($id)
    {
        userAllowance::find($id)->update(array('deleted_at' => date('Y-m-d H:i:s')));
        return response()->json([
            'status' => true
        ]);
    }

    public function userAllowanceEdit($id)
    {
        $userAllowance = userAllowance::where('id',$id)->first();
        return (string) view('fleet.edit_userAllowance', compact('userAllowance'));
    }

    public function userAllowanceUpdate(Request $request)
    {
        $this->validate($request, [
            'user' => 'required',
            'vehicle' => 'required',
            'allowance' => 'required',
            'start' => 'required',
        ]);
        try{
            
            $update = array(
                'user' => $request->user,
                'vehicle' => $request->vehicle,
                'allowance' => $request->allowance,
                'start' => $request->start,
                'detail' => $request->detail?$request->detail:''
            );
            userAllowance::find($request->id)->update($update);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

}
