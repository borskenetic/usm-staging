@extends('layouts.sidebar')

@section('title', 'Register student')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/students/patron-form.css') }}?v={{ filemtime(public_path('css/students/patron-form.css')) }}">
@endsection

@section('content')
<div class="container-fluid px-3 px-lg-4 py-4 patron-form-page">
    <header class="patron-form-hero">
        <div>
            <p class="patron-form-kicker">Library patrons</p>
            <h1>Register a student</h1>
            <p>Add a patron who can borrow, use the OPAC, and receive circulation email.</p>
        </div>
        <a href="{{ route('students.index') }}" class="btn patron-form-back">Back to students</a>
    </header>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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

    <form id="studentForm" action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data" class="patron-form">
        @csrf
        @include('students.partials.form', ['mode' => 'create', 'student' => null, 'programs' => $programs])
        <div class="patron-form-actions">
            <button type="submit" class="btn patron-form-save">Register student</button>
            <a href="{{ route('students.index') }}" class="btn patron-form-back">Cancel</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/signature_pad/signature_pad.umd.min.js') }}"></script>
    <script>
        const canvas = document.getElementById('studentSignaturePad');
        const width = canvas.parentElement.clientWidth || 500;
        canvas.width = width;
        canvas.height = 160;
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
@endsection
