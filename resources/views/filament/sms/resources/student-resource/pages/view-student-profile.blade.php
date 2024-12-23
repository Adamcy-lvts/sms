<x-filament-panels::page>

    <div class="mx-auto">
        <x-filament::avatar src="{{ $student->profile_picture_url }}" alt="{{ $student->full_name }}" size="w-48 h-48" />
    </div>

    <div x-data="{ activeTab: 'profile' }">



        <x-filament::tabs class="mb-5">
            <x-filament::tabs.item alpine-active="activeTab === 'profile'" x-on:click="activeTab = 'profile'">
                Profile
            </x-filament::tabs.item>

            <x-filament::tabs.item alpine-active="activeTab === 'academics'" x-on:click="activeTab = 'academics'">
                Academics
            </x-filament::tabs.item>

            <x-filament::tabs.item alpine-active="activeTab === 'activities'" x-on:click="activeTab = 'activities'">
                Activities
            </x-filament::tabs.item>

            <x-filament::tabs.item alpine-active="activeTab === 'awards'" x-on:click="activeTab = 'awards'">
                Payments
            </x-filament::tabs.item>



        </x-filament::tabs>

        <div x-show.transition="activeTab === 'profile'" class="mb-3">
            <!-- Profile content here -->
            {{ $this->profileInfolist }}
        </div>

        <div x-show.transition="activeTab === 'academics'">
            <!-- Academics content here -->
            <div class=" ">

                <div>
                    @livewire(\App\Livewire\StudentStats::class, [
                        'student' => $this->student,
                    ])
                </div>

                <div>
                    {{ $this->academicInfolist }}
                </div>


                
            </div>
        </div>

        <div x-show.transition="activeTab === 'activities'">
            <!-- Activities content here -->
            <div class="p-6 bg-gray-100">
                <h2 class="text-2xl font-bold mb-6">Student Activities</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold">Clubs</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <ul class="space-y-2">
                            <li class="flex items-center">
                                <span class="w-4 h-4 bg-green-500 rounded-full mr-2"></span>
                                <span>Debate Club - President</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-4 h-4 bg-blue-500 rounded-full mr-2"></span>
                                <span>Science Club - Member</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-4 h-4 bg-yellow-500 rounded-full mr-2"></span>
                                <span>Chess Club - Treasurer</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold">Sports</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                        </div>
                        <ul class="space-y-2">
                            <li class="flex items-center">
                                <span class="w-4 h-4 bg-red-500 rounded-full mr-2"></span>
                                <span>Basketball Team - Point Guard</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-4 h-4 bg-purple-500 rounded-full mr-2"></span>
                                <span>Track and Field - 100m Sprinter</span>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold">Volunteer Work</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <ul class="space-y-2">
                            <li class="flex items-center">
                                <span class="w-4 h-4 bg-indigo-500 rounded-full mr-2"></span>
                                <span>Local Food Bank - 50 hours</span>
                            </li>
                            <li class="flex items-center">
                                <span class="w-4 h-4 bg-pink-500 rounded-full mr-2"></span>
                                <span>Animal Shelter - 30 hours</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <h3 class="text-lg font-semibold mb-4">Upcoming Events</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center">
                            <div class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">
                                Mar
                                15</div>
                            <span>Inter-School Debate Competition</span>
                        </li>
                        <li class="flex items-center">
                            <div class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">
                                Apr
                                02</div>
                            <span>Science Fair</span>
                        </li>
                        <li class="flex items-center">
                            <div class="bg-yellow-100 text-yellow-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">
                                Apr 20</div>
                            <span>Regional Basketball Tournament</span>
                        </li>
                    </ul>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Activity Hours</h3>
                        <div class="h-64 bg-gray-200 rounded flex items-center justify-center">
                            <p class="text-gray-500">Chart placeholder: Pie chart showing distribution of activity
                                hours</p>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Recent Achievements</h3>
                        <ul class="space-y-2">
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-yellow-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                <span>Best Speaker Award - Regional Debate Competition</span>
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                <span>MVP - District Basketball Tournament</span>
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-500"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                <span>Outstanding Volunteer Award - Local Food Bank</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div x-show.transition="activeTab === 'awards'">
            <!-- Awards content here -->
            <div class="p-6 bg-gray-100">
                <h2 class="text-2xl font-bold mb-6">Payments & Financial Information</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-500">Total Tuition</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="text-2xl font-bold">$12,000</div>
                        <p class="text-xs text-gray-500">Per academic year</p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-500">Paid to Date</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="text-2xl font-bold">$8,000</div>
                        <p class="text-xs text-gray-500">66% of total tuition</p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-500">Remaining Balance</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="text-2xl font-bold">$4,000</div>
                        <p class="text-xs text-gray-500">Due by May 31, 2024</p>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-500">Scholarships</h3>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-500"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
                            </svg>
                        </div>
                        <div class="text-2xl font-bold">$2,000</div>
                        <p class="text-xs text-gray-500">Academic Merit Scholarship</p>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <h3 class="text-lg font-semibold mb-4">Payment History</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Description</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Amount</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-09-01</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Fall Semester
                                        Tuition
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$6,000</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Paid</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2024-01-15</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Spring Semester
                                        Tuition</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$6,000</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Partial</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Payment Plan</h3>
                        <ul class="space-y-2">
                            <li class="flex items-center justify-between">
                                <span>Monthly Payment:</span>
                                <span class="font-bold">$1,000</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span>Next Due Date:</span>
                                <span class="font-bold">April 15, 2024</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span>Remaining Payments:</span>
                                <span class="font-bold">4</span>
                            </li>
                        </ul>
                        <button
                            class="mt-4 w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                            Make a Payment
                        </button>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <h3 class="text-lg font-semibold mb-4">Financial Aid</h3>
                        <ul class="space-y-2">
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-500"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>FAFSA Submitted</span>
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-500"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Scholarship Applied</span>
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-yellow-500"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Work-Study: Pending</span>
                            </li>
                        </ul>
                        <button
                            class="mt-4 w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                            View Financial Aid Details
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-filament-panels::page>
