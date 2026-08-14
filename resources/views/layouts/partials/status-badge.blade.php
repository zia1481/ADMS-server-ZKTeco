@php
$map = [
    'active'    => ['success', 'bi-check-circle'],
    'inactive'  => ['secondary', 'bi-dash-circle'],
    'disabled'  => ['danger', 'bi-slash-circle'],
    'under_review' => ['warning', 'bi-clock-history'],
    'registered'=> ['success', 'bi-check-circle'],
    'pending'   => ['warning', 'bi-hourglass-split'],
    'blocked'   => ['danger', 'bi-slash-circle'],
    'detected'  => ['warning', 'bi-exclamation-triangle'],
    'assigned'  => ['success', 'bi-check-circle'],
    'ignored'   => ['secondary', 'bi-dash-circle'],
    'in'        => ['info', 'bi-box-arrow-in-right'],
    'out'       => ['secondary', 'bi-box-arrow-right'],
    'present'   => ['success', 'bi-check-circle'],
    'absent'    => ['danger', 'bi-x-circle'],
    'late'      => ['warning', 'bi-clock'],
    'early leave' => ['info', 'bi-clock'],
    'late & early leave' => ['danger', 'bi-clock-history'],
    'on time'   => ['success', 'bi-check-circle'],
    'no schedule' => ['secondary', 'bi-calendar-x'],
    'approved'  => ['success', 'bi-check-circle'],
    'rejected'  => ['danger', 'bi-x-circle'],
    'on leave'  => ['info', 'bi-palette'],
];
$key = strtolower(trim($status ?? ''));
$entry = $map[$key] ?? ['secondary', 'bi-circle'];
$labelText = $label ?? ucwords(str_replace(['_', '-'], ' ', $key));
@endphp
<span class="badge text-bg-{{ $entry[0] }} badge-soft">
    <i class="bi {{ $entry[1] }}"></i>{{ $labelText }}
</span>
