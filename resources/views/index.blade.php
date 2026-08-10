<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('img/pantas-10.png') }}">
    <title>PANTAS | Platform</title>
    <link rel="stylesheet" href="{{ asset('style.css') }}?v=responsive-nav-7">
    <link rel="stylesheet" href="{{ asset('css/auth-modal.css') }}?v=5">
</head>
<body>

    <header>
        <div class="logo-container">
            <img src="{{ asset('img/pantas-logo-landscape-10.png') }}" alt="PANTAS Logo" class="logo">
        </div>
        <button class="nav-toggle" type="button" aria-label="Toggle navigation menu" aria-controls="primary-navigation" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="nav-links" id="primary-navigation">
            <ul>
                <li><a href="#about">ABOUT</a></li>
                <li><a href="{{ route('landing') }}" >OPAC</a></li>
                <li><a href="https://zendy.io/">ZENDY</a></li>
                <li><a href="https://www.drillbitplagiarismcheck.com/auth/login" target="_blank" rel="noopener noreferrer">DRILLBIT</a></li>
                <li><a href="https://app.truscholar.io/" target="_blank" rel="noopener noreferrer">TRUSCHOLAR</a></li>
                <li><a href="#contact">CONTACT US</a></li>
                <li><a href="{{ url('/rooms/book') }}">ROOM RESERVATIONS</a></li>
                <li><a href="{{ route('feedback.create') }}" class="feedback-link" >FEEDBACK</a></li>
                <li><a href="{{ route('login') }}" class="login-button" data-auth-open="login">LOGIN</a></li>
            </ul>
        </nav>
    </header>

    <main class="hero">
        <div class="hero-content">
            <h1 class="fade-in">WELCOME TO PANTAS</h1>
        </div>
    </main>

    <section class="about-section fade-in-scroll" id="about">
        <div class="container">
            <img src="{{ asset('img/pantas-10.png') }}" alt="PANTAS Logo Large" class="about-logo">
            
            <h2 class="tagline">"Pinoy Automated Next-Generation Technology for Academic Services"</h2>
            
            <p class="description">
                PANTAS (Affiliated by AREA51) is a smart digital library system designed to revolutionize how libraries operate. It bridges traditional physical resources with modern digital management using advanced RFID technology. As AREA 51’s first start-up venture, PANTAS aims to build the libraries of tomorrow – offering intelligent solutions that improve efficiency, enhance security, and simplify daily operations for librarians, educators, and institutions.
            </p>
            
            <h3 class="footer-motto">“Your Partner in Building the Libraries of Tomorrow”</h3>
        </div>
    </section>

<footer id="contact">
        <div class="footer-container">
            <div class="footer-col branding">
                <img src="{{ asset('img/pantas-logo-landscape-10.png') }}" alt="Pantas Logo" class="footer-logo">
                <img src="{{ asset('img/area-51-new-logo-2-copy-10.png') }}" alt="Area 51 Logo" class="footer-logo-sub">
            </div>

            <div class="footer-col">
                <h3>QUICK LINKS</h3>
                <ul>
                    <li><a href="{{ route('home') }}">HOME</a></li>
                    <li><a href="#about">ABOUT</a></li>
                    <li><a href="#contact">CONTACT US</a></li>
                    <li><a href="{{ url('/rooms/book') }}">ROOM RESERVATIONS</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>GET IN TOUCH</h3>
                <p>Zamoras Bldg, 2nd Floor, Purok 4, Glodo Subd,<br>
                   San Francisco, Panabo City, Davao del Norte</p>
                <p class="schedule">MONDAY - FRIDAY<br>9:00 AM - 5:00 PM</p>
                <p><a href="mailto:inquiry@area51.ph">inquiry@area51.ph</a></p>
                <p>0917 762 1021</p>
            </div>
        </div>
    </footer>


    

    @include('partials.auth-modal')

    <script src="{{ asset('script.js') }}?v=responsive-nav-5"></script>
    <script src="{{ asset('js/auth-modal.js') }}?v=4"></script>
</body>
</html>
