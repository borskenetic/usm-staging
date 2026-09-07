<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Library catalog — OPAC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/books/landing.css') }}?v=opac-search-responsive-1">
    <link rel="stylesheet" href="{{ asset('css/site-responsive.css') }}">
    @include('components.branding-overrides')
    <script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="opac-body">
    @if($searchActive)
        <header class="opac-search-header" role="banner">
            <div class="opac-search-header-inner">
                <div class="opac-search-brand">
                    <img class="opac-search-logo" src="{{ $brandingOpacLogoUrl }}" alt="Library logo">
                    <div class="opac-search-title">Governor Generoso College of Arts, Sciences and Technology</div>
                </div>

                <form method="GET" action="{{ route('landing') }}" class="opac-search-header-form" aria-label="Search">
                    <input type="hidden" name="view" value="{{ $viewMode ?? 'books' }}">
                    <input type="hidden" name="course" value="{{ request('course', 'all') }}">
                    <input type="hidden" name="content_type" value="{{ request('content_type', 'All') }}">
                    <input type="hidden" name="section" value="{{ request('section', 'All') }}">
                    <input type="hidden" name="subject_topic" value="{{ request('subject_topic', 'All') }}">
                    <div class="opac-search-header-row">
                        <input id="searchBar" type="search" name="search" value="{{ request('search') }}"
                            class="form-control opac-search-input"
                            placeholder="{{ ($viewMode ?? 'books') === 'ebooks' ? 'Search e-books…' : 'Search books…' }}"
                            autocomplete="off"
                            aria-label="Search catalog">
                        <button type="submit" class="btn btn-success opac-search-btn">Search</button>
                    </div>
                    <div class="opac-search-clear small">
                        <a href="{{ route('landing', ['view' => ($viewMode ?? 'books')]) }}">Clear search</a>
                    </div>
                </form>
            </div>
        </header>
    @else
        <header class="opac-public-header opac-header-bar opac-header-sticky">
            <div class="logo opac-logo-wrap">
                <a href="{{ route('landing') }}" class="text-decoration-none d-inline-flex align-items-center">
                    <img src="{{ $brandingSidebarLogoUrl }}" alt="Library Logo">
                </a>
            </div>
            <span class="opac-nav-divider" aria-hidden="true"></span>
            <nav class="opac-top-nav" aria-label="Quick links">
                <a href="{{ route('home') }}" class="opac-nav-link">Home</a>
                <a href="{{ route('kiosk.scan') }}" class="opac-nav-link">Student lookup</a>
                <a href="{{ route('landing') }}" class="opac-nav-link opac-nav-link--active">Catalog</a>
            </nav>
            <form action="{{ route('logout') }}" method="POST" class="mb-0" hidden>
                @csrf
                <button type="submit" class="logout-btn" onclick="logout()" style="margin-right: 60px;">Logout</button>
            </form>
        </header>
    @endif

    <div class="opac-page-fill flex-grow-1">
    @unless($searchActive)
        <section class="hero-text">
            <img src="{{ $brandingOpacBannerUrl }}" alt="Banner" class="banner-img">
        </section>
    @endunless

    @unless($searchActive)
    <section class="opac-search-block px-3 px-md-4 py-4" aria-labelledby="opac-search-heading">
        <h2 id="opac-search-heading" class="opac-search-heading text-center mb-1">Search the Library Catalog</h2>
        <p class="text-center text-muted mb-3 opac-search-subheading">Find books, references, and resources available at the library</p>

        <div class="opac-search-tabs mb-3" role="tablist" aria-label="Catalog type">
            <a href="{{ route('landing', ['view' => 'books']) }}"
               class="opac-search-tab {{ ($viewMode ?? 'books') !== 'ebooks' ? 'opac-search-tab--active' : '' }}"
               role="tab" aria-selected="{{ ($viewMode ?? 'books') !== 'ebooks' ? 'true' : 'false' }}">
                Books
            </a>
            <a href="{{ route('landing', ['view' => 'ebooks']) }}"
               class="opac-search-tab {{ ($viewMode ?? 'books') === 'ebooks' ? 'opac-search-tab--active' : '' }}"
               role="tab" aria-selected="{{ ($viewMode ?? 'books') === 'ebooks' ? 'true' : 'false' }}">
                E-Books
            </a>
        </div>

        <form method="GET" action="{{ route('landing') }}" class="opac-search-form mx-auto">
            <input type="hidden" name="view" value="{{ $viewMode ?? 'books' }}">
            <div class="opac-search-input-wrap mb-2">
                <svg class="opac-search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="2"/>
                    <path d="M13 13l3.5 3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <input id="searchBar" type="search" name="search" value="{{ request('search') }}"
                    class="form-control opac-search-main-input"
                    placeholder="{{ ($viewMode ?? 'books') === 'ebooks' ? 'Search e-books by title, author, or keyword…' : 'Search books by title, author, or keyword…' }}"
                    autocomplete="off"
                    autofocus
                    aria-label="Search catalog">
                <button type="submit" class="btn opac-search-submit-btn">Search</button>
            </div>
        </form>

        <p class="text-center text-muted small mt-2 mb-0 mx-auto opac-search-hint">
            Catalog results and filters appear after you search. New arrivals are below.
        </p>
    </section>
    @endunless

    @unless($searchActive)
    <section class="opac-new-arrivals-block">
        <div class="opac-arrivals-header">
            <h2 class="opac-arrivals-title">New Arrival Books</h2>
        </div>

        <div class="opac-arrivals-list" id="carouselTrack" role="list">
            @forelse ($carouselBooks as $book)
                @php
                    $cMeta = $carouselMeta[$book->id] ?? ['copies' => 1, 'is_available' => $book->availability === 'Available'];
                    $cAvail = ($cMeta['is_available'] ?? false) ? 'Available' : 'Not Available';
                    $coverUrl = $book->cover_image ? asset('storage/'.$book->cover_image) : '';
                @endphp
                <div class="opac-result-row opac-arrival-row"
                    role="listitem"
                    data-img="{{ $coverUrl }}"
                    data-title="{{ $book->title_statement }}"
                    data-author="{{ $book->main_author }}"
                    data-note="{{ $book->general_note }}"
                    data-call="{{ $book->call_number }}"
                    data-id="{{ $book->id }}"
                    data-year="{{ $book->pub_year }}"
                    data-availability="{{ $cAvail }}"
                    data-copies="{{ $cMeta['copies'] }}"
                    data-content="{{ $book->content_type }}"
                    data-fixed="{{ $book->fixed_length_data }}"
                    data-library="{{ $book->library_name }}"
                    data-course="{{ $book->course ?? '' }}"
                    onclick="openBookCard(this)"
                    tabindex="0"
                    aria-label="{{ $book->title_statement }}">

                    <div class="opac-result-cover{{ $coverUrl ? '' : ' is-empty' }}">
                        @if ($coverUrl)
                            <img src="{{ $coverUrl }}" alt="">
                        @endif
                    </div>
                    <div class="opac-result-meta">
                        <div class="opac-result-title">
                            <a href="javascript:void(0)" class="opac-result-title-link">
                                {{ $book->title_statement }}
                            </a>
                            @if ($book->pub_year)
                                <span class="text-muted">({{ $book->pub_year }})</span>
                            @endif
                        </div>
                        <div class="opac-result-sub small text-muted">
                            @if ($book->main_author)
                                By {{ $book->main_author }}
                            @endif
                        </div>
                        <div class="opac-result-availability small {{ ($cMeta['is_available'] ?? false) ? 'text-success' : 'text-danger' }}">
                            {{ $cAvail }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No new arrivals to show yet.</p>
            @endforelse
        </div>
    </section>
    @endunless

    @if($searchActive)
    <div class="layout opac-results-shell">
        <aside class="opac-facets" aria-label="Filters">
            <div class="opac-facet-card opac-facet-card--primary">
                <div class="opac-facet-title">Library Catalog</div>
                <div class="opac-facet-item is-active">
                    {{ ($viewMode ?? 'books') === 'ebooks' ? 'E-Books' : 'Books' }}
                </div>
                <a class="opac-facet-link" href="{{ route('landing', array_merge(request()->except('page'), ['view' => (($viewMode ?? 'books') === 'ebooks' ? 'books' : 'ebooks')])) }}">
                    View {{ ($viewMode ?? 'books') === 'ebooks' ? 'Books' : 'E-Books' }}
                </a>
            </div>

            <div class="opac-facet-card">
                <div class="opac-facet-title">Searching</div>
                <div class="opac-facet-sub text-muted small">
                    {{ ($viewMode ?? 'books') === 'ebooks' ? ($ebooks?->total() ?? 0) : $books->total() }} Results
                </div>
            </div>

            @if(($viewMode ?? 'books') !== 'ebooks')
            <div class="opac-facet-card">
                <div class="opac-facet-title">Format</div>
                <form method="GET" action="{{ route('landing') }}" class="opac-facet-form">
                    <input type="hidden" name="view" value="books">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="course" value="{{ request('course', 'all') }}">
                    <input type="hidden" name="section" value="{{ request('section', 'All') }}">
                    <input type="hidden" name="subject_topic" value="{{ request('subject_topic', 'All') }}">
                    <select name="content_type" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Format">
                        <option value="All" {{ request('content_type', 'All') === 'All' ? 'selected' : '' }}>All resources</option>
                        @foreach ($content_type as $ct)
                            <option value="{{ $ct }}" {{ request('content_type') == $ct ? 'selected' : '' }}>{{ $ct }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="opac-facet-card">
                <div class="opac-facet-title">Section</div>
                <form method="GET" action="{{ route('landing') }}" class="opac-facet-form">
                    <input type="hidden" name="view" value="books">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="course" value="{{ request('course', 'all') }}">
                    <input type="hidden" name="content_type" value="{{ request('content_type', 'All') }}">
                    <input type="hidden" name="subject_topic" value="{{ request('subject_topic', 'All') }}">
                    <select name="section" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Section">
                        <option value="All" {{ request('section', 'All') === 'All' ? 'selected' : '' }}>All sections</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section }}" {{ request('section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="opac-facet-card">
                <div class="opac-facet-title">Subject</div>
                <form method="GET" action="{{ route('landing') }}" class="opac-facet-form">
                    <input type="hidden" name="view" value="books">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="course" value="{{ request('course', 'all') }}">
                    <input type="hidden" name="content_type" value="{{ request('content_type', 'All') }}">
                    <input type="hidden" name="section" value="{{ request('section', 'All') }}">
                    <select name="subject_topic" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Subject">
                        <option value="All" {{ request('subject_topic', 'All') === 'All' ? 'selected' : '' }}>All subject topics</option>
                        @foreach ($subjectTopics as $topic)
                            <option value="{{ $topic }}" {{ request('subject_topic') == $topic ? 'selected' : '' }}>
                                {{ \Illuminate\Support\Str::limit($topic, 25, '...') }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            @endif
        </aside>

        <main class="opac-results-panel">
            <div class="opac-results-head">
                <div class="opac-results-head-left">
                    <div class="opac-results-kicker small text-muted">
                        {{ ($viewMode ?? 'books') === 'ebooks' ? 'E-books' : 'Search results' }}
                    </div>
                </div>
            </div>

            @if((($viewMode ?? 'books') === 'ebooks' ? ($ebooks?->total() ?? 0) : $books->total()) === 0)
                <p class="text-center text-muted py-5">No titles matched your search. Try different keywords.</p>
            @endif

            <div class="opac-results-list" id="bookGrid">
                @if(($viewMode ?? 'books') === 'ebooks')
                    @foreach (($ebooks ?? []) as $eb)
                        <a class="opac-result-row opac-result-row--ebook"
                           href="{{ $eb->link ?: 'javascript:void(0)' }}"
                           target="_blank"
                           rel="noopener"
                           onclick="{{ $eb->link ? '' : 'return false;' }}">
                            <div class="opac-result-cover">
                                <img src="{{ $brandingOpacDefaultBookCoverUrl }}" alt="">
                            </div>
                            <div class="opac-result-meta">
                                <div class="opac-result-title">
                                    <span class="opac-result-title-link">{{ $eb->title }}</span>
                                    @if($eb->publication_year)
                                        <span class="text-muted">({{ $eb->publication_year }})</span>
                                    @endif
                                </div>
                                <div class="opac-result-sub small text-muted">
                                    @if($eb->author)
                                        By {{ $eb->author }}
                                    @endif
                                </div>
                                @if($eb->source)
                                    <div class="small text-muted">{{ $eb->source }}</div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                @else
                    @foreach ($books as $book)
                    <div class="opac-result-row"
                        data-img="{{ $book->cover_image ? asset('storage/' . $book->cover_image) : '' }}"
                        data-title="{{ $book->title_statement }}"
                        data-author="{{ $book->main_author }}"
                        data-note="{{ $book->general_note }}"
                        data-call="{{ $book->call_number }}"
                        data-id="{{ $book->id }}"
                        data-year="{{ $book->pub_year }}"
                        data-copies="{{ $book->copies }}"
                        data-availability="{{ $book->is_available == 1 ? 'Available' : 'Not Available' }}"
                        data-content="{{ $book->content_type }}"
                        data-fixed="{{ $book->fixed_length_data }}"
                        data-library="{{ $book->library_name }}"
                        data-course="{{ $book->course ?? '' }}"
                        onclick="openBookCard(this)">
                        <div class="opac-result-cover{{ $book->cover_image ? '' : ' is-empty' }}">
                            @if ($book->cover_image)
                                <img src="{{ asset('storage/' . $book->cover_image) }}" alt="">
                            @endif
                        </div>
                        <div class="opac-result-meta">
                            <div class="opac-result-title">
                                <a href="javascript:void(0)" class="opac-result-title-link">
                                    {{ $book->title_statement }}
                                </a>
                                @if($book->pub_year)
                                    <span class="text-muted">({{ $book->pub_year }})</span>
                                @endif
                            </div>
                            <div class="opac-result-sub small text-muted">
                                @if($book->main_author)
                                    By {{ $book->main_author }}
                                @endif
                            </div>
                            <div class="opac-result-availability small {{ $book->is_available == 1 ? 'text-success' : 'text-danger' }}">
                                {{ $book->is_available == 1 ? 'Available' : 'Not Available' }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            @if(($viewMode ?? 'books') === 'ebooks')
                @if(($ebooks?->total() ?? 0) > 0)
                <div class="d-flex justify-content-center mt-4">
                    {{ $ebooks->links('pagination::bootstrap-5') }}
                </div>
                @endif
            @else
                @if($books->total() > 0)
            <div class="d-flex justify-content-center mt-4">
                {{ $books->links('pagination::bootstrap-5') }}
            </div>
                @endif
            @endif
        </main>
    </div>
    @endif

    <div class="modal" id="bookModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-content modal-wide opac-record-modal">
            <span class="close" onclick="closeModal()" aria-label="Close">&times;</span>

            <div id="opacDetailLoading" class="opac-detail-loading py-5 text-center text-muted">Loading record…</div>

            <div id="opacDetailContent" class="opac-detail-content" style="display: none;">
                <p id="opacBreadcrumb" class="opac-breadcrumb small mb-2" aria-label="Context"></p>

                <div class="opac-detail-body modal-body-flex">
                    <div class="modal-left opac-detail-cover-col">
                        <img id="modalImg" src="" alt="Book cover">
                    </div>
                    <div class="modal-right opac-detail-main">
                        <h2 id="modalTitle" class="h4 mb-1"></h2>
                        <p id="modalAuthor" class="text-muted mb-3"></p>
                        <table class="table table-sm table-borderless opac-bib-table mb-0">
                            <tbody id="opacBibSummary"></tbody>
                        </table>
                    </div>
                </div>

                <div class="opac-tabs" role="tablist">
                    <button type="button" class="opac-tab is-active" data-tab="holdings" role="tab" aria-selected="true">Holdings</button>
                    <button type="button" class="opac-tab" data-tab="description" role="tab" aria-selected="false">Description</button>
                    <button type="button" class="opac-tab" data-tab="marc" role="tab" aria-selected="false">MARC View</button>
                </div>

                <div class="opac-tab-panels border-top">
                    <div id="opacTabHoldings" class="opac-tab-panel is-active pt-3" role="tabpanel">
                        <p class="opac-library-location small mb-2" id="opacHoldingsLibraryLine"></p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm opac-holdings-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Accession #</th>
                                        <th>Call #</th>
                                        <th>Volume / Part #</th>
                                        <th>Copy #</th>
                                        <th>Collection</th>
                                        <th>Shelving location</th>
                                        <th>Circulation type</th>
                                        <th>Circulation status</th>
                                        <th>Barcode</th>
                                        <th>RFID</th>
                                        <th>Add to cart</th>
                                    </tr>
                                </thead>
                                <tbody id="holdingsTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div id="opacTabDescription" class="opac-tab-panel pt-3" role="tabpanel">
                        <dl class="opac-desc-dl mb-0" id="descriptionDl"></dl>
                    </div>
                    <div id="opacTabMarc" class="opac-tab-panel pt-3" role="tabpanel">
                        <p class="small text-muted mb-2">Same layout as staff book view; only tags with a value that matches on every copy of this title are shown. Use <strong>Holdings</strong> when values differ by copy.</p>
                        <div class="table-responsive opac-marc-view-wrap">
                            <table class="table table-sm table-borderless opac-marc-view-table mb-0">
                                <tbody id="marcViewTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="studentModal">
        <div class="modal-content">
            <span class="close" onclick="closeStudentModal()">&times;</span>

            <h4>Self Check-Out</h4>

            <div class="mb-3">
                <label for="studentIdInput" class="form-label"><strong>Student ID</strong></label>
                <input type="text" id="studentIdInput" class="form-control" placeholder="Enter your Student ID">
            </div>

            <button type="button" class="btn btn-primary mt-3" onclick="confirmCheckout()">
                Confirm Checkout
            </button>

            <p id="studentError" class="text-danger mt-2" style="display:none;"></p>
        </div>
    </div>

    <button id="cartButton" type="button" onclick="openCartModal()" style="position:fixed; bottom:30px; right:30px; z-index:999;
                       padding:12px 20px; border-radius:50px;" class="btn btn-dark">
        Cart (<span id="cartCount">0</span>)
    </button>

    <div class="modal" id="cartModal">
        <div class="modal-content cart-modal-clean">
            <span class="close" onclick="closeCartModal()">&times;</span>

            <div class="cart-header">
                <h2>Borrow Cart</h2>
                <p>Maximum of 5 books allowed</p>
            </div>

            <div id="cartBody" class="cart-body">
                <ul id="cartList" class="cart-list"></ul>

                <div id="emptyCart" class="empty-cart" style="display:none;">
                    Your cart is empty.
                </div>
            </div>

            <div class="cart-footer">
                <div class="cart-count">
                    Total Books: <strong id="cartTotal">0</strong>
                </div>

                <button type="button" class="btn btn-dark px-5" onclick="openStudentModalFromCart()">
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>

    </div>

    <script>
        window.CHECKOUT_URL = "{{ route('checkout.process') }}";
        window.CSRF_TOKEN = "{{ csrf_token() }}";
        window.OPAC_BOOK_DETAIL_BASE = @json(url('/opac/api/book').'/');

        function logout() {
            Swal.fire({
                title: 'Are you sure you want to log out?',
                text: "You will be returned to the login screen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1f4ea7', // matching brand-navy color
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Temporarily disable the function to avoid recursion and submit
                    document.querySelector('header form[action*="logout"]').submit();
                }
            });
        }

        // Sticky nav elevated shadow on scroll
        (function () {
            var header = document.querySelector('.opac-header-sticky');
            if (!header) return;
            window.addEventListener('scroll', function () {
                header.classList.toggle('scrolled', window.scrollY > 10);
            }, { passive: true });
        })();
    </script>
    <script src="{{ asset('js/cart.js') }}"></script>
    <script src="{{ asset('js/landings.js') }}"></script>
</body>

</html>
