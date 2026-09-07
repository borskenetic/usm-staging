<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student</title>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <script src="{{ asset('vendor/signature_pad/signature_pad.umd.min.js') }}"></script>

    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        canvas {
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
            max-width: 500px;
        }

        .btn-save {
            background-color: #1b5e20;
            color: white;
        }

        .btn-save:hover {
            background-color: #145218;
        }
    </style>
</head>

<body>

<div class="container mt-5 mb-5">
    <div class="card">
        <div class="card-header text-center">
            <h4>Edit Student Information</h4>
        </div>

        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="studentForm"
                  method="POST"
                  action="{{ route('students.update', $student->id) }}"
                  enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <h5 class="mb-3">Student Information</h5>

                <div class="row g-3">

                    <!-- ID Number -->
                    <div class="col-md-6">
                        <label class="form-label">ID Number</label>
                        <input type="text"
                               name="id_number"
                               class="form-control"
                               placeholder="ID Number"
                               value="{{ old('id_number', $student->id_number) }}"
                               required>
                    </div>

                    <!-- QR Code -->
                    <div class="col-md-6">
                        <label class="form-label">QR Code</label>
                        <input type="text"
                               name="qrcode"
                               class="form-control"
                               placeholder="QR Code"
                               value="{{ old('qrcode', $student->qrcode) }}"
                               readonly>
                    </div>

                    <!-- First Name -->
                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input type="text"
                               name="firstname"
                               class="form-control"
                               placeholder="First Name"
                               value="{{ old('firstname', $student->firstname) }}"
                               required>
                    </div>

                    <!-- Last Name -->
                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input type="text"
                               name="lastname"
                               class="form-control"
                               placeholder="Last Name"
                               value="{{ old('lastname', $student->lastname) }}"
                               required>
                    </div>

                    <!-- Middle Initial -->
                    <div class="col-md-4">
                        <label class="form-label">Middle Initial</label>
                        <input type="text"
                               name="middle_initial"
                               class="form-control"
                               placeholder="Middle Initial"
                               value="{{ old('middle_initial', $student->middle_initial) }}">
                    </div>

                    <!-- Birthday: HTML date inputs require yyyy-MM-dd only -->
                    <div class="col-md-6">
                        <label class="form-label">Birthday</label>
                        <input type="date"
                               name="birthday"
                               class="form-control"
                               value="{{ old('birthday', $student->birthday?->format('Y-m-d')) }}">
                        @error('birthday')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Mobile Number -->
                    <div class="col-md-6">
                        <label class="form-label">Mobile Number</label>
                        <input type="text"
                               name="mobile_number"
                               class="form-control"
                               placeholder="09XXXXXXXXX"
                               value="{{ old('mobile_number', $student->mobile_number) }}">
                    </div>

                    <!-- Course -->
                    <div class="col-md-6">
                        <label class="form-label">Course</label>
                        <input type="text"
                               name="course"
                               class="form-control"
                               placeholder="Course"
                               value="{{ old('course', $student->course) }}">
                    </div>

                    <!-- Year -->
                    <div class="col-md-6">
                        <label class="form-label">Year Level</label>
                        <select name="year" class="form-select">
                            <option value="">Select Year</option>

                            <option value="1st Year"
                                {{ in_array(old('year', $student->year), ['1st Year', 'First Year'], true) ? 'selected' : '' }}>
                                1st Year
                            </option>

                            <option value="2nd Year"
                                {{ in_array(old('year', $student->year), ['2nd Year', 'Second Year'], true) ? 'selected' : '' }}>
                                2nd Year
                            </option>

                            <option value="3rd Year"
                                {{ in_array(old('year', $student->year), ['3rd Year', 'Third Year'], true) ? 'selected' : '' }}>
                                3rd Year
                            </option>

                            <option value="4th Year"
                                {{ in_array(old('year', $student->year), ['4th Year', 'Fourth Year'], true) ? 'selected' : '' }}>
                                4th Year
                            </option>

                            <option value="5th Year"
                                {{ in_array(old('year', $student->year), ['5th Year', 'Fifth Year'], true) ? 'selected' : '' }}>
                                5th Year
                            </option>

                            <option value="6th Year"
                                {{ in_array(old('year', $student->year), ['6th Year', 'Sixth Year'], true) ? 'selected' : '' }}>
                                6th Year
                            </option>
                        </select>
                    </div>

                    <!-- Address -->
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Complete Address">{{ old('address', $student->address) }}</textarea>
                    </div>

                </div>

                <hr class="my-4">

                <h5 class="mb-3">Emergency Contact Information</h5>

                <div class="row g-3">

                    <!-- Emergency Person -->
                    <div class="col-md-6">
                        <label class="form-label">Emergency Contact Person</label>
                        <input type="text"
                               name="emergency_person"
                               class="form-control"
                               placeholder="Full Name"
                               value="{{ old('emergency_person', $student->emergency_person) }}">
                    </div>

                    <!-- Relationship -->
                    <div class="col-md-6">
                        <label class="form-label">Relationship</label>
                        <input type="text"
                               name="emergency_relationship"
                               class="form-control"
                               placeholder="Relationship"
                               value="{{ old('emergency_relationship', $student->emergency_relationship) }}">
                    </div>

                    <!-- Emergency Number -->
                    <div class="col-md-6">
                        <label class="form-label">Emergency Contact Number</label>
                        <input type="text"
                               name="emergency_number"
                               class="form-control"
                               placeholder="09XXXXXXXXX"
                               value="{{ old('emergency_number', $student->emergency_number) }}">
                    </div>

                    <!-- Emergency Address -->
                    <div class="col-md-6">
                        <label class="form-label">Emergency Address</label>
                        <textarea name="emergency_address"
                                  class="form-control"
                                  rows="2"
                                  placeholder="Emergency Address">{{ old('emergency_address', $student->emergency_address) }}</textarea>
                    </div>

                </div>

                <hr class="my-4">

                <h5 class="mb-3">Profile & Signature</h5>

                <div class="row g-3">

                    <!-- Profile Picture -->
                    <div class="col-md-6">
                        <label class="form-label">Profile Picture</label>

                        <input type="file"
                               name="profile_picture"
                               class="form-control"
                               accept=".jpg,.jpeg,.png">

                        @if($student->profile_picture)
                            <div class="mt-2">
                                <img src="{{ asset($student->profile_picture) }}"
                                     alt="Profile Picture"
                                     width="120"
                                     class="rounded border">
                            </div>
                        @endif
                    </div>

                    <!-- Signature -->
                    <div class="col-md-12">
                        <label class="form-label">Signature (draw below)</label><br>

                        <canvas id="studentSignaturePad" width="500" height="150"></canvas>

                        <input type="hidden"
                               name="student_signature"
                               id="studentSignatureInput"
                               value="{{ old('student_signature', $student->student_signature) }}">

                        <div class="mt-2">
                            <button type="button"
                                    id="clearStudentSignature"
                                    class="btn btn-outline-danger btn-sm">
                                Clear
                            </button>
                        </div>

                        @if($student->student_signature)
                            <div class="mt-3">
                                <p class="mb-1">Current Signature:</p>

                                <img src="{{ asset($student->student_signature) }}"
                                     height="80"
                                     class="border rounded">
                            </div>
                        @endif
                    </div>

                </div>

                <!-- Buttons -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-save px-4">
                        Update Student
                    </button>

                    <a href="{{ route('students.index') }}"
                       class="btn btn-secondary px-4">
                        Back
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    const canvas = document.getElementById('studentSignaturePad');
    const signaturePad = new SignaturePad(canvas);
    const input = document.getElementById('studentSignatureInput');

    document.getElementById('clearStudentSignature')
        .addEventListener('click', () => {
            signaturePad.clear();
            input.value = '';
        });

    document.getElementById('studentForm')
        .addEventListener('submit', () => {
            if (!signaturePad.isEmpty()) {
                input.value = signaturePad.toDataURL();
            }
        });
</script>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

</body>
</html>