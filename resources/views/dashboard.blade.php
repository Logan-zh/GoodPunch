<x-app-layout>
    @push('styles')
        @vite(['resources/css/punch.css'])
    @endpush

    <div class="punch-container">
        <div class="max-w-4xl mx-auto">
            <!-- Header Status -->
            <div class="text-center mb-8">
                @if($lastPunch && $lastPunch->type === 'in')
                    <div class="status-badge status-in">
                        <span class="dot" style="background-color: var(--punch-in)"></span>
                        Currently On Duty
                    </div>
                @else
                    <div class="status-badge status-out">
                        <span class="dot" style="background-color: var(--punch-out)"></span>
                        Currently Off Duty
                    </div>
                @endif
                <h1 class="text-2xl font-bold text-slate-400">Welcome back, {{ Auth::user()->name }}</h1>
            </div>

            <!-- Main Clock Card -->
            <div class="glass-card text-center">
                <div class="text-slate-400 font-medium mb-2">{{ now()->format('F j, Y') }}</div>
                <div id="clock" class="clock-display">00:00:00</div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
                    <form action="{{ route('punch.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="in">
                        <button type="submit" class="btn-punch btn-in {{ ($lastPunch && $lastPunch->type === 'in') ? 'opacity-50 cursor-not-allowed' : '' }}" {{ ($lastPunch && $lastPunch->type === 'in') ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Punch In
                        </button>
                    </form>

                    <form action="{{ route('punch.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="out">
                        <button type="submit" class="btn-punch btn-out {{ (!$lastPunch || $lastPunch->type === 'out') ? 'opacity-50 cursor-not-allowed' : '' }}" {{ (!$lastPunch || $lastPunch->type === 'out') ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Punch Out
                        </button>
                    </form>
                </div>

                @if(session('success'))
                    <div class="mt-4 p-3 bg-emerald-500/20 border border-emerald-500/50 text-emerald-400 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mt-4 p-3 bg-red-500/20 border border-red-500/50 text-red-400 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            <!-- History Card -->
            <div class="glass-card">
                <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Recent Activity
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($punches as $punch)
                                <tr class="history-row">
                                    <td class="font-medium text-slate-300">{{ $punch->punch_time->format('Y-m-d') }}</td>
                                    <td class="text-slate-400">{{ $punch->punch_time->format('H:i:s') }}</td>
                                    <td>
                                        <span class="px-2 py-1 rounded text-xs font-bold uppercase {{ $punch->type === 'in' ? 'text-emerald-400 bg-emerald-400/10' : 'text-red-400 bg-red-400/10' }}">
                                            {{ $punch->type }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-8 text-slate-500">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-6">
                    {{ $punches->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</x-app-layout>
