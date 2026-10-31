<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Forgot Password | SS Advisory Lead Engine</title>

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
                <div class="avatar-text bg-soft-primary text-primary rounded-circle mb-3"
                    style="width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="feather-key fs-3"></i></div>
                <h3 class="fw-bold text-dark mb-1 fs-20">Forgot Password? 🔑</h3>
                <p class="text-muted fs-13 mb-0">Enter your registered work email address below to receive password
                    reset instructions.</p>
            </div>

            <!-- SUCCESS ALERT BANNER (Initially Hidden) -->
            <div class="alert alert-success d-none mb-4 p-3 border-0 shadow-sm" id="resetSuccessAlert">
                <div class="d-flex align-items-center gap-2">
                    <i class="feather-check-circle fs-4 text-success"></i>
                    <div>
                        <div class="fw-bold fs-13 text-dark">Password Reset Email Sent!</div>
                        <div class="fs-12 text-muted">We have emailed a reset link to <strong
                                id="sentEmailDisplay">advisor@ssadvisory.co.nz</strong>. Please check your inbox.</div>
                    </div>
                </div>
            </div>

            <form id="forgotPasswordForm" onsubmit="handleSendResetLink(event);">
                <!-- EMAIL INPUT -->
                <div class="mb-4">
                    <label class="form-label fs-12 fw-bold text-dark">Work Email Address <span class="text-danger">*</span></label>
                    <div class="auth-input-group">
                        <i class="feather-mail auth-icon"></i>
                        <input type="email" id="resetEmailInput" class="form-control"
                            placeholder="advisor@ssadvisory.co.nz" value="sushant@ssadvisory.co.nz" required />
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <button type="submit" id="btnSendReset"
                    class="btn btn-auth-primary w-100 d-flex align-items-center justify-content-center gap-2 mb-3">
                    <span>Send Reset Instructions</span>
                    <i class="feather-send fs-14"></i>
                </button>
            </form>

            <div class="text-center mt-2">
                <a href="login.html"
                    class="fs-13 fw-bold text-muted text-decoration-none d-inline-flex align-items-center gap-1 hover-text-dark">
                    <i class="feather-arrow-left fs-14"></i> Back to Login Page
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/vendors/js/vendors.min.js"></script>
    
    <script src="assets/js/pages/forgot-password.js"></script>
</body>

</html>