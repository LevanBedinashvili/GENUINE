@extends('layouts.app')

@section('title', 'მაღაზია - GENUINE-RP.GE')

@section('meta_tags')
<meta name="description" content="GENUINE-RP.GE მაღაზია - შეიძინეთ ვირტუალური ფული, G-COIN და სხვა ნივე">
<meta name="robots" content="index, follow">
<meta name="canonical" href="https://genuine-rp.ge/shop">
@endsection

@section('additional_styles')
<style>
    .shop-container {
        max-width: 1200px;
        margin: 100px auto;
        padding: 40px 20px;
    }

    #shop {
        min-height: calc(100vh - 300px);
    }

    .shop-title {
        font-size: 42px;
        text-align: center;
        margin-bottom: 10px;
        color: #fff;
    }

    .shop-subtitle {
        text-align: center;
        color: #bbb;
        margin-bottom: 40px;
        font-size: 16px;
    }

    .category-tabs {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .category-btn {
        background: rgba(26, 26, 26, 0.5);
        border: 2px solid rgba(255, 255, 255, 0.1);
        color: #bbb;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
    }

    .category-btn:hover,
    .category-btn.active {
        border-color: #FF891C;
        color: #FF891C;
        background: rgba(255, 137, 28, 0.1);
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .product-card {
        background: rgba(26, 26, 26, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .product-card:hover {
        border-color: rgba(255, 137, 28, 0.5);
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(255, 137, 28, 0.2);
    }

    .product-image {
        font-size: 48px;
        color: #FF891C;
        margin-bottom: 15px;
    }

    .product-card h3 {
        color: #fff;
        font-size: 16px;
        margin: 10px 0;
    }

    .buy-btn {
        background: linear-gradient(135deg, #FF891C, #FF6B1C);
        border: none;
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        width: 100%;
        transition: all 0.3s ease;
        margin-top: 10px;
    }

    .buy-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(255, 137, 28, 0.5);
    }

    /* Modal Overlay */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active {
        display: flex;
    }

    /* Modal Panel */
    .modal__panel {
        background: rgba(26, 26, 26, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 30px;
        max-width: 400px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        backdrop-filter: blur(10px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    /* Modal Header */
    .modal__top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modal__title {
        color: #fff;
        font-size: 22px;
        font-weight: 600;
        margin: 0;
    }

    .modal__close {
        background: none;
        border: none;
        color: #bbb;
        font-size: 24px;
        cursor: pointer;
        transition: color 0.3s ease;
        padding: 0;
    }

    .modal__close:hover {
        color: #FF891C;
    }

    /* Modal Body */
    .modal__body {
        padding: 15px 0 0 0;
    }

    /* Form Labels */
    .form-label {
        display: block;
        color: #ddd;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Form Inputs */
    .form-input {
        width: 100%;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #fff;
        padding: 11px 32px 11px 36px;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        box-sizing: border-box;
        position: relative;
        z-index: 1;
        height: 40px;
    }

    .form-input:focus {
        outline: none;
        background: rgba(255, 255, 255, 0.12);
        border-color: #FF891C;
        box-shadow: 0 0 15px rgba(255, 137, 28, 0.2);
    }

    .form-input:disabled {
        background: rgba(255, 255, 255, 0.05);
        cursor: not-allowed;
        opacity: 0.7;
        color: #aaa;
    }

    /* Input Icon */
    .input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #FF891C;
        font-size: 14px;
        pointer-events: none;
        z-index: 2;
    }

    /* Username Input Section */
    .username-input {
        position: relative;
        margin-bottom: 8px;
    }

    #usernameStatus {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 16px;
        z-index: 2;
        display: none;
    }

    #usernameStatusIcon {
        display: inline-block;
        transition: all 0.3s ease;
    }

    #usernameStatusIcon.success {
        color: #4CAF50;
    }

    #usernameStatusIcon.error {
        color: #F44336;
    }

    #usernameStatusIcon.loading {
        color: #FFA500;
        animation: spin 0.8s linear infinite;
    }

    /* Username Validation States */
    #username.valid {
        border-color: #4CAF50 !important;
        background: rgba(76, 175, 80, 0.08) !important;
    }

    #username.invalid {
        border-color: #F44336 !important;
        background: rgba(244, 67, 54, 0.08) !important;
    }

    #username.validating {
        border-color: #FFA500 !important;
        background: rgba(255, 165, 0, 0.08) !important;
    }

    /* Status Messages */
    #usernameMessage,
    #amountMessage {
        font-size: 12px;
        margin-top: 6px;
        margin-bottom: 0;
        min-height: 18px;
        line-height: 1.4;
        color: #999;
        transition: all 0.3s ease;
    }

    #usernameMessage.success,
    #amountMessage.success {
        color: #4CAF50;
        font-weight: 500;
    }

    #usernameMessage.error,
    #amountMessage.error {
        color: #F44336;
        font-weight: 500;
    }

    /* Input Wrapper */
    .input-wrapper {
        position: relative;
        margin-bottom: 16px;
    }

    /* Currency Fields Section */
    #currencyFields {
        margin-top: 16px;
    }

    /* Agreement Section */
    .modal-agree {
        margin: 20px 0;
        padding: 15px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modal-agree__row {
        display: flex;
        align-items: flex-start;
        color: #ddd;
        cursor: pointer;
        font-size: 12px;
        line-height: 1.6;
        gap: 10px;
    }

    .modal-agree__row input {
        margin-top: 3px;
        cursor: pointer;
        accent-color: #FF891C;
        flex-shrink: 0;
    }

    .modal-agree__row a {
        color: #FF891C;
        text-decoration: none;
        transition: opacity 0.3s ease;
    }

    .modal-agree__row a:hover {
        opacity: 0.8;
        text-decoration: underline;
    }

    /* reCAPTCHA Section */
    .captcha-section {
        margin: 20px 0;
        text-align: center;
    }

    .g-recaptcha {
        display: inline-block;
        background: transparent;
        border-radius: 8px;
        overflow: hidden;
    }

    .error-message {
        font-size: 12px;
        margin-top: 8px;
        color: #F44336;
        font-weight: 500;
    }

    /* Submit Button */
    .submit-btn {
        width: 100%;
        background: linear-gradient(135deg, #FF891C 0%, #FF6B1C 100%);
        border: none;
        color: white;
        padding: 14px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 15px;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-top: 20px;
    }

    .submit-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(255, 137, 28, 0.4);
    }

    .submit-btn:active:not(:disabled) {
        transform: translateY(0);
    }

    .submit-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Spinner Animation */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .loading-spinner {
        text-align: center;
        padding: 40px;
    }

    .spinner {
        border: 4px solid rgba(255, 137, 28, 0.2);
        border-top: 4px solid #FF891C;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 15px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .shop-container {
            margin-top: 80px;
        }

        .shop-title {
            font-size: 28px;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }

        .modal__panel {
            max-width: 95%;
        }
    }
