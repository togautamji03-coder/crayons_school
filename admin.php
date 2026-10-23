
<?php
 ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
    
/**
 * admin.php
 *
 * File-based CMS administration panel.
 *
 * Features:
 * - Admin login
 * - Teacher management
 * - Event management
 * - Contact management
 * - Student birthday management
 * - CSRF protection
 * - Required image uploads
 * - No database
 */

require_once __DIR__ . '/admin-config.php';
require_once __DIR__ . '/content-manager.php';
require_once __DIR__ . '/super-admin-config.php';

sendAdminSecurityHeaders();


/* =========================================================
   LOGIN / LOGOUT
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'login'
) {

    $password = $_POST['password'] ?? '';

    if (adminLogin($password)) {

        setFlash(
            'success',
            'Login successful.'
        );

        header(
            'Location: admin.php'
        );

        exit;

    } else {

        setFlash(
            'error',
            'Incorrect password.'
        );

        header(
            'Location: admin.php'
        );

        exit;
    }
}


/*
 * Logout is handled through POST so that it is
 * CSRF protected.
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'logout'
) {

    try {

        cm_process_action();

        header(
            'Location: admin.php'
        );

        exit;

    } catch (Throwable $e) {

        setFlash(
            'error',
            $e->getMessage()
        );
    }
}


/* =========================================================
   AUTH CHECK
   ========================================================= */

$loggedIn = isAdminLoggedIn();
$theme = cm_get_theme();
$heroImages = cm_get_hero_images();
$contentLimits = cm_get_content_limits();
$aboutSettings = cm_get_about();


/* =========================================================
   PROCESS CMS ACTIONS
   ========================================================= */

if (
    $loggedIn &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') !== 'login' &&
    ($_POST['action'] ?? '') !== 'logout'
) {

    if (($_POST['action'] ?? '') === 'update_director_profile') {

        try {

            $settings = sa_get_settings();

            sa_save_director_profile(array(
                'director_name' => $_POST['director_name'] ?? '',
            ));

            $result = array(
                'type' => 'success',
                'message' => 'Director name and photo updated successfully.'
            );

        } catch (Throwable $e) {

            $result = array(
                'type' => 'error',
                'message' => $e->getMessage()
            );
        }

    } else {

        $result = cm_process_action();
    }

    /*
     * Show the actual success/error message before
     * redirecting after POST.
     */
    if (is_array($result)) {

        if (
            isset($result['type']) &&
            isset($result['message'])
        ) {

            setFlash(
                $result['type'],
                $result['message']
            );
        }

    }

    /*
     * Redirect after POST to prevent duplicate submission
     * when browser refreshes the page.
     */
    header(
        'Location: admin.php'
    );

    exit;
}

/* =========================================================
   FLASH
   ========================================================= */

$flash = getFlash();


/* =========================================================
   LOGIN PAGE
   ========================================================= */

