@php
    $relatedActions = $this->getRelatedActions($getRecord());
@endphp

@if($relatedActions->isNotEmpty())
    <div class="space-y-2">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
            Actions on the same resource within 1 hour:
        </p>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Timestamp
                        </th>
                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Action
                        </th>
                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Organization
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($relatedActions as $action)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $action->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if(str_contains(strtolower($action->action), 'create'))
                                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif(str_contains(strtolower($action->action), 'update') || str_contains(strtolower($action->action), 'edit'))
                                        bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif(str_contains(strtolower($action->action), 'delete') || str_contains(strtolower($action->action), 'remove'))
                                        bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif(str_contains(strtolower($action->action), 'suspend'))
                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @else
                                        bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif
                                ">
                                    {{ $action->action }}
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                @if($action->user)
                                    {{ $action->user->name }}
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">{{ __('app.common.na') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                @if($action->organization)
                                    <a href="{{ route('filament.admin.resources.organizations.view', ['record' => $action->organization_id]) }}" 
                                       class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                        {{ $action->organization->name }}
                                    </a>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">{{ __('app.common.na') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400">
        No related actions found within the time window.
    </p>
@endif
