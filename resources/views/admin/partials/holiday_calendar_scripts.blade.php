@php
    $fcLocal = public_path('vendor/fullcalendar/index.global.min.js');
    $fcSrc = file_exists($fcLocal)
        ? asset('vendor/fullcalendar/index.global.min.js')
        : 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js';
@endphp
<script src="{{ $fcSrc }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('holiday-calendar');
    const wrapper = document.getElementById('calendarWrapper');
    const toggleBtn = document.getElementById('toggleCalendar');
    const calendarError = document.getElementById('calendarError');
    const holidayModalEl = document.getElementById('holidayModal');
    const holidayListModalEl = document.getElementById('holidayListModal');

    if (!calendarEl || !toggleBtn) {
        return;
    }

    const holidaysListUrl = @json(route('holidays.list'));
    const holidaysAllUrl = @json(route('holidays.all'));
    const holidaysToggleUrl = @json(route('holidays.toggle'));
    const csrfToken = @json(csrf_token());

    let calendar = null;
    let selectedDate = null;
    let allHolidays = [];
    let currentMonth = 'all';

    function showCalendarError(message) {
        calendarError.textContent = message;
        calendarError.classList.add('is-visible');
    }

    function hideCalendarError() {
        calendarError.classList.remove('is-visible');
        calendarError.textContent = '';
    }

    toggleBtn.addEventListener('click', function () {
        const opening = wrapper.style.display === 'none' || !wrapper.classList.contains('is-open');
        if (opening) {
            wrapper.style.display = 'block';
            wrapper.classList.add('is-open');
            toggleBtn.textContent = 'Hide Calendar';
            if (calendar) {
                requestAnimationFrame(function () {
                    calendar.updateSize();
                });
            }
        } else {
            wrapper.style.display = 'none';
            wrapper.classList.remove('is-open');
            toggleBtn.textContent = 'Show Calendar';
        }
    });

    if (typeof FullCalendar === 'undefined') {
        showCalendarError('Calendar library failed to load. Run npm install and refresh, or check your network connection.');
        return;
    }

    fetch(holidaysListUrl, { headers: { 'Accept': 'application/json' } })
        .then(function (res) {
            if (!res.ok) {
                throw new Error('Could not load holidays (HTTP ' + res.status + ').');
            }
            return res.json();
        })
        .then(function (data) {
            hideCalendarError();
            const events = (Array.isArray(data) ? data : []).map(function (h) {
                return {
                    title: h.name || 'Holiday',
                    start: h.holiday_date,
                    allDay: true,
                    color: '#dc3545',
                };
            });

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 'auto',
                events: events,
                dateClick: function (info) {
                    selectedDate = info.dateStr;
                    const event = calendar.getEvents().find(function (e) {
                        return e.startStr === selectedDate;
                    });
                    const isHoliday = !!event;
                    const modal = bootstrap.Modal.getOrCreateInstance(holidayModalEl);

                    if (isHoliday) {
                        document.getElementById('holidayMessage').innerText =
                            'This date is already a holiday. Do you want to remove it?';
                        document.getElementById('holidayNameField').style.display = 'none';
                    } else {
                        document.getElementById('holidayMessage').innerText =
                            'Do you want to set this date as a holiday?';
                        document.getElementById('holidayNameField').style.display = 'block';
                        document.getElementById('holidayName').value = '';
                    }

                    modal.show();
                },
            });

            calendar.render();
            window.__policyHolidayCalendar = calendar;
        })
        .catch(function (err) {
            showCalendarError(err.message || 'Could not load the holiday calendar.');
        });

    document.getElementById('confirmHolidayBtn').addEventListener('click', function () {
        if (!selectedDate || !calendar) {
            return;
        }

        const name = document.getElementById('holidayName').value;

        fetch(holidaysToggleUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                date: selectedDate,
                name: name,
            }),
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('Could not save holiday.');
                }
                return res.json();
            })
            .then(function (data) {
                if (data.status === 'added') {
                    calendar.addEvent({
                        title: name || 'Holiday',
                        start: selectedDate,
                        allDay: true,
                        color: '#dc3545',
                    });
                }
                if (data.status === 'removed') {
                    calendar.getEvents().forEach(function (event) {
                        if (event.startStr === selectedDate) {
                            event.remove();
                        }
                    });
                }
                bootstrap.Modal.getInstance(holidayModalEl)?.hide();
            })
            .catch(function () {
                alert('Could not update holiday. Please try again.');
            });
    });

    holidayListModalEl.addEventListener('show.bs.modal', function () {
        fetch(holidaysAllUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('Could not load holiday list.');
                }
                return res.json();
            })
            .then(function (data) {
                allHolidays = Array.isArray(data) ? data : [];
                renderHolidayTable();
            })
            .catch(function () {
                document.getElementById('holidayList').innerHTML =
                    '<li class="list-group-item text-center text-muted">Could not load holidays.</li>';
            });
    });

    function renderHolidayTable() {
        const list = document.getElementById('holidayList');
        list.innerHTML = '';

        let filtered = allHolidays;
        if (currentMonth !== 'all') {
            filtered = allHolidays.filter(function (h) {
                return new Date(h.holiday_date).getMonth() === currentMonth;
            });
        }

        if (filtered.length === 0) {
            list.innerHTML = '<li class="list-group-item text-center text-muted">No holidays</li>';
            return;
        }

        filtered.forEach(function (h) {
            const d = new Date(h.holiday_date);
            const formatted = d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between';
            li.innerHTML = '<span>' + formatted + '</span><span class="text-muted">' + (h.name || 'Holiday') + '</span>';
            list.appendChild(li);
        });
    }

    document.querySelectorAll('.month-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.month-tab').forEach(function (b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
            const month = this.dataset.month;
            currentMonth = month === 'all' ? 'all' : parseInt(month, 10);
            renderHolidayTable();
        });
    });
});
</script>