if (!$loggedIn):

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>School Admin Login</title>

    <style>

        :root {
            --admin-primary: <?= cm_escape($theme['primary']) ?>;
            --admin-secondary: <?= cm_escape($theme['secondary']) ?>;
            --admin-dark: <?= cm_escape($theme['dark']) ?>;
            --admin-light: <?= cm_escape($theme['light']) ?>;
            --admin-surface: <?= cm_escape($theme['light']) ?>;
            --admin-border: color-mix(in srgb, var(--admin-dark) 12%, transparent);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f6fb;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 14px;
            padding: 35px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
        }

        .login-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-title h1 {
            margin: 0 0 8px;
            font-size: 28px;
            color: #222;
        }

        .login-title p {
            margin: 0;
            color: #777;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="password"] {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d8dde5;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
        }

        input[type="password"]:focus {
            border-color: #0d6efd;
        }

        .btn {
            width: 100%;
            border: 0;
            border-radius: 8px;
            padding: 13px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            background: #0d6efd;
            color: #fff;
        }

        .btn:hover {
            opacity: 0.92;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #e8f7ee;
            color: #176b3a;
        }

        .alert-error {
            background: #fdecec;
            color: #a32020;
        }

        .admin-result-overlay { position:fixed; inset:0; z-index:99999; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(0,0,0,.42); animation:adminPopupFadeIn .18s ease-out; }
        .admin-result-popup { width:min(92vw,430px); background:#fff; border-radius:16px; padding:28px 28px 24px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,.22); position:relative; animation:adminPopupScaleIn .2s ease-out; }
        .admin-result-icon { width:58px; height:58px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; font-size:30px; font-weight:700; }
        .admin-result-popup.success .admin-result-icon { background:#e8f7ee; color:#176b3a; }
        .admin-result-popup.error .admin-result-icon { background:#fdecec; color:#a32020; }
        .admin-result-title { margin:0 0 8px; font-size:21px; color:#222; }
        .admin-result-message { margin:0 0 20px; color:#666; line-height:1.55; font-size:14px; }
        .admin-result-close { border:0; border-radius:8px; padding:10px 24px; background:var(--admin-primary); color:#fff; font-weight:600; cursor:pointer; }
        .admin-result-x { position:absolute; top:10px; right:12px; border:0; background:transparent; color:#888; font-size:25px; cursor:pointer; line-height:1; }
        @keyframes adminPopupFadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes adminPopupScaleIn { from { transform:scale(.94); opacity:0; } to { transform:scale(1); opacity:1; } }

        .login-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: #999;
        }

        .login-box {
            background: var(--admin-surface);
        }

        .btn {
            background: var(--admin-primary);
        }

        input[type="password"]:focus {
            border-color: var(--admin-primary);
        }

    
</style>
</head>

<body>

<div class="login-box">

    <div class="login-title">

        <h1>School Admin</h1>

        <p>Website Content Manager</p>

    </div>


    <?php if ($flash): ?>

        <div class="admin-result-overlay" id="adminResultPopup">
            <div class="admin-result-popup <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="alertdialog" aria-modal="true">
                <button type="button" class="admin-result-x" aria-label="Close" onclick="closeAdminResultPopup()">&times;</button>
                <div class="admin-result-icon"><?= $flash['type'] === 'success' ? '&#10003;' : '!' ?></div>
                <h2 class="admin-result-title"><?= $flash['type'] === 'success' ? 'Success' : 'Update Failed' ?></h2>
                <p class="admin-result-message"><?= cm_escape($flash['message']) ?></p>
                <button type="button" class="admin-result-close" onclick="closeAdminResultPopup()">OK</button>
            </div>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        action="admin.php"
    >

        <input
            type="hidden"
            name="action"
            value="login"
        >


        <div class="form-group">

            <label for="password">
                Admin Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                autofocus
            >

        </div>


        <button
            type="submit"
            class="btn"
        >
            Login
        </button>

    </form>


    <div class="login-footer">
        School Website Content Management System
    </div>

</div>

<?php
exit;
endif;


/* =========================================================
   LOAD CURRENT CONTENT
   ========================================================= */

try {

    $teachers = cm_get_teachers_from_file(
        TEAM_FILE
    );

} catch (Throwable $e) {

    $teachers = [];

    setFlash(
        'error',
        'Unable to read teachers: ' .
        $e->getMessage()
    );
}


try {

    $events = cm_get_events_from_file(
        EVENT_FILE
    );

} catch (Throwable $e) {

    $events = [];

    setFlash(
        'error',
        'Unable to read events: ' .
        $e->getMessage()
    );
}


try {

    $programs = cm_get_programs_from_file(
        PROGRAM_FILE
    );

} catch (Throwable $e) {

    $programs = [];

    setFlash(
        'error',
        'Unable to read programs: ' . $e->getMessage()
    );
}


$reviews = [];
try {
    $reviews = cm_get_reviews();
} catch (Throwable $e) {
    $reviews = [];
    setFlash('error', 'Unable to read parent reviews: ' . $e->getMessage());
}

$announcements = [];
try {
    $announcements = cm_get_announcements();
} catch (Throwable $e) {
    $announcements = [];
    setFlash('error', 'Unable to read announcements: ' . $e->getMessage());
}


$gallerySections = [];
try {
    $gallerySections = array_reverse(cm_get_gallery());
} catch (Throwable $e) {
    $gallerySections = [];
    setFlash('error', 'Unable to read gallery: ' . $e->getMessage());
}


try {

    $birthdays = cm_get_birthdays_from_file(
        BIRTHDAY_FILE
    );

} catch (Throwable $e) {

    $birthdays = [];

    setFlash(
        'error',
        'Unable to read birthdays: ' .
        $e->getMessage()
    );
}


/* =========================================================
   CONTACT DEFAULTS
   ========================================================= */

$contactAddress = '';
$contactEmail   = '';
$contactPhone   = '';
$contactMapLocation = '';
$contactMapLink = ''; // Set from Admin > Location (Google Maps Link)
$whatsappPhone = cm_get_whatsapp_phone();
$socialMedia = cm_get_social_media();


/*
 * Try to read the current contact information from
 * the managed contact section.
 */
try {

    $contactHtml = cm_read_file(
        CONTACT_FILE
    );


    /*
     * Email
     */
    if (
        preg_match(
            '/<!-- CONTACT-INFO-MANAGED-START -->(.*?)<!-- CONTACT-INFO-MANAGED-END -->/is',
            $contactHtml,
            $contactMatch
        )
    ) {

        $contactBlock = $contactMatch[1];


        /*
         * Extract email from the first paragraph
         * after "Mail Us".
         */
        if (
            preg_match(
                '/Mail Us.*?<p[^>]*>(.*?)<\/p>/is',
                $contactBlock,
                $m
            )
        ) {

            $contactEmail = trim(
                strip_tags($m[1])
            );
        }


        /*
         * Extract phone.
         */
        if (
            preg_match(
                '/Telephone.*?<p[^>]*>(.*?)<\/p>/is',
                $contactBlock,
                $m
            )
        ) {

            $contactPhone = trim(
                strip_tags($m[1])
            );
        }
    }


    /*
     * Address.
     */
    if (
        preg_match(
            '/<!-- CONTACT-ADDRESS-MANAGED-START -->(.*?)<!-- CONTACT-ADDRESS-MANAGED-END -->/is',
            $contactHtml,
            $addressMatch
        )
    ) {

        $contactAddress = trim(
            strip_tags(
                $addressMatch[1]
            )
        );
    }

    /*
     * Map location. The value is stored in dedicated markers
     * inside contact.html so it remains editable without a database.
     */
    if (
        preg_match(
            '/<!-- CONTACT-MAP-LOCATION-START -->(.*?)<!-- CONTACT-MAP-LOCATION-END -->/is',
            $contactHtml,
            $mapLocationMatch
        )
    ) {

        $contactMapLocation = trim(
            strip_tags(
                $mapLocationMatch[1]
            )
        );
    }

    if (
        preg_match(
            '/<!-- CONTACT-MAP-LINK-START -->(.*?)<!-- CONTACT-MAP-LINK-END -->/is',
            $contactHtml,
            $mapLinkMatch
        )
    ) {
        $contactMapLink = trim(strip_tags($mapLinkMatch[1]));
    }

} catch (Throwable $e) {
    /*
     * Keep defaults if contact section is not yet
     * configured.
     */
}


/* =========================================================
   HTML OUTPUT
   ========================================================= */

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        School Website Admin
    </title>


    <style>

        :root {
            --admin-primary: <?= cm_escape($theme['primary']) ?>;
            --admin-secondary: <?= cm_escape($theme['secondary']) ?>;
            --admin-dark: <?= cm_escape($theme['dark']) ?>;
            --admin-light: <?= cm_escape($theme['light']) ?>;
            --admin-surface: <?= cm_escape($theme['light']) ?>;
            --admin-border: color-mix(in srgb, var(--admin-dark) 12%, transparent);
        }

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;
            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f7fb;
            color: #222;
        }


        /* =================================================
           LAYOUT
           ================================================= */

        .admin-wrapper {
            min-height: 100vh;
            display: flex;
        }


        .sidebar {
            width: 250px;
            background: #172033;
            color: #fff;
            padding: 25px 18px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
        }


        .brand {
            padding: 5px 10px 25px;
            border-bottom: 1px solid rgba(
                255,
                255,
                255,
                0.1
            );
            margin-bottom: 20px;
        }


        .brand h2 {
            margin: 0 0 5px;
            font-size: 21px;
        }


        .brand span {
            color: #aeb8ca;
            font-size: 12px;
        }


        .nav-link {
            display: block;
            padding: 11px 12px;
            margin-bottom: 5px;
            color: #d8deea;
            text-decoration: none;
            border-radius: 7px;
            font-size: 14px;
        }


        .nav-link:hover {
            background: rgba(
                255,
                255,
                255,
                0.08
            );
            color: #fff;
        }


        .main {
            margin-left: 250px;
            width: calc(
                100% - 250px
            );
            padding: 30px;
        }


        /* =================================================
           TOP BAR
           ================================================= */

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 30px;
        }


        .topbar h1 {
            margin: 0;
            font-size: 28px;
        }


        .topbar p {
            margin: 6px 0 0;
            color: #777;
            font-size: 14px;
        }


        .logout-btn {
            background: #fff;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 7px;
            padding: 9px 15px;
            cursor: pointer;
        }


        .logout-btn:hover {
            background: #f2f2f2;
        }


        /* =================================================
           ALERT
           ================================================= */

        .alert {
            padding: 14px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
        }


        .alert-success {
            background: #e8f7ee;
            color: #176b3a;
        }


        .alert-error {
            background: #fdecec;
            color: #a32020;
        }

        .admin-result-overlay { position:fixed; inset:0; z-index:99999; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(0,0,0,.42); animation:adminPopupFadeIn .18s ease-out; }
        .admin-result-popup { width:min(92vw,430px); background:#fff; border-radius:16px; padding:28px 28px 24px; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,.22); position:relative; animation:adminPopupScaleIn .2s ease-out; }
        .admin-result-icon { width:58px; height:58px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; font-size:30px; font-weight:700; }
        .admin-result-popup.success .admin-result-icon { background:#e8f7ee; color:#176b3a; }
        .admin-result-popup.error .admin-result-icon { background:#fdecec; color:#a32020; }
        .admin-result-title { margin:0 0 8px; font-size:21px; color:#222; }
        .admin-result-message { margin:0 0 20px; color:#666; line-height:1.55; font-size:14px; }
        .admin-result-close { border:0; border-radius:8px; padding:10px 24px; background:var(--admin-primary); color:#fff; font-weight:600; cursor:pointer; }
        .admin-result-x { position:absolute; top:10px; right:12px; border:0; background:transparent; color:#888; font-size:25px; cursor:pointer; line-height:1; }
        @keyframes adminPopupFadeIn { from { opacity:0; } to { opacity:1; } }
        @keyframes adminPopupScaleIn { from { transform:scale(.94); opacity:0; } to { transform:scale(1); opacity:1; } }


        /* =================================================
           STATS
           ================================================= */

        .stats {
            display: grid;
            grid-template-columns:
                repeat(
                    3,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 20px;
            margin-bottom: 30px;
        }


        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            box-shadow:
                0 4px 18px
                rgba(
                    0,
                    0,
                    0,
                    0.05
                );
        }


        .stat-label {
            color: #777;
            font-size: 13px;
            margin-bottom: 8px;
        }


        .stat-number {
            font-size: 32px;
            font-weight: 700;
        }


        /* =================================================
           SECTIONS
           ================================================= */

        .section {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow:
                0 4px 18px
                rgba(
                    0,
                    0,
                    0,
                    0.05
                );
        }


        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }


        .section-header h2 {
            margin: 0;
            font-size: 21px;
        }


        .section-header p {
            margin: 5px 0 0;
            color: #777;
            font-size: 13px;
        }


        /* =================================================
           FORMS
           ================================================= */

        .form-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 18px;
        }


        .form-group {
            margin-bottom: 17px;
        }


        .full-width {
            grid-column: 1 / -1;
        }


        label {
            display: block;
            font-weight: 600;
            margin-bottom: 7px;
            font-size: 14px;
        }


        input[type="text"],
        input[type="email"],
        input[type="url"],
        input[type="file"],
        textarea {
            width: 100%;
            border: 1px solid #d9dee7;
            border-radius: 7px;
            padding: 11px 12px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            background: #fff;
        }


        input:focus,
        textarea:focus {
            border-color: #0d6efd;
        }


        textarea {
            min-height: 110px;
            resize: vertical;
        }


        .help-text {
            color: #888;
            font-size: 12px;
            margin-top: 5px;
        }


        .btn-primary {
            background: #0d6efd;
            color: #fff;
            border: 0;
            border-radius: 7px;
            padding: 11px 18px;
            font-weight: 600;
            cursor: pointer;
        }


        .btn-primary:hover {
            opacity: .9;
        }


        .btn-danger {
            background: #dc3545;
            color: #fff;
            border: 0;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 12px;
            cursor: pointer;
        }


        .btn-danger:hover {
            opacity: .9;
        }


        /* =================================================
           LISTS
           ================================================= */

        .content-list {
            margin-top: 25px;
        }


        .content-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 14px 0;
            border-top: 1px solid #edf0f4;
        }


        .content-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }


        .thumb {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
            flex-shrink: 0;
        }


        .content-info {
            min-width: 0;
        }


        .content-info h3 {
            margin: 0 0 4px;
            font-size: 15px;
        }


        .content-info p {
            margin: 0;
            color: #777;
            font-size: 13px;
            overflow-wrap: anywhere;
        }


        .content-meta {
            margin-top: 4px;
            color: #999;
            font-size: 12px;
        }


        .empty {
            padding: 25px 10px;
            color: #888;
            text-align: center;
            border-top: 1px solid #edf0f4;
        }


        /* =================================================
           CONTACT
           ================================================= */

        .contact-note {
            padding: 12px 14px;
            background: var(--admin-surface);
            border-radius: 7px;
            color: #5b6472;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid var(--admin-border);
        }

        /* Theme-aware admin surfaces */
        .section,
        .stat-card,
        .content-item,
        .login-box,
        .logout-btn,
        .admin-surface,
        .hero-admin-card {
            background: var(--admin-surface) !important;
        }

        .section,
        .stat-card,
        .content-item,
        .hero-admin-card {
            border-color: var(--admin-border);
        }

        .btn-primary {
            background: var(--admin-primary);
        }

        .btn-secondary {
            background: var(--admin-secondary);
            border-color: var(--admin-secondary);
        }

        .sidebar {
            background: var(--admin-dark);
        }

        .brand span,
        .nav-link {
            color: color-mix(in srgb, var(--admin-light) 82%, white);
        }

        .nav-link:hover {
            background: color-mix(in srgb, var(--admin-primary) 22%, transparent);
            color: #fff;
        }

        /* Active admin dashboard tab */
        .nav-link.admin-tab-active {
            background: var(--admin-primary) !important;
            color: #fff !important;
            font-weight: 700;
            box-shadow: 0 4px 12px color-mix(in srgb, var(--admin-primary) 28%, transparent);
        }

        .nav-link.admin-tab-active::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 16px;
            margin-right: 8px;
            vertical-align: -3px;
            border-radius: 999px;
            background: #fff;
        }

        /* Only the selected dashboard section is visible */
        .admin-content-section {
            display: none;
        }

        .admin-content-section.admin-section-active {
            display: block;
        }

        input[type="text"],
        input[type="email"],
        input[type="file"],
        textarea {
            background: color-mix(in srgb, var(--admin-light) 72%, white);
        }

        input:focus,
        textarea:focus {
            border-color: var(--admin-primary);
        }

        .content-item {
            border-top-color: var(--admin-border);
        }

        .empty {
            border-top-color: var(--admin-border);
        }

        .theme-aware-surface {
            background: var(--admin-surface) !important;
            border-color: var(--admin-border) !important;
        }


        /* =================================================
           MOBILE
           ================================================= */

        @media (
            max-width: 900px
        ) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                width: calc(
                    100% - 210px
                );
            }

            .stats {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );
            }
        }


        @media (
            max-width: 700px
        ) {

            .sidebar {
                position: static;
                width: 100%;
            }


            .admin-wrapper {
                display: block;
            }


            .main {
                margin-left: 0;
                width: 100%;
                padding: 18px;
            }


            .stats {
                grid-template-columns: 1fr;
            }


            .form-grid {
                grid-template-columns: 1fr;
            }


            .full-width {
                grid-column: auto;
            }


            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }


            .section {
                padding: 18px;
            }


            .content-item {
                align-items: flex-start;
            }


            .content-left {
                align-items: flex-start;
            }


            .thumb {
                width: 58px;
                height: 58px;
            }
        }

            /* =================================================
           SAVE / UPDATE LOADER
           ================================================= */
        .admin-save-loader {
            position: fixed;
            inset: 0;
            z-index: 100000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0, 0, 0, .45);
            backdrop-filter: blur(2px);
        }
        .admin-save-loader.is-visible { display: flex; }
        .admin-save-loader-card {
            min-width: 230px;
            max-width: 90vw;
            background: #fff;
            border-radius: 16px;
            padding: 28px 30px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }
        .admin-save-spinner {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            border: 5px solid #e9e9e9;
            border-top-color: var(--admin-primary);
            border-radius: 50%;
            animation: adminSaveSpin .8s linear infinite;
        }
        .admin-save-loader-title {
            margin: 0 0 6px;
            color: #222;
            font-size: 18px;
            font-weight: 700;
        }
        .admin-save-loader-text { margin: 0; color: #666; font-size: 14px; }
        .admin-form-submitting { pointer-events: none; }
        .admin-form-submitting button[type="submit"],
        .admin-form-submitting input[type="submit"] { opacity: .7; cursor: wait; }
        @keyframes adminSaveSpin { to { transform: rotate(360deg); } }
</style>
</head>


<body>


<!-- Global loader shown while any admin update is being processed. It disappears on the next page load, when the success/error popup is shown. -->
<div class="admin-save-loader" id="adminSaveLoader" aria-hidden="true">
    <div class="admin-save-loader-card" role="status" aria-live="polite">
        <div class="admin-save-spinner" aria-hidden="true"></div>
        <h2 class="admin-save-loader-title">Saving changes...</h2>
        <p class="admin-save-loader-text">Please wait while your update is being processed.</p>
    </div>
</div>

<div class="admin-wrapper">


    <!-- =================================================
         SIDEBAR
         ================================================= -->

    <aside class="sidebar">

        <div class="brand">

            <h2>School Admin</h2>

            <span>
                Website Content Manager
            </span>

        </div>


        <a
            href="#dashboard"
            class="nav-link admin-tab"
            data-section="dashboard"
        >
            Dashboard
        </a>


        <a
            href="#director-profile"
            class="nav-link admin-tab"
            data-section="director-profile"
        >
            Director Profile
        </a>


        <a
            href="#about-us"
            class="nav-link admin-tab"
            data-section="about-us"
        >
            About Us
        </a>


        <a
            href="#teachers"
            class="nav-link admin-tab"
            data-section="teachers"
        >
            Teachers
        </a>


        <a
            href="#events"
            class="nav-link admin-tab"
            data-section="events"
        >
            Events
        </a>


        <a
            href="#programs"
            class="nav-link admin-tab"
            data-section="programs"
        >
            Our Programs
        </a>


        <a
            href="#hero"
            class="nav-link admin-tab"
            data-section="hero"
        >
            Hero Image
        </a>


        <a
            href="#birthdays"
            class="nav-link admin-tab"
            data-section="birthdays"
        >
            Student Birthdays
        </a>


        <a
            href="#theme"
            class="nav-link admin-tab"
            data-section="theme"
        >
            Colour Theme
        </a>


        <a
            href="#contact"
            class="nav-link admin-tab"
            data-section="contact"
        >
            Contact Details
        </a>


        <a
            href="#reviews"
            class="nav-link admin-tab"
            data-section="reviews"
        >
            Parent Reviews
        </a>


        <a
            href="#whatsapp"
            class="nav-link admin-tab"
            data-section="whatsapp"
        >
            WhatsApp Settings
        </a>


        <a
            href="#social-media"
            class="nav-link admin-tab"
            data-section="social-media"
        >
            Social Media Handles
        </a>


        <a
            href="#announcements"
            class="nav-link admin-tab"
            data-section="announcements"
        >
            Announcements
        </a>


        <a
            href="#gallery"
            class="nav-link admin-tab"
            data-section="gallery"
        >
            Gallery Management
        </a>


        <a
            href="index.html"
            target="_blank"
            class="nav-link"
        >
            View Website ↗
        </a>


        <form
            method="POST"
            action="admin.php"
            style="margin-top:20px;"
        >

            <?= csrfField() ?>

            <input
                type="hidden"
                name="action"
                value="logout"
            >

            <button
                type="submit"
                class="nav-link"
                style="
                    width:100%;
                    text-align:left;
                    background:transparent;
                    border:0;
                    cursor:pointer;
                    font-family:inherit;
                "
            >
                Logout
            </button>

        </form>

    </aside>


    <!-- =================================================
         MAIN
         ================================================= -->

    <main class="main">


        <!-- =================================================
             TOP BAR
             ================================================= -->

        <div class="topbar">

            <div>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Manage your school website content
                </p>

            </div>


            <form
                method="POST"
                action="admin.php"
            >

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="action"
                    value="logout"
                >

                <button
                    type="submit"
                    class="logout-btn"
                >
                    Logout
                </button>

            </form>

        </div>


        <?php if ($flash): ?>

            <div class="admin-result-overlay" id="adminResultPopup">
            <div class="admin-result-popup <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="alertdialog" aria-modal="true">
                <button type="button" class="admin-result-x" aria-label="Close" onclick="closeAdminResultPopup()">&times;</button>
                <div class="admin-result-icon"><?= $flash['type'] === 'success' ? '&#10003;' : '!' ?></div>
                <h2 class="admin-result-title"><?= $flash['type'] === 'success' ? 'Success' : 'Update Failed' ?></h2>
                <p class="admin-result-message"><?= cm_escape($flash['message']) ?></p>
                <button type="button" class="admin-result-close" onclick="closeAdminResultPopup()">OK</button>
            </div>
        </div>

        <?php endif; ?>


        <!-- =================================================
             DASHBOARD STATS
             ================================================= -->

        <section
            class="stats admin-content-section"
            id="dashboard"
        >

            <div class="stat-card">

                <div class="stat-label">
                    Total Teachers
                </div>

                <div class="stat-number">
                    <?= count($teachers) ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Total Events
                </div>

                <div class="stat-number">
                    <?= count($events) ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Total Programs
                </div>

                <div class="stat-number">
                    <?= count($programs) ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    CMS
                </div>

                <div class="stat-number">
                    Active
                </div>

            </div>

        </section>


        <!-- =================================================
             DIRECTOR PROFILE
             ================================================= -->

        <?php
            $directorSettings = sa_get_settings();
            $directorName = $directorSettings['director_name'] ?? '';
            $directorPhoto = $directorSettings['director_photo'] ?? '';
        ?>

        <section
            class="section admin-content-section"
            id="director-profile"
        >

            <div class="section-header">

                <div>

                    <h2>Director Profile</h2>

                    <p>
                        Update the Director name and photo shown on the website.
                        Changes are applied to the Director section across the website.
                    </p>

                </div>

            </div>

            <form
                method="POST"
                action="admin.php"
                enctype="multipart/form-data"
            >

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="action"
                    value="update_director_profile"
                >

                <div class="form-grid">

                    <div class="form-group">

                        <label for="director_name">
                            Director Name
                        </label>

                        <input
                            type="text"
                            id="director_name"
                            name="director_name"
                            maxlength="150"
                            value="<?= cm_escape($directorName) ?>"
                            placeholder="Enter Director Name"
                        >

                    </div>

                    <div class="form-group">

                        <label for="director_photo">
                            Director Photo
                        </label>

                        <input
                            type="file"
                            id="director_photo"
                            name="director_photo"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <small style="display:block;margin-top:7px;color:#777;">
                            JPG, JPEG, PNG or WEBP. Maximum 5 MB.
                            Leave empty to keep the existing photo.
                        </small>

                        <?php if ($directorPhoto): ?>
                            <div style="margin-top:14px;">
                                <div style="font-size:13px;color:#666;margin-bottom:7px;">
                                    Current Director Photo
                                </div>
                                <img
                                    src="<?= cm_escape($directorPhoto) ?>?v=<?= is_file(__DIR__ . '/' . ltrim($directorPhoto, '/')) ? (int)filemtime(__DIR__ . '/' . ltrim($directorPhoto, '/')) : time() ?>"
                                    alt="Current Director Photo"
                                    style="width:140px;height:160px;object-fit:cover;object-position:center;border-radius:10px;border:1px solid #ddd;display:block;"
                                >
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="full-width">

                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            Save Director Profile
                        </button>

                    </div>

                </div>

            </form>

        </section>


        <!-- =================================================
             ABOUT US
             ================================================= -->

        <section
            class="section admin-content-section"
            id="about-us"
        >

            <div class="section-header">

                <div>

                    <h2>About Us Section</h2>

                    <p>
                        Update the About Us content displayed on the homepage and About page.
                        Changes are applied to both pages immediately after saving.
                    </p>

                </div>

            </div>

            <form method="POST" action="admin.php">

                <?= csrfField() ?>

                <input type="hidden" name="action" value="update_about">

                <div class="form-grid">

                    <div class="form-group">
                        <label for="about_label">Section Label</label>
                        <input
                            type="text"
                            id="about_label"
                            name="about_label"
                            maxlength="100"
                            value="<?= cm_escape($aboutSettings['label'] ?? 'About Us') ?>"
                            placeholder="About Us"
                        >
                    </div>

                    <div class="form-group">
                        <label for="about_heading">Main Heading *</label>
                        <input
                            type="text"
                            id="about_heading"
                            name="about_heading"
                            maxlength="300"
                            required
                            value="<?= cm_escape($aboutSettings['heading'] ?? '') ?>"
                            placeholder="Enter About Us heading"
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="about_paragraph1">First Paragraph *</label>
                        <textarea
                            id="about_paragraph1"
                            name="about_paragraph1"
                            rows="5"
                            maxlength="1500"
                            required
                            placeholder="Enter the first About Us paragraph..."
                        ><?= cm_escape($aboutSettings['paragraph1'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label for="about_paragraph2">Second Paragraph *</label>
                        <textarea
                            id="about_paragraph2"
                            name="about_paragraph2"
                            rows="5"
                            maxlength="1500"
                            required
                            placeholder="Enter the second About Us paragraph..."
                        ><?= cm_escape($aboutSettings['paragraph2'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="about_feature_1">Feature 1</label>
                        <input type="text" id="about_feature_1" name="about_feature_1" maxlength="100" value="<?= cm_escape($aboutSettings['features'][0] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="about_feature_2">Feature 2</label>
                        <input type="text" id="about_feature_2" name="about_feature_2" maxlength="100" value="<?= cm_escape($aboutSettings['features'][1] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="about_feature_3">Feature 3</label>
                        <input type="text" id="about_feature_3" name="about_feature_3" maxlength="100" value="<?= cm_escape($aboutSettings['features'][2] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="about_feature_4">Feature 4</label>
                        <input type="text" id="about_feature_4" name="about_feature_4" maxlength="100" value="<?= cm_escape($aboutSettings['features'][3] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="about_feature_5">Feature 5</label>
                        <input type="text" id="about_feature_5" name="about_feature_5" maxlength="100" value="<?= cm_escape($aboutSettings['features'][4] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="about_feature_6">Feature 6</label>
                        <input type="text" id="about_feature_6" name="about_feature_6" maxlength="100" value="<?= cm_escape($aboutSettings['features'][5] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="about_video_url">YouTube Video URL</label>
                        <input
                            type="url"
                            id="about_video_url"
                            name="about_video_url"
                            maxlength="1000"
                            value="<?= cm_escape($aboutSettings['video_url'] ?? '') ?>"
                            placeholder="https://www.youtube.com/embed/..."
                        >
                        <small style="display:block;margin-top:7px;color:#777;">Use the YouTube embed URL that should open when visitors click the video button.</small>
                    </div>

                    <div class="full-width">
                        <div class="contact-note" style="margin-top:8px;">
                            <strong>Contact us button:</strong> The button text and WhatsApp URL are managed automatically by the <strong>WhatsApp Settings</strong> section. They are not editable here.
                        </div>
                    </div>

                    <div class="full-width">
                        <button type="submit" class="btn-primary">Save About Us Section</button>
                    </div>

                </div>

            </form>

        </section>


        <!-- =================================================
             TEACHERS
             ================================================= -->

        <section
            class="section admin-content-section"
            id="teachers"
        >

            <div class="section-header">

                <div>

                    <h2>
                        Teachers
                    </h2>

                    <p>
                        Add or remove teachers from the website.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                action="admin.php"
                enctype="multipart/form-data"
            >

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="action"
                    value="add_teacher"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label for="teacher_name">
                            Teacher Name *
                        </label>

                        <input
                            type="text"
                            id="teacher_name"
                            name="teacher_name"
                            maxlength="150"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="teacher_designation">
                            Designation *
                        </label>

                        <input
                            type="text"
                            id="teacher_designation"
                            name="teacher_designation"
                            maxlength="150"
                            required
                        >

                    </div>


                    <div class="form-group full-width">

                        <label for="teacher_description">
                            Teacher Description *
                        </label>

                        <textarea
                            id="teacher_description"
                            name="teacher_description"
                            rows="4"
                            maxlength="500"
                            placeholder="Enter a short description about the teacher..."
                            required
                        ></textarea>

                        <div class="help-text">
                            Maximum 500 characters. This description will appear on the teacher card.
                        </div>

                    </div>


                    <div class="form-group full-width">

                        <label for="teacher_image">
                            Teacher Image *
                        </label>

                        <input
                            type="file"
                            id="teacher_image"
                            name="teacher_image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                        >

                        <div class="help-text">
                            JPG, JPEG, PNG or WEBP. Maximum 5 MB.
                        </div>

                    </div>


                    <div class="full-width">

                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            + Add Teacher
                        </button>

                    </div>

                </div>

            </form>


            <!-- CURRENT TEACHERS -->

            <div class="content-list">

                <?php if (empty($teachers)): ?>

                    <div class="empty">
                        No teachers found.
                    </div>

                <?php else: ?>

                    <?php foreach ($teachers as $teacher): ?>

                        <div class="content-item">

                            <div class="content-left">

                                <img
                                    src="<?= cm_escape(
                                        $teacher['image']
                                    ) ?>"
                                    alt=""
                                    class="thumb"
                                >


                                <div class="content-info">

                                    <h3>
                                        <?= cm_escape(
                                            $teacher['name']
                                        ) ?>
                                    </h3>

                                    <p>
                                        <?= cm_escape(
                                            $teacher['designation']
                                        ) ?>
                                    </p>

                                    <div class="content-meta">
                                        ID:
                                        <?= cm_escape(
                                            $teacher['id']
                                        ) ?>
                                    </div>

                                </div>

                            </div>


                            <form
                                method="POST"
                                action="admin.php"
                                onsubmit="return confirm(
                                    'Are you sure you want to delete this teacher?'
                                );"
                            >

                                <?= csrfField() ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete_teacher"
                                >

                                <input
                                    type="hidden"
                                    name="teacher_id"
                                    value="<?= cm_escape(
                                        $teacher['id']
                                    ) ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn-danger"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>


        <!-- =================================================
             EVENTS
             ================================================= -->

        <section
            class="section admin-content-section"
            id="events"
        >

            <div class="section-header">

                <div>

                    <h2>
                        Events
                    </h2>

                    <p>
                        Add or remove school events and announcements.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                action="admin.php"
                enctype="multipart/form-data"
            >

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="action"
                    value="add_event"
                >


                <div class="form-grid">


                    <div class="form-group">

                        <label for="event_title">
                            Event Title *
                        </label>

                        <input
                            type="text"
                            id="event_title"
                            name="event_title"
                            maxlength="200"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="event_date">
                            Event Date *
                        </label>

                        <input
                            type="date"
                            id="event_date"
                            name="event_date"
                            required
                        >

                        <div class="help-text">
                            Select the event date from the calendar.
                        </div>

                    </div>


                    <div class="form-group full-width">

                        <label for="event_description">
                            Description *
                        </label>

                        <textarea
                            id="event_description"
                            name="event_description"
                            maxlength="1000"
                            required
                        ></textarea>

                    </div>


                    <div class="form-group full-width">

                        <label for="event_image">
                            Event Image *
                        </label>

                        <input
                            type="file"
                            id="event_image"
                            name="event_image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                        >

                        <div class="help-text">
                            JPG, JPEG, PNG or WEBP. Maximum 5 MB.
                        </div>

                    </div>


                    <div class="full-width">

                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            + Add Event
                        </button>

                    </div>

                </div>

            </form>


            <!-- CURRENT EVENTS -->

            <div class="content-list">

                <?php if (empty($events)): ?>

                    <div class="empty">
                        No events found.
                    </div>

                <?php else: ?>

                    <?php foreach ($events as $event): ?>

                        <div class="content-item">

                            <div class="content-left">

                                <img
                                    src="<?= cm_escape(
                                        $event['image']
                                    ) ?>"
                                    alt=""
                                    class="thumb"
                                >


                                <div class="content-info">

                                    <h3>
                                        <?= cm_escape(
                                            $event['title']
                                        ) ?>
                                    </h3>

                                    <p>
                                        <?= cm_escape(
                                            $event['description']
                                        ) ?>
                                    </p>

                                    <div class="content-meta">

                                        <?= cm_escape(
                                            $event['date']
                                        ) ?>

                                        &nbsp; • &nbsp;

                                        ID:
                                        <?= cm_escape(
                                            $event['id']
                                        ) ?>

                                    </div>

                                </div>

                            </div>


                            <form
                                method="POST"
                                action="admin.php"
                                onsubmit="return confirm(
                                    'Are you sure you want to delete this event?'
                                );"
                            >

                                <?= csrfField() ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete_event"
                                >

                                <input
                                    type="hidden"
                                    name="event_id"
                                    value="<?= cm_escape(
                                        $event['id']
                                    ) ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn-danger"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>



        <!-- =================================================
             OUR PROGRAMS
             ================================================= -->

        <section class="section admin-content-section" id="programs">
            <div class="section-header">
                <div>
                    <h2>Our Programs</h2>
                    <p>Add or remove programs shown on the homepage and Programs page.</p>
                </div>
            </div>

            <form method="POST" action="admin.php" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_program">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="program_title">Program Name *</label>
                        <input type="text" id="program_title" name="program_title" maxlength="200" required>
                    </div>
                    <div class="form-group">
                        <label for="program_category">Category / Subject *</label>
                        <input type="text" id="program_category" name="program_category" maxlength="100" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="program_description">Description *</label>
                        <textarea id="program_description" name="program_description" maxlength="1000" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="program_teacher_name">Teacher Name *</label>
                        <input type="text" id="program_teacher_name" name="program_teacher_name" maxlength="120" required>
                    </div>
                    <div class="form-group">
                        <label for="program_teacher_designation">Teacher Designation *</label>
                        <input type="text" id="program_teacher_designation" name="program_teacher_designation" maxlength="120" required>
                    </div>
                    <div class="form-group">
                        <label for="program_teacher_image">Teacher / Program Head Photo *</label>
                        <input type="file" id="program_teacher_image" name="program_teacher_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        <div class="help-text">Photo shown beside the teacher name on the program card. Maximum 5 MB.</div>
                    </div>
                    <div class="form-group full-width">
                        <label for="program_image">Program Image *</label>
                        <input type="file" id="program_image" name="program_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        <div class="help-text">JPG, JPEG, PNG or WEBP. Maximum 5 MB.</div>
                    </div>
                    <div class="full-width">
                        <button type="submit" class="btn-primary">+ Add Program</button>
                    </div>
                </div>
            </form>

            <div class="content-list">
                <?php if (empty($programs)): ?>
                    <div class="empty">No programs found.</div>
                <?php else: ?>
                    <?php foreach ($programs as $program): ?>
                        <div class="content-item">
                            <div class="content-left">
                                <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
                                    <img src="<?= cm_escape($program['image']) ?>" alt="" class="thumb">
                                    <img src="<?= cm_escape($program['teacher_image'] ?? 'img/program-teacher.jpg') ?>" alt="" class="thumb" style="border-radius:50%;object-fit:cover;">
                                </div>
                                <div class="content-info">
                                    <h3><?= cm_escape($program['title']) ?></h3>
                                    <p><?= cm_escape($program['description']) ?></p>
                                    <div class="content-meta">
                                        <?= cm_escape($program['category']) ?> &nbsp; • &nbsp; <?= cm_escape($program['teacher_name']) ?> &nbsp; • &nbsp; ID: <?= cm_escape($program['id']) ?>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to delete this program?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete_program">
                                <input type="hidden" name="program_id" value="<?= cm_escape($program['id']) ?>">
                                <button type="submit" class="btn-danger">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>


        <!-- =================================================
             HERO IMAGE SLIDER
             ================================================= -->

        <section class="section admin-content-section" id="hero">
            <div class="section-header">
                <div>
                    <h2>Hero Images</h2>
                    <p>Add multiple homepage hero images. They will slide automatically every 3 seconds.</p>
                </div>
            </div>

            <?php $heroManagedCount = (count($heroImages) === 1 && $heroImages[0] === 'img/hero-img.jpg') ? 0 : count($heroImages); ?>
            <div class="contact-note" style="margin-bottom:18px;">Hero images: <strong><?= (int) $heroManagedCount ?></strong> / <strong><?= (int) $contentLimits['max_hero_images'] ?></strong> allowed.</div>

            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; margin-bottom:24px;">
                <?php foreach ($heroImages as $index => $heroImage): ?>
                    <div class="hero-admin-card" style="border:1px solid; border-radius:12px; overflow:hidden;">
                        <div class="theme-aware-surface" style="position:relative; aspect-ratio:16/7;">
                            <img src="<?= cm_escape($heroImage) ?>" alt="Hero image <?= (int) ($index + 1) ?>" style="display:block; width:100%; height:100%; object-fit:fill;">
                        </div>
                        <div style="padding:12px; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                            <div style="font-size:13px; color:#666;">Slide <?= (int) ($index + 1) ?></div>
                            <?php if ($heroImage !== 'img/hero-img.jpg'): ?>
                                <form method="POST" action="admin.php" onsubmit="return confirm('Delete this hero image?');" style="margin:0;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_hero_image">
                                    <input type="hidden" name="hero_image_path" value="<?= cm_escape($heroImage) ?>">
                                    <button type="submit" class="btn-danger">Delete</button>
                                </form>
                            <?php else: ?>
                                <span style="font-size:12px;color:#777;">Default image</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="admin.php" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_hero_image">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="hero_image">Add Hero Image *</label>
                        <input type="file" id="hero_image" name="hero_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        <div class="help-text">JPG, JPEG, PNG or WEBP. Maximum 5 MB. Any image dimensions are accepted; the image will be stretched to fill the hero frame. Super Admin limit: <?= (int) $contentLimits['max_hero_images'] ?> hero images.</div>
                    </div>
                    <div class="full-width">
                        <button type="submit" class="btn-primary" <?= $heroManagedCount >= $contentLimits['max_hero_images'] ? 'disabled title="Maximum hero image limit reached"' : '' ?>>Add Hero Image</button>
                        <?php if ($heroManagedCount >= $contentLimits['max_hero_images']): ?><div class="help-text" style="margin-top:8px;color:#b91c1c;">Maximum hero image limit reached. Ask Super Admin to increase the limit.</div><?php endif; ?>
                    </div>
                </div>
            </form>
        </section>


        <!-- =================================================
             STUDENT BIRTHDAYS
             ================================================= -->

        <section
            class="section admin-content-section"
            id="birthdays"
        >

            <div class="section-header">

                <div>

                    <h2>
                        Student Birthdays
                    </h2>

                    <p>
                        Add student name, date of birth and photo. If the birthday
                        matches today's date, the student will automatically appear
                        in a birthday popup on the home page.
                    </p>

                </div>

            </div>


            <div class="contact-note">
                The date of birth is stored as YYYY-MM-DD. The homepage checks only
                the month and day, so the year does not affect the birthday popup.
            </div>


            <form
                method="POST"
                action="admin.php"
                enctype="multipart/form-data"
            >

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="action"
                    value="add_birthday"
                >


                <div class="form-grid">

                    <div class="form-group">

                        <label for="birthday_name">
                            Student Name *
                        </label>

                        <input
                            type="text"
                            id="birthday_name"
                            name="birthday_name"
                            maxlength="150"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="birthday_dob">
                            Date of Birth *
                        </label>

                        <input
                            type="date"
                            id="birthday_dob"
                            name="birthday_dob"
                            required
                        >

                    </div>


                    <div class="form-group full-width">

                        <label for="birthday_image">
                            Student Photo *
                        </label>

                        <input
                            type="file"
                            id="birthday_image"
                            name="birthday_image"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                        >

                        <div class="help-text">
                            JPG, JPEG, PNG or WEBP. Maximum 5 MB.
                        </div>

                    </div>


                    <div class="full-width">

                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            + Add Student Birthday
                        </button>

                    </div>

                </div>

            </form>


            <!-- CURRENT STUDENT BIRTHDAYS -->

            <div class="content-list">

                <?php if (empty($birthdays)): ?>

                    <div class="empty">
                        No student birthdays found.
                    </div>

                <?php else: ?>

                    <?php foreach ($birthdays as $birthday): ?>

                        <div class="content-item">

                            <div class="content-left">

                                <img
                                    src="<?= cm_escape(
                                        $birthday['image']
                                    ) ?>"
                                    alt=""
                                    class="thumb"
                                >


                                <div class="content-info">

                                    <h3>
                                        <?= cm_escape(
                                            $birthday['name']
                                        ) ?>
                                    </h3>

                                    <p>
                                        Date of Birth:
                                        <?= cm_escape(
                                            $birthday['dob']
                                        ) ?>
                                    </p>

                                    <div class="content-meta">
                                        ID:
                                        <?= cm_escape(
                                            $birthday['id']
                                        ) ?>
                                    </div>

                                </div>

                            </div>


                            <form
                                method="POST"
                                action="admin.php"
                                onsubmit="return confirm(
                                    'Are you sure you want to delete this student birthday?'
                                );"
                            >

                                <?= csrfField() ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete_birthday"
                                >

                                <input
                                    type="hidden"
                                    name="birthday_id"
                                    value="<?= cm_escape(
                                        $birthday['id']
                                    ) ?>"
                                >

                                <button
                                    type="submit"
                                    class="btn-danger"
                                >
                                    Delete
                                </button>

                            </form>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>




        <!-- =================================================
             COLOUR THEME
             ================================================= -->

        <section
            class="section admin-content-section"
            id="theme"
        >

            <div class="section-header">
                <div>
                    <h2>Website Colour Theme</h2>
                    <p>Customize the main colours used across the website, including the card/block colour used by content sections.</p>
                </div>
            </div>

            <form method="POST" action="admin.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_theme">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="theme_primary">Primary Colour *</label>
                        <input type="color" id="theme_primary" name="theme_primary" value="<?= cm_escape($theme['primary']) ?>" required>
                        <small style="color:#777;">Main buttons, highlights and primary elements.</small>
                    </div>

                    <div class="form-group">
                        <label for="theme_secondary">Secondary Colour *</label>
                        <input type="color" id="theme_secondary" name="theme_secondary" value="<?= cm_escape($theme['secondary']) ?>" required>
                        <small style="color:#777;">Hover states and secondary highlights.</small>
                    </div>

                    <div class="form-group">
                        <label for="theme_dark">Dark Colour *</label>
                        <input type="color" id="theme_dark" name="theme_dark" value="<?= cm_escape($theme['dark']) ?>" required>
                        <small style="color:#777;">Dark text and dark theme elements.</small>
                    </div>

                    <div class="form-group">
                        <label for="theme_light">Light Colour *</label>
                        <input type="color" id="theme_light" name="theme_light" value="<?= cm_escape($theme['light']) ?>" required>
                        <small style="color:#777;">Light backgrounds and light theme elements.</small>
                    </div>

                    <div class="form-group">
                        <label for="theme_block">Website Block / Card Colour *</label>
                        <input type="color" id="theme_block" name="theme_block" value="<?= cm_escape($theme['block']) ?>" required>
                        <small style="color:#777;">Controls the background colour of website cards/blocks such as Programs, Events, Blog, Team, Testimonials and content cards.</small>
                    </div>

                    <div class="form-group">
                        <label for="theme_section">Website Section Background Colour *</label>
                        <input type="color" id="theme_section" name="theme_section" value="<?= cm_escape($theme['section']) ?>" required>
                        <small style="color:#777;">Controls the outer background of major website sections such as About, Services, Programs, Events, Team, Testimonials, Blog and Contact. Card colour remains independently controlled above.</small>
                    </div>

                    <div class="full-width" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                        <button type="submit" class="btn-primary">Save Colour Theme</button>
                    </div>
                </div>
            </form>

            <form method="POST" action="admin.php" style="margin-top:12px;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reset_theme">
                <button type="submit" class="btn-secondary" style="background:#6c757d; border-color:#6c757d;" onclick="return confirm('Reset the website colour theme to the original default theme? Your other website settings will not be changed.');">Reset to Default Theme</button>
                <small style="display:block; margin-top:7px; color:#777;">Restores only the original website colours. School name, logo, director, programs, events and other settings remain unchanged.</small>
            </form>

        </section>


        <!-- =================================================
             CONTACT
             ================================================= -->

        <section
            class="section admin-content-section"
            id="contact"
        >

            <div class="section-header">

                <div>

                    <h2>
                        Contact Details
                    </h2>

                    <p>
                        Update the contact information shown on the website.
                    </p>

                </div>

            </div>


            <div class="contact-note">

                Updating these details will update the pages that
                contain the corresponding CMS contact markers.

            </div>


            <form
                method="POST"
                action="admin.php"
            >

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="action"
                    value="update_contact"
                >


                <div class="form-grid">


                    <div class="form-group full-width">

                        <label for="contact_address">
                            Address *
                        </label>

                        <input
                            type="text"
                            id="contact_address"
                            name="contact_address"
                            maxlength="300"
                            value="<?= cm_escape(
                                $contactAddress
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="contact_email">
                            Email *
                        </label>

                        <input
                            type="email"
                            id="contact_email"
                            name="contact_email"
                            maxlength="200"
                            value="<?= cm_escape(
                                $contactEmail
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="contact_phone">
                            Phone *
                        </label>

                        <input
    type="text"
    id="contact_phone"
    name="contact_phone"
    maxlength="10"
    minlength="10"
    pattern="[1-9][0-9]{9}"
    inputmode="numeric"
    value="<?= cm_escape(
        $contactPhone
    ) ?>"
    required
