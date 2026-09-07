<div class="modal fade" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="holidayModalLabel">Set Holiday</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="holidayMessage"></p>
        <div id="holidayNameField">
            <label class="form-label" for="holidayName">Holiday Name</label>
            <input type="text" id="holidayName" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmHolidayBtn" class="btn btn-primary">Confirm</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="holidayListModal" tabindex="-1" aria-labelledby="holidayListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="holidayListModalLabel">Holiday List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex overflow-auto mb-3" id="monthTabs">
                    <button type="button" class="btn btn-outline-primary btn-sm me-2 month-tab active" data-month="all">All</button>
                    @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $i => $label)
                        <button type="button" class="btn btn-outline-primary btn-sm me-2 month-tab" data-month="{{ $i }}">{{ $label }}</button>
                    @endforeach
                </div>
                <ul class="list-group" id="holidayList"></ul>
            </div>
        </div>
    </div>
</div>
