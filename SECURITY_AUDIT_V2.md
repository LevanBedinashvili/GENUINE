# GENUINE-RP — უსაფრთხოების მეორე აუდიტი (V2)

**პროექტი:** GENUINE-RP (Laravel E-Commerce / Gaming Shop)  
**აუდიტის თარიღი:** 2026-03-20  
**წინა აუდიტის თარიღი:** 2026-03-20 (V1)  
**ფრეიმვორკი:** Laravel 11.x  
**ინტეგრაცია:** Bank of Georgia Payments API v1  
**სტატუსი:** პროდაქშენში (`genuine-rp.ge`)

---

## სარჩევი

1. [წინა აუდიტის შედეგების შეჯამება — რა გამოსწორდა და რა არა](#1-წინა-აუდიტის-შედეგების-შეჯამება)
2. [ამჟამინდელი პრობლემების სრული ჩამონათვალი](#2-ამჟამინდელი-პრობლემების-სრული-ჩამონათვალი)
3. [თითოეული პრობლემის დეტალური განმარტება](#3-თითოეული-პრობლემის-დეტალური-განმარტება)
4. [მოგვარების სტრატეგია და იმპლემენტაციის გაიდლაინები](#4-მოგვარების-სტრატეგია-და-იმპლემენტაციის-გაიდლაინები)

---

## 1. წინა აუდიტის შედეგების შეჯამება

### ✅ გამოსწორებული პრობლემები

| # | პრობლემა (V1) | სტატუსი | რა გაკეთდა |
|---|---------------|---------|------------|
| P-01 | ადმინ route-ებზე Authorization არ არსებობს | ✅ **გამოსწორდა** | `admin.php` ახლა იყენებს `['auth', 'admin.auth']` middleware-ს. `AdminAuth` middleware ამოწმებს `is_admin` ველს. |
| P-02 | ღია რეგისტრაცია ადმინ წვდომას იძლევა | ✅ **გამოსწორდა** | `RegisterController.php` წაიშალა. `/register` route არ არსებობს. ანგარიშის ღია რეგისტრაცია გათიშულია. |
| P-03 | Webhook signature ვერიფიკაციის გვერდის ავლა | ✅ **ნაწილობრივ** | `PaymentController::handleCallback` ახლა reject-ს აკეთებს ცარიელი ან არავალიდური signature-ის შემთხვევაში (401). **მაგრამ** `BogPaymentService::verifyCallbackSignature` კვლავ `return true`-ს აბრუნებს public key-ის არარსებობისას (იხ. P-03-NEW). |
| P-04 | Seeder-ში hardcoded სუსტი პაროლები | ✅ **გამოსწორდა** | `AdminUserSeeder` ახლა `Str::random(16)` იყენებს შემთხვევით პაროლს. არ არის hardcoded. |
| P-07 | SecurityHeaders middleware რეგისტრირებული არ არის | ✅ **გამოსწორდა** | `SecurityHeaders::class` ახლა global `$middleware` მასივშია Kernel.php-ში. |
| P-10 | `file_get_contents` reCAPTCHA-სთვის | ✅ **გამოსწორდა** | `RecaptchaValidator` ახლა `Http::asForm()->timeout(10)->post()` იყენებს. Laravel HTTP Client-ი timeout-ითა და error handling-ით. |
| P-16 | `SESSION_ENCRYPT=false` | ✅ **გამოსწორდა** | `.env.example`-ში `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIES=true`, `SESSION_HTTP_ONLY=true`. |
| P-17 | `BOG_THEME` ორჯერ განსაზღვრულია | ✅ **გამოსწორდა** | `.env.example`-ში მხოლოდ ერთი `BOG_THEME=dark` ხაზია. |

### ❌ გამოუსწორებელი პრობლემები (კვლავ აქტუალურია)

| # (V1) | პრობლემა | სტატუსი |
|--------|----------|---------|
| P-03 (ნაწილობრივ) | `BogPaymentService::verifyCallbackSignature` — public key ცარიელის `return true` | ❌ კვლავ არსებობს |
| P-05 | Transaction IDOR | ❌ არ გამოსწორებულა |
| P-06 | SQL Wildcard Injection `searchPlayer`-ში | ❌ არ გამოსწორებულა |
| P-08 | ShopRateLimit middleware route-ებს არ აქვს მინიჭებული | ❌ არ გამოსწორებულა |
| P-09 | `updateSortOrder` ვალიდაციის გარეშე | ❌ არ გამოსწორებულა |
| P-11 | `BogPaymentService` — `env()` fallback | ❌ არ გამოსწორებულა |
| P-12 | `uniqid()` order ID-სთვის | ❌ არ გამოსწორებულა |
| P-13 | `result_viewed` სვეტები მიგრაციაში არ არის | ❌ არ გამოსწორებულა |
| P-14 | `payment_response` — `longText` ნაცვლად `json` | ❌ არ გამოსწორებულა |
| P-15 | `account_id` foreign key constraint | ❌ არ გამოსწორებულა |
| P-18 | `BogPaymentService` — hardcoded URL-ები | ❌ არ გამოსწორებულა |

### 🆕 ახალი პრობლემები (V2-ში აღმოჩენილი)

| # | პრობლემა | რისკი |
|---|----------|-------|
| P-19 | Callback-ის სტატუსის ტრანზიცია ყოველთვის უარყოფილია | 🔴 კრიტიკული |
| P-20 | თანხის შედარება float-ით callback-ში | 🔴 კრიტიკული |
| P-21 | `checkTransactionStatus` არასწორ key-ს კითხულობს API პასუხიდან | 🟠 მაღალი |
| P-22 | CSP ბლოკავს reCAPTCHA-ს და BOG payment page-ს | 🟠 მაღალი |
| P-23 | `is_admin` არის mass-assignable (Privilege Escalation) | 🟠 მაღალი |
| P-24 | ორი განსხვავებული signature verification ალგორითმი | 🟡 საშუალო |
| P-25 | UUID v4 გენერაციის ბაგი Idempotency-Key-ში | 🟡 საშუალო |
| P-26 | `MoneyValidator::isValid()` — 0 თანხა ვალიდურია | 🟡 საშუალო |
| P-27 | `config('services.recaptcha.secret_key')` არ არსებობს config-ში | 🔵 დაბალი |

---

## 2. ამჟამინდელი პრობლემების სრული ჩამონათვალი

### რისკის დონეების კლასიფიკაცია

| სიმბოლო | დონე | აღწერა |
|----------|-------|--------|
| 🔴 | კრიტიკული | დაუყოვნებელი გამოსწორება სჭირდება. გადახდის ფუნქციონალი ეზიანება. |
| 🟠 | მაღალი | სერიოზული რისკი. მოკლე ვადაში უნდა გამოსწორდეს. |
| 🟡 | საშუალო | პოტენციური რისკი. დაგეგმილ ციკლში გასასწორებელი. |
| 🔵 | დაბალი | კარგი პრაქტიკის დარღვევა. გაუმჯობესება სასურველია. |

### აქტიური პრობლემების ცხრილი

| # | პრობლემა | რისკი | კატეგორია (OWASP) | ფაილი |
|---|----------|-------|-------------------|-------|
| P-19 | Callback სტატუსის ტრანზიცია ყოველთვის ჩაშლილია | 🔴 კრიტიკული | A04: Insecure Design | `PaymentController.php` + `BogPaymentSignatureValidator.php` |
| P-20 | თანხის შედარება `(float)` cast-ით callback-ში | 🔴 კრიტიკული | A04: Insecure Design | `PaymentController.php` |
| P-03 | `BogPaymentService::verifyCallbackSignature` — `return true` public key-ის გარეშე | 🟠 მაღალი | A08: Software Integrity Failures | `BogPaymentService.php` |
| P-05 | Transaction IDOR — სხვისი ტრანზაქციის ხილვადობა | 🟠 მაღალი | A01: Broken Access Control | `PaymentController.php`, `routes/web.php` |
| P-06 | SQL Wildcard Injection `searchPlayer`-ში | 🟠 მაღალი | A03: Injection | `AdminDashboardController.php` |
| P-08 | ShopRateLimit middleware route-ებს არ აქვს მინიჭებული | 🟠 მაღალი | A05: Security Misconfiguration | `routes/web.php` |
| P-21 | `checkTransactionStatus` არასწორ key-ს კითხულობს | 🟠 მაღალი | A04: Insecure Design | `PaymentService.php` |
| P-22 | CSP ბლოკავს reCAPTCHA-ს და BOG-ს | 🟠 მაღალი | A05: Security Misconfiguration | `SecurityHeaders.php` |
| P-23 | `is_admin` mass-assignable | 🟠 მაღალი | A01: Broken Access Control | `User.php` |
| P-09 | `updateSortOrder` ვალიდაციის გარეშე | 🟡 საშუალო | A04: Insecure Design | `ShopItemController.php` |
| P-11 | `BogPaymentService` — `env()` fallback | 🟡 საშუალო | A05: Security Misconfiguration | `BogPaymentService.php` |
| P-12 | `uniqid()` order ID-სთვის | 🟡 საშუალო | A02: Cryptographic Failures | `PaymentService.php` |
| P-13 | `result_viewed` სვეტები მიგრაციაში არ არის | 🟡 საშუალო | A04: Insecure Design | Migration / Model |
| P-24 | ორი განსხვავებული signature verification ალგორითმი | 🟡 საშუალო | A08: Software Integrity Failures | `BogPaymentSignatureValidator.php` vs `BogPaymentService.php` |
| P-25 | UUID v4 გენერაციის ბაგი | 🟡 საშუალო | A02: Cryptographic Failures | `BogPaymentService.php` |
| P-26 | `MoneyValidator::isValid()` — 0 თანხა ვალიდურია | 🟡 საშუალო | A04: Insecure Design | `MoneyValidator.php` |
| P-14 | `payment_response` — `longText` ნაცვლად `json` | 🔵 დაბალი | A04: Insecure Design | Migration |
| P-15 | `account_id` foreign key constraint | 🔵 დაბალი | A04: Insecure Design | Migration |
| P-18 | `BogPaymentService` — hardcoded URL-ები | 🔵 დაბალი | A05: Security Misconfiguration | `BogPaymentService.php` |
| P-27 | reCAPTCHA config key-ის შეუსაბამობა | 🔵 დაბალი | A05: Security Misconfiguration | `config/services.php` |

---

## 3. თითოეული პრობლემის დეტალური განმარტება

---

### 🔴 P-19: Callback სტატუსის ტრანზიცია ყოველთვის ჩაშლილია (NEW — კრიტიკული)

**ფაილები:** `app/Http/Controllers/PaymentController.php` სტრიქონი ~212-222, `app/Services/BogPaymentSignatureValidator.php` სტრიქონი ~108-125  
**OWASP:** A04 — Insecure Design

**რა ხდება:**

`handleCallback`-ში BOG-ის სტატუსი ჯერ მაპდება შიდა სტატუსზე, შემდეგ `isValidStatusTransition()` მოწმდება:

```php
// ნაბიჯი 1: BOG სტატუსი → შიდა სტატუსი
$newStatus = $this->mapBogStatusToTransactionStatus($callbackData['status']);
// 'succeeded' → 'completed', 'failed' → 'failed'

// ნაბიჯი 2: ტრანზიციის ვალიდაცია
$signatureValidator->isValidStatusTransition($transaction->status, $newStatus);
```

`isValidStatusTransition`-ში ვალიდური ტრანზიციები:

```php
'pending' => ['succeeded', 'failed', 'cancelled'],  // ← BOG-ის სტილის სტატუსები
```

მაგრამ `$newStatus` უკვე დამაპილია შიდა სტატუსზე (`completed`, `failed`). `completed` არ არსებობს ვალიდურ ტრანზიციების სიაში — ეს ნიშნავს, რომ **ყველა წარმატებული callback უარყოფრდება**.

**შედეგი:**  
- BOG აბრუნებს `succeeded` → მაპდება `completed` → ტრანზიცია `pending` → `completed` **ბლოკდება**
- BOG აბრუნებს `failed` → მაპდება `failed` → ტრანზიცია `pending` → `failed` **ბლოკდება**
- **ნებისმიერი callback მუშავდება, მაგრამ ტრანზაქციის სტატუსი არასდროს განახლდება**

---

### 🔴 P-20: თანხის შედარება `(float)` cast-ით callback-ში (NEW — კრიტიკული)

**ფაილი:** `app/Http/Controllers/PaymentController.php` სტრიქონი ~199-202  
**OWASP:** A04 — Insecure Design

**რა ხდება:**

```php
if ((float) $transaction->amount !== (float) $callbackData['amount']) {
    // Amount mismatch → reject
}
```

Floating-point arithmetic-ში `0.1 + 0.2 !== 0.3`. ფინანსური თანხების შედარება `(float)` cast-ით **არასანდოა**. კოდბეიზში არსებობს `MoneyValidator::amountsEqual()` სწორედ ამ მიზნით, მაგრამ აქ არ გამოიყენება.

**შედეგი:**  
ვალიდური გადახდები შეიძლება უარყოფილ იქნეს "Amount mismatch" შეცდომით.

---

### 🟠 P-03: `BogPaymentService::verifyCallbackSignature` — `return true` (V1-დან)

**ფაილი:** `app/Services/BogPaymentService.php` სტრიქონი ~319-322  
**OWASP:** A08 — Software and Data Integrity Failures

**რა ხდება:**

```php
if (empty($this->publicKey)) {
    Log::channel('payments')->warning('BOG public key not configured, skipping signature verification');
    return true; // ← საშიშია!
}
```

**შენიშვნა:** ამჟამად `handleCallback` იყენებს `BogPaymentSignatureValidator` კლასს (რომელიც `throw new Exception`-ს აკეთებს public key-ის არარსებობისას), არა `BogPaymentService::verifyCallbackSignature`-ს. მაგრამ ეს ფუნქცია კვლავ არსებობს და `return true`-ს აბრუნებს — მომავალში რომელიმე dev-მა შეიძლება ეს გამოიყენოს. **Dead code, მაგრამ საშიში default-ით**.

---

### 🟠 P-05: Transaction IDOR (V1-დან)

**ფაილი:** `routes/web.php` სტრიქონი 14-25, `app/Http/Controllers/PaymentController.php`  
**OWASP:** A01 — Broken Access Control

კვლავ იგივეა:

```php
Route::get('/success/{transaction_id}', ...)->name('success');
Route::get('/fail/{transaction_id}', ...)->name('fail');
Route::get('/check/{transaction_id}', ...)->name('check');
```

`transaction_id` არის auto-increment integer (1, 2, 3...). **არ მოწმდება ownership** — ნებისმიერს შეუძლია ენუმერაციით სხვისი ტრანზაქცია ნახოს.

**შედეგი:**  
- სხვისი ტრანზაქციის სტატუსის, თანხის, username-ის ხილვა
- ID-ების სკანირებით ბიზნესის მთელი ტრანზაქციების ისტორია

---

### 🟠 P-06: SQL Wildcard Injection `searchPlayer`-ში (V1-დან)

**ფაილი:** `app/Http/Controllers/Admin/AdminDashboardController.php` სტრიქონი ~108  
**OWASP:** A03 — Injection

კვლავ იგივეა:

```php
->where('accounts.playerName', 'LIKE', '%' . $playerName . '%')
```

SQL LIKE-ის სპეციალური სიმბოლოები (`%`, `_`) არ ესკეიპდება.

---

### 🟠 P-08: ShopRateLimit middleware route-ებს არ აქვს მინიჭებული (V1-დან)

**ფაილი:** `routes/web.php`  
**OWASP:** A05 — Security Misconfiguration

`ShopRateLimit` Kernel.php-ში alias-ითაა (`shop.rate.limit`), მაგრამ **არცერთ route-ზე არ არის მინიჭებული**:
- `POST /payment/create` — rate limit-ის გარეშე
- შეიძლება მასობრივი ტრანზაქციების შექმნა BOG API-ს spam-ით

---

### 🟠 P-21: `checkTransactionStatus` არასწორ key-ს კითხულობს (NEW)

**ფაილი:** `app/Services/PaymentService.php` სტრიქონი ~186-188  
**OWASP:** A04 — Insecure Design

**რა ხდება:**

```php
$bogStatus = $this->gateway->getOrderDetails($transaction->external_tx_id);

if ($bogStatus['status'] !== $transaction->status) {
    $transaction->update(['status' => $bogStatus['status']]);
```

`getOrderDetails()` აბრუნებს `order_status` key-ს (array: `['key' => '...']`), არა `status`. `$bogStatus['status']` ყოველთვის `null` იქნება → სტატუსი არასდროს განახლდება ამ flow-ით, ან `null` მნიშვნელობა ჩაიწერება.

---

### 🟠 P-22: CSP ბლოკავს reCAPTCHA-ს (NEW)

**ფაილი:** `app/Http/Middleware/SecurityHeaders.php` სტრიქონი 29-35  
**OWASP:** A05 — Security Misconfiguration

**რა ხდება:**

მიმდინარე CSP:
```
script-src 'self' https://cdn.jsdelivr.net https://kit.fontawesome.com;
connect-src 'self';
frame-ancestors 'none';
```

რაც **აკლია:**
- `script-src` — `https://www.google.com/recaptcha/` და `https://www.gstatic.com/recaptcha/`
- `frame-src` — `https://www.google.com/recaptcha/` (reCAPTCHA iframe) და `https://payment.bog.ge` (BOG payment page)
- `connect-src` — `https://www.google.com/recaptcha/`

View-ში ჩატვირთულია:
```html
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
```

**შედეგი:**  
ბრაუზერი ბლოკავს reCAPTCHA სკრიპტს CSP-ის გამო → reCAPTCHA არ მუშაობს → გადახდის ფორმა შეიძლება არ იმუშაოს.

---

### 🟠 P-23: `is_admin` mass-assignable (NEW)

**ფაილი:** `app/Models/User.php` სტრიქონი 25-30  
**OWASP:** A01 — Broken Access Control

**რა ხდება:**

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'is_admin',  // ← მასობრივი მინიჭებისთვის ღია
];
```

თუ რომელიმე endpoint `User::create($request->all())` ან `$user->update($request->all())` იყენებს, მომხმარებელს შეუძლია `is_admin=1` გაგზავნოს request-ში.

**შემამსუბუქებელი ფაქტორი:** ამჟამად Register endpoint არ არსებობს, Login Controller `validate()`-ს იყენებს (არა `all()`-ს). მაგრამ **დეფენსიურად** `is_admin` არ უნდა იყოს `$fillable`-ში.

---

### 🟡 P-09: `updateSortOrder` ვალიდაციის გარეშე (V1-დან)

**ფაილი:** `app/Http/Controllers/Admin/ShopItemController.php` სტრიქონი ~118-125  
**OWASP:** A04 — Insecure Design

კვლავ იგივეა:

```php
public function updateSortOrder(Request $request)
{
    $items = $request->get('items', []);
    foreach ($items as $index => $itemId) {
        ShopItem::find($itemId)->update(['sort_order' => $index]);
    }
}
```

`ShopItem::find()` → `null` → `null->update()` → 500 Error.

---

### 🟡 P-11: `env()` fallback (V1-დან)

**ფაილი:** `app/Services/BogPaymentService.php` სტრიქონი 30-33  
**OWASP:** A05 — Security Misconfiguration

```php
$this->clientId = config('bog.client_id') ?? env('BOG_CLIENT_ID');
$this->clientSecret = config('bog.client_secret') ?? env('BOG_CLIENT_SECRET');
```

ასევე `BogPaymentSignatureValidator.php` სტრიქონი 25:
```php
$this->publicKey = config('bog.public_key') ?? env('BOG_PUBLIC_KEY', '');
```

`config:cache`-ის შემდეგ `env()` `null`-ს აბრუნებს.

---

### 🟡 P-12: `uniqid()` order ID-სთვის (V1-დან)

**ფაილი:** `app/Services/PaymentService.php` სტრიქონი ~140

```php
'external_order_id' => 'GEN-' . uniqid(),
```

ასევე `Transaction::generateOrderId()`:
```php
$random = strtoupper(substr(md5(uniqid()), 0, 8));
```

`uniqid()` პროგნოზირებადია (microsecond timestamp).

---

### 🟡 P-13: `result_viewed` სვეტები მიგრაციაში არ არის (V1-დან)

Model-ში `$fillable`-ში: `result_viewed`, `result_viewed_at`  
Model-ში `$casts`-ში: `'result_viewed' => 'boolean'`, `'result_viewed_at' => 'datetime'`  
მიგრაციაში: **არ არსებობს**

`markResultAsViewed()` გამოძახება → `SQLSTATE[42S22]: Column not found`

---

### 🟡 P-24: ორი განსხვავებული signature ალგორითმი (NEW)

**ფაილები:**
- `BogPaymentSignatureValidator.php` — **HMAC-SHA256** (`hash_hmac`)
- `BogPaymentService.php` — **SHA256withRSA** (`openssl_verify`)

ამჟამად `PaymentController::handleCallback` იყენებს `BogPaymentSignatureValidator`-ს (HMAC). `BogPaymentService::verifyCallbackSignature` (RSA) dead code-ია. მხოლოდ ერთი შეიძლება იყოს BOG-ის რეალური ალგორითმი.

**რისკი:** თუ BOG RSA-ს იყენებს, HMAC ვერიფიკაცია ყოველთვის ჩაშლილია (ან ყოველთვის ჩავარდება).

---

### 🟡 P-25: UUID v4 გენერაციის ბაგი (NEW)

**ფაილი:** `app/Services/BogPaymentService.php` სტრიქონი ~47-58

```php
return sprintf(
    '%08x-%04x-%04x-%04x-%12x',
    unpack('N', substr($data, 0, 4))[1],
    unpack('n', substr($data, 4, 2))[1],
    unpack('n', substr($data, 6, 2))[1],
    unpack('n', substr($data, 8, 2))[1],
    unpack('N', substr($data, 10, 6))[1] . unpack('n', substr($data, 14, 2))[1]
);
```

- `unpack('N', substr($data, 10, 6))` — `N` format-ს 4 ბაიტი უნდა, 6 მოცემულია (შეიძლება warning)
- ბოლო სეგმენტში ორი integer **სტრინგად** კონკატენირდება (`"12345" . "678"`), `%12x` ვერ დააფორმატებს სწორად
- შედეგი: არავალიდური UUID, რამაც BOG-ის Idempotency-Key reject-ი შეიძლება გამოიწვიოს

---

### 🟡 P-26: `MoneyValidator::isValid()` — 0 თანხა ვალიდურია (NEW)

**ფაილი:** `app/Services/MoneyValidator.php` სტრიქონი ~59

```php
public static function isValid($amount, int $minAmount = 0, int $maxAmount = 999999): bool
```

Default `$minAmount = 0`, ანუ `bccomp("0", "0") === 0` → ტოლია → `< 0` არ სრულდება → **0 ლარიანი ტრანზაქცია ვალიდურია**.

`PaymentService::createPayment`-ში:
```php
if (!MoneyValidator::isValid($validated['amount'])) {
    throw new Exception('Invalid amount');
}
```

0.00 GEL-ის ტრანზაქცია გაივლის validation-ს (თუმცა controller-ის `min:0.01` rule ამას ბლოკავს, ეს მხოლოდ service-level gap-ია).

---

### 🔵 P-14, P-15, P-18: (V1-დან — უცვლელი)

იხილეთ V1 აუდიტი. არ შეცვლილა.

---

### 🔵 P-27: reCAPTCHA config key-ის შეუსაბამობა (NEW)

**ფაილი:** `config/services.php`

`config/services.php`-ში reCAPTCHA config `'recaptcha'` key-ის ქვეშ არის, მაგრამ `RecaptchaValidator` იხმარს `config('services.recaptcha.secret_key')`. `.env.example`-ში ნაჩვენებია `RECAPTCHA_SITE_KEY` და `RECAPTCHA_SECRET_KEY`. **ფაქტობრივი** config/services.php-ში ვერ ვნახე `recaptcha` ბლოკი პირველ 55 ხაზში. თუ ის ფაილის ბოლოს დამატებულია, ეს ოკეია — მაგრამ გადაამოწმეთ რომ key-ები ემთხვევა.

---

## 4. მოგვარების სტრატეგია და იმპლემენტაციის გაიდლაინები

### იმპლემენტაციის თანმიმდევრობა

```
ნაბიჯი 1: Callback ტრანზიციის fix (P-19 — გადახდა ვერ სრულდება)
    ↓
ნაბიჯი 2: Float შედარების fix (P-20 — callback amount mismatch)
    ↓
ნაბიჯი 3: CSP header-ების fix (P-22 — reCAPTCHA ბლოკდება)
    ↓
ნაბიჯი 4: `is_admin` mass-assignment (P-23)
    ↓
ნაბიჯი 5: IDOR + Rate Limiting (P-05, P-08)
    ↓
ნაბიჯი 6: checkTransactionStatus fix (P-21)
    ↓
ნაბიჯი 7: დანარჩენი საშუალო/დაბალი (P-03, P-06, P-09, P-11, P-12, P-13, P-24, P-25, P-26)
```

---

### ნაბიჯი 1: Callback სტატუსის ტრანზიციის fix

**აგვარებს:** P-19  
**პრინციპი:** `isValidStatusTransition` უნდა შეამოწმოს **შიდა** სტატუსებით, რადგან `mapBogStatusToTransactionStatus` უკვე შიდა სტატუსებს აბრუნებს.

#### 1.1 — `BogPaymentSignatureValidator::isValidStatusTransition` შეცვალეთ:

ამჟამინდელი:
```php
$validTransitions = [
    'pending' => ['succeeded', 'failed', 'cancelled'],
    'succeeded' => [],
    'failed' => [],
    'cancelled' => [],
];
```

შეცვალეთ:
```php
$validTransitions = [
    'pending'   => ['completed', 'failed'],   // შიდა სტატუსები
    'completed' => [],                          // Terminal state
    'failed'    => [],                          // Terminal state
];
```

ეს ემთხვევა `Transaction::STATUS_COMPLETED` (`'completed'`) და `Transaction::STATUS_FAILED` (`'failed'`) მნიშვნელობებს, რომლებსაც `mapBogStatusToTransactionStatus()` აბრუნებს.

---

### ნაბიჯი 2: Float შედარების fix

**აგვარებს:** P-20  
**პრინციპი:** ფინანსური თანხების შედარება bcmath-ით.

#### 2.1 — `PaymentController::handleCallback`-ში Amount check შეცვალეთ:

ამჟამინდელი:
```php
if ((float) $transaction->amount !== (float) $callbackData['amount']) {
```

შეცვალეთ:
```php
use App\Services\MoneyValidator;

if (!MoneyValidator::amountsEqual($transaction->amount, $callbackData['amount'])) {
```

---

### ნაბიჯი 3: CSP header-ების fix

**აგვარებს:** P-22  
**პრინციპი:** CSP-ში დაამატეთ reCAPTCHA და BOG payment domain-ები.

#### 3.1 — `SecurityHeaders.php`-ში CSP შეცვალეთ:

```php
$response->header('Content-Security-Policy', 
    "default-src 'self'; " .
    "script-src 'self' https://cdn.jsdelivr.net https://kit.fontawesome.com https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; " .
    "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com 'unsafe-inline'; " .
    "font-src 'self' https://fonts.gstatic.com https://kit-free.fontawesome.com; " .
    "img-src 'self' data: https:; " .
    "connect-src 'self' https://www.google.com/recaptcha/; " .
    "frame-src https://www.google.com/recaptcha/ https://payment.bog.ge https://payment.sandbox.bog.ge; " .
    "frame-ancestors 'none';"
);
```

---

### ნაბიჯი 4: `is_admin` mass-assignment-ის ბლოკირება

**აგვარებს:** P-23

#### 4.1 — `User.php`-ში `$fillable`-დან `is_admin` ამოიღეთ:

```php
protected $fillable = [
    'name',
    'email',
    'password',
    // 'is_admin' — ამოღებულია დაცვის მიზნით
];
```

ადმინის შესაქმნელად გამოიყენეთ:
```php
$user = User::create([...]);
$user->forceFill(['is_admin' => true])->save();
```

ან Seeder-ში `updateOrCreate` — ის მიმდინარე ფორმა გამართულია, რადგან `is_admin` პარამეტრი ხელით გადაეცემა.

---

### ნაბიჯი 5: IDOR + Rate Limiting

**აგვარებს:** P-05 + P-08

#### 5.1 — Signed Routes (P-05):

`PaymentService::buildGatewayOrderData`-ში:

```php
use Illuminate\Support\Facades\URL;

'redirect_urls' => [
    'success' => URL::signedRoute('payment.success', ['transaction_id' => $transaction->id]),
    'fail' => URL::signedRoute('payment.fail', ['transaction_id' => $transaction->id]),
],
```

Route-ებში `signed` middleware-ის დამატება:

```php
Route::get('/success/{transaction_id}', [PaymentController::class, 'handleRedirect'])
    ->middleware('signed')
    ->name('success');

Route::get('/fail/{transaction_id}', [PaymentController::class, 'handleRedirect'])
    ->middleware('signed')
    ->name('fail');
```

`checkStatus`-ისთვის IP შემოწმება:

```php
$transaction = $this->paymentService->getTransaction($transaction_id);
if ($transaction->ip_address !== $request->ip()) {
    abort(403);
}
```

#### 5.2 — Rate Limiting (P-08):

Payment route group-ზე middleware დამატება:

```php
Route::prefix('payment')->name('payment.')->middleware('shop.rate.limit')->group(function () {
    Route::post('/create', [PaymentController::class, 'createPayment'])->name('create');
    // ...
});
```

---

### ნაბიჯი 6: `checkTransactionStatus` fix

**აგვარებს:** P-21

#### 6.1 — `PaymentService::checkTransactionStatus` შეცვალეთ:

ამჟამინდელი:
```php
$bogStatus = $this->gateway->getOrderDetails($transaction->external_tx_id);
if ($bogStatus['status'] !== $transaction->status) {
    $transaction->update(['status' => $bogStatus['status']]);
```

შეცვალეთ:
```php
$bogResponse = $this->gateway->getOrderDetails($transaction->external_tx_id);
$bogStatusKey = $bogResponse['order_status']['key'] ?? 'unknown';

// Map BOG status to internal status (same as callback mapping)
$mappedStatus = $this->mapBogStatus($bogStatusKey);

if ($mappedStatus !== $transaction->status) {
    $transaction->update(['status' => $mappedStatus]);
```

სადაც `mapBogStatus` მეთოდი ანალოგიურია `PaymentController::mapBogStatusToTransactionStatus`-ის. იდეალურში ეს mapping ერთ ადგილას უნდა იყოს (მაგ. `Transaction` model-ში ან ცალკე trait/helper).

---

### ნაბიჯი 7: დანარჩენი პრობლემების მოგვარება

#### 7.1 — P-03: `BogPaymentService::verifyCallbackSignature` — dead code-ის fix

ვარიანტი A (რეკომენდებული): წაშალეთ `verifyCallbackSignature` მეთოდი `BogPaymentService`-დან, რადგან ის dead code-ია.

ვარიანტი B: თუ ტოვებთ, `return true` შეცვალეთ:
```php
if (empty($this->publicKey)) {
    Log::channel('payments')->error('BOG public key not configured — rejecting signature');
    return false;
}
```

#### 7.2 — P-06: SQL Wildcard Escape

```php
$escapedName = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $playerName);
$transactions = Transaction::select('transactions.*')
    ->join('accounts', 'transactions.account_id', '=', 'accounts.Id')
    ->where('accounts.playerName', 'LIKE', '%' . $escapedName . '%')
    ...
```

#### 7.3 — P-09: `updateSortOrder` ვალიდაცია

```php
public function updateSortOrder(Request $request)
{
    $validated = $request->validate([
        'items' => 'required|array',
        'items.*' => 'required|integer|exists:shop_items,id',
    ]);

    foreach ($validated['items'] as $index => $itemId) {
        ShopItem::where('id', $itemId)->update(['sort_order' => $index]);
    }

    return response()->json(['success' => true]);
}
```

#### 7.4 — P-11: `env()` fallback-ის მოცილება

`BogPaymentService.php`:
```php
$this->clientId = config('bog.client_id', '');
$this->clientSecret = config('bog.client_secret', '');
$this->publicKey = config('bog.public_key', '');
```

`BogPaymentSignatureValidator.php`:
```php
$this->publicKey = config('bog.public_key', '');
```

#### 7.5 — P-12: `uniqid()` → `Str::uuid()`

`PaymentService.php`:
```php
use Illuminate\Support\Str;

'external_order_id' => 'GEN-' . Str::uuid()->toString(),
```

`Transaction::generateOrderId()`:
```php
return 'ORD-' . Str::uuid()->toString();
```

#### 7.6 — P-13: მიგრაციის შექმნა `result_viewed` სვეტებისთვის

```bash
php artisan make:migration add_result_viewed_to_transactions_table
```

```php
public function up(): void
{
    Schema::table('transactions', function (Blueprint $table) {
        $table->boolean('result_viewed')->default(false)->after('metadata');
        $table->timestamp('result_viewed_at')->nullable()->after('result_viewed');
    });
}

public function down(): void
{
    Schema::table('transactions', function (Blueprint $table) {
        $table->dropColumn(['result_viewed', 'result_viewed_at']);
    });
}
```

#### 7.7 — P-24: Signature ალგორითმის კონსოლიდაცია

გადაამოწმეთ BOG-ის დოკუმენტაცია — რეალურად HMAC-SHA256 თუ SHA256withRSA იყენებენ.
- თუ **HMAC** → `BogPaymentSignatureValidator` სწორია, `BogPaymentService::verifyCallbackSignature` წაშალეთ
- თუ **RSA** → `BogPaymentSignatureValidator::verify` გადაწერეთ RSA-ზე

#### 7.8 — P-25: UUID — გამოიყენეთ Laravel-ის built-in

`BogPaymentService::generateUuid4()` მეთოდი **სრულად** შეცვალეთ:

```php
private function generateUuid4(): string
{
    return \Illuminate\Support\Str::uuid()->toString();
}
```

ან უშუალოდ გამოძახების ადგილას:
```php
$idempotencyKey = \Illuminate\Support\Str::uuid()->toString();
```

#### 7.9 — P-26: `MoneyValidator::isValid()` — minimum fix

```php
public static function isValid($amount, int $minAmount = 1, int $maxAmount = 999999): bool
```

ან caller-ის მხარეს explicit min:
```php
if (!MoneyValidator::isValid($validated['amount'], 1)) {
```

#### 7.10 — P-18: Hardcoded URL-ები

`BogPaymentService`-ის constructor-ში:
```php
$this->baseUrl = config('bog.base_url', 'https://api.bog.ge/payments/v1');
$this->authUrl = config('bog.auth_url', 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token');
$this->paymentUrl = config('bog.payment_url', 'https://payment.bog.ge');
```

---

## პროდაქშენ Deployment ჩეკლისტი

გამოსწორების შემდეგ, პროდაქშენზე:

- [ ] P-19 fix → callback ტესტირება (BOG-ის ტესტ callback გაგზავნით)
- [ ] P-20 fix → amount comparison unit test
- [ ] P-22 fix → ბრაუზერში reCAPTCHA-ს მუშაობის ტესტირება (DevTools Console — CSP errors)
- [ ] P-23 fix → `is_admin` რომ `$fillable`-ში აღარ არის, Seeder-ის ტესტი
- [ ] P-05 fix → signed URL-ების გენერაცია და ვალიდაცია
- [ ] P-08 fix → rate limiting-ის ტესტი (5+ request 60 წამში)
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan migrate` (result_viewed სვეტები)
- [ ] `APP_DEBUG=false` დადასტურება
- [ ] `BOG_PUBLIC_KEY` ვალიდური მნიშვნელობა

---

## დასკვნა

### V1-დან V2-მდე პროგრესი

| | V1 | V2 |
|---|---|---|
| 🔴 კრიტიკული | 4 | **2** (ახალი — callback flow) |
| 🟠 მაღალი | 4 | **7** (3 V1-დან + 4 ახალი) |
| 🟡 საშუალო | 5 | **7** (4 V1-დან + 3 ახალი) |
| 🔵 დაბალი | 4 | **4** (3 V1-დან + 1 ახალი) |
| **ჯამი** | **17** | **20** (8 გამოსწორდა, 11 დარჩა V1-დან, 9 ახალი) |

### ყველაზე კრიტიკული ჯაჭვი (ამჟამდ):

> **BOG callback მოდის** → **სტატუსის ტრანზიცია ბლოკდება (P-19)** → **ტრანზაქცია სამუდამოდ `pending` რჩება** → **მომხმარებელი იხდის ფულს, მაგრამ პროდუქტს ვერ იღებს**

**P-19 და P-20 დაუყოვნებელი გამოსწორებაა საჭირო** — ამის გარეშე BOG payment callback ფუნქციონალურად გატეხილია.
