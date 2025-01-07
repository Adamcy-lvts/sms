<x-filament-panels::page>
   <!-- Profile avatar header -->
   <div class="mx-auto">
       <x-filament::avatar 
           src="{{ $student->profile_picture_url }}" 
           alt="{{ $student->full_name }}" 
           size="w-48 h-48" 
       />
   </div>

   <!-- Main tabs container with state management -->
   <div x-data="{ 
       activeTab: new URLSearchParams(window.location.search).get('tab') || 
                  localStorage.getItem('studentActiveTab') || 
                  'profile'
   }"
   x-init="$watch('activeTab', value => {
       // Update localStorage
       localStorage.setItem('studentActiveTab', value);
       
       // Update URL without page refresh
       const url = new URL(window.location);
       url.searchParams.set('tab', value);
       window.history.pushState({}, '', url);
   })">

       <!-- Tab navigation -->
       <x-filament::tabs class="mb-5">
           <x-filament::tabs.item 
               alpine-active="activeTab === 'profile'" 
               x-on:click="activeTab = 'profile'"
               :active="request()->get('tab') === 'profile'">
               Profile
           </x-filament::tabs.item>

           <x-filament::tabs.item 
               alpine-active="activeTab === 'academics'" 
               x-on:click="activeTab = 'academics'"
               :active="request()->get('tab') === 'academics'">
               Academics
           </x-filament::tabs.item>


           <x-filament::tabs.item 
               alpine-active="activeTab === 'payments'" 
               x-on:click="activeTab = 'payments'"
               :active="request()->get('tab') === 'payments'">
               Payments
           </x-filament::tabs.item>
       </x-filament::tabs>

       <!-- Profile tab content -->
       <div x-show.transition="activeTab === 'profile'" class="mb-3">
           {{ $this->profileInfolist }}
       </div>

       <!-- Academics tab content -->
       <div x-show.transition="activeTab === 'academics'">
           <div class="space-y-6">
               <!-- Student stats component -->
               <div class="mb-3">
                   @livewire(\App\Livewire\StudentStats::class, [
                       'student' => $this->student,
                   ])
               </div>

               <!-- Top subjects infolist -->
               <div class="mb-3">
                   {{ $this->topSubjectsInfolist }}
               </div>

               <!-- Report card history table -->
               @livewire(\App\Livewire\StudentReportCardHistoryTable::class)
           </div>
       </div>


       <!-- Payments tab content -->
       <div x-show.transition="activeTab === 'payments'">
           <div class="space-y-6">
               <!-- Payment stats -->
               <div class="mb-3">
                   @livewire(\App\Livewire\StudentPaymentStats::class, [
                       'student' => $this->student,
                   ])
               </div>

               <!-- Payment history -->
               <div>
                   @livewire(\App\Livewire\StudentPaymentHistoryTable::class, [
                       'student' => $this->student,
                   ])
               </div>
           </div>
       </div>

   </div>

   <!-- Additional functionality -->
   <script>
       // Handle browser back/forward navigation
       window.addEventListener('popstate', () => {
           const tab = new URLSearchParams(window.location.search).get('tab') || 'profile';
           const event = new CustomEvent('tab-changed', { detail: tab });
           window.dispatchEvent(event);
       });

       // Listen for tab changes from other parts of the app
       window.addEventListener('tab-changed', (e) => {
           const tab = e.detail;
           Alpine.$data.activeTab = tab;
       });
   </script>

</x-filament-panels::page>