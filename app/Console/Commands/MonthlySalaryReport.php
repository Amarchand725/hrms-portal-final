<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserLeave;
use App\Models\Discrepancy;
use App\Models\MonthlySalaryReport as SalaryReport;
use Illuminate\Console\Command;

class MonthlySalaryReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monthly-salary-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly salary report excel sheet.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = [];

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
        
        // $data = [];

        // $data['month']=date('m');
        // $data['year']=date('Y');

        // $employees = User::where('is_employee', 1)->where('status', 1)->get(['id', 'first_name', 'last_name', 'slug']);
        // foreach($employees as $employee) {
        //     $currentDate = Carbon::now();
        //     $startOfMonth = $currentDate->copy()->startOfMonth();
        //     $endOfMonth = $currentDate->copy()->endOfMonth();

        //     // Today is before the 26th of the month, so calculate from the 26th of the previous month
        //     $previousMonth = $startOfMonth->subMonth();

        //     // if (date('G') < 0) { //it check it is less than mid night means before of 12pm
        //     $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate);
        //     // } else {
        //     //     $total_earning_days = $previousMonth->day(26)->diffInDays($currentDate) + 1;
        //     // }

        //     $data['total_earning_days'] = $total_earning_days;

        //     $date = Carbon::createFromFormat('Y-m', $data['year'] . '-' . $data['month']);
        //     $data['month_year'] = $date->format('m/Y');

        //     // $date = Carbon::create($data['year'], $data['month']);

        //     // Create a Carbon instance for the specified month
        //     $dateForMonth = Carbon::create(null, $data['month'], 1);

        //     // Calculate the start date (26th of the specified month)
        //     $startDate = $dateForMonth->copy()->subMonth()->startOfMonth()->addDays(25);
        //     $endDate = $dateForMonth->copy()->startOfMonth()->addDays(25);

        //     // Calculate the total days
        //     $data['totalDays'] = $startDate->diffInDays($endDate);

        //     $data['salary'] = 0;
        //     if(isset($employee->salaryHistory) && !empty($employee->salaryHistory->salary)) {
        //         $data['salary'] =  $employee->salaryHistory->salary;
        //         $data['per_day_salary'] = $data['salary'] / $data['totalDays'];
        //     } else {
        //         $data['per_day_salary'] = 0;
        //         $data['actual_salary'] =  0;
        //     }

        //     if(isset($employee->userWorkingShift) && !empty($employee->userWorkingShift->working_shift_id)) {
        //         $data['shift'] = $employee->userWorkingShift->workShift;
        //     } else {
        //         $data['shift'] = $employee->departmentBridge->department->departmentWorkShift->workShift;
        //     }
        //     $statistics = getAttandanceCount($employee->id, $data['year'] . "-" . ((int)$data['month'] - 1) . "-26", $data['year'] . "-" . (int)$data['month'] . "-25", 'all', $data['shift']);

        //     $lateIn = count($statistics['lateInDates']);
        //     $earlyOut = count($statistics['earlyOutDates']);

        //     $total_discrepancies = $lateIn + $earlyOut;

        //     $filled_discrepancies = Discrepancy::where('user_id', $employee->id)->where('status', 1)->whereBetween('date', [$startDate, $endDate])->count();

        //     $total_over_discrepancies = $total_discrepancies - $filled_discrepancies;
        //     $discrepancies_absent_days = 0;
        //     if($total_over_discrepancies > 2) {
        //         $discrepancies_absent_days = floor($total_over_discrepancies / 3);
        //     }
        //     $data['late_in_early_out_amount'] = $discrepancies_absent_days * $data['per_day_salary'];

        //     $filled_full_day_leaves = UserLeave::where('user_id', $employee->id)
        //                                         ->where('status', 1)
        //                                         ->whereMonth('start_at', $data['month'])
        //                                         ->whereYear('start_at', $data['year'])
        //                                         ->where('behavior_type', 'Full Day')
        //                                         ->get();

        //     $filled_full_day_leaves = $filled_full_day_leaves->sum('duration');

        //     $filled_half_day_leaves = UserLeave::where('user_id', $employee->id)
        //                                         ->where('status', 1)
        //                                         ->whereMonth('start_at', $data['month'])
        //                                         ->whereYear('start_at', $data['year'])
        //                                         ->where('behavior_type', 'First Half')
        //                                         ->orWhere('behavior_type', 'Last Half')
        //                                         ->count();
        //     $filled_half_day_leaves = $filled_half_day_leaves;
        //     $filled_half_day_leaves = $statistics['halfDay'] - $filled_half_day_leaves;
        //     $over_half_day_leaves = floor($filled_half_day_leaves / 2);

        //     $data['half_days_amount'] = $over_half_day_leaves * $data['per_day_salary'];

        //     $over_absent_days = $statistics['absent'] - $filled_full_day_leaves;
        //     $data['absent_days_amount'] = $over_absent_days * $data['per_day_salary'];

        //     $total_full_and_half_days_absent = $over_absent_days + $over_half_day_leaves;

        //     $all_absents = $total_full_and_half_days_absent + $discrepancies_absent_days;
        //     $all_absent_days_amount = $data['per_day_salary'] * $all_absents;

        //     $data['earning_days_amount'] =  $data['total_earning_days'] * $data['per_day_salary'];

        //     if(!empty($employee->hasAllowance)) {
        //         $data['car_allowance'] = $employee->hasAllowance->allowance;
        //     } else {
        //         $data['car_allowance'] = 0;
        //     }

        //     $data['total_actual_salary'] = $data['salary'];
        //     $total_earning_salary = $data['earning_days_amount'] + $data['car_allowance'];
        //     $data['total_earning_salary'] = $total_earning_salary;
        //     $total_net_salary = $total_earning_salary - $all_absent_days_amount;
        //     $data['net_salary'] = $total_net_salary;

        //     SalaryReport::create([
        //         'employee_id' => $employee->id,
        //         'month_year' => $data['month_year'],
        //         'actual_salary' =>  $data['total_actual_salary'],
        //         'car_allowance' =>  $data['car_allowance'],
        //         'earning_salary' =>  $data['total_earning_salary'],
        //         'deduction' =>  $all_absent_days_amount, //deduction
        //         'net_salary' =>  $data['net_salary'],
        //         'generated_date' =>  date('Y-m-d'),
        //     ]);
        // }
    }
}
