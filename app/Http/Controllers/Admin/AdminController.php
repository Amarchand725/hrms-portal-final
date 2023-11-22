<?php

namespace App\Http\Controllers\Admin;

use Auth;
use DB;
use DateTime;
use Carbon\Carbon;
use App\Models\User;
use App\Models\AttendanceSummary;
use App\Models\WFHEmployee;
use App\Models\UserLeave;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Discrepancy;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\DepartmentUser;
use App\Models\WorkingShiftUser;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Session;

class AdminController extends Controller
{
    public function departments()
    {
        $this->authorize('department-list');
        return view('admin.departments');
    }
    public function logOut()
    {
        if (Auth::check()) {
            Session::invalidate(); // Invalidates the user's session
            return redirect()->route('admin.login');
        }
    }
    
    public function dashboard(Request $request)
    {             
        $user = Auth::user()->load('profile', 'profile.coverImage', 'jobHistory', 'jobHistory.designation', 'userWorkingShift', 'userWorkingShift.workShift', 'departmentBridge', 'departmentBridge.department');
        $data = [];

        $role = $user->getRoleNames()->first();
        foreach($user->getRoleNames() as $user_role){
            if($user_role=='Admin'){
                $role = $user_role;
            }elseif($user_role=='Department Manager'){
                $role = $user_role;
            }
        }

        $data['year'] = date('Y');
        if(date('d')>26 || (date('d')==26 && date('H')>11)){
          $data['month']=date('m',strtotime('first day of +1 month'));
        }else{
          $data['month']=date('m');
        }

        $shift = WorkingShiftUser::where('user_id', $user->id)->where('end_date', null)->orderBy('id', 'desc')->first();

        if(empty($shift)){
            $data['shift'] = $user->departmentBridge->department->departmentWorkShift->workShift;
        }else{
            $data['shift'] = $shift->workShift;
        }

        $data['announcements'] = Announcement::orderby('id', 'desc')->where('status', 1)->get();

        $team_members = [];
        $team_members_ids = [];
        $department_users = [];
        $department_manager_id = '';

        if($role == 'Admin' || $role=='Department Manager'){
            $user_department = Department::with('departmentWorkShift', 'departmentWorkShift.workShift')->where('manager_id', $user->id)->where('status', 1)->first();
        }else{
            if(isset($user->departmentBridge->department) && !empty($user->departmentBridge->department->id)) {
                $user_department = $user->departmentBridge->department;
            }
        }
        
        if(date('m')==$data['month']){
            $currentMonthStart = Carbon::now()->subMonth()->startOfMonth()->addDays(25);
            $currentMonthEnd = Carbon::now()->startOfMonth()->addDays(24);
        }else{
            $currentMonthStart = Carbon::now()->startOfMonth()->addDays(25);
            $currentMonthEnd = Carbon::now()->startOfMonth()->addMonth()->addDays(24);
        }

        if($role == 'Department Manager'){
            $manager_depts = [];
            $manager_depts[] = $user_department->id;
            
            $child_departments = Department::where('parent_department_id', $user_department->id)->get();
            if(!empty($child_departments) && count($child_departments) > 0){
                foreach($child_departments as $child_department){
                    $manager_depts[] = $child_department->id;
                }
            }
            
            if(isset($user_department) && !empty($user_department)){
                $department_users = DepartmentUser::whereIn('department_id', $manager_depts)->where('user_id', '!=', $user->id)->where('user_id', '!=', $user_department->manager_id)->where('end_date', null)->get(['user_id']);
            }

            if(sizeof($department_users) > 0) {
                foreach($department_users as $department_user) {
                    $team_members_ids[] = $department_user->user_id;
                }
            }
            $data['team_members'] = User::with('profile', 'jobHistory', 'jobHistory.designation', 'employeeStatus', 'employeeStatus.employmentStatus')->whereIn('id', $team_members_ids)->where('is_employee', 1)->where('status', 1)->get();
        }elseif($role=='Employee'){
            if(isset($user_department) && !empty($user_department)){
                $department_users = DepartmentUser::where('department_id', $user_department->id)->where('user_id', '!=', $user->id)->where('user_id', '!=', $user_department->manager_id)->where('end_date', null)->get(['user_id']);
            }

            if(sizeof($department_users) > 0) {
                foreach($department_users as $department_user) {
                    $team_members_ids[] = $department_user->user_id;
                }
            }
            $data['team_members'] = User::with('profile', 'jobHistory', 'jobHistory.designation', 'employeeStatus', 'employeeStatus.employmentStatus')->whereIn('id', $team_members_ids)->where('is_employee', 1)->where('status', 1)->get();
        }
        
        if($role == 'Admin' || $role == 'Developer'){
            $departments = Department::where('parent_department_id', $user_department->id)->where('status', 1)->get();
            foreach($departments as $department_manager){
                $team_members[] = $department_manager->manager_id;
            }

            $data['team_members'] = User::with('profile', 'jobHistory', 'jobHistory.designation', 'employeeStatus', 'employeeStatus.employmentStatus')->whereIn('id', $team_members)->where('is_employee', 1)->where('status', 1)->get();

            $data['employees'] = User::with(
                    'profile', 'profile.coverImage', 'jobHistory', 'jobHistory.designation',
                    'userWorkingShift', 'userWorkingShift.workShift', 'departmentBridge',
                    'departmentBridge.department', 'departmentBridge.department.departmentWorkShift.workShift'
                )->where('status', 1)->where('is_employee', 1)->get();
            
            $all_emps = User::where('status', 1)->where('is_employee', 1)->get();
            
            $employees_ids = [];
            foreach($all_emps as $emp){
                $employees_ids[] = $emp->id;
            }
            
            $data['employee_ids'] = $employees_ids;
            
            // $data['employees'] = User::with(
            //         'profile', 'profile.coverImage', 'jobHistory', 'jobHistory.designation',
            //         'userWorkingShift', 'userWorkingShift.workShift', 'departmentBridge',
            //         'departmentBridge.department', 'departmentBridge.department.departmentWorkShift.workShift'
            //     )->where('status', 1)->where('is_employee', 1)->get();
            
            // $employees_ids = [];
            // $manager_depts = [];
            // $manager_depts[] = $user_department->id;
            
            // if(isset($user_department) && !empty($user_department)){
            //     $department_users = DepartmentUser::where('department_id', $user_department->id)->where('user_id', '!=', $user->id)->where('end_date', null)->get(['user_id']);
            // }

            // if(sizeof($department_users) > 0) {
            //     foreach($department_users as $department_user) {
            //         $team_members_ids[] = $department_user->user_id;
            //         $employees_ids[] = $department_user->user_id;
            //     }
            // }
            // $data['team_members'] = User::with('profile', 'jobHistory', 'jobHistory.designation', 'employeeStatus', 'employeeStatus.employmentStatus')->whereIn('id', $team_members_ids)->where('is_employee', 1)->where('status', 1)->get();
            
            // $data['employee_ids'] = $employees_ids;
            
            $data['current_month_discrepancies'] = Discrepancy::with('hasEmployee', 'hasEmployee.profile', 'hasAttendance')->whereBetween('date', [$currentMonthStart, $currentMonthEnd])->orderby('status', 'asc')->get();
            $data['current_month_leave_requests'] = UserLeave::with('hasEmployee', 'hasEmployee.profile', 'hasLeaveType')->whereBetween('start_at', [$currentMonthStart, $currentMonthEnd])->orderby('status', 'asc')->get();
        }else{
            $data['current_month_discrepancies'] = Discrepancy::with('hasEmployee', 'hasEmployee.profile', 'hasAttendance')->whereBetween('date', [$currentMonthStart, $currentMonthEnd])->whereIn('user_id', $team_members_ids)->orderby('status', 'asc')->get();
            $data['current_month_leave_requests'] = UserLeave::with('hasEmployee', 'hasEmployee.profile', 'hasLeaveType')->whereBetween('start_at', [$currentMonthStart, $currentMonthEnd])->whereIn('user_id', $team_members_ids)->orderby('status', 'asc')->get();
        }

        $startShiftTime = '';
        $endShiftTime = '';
        if(isset($user->userWorkingShift->workShift) && !empty($user->userWorkingShift->workShift->start_time)){
            $startShiftTime = $user->userWorkingShift->workShift->start_time;
            $endShiftTime = $user->userWorkingShift->workShift->end_time;
        }else{
            if(isset($user_department->departmentWorkShift->workShift) && !empty($user_department->departmentWorkShift->workShift->name)){
                $startShiftTime = $user_department->departmentWorkShift->workShift->start_time;
                $endShiftTime = $user_department->departmentWorkShift->workShift->end_time;
            }
        }

        $data['punchedIn_time']='Not yet';
        $data['punchedIn_date']='Not yet';

        $data['punchedOut_time']='Not yet';
        $data['punchedOut_date']='Not yet';

        $todayDate = date("Y-m-d");
        if(date("H")>=8){
            $nextDate = date("Y-m-d", strtotime($todayDate.'+1 day'));
        }else{
            $todayDate = date("Y-m-d", strtotime($todayDate.'-1 day'));
            $nextDate = date("Y-m-d", strtotime($todayDate.'+1 day'));
        }
        $attendances = DB::table('attendances')->where('user_id',Auth::user()->id)->whereBetween('in_date',[$todayDate.' 00:00',$nextDate.' 23:59'])->get();
        if(count($attendances)>0){
            $shiftStart = date("H:i:s", strtotime('-6 hours '.$startShiftTime));
            $shiftEnd = date("H:i:s", strtotime('+10 hours '.$endShiftTime));
            $punchedIn = DB::table('attendances')->where('user_id', Auth::user()->id)->where('behavior','I')->whereBetween('in_date',[$todayDate.' '.$shiftStart,$nextDate.' '.$shiftEnd])->orderBy('id', 'asc')->first();
            $punchedOut = DB::table('attendances')->where('user_id',Auth::user()->id)->where('behavior','O')->whereBetween('in_date',[$todayDate.' '.$shiftStart,$nextDate.' '.$shiftEnd])->orderBy('id', 'desc')->first();
            if($punchedIn!=null){
                $punchedIn_data=new DateTime($punchedIn->in_date);
                $data['punchedIn_date']=$punchedIn_data->format('d M Y');
                $data['punchedIn_time']=$punchedIn_data->format('h:i A');
            }

            if($punchedOut!=null){
                $punchedOut_data=new DateTime($punchedOut->in_date);
                $data['punchedOut_date']=$punchedOut_data->format('d M Y');
                $data['punchedOut_time']=$punchedOut_data->format('h:i A');
            }
        }
        // Step 1: Get the shift start time and current time (in 24-hour format)
        $shiftStartTime = $startShiftTime;
        $currentDateTime = date('H:i:s');

        // Step 2: Calculate the progress percentage
        $shiftStartTimestamp = strtotime($shiftStartTime);
        $currentTimestamp = strtotime($currentDateTime);

        // If the current time is before the shift start time, subtract 24 hours from the current timestamp
        if ($currentTimestamp < $shiftStartTimestamp) {
            $currentTimestamp += 24 * 60 * 60; // Add 24 hours in seconds
        }

        $shiftEndTimestamp = strtotime($endShiftTime) + 24 * 60 * 60; // Add 24 hours to the shift end time
        $totalDuration = $shiftEndTimestamp - $shiftStartTimestamp;
        $elapsedDuration = $currentTimestamp - $shiftStartTimestamp;
        $progressPercentage = ($elapsedDuration / $totalDuration) * 100;

        $data['currentDateTime'] = $currentDateTime;
        $data['endShiftTime'] = $endShiftTime;

        $data['check_in_to_current_duration_of_shift'] = $progressPercentage;
        $data['remaining_duration_shift'] = 100-$progressPercentage;

        //User Leave & Discrepancies Reprt
        $leave_report = hasExceededLeaveLimit($user);
        $leave_in_balance = 0;
        if(!empty($leave_report)){
            $leave_in_balance = $leave_report['leaves_in_balance'];
        }

        $user_have_used_discrepancies = Discrepancy::where('user_id', $user->id)->where('status', '!=', 2)->whereMonth('date', Carbon::now()->month)
        ->whereYear('date', Carbon::now()->year)
        ->count();

        $remaining_fillable_discrepancies = settings()->max_discrepancies - $user_have_used_discrepancies;
        if($remaining_fillable_discrepancies > 0){
            $data['remaining_fillable_discrepancies'] = $remaining_fillable_discrepancies;
        }else{
            $data['remaining_fillable_discrepancies'] = 0;
        }

        $data['remaining_fillable_leaves'] = $leave_in_balance;
        //User Leave & Discrepancies Reprt
        
        $department_manager = '';
       if($role=='Department Manager'){
            $department = Department::with('manager')->where('manager_id', $user->id)->first();
            if(isset($department->parentDepartment->manager) && !empty($department->parentDepartment->manager)){
                $department_manager = $department->parentDepartment->manager;
            }
        }else{
            $department_user = DepartmentUser::orderby('id', 'desc')->where('user_id', $user->id)->first();
            $department = Department::with('manager')->where('id', $department_user->department_id)->first();
            if(isset($department->manager) && !empty($department->manager)){
                $department_manager = $department->manager;
            }
        }

        $data['department_manager'] = $department_manager;
        $data['user'] = $user;
        
        $user_leave_report = hasExceededLeaveLimit($user);
        
        $data['remaining_filable_leaves'] = $user_leave_report['total_remaining_leaves'];
       
        if($role=='Admin'){
            $data['title'] = 'Admin Dashboard';
            return view('admin.dashboards.admin-dashboard', compact('data'));
        }elseif($role=='Department Manager'){
            $data['title'] = 'Manager Dashboard';
            return view('admin.dashboards.manager-dashboard', compact('data'));
        }else{
            $data['title'] = 'Employee Dashboard';
            return view('admin.dashboards.emp-dashboard', compact('data'));
        }
    }
    
