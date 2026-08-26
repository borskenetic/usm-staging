<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USM Library</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('img/usm_logo_1954.png') }}">
    <link href="{{ asset('vendor/fontsource/poppins/latin-400.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontsource/poppins/latin-600.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontsource/poppins/latin-700.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset(config('branding.css_path')) }}">
    <link rel="stylesheet" href="{{ asset('style.css') }}?v={{ filemtime(public_path('style.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/auth-modal.css') }}?v=6">
</head>
<body>

    <header class="navbar navbar--hero">
        <div class="navbar-content">
            <div class="logo-section">
                <img src="{{ asset('img/usm_logo_1954.png') }}" alt="University Logo" class="logo">
                <span class="university-name">
                    UNIVERSITY OF SOUTHERN MINDANAO
                </span>
            </div>
            <button type="button" class="nav-menu-toggle" id="navMenuToggle" aria-expanded="false" aria-controls="primaryNav" aria-label="Open menu">
                <span class="nav-menu-toggle-bar" aria-hidden="true"></span>
                <span class="nav-menu-toggle-bar" aria-hidden="true"></span>
                <span class="nav-menu-toggle-bar" aria-hidden="true"></span>
            </button>
            <nav class="nav-links" id="primaryNav">
                <a href="{{ url('/') }}">HOME</a>
                <a href="{{ asset('about.html') }}">ABOUT</a>
                <a href="{{ route('landing') }}">OPAC</a>
                <a href="https://zendy.io/">ZENDY</a>
                <a href="{{ asset('contact.html') }}">CONTACT US</a>
                <a href="{{ url('/rooms/book') }}">ROOM RESERVATIONS</a>
                <a href="{{ route('feedback.create') }}" class="feedback-link">FEEDBACK</a>
                <a href="{{ route('login') }}" class="login-button" data-auth-open="login">LOGIN</a>
            </nav>
        </div>
    </header>

    <main class="hero-section">
        <section class="hero-video-feature" aria-label="USM Library video">
            <video autoplay muted loop playsinline class="bg-video" poster="{{ asset('img/kplrc_banner_2-2048x881.png') }}">
                <source src="{{ asset('videos/hbanner.mp4') }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </section>

        <section class="overlay hero-search-feature" aria-label="OPAC book search">
            <h1 class="welcome-text" hidden>WELCOME TO USM LIBRARY</h1>
            <div class="home-search-react-root">
                <section class="home-search-panel" aria-labelledby="home-search-title">
                    <p class="home-search-kicker">Online Public Access Catalog</p>
                    <h1 id="home-search-title" class="home-search-title">
                        Search the USM Library collection
                    </h1>
                    <p class="home-search-copy">
                        Find books by title, author, subject, ISBN, or keyword.
                    </p>

                    <form action="{{ route('landing') }}" method="GET" class="home-opac-search" role="search">
                        <input type="hidden" name="view" value="books">
                        <svg class="home-opac-search__icon" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                        <input
                            type="search"
                            name="search"
                            placeholder="Search books, authors, titles..."
                            class="home-opac-search__input"
                            aria-label="Search library catalog"
                            required
                        >
                        <button type="submit" class="home-opac-search__button">Search OPAC</button>
                    </form>
                </section>
            </div>
        </section>
    </main>

    <section class="image-gallery-section">
        <div class="zendy-banner">
            <video autoplay muted loop playsinline class="bg-video" poster="{{ asset('img/kplrc_banner_2-2048x881.png') }}">
                <source src="{{ asset('videos/library-bg.mp4') }}" type="video/mp4">
            </video>
        </div>

        <div class="gallery-header">
            <h2>USM – Kundo E. Pahm Learning Resources Center</h2>
        </div>

        <div class="gallery-container">
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 13.png') }}" alt="KEPLRC Room 13 Activity" class="gallery-image">
                <p class="caption">KEPLRC 13</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 15.png') }}" alt="KEPLRC Room 15 Group Work" class="gallery-image">
                <p class="caption">KEPLRC 15</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 14.png') }}" alt="KEPLRC Room 14 Students Studying" class="gallery-image">
                <p class="caption">KEPLRC 14</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 12.png') }}" alt="KEPLRC 12" class="gallery-image">
                <p class="caption">KEPLRC 12</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 11.png') }}" alt="KEPLRC 11" class="gallery-image">
                <p class="caption">KEPLRC 11</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 10.png') }}" alt="KEPLRC 10" class="gallery-image">
                <p class="caption">KEPLRC 10</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 09.png') }}" alt="KEPLRC 09" class="gallery-image">
                <p class="caption">KEPLRC 09</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 08.png') }}" alt="KEPLRC 08" class="gallery-image">
                <p class="caption">KEPLRC 08</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 07.png') }}" alt="KEPLRC 07" class="gallery-image">
                <p class="caption">KEPLRC 07</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 06.png') }}" alt="KEPLRC 06" class="gallery-image">
                <p class="caption">KEPLRC 06</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 05.png') }}" alt="KEPLRC 05" class="gallery-image">
                <p class="caption">KEPLRC 05</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 04.png') }}" alt="KEPLRC 04" class="gallery-image">
                <p class="caption">KEPLRC 04</p>
            </div>
            <div class="image-card">
                <img src="{{ asset('img/KEPLRC 02.png') }}" alt="KEPLRC 02" class="gallery-image">
                <p class="caption">KEPLRC 02</p>
            </div>
        </div>
    </section>

    <section class="vm-section">
        <div class="vm-container">
            <div class="vm-item vision-item">
                <h2 class="vm-title">VISION</h2>
                <p>The University Library as the repository of resources shall seek to extend easy access, provide the venue and render assistance to
                    quality information materials for the benefit of all constituents.</p>
            </div>
            <div class="vm-item mission-item">
                <h2 class="vm-title">MISSION</h2>
                <p>The University Library’s primary responsibility is to provide information support to the teaching and learning process, research
                    and extension services activities of the students, faculty and other members of the academic community through the use of organized, relevant
                    and fast delivery of information services.</p>
            </div>
        </div>

        <div class="keplrc-description">
            <p>The Kundo E. Pahm Learning Resource Center is the information arm of the University. It supports basically the curricular offerings,
                research engagement and information needs of the faculty, students, and researchers of the entire university community.
                 In this Pandemic, the nature of services of the library totally shifted from a conventional type to a virtual type of services that libraries and librarians must embrace.
                 The University administration had not stop extending and delivering quality education to all constituents through creating a campus in various places of the Province.
                 Along with the expansion USM campuses in the community and opening of new programs and colleges the establishment of campus-based, college-based and unit-based
                 libraries of the University to ensure that the information needs of the stakeholders will be catered.</p>
        </div>
    </section>

    <div class="philosophy-container">
        <h2 class="philosophy-title">Philosophy</h2>
        <p class="philosophy-intro">The main philosophy of the library is efficiency of service. Its primary function is to transmit into action its institutional objectives. As a service agency, it is considered the brain of the institution and acts as the teaching agency in terms of giving assistance in the location of information from all available library resources. To achieve the full expression of service to its clientele, the library must be:</p>

        <ol class="philosophy-list">
            <li>An active and even aggressive participant in the whole program of education as it strives to meet the needs of the students, teachers and administration as well;</li>
            <li>A living repository of well-distributed and up-dated collections which readily support the course curriculum offerings and enhances the education’s learning processes and teaching competence;</li>
            <li>A resourceful facilitation of materials appropriate and most meaningful in the clientele’s growth and development as individuals;</li>
            <li>An efficient teaching instrument in the formation of a genuine citizen through the development of moral and spiritual values as designed in the curriculum; and</li>
            <li>It must be an efficient service agency that could readily answers the needs of every library clientele and whole objectives are adequately met by providing reference materials in all the subjects in the curriculum.</li>
        </ol>
    </div>

    <section class="goals-objectives-section">
        <div class="goals-container">
            <h2 class="goals-main-title">Goals and Objectives of the Library</h2>
            <p class="goals-subtitle">In line with the Vision and Mission of the University, the library shall achieve the following goals and objectives.</p>

            <h3 class="goals-heading">Goals</h3>
            <ol class="goals-list">
                <li>The foremost provider of information resources to support the academic requirements of our students and professional development for faculty, staff and researchers.</li>
                <li>The foremost provider of information resources to support the teaching and research functions of our students, faculty, staff and researchers.</li>
                <li>A teaching library engaged in the development of the information research skills of students, faculty, staff and researchers.</li>
                <li>A preserver and developer of collection and archives essential to faculty and student researches.</li>
            </ol>
        </div>
    </section>

    <section class="objectives-section">
        <div class="goals-container">
            <h2 class="goals-main-title">Library Hours</h2>
            <p class="goals-subtitle">The Library is open from
                <strong>Monday to Friday at 7:00 AM to 6:00 PM with No Noon Break. </strong>
                During Midterm and Final examination period,
                the library is <strong>open on Saturday’s from 7:00am to 6:00pm with No noon break.</strong></p>

            <h3 class="goals-heading">Objectives</h3>
            <ol class="goals-list">
                <li>To acquire printed, non-printed, electronic resources and
                    other instructional materials in line with the demands of the curriculum and the needs of the clientele and to organize these materials for effective use.</li>
                <li>To guide the clientele on the choice of resources and other learning materials for personal and curricular purposes.</li>
                <li>To develop clientele’s skill and resourcefulness on the use of printed, non-printed and electronic resources in the library and
                    to encourage the habit of personal investigation.</li>
                <li>To help every library clientele establish a wide range of significant interests.</li>
                <li>To provide aesthetic experience and develop appreciation of the arts.</li>
                <li>To encourage lifelong education through the use of library resources.</li>
                <li>To encourage social attitudes and provide experience in a social and democratic living.</li>
                <li>To include provisions for physical, social aspects and professional development of staff.</li>
                <li>To work cooperatively and constructively with the entire university constituents.</li>
            </ol>
        </div>
    </section>

    <footer class="site-footer">
        <div class="footer-content">
            <img src="{{ asset('img/usm_logo_1954.png') }}" alt="USM Logo" class="footer-logo">
            <h2 class="footer-university-name">UNIVERSITY OF SOUTHERN MINDANAO</h2>
            <p class="footer-tagline">"Your Partner in Academic Excellence and Leadership Development."</p>
            <p class="footer-tagline">USM, Kabacan, North Cotabato | usmvprde@usm.edu.ph | (064) 248 - 2638</p>
            <p class="footer-copyright">Pantas &copy; {{ date('Y') }}. All Rights Reserved.</p>
        </div>
    </footer>

    @include('partials.auth-modal')

    <script src="{{ asset('script.js') }}?v={{ filemtime(public_path('script.js')) }}"></script>
    <script src="{{ asset('js/auth-modal.js') }}?v=4"></script>
</body>
</html>
