<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Sign In') | SS Advisory Insurance Brokerage</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/styles.css?v=10.0') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/ss-custom.css?v=2.1') }}" />
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}" />

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #F8FAFC;
        }

        body.auth-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 1050px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(8, 27, 51, 0.08);
            overflow: hidden;
            display: flex;
            min-height: 620px;
        }

        /* LEFT HERO PANEL */
        .auth-hero-panel {
            flex: 1.1;
            background: linear-gradient(135deg, #081B33 0%, #0F294A 60%, #00A8B5 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            color: #ffffff;
            overflow: hidden;
        }

        .auth-hero-panel::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(0, 168, 181, 0.15);
            filter: blur(40px);
        }

        .auth-hero-panel::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: rgba(2, 132, 199, 0.2);
            filter: blur(50px);
        }

        .auth-logo-brand img {
            height: 42px;
            width: auto;
        }

        .auth-feature-pill {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        /* RIGHT FORM PANEL */
        .auth-form-panel {
            flex: 1;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .auth-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .auth-input-group i.auth-icon {
            position: absolute;
            left: 16px;
            color: #94A3B8;
            font-size: 16px;
        }

        .auth-input-group .form-control {
            padding-left: 46px;
            padding-right: 46px;
            height: 50px;
            border-radius: 10px;
            border: 1.5px solid #E2E8F0;
            background-color: #F8FAFC;
            font-size: 14px;
            font-weight: 500;
            transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        }

        .auth-input-group .form-control:focus {
            background-color: #ffffff;
            border-color: #00A8B5;
            box-shadow: 0 0 0 4px rgba(0, 168, 181, 0.12);
            outline: none;
        }
        
        input[type="password"].form-control {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            letter-spacing: 2px;
        }

        .auth-input-group .form-control.is-invalid {
            border-color: #ef4444;
            background: #fff8f8;
        }

        .auth-input-group .password-toggle {
            position: absolute;
            right: 16px;
            color: #94A3B8;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.2s;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }

        .auth-input-group .password-toggle:hover {
            color: #0F172A;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .invalid-feedback {
            font-size: 0.78rem;
            color: #ef4444;
        }

        .form-check-input:checked {
            background-color: #081B33;
            border-color: #081B33;
        }

        .btn-auth-primary {
            background-color: #081B33;
            color: #ffffff;
            width: 100%;
            height: 50px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.02em;
            border: none;
            transition: background-color 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 12px rgba(8, 27, 51, 0.15);
            cursor: pointer;
        }

        .btn-auth-primary:hover {
            background-color: #0F294A;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(8, 27, 51, 0.25);
        }

        .alert-auth-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            color: #b91c1c;
            font-size: 0.85rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 991.98px) {
            .auth-container {
                flex-direction: column;
                max-width: 500px;
            }

            .auth-hero-panel {
                padding: 35px 30px;
            }

            .auth-form-panel {
                padding: 35px 30px;
            }
        }
    </style>

    @stack('styles')
</head>

<body class="auth-body">
    <div class="auth-container">
        <!-- LEFT HERO BRAND PANEL -->
        <div class="auth-hero-panel">
            <div class="d-flex align-items-center gap-3" style="z-index:1;">
                <img src="{{ asset('assets/images/logo.png') }}" alt="SS Advisory Logo"
                    style="height:42px;width:auto;" />
                <div>
                    <span class="d-block fw-bolder text-white" style="font-size:16px;line-height:1.1;">SS <span
                            style="color:#00A8B5;">ADVISORY</span></span>
                    <span class="text-uppercase fw-semibold"
                        style="font-size:10px;letter-spacing:0.15em;color:#94A3B8;">LEAD ENGINE</span>
                </div>
            </div>

            <div class="my-4" style="z-index:1;">
                <h2 class="fw-bold text-white mb-2" style="font-size:24px;line-height:1.3;">Accelerate Your Insurance
                    Brokerage &amp; Lead Pipeline.</h2>
                <p class="text-white mb-4" style="opacity:0.75;font-size:13px;">Empowering financial advisors across New
                    Zealand with smart client CRM, automated lead acquisition, and policy lifecycle tracking.</p>

                <div class="auth-feature-pill">
                    <div>
                        <div class="fw-bold text-white" style="font-size:13px;">Smart Lead Management</div>
                        <div class="text-white" style="font-size:11px;opacity:0.75;">Real-time status tracking &amp;
                            follow-up callbacks</div>
                    </div>
                </div>

                <div class="auth-feature-pill">
                    <div>
                        <div class="fw-bold text-white" style="font-size:13px;">Inforce Policy Control</div>
                        <div class="text-white" style="font-size:11px;opacity:0.75;">Complete underwriting &amp; claims
                            oversight</div>
                    </div>
                </div>
            </div>

            <div style="font-size:11px;opacity:0.5;z-index:1;" class="text-white">
                © 2026 SS Advisory Engine. Developed with ❤ by <a href="https://sitesoch.com" target="_blank"
                    class="text-white text-decoration-underline">Sitesoch</a>
            </div>
        </div>

        <!-- RIGHT FORM PANEL -->
        <div class="auth-form-panel">


            @yield('content')
        </div>
    </div>

    <script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.password-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = this.closest('.auth-input-group').querySelector('input');
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>