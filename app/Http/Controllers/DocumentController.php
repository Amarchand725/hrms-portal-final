<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Document;
use Illuminate\Http\Request;
use App\Models\DocumentAttachments;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class DocumentController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('documents-list');
        $title = 'All Documents';

        $employees = User::where('status', 1)->where('is_employee', 1)->latest()->get();
        // $model = Document::orderby('id', 'desc')->get();
        
        $model = [];
        Document::latest()
            ->chunk(100, function ($documents) use (&$model) {
                foreach ($documents as $document) {
                    $model[] = $document;
                }
        });
        
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
                ->editColumn('date', function ($model) {
                    return Carbon::parse($model->date)->format('d, M Y');
                })
                ->editColumn('user_id', function ($model) {
                    return view('admin.documents.employee-profile', ['employee' => $model])->render();
                })
                ->addColumn('department', function($model){
                    if(!empty($model->hasEmployee->departmentBridge->department->name)) {
                        return $model->hasEmployee->departmentBridge->department->name;
                    }else{
                        return '-';
                    }
                })
                ->addColumn('action', function($model){
                    return view('admin.documents.action', ['model' => $model])->render();
                })
                ->rawColumns(['user_id', 'status', 'designation', 'date', 'action'])
                ->make(true);
        }

        return view('admin.documents.index', compact('title', 'employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'employee' => 'required',
            'titles' => 'required|array|min:1',
            'attachments' => 'required|array|min:1',
        ]);

        // return $request;

        DB::beginTransaction();

        try{
            $user = User::where('slug', $request->employee)->first();
            $model = Document::create([
                'user_id' => $user->id,
                'date' => date('Y-m-d'),
            ]);

            if($model && count($request->titles) > 0 && count($request->attachments) > 0){
                foreach($request->titles as $key=>$title){
                    $attachment = '';
                    if ($request->attachments[$key]) {
                        $attachment = $request->attachments[$key];
                        $attachmentName = rand(). '.' . $attachment->getClientOriginalExtension();
                        $attachment->move(public_path('admin/assets/document_attachments'), $attachmentName);
                        $attachment = $attachmentName;
                    }

                    DocumentAttachments::create([
                        'document_id' => $model->id,
                        'title' => $title,
                        'attachment' => $attachment,
                    ]);
                }
            }
            DB::commit();

            \LogActivity::addToLog('New document Added');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function show($announcement_id)
    {
        $model = Document::findOrFail($announcement_id);
        return (string) view('admin.documents.show_content', compact('model'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $this->authorize('documents-edit');
        $employees = User::where('status', 1)->where('is_employee', 1)->latest()->get();
        $model = Document::where('id', $id)->first();
        return (string) view('admin.documents.edit_content', compact('model', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'employee' => 'required',
            'titles' => 'required|array|min:1',
        ]);

        DB::beginTransaction();

        try{
            $user = User::where('slug', $request->employee)->first();
            $model = Document::where('id', $request->document_id)->first();
            $model->user_id = $user->id;
            $model->save();

            if($model && isset($request->titles) && count($request->titles) > 0 && isset($request->attachments) && count($request->attachments) > 0){
                foreach($request->titles as $key=>$title){
                    if($title != null){
                        $attachment = '';
                        if ($request->attachments[$key]) {
                            $attachment = $request->attachments[$key];
                            $attachmentName = rand() . '.' . $attachment->getClientOriginalExtension();
                            $attachment->move(public_path('admin/assets/document_attachments'), $attachmentName);
                            $attachment = $attachmentName;
                        }

                        DocumentAttachments::create([
                            'document_id' => $model->id,
                            'title' => $title,
                            'attachment' => $attachment,
                        ]);
                    }
                }
            }

            DB::commit();

            \LogActivity::addToLog('Announcement Updated');

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function documentAttachmentUpdate(Request $request, $id){
        $model = DocumentAttachments::where('id', $id)->first();
        $model->title = $request->title;
        $model->save();

        if($model){
            return response()->json([
                'status' => true,
            ]);
        }else{
            return false;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($document_id)
    {
        $this->authorize('documents-delete');
        $model = Document::where('id', $document_id)->delete();
        if($model){
            return response()->json([
                'status' => true,
            ]);
        }else{
            return false;
        }
    }

    public function documentAttachmentDestroy($document_attachment_id){
        $model = DocumentAttachments::where('id', $document_attachment_id)->delete();
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
        $model = Document::onlyTrashed()->latest()->get();
        $title = 'All Trashed documents';
        $temp = 'All Trashed documents';

        if($request->ajax()) {
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
                ->editColumn('date', function ($model) {
                    return Carbon::parse($model->date)->format('d, M Y');
                })
                ->editColumn('user_id', function ($model) {
                    return view('admin.documents.employee-profile', ['employee' => $model])->render();
                })
                ->addColumn('department', function($model){
                    if(!empty($model->hasEmployee->departmentBridge->department->name)) {
                        return $model->hasEmployee->departmentBridge->department->name;
                    }else{
                        return '-';
                    }
                })
                ->addColumn('action', function($model){
                    $button = '<a href="'.route('documents.restore', $model->id).'" class="btn btn-icon btn-label-info waves-effect">'.
                                    '<span>'.
                                        '<i class="ti ti-refresh ti-sm"></i>'.
                                    '</span>'.
                                '</a>';
                    return $button;
                })
                ->rawColumns(['user_id', 'status', 'designation', 'date', 'action'])
                ->make(true);
        }

        return view('admin.documents.index', compact('title', 'temp'));
    }
    public function restore($id)
    {
        Document::onlyTrashed()->where('id', $id)->restore();
        return redirect()->back()->with('message', 'Record Restored Successfully.');
    }
}