</style>
@endsection

@section('content')
<div class="shop-container">
    <section id="shop">
        <h1 class="shop-title">მაღაზია</h1>
        <p class="shop-subtitle">შეიძინეთ რაც გჭირდებათ ჩვენი ექსკლუზიური მაღაზიიდან</p>

        <!-- Flash Messages -->
        @if(session()->has('info'))
            <div class="alert alert-info" style="background: rgba(13, 110, 253, 0.1); border: 1px solid rgba(13, 110, 253, 0.3); color: #0d6efd; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                {{ session('info') }}
            </div>
        @endif

        @if(session()->has('warning'))
            <div class="alert alert-warning" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); color: #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                {{ session('warning') }}
            </div>
        @endif


        <!-- Category Tabs -->
        <div class="category-tabs">
            <button class="category-btn" data-category="packages">პაკები</button>
            <button class="category-btn" data-category="symbols">ტრანსპორტი</button>
            <button class="category-btn" data-category="other">სხვადასხვა</button>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid">
            <!-- Products will be rendered by JavaScript -->
        </div>

        <!-- Loading Indicator -->
        <div class="loading-spinner" id="loadingSpinner" style="display: none;">
            <div class="spinner"></div>
            <p>ჩატვირთვა...</p>
        </div>
    </section>
</div>

