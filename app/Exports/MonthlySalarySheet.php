<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\MonthlySalaryReport;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlySalarySheet implements FromCollection, WithHeadings, WithStyles
{
    protected $title, $month, $year;

    public function __construct($title, $month, $year)
    {
        $this->title = $title;
        $this->month = $month;
        $this->year = $year;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $currentDate = Carbon::now();
        $month_year = date('m/Y', strtotime($currentDate));

        if(!empty($this->month) && !empty($this->year)){
            $month_year = $this->month.'/'.$this->year;
        }else{
            $month_year = date('m/Y', strtotime($currentDate));
        }

        $models = MonthlySalaryReport::with('hasEmployee')->where('month_year', $month_year)->get(['id', 'employee_id', 'actual_salary', 'car_allowance', 'earning_salary', 'approved_days_amount', 'deduction', 'net_salary', 'generated_date']);
        $employees = [];
        $counter = 0;
        foreach($models as $model){
            $counter = $counter+1;
            $designation = '-';
            if(isset($model->hasEmployee->jobHistory->designation) && !empty($model->hasEmployee->jobHistory->designation->title)){
                $designation = $model->hasEmployee->jobHistory->designation->title;
            }
            $department = '-';
            if(isset($model->hasEmployee->departmentBridge->department) && !empty($model->hasEmployee->departmentBridge->department->name)){
                $department = $model->hasEmployee->departmentBridge->department->name;
            }

            $employees[] = [
                'sNo' => $counter,
                'employee' => $model->hasEmployee->first_name.' '.$model->hasEmployee->last_name,
                'designation' => $designation,
                'department' => $department,
                'actual_salary' => number_format($model->actual_salary),
                'car_allowance' => number_format($model->car_allowance),
                'earning_salary' => number_format($model->earning_salary),
                'approved_days_amount' => number_format($model->approved_days_amount),
                'deduction' => number_format($model->deduction),
                'net_salary' => number_format($model->net_salary),
                'created_at' => date('d, M Y', strtotime($model->generated_date)),
            ];
        }
        return collect($employees);
    }

    public function headings(): array
    {
        return [
            [ $this->title ], // Additional custom heading
            ['S.No#', 'Employee', 'Designation', 'Department', 'Actual Salary', 'Car Allowance', 'Earning', 'Approved Days Amount', 'Deduction', 'Net Salary', 'Created At'], // Standard column headings
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(15);
        $sheet->getColumnDimension('K')->setWidth(20);
        
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
            ],
            2 => ['font' => ['bold' => true]],
        ];
    }
}
