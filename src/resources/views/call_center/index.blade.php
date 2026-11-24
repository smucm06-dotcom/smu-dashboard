@extends('layouts.app')

@section('title', 'Call Center')

{{-- The Call Center menu: one card per call center campaign the user can open (CallCenterController). --}}

@section('content')

<div class="cc-header">
    <h3>Call center campaigns</h3>
    <p>Each campaign is a round of feedback calls about one programme. Open one to see what the people called said.</p>
</div>

<div class="cc-grid">
    @forelse ($cards as $card)
        @php
            $campaign = $card['initiative'];
            $summary = $card['summary'];
        @endphp
        <a href="{{ route('initiatives.show', $campaign) }}" class="cc-card">
            <div class="cc-icon"><i data-lucide="headset" aria-hidden="true"></i></div>

            <div class="cc-details">
                <h4>{{ $campaign->name }}</h4>
                <span class="cc-dept">{{ $campaign->department?->name }}</span>

                @if ($summary !== null && $summary['calls'] > 0)
                    <span class="cc-figures">
                        <b>{{ number_format($summary['calls']) }}</b> calls
                        &middot; <b>{{ number_format($summary['people']) }}</b> {{ $summary['peopleLabel'] ?? 'people' }}
                        @if ($summary['lastCall'])
                            &middot; latest call {{ \Illuminate\Support\Carbon::parse($summary['lastCall'])->format('j M Y') }}
                        @endif
                    </span>
                @else
                    <span class="cc-figures">No calls uploaded yet</span>
                @endif
            </div>

            <div class="cc-arrow"><i data-lucide="arrow-up-right" aria-hidden="true"></i></div>
        </a>
    @empty
        <div class="cc-empty">
            <div class="cc-empty-icon"><i data-lucide="headset" aria-hidden="true"></i></div>
            <h4>No call center campaigns yet</h4>
            <p>Campaigns appear here once they are set up.</p>
        </div>
    @endforelse
</div>

@endsection

@push('styles')
<style>
    .cc-header { margin-bottom: 24px; }
    .cc-header h3 { margin: 0 0 7px; color: #0B4534; font-size: 21px; font-weight: 700; line-height: 1.4; overflow-wrap: anywhere; }
    .cc-header p { margin: 0; color: #687D73; font-size: 12px; line-height: 1.6; }

    .cc-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }

    .cc-card { display: flex; align-items: center; gap: 15px; min-height: 118px; padding: 20px; background: #ffffff; border: 1px solid #D9E7DE; border-radius: 14px; text-decoration: none; transition: border-color .2s, transform .2s, box-shadow .2s; }
    .cc-card:hover { border-color: #0B6046; transform: translateY(-3px); box-shadow: 0 8px 24px rgba(11, 69, 52, .08); }
    .cc-card:focus-visible { outline: 2px solid #CAA24A; outline-offset: 3px; }

    .cc-icon { display: flex; align-items: center; justify-content: center; flex-shrink: 0; width: 50px; height: 50px; background: #28754F; border-radius: 12px; color: #ffffff; transition: background .2s; }
    .cc-icon svg { width: 24px; height: 24px; stroke: currentColor; }
    .cc-card:hover .cc-icon { background: #0B6046; }

    .cc-details { flex: 1; min-width: 0; display: grid; gap: 4px; }
    .cc-details h4 { margin: 0 0 3px; font-size: 15px; font-weight: 600; line-height: 1.5; color: #173F32; overflow-wrap: anywhere; }
    .cc-dept { font-size: 11px; font-weight: 600; color: #0B6046; overflow-wrap: anywhere; }
    .cc-figures { font-size: 11px; color: #687D73; line-height: 1.5; overflow-wrap: anywhere; }
    .cc-figures b { color: #173F32; font-variant-numeric: tabular-nums; }

    .cc-arrow { flex-shrink: 0; color: #687D73; transition: color .2s, transform .2s; }
    .cc-arrow svg { width: 17px; height: 17px; }
    .cc-card:hover .cc-arrow { color: #0B6046; transform: translate(2px, -2px); }

    .cc-empty { grid-column: 1 / -1; padding: 55px 25px; text-align: center; background: #ffffff; border: 1px dashed #D9E7DE; border-radius: 14px; }
    .cc-empty-icon { display: flex; align-items: center; justify-content: center; width: 65px; height: 65px; margin: 0 auto 18px; border-radius: 18px; background: #28754F; color: #ffffff; }
    .cc-empty-icon svg { width: 30px; height: 30px; }
    .cc-empty h4 { margin: 0 0 9px; font-size: 15px; color: #0B4534; }
    .cc-empty p { margin: 0 auto; max-width: 350px; font-size: 12px; line-height: 1.7; color: #687D73; }

    @media (max-width: 1200px) { .cc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 650px) {
        .cc-grid { grid-template-columns: 1fr; }
        .cc-card { min-height: 100px; padding: 16px; gap: 12px; }
        .cc-icon { width: 44px; height: 44px; }
        .cc-icon svg { width: 21px; height: 21px; }
        .cc-details h4 { font-size: 14px; }
    }
</style>
{{-- The banner's cards are numbered 1, 2, 3, 4, as on the Departments page. --}}
@include('departments.partials.topbar-numbers')
@endpush
