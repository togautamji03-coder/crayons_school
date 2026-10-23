
<?php
/**
 * admin-config.php
 *
 * Configuration and security functions for the
 * file-based school website CMS.
 *
 * IMPORTANT:
 * - No database is used.
 * - Content is stored directly in HTML files.
 * - Images are stored inside /img.
 */


/* =========================================================
   BASIC CONFIGURATION
   ========================================================= */

define(
    'SITE_ROOT',
    __DIR__
);


/*
 * Main website files.
 */
define(
    'INDEX_FILE',
    SITE_ROOT . '/index.html'
);

define(
    'TEAM_FILE',
    SITE_ROOT . '/team.html'
);

define(
    'EVENT_FILE',
    SITE_ROOT . '/event.html'
);

define(
    'CONTACT_FILE',
    SITE_ROOT . '/contact.html'
);

define(
    'BIRTHDAY_FILE',
    SITE_ROOT . '/birthday.html'
);

define(
    'PROGRAM_FILE',
    SITE_ROOT . '/program.html'
);

define(
    'REVIEWS_FILE',
    SITE_ROOT . '/reviews.json'
);

define(
    'THEME_CSS_FILE',
    SITE_ROOT . '/css/style.css'
);

define(
    'SETTINGS_FILE',
    SITE_ROOT . '/school-settings.json'
);


/*
 * Image directory.
 */
define(
    'IMAGE_DIR',
    SITE_ROOT . '/img'
);


/*
 * Backup directory.
 *
 * HTML files are backed up before every modification.
 */
define(
    'BACKUP_DIR',
    SITE_ROOT . '/admin-backups'
);


/* =========================================================
   ADMIN PASSWORD
   ========================================================= */

/*
 * IMPORTANT:
 *
 * This is a bcrypt password hash.
 *
 * Default password used while setting up the system:
 *
 *     ChangeMe@123
 *
 * BEFORE going live, change this hash to a new password hash.
 *
 * Never store the plain-text password here.
 */

define(
    'ADMIN_PASSWORD_HASH',
    '$2y$12$Un8dYf0yIKbdiOVN1UHWDe41xiy8KxiNz5ICPSqktNYnKtVXglwEi'
);


/* =========================================================
   SESSION CONFIGURATION
   ========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {

    /*
     * Secure session cookie settings.
     *
     * "secure" is enabled automatically when HTTPS is used.
     */
    $isHttps = (
        !empty($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] !== 'off'
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    session_start();
}


function writeCssFile(
    $file,
    $content
) {
    $realTarget = realpath($file);
    $allowed = realpath(THEME_CSS_FILE);

    if (
        $realTarget === false ||
        $allowed === false ||
        $realTarget !== $allowed
    ) {
        throw new RuntimeException(
            'Unauthorized CSS file modification.'
        );
    }

    ensureBackupDirectory();

    $backupName =
        basename($file) .
        '.' . date('Ymd-His') .
        '.' . bin2hex(random_bytes(4)) .
        '.bak';

    if (!copy($file, BACKUP_DIR . '/' . $backupName)) {
        throw new RuntimeException(
            'Unable to create CSS backup.'
        );
    }

    $temp = $file . '.tmp-' . bin2hex(random_bytes(6));

    if (file_put_contents($temp, $content, LOCK_EX) === false) {
        @unlink($temp);
        throw new RuntimeException(
            'Unable to write CSS file.'
        );
    }

    if (!rename($temp, $file)) {
        @unlink($temp);
        throw new RuntimeException(
            'Unable to finalize CSS update.'
        );
    }
}


/* =========================================================
   ADMIN AUTHENTICATION
   ========================================================= */

function isAdminLoggedIn()
{
    return !empty(
        $_SESSION['school_admin_logged_in']
    );
}


function requireAdmin()
{
    if (!isAdminLoggedIn()) {

        header(
            'Location: admin.php'
        );

        exit;
    }
}


function adminLogin($password)
{
    if (
        !is_string($password) ||
        $password === ''
    ) {
        return false;
    }

    if (
        password_verify(
            $password,
            ADMIN_PASSWORD_HASH
        )
    ) {

        /*
         * Prevent session fixation.
         */
        session_regenerate_id(true);

        $_SESSION[
            'school_admin_logged_in'
        ] = true;

        $_SESSION[
            'school_admin_login_time'
        ] = time();

        return true;
    }

    return false;
}


function adminLogout()
{
    /*
     * Remove all session data.
     */
    $_SESSION = [];

    /*
     * Remove session cookie.
     */
    if (
        ini_get('session.use_cookies')
    ) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}


/* =========================================================
   CSRF PROTECTION
   ========================================================= */

function getCsrfToken()
{
    if (
        empty(
            $_SESSION['school_admin_csrf']
        )
    ) {

        $_SESSION[
            'school_admin_csrf'
        ] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION[
        'school_admin_csrf'
    ];
}


function csrfField()
{
    return
        '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(
            getCsrfToken(),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ) .
        '">';
}


