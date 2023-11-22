<?php

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceSummary;
use App\Models\AttendanceAdjustment;
use App\Models\Setting;
use App\Models\UserLeave;
use App\Models\BankAccount;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Discrepancy;
use Illuminate\Support\Carbon;
use App\Models\UserEmploymentStatus;
use App\Models\AuthorizeEmail;
use App\Models\VehicleUser;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\AttendanceController;

function SubPermissions($label){
    return Permission::where('label', $label)->get();
}
function bankDetail()
{
    return BankAccount::where('user_id', Auth::user()->id)->first();
}
function settings()
{
    return Setting::first();
}

function appName(){
    $setting = Setting::first();
    if(isset($setting) && !empty($setting->name)){
        $app_name = $setting->name;
    }else{
        $app_name = '-';
    }

    return $app_name;
}

function isOnProbation($user)
{
    if(isset($user->employeeStatus) && !empty($user->employeeStatus->end_date)) {
        $probation_end_date = $user->employeeStatus->end_date;
        return Carbon::today()->lte($probation_end_date);
    }
}

function hasExceededLeaveLimit($user)
{
    // $probation = UserEmploymentStatus::where('user_id', $user->id)
    // ->where('employment_status_id', 1)
    // ->first();

    $probation = UserEmploymentStatus::where('user_id', $user->id)->first();

    if(!empty($probation) && $probation->employment_status_id==1) {
        // Check if probation exists and is not completed
        // if ($probation && Carbon::today()->lte($probation->end_date)) {
        //     return false;
        // }

        $leave_report = [
            'total_leaves' => 0,
            'total_remaining_leaves' => 0,
            'total_leaves_in_account' => 0,
            'total_used_leaves' => 0,
            'leaves_in_balance' => 0,
        ];

        return $leave_report;
    }
    // elseif($probation->employment_status_id==2 && date('m', strtotime($probation->start_date)) === date('m')){
    //     $leave_report = [
    //         'total_leaves' => 0,
    //         'total_remaining_leaves' => 0,
    //         'total_leaves_in_account' => 0,
    //         'total_used_leaves' => 0,
    //         'leaves_in_balance' => 0,
    //     ];

    //     return $leave_report;
    // }
    else{
        // Calculate the start and end dates of the current leave year
        $currentYear = Carbon::now()->year;
        $leaveYearStart = Carbon::createFromDate($currentYear, 6, 26); // June 26th of the current year
        $leaveYearEnd = Carbon::createFromDate($currentYear + 1, 7, 25); // June 25th of the next year

        // Calculate the total used leaves within the leave year
        $total_used_leaves = UserLeave::where('user_id', $user->id)
            ->where('status', 1)
            ->whereBetween('start_at', [$leaveYearStart, $leaveYearEnd])
            ->sum('duration');

        // Calculate the number of months from the start date to the current date
        $currentDate = Carbon::now();
        $monthsElapsed = $leaveYearStart->diffInMonths($currentDate)+1;

        // Check if the user joined after the leave year started
        $joiningDate = Carbon::createFromDate($user->employeeStatus->start_date); // Replace with the actual joining date
        if ($joiningDate > $leaveYearStart) {
            $monthsElapsed = max(0, $joiningDate->diffInMonths($currentDate))+1;

            $interval = $joiningDate->diff($leaveYearEnd);
            $monthsDifference = ($interval->y * 12) + $interval->m;
            $total_leaves = $monthsDifference*2;
        }else{
            $interval = $leaveYearStart->diff($leaveYearEnd);
            $monthsDifference = ($interval->y * 12) + $interval->m;
            $total_leaves = $monthsDifference*2;
        }

        $total_leaves_in_account = $monthsElapsed * 2;

        // Calculate the leave balance
        $leaves_in_balance = $total_leaves_in_account - $total_used_leaves;

        $leave_report = [
            'total_leaves' => $total_leaves,
            'total_remaining_leaves' => $total_leaves-$total_used_leaves,
            'total_leaves_in_account' => $total_leaves_in_account,
            'total_used_leaves' => $total_used_leaves,
            'leaves_in_balance' => $leaves_in_balance,
        ];

        return $leave_report;
    }
}

function getAttandanceCount($user_id, $year_month_pre, $year_month_post, $behavior, $shift)
{
    return AttendanceController::getAttandanceCount($user_id, $year_month_pre, $year_month_post, $behavior, $shift);
}

function getAttandanceSingleRecord($userID,$current_date,$next_date,$status,$shiftID){
    return AttendanceController::getAttandanceSingleRecord($userID,$current_date,$next_date,$status,$shiftID);
}

