<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <h3 class="text-lg font-semibold text-slate-950">Organizations · Properties · Managers</h3>
        <span class="text-xs uppercase tracking-wide text-slate-500">Control Plane</span>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="py-2 pr-4">Organization</th>
                    <th class="py-2 pr-4">Properties</th>
                    <th class="py-2 pr-4">Managers</th>
                    <th class="py-2">Users</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($organizations as $organization)
                    <tr>
                        <td class="py-2 pr-4 font-medium text-slate-900">{{ $organization->name }}</td>
                        <td class="py-2 pr-4 text-slate-700">{{ $organization->properties_count }}</td>
                        <td class="py-2 pr-4 text-slate-700">{{ $organization->managers_count }}</td>
                        <td class="py-2 text-slate-700">{{ $organization->users_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-slate-500">No organizations available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
