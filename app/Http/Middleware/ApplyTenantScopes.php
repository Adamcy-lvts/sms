<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Term;
use App\Models\School;
use App\Models\Template;
use App\Models\Admission;
use App\Models\SubsPayment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use App\Models\ActivityType;
use App\Models\AssessmentType;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSummary;
use App\Models\BehavioralTrait;
use App\Models\Book;
use App\Models\ClassRoom;
use App\Models\Designation;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Feature;
use App\Models\GradingScale;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Payment;
use App\Models\PaymentHistory;
use App\Models\PaymentItem;
use App\Models\PaymentMethod;
use App\Models\PaymentPlan;
use App\Models\PaymentType;
use App\Models\Permission;
use App\Models\Qualification;
use App\Models\ReportAssessmentColumn;
use App\Models\ReportCommentSection;
use App\Models\ReportGradingScale;
use App\Models\ReportSection;
use App\Models\ReportTemplate;
use App\Models\Role;
use App\Models\SalaryPayment;
use App\Models\SchoolSettings;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\StudentTermActivity;
use App\Models\StudentTermComment;
use App\Models\StudentTermTrait;
use App\Models\Subject;
use App\Models\SubjectAssessment;
use App\Models\SubscriptionReceipt;
use App\Models\Teacher;
use App\Models\StatusChange;
use App\Models\StudentMovement;
use App\Models\StudentPaymentPlan;
use App\Models\TemplateVariable;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenantScopes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        SubsPayment::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Subscription::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        SubscriptionReceipt::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        AcademicSession::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Term::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Admission::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Template::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        TemplateVariable::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        ClassRoom::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Subject::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Student::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Payment::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        PaymentMethod::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        PaymentType::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Designation::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Staff::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Teacher::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        SalaryPayment::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Qualification::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        AssessmentType::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        GradingScale::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );


        StudentGrade::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        SchoolSettings::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        // PaymentItem::addGlobalScope(
        //     fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        // );

        PaymentHistory::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        ReportAssessmentColumn::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        ReportCommentSection::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        ReportGradingScale::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        ReportTemplate::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        ReportSection::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        StudentTermTrait::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        StudentTermComment::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        StudentTermActivity::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        AttendanceRecord::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        AttendanceSummary::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Book::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        BehavioralTrait::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        ActivityType::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Expense::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        ExpenseCategory::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        StatusChange::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        StudentMovement::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Role::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        PaymentPlan::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        StudentPaymentPlan::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        Inventory::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        InventoryTransaction::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );

        




        return $next($request);
    }
}