function userAppliedLeaveOrDiscrepency($user_id, $type, $start_at){
    if($type=='absent' || $type=='firsthalf' || $type=="lasthalf"){
        return UserLeave::where('user_id', $user_id)->where('behavior_type', $type)->where('start_at', $start_at)->first();
    }elseif($type=='lateIn' || $type="earlyout"){
        return Discrepancy::where('user_id', $user_id)->where('type', $type)->where('date', $start_at)->first();
    }
}

function formatLetterTitle($text)
{
    // Remove underscores and replace with spaces
    $textWithoutUnderscores = str_replace('_', ' ', $text);

    // Capitalize the first character of each word
    $formattedText = ucwords($textWithoutUnderscores);

    return $formattedText;
}

function notifyBy($created_by) {
    return User::where('id', $created_by)->first();
}

function chatSupportData(){
    $adminDepart = Department::where('name', 'Main Department')->where('status', 1)->where('deleted_at', NULL)->pluck('id')->toArray();
    $financeDepart = Department::where('name', 'Accounts & Finance')->where('status', 1)->where('deleted_at', NULL)->pluck('id')->toArray();
    $itDepart = Department::where('name', 'IT Department')->where('status', 1)->where('deleted_at', NULL)->pluck('id')->toArray();
    $adminUsers = DepartmentUser::whereIn('department_id', $adminDepart)->where('end_date', NULL)->pluck('user_id')->toArray();
    $financeUsers = DepartmentUser::whereIn('department_id', $financeDepart)->where('end_date', NULL)->pluck('user_id')->toArray();
    $itUsers = DepartmentUser::whereIn('department_id', $itDepart)->where('end_date', NULL)->pluck('user_id')->toArray();
    $adminUsers = array_values(array_unique($adminUsers));
    $financeUsers = array_values(array_unique($financeUsers));
    $itUsers = array_values(array_unique($itUsers));
    $data['adminUsersID'] = $adminUsers;
    $data['financeUsersID'] = $financeUsers;
    $data['itUsersID'] = $itUsers;

    $data['adminUsers'] = User::with('profile', 'profile.coverImage', 'jobHistory', 'jobHistory.designation')->whereIn('id', $adminUsers)->get();
    $data['financeUsers'] = User::with('profile', 'profile.coverImage', 'jobHistory', 'jobHistory.designation')->whereIn('id', $financeUsers)->get();
    $data['itUsers'] = User::with('profile', 'profile.coverImage', 'jobHistory', 'jobHistory.designation')->whereIn('id', $itUsers)->get();
    $team_members_ids = [];

    $user = User::with('profile', 'profile.coverImage', 'jobHistory', 'jobHistory.designation')->where('id', Auth::user()->id)->first();
    $data['authUser']= Auth::user();
    $role = $user->getRoleNames()->first();
    foreach($user->getRoleNames() as $user_role){
        if($user_role=='Admin'){
            $role = $user_role;
        }elseif($user_role=='Department Manager'){
            $role = $user_role;
        }
    }

    if($role == 'Admin'){
        $data['team_members'] = User::with('profile', 'profile.coverImage', 'jobHistory', 'jobHistory.designation')->where('is_employee', 1)->where('status', 1)->get();
    }else{
        if(isset($user->departmentBridge->department) && !empty($user->departmentBridge->department->id)) {
            $user_department = $user->departmentBridge->department;
        }

        $team_member_ids = [];
        if(isset($user_department) && !empty($user_department)){
            $team_member_ids = DepartmentUser::where('department_id', $user_department->id)->where('user_id', '!=', $user->id)->where('end_date', null)->get(['user_id']);
        }

        if(sizeof($team_member_ids) > 0) {
            foreach($team_member_ids as $team_member_id) {
                $team_members_ids[] = $team_member_id->user_id;
            }
        }

        $data['team_members'] = User::with('profile', 'profile.coverImage', 'jobHistory', 'jobHistory.designation')->whereIn('id', $team_members_ids)->get();
    }
    return $data;
}

function attendanceAdjustment($employee_id, $attendance_id){
    return AttendanceAdjustment::where('employee_id', $employee_id)->where('attendance_id', $attendance_id)->first();
}

