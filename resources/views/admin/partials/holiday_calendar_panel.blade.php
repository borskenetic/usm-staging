<div class="d-flex gap-2 mb-2 flex-wrap">
    <button type="button" id="toggleCalendar" class="btn btn-secondary btn-sm">
        Show Calendar
    </button>
    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#holidayListModal">
        View Holidays
    </button>
</div>

<div id="calendarError" class="alert alert-warning" role="alert"></div>

<div id="calendarWrapper" style="display: none;">
    <div id="holiday-calendar"></div>
</div>

<p class="text-muted small mb-0 mt-2">Click a date to add or remove a holiday.</p>
