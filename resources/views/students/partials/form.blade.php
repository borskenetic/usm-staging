@php
    $student = $student ?? null;
    $years = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', '6th Year'];
    $yearAliases = [
        '1st Year' => ['1st Year', 'First Year'],
        '2nd Year' => ['2nd Year', 'Second Year'],
        '3rd Year' => ['3rd Year', 'Third Year'],
        '4th Year' => ['4th Year', 'Fourth Year'],
        '5th Year' => ['5th Year', 'Fifth Year'],
        '6th Year' => ['6th Year', 'Sixth Year'],
    ];
    $courseValue = old('course', $student->course ?? '');
    $yearValue = old('year', $student->year ?? '');
    $knownCourses = $programs->pluck('program_code')->all();
@endphp

<section class="patron-form-card">
    <header class="patron-form-card__head">
        <span>01</span>
        <div>
            <h2>Student record</h2>
            <p>Name and ID used at the desk and on circulation notices.</p>
        </div>
    </header>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="id_number">ID number</label>
            <input type="text" id="id_number" name="id_number" class="form-control" placeholder="ID number"
                   value="{{ old('id_number', $student->id_number ?? '') }}" @if($mode === 'create') required @endif>
        </div>
        @if($mode === 'edit')
            <div class="col-md-6">
                <label class="form-label" for="qrcode">QR code</label>
                <input type="text" id="qrcode" name="qrcode" class="form-control" value="{{ $student->qrcode }}" readonly>
            </div>
        @endif
        <div class="col-md-4">
            <label class="form-label" for="firstname">First name</label>
            <input type="text" id="firstname" name="firstname" class="form-control" value="{{ old('firstname', $student->firstname ?? '') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="lastname">Last name</label>
            <input type="text" id="lastname" name="lastname" class="form-control" value="{{ old('lastname', $student->lastname ?? '') }}" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="middle_initial">Middle initial</label>
            <input type="text" id="middle_initial" name="middle_initial" class="form-control" maxlength="16"
                   value="{{ old('middle_initial', $student->middle_initial ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="course">Course</label>
            <select id="course" name="course" class="form-select" @if($mode === 'create') required @endif>
                <option value="">Select course</option>
                @if($courseValue !== '' && ! in_array($courseValue, $knownCourses, true))
                    <option value="{{ $courseValue }}" selected>{{ $courseValue }}</option>
                @endif
                @foreach($programs as $program)
                    <option value="{{ $program->program_code }}" @selected($courseValue === $program->program_code)>
                        {{ $program->program_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="year">Year level</label>
            <select id="year" name="year" class="form-select" @if($mode === 'create') required @endif>
                <option value="">Select year</option>
                @foreach($years as $yr)
                    <option value="{{ $yr }}" @selected(in_array($yearValue, $yearAliases[$yr], true))>{{ $yr }}</option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<section class="patron-form-card">
    <header class="patron-form-card__head">
        <span>02</span>
        <div>
            <h2>Contact</h2>
            <p>Circulation receipts are emailed when an address is on file.</p>
        </div>
    </header>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="email">Email address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="name@usm.edu.ph"
                   value="{{ old('email', $student->email ?? '') }}" autocomplete="email">
            <p class="patron-form-hint">Leave blank if the patron has no email. No circulation message is sent in that case.</p>
            @error('email')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="mobile_number">Mobile number</label>
            <input type="text" id="mobile_number" name="mobile_number" class="form-control" placeholder="09XXXXXXXXX"
                   value="{{ old('mobile_number', $student->mobile_number ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="birthday">Birthday</label>
            <input type="date" id="birthday" name="birthday" class="form-control"
                   value="{{ old('birthday', $student?->birthday?->format('Y-m-d')) }}">
            @error('birthday')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="address">Address</label>
            <textarea id="address" name="address" class="form-control" rows="3" placeholder="Complete address">{{ old('address', $student->address ?? '') }}</textarea>
        </div>
    </div>
</section>

<section class="patron-form-card">
    <header class="patron-form-card__head">
        <span>03</span>
        <div>
            <h2>Emergency contact</h2>
            <p>Who the desk should call if the patron cannot be reached.</p>
        </div>
    </header>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="emergency_person">Contact person</label>
            <input type="text" id="emergency_person" name="emergency_person" class="form-control"
                   value="{{ old('emergency_person', $student->emergency_person ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="emergency_relationship">Relationship</label>
            <input type="text" id="emergency_relationship" name="emergency_relationship" class="form-control"
                   value="{{ old('emergency_relationship', $student->emergency_relationship ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="emergency_number">Contact number</label>
            <input type="text" id="emergency_number" name="emergency_number" class="form-control" placeholder="09XXXXXXXXX"
                   value="{{ old('emergency_number', $student->emergency_number ?? '') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="emergency_address">Emergency address</label>
            <textarea id="emergency_address" name="emergency_address" class="form-control" rows="2">{{ old('emergency_address', $student->emergency_address ?? '') }}</textarea>
        </div>
    </div>
</section>

<section class="patron-form-card">
    <header class="patron-form-card__head">
        <span>04</span>
        <div>
            <h2>Photo and signature</h2>
            <p>Used on the patron record. Draw a new signature only when replacing the current one.</p>
        </div>
    </header>
    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label" for="profile_picture">Profile picture</label>
            <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept=".jpg,.jpeg,.png">
            @if($student?->profile_picture)
                <img src="{{ asset($student->profile_picture) }}" alt="Current profile picture" class="patron-form-photo">
            @endif
        </div>
        <div class="col-md-7">
            <label class="form-label" for="studentSignaturePad">Signature</label>
            <div class="patron-sign">
                <canvas id="studentSignaturePad" height="160"></canvas>
            </div>
            <input type="hidden" name="student_signature" id="studentSignatureInput" value="{{ old('student_signature', $student->student_signature ?? '') }}">
            <button type="button" id="clearStudentSignature" class="btn patron-form-clear">Clear signature</button>
            @if($student?->student_signature)
                <p class="patron-form-hint">Current signature</p>
                <img src="{{ asset($student->student_signature) }}" alt="Current signature" class="patron-form-signature">
            @endif
        </div>
    </div>
</section>
