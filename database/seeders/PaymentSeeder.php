<?php


namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\School;
use App\Models\Status;
use App\Models\Student;
use App\Models\Payment;
use App\Models\PaymentPlan;
use App\Models\PaymentType;
use App\Models\PaymentMethod;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    // Define payment schedules and amounts
    protected $feeStructure = [
        'School Fee' => [
            'JSS' => [
                'amount' => 45000,
                'termly' => true,
            ],
            'SSS' => [
                'amount' => 55000,
                'termly' => true,
            ]
        ],
        'Development Levy' => [
            'amount' => 10000,
            'termly' => true,
        ],
        'Uniform' => [
            'amount' => 15000,
            'termly' => false,
            'one_time' => true
        ],
        'Books' => [
            'JSS' => [
                'amount' => 25000,
                'termly' => false,
                'one_time' => true
            ],
            'SSS' => [
                'amount' => 35000,
                'termly' => false,
                'one_time' => true
            ]
        ],
        'Laboratory Fee' => [
            'JSS' => [
                'amount' => 5000,
                'termly' => true
            ],
            'SSS' => [
                'amount' => 10000,
                'termly' => true
            ]
        ],
        'Sports Wear' => [
            'amount' => 8000,
            'termly' => false,
            'one_time' => true
        ],
        'ID Card' => [
            'amount' => 2000,
            'termly' => false,
            'one_time' => true
        ],
        'Library Fee' => [
            'amount' => 5000,
            'termly' => true
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $school = School::where('slug', 'khalil-integrated-academy')->first();

            // Get payment methods (they should already exist from PaymentMethodTableSeeder)
            $paymentMethods = PaymentMethod::where('school_id', $school->id)->get();
            $paymentPlans = PaymentPlan::where('school_id', $school->id)->get();

            // Validate that payment methods exist
            if ($paymentMethods->isEmpty()) {
                throw new \Exception('No payment methods found. Please run PaymentMethodTableSeeder first.');
            }

            // Get statuses
            $statuses = [
                'pending' => Status::where('name', 'pending')->first(),
                'partial' => Status::where('name', 'partial')->first(),
                'paid' => Status::where('name', 'paid')->first()
            ];

            // Get all students
            $students = Student::where('school_id', $school->id)->get();

            // Get last 3 academic sessions
            $sessions = $school->academicSessions()
                ->with('terms')
                ->orderBy('start_date', 'desc')
                ->take(3)
                ->get();

            foreach ($students as $student) {
                $classLevel = $this->getClassLevel($student->classRoom->name);
                $paymentPlan = $paymentPlans->where('class_level', $classLevel)->first();

                if (!$paymentPlan) continue;

                foreach ($sessions as $session) {
                    $this->createSessionPayments(
                        $student,
                        $session,
                        $paymentPlan,
                        $paymentMethods->random()
                    );
                }
            }
        });
    }

    protected function createSessionPayments($student, $session, $paymentPlan, $paymentMethod): void
    {
        // Determine if paying full session or term by term (70% chance of full session)
        $isFullSession = rand(1, 100) <= 70;

        if ($isFullSession) {
            $this->createFullSessionPayment($student, $session, $paymentPlan, $paymentMethod);
        } else {
            foreach ($session->terms as $term) {
                $this->createTermPayment($student, $session, $term, $paymentPlan, $paymentMethod);
            }
        }
    }

    protected function createFullSessionPayment($student, $session, $paymentPlan, $paymentMethod): void
    {
        $payment = Payment::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'class_room_id' => $student->class_room_id,
            'payment_method_id' => $paymentMethod->id,
            'academic_session_id' => $session->id,
            'status_id' => Status::where('name', 'paid')->first()->id,
            'payment_plan_type' => 'session',
            'payment_category' => 'tuition',
            'is_tuition' => true,
            'reference' => 'PAY-' . strtoupper(uniqid()),
            'payer_name' => $student->admission->guardian_name,
            'payer_phone_number' => $student->admission->guardian_phone_number,
            'amount' => $paymentPlan->session_amount,
            'deposit' => $paymentPlan->session_amount,
            'balance' => 0,
            'paid_at' => now()->subDays(rand(1, 30)),
            'created_by' => 1,
        ]);

        $payment->paymentItems()->create([
            'payment_type_id' => $paymentPlan->payment_type_id,
            'amount' => $paymentPlan->session_amount,
            'deposit' => $paymentPlan->session_amount,
            'balance' => 0,
            'is_tuition' => true
        ]);

        $this->createPaymentHistory($payment, $paymentPlan->session_amount, $paymentMethod);
    }

    protected function createTermPayment($student, $session, $term, $paymentPlan, $paymentMethod): void
    {
        $isPaid = rand(1, 100) <= 80; // 80% chance of being paid
        $deposit = $isPaid ? $paymentPlan->term_amount : 0;

        $payment = Payment::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'class_room_id' => $student->class_room_id,
            'payment_method_id' => $paymentMethod->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'status_id' => Status::where('name', $isPaid ? 'paid' : 'pending')->first()->id,
            'payment_plan_type' => 'term',
            'payment_category' => 'tuition',
            'is_tuition' => true,
            'reference' => 'PAY-' . strtoupper(uniqid()),
            'payer_name' => $student->admission->guardian_name,
            'payer_phone_number' => $student->admission->guardian_phone_number,
            'amount' => $paymentPlan->term_amount,
            'deposit' => $deposit,
            'balance' => $paymentPlan->term_amount - $deposit,
            'paid_at' => $isPaid ? now()->subDays(rand(1, 30)) : null,
            'created_by' => 1,
        ]);

        $payment->paymentItems()->create([
            'payment_type_id' => $paymentPlan->payment_type_id,
            'amount' => $paymentPlan->term_amount,
            'deposit' => $deposit,
            'balance' => $paymentPlan->term_amount - $deposit,
            'is_tuition' => true
        ]);

        if ($isPaid) {
            $this->createPaymentHistory($payment, $deposit, $paymentMethod);
        }
    }

    protected function getClassLevel(string $className): string
    {
        if (str_contains(strtoupper($className), 'NURSERY')) return 'nursery';
        if (str_contains(strtoupper($className), 'PRIMARY')) return 'primary';
        return 'secondary';
    }

    protected function createPaymentHistory($payment, $amount, $paymentMethod): void
    {
        PaymentHistory::create([
            'payment_id' => $payment->id,
            'amount' => $amount,
            'payment_method_id' => $paymentMethod->id,
            'remark' => 'Payment recorded',
            'created_by' => 1,
        ]);
    }

    protected function createPaymentsForStudent($student, $sessions, $statuses, $paymentMethods)
    {
        $level = str_contains(strtoupper($student->classRoom->name), 'JSS') ? 'JSS' : 'SSS';

        foreach ($sessions as $session) {
            foreach ($session->terms as $term) {
                // Create regular term payments
                $this->createTermPayment(
                    $student,
                    $session,
                    $term,
                    $level,
                    $paymentMethods->random(),
                    $statuses,
                );

                // Create one-time payments in first term only
                if ($term->name === 'First Term') {
                    $this->createOneTimePayments(
                        $student,
                        $session,
                        $term,
                        $level,
                        $paymentMethods->random(),
                        $statuses['paid']
                    );
                }
            }
        }
    }

    // protected function createTermPayment($student, $session, $term, $level, $paymentMethod, $statuses)
    // {
    //     $termStart = Carbon::parse($term->start_date);
    //     $isPastTerm = $termStart->isPast();

    //     // Calculate total amount for termly fees
    //     $termlyFees = $this->calculateTermlyFees($level);

    //     if ($isPastTerm) {
    //         // Past terms - create full payments with 90% probability
    //         if (rand(1, 100) <= 90) {
    //             $payment = $this->createFullPayment($student, $session, $term, $termlyFees, $paymentMethod, $statuses);
    //         } else {
    //             // 10% chance of partial/pending payment for realism
    //             $this->createPartialPayment($student, $session, $term, $termlyFees, $paymentMethod, $statuses);
    //         }
    //     } else {
    //         // Current/future terms - more variation in payment status
    //         $rand = rand(1, 100);
    //         if ($rand <= 60) {
    //             // 60% full payment
    //             $payment = $this->createFullPayment($student, $session, $term, $termlyFees, $paymentMethod, $statuses);
    //         } elseif ($rand <= 85) {
    //             // 25% partial payment
    //             $this->createPartialPayment($student, $session, $term, $termlyFees, $paymentMethod, $statuses);
    //         } else {
    //             // 15% pending payment
    //             $this->createPendingPayment($student, $session, $term, $termlyFees, $paymentMethod, $statuses);
    //         }
    //     }
    // }

    protected function createFullPayment($student, $session, $term, $amount, $paymentMethod, $statuses): Payment
    {
        $payment = Payment::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'class_room_id' => $student->class_room_id,
            'payment_method_id' => $paymentMethod->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'status_id' => $statuses['paid']->id,
            'reference' => 'PAY-' . strtoupper(uniqid()),
            'payer_name' => $student->admission->guardian_name,
            'payer_phone_number' => $student->admission->guardian_phone_number,
            'amount' => $amount,
            'deposit' => $amount,
            'balance' => 0,
            'meta_data' => $this->getPaymentMeta('full', 'termly'),
            'remark' => 'Full term payment',
            'due_date' => Carbon::parse($term->start_date)->addDays(30),
            'paid_at' => Carbon::parse($term->start_date)->addDays(rand(1, 14)),
            'created_by' => 1,
        ]);

        $this->createPaymentItems($payment, $student, 1.0); // 100% paid
        $this->createPaymentHistory($payment, $amount, $paymentMethod);

        return $payment;
    }

    protected function createPartialPayment($student, $session, $term, $amount, $paymentMethod, $statuses)
    {
        $paidPercent = rand(40, 70) / 100;
        $deposit = $amount * $paidPercent;

        $payment = Payment::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'class_room_id' => $student->class_room_id,
            'payment_method_id' => $paymentMethod->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'status_id' => $statuses['partial']->id,
            'reference' => 'PAY-' . strtoupper(uniqid()),
            'payer_name' => $student->admission->guardian_name,
            'payer_phone_number' => $student->admission->guardian_phone_number,
            'amount' => $amount,
            'deposit' => $deposit,
            'balance' => $amount - $deposit,
            'meta_data' => $this->getPaymentMeta('partial', 'termly', ['installment' => 1]),
            'remark' => 'First installment payment',
            'due_date' => Carbon::parse($term->start_date)->addDays(30),
            'paid_at' => now()->subDays(rand(1, 30)),
            'created_by' => 1,
        ]);

        $this->createPaymentItems($payment, $student, $paidPercent);
        $this->createPaymentHistory($payment, $deposit, $paymentMethod);

        // 30% chance of balance payment
        if (rand(1, 100) <= 30) {
            $this->createBalancePayment($payment, $paymentMethod, $statuses['paid']);
        }

        return $payment;
    }

    protected function createBalancePayment($originalPayment, $paymentMethod, $status)
    {
        $balancePayment = Payment::create([
            'school_id' => $originalPayment->school_id,
            'student_id' => $originalPayment->student_id,
            'class_room_id' => $originalPayment->class_room_id,
            'payment_method_id' => $paymentMethod->id,
            'academic_session_id' => $originalPayment->academic_session_id,
            'term_id' => $originalPayment->term_id,
            'original_payment_id' => $originalPayment->id,
            'is_balance_payment' => true,
            'status_id' => $status->id,
            'reference' => 'BAL-' . strtoupper(uniqid()),
            'payer_name' => $originalPayment->payer_name,
            'payer_phone_number' => $originalPayment->payer_phone_number,
            'amount' => $originalPayment->balance,
            'deposit' => $originalPayment->balance,
            'balance' => 0,
            'meta_data' => [
                'original_payment_ref' => $originalPayment->reference,
                'payment_type' => 'balance',
            ],
            'remark' => 'Balance payment',
            'paid_at' => now()->subDays(rand(1, 15)),
            'created_by' => 1,
        ]);

        // Update original payment
        $originalPayment->update([
            'balance' => 0,
            'status_id' => $status->id,
        ]);

        $this->createBalancePaymentItems($balancePayment, $originalPayment);
        $this->createPaymentHistory($balancePayment, $balancePayment->amount, $paymentMethod);
    }

    protected function createPaymentItems(Payment $payment, Student $student, float $paidPercent)
    {
        $level = str_contains(strtoupper($student->classRoom->name), 'JSS') ? 'JSS' : 'SSS';

        foreach ($this->feeStructure as $feeName => $feeData) {
            if ($this->isTermlyFee($feeData)) {
                $amount = $this->getFeeAmount($feeData, $level);
                $deposit = $amount * $paidPercent;

                $paymentType = PaymentType::where('name', $feeName)
                    ->where('school_id', $payment->school_id)
                    ->first();

                if ($paymentType) {
                    $payment->paymentItems()->create([
                        'payment_type_id' => $paymentType->id,
                        'amount' => $amount,
                        'deposit' => $deposit,
                        'balance' => $amount - $deposit
                    ]);
                }
            }
        }
    }

    // protected function createPaymentHistory($payment, $amount, $paymentMethod): void
    // {
    //     PaymentHistory::create([
    //         'payment_id' => $payment->id,
    //         'amount' => $amount,
    //         'payment_method_id' => $paymentMethod->id,
    //         'remark' => $payment->remark,
    //         'created_by' => 1,
    //     ]);
    // }

    protected function calculateTermlyFees($level): float
    {
        $total = 0;
        foreach ($this->feeStructure as $feeName => $feeData) {
            if ($this->isTermlyFee($feeData)) {
                $total += $this->getFeeAmount($feeData, $level);
            }
        }
        return $total;
    }

    protected function calculateOneTimeFees($level): float
    {
        $total = 0;
        foreach ($this->feeStructure as $feeName => $feeData) {
            if ($this->isOneTimeFee($feeData)) {
                $total += $this->getFeeAmount($feeData, $level);
            }
        }
        return $total;
    }

    protected function getPaymentMeta($type, $level, array $additional = []): array
    {
        return array_merge([
            'payment_type' => $type,
            'level' => $level,
            'academic_year' => '2023/2024',
            'currency' => 'NGN',
        ], $additional);
    }

    protected function isTermlyFee($feeData): bool
    {
        return ($feeData['termly'] ?? false) === true;
    }

    protected function isOneTimeFee($feeData): bool
    {
        return ($feeData['one_time'] ?? false) === true;
    }

    protected function getFeeAmount($feeData, $level): float
    {
        return isset($feeData[$level]) ? $feeData[$level]['amount'] : $feeData['amount'];
    }

    protected function createOneTimePayments($student, $session, $term, $level, $paymentMethod, $status): void
    {
        $oneTimeFees = $this->calculateOneTimeFees($level);
        $paymentDate = Carbon::parse($term->start_date)->addDays(rand(1, 14));

        $payment = Payment::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'class_room_id' => $student->class_room_id,
            'payment_method_id' => $paymentMethod->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'status_id' => $status->id,
            'reference' => 'ONE-' . strtoupper(uniqid()),
            'payer_name' => $student->admission->guardian_name,
            'payer_phone_number' => $student->admission->guardian_phone_number,
            'amount' => $oneTimeFees,
            'deposit' => $oneTimeFees,
            'balance' => 0,
            'meta_data' => $this->getPaymentMeta('one_time', $level),
            'remark' => 'One-time fees payment',
            'paid_at' => $paymentDate,
            'due_date' => $paymentDate->copy()->addDays(7),
            'created_by' => 1,
        ]);

        $this->createOneTimePaymentItems($payment, $level);
        $this->createPaymentHistory($payment, $oneTimeFees, $paymentMethod);
    }

    protected function createPendingPayment($student, $session, $term, $amount, $paymentMethod, $statuses): Payment
    {
        $payment = Payment::create([
            'school_id' => $student->school_id,
            'student_id' => $student->id,
            'class_room_id' => $student->class_room_id,
            'payment_method_id' => $paymentMethod->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'status_id' => $statuses['pending']->id,
            'reference' => 'PAY-' . strtoupper(uniqid()),
            'payer_name' => $student->admission->guardian_name,
            'payer_phone_number' => $student->admission->guardian_phone_number,
            'amount' => $amount,
            'deposit' => 0,
            'balance' => $amount,
            'meta_data' => $this->getPaymentMeta('pending', 'termly'),
            'remark' => 'Pending term payment',
            'due_date' => Carbon::parse($term->start_date)->addDays(30),
            'created_by' => 1,
        ]);

        $this->createPaymentItems($payment, $student, 0); // 0% paid
        return $payment;
    }

    protected function createBalancePaymentItems(Payment $balancePayment, Payment $originalPayment): void
    {
        foreach ($originalPayment->paymentItems as $item) {
            if ($item->balance > 0) {
                $balancePayment->paymentItems()->create([
                    'payment_type_id' => $item->payment_type_id,
                    'amount' => $item->balance,
                    'deposit' => $item->balance,
                    'balance' => 0
                ]);

                $item->update([
                    'balance' => 0
                ]);
            }
        }
    }

    protected function createOneTimePaymentItems(Payment $payment, string $level): void
    {
        foreach ($this->feeStructure as $feeName => $feeData) {
            if ($this->isOneTimeFee($feeData)) {
                $amount = $this->getFeeAmount($feeData, $level);

                $paymentType = PaymentType::where('name', $feeName)
                    ->where('school_id', $payment->school_id)
                    ->first();

                if ($paymentType) {
                    $payment->paymentItems()->create([
                        'payment_type_id' => $paymentType->id,
                        'amount' => $amount,
                        'deposit' => $amount,
                        'balance' => 0
                    ]);
                }
            }
        }
    }
}
