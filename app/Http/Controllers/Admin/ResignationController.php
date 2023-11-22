<?php

namespace App\Http\Controllers\Admin;

use Auth;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\Email;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Resignation;
use App\Models\User;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\UserEmploymentStatus;
use App\Models\EmploymentStatus;
use App\Models\WorkShift;
use App\Models\Designation;
use App\Models\JobHistory;
use App\Models\SalaryHistory;
use App\Models\WorkingShiftUser;
use App\Models\AuthorizeEmail;
use Spatie\Permission\Models\Role;
use App\Notifications\ImportantNotification;

class ResignationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('resignations-list');
        $data = [];
        $title = 'All Resignations';
        
        $logined_user = Auth::user();

        $user = $logined_user;

        $employees_ids = [];

        $department_users = DepartmentUser::where('end_date',  NULL)->get();
        
        $data['designations'] = Designation::orderby('id', 'desc')->where('status', 1)->get();
        $data['roles'] = Role::orderby('id', 'desc')->get();
        $data['departments'] = Department::orderby('id', 'desc')->has('departmentWorkShift')->has('manager')->where('status', 1)->get();
        $data['employment_statues'] = EmploymentStatus::orderby('id', 'desc')->get();
        $data['work_shifts'] = WorkShift::where('status', 1)->get();
        
        // $model = Resignation::where('is_rehired', 0)->latest()->get();
        
        $model = [];
        Resignation::where('is_rehired', 0)
            ->latest()
            ->chunk(100, function ($resignations) use (&$model) {
                foreach ($resignations as $resignation) {
                    $model[] = $resignation;
                }
        });
        
        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 0:
                            $label = '<span class="badge bg-label-warning" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-danger" data-bs-original-title="Pending">Pending</span>';
                            break;
                        case 1:
                            $label = '<span class="badge bg-label-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-info" data-bs-original-title="Approved">Approved By RA</span>';
                            break;
                        case 2:
                            $label = '<span class="badge bg-label-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-success" data-bs-original-title="Approved By Admin">Approved By Admin</span>';
                            break;
                        case 3:
                            $label = '<span class="badge bg-label-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-success" data-bs-original-title="Rejected">Rejected</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('subject', function ($model) {
                    $subject = '-';
                    if(!empty($model->subject)){
                        $subject = $model->subject;
                    }
                    
                    return $subject;
                })
                ->editColumn('resignation_date', function ($model) {
                    return '<span class="text-primary fw-semibold">'.Carbon::parse($model->resignation_date)->format('d, M Y').'</span>';
                })
                ->editColumn('last_working_date', function ($model) {
                    return Carbon::parse($model->last_working_date)->format('d, M Y');
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('employee_id', function ($model) {
                    return view('admin.resignations.employee-profile', ['model' => $model])->render();
                })
                ->editColumn('notice_period', function ($model) {
                    return '<span class="badge bg-label-primary">'.$model->notice_period.'</span>';
                })
                ->editColumn('employment_status_id', function ($model) {
                    $label = '';
                    if(isset($model->hasEmploymentStatus) && !empty($model->hasEmploymentStatus)){
                        $label = '<span class="badge bg-label-'.$model->hasEmploymentStatus->class.'">'.
                                    $model->hasEmploymentStatus->name.
                                '</span>';
                    }
                    return $label;
                })
                ->addColumn('action', function($model){
                    return view('admin.resignations.action', ['data' => $model])->render();
                })
                ->rawColumns(['employee_id', 'employment_status_id', 'status', 'resignation_date', 'notice_period', 'action'])
                ->make(true);
        }
        
        return view('admin.resignations.index', compact('title', 'user', 'data'));
    }
   
    public function employeeResignations(Request $request)
    {
        $this->authorize('employee_resignations-list');
        $data = [];
        $title = 'All Resignations';
        
        $logined_user = Auth::user();

        $user = $logined_user;
        
        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
            if($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $employees_ids = [];
        $dept_ids = [];

        if($role=='Department Manager'){
            $data['employment_status'] = EmploymentStatus::where('name', 'Resign')->first();
            
            $department = Department::where('manager_id', $logined_user->id)->first();
            if(isset($department) && !empty($department->id)){
                $department_id = $department->id;
                
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
            $department_users = DepartmentUser::whereIn('department_id',  $dept_ids)->where('end_date', NULL)->get();
            foreach($department_users as $department_user){
                $employee = User::where('id', $department_user->user_id)->first(['id', 'first_name', 'last_name', 'slug']);
                if(!empty($employee)){
                    $employees_ids[] = $employee->id;
                }
            }

            $employees_ids[] = Auth::user()->id;
            // $model = Resignation::whereIn('employee_id', $employees_ids)->where('is_rehired', 0)->latest()->get();
            
            $model = [];
            Resignation::whereIn('employee_id', $employees_ids)
                ->where('is_rehired', 0)
                ->latest()
                ->chunk(100, function ($resignations) use (&$model) {
                    foreach ($resignations as $resignation) {
                        $model[] = $resignation;
                    }
            });
        }else{
            $data['employment_status'] = EmploymentStatus::where('name', 'Resign')->first();
            // $model = Resignation::where('employee_id', Auth::user()->id)->where('is_rehired', 0)->latest()->get();
            
            $model = [];
            Resignation::where('employee_id', Auth::user()->id)
                ->where('is_rehired', 0)
                ->latest()
                ->chunk(100, function ($resignations) use (&$model) {
                    foreach ($resignations as $resignation) {
                        $model[] = $resignation;
                    }
            });
        }
        
        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 0:
                            $label = '<span class="badge bg-label-warning" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-danger" data-bs-original-title="Pending">Pending</span>';
                            break;
                        case 1:
                            $label = '<span class="badge bg-label-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-info" data-bs-original-title="Approved">Approved By RA</span>';
                            break;
                        case 2:
                            $label = '<span class="badge bg-label-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-success" data-bs-original-title="Approved By Admin">Approved By Admin</span>';
                            break;
                        case 3:
                            $label = '<span class="badge bg-label-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-success" data-bs-original-title="Rejected">Rejected</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('resignation_date', function ($model) {
                    return '<span class="text-primary fw-semibold">'.Carbon::parse($model->resignation_date)->format('d, M Y').'</span>';
                })
                ->editColumn('last_working_date', function ($model) {
                    return Carbon::parse($model->last_working_date)->format('d, M Y');
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('employee_id', function ($model) {
                    return view('admin.resignations.employee-profile', ['model' => $model])->render();
                })
                ->editColumn('notice_period', function ($model) {
                    return '<span class="badge bg-label-primary">'.$model->notice_period.'</span>';
                })
                ->editColumn('employment_status_id', function ($model) {
                    $label = '';
                    if(isset($model->hasEmploymentStatus) && !empty($model->hasEmploymentStatus)){
                        $label = '<span class="badge bg-label-'.$model->hasEmploymentStatus->class.'">'.
                                    $model->hasEmploymentStatus->name.
                                '</span>';
                    }
                    return $label;
                })
                ->addColumn('action', function($model){
                    return view('admin.resignations.action', ['data' => $model])->render();
                })
                ->rawColumns(['employee_id', 'employment_status_id', 'status', 'resignation_date', 'notice_period', 'action'])
                ->make(true);
        }
        
        return view('admin.resignations.employee_resignations', compact('title', 'user', 'data'));
    }
    
    public function reHiredEmployees(Request $request)
    {
        $this->authorize('employee_rehire-list');
        $data = [];
        $title = 'All Re-Hired Employees';
        $rehired_page = 're-hired';
        
        $logined_user = Auth::user();

        $user = $logined_user;

        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $employees_ids = [];

        if($role=='Admin'){
            $department_users = DepartmentUser::where('end_date',  NULL)->get();
            
            foreach($department_users as $department_user){
                $emp_data = User::where('id', $department_user->user_id)->first(['id','first_name', 'last_name', 'slug']);
                if(!empty($emp_data)  && $emp_data->id != Auth::user()->id){
                    $employees_ids[] = $emp_data->id;
                }
            }

            $model = Resignation::whereIn('employee_id', $employees_ids)->where('is_rehired', 1)->latest()->get();
            
            $data['designations'] = Designation::orderby('id', 'desc')->where('status', 1)->get();
            $data['roles'] = Role::orderby('id', 'desc')->get();
            $data['departments'] = Department::orderby('id', 'desc')->has('departmentWorkShift')->has('manager')->where('status', 1)->get();
            $data['employment_statues'] = EmploymentStatus::orderby('id', 'desc')->get();
            $data['work_shifts'] = WorkShift::where('status', 1)->get();
        }elseif($role=='Department Manager'){
            $emp_statuses = ['Terminated', 'Voluntary', 'Layoffs', 'Retirements'];
            $data['employment_statues'] = EmploymentStatus::whereIn('name', $emp_statuses)->get();
            
            $department = Department::where('manager_id', $logined_user->id)->first();
            if(isset($department) && !empty($department->id)){
                $department_id = $department->id;
            }
            $department_users = DepartmentUser::where('department_id',  $department_id)->where('end_date', NULL)->get();
            foreach($department_users as $department_user){
                $employee = User::where('id', $department_user->user_id)->first(['id', 'first_name', 'last_name', 'slug']);
                if(!empty($employee)){
                    $employees_ids[] = $employee->id;
                }
            }

            $employees_ids[] = Auth::user()->id;
            $model = Resignation::whereIn('employee_id', $employees_ids)->where('is_rehired', 1)->latest()->get();
        }
        
        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                
                ->editColumn('updated_at', function ($model) {
                    return Carbon::parse($model->updated_at)->format('d, M Y');
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('employee_id', function ($model) {
                    return view('admin.resignations.employee-profile', ['model' => $model])->render();
                })
                ->editColumn('employment_status_id', function ($model) {
                    $label = '';
                    if(isset($model->hasEmploymentStatus) && !empty($model->hasEmploymentStatus)){
                        $label = '<span class="badge bg-label-'.$model->hasEmploymentStatus->class.'">'.
                                    $model->hasEmploymentStatus->name.
                                '</span>';
                    }
                    return $label;
                })
                ->addColumn('emp_status', function ($model) {
                    $label = '';
                    
                    if(isset($model->hasEmployee) && !empty($model->hasEmployee->status)){
                        if($model->hasEmployee->status){
                            $label = '<span class="badge bg-label-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-info" data-bs-original-title="Active">Active</span>';
                        }else{
                            $label = '<span class="badge bg-label-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-info" data-bs-original-title="De-active">De-active</span>';
                        }
                    }
                    
                    return $label;
                })
                ->addColumn('action', function($model){
                    return view('admin.resignations.action', ['data' => $model])->render();
                })
                ->rawColumns(['employee_id', 'employment_status_id', 'emp_status', 'action'])
                ->make(true);
        }

        return view('admin.resignations.rehired-employees', compact('title', 'user', 'data', 'rehired_page'));

    }
    
    public function adminReHiredEmployees(Request $request)
    {
        $this->authorize('admin_employee_re_hire-list');
        $data = [];
        $title = 'All Re-Hired Employees';
        $rehired_page = 're-hired';
        
        $logined_user = Auth::user();
        $user = $logined_user;

        $employees_ids = [];

        $department_users = DepartmentUser::where('end_date',  NULL)->get();
        
        foreach($department_users as $department_user){
            $emp_data = User::where('id', $department_user->user_id)->first(['id','first_name', 'last_name', 'slug']);
            if(!empty($emp_data)  && $emp_data->id != Auth::user()->id){
                $employees_ids[] = $emp_data->id;
            }
        }

        $model = Resignation::whereIn('employee_id', $employees_ids)->where('is_rehired', 1)->latest()->get();
        
        $data['designations'] = Designation::orderby('id', 'desc')->where('status', 1)->get();
        $data['roles'] = Role::orderby('id', 'desc')->get();
        $data['departments'] = Department::orderby('id', 'desc')->has('departmentWorkShift')->has('manager')->where('status', 1)->get();
        $data['employment_statues'] = EmploymentStatus::orderby('id', 'desc')->get();
        $data['work_shifts'] = WorkShift::where('status', 1)->get();
        
        
        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                
                ->editColumn('updated_at', function ($model) {
                    return Carbon::parse($model->updated_at)->format('d, M Y');
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('employee_id', function ($model) {
                    return view('admin.resignations.employee-profile', ['model' => $model])->render();
                })
                ->editColumn('employment_status_id', function ($model) {
                    $label = '';
                    if(isset($model->hasEmploymentStatus) && !empty($model->hasEmploymentStatus)){
                        $label = '<span class="badge bg-label-'.$model->hasEmploymentStatus->class.'">'.
                                    $model->hasEmploymentStatus->name.
                                '</span>';
                    }
                    return $label;
                })
                ->addColumn('emp_status', function ($model) {
                    $label = '';
                    
                    if(isset($model->hasEmployee) && !empty($model->hasEmployee->status)){
                        if($model->hasEmployee->status){
                            $label = '<span class="badge bg-label-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-info" data-bs-original-title="Active">Active</span>';
                        }else{
                            $label = '<span class="badge bg-label-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-info" data-bs-original-title="De-active">De-active</span>';
                        }
                    }
                    
                    return $label;
                })
                ->addColumn('action', function($model){
                    return view('admin.resignations.action', ['data' => $model])->render();
                })
                ->rawColumns(['employee_id', 'employment_status_id', 'emp_status', 'action'])
                ->make(true);
        }

        return view('admin.resignations.admin-rehired-employees', compact('title', 'user', 'data', 'rehired_page'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if(isset($request->from) && $request->from=='termination'){
            $this->validate($request, [
                'employment_status' => 'required',
                'notice_period' => 'required',
                'resignation_date' => 'required',
                'reason_for_resignation' => 'max:500',
            ]);
        }else{
            $this->validate($request, [
                'subject' => 'required',
                'resignation_date' => 'required',
                'reason_for_resignation' => 'max:500',
            ]);
        }
        
        DB::beginTransaction();
        
        try{
            if(Auth::user()->hasRole('Admin')){
                // Assuming $request->resignation_date is a valid date in a format Carbon can parse
                $resignationDate = Carbon::parse($request->resignation_date);
                
                // Add one month to the date
                if($request->notice_period=='Immediately'){
                    $last_working_date = $resignationDate;
                }elseif($request->notice_period=='One Week'){
                    $last_working_date = $resignationDate->addWeek();
                }else{
                    $last_working_date = $resignationDate->addMonth();
                }
                
                $ifexist = Resignation::where('employee_id', $request->user_id)->latest()->first();
                if(!empty($ifexist)){
                    $ifexist->employment_status_id = $request->employment_status;
                    $ifexist->save();
                }
                
                $subject = '';
                if(isset($request->subject)){
                    $subject = $request->subject;
                }
                //testing
    
                $resignation = Resignation::create([
                    'created_by' => Auth::user()->id,
                    'is_manager_approved' => now(),
                    'is_concerned_approved' => now(),
                    'employee_id' => $request->user_id,
                    'employment_status_id' => $request->employment_status,
                    'subject' => $subject,
                    'resignation_date' => $request->resignation_date,
                    'reason_for_resignation' => $request->reason_for_resignation,
                    'notice_period' => $request->notice_period,
                    'last_working_date' => date('Y-m-d', strtotime($last_working_date)),
                    'status' => 2,
                    'comment' => 'Terminated by admin',
                ]);
                
                if($resignation){
                    //employee
                    $model = User::where('id', $request->user_id)->first();
                    
                    $login_user = Auth::user();
                    $notification_data = [
                        'id' => $resignation->id,
                        'date' => $request->resignation_date,
                        'type' => $resignation->hasEmploymentStatus->name,
                        'name' => $login_user->first_name.' '.$login_user->last_name,
                        'profile' => $login_user->profile->profile,
                        'title' => 'You have new update about '.$resignation->hasEmploymentStatus->name,
                        'reason' => $resignation->reason_for_resignation,
                    ];
                    
                    if(isset($notification_data) && !empty($notification_data)){
                        $model->notify(new ImportantNotification($notification_data));
                        
                        if($model->hasRole('Department Manager')){
                            $parent_department = Department::where('manager_id', $model->id)->first();
                            $manager = $parent_department->parentDepartment->manager;
                        }else{
                            $manager = $model->departmentBridge->department->manager;
                        }
                        
                        $manager->notify(new ImportantNotification($notification_data));
                    }
                    
                    \LogActivity::addToLog('New Termination Added by admin');
                    
                    if($request->notice_period=='Immediately'){
                        //close job employment status
                        $user_emp_status = UserEmploymentStatus::orderby('id', 'desc')->where('user_id', $model->id)->first();
                        $user_emp_status->employment_status_id = $resignation->employment_status_id;
                        $user_emp_status->end_date = $resignation->last_working_date;
                        $user_emp_status->save();
                        
                        //close job history
                        $job_history = JobHistory::orderby('id', 'desc')->where('user_id', $model->id)->first();
                        $job_history->end_date = $resignation->last_working_date;
                        $job_history->save();
                        
                        //close salary history
                        $salary_history = SalaryHistory::orderby('id', 'desc')->where('user_id', $model->id)->first();
                        if(!empty($salary_history)){
                            $salary_history->end_date = $resignation->last_working_date;
                            $salary_history->status = 0;
                            $salary_history->save();    
                        }else{
                            SalaryHistory::create([
                                'created_by' => Auth::user()->id,
                                'user_id' => $model->id,
                                'job_history_id' => $job_history->id,
                                'salary' => 0,
                                'effective_date' => $resignation->last_working_date,
                                'end_date' => $resignation->last_working_date,
                                'status' => 0,
                            ]);
                        }
                        
                        //close DepartmentUser
                        $user_dept = DepartmentUser::orderby('id', 'desc')->where('user_id', $model->id)->first();
                        $user_dept->end_date = $resignation->last_working_date;
                        $user_dept->save();
                        
                        //close DepartmentUser
                        $user_dept = WorkingShiftUser::orderby('id', 'desc')->where('user_id', $model->id)->first();
                        $user_dept->end_date = $resignation->last_working_date;
                        $user_dept->save();
            
                        //de-active employee and remove from employment
                        $model->status = 0; //set to deactive
                        $model->is_employee = 0; //set to deactive
                        $model->save();
                    }
                    
                    DB::commit();
                    
                    //send email.
                    $admin_user = User::role('Admin')->first();
                    
                    $mailData = [
                        'from' => 'termination',
                        'title' => 'Employee Termination Notification',
                        'employee' => $model->first_name.' '.$model->last_name,
                    ];
                    
                    if(!empty(sendEmailTo($model, 'employee_termination')) && !empty(sendEmailTo($model, 'employee_termination')['cc_emails'])){
                        $to_emails = sendEmailTo($model, 'employee_termination')['to_emails'];
                        $cc_emails = sendEmailTo($model, 'employee_termination')['cc_emails'];
                        Mail::to($to_emails)->cc($cc_emails)->send(new Email($mailData));
                    }else{
                        $to_emails = sendEmailTo($model, 'employee_termination')['to_emails'];
                        Mail::to($to_emails)->send(new Email($mailData));
                    }
    
                    \LogActivity::addToLog('Terminated employee');
                    return response()->json(['success' => true]);
                    //send email.
                }
            }else{
                // Assuming $request->resignation_date is a valid date in a format Carbon can parse
                $resignationDate = Carbon::parse($request->resignation_date);
                
                // Add one month to the date
                $last_working_date = $resignationDate->addMonth();
                
                $ifexist = Resignation::where('status', '!=', 0)->where('employee_id', Auth::user()->id)->latest()->first();
                if(!empty($ifexist)){
                    $ifexist->employment_status_id = $request->employment_status;
                    $ifexist->save();
                }
    
                $model = Resignation::create([
                    'created_by' => Auth::user()->id,
                    'employee_id' => Auth::user()->id,
                    'employment_status_id' => $request->employment_status,
                    'subject' => $request->subject,
                    'resignation_date' => $request->resignation_date,
                    'reason_for_resignation' => $request->reason_for_resignation,
                    'notice_period' => 'One Month',
                    'last_working_date' => date('Y-m-d', strtotime($last_working_date)),
                ]);
                
                if($model){
                    $login_user = Auth::user();
                    $notification_data = [
                        'id' => $model->id,
                        'date' => $request->resignation_date,
                        'type' => $model->hasEmploymentStatus->name,
                        'name' => $login_user->first_name.' '.$login_user->last_name,
                        'profile' => $login_user->profile->profile,
                        'title' => 'has applied for '.$model->hasEmploymentStatus->name,
                        'reason' => $request->reason_for_resignation,
                    ];
                    
                    if(isset($notification_data) && !empty($notification_data)){
                        if($login_user->hasRole('Department Manager')){
                            $parent_department = Department::where('manager_id', $login_user->id)->first();
                            $manager = $parent_department->parentDepartment->manager;
                        }else{
                            $manager = $login_user->departmentBridge->department->manager;
                        }
                        $manager->notify(new ImportantNotificationWithMail($notification_data));
                    }
                    
                    \LogActivity::addToLog('New Resignation Added');
                    
                    DB::commit();
                }
                
                return response()->json(['success' => true]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function show($model_id)
    {
        $model = Resignation::findOrFail($model_id);
        return (string) view('admin.resignations.show_content', compact('model'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $this->authorize('resignations-edit');
        $logined_user = Auth::user();
        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $employees_ids = [];

        if($role=='Admin' || $role=='Department Manager'){
            $emp_statuses = ['Terminated', 'Voluntary', 'Layoffs', 'Retirements'];
            $employment_statues = EmploymentStatus::whereIn('name', $emp_statuses)->get();
        }else{
            $emp_statuses = ['Voluntary', 'Layoffs', 'Retirements'];
            $employment_statues = EmploymentStatus::whereIn('name', $emp_statuses)->get();
        }
        
        $model = Resignation::where('id', $id)->first();
        return (string) view('admin.resignations.edit_content', compact('model', 'employment_statues'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'subject' => 'required',
            'resignation_date' => 'required',
            'reason_for_resignation' => 'max:500',
        ]);

        DB::beginTransaction();

        try{
            $model = Resignation::where('id', $id)->first();

            // Assuming $request->resignation_date is a valid date in a format Carbon can parse
            $resignationDate = Carbon::parse($request->resignation_date);
            
            // Add one month to the date
            $last_working_date = $resignationDate->addMonth();
            
            $model->created_by = Auth::user()->id;
            $model->employee_id = Auth::user()->id;
            $model->employment_status_id = $request->employment_status;
            $model->subject = $request->subject;
            $model->resignation_date = $request->resignation_date;
            $model->last_working_date = date('Y-m-d', strtotime($last_working_date));
            $model->reason_for_resignation = $request->reason_for_resignation;
            $model->save();

            if($model){
                $login_user = Auth::user();
                $notification_data = [
                    'id' => $model->id,
                    'date' => $request->resignation_date,
                    'type' => $model->hasEmploymentStatus->name,
                    'name' => $login_user->first_name.' '.$login_user->last_name,
                    'profile' => $login_user->profile->profile,
                    'title' => 'has updated request for '.$model->hasEmploymentStatus->name,
                    'reason' => $request->reason_for_resignation,
                ];

                if(isset($notification_data) && !empty($notification_data)){
                    if($login_user->hasRole('Department Manager')){
                        $parent_department = Department::where('manager_id', $login_user->id)->first();
                        $manager = $parent_department->parentDepartment->manager;
                    }else{
                        $manager = $login_user->departmentBridge->department->manager;
                    }
                    $manager->notify(new ImportantNotificationWithMail($notification_data));
                }
                
                \LogActivity::addToLog('Resignation Updated');
                DB::commit();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->authorize('resignations-delete');
        $model = Resignation::where('id', $id)->delete();
        if($model){
            return response()->json([
                'status' => true,
            ]);
        }else{
            return false;
        }
    }

    public function trashed(Request $request)
    {
        $title = 'All Trashed Resignations';
        $temp = 'All Trashed Resignations';
        $user = Auth::user();
        
        $model = Resignation::onlyTrashed()->where('employee_id', Auth::user()->id)->orderby('id', 'desc')->get();
        
        if($request->ajax()) {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 0:
                            $label = '<span class="badge bg-label-warning" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-danger" data-bs-original-title="Pending">Pending</span>';
                            break;
                        case 1:
                            $label = '<span class="badge bg-label-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-info" data-bs-original-title="Approved">Approved By RA</span>';
                            break;
                        case 2:
                            $label = '<span class="badge bg-label-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-success" data-bs-original-title="Approved By Admin">Approved By Admin</span>';
                            break;
                        case 3:
                            $label = '<span class="badge bg-label-danger" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="tooltip-success" data-bs-original-title="Rejected">Rejected</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('resignation_date', function ($model) {
                    return Carbon::parse($model->resignation_date)->format('d, M Y');
                })
                ->editColumn('last_working_date', function ($model) {
                    return Carbon::parse($model->last_working_date)->format('d, M Y');
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('employee_id', function ($model) {
                    return view('admin.resignations.employee-profile', ['model' => $model])->render();
                })
                ->editColumn('employment_status_id', function ($model) {
                    $label = '';
                    if(isset($model->hasEmploymentStatus) && !empty($model->hasEmploymentStatus)){
                        $label = '<span class="badge bg-label-'.$model->hasEmploymentStatus->class.'">'.
                                    $model->hasEmploymentStatus->name.
                                '</span>';
                    }
                    return $label;
                })
                ->addColumn('action', function($model){
                    $button = '<a href="'.route('resignations.restore', $model->id).'" class="btn btn-icon btn-label-info waves-effect">'.
                                    '<span>'.
                                        '<i class="ti ti-refresh ti-sm"></i>'.
                                    '</span>'.
                                '</a>';
                    return $button;
                })
                ->rawColumns(['employee_id', 'status', 'employment_status_id', 'action'])
                ->make(true);
        }

        return view('admin.resignations.index', compact('title', 'user', 'temp'));
    }
    public function restore($id)
    {
        Resignation::onlyTrashed()->where('id', $id)->restore();
        return redirect()->back()->with('message', 'Record Restored Successfully.');
    }

    public function status(Request $request, $id){
        $logined_user = Auth::user();
        
        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
            if($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $model = Resignation::where('id', $id)->first();
        if($role=='Department Manager'){
            if($request->status_type=='approve'){
                $model->status = 1;
            }else{
                $model->status = 3;
            }
            $model->comment = 'Manager: <br />'. $request->comment;
            $model->is_manager_approved = now();
            $model->save();

            if($request->status_type=='approve'){
                $notification_data = [
                    'id' => $model->id,
                    'date' => $model->resignation_date,
                    'type' => $model->hasEmploymentStatus->name,
                    'name' => $logined_user->first_name.' '.$logined_user->last_name,
                    'profile' => $logined_user->profile->profile,
                    'title' => 'Your request for '.$model->hasEmploymentStatus->name. ' has been approved by manager.',
                    'reason' => $request->comment,
                ];

                if(isset($notification_data) && !empty($notification_data)){
                    $model->hasEmployee->notify(new ImportantNotificationWithMail($notification_data));
                }
                \LogActivity::addToLog('Approved Resignation by Manager');
            }else{
                $notification_data = [
                    'id' => $model->id,
                    'date' => $request->resignation_date,
                    'type' => $model->hasEmploymentStatus->name,
                    'name' => $logined_user->first_name.' '.$logined_user->last_name,
                    'profile' => $logined_user->profile->profile,
                    'title' => 'Your request for '.$model->hasEmploymentStatus->name. ' has been rejected by manager.',
                    'reason' => $request->comment,
                ];

                if(isset($notification_data) && !empty($notification_data)){
                    $model->hasEmployee->notify(new ImportantNotificationWithMail($notification_data));
                }
                
                \LogActivity::addToLog('Rejected Resignation by Manager');
            }
        }else{
            if($request->status_type=='approve'){
                $model->status = 2;
            }else{
                $model->status = 3;
            }
            $model->comment = $model->comment.' <br /> Admin: '. $request->comment;
            $model->is_concerned_approved = now();
            $model->save();

            if($request->status_type=='approve'){
                \LogActivity::addToLog('Approved Resignation by Admin');
                
                //get user record.
                $user = User::where('id', $model->employee_id)->first();

                $notification_data = [
                    'id' => $model->id,
                    'date' => $model->resignation_date,
                    'type' => $model->hasEmploymentStatus->name,
                    'name' => $logined_user->first_name.' '.$logined_user->last_name,
                    'profile' => $logined_user->profile->profile,
                    'title' => 'Your request for '.$model->hasEmploymentStatus->name. ' has been approved by admin.',
                    'reason' => $request->comment,
                ];

                if(isset($notification_data) && !empty($notification_data)){
                    $user->notify(new ImportantNotificationWithMail($notification_data));
                }
    
                //send email.
                try{
                    $admin_user = User::role('Admin')->first();
    
                    $body = "Dear All, <br /><br />".
                            "I am writing to inform you that we have terminated the employment of ".$user->first_name." from our organization, effective immediately. <br /><br />".
                            "As per company policy, I am notifying you of this termination and providing you with the necessary information for payroll and other administrative purposes. <br /><br />".
    
                            $user->first_name. " 's final paycheck will be processed and distributed in accordance with state and federal laws.Please note that Amar Chand will no longer have access to our organization's portals, systems, and resources, effective immediately. We kindly request that you take the necessary steps to revoke their access and ensure the security of our systems and data.. <br /><br />".
    
                            "If you have any questions or concerns regarding this matter, please do not hesitate to contact me. <br /><br /><br />".
                            "Thank you for your attention to this matter. <br /><br />";
    
                    $thanks_regards = "Sincerely, <br /><br />".
                                      $admin_user->first_name;
    
                    $mailData = [
                        'title' => 'Employee Termination Notification - '.$user->first_name,
                        'body' => $body,
                        'footer' => $thanks_regards
                    ];
                    
                    if(!empty(sendEmailTo($user, 'employee_resignation')) && !empty(sendEmailTo($user, 'employee_resignation')['cc_emails'])){
                        $to_emails = sendEmailTo($user, 'employee_resignation')['to_emails'];
                        $cc_emails = sendEmailTo($user, 'employee_resignation')['cc_emails'];
                        Mail::to($to_emails)->cc($cc_emails)->send(new Email($mailData));
                    }else{
                        $to_emails = sendEmailTo($user, 'employee_resignation')['to_emails'];
                        Mail::to($to_emails)->send(new Email($mailData));
                    }
    
                    \LogActivity::addToLog('Resigned employee');
                    return response()->json(['success' => true]);
                } catch (\Exception $e) {
                    DB::rollback();
                    return $e->getMessage();
                }
                //send email.
            }else{
                //get user record.
                $user = User::where('id', $model->employee_id)->first();

                $notification_data = [
                    'id' => $model->id,
                    'date' => $model->resignation_date,
                    'type' => $model->hasEmploymentStatus->name,
                    'name' => $logined_user->first_name.' '.$logined_user->last_name,
                    'profile' => $logined_user->profile->profile,
                    'title' => 'Your request for '.$model->hasEmploymentStatus->name. ' has been rejected by Admin.',
                    'reason' => $request->comment,
                ];

                if(isset($notification_data) && !empty($notification_data)){
                    $user->notify(new ImportantNotificationWithMail($notification_data));
                }
                \LogActivity::addToLog('Rejected Resignation by Admin');
            }
        }

        return response()->json(['success' => true]);
    }
}
