@extends('layouts.app')
@section('title', 'Calendar')
@section('content')
@php
    $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $prevMonth = $month - 1 < 1 ? 12 : $month - 1;
    $prevYear  = $month - 1 < 1 ? $year - 1 : $year;
    $nextMonth = $month + 1 > 12 ? 1 : $month + 1;
    $nextYear  = $month + 1 > 12 ? $year + 1 : $year;
    $today     = now()->toDateString();
@endphp
<!-- CALENDAR HEADER & TOGGLES -->
            <div class="card-widget p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold text-dark mb-0 fs-16" id="calendarMonthTitle">{{ $monthNames[$month-1] }} {{ $year }}</h5>
                        <div class="btn-group btn-group-sm ms-2">
                            <a href="{{ route('calendar.index', ['year' => $prevYear, 'month' => $prevMonth]) }}" class="btn btn-light"><i class="feather-chevron-left"></i></a>
                            <a href="{{ route('calendar.index') }}" class="btn btn-light">Today</a>
                            <a href="{{ route('calendar.index', ['year' => $nextYear, 'month' => $nextMonth]) }}" class="btn btn-light"><i class="feather-chevron-right"></i></a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm" id="calendarViewToggle">
                            <button class="btn btn-primary" onclick="switchCalendarView('month', this)">Month</button>
                            <button class="btn btn-outline-primary" onclick="switchCalendarView('week', this)">Week (7 Days)</button>
                            <button class="btn btn-outline-primary" onclick="switchCalendarView('day', this)">Day</button>
                        </div>
                        <button class="btn btn-primary btn-sm px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#scheduleEventModal"><i class="feather-plus me-1"></i> Schedule Appointment</button>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show fs-13 py-2" role="alert">
                    {{ session('success') }} <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- EXPANDED FULL-WIDTH CALENDAR GRID & AGENDA SIDEBAR -->
            <div class="row g-4">
                <div class="col-xl-9 col-lg-8 col-md-12">
                    <div class="card-widget p-3">

                        <!-- 1. MONTH VIEW GRID (fully dynamic) -->
                        <div id="monthViewContainer" class="table-responsive">
                            <table class="table table-bordered align-middle mb-0 calendar-table w-100">
                                <thead>
                                    <tr>
                                        <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @php
                                    $firstDay     = \Carbon\Carbon::createFromDate($year, $month, 1)->dayOfWeek;
                                    $daysInMonth  = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
                                    $daysInPrev   = \Carbon\Carbon::createFromDate($prevYear, $prevMonth, 1)->daysInMonth;
                                    $cellCount    = 0;
                                    $dayNum       = 1;
                                    $nextDay      = 1;
                                    $colors       = ['event-blue','event-green','event-yellow','event-purple','event-red'];
                                @endphp
                                @for ($row = 0; $row < 5; $row++)
                                    <tr>
                                    @for ($col = 0; $col < 7; $col++)
                                        @php $cellCount = $row * 7 + $col; @endphp
                                        @if ($cellCount < $firstDay)
                                            <td class="calendar-cell other-month fw-bold">{{ $daysInPrev - ($firstDay - 1 - $cellCount) }}</td>
                                        @elseif ($dayNum <= $daysInMonth)
                                            @php
                                                $cellDate = \Carbon\Carbon::createFromDate($year, $month, $dayNum)->toDateString();
                                                $isToday  = $cellDate === $today;
                                                $dayEvents = $appointmentsByDay->get($dayNum, collect());
                                                $dayTasks = $tasksByDay->get($dayNum, collect());
                                            @endphp
                                            <td class="calendar-cell {{ $isToday ? 'today' : '' }} fw-bold" data-calendar-date="{{ $cellDate }}">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span>{{ $dayNum }}</span>
                                                    @if($isToday)<span class="badge bg-cyan text-white fs-10 px-1">Today</span>@endif
                                                </div>
                                                @foreach($dayEvents as $ev)
                                                    @php $badgeColor = $ev->color ?? 'event-blue'; @endphp
                                                    <div class="event-badge calendar-popup-trigger"
                                                        data-calendar-popup="appointment"
                                                        data-appointment-id="{{ $ev->id }}"
                                                        data-title="{{ $ev->title ?? '' }}"
                                                        data-time="{{ \Carbon\Carbon::parse($ev->appointment_time)->format('h:i A') }}"
                                                        data-client="{{ $ev->client_name ?? '' }}"
                                                        data-location="{{ $ev->location ?? '' }}"
                                                        data-status="{{ $ev->status ?? '' }}"
                                                        data-notes="{{ $ev->notes ?? '' }}">
                                                        <i class="feather-calendar"></i> {{ \Carbon\Carbon::parse($ev->appointment_time)->format('H:i') }} {{ Str::limit($ev->title, 15) }}
                                                    </div>
                                                @endforeach
                                                @foreach($dayTasks as $task)
                                                    <div class="event-badge {{ $task->status === 'Completed' ? 'event-green' : 'event-purple' }} calendar-popup-trigger"
                                                        data-calendar-popup="task"
                                                        data-task-id="{{ $task->id }}"
                                                        data-title="{{ $task->title }}"
                                                        data-client="{{ $task->client_name ?? '' }}"
                                                        data-due-date="{{ $task->due_date->format('d M Y') }}"
                                                        data-due-time="{{ $task->due_time ? \Carbon\Carbon::parse($task->due_time)->format('h:i A') : '' }}"
                                                        data-status="{{ $task->status }}"
                                                        data-note="{{ $task->notes->first()?->note ?? '' }}">
                                                        <i class="{{ $task->status === 'Completed' ? 'feather-check-circle' : 'feather-check-square' }}"></i> @if($task->due_time) {{ \Carbon\Carbon::parse($task->due_time)->format('H:i') }} @endif {{ Str::limit($task->title, 15) }} @if($task->status === 'Completed') (Done) @endif
                                                    </div>
                                                @endforeach
                                            </td>
                                            @php $dayNum++; @endphp
                                        @else
                                            <td class="calendar-cell other-month fw-bold">{{ $nextDay++ }}</td>
                                        @endif
                                    @endfor
                                    </tr>
                                @endfor
                                </tbody>
                            </table>
                        </div>

                        <!-- 2. WEEK VIEW -->
                        <div id="weekViewContainer" class="table-responsive d-none">
                            @php
                                $weekStart       = now()->startOfWeek(\Carbon\Carbon::MONDAY);
                                $weekEnd         = $weekStart->copy()->addDays(6);
                                $weekAppointments = \App\Models\Appointment::whereBetween('appointment_date', [
                                    $weekStart->toDateString(),
                                    $weekEnd->toDateString(),
                                ])->orderBy('appointment_time')->get()
                                  ->groupBy(fn($a) => $a->appointment_date->toDateString());
                            @endphp
                            <table class="table table-bordered align-middle fs-13 mb-0 w-100">
                                <thead class="bg-light text-center">
                                    <tr>
                                        <th style="width: 85px;">Time</th>
                                        @for($d = 0; $d < 7; $d++)
                                            <th>{{ $weekStart->copy()->addDays($d)->format('D d M') }}</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                @php
                                    $allWeekAppts = $weekAppointments->flatten()->sortBy('appointment_time');
                                    $hasAnyAppts  = $allWeekAppts->isNotEmpty();

                                    // Collect tasks-without-time and tasks-with-time for the week
                                    $weekTasksAllDay = collect();
                                    $weekTasksTimed  = collect();
                                    foreach ($weekTasks as $dateKey => $dayTasks) {
                                        foreach ($dayTasks as $t) {
                                            if ($t->due_time) {
                                                $weekTasksTimed->push($t);
                                            } else {
                                                $weekTasksAllDay->push($t);
                                            }
                                        }
                                    }
                                    $hasAnyTasks = $weekTasksTimed->isNotEmpty() || $weekTasksAllDay->isNotEmpty();
                                    $hasAny = $hasAnyAppts || $hasAnyTasks;
                                @endphp

                                @if($hasAny)
                                    {{-- All-day row for tasks without a due_time --}}
                                    @if($weekTasksAllDay->isNotEmpty())
                                        @php $allDayByDate = $weekTasksAllDay->groupBy(fn($t) => $t->due_date->toDateString()); @endphp
                                        <tr style="min-height: 40px;">
                                            <td class="fw-bold text-muted text-center fs-11">All day</td>
                                            @for($d = 0; $d < 7; $d++)
                                                @php $dayDate = $weekStart->copy()->addDays($d)->toDateString(); @endphp
                                                <td>
                                                    @foreach($allDayByDate->get($dayDate, []) as $t)
                                                        <div class="event-badge {{ $t->status === 'Completed' ? 'event-green' : 'event-purple' }} p-1 px-2 w-100 text-start shadow-sm cursor-pointer mb-1 calendar-popup-trigger"
                                                            data-calendar-popup="task"
                                                            data-task-id="{{ $t->id }}"
                                                            data-title="{{ $t->title }}"
                                                            data-client="{{ $t->client_name ?? '' }}"
                                                            data-due-date="{{ $t->due_date->format('d M Y') }}"
                                                            data-due-time=""
                                                            data-status="{{ $t->status }}"
                                                            data-note="{{ $t->notes->first()?->note ?? '' }}">
                                                            <i class="{{ $t->status === 'Completed' ? 'feather-check-circle' : 'feather-check-square' }}"></i> {{ Str::limit($t->title, 18) }}
                                                        </div>
                                                    @endforeach
                                                </td>
                                            @endfor
                                        </tr>
                                    @endif

                                    {{-- Rows for timed appointments and timed tasks --}}
                                    @php
                                        // Collect all unique time keys from both appointments and timed tasks
                                        $allTimeKeys = collect();
                                        foreach ($allWeekAppts as $a) {
                                            $allTimeKeys->push(\Carbon\Carbon::parse($a->appointment_time)->format('H:i'));
                                        }
                                        foreach ($weekTasksTimed as $t) {
                                            $allTimeKeys->push(\Carbon\Carbon::parse($t->due_time)->format('H:i'));
                                        }
                                        $uniqueTimeKeys = $allTimeKeys->unique()->sort()->values();
                                        $weekTimedTasksByDate = $weekTasksTimed->groupBy(fn($t) => $t->due_date->toDateString());
                                    @endphp

                                    @foreach($uniqueTimeKeys as $timeKey)
                                        <tr style="min-height: 60px;">
                                            <td class="fw-bold text-muted text-center">{{ \Carbon\Carbon::createFromFormat('H:i', $timeKey)->format('h:i A') }}</td>
                                            @for($d = 0; $d < 7; $d++)
                                                @php
                                                    $dayDate  = $weekStart->copy()->addDays($d)->toDateString();
                                                    $dayAppts = ($weekAppointments[$dayDate] ?? collect())
                                                        ->filter(fn($a) => \Carbon\Carbon::parse($a->appointment_time)->format('H:i') === $timeKey);
                                                    $dayTasks = ($weekTimedTasksByDate[$dayDate] ?? collect())
                                                        ->filter(fn($t) => \Carbon\Carbon::parse($t->due_time)->format('H:i') === $timeKey);
                                                @endphp
                                                <td>
                                                    @foreach($dayAppts as $ev)
                                                        <div class="event-badge {{ $ev->color ?? 'event-blue' }} p-1 px-2 w-100 text-start shadow-sm cursor-pointer mb-1 calendar-popup-trigger"
                                                            data-calendar-popup="appointment"
                                                            data-appointment-id="{{ $ev->id }}"
                                                            data-title="{{ $ev->title ?? '' }}"
                                                            data-time="{{ \Carbon\Carbon::parse($ev->appointment_time)->format('h:i A') }}"
                                                            data-client="{{ $ev->client_name ?? '' }}"
                                                            data-location="{{ $ev->location ?? '' }}"
                                                            data-status="{{ $ev->status ?? '' }}"
                                                            data-notes="{{ $ev->notes ?? '' }}">
                                                            {{ Str::limit($ev->title, 20) }}
                                                        </div>
                                                    @endforeach
                                                    @foreach($dayTasks as $t)
                                                        <div class="event-badge {{ $t->status === 'Completed' ? 'event-green' : 'event-purple' }} p-1 px-2 w-100 text-start shadow-sm cursor-pointer mb-1 calendar-popup-trigger"
                                                            data-calendar-popup="task"
                                                            data-task-id="{{ $t->id }}"
                                                            data-title="{{ $t->title }}"
                                                            data-client="{{ $t->client_name ?? '' }}"
                                                            data-due-date="{{ $t->due_date->format('d M Y') }}"
                                                            data-due-time="{{ \Carbon\Carbon::parse($t->due_time)->format('h:i A') }}"
                                                            data-status="{{ $t->status }}"
                                                            data-note="{{ $t->notes->first()?->note ?? '' }}">
                                                            <i class="{{ $t->status === 'Completed' ? 'feather-check-circle' : 'feather-check-square' }}"></i> {{ Str::limit($t->title, 18) }}
                                                        </div>
                                                    @endforeach
                                                </td>
                                            @endfor
                                        </tr>
                                    @endforeach
                                @else
                                    {{-- Empty week: show standard time slots --}}
                                    @foreach(['09:00 AM','10:30 AM','12:00 PM','02:00 PM','04:00 PM'] as $slot)
                                        <tr style="height: 60px;">
                                            <td class="fw-bold text-muted text-center">{{ $slot }}</td>
                                            @for($d = 0; $d < 7; $d++)
                                                <td></td>
                                            @endfor
                                        </tr>
                                    @endforeach
                                @endif
                                </tbody>
                            </table>
                        </div>


                        <!-- 3. DAY VIEW (today's appointments) -->
                        <div id="dayViewContainer" class="d-none">
                            <div class="timeline-day-wrapper d-flex flex-column gap-3">
                                @forelse($todayAppointments as $ev)
                                    @php $badgeColor = $colorMap[$ev->status] ?? $ev->color; @endphp
                                    @php $borderColor = str_replace('event-', '', $badgeColor); @endphp
                                    <div class="p-3 border rounded border-primary bg-soft-primary cursor-pointer calendar-popup-trigger"
                                        data-calendar-popup="appointment"
                                        data-appointment-id="{{ $ev->id }}"
                                        data-title="{{ $ev->title ?? '' }}"
                                        data-time="{{ \Carbon\Carbon::parse($ev->appointment_time)->format('h:i A') }}"
                                        data-client="{{ $ev->client_name ?? '' }}"
                                        data-location="{{ $ev->location ?? '' }}"
                                        data-status="{{ $ev->status ?? '' }}"
                                        data-notes="{{ $ev->notes ?? '' }}">
                                        <div class="fs-12 fw-bold text-primary">{{ \Carbon\Carbon::parse($ev->appointment_time)->format('h:i A') }}</div>
                                        <h6 class="fw-bold text-dark mb-1">{{ $ev->title }}</h6>
                                        <div class="text-muted fs-12">Client: {{ $ev->client_name }} | Location: {{ $ev->location }}</div>
                                    </div>
                                @empty
                                @endforelse
                                @foreach($todayTasks as $task)
                                    <div class="p-3 border rounded border-purple bg-soft-purple cursor-pointer calendar-popup-trigger"
                                        data-calendar-popup="task"
                                        data-task-id="{{ $task->id }}"
                                        data-title="{{ $task->title }}"
                                        data-client="{{ $task->client_name ?? '' }}"
                                        data-due-date="{{ $task->due_date->format('d M Y') }}"
                                        data-due-time="{{ $task->due_time ? \Carbon\Carbon::parse($task->due_time)->format('h:i A') : '' }}"
                                        data-status="{{ $task->status }}"
                                        data-note="{{ $task->notes->first()?->note ?? '' }}">
                                        @if($task->due_time)
                                            <div class="fs-12 fw-bold text-purple">{{ \Carbon\Carbon::parse($task->due_time)->format('h:i A') }}</div>
                                        @endif
                                        <h6 class="fw-bold text-dark mb-1"><i class="feather-check-square me-1 text-purple"></i>{{ $task->title }}</h6>
                                        <div class="text-muted fs-12">Client: {{ $task->client_name ?? 'General Client' }} | Status: {{ $task->status }}</div>
                                    </div>
                                @endforeach
                                @if($todayAppointments->isEmpty() && $todayTasks->isEmpty())
                                    <div class="text-center text-muted py-4 fs-13">No appointments or tasks today.</div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-xl-3 col-lg-4 col-md-12">
                    <div class="card-widget p-4">
                        <h6 class="widget-title mb-3">Today's Agenda & Appointments</h6>
                        <div class="d-flex flex-column gap-3" id="agendaListContainer">
                            @forelse($todayAppointments as $ev)
                                @php
                                    $bgMap   = ['event-blue'=>'bg-soft-primary','event-green'=>'bg-soft-success','event-yellow'=>'bg-soft-warning','event-purple'=>'bg-soft-purple'];
                                    $badgeBg = ['event-blue'=>'bg-primary','event-green'=>'bg-success','event-yellow'=>'bg-warning text-dark','event-purple'=>'bg-purple'];
                                    $bg      = $bgMap[$ev->color] ?? 'bg-soft-primary';
                                    $badge   = $badgeBg[$ev->color] ?? 'bg-primary';
                                    // Smart location label & icon
                                    $isUrl     = filter_var($ev->location, FILTER_VALIDATE_URL) !== false;
                                    $locIcon   = $isUrl ? 'feather-link' : (str_contains(strtolower($ev->location ?? ''), 'zoom') ? 'feather-video' : (str_contains(strtolower($ev->location ?? ''), 'phone') ? 'feather-phone' : 'feather-map-pin'));
                                    $locLabel  = $isUrl ? 'Meeting Link' : ($ev->location ?? 'N/A');
                                @endphp
                                <div class="p-3 {{ $bg }} rounded border cursor-pointer calendar-popup-trigger"
                                    data-calendar-popup="appointment"
                                    data-appointment-id="{{ $ev->id }}"
                                    data-title="{{ $ev->title ?? '' }}"
                                    data-time="{{ \Carbon\Carbon::parse($ev->appointment_time)->format('h:i A') }}"
                                    data-client="{{ $ev->client_name ?? '' }}"
                                    data-location="{{ $ev->location ?? '' }}"
                                    data-status="{{ $ev->status ?? '' }}"
                                    data-notes="{{ $ev->notes ?? '' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="badge {{ $badge }} fs-11">{{ \Carbon\Carbon::parse($ev->appointment_time)->format('h:i A') }}</span>
                                        <span class="text-muted fs-11"><i class="{{ $locIcon }} me-1"></i> {{ $locLabel }}</span>
                                    </div>
                                    <div class="fw-bold text-dark fs-13">{{ $ev->title }}</div>
                                    <div class="text-muted fs-12 mt-1">Client: {{ $ev->client_name }}</div>
                                </div>
                            @empty
                            @endforelse
                            @foreach($todayTasks as $task)
                                <div class="p-3 bg-soft-purple rounded border cursor-pointer calendar-popup-trigger"
                                    data-calendar-popup="task"
                                    data-task-id="{{ $task->id }}"
                                    data-title="{{ $task->title }}"
                                    data-client="{{ $task->client_name ?? '' }}"
                                    data-due-date="{{ $task->due_date->format('d M Y') }}"
                                    data-due-time="{{ $task->due_time ? \Carbon\Carbon::parse($task->due_time)->format('h:i A') : '' }}"
                                    data-status="{{ $task->status }}"
                                    data-note="{{ $task->notes->first()?->note ?? '' }}">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="badge bg-purple text-white fs-11">{{ $task->due_time ? \Carbon\Carbon::parse($task->due_time)->format('h:i A') : 'Task' }}</span>
                                        <span class="badge {{ $task->status === 'Completed' ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning' }} fs-11">{{ $task->status }}</span>
                                    </div>
                                    <div class="fw-bold text-dark fs-13"><i class="feather-check-square me-1"></i>{{ $task->title }}</div>
                                    <div class="text-muted fs-12 mt-1">Client: {{ $task->client_name ?? 'General Client' }}</div>
                                </div>
                            @endforeach
                            @if($todayAppointments->isEmpty() && $todayTasks->isEmpty())
                                <div class="text-center text-muted fs-13 py-3">No appointments or tasks today.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

<!-- Modal: Event Detail -->
    <div class="modal fade" id="calendarEventDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-calendar me-2"></i> Appointment Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                        <div>
                            <span class="badge bg-soft-primary text-primary fw-bold fs-11" id="eventDetailStatus">Confirmed</span>
                            <h5 class="fw-bold text-dark mt-1 mb-0" id="eventDetailHeaderTitle"></h5>
                        </div>
                        <div class="bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;"><i class="feather-clock fs-4"></i></div>
                    </div>
                    <div class="row g-3 fs-13">
                        <div class="col-6">
                            <span class="text-muted d-block fs-11 fw-semibold text-uppercase">Time &amp; Duration</span>
                            <strong class="text-dark" id="eventDetailTime"></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block fs-11 fw-semibold text-uppercase">Client Name</span>
                            <strong class="text-dark" id="eventDetailClient"></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block fs-11 fw-semibold text-uppercase">Location / Meeting Link</span>
                            <strong class="text-primary" id="eventDetailLocation"></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block fs-11 fw-semibold text-uppercase">Assigned Advisor</span>
                            <strong class="text-dark">{{ auth()->user()->name }}</strong>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="text-muted d-block fs-11 fw-semibold text-uppercase mb-1">Appointment Notes</span>
                            <div class="p-3 bg-light rounded border text-dark fs-13 mb-2" id="eventDetailNotes" style="max-height: 140px; overflow-y: auto; white-space: pre-line;"></div>
                            <textarea id="eventDetailNewNoteInput" class="form-control fs-12 mb-2" rows="2" placeholder="Type appointment note..."></textarea>
                            <button type="button" class="btn btn-primary btn-sm fw-bold" onclick="handleSaveDetailNote()"><i class="feather-plus me-1"></i> Add Note</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <form id="deleteAppointmentForm" method="POST" class="me-auto">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm fw-bold px-3"><i class="feather-trash-2 me-1"></i> Delete</button>
                    </form>
                    <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm fw-bold px-3" id="sendReminderBtn" onclick="handleSendReminder()"><i class="feather-send me-1"></i> Send Reminder</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Schedule Appointment -->
    <div class="modal fade" id="scheduleEventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-calendar me-2"></i> Schedule Appointment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('appointments.store') }}">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Meeting Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" id="eventTitleInput" placeholder="e.g. Life Cover Consult" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Client <span class="text-danger">*</span></label>
                            <select class="form-select client-name-search" name="client_id" id="eventClientInput" data-placeholder="Select Client..." required>
                                <option value="" disabled selected>Select Client...</option>
                                @foreach($clients as $client)
                                    @php
                                        $fullName = trim($client->first_name . ' ' . $client->last_name);
                                    @endphp
                                    @if(!empty($fullName))
                                        <option value="{{ $client->id }}">{{ $fullName }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="appointment_date" id="eventDateInput" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold fs-13 text-dark">Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="appointment_time" id="eventTimeInput" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Location / Link</label>
                            <input type="text" class="form-control" name="location" id="eventLocationInput" placeholder="Zoom Link or Office Location">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Color</label>
                            <select class="form-select" name="color">
                                <option value="event-blue">Blue (Default)</option>
                                <option value="event-green">Green</option>
                                <option value="event-yellow">Yellow</option>
                                <option value="event-purple">Purple</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Appointment Notes / Agenda</label>
                            <textarea class="form-control" name="notes" id="eventNotesInput" rows="3" placeholder="Add meeting agenda..." maxlength="2000"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Schedule Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Task Detail -->
    <div class="modal fade" id="calendarTaskDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-check-square me-2"></i> Task Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h5 class="fw-bold text-dark mb-3" id="taskDetailTitle"></h5>
                    <div class="row g-3 fs-13">
                        <div class="col-6">
                            <span class="text-muted d-block fs-11 fw-semibold text-uppercase">Client</span>
                            <strong class="text-dark" id="taskDetailClient"></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block fs-11 fw-semibold text-uppercase">Due</span>
                            <strong class="text-dark" id="taskDetailDue"></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block fs-11 fw-semibold text-uppercase">Status</span>
                            <span id="taskDetailStatus" class="badge fs-11"></span>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="text-muted d-block fs-11 fw-semibold text-uppercase mb-1">Latest Note</span>
                            <div class="p-3 bg-light rounded border text-dark fs-13" id="taskDetailNote" style="white-space: pre-line;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <a id="taskDetailLink" href="{{ route('tasks.index') }}" class="btn btn-primary btn-sm fw-bold"><i class="feather-external-link me-1"></i> Open in Tasks</a>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    <script src="{{ asset('assets/js/pages/calendar.js?v=2.0') }}"></script>
    <script>
        document.addEventListener('click', function (event) {
            const popup = event.target.closest('.calendar-popup-trigger');
            if (popup) {
                event.stopPropagation();
                if (popup.dataset.calendarPopup === 'appointment') {
                    openEventDetail(
                        popup.dataset.appointmentId,
                        popup.dataset.title,
                        popup.dataset.time,
                        popup.dataset.client,
                        popup.dataset.location,
                        popup.dataset.status,
                        popup.dataset.notes
                    );
                } else {
                    openTaskDetailModal(
                        popup.dataset.taskId,
                        popup.dataset.title,
                        popup.dataset.client,
                        popup.dataset.dueDate,
                        popup.dataset.dueTime,
                        popup.dataset.status,
                        popup.dataset.note
                    );
                }
                return;
            }

            const dateCell = event.target.closest('.calendar-cell[data-calendar-date]');
            if (dateCell) {
                document.getElementById('eventDateInput').value = dateCell.dataset.calendarDate;
                new bootstrap.Modal(document.getElementById('scheduleEventModal')).show();
            }
        });

        function openTaskDetailModal(id, title, clientName, dueDate, dueTime, status, latestNote) {
            document.getElementById('taskDetailTitle').textContent    = title || '';
            document.getElementById('taskDetailClient').textContent   = clientName || 'General Client';
            document.getElementById('taskDetailDue').textContent      = dueDate + (dueTime ? ' at ' + dueTime : '');
            var statusEl = document.getElementById('taskDetailStatus');
            var isCompleted = status === 'Completed';
            statusEl.className = 'badge fs-11 ' + (isCompleted ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning');
            statusEl.textContent = status || 'Pending';
            document.getElementById('taskDetailNote').textContent     = latestNote || 'No notes yet.';
            document.getElementById('taskDetailLink').href            = '{{ route('tasks.index') }}';
            new bootstrap.Modal(document.getElementById('calendarTaskDetailModal')).show();
        }

        function openEventDetail(id, title, time, client, location, status, notes) {
            document.getElementById('eventDetailHeaderTitle').textContent = title;
            document.getElementById('eventDetailTime').textContent = time;
            document.getElementById('eventDetailClient').textContent = client;
            // Show URL as "Meeting Link" (clickable), not the raw URL
            var locEl = document.getElementById('eventDetailLocation');
            if (location && location.startsWith('http')) {
                locEl.innerHTML = '<a href="' + location + '" target="_blank" rel="noopener" class="text-primary fw-bold">Meeting Link <i class="feather-external-link fs-11"></i></a>';
            } else {
                locEl.textContent = location || 'N/A';
            }
            document.getElementById('eventDetailStatus').textContent = status;
            document.getElementById('eventDetailNotes').textContent = notes || 'No notes.';
            document.getElementById('eventDetailNewNoteInput').value = '';
            document.getElementById('deleteAppointmentForm').action = '/utilities/appointments/' + id;
            document.getElementById('sendReminderBtn').setAttribute('data-id', id);
            new bootstrap.Modal(document.getElementById('calendarEventDetailModal')).show();
        }

        function handleSendReminder() {
            var btn = document.getElementById('sendReminderBtn');
            var id = btn.getAttribute('data-id');
            var originalText = btn.innerHTML;
            
            btn.innerHTML = 'Sending...';
            btn.disabled = true;

            fetch('/utilities/appointments/' + id + '/remind', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                if(data.success) {
                    alert(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('calendarEventDetailModal')).hide();
                } else {
                    alert(data.message);
                }
            })
            .catch(e => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('An error occurred while sending the reminder.');
            });
        }

        function handleSaveDetailNote() {
            var input = document.getElementById('eventDetailNewNoteInput');
            var newNote = input.value.trim();
            if (!newNote) { return; }
            var notesEl = document.getElementById('eventDetailNotes');
            var now = new Date();
            var timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            notesEl.textContent = notesEl.textContent + '\n[' + timeStr + '] ' + newNote;
            input.value = '';
        }
    </script>
@endpush

@endsection