function verifyCsrfToken($token)
{
    if (
        !is_string($token) ||
        $token === ''
    ) {
        return false;
    }

    return hash_equals(
        getCsrfToken(),
        $token
    );
}


/* =========================================================
   TEXT SANITIZATION
   ========================================================= */

function cleanText($value)
{
    $value = trim(
        (string)$value
    );

    /*
     * Remove null bytes.
     */
    $value = str_replace(
        "\0",
        '',
        $value
    );

    /*
     * Normalize excessive whitespace.
     */
    $value = preg_replace(
        '/[ \t]+/',
        ' ',
        $value
    );

    return trim(
        $value
    );
}


function safeFilename($filename)
{
    $filename = basename(
        (string)$filename
    );

    /*
     * Remove anything except:
     * letters
     * numbers
     * dot
     * dash
     * underscore
     */
    $filename = preg_replace(
        '/[^A-Za-z0-9._-]/',
        '_',
        $filename
    );

    return trim(
        $filename,
        '._'
    );
}


/* =========================================================
   IMAGE HANDLING
   ========================================================= */

define(
    'MAX_IMAGE_SIZE',
    5 * 1024 * 1024
);


/*
 * Allowed extensions.
 */
define(
    'ALLOWED_IMAGE_EXTENSIONS',
    [
        'jpg',
        'jpeg',
        'png',
        'webp'
    ]
);


/*
 * Allowed MIME types.
 */
define(
    'ALLOWED_IMAGE_MIMES',
    [
        'image/jpeg',
        'image/png',
        'image/webp'
    ]
);


function generateImageFilename(
    $prefix,
    $originalFilename
) {
    $extension = strtolower(
        pathinfo(
            $originalFilename,
            PATHINFO_EXTENSION
        )
    );

    if (
        !in_array(
            $extension,
            ALLOWED_IMAGE_EXTENSIONS,
            true
        )
    ) {
        throw new RuntimeException(
            'Unsupported image format.'
        );
    }

    return
        $prefix .
        bin2hex(
            random_bytes(8)
        ) .
        '.' .
        $extension;
}


