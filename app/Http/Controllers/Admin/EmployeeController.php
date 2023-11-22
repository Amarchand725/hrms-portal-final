<?php

namespace App\Http\Controllers\Admin;

use DB;
use Str;
use DateTime;
use Carbon\Carbon;
use App\Mail\Email;
use App\Models\User;
use App\Models\UserContact;
use App\Models\Profile;
use App\Models\Resignation;
use App\Models\Vehicle;
use App\Models\Position;
use App\Models\UserLeave;
use App\Models\WorkShift;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\JobHistory;
use App\Models\Designation;
use App\Models\Discrepancy;
use Illuminate\Http\Request;
use App\Models\SalaryHistory;
use App\Models\AuthorizeEmail;
use App\Models\DepartmentUser;
use App\Models\EmployeeLetter;
use App\Models\EmploymentStatus;
use App\Models\WorkingShiftUser;
use Illuminate\Validation\Rules;
use App\Rules\MobileNumberFormat;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Models\UserEmploymentStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use App\Notifications\SalaryIncreamentNotification;
use App\Notifications\ImportantNotificationWithMail;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('employees-list');
        $data = [];

        $title = 'All Employees';
        $data['designations'] = Designation::orderby('id', 'desc')->where('status', 1)->get();
        $data['roles'] = Role::orderby('id', 'desc')->get();
        // $data['departments'] = Department::orderby('id', 'desc')->has('departmentWorkShift')->has('manager')->where('status', 1)->get();
        $data['departments'] = Department::orderby('id', 'desc')->where('status', 1)->get();
        $data['employment_statues'] = EmploymentStatus::orderby('id', 'desc')->get();
        $emp_statuses = ['Terminated', 'Voluntary', 'Layoffs', 'Retirements'];
        $data['termination_employment_statues'] = EmploymentStatus::whereIn('name', $emp_statuses)->get();
        $data['work_shifts'] = WorkShift::where('status', 1)->get();

        // $model = User::where('is_employee', 1)->latest()->get();
        $model = [];
        User::where('is_employee', 1)
            ->latest()
            ->chunk(100, function ($users) use (&$model) {
                foreach ($users as $user) {
                    // Process each user here
                    // For example, add them to the $processedUsers array
                    $model[] = $user;
                }
        });

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->addColumn('role', function($model){
                    return '<span class="badge bg-label-primary">'.$model->getRoleNames()->first().'</span>';
                })
                ->addColumn('Department', function($model){
                    if(isset($model->departmentBridge->department) && !empty($model->departmentBridge->department)){
                        return '<span class="text-primary">'.$model->departmentBridge->department->name.'</span>';
                    }else{
                        return '-';
                    }
                })
                ->addColumn('shift', function($model){
                    if(isset($model->userWorkingShift->workShift) && !empty($model->userWorkingShift->workShift->name)) {
                        return $model->userWorkingShift->workShift->name;
                    }else{
                        return '-';
                    }
                })
                ->addColumn('emp_status', function ($model) {
                    $label = '-';

                    if(isset($model->employeeStatus->employmentStatus) && !empty($model->employeeStatus->employmentStatus->name)){
                        if($model->employeeStatus->employmentStatus->name=='Terminated'){
                            $label = '<span class="badge bg-label-danger me-1">Terminated</span>';
                        }elseif($model->employeeStatus->employmentStatus->name=='Permanent'){
                            $label = '<span class="badge bg-label-success me-1">Permanent</span>';
                        }elseif($model->employeeStatus->employmentStatus->name=='Probation'){
                            $label = '<span class="badge bg-label-warning me-1">Probation</span>';
                        }else{
                            $label = '<span class="badge bg-label-info me-1">'.$model->employeeStatus->employmentStatus->name.'</span>';
                        }
                    }

                    return $label;
                })
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Active</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">De-active</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('first_name', function ($model) {
                    return view('admin.employees.employee-profile', ['employee' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('admin.employees.employee-action', ['employee' => $model])->render();
                })
                ->rawColumns(['emp_status', 'status', 'first_name', 'role', 'Department', 'action'])
                ->make(true);
        }

        return view('admin.employees.index', compact('title', 'data'));
    }

    public function employeePermanent($employee_id){
        DB::beginTransaction();
        $login_user = Auth::user();

        try{
            $job_history = JobHistory::orderby('id', 'desc')->where('user_id', $employee_id)->where('end_date', null)->first();
            $new_job_job_history = $job_history;
            if(!empty($job_history)){
                $job_history->end_date = date('Y-m-d');
                $job_history->save();

                $new_job_job_history = JobHistory::create([
                    'designation_id' => $job_history->designation_id,
                    'user_id' => $employee_id,
                    'employment_status_id' => 2, //Permanent Employee status
                    'joining_date' => date('Y-m-d'),
                    'vehicle_name' => $job_history->vehicle_name,
                    'vehicle_cc' => $job_history->vehicle_cc,
                ]);
            }

            $user_emp_status = UserEmploymentStatus::orderby('id', 'desc')->where('user_id', $employee_id)->first();
            if(!empty($user_emp_status)){
                $user_emp_status->end_date = date('Y-m-d');
                $user_emp_status->save();
            }

            UserEmploymentStatus::create([
                'user_id' => $employee_id,
                'employment_status_id' => 2, //permanent
                'start_date' => date('Y-m-d'),
            ]);

            EmployeeLetter::create([
                'created_by' => Auth::user()->id,
                'employee_id' => $employee_id,
                'title' => 'promotion_letter',
                'effective_date' => date('Y-m-d'),
                'validity_date' => NULL,
            ]);

            DB::commit();

            \LogActivity::addToLog('Employee has been permanent');
            $model = User::where('id', $employee_id)->first();

            // send email on salary increments.
            try{
                $body = "Dear ".$model->first_name." ". $model->last_name.", <br /><br />".
                        "I hope this email finds you well. I am writing to inform you about an important update regarding your employment. We are pleased to announce that your hard work, dedication, and valuable contributions to the company have been recognized. <br /><br />".
                        "After careful consideration, we have decided to permanent. You have been permanent employees in this company regards outstanding performance, commitment, and the value you bring to our organization. <br /><br />".

                $footer = "Best regards,, <br /><br />".
                            "HR Department";

                $mailData = [
                    'from' => 'salary_increments',
                    'title' => 'Permanent',
                    'body' => $body,
                    'footer' => $footer
                ];

                $increment_message = [
                    'id' => $new_job_job_history->id,
                    'profile' => $login_user->profile->profile,
                    'name' => $login_user->first_name.' '.$login_user->last_name,
                    'title' => 'Congratulation! You have been permanent.',
                    'message' => 'This promotion reflects your outstanding performance, commitment, and the value you bring to our organization.',
                ];

                $model->notify(new SalaryIncreamentNotification($increment_message));

                if(!empty(sendEmailTo($model, 'promotion')) && !empty(sendEmailTo($model, 'promotion')['cc_emails'])){
                    $to_emails = sendEmailTo($model, 'promotion')['to_emails'];
                    $cc_emails = sendEmailTo($model, 'promotion')['cc_emails'];
                    Mail::to($to_emails)->cc($cc_emails)->send(new Email($mailData));
                }elseif(!empty(sendEmailTo($model, 'promotion')['to_emails'])){
                    $to_emails = sendEmailTo($model, 'promotion')['to_emails'];
                    Mail::to($to_emails)->send(new Email($mailData));
                }

                return response()->json(['success' => true]);
            } catch (\Exception $e) {
                DB::rollback();
                return $e->getMessage();
            }
            //send email.

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'gender' => ['required'],
            'email' => ['required', 'ends_with:demo.org', 'string', 'email', 'max:255', 'unique:'.User::class],
            // 'phone_number' => 'nullable|min:12',
            'employment_status_id' => 'required',
            'designation_id' => 'required',
            'department_id' => 'required',
            'role_ids' => 'required',
            'role_ids*' => 'required',
            'joining_date' => 'required',
            'work_shift_id' => 'required',
            'employment_id' => 'max:200',
            'salary' => 'max:255',
        ]
        // [
        //     'phone_number.required' => 'The phone number field is required.',
        //     'phone_number.numeric' => 'The phone number must be a numeric value.',
        //     'phone_number.min' => 'The phone number must be at least 11 digits.',
        // ]
        );

        DB::beginTransaction();

        try{
            // cPanel API credentials
            $cpanelUsername = 'demo';
            $cpanelToken = 'XVVAA0LUFSK6OSE0F1TWJHIEFW94G98I';
            $cpanelDomain = 'host.dnscloudcentral.com';

            // Email account details
            $user_email = $request->email;
            // $user_password = Str::random(8);
            $user_password = 'demo@2023';

            // Call the function to create an email account
            $this->createEmailAccount($cpanelUsername, $cpanelToken, $cpanelDomain, $user_email, $user_password);

            //     return response()->json(['error' => 'This account '.$user_email.' already exists!']);
            // }else{
                $model = [
                    'created_by' => Auth::user()->id,
                    'status' => 1,
                    'slug' => $request->first_name.'-'.Str::random(5),
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($user_password),
                ];

                $model = User::create($model);
                $model->assignRole($request->role_ids);

                if($model){
                    Profile::create([
                        'user_id' => $model->id,
                        'employment_id' => $request->employment_id,
                        'joining_date' => $request->joining_date,
                        'gender' => $request->gender,
                        'phone_number' => $request->phone_number,
                    ]);

                    $job_history = JobHistory::create([
                        'created_by' => Auth::user()->id,
                        'user_id' => $model->id,
                        'designation_id' => $request->designation_id,
                        'employment_status_id' => $request->employment_status_id,
                        'joining_date' => $request->joining_date,
                    ]);

                    if($job_history && !empty($request->salary)){
                        SalaryHistory::create([
                            'created_by' => Auth::user()->id,
                            'user_id' => $model->id,
                            'job_history_id' => $job_history->id,
                            'salary' => $request->salary,
                            'effective_date' => $request->joining_date,
                            'status' => 1,
                        ]);
                    }

                    if(!empty($request->department_id)){
                        DepartmentUser::create([
                            'department_id' => $request->department_id,
                            'user_id' => $model->id,
                            'start_date' => $request->joining_date,
                        ]);
                    }

                    UserEmploymentStatus::create([
                        'user_id' => $model->id,
                        'employment_status_id' =>$request->employment_status_id,
                        'start_date' => $request->joining_date,
                    ]);

                    WorkingShiftUser::create([
                        'user_id' => $model->id,
                        'working_shift_id' =>$request->work_shift_id,
                        'start_date' => $request->joining_date,
                    ]);

                    DB::commit();

                    //send email with password.
                    $admin_user = User::role('Admin')->first();
                    $authorize_emails = AuthorizeEmail::where('email_title', 'new_employee_info')->first();

                    //Employee portal credentials mail
                    $employee_info = [
                        'name' => $model->first_name.' '.$model->last_name ,
                        'email' => $model->email,
                        'password' => $user_password,
                    ];

                    $mailData = [
                        'from' => 'welcome',
                        'title' => 'Welcome to Our Team - Important Onboarding Information',
                        'employee_info' => $employee_info,
                    ];

                    Mail::to($user_email)->send(new Email($mailData));

                    //Joining Email to departments
                    $manager_name = '';
                    if(isset($model->departmentBridge->department->manager) && !empty($model->departmentBridge->department->manager->first_name)) {
                        $manager_name = $model->departmentBridge->department->manager->first_name;
                    }

                    $designation_name = '';
                    if(isset($model->jobHistory->designation) && !empty($model->jobHistory->designation->title)) {
                        $designation_name = $model->jobHistory->designation->title;
                    }
                    $department_name = '';
                    if(isset($model->departmentBridge->department) && !empty($model->departmentBridge->department->name)) {
                        $department_name = $model->departmentBridge->department->name;
                    }
                    $work_shift_name = '';
                    if(isset($model->userWorkingShift->workShift) && !empty($model->userWorkingShift->workShift->name)) {
                        $work_shift_name = $model->userWorkingShift->workShift->name;
                    }
                    $joining_date = '';
                    if(isset($model->profile) && !empty($model->profile->joining_date)) {
                        $joining_date = date('d M Y', strtotime($model->profile->joining_date));
                    }

                    $employee_info = [
                        'name' => $model->first_name.' '. $model->last_name,
                        'email' => $model->email,
                        'password' => $user_password,
                        'manager' => $manager_name,
                        'designation' => $designation_name,
                        'department' => $department_name,
                        'shift_time' => $work_shift_name,
                        'joining_date' => $joining_date,
                    ];

                    $mailData = [
                        'from' => 'employee_info',
                        'title' => 'Employee Approval and Joining Information',
                        'employee_info' => $employee_info,
                    ];

                    if(!empty(sendEmailTo($model, 'new_employee_info')) && !empty(sendEmailTo($model, 'new_employee_info')['cc_emails'])){
                        $to_emails = sendEmailTo($model, 'new_employee_info')['to_emails'];
                        $cc_emails = sendEmailTo($model, 'new_employee_info')['cc_emails'];
                        Mail::to($to_emails)->cc($cc_emails)->send(new Email($mailData));
                    }elseif(!empty(sendEmailTo($model, 'new_employee_info')) && !empty(sendEmailTo($model, 'new_employee_info')['to_emails'])){
                        $to_emails = sendEmailTo($model, 'new_employee_info')['to_emails'];
                        Mail::to($to_emails)->send(new Email($mailData));
                    }
                // }

                \LogActivity::addToLog('Employee added');

                return response()->json(['success' => true]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    // API function to create an email account
    function createEmailAccount($cpanelUsername, $cpanelToken, $cpanelDomain, $emailUsername, $emailPassword) {
        $buildRequest = json_encode([
            'cpanel_jsonapi_version' => 2,
            'cpanel_jsonapi_module' => 'Email',
            'cpanel_jsonapi_func' => 'add_pop',
            'email' => $emailUsername,
            'password' => $emailPassword,
            'quota' => 'unlimited'
        ]);

        $query = "https://{$cpanelDomain}:2083/execute/Email/add_pop";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $buildRequest);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: cpanel {$cpanelUsername}:{$cpanelToken}"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $responseData = json_decode($response, true);
            if ($responseData['errors'] === null) {
                return 'success';
            } else {
                return 'failed';
            }
        } else {
            return 'failed';
        }
    }

    public function edit($slug)
    {
        // $this->authorize('employees-edit');
        $data = [];
        $data['model'] = User::with('jobHistory', 'employeeStatus')->where('slug', $slug)->first();
        $data['positions'] = Position::orderby('id', 'desc')->where('status', 1)->get();
        $data['designations'] = Designation::orderby('id', 'desc')->where('status', 1)->get();
        $data['roles'] = Role::orderby('id', 'desc')->get();
        $data['departments'] = Department::orderby('id', 'desc')->get();
        $data['work_shifts'] = WorkShift::where('status', 1)->get();
        $data['employment_statues'] = EmploymentStatus::orderby('id', 'desc')->get();

        return (string) view('admin.employees.edit_content', compact('data'));
    }

    public function update(Request $request, $slug)
    {
        $user = User::where('slug', $slug)->first();

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'gender' => ['required'],
            'email' => 'required|max:255|ends_with:demo.org|unique:users,id,'.$user->id,
            // 'phone_number' => 'nullable|min:12',
            'employment_status_id' => 'required',
            'designation_id' => 'required',
            'department_id' => 'required',
            'role_ids' => 'required',
            'role_ids*' => 'required',
            'joining_date' => 'required',
            'work_shift_id' => 'required',
            'employment_id' => 'max:200',
            'salary' => 'max:255',
        ]
        // ,
        // [
        //     'phone_number.required' => 'The phone number field is required.',
        //     'phone_number.numeric' => 'The phone number must be a numeric value.',
        //     'phone_number.min' => 'The phone number must be at least 11 digits.',
        // ]
        );

        DB::beginTransaction();

        try{
            $user->created_by = Auth::user()->id;
            $user->status = 1;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;

            if($user->email != $request->email) {
                $user->email = $request->email;
                $user->password = Hash::make(Str::random(5));
            }

            $user->save();
            $user->syncRoles($request->role_ids);

            if($user){
                Profile::where('user_id', $user->id)->update([
                    'employment_id' => $request->employment_id,
                    'joining_date' => $request->joining_date,
                    'gender' => $request->gender,
                    'phone_number' => $request->phone_number,
                ]);

                $job_history = JobHistory::where('user_id', $user->id)->update([
                    'created_by' => Auth::user()->id,
                    'designation_id' => $request->designation_id,
                    'employment_status_id' => $request->employment_status_id,
                    'joining_date' => $request->joining_date,
                ]);

                // if(!empty($request->department_id)){
                //     $user_department = DepartmentUser::where('user_id', $user->id)->where('department_id', $request->department_id)->where('end_date', NULL)->first();
                //     if(empty($user_department)){
                //         DepartmentUser::create([
                //             'department_id' => $request->department_id,
                //             'user_id' => $user->id,
                //             'start_date' => $request->joining_date,
                //         ]);
                //     }
                // }

                if(!empty($request->department_id)){
                    $user_department = DepartmentUser::where('user_id', $user->id)->where('end_date', NULL)->update([
                        'department_id' => $request->department_id,
                        'start_date' => $request->joining_date,
                    ]);
                }

                if($job_history && !empty($request->salary)){
                    $salary_history = SalaryHistory::where('user_id', $user->id)->first();
                    if(!empty($salary_history)){
                        $salary_history->salary = $request->salary;
                        $salary_history->effective_date = $request->joining_date;
                        $salary_history->save();
                    }else{
                        SalaryHistory::create([
                            'created_by' => Auth::user()->id,
                            'user_id' => $user->id,
                            'job_history_id' => $user->jobHistory->id,
                            'salary' => $request->salary,
                            'effective_date' => $request->joining_date,
                            'status' => 1,
                        ]);
                    }
                }

                $user_emp_status = UserEmploymentStatus::orderby('id', 'desc')->where('user_id', $user->id)->first();
                if(!empty($user_emp_status)){
                    $user_emp_status->employment_status_id = $request->employment_status_id;
                    $user_emp_status->start_date = $request->joining_date;
                    $user_emp_status->save();
                }else{
                    UserEmploymentStatus::create([
                        'user_id' => $user->id,
                        'employment_status_id' => $request->employment_status_id,
                        'start_date' => $request->joining_date,
                    ]);
                }



                $user_work_shift = WorkingShiftUser::orderby('id', 'desc')->where('user_id', $user->id)->first();
                if(!empty($user_work_shift)){
                    $user_work_shift->working_shift_id = $request->work_shift_id;
                    $user_work_shift->start_date = $request->joining_date;
                    $user_work_shift->save();
                }else{
                    WorkingShiftUser::create([
                        'user_id' => $user->id,
                        'working_shift_id' =>$request->work_shift_id,
                        'start_date' => $request->joining_date,
                    ]);
                }

                DB::commit();
            }

            //send email if email changed and generated new password.

            \LogActivity::addToLog('Employee updated');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function show($slug)
    {
        $this->authorize('employee_show-custom');
        $title = 'Show Details';
        $model = User::where('slug', $slug)->first();
        $histories = SalaryHistory::orderby('id','desc')->where('user_id', $model->id)->get();
        $user_permanent_address = UserContact::where('user_id', $model->id)->where('key', 'permanent_address')->first();
        $user_current_address = UserContact::where('user_id', $model->id)->where('key', 'current_address')->first();
        $user_emergency_contacts = UserContact::where('user_id', $model->id)->where('key', 'emergency_contact')->get();
        return view('admin.employees.show', compact('model', 'histories', 'title', 'user_permanent_address', 'user_current_address', 'user_emergency_contacts'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->authorize('employees-delete');
        $model = User::where('id', $id)->delete();
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
        $title = 'All Trashed Employees Records';

        if($request->ajax()) {
            $model = User::onlyTrashed();

            return DataTables::of($model)
                ->addIndexColumn()
                ->addColumn('Emp-ID', function($model){
                    return $model->profile->employment_id??'-';
                })
                ->addColumn('role', function($model){
                    return $model->getRoleNames()->first();
                })
                ->addColumn('Department', function($model){
                    if(isset($model->departmentBridge->department) && !empty($model->departmentBridge->department)){
                        return $model->departmentBridge->department->name;
                    }else{
                        return '-';
                    }
                })
                ->addColumn('shift', function($model){
                    if(isset($model->userWorkingShift->workShift) && !empty($model->userWorkingShift->workShift->name)) {
                        return $model->userWorkingShift->workShift->name;
                    }else{
                        return '-';
                    }
                })
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Active</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">De-active</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('first_name', function ($model) {
                    return view('admin.employees.employee-profile', ['employee' => $model])->render();
                })
                ->addColumn('action', function($model){
                    $button = '<a href="'.route('employees.restore', $model->id).'" class="btn btn-icon btn-label-info waves-effect">'.
                                    '<span>'.
                                        '<i class="ti ti-refresh ti-sm"></i>'.
                                    '</span>'.
                                '</a>';
                    return $button;
                })
                ->rawColumns(['status', 'first_name', 'action'])
                ->make(true);
        }

        return view('admin.employees.index', compact('title'));
    }
    public function restore($id)
    {
        User::onlyTrashed()->where('id', $id)->restore();
        return redirect()->back()->with('message', 'Record Restored Successfully.');
    }

    public function status(Request $request, $user_id)
    {
        $model = User::where('id', $user_id)->first();

        if($request->status_type=='status') {
            if($model->status==1) {
                $model->status = 0;
            } else {
                $model->status = 1; //Active
            }

            $model->save();
            //send email if possible

            \LogActivity::addToLog('Status updated');
            return response()->json(['success' => true]);
        }elseif($request->status_type=='remove'){
            $model->is_employee = 0;
            $model->save();

            \LogActivity::addToLog('Removed from list');
            return response()->json(['success' => true]);
        }
        // elseif($request->status_type=='terminate'){
        //     $user_emp_status = UserEmploymentStatus::orderby('id', 'desc')->where('user_id', $user_id)->first();
        //     $user_emp_status->end_date = date('Y-m-d');
        //     $user_emp_status->save();

        //     $terminate_status_id = EmploymentStatus::where('name', 'Terminated')->first()->id;

        //     UserEmploymentStatus::create([
        //         'user_id' => $user_id,
        //         'employment_status_id' => $terminate_status_id,
        //         'start_date' => date('Y-m-d'),
        //     ]);

        //     $model->status = 0; //set to deactive
        //     $model->save();

        //     //send email.
        //     try{
        //         $admin_user = User::role('Admin')->first();

        //         $body = "Dear All, <br /><br />".
        //                 "I am writing to inform you that we have terminated the employment of ".$model->first_name." from our organization, effective immediately. <br /><br />".
        //                 "As per company policy, I am notifying you of this termination and providing you with the necessary information for payroll and other administrative purposes. <br /><br />".

        //                 $model->first_name. " 's final paycheck will be processed and distributed in accordance with state and federal laws.Please note that Amar Chand will no longer have access to our organization's portals, systems, and resources, effective immediately. We kindly request that you take the necessary steps to revoke their access and ensure the security of our systems and data.. <br /><br />".

        //                 "If you have any questions or concerns regarding this matter, please do not hesitate to contact me. <br /><br /><br />".
        //                 "Thank you for your attention to this matter. <br /><br />";

        //         $thanks_regards = "Sincerely, <br /><br />".
        //                           $admin_user->first_name;

        //         $mailData = [
        //             'title' => 'Employee Termination Notification - '.$model->first_name,
        //             'body' => $body,
        //             'footer' => $thanks_regards
        //         ];

        //         if(!empty(sendEmailTo($model, 'employee_termination')) && !empty(sendEmailTo($model, 'employee_termination')['cc_emails'])){
        //             $to_emails = sendEmailTo($model, 'employee_termination')['to_emails'];
        //             $cc_emails = sendEmailTo($model, 'employee_termination')['cc_emails'];
        //             Mail::to($to_emails)->cc($cc_emails)->send(new Email($mailData));
        //         }elseif(!empty(sendEmailTo($model, 'employee_termination')['to_emails'])){
        //             $to_emails = sendEmailTo($model, 'employee_termination')['to_emails'];
        //             Mail::to($to_emails)->send(new Email($mailData));
        //         }

        //         \LogActivity::addToLog('Terminated employee');
        //         return response()->json(['success' => true]);
        //     } catch (\Exception $e) {
        //         DB::rollback();
        //         return $e->getMessage();
        //     }
        //     //send email.
        // }
    }

    public function getPromoteData(Request $request){
        $data = [];

        $data['model'] = User::where('id', $request->user_id)->first();
        $data['departments'] = Department::where('status', 1)->latest()->get();
        $data['designations'] = Designation::orderby('id', 'desc')->latest()->where('status', 1)->get();
        $data['roles'] = Role::orderby('id', 'desc')->latest()->get();

        return (string) view('admin.employees.promote', compact('data'));
    }

    public function promote(Request $request)
    {
        $request->validate([
            'department_id' => 'required',
            'designation_id' => 'required',
            'raise_salary' => 'required',
            'effective_date' => 'required',
        ]);

        DB::beginTransaction();

        try{
            $job_history = JobHistory::orderby('id', 'desc')->where('user_id', $request->user_id)->where('end_date', null)->first();
            $new_job_job_history = $job_history;

            if(!empty($job_history)){
                if($job_history->designation_id != $request->designation_id || !empty($request->vehicle_name)){
                    $job_history->end_date = $request->effective_date;
                    $job_history->save();

                    $new_job_job_history = JobHistory::create([
                        'designation_id' => $request->designation_id,
                        'user_id' => $request->user_id,
                        'employment_status_id' => 2, //Permanent Employee status
                        'joining_date' => $request->effective_date,
                        'vehicle_name' => $request->vehicle_name,
                        'vehicle_cc' => $request->vehicle_cc,
                    ]);
                }
            }else{
                $new_job_job_history = JobHistory::create([
                    'designation_id' => $request->designation_id,
                    'user_id' => $request->user_id,
                    'employment_status_id' => 2, //Permanent Employee status
                    'joining_date' => $request->effective_date,
                    'vehicle_name' => $request->vehicle_name,
                    'vehicle_cc' => $request->vehicle_cc,
                ]);
            }

            $last_salary = SalaryHistory::where('job_history_id', $job_history->id)->where('end_date', null)->where('status', 1)->first();
            $salary_history = $last_salary;
            if(!empty($last_salary)){
                $last_salary->status = 0;
                $last_salary->end_date = $request->effective_date;
                $last_salary->save();

                $updated_salary = (int)$last_salary->salary+(int)$request->raise_salary;

                $salary_history = SalaryHistory::create([
                    'created_by' => Auth::user()->id,
                    'user_id' => $request->user_id,
                    'job_history_id' => $new_job_job_history->id,
                    'raise_salary' => $request->raise_salary,
                    'salary' => $updated_salary,
                    'effective_date' => $request->effective_date,
                ]);
            }else{
                $salary_history = SalaryHistory::create([
                    'created_by' => Auth::user()->id,
                    'user_id' => $request->user_id,
                    'job_history_id' => $new_job_job_history->id,
                    'raise_salary' => $request->raise_salary,
                    'salary' => (int)$request->raise_salary,
                    'effective_date' => $request->effective_date,
                ]);
            }

            $user_department = DepartmentUser::orderby('id', 'desc')->where('user_id', $request->user_id)->where('end_date', null)->first();
            if(!empty($user_department) && $user_department->department_id != $request->department_id){
                $user_department->end_date = $request->effective_date;
                $user_department->save();

                DepartmentUser::create([
                    'department_id' => $request->department_id,
                    'user_id' => $request->user_id,
                    'start_date' => $request->effective_date,
                ]);
            }
            if(empty($user_department)){
                DepartmentUser::create([
                    'department_id' => $request->department_id,
                    'user_id' => $request->user_id,
                    'start_date' => $request->effective_date,
                ]);
            }

            EmployeeLetter::create([
                'created_by' => Auth::user()->id,
                'employee_id' => $request->user_id,
                'title' => 'promotion_letter',
                'effective_date' => $request->effective_date,
                'validity_date' => $request->validity_date??NULL,
            ]);

            DB::commit();

            \LogActivity::addToLog('Employee has been promoted');
            $model = User::where('id', $request->user_id)->first();

            // send email on salary increments.
            try{
                $current_salary = '';
                if(isset($model->salaryHistory) && !empty($model->salaryHistory->salary)){
                    $current_salary = $model->salaryHistory->salary;
                }

                $updated_salary = $current_salary+$request->raise_salary;

                $body = [
                    'name' => $model->first_name.' '.$model->last_name,
                    'effective_date' => date('d M Y', strtotime($request->effective_date)),
                    'current_salary' => number_format($current_salary),
                    'increased_salary' => number_format($request->raise_salary),
                    'updated_salary' => number_format($updated_salary),
                ];

                $mailData = [
                    'from' => 'salary_increments',
                    'title' => 'Promotion',
                    'body' => $body,
                ];

                $increament_message = [
                    'id' => $salary_history->id,
                    'profile' => $salary_history->createdBy->profile->profile,
                    'name' => $salary_history->createdBy->first_name.' '.$salary_history->createdBy->last_name,
                    'title' => 'Congratulation! You have been promoted.',
                    'message' => 'This promotion reflects your outstanding performance, commitment, and the value you bring to our organization.',
                ];

                $model->notify(new SalaryIncreamentNotification($increament_message));

                if(!empty(sendEmailTo($model, 'promotion')) && !empty(sendEmailTo($model, 'promotion')['cc_emails'])){
                    $to_emails = sendEmailTo($model, 'promotion')['to_emails'];
                    $cc_emails = sendEmailTo($model, 'promotion')['cc_emails'];
                    Mail::to($to_emails)->cc($cc_emails)->send(new Email($mailData));
                }elseif(!empty(sendEmailTo($model, 'promotion')['to_emails'])){
                    $to_emails = sendEmailTo($model, 'promotion')['to_emails'];
                    Mail::to($to_emails)->send(new Email($mailData));
                }

                return response()->json(['success' => true]);
            } catch (\Exception $e) {
                DB::rollback();
                return $e->getMessage();
            }
            //send email.

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function storeWorkShift(Request $request){
        $request->validate([
            'working_shift_id' => 'required',
            'start_date' => 'required',
        ]);

        DB::beginTransaction();

        try{
            $login_user = Auth::user();
            $current_shift = WorkingShiftUser::orderby('id', 'desc')->where('user_id', $request->user_id)->where('end_date', null)->first();

            if(isset($current_shift) && !empty($current_shift) && $current_shift->working_shift_id != $request->working_shift_id){
                $current_shift->end_date = $request->start_date;
                $current_shift->save();

                $model = WorkingShiftUser::create([
                    'working_shift_id' => $request->working_shift_id,
                    'user_id' => $request->user_id,
                    'start_date' => $request->start_date,
                ]);
            }

            if(empty($current_shift)){
                $model = WorkingShiftUser::create([
                    'working_shift_id' => $request->working_shift_id,
                    'user_id' => $request->user_id,
                    'start_date' => $request->start_date,
                ]);
            }

            $notification_data = [
                'id' => $model->id,
                'date' => $model->start_date,
                'type' => 'shift',
                'name' => $login_user->first_name.' '.$login_user->last_name,
                'profile' => $login_user->profile->profile,
                'title' => 'Your shift has been updated',
                'reason' => 'updated.',
            ];

            if(isset($notification_data) && !empty($notification_data)){
                $model->hasEmployee->notify(new ImportantNotificationWithMail($notification_data));

                if($model->hasEmployee->hasRole('Department Manager')){
                    $parent_department = Department::where('manager_id', $model->user_id)->first();
                    $manager = $parent_department->parentDepartment->manager;
                }else{
                    $manager = $model->hasEmployee->departmentBridge->department->manager;
                }

                $manager->notify(new ImportantNotificationWithMail($notification_data));
            }

            DB::commit();

            \LogActivity::addToLog('Shift has been updated.');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error. '. $e->getMessage());
        }
    }

    public function salaryDetails($getMonth = null, $getYear = null, $user_slug = null)
    {
        $this->authorize('employee_salary_details-list');
        $title = 'Salary Details';
        $data = [];

        $logined_user = Auth::user();

        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        if(isset($user_slug) && !empty($user_slug)){
            $user = User::where('slug', $user_slug)->first();
        }else{
            $user = $logined_user;
        }

        $user_joining_date = date('m/Y');
        if(isset($user->joiningDate->joining_date) && !empty($user->joiningDate->joining_date)){
            $user_joining_date = date('m/Y', strtotime($user->joiningDate->joining_date));
        }

        $data['user_joining_date'] = $user_joining_date;

        $employees = [];

        if($role=='Department Manager'){
            $department = Department::where('manager_id', $logined_user->id)->first();
            $departs = [];
            if(isset($department) && !empty($department->id)){
                $departs[] = $department->id;
            }
            $sub_deps = Department::where('parent_department_id', $department->id)->get();
            if(!empty($sub_deps)){
                foreach($sub_deps as $sub_dept){
                    $departs[] = $sub_dept->id;
                }
            }

            $department_users = DepartmentUser::whereIn('department_id',  $departs)->where('end_date', NULL)->get();
            foreach($department_users as $department_user){
                $dep_users = User::where('id', $department_user->user_id)->where('status', 1)->where('is_employee', 1)->first(['id', 'first_name', 'last_name', 'slug']);
                if(!empty($dep_users)){
                    $employees[] = $dep_users;
                }
            }
        }elseif($role=='Admin'){
            $employees = User::where('is_employee', 1)->where('status', 1)->get(['id', 'first_name', 'last_name', 'slug']);
        }

        $month=date('m');
        $year=date('Y');

        $data['user'] = $user;
        $data['employees'] = $employees;

        if(isset($getMonth) && !empty($getMonth)){
            $data['month'] = $getMonth;
            $data['year'] = $getYear;

            $currentDate = Carbon::now();
            if($data['year'] == date('Y', strtotime($currentDate)) && $getMonth==date('m', strtotime($currentDate))){
                $currentDate = Carbon::now();
                $startDate = Carbon::createFromDate($currentDate->year, $currentDate->month, 25); // Current month's 26th
                $startDate->subMonth();

                $endDate = $currentDate; // Current date

                if ($currentDate->isMidnight() || $currentDate->gt($currentDate->copy()->endOfDay())) {
                    $total_earning_days = $startDate->diffInDays($endDate) + 1;
                }else{
                    $total_earning_days = $startDate->diffInDays($endDate);
                }

                // $startOfMonth = $currentDate->copy()->startOfMonth();
                // $endOfMonth = $currentDate->copy()->endOfMonth();

                // if ($currentDate->day >= 26) {
                //     // Today is on or after the 26th of the month, so include the whole month
                //     $total_earning_days = $startOfMonth->diffInDays($endOfMonth);
                // } else {
                //     // Today is before the 26th of the month, so calculate from the 26th of the previous month
                //     $previousMonth = $startOfMonth->subMonth();

                //     $dayStart = $currentDate->copy()->setTime(1, 0, 0); // 1:00:00 AM
                //     $dayEnd = $currentDate->copy()->setTime(12, 0, 0); // 12:00:00 PM (noon)

                //     if ($currentDate->between($dayStart, $dayEnd, true)) {
                //         $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate);
                //     } else {
                //         $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate) + 1;
                //     }
                // }
            }else{
                $startDate = Carbon::create($year, $getMonth - 1, 26); // 26th of the previous month
                $endDate = Carbon::create($year, $getMonth, 25); // 25th of the current month

                // Calculate the total days within the range
                if ($currentDate->isMidnight() || $currentDate->gt($currentDate->copy()->endOfDay())) {
                    $total_earning_days = $startDate->diffInDays($endDate) + 1;
                }else{
                    $total_earning_days = $startDate->diffInDays($endDate) + 1;
                }
            }
        }else{
            $data['month'] = $month;
            $data['year'] = $year;

            $currentDate = Carbon::now();
            $startDate = Carbon::createFromDate($currentDate->year, $currentDate->month, 25); // Current month's 26th
            $startDate->subMonth();

            $endDate = $currentDate; // Current date

            $current_time = time(); // Get the current Unix timestamp
            $midnight = strtotime(date('Y-m-d') . ' 00:00:00'); // Calculate the Unix timestamp for midnight

            if ($current_time < $midnight) {
                $total_earning_days = $startDate->diffInDays($endDate) + 1;
            }else{
                $total_earning_days = $startDate->diffInDays($endDate);
            }

            // $currentDate = Carbon::now();

            // $startOfMonth = $currentDate->copy()->startOfMonth();
            // $endOfMonth = $currentDate->copy()->endOfMonth();

            // if ($currentDate->day >= 26) {
            //     // Today is on or after the 26th of the month, so include the whole month
            //     $total_earning_days = $startOfMonth->diffInDays($endOfMonth);
            //     // return $total_earning_days;
            // } else {
            //     // Today is before the 26th of the month, so calculate from the 26th of the previous month
            //     $previousMonth = $startOfMonth->subMonth();

            //     $dayStart = $currentDate->copy()->setTime(1, 0, 0); // 1:00:00 AM
            //     $dayEnd = $currentDate->copy()->setTime(12, 0, 0); // 12:00:00 PM (noon)

            //     if ($currentDate->between($dayStart, $dayEnd, true)) {
            //         return $total_earning_days = $previousMonth->day(25)->diffInDays($currentDate);
            //     } else {
            //         $total_earning_days = $previousMonth->day(25)->diffInDays($currentDate) + 1;
            //     }
            // }
        }

        $data['total_earning_days'] = $total_earning_days;

        $date = date('F Y', mktime(0, 0, 0, $data['month'], 1, $data['year']));
        $data['month_year'] = $date;

        $date = Carbon::create($data['year'], $data['month']);

        // Create a Carbon instance for the specified month
        $dateForMonth = Carbon::create(null, $data['month'], 1);

        // Calculate the start date (26th of the specified month)
        $startDate = $dateForMonth->copy()->subMonth()->startOfMonth()->addDays(25);
        $endDate = $dateForMonth->copy()->startOfMonth()->addDays(25);

        // Calculate the total days
        $data['totalDays'] = $startDate->diffInDays($endDate);

        $data['salary'] = 0;
        if(isset($user->salaryHistory) && !empty($user->salaryHistory->salary)){
            $data['salary'] =  $user->salaryHistory->salary;
            $data['per_day_salary'] = $data['salary']/$data['totalDays'];
        }else{
            $data['per_day_salary'] = 0;
            $data['actual_salary'] =  0;
        }

        if(isset($user->userWorkingShift) && !empty($user->userWorkingShift->working_shift_id)){
            $data['shift'] = $user->userWorkingShift->workShift;
        }else{
            $data['shift'] = $user->departmentBridge->department->departmentWorkShift->workShift;
        }
        $statistics = getAttandanceCount($data['user']->id, $data['year']."-".((int)$data['month']-1)."-26", $data['year']."-".(int)$data['month']."-25",'all', $data['shift']);

        $lateIn = count($statistics['lateInDates']);
        $earlyOut = count($statistics['earlyOutDates']);

        $total_discrepancies = $lateIn+$earlyOut;

        $filled_discrepencies = Discrepancy::where('user_id', $user->id)->where('status', 1)->whereBetween('date', [$startDate, $endDate])->count();

        $total_over_discrepancies = $total_discrepancies-$filled_discrepencies;
        $discrepancies_absent_days = 0;
        if($total_over_discrepancies > 2){
            $discrepancies_absent_days = floor($total_over_discrepancies / 3);
            $discrepancies_absent_days = $discrepancies_absent_days/2;
        }

        $data['late_in_early_out_amount'] = $discrepancies_absent_days*$data['per_day_salary'];

        $filled_full_day_leaves = UserLeave::where('user_id', $user->id)
                                            ->where('status', 1)
                                            ->whereMonth('start_at', $data['month'])
                                            ->whereYear('start_at', $data['year'])
                                            ->where('behavior_type', 'Full Day')
                                            ->get();

        $filled_full_day_leaves = $filled_full_day_leaves->sum('duration');

        $filled_half_day_leaves = UserLeave::where('user_id', $user->id)
                                            ->where('status', 1)
                                            ->whereMonth('start_at', $data['month'])
                                            ->whereYear('start_at', $data['year'])
                                            ->where('behavior_type', 'First Half')
                                            ->orWhere('behavior_type', 'Last Half')
                                            ->count();
        $filled_half_day_leaves = $filled_half_day_leaves;
        $filled_half_day_leaves = $statistics['halfDay']-$filled_half_day_leaves;
        $over_half_day_leaves = floor($filled_half_day_leaves / 2);

        $data['half_days_amount'] = $over_half_day_leaves*$data['per_day_salary'];

        $over_absent_days = $statistics['absent']-$filled_full_day_leaves;
        $data['absent_days_amount'] = $over_absent_days*$data['per_day_salary'];

        $total_full_and_half_days_absent = $over_absent_days + $over_half_day_leaves;

        $all_absents = $total_full_and_half_days_absent + $discrepancies_absent_days;
        $all_absent_days_amount = $data['per_day_salary']*$all_absents;

        $data['earning_days_amount'] =  $data['total_earning_days']*$data['per_day_salary'];

        if(!empty($user->hasAllowance)){
            $data['car_allowance'] = $user->hasAllowance->allowance;
        }else{
            $data['car_allowance'] = 0;
        }

        $data['total_actual_salary'] = number_format($data['salary']+$data['car_allowance']);
        $total_earning_salary = $data['earning_days_amount']+$data['car_allowance'];
        $data['total_earning_salary'] = number_format($total_earning_salary);
        $total_net_salary = $total_earning_salary-$all_absent_days_amount;
        $data['net_salary'] = number_format($total_net_salary);

        return view('admin.salary.salary-details', compact('title', 'data'));
    }

    public function generateSalarySlip($getMonth = null, $getYear = null, $user_slug = null)
    {
        $this->authorize('generate_pay_slip-create');
        $title = 'Pay Slip';
        $data = [];

        $logined_user = Auth::user();

        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        if(isset($user_slug) && !empty($user_slug)){
            $user = User::where('slug', $user_slug)->first();
        }else{
            $user = $logined_user;
        }

        $employees = [];

        if(isset($logined_user->departmentBridge) && !empty($logined_user->departmentBridge->department_id)){
            $department_id = $logined_user->departmentBridge->department_id;
        }

        if($role=='Department Manager' && isset($department_id)){
            $department_users = DepartmentUser::where('department_id',  $department_id)->get();
            foreach($department_users as $department_user){
                $employees[] = User::where('id', $department_user->user_id)->first(['first_name', 'slug']);
            }
        }elseif($role=='Admin'){
            $employees = User::where('status', 1)->get(['first_name', 'slug']);
        }

        $month=date('m');
        $year=date('Y');

        $data['user'] = $user;
        $data['employees'] = $employees;

        if(isset($getMonth) && !empty($getMonth)){
            $data['month'] = $getMonth;
            $data['year'] = $getYear;

            $currentDate = Carbon::now();
            if($data['year'] == date('Y', strtotime($currentDate)) && $getMonth==date('m', strtotime($currentDate))){
                $currentDate = Carbon::now();
                $startDate = Carbon::createFromDate($currentDate->year, $currentDate->month, 25); // Current month's 26th
                $startDate->subMonth();

                $endDate = $currentDate; // Current date

                if ($currentDate->isMidnight() || $currentDate->gt($currentDate->copy()->endOfDay())) {
                    $total_earning_days = $startDate->diffInDays($endDate) + 1;
                }else{
                    $total_earning_days = $startDate->diffInDays($endDate);
                }

                // $startOfMonth = $currentDate->copy()->startOfMonth();
                // $endOfMonth = $currentDate->copy()->endOfMonth();

                // if ($currentDate->day >= 26) {
                //     // Today is on or after the 26th of the month, so include the whole month
                //     $total_earning_days = $startOfMonth->diffInDays($endOfMonth);
                // } else {
                //     // Today is before the 26th of the month, so calculate from the 26th of the previous month
                //     $previousMonth = $startOfMonth->subMonth();

                //     $dayStart = $currentDate->copy()->setTime(1, 0, 0); // 1:00:00 AM
                //     $dayEnd = $currentDate->copy()->setTime(12, 0, 0); // 12:00:00 PM (noon)

                //     if ($currentDate->between($dayStart, $dayEnd, true)) {
                //         $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate);
                //     } else {
                //         $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate) + 1;
                //     }
                // }
            }else{
                $startDate = Carbon::create($year, $getMonth - 1, 26); // 26th of the previous month
                $endDate = Carbon::create($year, $getMonth, 25); // 25th of the current month

                // Calculate the total days within the range
                if ($currentDate->isMidnight() || $currentDate->gt($currentDate->copy()->endOfDay())) {
                    $total_earning_days = $startDate->diffInDays($endDate) + 1;
                }else{
                    $total_earning_days = $startDate->diffInDays($endDate);
                }
            }
        }else{
            $data['month'] = $month;
            $data['year'] = $year;

            $currentDate = Carbon::now();
            $startDate = Carbon::createFromDate($currentDate->year, $currentDate->month, 25); // Current month's 26th
            $startDate->subMonth();

            $endDate = $currentDate; // Current date

            if ($currentDate->isMidnight() || $currentDate->gt($currentDate->copy()->endOfDay())) {
                $total_earning_days = $startDate->diffInDays($endDate) + 1;
            }else{
                $total_earning_days = $startDate->diffInDays($endDate);
            }

            // $currentDate = Carbon::now();
            // $startOfMonth = $currentDate->copy()->startOfMonth();
            // $endOfMonth = $currentDate->copy()->endOfMonth();

            // if ($currentDate->day >= 26) {
            //     // Today is on or after the 26th of the month, so include the whole month
            //     $total_earning_days = $startOfMonth->diffInDays($endOfMonth);
            // } else {
            //     // Today is before the 26th of the month, so calculate from the 26th of the previous month
            //     $previousMonth = $startOfMonth->subMonth();

            //     $dayStart = $currentDate->copy()->setTime(1, 0, 0); // 1:00:00 AM
            //     $dayEnd = $currentDate->copy()->setTime(12, 0, 0); // 12:00:00 PM (noon)

            //     if ($currentDate->between($dayStart, $dayEnd, true)) {
            //         $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate);
            //     } else {
            //         $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate) + 1;
            //     }
            // }
        }

        $data['total_earning_days'] = $total_earning_days;

        $date = Carbon::createFromFormat('Y-m', $data['year'] . '-' . $data['month']);
        $data['month_year'] = $date->format('M Y');

        $date = Carbon::create($data['year'], $data['month']);

        // Create a Carbon instance for the specified month
        $dateForMonth = Carbon::create(null, $data['month'], 1);

        // Calculate the start date (26th of the specified month)
        $startDate = $dateForMonth->copy()->subMonth()->startOfMonth()->addDays(25);
        $endDate = $dateForMonth->copy()->startOfMonth()->addDays(25);

        // Calculate the total days
        $data['totalDays'] = $startDate->diffInDays($endDate);

        $data['salary'] = 0;
        if(isset($user->salaryHistory) && !empty($user->salaryHistory->salary)){
            $data['salary'] =  $user->salaryHistory->salary;
            $data['per_day_salary'] = $data['salary']/$data['totalDays'];
        }else{
            $data['per_day_salary'] = 0;
            $data['actual_salary'] =  0;
        }

        if(isset($user->userWorkingShift) && !empty($user->userWorkingShift->working_shift_id)){
            $data['shift'] = $user->userWorkingShift->workShift;
        }else{
            $data['shift'] = $user->departmentBridge->department->departmentWorkShift->workShift;
        }

        $statistics = getAttandanceCount($data['user']->id, $data['year']."-".((int)$data['month']-1)."-26", $data['year']."-".(int)$data['month']."-25",'all', $data['shift']);

        $lateIn = count($statistics['lateInDates']);
        $earlyOut = count($statistics['earlyOutDates']);

        $total_discrepancies = $lateIn+$earlyOut;

        $filled_discrepencies = Discrepancy::where('user_id', $user->id)->where('status', 1)->whereBetween('date', [$startDate, $endDate])->count();

        $total_over_discrepancies = $total_discrepancies-$filled_discrepencies;
        $discrepancies_absent_days = 0;
        if($total_over_discrepancies > 2){
            $discrepancies_absent_days = floor($total_over_discrepancies / 3);
            $discrepancies_absent_days = $discrepancies_absent_days/2;
        }

        $data['late_in_early_out_amount'] = $discrepancies_absent_days*$data['per_day_salary'];

        $filled_full_day_leaves = UserLeave::where('user_id', $user->id)
                                            ->where('status', 1)
                                            ->whereMonth('start_at', $data['month'])
                                            ->whereYear('start_at', $data['year'])
                                            ->where('behavior_type', 'Full Day')
                                            ->get();

        $filled_full_day_leaves = $filled_full_day_leaves->sum('duration');

        $filled_half_day_leaves = UserLeave::where('user_id', $user->id)
                                            ->where('status', 1)
                                            ->whereMonth('start_at', $data['month'])
                                            ->whereYear('start_at', $data['year'])
                                            ->where('behavior_type', 'First Half')
                                            ->orWhere('behavior_type', 'Last Half')
                                            ->count();
        $filled_half_day_leaves = $filled_half_day_leaves;
        $filled_half_day_leaves = $statistics['halfDay']-$filled_half_day_leaves;
        $over_half_day_leaves = floor($filled_half_day_leaves / 2);

        $data['half_days_amount'] = $over_half_day_leaves*$data['per_day_salary'];

        $over_absent_days = $statistics['absent']-$filled_full_day_leaves;
        $data['absent_days_amount'] = $over_absent_days*$data['per_day_salary'];

        $total_full_and_half_days_absent = $over_absent_days + $over_half_day_leaves;

        $all_absents = $total_full_and_half_days_absent + $discrepancies_absent_days;
        $all_absent_days_amount = $data['per_day_salary']*$all_absents;

        $data['earning_days_amount'] =  $data['total_earning_days']*$data['per_day_salary'];

        if(!empty($user->hasAllowance)){
            $data['car_allowance'] = $user->hasAllowance->allowance;
        }else{
            $data['car_allowance'] = 0;
        }

        $data['total_actual_salary'] = number_format($data['salary']+$data['car_allowance']);
        $total_earning_salary = $data['earning_days_amount']+$data['car_allowance'];
        $data['total_earning_salary'] = number_format($total_earning_salary);
        $total_net_salary = $total_earning_salary-$all_absent_days_amount;
        $data['net_salary'] = number_format($total_net_salary);

        return view('admin.salary.salary-slip', compact('title', 'data'));
    }

    public function getTeamMembers($user_id)
    {
        $user = User::findOrFail($user_id);

        $role = $user->getRoleNames()->first();
        foreach($user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $team_members = [];

        if($role == 'Admin'){
            $user_department = Department::where('manager_id', $user->id)->where('status', 1)->first();
            $departments = Department::where('parent_department_id', $user_department->id)->where('status', 1)->get();
            foreach($departments as $department_manager){
                $team_members[] = $department_manager->manager_id;
            }

            // if(isset($user_department) && !empty($user_department)){
            //     $department_users = DepartmentUser::where('department_id', $user_department->id)->where('user_id', '!=', $user->id)->where('end_date', null)->get(['user_id']);
            // }

            // if(sizeof($department_users) > 0) {
            //     foreach($department_users as $department_user) {
            //         $team_members[] = $department_user->user_id;
            //     }
            // }
            // $data['team_members'] = User::with('profile', 'jobHistory', 'jobHistory.designation', 'employeeStatus', 'employeeStatus.employmentStatus')->whereIn('id', $team_members)->where('is_employee', 1)->where('status', 1)->get();

        }else if($role=='Department Manager'){
            $department = Department::where('manager_id', $user->id)->where('status', 1)->first();

            $manager_depts = [];
            $manager_depts[] = $department->id;

            $child_departments = Department::where('parent_department_id', $department->id)->get();
            if(!empty($child_departments) && count($child_departments) > 0){
                foreach($child_departments as $child_department){
                    $manager_depts[] = $child_department->id;
                }
            }

            $department_users = DepartmentUser::where('user_id', '!=', Auth::user()->id)->whereIn('department_id', $manager_depts)->where('end_date', null)->get(['user_id']);
            foreach($department_users as $department_user){
                if($department_user->user_id != $user->id) {
                    $team_members[] = $department_user->user_id;
                }
            }
        }else{
            if(isset($user->departmentBridge->department) && !empty($user->departmentBridge->department->id)) {
                $user_department = $user->departmentBridge->department;
                $team_member_ids = DepartmentUser::where('department_id', $user_department->id)->where('user_id', '!=', $user->id)->where('user_id', '!=', $user_department->manager_id)->get(['user_id']);

                foreach($team_member_ids as $team_member_id){
                    $team_members[] = $team_member_id->user_id;
                }
            }
        }

        $team_members = User::whereIn('id', $team_members)->where('is_employee', 1)->where('status', 1)->get();

        return (string) view('admin.employees.team-members', compact('team_members'));
    }

    public function teamSummary($user_id){
        $user = User::findOrFail($user_id);

        $department = Department::where('manager_id', $user->id)->where('status', 1)->first();
        $department_users = DepartmentUser::where('department_id', $department->id)->where('end_date', null)->get(['user_id']);
        $employees_check_ins = [];
        $absent_employees = [];
        foreach($department_users as $department_user){
            if($department_user->user_id != $user->id) {
                $dept_user = User::where('id', $department_user->user_id)->first();
                $shift = WorkingShiftUser::where('user_id', $department_user->user_id)->where('start_date', '<=', today()->format('Y-m-d'))->orderBy('id', 'desc')->first();

                if(empty($shift)){
                    $shift = $dept_user->departmentBridge->department->departmentWorkShift->workShift;
                }else{
                    $shift = $shift->workShift;
                }

                $current_date = date('Y-m-d');
                $next_date = date("Y-m-d", strtotime('+1 day '.$current_date));
                $start_time = date("Y-m-d h:i A", strtotime($current_date.' '.$shift->start_time));
                $end_time = date("Y-m-d h:i A", strtotime($next_date.' '.$shift->end_time));

                $start_time = date("Y-m-d h:i A", strtotime($current_date.' '.$shift->start_time));
                $end_time = date("Y-m-d h:i A", strtotime($next_date.' '.$shift->end_time));

                $shift_start_time = date("Y-m-d h:i A", strtotime('+16 minutes '.$start_time));
                $shift_end_time = date("Y-m-d h:i A", strtotime('-16 minutes '.$end_time));

                $shift_start_halfday = date("Y-m-d h:i A", strtotime('+121 minutes '.$start_time));
                $shift_end_halfday = date("Y-m-d h:i A", strtotime('-121 minutes '.$end_time));

                $start = date("Y-m-d H:i:s", strtotime('-6 hours '.$start_time));
                $end = date("Y-m-d H:i:s", strtotime('+6 hours '.$end_time));

                $punchIn = Attendance::where('user_id', $department_user->user_id)->where('work_shift_id',$shift->id)->whereBetween('in_date',[$start,$end])->where('behavior','I')->orderBy('id', 'asc')->first();
                $punchOut = Attendance::where('user_id', $department_user->user_id)->where('work_shift_id',$shift->id)->whereBetween('in_date',[$start,$end])->where('behavior','O')->orderBy('id', 'desc')->first();

                $label='-';
                $type='';
                $checkSecond=true;
                if(!empty($punchIn)){
                    if(strtotime($punchIn->in_date) > strtotime($shift_start_time) && strtotime($punchIn->in_date) < strtotime($shift_start_halfday)){
                        $label='<span class="badge bg-label-late-in"> Late In</span>';
                        $type='lateIn';
                        $employees_check = $punchIn;
                        $employees_check_out = $punchOut;
                        $employees_check['label'] = $label;
                        $employees_check['type'] = $type;
                        $employees_check_ins[] = $employees_check;

                    }else if(strtotime($punchIn->in_date) > strtotime($shift_start_halfday)){
                        $label='<span class="badge bg-label-half-day"> Half Day</span>';
                        $type='firsthalf';
                        $checkSecond=false;
                        $employees_check = $punchIn;
                        $employees_check_out = $punchOut;
                        $employees_check['label'] = $label;
                        $employees_check['type'] = $type;
                        $employees_check_ins[] = $employees_check;
                    }else if($type=='regular'){
                        $label='<span class="badge bg-label-regular">Regular</span>';
                        $type='regular';
                        $employees_check = $punchIn;
                        $employees_check_out = $punchOut;
                        $employees_check['label'] = $label;
                        $employees_check['type'] = $type;
                        $employees_check_ins[] = $employees_check;
                    }
                }
                // if(!empty($punchOut)){
                //     $punchOutRecord=new DateTime($punchOut->in_date);
                //     $checkOut=$punchOutRecord->format('h:i A');
                //     if($checkSecond && (strtotime($punchOut->in_date) < strtotime($shift_end_time) && strtotime($punchOut->in_date) > strtotime($shift_end_halfday))){
                //         $label='<span class="badge bg-label-warning"><i class="far fa-dot-circle text-danger"></i> Early Out</span>';
                //         $type='earlyout';
                //         $employees_check = $punchIn;
                //         $employees_check_out = $punchOut;
                //         $employees_check['label'] = $label;
                //         $employees_check['type'] = $type;
                //         $employees_check_ins[] = $employees_check;
                //     }else if(strtotime($punchOut->in_date) < strtotime($shift_end_halfday)){
                //         $label='<span class="badge bg-label-danger"><i class="far fa-dot-circle text-danger"></i>Last Half-Day</span>';
                //         $type='lasthalf';

                //         $employees_check = $punchIn;
                //         $employees_check_out = $punchOut;
                //         $employees_check['label'] = $label;
                //         $employees_check['type'] = $type;
                //         $employees_check_ins[] = $employees_check;
                //     }
                // }

                if((empty($punchIn)) && strtotime($end_time)<=strtotime(date('Y-m-d h:i A'))){
                    $label='<span class="badge bg-label-full-day"> Absent</span>';
                    $type='absent';
                    $employees_check[] = $dept_user;
                    $employees_check['label'] = $label;
                    $employees_check['type'] = $type;
                    $employees_check_ins[] = $employees_check;
                }
            }
        }

        return view('admin.employees.team-summary', compact('employees_check_ins'));
    }

    public function teamMembers(Request $request)
    {
        $this->authorize('team_members-list');
        $title = 'Team Members';

        $login_user = Auth::user();
        if(!$login_user->hasRole('Admin')){
            $login_user = User::whereHas('roles', function ($query) {
                $query->where('name', 'Admin'); // Replace 'admin' with the actual role name.
            })->first();
        }

        $login_user = User::where('slug', $login_user->slug)->first();
        $data = [];

        $employee_ids = [];

        $user_department = Department::where('manager_id', $login_user->id)->where('status', 1)->first();
        $departments = Department::where('parent_department_id', $user_department->id)->where('status', 1)->get();
        foreach($departments as $department_manager){
            $user = User::where('id', $department_manager->manager_id)->where('is_employee', 1)->where('status', 1)->first();
            if(!empty($user)){
                $employee_ids[] = $user->id;
            }
        }

        $model = User::whereIn('id', $employee_ids)->where('is_employee', 1)->where('status', 1)->get();

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Active</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">De-active</span>';
                            break;
                    }

                    return $label;
                })
                ->addColumn('department', function($model){
                    if(Auth::user()->hasRole('Admin')){
                        if(isset($model->hasManagerDepartment) && !empty($model->hasManagerDepartment->name)){
                            return '<span class="text-primary fw-semibold">'.$model->hasManagerDepartment->name.'</span>';
                        }else{
                            return '-';
                        }
                    }else{
                        if(isset($model->departmentBridge->department) && !empty($model->departmentBridge->department)){
                            return '<span class="text-primary fw-semibold">'.$model->departmentBridge->department->name.'</span>';
                        }else{
                            return '-';
                        }
                    }
                })
                ->addColumn('shift', function($model){
                    if(isset($model->userWorkingShift->workShift) && !empty($model->userWorkingShift->workShift->name)) {
                        return '<span class="fw-semibold">'.$model->userWorkingShift->workShift->name.'</span>';
                    }else{
                        return '-';
                    }
                })
                ->addColumn('role', function ($model) {
                    return '<span class="badge bg-label-primary">'.$model->getRoleNames()->first().'</span>';
                })
                ->editColumn('first_name', function ($model) {
                    return view('admin.employees.employee-profile', ['employee' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('admin.employees.team-members-action', ['employee' => $model])->render();
                })
                ->rawColumns(['status', 'first_name', 'role', 'department', 'shift', 'action'])
                ->make(true);
        }

        return view('admin.employees.team-members-list', compact('title', 'data'));
    }

    public function managerTeamMembers(Request $request)
    {
        $this->authorize('manager_team_member-list');
        $title = 'Team Members';
        $login_user = User::where('slug', Auth::user()->slug)->first();
        $data = [];

        $role = $login_user->getRoleNames()->first();
        foreach($login_user->getRoleNames() as $user_role){
            if($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $employee_ids = [];
        $dept_ids = [];

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
                $user = User::where('id', '!=', Auth::user()->id)->where('id', $department_user->user_id)->where('is_employee', 1)->where('status', 1)->first();
                if(!empty($user)){
                    $employee_ids[] = $user->id;
                }
            }
        }

        $model = User::whereIn('id', $employee_ids)->where('is_employee', 1)->where('status', 1)->get();

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('status', function ($model) {
                    $label = '';

                    switch ($model->status) {
                        case 1:
                            $label = '<span class="badge bg-label-success" text-capitalized="">Active</span>';
                            break;
                        case 0:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">De-active</span>';
                            break;
                    }

                    return $label;
                })
                ->addColumn('department', function($model){
                    if(Auth::user()->hasRole('Admin')){
                        if(isset($model->hasManagerDepartment) && !empty($model->hasManagerDepartment->name)){
                            return '<span class="text-primary fw-semibold">'.$model->hasManagerDepartment->name.'</span>';
                        }else{
                            return '-';
                        }
                    }else{
                        if(isset($model->departmentBridge->department) && !empty($model->departmentBridge->department)){
                            return '<span class="text-primary fw-semibold">'.$model->departmentBridge->department->name.'</span>';
                        }else{
                            return '-';
                        }
                    }
                })
                ->addColumn('shift', function($model){
                    if(isset($model->userWorkingShift->workShift) && !empty($model->userWorkingShift->workShift->name)) {
                        return '<span class="fw-semibold">'.$model->userWorkingShift->workShift->name.'</span>';
                    }else{
                        return '-';
                    }
                })
                ->addColumn('role', function ($model) {
                    return '<span class="badge bg-label-primary">'.$model->getRoleNames()->first().'</span>';
                })
                ->editColumn('first_name', function ($model) {
                    return view('admin.employees.employee-profile', ['employee' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('admin.employees.team-members-action', ['employee' => $model])->render();
                })
                ->rawColumns(['status', 'first_name', 'role', 'department', 'shift', 'action'])
                ->make(true);
        }

        return view('admin.employees.manager-team-members-list', compact('title', 'data'));
    }

    public function userDirectPermissionEdit($slug){
        $user = User::where('slug', $slug)->first();
        $user_permissions = $user->getPermissionNames();
        $models = Permission::orderby('id','DESC')->groupBy('label')->get();

        return (string) view('admin.employees.edit-direct-permission', compact('user', 'models', 'user_permissions'));
    }

    public function userDirectPermissionUpdate(Request $request, $user_slug){
        DB::beginTransaction();

        try{
            $user = User::where('slug', $user_slug)->first();

            $user->syncPermissions($request->input('permissions'));

            DB::commit();

            \LogActivity::addToLog('Direct Permission assigned');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function getUserDetails(Request $request){
        $user = User::where('id', $request->user_id)->first();
        $user_details = '';
        if(isset($user->hasPreEmployee) && !empty($user->hasPreEmployee)){
            $user_details = $user->hasPreEmployee;
        }else if(isset($user->profile) && !empty($user->profile)){
            $user_details = $user->profile;
        }

        return $user_details;
    }

    public function reHire(Request $request){
        $user = User::where('id', $request->user_id)->first();

        $resignation = Resignation::orderby('id', 'desc')->where('status', 2)->where('employee_id', $user->id)->first(); //2=terminated approved by admin.


        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'email' => 'required|max:255|ends_with:demo.org|unique:users,id,'.$user->id,
            'employment_status_id' => [
                'required',
                'not_in:3',
            ],
            'designation_id' => 'required',
            'department_id' => 'required',
            'role_ids' => 'required',
            'role_ids*' => 'required',
            'joining_date' => 'required',
            'work_shift_id' => 'required',
            'employment_id' => 'max:200',
            'salary' => 'max:255',
        ]);

        DB::beginTransaction();

        try{
            $resignation->employment_status_id = $request->employment_status_id;
            $resignation->is_rehired = 1;
            $resignation->save();

            // cPanel API credentials
            $cpanelUsername = 'demo';
            $cpanelToken = 'XVVAA0LUFSK6OSE0F1TWJHIEFW94G98I';
            $cpanelDomain = 'host.dnscloudcentral.com';

            // Email account details
            $user_email = $request->email;
            $user_password = Str::random(8);
            // $user_password = 'demo@2023';

            // Call the function to create an email account
            $this->createEmailAccount($cpanelUsername, $cpanelToken, $cpanelDomain, $user_email, $user_password);

            // if($create_email_response=='failed'){
            //     return response()->json(['success' => 'failed', 'message' => 'This account '.$user_email.' already exists!']);
            // }

            $user->created_by = Auth::user()->id;
            $user->status = 1;
            $user->is_employee = 1;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;

            if($user->email != $request->email) {
                $user->email = $user_email;
                $user->password = Hash::make($user_password);
            }

            $user->save();
            $user->syncRoles($request->role_ids);

            if($user){
                Profile::where('user_id', $user->id)->update([
                    'employment_id' => $request->employment_id,
                    'joining_date' => $request->joining_date,
                    'gender' => $request->gender,
                    'phone_number' => $request->phone_number,
                ]);

                $job_history = JobHistory::create([
                    'created_by' => Auth::user()->id,
                    'user_id' => $user->id,
                    'designation_id' => $request->designation_id,
                    'employment_status_id' => $request->employment_status_id,
                    'joining_date' => $request->joining_date,
                ]);

                if(!empty($request->department_id)){
                    DepartmentUser::create([
                        'department_id' => $request->department_id,
                        'user_id' => $user->id,
                        'start_date' => $request->joining_date,
                    ]);
                }

                if($job_history && !empty($request->salary)){
                    SalaryHistory::create([
                        'created_by' => Auth::user()->id,
                        'user_id' => $user->id,
                        'job_history_id' => $user->jobHistory->id,
                        'salary' => $request->salary,
                        'effective_date' => $request->joining_date,
                        'status' => 1,
                    ]);
                }

                UserEmploymentStatus::create([
                    'user_id' => $user->id,
                    'employment_status_id' => $request->employment_status_id,
                    'start_date' => $request->joining_date,
                ]);

                WorkingShiftUser::create([
                    'user_id' => $user->id,
                    'working_shift_id' =>$request->work_shift_id,
                    'start_date' => $request->joining_date,
                ]);

                DB::commit();
            }

            //send email with password.
            $model = $user;

            //Employee portal credentials mail
            $employee_info = [
                'name' => $model->first_name.' '.$model->last_name ,
                'email' => $model->email,
                'password' => $user_password,
            ];

            $mailData = [
                'from' => 'welcome',
                'title' => 'Welcome to Our Team - Important Onboarding Information',
                'employee_info' => $employee_info,
            ];

            Mail::to($user_email)->send(new Email($mailData));

            //Joining Email to departments
            $manager_name = '';
            if(isset($model->departmentBridge->department->manager) && !empty($model->departmentBridge->department->manager->first_name)) {
                $manager_name = $model->departmentBridge->department->manager->first_name;
            }

            $designation_name = '';
            if(isset($model->jobHistory->designation) && !empty($model->jobHistory->designation->title)) {
                $designation_name = $model->jobHistory->designation->title;
            }
            $department_name = '';
            if(isset($model->departmentBridge->department) && !empty($model->departmentBridge->department->name)) {
                $department_name = $model->departmentBridge->department->name;
            }
            $work_shift_name = '';
            if(isset($model->userWorkingShift->workShift) && !empty($model->userWorkingShift->workShift->name)) {
                $work_shift_name = $model->userWorkingShift->workShift->name;
            }
            $joining_date = '';
            if(isset($model->profile) && !empty($model->profile->joining_date)) {
                $joining_date = date('d M Y', strtotime($model->profile->joining_date));
            }

            $employee_info = [
                'name' => $model->first_name.' '. $model->last_name,
                'email' => $model->email,
                'password' => $user_password,
                'manager' => $manager_name,
                'designation' => $designation_name,
                'department' => $department_name,
                'shift_time' => $work_shift_name,
                'joining_date' => $joining_date,
            ];

            $mailData = [
                'from' => 'employee_info',
                'title' => 'Employee Approval and Joining Information',
                'employee_info' => $employee_info,
            ];

            if(!empty(sendEmailTo($model, 'new_employee_info')) && !empty(sendEmailTo($model, 'new_employee_info')['cc_emails'])){
                $to_emails = sendEmailTo($model, 'new_employee_info')['to_emails'];
                $cc_emails = sendEmailTo($model, 'new_employee_info')['cc_emails'];
                Mail::to($to_emails)->cc($cc_emails)->send(new Email($mailData));
            }elseif(!empty(sendEmailTo($model, 'new_employee_info')['to_emails'])){
                $to_emails = sendEmailTo($model, 'new_employee_info')['to_emails'];
                Mail::to($to_emails)->send(new Email($mailData));
            }

            //send email with password.

            \LogActivity::addToLog('Employee re-hired');
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}

