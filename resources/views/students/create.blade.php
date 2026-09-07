<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Student</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('vendor/signature_pad/signature_pad.umd.min.js') }}"></script>

    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        canvas { border: 1px solid #ccc; border-radius: 6px; }
        .btn-save { background-color: #1b5e20; color: white; }
        .btn-save:hover { background-color: #145218; }
    </style>
</head>

<body>

<div class="container mt-5 mb-5">
    <div class="card">
        <div class="card-header text-center">
            <h4>Register New Student</h4>
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="studentForm" action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <h5 class="mb-3">Student Information</h5>

                <div class="row g-3">

                    <div class="col-md-6">
                        <input type="text" name="firstname" class="form-control"
                               placeholder="First Name"
                               value="{{ old('firstname') }}" >
                    </div>

                    <div class="col-md-6">
                        <input type="text" name="lastname" class="form-control"
                               placeholder="Last Name"
                               value="{{ old('lastname') }}" >
                    </div>

                    <div class="col-md-6">
                        <input type="text" name="middle_initial" class="form-control"
                               placeholder="Middle Initial"
                               value="{{ old('middle_initial') }}">
                    </div>

                    <div class="col-md-6">
                        <input type="text" name="id_number" class="form-control"
                               placeholder="ID Number"
                               value="{{ old('id_number') }}">
                    </div>

                    <div class="col-md-6" hidden>
                        <input type="text" name="qrcode" class="form-control"
                               placeholder="QR Code"
                               value="{{ old('qrcode') }}" >
                    </div>

                    <div class="col-md-6">
                        <select name="course" class="form-select" >
                            <option value="">Select Course</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->program_code }}"
                                    {{ old('course') == $program->program_code ? 'selected' : '' }}>
                                    {{ $program->program_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <select name="year" class="form-select" >
                            <option value="">Select Year</option>
                            @foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year','6th Year'] as $yr)
                                <option value="{{ $yr }}" {{ old('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Birthday</label>
                        <input type="date" name="birthday" class="form-control"
                               value="{{ old('birthday') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" class="form-control"
                               placeholder="09XXXXXXXXX"
                               value="{{ old('mobile_number') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3"
                                  placeholder="Complete Address">{{ old('address') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_picture" class="form-control" accept=".jpg,.jpeg,.png">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Signature (draw below)</label><br>
                        <canvas id="studentSignaturePad" width="500" height="150"></canvas>
                        <input type="hidden" name="student_signature" id="studentSignatureInput" value="{{ old('student_signature') }}">

                        <div class="mt-2">
                            <button type="button" id="clearStudentSignature" class="btn btn-outline-danger btn-sm">Clear</button>
                        </div>
                    </div>

                </div>

                <hr class="my-4">

                <h5 class="mb-3">Emergency Contact Information</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Emergency Contact Person</label>
                        <input type="text" name="emergency_person" class="form-control"
                               placeholder="Full Name"
                               value="{{ old('emergency_person') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="emergency_relationship" class="form-control"
                               placeholder="Relationship"
                               value="{{ old('emergency_relationship') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Emergency Contact Number</label>
                        <input type="text" name="emergency_number" class="form-control"
                               placeholder="09XXXXXXXXX"
                               value="{{ old('emergency_number') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Emergency Address</label>
                        <textarea name="emergency_address" class="form-control" rows="2"
                                  placeholder="Emergency Address">{{ old('emergency_address') }}</textarea>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-save px-4">Register Student</button>
                    <a href="{{ route('students.index') }}" class="btn btn-secondary px-4">Back</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    const canvas = document.getElementById('studentSignaturePad');
    const signaturePad = new SignaturePad(canvas);
    const input = document.getElementById('studentSignatureInput');

    document.getElementById('clearStudentSignature').addEventListener('click', () => {
        signaturePad.clear();
        input.value = '';
    });

    document.getElementById('studentForm').addEventListener('submit', () => {
        if (!signaturePad.isEmpty()) {
            input.value = signaturePad.toDataURL();
        }
    });
</script>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
