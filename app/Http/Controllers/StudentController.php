<?php

namespace App\Http\Controllers;
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

            return response()->json(['success' => true, 'data' => $scholarships]);
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
                    'url' =>asset(Storage::url($doc->file_path)),
                    'status' => 'uploaded'
                ];
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Documents processed', 'data' => $uploaded], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Application not found or not owned by you'], 404);
        } catch (\Throwable $e) {
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
            $data = $applications->map(function ($app) {
                return $this->mapApplicationFull($app);
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch applications', 'error' => $e->getMessage()], 500);
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

            return response()->json(['success' => true, 'data' => $this->mapApplicationFull($application)]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Application not found or not owned by you'], 404);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch application', 'error' => $e->getMessage()], 500);
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

            $result = $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'admin' => $log->admin ? ['id' => $log->admin->id, 'name' => $log->admin->name] : null,
                    'created_at' => $log->created_at
                ];
            });

            return response()->json(['success' => true, 'data' => $result]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Application not found or not owned by you'], 404);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch review logs', 'error' => $e->getMessage()], 500);
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

            $data = $awards->map(function ($award) {
                return [
                    'id' => $award->id,
                    'application_id' => $award->application_id,
                    'award_amount' => $award->award_amount,
                    'award_date' => $award->award_date,
                    'scholarship' => $award->application ? [
                        'id' => $award->application->scholarship->id,
                        'name' => $award->application->scholarship->name
                    ] : null,
                    'allocations' => $award->allocations->map(function ($alloc) {
                        return [
                            'id' => $alloc->id,
                            'cost_category' => [
                                'id' => $alloc->costCategory->id,
                                'name' => $alloc->costCategory->name,
                            ],
                            'allocated_amount' => $alloc->allocated_amount,
                            'disbursements' => $alloc->disbursements->map(function ($d) {
                                return [
                                    'id' => $d->id,
                                    'amount' => $d->amount,
                                    'disbursement_date' => $d->disbursement_date,
                                    'reference_number' => $d->reference_number,
                                    'receipts' => $d->receipts->map(function ($r) {
                                        return [
                                            'id' => $r->id,
                                            'filename' => $r->filename,
                                            'url' =>asset(Storage::url($r->file_path)),
                                            'amount' => $r->amount,
                                            'uploaded_at' => $r->uploaded_at
                                        ];
                                    })
                                ];
                            })
                        ];
                    })
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch awards', 'error' => $e->getMessage()], 500);
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

            $result = $award->allocations->map(function ($alloc) {
                return [
                    'allocation_id' => $alloc->id,
                    'cost_category' => $alloc->costCategory ? ['id' => $alloc->costCategory->id, 'name' => $alloc->costCategory->name] : null,
                    'allocated_amount' => $alloc->allocated_amount,
                    'disbursements' => $alloc->disbursements->map(function ($d) {
                        return [
                            'id' => $d->id,
                            'amount' => $d->amount,
                            'date' => $d->disbursement_date,
                            'reference_number' => $d->reference_number,
                            'receipts' => $d->receipts->map(fn($r) => [
                                'id' => $r->id,
                                'filename' => $r->filename,
                                'url' => Storage::url($r->file_path),
                                'amount' => $r->amount,
                                'uploaded_at' => $r->uploaded_at
                            ])
                        ];
                    })
                ];
            });

            return response()->json(['success' => true, 'data' => $result]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Award not found or not owned by you'], 404);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch disbursements', 'error' => $e->getMessage()], 500);
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
                    'url' =>asset(Storage::url($receipt->file_path)),
                    'amount' => $receipt->amount
                ]
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Disbursement not found or not owned by you'], 404);
        } catch (\Throwable $e) {
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

            $data = [
                'id' => $disb->id,
                'amount' => $disb->amount,
                'date' => $disb->disbursement_date,
                'reference_number' => $disb->reference_number,
                'allocation' => [
                    'id' => $disb->allocation->id,
                    'cost_category' => $disb->allocation->costCategory ? ['id' => $disb->allocation->costCategory->id, 'name' => $disb->allocation->costCategory->name] : null,
                ],
                'receipts' => $disb->receipts->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'filename' => $r->filename,
                        'url' =>asset(Storage::url($r->file_path)),
                        'amount' => $r->amount,
                        'uploaded_at' => $r->uploaded_at
                    ];
                })
            ];

            return response()->json(['success' => true, 'data' => $data]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Disbursement not found or not owned by you'], 404);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch disbursement', 'error' => $e->getMessage()], 500);
        }
    }
    private function mapApplicationFull(Application $app)
    {
        return [
            'id' => $app->id,
            'status' => $app->status,
            'submitted_at' => $app->submitted_at,
            'created_at' => $app->created_at,
            'scholarship' => $app->scholarship ? [
                'id' => $app->scholarship->id,
                'name' => $app->scholarship->name,
                'description' => $app->scholarship->description,
                'application_deadline' => $app->scholarship->application_deadline
            ] : null,
            'documents' => $app->documents->map(function ($d) {
                return [
                    'id' => $d->id,
                    'filename' => $d->filename,
                    'url' =>asset(Storage::url($d->file_path)),
                    'uploaded_at' => $d->uploaded_at
                ];
            }),
            'review_logs' => $app->reviewLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'admin' => $log->admin ? ['id' => $log->admin->id, 'name' => $log->admin->name] : null,
                    'created_at' => $log->created_at
                ];
            }),
            'award' => $app->award ? [
                'id' => $app->award->id,
                'award_amount' => $app->award->award_amount,
                'award_date' => $app->award->award_date,
                'allocations' => $app->award->allocations->map(function ($alloc) {
                    return [
                        'id' => $alloc->id,
                        'cost_category' => $alloc->costCategory ? ['id' => $alloc->costCategory->id, 'name' => $alloc->costCategory->name] : null,
                        'allocated_amount' => $alloc->allocated_amount,
                        'disbursements' => $alloc->disbursements->map(function ($d) {
                            return [
                                'id' => $d->id,
                                'amount' => $d->amount,
                                'date' => $d->disbursement_date,
                                'reference_number' => $d->reference_number,
                                'receipts' => $d->receipts->map(function ($r) {
                                    return [
                                        'id' => $r->id,
                                        'filename' => $r->filename,
                                        'url' => asset(Storage::url($r->file_path)),
                                        'amount' => $r->amount,
                                        'uploaded_at' => $r->uploaded_at
                                    ];
                                })
                            ];
                        })
                    ];
                })
            ] : null
        ];
    }
}
