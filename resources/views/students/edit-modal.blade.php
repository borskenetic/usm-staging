<div class="modal fade" id="editProfileModal">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('students.profile.request') }}" enctype="multipart/form-data">
    @csrf
        <input type="hidden" name="student_id" value="{{ $student->id }}">

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Request Profile Edit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
        {{-- Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
        
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
          {{-- NAME SECTION --}}
          <div class="row">
            <div class="col-md-4">
              <label>Last Name</label>
              <input type="text" name="lastname" class="form-control"
                     value="{{ $student->lastname }}">
            </div>

            <div class="col-md-4">
              <label>First Name</label>
              <input type="text" name="firstname" class="form-control"
                     value="{{ $student->firstname }}">
            </div>

            <div class="col-md-4">
              <label>Middle Initial</label>
              <input type="text" name="middle_initial" class="form-control"
                     value="{{ $student->middle_initial }}">
            </div>
          </div>

          {{-- BIRTHDAY: HTML date inputs require yyyy-MM-dd only --}}
          <label class="mt-3">Birthday</label>
          <input type="date" name="birthday" class="form-control"
                 value="{{ old('birthday', $student->birthday?->format('Y-m-d')) }}">

          {{-- PROGRAM --}}
          <label class="mt-3">Program</label>
          <select name="program_id" class="form-select">
            <option value="">-- Select Program --</option>
            @foreach($programs as $prog)
              <option value="{{ $prog->id }}"
                {{ $student->course == $prog->program_code ? 'selected' : '' }}>
                {{ $prog->program_name }}
              </option>
            @endforeach
          </select>

          {{-- YEAR --}}
          <label class="mt-3">Year Level</label>
          <select name="year" class="form-select">
            @foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year','6th Year'] as $yr)
              <option value="{{ $yr }}" {{ (string) $student->year === $yr ? 'selected' : '' }}>
                {{ $yr }}
              </option>
            @endforeach
          </select>

          {{-- CONTACT INFO --}}
          <hr class="my-3">
          <h6>Contact Information</h6>

          <label class="mt-2">Mobile Number</label>
          <input type="text" name="mobile_number" class="form-control"
                 value="{{ $student->mobile_number }}">

          <label class="mt-2">Address</label>
          <textarea name="address" class="form-control">{{ $student->address }}</textarea>

          {{-- EMERGENCY INFO --}}
          <hr class="my-3">
          <h6>Emergency Contact</h6>

          <label class="mt-2">Emergency Person</label>
          <input type="text" name="emergency_person" class="form-control"
                 value="{{ $student->emergency_person }}">

          <label class="mt-2">Relationship</label>
          <input type="text" name="emergency_relationship" class="form-control"
                 value="{{ $student->emergency_relationship }}">

          <label class="mt-2">Emergency Number</label>
          <input type="text" name="emergency_number" class="form-control"
                 value="{{ $student->emergency_number }}">

          <label class="mt-2">Emergency Address</label>
          <textarea name="emergency_address" class="form-control">{{ $student->emergency_address }}</textarea>

          {{-- PROFILE PHOTO --}}
          <hr class="my-3">
          <label>Profile Picture</label>
          <input type="file" name="profile_picture" class="form-control">

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">
            Submit Request
          </button>
        </div>
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
            <p>{{ session('success') }}</p>
          </div>
        @endif

        @if(session('error'))
          <div class="text-danger">
            <h5>Error</h5>
            <p>{{ session('error') }}</p>
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


