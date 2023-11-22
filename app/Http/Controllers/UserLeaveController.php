<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\UserLeave;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Discrepancy;
use Illuminate\Http\Request;
use App\Models\DepartmentUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Models\UserEmploymentStatus;
use Yajra\DataTables\Facades\DataTables;
use App\Notifications\DiscrepancyNotification;
use App\Notifications\ImportantNotification;

class UserLeaveController extends Controller
{
    public function index(Request $request)
    {   
        $this->authorize('employee_leave_requests-list');
        $title = 'My Leaves';
        $user = Auth::user();

        //updated year tensure 26 june to 25 june of next year.
        $currentYear = Carbon::now()->year;
        $leaveYearStart = Carbon::createFromDate($currentYear, 6, 26); // June 26th of the current year
        $leaveYearEnd = Carbon::createFromDate($currentYear + 1, 6, 25); // June 25th of the next year
        
        $leave_types = LeaveType::where('status', 1)->latest()->get();

        // Calculate the total used leaves within the leave year
        // $model = UserLeave::where('user_id', $user->id)
        //     ->whereBetween('start_at', [$leaveYearStart, $leaveYearEnd])
        //     ->orderby('status', 'asc')->get();
          
        $model = [];  
        UserLeave::where('user_id', $user->id)
            ->whereBetween('start_at', [$leaveYearStart, $leaveYearEnd])
            ->latest()
            ->chunk(100, function ($user_leaves) use (&$model) {
                foreach ($user_leaves as $user_leave) {
                    $model[] = $user_leave;
                }
        });
            
        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Approved</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">Pending</span>';
                            break;
                        case 2:
                            $label = '<span class="badge bg-label-warning" text-capitalized="">Rejected</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('behavior_type', function ($model) {
                    if($model->behavior_type=='firsthalf'){
                        return '<span class="badge bg-label-info">First Half</span>';
                    }elseif($model->behavior_type=='lasthalf'){
                        return '<span class="badge bg-label-warning">Last Half</span>';
                    }else{
                        if($model->behavior_type=='absent'){
                            return '<span class="badge bg-label-danger">Absent</span>';
                        }else{
                            return '<span class="badge bg-label-primary">'.$model->behavior_type.'</span>';
                        }
                    }
                })
                ->editColumn('start_at', function ($model) {
                    if($model->duration <= 1){
                        return '<span class="fw-semibold"><b>'.Carbon::parse($model->start_at)->format('d-M-Y') . '</b>';
                    }else{
                        return '<span class="fw-semibold"><b>'.Carbon::parse($model->start_at)->format('d-M-Y') . '</b> to <b>' . Carbon::parse($model->end_at)->format('d-M-Y').'</b></span>';
                    }
                })
                ->editColumn('duration', function ($model) {
                    return '<span class="text-primary fw-semibold">'.$model->duration.'</span>';
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('user_id', function ($model) {
                    return view('admin.user_leaves.employee-profile', ['model' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('admin.user_leaves.action', ['model' => $model])->render();
                })
                ->rawColumns(['status', 'start_at', 'duration', 'behavior_type', 'user_id', 'action'])
                ->make(true);
        }
        
        $user_leave_report = hasExceededLeaveLimit($user);
        $remaining_filable_leaves = $user_leave_report['total_remaining_leaves'];

        return view('admin.user_leaves.index', compact('title', 'user', 'leave_types', 'remaining_filable_leaves'));
    }

    public function store(Request $request){
        if(isset($request->apply_leave)){
            $this->validate($request, [
                'leave_type_id' => ['required'],
                'start_at' => ['required'],
                'end_at' => ['required'],
                'reason' => ['required', 'max:255'],
            ]);
            
            $request['user_slug'] = Auth::user()->slug;
        }else{
            $this->validate($request, [
                'reason' => ['required', 'max:255'],
            ]);
        }
        
        $max_allow_discrepancies = settings();
        $max_discrepancies = 6; //default
        if(!empty($max_allow_discrepancies)){
           $max_discrepancies = $max_allow_discrepancies->max_discrepancies; 
        }
        
        $currentMonthStart = Carbon::now()->subMonth()->startOfMonth()->addDays(25);
        $currentMonthEnd = Carbon::now()->startOfMonth()->addDays(24);

        $user = User::where('slug', $request->user_slug)->first();
        
        $login_user = Auth::user();
        if($login_user->hasRole('Department Manager')){
            $department = Department::where('manager_id', $login_user->id)->first();
            $manager = $department->parentDepartment->manager;
        }elseif($login_user->hasRole('Employee')){
            $manager = $login_user->departmentBridge->department->manager;
        }
        
        $department_id = '';
        if(isset($user->departmentBridge) && !empty($user->departmentBridge->department_id)){
            $department_id = $user->departmentBridge->department_id;
        }

        if(isset($request->form_type) && $request->form_type=='pop-up-modal'){
            $applied_dates = json_decode($request->applied_dates);
        }

        DB::beginTransaction();

        try{
            $user_leave_report = hasExceededLeaveLimit(auth()->user());
            $remaining_filable_leaves = $user_leave_report['total_remaining_leaves'];
        
            if(isset($applied_dates) && !empty($applied_dates)){
                $duration = 0;
                foreach($applied_dates as $applied_date){
                    if($applied_date->type=='firsthalf' || $applied_date->type=='lasthalf' || $applied_date->type=='absent') {
                        if($applied_date->type=='firsthalf' || $applied_date->type=='lasthalf') {
                            $duration += 0.5;
                        } else {
                            $duration += 1;
                        }
                    } 
                }
                if($remaining_filable_leaves >= $duration){
                    foreach($applied_dates as $applied_date){
                        if($applied_date->type=='firsthalf' || $applied_date->type=='lasthalf' || $applied_date->type=='absent') {
                            $duration = 0;
                            $leave_type_id = '';
                            if($applied_date->type=='firsthalf' || $applied_date->type=='lasthalf') {
                                $duration = 0.5;
    
                                $leave_type_id = LeaveType::where('name', 'Half-Day')->first()->id;
                            } else {
                                $duration = 1;
    
                                $leave_type_id = LeaveType::where('name', 'Annual')->first();
                                $leave_type_id = $leave_type_id->id;
                            }
                            
                            $leave_type = '';
                            if($applied_date->type=='firsthalf'){
                                $leave_type = 'First Half';
                            }elseif($applied_date->type=='lasthalf'){
                                $leave_type = 'Last Half';
                            }else{
                                $leave_type = 'Full Day';
                            }
                            
                            $status = 0;
                            $notification_title = 'has applied for '. $leave_type . ' leave.';
                            if(Auth::user()->hasRole('Admin')){
                                $user_id = $applied_date->user_id;
                                $status = 1;
                                $notification_title = 'Admin has approved your '. $leave_type . ' leave.';
                            }else{
                                $user_id = $user->id;
                            }
                            
                            $user_leave = UserLeave::create([
                                'department_id' => $department_id,
                                'leave_type_id' => $leave_type_id,
                                'user_id' => $user_id,
                                'start_at' => date('Y-m-d', strtotime($applied_date->date)),
                                'end_at' => date('Y-m-d', strtotime($applied_date->date)),
                                'duration' => $duration,
                                'behavior_type' => $applied_date->type,
                                'reason' => $request->reason,
                                'status' => $status,
                            ]);
                            
                            $notification_data = [
                                'id' => $user_leave->id,
                                'date' => $user_leave->start_at,
                                'type' => $leave_type,
                                'profile' => $login_user->profile->profile,
                                'name' => $login_user->first_name.' '.$login_user->last_name,
                                'title' => $notification_title,
                                'reason' => $user_leave->reason,
                            ];
                            
                            if(Auth::user()->hasRole('Admin') && isset($notification_data) && !empty($notification_data)){
                                $user = User::where('id', $user_id)->first();
                                $user->notify(new DiscrepancyNotification($notification_data));
                            }else{
                                $manager->notify(new DiscrepancyNotification($notification_data));
                            }
                        } elseif($applied_date->type=='lateIn' || $applied_date->type=='earlyout') {
                            $attendance = Attendance::where('id', $applied_date->date)->first(); //for latein and earlyout we pass attendance id so this is id not date.
                            
                            $discrepancy_type = '';
                            if($applied_date->type=='lateIn'){
                                $discrepancy_type = 'Late In';
                            }elseif($applied_date->type=='earlyout'){
                                $discrepancy_type = 'Early Out';
                            }
                            
                            $status = 0;
                            $notification_title = 'has applied for '. $discrepancy_type . ' discrepancy.';
                            if(Auth::user()->hasRole('Admin')){
                                $user_id = $applied_date->user_id;
                                $status = 1;
                                $notification_title = 'Admin has approved your '. $discrepancy_type . ' discrepancy.';
                            }else{
                                $user_id = $user->id;
                            }
                            
                            $discrepancies_count = Discrepancy::where('user_id', $user_id)->whereBetween('date', [$currentMonthStart, $currentMonthEnd])->count();
                            
                            if($discrepancies_count >= $max_discrepancies){
                                $user_discrepancy = Discrepancy::create([
                                    'user_id' => $user_id,
                                    'attendance_id' => $attendance->id,
                                    'date' => date('Y-m-d', strtotime($attendance->in_date)),
                                    'type' => $applied_date->type,
                                    'description' => $request->reason,
                                    'is_additional' => 1,
                                    'status' => $status,
                                ]);
                            }else{
                                $user_discrepancy = Discrepancy::create([
                                    'user_id' => $user_id,
                                    'attendance_id' => $attendance->id,
                                    'date' => date('Y-m-d', strtotime($attendance->in_date)),
                                    'type' => $applied_date->type,
                                    'description' => $request->reason,
                                    'status' => $status,
                                ]);
                            }
    
                            $notification_data = [
                                'id' => $user_discrepancy->id,
                                'date' => $user_discrepancy->date,
                                'type' => $user_discrepancy->type,
                                'profile' => $login_user->profile->profile,
                                'name' => $login_user->first_name.' '.$login_user->last_name,
                                'title' => $notification_title,
                                'reason' => $user_discrepancy->description,
                            ];
                            
                            if(Auth::user()->hasRole('Admin') && isset($notification_data) && !empty($notification_data)){
                                $user = User::where('id', $user_id)->first();
                                $user->notify(new DiscrepancyNotification($notification_data));
                            }else{
                                $manager->notify(new DiscrepancyNotification($notification_data));
                            }
                        }
                    }
                }else{
                    return response()->json(['error' => "You don't have leaves in your account balance, visit leaves -> leave report."]);
                }
            }else{
                if(isset($request->apply_leave)){
                    $leave_type = LeaveType::where('id', $request->leave_type_id)->first();
                    
                    $numberOfDays = 0;
            
                    $startDate = Carbon::parse($request->start_at);
                    $endDate = Carbon::parse($request->end_at);
                    
                    // Calculate the difference in days
                    $numberOfDays = $endDate->diffInDays($startDate) + 1;
                    
                    if($numberOfDays<1){
                        $numberOfDays = $leave_type->amount;
                    }
                    
                    if($remaining_filable_leaves >= $numberOfDays){
                        $user_leave = UserLeave::create([
                            'department_id' => $department_id,
                            'leave_type_id' => $request->leave_type_id,
                            'user_id' => $user->id,
                            'is_applied' => 1,
                            'start_at' => date('Y-m-d', strtotime($request->start_at)),
                            'end_at' => date('Y-m-d', strtotime($request->end_at)),
                            'duration' => $numberOfDays,
                            'behavior_type' => $leave_type->name,
                            'reason' => $request->reason,
                        ]);
                        
                        $notification_data = [
                            'id' => $user_leave->id,
                            'date' => $user_leave->start_at,
                            'type' => $user_leave->behavior_type,
                            'profile' => $login_user->profile->profile,
                            'name' => $login_user->first_name.' '.$login_user->last_name,
                            'title' => 'has applied for '. $user_leave->behavior_type . ' leave.',
                            'reason' => $user_leave->reason,
                        ];
                        
                        if(isset($notification_data) && !empty($notification_data)){
                            $manager->notify(new DiscrepancyNotification($notification_data));
                        }
                    }else{
                        return response()->json(['error' => "You don't have leaves in your account balance, visit leaves -> leave report."]);
                    }
                }else if($request->type=='firsthalf' || $request->type=='lasthalf' || $request->type=='absent') {
                    $duration = 0;
                    if($request->type=='firsthalf' || $request->type=='lasthalf') {
                        $duration = 0.5;
                    } else {
                        $duration = 1;
                    }
                    
                    $leave_type = '';
                    if($request->type=='firsthalf'){
                        $leave_type = 'First Half';
                    }elseif($request->type=='lasthalf'){
                        $leave_type = 'Last Half';
                    }else{
                        $leave_type = 'Full Day';
                    }
                    
                    $status = 0;
                    $notification_title = 'has applied for '. $leave_type . ' leave.';
                    if(Auth::user()->hasRole('Admin')){
                        $status = 1;
                        $notification_title = 'Admin has approved your '. $leave_type . ' leave.';
                    }
                    
                    if($remaining_filable_leaves >= $duration){
                        $user_leave = UserLeave::create([
                            'department_id' => $department_id,
                            'leave_type_id' => $request->leave_type_id,
                            'user_id' => $user->id,
                            'start_at' => date('Y-m-d', strtotime($request->date)),
                            'end_at' => date('Y-m-d', strtotime($request->date)),
                            'duration' => $duration,
                            'behavior_type' => $request->type,
                            'reason' => $request->reason,
                            'status' => $status,
                        ]);
    
                        $notification_data = [
                            'id' => $user_leave->id,
                            'date' => $user_leave->start_at,
                            'type' => $user_leave->behavior_type,
                            'profile' => $login_user->profile->profile,
                            'name' => $login_user->first_name.' '.$login_user->last_name,
                            'title' => $notification_title,
                            'reason' => $user_leave->reason,
                        ];
                        
                        if(Auth::user()->hasRole('Admin') && isset($notification_data) && !empty($notification_data)){
                            $user->notify(new DiscrepancyNotification($notification_data));
                        }else{
                            $manager->notify(new DiscrepancyNotification($notification_data));
                        }
                    }else{
                        return response()->json(['error' => "You don't have leaves in your account balance, visit leaves -> leave report."]);
                    }
                }elseif($request->type=='lateIn' || $request->type=='earlyout') {
                    $attendance = Attendance::where('id', $request->date)->first();

                    $discrepancies_count = Discrepancy::where('user_id', $user->id)->whereBetween('date', [$currentMonthStart, $currentMonthEnd])->count();
                    $discrepancy_type = '';
                    if($request->type=='lateIn'){
                        $discrepancy_type = 'Late In';
                    }elseif($request->type=='earlyout'){
                        $discrepancy_type = 'Early Out';
                    }
                    
                    $status = 0;
                    $notification_title = 'has applied for '. $discrepancy_type . ' discrepancy.';
                    if(Auth::user()->hasRole('Admin')){
                        $status = 1;
                        $notification_title = 'Admin has approved your '. $discrepancy_type . ' discrepancy.';
                    }
                    if($discrepancies_count >= $max_discrepancies){
                        $user_discrepancy = Discrepancy::create([
                            'user_id' => $user->id,
                            'attendance_id' => $request->date,
                            'date' => date('Y-m-d', strtotime($attendance->in_date)),
                            'type' => $request->type,
                            'description' => $request->reason,
                            'is_additional' => 1,
                            'status' => $status,
                        ]);
                    }else{
                        $user_discrepancy = Discrepancy::create([
                            'user_id' => $user->id,
                            'attendance_id' => $request->date,
                            'date' => date('Y-m-d', strtotime($attendance->in_date)),
                            'type' => $request->type,
                            'description' => $request->reason,
                            'status' => $status,
                        ]);
                    }
                    
                    $notification_data = [
                        'id' => $user_discrepancy->id,
                        'date' => $user_discrepancy->date,
                        'type' => $user_discrepancy->type,
                        'profile' => $login_user->profile->profile,
                        'name' => $login_user->first_name.' '.$login_user->last_name,
                        'title' => $notification_title,
                        'reason' => $user_discrepancy->description,
                    ];

                    if(Auth::user()->hasRole('Admin') && isset($notification_data) && !empty($notification_data)){
                        $user->notify(new DiscrepancyNotification($notification_data));
                    }else{
                        $manager->notify(new DiscrepancyNotification($notification_data));
                    }
                }
            }

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function teamLeaves(Request $request, $user_slug=null){
        $title = 'Team Leaves';
        
        $userWithAdminRole = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin'); // Replace 'admin' with the actual role name.
        })->first();

        $departments = [];
        $employees = [];
        $url = '';
        $employees_ids = [];

        $department_users = DepartmentUser::where('end_date',  NULL)->get();

        foreach($department_users as $department_user){
            $emp_data = User::where('status', 1)->where('is_employee', 1)->where('id', $department_user->user_id)->where('id', '!=', Auth::user()->id)->first(['id', 'first_name', 'last_name', 'slug']);
            if(!empty($emp_data)){
                $employees[] = $emp_data;
                $employees_ids[] = $department_user->user_id;
            }
        }
        
        if(!empty($user_slug) && $user_slug != 'All') {
            $user = User::where('slug', $user_slug)->first();
            $url = URL::to('team/leaves/'.$user_slug);
            // $model = UserLeave::where('user_id', $user->id)->orderby('status', 'asc')->get();
            
            $model = [];  
            UserLeave::where('user_id', $user->id)
                ->latest()
                ->chunk(100, function ($user_leaves) use (&$model) {
                    foreach ($user_leaves as $user_leave) {
                        $model[] = $user_leave;
                    }
            });
        }else{
            $user = User::where('slug', $userWithAdminRole->slug)->first();
            // $model = UserLeave::whereIn('user_id', $employees_ids)->orderby('status', 'asc')->get();
            
            $model = [];  
            UserLeave::whereIn('user_id', $employees_ids)
                ->latest()
                ->chunk(100, function ($user_leaves) use (&$model) {
                    foreach ($user_leaves as $user_leave) {
                        $model[] = $user_leave;
                    }
            });
        }
        
        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->addColumn('select', function ($model) {
                    return view('admin.user_leaves.check', ['model' => $model])->render();
                })
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Approved</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">Pending</span>';
                            break;
                        case 2:
                            $label = '<span class="badge bg-label-warning" text-capitalized="">Rejected</span>';
                            break;
                    }
                    return $label;
                })
                ->editColumn('behavior_type', function ($model) {
                    if($model->behavior_type=='firsthalf'){
                        return '<span class="badge bg-label-info">First Half</span>';
                    }elseif($model->behavior_type=='lasthalf'){
                        return '<span class="badge bg-label-warning">Last Half</span>';
                    }else{
                        if($model->behavior_type=='absent'){
                            return '<span class="badge bg-label-danger">Absent</span>';
                        }else{
                            return '<span class="badge bg-label-primary">'.$model->behavior_type.'</span>';
                        }
                    }
                })
                ->editColumn('start_at', function ($model) {
                    if($model->duration <= 1){
                        return '<span class="fw-semibold"><b>'.Carbon::parse($model->start_at)->format('d-M-Y') . '</b>';
                    }else{
                        return '<span class="fw-semibold"><b>'.Carbon::parse($model->start_at)->format('d-M-Y') . '</b> to <b>' . Carbon::parse($model->end_at)->format('d-M-Y').'</b></span>';
                    }
                })
                ->editColumn('duration', function ($model) {
                    return '<span class="text-primary fw-semibold">'.$model->duration.'</span>';
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('user_id', function ($model) {
                    return view('admin.user_leaves.employee-profile', ['model' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('admin.user_leaves.action', ['model' => $model])->render();
                })
                ->rawColumns(['select', 'status', 'duration', 'behavior_type', 'user_id', 'start_at', 'action'])
                ->make(true);
        }

        return view('admin.user_leaves.team_leaves', compact('title', 'user', 'employees', 'url'));
    }
    
    public function managerTeamLeaves(Request $request, $user_slug=null){
        $this->authorize('manager_team_leaves-list');
        $title = 'Team Leaves';

        $login_user = User::where('id', Auth::user()->id)->first();

        $role = $login_user->getRoleNames()->first();
        foreach($login_user->getRoleNames() as $user_role){
            if($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $departments = [];
        $employees = [];
        $url = '';
        $employees_ids = [];
        $dept_ids = [];

        $department = Department::where('manager_id', $login_user->id)->first();
        if(isset($department) && !empty($department->id)){
            $dept_ids[] = $department->id;
            
            $sub_dep = Department::where('parent_department_id', $department->id)->where('manager_id', Auth::user()->id)->first();
            if(!empty($sub_dep)){
                $dept_ids[] = $sub_dep->id;
            }else{
                $sub_deps = Department::where('parent_department_id', $department->id)->get();    
                if(!empty($sub_deps) && count($sub_deps)){
                    foreach($sub_deps as $sub_dept){
                        $dept_ids[] = $sub_dept->id;
                    }
                }
            }
        }
        
        $department_users = DepartmentUser::orderby('id', 'desc')->whereIn('department_id',  $dept_ids)->where('end_date', NULL)->get();
        foreach($department_users as $department_user){
            $user = User::where('status', 1)->where('is_employee', 1)->where('id', $department_user->user_id)->where('id', '!=', Auth::user()->id)->first(['first_name', 'slug']);
            if(!empty($user)){
                $employees[] = User::where('status', 1)->where('is_employee', 1)->where('id', $department_user->user_id)->first(['id', 'first_name', 'last_name', 'slug']);
                $employees_ids[] = $department_user->user_id;
            }
        }

        if(!empty($user_slug) && $user_slug != 'All') {
            $user = User::where('slug', $user_slug)->first();
            $url = URL::to('manager/team/leaves/'.$user_slug);
            // $model = UserLeave::where('user_id', $user->id)->orderby('status', 'asc')->get();
            
            $model = [];  
            UserLeave::where('user_id', $user->id)
                ->latest()
                ->chunk(100, function ($user_leaves) use (&$model) {
                    foreach ($user_leaves as $user_leave) {
                        $model[] = $user_leave;
                    }
            });
        }else{
            $user = User::where('slug', $login_user->slug)->first();
            // $model = UserLeave::whereIn('user_id', $employees_ids)->orderby('status', 'asc')->get();
            $model = [];  
            UserLeave::whereIn('user_id', $employees_ids)
                ->latest()
                ->chunk(100, function ($user_leaves) use (&$model) {
                    foreach ($user_leaves as $user_leave) {
                        $model[] = $user_leave;
                    }
            });
        }
        
        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->addColumn('select', function ($model) {
                    return view('admin.user_leaves.check', ['model' => $model])->render();
                })
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Approved</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">Pending</span>';
                            break;
                        case 2:
                            $label = '<span class="badge bg-label-warning" text-capitalized="">Rejected</span>';
                            break;
                    }
                    return $label;
                })
                ->editColumn('behavior_type', function ($model) {
                    if($model->behavior_type=='firsthalf'){
                        return '<span class="badge bg-label-info">First Half</span>';
                    }elseif($model->behavior_type=='lasthalf'){
                        return '<span class="badge bg-label-warning">Last Half</span>';
                    }else{
                        if($model->behavior_type=='absent'){
                            return '<span class="badge bg-label-danger">Absent</span>';
                        }else{
                            return '<span class="badge bg-label-primary">'.$model->behavior_type.'</span>';
                        }
                    }
                })
                ->editColumn('start_at', function ($model) {
                    if($model->duration <= 1){
                        return '<span class="fw-semibold"><b>'.Carbon::parse($model->start_at)->format('d-M-Y') . '</b>';
                    }else{
                        return '<span class="fw-semibold"><b>'.Carbon::parse($model->start_at)->format('d-M-Y') . '</b> to <b>' . Carbon::parse($model->end_at)->format('d-M-Y').'</b></span>';
                    }
                })
                ->editColumn('duration', function ($model) {
                    return '<span class="text-primary fw-semibold">'.$model->duration.'</span>';
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('user_id', function ($model) {
                    return view('admin.user_leaves.employee-profile', ['model' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('admin.user_leaves.action', ['model' => $model])->render();
                })
                ->rawColumns(['select', 'status', 'duration', 'behavior_type', 'user_id', 'start_at', 'action'])
                ->make(true);
        }

        return view('admin.user_leaves.manager_team_leaves', compact('title', 'user', 'employees', 'url'));
    }

    public function leaveReport(Request $request, $user_slug = null)
    {
        $this->authorize('admin_leave_reports-list');
        $title = 'Leave Report';

        $userWithAdminRole = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin'); // Replace 'admin' with the actual role name.
        })->first();
        
        $employees = [];
        $url = '';
        
        $department_users = DepartmentUser::where('end_date',  NULL)->get();

        foreach($department_users as $department_user){
            $emp_data = User::where('id', $department_user->user_id)->where('id', '!=',$userWithAdminRole->id)->where('status', 1)->where('is_employee', 1)->first(['id', 'first_name', 'last_name', 'slug']);
            if(!empty($emp_data)){
                $employees[] = $emp_data;
            }
        }
        
        if(!empty($user_slug)) {
            $user = User::where('slug', $user_slug)->first();
            $url = URL::to('user_leaves/report/'.$user->slug);
        }else{
            $user = $userWithAdminRole;
        }
        $leave_report = [];
        if(!isOnProbation($user)){
            $leave_report = hasExceededLeaveLimit($user);
        }

        //updated year tensure 26 june to 25 june of next year.
        $currentYear = Carbon::now()->year;
        $leaveYearStart = Carbon::createFromDate($currentYear, 6, 26); // June 26th of the current year
        $leaveYearEnd = Carbon::createFromDate($currentYear + 1, 6, 25); // June 25th of the next year

        // Calculate the total used leaves within the leave year
        // $model = UserLeave::where('user_id', $user->id)
        //     ->where('status', 1)
        //     ->whereBetween('start_at', [$leaveYearStart, $leaveYearEnd])
        //     ->get();
            
        $model = [];  
        UserLeave::where('user_id', $user->id)
            ->where('status', 1)
            ->whereBetween('start_at', [$leaveYearStart, $leaveYearEnd])
            ->latest()
            ->chunk(100, function ($user_leaves) use (&$model) {
                foreach ($user_leaves as $user_leave) {
                    $model[] = $user_leave;
                }
        });

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Approved</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-warning" text-capitalized="">Pending</span>';
                            break;
                        case 2:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">Rejected</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('behavior_type', function ($model) {
                    if($model->behavior_type=='firsthalf'){
                        return '<span class="badge bg-label-info">First Half</span>';
                    }elseif($model->behavior_type=='lasthalf'){
                        return '<span class="badge bg-label-warning">Last Half</span>';
                    }else{
                        return '<span class="badge bg-label-danger">Absent</span>';
                    }
                })
                ->editColumn('duration', function ($model) {
                    return '<span class="text-primary fw-semibold">'.$model->duration.'</span>';
                })
                ->editColumn('start_at', function ($model) {
                    return '<span class="fw-semibold">'.Carbon::parse($model->start_at)->format('d, M Y').'</span>';
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('reason', function ($model) {
                    return '<span class="fw-semibold text-primary">'.$model->reason.'</span>';
                })
                ->editColumn('user_id', function ($model) {
                    if(isset($model->hasEmployee) && !empty($model->hasEmployee->first_name)){
                        return '<span class="fw-semibold">'.$model->hasEmployee->first_name .' '. $model->hasEmployee->last_name.'</span>';
                    }else{
                        return '-';
                    }
                })
                ->rawColumns(['status', 'duration', 'user_id', 'reason', 'start_at', 'behavior_type'])
                ->make(true);
        }

        return view('admin.user_leaves.leave-report', compact('title', 'leave_report', 'user', 'employees', 'url'));
    }
    
    public function employeeLeaveReport(Request $request, $user_slug = null)
    {
        $this->authorize('employee_leave_report-list');
        $title = 'Leave Report';

        $login_user = User::where('id', Auth::user()->id)->first();

        $role = $login_user->getRoleNames()->first();
        foreach($login_user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $employees = [];
        $dept_ids = [];
        $url = '';
        if($role=='Department Manager'){
            $department = Department::where('manager_id', $login_user->id)->first();
            if(isset($department) && !empty($department->id)){
                $department_id = $department->id;
                $department_manager = $department->manager;
                
                $dept_ids[] = $department->id;
                $sub_dep = Department::where('parent_department_id', $department->id)->where('manager_id', Auth::user()->id)->first();
                if(!empty($sub_dep)){
                    $dept_ids[] = $sub_dep->id;
                }else{
                    $sub_deps = Department::where('parent_department_id', $department->id)->get();    
                    if(!empty($sub_deps) && count($sub_deps)){
                        foreach($sub_deps as $sub_dept){
                            $dept_ids[] = $sub_dept->id;
                        }
                    }
                }
            }
            $department_users = DepartmentUser::orderby('id', 'desc')->whereIn('department_id',  $dept_ids)->where('end_date', null)->get();
            foreach($department_users as $department_user){
                $user = User::where('id', $department_user->user_id)->where('status', 1)->where('is_employee', 1)->first(['first_name', 'slug']);
                if(!empty($user)){
                    $dep_user = User::where('id', $department_user->user_id)->first(['id','first_name', 'last_name', 'slug']);
                    if(!empty($dep_user)){
                        $employees[] = $dep_user;
                    }
                }
            }
        }
        if(!empty($user_slug)) {
            $user = User::where('slug', $user_slug)->first();
            $url = URL::to('employee/leaves/report/'.$user->slug);
        }else{
            $user = $login_user;
        }
        $leave_report = [];
        if(!isOnProbation($user)){
            $leave_report = hasExceededLeaveLimit($user);
        }

        //updated year tensure 26 june to 25 june of next year.
        $currentYear = Carbon::now()->year;
        $leaveYearStart = Carbon::createFromDate($currentYear, 6, 26); // June 26th of the current year
        $leaveYearEnd = Carbon::createFromDate($currentYear + 1, 6, 25); // June 25th of the next year

        // Calculate the total used leaves within the leave year
        // $model = UserLeave::where('user_id', $user->id)
        //     ->where('status', 1)
        //     ->whereBetween('start_at', [$leaveYearStart, $leaveYearEnd])
        //     ->get();
            
        $model = [];  
        UserLeave::where('user_id', $user->id)
            ->where('status', 1)
            ->whereBetween('start_at', [$leaveYearStart, $leaveYearEnd])
            ->latest()
            ->chunk(100, function ($user_leaves) use (&$model) {
                foreach ($user_leaves as $user_leave) {
                    $model[] = $user_leave;
                }
        });

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Approved</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-warning" text-capitalized="">Pending</span>';
                            break;
                        case 2:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">Rejected</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('behavior_type', function ($model) {
                    if($model->behavior_type=='firsthalf'){
                        return '<span class="badge bg-label-info">First Half</span>';
                    }elseif($model->behavior_type=='lasthalf'){
                        return '<span class="badge bg-label-warning">Last Half</span>';
                    }else{
                        return '<span class="badge bg-label-danger">Absent</span>';
                    }
                })
                ->editColumn('duration', function ($model) {
                    return '<span class="text-primary fw-semibold">'.$model->duration.'</span>';
                })
                ->editColumn('start_at', function ($model) {
                    return Carbon::parse($model->start_at)->format('d, M Y');
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('user_id', function ($model) {
                    if(isset($model->hasEmployee) && !empty($model->hasEmployee->first_name)){
                        return $model->hasEmployee->first_name .' '. $model->hasEmployee->last_name;
                    }else{
                        return '-';
                    }
                })
                ->rawColumns(['status', 'duration', 'behavior_type'])
                ->make(true);
        }

        return view('admin.user_leaves.employee-leave-report', compact('title', 'leave_report', 'user', 'employees', 'url'));
    }

    public function show($leave_id)
    {
        $model = UserLeave::where('id', $leave_id)->first();
        return (string) view('admin.user_leaves.show_content', compact('model'));
    }
    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $this->authorize('employee_leave_requests-edit');
        $leave_types = LeaveType::where('status', 1)->latest()->get();
        $model = UserLeave::where('id', $id)->first();
        return (string) view('admin.user_leaves.edit_content', compact('model', 'leave_types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $user_leave_id)
    {
        $this->validate($request, [
            'leave_type_id' => ['required'],
            'start_at' => ['required'],
            'end_at' => ['required'],
            'reason' => ['required', 'max:255'],
        ]);
            
        $user = User::where('slug', Auth::user()->slug)->first();
       
        // $manager = $user->departmentBridge->department->manager;
        // if(empty($manager)){
        //     $manager = User::role('Admin')->first();
        // }
        
        $login_user = Auth::user();
        if($login_user->hasRole('Department Manager')){
            $department = Department::where('manager_id', $login_user->id)->first();
            $manager = $department->parentDepartment->manager;
        }elseif($login_user->hasRole('Employee')){
            $manager = $login_user->departmentBridge->department->manager;
        }
        
        $department_id = '';
        if(isset($user->departmentBridge) && !empty($user->departmentBridge->department_id)){
            $department_id = $user->departmentBridge->department_id;
        }

        DB::beginTransaction();

        try{
            $user_leave_report = hasExceededLeaveLimit(auth()->user());
            $remaining_filable_leaves = $user_leave_report['total_remaining_leaves'];
            
            $leave_type = LeaveType::where('id', $request->leave_type_id)->first();
            
            $numberOfDays = 0;
            
            $startDate = Carbon::parse($request->start_at);
            $endDate = Carbon::parse($request->end_at);
            
            // Calculate the difference in days
            $numberOfDays = $endDate->diffInDays($startDate) + 1;
            
            if($numberOfDays<1){
                $numberOfDays = $leave_type->amount;
            }
            
            if($remaining_filable_leaves >= $numberOfDays){
                $user_leave = UserLeave::where('id', $user_leave_id)->first();
                $user_leave->department_id = $department_id;
                $user_leave->leave_type_id = $request->leave_type_id;
                $user_leave->start_at = date('Y-m-d', strtotime($request->start_at));
                $user_leave->end_at = date('Y-m-d', strtotime($request->end_at));
                $user_leave->duration = $numberOfDays;
                $user_leave->behavior_type = $leave_type->name;
                $user_leave->reason = $request->reason;
                $user_leave->save();
    
                if($login_user->hasRole('Department Manager')){
                    $department = Department::where('manager_id', $login_user->id)->first();
                    $manager = $department->parentDepartment->manager;
                }else{
                    $manager = $login_user->departmentBridge->department->manager;
                }
    
                $notification_data = [
                    'id' => $user_leave_id,
                    'date' => $request->start_at,
                    'type' => $leave_type->behavior_type,
                    'profile' => $login_user->profile->profile,
                    'name' => $login_user->first_name.' '.$login_user->last_name,
                    'title' => 'has updated applied leave '. $user_leave->behavior_type,
                    'reason' => $user_leave->reason,
                ];
    
                if(isset($notification_data) && !empty($notification_data)){
                    $manager->notify(new ImportantNotification($notification_data));
                }
                
                DB::commit();
                
                return response()->json(['success' => true]);
            }else{
                return response()->json(['error' => "You don't have leaves in your account balance, visit leaves -> leave report."]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function ApproveOrRejectLeave($user_leave_id, $status){
        $model = UserLeave::where('id', $user_leave_id)->first();
        if(!empty($model) && $status=='approve'){
            $model->status = 1; //approve
        }else{
            $model->status = 2; //reject
        }

        $model->approved_by = Auth::user()->id;
        $model->save();

        $title = '';
        if($model->status==1){
            $title = 'Your leave request has been approved.';
        }else{
            $title = 'Your leave request has been rejected.';
        }

        if($model){
            $login_user = Auth::user();

            $notification_data = [
                'id' => $model->id,
                'date' => $model->start_at,
                'type' => $model->behavior_type,
                'profile' => $login_user->profile->profile,
                'name' => $login_user->first_name.' '.$login_user->last_name,
                'title' => $title,
                'reason' => $model->reason,
            ];

            if(isset($notification_data) && !empty($notification_data)){
                $model->hasEmployee->notify(new ImportantNotification($notification_data));
            }

            return true;
        }
    }

    public function blukStatus(Request $request, $status){
        $data = json_decode($request->data);
        
        $login_user = Auth::user();
        foreach($data as $value){
            $model = UserLeave::where('id', $value->id)->first();
            if($model){
                $model->approved_by = Auth::user()->id;
                if($status=='approve'){
                    $model->status = 1;
                }else{
                    $model->status = 2;
                }
                $model->save();
                
                $title = '';
                if($model->status==1){
                    $title = 'Your leave request has been approved.';
                }else{
                    $title = 'Your leave request has been rejected.';
                }

                $notification_data = [
                    'id' => $model->id,
                    'date' => $model->start_at,
                    'type' => $model->behavior_type,
                    'profile' => $login_user->profile->profile,
                    'name' => $login_user->first_name.' '.$login_user->last_name,
                    'title' => $title,
                    'reason' => $model->reason,
                ];

                if(isset($notification_data) && !empty($notification_data)){
                    $model->hasEmployee->notify(new ImportantNotification($notification_data));
                }
            }
        }

        if($model){
            return 'true';
        }else{
            return false;
        }
    }

    public function destroy($id)
    {
        $this->authorize('employee_leave_requests-delete');
        $model = UserLeave::where('id', $id)->delete();
        if($model){
            return response()->json([
                'status' => true,
            ]);
        }else{
            return false;
        }
    }
}