    public function loginForm()
    {
        $title = 'Login';
        if(Auth::check()){
            return redirect()->route('dashboard');
        }else{
            return view('admin.auth.login', compact('title'));
        }
    }
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->remember)) {
            $user = Auth::user();
            
            if ($user->status==1) {
                return response()->json(['success' => true]);
            } else {
                Auth::logout(); // Log out the user if they are not active
                return response()->json(['error' => 'Your account is not active.']);
            }
        } else {
            return response()->json(['error' => 'Invalid credentials']);
        }
    }
    
    //This is for WFH Users check in & out function.
    public function wfhCheckIn(){
        $user_work_shift = WorkingShiftUser::where('user_id', Auth::user()->id)->where('end_date', null)->first();
        $checked_in = Attendance::create([
            'user_id' => Auth::user()->id,
            'work_shift_id' => $user_work_shift->working_shift_id,
            'in_date' => date('Y-m-d H:i:s'),
            'behavior' => 'I',
            'status_id' => 7,
        ]);
        
        if($checked_in){
            return redirect()->back()->with('message', 'You have checked in successfully.');
        }
    }
    
    public function wfhCheckOut(){
        $user_work_shift = WorkingShiftUser::where('user_id', Auth::user()->id)->where('end_date', null)->first();
        $check_out = Attendance::create([
            'user_id' => Auth::user()->id,
            'work_shift_id' => $user_work_shift->working_shift_id,
            'in_date' => date('Y-m-d H:i:s'),
            'behavior' => 'O',
            'status_id' => 7,
        ]);
        
        if($check_out){
            return redirect()->back()->with('message', 'You have checkedout Successfully.');
        }
    }
}
