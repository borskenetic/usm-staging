@extends('layouts.sidebar')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/students/create.css') }}">
@endsection

@section('content')
<div class="container mt-5 mb-5">
    <div class="card">
        <div class="card-header text-center">
            <h4 class="mb-0">Edit Faculty &amp; Staff</h4>
        </div>
        <div class="card-body">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="employeeForm" method="POST" action="{{ route('employees.update', $employee->id) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <h5 class="mb-3">Faculty &amp; Staff Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name</label>
                        <input type="text" name="firstname" class="form-control" value="{{ old('firstname', $employee->firstname) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="lastname" class="form-control" value="{{ old('lastname', $employee->lastname) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Middle Initial</label>
                        <input type="text" name="middle_initial" class="form-control" value="{{ old('middle_initial', $employee->middle_initial) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Employee ID</label>
                        <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id', $employee->employee_id) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" class="form-control"
                               value="{{ old('designation', $employee->designation ?? $employee->position) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Select Program</label>
                        <select name="program" class="form-select" required>
                            <option value="">— Select program —</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->program_code }}"
                                    @selected(old('program', $employee->program) === $program->program_code)>
                                    {{ $program->program_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Year of start of work in this HEI</label>
                        <select name="year_start_work" class="form-select" required>
                            <option value="">— Select year —</option>
                            @foreach ($workStartYears as $yr)
                                <option value="{{ $yr }}" @selected(old('year_start_work', $employee->year_start_work) == (string) $yr)>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Birthday</label>
                        <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d') ?? $employee->birth_date) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $employee->mobile_number) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3">{{ old('address', $employee->address) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Formal picture</label>
                        <input type="file" name="formal_picture" class="form-control" accept=".jpg,.jpeg,.png">
                        @if ($employee->formal_picture)
                            <img src="{{ asset($employee->formal_picture) }}" alt="" width="100" class="rounded mt-2">
                        @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label">Signature</label>
                        <canvas id="employeeSignaturePad" width="500" height="150"></canvas>
                        <input type="hidden" name="employee_signature" id="employeeSignatureInput">
                        <button type="button" id="clearEmployeeSignature" class="btn btn-outline-danger btn-sm mt-2">Clear</button>
                        @if ($employee->employee_signature)
                            <img src="{{ asset($employee->employee_signature) }}" alt="" height="60" class="mt-2 d-block">
                        @endif
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Emergency Contact Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Contact person</label>
                        <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="emergency_contact_relationship" class="form-control" value="{{ old('emergency_contact_relationship', $employee->emergency_contact_relationship) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact number</label>
                        <input type="text" name="emergency_contact_number" class="form-control" value="{{ old('emergency_contact_number', $employee->emergency_contact_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Emergency address</label>
                        <textarea name="emergency_address" class="form-control" rows="2">{{ old('emergency_address', $employee->emergency_address) }}</textarea>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-4">Save changes</button>
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary px-4">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/signature_pad/signature_pad.umd.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('employeeSignaturePad');
    if (!canvas || typeof SignaturePad === 'undefined') return;
    const signaturePad = new SignaturePad(canvas);
    const input = document.getElementById('employeeSignatureInput');
    document.getElementById('clearEmployeeSignature')?.addEventListener('click', () => {
        signaturePad.clear();
        input.value = '';
    });
    document.getElementById('employeeForm')?.addEventListener('submit', () => {
        if (!signaturePad.isEmpty()) input.value = signaturePad.toDataURL();
    });
});
</script>
@endsection
