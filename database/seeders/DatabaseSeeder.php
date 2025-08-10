<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;
use App\Models\Student;
use App\Models\Admin;
use App\Models\Scholarship;
use App\Models\CostCategory;
use App\Models\ScholarshipBudget;
use App\Models\Application;
use App\Models\Document;
use App\Models\ReviewLog;
use App\Models\ApplicationAward;
use App\Models\ApplicationAwardAllocation;
use App\Models\DisbursementSchedule;
use App\Models\Disbursement;
use App\Models\Receipt;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            // DB::transaction(function () {
            Role::truncate();
            User::truncate();
            Student::truncate();
            Admin::truncate();
            Scholarship::truncate();
            CostCategory::truncate();
            ScholarshipBudget::truncate();
            Application::truncate();
            Document::truncate();
            ReviewLog::truncate();
            ApplicationAward::truncate();
            ApplicationAwardAllocation::truncate();
            DisbursementSchedule::truncate();
            Disbursement::truncate();
            Receipt::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $adminRole = Role::create(['name' => 'admin']);
            $studentRole = Role::create(['name' => 'student']);
            $adminUser1 = User::create([
                'name' => 'Sameer Danish',
                'email' => 'sameer@example.com',
                'password' => 'loopcraft',
                'role_id' => $adminRole->id,
            ]);

            $adminUser2 = User::create([
                'name' => 'Mohamed Ali',
                'email' => 'mohamed@example.com',
                'password' => 'loopcraft',
                'role_id' => $adminRole->id,
            ]);

            Admin::create(['user_id' => $adminUser1->id, 'name' => $adminUser1->name, 'emp_no' => '123']);
            Admin::create(['user_id' => $adminUser2->id, 'name' => $adminUser2->name, 'emp_no' => '1245']);
            $studentUsers = [];
            for ($i = 1; $i <= 10; $i++) {
                $studentUser = User::create([
                    'name' => "Sameer Danish $i",
                    'email' => "sameerdanish12345$i@example.com",
                    'password' => 'loopcraft',
                    'role_id' => $studentRole->id,
                ]);

                $studentUsers[] = Student::create([
                    'user_id' => $studentUser->id,
                    'regno' => 'STU' . str_pad($i, 5, '0', STR_PAD_LEFT),
                    'name' => $studentUser->name,
                    'dob' => now()->subYears(20)->subMonths($i),
                    'guardian' => "Guardian $i",
                ]);
            }
            $costCategories = [
                ['name' => 'Tuition', 'description' => 'Tuition fees'],
                ['name' => 'Stipend', 'description' => 'Monthly stipend'],
                ['name' => 'Books', 'description' => 'Books and supplies'],
                ['name' => 'Travel', 'description' => 'Travel expenses'],
                ['name' => 'Accommodation', 'description' => 'Housing costs'],
            ];

            foreach ($costCategories as $category) {
                CostCategory::create($category);
            }
            $scholarships = [
                [
                    'name' => 'Merit Scholarship',
                    'description' => 'For students with excellent academic records',
                    'application_deadline' => now()->addMonths(3),
                    'award_amount' => 5000,
                    'total_budget' => 50000,
                    'max_awards' => 10,
                    'status' => 'active',
                    'eligibility_criteria' => 'Minimum GPA of 3.5',
                    'created_by' => $adminUser1->id,
                ],
                [
                    'name' => 'Need-Based Scholarship',
                    'description' => 'For students with financial need',
                    'application_deadline' => now()->addMonths(2),
                    'award_amount' => 3000,
                    'total_budget' => 30000,
                    'max_awards' => 10,
                    'status' => 'active',
                    'eligibility_criteria' => 'Family income below $50,000',
                    'created_by' => $adminUser2->id,
                ],
                [
                    'name' => 'Research Scholarship',
                    'description' => 'For students engaged in research projects',
                    'application_deadline' => now()->addMonths(4),
                    'award_amount' => 4000,
                    'total_budget' => 20000,
                    'max_awards' => 5,
                    'status' => 'active',
                    'eligibility_criteria' => 'Research proposal required',
                    'created_by' => $adminUser1->id,
                ],
            ];

            $createdScholarships = [];
            foreach ($scholarships as $scholarship) {
                $createdScholarships[] = Scholarship::create($scholarship);
            }
            foreach ($createdScholarships as $scholarship) {
                foreach (CostCategory::all() as $category) {
                    ScholarshipBudget::create([
                        'scholarship_id' => $scholarship->id,
                        'cost_category_id' => $category->id,
                        'planned_amount' => rand(1000, 5000),
                        'committed_amount' => 0,
                        'disbursed_amount' => 0,
                        'receipted_amount' => 0,
                    ]);
                }
            }
            $applicationStatuses = ['submitted', 'approved', 'rejected'];
            $applications = [];

            foreach ($studentUsers as $index => $student) {
                foreach ($createdScholarships as $scholarship) {
                    $status = $applicationStatuses[array_rand($applicationStatuses)];

                    $applications[] = Application::create([
                        'scholarship_id' => $scholarship->id,
                        'student_id' => $student->id,
                        'status' => $status,
                        'submitted_at' => now()->subDays(rand(1, 30)),
                    ]);
                }
            }
            foreach ($applications as $application) {
                if (rand(0, 1)) {
                    Document::create([
                        'application_id' => $application->id,
                        'filename' => 'document_' . $application->id . '.pdf',
                        'file_path' => 'application_documents/doc_' . $application->id . '.pdf',
                        'uploaded_at' => now(),
                    ]);
                }
            }
            foreach ($applications as $application) {
                if (in_array($application->status, ['approved', 'rejected'])) {
                    ReviewLog::create([
                        'application_id' => $application->id,
                        'admin_id' => rand(1, 2),
                        'action' => $application->status,
                        'created_at' => $application->submitted_at->addDays(rand(1, 7)),
                    ]);
                }
            }
            $approvedApplications = Application::where('status', 'approved')->get();
            foreach ($approvedApplications as $application) {
                $award = ApplicationAward::create([
                    'application_id' => $application->id,
                    'award_amount' => $application->scholarship->award_amount,
                    'award_date' => Carbon::parse($application->submitted_at)->addDays(rand(7, 14)),
                ]);
                $categories = CostCategory::inRandomOrder()->limit(rand(2, 4))->get();
                $totalAllocated = 0;
                foreach ($categories as $index => $category) {
                    $amount = $index === $categories->count() - 1
                        ? $award->award_amount - $totalAllocated
                        : rand(500, $award->award_amount - $totalAllocated - 500);

                    $allocation = ApplicationAwardAllocation::create([
                        'award_id' => $award->id,
                        'cost_category_id' => $category->id,
                        'allocated_amount' => $amount,
                    ]);

                    $totalAllocated += $amount;
                    $numSchedules = rand(1, 3);
                    $remainingAmount = $amount;

                    for ($i = 0; $i < $numSchedules; $i++) {
                        $scheduleAmount = $i === $numSchedules - 1
                            ? $remainingAmount
                            : rand(100, $remainingAmount - 100);

                        DisbursementSchedule::create([
                            'scholarship_id' => $application->scholarship_id,
                            'cost_category_id' => $category->id,
                            'scheduled_date' => $award->award_date->addMonths($i + 1),
                            'scheduled_amount' => $scheduleAmount,
                            'description' => "Disbursement " . ($i + 1) . " of $numSchedules",
                            'status' => $i === 0 ? 'pending' : 'ready',
                            'award_allocation_id' => $allocation->id,
                        ]);

                        $remainingAmount -= $scheduleAmount;
                    }
                    $schedules = DisbursementSchedule::where('award_allocation_id', $allocation->id)
                        ->orderBy('scheduled_date')
                        ->get();

                    foreach ($schedules as $schedule) {
                        if (rand(0, 1)) {
                            $disbursement = Disbursement::create([
                                'award_allocation_id' => $allocation->id,
                                'amount' => $schedule->scheduled_amount,
                                'disbursement_date' => $schedule->scheduled_date,
                                'reference_number' => 'DISB' . str_pad($schedule->id, 6, '0', STR_PAD_LEFT),
                                'idempotency_key' => 'IDEMP' . $schedule->id,
                            ]);

                            $schedule->update(['status' => 'completed']);
                            if (rand(0, 1)) {
                                Receipt::create([
                                    'disbursement_id' => $disbursement->id,
                                    'filename' => 'receipt_' . $disbursement->id . '.pdf',
                                    'file_path' => 'receipts/rcpt_' . $disbursement->id . '.pdf',
                                    'amount' => $disbursement->amount,
                                    'uploaded_at' => Carbon::parse($disbursement->disbursement_date)->addDays(rand(1, 7)),

                                ]);
                            }
                        }
                    }
                }
            }
            // });
        } finally {

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

    }
}