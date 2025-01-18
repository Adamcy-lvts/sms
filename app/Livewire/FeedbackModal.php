<?php

namespace App\Http\Livewire;

use App\Models\Feedback;
use App\Models\FeedbackResponse;
use App\Models\FeedbackTracking;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class FeedbackModal extends Component
{
    public $showModal = false;
    public ?Feedback $feedback = null;
    public $responses = [];
    public $rating;
    public $comment;

    protected $listeners = [
        'echo:feedback,open-modal' => 'handleOpenModal'
    ];

    public function handleOpenModal($event)
    {
        $this->feedback = Feedback::find($event['feedbackId']);
        $this->showModal = true;
    }

    // Validation rules
    protected $rules = [
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string|max:1000',
        'responses.*.answer' => 'required',
    ];

    // Custom validation messages
    protected $messages = [
        'rating.required' => 'Please provide an overall rating',
        'responses.*.answer.required' => 'Please answer all questions',
    ];

    public function mount()
    {
        $this->feedback = (object)[
            'title' => 'Share Your Feedback',
            'description' => 'Help us improve your experience',
            'questions' => collect([]), // Initialize as empty collection
            'id' => null
        ];
        $this->dispatch('init-feedback-monitor');
    }

    // Event listeners
    public function getListeners()
    {
        return [
            'userActive' => 'handleUserActivity',
            'checkFeedback' => 'checkAndShowFeedback'
        ];
    }

    // Handle user activity events
    protected function handleUserActivity()
    {
        if (!session()->has('feedback_shown')) {
            $this->activityTimer++;

            if ($this->activityTimer >= 3) {
                $this->checkAndShowFeedback();
            }
        }
    }

    // Check if feedback should be shown
    protected function checkAndShowFeedback()
    {
        if (session()->has('feedback_shown')) {
            return;
        }

        $this->feedback = Feedback::query()
            ->active()
            ->forSchool(auth()->user()->school->id)
            ->whereDoesntHave('tracking', function ($query) {
                $query->where('school_id', auth()->user()->school->id)
                    ->where('last_shown_at', '>=', now()->subDays(
                        Feedback::first()->frequency_days ?? 7
                    ));
            })
            ->first();

        if ($this->feedback) {
            $this->showModal = true;
            session(['feedback_shown' => now()]);
        }
    }

    // Handle feedback submission
    public function submit()
    {
        try {
            DB::beginTransaction();

            // Validate the responses
            $this->validate();

            // Create feedback response
            $response = FeedbackResponse::create([
                'feedback_id' => $this->feedback->id,
                'school_id' => auth()->user()->school->id,
                'user_id' => auth()->id(),
                'responses' => $this->responses,
                'comments' => $this->comment,
                'rating' => $this->rating,
            ]);

            // Create tracking record
            FeedbackTracking::create([
                'feedback_id' => $this->feedback->id,
                'school_id' => auth()->user()->school->id,
                'last_shown_at' => now(),
            ]);

            DB::commit();

            // Reset form and close modal
            $this->resetForm();

            // Show success notification
            Notification::make()
                ->success()
                ->title('Thank you for your feedback!')
                ->duration(5000)
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Feedback submission failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'school_id' => auth()->user()->school->id
            ]);

            Notification::make()
                ->danger()
                ->title('Something went wrong!')
                ->body('Please try again later.')
                ->duration(5000)
                ->send();
        }
    }

    // Handle skipping feedback
    public function skip()
    {
        try {
            // Create tracking record
            FeedbackTracking::create([
                'feedback_id' => $this->feedback->id,
                'school_id' => auth()->user()->school->id,
                'last_shown_at' => now(),
            ]);

            $this->resetForm();

            Notification::make()
                ->info()
                ->title('Feedback skipped')
                ->duration(3000)
                ->send();
        } catch (\Exception $e) {
            Log::error('Feedback skip failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'school_id' => auth()->user()->school->id
            ]);
        }
    }

    // Cancel feedback
    public function cancel()
    {
        $this->resetForm();
    }

    // Confirm skip action
    public function confirmSkip()
    {
        return Notification::make()
            ->warning()
            ->title('Skip Feedback?')
            ->body('Are you sure you want to skip providing feedback?')
            ->actions([
                Action::make('skip')
                    ->label('Yes, Skip')
                    ->color('gray')
                    ->button()
                    ->close()
                    ->action('skip'),
                Action::make('cancel')
                    ->label('No, Continue')
                    ->close(),
            ])
            ->persistent()
            ->send();
    }

    // Reset form and modal state
    protected function resetForm()
    {
        $this->reset(['responses', 'rating', 'comment']);
        $this->showModal = false;
    }

    // Render the component
    public function render()
    {
        return view('livewire.feedback-modal');
    }
}
