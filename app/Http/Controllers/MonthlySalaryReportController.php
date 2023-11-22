<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Exports\MonthlySalarySheet;
use App\Models\MonthlySalaryReport;
use Maatwebsite\Excel\Facades\Excel;

class MonthlySalaryReportController extends Controller
{
    public function index(Request $request, $getMonth = null, $getYear = null){
        $title = 'Monthly Salary Report';
        $currentDate = Carbon::now();
        $customMonth = '01-01-2023'; // Your custom month number

        // Create a Carbon instance with the custom month
        // $currentDate = Carbon::now();
        // $currentDate->month = $customMonth;
        
        $month = '';
        $year = '';
        $url = '';
        if(!empty($getMonth) && !empty($getYear)){
            $date = Carbon::createFromFormat('Y-m', $getYear . '-' . $getMonth);
            $month_year = $date->format('m/Y');

            $selectMonth = $date->format('F');
            $month = $date->format('m');
            $year = $date->format('Y');
            
            $url = 'Custom url';
        }else{
            $month_year = date('m/Y', strtotime($customMonth));
            $selectMonth = date('F', strtotime($currentDate));
            $month = date('m', strtotime($currentDate));
            $year = date('Y', strtotime($currentDate));

        }
        $start_from_monthly_report = MonthlySalaryReport::first();

        $model = MonthlySalaryReport::with('hasEmployee')->where('month_year', $month_year)->get();

        if($request->ajax() && $request->loaddata == "yes") {
            return DataTables::of($model)
                ->addIndexColumn()
                ->editColumn('actual_salary', function ($model) {
                    return number_format($model->actual_salary);
                })
                ->editColumn('car_allowance', function ($model) {
                    return number_format($model->car_allowance);
                })
                ->editColumn('earning_salary', function ($model) {
                    return number_format($model->earning_salary);
                })
                ->editColumn('approved_days_amount', function ($model) {
                    return number_format($model->approved_days_amount);
                })
                ->editColumn('deduction', function ($model) {
                    return number_format($model->deduction);
                })
                ->editColumn('net_salary', function ($model) {
                    return number_format($model->net_salary);
                })
                ->editColumn('generated_date', function ($model) {
                    return date('d-M-Y', strtotime($model->generated_date));
                })
                ->editColumn('employee_id', function ($model) {
                    return view('admin.salary_monthly_reports.employee-profile', ['employee' => $model])->render();
                })
                ->addColumn('action', function($model){
                    return view('admin.salary_monthly_reports.employee-action', ['employee' => $model])->render();
                })
                ->rawColumns(['employee_id', 'action'])
                ->make(true);
        }

        return view('admin.salary_monthly_reports.index', compact('title', 'selectMonth', 'month', 'year', 'month_year', 'url'));
    }

    public function monthlySalaryReportDownload($getMonth = null, $getYear = null){
        $month = $getMonth;
        $year = $getYear;
        $title = "Monthly Salary Report of ". $month. '/'.$year; // Replace with your desired title
        $file_name = date('d-m-Y').'.xlsx';
        return Excel::download(new MonthlySalarySheet($title, $month, $year), $file_name);
    }
}
