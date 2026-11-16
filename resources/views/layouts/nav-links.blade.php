@foreach($links as [$route, $label, $matches, $icon])
    <a href="{{ route($route) }}" aria-label="{{ $label }}" title="{{ $label }}"
       @if(request()->routeIs(...$matches)) aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
        </svg>
        <span class="ops-nav-label">{{ $label }}</span>
        @if($route === 'admin.approvals.index' && ($pendingApprovals ?? 0) > 0)
            <span class="ops-nav-count ops-nav-label" aria-label="{{ $pendingApprovals }} menunggu">{{ $pendingApprovals }}</span>
        @endif
    </a>
@endforeach
