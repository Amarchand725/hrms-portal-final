<?php

namespace App\Http\Controllers\Admin;

use DB;
use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Models\AttendanceAdjustment;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\URL;
use App\Notifications\ImportantNotification;

class AttendanceAdjustmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $user_slug = null)
    {
        $this->authorize('mark_attendance-list');
        $title = 'All Attendance Adjustments';

        $data = [];
        $url = '';
        $data['employees'] = User::where('is_employee', 1)->where('status', 1)->get();

        if(!empty($user_slug)){
            $user = User::where('slug', $user_slug)->first();
            $url = URL::to('mark_attendance/'.$user->slug);
            // $model = AttendanceAdjustment::where('employee_id', $user->id)->get();
            
            $model = [];
            AttendanceAdjustment::where('employee_id', $user->id)
                ->latest()
                ->chunk(100, function ($adjustments) use (&$model) {
                    foreach ($adjustments as $adjustment) {
                        $model[] = $adjustment;
                    }
            });
        }else{
            $user = Auth::user();
            // $model = AttendanceAdjustment::latest()->get();
            
            $model = [];
            AttendanceAdjustment::latest()
                ->chunk(100, function ($adjustments) use (&$model) {
                    foreach ($adjustments as $adjustment) {
                        $model[] = $adjustment;
                    }
            });
        }

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('mark_type', function ($model) {
                    $label = '';

                    switch ($model->mark_type) {
                        case 'absent':
                            $label = '<span class="badge bg-label-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-danger" data-bs-original-title="Absent">Absent</span>';
                            break;
                        case 'firsthalf':
                            $label = '<span class="badge bg-label-warning" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-info" data-bs-original-title="Half Day Leave">Half Day</span>';
                            break;
                        case 'lateIn':
                            $label = '<span class="badge bg-label-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-success" data-bs-original-title="Late In">Late In</span>';
                            break;
                        case 'fullday':
                            $label = '<span class="badge bg-label-success" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-success" data-bs-original-title="Full Day">Full Day</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('attendance_id', function ($model) {
                    if(isset($model->hasAttendance) && !empty($model->hasAttendance->in_date)){
                        return '<span class="text-primary fw-semibold">'.date('d, M Y h:i A', strtotime($model->hasAttendance->in_date)).'</span>';
                    }else{
                        return '-';
                    }
                })
                ->editColumn('employee_id', function ($model) {
                    return view('admin.attendance_adjustments.employee-profile', ['model' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('admin.attendance_adjustments.action', ['model' => $model])->render();
                })
                ->rawColumns(['mark_type', 'employee_id', 'attendance_id', 'action'])
                ->make(true);
        }

        return view('admin.attendance_adjustments.index', compact('title', 'user', 'data', 'url'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        
        try{
            $model = AttendanceAdjustment::where('attendance_id', $request->attendance_id)->first();

            $mark_type = $request->mark_type;
            if($mark_type=='firsthalf'){
                $mark_type = 'halfday';
            }

            if(!empty($model)){
                $model->created_by = Auth::user()->id;
                $model->employee_id = $request->user_id;
                $model->mark_type = $mark_type;
                $model->save();

                \LogActivity::addToLog('New Attendance Adjustment Mark Added');
            }else{
                $model = AttendanceAdjustment::create([
                    'created_by' => Auth::user()->id,
                    'employee_id' => $request->user_id,
                    'attendance_id' => $request->attendance_id,
                    'mark_type' => $mark_type,
                ]);

                \LogActivity::addToLog('New Attendance Adjustment Mark Added');
            }
            
            DB::commit();
            
            $login_user = Auth::user();
            
            $notification_data = [
                'id' => $model->id,
                'date' => date('d-m-Y', strtotime($model->hasAttendance->in_date)),
                'type' => $mark_type,
                'name' => $login_user->first_name.' '.$login_user->last_name,
                'profile' => $login_user->profile->profile,
                'title' => 'Your attendance date "'. date('d-m-Y', strtotime($model->hasAttendance->in_date)) .'" has been adjusted',
                'reason' => 'Adjusted.',
            ];

            if(isset($notification_data) && !empty($notification_data)){
                $model->hasEmployee->notify(new ImportantNotification($notification_data));
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            // return response()->json(['error' => $e->getMessage()]);
            return $e->getMessage();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->authorize('tickets-delete');
        $model = AttendanceAdjustment::where('id', $id)->delete();
        if($model){
            return response()->json([
                'status' => true,
            ]);
        }else{
            return false;
        }
    }
}