>

                    </div>


                    <div class="form-group full-width">

                        <label for="contact_map_location">
                            Location (Google Maps Link) *
                        </label>

                        <input
                            type="url"
                            id="contact_map_location"
                            name="contact_map_location"
                            maxlength="2000"
                            value="<?= cm_escape(
                                $contactMapLink
                            ) ?>"
                            placeholder="https://www.google.com/maps/..."
                            required
                        >

                        <small style="display:block;margin-top:6px;color:#6b7280;">
                            Open Google Maps, choose the school/location, click Share, then copy and paste the Google Maps link here.
                        </small>

                    </div>


                    <div class="full-width">

                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            Save Contact Details
                        </button>

                    </div>

                </div>

            </form>

        </section>


        <!-- =================================================
             PARENT REVIEWS
             ================================================= -->

        <section class="section admin-content-section" id="reviews">
            <div class="section-header">
                <div>
                    <h2>Parent Reviews</h2>
                    <p>Review parent submissions, approve them for the website, reject them, or delete them.</p>
                </div>
            </div>

            <?php
            $pendingReviews = array_values(array_filter($reviews, function($r){ return ($r['status'] ?? '') === 'pending'; }));
            $approvedReviews = array_values(array_filter($reviews, function($r){ return ($r['status'] ?? '') === 'approved'; }));
            $rejectedReviews = array_values(array_filter($reviews, function($r){ return ($r['status'] ?? '') === 'rejected'; }));
            ?>

            <div class="stats" style="margin-bottom:20px;">
                <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-number"><?= count($pendingReviews) ?></div></div>
                <div class="stat-card"><div class="stat-label">Published</div><div class="stat-number"><?= count($approvedReviews) ?></div></div>
                <div class="stat-card"><div class="stat-label">Rejected</div><div class="stat-number"><?= count($rejectedReviews) ?></div></div>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="empty">No parent reviews have been submitted yet.</div>
            <?php else: ?>
                <div class="content-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="content-item">
                            <div class="content-left">
                                <div style="display:flex;gap:12px;align-items:center;flex-shrink:0;">
                                    <?php if (!empty($review['photo'])): ?>
                                        <img src="<?= cm_escape($review['photo']) ?>" alt="" class="thumb" style="border-radius:50%;object-fit:cover;">
                                    <?php else: ?>
                                        <img src="img/testimonial-2.jpg" alt="" class="thumb" style="border-radius:50%;object-fit:cover;">
                                    <?php endif; ?>
                                </div>
                                <div class="content-info">
                                    <h3><?= cm_escape($review['parent_name']) ?></h3>
                                    <p><?= cm_escape($review['profession'] ?: 'Parent') ?></p>
                                    <p style="color:#ff4880;letter-spacing:2px;"><?= str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5-(int)$review['rating']) ?></p>
                                    <p style="max-width:700px;"><?= nl2br(cm_escape($review['review'])) ?></p>
                                    <small><?= cm_escape($review['submitted_at']) ?> · <strong><?= cm_escape(ucfirst($review['status'])) ?></strong></small>
                                </div>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <?php if (($review['status'] ?? '') !== 'approved'): ?>
                                    <form method="POST" action="admin.php">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="approve_review">
                                        <input type="hidden" name="review_id" value="<?= cm_escape($review['id']) ?>">
                                        <button type="submit" class="btn-primary">Approve</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (($review['status'] ?? '') !== 'rejected'): ?>
                                    <form method="POST" action="admin.php">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="reject_review">
                                        <input type="hidden" name="review_id" value="<?= cm_escape($review['id']) ?>">
                                        <button type="submit" class="btn-danger">Reject</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="admin.php" onsubmit="return confirm('Delete this review permanently?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_review">
                                    <input type="hidden" name="review_id" value="<?= cm_escape($review['id']) ?>">
                                    <button type="submit" class="btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="contact-note" style="margin-top:18px;">
                Parent reviews are <strong>pending by default</strong>. Only reviews approved by the school admin are published on the homepage and Testimonial page.
            </div>
        </section>


        <!-- =================================================
             ANNOUNCEMENT MANAGEMENT
             ================================================= -->

        <section class="section admin-content-section" id="announcements">
            <div class="section-header">
                <div>
                    <h2>Announcement Management</h2>
                    <p>Add, edit and remove school announcements with a downloadable JPG, PNG, WEBP image or PDF file.</p>
                </div>
            </div>

            <div class="contact-note">
                Maximum file size is 10 MB. The announcement date is added automatically using the date on which you publish the announcement. Enter the description and upload the related file. Announcements are displayed on the public Announcement page with the newest date first.
            </div>

            <form method="POST" action="admin.php" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_announcement">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="announcement_description">Description *</label>
                        <textarea id="announcement_description" name="announcement_description" maxlength="1000" rows="3" placeholder="Enter announcement description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="announcement_file">Related File * (Image/PDF)</label>
                        <input type="file" id="announcement_file" name="announcement_file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required>
                        <small style="display:block;margin-top:7px;color:#777;">Allowed: JPG, JPEG, PNG, WEBP and PDF. Maximum 10 MB.</small>
                    </div>
                    <div class="full-width">
                        <button type="submit" class="btn-primary">+ Add Announcement</button>
                    </div>
                </div>
            </form>

            <?php if (empty($announcements)): ?>
                <div class="contact-note" style="margin-top:18px;">No announcements have been added yet.</div>
            <?php else: ?>
                <div style="overflow-x:auto;margin-top:22px;">
                    <table style="width:100%;border-collapse:collapse;min-width:760px;">
                        <thead>
                            <tr>
                                <th style="text-align:left;padding:12px;border-bottom:2px solid #e5e7eb;">Date</th>
                                <th style="text-align:left;padding:12px;border-bottom:2px solid #e5e7eb;">Description</th>
                                <th style="text-align:left;padding:12px;border-bottom:2px solid #e5e7eb;">File</th>
                                <th style="text-align:left;padding:12px;border-bottom:2px solid #e5e7eb;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($announcements as $announcement): ?>
                            <tr>
                                <td style="padding:12px;border-bottom:1px solid #eee;white-space:nowrap;"><?= cm_escape(date('d-m-Y', strtotime($announcement['date']))) ?></td>
                                <td style="padding:12px;border-bottom:1px solid #eee;min-width:300px;"><?= nl2br(cm_escape($announcement['description'])) ?></td>
                                <td style="padding:12px;border-bottom:1px solid #eee;"><a href="<?= cm_escape($announcement['file']) ?>" target="_blank" rel="noopener noreferrer"><?= cm_escape($announcement['file_name']) ?></a></td>
                                <td style="padding:12px;border-bottom:1px solid #eee;min-width:330px;">
                                    <form method="POST" action="admin.php" enctype="multipart/form-data" style="display:grid;gap:9px;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update_announcement">
                                        <input type="hidden" name="announcement_id" value="<?= cm_escape($announcement['id']) ?>">
                                        <textarea name="announcement_description" maxlength="1000" rows="2" required><?= cm_escape($announcement['description']) ?></textarea>
                                        <input type="file" name="announcement_file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
                                        <small style="color:#777;">Leave file empty to keep the current file.</small>
                                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                            <button type="submit" class="btn-primary">Update</button>
                                            <button type="submit" name="delete_from_update" value="1" formaction="admin.php" formmethod="POST" class="btn-danger" onclick="this.form.querySelector('[name=action]').value='delete_announcement';return confirm('Delete this announcement and its file? This cannot be undone.');">Delete</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>


        <!-- =================================================
             GALLERY MANAGEMENT
             ================================================= -->
        <style>
            .gallery-admin-card {
                margin-top: 18px;
                padding: 0;
                border: 2px solid transparent;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
                transition: transform .2s ease, box-shadow .2s ease;
            }
            .gallery-admin-card:hover {
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.09);
            }
            .gallery-admin-card.gallery-colour-a {
                background: #eef6ff;
                border-color: #60a5fa;
            }
            .gallery-admin-card.gallery-colour-b {
                background: #fff4e6;
                border-color: #f59e0b;
            }
            .gallery-admin-accordion-header {
                width: 100%;
                border: 0;
                background: transparent;
                padding: 17px 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
                cursor: pointer;
                text-align: left;
                color: #0f172a;
            }
            .gallery-admin-accordion-header:hover {
                background: rgba(255,255,255,.28);
            }
            .gallery-admin-section-heading {
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 0;
                border-left: 6px solid #2563eb;
                padding-left: 12px;
            }
            .gallery-colour-b .gallery-admin-section-heading {
                border-left-color: #ea580c;
            }
            .gallery-admin-section-title {
                font-size: 20px;
                font-weight: 700;
                overflow-wrap: anywhere;
            }
            .gallery-admin-chevron {
                flex: 0 0 auto;
                width: 34px;
                height: 34px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: rgba(255,255,255,.75);
                font-size: 17px;
                transition: transform .2s ease;
            }
            .gallery-admin-card.is-open .gallery-admin-chevron {
                transform: rotate(180deg);
            }
            .gallery-admin-panel {
                display: none;
                padding: 0 20px 20px;
                border-top: 1px solid rgba(148,163,184,.35);
                background: rgba(255,255,255,.18);
            }
            .gallery-admin-card.is-open .gallery-admin-panel {
                display: block;
            }
            .gallery-admin-panel > form:first-child {
                padding-top: 18px;
            }
            .gallery-admin-images {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                margin: 0 0 20px;
            }
            .gallery-admin-image-card {
                border: 1px solid rgba(148, 163, 184, .45);
                border-radius: 12px;
                padding: 8px;
                background: rgba(255,255,255,.88);
            }
            .gallery-admin-image-card img {
                width: 100%;
                height: 130px;
                object-fit: cover;
                border-radius: 8px;
                display: block;
            }
            @media (max-width: 700px) {
                .gallery-admin-accordion-header { padding: 15px; }
                .gallery-admin-panel { padding: 0 15px 15px; }
                .gallery-admin-section-title { font-size: 18px; }
                .gallery-admin-images { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
        </style>

        <section class="section admin-content-section" id="gallery">
            <div class="section-header">
                <div>
                    <h2>Gallery Management</h2>
                    <p>Create gallery sections and manage the images displayed on the public Gallery page.</p>
                </div>
            </div>

            <div class="contact-note">
                Gallery sections: <strong><?= count($gallerySections) ?></strong> / <strong><?= (int) $contentLimits['max_gallery_sections'] ?></strong> allowed. Maximum <strong><?= (int) $contentLimits['max_gallery_images_per_section'] ?></strong> images per section. Only one section is expanded at a time so the dashboard remains clean even with many gallery sections.
            </div>

            <form method="POST" action="admin.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_gallery_section">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="gallery_section_name">New Section Name *</label>
                        <input type="text" id="gallery_section_name" name="gallery_section_name" maxlength="120" placeholder="e.g. Annual Function 2026" required>
                    </div>
                    <div class="full-width">
                        <button type="submit" class="btn-primary" <?= count($gallerySections) >= $contentLimits['max_gallery_sections'] ? 'disabled title="Maximum gallery section limit reached"' : '' ?>>+ Add Gallery Section</button>
                        <?php if (count($gallerySections) >= $contentLimits['max_gallery_sections']): ?><div class="help-text" style="margin-top:8px;color:#b91c1c;">Maximum gallery section limit reached. Ask Super Admin to increase the limit.</div><?php endif; ?>
                    </div>
                </div>
            </form>

            <?php if (empty($gallerySections)): ?>
                <div class="contact-note" style="margin-top:18px;">No gallery sections have been created yet.</div>
            <?php endif; ?>

            <?php foreach ($gallerySections as $galleryIndex => $gallerySection): ?>
                <?php $galleryImageCount = count($gallerySection['images'] ?? array()); $galleryRemaining = max(0, $contentLimits['max_gallery_images_per_section'] - $galleryImageCount); ?>
                <div class="gallery-admin-card <?= ($galleryIndex % 2 === 0) ? 'gallery-colour-a' : 'gallery-colour-b' ?>" data-gallery-accordion>
                    <button type="button" class="gallery-admin-accordion-header" data-gallery-toggle aria-expanded="<?= $galleryIndex === 0 ? 'true' : 'false' ?>">
                        <span class="gallery-admin-section-heading">
                            <strong class="gallery-admin-section-title">📁 <?= cm_escape($gallerySection['name']) ?></strong>
                        </span>
                        <span class="gallery-admin-chevron" aria-hidden="true">⌄</span>
                    </button>

                    <div class="gallery-admin-panel">
                        <form method="POST" action="admin.php" style="margin-bottom:18px;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_gallery_section">
                            <input type="hidden" name="gallery_section_id" value="<?= cm_escape($gallerySection['id']) ?>">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Section Name</label>
                                    <input type="text" name="gallery_section_name" maxlength="120" value="<?= cm_escape($gallerySection['name']) ?>" required>
                                </div>
                                <div style="display:flex;align-items:end;gap:10px;flex-wrap:wrap;">
                                    <button type="submit" class="btn-primary">Save Section Name</button>
                                    <button type="submit" class="btn-danger" formaction="admin.php" name="action" value="delete_gallery_section" onclick="return confirm('Delete this gallery section and ALL images inside it? This cannot be undone.');">Delete Section</button>
                                </div>
                            </div>
                        </form>

                        <?php if (!empty($gallerySection['images'])): ?>
                            <div class="gallery-admin-images">
                                <?php foreach ($gallerySection['images'] as $galleryImage): ?>
                                    <div class="gallery-admin-image-card">
                                        <img src="<?= cm_escape($galleryImage) ?>" alt="Gallery image">
                                        <form method="POST" action="admin.php" onsubmit="return confirm('Delete this image?');" style="margin-top:8px;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_gallery_image">
                                            <input type="hidden" name="gallery_section_id" value="<?= cm_escape($gallerySection['id']) ?>">
                                            <input type="hidden" name="gallery_image" value="<?= cm_escape($galleryImage) ?>">
                                            <button type="submit" class="btn-danger" style="width:100%;">Delete Image</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="contact-note" style="margin-bottom:18px;">No images in this section yet.</div>
                        <?php endif; ?>

                        <form method="POST" action="admin.php" enctype="multipart/form-data">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="add_gallery_images">
                            <input type="hidden" name="gallery_section_id" value="<?= cm_escape($gallerySection['id']) ?>">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Add Images to “<?= cm_escape($gallerySection['name']) ?>”</label>
                                    <input type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple required>
                                    <small style="display:block;margin-top:7px;color:#777;"><?= $galleryImageCount ?> / <?= (int) $contentLimits['max_gallery_images_per_section'] ?> images used. You can add up to <?= $galleryRemaining ?> more. Maximum 5 MB per image.</small>
                                </div>
                                <div style="display:flex;align-items:end;">
                                    <button type="submit" class="btn-primary" <?= $galleryRemaining <= 0 ? 'disabled title="Maximum images per section reached"' : '' ?>>+ Add Selected Images</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <script>
        (function () {
            function initGalleryAccordion() {
                var cards = Array.prototype.slice.call(document.querySelectorAll('[data-gallery-accordion]'));
                if (!cards.length) return;

                function closeCard(card) {
                    card.classList.remove('is-open');
                    var toggle = card.querySelector('[data-gallery-toggle]');
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                }

                function openCard(card) {
                    cards.forEach(function (other) {
                        if (other !== card) closeCard(other);
                    });
                    card.classList.add('is-open');
                    var toggle = card.querySelector('[data-gallery-toggle]');
                    if (toggle) toggle.setAttribute('aria-expanded', 'true');
                }

                cards.forEach(function (card, index) {
                    var toggle = card.querySelector('[data-gallery-toggle]');
                    if (!toggle) return;
                    toggle.addEventListener('click', function () {
                        if (card.classList.contains('is-open')) {
                            closeCard(card);
                        } else {
                            openCard(card);
                        }
                    });
                    if (index === 0) openCard(card);
                    else closeCard(card);
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initGalleryAccordion);
            } else {
                initGalleryAccordion();
            }
        })();
        </script>
        </section>


        <!-- =================================================
             SOCIAL MEDIA SETTINGS
             ================================================= -->

        <section
            class="section admin-content-section"
            id="social-media"
        >

            <div class="section-header">
                <div>
                    <h2>Social Media Handles</h2>
                    <p>Update the social media links displayed in the website footer.</p>
                </div>
            </div>

            <div class="contact-note">
                Enter the complete profile/page URL for each social network. Leave a field blank if the school does not want that social icon active. Changes apply automatically to the header and footer on all normal website pages.
            </div>

            <form method="POST" action="admin.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_social_media">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="social_facebook">Facebook</label>
                        <input
                            type="url"
                            id="social_facebook"
                            name="social_facebook"
                            value="<?= cm_escape($socialMedia['facebook'] ?? '') ?>"
                            placeholder="https://www.facebook.com/yourpage"
                        >
                    </div>

                    <div class="form-group">
                        <label for="social_twitter">Twitter / X</label>
                        <input
                            type="url"
                            id="social_twitter"
                            name="social_twitter"
                            value="<?= cm_escape($socialMedia['twitter'] ?? '') ?>"
                            placeholder="https://x.com/yourhandle"
                        >
                    </div>

                    <div class="form-group">
                        <label for="social_instagram">Instagram</label>
                        <input
                            type="url"
                            id="social_instagram"
                            name="social_instagram"
                            value="<?= cm_escape($socialMedia['instagram'] ?? '') ?>"
                            placeholder="https://www.instagram.com/yourhandle"
                        >
                    </div>

                    <div class="form-group">
                        <label for="social_linkedin">LinkedIn</label>
                        <input
                            type="url"
                            id="social_linkedin"
                            name="social_linkedin"
                            value="<?= cm_escape($socialMedia['linkedin'] ?? '') ?>"
                            placeholder="https://www.linkedin.com/company/yourpage"
                        >
                    </div>

                    <div class="full-width">
                        <button type="submit" class="btn-primary">Save Social Media Handles</button>
                    </div>
                </div>
            </form>

        </section>


        <!-- =================================================
             WHATSAPP SETTINGS
             ================================================= -->

        <section
            class="section admin-content-section"
            id="whatsapp"
        >

            <div class="section-header">
                <div>
                    <h2>WhatsApp Settings</h2>
                    <p>Set the WhatsApp number used by the website Contact us buttons.</p>
                </div>
            </div>

            <div class="contact-note">
                Buttons labelled <strong>Contact us</strong> replace the old Read More / More Details buttons and open WhatsApp with the standard school inquiry message. Enter only the 10-digit Indian WhatsApp number; +91 is added automatically.
            </div>

            <form method="POST" action="admin.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_whatsapp">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="whatsapp_phone">WhatsApp Number *</label>
                        <input
                            type="text"
                            id="whatsapp_phone"
                            name="whatsapp_phone"
                            maxlength="10"
                            minlength="10"
                            pattern="[1-9][0-9]{9}"
                            inputmode="numeric"
                            value=""
                            required
                        >
                        <small style="display:block;margin-top:7px;color:#777;">Example: 9211271039</small>
                    </div>

                    <div class="full-width">
                        <button type="submit" class="btn-primary">Save WhatsApp Number</button>
                    </div>
                </div>
            </form>

        </section>


        <!-- =================================================
             FOOTER
             ================================================= -->

        <div
            style="
                text-align:center;
                color:#999;
                font-size:12px;
                padding:10px 0 25px;
            "
        >
            School Website CMS • File-based management
        </div>


    </main>

</div>




<script>

// Global save/update loader: show immediately for POST submissions and
// keep it visible until the server returns the success/error popup.
(function () {
    'use strict';

    const loader = document.getElementById('adminSaveLoader');
    if (!loader) return;

    let submitting = false;

    function showSaveLoader(form) {
        if (submitting) return;
        submitting = true;

        if (form) form.classList.add('admin-form-submitting');
        loader.classList.add('is-visible');
        loader.setAttribute('aria-hidden', 'false');

        if (form) {
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
                button.disabled = true;
            });
        }
    }

    document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            // If an inline confirmation (e.g. Delete) cancelled the action,
            // do not show the loader.
            if (event.defaultPrevented) return;
            showSaveLoader(form);
        });
    });
})();
function closeAdminResultPopup() {
    const popup = document.getElementById('adminResultPopup');
    if (popup) popup.remove();
}

