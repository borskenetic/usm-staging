<!DOCTYPE html>
<html>
<head>
  <title>USM Library Attendance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="{{ asset('css/attendance/scan.css') }}?v={{ filemtime(public_path('css/attendance/scan.css')) }}">
  <link href="{{ asset('vendor/fontsource/poppins/latin-400.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontsource/poppins/latin-600.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/fontsource/poppins/latin-700.css') }}" rel="stylesheet">
  @include('components.branding-overrides')
  <style>
    /* USM kiosk theme (matches production gate terminal) */
    body.attendance-kiosk {
      --brand-nav-link: #2e7d32;
      --brand-button-bg: #2e7d32;
      --brand-button-hover-bg: #1b5e20;
      --brand-button-text: #ffffff;
      --brand-footer-bg: #2e7d32;
      --brand-nav-link-active: #ffffff;
      --brand-button-hover-text: #ffffff;
    }

    .marquee-container {
      width: 100%;
      overflow: hidden;
      background-color: #222;
      color: #fff;
      border-top: 2px solid #2e7d32;
      border-bottom: 2px solid #2e7d32;
      padding: 15px 0;
      box-sizing: border-box;
    }

    .marquee {
      display: inline-block;
      white-space: nowrap;
      padding-left: 100%;
      animation: scroll-text 15s linear infinite;
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      font-size: 24px;
    }

    @keyframes scroll-text {
      0% { transform: translateX(0%); }
      100% { transform: translateX(-100%); }
    }
  </style>
