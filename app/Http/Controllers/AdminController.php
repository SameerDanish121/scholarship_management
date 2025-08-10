<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationAward;
use App\Models\ApplicationAwardAllocation;
use App\Models\DisbursementSchedule;
use App\Models\Scholarship;
use App\Models\CostCategory;
use App\Models\Disbursement;
use App\Models\Receipt;
use App\Models\ReviewLog;
use App\Models\ScholarshipBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function createScholarship(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'application_deadline' => 'required|date',
            'award_amount' => 'nullable|numeric',
            'total_budget' => 'nullable|numeric',
            'max_awards' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,in-active',
            'eligibility_criteria' => 'nullable|string',
            'created_by' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $scholarship = Scholarship::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Scholarship created successfully',
                'data' => $this->mapScholarship($scholarship),
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create scholarship: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function updateScholarship(Request $request, $id)
    {
        $scholarship = Scholarship::find($id);
        if (!$scholarship) {
            return response()->json([
                'success' => false,
                'message' => 'Scholarship not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'application_deadline' => 'sometimes|required|date',
            'award_amount' => 'nullable|numeric',
            'total_budget' => 'nullable|numeric',
            'max_awards' => 'nullable|integer|min:1',
            'status' => 'nullable|in:active,in-active',
            'eligibility_criteria' => 'nullable|string',
            'created_by' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $scholarship->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Scholarship updated successfully',
                'data' => $this->mapScholarship($scholarship->fresh()),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update scholarship: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function deleteScholarship($id)
    {
        $scholarship = Scholarship::find($id);
        if (!$scholarship) {
            return response()->json([
                'success' => false,
                'message' => 'Scholarship not found',
            ], 404);
        }

        try {
            $scholarship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Scholarship deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete scholarship: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function createCostCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:cost_categories,name',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $category = CostCategory::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cost category created successfully',
                'data' => $category,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create cost category: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function listCostCategories()
    {
        try {
            $categories = CostCategory::all();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cost categories: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function setScholarshipBudgets(Request $request, $id)
    {
        $scholarship = Scholarship::find($id);
        if (!$scholarship) {
            return response()->json([
                'success' => false,
                'message' => 'Scholarship not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'budgets' => 'required|array|min:1',
            'budgets.*.cost_category_id' => 'required|integer|exists:cost_categories,id',
            'budgets.*.planned_amount' => 'nullable|numeric|min:0',
            'budgets.*.committed_amount' => 'nullable|numeric|min:0',
            'budgets.*.disbursed_amount' => 'nullable|numeric|min:0',
            'budgets.*.receipted_amount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $budgetsData = $validator->validated()['budgets'];

        try {
            foreach ($budgetsData as $budget) {
                $costCategoryId = $budget['cost_category_id'];
                $planned = $budget['planned_amount'] ?? 0;
                $committed = $budget['committed_amount'] ?? 0;
                $disbursed = $budget['disbursed_amount'] ?? 0;
                $receipted = $budget['receipted_amount'] ?? 0;
                if ($committed > $planned) {
                    return response()->json([
                        'success' => false,
                        'message' => "Committed amount cannot exceed planned amount for cost category ID {$costCategoryId}",
                    ], 422);
                }
                $totalScheduled = DB::table('disbursement_schedules')
                    ->where('scholarship_id', $scholarship->id)
                    ->where('cost_category_id', $costCategoryId)
                    ->sum('scheduled_amount');

                if ($totalScheduled > $committed) {
                    return response()->json([
                        'success' => false,
                        'message' => "Total scheduled amount ({$totalScheduled}) exceeds committed amount ({$committed}) for cost category ID {$costCategoryId}",
                    ], 422);
                }
                $remainingScheduled = $totalScheduled > 0 ? ($totalScheduled - $disbursed) : PHP_INT_MAX;

                if ($receipted > $remainingScheduled) {
                    return response()->json([
                        'success' => false,
                        'message' => "Receipted amount ({$receipted}) cannot exceed remaining scheduled amount ({$remainingScheduled}) for cost category ID {$costCategoryId}",
                    ], 422);
                }
            }
            foreach ($budgetsData as $budget) {
                ScholarshipBudget::updateOrCreate(
                    [
                        'scholarship_id' => $scholarship->id,
                        'cost_category_id' => $budget['cost_category_id'],
                    ],
                    [
                        'planned_amount' => $budget['planned_amount'] ?? 0,
                        'committed_amount' => $budget['committed_amount'] ?? 0,
                        'disbursed_amount' => $budget['disbursed_amount'] ?? 0,
                        'receipted_amount' => $budget['receipted_amount'] ?? 0,
                    ]
                );
            }

            $updatedBudgets = ScholarshipBudget::with('costCategory')
                ->where('scholarship_id', $scholarship->id)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Budgets set successfully',
                'data' => $updatedBudgets->map(function ($budget) {
                    return [
                        'id' => $budget->id,
                        'cost_category' => [
                            'id' => $budget->costCategory->id,
                            'name' => $budget->costCategory->name,
                        ],
                        'planned_amount' => $budget->planned_amount,
                        'committed_amount' => $budget->committed_amount,
                        'disbursed_amount' => $budget->disbursed_amount,
                        'receipted_amount' => $budget->receipted_amount,
                    ];
                }),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set budgets: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function viewScholarshipBudgets($id)
    {
        $scholarship = Scholarship::find($id);
        if (!$scholarship) {
            return response()->json([
                'success' => false,
                'message' => 'Scholarship not found',
            ], 404);
        }

        try {
            $budgets = ScholarshipBudget::with('costCategory')
                ->where('scholarship_id', $scholarship->id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $budgets->map(function ($budget) {
                    return [
                        'id' => $budget->id,
                        'cost_category' => [
                            'id' => $budget->costCategory->id,
                            'name' => $budget->costCategory->name,
                        ],
                        'planned_amount' => $budget->planned_amount,
                        'committed_amount' => $budget->committed_amount,
                        'disbursed_amount' => $budget->disbursed_amount,
                        'receipted_amount' => $budget->receipted_amount,
                    ];
                }),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve budgets: ' . $e->getMessage(),
            ], 500);
        }
    }
    private function mapScholarship(Scholarship $scholarship)
    {
        return [
            'id' => $scholarship->id,
            'name' => $scholarship->name,
            'description' => $scholarship->description,
            'application_deadline' => $scholarship->application_deadline,
            'award_amount' => $scholarship->award_amount,
            'total_budget' => $scholarship->total_budget,
            'max_awards' => $scholarship->max_awards,
            'status' => $scholarship->status,
            'eligibility_criteria' => $scholarship->eligibility_criteria,
            'created_by' => $scholarship->created_by,
            'created_at' => $scholarship->created_at->toDateTimeString(),
            'updated_at' => $scholarship->updated_at->toDateTimeString(),
        ];
    }
    public function applicationsIndex()
    {
        $applications = Application::with([
            'scholarship',
            'student',
            'documents',
            'reviewLogs.admin',
            'award.allocations.costCategory',
        ])->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $applications->map(function ($app) {
                return [
                    'id' => $app->id,
                    'status' => $app->status,
                    'submitted_at' => $app->submitted_at,
                    'created_at' => $app->created_at,
                    'scholarship' => [
                        'id' => $app->scholarship->id,
                        'name' => $app->scholarship->name,
                    ],
                    'student' => [
                        'id' => $app->student->id,
                        'name' => $app->student->name ?? 'N/A',
                        'email' => $app->student->email ?? 'N/A',
                    ],
                    'documents' => $app->documents->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'filename' => $doc->filename,
                            'url' => Storage::url($doc->file_path),
                            'uploaded_at' => $doc->uploaded_at,
                        ];
                    }),
                    'review_logs' => $app->reviewLogs->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'admin' => [
                                'id' => $log->admin->id,
                                'name' => $log->admin->name ?? 'N/A',
                            ],
                            'action' => $log->action,
                            'created_at' => $log->created_at,
                        ];
                    }),
                    'award' => $app->award ? [
                        'id' => $app->award->id,
                        'award_amount' => $app->award->award_amount,
                        'award_date' => $app->award->award_date,
                        'allocations' => $app->award->allocations->map(function ($alloc) {
                            return [
                                'id' => $alloc->id,
                                'cost_category' => [
                                    'id' => $alloc->costCategory->id,
                                    'name' => $alloc->costCategory->name,
                                ],
                                'allocated_amount' => $alloc->allocated_amount,
                            ];
                        }),
                    ] : null,
                ];
            }),
            'pagination' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }
    public function applicationsShow($id)
    {
        $application = Application::with([
            'scholarship',
            'student',
            'documents',
            'reviewLogs.admin',
            'award.allocations.costCategory',
        ])->find($id);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $application->id,
                'status' => $application->status,
                'submitted_at' => $application->submitted_at,
                'created_at' => $application->created_at,
                'scholarship' => [
                    'id' => $application->scholarship->id,
                    'name' => $application->scholarship->name,
                    'description' => $application->scholarship->description,
                    'application_deadline' => $application->scholarship->application_deadline,
                ],
                'student' => [
                    'id' => $application->student->id,
                    'name' => $application->student->name ?? 'N/A',
                    'email' => $application->student->email ?? 'N/A',
                ],
                'documents' => $application->documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'filename' => $doc->filename,
                        'url' => Storage::url($doc->file_path),
                        'uploaded_at' => $doc->uploaded_at,
                    ];
                }),
                'review_logs' => $application->reviewLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'admin' => [
                            'id' => $log->admin->id,
                            'name' => $log->admin->name ?? 'N/A',
                        ],
                        'action' => $log->action,
                        'created_at' => $log->created_at,
                    ];
                }),
                'award' => $application->award ? [
                    'id' => $application->award->id,
                    'award_amount' => $application->award->award_amount,
                    'award_date' => $application->award->award_date,
                    'allocations' => $application->award->allocations->map(function ($alloc) {
                        return [
                            'id' => $alloc->id,
                            'cost_category' => [
                                'id' => $alloc->costCategory->id,
                                'name' => $alloc->costCategory->name,
                            ],
                            'allocated_amount' => $alloc->allocated_amount,
                        ];
                    }),
                ] : null,
            ],
        ]);
    }
    public function applicationsReview(Request $request, $id)
    {
        $application = Application::find($id);

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approved,rejected',
            'user_id' => 'required|integer|exists:admins,id',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            $application->status = $validated['action'];
            $application->submitted_at = $application->submitted_at ?? now();
            $application->save();

            ReviewLog::create([
                'application_id' => $application->id,
                'admin_id' => $validated['user_id'],
                'action' => $validated['action'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Application has been {$validated['action']}.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to review application: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function createAward(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'award_amount' => 'required|numeric|min:0',
            'award_date' => 'required|date',
            'allocations' => 'required|array|min:1',
            'allocations.*.cost_category_id' => 'required|integer|exists:cost_categories,id',
            'allocations.*.allocated_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $application = Application::findOrFail($id);

            if ($application->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved applications can receive awards',
                ], 400);
            }

            DB::beginTransaction();
            $award = ApplicationAward::create([
                'application_id' => $application->id,
                'award_amount' => $request->award_amount,
                'award_date' => $request->award_date,
            ]);
            $totalAllocated = collect($request->allocations)->sum('allocated_amount');
            if ($totalAllocated > $award->award_amount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Total allocated amount cannot exceed award amount',
                ], 422);
            }
            foreach ($request->allocations as $alloc) {
                ApplicationAwardAllocation::create([
                    'award_id' => $award->id,
                    'cost_category_id' => $alloc['cost_category_id'],
                    'allocated_amount' => $alloc['allocated_amount'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Award and allocations created successfully',
                'data' => $award->load('allocations.costCategory'),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create award: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function createDisbursementSchedules(Request $request, $awardId)
    {
        $award = ApplicationAward::with('application.scholarship', 'allocations')->find($awardId);
        if (!$award) {
            return response()->json([
                'success' => false,
                'message' => 'Award not found',
            ], 404);
        }
        $allocationsByCategory = $award->allocations->keyBy('cost_category_id');

        $validator = Validator::make($request->all(), [
            'schedules' => 'required|array|min:1',
            'schedules.*.cost_category_id' => 'required|integer|exists:cost_categories,id',
            'schedules.*.scheduled_date' => 'required|date',
            'schedules.*.scheduled_amount' => 'required|numeric|min:0',
            'schedules.*.description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($validator->validated()['schedules'] as $item) {
                $costCategoryId = $item['cost_category_id'];
                if (!isset($allocationsByCategory[$costCategoryId])) {
                    throw new \Exception("No allocation found for cost_category_id {$costCategoryId}");
                }

                DisbursementSchedule::create([
                    'scholarship_id' => $award->application->scholarship->id,
                    'cost_category_id' => $costCategoryId,
                    'scheduled_date' => $item['scheduled_date'],
                    'scheduled_amount' => $item['scheduled_amount'],
                    'description' => $item['description'] ?? null,
                    'status' => 'pending',
                    'award_allocation_id' => $allocationsByCategory[$costCategoryId]->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Disbursement schedules created successfully',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create disbursement schedules: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function payDisbursement(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'idempotency_key' => 'required|string|max:255|unique:disbursements,idempotency_key',
            'amount' => 'required|numeric|min:0',
            'disbursement_date' => 'required|date',
            'reference_number' => 'required|string|max:100|unique:disbursements,reference_number',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $disbursementSchedule = DisbursementSchedule::findOrFail($id);
            $existingDisbursement = Disbursement::where('reference_number', $request->reference_number)->first();
            if ($existingDisbursement) {
                return response()->json([
                    'success' => true,
                    'message' => 'Disbursement already paid',
                    'data' => $existingDisbursement,
                ]);
            }

            DB::beginTransaction();
            $disbursement = Disbursement::create([
                'award_allocation_id' => $disbursementSchedule->awardAllocation->id,
                'amount' => $request->amount,
                'disbursement_date' => $request->disbursement_date,
                'reference_number' => $request->reference_number,
                'idempotency_key' => $request->idempotency_key,
            ]);
            $disbursementSchedule->update(['status' => 'completed']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Disbursement marked as paid',
                'data' => $disbursement,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to pay disbursement: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function filterDisbursements(Request $request)
    {
        $query = Disbursement::query();
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('disbursement_date', [$request->from_date, $request->to_date]);
        }
        if ($request->has('cost_category_id')) {
            $query->whereHas('awardAllocation', function ($q) use ($request) {
                $q->where('cost_category_id', $request->cost_category_id);
            });
        }
        $disbursements = $query->with(['awardAllocation.costCategory'])->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $disbursements,
        ]);
    }
    public function verifyReceipt(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:verified,rejected',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        $user = $request->user()->id;
        try {
            $receipt = Receipt::findOrFail($id);
            $receipt->update([
                'status' => $request->status,
                'remarks' => $request->remarks,
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Receipt {$request->status} successfully",
                'data' => $receipt,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify receipt: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function scholarshipReport($id)
    {
        try {
            $scholarship = Scholarship::with([
                'budgets.costCategory',
                'applications.awards.allocations',
                'applications.awards.allocations.disbursements.receipts'
            ])->findOrFail($id);

            $report = [
                'scholarship' => $scholarship->name,
                'budgets' => $scholarship->budgets->map(function ($budget) {
                    return [
                        'cost_category' => $budget->costCategory->name,
                        'planned' => $budget->planned_amount,
                        'committed' => $budget->committed_amount,
                        'paid' => $budget->disbursed_amount,
                        'receipted' => $budget->receipted_amount,
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch scholarship report: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function awardReport($awardId)
    {
        try {
            $award = ApplicationAward::with([
                'allocations.costCategory',
                'allocations.disbursements.receipts'
            ])->findOrFail($awardId);

            $report = [
                'award_id' => $award->id,
                'award_amount' => $award->award_amount,
                'award_date' => $award->award_date,
                'allocations' => $award->allocations->map(function ($alloc) {
                    return [
                        'cost_category' => $alloc->costCategory->name,
                        'allocated_amount' => $alloc->allocated_amount,
                        'disbursements' => $alloc->disbursements->map(function ($disbursement) {
                            return [
                                'amount' => $disbursement->amount,
                                'date' => $disbursement->disbursement_date,
                                'receipts' => $disbursement->receipts->map(function ($receipt) {
                                    return [
                                        'filename' => $receipt->filename,
                                        'amount' => $receipt->amount,
                                        'uploaded_at' => $receipt->uploaded_at,
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch award report: ' . $e->getMessage(),
            ], 500);
        }
    }


}