(function () {
    const popup = document.getElementById('adminResultPopup');
    if (popup) {
        popup.addEventListener('click', function (event) {
            if (event.target === popup) closeAdminResultPopup();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeAdminResultPopup();
        });
    }

    'use strict';

    const tabs = Array.from(document.querySelectorAll('.admin-tab[data-section]'));
    const sections = Array.from(document.querySelectorAll('.admin-content-section[id]'));
    const storageKey = 'schoolAdminSelectedTab';

    if (!tabs.length || !sections.length) return;

    function showSection(sectionId, updateUrl = true) {
        const exists = sections.some(section => section.id === sectionId);
        if (!exists) sectionId = 'dashboard';

        sections.forEach(section => {
            const active = section.id === sectionId;
            section.classList.toggle('admin-section-active', active);
            section.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        tabs.forEach(tab => {
            const active = tab.dataset.section === sectionId;
            tab.classList.toggle('admin-tab-active', active);
            tab.setAttribute('aria-current', active ? 'page' : 'false');
        });

        try {
            localStorage.setItem(storageKey, sectionId);
        } catch (e) {}

        if (updateUrl && window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + sectionId);
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function (event) {
            event.preventDefault();
            showSection(this.dataset.section, true);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    let initialSection = '';

    if (window.location.hash) {
        initialSection = window.location.hash.substring(1);
    }

    if (!initialSection) {
        try {
            initialSection = localStorage.getItem(storageKey) || '';
        } catch (e) {}
    }

    showSection(initialSection || 'dashboard', false);
})();
</script>

</body>

</html>
