<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        <form wire:submit="processPayment">
            {{ $this->form }}
            <input type="hidden" name="refrence" wire:model="reference" value="{{ Paystack::genTranxRef() }}">
            <button type="submit"
                class="w-full my-2 grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-green-600 text-white hover:bg-green-500 focus-visible:ring-green-500/50 dark:bg-green-500 dark:hover:bg-green-400 dark:focus-visible:ring-green-400/50 fi-ac-action fi-ac-btn-action">
                Pay
            </button>
        </form>
    </div>
</x-filament-panels::page>