<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\User;
use App\Models\Profile;
use App\Models\Discrepancy;
use App\Models\UserLeave;
use App\Models\DepartmentUser;
use App\Models\Department;
use App\Models\MonthlySalaryReport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\Email;
use Auth;
use Carbon\Carbon;

class DeveloperController extends Controller
{
    public function generateMonthlySalaryReport(){
        return 'Already Done';
        $data = [];

        // $data['month']=date('m');
        // $data['year']=date('Y');

        $employees = User::where('is_employee', 1)->where('status', 1)->get();
        foreach($employees as $employee) {
            // $currentDate = Carbon::now();
            $currentDate = Carbon::now();
            $currentDate = $currentDate->subMonth();

            $data['month']=date('m', strtotime($currentDate));
            $data['year']=date('Y', strtotime($currentDate));

            $startOfMonth = $currentDate->copy()->startOfMonth();
            $endOfMonth = $currentDate->copy()->endOfMonth();

            // Today is before the 26th of the month, so calculate from the 26th of the previous month
            $previousMonth = $startOfMonth->subMonth();

            // if (date('G') < 0) { //it check it is less than mid night means before of 12pm
            $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate);
            // } else {
            //     $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate) + 1;
            // }

            $data['total_earning_days'] = $total_earning_days;

            $date = Carbon::createFromFormat('Y-m', $data['year'] . '-' . $data['month']);
            $data['month_year'] = $date->format('m/Y');

            // $date = Carbon::create($data['year'], $data['month']);

            // Create a Carbon instance for the specified month
            $dateForMonth = Carbon::create(null, $data['month'], 1);

            // Calculate the start date (26th of the specified month)
            $startDate = $dateForMonth->copy()->subMonth()->startOfMonth()->addDays(25);
            $endDate = $dateForMonth->copy()->startOfMonth()->addDays(25);

            // Calculate the total days
            $data['totalDays'] = $startDate->diffInDays($endDate);

            $data['salary'] = 0;
            if(isset($employee->salaryHistory) && !empty($employee->salaryHistory->salary)) {
                $data['salary'] =  $employee->salaryHistory->salary;
                $data['per_day_salary'] = $data['salary'] / $data['totalDays'];
            } else {
                $data['per_day_salary'] = 0;
                $data['actual_salary'] =  0;
            }

            if(isset($employee->userWorkingShift) && !empty($employee->userWorkingShift->working_shift_id)) {
                $data['shift'] = $employee->userWorkingShift->workShift;
            } else {
                $data['shift'] = $employee->departmentBridge->department->departmentWorkShift->workShift;
            }
            $statistics = getAttandanceCount($employee->id, $data['year'] . "-" . ((int)$data['month'] - 1) . "-26", $data['year'] . "-" . (int)$data['month'] . "-25", 'all', $data['shift']);

            $lateIn = count($statistics['lateInDates']);
            $earlyOut = count($statistics['earlyOutDates']);

            $total_discrepancies = $lateIn + $earlyOut;

            $filled_discrepancies = Discrepancy::where('user_id', $employee->id)->where('status', 1)->whereBetween('date', [$startDate, $endDate])->count();

            $total_over_discrepancies = $total_discrepancies - $filled_discrepancies;
            $discrepancies_absent_days = 0;
            if($total_over_discrepancies > 2) {
                $discrepancies_absent_days = floor($total_over_discrepancies / 3);
            }
            $data['late_in_early_out_amount'] = $discrepancies_absent_days * $data['per_day_salary'];

            $filled_full_day_leaves = UserLeave::where('user_id', $employee->id)
                                                ->where('status', 1)
                                                ->whereMonth('start_at', $data['month'])
                                                ->whereYear('start_at', $data['year'])
                                                ->where('behavior_type', 'Full Day')
                                                ->get();

            $filled_full_day_leaves = $filled_full_day_leaves->sum('duration');

            $filled_half_day_leaves = UserLeave::where('user_id', $employee->id)
                                                ->where('status', 1)
                                                ->whereMonth('start_at', $data['month'])
                                                ->whereYear('start_at', $data['year'])
                                                ->where('behavior_type', 'First Half')
                                                ->orWhere('behavior_type', 'Last Half')
                                                ->count();
            $filled_half_day_leaves = $filled_half_day_leaves;
            $filled_half_day_leaves = $statistics['halfDay'] - $filled_half_day_leaves;
            $over_half_day_leaves = floor($filled_half_day_leaves / 2);

            $data['half_days_amount'] = $over_half_day_leaves * $data['per_day_salary'];

            $over_absent_days = $statistics['absent'] - $filled_full_day_leaves;
            $data['absent_days_amount'] = $over_absent_days * $data['per_day_salary'];

            $total_full_and_half_days_absent = $over_absent_days + $over_half_day_leaves;

            $all_absents = $total_full_and_half_days_absent + $discrepancies_absent_days;
            $all_absent_days_amount = $data['per_day_salary'] * $all_absents;

            $data['earning_days_amount'] =  $data['total_earning_days'] * $data['per_day_salary'];

            if(!empty($employee->hasAllowance)) {
                $data['car_allowance'] = $employee->hasAllowance->allowance;
            } else {
                $data['car_allowance'] = 0;
            }

            $data['total_actual_salary'] = $data['salary'];
            $total_earning_salary = $data['earning_days_amount'] ;
            $data['total_earning_salary'] = $total_earning_salary;
            $total_earning_and_deduction_amount = $total_earning_salary + $all_absent_days_amount;
            $data['total_leave_discrepancies_approve_salary'] = $data['salary'] - $total_earning_and_deduction_amount;
            $data['net_salary'] = $total_earning_salary + $data['car_allowance'] + $data['total_leave_discrepancies_approve_salary'];

            MonthlySalaryReport::create([
                'employee_id' => $employee->id,
                'month_year' => $data['month_year'],
                'actual_salary' =>  $data['total_actual_salary'],
                'car_allowance' =>  $data['car_allowance'],
                'earning_salary' =>  $data['total_earning_salary'],
                'approved_days_amount' =>  $data['total_leave_discrepancies_approve_salary'],
                'deduction' =>  $all_absent_days_amount, //deduction
                'net_salary' =>  $data['net_salary'],
                'generated_date' =>  date('Y-m-d'),
            ]);
        }
        return 'done';
    }

    public function emailTemplate(){
        return 'Already Done';
        // $mailData = [
        //             'from' => 'birthday',
        //             'title' => 'Birthday Greeting',
        //             'name' => 'User Name',
        //         ];
        // return view('emails.birthday', compact('mailData'));

        // $employee_info = [
        //     'name' => 'User name',
        //     'email' => 'user@email',
        //     'password' => 'user@123',
        //     'manager' => 'Manager Name',
        //     'designation' => 'Designation Name',
        //     'department' => 'Department Name',
        //     'shift_time' => 'Shift Time',
        //     'joining_date' => 'joining Date',
        // ];

        // $mailData = [
        //     'from' => 'employee_info',
        //     'title' => 'Employee Approval and Joining Information',
        //     'employee_info' => $employee_info,
        // ];

        // return view('emails.employee-info', compact('mailData'));

        // $mailData = [
        //     'from' => 'termination',
        //     'title' => 'Employee Termination Notification',
        //     'employee' => 'User Name',
        // ];

        // return view('emails.temination', compact('mailData'));

        // $employee_info = [
        //     'name' => 'User Name',
        //     'email' => 'user@email',
        //     'password' => 'user@123',
        // ];

        // $mailData = [
        //     'from' => 'welcome',
        //     'title' => 'Welcome to Our Team - Important Onboarding Information',
        //     'employee_info' => $employee_info,
        // ];

        // return view('emails.welcome', compact('mailData'));

        // $mailData = [
        //     'from' => 'forgot-password',
        //     'title' => 'Welcome to Our Team - Important Onboarding Information',
        // ];

        // return view('emails.forgot-password', compact('mailData'));

        // $body = [
        //     'name' => 'Demo Name',
        //     'effective_date' => 'date',
        //     'current_salary' => 123,
        //     'increased_salary' => 123,
        //     'updated_salary' => 123,
        // ];

        // $mailData = [
        //     'from' => 'salary_increments',
        //     'title' => 'Promotion',
        //     'body' => $body,
        // ];

        // return view('emails.email', compact('mailData'));
    }

    public function sendEmail(){
        // return 'Already Done';

        $mailData = [
            'from' => 'birthday',
            'title' => 'Birthday Greeting',
            'name' => 'Amar Chand',
        ];

        Mail::to('amarTest@yopmail.com')->send(new Email($mailData));

        return 'done';

        $users = User::get();
        foreach($users as $user){
            $role = $user->getRoleNames()->first();
            foreach($user->getRoleNames() as $user_role){
                if($user_role=='Admin'){
                    $role = $user_role;
                }elseif($user_role=='Department Manager'){
                    $role = $user_role;
                }
            }

            $team_members = [];
            if($role=='Admin'){
                $departs = Department::where('manager_id', $user->id)->get();
                $depart_ids = [];
                foreach($departs as $depart){
                    if(!empty($depart)){
                        $depart_ids[] = $depart->id;
                    }
                }

                $team_employees = DepartmentUser::whereIn('department_id', $depart_ids)->get();
                foreach($team_employees as $team_employee){
                    $dep_user = User::where('id', $team_employee->user_id)->where('status', 1)->where('is_employee')->first();
                    if(!empty($dep_user)){
                        $team_members[] = $dep_user->email;
                    }
                }
            }
            if($role == 'Department Manager'){
                if(isset($user->departmentBridge->department) && !empty($user->departmentBridge->department->id)) {
                    $user_department = $user->departmentBridge->department;
                }

                $dept_ids = [];
                if(isset($user_department) && !empty($user_department)){
                    $sub_dep = Department::where('parent_department_id', $user_department->id)->where('manager_id', $user->id)->first();
                    if(!empty($sub_dep)){
                        $dept_ids[] = $sub_dep->id;
                        $dept_ids[] = $sub_dep->parent_department_id;
                        $sub_deps = Department::where('parent_department_id', $sub_dep->id)->get();
                        if(!empty($sub_deps)){
                            foreach($sub_deps as $sub_department){
                                $dept_ids[] = $sub_department->id;
                            }
                        }
                    }else{
                        $sub_deps = Department::where('manager_id', $user->id)->get();
                        $dept_ids[] = $user_department->manager_id;
                        if(!empty($sub_deps) && count($sub_deps)){
                            foreach($sub_deps as $sub_dept){
                                $dept_ids[] = $sub_dept->id;
                            }
                        }
                    }

                    $team_employees = DepartmentUser::whereIn('department_id', $dept_ids)->get();
                    if(!empty($sub_dep->parentDepartment->manager_id)){
                        $team_employees[] = (object)['user_id' => $sub_dep->parentDepartment->manager_id];
                    }

                    foreach($team_employees as $team_employee){
                        $dep_user = User::where('id', $team_employee->user_id)->where('status', 1)->where('is_employee', 1)->first();
                        if(!empty($dep_user)){
                            $team_members[] = $dep_user->email;
                        }
                    }
                }
            }elseif($role=='Employee'){
                if(isset($user->departmentBridge->department) && !empty($user->departmentBridge->department->id)) {
                    $user_department = $user->departmentBridge->department;
                }

                $team_member_ids = [];
                $parent_dept_teams = [];
                if(isset($user_department) && !empty($user_department)){
                    $parent_departments = Department::where('parent_department_id', $user_department->parent_department_id)->where('status', 1)->get();
                    if(!empty($parent_departments)){
                        $parent_dept_ids = [];
                        $team_members[] = $user_department->parentDepartment->manager->email;
                        foreach($parent_departments as $parent_dept){
                            $parent_dept_ids[] = $parent_dept->parent_department_id;
                            $parent_dept_ids[] = $parent_dept->id;
                        }
                        $parent_dept_teams = DepartmentUser::whereIn('department_id', $parent_dept_ids)->where('end_date', null)->get(['user_id']);
                    }else{
                        $team_members[] = $user_department->manager->email;
                    }
                    $team_member_ids = DepartmentUser::where('department_id', $user_department->id)->where('user_id', '!=', $user->id)->where('end_date', null)->get(['user_id']);
                }

                $team_member_ids = collect($parent_dept_teams)->merge($team_member_ids)->all();

                if(sizeof($team_member_ids) > 0) {
                    foreach($team_member_ids as $team_member_id) {
                        $dep_user = User::where('id', $team_member_id->user_id)->where('status', 1)->where('is_employee', 1)->first();
                        if(!empty($dep_user)){
                            $team_members[] = $dep_user->email;
                        }
                    }
                }
            }

            $mailData = [
                    'from' => 'birthday',
                    'title' => 'Birthday Greeting',
                    'name' => $user->first_name .' '. $user->last_name,
                ];

            Mail::to($user->email)->cc($team_members)->send(new Email($mailData));
        }

        // return 'done';
    }

    public function changePasswordAllEmployees(){
        return 'Already changed all employees password';
        $employees = User::where('status', 1)->where('is_employee', 1)->get();
        foreach($employees as $employee){
            $user_password = 'demo@2023';
            $employee->password = Hash::make($user_password);
            $employee->save();
        }

        return 'done';
    }

    public function sendEmailTermination(){
        return 'Already done';
        $model = User::where('id', 36)->first();

        $mailData = [
            'from' => 'termination',
            'title' => 'Employee Termination Notification',
            'employee' => $model->first_name.' '.$model->last_name,
        ];

        $to_emails = ['amar.chand@demo.org', 'muhammad.umer@demo.org'];
        Mail::to($to_emails)->send(new Email($mailData));

        return 'done';
    }

    public function birthday(){
        return 'tested...';
        $today = now();

        // $users = Profile::whereMonth('date_of_birth', $today->month)
        //     ->whereDay('date_of_birth', $today->day)
        //     ->get();

        $users = User::whereHas('profile', function($query) use ($today) {
            $query->whereMonth('date_of_birth', $today->month)
                  ->whereDay('date_of_birth', $today->day);
        })->get();

        // $user = Profile::where('user_id', 36)->first();

        // $mailData = [
        //     'from' => 'birthday',
        //     'title' => 'Birthday Greeting',
        //     'name' => $user->first_name .' '. $user->last_name,
        // ];

        // Mail::to('amar725@yopmail.com')->send(new Email($mailData));

        // return 'sent...';

        foreach($users as $user){
            $team_members = [];
            if($user->hasRole('Admin')){
                $departs = Department::where('manager_id', $user->id)->get();
                $depart_ids = [];
                foreach($departs as $depart){
                    if(!empty($depart)){
                        $depart_ids[] = $depart->id;
                    }
                }

                $team_employees = DepartmentUser::whereIn('department_id', $depart_ids)->get();
                foreach($team_employees as $team_employee){
                    $dep_user = User::where('id', $team_employee->user_id)->where('status', 1)->where('is_employee')->first();
                    if(!empty($dep_user)){
                        $team_members[] = $dep_user->email;
                    }
                }
            }
            if($user->hasRole('Department Manager')){
                if(isset($user->departmentBridge->department) && !empty($user->departmentBridge->department->id)) {
                    $user_department = $user->departmentBridge->department;
                }

                $dept_ids = [];
                if(isset($user_department) && !empty($user_department)){
                    $sub_dep = Department::where('parent_department_id', $user_department->id)->where('manager_id', $user->id)->first();
                    if(!empty($sub_dep)){
                        $dept_ids[] = $sub_dep->id;
                        $dept_ids[] = $sub_dep->parent_department_id;
                        $sub_deps = Department::where('parent_department_id', $sub_dep->id)->get();
                        if(!empty($sub_deps)){
                            foreach($sub_deps as $sub_department){
                                $dept_ids[] = $sub_department->id;
                            }
                        }
                    }else{
                        $sub_deps = Department::where('manager_id', $user->id)->get();
                        $dept_ids[] = $user_department->manager_id;
                        if(!empty($sub_deps) && count($sub_deps)){
                            foreach($sub_deps as $sub_dept){
                                $dept_ids[] = $sub_dept->id;
                            }
                        }
                    }

                    $team_employees = DepartmentUser::whereIn('department_id', $dept_ids)->get();
                    if(!empty($sub_dep->parentDepartment->manager_id)){
                        $team_employees[] = (object)['user_id' => $sub_dep->parentDepartment->manager_id];
                    }

                    foreach($team_employees as $team_employee){
                        $dep_user = User::where('id', $team_employee->user_id)->where('status', 1)->where('is_employee', 1)->first();
                        if(!empty($dep_user)){
                            $team_members[] = $dep_user->email;
                        }
                    }
                }
            }elseif($user->hasRole('Employee')){
                if(isset($user->departmentBridge->department) && !empty($user->departmentBridge->department->id)) {
                    $user_department = $user->departmentBridge->department;
                }

                $team_member_ids = [];
                $parent_dept_teams = [];
                if(isset($user_department) && !empty($user_department)){
                    $parent_departments = Department::where('parent_department_id', $user_department->parent_department_id)->where('status', 1)->get();
                    if(!empty($parent_departments)){
                        $parent_dept_ids = [];
                        $team_members[] = $user_department->parentDepartment->manager->email;
                        foreach($parent_departments as $parent_dept){
                            $parent_dept_ids[] = $parent_dept->parent_department_id;
                            $parent_dept_ids[] = $parent_dept->id;
                        }
                        $parent_dept_teams = DepartmentUser::whereIn('department_id', $parent_dept_ids)->where('end_date', null)->get(['user_id']);
                    }else{
                        $team_members[] = $user_department->manager->email;
                    }
                    $team_member_ids = DepartmentUser::where('department_id', $user_department->id)->where('user_id', '!=', $user->id)->where('end_date', null)->get(['user_id']);
                }

                $team_member_ids = collect($parent_dept_teams)->merge($team_member_ids)->all();

                if(sizeof($team_member_ids) > 0) {
                    foreach($team_member_ids as $team_member_id) {
                        $dep_user = User::where('id', $team_member_id->user_id)->where('status', 1)->where('is_employee', 1)->first();
                        if(!empty($dep_user)){
                            $team_members[] = $dep_user->email;
                        }
                    }
                }
            }

            $mailData = [
                    'from' => 'birthday',
                    'title' => 'Birthday Greeting',
                    'name' => $user->first_name .' '. $user->last_name,
                ];

            // Mail::to($user->email)->cc($team_members)->send(new Email($mailData));
            Mail::to('amar725@yopmail.com')->send(new Email($mailData));
            return 'done';
        }
    }
}
