<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Feedback;
use Filament\Facades\Filament;
use App\Models\FeedbackResponse;
use App\Models\FeedbackTracking;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Services\LegalDocumentService;

class Footer extends Component
{
    public $showModal = false;
    public $type = 'feedback';
    public $title = '';
    public $description = '';
    public $responses = [];
    public ?Feedback $activeFeedback = null;

    protected $rules = [
        'type' => 'required|in:feedback,bug,feature',
        'title' => 'required|min:3|max:100',
        'description' => 'required|min:10|max:1000'
    ];

    protected $listeners = ['open-feedback' => 'openFeedbackModal'];

    public function mount()
    {
        $this->resetForm();
        // Try to find an active feedback campaign for the current school
        $this->loadActiveFeedback();
    }

    public function openFeedbackModal()
    {
        $this->loadActiveFeedback(); // Refresh active feedback
        $this->dispatch('open-modal', id: 'feedback-modal');
    }

    public function setRating($questionIndex, $rating)
    {
        // Set the rating for the specific question
        $this->responses[$questionIndex]['answer'] = $rating;
        
        // Initialize empty comment if it doesn't exist
        if (!isset($this->responses[$questionIndex]['comment'])) {
            $this->responses[$questionIndex]['comment'] = '';
        }
    }

    protected function loadActiveFeedback()
    {
        $school = Filament::getTenant();

        if (!$school) return;

        // Debug the query
        $query = Feedback::query()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->where(function ($query) use ($school) {
                $query->whereNull('target_schools')
                    ->orWhereJsonContains('target_schools', (string) $school->id); // Cast to string for JSON comparison
            })
            ->whereDoesntHave('tracking', function ($query) use ($school) {
                $query->where('school_id', $school->id)
                    ->where('last_shown_at', '>=', now()->subDays(7));
            })
            ->latest();

        // Log the query for debugging
        \Illuminate\Support\Facades\Log::info('Feedback Query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'school_id' => $school->id,
            'now' => now()->toDateTimeString(),
        ]);

        // Execute and get feedback
        $this->activeFeedback = $query->first();

        // Log the actual feedback data
        if ($this->activeFeedback) {
            \Illuminate\Support\Facades\Log::info('Found Feedback Data', [
                'id' => $this->activeFeedback->id,
                'title' => $this->activeFeedback->title,
                'target_schools' => $this->activeFeedback->target_schools,
                'is_active' => $this->activeFeedback->is_active,
                'start_date' => $this->activeFeedback->start_date,
                'end_date' => $this->activeFeedback->end_date,
            ]);
        }

        // Initialize responses if we have feedback with better structure checking
        if ($this->activeFeedback && !empty($this->activeFeedback->questions)) {
            $this->responses = collect($this->activeFeedback->questions)
                ->map(function ($question, $index) {
                    return [
                        'answer' => null,
                        'comment' => '',
                        'type' => $question['type'] ?? 'text',
                        'question' => $question['question'] ?? "Question " . ($index + 1),
                        'options' => $question['options'] ?? []
                    ];
                })
                ->toArray();

            // Log questions structure for debugging
            \Illuminate\Support\Facades\Log::info('Questions Structure', [
                'questions' => $this->activeFeedback->questions,
                'formatted_responses' => $this->responses
            ]);
        }
    }

    public function submit()
    {
        try {
            DB::beginTransaction();

            if ($this->activeFeedback) {
                // Validate responses for active feedback
                $this->validateResponses();
                
                // Calculate average rating and collect comments
                $ratingSum = 0;
                $ratingCount = 0;
                $allComments = [];

                // Format responses for storage and collect ratings/comments
                $formattedResponses = collect($this->responses)->map(function ($response, $index) use (&$ratingSum, &$ratingCount, &$allComments) {
                    if (isset($response['type']) && $response['type'] === 'rating') {
                        if (isset($response['answer'])) {
                            $ratingSum += $response['answer'];
                            $ratingCount++;
                        }
                        if (!empty($response['comment'])) {
                            $allComments[] = $response['comment'];
                        }
                    }

                    return [
                        'question' => $this->activeFeedback->questions[$index]['question'] ?? "Question " . ($index + 1),
                        'type' => $this->activeFeedback->questions[$index]['type'] ?? 'text',
                        'answer' => $response['answer'] ?? null,
                        'comment' => $response['comment'] ?? null,
                    ];
                })->toArray();

                // Calculate average rating
                $averageRating = $ratingCount > 0 ? round($ratingSum / $ratingCount) : null;

                // Create feedback response
                FeedbackResponse::create([
                    'feedback_id' => $this->activeFeedback->id,
                    'school_id' => Filament::getTenant()->id,
                    'user_id' => auth()->id(),
                    'responses' => $formattedResponses,
                    'rating' => $averageRating,
                    'comments' => !empty($allComments) ? implode("\n", $allComments) : null,
                ]);

                // Update tracking
                FeedbackTracking::updateOrCreate(
                    [
                        'feedback_id' => $this->activeFeedback->id,
                        'school_id' => Filament::getTenant()->id,
                    ],
                    ['last_shown_at' => now()]
                );
            } else {
                // Validate general feedback
                $this->validate([
                    'type' => 'required|in:feedback,bug,feature',
                    'title' => 'required|min:3|max:100',
                    'description' => 'required|min:10|max:1000'
                ]);

                // Format general feedback as responses
                $formattedResponses = [
                    [
                        'type' => 'select',
                        'question' => 'Feedback Type',
                        'answer' => $this->type
                    ],
                    [
                        'type' => 'text',
                        'question' => 'Title',
                        'answer' => $this->title
                    ],
                    [
                        'type' => 'textarea',
                        'question' => 'Description',
                        'answer' => $this->description
                    ]
                ];

                // Create general feedback response with all required fields
                FeedbackResponse::create([
                    'feedback_id' => null, // or create a default feedback record for general feedback
                    'school_id' => Filament::getTenant()->id,
                    'user_id' => auth()->id(),
                    'responses' => $formattedResponses,
                    'rating' => 0, // default rating for general feedback
                    'comments' => null,
                  
                ]);
            }

            DB::commit();

            $this->resetForm();
            $this->dispatch('close-modal', id: 'feedback-modal');

            Notification::make()
                ->success()
                ->title('Thank you for your feedback!')
                ->body('Your response has been recorded.')
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Enhanced error logging
            \Illuminate\Support\Facades\Log::error('Feedback submission error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'feedback_id' => $this->activeFeedback?->id,
                'responses' => $this->responses,
                'type' => $this->type,
                'school_id' => Filament::getTenant()->id,
                'user_id' => auth()->id()
            ]);

            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Failed to submit feedback. Please try again.')
                ->send();
        }
    }

    protected function validateResponses()
    {
        $rules = [];
        $messages = [];
        
        if ($this->activeFeedback && $this->activeFeedback->questions) {
            foreach ($this->activeFeedback->questions as $index => $question) {
                $rules["responses.{$index}.answer"] = 'required';
                $messages["responses.{$index}.answer.required"] = "Please answer question " . ($index + 1);
            }
        }

        $this->validate($rules, $messages);
    }

    public function resetForm()
    {
        $this->reset(['type', 'title', 'description', 'responses', 'showModal']);
        $this->loadActiveFeedback(); // Reload active feedback
    }

    public function render()
    {
        $currentYear = date('Y');
        $tenant = Filament::getTenant();
        $legalDocs = new LegalDocumentService();

        return view('livewire.footer', [
            'currentYear' => $currentYear,
            'tenant' => $tenant,
            'legalDocs' => $legalDocs,
        ]);
    }
}
