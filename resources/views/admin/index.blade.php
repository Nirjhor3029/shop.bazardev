<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Super admin: all stores</h2></x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto px-4 sm:px-6 space-y-4">
        @if (session('status'))<div class="bg-green-50 text-green-700 p-3 rounded">{{ session('status') }}</div>@endif
        <a href="{{ route('admin.plans.index') }}" class="text-sm text-indigo-600">Manage plans &rarr;</a>
        <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-gray-500"><tr><th class="p-3">Store</th><th class="p-3">Owner</th><th class="p-3">Status</th><th class="p-3">Plan</th><th class="p-3">Products</th><th class="p-3">Orders</th><th class="p-3">Sales</th><th class="p-3"></th></tr></thead>
                <tbody class="divide-y">
                @foreach ($stores as $s)
                    <tr>
                        <td class="p-3"><a class="text-indigo-600" href="{{ $s->url() }}" target="_blank" rel="noopener">{{ $s->slug }}</a><br><span class="text-gray-500">{{ $s->name }}</span></td>
                        <td class="p-3">{{ $s->user->email }}</td>
                        <td class="p-3">{{ $s->status }}</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.stores.plan', $s) }}" class="flex items-center gap-1">@csrf
                                <select name="plan_id" class="text-xs border-gray-300 rounded-md" onchange="this.form.submit()">
                                    @foreach ($plans as $p)<option value="{{ $p->id }}" @selected($s->plan_id === $p->id)>{{ $p->name }}</option>@endforeach
                                </select>
                            </form>
                            @if ($s->plan_expires_at)<span class="text-xs text-gray-400">till {{ $s->plan_expires_at->format('Y-m-d') }}</span>@endif
                        </td>
                        <td class="p-3">{{ $products[$s->id] ?? 0 }}</td>
                        <td class="p-3">{{ $orders[$s->id]->c ?? 0 }}</td>
                        <td class="p-3">{{ number_format($orders[$s->id]->t ?? 0) }}</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.stores.status', $s) }}">@csrf
                                @if ($s->status === 'suspended')<input type="hidden" name="status" value="draft"><button class="text-green-700">Unsuspend</button>
                                @else<input type="hidden" name="status" value="suspended"><button class="text-red-600">Suspend</button>@endif
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $stores->links() }}
    </div></div>
</x-app-layout>
