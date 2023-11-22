<?php

namespace App\Http\Controllers;

use Str;
use Auth;
use DateTime;
use Carbon\Carbon;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\UserLeave;
use App\Models\WorkShift;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Discrepancy;
use Illuminate\Http\Request;
use App\Models\DepartmentUser;
use App\Models\WorkingShiftUser;
use App\Models\EmploymentStatus;
use App\Models\UserEmploymentStatus;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    public function summary($getMonth = null, $getYear = null, $user_slug = null)
    {
        $this->authorize('admin_summary-list');
        $title = 'Attendance Summary';

        $employees = [];
        
        $userWithAdminRole = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin'); // Replace 'admin' with the actual role name.
        })->first();

        $employees = User::where('id', '!=', $userWithAdminRole->id)->where('status', 1)->where('is_employee', 1)->get();

        $currentMonth=date('m/Y');
        if(date('d')>25){
            $currentMonth=date('m/Y',strtotime('first day of +1 month'));
        }
        if(!empty($getMonth) || !empty($user_slug)){
            $year = $getYear;
            $month = $getMonth;

            $user = User::where('slug', $user_slug)->first();
        }else{
            $year = date('Y');
            if(date('d')>26 || (date('d')==26 && date('H')>11)) {
                $month=date('m', strtotime('first day of +1 month'));
            } else {
                $month=date('m');
            }

            $user = User::where('slug', $userWithAdminRole->slug)->first();
        }
        
        $shift = WorkingShiftUser::where('user_id', $user->id)->where('end_date', NULL)->first();
        if(empty($shift)){
            $shift = $user->departmentBridge->department->departmentWorkShift->workShift;
        }else{
            $shift = $shift->workShift;
        }

        //User Leave & Discrepancies Reprt
        $leave_report = hasExceededLeaveLimit($user);
        if($leave_report){
            $leave_in_balance = $leave_report['leaves_in_balance'];
        }else{
            $leave_in_balance = 0;
        }

        $user_have_used_discrepancies = Discrepancy::where('user_id', $user->id)->where('status', '!=', 2)->whereMonth('date', Carbon::now()->month)
        ->whereYear('date', Carbon::now()->year)
        ->count();

        $user_joining_date = date('d-m-Y');
        // if(isset($user->joiningDate->joining_date) && !empty($user->joiningDate->joining_date)){
        //     $user_joining_date = date('m/Y', strtotime($user->joiningDate->joining_date));
        // }
        
        if(isset($user->profile) && !empty($user->profile->joining_date)){
            $user_joining_date = date('m/Y', strtotime($user->profile->joining_date));
        }

        $leave_types = LeaveType::where('status', 1)->get(['id', 'name']);
        
        $user_leave_report = hasExceededLeaveLimit($user);
        $remaining_filable_leaves = $user_leave_report['total_remaining_leaves'];
        
        return view('user.attendance.summary', compact('title','user', 'user_joining_date','shift','month','year', 'currentMonth', 'employees', 'leave_types', 'remaining_filable_leaves'));
    }
    
    public function employeeSummary($getMonth = null, $getYear = null, $user_slug = null)
    {
        $this->authorize('employee_summary-list');
        $title = 'Attendance Summary';
        $logined_user = Auth::user();

        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
            if($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $employees = [];
        $dept_ids = [];

        if($role=='Department Manager'){
            $department = Department::where('manager_id', $logined_user->id)->first();
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
                $user = User::where('id', $department_user->user_id)->first(['id','first_name', 'last_name', 'slug']);
                if(!empty($user)){
                    $emp = User::where('id', $department_user->user_id)->where('status', 1)->where('is_employee', 1)->first(['id', 'first_name', 'last_name', 'slug']);
                    if(!empty($emp)){
                        $employees[] = $emp;
                    }
                }
            }
        }

        $currentMonth=date('m/Y');
        if(date('d')>25){
            $currentMonth=date('m/Y',strtotime('first day of +1 month'));
        }
        if(!empty($getMonth) || !empty($user_slug)){
            $year = $getYear;
            $month = $getMonth;

            $user = User::where('slug', $user_slug)->first();
        }else{
            $year = date('Y');
            if(date('d')>26 || (date('d')==26 && date('H')>11)) {
                $month=date('m', strtotime('first day of +1 month'));
            } else {
                $month=date('m');
            }

            $user = User::where('slug', Auth::user()->slug)->first();
        }
        
        $shift = WorkingShiftUser::where('user_id', $user->id)->where('end_date', NULL)->first();
        if(empty($shift)){
            $shift = $user->departmentBridge->department->departmentWorkShift->workShift;
        }else{
            $shift = $shift->workShift;
        }

        //User Leave & Discrepancies Reprt
        $leave_report = hasExceededLeaveLimit($user);
        if($leave_report){
            $leave_in_balance = $leave_report['leaves_in_balance'];
        }else{
            $leave_in_balance = 0;
        }

        $user_have_used_discrepancies = Discrepancy::where('user_id', $user->id)->where('status', '!=', 2)->whereMonth('date', Carbon::now()->month)
        ->whereYear('date', Carbon::now()->year)
        ->count();
        
        $user_joining_date = date('d-m-Y');
        if(isset($user->joiningDate->joining_date) && !empty($user->joiningDate->joining_date)){
            $user_joining_date = date('m/Y', strtotime($user->joiningDate->joining_date));
        }

        $leave_types = LeaveType::where('status', 1)->get(['id', 'name']);
        
        $user_leave_report = hasExceededLeaveLimit($user);
        $remaining_filable_leaves = $user_leave_report['total_remaining_leaves'];
        
        return view('user.attendance.employee-summary', compact('title','user', 'user_joining_date','shift','month','year', 'currentMonth', 'employees', 'leave_types', 'remaining_filable_leaves'));
    }
    
    public function terminatedEmployeeSummary($getMonth = null, $getYear = null, $user_slug = null)
    {
        $this->authorize('terminated_employee_summary-list');
        $data = [];
        
        $data['title'] = 'Terminated Employee Summary';

        $employees = [];
        $employment_status = EmploymentStatus::where('name', 'Terminated')->first();
        $teminated_employees = UserEmploymentStatus::where('employment_status_id', $employment_status->id)->get();
        
        $terminated_employee_ids = [];
        foreach($teminated_employees as $terminated_employee){
            $terminated_employee_ids[] = $terminated_employee->user_id;
        }
        
        $data['employees'] = User::whereIn('id', $terminated_employee_ids)->get();

        
        $data['currentMonth']=date('m/Y');
        if(date('d')>25){
            $data['currentMonth']=date('m/Y',strtotime('first day of +1 month'));
        }
        
        if(!empty($getMonth) || !empty($user_slug)){
            $data['year'] = $getYear;
            $data['month'] = $getMonth;

            $user = User::where('slug', $user_slug)->first();
            $data['user_slug'] = $user_slug;
            
            // $shift = WorkingShiftUser::where('user_id', $user->id)->where('end_date', NULL)->first();
            
            // if(empty($shift) && isset($user->departmentBridge->department->departmentWorkShift->workShift) && !empty($user->departmentBridge->department->departmentWorkShift->workShift)){
            //     $shift = $user->departmentBridge->department->departmentWorkShift->workShift;
            // }else{
            //     if(!empty($shift->workShift)){
            //         $shift = $shift->workShift;
            //     }else{
            //         $shift = WorkShift::where('name', 'Night Shift (9 to 6)')->first();
            //     }
            // }
            
            $shift = WorkingShiftUser::where('user_id', $user->id)->orderby('id', 'desc')->first();
            if(empty($shift)){
                $shift = WorkShift::where('name', 'Night Shift (9 to 6)')->first();
            }else{
                $shift = $shift->workShift;
            }
            if(!empty($user->employeeStatus->end_date) ){
                $data['currentMonth']=date('m/Y', strtotime($user->employeeStatus->end_date));
            }
    
            $data['shift'] = $shift;
            
            //User Leave & Discrepancies Reprt
            $leave_report = hasExceededLeaveLimit($user);
            if($leave_report){
                $leave_in_balance = $leave_report['leaves_in_balance'];
            }else{
                $leave_in_balance = 0;
            }
            
            $data['leave_in_balance'] = $leave_in_balance;
    
            $user_have_used_discrepancies = Discrepancy::where('user_id', $user->id)->where('status', '!=', 2)->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->count();
    
            $remaining_filable_discrepancies = settings()->max_discrepancies - $user_have_used_discrepancies;
            if($remaining_filable_discrepancies > 0){
                $remaining_filable_discrepancies = $remaining_filable_discrepancies;
            }else{
                $remaining_filable_discrepancies = 0;
            }
            $data['remaining_filable_discrepancies'] = $remaining_filable_discrepancies;
            $data['remaining_filable_leaves'] = $leave_in_balance;
            //User Leave & Discrepancies Reprt
            
            $user_joining_date = date('d-m-Y');
            if(isset($user->joiningDate->joining_date) && !empty($user->joiningDate->joining_date)){
                $user_joining_date = date('m/Y', strtotime($user->joiningDate->joining_date));
            }
            
            $data['user_joining_date'] = $user_joining_date;
    
            $data['leave_types'] = LeaveType::where('status', 1)->get(['id', 'name']);
            $data['user'] = $user;
        }else{
            // $data['year'] = date('Y');
            // if(date('d')>26 || (date('d')==26 && date('H')>11)) {
            //     $data['month']=date('m', strtotime('first day of +1 month'));
            // } else {
            //     $data['month']=date('m');
            // }
            
            $data['user'] = Auth::user();
        }
        
        return view('user.attendance.terminated_emp_summary', compact('data'));
    }

    public function advanceFilterSummary(Request $request){
        $this->authorize('admin_attendance_filter-list');
        $title = 'Attendance Summary';
        $data = [];
        
        $userWithAdminRole = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin'); // Replace 'admin' with the actual role name.
        })->first();

        $employees = [];
        $departments = [];
        $department_id = '';

        $department = Department::where('manager_id', $userWithAdminRole->id)->first();
        if(isset($department) && !empty($department->id)){
            $department_id = $department->id;
        }
        $employees = User::where('id', '!=', $userWithAdminRole->id)->where('status', 1)->where('is_employee', 1)->get();
        $departments = Department::where('status', 1)->latest()->get();

        if($request->ajax()){
            $users = [];
            
            $filter_date = explode('to', $request->filter_date);
            $from_date = $filter_date[0];
            if(isset($filter_date[1])){
                $to_date = $filter_date[1];
            }else{
                $to_date = $filter_date[0];
            }

            if(isset($request->filter_behavior) && !empty($request->filter_behavior)){
                $behavior = $request->filter_behavior;
            }
            
            $all_employee_ids = [];
            $filter_employees = json_decode($request['employees']);
            if(!empty($filter_employees) && count($filter_employees) > 0){
                if($filter_employees[0]=='All'){
                    foreach($employees as $employee){
                        $all_employee_ids[] = $employee->id;
                    }
                }else{
                    $all_employee_ids = $filter_employees;
                }
            }
            
            $all_department_ids = [];
            $filter_departments = json_decode($request['departments']);
            if(!empty($filter_departments) && count($filter_departments) > 0){
                if($filter_departments[0]=='All'){
                    foreach($departments as $department){
                        $all_department_ids[] = $department->id;
                    }
                }else{
                    $all_department_ids = $filter_departments;
                }
            }
            
            $employees = $all_employee_ids;
            if(!empty($all_department_ids) && count($all_department_ids) > 0){
                if(!empty($all_employee_ids)){
                    $department_users = DepartmentUser::whereIn('department_id', $all_department_ids)->whereIn('user_id', $all_employee_ids)->get();    
                }else{
                    $department_users = DepartmentUser::whereIn('department_id', $all_department_ids)->get();    
                }
                
                foreach($department_users as $department_user){
                    $dep_user = User::where('id', $department_user->user_id)->where('status', 1)->where('is_employee', 1)->first();
                    if(!empty($dep_user)){
                        $users[] = $dep_user;
                    }
                }
            }else{
                $users = User::whereIn('id', $employees)->where('status', 1)->where('is_employee', 1)->get();
            }

            $data['from_date'] = date('Y-m-d', strtotime($from_date));
            $data['to_date'] = date('Y-m-d', strtotime($to_date));
            $data['behavior'] = $behavior;
            $data['users'] = $users;

            return (string) view('user.attendance.filter-summary-content', compact('data'));
        }

        $user = User::where('slug', $userWithAdminRole->slug)->first();
        $data['employees'] = $employees;

        return view('user.attendance.filter-summary', compact('title','user', 'data', 'departments', 'department_id'));
    }
    
    public function employteeAdvanceFilterSummary(Request $request){
        $this->authorize('employee_attendance_filter-list');
        $title = 'Attendance Summary';
        $data = [];
        $logined_user = Auth::user();

        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
            if($user_role=='Department Manager' || $user_role=='Manager'){
                $role = $user_role;
            }
        }

        $employees = [];
        $departments = [];
        $department_id = '';
        $dept_ids = [];

        if($role=='Department Manager'){
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

            $department_users = DepartmentUser::orderby('id', 'desc')->whereIn('department_id',  $dept_ids)->where('end_date', null)->get();
            foreach($department_users as $department_user){
                $user = User::where('id', $department_user->user_id)->first(['id','first_name', 'last_name', 'slug']);
                if(!empty($user)){
                    $dep_user = User::where('id', $department_user->user_id)->where('status', 1)->where('is_employee', 1)->first(['id', 'first_name', 'last_name', 'slug']);
                    if(!empty($dep_user)){
                        $employees[] = $dep_user;
                    }
                }
            }
            
            $departments = Department::whereIn('id', $dept_ids)->where('status', 1)->latest()->get();
        }

        if($request->ajax()){
            $users = [];
            
            $filter_date = explode('to', $request->filter_date);
            $from_date = $filter_date[0];
            if(isset($filter_date[1])){
                $to_date = $filter_date[1];
            }else{
                $to_date = $filter_date[0];
            }

            if(isset($request->filter_behavior) && !empty($request->filter_behavior)){
                $behavior = $request->filter_behavior;
            }

            $all_employee_ids = [];
            $filter_employees = json_decode($request['employees']);
            if(!empty($request['employees']) && count($filter_employees) > 0){
                if($filter_employees[0]=='All'){
                    foreach($employees as $employee){
                        $all_employee_ids[] = $employee->id;
                    }
                }else{
                    $all_employee_ids = $filter_employees;
                }
            }
            
            $all_department_ids = [];
            $filter_departments = json_decode($request['departments']);
            if(!empty($request['departments']) && count($filter_departments) > 0){
                if($filter_departments[0]=='All'){
                    foreach($departments as $department){
                        $all_department_ids[] = $department->id;
                    }
                }else{
                    $all_department_ids = $filter_departments;
                }
            }

            $employees = $all_employee_ids;
            if(isset($request['departments']) && !empty($all_department_ids)){
                $query = DepartmentUser::orderby('id', 'desc')->where('id', '>', 0);
                    $query->whereIn('department_id', $all_department_ids);
                    if(!empty($employees) && count($employees) > 0){
                        $query->whereIn('user_id', $employees);
                    }
                $department_users = $query->get();
                
                // $department_users = DepartmentUser::whereIn('department_id', $all_department_ids)->get();
                foreach($department_users as $department_user){
                    $dep_user = User::where('id', $department_user->user_id)->where('status', 1)->where('is_employee', 1)->first();
                    if(!empty($dep_user)){
                        $users[] = $dep_user;
                    }
                }
            }else{
                $users = User::whereIn('id', $employees)->where('status', 1)->where('is_employee', 1)->get();
            }

            $data['from_date'] = date('Y-m-d', strtotime($from_date));
            $data['to_date'] = date('Y-m-d', strtotime($to_date));
            $data['behavior'] = $behavior;
            $data['users'] = $users;

            return (string) view('user.attendance.filter-summary-content', compact('data'));
        }

        $user = User::where('slug', Auth::user()->slug)->first();
        $data['employees'] = $employees;

        return view('user.attendance.employee-filter-summary', compact('title','user', 'data', 'departments', 'department_id'));
    }

    public function getTeamSummary()
    {
        $title = 'Attendance Summary';
        $year = date('Y');
        if(date('d')>26 || (date('d')==26 && date('H')>11)){
          $month=date('m',strtotime('first day of +1 month'));
        }else{
          $month=date('m');
        }
        $user = User::where('id', auth()->user()->id)->first();

        $shift = WorkingShiftUser::where('user_id', auth()->user()->id)->where('start_date', '<=', today()->format('Y-m-d'))->orderBy('id', 'desc')->first();
        if(empty($shift)){
            $shift = $user->departmentBridge->department->departmentWorkShift->workShift->id;
        }else{
            $shift = $shift->working_shift_id;
        }
        return view('user.attendance.summary', compact('title','user','shift','month','year'));
    }

    public static function getAttandanceCount($userID,$start_date,$end_date,$status,$shiftID)
    {
        $begin = new DateTime($start_date);
        $end   = new DateTime($end_date);
        $totalDays=0;
        $workDays=0;
        $lateIn=0;
        $lateInDates=[];
        $earlyOut=0;
        $earlyOutDates=[];
        $halfDay=0;
        $halfDayDates=[];
        $absent=0;
        $absent_dates = [];
        $discrepancy_late=0;
        $discrepancy_early=0;
        $leave_first_half=0;
        $leave_last_half=0;
        $leave_single=0;
        
        $user = User::where('id', $userID)->first();
        for($i = $begin; $i <= $end; $i->modify('+1 day')){
            $next=date("Y-m-d", strtotime('+1 day '.$i->format("Y-m-d")));
            
            $day=date("D", strtotime($i->format("Y-m-d")));
            if($day!='Sat' && $day!='Sun'){
                $reponse = self::getAttandanceSingleRecord($userID, $i->format("Y-m-d"), $next, 'all', $shiftID);
                
                if($reponse!=null){
                    $attendance_date = $reponse['attendance_date'];
                    if(isset($reponse['attendance_id']) && !empty($reponse['attendance_id'])){
                        $check_att = checkAttendanceByID($reponse['attendance_id']);
                        if(!empty($check_att)){
                            $attendance_date = $check_att;
                        }
                        $attendance_adjustment = attendanceAdjustment($userID, $reponse['attendance_id']);
                    }

                    if($reponse['type']=='absent' || isset($attendance_adjustment) && !empty($attendance_adjustment) && $attendance_adjustment->mark_type=='absent' && $i->format("Y-m-d") <= date('Y-m-d')){
                        $absent++;

                        $applied_date = $reponse['applied_leaves'];
                        $marked_label = '';
                        if(!empty($applied_date) && $applied_date->behavior_type=='absent'){
                            if($applied_date->status==1){
                                $absent--;
                            }
                            $absent_dates[] = [
                                'date' => date('d M, Y', strtotime($i->format("Y-m-d"))),
                                'status' => $applied_date->status,
                                'type' => $applied_date->behavior_type,
                                'applied_at' => $applied_date->created_at,
                                'label' => $marked_label,
                            ];
                        }else{
                            $type = $reponse['type'];
                            $marked_label = '';
                            if(!empty($attendance_adjustment->mark_type)){
                                $type = $attendance_adjustment->mark_type;
                                $marked_label = ' - Marked as Absent';
                            }
                            $absent_dates[] = [
                                'date' => date('d M, Y', strtotime($i->format("Y-m-d"))),
                                'status' => '',
                                'type' => $type,
                                'label' => $marked_label,
                            ];
                        }
                    }
                    if($reponse['type']=='lateIn' || isset($attendance_adjustment) && !empty($attendance_adjustment->mark_type) && $attendance_adjustment->mark_type=='lateIn'){
                        $lateIn++;

                        $applied_date = $reponse['applied_discrepancy'];
                        $marked_label = '';
                        if(!empty($applied_date)){
                            $check_in_out_time = '';
                            if(isset($applied_date->hasAttendance) && !empty($applied_date->hasAttendance)){
                                $check_in_attendance = $applied_date->hasAttendance;

                                if(!empty($check_in_attendance)){
                                    $check_in_out_time = date('h:i A', strtotime($check_in_attendance->in_date));
                                }
                            }

                            if($applied_date->status==1){
                                $lateIn--;
                            }

                            $lateInDates[] = [
                                'attendance_id' => $applied_date->attendance_id,
                                'time' => $check_in_out_time,
                                'date' => date('d M, Y', strtotime($applied_date->date)),
                                'type' => $applied_date->type,
                                'status' => $applied_date->status,
                                'applied_at' => $applied_date->created_at,
                                'label' => $marked_label,
                            ];
                        }
                        else{
                            $type = $reponse['type'];

                            if(!empty($attendance_adjustment->mark_type)){
                                $type = $attendance_adjustment->mark_type;
                                $marked_label = ' - Marked as Late In';
                            }
                            $lateInDates[] = [
                                'attendance_id' => $attendance_date->id,
                                'time' => date('h:i A', strtotime($attendance_date->in_date)),
                                'date' => date('d M, Y', strtotime($attendance_date->in_date)),
                                'behavior' => $attendance_date->behavior,
                                'status' => '',
                                'type' => $type,
                                'label' => $marked_label,
                            ];
                        }
                    }
                    if($reponse['type']=='earlyout'){
                        $earlyOut++;
                        
                        $applied_date = $reponse['applied_discrepancy'];
                        if(!empty($applied_date)){
                            $check_in_out_time = '';
                            if(isset($applied_date->hasAttendance) && !empty($applied_date->hasAttendance)){
                                $check_in_attendance = $applied_date->hasAttendance;

                                if(!empty($check_in_attendance)){
                                    $check_in_out_time = date('h:i A', strtotime($check_in_attendance->in_date));
                                }
                            }
                            
                            if($applied_date->status==1){
                                $earlyOut--;
                            }

                            $earlyOutDates[] = [
                                'attendance_id' => $applied_date->attendance_id,
                                'time' => $check_in_out_time,
                                'date' => date('d M, Y', strtotime($applied_date->date)),
                                'type' => $applied_date->type,
                                'status' => $applied_date->status,
                                'applied_at' => $applied_date->created_at,
                            ];
                        }else{
                            $type = $reponse['type'];
                            if(!empty($attendance_date)){
                                $earlyOutDates[] = [
                                    'attendance_id' => $attendance_date->id,
                                    'time' => date('h:i A', strtotime($attendance_date->in_date)),
                                    'date' => date('d M, Y', strtotime($attendance_date->in_date)),
                                    'behavior' => $attendance_date->behavior,
                                    'status' => '',
                                    'type' => $type,
                                ];
                            }
                        }
                    }

                    if((isset($attendance_adjustment) && !empty($attendance_adjustment->mark_type) &&
                        ($attendance_adjustment->mark_type == 'firsthalf'))){

                        $halfDay++;

                        $halfDayDate = $reponse['applied_leaves'];
                        $marked_label = '';
                        if(!empty($halfDayDate)){
                            if($halfDayDate->status==1){
                                $halfDay--;
                            }
                            $halfDayDates[] = [
                                'date' => date('d M, Y', strtotime($halfDayDate->start_at)),
                                'status' => $halfDayDate->status,
                                'type' => $halfDayDate->behavior_type,
                                'applied_at' => $halfDayDate->created_at,
                                'label' => $marked_label,
                            ];
                        }
                        else{
                            $in_date = '';
                            $behavior = '';
                            $time = '';

                            $type = $reponse['type'];

                            if(!empty($attendance_adjustment->mark_type)){
                                $type = $attendance_adjustment->mark_type;
                                $marked_label = ' - Marked as Half Day';
                            }

                            if(!empty($attendance_date)){
                                $in_date = date('d M, Y', strtotime($attendance_date->in_date));
                                $behavior = $attendance_date->behavior;
                            }
                            $halfDayDates[] = [
                                'date' => $in_date,
                                'behavior' => $behavior,
                                'status' => '',
                                'type' => $type,
                                'label' => $marked_label,
                            ];
                        }
                    }elseif (($reponse['type'] == 'firsthalf' || $reponse['type'] == 'lasthalf') && empty($attendance_adjustment)) {
                        $halfDay++;

                        $halfDayDate = $reponse['applied_leaves'];
                        $marked_label = '';
                        if(!empty($halfDayDate)){
                            if($halfDayDate->status==1){
                                $halfDay--;
                            }
                            $halfDayDates[] = [
                                'date' => date('d M, Y', strtotime($halfDayDate->start_at)),
                                'time' => date('h:i A', strtotime($attendance_date->in_date)),
                                'status' => $halfDayDate->status,
                                'type' => $halfDayDate->behavior_type,
                                'applied_at' => $halfDayDate->created_at,
                                'label' => $marked_label,
                            ];
                        }
                        else{
                            $in_date = '';
                            $behavior = '';
                            $time = '';

                            $type = $reponse['type'];
                            if(!empty($attendance_date)){
                                if(isset($attendance_date->in_date) && !empty($attendance_date->in_date)){
                                    $in_date = date('d M, Y', strtotime($attendance_date->in_date));
                                    $time = date('h:i A', strtotime($attendance_date->in_date));
                                }else{
                                    $in_date = date('d M, Y', strtotime($attendance_date));
                                }
                                $behavior = $type;
                            }
                            $halfDayDates[] = [
                                'date' => $in_date,
                                'time' => $time,
                                'behavior' => $behavior,
                                'status' => '',
                                'type' => $type,
                                'label' => $marked_label,
                            ];
                        }
                    }
                    if($reponse['punchIn']!='-'){
                        $workDays++;
                    }

                    // if($reponse['discrepancy']=='late' && $reponse['discrepancyStatus']==1){
                    //     $discrepancy_late++;
                    // }
                    // if($reponse['discrepancy']=='early' && $reponse['discrepancyStatus']==1){
                    //     $discrepancy_early++;
                    // }
                    // if($reponse['leave']=='first_half' && $reponse['leaveStatus']==1){
                    //     $leave_first_half++;
                    // }
                    // if($reponse['leave']=='last_half' && $reponse['leaveStatus']==1){
                    //     $leave_last_half++;
                    // }
                    // if($reponse['leave']=='single' && $reponse['leaveStatus']==1){
                    //     $leave_single++;
                    // }
                }
                $totalDays++;  
            // }elseif($i->format("Y-m-d") <= date('Y-m-d') && isset($user->employeeStatus->employmentStatus) && $user->employeeStatus->employmentStatus->name=='Permanent' && $user->employeeStatus->employmentStatus->start_date > date('Y-m-d')){
            }elseif($i->format("Y-m-d") <= date('Y-m-d') && isset($user->employeeStatus->employmentStatus) && $user->employeeStatus->employmentStatus->name=='Permanent'){
                if($day=='Sat'){
                        $date = Carbon::createFromFormat('Y-m-d', $i->format("Y-m-d"));
                        $nextDate = $date->copy()->addDays(2);
                        $secondNextDate = $nextDate->copy()->addDay();
                        $previousDate = $date->copy()->subDay();
                }elseif($day=='Sun'){
                    $date = Carbon::createFromFormat('Y-m-d', $i->format("Y-m-d"));
                    $nextDate = $date->copy()->addDay();
                    $secondNextDate = $nextDate->copy()->addDay();
                    
                    $previousDate = $date->copy()->subDays(2);
                }
                if(checkAttendance($userID, date('Y-m-d', strtotime($nextDate)), date('Y-m-d', strtotime($secondNextDate)), $shiftID) && checkAttendance($userID, date('Y-m-d', strtotime($previousDate)), $i->format("Y-m-d"), $shiftID)){
                    $absent++;
                    $applied_date = UserLeave::where('behavior_type', 'absent')->where('start_at', date('Y-m-d', strtotime($date)))->where('user_id', $userID)->first();
                    
                    $marked_label = '';
                    if(!empty($applied_date) && $applied_date->behavior_type=='absent'){
                        if($applied_date->status==1){
                            $absent--;
                        }
                        $absent_dates[] = [
                            'date' => date('d M, Y', strtotime($i->format("Y-m-d"))),
                            'status' => $applied_date->status,
                            'type' => $applied_date->behavior_type,
                            'applied_at' => $applied_date->created_at,
                            'label' => $marked_label,
                        ];
                    }else{
                        $type = 'absent';
                        $absent_dates[] = [
                            'date' => date('d M, Y', strtotime($i->format("Y-m-d"))),
                            'status' => '',
                            'type' => $type,
                            'label' => $marked_label,
                        ];
                    }
                }
            }
       }

        $data = array(
            'totalDays' => $totalDays,
            'workDays' => $workDays,
            'lateIn' => $lateIn,
            'lateInDates' => $lateInDates,
            'earlyOut' => $earlyOut,
            'earlyOutDates' => $earlyOutDates,
            'halfDay' => $halfDay,
            'halfDayDates' => $halfDayDates,
            'absent' => $absent,
            'absent_dates' => $absent_dates,
            'discrepancy_late' => $discrepancy_late,
            'discrepancy_early' => $discrepancy_early,
            'leave_first_half' => $leave_first_half,
            'leave_last_half' => $leave_last_half,
            'leave_single' => $leave_single
        );

        return $data;
    }

    public static function getAttandanceSingleRecord($userID,$current_date,$next_date,$status,$shift)
    {
        $user = User::where('id',$userID)->first();

        if($shift->type=='scheduled'){
            $scheduled='(Flexible)';
        }else{
            $scheduled='';
        }
        $shiftTiming = date("h:i A", strtotime($shift->start_time)).' - '.date("h:i A", strtotime($shift->end_time)).$scheduled;
        
        $start_time = date("Y-m-d H:i:s", strtotime($current_date.' '.$shift->start_time));
        
        $end_time = date("Y-m-d H:i:s", strtotime($next_date.' '.$shift->end_time));
        
        $shift_start_time = date("Y-m-d h:i A", strtotime('+16 minutes '.$start_time));
        
        $shift_end_time = date("Y-m-d h:i A", strtotime('-16 minutes '.$end_time));
        
        $shift_start_halfday = date("Y-m-d h:i A", strtotime('+121 minutes '.$start_time));
        $shift_end_halfday = date("Y-m-d h:i A", strtotime('-121 minutes '.$end_time));
        
        $start = date("Y-m-d H:i:s", strtotime('-6 hours '.$start_time));
        $end = date("Y-m-d H:i:s", strtotime('+6 hours '.$end_time));

        $punchIn = Attendance::where('user_id',$userID)->whereBetween('in_date',[$start, $end])->where('behavior','I')->orderBy('in_date', 'asc')->first();
        $punchOut = Attendance::where('user_id',$userID)->whereBetween('in_date',[$start, $end])->where('behavior','O')->orderBy('in_date', 'desc')->first();

        $label='-';
        $type='';
        $workingHours='-';
        $workingMinutes=0;
        $checkSecond=true;
        $attendance_id = '';
        
        if($punchIn!=null){
            $attendance_id = $punchIn->id;
            $punchInRecord=new DateTime($punchIn->in_date);
            $checkIn=$punchInRecord->format('h:i A');
            $label='<span class="badge bg-label-success">Regular</span>';
            $type='regular';
            
            if(strtotime($punchIn->in_date) > strtotime($shift_start_time) && strtotime($punchIn->in_date) < strtotime($shift_start_halfday)){
                $label='<span class="badge bg-label-warning"><i class="far fa-dot-circle text-warning"></i> Late In</span>';
                $type='lateIn';
                $checkSecond = false;
            }elseif(strtotime($punchIn->in_date) > strtotime($shift_start_halfday) && strtotime($punchIn->in_date) < strtotime($shift_end_halfday)){
                $label='<span class="badge bg-label-danger"><i class="far fa-dot-circle text-danger"></i> Half-Day</span>';
                $type='firsthalf';
                $checkSecond=false;
            }
        }else{      
            $checkIn='-';
        }
        
        if($punchOut!=null){
            if($punchIn==null){
                $attendance_id = $punchOut->id;
            }
            $punchOutRecord=new DateTime($punchOut->in_date);
            $checkOut=$punchOutRecord->format('h:i A');
            
            if($checkSecond && (strtotime($punchOut->in_date) < strtotime($shift_end_time) && strtotime($punchOut->in_date) > strtotime($shift_end_halfday))){
                $label='<span class="badge bg-label-warning"><i class="far fa-dot-circle text-warning"></i> Early Out</span>';
                $type='earlyout';
            }else if($checkSecond && strtotime($punchOut->in_date) < strtotime($shift_end_halfday)){
                $label='<span class="badge bg-label-danger"><i class="far fa-dot-circle text-danger"></i> Half-Day</span>';
                $type='lasthalf';
            }
        }else{
            $checkOut='-';
        }

        if($punchIn!=null && $punchOut!=null){
            $h1 = new DateTime($punchIn->in_date);
            $h2 = new DateTime($punchOut->in_date);
            $diff = $h2->diff($h1);
            $workingHours=$diff->format('%H:%I');
            $workingMinutes=$diff->h*60+$diff->i;
        }

        if($punchIn!=null && $punchOut==null){
            $checkOut='Not Yet';
        }
        
        $current_time = date("H:i:s");
        $date_comparsion = '';
        if (strtotime($current_time) > strtotime("00:00:00") && strtotime($current_time) <= strtotime("01:00:00")) {
            $date_comparsion = $current_date < date('Y-m-d');
        } else {
            $date_comparsion = $current_date <= date('Y-m-d');
        }
        
        if(($punchIn==null && $punchOut==null) && date('Y-m-d h:i A') > $shift_start_time && $date_comparsion){
            $label='<span class="badge bg-label-danger"><i class="far fa-dot-circle text-danger"></i> Absent</span>';
            $type='absent';
            $attendance_date = $current_date;
            $checkIn = '-';
        }

        $discrepancy='';
        $discrepancyStatus='';
        $applied_discrepancy = '';
        $attendance_date = '';
        
        if($type=='lateIn' || $type=='earlyout' && !empty($punchIn)){
            $discrepancy_record = Discrepancy::where('attendance_id', $punchIn->id)->where('user_id',$userID)->first();
            if(!empty($discrepancy_record)){
               $discrepancy=$discrepancy_record->type;
               $discrepancyStatus=$discrepancy_record->status;
               $applied_discrepancy = $discrepancy_record;
            }else{
                $attendance_date = $punchIn;
                $attendance_id = $attendance_date->id;
            }
        }

        $leave='';
        $applied_leaves='';
        $leaveStatus='';
        $punch_date = '';

        if($type=='absent'){
            $punch_date = date('Y-m-d', strtotime($current_date));
        }else if($type=='firsthalf'){
            $punch_date = date('Y-m-d', strtotime($current_date));
        }else if($type=='lasthalf'){
            $punch_date = date('Y-m-d', strtotime($current_date));
        }
        
        if(isset($punch_date) && $punch_date != ''){
            $leaves = UserLeave::where('behavior_type', $type)->where('start_at', $punch_date)->where('user_id', $userID)->first();
            if(!empty($leaves)){
               $leave=$leaves->behavior_type;
               $leaveStatus=$leaves->status;
               $applied_leaves = $leaves;
            }else{
                if($type=='absent'){
                    $attendance_date = $current_date;
                }elseif($type=="lasthalf" && $punchIn==''){
                    $attendance_date = $current_date;
                }elseif($type=="lasthalf" && $punchIn!=''){
                    $attendance_date = $punchIn;
                    $attendance_id = $attendance_date->id;
                }elseif($type=="firsthalf" && $punchIn==''){
                    $attendance_date = $current_date;
                }elseif($type=="firsthalf" && $punchIn!=''){
                    // $attendance_date = $punchIn;
                    // $attendance_id = $attendance_date->id;
                    $attendance_date = $current_date;
                }
            }
        }

        if($type=='regular'){
            $attendance_date = $punchIn;
            $attendance_id = $attendance_date->id;
        }
        
        $data = array(
            'punchIn' => $checkIn,
            'punchOut' => $checkOut,
            'label' => $label,
            'type' => $type,
            'shiftTiming' => $shiftTiming,
            'shiftType' => $shift->type,
            'workingHours' => $workingHours,
            'workingMinutes' => $workingMinutes,
            'discrepancy' => $discrepancy,
            'discrepancyStatus' => $discrepancyStatus,
            'applied_discrepancy' => $applied_discrepancy,
            'leave' => $leave,
            'leaveStatus' => $leaveStatus,
            'applied_leaves' => $applied_leaves,
            'attendance_date' => $attendance_date,
            'attendance_id' => $attendance_id,
            'user' => $user
        );

        if($status=='all'){
            return $data;
        }elseif($status=='regular' && $type=='regular'){
            return $data;
        }elseif($status=='absent' && $type=='absent'){
            return $data;
        }elseif($status=='lateIn' && $type=='lateIn'){
            return $data;
        }elseif($status=='earlyout' && $type=='earlyout'){
            return $data;
        }elseif($status=='halfday' && ($type=='firsthalf' || $type=='lasthalf')){
            return $data;
        }else{
            return null;
        }
    }

    public static function getAppliedLeave($leave_date, $user_id){
        return UserLeave::where('user_id', $user_id)->where('start_at', '>=', $leave_date)->where('end_at', '<=', date('Y-m-d'))->first();
    }
    public static function getAppliedDiscrepancy($applied_date, $user_id){
        return Discrepancy::where('user_id', $user_id)->where('date', $applied_date)->first();
    }

    public function discrepancies(Request $request,  $getMonth = null, $getYear = null, $user_slug = null)
    {
        $this->authorize('employee_discrepancies-list');
        $title = 'Discrepancies';
        $user = Auth::user();

        // $model = Discrepancy::orderby('id','desc')->where('user_id', $user->id)->get();
        $model = [];
        Discrepancy::where('user_id', $user->id)
            ->latest()
            ->chunk(100, function ($discrepancies) use (&$model) {
                foreach ($discrepancies as $discrepancy) {
                    $model[] = $discrepancy;
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
                    }

                    return $label;
                })
                ->editColumn('is_additional', function ($model) {
                    $label = '';

                    switch ($model->is_additional) {
                        case 1:
                            $label = '<span class="badge bg-label-danger" text-capitalized="">Additional</span>';
                            break;
                        case 0:
                            $label = '-';
                            break;
                    }

                    return $label;
                })
                ->editColumn('date', function ($model) {
                    return Carbon::parse($model->date)->format('d, M Y');
                })
                ->editColumn('type', function ($model) {
                    $label = '-';
                    if($model->type=='early'){
                        $label = '<span class="badge bg-label-warning"> Early Out</span>';
                    }elseif($model->type=='late'){
                        $label = '<span class="badge bg-label-info">Late In</span>';
                    }
                    
                    return $label;
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('user_id', function ($model) {
                    return view('user.attendance.employee-profile', ['model' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('user.attendance.discrepancy-action', ['model' => $model])->render();
                })
                ->rawColumns(['user_id', 'status', 'type', 'is_additional', 'action'])
                ->make(true);
        }

        return view('user.attendance.discrepancies', compact('title', 'user'));
    }

    public function teamDiscrepancies(Request $request, $user_slug = null){
        $this->authorize('team_discrepancies-list');
        $title = 'Team Discrepancies';
        
        $userWithAdminRole = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin'); // Replace 'admin' with the actual role name.
        })->first();
        
        $logined_user = $userWithAdminRole;
        
        $employees = [];
        $employees_ids = [];
        $departments = [];
        $url = '';

        $department_users = DepartmentUser::where('end_date',  null)->get();
        foreach($department_users as $department_user){
            $emp_data = User::where('id', $department_user->user_id)->where('id', '!=', $userWithAdminRole->id)->where('status', 1)->where('is_employee', 1)->first(['id', 'first_name', 'last_name', 'slug']);
            if(!empty($emp_data)){
                $employees[] = $emp_data;
                $employees_ids[] = $department_user->user_id;
            }
        }

        $departments = Department::where('status', 1)->get();
        
        if(!empty($user_slug) && $user_slug != 'All') {
            $user = User::where('slug', $user_slug)->first();
            $url = URL::to('team/discrepancies/'.$user_slug);
            
            // $model = Discrepancy::where('user_id', $user->id)->latest()->get();
            $model = [];
            Discrepancy::where('user_id', $user->id)
                ->latest()
                ->chunk(100, function ($discrepancies) use (&$model) {
                    foreach ($discrepancies as $discrepancy) {
                        $model[] = $discrepancy;
                    }
            });
        }else{
            $user = User::where('slug', $logined_user->slug)->first();
            // $model = Discrepancy::orderby('status', 'asc')->whereIn('user_id', $employees_ids)->get();
            $model = [];
            Discrepancy::whereIn('user_id', $employees_ids)
                ->latest()
                ->chunk(100, function ($discrepancies) use (&$model) {
                    foreach ($discrepancies as $discrepancy) {
                        $model[] = $discrepancy;
                    }
            });
        }

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->addColumn('select', function ($model) {
                    return view('user.attendance.discrepancy_check', ['model' => $model])->render();
                })
                ->editColumn('status', function ($model) {
                   if($model->status==1){
                        return '<span class="badge bg-label-success" text-capitalized="">Approved</span>';
                    }elseif($model->status==2){
                        return '<span class="badge bg-label-danger" text-capitalized="">Rejected</span>';
                    }else{
                        return '<span class="badge bg-label-warning" text-capitalized="">Pending</span>';
                    }
                })
                ->editColumn('is_additional', function ($model) {
                    if($model->is_additional == 1){
                        return '<span class="badge bg-label-danger" text-capitalized="">Additional</span>';
                    }else{
                        return '-';
                    }
                })
                ->editColumn('type', function ($model) {
                    $label = '';
                    $title_label = '';
                    if(!empty($model->hasAttendance->in_date)){
                        $title_label = date('h:i A', strtotime($model->hasAttendance->in_date));
                    }
                    if($model->type=="lateIn"){
                        $label = '<span data-toggle="tooltip" data-placement="top" title="PUNCH TIME: '. $title_label .'" class="badge bg-label-primary" text-capitalized="">';
                                    $label .= Str::ucfirst($model->type);
                        $label .= '</span>'; 
                    }else{
                        $label = '<span data-toggle="tooltip" data-placement="top" title="PUNCH TIME: '. $title_label .'" class="badge bg-label-warning" text-capitalized="">';
                                    $label .= Str::ucfirst($model->type);
                        $label .= '</span>';
                    }
                    
                    return $label;
                })
                ->editColumn('attendance_id', function ($model) {
                    if(isset($model->hasAttendance) && !empty($model->hasAttendance->in_date)){
                        return '<span class="fw-semibold">'.date('d M Y', strtotime($model->hasAttendance->in_date)).'</span>';
                    }else{
                        return '-';
                    }
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('user_id', function ($model) {
                    return view('user.attendance.employee-profile', ['model' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('user.attendance.discrepancy_action', ['model' => $model])->render();
                })
                ->rawColumns(['select', 'user_id', 'type', 'is_additional', 'attendance_id', 'status', 'action'])
                ->make(true);
        }

        return view('user.attendance.team_discrepancies', compact('title', 'user', 'employees', 'departments', 'url'));
    }
    
    public function managerTeamDiscrepancies(Request $request, $user_slug = null){
        $this->authorize('manager_team_discrepancies-list');
        $title = 'Team Discrepancies';
        $logined_user = Auth::user();

        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $employees = [];
        $employees_ids = [];
        $departments = [];
        $url = '';
        $dept_ids = [];

        if($role=='Department Manager'){
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

            $department_users = DepartmentUser::orderby('id', 'desc')->whereIn('department_id',  $dept_ids)->where('user_id', '!=', Auth::user()->id)->where('end_date', null)->get();
            foreach($department_users as $department_user){
                $depat_user = User::where('id', $department_user->user_id)->where('status', 1)->where('is_employee', 1)->first();
                if(!empty($depat_user)){
                    $dep_user = User::where('id', $department_user->user_id)->first(['id', 'first_name', 'last_name', 'slug']);
                    if(!empty($dep_user)){
                        $employees[] = $dep_user;
                        $employees_ids[] = $department_user->user_id;
                    }
                }
            }
        }
        
        if(!empty($user_slug) && $user_slug != 'All') {
            $user = User::where('slug', $user_slug)->first();
            $url = URL::to('manager/team/discrepancies/'.$user_slug);
            // $model = Discrepancy::where('user_id', $user->id)->latest()->get();
            
            $model = [];
            Discrepancy::where('user_id', $user->id)
                ->latest()
                ->chunk(100, function ($discrepancies) use (&$model) {
                    foreach ($discrepancies as $discrepancy) {
                        $model[] = $discrepancy;
                    }
            });
        }else{
            $user = User::where('slug', $logined_user->slug)->first();
            // $model = Discrepancy::orderby('status', 'asc')->whereIn('user_id', $employees_ids)->get();
            
            $model = [];
            Discrepancy::whereIn('user_id', $employees_ids)
                ->latest()
                ->chunk(100, function ($discrepancies) use (&$model) {
                    foreach ($discrepancies as $discrepancy) {
                        $model[] = $discrepancy;
                    }
            });
        }

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->addColumn('select', function ($model) {
                    return view('user.attendance.discrepancy_check', ['model' => $model])->render();
                })
                ->editColumn('status', function ($model) {
                   if($model->status==1){
                        return '<span class="badge bg-label-success" text-capitalized="">Approved</span>';
                    }elseif($model->status==2){
                        return '<span class="badge bg-label-danger" text-capitalized="">Rejected</span>';
                    }else{
                        return '<span class="badge bg-label-warning" text-capitalized="">Pending</span>';
                    }
                })
                ->editColumn('is_additional', function ($model) {
                    if($model->is_additional == 1){
                        return '<span class="badge bg-label-danger" text-capitalized="">Additional</span>';
                    }else{
                        return '-';
                    }
                })
                ->editColumn('type', function ($model) {
                    $label = '';
                    if($model->type=="lateIn"){
                        $label = '<span data-toggle="tooltip" data-placement="top" title="PUNCH TIME: '. date('h:i A', strtotime($model->hasAttendance->in_date)) .'" class="badge bg-label-primary" text-capitalized="">';
                                    $label .= Str::ucfirst($model->type);
                        $label .= '</span>'; 
                    }else{
                        $label = '<span data-toggle="tooltip" data-placement="top" title="PUNCH TIME: '. date('h:i A', strtotime($model->hasAttendance->in_date)) .'" class="badge bg-label-warning" text-capitalized="">';
                                    $label .= Str::ucfirst($model->type);
                        $label .= '</span>';
                    }

                    
                    return $label;
                })
                ->editColumn('attendance_id', function ($model) {
                    if(isset($model->hasAttendance) && !empty($model->hasAttendance->in_date)){
                        return '<span class="fw-semibold">'.date('d M Y', strtotime($model->hasAttendance->in_date)).'</span>';
                    }else{
                        return '-';
                    }
                })
                ->editColumn('created_at', function ($model) {
                    return Carbon::parse($model->created_at)->format('d, M Y');
                })
                ->editColumn('user_id', function ($model) {
                    return view('user.attendance.employee-profile', ['model' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('user.attendance.discrepancy_action', ['model' => $model])->render();
                })
                ->rawColumns(['select', 'user_id', 'type', 'is_additional', 'attendance_id', 'status', 'action'])
                ->make(true);
        }

        return view('user.attendance.manager-team_discrepancies', compact('title', 'user', 'employees', 'departments', 'url'));
    }

    public function showDiscrepancy($id)
    {
        $model = Discrepancy::where('id', $id)->first();
        return (string) view('user.attendance.show_content', compact('model'));
    }

    public function dailyLog(Request $request, $getMonth = null, $getYear = null, $user_slug = null)
    {
        $this->authorize('admin_attendance_daily_log-list');
        $title = 'Daily Log';

        if(isset($request->user_slug)){
            $user_slug = $request->user_slug;
        }
        
        $userWithAdminRole = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin'); // Replace 'admin' with the actual role name.
        })->first();

        $employees = [];
        $url = '';
        $department_users = DepartmentUser::where('end_date',  NULL)->get();

        foreach($department_users as $department_user){
            $emp_data = User::where('id', $department_user->user_id)->where('id', '!=', $userWithAdminRole->id)->first(['id', 'first_name', 'last_name', 'slug']);
            if(!empty($emp_data)){
                $employees[] = $emp_data;
            }
        }

        $currentMonth=date('m');
        if(date('d')>25){
            $currentMonth=date('m',strtotime('first day of +1 month'));
        }
        if(!empty($getMonth) || !empty($user_slug)){
            $year = $getYear;
            $month = $getMonth;

            $user = User::where('slug', $user_slug)->first();
            $url = URL::to('user/attendance/daily-log/'.$month.'/'.$year.'/'.$user_slug);
        }else{
            $year = date('Y');
            if(date('d')>26 || (date('d')==26 && date('H')>11)) {
                $month=date('m', strtotime('first day of +1 month'));
            } else {
                $month=date('m');
            }

            $user = User::where('slug', $userWithAdminRole->slug)->first();
        }

        // $model = Attendance::orderby('id', 'desc')->where('user_id', $user->id)->get();
        $model = [];
        Attendance::where('user_id', $user->id)
            ->latest()
            ->chunk(100, function ($logs) use (&$model) {
                foreach ($logs as $log) {
                    $model[] = $log;
                }
        });

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('behavior', function ($model) {
                    $label = '';

                    switch ($model->behavior) {
                        case 'I':
                            $label = '<span class="badge bg-label-success" text-capitalized="">Punched In</span>';
                            break;
                        case 'O':
                            $label = '<span class="badge bg-label-info" text-capitalized="">Punched Out</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('in_date', function ($model) {
                    return '<span class="text-primary fw-semibold">'.Carbon::parse($model->in_date)->format('d, M Y').'</span>';
                })
                ->addColumn('time', function ($model) {
                    return '<span class="fw-semibold">'.Carbon::parse($model->in_date)->format('h:i A').'</span>';
                })
                ->editColumn('user_id', function ($model) {
                    return view('user.attendance.employee-profile', ['model' => $model])->render();
                })
                ->rawColumns(['user_id', 'behavior', 'in_date', 'time'])
                ->make(true);
        }

        return view('user.attendance.daily-log', compact('title', 'user', 'month','year', 'currentMonth', 'employees', 'url'));
    }
    
    public function employeeDailyLog(Request $request, $getMonth = null, $getYear = null, $user_slug = null)
    {
        $this->authorize('employee_attendance_daily_log-list');
        $title = 'Daily Log';

        if(isset($request->user_slug)){
            $user_slug = $request->user_slug;
        }

        $logined_user = Auth::user();

        $role = $logined_user->getRoleNames()->first();
        foreach($logined_user->getRoleNames() as $user_role){
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

            $department_users = DepartmentUser::orderby('id', 'desc')->whereIn('department_id',  $dept_ids)->where('end_date', null)->get();
            foreach($department_users as $department_user){
                $user = User::where('id', $department_user->user_id)->first();
                if(!empty($user)){
                    $dep_user = User::where('id', $department_user->user_id)->where('status', 1)->where('is_employee', 1)->first(['id','first_name', 'last_name', 'slug']);
                    if(!empty($dep_user)){
                        $employees[] = $dep_user;
                    }
                }
            }
        }

        $currentMonth=date('m');
        if(date('d')>25){
            $currentMonth=date('m',strtotime('first day of +1 month'));
        }
        if(!empty($getMonth) || !empty($user_slug)){
            $year = $getYear;
            $month = $getMonth;

            $user = User::where('slug', $user_slug)->first();
            $url = URL::to('employee/attendance/daily-log/'.$month.'/'.$year.'/'.$user_slug);
        }else{
            $year = date('Y');
            if(date('d')>26 || (date('d')==26 && date('H')>11)) {
                $month=date('m', strtotime('first day of +1 month'));
            } else {
                $month=date('m');
            }

            $user = User::where('slug', Auth::user()->slug)->first();
        }

        $model = Attendance::orderby('id', 'desc')->where('user_id', $user->id)->get();

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('behavior', function ($model) {
                    $label = '';

                    switch ($model->behavior) {
                        case 'I':
                            $label = '<span class="badge bg-label-success" text-capitalized="">Punched In</span>';
                            break;
                        case 'O':
                            $label = '<span class="badge bg-label-info" text-capitalized="">Punched Out</span>';
                            break;
                    }

                    return $label;
                })
                ->editColumn('in_date', function ($model) {
                    return '<span class="text-primary fw-semibold">'.Carbon::parse($model->in_date)->format('d, M Y').'</span>';
                })
                ->addColumn('time', function ($model) {
                    return '<span class="fw-semibold">'.Carbon::parse($model->in_date)->format('h:i A').'</span>';
                })
                ->editColumn('user_id', function ($model) {
                    return view('user.attendance.employee-profile', ['model' => $model])->render();
                })
                ->rawColumns(['user_id', 'behavior', 'in_date', 'time'])
                ->make(true);
        }

        return view('user.attendance.employee-daily-log', compact('title', 'user', 'month','year', 'currentMonth', 'employees', 'url'));
    }

    //Team
    public function getDiscrepancies()
    {
        $user = Auth::user();

        $role = $user->getRoleNames()->first();
        foreach($user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager' || $user_role=='Manager'){
                $role = $user_role;
            }
        }

        // $currentMonthStart = Carbon::now()->subMonth()->startOfMonth()->addDays(25);
        // $currentMonthEnd = Carbon::now()->startOfMonth()->addDays(24);
        
        if(date('d')>26 || (date('d')==26 && date('H')>11)){
          $data['month']=date('m',strtotime('first day of +1 month'));
        }else{
          $data['month']=date('m');
        }
        
        if(date('m')==$data['month']){
            $currentMonthStart = Carbon::now()->subMonth()->startOfMonth()->addDays(25);
            $currentMonthEnd = Carbon::now()->startOfMonth()->addDays(24);
        }else{
            $currentMonthStart = Carbon::now()->startOfMonth()->addDays(25);
            $currentMonthEnd = Carbon::now()->startOfMonth()->addMonth()->addDays(24);
        }

        if($role == 'Admin'){
            $current_month_discrepancies = Discrepancy::orderby('status', 'asc')->where('user_id', '!=', $user->id)->whereBetween('date', [$currentMonthStart, $currentMonthEnd])->get();
        }else{
            $department = Department::where('manager_id', $user->id)->where('status', 1)->first();
            if(!empty($department)) {
                $department_users = DepartmentUser::where('department_id', $department->id)->where('end_date', null)->get(['user_id']);
                $team_members_ids = [];

                foreach($department_users as $department_user) {
                    if($department_user->user_id != $user->id) {
                        $team_members_ids[] = $department_user->user_id;
                    }
                }
            }

            $current_month_discrepancies = Discrepancy::orderby('status', 'asc')->whereBetween('date', [$currentMonthStart, $currentMonthEnd])->whereIn('user_id', $team_members_ids)->get();
        }

        return (string) view('user.attendance.get-discrepancies', compact('current_month_discrepancies'));
    }

    public function getLeaves()
    {
        $user = Auth::user();

        $role = $user->getRoleNames()->first();
        foreach($user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager' || $user_role=='Manager'){
                $role = $user_role;
            }
        }

        // $currentMonthStart = Carbon::now()->startOfMonth(); // Get the first day of the current month
        // $currentMonthEnd = Carbon::now()->startOfMonth()->addDays(24); // Get the 25th day of the current month
        
        if(date('d')>26 || (date('d')==26 && date('H')>11)){
          $data['month']=date('m',strtotime('first day of +1 month'));
        }else{
          $data['month']=date('m');
        }
        
        if(date('m')==$data['month']){
            $currentMonthStart = Carbon::now()->subMonth()->startOfMonth()->addDays(25);
            $currentMonthEnd = Carbon::now()->startOfMonth()->addDays(24);
        }else{
            $currentMonthStart = Carbon::now()->startOfMonth()->addDays(25);
            $currentMonthEnd = Carbon::now()->startOfMonth()->addMonth()->addDays(24);
        }

        if($role == 'Admin'){
            $current_month_leaves = UserLeave::orderby('status', 'ASC')->where('user_id', '!=', $user->id)->whereBetween('start_at', [$currentMonthStart, $currentMonthEnd])->get();
        }else{
            $department = Department::where('manager_id', Auth::user()->id)->where('status', 1)->first();
            if(!empty($department)) {
                $department_users = DepartmentUser::where('department_id', $department->id)->where('end_date', null)->get(['user_id']);
                $team_members_ids = [];

                foreach($department_users as $department_user) {
                    if($department_user->user_id != Auth::user()->id) {
                        $team_members_ids[] = $department_user->user_id;
                    }
                }
            }

            $current_month_leaves = UserLeave::orderby('status', 'ASC')->whereBetween('start_at', [$currentMonthStart, $currentMonthEnd])->whereIn('user_id', $team_members_ids)->get();
        }

        return (string) view('user.attendance.get-leaves', compact('current_month_leaves'));
    }
    public function ApproveOrRejectDiscrepancy(Request $request, $discrepancy_id=null, $status=null){
        if($discrepancy_id != null){
            $discrepancy = Discrepancy::where('id', $discrepancy_id)->first();
            if(!empty($discrepancy) && $status=='approve'){
                $discrepancy->status = 1; //approve
            }else{
                $discrepancy->status = 2; //reject
            }

            $discrepancy->approved_by = Auth::user()->id;
            $discrepancy->save();

            if($discrepancy){
                return true;
            }else{
                return false;
            }
        }else{
            $data = json_decode($request->data);
            
            foreach($data as $value){
                if($value->type=='lateIn' || $value->type=='earlyout') {
                    $model = Discrepancy::where('id', $value->id)->first();
                    if($model){
                        $model->approved_by = Auth::user()->id;
                        $model->status = 1;
                        $model->save();
                    }
                }else{
                    $model = UserLeave::where('id', $value->id)->first();
                    if($model){
                        $model->approved_by = Auth::user()->id;
                        $model->status = 1;
                        $model->save();
                    }
                }
            }

            return true;
        }
    }

    public function ApproveOrRejectTeamDiscrepancies(Request $request, $status){
        $data = json_decode($request->data);
        
        foreach($data as $value){
            $model = Discrepancy::where('id', $value->id)->first();
            if($model){
                $model->approved_by = Auth::user()->id;
                if($status=='approve'){
                    $model->status = 1;
                }else{
                    $model->status = 2;
                }
                $model->save();
            }
        }

        if($model){
            return 'true';
        }else{
            return false;
        }
    }
    
    public function monthlyAttendanceReport($getMonth = null, $getYear = null){
        $this->authorize('attendance_monthly_report-list');
        $title = 'Monthly Attendance Report';
        $behavior = 'all';
        $user = Auth::user();
        
        $data = [];
        $employees = [];
        $data['employees'] = User::where('status', 1)->where('is_employee', 1)->get();
        $data['users'] = User::where('status', 1)->where('is_employee', 1)->paginate(10);
        
        $year = date('Y');
        $month = date('m');
        if(!empty($getMonth)){
            $year = $getYear;
            $month = $getMonth;
            
            // Calculate the start date (26th of the previous month)
            $from_date = Carbon::create($year, $month, 26, 0, 0, 0)->subMonth();
            
            // Calculate the end date (25th of the current month)
            $to_date = Carbon::create($year, $month, 25, 23, 59, 59);
        }else{
            if(date('d')>26 || (date('d')==26 && date('H')>11)){
                $from_date = Carbon::now()->startOfMonth()->addDays(25);
                $to_date = Carbon::now()->startOfMonth()->addMonth()->addDays(24);
            }else{
                $from_date = Carbon::now()->subMonth()->startOfMonth()->addDays(25);
                $to_date = Carbon::now()->startOfMonth()->addDays(24);
            }
        }
        
        $fullMonthName = \Carbon\Carbon::create(null, $month, 1)->format('F');
        
        $data['from_date'] = date('Y-m-d', strtotime($from_date));
        $data['to_date'] = date('Y-m-d', strtotime($to_date));
        $data['behavior'] = $behavior;
        
        return view('user.attendance.monthly-attendance-report', compact('title', 'user', 'data', 'year', 'month', 'fullMonthName'));
    }
    public function monthlyAttendanceReportFilter(Request $request){
        $title = 'Attendance Summary';
        $data = [];

        $employees = [];
        $employees = User::where('status', 1)->where('is_employee', 1)->get();

        if($request->ajax()){
            $users = [];
            
            if(!empty($request->year) && !empty($request->month)){
                $year = $request->year;
                $month = $request->month;
                
                // Calculate the start date (26th of the previous month)
                $from_date = Carbon::create($year, $month, 26, 0, 0, 0)->subMonth();
                
                // Calculate the end date (25th of the current month)
                $to_date = Carbon::create($year, $month, 25, 23, 59, 59);
            }else{
                if(date('d')>26 || (date('d')==26 && date('H')>11)){
                    $from_date = Carbon::now()->startOfMonth()->addDays(25);
                    $to_date = Carbon::now()->startOfMonth()->addMonth()->addDays(24);
                }else{
                    $from_date = Carbon::now()->subMonth()->startOfMonth()->addDays(25);
                    $to_date = Carbon::now()->startOfMonth()->addDays(24);
                }
            }
            
            $data['employees'] = $employees;
            $behavior = 'all';
            
            $all_employee_ids = [];
            $filter_employees = json_decode($request['employees']);
            if(!empty($filter_employees) && count($filter_employees) > 0){
                if($filter_employees[0]=='All'){
                    foreach($employees as $employee){
                        $all_employee_ids[] = $employee->id;
                    }
                }else{
                    $all_employee_ids = $filter_employees;
                }
            }
            
            $employees = $all_employee_ids;
            $users = User::whereIn('id', $employees)->where('status', 1)->where('is_employee', 1)->get();

            $data['from_date'] = date('Y-m-d', strtotime($from_date));
            $data['to_date'] = date('Y-m-d', strtotime($to_date));
            $data['behavior'] = $behavior;
            $data['users'] = $users;

            return (string) view('user.attendance.monthly-attendance-report-filter', compact('data'));
        }
    }
}
