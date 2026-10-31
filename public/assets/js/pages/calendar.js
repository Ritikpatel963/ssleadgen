/* calendar.js — view toggle only; data is rendered server-side */

function switchCalendarView(viewMode, btn) {
    document.querySelectorAll('#calendarViewToggle button').forEach(function (b) {
        b.classList.remove('btn-primary');
        b.classList.add('btn-outline-primary');
    });
    btn.classList.remove('btn-outline-primary');
    btn.classList.add('btn-primary');

    ['monthViewContainer', 'weekViewContainer', 'dayViewContainer'].forEach(function (id) {
        document.getElementById(id).classList.add('d-none');
    });

    if (viewMode === 'month') {
        document.getElementById('monthViewContainer').classList.remove('d-none');
    } else if (viewMode === 'week') {
        document.getElementById('weekViewContainer').classList.remove('d-none');
    } else if (viewMode === 'day') {
        document.getElementById('dayViewContainer').classList.remove('d-none');
    }
}