function uploadImage(
    $file,
    $prefix
) {
    if (
        !is_array($file)
    ) {
        throw new RuntimeException(
            'Invalid image upload.'
        );
    }


    /*
     * Check upload error.
     */
    if (
        !isset($file['error']) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {

        throw new RuntimeException(
            'Image upload failed.'
        );
    }


    /*
     * Check size.
     */
    if (
        !isset($file['size']) ||
        $file['size'] <= 0
    ) {

        throw new RuntimeException(
            'Uploaded image is empty.'
        );
    }


    if (
        $file['size'] > MAX_IMAGE_SIZE
    ) {

        throw new RuntimeException(
            'Image size must not exceed 5 MB.'
        );
    }


    /*
     * Check temporary file.
     */
    if (
        empty($file['tmp_name']) ||
        !is_uploaded_file(
            $file['tmp_name']
        )
    ) {

        throw new RuntimeException(
            'Invalid uploaded file.'
        );
    }


    /*
     * Detect actual MIME type from file contents.
     *
     * Do NOT trust the browser supplied MIME type.
     */
    $finfo = new finfo(
        FILEINFO_MIME_TYPE
    );

    $mime = $finfo->file(
        $file['tmp_name']
    );

    if (
        !in_array(
            $mime,
            ALLOWED_IMAGE_MIMES,
            true
        )
    ) {

        throw new RuntimeException(
            'Only JPG, JPEG, PNG and WEBP images are allowed.'
        );
    }


    /*
     * Confirm the file is actually an image.
     */
    $imageInfo = @getimagesize(
        $file['tmp_name']
    );

    if (
        $imageInfo === false
    ) {

        throw new RuntimeException(
            'The uploaded file is not a valid image.'
        );
    }


    /*
     * Generate a completely new server-side filename.
     */
    $originalFilename =
        safeFilename(
            $file['name'] ?? ''
        );

    $filename = generateImageFilename(
        $prefix,
        $originalFilename
    );


    /*
     * Make sure image directory exists.
     */
    if (
        !is_dir(IMAGE_DIR)
    ) {

        if (
            !mkdir(
                IMAGE_DIR,
                0755,
                true
            )
        ) {

            throw new RuntimeException(
                'Unable to create image directory.'
            );
        }
    }


    $destination =
        IMAGE_DIR . '/' . $filename;


    /*
     * Never overwrite an existing file.
     */
    if (
        file_exists($destination)
    ) {

        throw new RuntimeException(
            'Generated image filename already exists.'
        );
    }


    /*
     * Move uploaded file.
     */
    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {

        throw new RuntimeException(
            'Unable to save uploaded image.'
        );
    }


    /*
     * Restrict normal image permissions.
     */
    @chmod(
        $destination,
        0644
    );


    return $filename;
}


/* =========================================================
   MANAGED IMAGE DELETION
   ========================================================= */

function deleteManagedImage($relativePath)
{
    $relativePath = ltrim(
        str_replace(
            '\\',
            '/',
            (string)$relativePath
        ),
        '/'
    );


    /*
     * Only allow files inside /img.
     */
    if (
        strpos(
            $relativePath,
            'img/'
        ) !== 0
    ) {
        return false;
    }


    $filename = basename(
        $relativePath
    );


    /*
     * Only delete files generated by this CMS.
     *
     * Therefore original template images such as:
     *
     * team-1.jpg
     * team-2.jpg
     * event-1.jpg
     *
     * are protected.
     */
    $allowedPrefixes = [
        'teacher_',
        'event_',
        'birthday_',
        'program_',
        'program_teacher_',
        'gallery_',
        'hero_'
    ];

    $isManaged = false;

    foreach (
        $allowedPrefixes as $prefix
    ) {

        if (
            strpos(
                $filename,
                $prefix
            ) === 0
        ) {

            $isManaged = true;
            break;
        }
    }


    if (!$isManaged) {
        return false;
    }


    $fullPath =
        IMAGE_DIR . '/' . $filename;


    /*
     * Extra path safety.
     */
    $realImageDir = realpath(
        IMAGE_DIR
    );

    $realFile = realpath(
        $fullPath
    );

    if (
        $realImageDir === false ||
        $realFile === false
    ) {
        return false;
    }


    if (
        dirname($realFile) !==
        $realImageDir
    ) {
        return false;
    }


    if (
        file_exists($realFile)
    ) {

        return @unlink(
            $realFile
        );
    }


    return false;
}


/* =========================================================
   HTML FILE WRITING + BACKUP
   ========================================================= */

function ensureBackupDirectory()
{
    if (
        !is_dir(BACKUP_DIR)
    ) {

        if (
            !mkdir(
                BACKUP_DIR,
                0755,
                true
            )
        ) {

            throw new RuntimeException(
                'Unable to create backup directory.'
            );
        }
    }
}


function writeHtmlFile(
    $file,
    $content
) {
    /*
     * Make sure the target is one of the known
     * website files.
     */
    /* Allow only existing HTML files directly inside the website root. */
    $allowedFiles = array();

    $htmlFiles = glob(SITE_ROOT . DIRECTORY_SEPARATOR . '*.html');

    if ($htmlFiles !== false) {
        foreach ($htmlFiles as $htmlFile) {
            $realHtmlFile = realpath($htmlFile);

            if ($realHtmlFile !== false) {
                $allowedFiles[] = $realHtmlFile;
            }
        }
    }

    $realTarget = realpath(
        $file
    );

    /*
     * For a file that already exists, realpath()
     * must match one of the allowed targets.
     */
    if (
        $realTarget === false ||
        !in_array(
            $realTarget,
            $allowedFiles,
            true
        )
    ) {

        throw new RuntimeException(
            'Unauthorized HTML file modification.'
        );
    }


    /*
     * Create backup directory.
     */
    ensureBackupDirectory();


    /*
     * Create timestamped backup.
     */
    $baseName =
        basename($realTarget);

    $backupName =
        date('Ymd_His') .
        '_' .
        bin2hex(
            random_bytes(4)
        ) .
        '_' .
        $baseName;

    $backupFile =
        BACKUP_DIR . '/' . $backupName;


    if (
        !copy(
            $realTarget,
            $backupFile
        )
    ) {

        throw new RuntimeException(
            'Unable to create HTML backup.'
        );
    }


    /*
     * Write to a temporary file first.
     *
     * This prevents partially-written HTML if the
     * write operation is interrupted.
     */
    $tempFile =
        $realTarget .
        '.tmp_' .
        bin2hex(
            random_bytes(8)
        );


    $bytes = file_put_contents(
        $tempFile,
        $content,
        LOCK_EX
    );


    if (
        $bytes === false
    ) {

        @unlink(
            $tempFile
        );

        throw new RuntimeException(
            'Unable to write HTML file.'
        );
    }


    /*
     * Replace original atomically.
     */
    if (
        !rename(
            $tempFile,
            $realTarget
        )
    ) {

        @unlink(
            $tempFile
        );

        throw new RuntimeException(
            'Unable to replace HTML file.'
        );
    }


    /*
     * Keep normal website-readable permissions.
     */
    @chmod(
        $realTarget,
        0644
    );


    return true;
}


/* =========================================================
   SECURITY HEADERS
   ========================================================= */

function sendAdminSecurityHeaders()
{
    header(
        'X-Content-Type-Options: nosniff'
    );

    header(
        'X-Frame-Options: SAMEORIGIN'
    );

    header(
        'Referrer-Policy: strict-origin-when-cross-origin'
    );

    header(
        'Permissions-Policy: camera=(), microphone=(), geolocation=()'
    );

    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );
}


/* =========================================================
   FLASH MESSAGES
   ========================================================= */

function setFlash(
    $type,
    $message
) {
    $_SESSION['admin_flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}


function getFlash()
{
    if (
        empty(
            $_SESSION['admin_flash']
        )
    ) {
        return null;
    }

    $flash =
        $_SESSION['admin_flash'];

    unset(
        $_SESSION['admin_flash']
    );

    return $flash;
}

