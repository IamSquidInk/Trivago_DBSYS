<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title ?? "trivago"; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">

    <style>
        /* ── ROOT VARIABLES ── */
        :root {
            --trivago-blue:  #007aff;
            --trivago-dark:  #1a1a2e;
            --trivago-gray:  #f5f5f5;
            --trivago-text:  #333333;
            --trivago-muted: #6c757d;
            --navbar-height: 64px;
        }

        /* ── BASE ── */
        body {
            background: var(--trivago-gray);
            font-family: 'Source Sans 3', sans-serif;
            color: var(--trivago-text);
            padding-top: var(--navbar-height);
            overflow-y: scroll;
        }

        /* ── NAVBAR ── */
        .navbar-custom {
            background: #ffffff;
            height: var(--navbar-height);
            padding: 0;
            border-bottom: 1px solid #e8e8e8;
        }

        .navbar-brand {
            font-size: 26px;
            font-weight: 700;
            color: var(--trivago-dark) !important;
            letter-spacing: -0.5px;
        }

        .navbar-brand span {
            color: var(--trivago-blue);
        }

        /* ── NAV LINKS ── */
        .nav-link-custom {
            color: var(--trivago-text);
            font-size: 14px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .nav-link-custom:hover {
            background: var(--trivago-gray);
            color: var(--trivago-blue);
        }

        /* ── BUTTONS ── */
        .btn-trivago {
            background: var(--trivago-blue);
            color: #ffffff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .btn-trivago:hover {
            background: #005fcc;
            color: #ffffff;
        }

        .btn-trivago-outline {
            background: transparent;
            color: var(--trivago-blue);
            border: 1.5px solid var(--trivago-blue);
            padding: 7px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-trivago-outline:hover {
            background: var(--trivago-blue);
            color: #ffffff;
        }

        /* ── CARDS ── */
        .card-trivago {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.07);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-trivago:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.10);
        }

        /* ── ADMIN SIDEBAR ── */
        .sidebar {
            background: #ffffff;
            min-height: calc(100vh - var(--navbar-height));
            border-right: 1px solid #e8e8e8;
            position: sticky;
            top: var(--navbar-height);
        }

        .sidebar-link {
            color: var(--trivago-text);
            padding: 10px 12px;
            border-left: 4px solid transparent;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: block;
        }

        .sidebar-link:hover {
            background: var(--trivago-gray);
            color: var(--trivago-blue);
        }

        .sidebar-link.active-sidebar {
            border-left: 4px solid var(--trivago-blue);
            background: #e8f0fe;
            color: var(--trivago-blue);
        }

        /* ── DASHBOARD CARDS ── */
        .dashboard-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            transition: all 0.2s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 28px rgba(0,0,0,0.09);
        }

        .dashboard-icon {
            font-size: 28px;
            color: var(--trivago-blue);
        }

        /* ── FOOTER ── */
        .footer-custom {
            background: var(--trivago-dark);
            color: #aaaaaa;
            font-size: 13px;
            padding: 40px 0 28px;
            margin-top: 60px;
        }

        .footer-custom a {
            color: #aaaaaa;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-custom a:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>

    <!-- ══════════════════════════════════ -->
    <!--              NAVBAR               -->
    <!-- ══════════════════════════════════ -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom fixed-top shadow-sm">
        <div class="container">

            <!-- BRAND -->
            <a class="navbar-brand" href="/trivago/index.php">
                tri<span>vago</span>
            </a>

            <!-- TOGGLER (mobile) -->
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <div class="ms-auto d-flex align-items-center gap-2">

                    <?php if(isset($_SESSION['guest_id'])): ?>

                        <?php if(($_SESSION['role'] ?? '') === 'admin'): ?>
                            <a href="/trivago/admin/admin_dashboard.php" class="nav-link-custom">
                                <i class="bi bi-speedometer2 me-1"></i>Admin Panel
                            </a>
                        <?php endif; ?>

                        <!-- LOGGED IN: dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-trivago dropdown-toggle"
                                    data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>
                                Hi, <?= htmlspecialchars($_SESSION['guest_name'] ?? 'User') ?>!
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li>
                                    <a class="dropdown-item" href="/trivago/user/user_dashboard.php">
                                        <i class="bi bi-speedometer2 me-2"></i>My Dashboard
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger"
                                    href="/trivago/auth/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </div>

                    <?php else: ?>

                        <!-- NOT LOGGED IN -->
                        <a href="/trivago/auth/login.php" class="btn-trivago-outline">
                            Sign in
                        </a>
                        <a href="/trivago/auth/register.php" class="btn btn-trivago">
                            Register
                        </a>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </nav>

    <!-- ══════════════════════════════════ -->
    <!--       PAGE CONTENT STARTS HERE    -->
    <!-- ══════════════════════════════════ -->