<!-- Modal Popup -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="productTitle">
        <div class="modal__top">
            <h3 class="modal__title" id="productTitle">პროდუქტი</h3>
            <button class="modal__close" type="button" aria-label="Close">✕</button>
        </div>

        <div class="modal__body">
            <!-- reCAPTCHA Gate - Shown Initially -->
            <div class="captcha-section" id="recaptchaGate" style="order: -1;">
                <p style="color: #bbb; font-size: 13px; margin-bottom: 15px;">ჯერ რობოტი უნდა დაადასტუროთ</p>
                @if(config('services.recaptcha.site_key'))
                    <div id="recaptcha-wrapper">
                        <div class="g-recaptcha" id="recaptcha-container" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                    </div>
                    <div id="captcha-error" class="error-message" style="display: none; color: #F44336; font-size: 12px; margin-top: 8px;">გთხოვთ დაადასტუროთ, რომ არ ხართ რობოტი</div>
                @else
                    <div class="captcha-placeholder">
                        <div style="background: rgba(255, 255, 255, 0.1); border: 2px dashed rgba(255, 255, 255, 0.3); border-radius: 8px; padding: 20px; text-align: center; color: #bbb;">
                            <i class="fas fa-shield-alt" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
                            <p style="margin: 0; font-size: 14px;">reCAPTCHA არ არის კონფიგურირებული</p>
                            <p style="margin: 5px 0 0 0; font-size: 12px; opacity: 0.7;">დამატეთ RECAPTCHA_SITE_KEY და RECAPTCHA_SECRET_KEY .env ფაილში</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Form Fields Container - Hidden Until reCAPTCHA Success -->
            <div id="formFieldsContainer" style="display: none;">
                <!-- Username Section -->
                <div id="usernameSection" style="display: block;">
                    <label class="form-label">სახელი სერვერზე</label>
                    <div class="username-input">
                        <i class="fas fa-user input-icon"></i>
                        <input class="form-input" id="username" type="text" placeholder="სახელი თამაშში" autocomplete="off">
                        <div id="usernameStatus" style="display: none;">
                            <i id="usernameStatusIcon"></i>
                        </div>
                    </div>
                    <div id="usernameMessage"></div>
                </div>

                <!-- Currency Fields Section -->
                <div id="currencyFields" style="display: none;">
                <label class="form-label" style="margin-top: 16px;">თანხა (₾)</label>
                <div class="input-wrapper">
                    <i class="fas fa-lari-sign input-icon"></i>
                    <input class="form-input" id="amount" type="number" step="1" min="1" placeholder="1">
                </div>
                <div id="amountMessage"></div>

                <label class="form-label" style="margin-top: 16px;">მიიღებთ</label>
                <div class="input-wrapper">
                    <i class="fas fa-star input-icon" id="receiveIcon"></i>
                    <input class="form-input" id="receiveAmount" type="text" disabled="">
                </div>
            </div>

            <!-- Agreement Section - Inside Form Fields Container -->
            <div class="modal-agree">
                <label class="modal-agree__row">
                    <input type="checkbox" id="agreeCheckbox">
                    <span>
                        ვეთანხმები <a href="{{ route('agreement') }}" target="_blank">სამომხმარებლო შეთანხმებას</a> და <a href="{{ route('privacy') }}" target="_blank">კონფიდენციალურობის პოლიტიკას</a>
                    </span>
                </label>
            </div>

            <!-- Submit Button - Inside Form Fields Container -->
            <button class="submit-btn" type="button" id="buyBtn" disabled="">0.00₾ • ყიდვა</button>
            </div>
            <!-- End of formFieldsContainer -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>

    const products = @json($items);
    
    const categories = @json($categories);

    let currentModal = null;
    let selectedCategory = null;
    let recaptchaWidgetId = null;
    let recaptchaCompleted = false;
    
    const currencyCategory = categories.find(cat => cat.slug === 'valuta');

    function initializeShop() {
        if (categories.length > 0) {
            selectedCategory = categories[0].id;
            renderCategories();
            renderProducts(selectedCategory);
        }
    }

    function renderCategories() {
        const container = document.querySelector('.category-tabs');
        container.innerHTML = '';
        
        categories.forEach(cat => {
            const btn = document.createElement('button');
            btn.className = 'category-btn' + (cat.id === selectedCategory ? ' active' : '');
            btn.dataset.category = cat.id;
            btn.textContent = cat.name;
            btn.addEventListener('click', () => selectCategory(cat.id));
            container.appendChild(btn);
        });
    }

    function selectCategory(categoryId) {
        selectedCategory = categoryId;
        renderCategories();
        renderProducts(categoryId);
    }

    function renderProducts(categoryId) {
        const grid = document.getElementById('productsGrid');
        const filtered = products.filter(p => p.category_id === categoryId);
        
        if (filtered.length === 0) {
            grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #999; padding: 40px;">ამ კატეგორიაში ნივე არ არის</p>';
            return;
        }
        
        grid.innerHTML = filtered.map(product => `
            <div class="product-card" data-category="${product.category_id}">
                <div class="product-image">
                    ${product.image_url ? 
                        `<img src="${product.image_url}" alt="${product.name}" style="width: 150px; height: 150px; object-fit: scale-down; border-radius: 8px;">` : 
                        '<i class="fas fa-box"></i>'
                    }
                </div>
                <h3>${product.name}</h3>
                <button class="buy-btn" data-product-id="${product.id}">
                    ${product.price}₾ <i class="fas fa-shopping-cart"></i>
                </button>
            </div>
        `).join('');

        // Attach event listeners to buy buttons
        document.querySelectorAll('.buy-btn').forEach(btn => {
            btn.addEventListener('click', () => openModal(parseInt(btn.dataset.productId)));
        });
    }

    function openModal(productId) {
        const product = products.find(p => p.id === productId);
        if (!product) return;

        const isCurrency = currencyCategory && product.category_id === currencyCategory.id;

        currentModal = { type: 'product', data: product };
        document.getElementById('productTitle').textContent = product.name;
        
        if (isCurrency) {
            if (product.quantity === 1) {
                document.getElementById('receiveIcon').className = 'fas fa-medal input-icon';
            } else {
                document.getElementById('receiveIcon').className = 'fas fa-money-bill input-icon';
            }
        }
        
        document.getElementById('receiveAmount').value = '';
        document.getElementById('amount').value = '';
        document.getElementById('username').value = '';
        document.getElementById('agreeCheckbox').checked = false;
        document.getElementById('buyBtn').disabled = true;
        document.getElementById('buyBtn').textContent = `${product.price}₾ • ყიდვა`;
        
        if (isCurrency) {
            document.getElementById('currencyFields').style.display = 'block';
        } else {
            document.getElementById('currencyFields').style.display = 'none';
        }

        document.getElementById('formFieldsContainer').style.display = 'none';
        recaptchaCompleted = false;
        
        clearUsernameValidation();
        
        renderRecaptcha();
        
        document.getElementById('modalOverlay').classList.add('active');
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('active');
        clearUsernameValidation();
        if (recaptchaWidgetId !== null) {
            grecaptcha.reset(recaptchaWidgetId);
        }
    }

    function renderRecaptcha() {
        const wrapper = document.getElementById('recaptcha-wrapper');
        const siteKey = '{{ config('services.recaptcha.site_key') }}';
        
        // If reCAPTCHA is not configured, skip gate and show form directly
        if (!siteKey) {
            recaptchaCompleted = true;
            document.getElementById('recaptchaGate').style.display = 'none';
            document.getElementById('formFieldsContainer').style.display = 'block';
            return;
        }

        if (wrapper) {
            // Destroy existing widget if it exists
            if (recaptchaWidgetId !== null) {
                try {
                    grecaptcha.reset(recaptchaWidgetId);
                } catch (e) {
                    // Ignore reset errors
                }
                recaptchaWidgetId = null;
            }
            
            wrapper.innerHTML = '<div class="g-recaptcha" id="recaptcha-container"></div>';
            
            try {
                const container = document.getElementById('recaptcha-container');
                recaptchaWidgetId = grecaptcha.render(container, {
                    'sitekey': siteKey,
                    'callback': onRecaptchaSuccess,
                    'expired-callback': onRecaptchaExpired,
                    'error-callback': onRecaptchaError
                });
            } catch (e) {
                console.error('reCAPTCHA render error:', e);
                wrapper.innerHTML = '<div style="color: #999; padding: 20px; text-align: center;">reCAPTCHA loading error</div>';
            }
        }
    }

    function onRecaptchaSuccess() {
        recaptchaCompleted = true;
        document.getElementById('captcha-error').style.display = 'none';
        
        // Reveal form fields when reCAPTCHA is completed
        document.getElementById('formFieldsContainer').style.display = 'block';
        
        // Focus on the username field for better UX
        setTimeout(() => {
            document.getElementById('username').focus();
        }, 100);
        
        updateBuyButtonState();
    }

    function onRecaptchaExpired() {
        recaptchaCompleted = false;
        document.getElementById('formFieldsContainer').style.display = 'none';
        document.getElementById('captcha-error').style.display = 'block';
        document.getElementById('captcha-error').textContent = 'reCAPTCHA ვადია გაუვიდა. გაიმეორეთ ცდა.';
        updateBuyButtonState();
    }

    function onRecaptchaError() {
        recaptchaCompleted = false;
        document.getElementById('formFieldsContainer').style.display = 'none';
        document.getElementById('captcha-error').style.display = 'block';
        document.getElementById('captcha-error').textContent = 'reCAPTCHA შეცდომა. გადატვირთეთ გვერდი და სცადეთ ხელახლა.';
        updateBuyButtonState();
    }

    function isRecaptchaCompleted() {
        const siteKey = '{{ config('services.recaptcha.site_key') }}';
        
        // If reCAPTCHA is not configured, consider it "completed" for testing
        if (!siteKey) {
            return true;
        }
        
        // Check if grecaptcha is available and widget is rendered
        if (typeof grecaptcha === 'undefined' || recaptchaWidgetId === null) {
            return false;
        }
        
        try {
            const response = grecaptcha.getResponse(recaptchaWidgetId);
            return response && response.length > 0;
        } catch (e) {
            console.error('Error checking reCAPTCHA status:', e);
            return false;
        }
    }

    document.querySelectorAll('.currency-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const currency = btn.dataset.currency;
            document.querySelectorAll('.currency-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            document.getElementById('currencyFields').style.display = 'block';
            document.getElementById('receiveIcon').className = currency === 'money' 
                ? 'fas fa-money-bill input-icon' 
                : 'fas fa-medal input-icon';
            
            updateReceiveAmount(currency);
        });
    });

    document.getElementById('amount').addEventListener('input', () => {
        const amount = document.getElementById('amount').value;
        if (amount) {
            document.getElementById('buyBtn').textContent = `${amount}₾ • ყიდვა`;
        }
        calculateReceiveAmount();
        updateBuyButtonState();
    });

    function calculateReceiveAmount() {
        const amountField = document.getElementById('amount');
        const receiveAmountField = document.getElementById('receiveAmount');
        const isCurrency = currencyCategory && currentModal && currentModal.data.category_id === currencyCategory.id;
        
        if (!isCurrency || !amountField.value) {
            receiveAmountField.value = '';
            return;
        }
        
        const amount = parseInt(amountField.value);
        const product = currentModal.data;
        const received = amount * product.quantity;
        
        if (product.quantity === 1) {
            receiveAmountField.value = `${received} G-COIN`;
        } else {
            receiveAmountField.value = `${received.toLocaleString('ka-GE')} ვერტუალური ფული`;
        }
    }

    document.getElementById('agreeCheckbox').addEventListener('change', updateBuyButtonState);

    document.getElementById('username').addEventListener('input', updateBuyButtonState);

    function updateBuyButtonState() {
        const usernameField = document.getElementById('username');
        const amountField = document.getElementById('amount');
        const isChecked = document.getElementById('agreeCheckbox').checked;
        const isCurrency = currencyCategory && currentModal && currentModal.data.category_id === currencyCategory.id;
        
        if (!recaptchaCompleted) {
            document.getElementById('buyBtn').disabled = true;
            return;
        }
        
        if (!isChecked) {
            document.getElementById('buyBtn').disabled = true;
            return;
        }
        
        if (!usernameField.value.trim()) {
            document.getElementById('buyBtn').disabled = true;
            return;
        }

        
        if (isCurrency) {
            const amountValue = parseFloat(amountField.value);
            const amountMessage = document.getElementById('amountMessage');
            if (!amountField.value || isNaN(amountValue) || amountValue < 1) {
                amountMessage.textContent = 'თანხა უნდა იყოს მინიმუმ 1 ლარი';
                amountMessage.className = 'error';
                document.getElementById('buyBtn').disabled = true;
                return;
            } else {
                amountMessage.textContent = '';
                amountMessage.className = '';
            }
        }
        
        document.getElementById('buyBtn').disabled = false;
    }

    function clearUsernameValidation() {
        const usernameInput = document.getElementById('username');
        usernameInput.classList.remove('validating', 'valid', 'invalid');
        document.getElementById('usernameStatus').style.display = 'none';
        document.getElementById('usernameMessage').textContent = '';
        document.getElementById('usernameMessage').className = '';
    }

    async function validateUsername() {
        const usernameInput = document.getElementById('username');
        const username = usernameInput.value.trim();
        const statusEl = document.getElementById('usernameStatus');
        const iconEl = document.getElementById('usernameStatusIcon');
        const messageEl = document.getElementById('usernameMessage');

        if (!username || username.length < 1) {
            messageEl.textContent = 'სახელი აუცილებელია';
            messageEl.className = 'error';
            return false;
        }

        if (!/^[a-zA-Z0-9_-]{1,24}$/.test(username)) {
            usernameInput.classList.add('invalid');
            statusEl.style.display = 'flex';
            iconEl.className = 'fas fa-times error';
            messageEl.textContent = 'სახელი შეიძლება შეიცავდეს მხოლოდ ასოებს, ციფრებს, _ და -';
            messageEl.className = 'error';
            return false;
        }

        // Show loading state
        usernameInput.classList.remove('valid', 'invalid');
        usernameInput.classList.add('validating');
        statusEl.style.display = 'flex';
        iconEl.className = 'fas fa-spinner fa-spin loading';
        messageEl.textContent = 'მოწმდება...';
        messageEl.className = '';

        try {
            const response = await fetch(`{{ route('shop.validate-username') }}?username=${encodeURIComponent(username)}`);
            const data = await response.json();

            usernameInput.classList.remove('validating');

            if (data.exists) {
                usernameInput.classList.add('valid');
                iconEl.className = 'fas fa-check success';
                messageEl.textContent = data.message;
                messageEl.className = 'success';
                return true;
            } else {
                usernameInput.classList.add('invalid');
                iconEl.className = 'fas fa-times error';
                messageEl.textContent = data.message || 'ანგარიში ვერ მოიძებნა';
                messageEl.className = 'error';
                return false;
            }
        } catch (e) {
            usernameInput.classList.remove('validating');
            usernameInput.classList.add('invalid');
            iconEl.className = 'fas fa-times error';
            messageEl.textContent = 'სერვერთან კავშირის შეცდომა';
            messageEl.className = 'error';
            return false;
        }
    }

    function isRecaptchaCompleted() {
        const siteKey = '{{ config('services.recaptcha.site_key') }}';
        if (!siteKey) {
            return true;
        }
        
        return recaptchaCompleted;
    }

    // Handle payment submission
    document.getElementById('buyBtn').addEventListener('click', async function() {
        if (this.disabled) return;

        const siteKey = '{{ config('services.recaptcha.site_key') }}';
        let originalText = this.textContent;
        
        try {
            const username = document.getElementById('username').value.trim();

            // Validate username exists before proceeding to payment
            clearUsernameValidation();
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> მოწმდება...';

            const usernameValid = await validateUsername();
            if (!usernameValid) {
                this.disabled = false;
                this.textContent = originalText || 'ყიდვა';
                return;
            }

            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> დამუშავება...';

            const amountField = document.getElementById('amount');
            const isCurrency = currencyCategory && currentModal && currentModal.data.category_id === currencyCategory.id;
            const amount = isCurrency ? parseFloat(amountField.value) : currentModal.data.price;

            let recaptchaToken = null;
            if (siteKey && typeof grecaptcha !== 'undefined' && recaptchaWidgetId !== null) {
                try {
                    recaptchaToken = grecaptcha.getResponse(recaptchaWidgetId);
                } catch (e) {
                    console.error('Error getting reCAPTCHA token:', e);
                }
            }

            if (siteKey && !recaptchaToken) {
                throw new Error('reCAPTCHA ვერიფიკაცია ვერ მოხერხდა. გთხოვთ სცადოთ ხელახლა.');
            }

            const paymentData = {
                username: username,
                shop_item_id: currentModal.data.id,
                amount: amount,
                agree: document.getElementById('agreeCheckbox').checked,
                recaptcha_token: recaptchaToken,
            };

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const response = await fetch('{{ route("payment.create") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(paymentData),
            });

            const data = await response.json();

            if (data.success && data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                throw new Error(data.message || 'გადახდის ინიციირება ვერ მოხერხდა');
            }
        } catch (error) {
            console.error('Payment error:', error);
            alert(error.message || 'უცნობი შეცდომა');
            document.getElementById('buyBtn').disabled = false;
            document.getElementById('buyBtn').textContent = originalText || 'ყიდვა';
        }
    });

    document.querySelector('.modal__close').addEventListener('click', closeModal);

    document.getElementById('modalOverlay').addEventListener('click', (e) => {
        if (e.target === document.getElementById('modalOverlay')) {
            closeModal();
        }
    });

    document.addEventListener('DOMContentLoaded', initializeShop);
</script>
@endsection
