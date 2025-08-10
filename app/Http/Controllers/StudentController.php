<?php

namespace App\Http\Controllers;
use App\Http\Resources\AllocationResource;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\AwardResource;
use App\Http\Resources\DisbursementResource;
use App\Http\Resources\ReviewLogResource;
use App\Http\Resources\ScholarshipResource;
use App\Models\{
    Scholarship,
    Application,
    Document,
    ReviewLog,
    ApplicationAward,
    ApplicationAwardAllocation,
    Disbursement,
    Receipt
};
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StudentController extends Controller
{
    public function listScholarships(Request $request)
    {
        try {
            $scholarships = Scholarship::withCount('applications')
                ->where('status', 'active')
                ->whereDate('application_deadline', '>=', now())
                ->orderBy('application_deadline', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => ScholarshipResource::collection($scholarships)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch scholarships',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function applyToScholarship(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scholarship_id' => 'required|integer|exists:scholarships,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $studentId = $request->user()->id;
        $scholarshipId = $request->input('scholarship_id');

        try {

            $sch = Scholarship::findOrFail($scholarshipId);
            if ($sch->status !== 'active' || $sch->application_deadline < now()->toDateString()) {
                return response()->json(['success' => false, 'message' => 'Scholarship is not open for applications'], 409);
            }
            $exists = Application::where('student_id', $studentId)
                ->where('scholarship_id', $scholarshipId)
                ->exists();

            if ($exists) {
                return response()->json(['success' => false, 'message' => 'You have already applied for this scholarship'], 409);
            }

            DB::beginTransaction();

            $application = Application::create([
                'scholarship_id' => $scholarshipId,
                'student_id' => $studentId,
                'status' => 'submitted',
                'submitted_at' => now()
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Application submitted', 'data' => $application], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Scholarship not found'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to submit application', 'error' => $e->getMessage()], 500);
        }
    }
    public function uploadApplicationDocuments(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $studentId = $request->user()->id;

        try {
            $application = Application::where('id', $id)
                ->where('student_id', $studentId)
                ->firstOrFail();

            DB::beginTransaction();

            $uploaded = [];

            foreach ($request->file('documents') as $file) {
                $originalName = $file->getClientOriginalName();
                $duplicate = Document::where('application_id', $application->id)
                    ->where('filename', $originalName)
                    ->exists();
                if ($duplicate) {
                    $uploaded[] = [
                        'filename' => $originalName,
                        'status' => 'skipped',
                        'reason' => 'duplicate filename for this application'
                    ];
                    continue;
                }
                $storedName = uniqid() . '_' . preg_replace('/\s+/', '_', $originalName);
                $path = $file->storeAs('application_documents', $storedName, 'public');

                $doc = Document::create([
                    'application_id' => $application->id,
                    'filename' => $originalName,
                    'file_path' => $path,
                    'uploaded_at' => now()
                ]);

                $uploaded[] = [
                    'id' => $doc->id,
                    'filename' => $doc->filename,
                    'url' => asset(Storage::url($doc->file_path)),
                    'status' => 'uploaded'
                ];
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Documents processed', 'data' => $uploaded], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Application not found or not owned by you'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to upload documents', 'error' => $e->getMessage()], 500);
        }
    }
    public function myApplications(Request $request)
    {
        try {
            $studentId = $request->user()->id;

            $applications = Application::with([
                'scholarship',
                'documents',
                'reviewLogs.admin',
                'award.allocations.costCategory',
                'award.allocations.disbursements.receipts'
            ])->where('student_id', $studentId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => ApplicationResource::collection($applications)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch applications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function viewApplication(Request $request, $id)
    {
        $studentId = $request->user()->id;

        try {
            $application = Application::with([
                'scholarship',
                'documents',
                'reviewLogs.admin',
                'award.allocations.costCategory',
                'award.allocations.disbursements.receipts'
            ])->where('id', $id)
                ->where('student_id', $studentId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new ApplicationResource($application)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Application not found or not owned by you'], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewReviewLogs(Request $request, $id)
    {
        $studentId = $request->user()->id;

        try {
            $application = Application::where('id', $id)
                ->where('student_id', $studentId)
                ->firstOrFail();

            $logs = $application->reviewLogs()->with('admin')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => ReviewLogResource::collection($logs)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Application not found or not owned by you'], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch review logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function myAwards(Request $request)
    {
        try {
            $studentId = $request->user()->id;

            $awards = ApplicationAward::with([
                'application.scholarship',
                'allocations.costCategory',
                'allocations.disbursements.receipts'
            ])->whereHas('application', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })->get();

            return response()->json([
                'success' => true,
                'data' => AwardResource::collection($awards)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch awards',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewDisbursements(Request $request, $awardId)
    {
        try {
            $studentId = $request->user()->id;

            $award = ApplicationAward::with(['allocations.disbursements.receipts', 'application'])
                ->where('id', $awardId)
                ->whereHas('application', function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                })->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => AllocationResource::collection($award->allocations)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Award not found or not owned by you'], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch disbursements',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function uploadReceipt(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $studentId = $request->user()->id;

        try {
            $disbursement = Disbursement::where('id', $id)
                ->whereHas('allocation.award.application', function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                })->firstOrFail();
            $file = $request->file('receipt');
            $originalName = $file->getClientOriginalName();
            $amount = $request->input('amount');

            $duplicate = Receipt::where('disbursement_id', $disbursement->id)
                ->where('filename', $originalName)
                ->where('amount', $amount)
                ->exists();

            if ($duplicate) {
                return response()->json(['success' => false, 'message' => 'Duplicate receipt already uploaded'], 409);
            }

            DB::beginTransaction();

            $storedName = uniqid() . '_' . preg_replace('/\s+/', '_', $originalName);
            $path = $file->storeAs('receipts', $storedName, 'public');

            $receipt = Receipt::create([
                'disbursement_id' => $disbursement->id,
                'filename' => $originalName,
                'file_path' => $path,
                'amount' => $amount,
                'uploaded_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded',
                'data' => [
                    'id' => $receipt->id,
                    'filename' => $receipt->filename,
                    'url' => asset(Storage::url($receipt->file_path)),
                    'amount' => $receipt->amount
                ]
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Disbursement not found or not owned by you'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to upload receipt', 'error' => $e->getMessage()], 500);
        }
    }
    public function viewDisbursement(Request $request, $id)
    {
        $studentId = $request->user()->id;

        try {
            $disb = Disbursement::with('receipts', 'allocation.costCategory', 'allocation.award.application')
                ->where('id', $id)
                ->whereHas('allocation.award.application', function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                })->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new DisbursementResource($disb)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Disbursement not found or not owned by you'], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch disbursement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
