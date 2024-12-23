<x-filament-panels::page>

    <div class="mx-auto">
        <x-filament::avatar src="{{ $staff->profile_picture_url }}" alt="{{ $staff->full_name }}" size="w-48 h-48" />
    </div>


    <div x-data="{ activeTab: 'profile', isTeacher: @json($staff->teacher()->exists()) }">
        <x-filament::tabs class="mb-5">
            <x-filament::tabs.item alpine-active="activeTab === 'profile'" x-on:click="activeTab = 'profile'">
                Profile
            </x-filament::tabs.item>

            <x-filament::tabs.item alpine-active="activeTab === 'academics'" x-on:click="activeTab = 'academics'">
                Academics
            </x-filament::tabs.item>

            <x-filament::tabs.item alpine-active="activeTab === 'qualifications'"
                x-on:click="activeTab = 'qualifications'">
                Qualifications
            </x-filament::tabs.item>

            <x-filament::tabs.item alpine-active="activeTab === 'salary'" x-on:click="activeTab = 'salary'">
                Salary
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div x-show.transition="activeTab === 'profile'" class="mb-3">
            <!-- Profile content here -->

            {{ $this->profileInfolist }}
        </div>

        <div x-show.transition="activeTab === 'academics'"
            class="bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 rounded-xl p-6 transition-all duration-300">
            @if ($staff->teacher)
                <!-- Academics content for teachers -->
                <div class="space-y-4">
                    <h3 class="text-2xl font-semibold text-gray-800 dark:text-white">Academic Information</h3>
                    <div>
                        <h4 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Subjects:</h4>
                        @if ($staff->teacher->subjects->isNotEmpty())
                            <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
                                @foreach ($staff->teacher->subjects as $subject)
                                    <li>{{ $subject->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-600 dark:text-gray-400 italic">No subjects assigned.</p>
                        @endif
                    </div>
                    <div>
                        <h4 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Classes:</h4>
                        @if ($staff->teacher->classRooms->isNotEmpty())
                            <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
                                @foreach ($staff->teacher->classRooms as $class)
                                    <li>{{ $class->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-600 dark:text-gray-400 italic">No classes assigned.</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <h3 class="text-2xl font-semibold text-gray-800 dark:text-white">Non-Academic Staff Information</h3>
                    <p class="text-gray-600 dark:text-gray-400">This staff member is not part of the academic teaching
                        staff.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h4 class="text-lg font-medium text-gray-700 dark:text-gray-300">Role:</h4>
                            <p class="text-gray-600 dark:text-gray-400">{{ $staff->role }}</p>
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-gray-700 dark:text-gray-300">Department:</h4>
                            <p class="text-gray-600 dark:text-gray-400">{{ $staff->department }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div x-show.transition="activeTab === 'qualifications'">
            <!-- Qualifications content here -->

            @if ($staff->qualifications->isNotEmpty())
                {{ $this->qualificationsInfolist }}
            @else
                <p>No qualifications recorded.</p>
            @endif
        </div>

        <div x-show.transition="activeTab === 'salary'">
            <!-- Salary content here -->

            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
