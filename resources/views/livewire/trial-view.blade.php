<div>
    {{-- {{ dd($this->daysLeftInTrial)}} --}}
    @if ($latestSubscription->is_on_trial ?? null)
        @if ($this->daysLeftInTrial > 0)
            <span class="text-xs text-green-500 dark:text-green-400 ml-2">
                You are currently on trial for 30 days, you have {{ $this->daysLeftInTrial }} days left.
            </span>
        @else
            <span class="text-xs text-red-500 dark:text-red-400 ml-2">
                Your trial period has ended.
            </span>
        @endif

    @endif
</div>
