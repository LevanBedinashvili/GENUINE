@extends('layouts.app')

@section('title', 'GENUINE-RP.GE - ქართული SA-MP როლური თამაშის სერვერი')

@section('meta_tags')
<meta name="description" content="GENUINE-RP.GE - ქართული SA-MP სერვერი. როლური თამაში, 300+ ავტომობილი, ქონების სისტემა, ფრაქციები, კაზინო, მეტრო სისტემა. Windows & Android. უფასო ლაუნჩერი.">
<meta name="keywords" content="SA-MP, ქართული სერვერი, როლური თამაში, GTA, Genuine RP, გეიმინგი, მულტიპლეერი">
<meta name="author" content="Levan Bedinashvili">
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<meta name="canonical" href="https://genuine-rp.ge">

<meta property="og:title" content="GENUINE-RP.GE - ქართული SA-MP როლური თამაშის სერვერი">
<meta property="og:description" content="შეუერთდით საუკეთესო ქართულ SA-MP სერვერს! 300+ ავტომობილი, ფრაქციები, კაზინო, მეტრო და კიდევ ბევრი.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://genuine-rp.ge">
<meta property="og:image" content="{{ asset('assets/images/logo.png') }}">
<meta property="og:site_name" content="GENUINE-RP.GE">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="GENUINE-RP.GE - ქართული SA-MP სერვერი">
<meta name="twitter:description" content="შეუერთდით საუკეთესო ქართულ SA-MP სერვერს!">
<meta name="twitter:image" content="{{ asset('assets/images/logo.png') }}">

<meta name="theme-color" content="#FF891C">
<meta name="msapplication-TileColor" content="#FF891C">
<meta name="language" content="Georgian">
<meta name="revisit-after" content="7 days">
@endsection

@section('content')
<div class="loading-screen" id="loadingScreen">
    <div class="loading-logo">
        <img src="{{ asset('assets/images/logo.png') }}" alt="GenuineRP.ge" style="height: 60px; width: auto;">
    </div>
    <div class="loading-bar-container">
        <div class="loading-bar" id="loadingBar"></div>
    </div>
    <div class="loading-text" id="loadingText">იტვირთება...</div>
</div>

<div class="particles-container" id="particlesContainer"></div>

<section class="hero">
    <video id="background-video" class="hero-video" autoplay muted loop playsinline>
        <source src="{{ asset('assets/images/video.mp4') }}" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    
    <div class="hero-overlay"></div>
    
    <div class="hero-content">
        <h1 class="hero-title">
            <span class="title-line-1">მოგესალმებით</span>
            <span class="title-line-2">GENUINE <span class="highlight">ROLE PLAY</span>-ზე</span>
        </h1>
    </div>
</section>

