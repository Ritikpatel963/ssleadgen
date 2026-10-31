<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign In | SS Advisory Lead Engine</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/vendors/css/vendors.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Modular Design System -->
    <link rel="stylesheet" href="assets/css/styles.css" />

    <link rel="icon" type="image/png" href="assets/images/logo.png" />
    <link rel="shortcut icon" type="image/png" href="assets/images/logo.png" />
    <link rel="stylesheet" href="assets/css/pages/auth.css">
</head>

<body class="auth-body">

    <div class="auth-container">
        <!-- LEFT HERO BRAND PANEL -->
        <div class="auth-hero-panel">
            <div class="auth-logo-brand d-flex align-items-center gap-3">
                <img src="assets/images/logo.png" alt="SS Advisory Logo" />
                <div>
                    <span class="fs-16 fw-bolder text-white tracking-wide d-block" style="line-height: 1.1;">SS <span
                            style="color: #00A8B5;">ADVISORY</span></span>
                    <span class="fs-10 text-muted fw-semibold text-uppercase"
                        style="letter-spacing: 0.15em; color: #94A3B8 !important;">LEAD ENGINE</span>
                </div>
            </div>

            <div class="my-4 z-1">
                <h2 class="fw-extrabold text-white mb-2 fs-24" style="line-height: 1.3;">Accelerate Your Insurance
                    Brokerage & Lead Pipeline.</h2>
                <p class="text-light opacity-75 fs-13 mb-4">Empowering financial advisors across New Zealand with smart
                    client CRM, automated lead acquisition, and policy lifecycle tracking.</p>

                <div class="auth-feature-pill">

                    <div>
                        <div class="fs-13 fw-bold text-white">Smart Lead Management</div>
                        <div class="fs-11 text-light opacity-75">Real-time status tracking & follow-up callbacks</div>
                    </div>
                </div>

                <div class="auth-feature-pill">

                    <div>
                        <div class="fs-13 fw-bold text-white">Inforce Policy Control</div>
                        <div class="fs-11 text-light opacity-75">Complete underwriting & claims oversight</div>
                    </div>
                </div>
            </div>

            <div class="fs-11 text-light opacity-50 z-1">
                © 2026 SS Advisory Engine. Developed with ❤ by <a href="https://sitesoch.com" target="_blank"
                    class="text-white text-decoration-underline">Sitesoch</a>
            </div>
        </div>

        <!-- RIGHT FORM PANEL -->
        <div class="auth-form-panel">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1 fs-20">Welcome Back 👋</h3>
                <p class="text-muted fs-13 mb-0">Sign in to access your advisor dashboard and client records.</p>
            </div>

            <form id="loginForm" onsubmit="event.preventDefault(); window.location.href='index.html';">
                <!-- EMAIL INPUT -->
                <div class="mb-3">
                    <label class="form-label fs-12 fw-bold text-dark">Work Email Address <span class="text-danger">*</span></label>
                    <div class="auth-input-group">
                        <i class="feather-mail auth-icon"></i>
                        <input type="email" class="form-control" placeholder="advisor@ssadvisory.co.nz"
                            value="sushant@ssadvisory.co.nz" required />
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label fs-12 fw-bold text-dark mb-0">Password <span class="text-danger">*</span></label>
                        <a href="{{ route('password.request') }}"
                            class="fs-12 text-primary fw-semibold text-decoration-none">Forgot Password?</a>
                    </div>
                    <div class="auth-input-group">
                        <i class="feather-lock auth-icon"></i>
                        <input type="password" id="passwordInput" class="form-control" placeholder="••••••••••••"
                            required />
                        <button type="button" class="password-toggle" id="togglePasswordBtn">
                            <i class="feather-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- REMEMBER ME -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMeCheck" name="remember" checked />
                        <label class="form-check-input-label fs-13 text-muted fw-semibold"
                            for="rememberMeCheck">Remember me (Keep me signed in for 60 days)</label>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit"
                    class="btn btn-auth-primary w-100 d-flex align-items-center justify-content-center gap-2">
                    <span>Sign In</span>
                    <i class="feather-arrow-right fs-15"></i>
                </button>
            </form>


        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>

    <script src="assets/js/pages/login.js"></script>
</body>

</html>
