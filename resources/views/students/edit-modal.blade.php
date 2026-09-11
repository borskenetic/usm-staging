<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form method="POST" action="{{ route('students.profile.request') }}" enctype="multipart/form-data" class="modal-content">
      @csrf
      <input type="hidden" name="student_id" value="{{ $student->id }}">

      <div class="modal-header py-2">
        <div>
          <h5 class="modal-title mb-0" id="editProfileModalLabel">Request profile edit</h5>
          <div class="small text-muted">Changes need staff approval before they appear on your record.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body py-3">
        @if(session('success'))
          <div class="alert alert-success py-2 mb-3">{{ session('success') }}</div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger py-2 mb-3">{{ session('error') }}</div>
        @endif

        <div class="small text-uppercase text-muted fw-semibold mb-2">Identity</div>
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <label class="form-label small mb-1" for="edit_lastname">Last name</label>
            <input type="text" id="edit_lastname" name="lastname" class="form-control form-control-sm"
                   value="{{ old('lastname', $student->lastname) }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1" for="edit_firstname">First name</label>
            <input type="text" id="edit_firstname" name="firstname" class="form-control form-control-sm"
                   value="{{ old('firstname', $student->firstname) }}" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1" for="edit_middle_initial">Middle initial</label>
            <input type="text" id="edit_middle_initial" name="middle_initial" class="form-control form-control-sm"
                   value="{{ old('middle_initial', $student->middle_initial) }}" maxlength="10">
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1" for="edit_birthday">Birthday</label>
            <input type="date" id="edit_birthday" name="birthday" class="form-control form-control-sm"
                   value="{{ old('birthday', $student->birthday?->format('Y-m-d')) }}">
          </div>
          <div class="col-md-5">
            <label class="form-label small mb-1" for="edit_program_id">Program</label>
            <select id="edit_program_id" name="program_id" class="form-select form-select-sm">
              <option value="">Select program</option>
              @foreach($programs as $prog)
                <option value="{{ $prog->id }}"
                  @selected((string) old('program_id') === (string) $prog->id || (! old('program_id') && $student->course == $prog->program_code))>
                  {{ $prog->program_name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1" for="edit_year">Year level</label>
            <select id="edit_year" name="year" class="form-select form-select-sm">
              @foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year','6th Year'] as $yr)
                <option value="{{ $yr }}" @selected((string) old('year', $student->year) === $yr)>
                  {{ $yr }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="small text-uppercase text-muted fw-semibold mb-2">Contact</div>
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <label class="form-label small mb-1" for="edit_mobile_number">Mobile number</label>
            <input type="text" id="edit_mobile_number" name="mobile_number" class="form-control form-control-sm"
                   value="{{ old('mobile_number', $student->mobile_number) }}">
          </div>
          <div class="col-md-8">
            <label class="form-label small mb-1" for="edit_address">Address</label>
            <input type="text" id="edit_address" name="address" class="form-control form-control-sm"
                   value="{{ old('address', $student->address) }}">
          </div>
        </div>

        <div class="small text-uppercase text-muted fw-semibold mb-2">Emergency contact</div>
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <label class="form-label small mb-1" for="edit_emergency_person">Person</label>
            <input type="text" id="edit_emergency_person" name="emergency_person" class="form-control form-control-sm"
                   value="{{ old('emergency_person', $student->emergency_person) }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1" for="edit_emergency_relationship">Relationship</label>
            <input type="text" id="edit_emergency_relationship" name="emergency_relationship" class="form-control form-control-sm"
                   value="{{ old('emergency_relationship', $student->emergency_relationship) }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1" for="edit_emergency_number">Number</label>
            <input type="text" id="edit_emergency_number" name="emergency_number" class="form-control form-control-sm"
                   value="{{ old('emergency_number', $student->emergency_number) }}">
          </div>
          <div class="col-12">
            <label class="form-label small mb-1" for="edit_emergency_address">Address</label>
            <input type="text" id="edit_emergency_address" name="emergency_address" class="form-control form-control-sm"
                   value="{{ old('emergency_address', $student->emergency_address) }}">
          </div>
        </div>

        <div class="small text-uppercase text-muted fw-semibold mb-2">Photo</div>
        <div class="row g-2 align-items-end">
          <div class="col-md-8">
            <label class="form-label small mb-1" for="edit_profile_picture">Profile picture</label>
            <input type="file" id="edit_profile_picture" name="profile_picture" class="form-control form-control-sm" accept="image/*">
          </div>
          <div class="col-md-4">
            <div class="small text-muted">Optional. Max 2 MB.</div>
          </div>
        </div>
      </div>

      <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-sm btn-primary">Submit request</button>
      </div>
    </form>
  </div>
</div>

{{-- STATUS MODAL --}}
<div class="modal fade" id="statusModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content text-center">
      <div class="modal-body p-4">
        @if(session('success'))
          <div class="text-success">
            <h5>Success</h5>
            <p class="mb-2">{{ session('success') }}</p>
          </div>
        @endif

        @if(session('error'))
          <div class="text-danger">
            <h5>Error</h5>
            <p class="mb-2">{{ session('error') }}</p>
          </div>
        @endif

        <small class="text-muted">
          Closing in <span id="countdown">3</span> seconds...
        </small>
      </div>
    </div>
  </div>
</div>

@if(session('success') || session('error'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    statusModal.show();

    let seconds = 3;
    const countdown = document.getElementById('countdown');

    const timer = setInterval(function () {
        seconds--;
        countdown.textContent = seconds;

        if (seconds <= 0) {
            clearInterval(timer);
            statusModal.hide();
        }
    }, 1000);
});
</script>
@endif
