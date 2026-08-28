<div class="space-y-4">
    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ __('The following changes will be reverted:') }}
    </div>

    @if(isset($modified) && count($modified))
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-2 px-3 font-medium text-gray-600 dark:text-gray-400">{{ __('Attribute') }}</th>
                    <th class="text-left py-2 px-3 font-medium text-red-600 dark:text-red-400">{{ __('Current') }}</th>
                    <th class="text-left py-2 px-3 font-medium text-green-600 dark:text-green-400">{{ __('Restoring to') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($modified as $attribute => $values)
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <td class="py-2 px-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $attribute }}</td>
                        <td class="py-2 px-3 font-mono text-xs text-red-600 dark:text-red-400">
                            @if($values['new'] === '***')
                                ***
                            @elseif(is_string($values['new']) && str_starts_with($values['new'], '__encrypted:'))
                                [encrypted]
                            @else
                                {{ is_array($values['new']) ? json_encode($values['new']) : $values['new'] }}
                            @endif
                        </td>
                        <td class="py-2 px-3 font-mono text-xs text-green-600 dark:text-green-400">
                            @if($values['old'] === '***')
                                ***
                            @elseif(is_string($values['old']) && str_starts_with($values['old'], '__encrypted:'))
                                [encrypted]
                            @else
                                {{ is_array($values['old']) ? json_encode($values['old']) : $values['old'] }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No attribute changes found.') }}</p>
    @endif

    @if(isset($event))
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
            {{ __('Event') }}: <span class="font-mono">{{ $event }}</span>
            @if(isset($createdAt))
                &middot; {{ $createdAt }}
            @endif
        </div>
    @endif
</div>