</head>
<body class="attendance-kiosk">
  <header>
    <div class="header">
      <div class="logo-title">
        <div class="kiosk-brand">
          <img src="{{ asset('img/usm_logo_1954.png') }}" alt="USM Logo">
          <span class="university-name">UNIVERSITY OF SOUTHERN MINDANAO</span>
        </div>
        <div class="system-title">POWERED BY PANTAS</div>
        <a href="{{ url('/') }}" class="home-button">Home</a>
      </div>
    </div>
  </header>

  <div class="main">
    <div class="sidebar">
      <div class="date" id="currentDate">Date</div>
      <div class="time" id="currentTime">--:--:--</div>

      <div class="profile-pic">
        @if(isset($student) && $student->profile_picture)
          <img src="{{ asset($student->profile_picture) }}" alt="Profile">
        @else
          <img src="{{ asset('images/2x2_undifined_gender.jpg') }}" alt="Default Profile">
        @endif
      </div>

      @if(isset($student))
        <div class="name-box">
          <div class="student-name">{{ $student->firstname }} {{ $student->lastname }}</div>
          <div class="label">Name</div>
          <div class="status-button {{ strtolower($status) === 'out' ? 'status-out' : '' }}">
            {{ $status }}
          </div>
          <div class="timestamp">
            {{ isset($log) ? \Carbon\Carbon::parse($log->scanned_at)->format('Y-m-d h:i:s A') : '' }}
          </div>
        </div>
      @endif

      @if(isset($book))
        <div class="name-box">
          <div class="student-name">{{ $book->title_statement }}</div>
          <div class="label">Book Title</div>
          <div class="status-button {{ strtolower($bookStatus) === 'not checked out' ? 'status-out' : '' }}">
            {{ $bookStatus }}
          </div>
        </div>
      @endif

      @if(session('error'))
        <div class="name-box">
          <div class="student-name">{{ session('error') }}</div>
          <div class="label">Error</div>
        </div>
      @endif
    </div>

    <div class="right-content">
      <form id="scanForm">
        @csrf
        <textarea name="qrcode" id="qrcode" style="opacity:0; position:absolute;" autofocus autocomplete="off"></textarea>
      </form>

      <video autoplay muted loop playsinline controls class="ads-vid">
        @if (is_file(public_path('videos/library-bg.mp4')))
          <source src="{{ asset('videos/library-bg.mp4') }}" type="video/mp4">
        @elseif (is_file(public_path('videos/area51_product_slideshow.mp4')))
          <source src="{{ asset('videos/area51_product_slideshow.mp4') }}" type="video/mp4">
        @endif
        Your browser does not support the video tag.
      </video>
    </div>
  </div>

  <footer>
    <div class="footer1">
      <div class="footer-logo">
        <div class="marquee-container">
          <div class="marquee">
            Welcome to University of Southern Mindanao - Kabacan
          </div>
        </div>
      </div>
    </div>
  </footer>

  <audio id="alertSound" src="{{ asset('sounds/alert.wav') }}" type="audio/wav"></audio>

  <div id="feedbackModal" class="section-modal" aria-hidden="true">
    <div class="modal-content feedback-card" role="dialog" aria-labelledby="feedbackModalTitle">
      <h2 id="feedbackModalTitle">How was your library experience?</h2>
      <div class="feedback-options">
        <button type="button" data-rating="excellent">😊<span>Excellent</span></button>
        <button type="button" data-rating="good">🙂<span>Good</span></button>
        <button type="button" data-rating="medium">😐<span>Medium</span></button>
        <button type="button" data-rating="poor">🙁<span>Poor</span></button>
        <button type="button" data-rating="very_bad">😠<span>Very Bad</span></button>
      </div>
      <button type="button" id="declineFeedback" class="decline-btn">Skip</button>
    </div>
  </div>

  <script>
    const LOGOUT_FEEDBACK_ENABLED = @json($logoutFeedbackEnabled ?? true);
    const feedbackModal = document.getElementById('feedbackModal');
    let currentStudentId = null;
    let currentEmployeeId = null;
    let clearDisplayTimer = null;

    document.addEventListener('DOMContentLoaded', function () {
      const input = document.getElementById('qrcode');
      const profileImg = document.querySelector('.profile-pic img');
      const sidebar = document.querySelector('.sidebar');
      const alertSound = document.getElementById('alertSound');
      let isCooldown = false;

      setInterval(() => input.focus(), 100);
      input.focus();

      function clearDisplay() {
        if (feedbackModal && feedbackModal.style.display === 'flex') {
          return;
        }
        profileImg.src = "{{ asset('images/2x2_undifined_gender.jpg') }}";
        document.querySelectorAll('.name-box').forEach(box => box.remove());
        currentStudentId = null;
        currentEmployeeId = null;
      }

      function scheduleClear(delayMs) {
        if (clearDisplayTimer) {
          clearTimeout(clearDisplayTimer);
        }
        clearDisplayTimer = setTimeout(clearDisplay, delayMs);
      }

      function showLogoutFeedback() {
        if (!LOGOUT_FEEDBACK_ENABLED || !feedbackModal || (!currentStudentId && !currentEmployeeId)) {
          scheduleClear(2000);
          return;
        }
        if (clearDisplayTimer) {
          clearTimeout(clearDisplayTimer);
          clearDisplayTimer = null;
        }
        setTimeout(() => {
          feedbackModal.style.display = 'flex';
          feedbackModal.setAttribute('aria-hidden', 'false');
        }, 500);
      }

      input.addEventListener('keypress', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        if (isCooldown) return;
        isCooldown = true;
        setTimeout(() => { isCooldown = false; }, 100);

        const formData = new FormData();
        formData.append('qrcode', input.value.trim().replace(/\r/g, ''));
        formData.append('_token', '{{ csrf_token() }}');

        fetch("{{ route('attendance.process') }}", {
          method: 'POST',
          body: formData
        })
          .then(res => res.json())
          .then(data => {
            if (feedbackModal && feedbackModal.style.display === 'flex') {
              closeFeedbackModal();
            }
            clearDisplay();

            if (data.type === 'student' || data.type === 'employee') {
              const patron = data.type === 'student' ? data.student : data.employee;
              currentStudentId = data.type === 'student' ? data.student_id : null;
              currentEmployeeId = data.type === 'employee' ? data.employee_id : null;
              const pic = patron.profile_picture
                ? "{{ asset('') }}" + patron.profile_picture
                : "{{ asset('images/2x2_undifined_gender.jpg') }}";
              profileImg.src = pic;

              const div = document.createElement('div');
              div.classList.add('name-box');
              div.innerHTML = `
                <div class="student-name">${patron.firstname} ${patron.lastname}</div>
                <div class="label">Name</div>
                <div class="status-button ${data.status.toLowerCase() === 'out' ? 'status-out' : ''}">${data.status}</div>
                <div class="timestamp">${data.log.scanned_at}</div>
              `;
              sidebar.appendChild(div);

              const feedbackOn = data.logout_feedback_enabled ?? LOGOUT_FEEDBACK_ENABLED;
              if (data.status.toLowerCase() === 'out' && feedbackOn) {
                showLogoutFeedback();
              } else {
                scheduleClear(2000);
              }
            } else if (data.type === 'book') {
              if (data.bookStatus.toLowerCase() === 'not checked out') {
                alertSound.play().catch(() => {});
              }
              const div = document.createElement('div');
              div.classList.add('name-box');
              div.innerHTML = `
                <div class="student-name">${data.book.title_statement}</div>
                <div class="label">Book Title</div>
                <div class="status-button ${data.bookStatus.toLowerCase() === 'not checked out' ? 'status-out' : ''}">${data.bookStatus}</div>
              `;
              sidebar.appendChild(div);
              scheduleClear(2000);
            } else if (data.type === 'error') {
              const div = document.createElement('div');
              div.classList.add('name-box');
              div.innerHTML = `
                <div class="student-name">${data.message}</div>
                <div class="label">Error</div>
              `;
              sidebar.appendChild(div);
              scheduleClear(2000);
            }

            input.value = '';
          })
          .catch(err => console.error(err));
      });

      function updateDateTime() {
        const now = new Date();
        const dateEl = document.getElementById('currentDate');
        const timeEl = document.getElementById('currentTime');
        if (dateEl && timeEl) {
          dateEl.textContent = now.toLocaleDateString('en-GB', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
          });
          timeEl.textContent = now.toLocaleTimeString('en-US');
        }
      }
      updateDateTime();
      setInterval(updateDateTime, 1000);

      const feedbackButtons = document.querySelectorAll('.feedback-options button');
      const declineBtn = document.getElementById('declineFeedback');

      function closeFeedbackModal() {
        if (!feedbackModal) return;
        feedbackModal.style.display = 'none';
        feedbackModal.setAttribute('aria-hidden', 'true');
      }

      function sendFeedback(rating = null, declined = 0) {
        if (!currentStudentId && !currentEmployeeId) {
          closeFeedbackModal();
          clearDisplay();
          input.focus();
          return;
        }

        fetch("{{ route('library.attendance.feedback.store') }}", {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            student_id: currentStudentId,
            employee_id: currentEmployeeId,
            rating: rating,
            declined: declined ? 1 : 0,
          }),
        }).catch(err => console.error(err)).finally(() => {
          closeFeedbackModal();
          clearDisplay();
          input.focus();
        });
      }

      feedbackButtons.forEach(btn => {
        btn.addEventListener('click', function () {
          sendFeedback(this.dataset.rating, 0);
        });
      });

      declineBtn?.addEventListener('click', function () {
        sendFeedback(null, 1);
      });
    });
  </script>
</body>
</html>