function sendEmailTo($user, $title){
    $authorize_email = AuthorizeEmail::where('status', 1)->where('email_title', $title)->first();

    $shoot_email = [];
    if(!empty($authorize_email)){
        if(!empty($authorize_email->to_emails)){
            $to_email_data = json_decode($authorize_email->to_emails);
            $to_emails = [];
            foreach($to_email_data as $to_email){
                if($to_email=='to_employee'){
                    $to_emails[] = $user->email;
                }elseif($to_email == 'to_ra' && isset($user->departmentBridge->department->manager) && !empty($user->departmentBridge->department->manager->email)){
                    $to_emails[] = $user->departmentBridge->department->manager->email;
                }else{
                    $to_emails[] = $to_email;
                }
            }
            $shoot_email['to_emails'] = $to_emails;
        }

        if(!empty($authorize_email->cc_emails)){
            $cc_email_data = json_decode($authorize_email->cc_emails);
            $cc_emails = [];
            foreach($cc_email_data as $cc_email){
                if($cc_email=='to_employee'){
                    $cc_emails[] = $user->email;
                }elseif($cc_email == 'to_ra' && isset($user->departmentBridge->department->manager) && !empty($user->departmentBridge->department->manager->email)){
                    $cc_emails[] = $user->departmentBridge->department->manager->email;
                }else{
                    $cc_emails[] = $cc_email;
                }
            }
            $shoot_email['cc_emails'] = $cc_emails;
        }
    }

    return $shoot_email;
}
function insuranceEligibility(){
    $setting = Setting::first();
    $user = User::where('id', Auth::user()->id)->first();
    $user_joining_date = $user->joiningDate->joining_date;

    // Your joining date in a variable (replace this with your actual date)
    $joiningDate = Carbon::create($user_joining_date);

    // Add 6 months to the joining date
    $newDate = $joiningDate->addMonths($setting->insurance_eligibility);
    $today = date('d-m-Y');

    if(Auth::user()->hasRole('Admin')){
        return true;
    }

    if(strtotime($today) >= strtotime($newDate)){
        return true;
    }else{
        return false;
    }
}
function getCars(){
    $vehicle_user = VehicleUser::where('user_id', Auth::user()->id)->get();
    if(count($vehicle_user) > 0){
        return true;
    }else{
        return false;
    }
}
function hrName(){
    $department = Department::where('name', 'like', '%Admin%')->where('manager_id', '!=', NULL)->where('status', 1)->first();
    if(!empty($department) && !empty($department->manager)){
        $manager_full_name = $department->manager->first_name.' '.$department->manager->last_name;
    }else{
        $manager_full_name = 'N/A';
    }

    return $manager_full_name;
}
function checkAttendance($userID, $current_date, $next_date, $shift){
    $user = User::where('id',$userID)->first();

    $start_time = date("Y-m-d H:i:s", strtotime($current_date.' '.$shift->start_time));
    $end_time = date("Y-m-d H:i:s", strtotime($next_date.' '.$shift->end_time));

    $start = date("Y-m-d H:i:s", strtotime('-6 hours '.$start_time));
    $end = date("Y-m-d H:i:s", strtotime('+6 hours '.$end_time));

    $punchIn = Attendance::where('user_id', $userID)->whereBetween('in_date',[$start, $end])->where('behavior', 'I')->orderBy('in_date', 'asc')->first();
    if(empty($punchIn)){
        return true;
    }else{
        return false;
    }
}

function getAttendanceCount($employee_id, $current_date, $next_date, $shift){
    $start_time = date('Y-m-d', strtotime($current_date)).' '.$shift->start_time;
    $end_time = date("Y-m-d", strtotime($next_date)).' '.$shift->end_time;

    $start = date("Y-m-d H:i:s", strtotime('-6 hours '.$start_time));
    $end = date("Y-m-d H:i:s", strtotime('+6 hours '.$end_time));
    return AttendanceSummary::where('user_id', $employee_id)->whereBetween('in_date',[$start, $end])->first();
}

function getEmployeesAttendanceCount($employees, $current_date, $next_date){
    $data = [];

    $total_late_in = 0;
    $total_half_days = 0;
    $total_absent = 0;

    $attendanceSummaries = AttendanceSummary::whereIn('user_id', $employees)
        ->whereBetween('in_date', [$current_date, $next_date])
        ->get();

    $lateInCount = 0;
    $halfDayCount = 0;

    foreach ($attendanceSummaries as $attendanceSummary) {
        if ($attendanceSummary->attendance_type === 'lateIn') {
            $lateInCount++;
        } elseif ($attendanceSummary->attendance_type === 'firsthalf' || $attendanceSummary->attendance_type === 'lasthalf') {
            $halfDayCount++;
        }
    }

    $data['total_late_in'] = $lateInCount;
    $data['total_half_days'] = $halfDayCount;
    $data['total_absent'] = count($employees)-count($attendanceSummaries);

    return $data;

}

function checkAttendanceByID($attendance_id){
    $att = Attendance::where('id', $attendance_id)->first();
    $data = '';
    if(!empty($att)){
        $data = $att;
    }

    return $data;
}