<div class="container">

    <section id="how-to-start">
        <h2 class="section-title scroll-animate">როგორ დავიწყოთ თამაში?</h2>
        
        <div class="steps-container">
            <div class="step-card scroll-animate">
                <div class="step-number">1</div>
                <div class="step-icon">
                    <i class="fas fa-download"></i>
                </div>
                <h3>ჩამოტვირთეთ ლაუნჩერი</h3>
                <p>ჩამოტვირთეთ და დააინსტალირეთ ჩვენი უახლესი ლაუნჩერი</p>
            </div>
            
            <div class="step-card scroll-animate">
                <div class="step-number">2</div>
                <div class="step-icon">
                    <i class="fas fa-server"></i>
                </div>
                <h3>შემოგვიერთდით სერვერზე</h3>
                <p>შემოგვიერთდით სერვერზე, შექმენით ახალი პერსონაჟი და დაიწყეთ თამაში</p>
            </div>
            
            <div class="step-card scroll-animate">
                <div class="step-number">3</div>
                <div class="step-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <h3>დაიწყეთ როლური თამაში</h3>
                <p>თქვენ იხილავთ სამყაროს, სადაც თითოეული ქმედება მნიშვნელოვანია</p>
            </div>
        </div>
    </section>

    <section id="launcher">
        <div class="launcher-container">
            <div class="launcher-content">
                <div class="launcher-text scroll-animate-scale">
                    <h2>ლაუნჩერის ჩამოტვირთვა</h2>
                    <p>ჩვენი ლაუნჩერი ხელმისაწვდომია სრულიად უფასოდ WINDOWS ოპერაციული სისტემისთვის</p>
                    
                    <div class="system-requirements">                 
                        <h3>სისტემური მოთხოვნები</h3> 
                        
                        <div class="platform-tabs">
                            <button class="platform-tab active" data-platform="windows">
                                <i class="fab fa-windows"></i> Windows
                            </button>
                            <button class="platform-tab" data-platform="android">
                                <i class="fab fa-android"></i> Android
                            </button>
                        </div>
                        
                        <div class="requirements-content">
                            <div class="requirement-item">
                                <span class="req-label">ოპერაციული სისტემა</span>
                                <span class="req-value">Windows 10 (64 ბიტი)</span>
                            </div>
                            <div class="requirement-item">
                                <span class="req-label">პროცესორი</span>
                                <span class="req-value">Intel Core I3</span>
                            </div>
                            <div class="requirement-item">
                                <span class="req-label">ოპერატიული მეხსიერება</span>
                                <span class="req-value">4 GB რამი</span>
                            </div>
                            <div class="requirement-item">
                                <span class="req-label">ვიდეო ბარათი</span>
                                <span class="req-value">2 GB VRAM-დან</span>
                            </div>
                            <div class="requirement-item">
                                <span class="req-label">დისკის სივრცე</span>
                                <span class="req-value">მაქსიმუმ 10 GB</span>
                            </div>
                        </div>
                        
                        <span style="color: red;">გაუშვით ინსტალატორი RUN AS ADMINISTRATOR-ით!</span>
                        <a class="download-btn" href="{{ asset('assets/launcherexe/Genuine-RP-Launcher-Setup-1.1.7.exe') }}" target="_blank">
                            <i class="fas fa-download"></i> ჩამოტვირთვა
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="features-section">
        <div class="container">
            <h2 class="section-title scroll-animate">ჩვენს შესახებ</h2>
            <p class="section-subtitle scroll-animate">ინფორმაცია პროექტის და ვირტუალური შესაძლებლობების შესახებ</p>
            
            <div class="features-grid">
                <div class="feature-card scroll-animate-left">
                    <div class="feature-icon"><i class="fas fa-briefcase"></i></div>
                    <h3>სამუშაოები</h3>
                    <p>პოლიციელიდან ბიზნესმენამდე, აირჩიეთ 20-ზე მეტი რეალისტური კარიერული გზა.</p>
                </div>
                
                <div class="feature-card scroll-animate">
                    <div class="feature-icon"><i class="fas fa-car"></i></div>
                    <h3>ავტომობილები</h3>
                    <p>იმოძრავეთ ექსკლუზიური ავტომობილებით. 300+ დამატებული ავტომობილი.</p>
                </div>
                
                <div class="feature-card scroll-animate-right">
                    <div class="feature-icon"><i class="fas fa-home"></i></div>
                    <h3>ქონების სისტემა</h3>
                    <p>იყიდეთ, გაყიდეთ ან ივაჭრეთ თქვენი უძრავი ქონებოთ. 300+ ქონება ხელმისაწვდომია</p>
                </div>
                
                <div class="feature-card scroll-animate-left">
                    <div class="feature-icon"><i class="fas fa-tshirt"></i></div>
                    <h3>პერსონაჟის ვიზუალი</h3>
                    <p>200+ ტანსაცმლის ნივთი და ექსკლუზიური აქსესუარები.</p>
                </div>
                
                <div class="feature-card scroll-animate">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h3>ფრაქციების სისტემა</h3>
                    <p>შეუერთდით ორგანიზებულ ჯგუფებს უნიკალური ისტორიებით, რანგებითა და მიზნებით</p>
                </div>
                
                <div class="feature-card scroll-animate-right">
                    <div class="feature-icon"><i class="fas fa-trophy"></i></div>
                    <h3>ივენთები</h3>
                    <p>მიიღეთ მონაწილეობა ყოველდღიურ ივენთებში და ტურნირებში</p>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection

@section('scripts')
<script>
    function initLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        const loadingBar = document.getElementById('loadingBar');
        const loadingText = document.getElementById('loadingText');
        
        let progress = 0;
        const loadingMessages = ['იტვირთება...', 'იტვირთება...', 'იტვირთება...', 'თითქმის მზადა...'];
        
        const interval = setInterval(() => {
            progress += Math.random() * 15 + 5;
            if (progress > 100) progress = 100;
            
            loadingBar.style.width = progress + '%';
            
            const messageIndex = Math.floor((progress / 100) * loadingMessages.length);
            if (messageIndex < loadingMessages.length) {
                loadingText.textContent = loadingMessages[messageIndex];
            }
            
            if (progress >= 100) {
                clearInterval(interval);
                setTimeout(() => {
                    loadingScreen.classList.add('hidden');
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                    }, 500);
                }, 500);
            }
        }, 100);
    }

    function initScrollAnimations() {
        const animatedElements = document.querySelectorAll('.scroll-animate, .scroll-animate-left, .scroll-animate-right, .scroll-animate-scale, .scroll-animate-fade');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        animatedElements.forEach(element => {
            observer.observe(element);
        });
    }

    function initParticles() {
        const container = document.getElementById('particlesContainer');
        if (!container) return;
        
        const particleCount = 50;
        
        for (let i = 0; i < particleCount; i++) {
            createParticle();
        }
        
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 6 + 's';
            particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
            
            container.appendChild(particle);
            
            setTimeout(() => {
                particle.remove();
                createParticle();
            }, 6000);
        }
    }

    window.addEventListener('load', function() {
        initLoadingScreen();
        initScrollAnimations();
        initParticles();
        
        const video = document.getElementById('background-video');
        if (video) {
            video.play().catch(error => {
                console.log('Auto-play prevented: ', error);
            });
        }
    });
</script>
@endsection
