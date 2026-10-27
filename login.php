<?php
include_once("connection/localhost.php");
/*$oBranch = new Branch();*/
$oYear = new Year();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Login | Blitztech ERP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Login - Blitztech ERP" name="description" />
    <meta content="3DM Solutions" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/blitztech2.png">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Css -->
    <link href="assets/css/bootstrap.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="assets/css/app.css" id="app-style" rel="stylesheet" type="text/css" />

    <style>
        * {
            font-family: 'Outfit', sans-serif !important;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: #0f172a;
            overflow-x: hidden;
        }

        .login-split-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* ================= Left Side: 50% Login Form ================= */
        .login-left-pane {
            flex: 0 0 50%;
            width: 50%;
            max-width: 50%;
            min-height: 100vh;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 30px;
            position: relative;
            z-index: 2;
        }

        .login-form-box {
            width: 100%;
            max-width: 440px;
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .logo-container {
            padding: 10px 0 25px 0;
            text-align: center;
        }

        .logo-container .brand-logo {
            height: 64px;
            width: auto;
            filter: drop-shadow(0 4px 10px rgba(11, 56, 102, 0.15));
            transition: transform 0.3s ease;
        }

        .logo-container .brand-logo:hover {
            transform: scale(1.04);
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            display: block;
        }

        .input-group {
            background: #ffffff;
            border: 1.5px solid #d4e3e1;
            border-radius: 12px;
            transition: all 0.25s ease;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(11, 56, 102, 0.03);
            display: flex;
            align-items: center;
        }

        .input-group:focus-within {
            border-color: #0b3866;
            box-shadow: 0 0 0 3.5px rgba(11, 56, 102, 0.15);
        }

        .form-control {
            background: transparent !important;
            border: none !important;
            height: 48px;
            font-size: 14.5px;
            color: #000000 !important;
            padding: 8px 16px;
            box-shadow: none !important;
            width: 100%;
        }

        .form-control::placeholder {
            color: #94a3b8;
            opacity: 1;
        }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            padding-right: 36px;
        }

        .input-group-append {
            margin-left: 0;
            display: flex;
        }

        .input-group-append .input-group-text {
            background: transparent;
            border: none;
            color: #0b3866;
            font-size: 18px;
            padding: 0 16px;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password {
            cursor: pointer !important;
            user-select: none;
            transition: all 0.2s ease;
        }

        .toggle-password:hover {
            color: #b57bee !important;
            background-color: rgba(181, 123, 238, 0.08) !important;
        }

        .btn-navy {
            background: linear-gradient(135deg, #0b3866 0%, #b57bee 100%);
            color: #ffffff !important;
            font-weight: 600;
            font-size: 15px;
            height: 48px;
            border: none;
            border-radius: 12px;
            letter-spacing: 0.3px;
            transition: all 0.25s ease;
            box-shadow: 0 8px 20px rgba(11, 56, 102, 0.25);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 24px;
        }

        .btn-navy:hover {
            background: linear-gradient(135deg, #0b3866 0%, #c897f2 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(11, 56, 102, 0.35);
        }

        .btn-navy:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12.5px;
            color: #94a3b8;
        }

        /* ================= Right Side: 50% Blitztech Cover ================= */
        .login-right-pane {
            flex: 0 0 50%;
            width: 50%;
            max-width: 50%;
            min-height: 100vh;
            height: 100vh;
            position: relative;
            overflow: hidden;
            padding: 0;
            margin: 0;
            display: flex;
        }

        .cover-poster-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }



        /* Alerts wrapper */
        .login-alerts {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 9999;
            max-width: 380px;
        }

        .alert {
            border-radius: 12px;
            font-size: 13.5px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 12px 18px;
            margin-bottom: 10px;
            color: #000000;
        }

        .alert-danger {
            background-color: #fde8e8 !important;
            color: #991b1b !important;
            border-left: 4px solid #ef4444 !important;
        }

        .alert-success {
            background-color: #e8f5f3 !important;
            color: #065f46 !important;
            border-left: 4px solid #10b981 !important;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Breakpoints */
        @media (max-width: 991.98px) {
            .login-split-wrapper {
                flex-direction: column;
            }

            .login-left-pane {
                flex: none;
                width: 100%;
                max-width: 100%;
                min-height: auto;
                padding: 40px 20px 60px 20px;
            }

            .login-right-pane {
                flex: none;
                width: 100%;
                max-width: 100%;
                height: 260px;
                min-height: 260px;
                order: -1;
            }

            .cover-poster-img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center;
            }
        }
    </style>
</head>
<body>

<div class="login-split-wrapper">
    <!-- LEFT 50%: Login Form Area -->
    <div class="login-left-pane">
        <div class="login-form-box">
            <div class="logo-container">
                <svg class="brand-logo" viewBox="0 0 260 64" role="img" aria-label="Blitztech Logo" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="blitzGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#0b3866"/>
                            <stop offset="100%" stop-color="#b57bee"/>
                        </linearGradient>
                    </defs>
                    <rect x="2" y="4" width="56" height="56" rx="14" fill="url(#blitzGrad)"/>
                    <path d="M34 11 L19 36 H29 L25 53 L41 27 H31 Z" fill="#ffffff"/>
                    <text x="70" y="30" font-family="Poppins, 'Segoe UI', Arial, sans-serif" font-size="28" font-weight="700" fill="#0b3866">Blitz<tspan fill="#b57bee">tech</tspan></text>
                    <text x="71" y="50" font-family="Poppins, 'Segoe UI', Arial, sans-serif" font-size="10" font-weight="600" letter-spacing="3" fill="url(#blitzGrad)">Innovation</text>
                </svg>
            </div>

            <form class="form-horizontal" action="security_shell/op_login.php" method="post">
                <div class="form-group">
                    <label for="email">Username or Email</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="email" name="email" placeholder="Enter username / email" required autocomplete="username">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="mdi mdi-account-outline"></i></span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                        <div class="input-group-append">
                            <span class="input-group-text toggle-password" id="togglePasswordBtn" title="Show / Hide Password">
                                <i class="mdi mdi-eye-outline" id="togglePasswordIcon"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="year_id">Financial Year</label>
                    <div class="input-group">
                        <select class="form-control" id="year_id" name="year_id" required>
                            <? Utils::getDropDownOptionsFromSelect($oYear->getYears(), 'id', 'year_name') ?>
                        </select>
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="mdi mdi-calendar-blank-outline"></i></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-navy btn-block waves-effect waves-light" type="submit" name="submit">
                        <span>Sign In</span> <i class="mdi mdi-arrow-right ml-1"></i>
                    </button>
                </div>
            </form>

            <div class="login-footer">
                <p>© <?=date('Y')?> Blitztech Innovation. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- RIGHT 50%: Blitztech Cover Showcase -->
    <div class="login-right-pane">
        <img src="assets/images/blitztechcover.png" alt="Blitztech Cover" class="cover-poster-img" onerror="this.onerror=null; this.src='assets/images/blitzcover.png';">
    </div>
</div>

<div class="login-alerts">
    <?
    SuccessHandler::displayMessages();
    ErrorHandler::displayErrors();
    ?>
</div>

<!-- JAVASCRIPT -->
<script src="assets/libs/jquery/jquery.min.js"></script>
<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/libs/metismenu/metisMenu.min.js"></script>
<script src="assets/libs/simplebar/simplebar.min.js"></script>
<script src="assets/libs/node-waves/waves.min.js"></script>

<script src="assets/js/app.js"></script>

<script>
    $(document).ready(function() {
        $('#togglePasswordBtn').on('click', function(e) {
            e.preventDefault();
            var $pwd = $('#password');
            var $icon = $('#togglePasswordIcon');
            if ($pwd.attr('type') === 'password') {
                $pwd.attr('type', 'text');
                $icon.removeClass('mdi-eye-outline').addClass('mdi-eye-off-outline');
            } else {
                $pwd.attr('type', 'password');
                $icon.removeClass('mdi-eye-off-outline').addClass('mdi-eye-outline');
            }
        });
    });
</script>

</body>
</html>
