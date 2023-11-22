<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Resignation;
use Illuminate\Support\Facades\Mail;
use App\Mail\Email;

class SendNoticePeriodEndNotifications extends Command
{
    protected $signature = 'notice:end';
    protected $description = 'Send notice period end notifications to employees';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $today_last_date_employees = Resignation::whereNotNull('last_working_date')
            ->whereDate('last_working_date', now())
            ->get();
            
        foreach($today_last_date_employees as $today_last_employee){
            //employee
            $emp_last_date = User::where('id', $today_last_employee->employee_id)->first();
            
            //close job employment status
            $user_emp_status = UserEmploymentStatus::orderby('id', 'desc')->where('user_id', $emp_last_date->id)->first();
            $user_emp_status->employment_status_id = $resignation->employment_status_id;
            $user_emp_status->end_date = $resignation->last_working_date;
            $user_emp_status->save();
            
            //close job history
            $job_history = JobHistory::orderby('id', 'desc')->where('user_id', $emp_last_date->id)->first();
            $job_history->end_date = $resignation->last_working_date;
            $Job_history->save();
            
            //close salary history
            $salary_history = SalaryHistory::orderby('id', 'desc')->where('user_id', $emp_last_date->id)->first();
            $salary_history->end_date = $resignation->last_working_date;
            $salary_history->save();
            
            //close DepartmentUser
            $user_dept = DepartmentUser::orderby('id', 'desc')->where('user_id', $emp_last_date->id)->first();
            $user_dept->end_date = $resignation->last_working_date;
            $user_dept->save();
            
            //close DepartmentUser
            $user_dept = WorkingShiftUser::orderby('id', 'desc')->where('user_id', $emp_last_date->id)->first();
            $user_dept->end_date = $resignation->last_working_date;
            $user_dept->save();

            //de-active employee and remove from employment
            $emp_last_date->status = 0; //set to deactive
            $emp_last_date->is_employee = 0; //set to deactive
            $emp_last_date->save();
        }
            
        $tomorrow = now()->addDay();
        $employees = Resignation::whereNotNull('last_working_date')
            ->whereDate('last_working_date', $tomorrow)
            ->get();

        $admin_user = User::role('Admin')->first();
        foreach ($employees as $employee) {
            $model = User::where('id', $employee->employee_id)->first();
            
            $mailData = [
                        'from' => 'termination',
                        'title' => 'Employee Termination Notification',
                        'employee' => $model->first_name.' '.$model->last_name,
                    ];

            //Email Shooting
            if(!empty(sendEmailTo($model, 'employee_termination')) && !empty(sendEmailTo($model, 'employee_termination')['cc_emails'])){
                $to_emails = sendEmailTo($model, 'employee_termination')['to_emails'];
                $cc_emails = sendEmailTo($model, 'employee_termination')['cc_emails'];
                Mail::to($to_emails)->cc($cc_emails)->send(new Email($mailData));
            }else{
                $to_emails = sendEmailTo($model, 'employee_termination')['to_emails'];
                Mail::to($to_emails)->send(new Email($mailData));
            }
            //Email Shooting
        }
    }
}
