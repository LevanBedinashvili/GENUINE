<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/images/favicon.ico') }}">
    <title>@yield('title', 'GENUINE-RP.GE - ქართული SA-MP როლური თამაშის სერვერი')</title>

    @yield('meta_tags')

    <link rel="stylesheet" href="https://cdn.web-fonts.ge/fonts/bpg-arial-caps/css/bpg-arial-caps.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    
    <style>
        * {
            font-family: "BPG Arial Caps", sans-serif !important;
        }
        body {
            font-family: "BPG Arial Caps", sans-serif !important;
        }
        main {
            font-family: "BPG Arial Caps", sans-serif !important;
        }
        header {
            font-family: "BPG Arial Caps", sans-serif !important;
        }
        .download-btn {
            font-family: "BPG Arial Caps", sans-serif !important;
        }
        .loading-screen {
            font-family: "BPG Arial Caps", sans-serif !important;
        }
        .footer-bottom-left {
            font-family: "BPG Arial Caps", sans-serif !important;
        }
        /* Font Awesome აიქონების მხარდაჭერა */
        .fa, .fas, .fab, .far, .fal, .fad {
            font-family: "Font Awesome 6 Free" !important;
        }
        [class*="fa-"]:before, [class*="fa-"]:after {
            font-family: "Font Awesome 6 Free" !important;
        }
    </style>

    @yield('additional_styles')
</head>
<body>
    <a href="#main-content" class="skip-link">გადახტომა მთავარ შინაარსზე</a>
    
    <div class="scroll-progress" id="scrollProgress"></div>

    <button class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <header role="banner">
        <div class="container">
            <div class="header-content">
                <a href="{{ route('home') }}" class="logo" aria-label="Genuine RP Georgia Homepage">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Genuine RP Georgia Logo" class="logo-img">
                </a>
                <nav class="nav-menu" role="navigation" aria-label="Main navigation">
                    <ul>
                        <li><a href="{{ route('home') }}#how-to-start" aria-label="შეიტყვეთ როგორ დაიწყოთ თამაში">თამაში</a></li>
                        <li><a href="{{ route('home') }}#launcher" aria-label="ჩამოტვირთეთ თამაშის ლაუნჩერი">ლაუნჩერი</a></li>
                        <li><a href="{{ route('shop') }}" aria-label="ეწვიეთ მაღაზიას">მაღაზია</a></li>
                        <li><a href="http://forum.genuine-rp.ge/" target="_blank" aria-label="ეწვიეთ ფორუმს">ფორუმი</a></li>
                        <li><a href="https://discord.gg/nGbVdyBg" target="_blank" aria-label="ეწვიეთ დისქორდს">დისქორდი</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <main id="main-content">
        @yield('content')
    </main>
    
    <footer id="footer">
        <div class="footer-content">
            <div class="footer-bottom">
                <img src="{{ asset('assets/images/logo.png') }}" alt="GenuineRP.ge" style="height: 32px; width: auto;">
                <div class="footer-bottom-left">
                    <a href="{{ route('agreement') }}">სამომხმარებლო შეთანხმება</a>
                    <a href="{{ route('privacy') }}">კონფიდენციალურობის პოლიტიკა</a>
                    <a href="mailto:genuineprojectx@gmail.com">genuineprojectx@gmail.com</a>
                    <span>© Genuine Project © 2026</span>
                </div>
                <div class="age-rating">18+</div>
            </div>
        </div>
    </footer>

    @yield('scripts')

    <script>
        // Common header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            const scrollPosition = window.scrollY;
            
            if (scrollPosition > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Back to top button
        function initBackToTop() {
            const backToTop = document.getElementById('backToTop');
            
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });
            
            backToTop.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Scroll progress bar
        function initScrollProgress() {
            const progressBar = document.getElementById('scrollProgress');
            if (!progressBar) {
                return;
            }

            const updateProgress = () => {
                const scrollTop = window.pageYOffset;
                const docHeight = document.body.scrollHeight - window.innerHeight;
                const scrollPercent = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
                progressBar.style.width = scrollPercent + '%';

                if (scrollPercent > 0) {
                    progressBar.classList.add('active');
                } else {
                    progressBar.classList.remove('active');
                }
            };
            
            window.addEventListener('scroll', updateProgress);
            updateProgress();
        }

        // Initialize on load
        window.addEventListener('load', function() {
            initBackToTop();
            initScrollProgress();
        });
    </script>
</body>
</html>
</html>
