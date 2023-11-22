<?php

namespace App\Http\Controllers;

use DB;
use PDF;
use Str;
use Auth;
use App\Mail\Email;
use App\Models\User;
use App\Models\Resume;
use App\Models\Profile;
use App\Models\Academic;
use App\Models\Position;
use App\Models\Reference;
use App\Models\WorkShift;
use App\Models\Department;
use App\Models\JobHistory;
use App\Models\Designation;
use App\Models\PreEmployee;
use Illuminate\Http\Request;
use App\Models\SalaryHistory;
use App\Models\AuthorizeEmail;
use App\Models\DepartmentUser;
use App\Models\EmployeeLetter;
use Illuminate\Support\Carbon;
use App\Models\AppliedPosition;
use App\Models\WorkingShiftUser;
use App\Models\EmploymentHistory;
use App\Models\UserEmploymentStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Yajra\DataTables\Facades\DataTables;

class PreEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $title = 'All Pre-Employees';
        $temp = 'All Pre-Employees';

        if(Auth::user()->hasRole('Department Manager')){
            // $model = PreEmployee::orderby('id', 'desc')->where('manager_id', Auth::user()->id)->get();

            $model = [];
            PreEmployee::where('manager_id', Auth::user()->id)
                ->latest()
                ->chunk(100, function ($pre_employees) use (&$model) {
                    foreach ($pre_employees as $pre_employee) {
                        $model[] = $pre_employee;
                    }
            });
        // }elseif(Auth::user()->hasRole('Admin')){
        }else{
            // $model = PreEmployee::orderby('id', 'desc')->get();

            $model = [];
            PreEmployee::latest()
                ->chunk(100, function ($pre_employees) use (&$model) {
                    foreach ($pre_employees as $pre_employee) {
                        $model[] = $pre_employee;
                    }
            });
        }

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->addColumn('applied_position', function($model){
                    if(isset($model->hasAppliedPosition->hasPosition) && !empty($model->hasAppliedPosition->hasPosition->title)){
                        $label = '<b class="text-primary">'.$model->hasAppliedPosition->hasPosition->title.'</b>';
                        return '<span class="text-primary fw-semibold">'.strip_tags($label).'</span';
                    }else{
                        return '-';
                    }
                })
                ->addColumn('expected_salary', function($model){
                    if(isset($model->hasAppliedPosition) && !empty($model->hasAppliedPosition->expected_salary)){
                        $expected_salary_label = 'PKR. <b>'.number_format($model->hasAppliedPosition->expected_salary).'</b>';
                        return '<span class="fw-semibold">'.strip_tags($expected_salary_label).'</span>';
                    }else{
                        return '-';
                    }
                })
                ->editColumn('status', function ($data) {
                    $label = '';

                    switch ($data->status) {
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
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->addColumn('manager_id', function($model){
                    return view('admin.pre_employees.manager-profile', ['employee' => $model])->render();
                })
                ->editColumn('name', function ($model) {
                    return view('admin.pre_employees.pre_employee-profile', ['employee' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('admin.pre_employees.pre_employees-action', ['employee' => $model])->render();
                })
                ->rawColumns(['status', 'name', 'manager_id', 'applied_position', 'expected_salary',  'action'])
                ->make(true);
        }

        return view('admin.pre_employees.index', compact('title', 'temp'));
    }

    public function create()
    {
        $data = [];
        $title = 'Pre-Employee Form';

        $data['positions'] = Position::where('status', 1)->get();
        $data['managers'] = User::role('Department Manager')->where('status', 1)->get();
        return view('user.pre_employees.create', compact('title', 'data'));
    }

    public function store(Request $request)
    {
        $resume='';
        if($request->file('resume')){
            $image = $request->file('resume');
            $resume = rand(). '_employee_resume.'. $image->getClientOriginalExtension();
            $image->move(public_path('resumes'), $resume);
        }

        $insertEmployee = PreEmployee::create([
            'manager_id' => $request->manager_id,
            'name' => $request->name,
            'father_name' => $request->father_name,
            'email' => $request->email,
            'date_of_birth' => $request->date_of_birth,
            'cnic' => $request->cnic,
            'contact_no' => $request->contact_no,
            'emergency_number' => $request->emergency_number,
            'address' => $request->address,
            'apartment' => $request->apartment,
            'marital_status' => $request->marital_status
        ]);


        if(!empty($insertEmployee)) {
            AppliedPosition::create([
                'pre_employee_id' => $insertEmployee->id,
                'applied_for_position' => $request->applied_for_position,
                'expected_salary' => $request->expected_salary,
                'expected_joining_date' => $request->expected_joining_date,
                'source_of_this_post' => $request->source_of_this_post
            ]);

            Academic::create([
                'pre_employee_id' => $insertEmployee->id,
                'degree' => $request->degree,
                'major_subject' => $request->major_subject,
                'institute' => $request->institute,
                'passing_year' => $request->passing_year,
                'grade_or_gpa' => $request->grade_or_gpa,
            ]);

            //reference 1
            Reference::create([
                'pre_employee_id' => $insertEmployee->id,
                'reference_name' => $request->first_ref_name,
                'company' => $request->first_ref_company,
                'contact_no' => $request->first_ref_contact,
            ]);

            //reference 2
            if(isset($request->second_ref_name) && !empty($request->second_ref_name)) {
                Reference::create([
                    'pre_employee_id' => $insertEmployee->id,
                    'reference_name' => $request->second_ref_name,
                    'company' => $request->second_ref_company,
                    'contact_no' => $request->second_ref_contact,
                ]);
            }

            //reference 3
            if(isset($request->second_ref_name) && !empty($request->second_ref_name)) {
                Reference::create([
                    'pre_employee_id' => $insertEmployee->id,
                    'reference_name' => $request->third_ref_name,
                    'company' => $request->third_ref_company,
                    'contact_no' => $request->third_ref_contact,
                ]);
            }

            Resume::create([
                'pre_employee_id' => $insertEmployee->id,
                'hobbies_and_interests' => $request->hobbies_and_interests,
                'achievements' => $request->achievements,
                'portfolio_link' => $request->portfolio_link ? $request->portfolio_link : '',
                'resume' => $resume
            ]);

            // step3
            if(is_array($request->input('companies'))) {
                $companies=$request->input('companies');
                $designations=$request->input('designations');
                $durations=$request->input('durations');
                $salaries=$request->input('salaries');
                $reasons=$request->input('reasons_of_leaving');
                for ($i=0;$i<count($companies);$i++) {
                    EmploymentHistory::create([
                        'pre_employee_id' => $insertEmployee->id,
                        'company' => $companies[$i] ? $companies[$i] : '',
                        'designation' => $designations[$i] ? $designations[$i] : '',
                        'duration' => $durations[$i] ? $durations[$i] : '',
                        'salary' => $salaries[$i] ? $salaries[$i] : '',
                        'reason_of_leaving' => $reasons[$i] ? $reasons[$i] : ''
                    ]);
                }
            } else {
                EmploymentHistory::create([
                    'pre_employee_id' => $insertEmployee->id,
                    'company' => $request->input('companies') ? $request->input('companies') : '',
                    'designation' => $request->input('designations') ? $request->input('designations') : '',
                    'duration' => $request->input('durations') ? $request->input('durations') : '',
                    'salary' => $request->input('salaries') ? $request->input('salaries') : '',
                    'reason_of_leaving' => $request->input('reasons_of_leaving') ? $request->input('reasons_of_leaving') : ''
                ]);
            }
        }

        return redirect()->route('pre_employee.thank-you');

    }

    public function thankYou()
    {
        $title = 'Thank You';
        return view('user.pre_employees.thank-you', compact('title'));
    }

    public function edit($pre_employee_id){
        $data = [];
        $model = PreEmployee::where('id', $pre_employee_id)->first();
        $user_email = explode('@', $model->email);
        $data['user_email'] = $user_email[0].'@demo.org';
        $data['model'] = $model;

        $salary = '';
        if(isset($model->hasAppliedPosition) && !empty($model->hasAppliedPosition)){
            $salary = $model->hasAppliedPosition->expected_salary;
        }
        $data['expected_salary'] = $salary;
        $data['designations'] = Designation::where('status', 1)->get();
        $data['departments'] = Department::where('status', 1)->get();
        $data['shifts'] = WorkShift::where('status', 1)->get();
        return (string) view('admin.pre_employees.edit_content', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'email' => ['required', 'ends_with:demo.org', 'string', 'email', 'max:255', 'unique:'.User::class],
            'designation_id' => 'required_without:custom_designation',
            'custom_designation' => 'required_without:designation_id',
            'salary' => 'required',
            'department_id' => 'required',
            'working_shift_id' => 'required',
            'joining_date' => 'required',
            'is_vehicle' => 'in:0,1',
            'vehicle_cc' => 'required_if:is_vehicle,1',
        ]);

        $pre_employee = PreEmployee::where('id', $id)->first();

        DB::beginTransaction();

        try{
            $user_email = $request->email;

            $user_exist = User::where('email', $user_email)->first();
            // if($user_exist){
            //     return redirect()->back()->with('message', 'He is already employee.');
            // }else{
                // cPanel API credentials
            $cpanelUsername = 'demo';
            $cpanelToken = '58WW5OY5PYWI69661JPHW92G9O05PE37';
            $cpanelDomain = 'host.demo.info';

            // Email account details
            $emailUsername = $user_email;
            // $user_password = Str::random(8);
            $user_password = 'demo@2023';

            // Call the function to create an email account
            $create_email_response = $this->createEmailAccount($cpanelUsername, $cpanelToken, $cpanelDomain, $emailUsername, $user_password);

            if($create_email_response=='failed'){
                return response()->json(['success' => 'failed', 'message' => 'This account '.$user_email.' already exists!']);
            }
            // }

            $pre_employee->status = 1;
            $pre_employee->note = $request->note;
            $pre_employee->save();

            $emp_name =  $pre_employee->name;
            $parts = explode(' ', $emp_name);

            $first_name = '';
            $last_name = '';
            if (count($parts) >= 2) {
                $first_name = $parts[0];
                $last_name = implode(' ', array_slice($parts, 1));
                $last_name = Str::title($last_name);
            }else{
                $first_name = $pre_employee->name;
            }

            if($pre_employee) {
                $model = [
                    'pre_emp_id' => $pre_employee->id,
                    'created_by' => Auth::user()->id,
                    'is_vehicle' => $request->is_vehicle,
                    'status' => 1,
                    'slug' => $pre_employee->name.'-'.Str::random(5),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $emailUsername,
                    'password' => Hash::make($user_password),
                ];

                $model = User::create($model);

                $model->assignRole('Employee');

                if($model) {
                    Profile::create([
                        'user_id' => $model->id,
                        'employment_id' => NULL,
                        'joining_date' => $request->joining_date,
                        'phone_number' => $pre_employee->contact_no,
                    ]);

                    $designation_id = '';
                    if(empty($request->designation_id)){
                        $designation = Designation::where('title', $request->custom_designation)->first();
                        if(empty($designation )){
                            $designation = Designation::create([
                                'title' => $request->custom_designation,
                                'status' => 1,
                            ]);

                            $designation_id = $designation->id;
                        }else{
                            $designation_id = $designation->id;
                        }
                    }else{
                        $designation_id = $request->designation_id;
                    }

                    $job_history = JobHistory::create([
                        'created_by' => Auth::user()->id,
                        'user_id' => $model->id,
                        'designation_id' => $designation_id,
                        'employment_status_id' => 1,
                        'joining_date' => $request->joining_date,
                        'vehicle_cc' => $request->vehicle_cc??NULL,
                    ]);

                    if($job_history && !empty($request->salary)) {
                        SalaryHistory::create([
                            'created_by' => Auth::user()->id,
                            'user_id' => $model->id,
                            'job_history_id' => $job_history->id,
                            'salary' => $request->salary,
                            'effective_date' => $request->joining_date,
                            'status' => 1,
                        ]);
                    }

                    if(!empty($request->department_id)) {
                        DepartmentUser::create([
                            'department_id' => $request->department_id,
                            'user_id' => $model->id,
                            'start_date' => $request->joining_date,
                        ]);
                    }
                    if(!empty($request->working_shift_id)) {
                        WorkingShiftUser::create([
                            'working_shift_id' => $request->working_shift_id,
                            'user_id' => $model->id,
                            'start_date' => $request->joining_date,
                        ]);
                    }

                    UserEmploymentStatus::create([
                        'user_id' => $model->id,
                        'employment_status_id' => 1,
                        'start_date' => $request->joining_date,
                    ]);

                    // Get the joining date from the request
                    $joiningDate = $request->joining_date;

                    // Add 7 days to the joining date
                    $validity_date = date('Y-m-d', strtotime($joiningDate . ' +7 days'));

                    EmployeeLetter::create([
                        'created_by' => Auth::user()->id,
                        'employee_id' => $model->id,
                        'title' => "joining_letter",
                        'effective_date' => $request->joining_date,
                        'validity_date' => $validity_date,
                    ]);

                    DB::commit();
                }

                //Employee portal credentials mail
                $employee_info = [
                    'name' => $model->first_name.' '.$model->last_name,
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
                    $manager_name = $model->departmentBridge->department->manager->first_name. ' ' .$model->departmentBridge->department->manager->last_name;
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
                    'name' => $model->first_name.' '.$model->last_name,
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
                }else{
                    $to_emails = sendEmailTo($model, 'new_employee_info')['to_emails'];
                    Mail::to($to_emails)->send(new Email($mailData));
                }

                return response()->json(['success' => true]);
                //send email with password.
            }
        } catch (\Exception $e) {
            DB::rollback();
            return $e->getMessage();
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
                // echo 'Email account created successfully.';
                return 'success';
            } else {
                // echo 'Failed to create email account. Error: ' . $responseData['result']['errors'][0]['message'];
                return 'failed';
            }
        } else {
            // echo 'Failed to create email account. Error: ' . $httpCode;
            return 'failed';
        }
    }

    public function show($id)
    {
        $title = 'Show Employee Details';
        $model = PreEmployee::with('haveReferences')->where('id', $id)->first();
        return view('admin.pre_employees.show', compact('model', 'title'));
    }

    public function destroy($id)
    {
        $model = PreEmployee::where('id', $id)->delete();
        if($model){
            return true;
        }else{
            return false;
        }
    }

    public function trashed(Request $request)
    {
        $title = 'All Trashed Records';

        if($request->ajax()) {
            $model = PreEmployee::orderby('id', 'desc')->onlyTrashed();

            return DataTables::of($model)
                ->addIndexColumn()
                ->addColumn('applied_position', function($model){
                    if(isset($model->hasAppliedPosition->hasPosition) && !empty($model->hasAppliedPosition->hasPosition->title)){
                        $label = '<b class="text-primary">'.$model->hasAppliedPosition->hasPosition->title.'</b>';
                        return strip_tags($label);
                    }else{
                        return '-';
                    }
                })
                ->addColumn('expected_salary', function($model){
                    if(isset($model->hasAppliedPosition) && !empty($model->hasAppliedPosition->expected_salary)){
                        $expected_salary_label = 'PKR. <b>'.number_format($model->hasAppliedPosition->expected_salary).'</b>';
                        return strip_tags($expected_salary_label);
                    }else{
                        return '-';
                    }
                })
                ->editColumn('status', function ($data) {
                    $label = '';

                    switch ($data->status) {
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
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->addColumn('manager_id', function($model){
                    return view('admin.pre_employees.manager-profile', ['employee' => $model])->render();
                })
                ->editColumn('name', function ($model) {
                    return view('admin.pre_employees.pre_employee-profile', ['employee' => $model])->render();
                })
                ->addColumn('action', function($model){
                    $button = '<a href="'.route('pre_employees.restore', $model->id).'" class="btn btn-icon btn-label-info waves-effect">'.
                                    '<span>'.
                                        '<i class="ti ti-refresh ti-sm"></i>'.
                                    '</span>'.
                                '</a>';
                    return $button;
                })
                ->rawColumns(['status', 'name', 'manager_id', 'action'])
                ->make(true);
        }

        return view('admin.pre_employees.index', compact('title'));
    }
    public function restore($id)
    {
        PreEmployee::onlyTrashed()->where('id', $id)->restore();
        return redirect()->back()->with('message', 'Record Restored Successfully.');
    }

    public function convertPdf($pre_employee_id){
        $title = 'Pre-Employee PDF Form';
        $model = PreEmployee::where('id', $pre_employee_id)->first();

        $pdf = PDF::loadView('admin.pre_employees.pre-employee-pdf', compact('title', 'model'));

        $download_file_name = str_replace(' ', '_', Str::lower($model->name)).'-'.date('d-m-Y');
        return $pdf->download($download_file_name.'.pdf');
    }
}
