<?php

/**
 * content-manager.php
 *
 * File-based CMS manager for:
 * - Teachers
 * - Events
 * - Contact details
 *
 * No database is used.
 *
 * Managed HTML files:
 * - team.html
 * - event.html
 * - index.html
 */

require_once __DIR__ . '/admin-config.php';


/* =========================================================
   MANAGED SECTION MARKERS
   ========================================================= */

define('TEAM_MANAGED_START', '<!-- TEAM-MANAGED-START -->');
define('TEAM_MANAGED_END', '<!-- TEAM-MANAGED-END -->');

define('EVENT_MANAGED_START', '<!-- EVENT-MANAGED-START -->');
define('EVENT_MANAGED_END', '<!-- EVENT-MANAGED-END -->');
define('PROGRAM_MANAGED_START', '<!-- PROGRAM-MANAGED-START -->');
define('PROGRAM_MANAGED_END', '<!-- PROGRAM-MANAGED-END -->');
define('TESTIMONIAL_FILE', SITE_ROOT . '/testimonial.html');
define('BIRTHDAY_MANAGED_START', '<!-- BIRTHDAY-MANAGED-START -->');
define('BIRTHDAY_MANAGED_END', '<!-- BIRTHDAY-MANAGED-END -->');
define('EVENT_POPUP_MANAGED_START', '<!-- EVENT-POPUP-MANAGED-START -->');
define('EVENT_POPUP_MANAGED_END', '<!-- EVENT-POPUP-MANAGED-END -->');
define('THEME_MANAGED_START', '/* THEME-MANAGED-START */');
define('THEME_MANAGED_END', '/* THEME-MANAGED-END */');


/* =========================================================
   WEBSITE COLOUR THEME
   ========================================================= */

function cm_normalize_color($value, $fallback)
{
    $value = trim((string) $value);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
        return strtoupper($value);
    }
    if (preg_match('/^#[0-9a-fA-F]{3}$/', $value)) {
        return strtoupper(
            '#' . $value[1] . $value[1] .
            $value[2] . $value[2] .
            $value[3] . $value[3]
        );
    }
    return $fallback;
}

function cm_get_theme_defaults()
{
    return array(
        'primary' => '#FF4880',
        'secondary' => '#1A1A1A',
        'dark' => '#1A1A1A',
        'light' => '#FFECF2',
        'block' => '#FFECF2',
        'section' => '#FFFFFF'
    );
}

function cm_get_theme()
{
    $defaults = cm_get_theme_defaults();
    $settingsFile = defined('SITE_ROOT') ? SITE_ROOT . '/school-settings.json' : '';
    if ($settingsFile && is_file($settingsFile)) {
        $settings = json_decode((string) file_get_contents($settingsFile), true);
        if (is_array($settings) && isset($settings['theme']) && is_array($settings['theme'])) {
            foreach ($defaults as $name => $fallback) {
                if (isset($settings['theme'][$name])) {
                    $defaults[$name] = cm_normalize_color($settings['theme'][$name], $fallback);
                }
            }
            return $defaults;
        }
    }
    $css = cm_read_file(THEME_CSS_FILE);
    $section = '';
    $start = strpos($css, THEME_MANAGED_START);
    $end = strpos($css, THEME_MANAGED_END);
    if ($start !== false && $end !== false && $end > $start) {
        $section = substr($css, $start, $end - $start);
    }
    foreach ($defaults as $name => $fallback) {
        if (preg_match('/--bs-' . preg_quote($name, '/') . '\s*:\s*(#[0-9a-fA-F]{3,6})\s*;/', $section, $m)) {
            $defaults[$name] = cm_normalize_color($m[1], $fallback);
        }
    }
    return $defaults;
}

function cm_apply_theme($theme)
{
    $theme = array(
        'primary' => cm_normalize_color($theme['primary'] ?? '', '#FF4880'),
        'secondary' => cm_normalize_color($theme['secondary'] ?? '', '#1A1A1A'),
        'dark' => cm_normalize_color($theme['dark'] ?? '', '#1A1A1A'),
        'light' => cm_normalize_color($theme['light'] ?? '', '#FFECF2'),
        'block' => cm_normalize_color($theme['block'] ?? '', '#FFECF2'),
        'section' => cm_normalize_color($theme['section'] ?? '', '#FFFFFF')
    );

    $css = cm_read_file(THEME_CSS_FILE);
    $block = THEME_MANAGED_START . "\n" .
        ':root {\n' .
        '    --bs-primary: ' . $theme['primary'] . ';\n' .
        '    --bs-secondary: ' . $theme['secondary'] . ';\n' .
        '    --bs-dark: ' . $theme['dark'] . ';\n' .
        '    --bs-light: ' . $theme['light'] . ';\n' .
        '}\n' .
        '.btn-primary, .bg-primary { background-color: ' . $theme['primary'] . ' !important; border-color: ' . $theme['primary'] . ' !important; }\n' .
        '.btn-primary:hover, .btn-primary:focus, .btn-primary:active { background-color: ' . $theme['primary'] . ' !important; border-color: ' . $theme['primary'] . ' !important; }\n' .
        '.text-primary { color: ' . $theme['primary'] . ' !important; }\n' .
        '.border-primary { border-color: ' . $theme['primary'] . ' !important; }\n' .
        '.btn-secondary, .bg-secondary { background-color: ' . $theme['secondary'] . ' !important; border-color: ' . $theme['secondary'] . ' !important; }\n' .
        '.btn-secondary:hover, .btn-secondary:focus, .btn-secondary:active { background-color: ' . $theme['secondary'] . ' !important; border-color: ' . $theme['secondary'] . ' !important; }\n' .
        '.text-secondary { color: ' . $theme['secondary'] . ' !important; }\n' .
        '.border-secondary { border-color: ' . $theme['secondary'] . ' !important; }\n' .
        '.bg-dark { background-color: ' . $theme['dark'] . ' !important; }\n' .
        '.text-dark { color: ' . $theme['dark'] . ' !important; }\n' .
        '.border-dark { border-color: ' . $theme['dark'] . ' !important; }\n' .
        '.bg-light { background-color: ' . $theme['light'] . ' !important; }\n' .
        '.text-light { color: ' . $theme['light'] . ' !important; }\n' .
        '.border-light { border-color: ' . $theme['light'] . ' !important; }\n' .
        '.school-theme-block, .service .service-item, .program .program-item .program-text, .program .program-item .program-teacher, .events .events-text, .blog .blog-item, .blog .blog-item .blog-date-comments, .blog .blog-item .blog-content, .team .team-item .team-content, .testimonial .testimonial-item, .about .border.border-primary.p-3.rounded.bg-light, .contact .d-flex.w-100.border.border-primary.p-4.rounded.bg-white, .contact .border.border-primary.h-100.rounded { background-color: ' . $theme['block'] . ' !important; }\n' .
        '.program .program-item, .blog .blog-item, .team .team-item, .testimonial .testimonial-item { --school-block-color: ' . $theme['block'] . '; }\n' .
        '.about, .service, .program, .events, .team, .testimonial, .blog, .contact { background-color: ' . $theme['section'] . ' !important; }\n' .
        'a:hover, a:focus, .nav-link:hover, .nav-link.active { color: ' . $theme['primary'] . ' !important; }\n' .
        THEME_MANAGED_END;

    $start = strpos($css, THEME_MANAGED_START);
    $end = strpos($css, THEME_MANAGED_END);

    if ($start !== false && $end !== false && $end > $start) {
        $end += strlen(THEME_MANAGED_END);
        $css = substr($css, 0, $start) . $block . substr($css, $end);
    } else {
        $css = $block . "\n\n" . $css;
    }

    writeCssFile(THEME_CSS_FILE, $css);

    $settingsFile = defined('SITE_ROOT') ? SITE_ROOT . '/school-settings.json' : '';
    if ($settingsFile) {
        $settings = array();
        if (is_file($settingsFile)) {
            $decoded = json_decode((string) file_get_contents($settingsFile), true);
            if (is_array($decoded)) { $settings = $decoded; }
        }
        $settings['theme'] = $theme;
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        if (file_put_contents($settingsFile, $json, LOCK_EX) === false) {
            throw new RuntimeException('Unable to save website colour theme settings.');
        }
    }

    return $theme;
}

function cm_update_theme()
{
    return cm_apply_theme(array(
        'primary' => $_POST['theme_primary'] ?? '',
        'secondary' => $_POST['theme_secondary'] ?? '',
        'dark' => $_POST['theme_dark'] ?? '',
        'light' => $_POST['theme_light'] ?? '',
        'block' => $_POST['theme_block'] ?? '',
        'section' => $_POST['theme_section'] ?? ''
    ));
}

function cm_reset_theme()
{
    return cm_apply_theme(cm_get_theme_defaults());
}




/* =========================================================
   HERO SLIDER IMAGE MANAGEMENT
   ========================================================= */

function cm_get_content_limits()
{
    $defaults = array(
        'max_hero_images' => 5,
        'max_gallery_sections' => 10,
        'max_gallery_images_per_section' => 20
    );
    $settings = cm_get_site_settings();
    if (isset($settings['content_limits']) && is_array($settings['content_limits'])) {
        foreach ($defaults as $key => $fallback) {
            if (isset($settings['content_limits'][$key]) && is_numeric($settings['content_limits'][$key])) {
                $value = (int) $settings['content_limits'][$key];
                if ($value >= 1) {
                    $defaults[$key] = $value;
                }
            }
        }
    }
    return $defaults;
}

function cm_get_site_settings()
{
    $settings = array();
    if (defined('SETTINGS_FILE') && is_file(SETTINGS_FILE)) {
        $decoded = json_decode((string) file_get_contents(SETTINGS_FILE), true);
        if (is_array($decoded)) {
            $settings = $decoded;
        }
    }
    return $settings;
}

function cm_save_site_settings($settings)
{
    if (!defined('SETTINGS_FILE')) {
        throw new RuntimeException('Website settings file is not configured.');
    }
    $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if (file_put_contents(SETTINGS_FILE, $json, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save website settings.');
    }
}


/* =========================================================
   ABOUT US SECTION MANAGEMENT
   ========================================================= */

function cm_get_about_defaults()
{
    return array(
        'label' => 'About Us',
        'heading' => 'We Learn Smart Way To Build Bright Futute For Your Children',
        'paragraph1' => 'Founded on the principles of integrity and excellence, our school has become a cornerstone of quality education in our community. We offer a comprehensive educational experience that blends traditional values with modern pedagogical techniques.',
        'paragraph2' => 'Our school is more than just classrooms; it is a vibrant ecosystem where students are encouraged to push boundaries—academically, athletically, and artistically. With a dedicated faculty and state-of-the-art facilities, we prepare our students to meet the challenges of the 21st century with resilience and intelligence.',
        'features' => array(
            'Sport Activites',
            'Outdoor Games',
            'Nutritious Foods',
            'Highly Secured',
            'Friendly Environment',
            'Qualified Teacher'
        ),
        'video_url' => 'https://www.youtube.com/embed/DWRcNpR6Kdc',
        'button_text' => 'Contact us',
        'button_url' => ''
    );
}

function cm_get_about()
{
    $about = cm_get_about_defaults();
    $settings = cm_get_site_settings();

    if (isset($settings['about']) && is_array($settings['about'])) {
        foreach (array('label','heading','paragraph1','paragraph2','video_url','button_text','button_url') as $key) {
            if (isset($settings['about'][$key]) && is_string($settings['about'][$key])) {
                $about[$key] = $settings['about'][$key];
            }
        }
        if (isset($settings['about']['features']) && is_array($settings['about']['features'])) {
            $features = array_values($settings['about']['features']);
            for ($i = 0; $i < 6; $i++) {
                if (isset($features[$i]) && is_string($features[$i])) {
                    $about['features'][$i] = $features[$i];
                }
            }
        }
    }

    return $about;
}

function cm_clean_about_text($value, $max = 1000)
{
    $value = trim(strip_tags((string) $value));
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function cm_clean_about_url($value)
{
    $value = trim((string) $value);
    if ($value === '') return '';

    if (!preg_match('#^https?://#i', $value)) return '';

    /*
     * The About Us player uses a YouTube iframe. Convert the common
     * YouTube sharing formats to a proper /embed/ URL so links such as
     * https://youtu.be/_MppF9bud7M work correctly in the modal.
     */
    $parts = parse_url($value);
    $host = strtolower($parts['host'] ?? '');
    $path = trim($parts['path'] ?? '', '/');
    $videoId = '';

    if ($host === 'youtu.be' || $host === 'www.youtu.be') {
        $videoId = explode('/', $path)[0] ?? '';
    } elseif ($host === 'youtube.com' || $host === 'www.youtube.com' || $host === 'm.youtube.com') {
        if (preg_match('#^watch$#i', $path)) {
            parse_str($parts['query'] ?? '', $query);
            $videoId = $query['v'] ?? '';
        } elseif (preg_match('#^(?:embed|shorts)/([^/]+)#i', $path, $m)) {
            $videoId = $m[1];
        }
    }

    if ($videoId !== '' && preg_match('/^[A-Za-z0-9_-]{6,20}$/', $videoId)) {
        return 'https://www.youtube.com/embed/' . $videoId;
    }

    /* Allow an already-correct embed URL or another HTTPS URL. */
    return substr($value, 0, 1000);
}

function cm_render_about_section($about)
{
    $e = function($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
    $features = array_values($about['features'] ?? array());
    while (count($features) < 6) $features[] = '';

    $video = $e($about['video_url'] ?? '');

    /*
     * The About Us editor does not manage the Contact us button.
     * Its text and destination are controlled centrally by WhatsApp Settings.
     * Always generate the current WhatsApp URL here so changing the WhatsApp
     * number in Admin > WhatsApp Settings is reflected in the About section.
     */
    $buttonText = 'Contact us';
    try {
        $buttonUrl = $e(cm_get_whatsapp_url());
    } catch (Throwable $e) {
        $buttonUrl = '#contact';
    }

    $featureHtml = '';
    $iconClasses = array('', 'text-primary', 'text-secondary', '', 'text-primary', 'text-secondary');
    foreach ($features as $i => $feature) {
        if ($feature === '') continue;
        $class = $iconClasses[$i] ?? '';
        $featureHtml .= '<h6' . ($i < 5 ? ' class="mb-3"' : '') . '><i class="fas fa-check-circle me-2' . ($class ? ' ' . $class : '') . '"></i>' . $e($feature) . '</h6>' . "\n                                ";
    }
    $leftFeatures = array_slice($features, 0, 3);
    $rightFeatures = array_slice($features, 3, 3);
    $renderFeatures = function($items, $offset) use ($e, $iconClasses) {
        $html = '';
        foreach ($items as $j => $feature) {
            if ($feature === '') continue;
            $i = $offset + $j;
            $class = $iconClasses[$i] ?? '';
            $mb = ($j < count($items)-1) ? ' class="mb-3"' : '';
            $html .= '<h6' . $mb . '><i class="fas fa-check-circle me-2' . ($class ? ' ' . $class : '') . '"></i>' . $e($feature) . '</h6>' . "\n                                ";
        }
        return $html;
    };

    $html = '<!-- About Start -->\n'
        . '        <div class="container-fluid py-5 about bg-light">\n'
        . '            <div class="container py-5">\n'
        . '                <div class="row g-5 align-items-center">\n'
        . '                    <div class="col-lg-5 wow fadeIn" data-wow-delay="0.1s">\n'
        . '                        <div class="video border">\n'
        . '                            <button type="button" class="btn btn-play" data-bs-toggle="modal" data-src="' . $video . '" data-bs-target="#videoModal">\n'
        . '                                <span></span>\n'
        . '                            </button>\n'
        . '                        </div>\n'
        . '                    </div>\n'
        . '                    <div class="col-lg-7 wow fadeIn" data-wow-delay="0.3s">\n'
        . '                        <h4 class="text-primary mb-4 border-bottom border-primary border-2 d-inline-block p-2 title-border-radius">' . $e($about['label']) . '</h4>\n'
        . '                        <h1 class="text-dark mb-4 display-5">' . $e($about['heading']) . '</h1>\n'
        . '                        <p class="text-dark mb-4">' . $e($about['paragraph1']) . '</p>\n'
        . '                        <p class="text-dark mb-4">' . $e($about['paragraph2']) . '</p>\n'
        . '                        <div class="row mb-4">\n'
        . '                            <div class="col-lg-6">\n'
        . $renderFeatures($leftFeatures, 0)
        . '                            </div>\n'
        . '                            <div class="col-lg-6">\n'
        . $renderFeatures($rightFeatures, 3)
        . '                            </div>\n'
        . '                        </div>\n'
        . '                        <a href="' . $buttonUrl . '" class="btn btn-primary px-5 py-3 btn-border-radius" target="_blank" rel="noopener noreferrer">' . $e($buttonText) . '</a>\n'
        . '                    </div>\n'
        . '                </div>\n'
        . '            </div>\n'
        . '        </div>\n'
        . '        <!-- About End -->';

    return str_replace('\\n', "\n", $html);
}

function cm_save_about($post)
{
    $about = cm_get_about_defaults();
    $about['label'] = cm_clean_about_text($post['about_label'] ?? '', 100);
    $about['heading'] = cm_clean_about_text($post['about_heading'] ?? '', 300);
    $about['paragraph1'] = cm_clean_about_text($post['about_paragraph1'] ?? '', 1500);
    $about['paragraph2'] = cm_clean_about_text($post['about_paragraph2'] ?? '', 1500);
    $about['video_url'] = cm_clean_about_url($post['about_video_url'] ?? '');

    /* Contact button is managed by WhatsApp Settings, not About Us. */
    $currentAbout = cm_get_about();
    $about['button_text'] = $currentAbout['button_text'] ?? 'Contact us';
    $about['button_url'] = $currentAbout['button_url'] ?? '';

    if ($about['label'] === '') $about['label'] = 'About Us';
    if ($about['heading'] === '') throw new RuntimeException('About Us heading is required.');
    if ($about['paragraph1'] === '') throw new RuntimeException('About Us first paragraph is required.');
    if ($about['paragraph2'] === '') throw new RuntimeException('About Us second paragraph is required.');
    if ($about['button_text'] === '') $about['button_text'] = 'Contact us';

    $features = array();
    for ($i = 1; $i <= 6; $i++) {
        $features[] = cm_clean_about_text($post['about_feature_' . $i] ?? '', 100);
    }
    $about['features'] = $features;

    $settings = cm_get_site_settings();
    $settings['about'] = $about;
    cm_save_site_settings($settings);

    foreach (array(__DIR__ . '/index.html', __DIR__ . '/about.html') as $file) {
        if (!is_file($file)) continue;
        $html = file_get_contents($file);
        if ($html === false) continue;
        $newSection = cm_render_about_section($about);
        $updated = preg_replace(
            '/<!-- About Start -->.*?<!-- Modal Video -->/is',
            $newSection . "\n        <!-- Modal Video -->",
            $html,
            1,
            $count
        );
        if ($count !== 1 || $updated === null) {
            throw new RuntimeException('Unable to update the About Us section in ' . basename($file) . '.');
        }
        if (file_put_contents($file, $updated, LOCK_EX) === false) {
            throw new RuntimeException('Unable to save ' . basename($file) . '.');
        }
    }

    return $about;
}

/* =========================================================
   GALLERY MANAGEMENT
   ========================================================= */

function cm_get_gallery()
{
    $file = __DIR__ . '/gallery-data.json';
    if (!file_exists($file)) {
        return array();
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    if (!is_array($decoded)) {
        return array();
    }

    $sections = array();
    foreach ($decoded as $section) {
        if (!is_array($section)) {
            continue;
        }
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($section['id'] ?? ''));
        $name = trim((string) ($section['name'] ?? ''));
        if ($id === '' || $name === '') {
            continue;
        }
        $images = array();
        foreach (($section['images'] ?? array()) as $image) {
            $image = cm_normalize_image_path($image);
            if ($image !== '' && strpos($image, 'img/gallery_') === 0) {
                $images[] = $image;
            }
        }
        $sections[] = array('id' => $id, 'name' => $name, 'images' => $images);
    }
    return $sections;
}

function cm_save_gallery($sections)
{
    $file = __DIR__ . '/gallery-data.json';
    $json = json_encode(array_values($sections), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if (file_put_contents($file, $json, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save gallery data.');
    }
}

function cm_gallery_section_id($name)
{
    $base = strtolower(trim((string) $name));
    $base = preg_replace('/[^a-z0-9]+/', '-', $base);
    $base = trim($base, '-');
    if ($base === '') {
        $base = 'section';
    }
    return $base . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
}

function cm_add_gallery_section()
{
    cm_validate_request();
    $name = trim((string) ($_POST['gallery_section_name'] ?? ''));
    if ($name === '') {
        throw new RuntimeException('Gallery section name is required.');
    }
    if (strlen($name) > 120) {
        throw new RuntimeException('Gallery section name must not exceed 120 characters.');
    }

    $sections = cm_get_gallery();
    $limits = cm_get_content_limits();
    if (count($sections) >= $limits['max_gallery_sections']) {
        throw new RuntimeException('Maximum gallery sections limit reached (' . $limits['max_gallery_sections'] . '). Please ask Super Admin to increase the limit.');
    }
    foreach ($sections as $section) {
        if (strcasecmp($section['name'], $name) === 0) {
            throw new RuntimeException('A gallery section with this name already exists.');
        }
    }
    $sections[] = array('id' => cm_gallery_section_id($name), 'name' => $name, 'images' => array());
    cm_save_gallery($sections);
}

function cm_update_gallery_section()
{
    cm_validate_request();
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_POST['gallery_section_id'] ?? ''));
    $name = trim((string) ($_POST['gallery_section_name'] ?? ''));
    if ($id === '' || $name === '') {
        throw new RuntimeException('Gallery section ID and name are required.');
    }
    if (strlen($name) > 120) {
        throw new RuntimeException('Gallery section name must not exceed 120 characters.');
    }

    $sections = cm_get_gallery();
    $found = false;
    foreach ($sections as &$section) {
        if ($section['id'] === $id) {
            $section['name'] = $name;
            $found = true;
            break;
        }
    }
    unset($section);
    if (!$found) {
        throw new RuntimeException('Gallery section not found.');
    }
    cm_save_gallery($sections);
}

function cm_delete_gallery_section()
{
    cm_validate_request();
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_POST['gallery_section_id'] ?? ''));
    $sections = cm_get_gallery();
    $kept = array();
    $found = false;
    foreach ($sections as $section) {
        if ($section['id'] !== $id) {
            $kept[] = $section;
            continue;
        }
        $found = true;
        foreach ($section['images'] as $image) {
            cm_delete_image_if_managed($image, 'gallery');
        }
    }
    if (!$found) {
        throw new RuntimeException('Gallery section not found.');
    }
    cm_save_gallery($kept);
}

function cm_add_gallery_images()
{
    cm_validate_request();
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_POST['gallery_section_id'] ?? ''));
    if ($id === '') {
        throw new RuntimeException('Gallery section is required.');
    }
    if (!isset($_FILES['gallery_images']) || !is_array($_FILES['gallery_images'])) {
        throw new RuntimeException('Please select at least one image.');
    }

    $sections = cm_get_gallery();
    $index = null;
    foreach ($sections as $i => $section) {
        if ($section['id'] === $id) {
            $index = $i;
            break;
        }
    }
    if ($index === null) {
        throw new RuntimeException('Gallery section not found.');
    }

    $files = $_FILES['gallery_images'];
    $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
    if ($count < 1) {
        throw new RuntimeException('Please select at least one image.');
    }

    $limits = cm_get_content_limits();
    $existingCount = count($sections[$index]['images'] ?? array());
    $remaining = $limits['max_gallery_images_per_section'] - $existingCount;
    if ($remaining <= 0) {
        throw new RuntimeException('This gallery section has reached the maximum of ' . $limits['max_gallery_images_per_section'] . ' images.');
    }
    if ($count > $remaining) {
        throw new RuntimeException('You can add only ' . $remaining . ' more image(s) to this section. Maximum is ' . $limits['max_gallery_images_per_section'] . '.');
    }

    $uploaded = array();
    try {
        for ($i = 0; $i < $count; $i++) {
            $file = array(
                'name' => $files['name'][$i] ?? '',
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0
            );
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('One of the selected gallery images failed to upload.');
            }
            $path = uploadImage($file, 'gallery_');
            $path = cm_normalize_image_path($path);
            if ($path === '' || strpos($path, 'img/gallery_') !== 0) {
                throw new RuntimeException('Unable to verify uploaded gallery image.');
            }
            $uploaded[] = $path;
        }
    } catch (Throwable $e) {
        foreach ($uploaded as $path) {
            cm_delete_image_if_managed($path, 'gallery');
        }
        throw $e;
    }

    foreach ($uploaded as $path) {
        $sections[$index]['images'][] = $path;
    }
    cm_save_gallery($sections);
    return count($uploaded);
}

function cm_delete_gallery_image()
{
    cm_validate_request();
    $sectionId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_POST['gallery_section_id'] ?? ''));
    $image = cm_normalize_image_path($_POST['gallery_image'] ?? '');
    if ($sectionId === '' || $image === '' || strpos($image, 'img/gallery_') !== 0) {
        throw new RuntimeException('Invalid gallery image.');
    }

    $sections = cm_get_gallery();
    $found = false;
    foreach ($sections as &$section) {
        if ($section['id'] !== $sectionId) {
            continue;
        }
        $newImages = array();
        foreach ($section['images'] as $savedImage) {
            if ($savedImage === $image && !$found) {
                $found = true;
                continue;
            }
            $newImages[] = $savedImage;
        }
        $section['images'] = $newImages;
        break;
    }
    unset($section);
    if (!$found) {
        throw new RuntimeException('Gallery image not found.');
    }
    cm_delete_image_if_managed($image, 'gallery');
    cm_save_gallery($sections);
}


/* =========================================================
   ANNOUNCEMENT MANAGEMENT
   ========================================================= */

function cm_get_announcements()
{
    $file = __DIR__ . '/announcements-data.json';
    if (!is_file($file)) {
        return array();
    }
    $decoded = json_decode((string) file_get_contents($file), true);
    if (!is_array($decoded)) {
        return array();
    }

    $items = array();
    foreach ($decoded as $item) {
        if (!is_array($item)) continue;
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($item['id'] ?? ''));
        $date = trim((string)($item['date'] ?? ''));
        $description = trim((string)($item['description'] ?? ''));
        $filePath = cm_normalize_announcement_file_path($item['file'] ?? '');
        if ($id === '' || $date === '' || $description === '' || $filePath === '') continue;
        $items[] = array(
            'id' => $id,
            'date' => $date,
            'description' => $description,
            'file' => $filePath,
            'file_name' => trim((string)($item['file_name'] ?? basename($filePath)))
        );
    }

    usort($items, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    return $items;
}

function cm_save_announcements($items)
{
    $file = __DIR__ . '/announcements-data.json';
    $json = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if (file_put_contents($file, $json, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save announcements.');
    }
}

function cm_announcement_id()
{
    return 'announcement-' . bin2hex(random_bytes(8));
}

function cm_normalize_announcement_file_path($path)
{
    $path = str_replace('\\', '/', trim((string)$path));
    if ($path === '') return '';
    if (strpos($path, './') === 0) $path = substr($path, 2);
    if (preg_match('#^(img/announcement_[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)|uploads/announcements/announcement_[A-Za-z0-9_-]+\.pdf)$#i', $path)) {
        return $path;
    }
    return '';
}

function cm_upload_announcement_file($file)
{
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Announcement file upload failed.');
    }
    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > 10 * 1024 * 1024) {
        throw new RuntimeException('Announcement file size must be greater than 0 and must not exceed 10 MB.');
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid announcement upload.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $original = safeFilename($file['name'] ?? 'announcement');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $token = bin2hex(random_bytes(8));

    $isImage = in_array($mime, array('image/jpeg','image/png','image/webp'), true);
    $isPdf = ($mime === 'application/pdf' && $ext === 'pdf');
    if (!$isImage && !$isPdf) {
        throw new RuntimeException('Only JPG, JPEG, PNG, WEBP images and PDF files are allowed.');
    }

    if ($isImage) {
        if (@getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('The uploaded announcement image is not valid.');
        }
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $dir = SITE_ROOT . '/img';
        $filename = 'announcement_' . $token . '.' . $ext;
        $relative = 'img/' . $filename;
    } else {
        $dir = SITE_ROOT . '/uploads/announcements';
        $filename = 'announcement_' . $token . '.pdf';
        $relative = 'uploads/announcements/' . $filename;
    }

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Unable to create announcement upload directory.');
    }
    $destination = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Unable to save announcement file.');
    }
    return array('path' => $relative, 'name' => basename($original));
}

function cm_delete_announcement_file($path)
{
    $path = cm_normalize_announcement_file_path($path);
    if ($path === '') return;
    $full = SITE_ROOT . '/' . $path;
    if (is_file($full)) @unlink($full);
}

function cm_add_announcement()
{
    // Announcement date is generated automatically from India Standard Time
    // when the admin creates the announcement. It is not entered manually.
    $date = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
    $description = trim((string)($_POST['announcement_description'] ?? ''));
    if ($description === '') throw new RuntimeException('Announcement description is required.');
    if (strlen($description) > 1000) throw new RuntimeException('Announcement description must not exceed 1000 characters.');
    $uploaded = cm_upload_announcement_file($_FILES['announcement_file'] ?? array());

    $items = cm_get_announcements();
    $items[] = array('id' => cm_announcement_id(), 'date' => $date, 'description' => $description, 'file' => $uploaded['path'], 'file_name' => $uploaded['name']);
    try { cm_save_announcements($items); } catch (Throwable $e) { cm_delete_announcement_file($uploaded['path']); throw $e; }
}

function cm_update_announcement()
{
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['announcement_id'] ?? ''));
    $description = trim((string)($_POST['announcement_description'] ?? ''));
    if ($id === '') throw new RuntimeException('Announcement ID is required.');
    if ($description === '') throw new RuntimeException('Announcement description is required.');
    if (strlen($description) > 1000) throw new RuntimeException('Announcement description must not exceed 1000 characters.');

    $items = cm_get_announcements();
    $found = false;
    foreach ($items as &$item) {
        if ($item['id'] !== $id) continue;
        $oldFile = $item['file'];
        // Keep the original automatically generated announcement date unchanged.
        $item['description'] = $description;
        $newUploaded = null;
        if (isset($_FILES['announcement_file']) && is_array($_FILES['announcement_file']) && ($_FILES['announcement_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $newUploaded = cm_upload_announcement_file($_FILES['announcement_file']);
            $item['file'] = $newUploaded['path'];
            $item['file_name'] = $newUploaded['name'];
        }
        $found = true;
        break;
    }
    unset($item);
    if (!$found) throw new RuntimeException('Announcement not found.');
    try {
        cm_save_announcements($items);
    } catch (Throwable $e) {
        if (!empty($newUploaded['path'])) cm_delete_announcement_file($newUploaded['path']);
        throw $e;
    }
    if (!empty($newUploaded['path']) && !empty($oldFile)) cm_delete_announcement_file($oldFile);
}

function cm_delete_announcement()
{
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['announcement_id'] ?? ''));
    $items = cm_get_announcements();
    $kept = array(); $found = false;
    foreach ($items as $item) {
        if ($item['id'] === $id) { cm_delete_announcement_file($item['file']); $found = true; continue; }
        $kept[] = $item;
    }
    if (!$found) throw new RuntimeException('Announcement not found.');
    cm_save_announcements($kept);
}

/* =========================================================
   WHATSAPP CONTACT BUTTONS
   ========================================================= */

function cm_normalize_whatsapp_phone($value)
{
    $digits = preg_replace('/[^0-9]/', '', (string) $value);

    /* Accept either the normal 10-digit Indian number or 91 + 10 digits. */
    if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
        $digits = substr($digits, 2);
    }

    if (!preg_match('/^[1-9][0-9]{9}$/', $digits)) {
        throw new RuntimeException(
            'WhatsApp number must be exactly 10 digits and must not start with 0.'
        );
    }

    return $digits;
}

function cm_get_social_media()
{
    $defaults = array(
        'facebook' => '',
        'twitter' => '',
        'instagram' => '',
        'linkedin' => ''
    );

    $settings = cm_get_site_settings();
    $saved = isset($settings['social_media']) && is_array($settings['social_media'])
        ? $settings['social_media']
        : array();

    foreach ($defaults as $key => $value) {
        $candidate = isset($saved[$key]) ? trim((string) $saved[$key]) : '';
        if ($candidate !== '' && !preg_match('/^https?:\\/\\//i', $candidate)) {
            $candidate = '';
        }
        $defaults[$key] = $candidate;
    }

    return $defaults;
}

function cm_normalize_social_url($value, $label)
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (!preg_match('/^https?:\\/\\//i', $value)) {
        throw new RuntimeException($label . ' must be a complete URL beginning with http:// or https://');
    }

    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Please enter a valid ' . $label . ' URL.');
    }

    return $value;
}

function cm_update_social_media()
{
    cm_validate_request();

    $social = array(
        'facebook' => cm_normalize_social_url($_POST['social_facebook'] ?? '', 'Facebook'),
        'twitter' => cm_normalize_social_url($_POST['social_twitter'] ?? '', 'Twitter / X'),
        'instagram' => cm_normalize_social_url($_POST['social_instagram'] ?? '', 'Instagram'),
        'linkedin' => cm_normalize_social_url($_POST['social_linkedin'] ?? '', 'LinkedIn')
    );

    $settings = cm_get_site_settings();
    $settings['social_media'] = $social;
    cm_save_site_settings($settings);

    /*
     * The footer was already being updated from the saved social settings,
     * but the top/header social icons are part of the static HTML pages.
     * Regenerate both header and footer links whenever the admin saves the
     * social-media settings so every page stays in sync.
     */
    cm_apply_social_media_to_pages($social);

    return true;
}

/**
 * Apply the saved social URLs to the static website header and footer.
 * Blank URLs are kept as # so the icon remains harmless/inactive.
 */
function cm_apply_social_media_to_pages($social)
{
    $baseDir = __DIR__;
    $files = glob($baseDir . '/*.html');
    if (!is_array($files)) {
        return;
    }

    $urls = array(
        'facebook' => trim((string) ($social['facebook'] ?? '')),
        'twitter' => trim((string) ($social['twitter'] ?? '')),
        'instagram' => trim((string) ($social['instagram'] ?? '')),
        'linkedin' => trim((string) ($social['linkedin'] ?? ''))
    );

    foreach ($files as $file) {
        $html = @file_get_contents($file);
        if ($html === false || $html === '') {
            continue;
        }

        $original = $html;

        /* Header/topbar social links. */
        $html = cm_replace_social_block(
            $html,
            '/(<div\s+class=[\"\']top-link\b[^>]*>)(.*?)(<\/div>)/is',
            $urls
        );

        /* Footer social links. */
        $html = cm_replace_social_block(
            $html,
            '/(<div\s+class=[\"\']footer-icon\s+d-flex[\"\'][^>]*>)(.*?)(<\/div>)/is',
            $urls
        );

        if ($html !== $original) {
            @file_put_contents($file, $html, LOCK_EX);
        }
    }
}

function cm_replace_social_block($html, $pattern, $urls)
{
    return preg_replace_callback($pattern, function ($match) use ($urls) {
        $block = $match[0];

        $mapping = array(
            'facebook' => 'fa-facebook-f',
            'twitter' => 'fa-twitter',
            'instagram' => 'fa-instagram',
            'linkedin' => 'fa-linkedin-in'
        );

        foreach ($mapping as $key => $iconClass) {
            $href = $urls[$key] !== '' ? $urls[$key] : '#';
            $safeHref = cm_escape($href);

            $block = preg_replace_callback(
                '/(<a\b[^>]*?class=[\"\'][^\"\']*?)([\"\'])[^>]*>(?:(?!<a\b).)*?<i[^>]*class=[\"\'][^\"\']*\b' . preg_quote($iconClass, '/') . '\b[^\"\']*[\"\'][^>]*><\/i>.*?<\/a>/is',
                function ($a) use ($safeHref, $urls, $key) {
                    $tag = $a[0];
                    $tag = preg_replace('/\bhref\s*=\s*[\"\'][^\"\']*[\"\']/i', 'href="' . $safeHref . '"', $tag, 1);
                    if ($urls[$key] !== '') {
                        if (!preg_match('/\btarget\s*=/i', $tag)) {
                            $tag = preg_replace('/<a\b/i', '<a target="_blank" rel="noopener noreferrer"', $tag, 1);
                        }
                    } else {
                        $tag = preg_replace('/\s+target\s*=\s*[\"\'][^\"\']*[\"\']/i', '', $tag);
                        $tag = preg_replace('/\s+rel\s*=\s*[\"\'][^\"\']*[\"\']/i', '', $tag);
                    }
                    return $tag;
                },
                $block,
                1
            );
        }

        return $block;
    }, $html);
}

function cm_get_whatsapp_phone()
{
    $default = '9211271039';
    $settings = cm_get_site_settings();
    $value = $settings['whatsapp_phone'] ?? $default;

    try {
        return cm_normalize_whatsapp_phone($value);
    } catch (Throwable $e) {
        return $default;
    }
}

function cm_get_whatsapp_url($phone = '')
{
    $phone = cm_normalize_whatsapp_phone(
        $phone !== '' ? $phone : cm_get_whatsapp_phone()
    );

    $message = 'Hello, I have an inquiry related to service your school provides.';

    $query = http_build_query(
        array(
            'phone' => '91' . $phone,
            'text' => $message,
            'type' => 'phone_number',
            'app_absent' => '0'
        ),
        '',
        '&',
        PHP_QUERY_RFC1738
    );

    return 'https://api.whatsapp.com/send/?' . $query;
}

function cm_update_whatsapp_buttons($phone = '')
{
    $phone = cm_normalize_whatsapp_phone(
        $phone !== '' ? $phone : cm_get_whatsapp_phone()
    );

    $url = cm_escape(cm_get_whatsapp_url($phone));
    $files = cm_get_contact_sync_files();
    $changes = array();

    foreach ($files as $file) {
        $html = cm_read_file($file);
        $updated = preg_replace_callback(
            '/<a\b([^>]*)>(\s*)(Read\s+More|More\s+Details|Contact\s+us)(\s*)<\/a>/is',
            function ($matches) use ($url) {
                $attrs = $matches[1];

                if (preg_match('/\bhref\s*=\s*(["\'])[^"\']*\1/i', $attrs)) {
                    $attrs = preg_replace(
                        '/\bhref\s*=\s*(["\'])[^"\']*\1/i',
                        'href="' . $url . '"',
                        $attrs,
                        1
                    );
                } else {
                    $attrs .= ' href="' . $url . '"';
                }

                if (!preg_match('/\btarget\s*=\s*["\'][^"\']*["\']/i', $attrs)) {
                    $attrs .= ' target="_blank"';
                }

                if (!preg_match('/\brel\s*=\s*["\'][^"\']*["\']/i', $attrs)) {
                    $attrs .= ' rel="noopener noreferrer"';
                }

                return '<a' . $attrs . '>'
                    . $matches[2]
                    . 'Contact us'
                    . $matches[4]
                    . '</a>';
            },
            $html
        );

        if ($updated === null) {
            throw new RuntimeException(
                'Unable to update WhatsApp buttons on ' . basename($file) . '.'
            );
        }

        if ($updated !== $html) {
            $changes[$file] = $updated;
        }
    }

    if ($changes) {
        cm_save_multiple_files($changes);
    }

    return count($changes);
}

/**
 * Replace the homepage hero's two CTA buttons with one Contact us button.
 * The button uses the same WhatsApp destination as the other Contact us buttons.
 */
function cm_update_hero_contact_button($phone = '')
{
    $phone = cm_normalize_whatsapp_phone(
        $phone !== '' ? $phone : cm_get_whatsapp_phone()
    );

    $url = cm_escape(cm_get_whatsapp_url($phone));
    $files = glob(__DIR__ . '/*.html');
    if (!is_array($files)) {
        return 0;
    }

    $updatedCount = 0;

    foreach ($files as $file) {
        $html = @file_get_contents($file);
        if ($html === false || $html === '') {
            continue;
        }

        $updated = preg_replace_callback(
            '/(<div\s+class=["\']hero-buttons-below["\'][^>]*>).*?(<\/div>)/is',
            function ($matches) use ($url) {
                return $matches[1]
                    . "\n                            <a href=\"" . $url . "\" class=\"btn btn-primary px-4 py-3 px-md-5 btn-border-radius\" target=\"_blank\" rel=\"noopener noreferrer\">Contact us</a>\n                        "
                    . $matches[2];
            },
            $html,
            1,
            $count
        );

        if ($updated === null) {
            throw new RuntimeException(
                'Unable to update the hero Contact us button on ' . basename($file) . '.'
            );
        }

        if ($count === 1 && $updated !== $html) {
            cm_save_multiple_files(array($file => $updated));
            $updatedCount++;
        }
    }

    return $updatedCount;
}

function cm_update_whatsapp()
{
    cm_validate_request();

    $phone = cm_normalize_whatsapp_phone(
        $_POST['whatsapp_phone'] ?? ''
    );

    $settings = cm_get_site_settings();
    $settings['whatsapp_phone'] = $phone;

    /* Save the setting first; if button sync fails the number can be retried. */
    cm_save_site_settings($settings);
    $updatedPages = cm_update_whatsapp_buttons($phone);
    $updatedHeroPages = cm_update_hero_contact_button($phone);

    setFlash(
        'success',
        'WhatsApp number updated successfully. ' . ($updatedPages + $updatedHeroPages) . ' website page(s) updated.'
    );

    return true;
}


function cm_get_hero_images()
{
    $settings = cm_get_site_settings();
    $images = array();

    if (isset($settings['hero_images']) && is_array($settings['hero_images'])) {
        foreach ($settings['hero_images'] as $image) {
            $image = trim((string) $image);
            if ($image !== '') {
                $images[] = cm_normalize_image_path($image);
            }
        }
    }

    /* Backward compatibility with the previous single-image system. */
    if (!$images && !empty($settings['hero_image'])) {
        $images[] = cm_normalize_image_path($settings['hero_image']);
    }

    if (!$images) {
        $images[] = 'img/hero-img.jpg';
    }

    return array_values(array_unique($images));
}

function cm_get_hero_image()
{
    $images = cm_get_hero_images();
    return $images[0];
}

function cm_add_hero_image()
{
    if (!isset($_FILES['hero_image']) || !is_array($_FILES['hero_image'])) {
        throw new RuntimeException('Hero image is required.');
    }

    $file = $_FILES['hero_image'];
    $limits = cm_get_content_limits();
    $currentImages = cm_get_hero_images();
    $managedCount = (count($currentImages) === 1 && $currentImages[0] === 'img/hero-img.jpg') ? 0 : count($currentImages);
    if ($managedCount >= $limits['max_hero_images']) {
        throw new RuntimeException('Maximum hero images limit reached (' . $limits['max_hero_images'] . '). Please ask Super Admin to increase the limit.');
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Hero image upload failed.');
    }

    $newImage = uploadImage($file, 'hero_');
    if (!$newImage) {
        throw new RuntimeException('Unable to upload hero image.');
    }
    $newImage = cm_normalize_image_path($newImage);

    $settings = cm_get_site_settings();
    $images = cm_get_hero_images();

    /* If only the built-in default exists, don't persist it as a managed image. */
    if (count($images) === 1 && $images[0] === 'img/hero-img.jpg') {
        $images = array();
    }

    $images[] = $newImage;
    $settings['hero_images'] = array_values(array_unique($images));
    /* Keep legacy key pointing to the first slide for older code. */
    $settings['hero_image'] = $settings['hero_images'][0];

    try {
        cm_save_site_settings($settings);
    } catch (Throwable $e) {
        cm_delete_image_if_managed($newImage, 'hero');
        throw $e;
    }

    return $newImage;
}

function cm_delete_hero_image($imagePath)
{
    $imagePath = cm_normalize_image_path($imagePath);
    if ($imagePath === 'img/hero-img.jpg') {
        throw new RuntimeException('The default hero image cannot be deleted.');
    }

    $settings = cm_get_site_settings();
    $images = cm_get_hero_images();
    $found = false;
    $remaining = array();

    foreach ($images as $image) {
        if ($image === $imagePath) {
            $found = true;
            continue;
        }
        $remaining[] = $image;
    }

    if (!$found) {
        throw new RuntimeException('Hero image not found.');
    }

    if (!$remaining) {
        $remaining = array('img/hero-img.jpg');
    }

    $settings['hero_images'] = array_values(array_unique($remaining));
    $settings['hero_image'] = $settings['hero_images'][0];
    cm_save_site_settings($settings);
    cm_delete_image_if_managed($imagePath, 'hero');
}

/* =========================================================
   BASIC HELPERS
   ========================================================= */

function cm_escape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function cm_read_file($file)
{
    if (!file_exists($file)) {
        throw new RuntimeException(
            'File not found: ' . basename($file)
        );
    }

    $content = file_get_contents($file);

    if ($content === false) {
        throw new RuntimeException(
            'Unable to read file: ' . basename($file)
        );
    }

    return $content;
}


function cm_save_file($file, $html)
{
    /*
     * Save the modified HTML file safely.
     *
     * writeHtmlFile() is defined in admin-config.php
     * and handles backup + atomic writing.
     */

    if (!is_string($html)) {
        throw new RuntimeException(
            'Invalid HTML content.'
        );
    }

    writeHtmlFile(
        $file,
        $html
    );
}

function cm_validate_request()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (function_exists('verifyCsrfToken')) {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new RuntimeException(
                'Invalid security token. Please refresh the page and try again.'
            );
        }
    }
}


function cm_required($value, $fieldName)
{
    $value = trim((string) $value);
    $value = str_replace("\0", '', $value);
    $value = preg_replace('/[ \t]+/', ' ', $value);

    if ($value === '') {
        throw new RuntimeException(
            $fieldName . ' is required.'
        );
    }

    return $value;
}


/* =========================================================
   HTML SECTION HELPERS
   ========================================================= */

function cm_get_section(
    $html,
    $startMarker,
    $endMarker
) {
    $start = strpos($html, $startMarker);

    if ($start === false) {
        throw new RuntimeException(
            'Managed section start marker not found.'
        );
    }

    $end = strpos(
        $html,
        $endMarker,
        $start + strlen($startMarker)
    );

    if ($end === false) {
        throw new RuntimeException(
            'Managed section end marker not found.'
        );
    }

    $contentStart = $start + strlen($startMarker);

    return substr(
        $html,
        $contentStart,
        $end - $contentStart
    );
}


function cm_replace_section(
    $html,
    $startMarker,
    $endMarker,
    $newSection
) {
    $start = strpos($html, $startMarker);

    if ($start === false) {
        throw new RuntimeException(
            'Managed section start marker not found.'
        );
    }

    $end = strpos(
        $html,
        $endMarker,
        $start + strlen($startMarker)
    );

    if ($end === false) {
        throw new RuntimeException(
            'Managed section end marker not found.'
        );
    }

    $before = substr(
        $html,
        0,
        $start + strlen($startMarker)
    );

    $after = substr(
        $html,
        $end
    );

    return $before . "\n\n" . $newSection . "\n\n" . $after;
}


function cm_validate_managed_sections(
    $files,
    $startMarker,
    $endMarker
) {
    foreach ($files as $file) {

        $html = cm_read_file($file);

        $start = strpos($html, $startMarker);

        if ($start === false) {
            throw new RuntimeException(
                'Managed section start marker not found in ' .
                basename($file) . '.'
            );
        }

        $end = strpos(
            $html,
            $endMarker,
            $start + strlen($startMarker)
        );

        if ($end === false) {
            throw new RuntimeException(
                'Managed section end marker not found in ' .
                basename($file) . '.'
            );
        }
    }
}


/* =========================================================
   SAFE HTML WRITE / ROLLBACK
   ========================================================= */

function cm_write_file($file, $content)
{
    if (function_exists('writeHtmlFile')) {
        if (!writeHtmlFile($file, $content)) {
            throw new RuntimeException(
                'Unable to save ' . basename($file) . '.'
            );
        }

        return true;
    }

    $result = file_put_contents(
        $file,
        $content,
        LOCK_EX
    );

    if ($result === false) {
        throw new RuntimeException(
            'Unable to save ' . basename($file) . '.'
        );
    }

    return true;
}


function cm_restore_file($file, $originalContent)
{
    @file_put_contents(
        $file,
        $originalContent,
        LOCK_EX
    );
}


/**
 * Save multiple HTML files as one logical operation.
 *
 * If the second file fails, the first file is restored.
 */
function cm_save_multiple_files($changes)
{
    $originals = array();

    foreach ($changes as $file => $newContent) {
        $originals[$file] = cm_read_file($file);
    }

    $saved = array();

    try {

        foreach ($changes as $file => $newContent) {

            cm_write_file(
                $file,
                $newContent
            );

            $saved[] = $file;
        }

        return true;

    } catch (Throwable $e) {

        foreach ($saved as $file) {

            if (isset($originals[$file])) {
                cm_restore_file(
                    $file,
                    $originals[$file]
                );
            }
        }

        throw $e;
    }
}


/* =========================================================
   IMAGE PATH HELPERS
   ========================================================= */

function cm_normalize_image_path($path)
{
    $path = trim((string) $path);

    $path = str_replace(
        '\\',
        '/',
        $path
    );

    if ($path === '') {
        return '';
    }

    /*
     * Remove leading ./ if present.
     */
    if (strpos($path, './') === 0) {
        $path = substr($path, 2);
    }

    /*
     * Uploaded CMS images are stored inside /img.
     *
     * If uploadImage() returns only:
     *
     *     teacher8e7d0e973f80839e.jpg
     *
     * convert it to:
     *
     *     img/teacher8e7d0e973f80839e.jpg
     */
    if (
        strpos($path, 'img/') !== 0 &&
        strpos($path, '/') === false
    ) {
        $path = 'img/' . $path;
    }

    return $path;
}


function cm_is_managed_image($path, $type = '')
{
    $path = basename(
        cm_normalize_image_path($path)
    );

    if ($type === 'teacher') {
        return strpos($path, 'teacher') === 0;
    }

    if ($type === 'event') {
        return strpos($path, 'event') === 0;
    }

    if ($type === 'birthday') {
        return strpos($path, 'birthday') === 0;
    }

    if ($type === 'program_teacher') {
        return strpos($path, 'program_teacher_') === 0;
    }

    if ($type === 'program') {
        return strpos($path, 'program_') === 0;
    }

    if ($type === 'review') {
        return strpos($path, 'review_') === 0;
    }

    return (
        strpos($path, 'teacher') === 0 ||
        strpos($path, 'event') === 0 ||
        strpos($path, 'birthday') === 0 ||
        strpos($path, 'program_') === 0 ||
        strpos($path, 'review_') === 0 ||
        strpos($path, 'gallery_') === 0
    );
}


function cm_delete_image_if_managed(
    $imagePath,
    $type = ''
) {
    $imagePath = cm_normalize_image_path(
        $imagePath
    );

    if (!cm_is_managed_image(
        $imagePath,
        $type
    )) {
        return;
    }

    /*
     * IMAGE_DIR comes from admin-config.php.
     */
    $filename = basename($imagePath);

    $fullPath = rtrim(
        IMAGE_DIR,
        DIRECTORY_SEPARATOR
    ) . DIRECTORY_SEPARATOR . $filename;

    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }
}


/* =========================================================
   TEACHER READER
   ========================================================= */

function cm_get_teachers_from_file($file)
{
    $html = cm_read_file($file);

    $section = cm_get_section(
        $html,
        TEAM_MANAGED_START,
        TEAM_MANAGED_END
    );

    $teachers = array();

    /*
     * Each teacher card contains:
     *
     * data-admin-id="teacher-..."
     */
    $pattern =
        '/<div\s+class="col-md-6\s+col-lg-4\s+col-xl-3[^>]*>'
        . '\s*<div\s+class="team-item[^>]*'
        . 'data-admin-id="(teacher-[^"]+)"'
        . '[^>]*>'
        . '(.*?)'
        . '<\/div>\s*<\/div>/is';

    /*
     * The actual card has nested divs, therefore the
     * above broad pattern may not reliably capture the
     * complete card. We instead locate every ID and use
     * the next ID/end marker as the boundary.
     */

    preg_match_all(
        '/data-admin-id="(teacher-[^"]+)"/i',
        $section,
        $matches,
        PREG_OFFSET_CAPTURE
    );

    if (empty($matches[1])) {
        return array();
    }

    $count = count($matches[1]);

    for ($i = 0; $i < $count; $i++) {

        $id = $matches[1][$i][0];

        /*
         * Find the beginning of the outer column.
         */
        $idOffset = $matches[1][$i][1];

        $columnStart = strrpos(
            substr($section, 0, $idOffset),
            '<div class="col-md-6'
        );

        if ($columnStart === false) {
            $columnStart = 0;
        }

        /*
         * Find next teacher column.
         */
        if ($i + 1 < $count) {

            $nextIdOffset =
                $matches[1][$i + 1][1];

            $nextColumnStart = strrpos(
                substr(
                    $section,
                    0,
                    $nextIdOffset
                ),
                '<div class="col-md-6'
            );

            if ($nextColumnStart === false) {
                $nextColumnStart = strlen($section);
            }

        } else {

            $nextColumnStart = strlen($section);
        }

        $card = substr(
            $section,
            $columnStart,
            $nextColumnStart - $columnStart
        );

        /*
         * Image
         */
        $image = '';

        if (preg_match(
            '/<img[^>]+src="([^"]+)"/i',
            $card,
            $m
        )) {
            $image = cm_normalize_image_path(
                $m[1]
            );
        }

        /*
         * Name
         */
        $name = '';

        if (preg_match(
            '/<h4[^>]*>(.*?)<\/h4>/is',
            $card,
            $m
        )) {
            $name = trim(
                strip_tags($m[1])
            );
        }

        /*
         * Designation
         */
        $designation = '';

        if (preg_match(
            '/<p[^>]*>(.*?)<\/p>/is',
            $card,
            $m
        )) {
            $designation = trim(
                strip_tags($m[1])
            );
        }

        /*
         * Teacher description.
         * New CMS-created cards use the dedicated
         * teacher-description class so it cannot be confused
         * with the designation paragraph. Existing teachers
         * without a description remain compatible.
         */
        $description = '';

        if (preg_match(
            '/<p[^>]*class=["\'][^"\']*\bteacher-description\b[^"\']*["\'][^>]*>(.*?)<\/p>/is',
            $card,
            $m
        )) {
            $description = trim(
                strip_tags($m[1])
            );
        }

        if ($name === '') {
            continue;
        }

        $teachers[] = array(
            'id' => $id,
            'name' => $name,
            'designation' => $designation,
            'description' => $description,
            'image' => $image
        );
    }

    return $teachers;
}


/* =========================================================
   TEACHER CARD RENDERER
   ========================================================= */

function cm_teacher_card($teacher, $delay = '0.1s')
{
    $id = cm_escape(
        $teacher['id']
    );

    $name = cm_escape(
        $teacher['name']
    );

    $designation = cm_escape(
        $teacher['designation']
    );

    $description = cm_escape(
        $teacher['description'] ?? ''
    );

    $image = cm_escape(
        cm_normalize_image_path(
            $teacher['image']
        )
    );

    return '
            <div class="col-md-6 col-lg-4 col-xl-3 wow fadeIn" data-wow-delay="' . $delay . 's">
                <div class="team-item border border-primary img-border-radius overflow-hidden" data-admin-id="' . $id . '">
                    <img src="' . $image . '" class="img-fluid w-100" alt="' . $name . '">

                    <div class="team-icon d-flex align-items-center justify-content-center">
                        <a class="share btn btn-primary btn-md-square text-white rounded-circle me-3" href="">
                            <i class="fas fa-share-alt"></i>
                        </a>
                        <a class="share-link btn btn-primary btn-md-square text-white rounded-circle me-3" href="">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a class="share-link btn btn-primary btn-md-square text-white rounded-circle me-3" href="">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a class="share-link btn btn-primary btn-md-square text-white rounded-circle" href="">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>

                    <div class="team-content text-center py-3">
                        <h4 class="text-primary">' . $name . '</h4>
                        <p class="text-muted mb-2">' . $designation . '</p>' .
                        ($description !== '' ? '
                        <p class="teacher-description text-muted mb-0">' . $description . '</p>' : '') . '
                    </div>
                </div>
            </div>';
}


function cm_render_teachers($teachers)
{
    $html = '';

    $delayValues = array(
        '0.1',
        '0.3',
        '0.5',
        '0.7'
    );

    foreach ($teachers as $index => $teacher) {

        if (isset($delayValues[$index % 4])) {
            $delay = $delayValues[
                $index % 4
            ];
        } else {
            $delay = '0.1';
        }

        $html .=
            cm_teacher_card(
                $teacher,
                $delay
            );

        $html .= "\n";
    }

    return
        '<div class="row g-5 justify-content-center">' .
        "\n" .
        $html .
        '        </div>';
}


/* =========================================================
   ADD TEACHER
   ========================================================= */

function cm_add_teacher()
{
    $name = cm_required(
        $_POST['teacher_name'] ?? '',
        'Teacher name'
    );

    $designation = cm_required(
        $_POST['teacher_designation'] ?? '',
        'Designation'
    );

    $description = cm_required(
        $_POST['teacher_description'] ?? '',
        'Teacher description'
    );

    if (
        !isset($_FILES['teacher_image']) ||
        !is_array($_FILES['teacher_image'])
    ) {
        throw new RuntimeException(
            'Teacher image is required.'
        );
    }

    $file = $_FILES['teacher_image'];

    if (
        !isset($file['error']) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException(
            'Teacher image upload failed.'
        );
    }

    /*
     * IMPORTANT:
     * Validate BOTH files before uploading anything.
     */
    cm_validate_managed_sections(
        array(
            TEAM_FILE,
            INDEX_FILE
        ),
        TEAM_MANAGED_START,
        TEAM_MANAGED_END
    );

    /*
     * Read current teachers from team.html.
     */
    $teamHtml = cm_read_file(
        TEAM_FILE
    );

    $indexHtml = cm_read_file(
        INDEX_FILE
    );

    $teachers = cm_get_teachers_from_file(
        TEAM_FILE
    );

    /*
     * Generate a unique admin ID.
     */
    do {
        $id =
            'teacher-' .
            bin2hex(
                random_bytes(5)
            );

        $exists = false;

        foreach ($teachers as $teacher) {
            if ($teacher['id'] === $id) {
                $exists = true;
                break;
            }
        }

    } while ($exists);


    /*
     * Upload image.
     *
     * uploadImage() is supplied by admin-config.php.
     */
    $imagePath = uploadImage(
        $file,
        'teacher'
    );

    if (!$imagePath) {
        throw new RuntimeException(
            'Unable to upload teacher image.'
        );
    }

    $imagePath =
        cm_normalize_image_path(
            $imagePath
        );


    $newTeacher = array(
        'id' => $id,
        'name' => $name,
        'designation' => $designation,
        'description' => $description,
        'image' => $imagePath
    );


    /*
     * Add new teacher to the existing list.
     */
    $teachers[] = $newTeacher;


    /*
     * Render ONE identical managed teacher list
     * for both pages.
     */
    $newSection =
        cm_render_teachers(
            $teachers
        );


    try {

        $newTeamHtml =
            cm_replace_section(
                $teamHtml,
                TEAM_MANAGED_START,
                TEAM_MANAGED_END,
                $newSection
            );

        $newIndexHtml =
            cm_replace_section(
                $indexHtml,
                TEAM_MANAGED_START,
                TEAM_MANAGED_END,
                $newSection
            );


        /*
         * Save both as one logical operation.
         */
        cm_save_multiple_files(
            array(
                TEAM_FILE => $newTeamHtml,
                INDEX_FILE => $newIndexHtml
            )
        );


        /*
         * Verify that the new ID exists in BOTH files.
         */
        $savedTeam =
            cm_read_file(TEAM_FILE);

        $savedIndex =
            cm_read_file(INDEX_FILE);

        if (
            strpos(
                $savedTeam,
                'data-admin-id="' . $id . '"'
            ) === false ||
            strpos(
                $savedIndex,
                'data-admin-id="' . $id . '"'
            ) === false
        ) {
            throw new RuntimeException(
                'Teacher was not written correctly to both website pages.'
            );
        }

        /*
         * Verify image file actually exists.
         */
        $imageFilename =
            basename($imagePath);

        $imageFullPath =
            rtrim(
                IMAGE_DIR,
                DIRECTORY_SEPARATOR
            ) .
            DIRECTORY_SEPARATOR .
            $imageFilename;

        if (!file_exists($imageFullPath)) {

            /*
             * HTML rollback is handled below.
             */
            throw new RuntimeException(
                'Teacher was added but the uploaded image file could not be verified.'
            );
        }

    } catch (Throwable $e) {

        /*
         * If operation fails, remove only the newly
         * generated managed image.
         */
        cm_delete_image_if_managed(
            $imagePath,
            'teacher'
        );

        throw $e;
    }

    return true;
}


/* =========================================================
   DELETE TEACHER
   ========================================================= */

function cm_delete_teacher()
{
    $teacherId = cm_required(
        $_POST['teacher_id'] ?? '',
        'Teacher ID'
    );

    cm_validate_managed_sections(
        array(
            TEAM_FILE,
            INDEX_FILE
        ),
        TEAM_MANAGED_START,
        TEAM_MANAGED_END
    );

    $teamHtml =
        cm_read_file(TEAM_FILE);

    $indexHtml =
        cm_read_file(INDEX_FILE);

    $teachers =
        cm_get_teachers_from_file(
            TEAM_FILE
        );

    $remaining =
        array();

    $deletedTeacher =
        null;

    foreach ($teachers as $teacher) {

        if (
            $teacher['id'] === $teacherId
        ) {
            $deletedTeacher =
                $teacher;
        } else {
            $remaining[] =
                $teacher;
        }
    }

    if ($deletedTeacher === null) {
        throw new RuntimeException(
            'Teacher not found.'
        );
    }

    /*
     * Prevent accidentally deleting all
     * managed data due to a malformed request.
     */
    if (count($remaining) < 0) {
        throw new RuntimeException(
            'Invalid teacher list.'
        );
    }

    $newSection =
        cm_render_teachers(
            $remaining
        );

    $newTeamHtml =
        cm_replace_section(
            $teamHtml,
            TEAM_MANAGED_START,
            TEAM_MANAGED_END,
            $newSection
        );

    $newIndexHtml =
        cm_replace_section(
            $indexHtml,
            TEAM_MANAGED_START,
            TEAM_MANAGED_END,
            $newSection
        );

    cm_save_multiple_files(
        array(
            TEAM_FILE => $newTeamHtml,
            INDEX_FILE => $newIndexHtml
        )
    );

    /*
     * Only after BOTH HTML files are successfully
     * updated, delete the managed image.
     */
    cm_delete_image_if_managed(
        $deletedTeacher['image'],
        'teacher'
    );

    return true;
}


/* =========================================================
   EVENT READER
   ========================================================= */

function cm_get_events_from_file($file)
{
    $html = cm_read_file($file);

    $section = cm_get_section(
        $html,
        EVENT_MANAGED_START,
        EVENT_MANAGED_END
    );

    $events = array();

    preg_match_all(
        '/data-admin-id="(event-[^"]+)"/i',
        $section,
        $matches,
        PREG_OFFSET_CAPTURE
    );

    if (empty($matches[1])) {
        return array();
    }

    $count =
        count($matches[1]);

    for ($i = 0; $i < $count; $i++) {

        $id =
            $matches[1][$i][0];

        $idOffset =
            $matches[1][$i][1];

        /*
         * Locate the event outer column.
         */
        $columnStart = strrpos(
            substr(
                $section,
                0,
                $idOffset
            ),
            '<div class="col-md-6'
        );

        if ($columnStart === false) {
            $columnStart = 0;
        }

        /*
         * Locate next event.
         */
        if ($i + 1 < $count) {

            $nextIdOffset =
                $matches[1][$i + 1][1];

            $nextColumnStart =
                strrpos(
                    substr(
                        $section,
                        0,
                        $nextIdOffset
                    ),
                    '<div class="col-md-6'
                );

            if ($nextColumnStart === false) {
                $nextColumnStart =
                    strlen($section);
            }

        } else {

            $nextColumnStart =
                strlen($section);
        }

        $card =
            substr(
                $section,
                $columnStart,
                $nextColumnStart - $columnStart
            );


        /*
         * Image
         */
        $image = '';

        if (preg_match(
            '/<img[^>]+src="([^"]+)"/i',
            $card,
            $m
        )) {
            $image =
                cm_normalize_image_path(
                    $m[1]
                );
        }


        /*
         * Date
         */
        $date = '';

        if (preg_match(
            '/class="[^"]*events-rate[^"]*"[^>]*>(.*?)<\/div>/is',
            $card,
            $m
        )) {
            $date =
                trim(
                    strip_tags($m[1])
                );
        }


        /*
         * Title
         */
        $title = '';

        if (preg_match(
            '/<a[^>]*class="[^"]*\bh4\b[^"]*"[^>]*>(.*?)<\/a>/is',
            $card,
            $m
        )) {
            $title =
                trim(
                    strip_tags($m[1])
                );
        }

        if ($title === '') {

            if (preg_match(
                '/<a[^>]*>(.*?)<\/a>/is',
                $card,
                $m
            )) {
                $title =
                    trim(
                        strip_tags($m[1])
                    );
            }
        }


        /*
         * Description
         */
        $description = '';

        if (preg_match(
            '/<p[^>]*>(.*?)<\/p>/is',
            $card,
            $m
        )) {
            $description =
                trim(
                    strip_tags($m[1])
                );
        }


        if ($title === '') {
            continue;
        }

        $events[] = array(
            'id' => $id,
            'title' => $title,
            'date' => $date,
            'description' => $description,
            'image' => $image
        );
    }

    return $events;
}


/* =========================================================
   EVENT CARD RENDERER
   ========================================================= */

function cm_event_card($event, $delay = '0.1s')
{
    $id =
        cm_escape(
            $event['id']
        );

    $title =
        cm_escape(
            $event['title']
        );

    $date =
        cm_escape(
            $event['date']
        );

    $description =
        cm_escape(
            $event['description']
        );

    $image =
        cm_escape(
            cm_normalize_image_path(
                $event['image']
            )
        );

    return '
            <div class="col-md-6 col-lg-6 col-xl-4 wow fadeIn" data-wow-delay="' . $delay . 's">
                <div class="events-item bg-primary rounded" data-admin-id="' . $id . '">
                    <div class="events-inner position-relative">

                        <div class="events-img overflow-hidden rounded-circle position-relative">
                            <img src="' . $image . '" class="img-fluid w-100 rounded-circle" alt="' . $title . '">

                            <div class="event-overlay">
                                <a href="' . $image . '" data-lightbox="' . $id . '">
                                    <i class="fas fa-link text-white fa-2x"></i>
                                </a>
                            </div>
                        </div>

                        <div class="px-4 py-2 bg-secondary text-white text-center events-rate">
                            ' . $date . '
                        </div>

                        <div class="d-flex justify-content-between px-4 py-2 bg-secondary">
                        </div>

                    </div>

                    <div class="events-text p-4 border border-primary bg-white border-top-0 rounded-bottom">
                        <a href="#" class="h4">
                            ' . $title . '
                        </a>

                        <p class="mb-0 mt-3">
                            ' . $description . '
                        </p>
                    </div>

                </div>
            </div>';
}


function cm_render_events($events)
{
    $html = '';

    $delayValues =
        array(
            '0.1',
            '0.3',
            '0.5',
            '0.7'
        );

    foreach ($events as $index => $event) {

        $delay =
            $delayValues[
                $index % 4
            ];

        $html .=
            cm_event_card(
                $event,
                $delay
            );

        $html .= "\n";
    }

    return
        '<div class="row g-5 justify-content-center">' .
        "\n" .
        $html .
        '        </div>';
}


/* =========================================================
   ADD EVENT
   ========================================================= */

function cm_add_event()
{
    $title =
        cm_required(
            $_POST['event_title'] ?? '',
            'Event title'
        );

    $date =
        cm_required(
            $_POST['event_date'] ?? '',
            'Event date'
        );

    $description =
        cm_required(
            $_POST['event_description'] ?? '',
            'Event description'
        );


    if (
        !isset($_FILES['event_image']) ||
        !is_array($_FILES['event_image'])
    ) {
        throw new RuntimeException(
            'Event image is required.'
        );
    }

    $file =
        $_FILES['event_image'];

    if (
        !isset($file['error']) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException(
            'Event image upload failed.'
        );
    }


    /*
     * Validate BOTH files before upload.
     */
    cm_validate_managed_sections(
        array(
            EVENT_FILE,
            INDEX_FILE
        ),
        EVENT_MANAGED_START,
        EVENT_MANAGED_END
    );


    $eventHtml =
        cm_read_file(EVENT_FILE);

    $indexHtml =
        cm_read_file(INDEX_FILE);

    $events =
        cm_get_events_from_file(
            EVENT_FILE
        );


    /*
     * Generate unique event ID.
     */
    do {

        $id =
            'event-' .
            bin2hex(
                random_bytes(5)
            );

        $exists =
            false;

        foreach ($events as $event) {

            if (
                $event['id'] === $id
            ) {
                $exists = true;
                break;
            }
        }

    } while ($exists);


    /*
     * Upload image.
     */
    $imagePath =
        uploadImage(
            $file,
            'event'
        );

    if (!$imagePath) {
        throw new RuntimeException(
            'Unable to upload event image.'
        );
    }

    $imagePath =
        cm_normalize_image_path(
            $imagePath
        );


    $newEvent =
        array(
            'id' => $id,
            'title' => $title,
            'date' => $date,
            'description' => $description,
            'image' => $imagePath
        );


    $events[] =
        $newEvent;


    /*
     * Render identical event list
     * for both event.html and index.html.
     */
    $newSection =
        cm_render_events(
            $events
        );
try {

        $newEventHtml =
            cm_replace_section(
                $eventHtml,
                EVENT_MANAGED_START,
                EVENT_MANAGED_END,
                $newSection
            );

        $newIndexHtml =
            cm_replace_section(
                $indexHtml,
                EVENT_MANAGED_START,
                EVENT_MANAGED_END,
                $newSection
            );

        // Keep the homepage event-popup data in sync with the newly saved event list.
        $newIndexHtml =
            cm_update_event_homepage_from_html(
                $newIndexHtml,
                $events
            );


        cm_save_multiple_files(
            array(
                EVENT_FILE => $newEventHtml,
                INDEX_FILE => $newIndexHtml
            )
        );


        /*
         * Verify new event exists in both files.
         */
        $savedEvent =
            cm_read_file(EVENT_FILE);

        $savedIndex =
            cm_read_file(INDEX_FILE);

        if (
            strpos(
                $savedEvent,
                'data-admin-id="' . $id . '"'
            ) === false ||
            strpos(
                $savedIndex,
                'data-admin-id="' . $id . '"'
            ) === false
        ) {
            throw new RuntimeException(
                'Event was not written correctly to both website pages.'
            );
        }


        /*
         * Verify uploaded image exists.
         */
        $imageFilename =
            basename($imagePath);

        $imageFullPath =
            rtrim(
                IMAGE_DIR,
                DIRECTORY_SEPARATOR
            ) .
            DIRECTORY_SEPARATOR .
            $imageFilename;

        if (!file_exists($imageFullPath)) {

            throw new RuntimeException(
                'Event was added but the uploaded image file could not be verified.'
            );
        }

    } catch (Throwable $e) {

        cm_delete_image_if_managed(
            $imagePath,
            'event'
        );

        throw $e;
    }

    return true;
}


/* =========================================================
   DELETE EVENT
   ========================================================= */

function cm_delete_event()
{
    $eventId =
        cm_required(
            $_POST['event_id'] ?? '',
            'Event ID'
        );


    cm_validate_managed_sections(
        array(
            EVENT_FILE,
            INDEX_FILE
        ),
        EVENT_MANAGED_START,
        EVENT_MANAGED_END
    );


    $eventHtml =
        cm_read_file(EVENT_FILE);

    $indexHtml =
        cm_read_file(INDEX_FILE);


    $events =
        cm_get_events_from_file(
            EVENT_FILE
        );


    $remaining =
        array();

    $deletedEvent =
        null;


    foreach ($events as $event) {

        if (
            $event['id'] === $eventId
        ) {

            $deletedEvent =
                $event;

        } else {

            $remaining[] =
                $event;
        }
    }


    if ($deletedEvent === null) {
        throw new RuntimeException(
            'Event not found.'
        );
    }


    $newSection =
        cm_render_events(
            $remaining
        );
$newEventHtml =
        cm_replace_section(
            $eventHtml,
            EVENT_MANAGED_START,
            EVENT_MANAGED_END,
            $newSection
        );


    $newIndexHtml =
        cm_replace_section(
            $indexHtml,
            EVENT_MANAGED_START,
            EVENT_MANAGED_END,
            $newSection
        );

    // Keep the homepage event-popup data in sync after deleting the event.
    $newIndexHtml =
        cm_update_event_homepage_from_html(
            $newIndexHtml,
            $remaining
        );


    cm_save_multiple_files(
        array(
            EVENT_FILE => $newEventHtml,
            INDEX_FILE => $newIndexHtml
        )
    );


    /*
     * Delete image only after both HTML files
     * have been successfully saved.
     */
    cm_delete_image_if_managed(
        $deletedEvent['image'],
        'event'
    );

    return true;
}




function cm_render_event_home_popup($events)
{
    $data = array();

    foreach ($events as $event) {
        $date = trim((string)$event['date']);
        $normalized = '';

        $formats = array(
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'm/d/Y',
            'd.m.Y'
        );

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat('!' . $format, $date);

            if ($dt && $dt->format($format) === $date) {
                $normalized = $dt->format('Y-m-d');
                break;
            }
        }

        if ($normalized === '') {
            $timestamp = strtotime($date);

            if ($timestamp !== false) {
                $normalized = date('Y-m-d', $timestamp);
            }
        }

        if ($normalized === '') {
            continue;
        }

        $data[] = array(
            'id' => (string)$event['id'],
            'title' => (string)$event['title'],
            'date' => $normalized,
            'description' => (string)$event['description'],
            'image' => cm_normalize_image_path($event['image'])
        );
    }

    $json = json_encode(
        $data,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        throw new RuntimeException(
            'Unable to prepare event data for homepage.'
        );
    }

    return str_replace(
        '__EVENT_JSON__',
        cm_escape($json),
        <<<'EVENT_POPUP_HTML'

<div id="school-event-popup-data"
     data-events="__EVENT_JSON__"></div>

<div id="school-event-popup"
     class="school-event-popup"
     aria-hidden="true"
     role="dialog"
     aria-modal="true"
     aria-labelledby="school-event-popup-title">

    <div class="school-event-popup-overlay"
         data-event-close></div>

    <div class="school-event-popup-box">

        <button type="button"
                class="school-event-popup-close"
                aria-label="Close"
                data-event-close>
            &times;
        </button>

        <div class="school-event-popup-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>

        <h2 id="school-event-popup-title">
            Today at Our School
        </h2>

        <p class="school-event-popup-subtitle">
            We are excited to share today&apos;s special event with you!
        </p>

        <div id="school-event-popup-list"></div>

    </div>
</div>

<style>
.school-event-popup {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99998;
}

.school-event-popup.is-visible {
    display: block;
}

.school-event-popup-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.65);
}

.school-event-popup-box {
    position: relative;
    width: min(92%, 620px);
    max-height: 90vh;
    overflow-y: auto;
    margin: 5vh auto 0;
    background: #fff;
    border-radius: 22px;
    padding: 34px 28px 28px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
}

.school-event-popup-close {
    position: absolute;
    top: 10px;
    right: 14px;
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: #f3f3f3;
    color: #333;
    font-size: 28px;
    line-height: 34px;
    cursor: pointer;
}

.school-event-popup-icon {
    font-size: 44px;
    margin-bottom: 8px;
}

.school-event-popup-subtitle {
    color: #666;
    margin-bottom: 20px;
}

.school-event-popup-event {
    display: inline-block;
    width: min(100%, 500px);
    margin: 10px auto;
    text-align: left;
    border: 1px solid #eee;
    border-radius: 16px;
    overflow: hidden;
    background: #fff;
}

.school-event-popup-event img {
    display: block;
    width: 100%;
    height: 230px;
    object-fit: cover;
}

.school-event-popup-event-content {
    padding: 18px;
}

.school-event-popup-event-title {
    margin: 0 0 8px;
    font-size: 23px;
    font-weight: 700;
}

.school-event-popup-event-date {
    font-weight: 600;
    margin-bottom: 10px;
}

.school-event-popup-event-description {
    margin: 0;
    color: #666;
    line-height: 1.6;
}

@media (max-width: 575.98px) {
    .school-event-popup-box {
        padding: 32px 16px 22px;
    }

    .school-event-popup-event img {
        height: 190px;
    }
}
</style>

<script>
(function () {
    function getIndiaToday() {
        try {
            var parts = new Intl.DateTimeFormat("en-CA", {
                timeZone: "Asia/Kolkata",
                year: "numeric",
                month: "2-digit",
                day: "2-digit"
            }).formatToParts(new Date());

            var year = "";
            var month = "";
            var day = "";

            parts.forEach(function (part) {
                if (part.type === "year") year = part.value;
                if (part.type === "month") month = part.value;
                if (part.type === "day") day = part.value;
            });

            return year + "-" + month + "-" + day;
        } catch (error) {
            var now = new Date();

            return now.getFullYear() + "-"
                + String(now.getMonth() + 1).padStart(2, "0") + "-"
                + String(now.getDate()).padStart(2, "0");
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(new RegExp("[']", "g"), "&#039;");
    }

    function showEventPopup() {
        var birthdayPopup = document.getElementById("school-birthday-popup");

        // If today's birthday popup is currently visible, wait until it is
        // closed before showing today's event popup. This prevents the two
        // full-screen overlays from hiding one another.
        if (birthdayPopup && birthdayPopup.classList.contains("is-visible")) {
            return false;
        }

        var node =
            document.getElementById("school-event-popup-data");

        var popup =
            document.getElementById("school-event-popup");

        var list =
            document.getElementById("school-event-popup-list");

        if (!node || !popup || !list) return false;

        var events = [];

        try {
            events = JSON.parse(
                node.getAttribute("data-events") || "[]"
            );
        } catch (error) {
            return false;
        }

        var today = getIndiaToday();

        var todaysEvents = events.filter(function (event) {
            return event && event.date === today;
        });

        if (!todaysEvents.length) return false;

        var todayKey =
            "schoolEventPopupShown-" + today;

        try {
            if (sessionStorage.getItem(todayKey) === "1") {
                return false;
            }
        } catch (error) {}

        list.innerHTML = todaysEvents.map(function (event) {
            return ''
                + '<div class="school-event-popup-event">'
                + '<img src="' + escapeHtml(event.image) + '" alt="'
                + escapeHtml(event.title) + '">'
                + '<div class="school-event-popup-event-content">'
                + '<h3 class="school-event-popup-event-title">'
                + escapeHtml(event.title)
                + '</h3>'
                + '<div class="school-event-popup-event-date">'
                + 'Date: ' + escapeHtml(event.date)
                + '</div>'
                + '<p class="school-event-popup-event-description">'
                + escapeHtml(event.description)
                + '</p>'
                + '</div>'
                + '</div>';
        }).join("");

        popup.classList.add("is-visible");
        popup.setAttribute("aria-hidden", "false");

        try {
            sessionStorage.setItem(todayKey, "1");
        } catch (error) {}

        return true;
    }

    function closeEventPopup() {
        var popup =
            document.getElementById("school-event-popup");

        if (!popup) return;

        popup.classList.remove("is-visible");
        popup.setAttribute("aria-hidden", "true");

        // Tell the event popup that the birthday popup has been closed.
        // This allows both popups to appear sequentially on the same date.
        document.dispatchEvent(new CustomEvent("schoolBirthdayPopupClosed"));
    }

    document.addEventListener("DOMContentLoaded", function () {
        var birthdayPopup = document.getElementById("school-birthday-popup");

        if (birthdayPopup && birthdayPopup.classList.contains("is-visible")) {
            // Birthday is currently on top. Wait for it to close, then show event.
            document.addEventListener("schoolBirthdayPopupClosed", function () {
                window.setTimeout(function () {
                    var birthday = document.getElementById("school-birthday-popup");
                    if (!birthday || !birthday.classList.contains("is-visible")) {
                        showEventPopup();
                    }
                }, 0);
            }, { once: true });
        } else {
            showEventPopup();
        }

        document.querySelectorAll(
            "[data-event-close]"
        ).forEach(function (button) {
            button.addEventListener(
                "click",
                closeEventPopup
            );
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeEventPopup();
            }
        });
    });
})();
</script>
EVENT_POPUP_HTML
    );
}

function cm_update_event_homepage_from_html($indexHtml, $events)
{
    $newSection =
        cm_render_event_home_popup($events);

    return cm_replace_section(
        $indexHtml,
        EVENT_POPUP_MANAGED_START,
        EVENT_POPUP_MANAGED_END,
        $newSection
    );
}

function cm_update_event_homepage($events)
{
    return cm_update_event_homepage_from_html(
        cm_read_file(INDEX_FILE),
        $events
    );
}



/* =========================================================
   PROGRAM MANAGEMENT
   ========================================================= */

function cm_cleanup_program_literal_newlines()
{
    foreach (array(PROGRAM_FILE, INDEX_FILE) as $file) {
        if (!is_file($file)) {
            continue;
        }

        $html = cm_read_file($file);
        $section = cm_get_section($html, PROGRAM_MANAGED_START, PROGRAM_MANAGED_END);
        if ($section === '' || strpos($section, '\\n') === false) {
            continue;
        }

        // Older program records were generated with literal "\\n" text.
        // Convert those sequences back to real line breaks without changing
        // any program card content.
        $cleanSection = str_replace(
            array('\\r\\n', '\\n', '\\r'),
            "\n",
            $section
        );

        if ($cleanSection !== $section) {
            $cleanHtml = cm_replace_section(
                $html,
                PROGRAM_MANAGED_START,
                PROGRAM_MANAGED_END,
                $cleanSection
            );
            cm_write_file($file, $cleanHtml);
        }
    }
}

function cm_get_programs_from_file($file)
{
    // Also repair program HTML created by the previous CMS version.
    cm_cleanup_program_literal_newlines();
    $html = cm_read_file($file);
    $section = cm_get_section($html, PROGRAM_MANAGED_START, PROGRAM_MANAGED_END);
    $programs = array();

    if ($section === '') return $programs;

    /*
     * Program cards may contain literal "\\n" sequences when they were
     * generated by an older version of the CMS. Do not depend on whitespace
     * between closing tags. Instead, locate each program-item div and match
     * its complete nested div structure by depth.
     */
    $offset = 0;
    $sectionLength = strlen($section);

    while ($offset < $sectionLength && preg_match(
        '/<div\s+class=["\']program-item\s+rounded["\'][^>]*data-admin-id=["\']([^"\']+)["\'][^>]*>/i',
        $section,
        $startMatch,
        PREG_OFFSET_CAPTURE,
        $offset
    )) {
        $cardStart = $startMatch[0][1];
        $id = trim($startMatch[1][0]);
        $tagStart = strpos($section, '>', $cardStart);
        if ($tagStart === false) break;

        $scanPos = $tagStart + 1;
        $depth = 1;
        $sectionEnd = null;

        while ($scanPos < $sectionLength) {
            $nextOpen = stripos($section, '<div', $scanPos);
            $nextClose = stripos($section, '</div', $scanPos);

            if ($nextClose === false) break;

            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $gt = strpos($section, '>', $nextOpen);
                if ($gt === false) break;
                $scanPos = $gt + 1;
            } else {
                $depth--;
                $gt = strpos($section, '>', $nextClose);
                if ($gt === false) break;
                $scanPos = $gt + 1;
                if ($depth === 0) {
                    $sectionEnd = $scanPos;
                    break;
                }
            }
        }

        if ($sectionEnd === null) break;

        $card = substr($section, $cardStart, $sectionEnd - $cardStart);
        $pick = function($pattern) use ($card) {
            return preg_match($pattern, $card, $x) ? trim(strip_tags($x[1])) : '';
        };

        $image = '';
        if (preg_match('/<img[^>]*class=["\'][^"\']*img-fluid\s+w-100[^"\']*["\'][^>]*src=["\']([^"\']+)["\']/is', $card, $x)) {
            $image = trim($x[1]);
        } elseif (preg_match('/<img[^>]*src=["\']([^"\']+)["\']/is', $card, $x)) {
            $image = trim($x[1]);
        }

        $teacherImage = 'img/program-teacher.jpg';
        if (preg_match('/<div[^>]*class=["\'][^"\']*\bprogram-teacher\b[^"\']*["\'][^>]*>.*?<img[^>]*src=["\']([^"\']+)["\']/is', $card, $tx)) {
            $teacherImage = trim($tx[1]);
        }

        $programs[] = array(
            'id' => $id,
            'title' => $pick('/<a[^>]*class=["\'][^"\']*\bh4\b[^"\']*["\'][^>]*>(.*?)<\/a>/is'),
            'category' => $pick('/class=["\'][^"\']*\bprogram-rate\b[^"\']*["\'][^>]*>(.*?)<\/div>/is'),
            'description' => $pick('/<p[^>]*class=["\'][^"\']*\bmt-3\s+mb-0\b[^"\']*["\'][^>]*>(.*?)<\/p>/is'),
            'teacher_name' => $pick('/<h6[^>]*class=["\'][^"\']*\bmb-0\s+text-primary\b[^"\']*["\'][^>]*>(.*?)<\/h6>/is'),
            'teacher_designation' => $pick('/<h6[^>]*class=["\'][^"\']*\bmb-0\s+text-primary\b[^"\']*["\'][^>]*>.*?<\/h6>\s*<small[^>]*>(.*?)<\/small>/is'),
            'image' => $image,
            'teacher_image' => $teacherImage
        );

        $offset = $sectionEnd;
    }

    return $programs;
}

function cm_program_card($program, $delay='0.1')
{
    $id=cm_escape($program['id']);
    $title=cm_escape($program['title']);
    $category=cm_escape($program['category']);
    $description=cm_escape($program['description']);
    $teacherName=cm_escape($program['teacher_name']);
    $teacherDesignation=cm_escape($program['teacher_designation']);
    $image=cm_escape(cm_normalize_image_path($program['image']));
    $teacherImage=cm_escape(cm_normalize_image_path($program['teacher_image'] ?? 'img/program-teacher.jpg'));

    return <<<HTML
                    <div class="col-md-6 col-lg-6 col-xl-4 wow fadeIn" data-wow-delay="{$delay}s">
                        <div class="program-item rounded" data-admin-id="{$id}">
                            <div class="program-img position-relative">
                                <div class="overflow-hidden img-border-radius">
                                    <img src="{$image}" class="img-fluid w-100" alt="{$title}">
                                </div>
                                <div class="px-4 py-2 bg-primary text-white program-rate">{$category}</div>
                            </div>
                            <div class="program-text bg-white px-4 pb-3">
                                <div class="program-text-inner">
                                    <a href="#" class="h4">{$title}</a>
                                    <p class="mt-3 mb-0">{$description}</p>
                                </div>
                            </div>
                            <div class="program-teacher d-flex align-items-center border-top border-primary bg-white px-4 py-3">
                                <img src="{$teacherImage}" class="img-fluid rounded-circle p-2 border border-primary bg-white" alt="{$teacherName}" style="width: 70px; height: 70px; object-fit: cover;">
                                <div class="ms-3">
                                    <h6 class="mb-0 text-primary">{$teacherName}</h6>
                                    <small>{$teacherDesignation}</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between px-4 py-2 bg-primary rounded-bottom"></div>
                        </div>
                    </div>
HTML;
}

function cm_render_programs($programs)
{
    $html='';
    $delays=array('0.1','0.3','0.5','0.7');
    foreach($programs as $i=>$program) {
        $html .= cm_program_card($program,$delays[$i%4]);
    }

    return '<div class="row g-5 justify-content-center">' . "\n"
        . $html
        . '                    <div class="d-inline-block text-center wow fadeIn" data-wow-delay="0.1s"></div>' . "\n"
        . '                </div>';
}

function cm_add_program()
{
    $title=cm_required($_POST['program_title']??'','Program name');
    $category=cm_required($_POST['program_category']??'','Program category');
    $description=cm_required($_POST['program_description']??'','Program description');
    $teacherName=cm_required($_POST['program_teacher_name']??'','Teacher name');
    $teacherDesignation=cm_required($_POST['program_teacher_designation']??'','Teacher designation');
    if(!isset($_FILES['program_image'])||!is_array($_FILES['program_image'])) throw new RuntimeException('Program image is required.');
    if(!isset($_FILES['program_teacher_image'])||!is_array($_FILES['program_teacher_image'])) throw new RuntimeException('Teacher / Program Head photo is required.');
    $file=$_FILES['program_image'];
    $teacherFile=$_FILES['program_teacher_image'];
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Program image upload failed.');
    if(($teacherFile['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Teacher / Program Head photo upload failed.');
    cm_validate_managed_sections(array(PROGRAM_FILE,INDEX_FILE),PROGRAM_MANAGED_START,PROGRAM_MANAGED_END);
    $programHtml=cm_read_file(PROGRAM_FILE); $indexHtml=cm_read_file(INDEX_FILE); $programs=cm_get_programs_from_file(PROGRAM_FILE);
    do{$id='program-'.bin2hex(random_bytes(5));$exists=false;foreach($programs as $p)if($p['id']===$id){$exists=true;break;}}while($exists);
    $imagePath=uploadImage($file,'program_'); if(!$imagePath) throw new RuntimeException('Unable to upload program image.'); $imagePath=cm_normalize_image_path($imagePath);
    $teacherImagePath=uploadImage($teacherFile,'program_teacher_');
    if(!$teacherImagePath) { cm_delete_image_if_managed($imagePath,'program'); throw new RuntimeException('Unable to upload teacher / program head photo.'); }
    $teacherImagePath=cm_normalize_image_path($teacherImagePath);
    $programs[]=array('id'=>$id,'title'=>$title,'category'=>$category,'description'=>$description,'teacher_name'=>$teacherName,'teacher_designation'=>$teacherDesignation,'image'=>$imagePath,'teacher_image'=>$teacherImagePath);
    $newSection=cm_render_programs($programs);
    try{
        $newProgramHtml=cm_replace_section($programHtml,PROGRAM_MANAGED_START,PROGRAM_MANAGED_END,$newSection);
        $newIndexHtml=cm_replace_section($indexHtml,PROGRAM_MANAGED_START,PROGRAM_MANAGED_END,$newSection);
        cm_save_multiple_files(array(PROGRAM_FILE=>$newProgramHtml,INDEX_FILE=>$newIndexHtml));
    }catch(Throwable $e){cm_delete_image_if_managed($imagePath,'program');cm_delete_image_if_managed($teacherImagePath,'program_teacher');throw $e;}
    return true;
}

function cm_delete_program()
{
    $programId=cm_required($_POST['program_id']??'','Program ID');
    cm_validate_managed_sections(array(PROGRAM_FILE,INDEX_FILE),PROGRAM_MANAGED_START,PROGRAM_MANAGED_END);
    $programHtml=cm_read_file(PROGRAM_FILE); $indexHtml=cm_read_file(INDEX_FILE); $programs=cm_get_programs_from_file(PROGRAM_FILE);
    $remaining=array();$deleted=null;
    foreach($programs as $program){if($program['id']===$programId)$deleted=$program;else$remaining[]=$program;}
    if($deleted===null)throw new RuntimeException('Program not found.');
    $newSection=cm_render_programs($remaining);
    $newProgramHtml=cm_replace_section($programHtml,PROGRAM_MANAGED_START,PROGRAM_MANAGED_END,$newSection);
    $newIndexHtml=cm_replace_section($indexHtml,PROGRAM_MANAGED_START,PROGRAM_MANAGED_END,$newSection);
    cm_save_multiple_files(array(PROGRAM_FILE=>$newProgramHtml,INDEX_FILE=>$newIndexHtml));
    cm_delete_image_if_managed($deleted['image'],'program');
    cm_delete_image_if_managed($deleted['teacher_image'] ?? '','program_teacher');
    return true;
}


/* =========================================================
   BIRTHDAY MANAGEMENT
   ========================================================= */

function cm_get_birthdays_from_file($file)
{
    $html = cm_read_file($file);

    $section = cm_get_section(
        $html,
        BIRTHDAY_MANAGED_START,
        BIRTHDAY_MANAGED_END
    );

    $birthdays = array();

    preg_match_all(
        '/data-admin-id="(birthday-[^"]+)"/i',
        $section,
        $matches,
        PREG_OFFSET_CAPTURE
    );

    if (empty($matches[1])) {
        return array();
    }

    $count = count($matches[1]);

    for ($i = 0; $i < $count; $i++) {
        $id = $matches[1][$i][0];
        $offset = $matches[1][$i][1];

        $columnStart = strrpos(
            substr($section, 0, $offset),
            '<div class="birthday-card'
        );

        if ($columnStart === false) {
            $columnStart = strrpos(
                substr($section, 0, $offset),
                '<div class="col-'
            );
        }

        if ($columnStart === false) {
            $columnStart = 0;
        }

        if ($i + 1 < $count) {
            $nextOffset = $matches[1][$i + 1][1];
            $nextColumnStart = strrpos(
                substr($section, 0, $nextOffset),
                '<div class="birthday-card'
            );

            if ($nextColumnStart === false) {
                $nextColumnStart = strrpos(
                    substr($section, 0, $nextOffset),
                    '<div class="col-'
                );
            }

            if ($nextColumnStart === false) {
                $nextColumnStart = strlen($section);
            }
        } else {
            $nextColumnStart = strlen($section);
        }

        $card = substr(
            $section,
            $columnStart,
            $nextColumnStart - $columnStart
        );

        $name = '';
        if (preg_match(
            '/<h4[^>]*>(.*?)<\/h4>/is',
            $card,
            $m
        )) {
            $name = trim(strip_tags($m[1]));
        }

        $dob = '';
        if (preg_match(
            '/data-dob="([^"]+)"/i',
            $card,
            $m
        )) {
            $dob = trim($m[1]);
        }

        $image = '';
        if (preg_match(
            '/<img[^>]+src="([^"]+)"/i',
            $card,
            $m
        )) {
            $image = cm_normalize_image_path($m[1]);
        }

        if ($name === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            continue;
        }

        $birthdays[] = array(
            'id' => $id,
            'name' => $name,
            'dob' => $dob,
            'image' => $image
        );
    }

    return $birthdays;
}


function cm_birthday_card($birthday)
{
    $id = cm_escape($birthday['id']);
    $name = cm_escape($birthday['name']);
    $dob = cm_escape($birthday['dob']);
    $image = cm_escape(
        cm_normalize_image_path($birthday['image'])
    );

    return '
        <div class="col-md-6 col-lg-4 col-xl-3 birthday-card"
             data-admin-id="' . $id . '"
             data-dob="' . $dob . '">
            <div class="border border-primary rounded overflow-hidden h-100 bg-white">
                <img src="' . $image . '"
                     class="img-fluid w-100"
                     alt="' . $name . '">
                <div class="text-center p-3">
                    <h4 class="text-primary mb-2">' . $name . '</h4>
                    <p class="text-muted mb-0">
                        Date of Birth: ' . $dob . '
                    </p>
                </div>
            </div>
        </div>';
}


function cm_render_birthdays($birthdays)
{
    $html = '';

    foreach ($birthdays as $birthday) {
        $html .= cm_birthday_card($birthday);
        $html .= "\n";
    }

    if ($html === '') {
        $html = '
            <div class="col-12">
                <div class="alert alert-light border text-center mb-0">
                    No student birthdays have been added yet.
                </div>
            </div>';
    }

    return
        '<div class="row g-4 justify-content-center">' .
        "\n" .
        $html .
        '        </div>';
}


function cm_render_birthday_home_section($birthdays)
{
    $data = array();

    foreach ($birthdays as $birthday) {
        $data[] = array(
            'id' => (string)$birthday['id'],
            'name' => (string)$birthday['name'],
            'dob' => (string)$birthday['dob'],
            'image' => cm_normalize_image_path($birthday['image'])
        );
    }

    $json = json_encode(
        $data,
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        throw new RuntimeException(
            'Unable to prepare birthday data for homepage.'
        );
    }

    $template = <<<'HTML'
<div id="school-birthday-popup-data"
     data-birthdays="__BIRTHDAY_JSON__"></div>

<div id="school-birthday-popup"
     class="school-birthday-popup"
     aria-hidden="true"
     role="dialog"
     aria-modal="true"
     aria-labelledby="school-birthday-popup-title">

    <div class="school-birthday-popup-overlay"
         data-birthday-close></div>

    <div class="school-birthday-popup-box">

        <button type="button"
                class="school-birthday-popup-close"
                aria-label="Close"
                data-birthday-close>
            &times;
        </button>

        <div class="school-birthday-popup-icon">
            <i class="fas fa-birthday-cake"></i>
        </div>

        <h2 id="school-birthday-popup-title">Happy Birthday!</h2>

        <p class="school-birthday-popup-subtitle">
            Warm birthday wishes from everyone at our school family!
        </p>

        <div id="school-birthday-popup-list"></div>

        <p class="school-birthday-popup-footer">
            May your special day be filled with happiness, smiles and wonderful memories.
        </p>

    </div>
</div>

<style>
.school-birthday-popup{display:none;position:fixed;inset:0;z-index:99999}
.school-birthday-popup.is-visible{display:block}
.school-birthday-popup-overlay{position:absolute;inset:0;background:rgba(0,0,0,.65)}
.school-birthday-popup-box{position:relative;width:min(92%,560px);max-height:90vh;overflow-y:auto;margin:5vh auto 0;background:#fff;border-radius:22px;padding:34px 28px 28px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.school-birthday-popup-close{position:absolute;top:10px;right:14px;width:38px;height:38px;border:0;border-radius:50%;background:#f3f3f3;color:#333;font-size:28px;line-height:34px;cursor:pointer}
.school-birthday-popup-icon{font-size:46px;margin-bottom:8px}
.school-birthday-popup-subtitle,.school-birthday-popup-footer{color:#666}
.school-birthday-popup-student{display:inline-flex;flex-direction:column;align-items:center;margin:14px 8px 4px;vertical-align:top}
.school-birthday-popup-student img{width:150px;height:150px;object-fit:cover;border-radius:50%;border:5px solid #fff;box-shadow:0 6px 22px rgba(0,0,0,.16)}
.school-birthday-popup-student-name{margin-top:12px;font-size:20px;font-weight:700}
@media (max-width:575.98px){.school-birthday-popup-box{padding:32px 16px 22px}.school-birthday-popup-student img{width:125px;height:125px}}
</style>

<script>
(function () {
    function getIndiaTodayMonthDay() {
        try {
            var parts = new Intl.DateTimeFormat("en-CA", {
                timeZone: "Asia/Kolkata",
                month: "2-digit",
                day: "2-digit"
            }).formatToParts(new Date());

            var month = "";
            var day = "";

            parts.forEach(function (part) {
                if (part.type === "month") month = part.value;
                if (part.type === "day") day = part.value;
            });

            return month + "-" + day;
        } catch (error) {
            var now = new Date();
            return String(now.getMonth() + 1).padStart(2, "0")
                + "-"
                + String(now.getDate()).padStart(2, "0");
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(new RegExp("[']", "g"), "&#039;");
    }

    function showBirthdayPopup() {
        var dataNode = document.getElementById("school-birthday-popup-data");
        var popup = document.getElementById("school-birthday-popup");
        var list = document.getElementById("school-birthday-popup-list");

        if (!dataNode || !popup || !list) return;

        var birthdays = [];

        try {
            birthdays = JSON.parse(
                dataNode.getAttribute("data-birthdays") || "[]"
            );
        } catch (error) {
            return;
        }

        var todayMonthDay = getIndiaTodayMonthDay();

        var todaysBirthdays = birthdays.filter(function (student) {
            if (!student || !student.dob) return false;
            return student.dob.slice(5) === todayMonthDay;
        });

        if (!todaysBirthdays.length) return;

        var todayKey = "schoolBirthdayPopupShown-" + todayMonthDay;

        try {
            if (sessionStorage.getItem(todayKey) === "1") return;
        } catch (error) {}

        list.innerHTML = todaysBirthdays.map(function (student) {
            return '<div class="school-birthday-popup-student">'
                + '<img src="' + escapeHtml(student.image) + '" alt="'
                + escapeHtml(student.name) + '">'
                + '<div class="school-birthday-popup-student-name">'
                + escapeHtml(student.name)
                + '</div>'
                + '</div>';
        }).join("");

        popup.classList.add("is-visible");
        popup.setAttribute("aria-hidden", "false");

        try {
            sessionStorage.setItem(todayKey, "1");
        } catch (error) {}
    }

    function closeBirthdayPopup() {
        var popup = document.getElementById("school-birthday-popup");
        if (!popup) return;

        popup.classList.remove("is-visible");
        popup.setAttribute("aria-hidden", "true");

        // Tell the event popup manager that the birthday popup is closed.
        document.dispatchEvent(new CustomEvent("schoolBirthdayPopupClosed"));
    }

    document.addEventListener("DOMContentLoaded", function () {
        showBirthdayPopup();

        document.querySelectorAll("[data-birthday-close]").forEach(function (button) {
            button.addEventListener("click", closeBirthdayPopup);
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") closeBirthdayPopup();
        });
    });
})();
</script>
HTML;

    return str_replace(
        '__BIRTHDAY_JSON__',
        cm_escape($json),
        $template
    );
}


function cm_update_birthday_homepage($birthdays)
{
    $indexHtml = cm_read_file(INDEX_FILE);

    $newSection = cm_render_birthday_home_section($birthdays);

    return cm_replace_section(
        $indexHtml,
        BIRTHDAY_MANAGED_START,
        BIRTHDAY_MANAGED_END,
        $newSection
    );
}


function cm_add_birthday()
{
    $name = cm_required(
        $_POST['birthday_name'] ?? '',
        'Student name'
    );

    $dob = trim(
        (string)($_POST['birthday_dob'] ?? '')
    );

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        throw new RuntimeException(
            'Please select a valid date of birth.'
        );
    }

    $timestamp = strtotime($dob);

    if ($timestamp === false || date('Y-m-d', $timestamp) !== $dob) {
        throw new RuntimeException(
            'Please select a valid date of birth.'
        );
    }

    // Future calendar dates are allowed: this field is used to schedule
    // a recurring birthday popup by month/day, not to validate a real DOB.

    if (
        !isset($_FILES['birthday_image']) ||
        !is_array($_FILES['birthday_image'])
    ) {
        throw new RuntimeException(
            'Student photo is required.'
        );
    }

    $file = $_FILES['birthday_image'];

    if (
        !isset($file['error']) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException(
            'Student photo upload failed.'
        );
    }

    cm_validate_managed_sections(
        array(
            BIRTHDAY_FILE,
            INDEX_FILE
        ),
        BIRTHDAY_MANAGED_START,
        BIRTHDAY_MANAGED_END
    );

    $birthdayHtml = cm_read_file(BIRTHDAY_FILE);
    $indexHtml = cm_read_file(INDEX_FILE);

    $birthdays = cm_get_birthdays_from_file(BIRTHDAY_FILE);

    do {
        $id = 'birthday-' . bin2hex(random_bytes(5));

        $exists = false;

        foreach ($birthdays as $birthday) {
            if ($birthday['id'] === $id) {
                $exists = true;
                break;
            }
        }
    } while ($exists);

    $imagePath = uploadImage(
        $file,
        'birthday_'
    );

    if (!$imagePath) {
        throw new RuntimeException(
            'Unable to upload student photo.'
        );
    }

    $imagePath = cm_normalize_image_path($imagePath);

    $birthdays[] = array(
        'id' => $id,
        'name' => $name,
        'dob' => $dob,
        'image' => $imagePath
    );

    $newBirthdaySection =
        cm_render_birthdays($birthdays);

    $newBirthdayHtml =
        cm_replace_section(
            $birthdayHtml,
            BIRTHDAY_MANAGED_START,
            BIRTHDAY_MANAGED_END,
            $newBirthdaySection
        );

    $newIndexHtml =
        cm_update_birthday_homepage($birthdays);

    try {
        cm_save_multiple_files(
            array(
                BIRTHDAY_FILE => $newBirthdayHtml,
                INDEX_FILE => $newIndexHtml
            )
        );

        $savedBirthdayHtml = cm_read_file(BIRTHDAY_FILE);
        $savedIndexHtml = cm_read_file(INDEX_FILE);

        if (
            strpos(
                $savedBirthdayHtml,
                'data-admin-id="' . $id . '"'
            ) === false ||
            strpos(
                $savedIndexHtml,
                'data-birthdays='
            ) === false
        ) {
            throw new RuntimeException(
                'Birthday record was not written correctly.'
            );
        }

        $imageFilename = basename($imagePath);
        $imageFullPath =
            rtrim(IMAGE_DIR, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $imageFilename;

        if (!file_exists($imageFullPath)) {
            throw new RuntimeException(
                'Student was added but the uploaded image could not be verified.'
            );
        }

    } catch (Throwable $e) {
        cm_delete_image_if_managed(
            $imagePath,
            'birthday'
        );

        throw $e;
    }

    return true;
}


function cm_delete_birthday()
{
    $birthdayId = cm_required(
        $_POST['birthday_id'] ?? '',
        'Birthday ID'
    );

    cm_validate_managed_sections(
        array(
            BIRTHDAY_FILE,
            INDEX_FILE
        ),
        BIRTHDAY_MANAGED_START,
        BIRTHDAY_MANAGED_END
    );

    $birthdayHtml = cm_read_file(BIRTHDAY_FILE);
    $indexHtml = cm_read_file(INDEX_FILE);

    $birthdays =
        cm_get_birthdays_from_file(
            BIRTHDAY_FILE
        );

    $remaining = array();
    $deletedBirthday = null;

    foreach ($birthdays as $birthday) {
        if ($birthday['id'] === $birthdayId) {
            $deletedBirthday = $birthday;
        } else {
            $remaining[] = $birthday;
        }
    }

    if ($deletedBirthday === null) {
        throw new RuntimeException(
            'Birthday record not found.'
        );
    }

    $newBirthdayHtml =
        cm_replace_section(
            $birthdayHtml,
            BIRTHDAY_MANAGED_START,
            BIRTHDAY_MANAGED_END,
            cm_render_birthdays($remaining)
        );

    $newIndexHtml =
        cm_update_birthday_homepage($remaining);

    cm_save_multiple_files(
        array(
            BIRTHDAY_FILE => $newBirthdayHtml,
            INDEX_FILE => $newIndexHtml
        )
    );

    cm_delete_image_if_managed(
        $deletedBirthday['image'],
        'birthday'
    );

    return true;
}


/* =========================================================
   CONTACT DETAILS
   ========================================================= */

function cm_update_topbar_contact_details($html, $address, $email)
{
    /* Update only the topbar contact links, not unrelated page content. */
    $html = preg_replace_callback(
        '/(<div\b[^>]*class=["\'][^"\']*\btop-info\b[^"\']*["\'][^>]*>.*?<small\b[^>]*>\s*<i\b[^>]*class=["\'][^"\']*\bfa-map-marker-alt\b[^"\']*["\'][^>]*>\s*<\/i>\s*<a\b[^>]*>).*?(<\/a>)/is',
        function ($matches) use ($address) {
            return $matches[1] . ' ' . cm_escape($address) . $matches[2];
        },
        $html,
        1
    );

    $html = preg_replace_callback(
        '/(<div\b[^>]*class=["\'][^"\']*\btop-info\b[^"\']*["\'][^>]*>.*?<small\b[^>]*>\s*<i\b[^>]*class=["\'][^"\']*\bfa-envelope\b[^"\']*["\'][^>]*>\s*<\/i>\s*<a\b[^>]*>).*?(<\/a>)/is',
        function ($matches) use ($email) {
            return $matches[1] . cm_escape($email) . $matches[2];
        },
        $html,
        1
    );

    return $html;
}


function cm_update_header_contact_details($html, $phone)
{
    /*
     * The template header has two separate parts:
     * 1. #phone-tada -> icon only
     * 2. "Have any questions?" -> Free: phone number
     *
     * Older versions of the CMS accidentally wrote the phone into
     * the icon anchor. Remove that accidental text and then write
     * the phone only into the intended "Free:" span.
     */

    $html = preg_replace_callback(
        '/(<div\b[^>]*id=["\']phone-tada["\'][^>]*>.*?<a\b[^>]*>\s*<i\b[^>]*class=["\'][^"\']*\\bfa-phone-alt\\b[^"\']*["\'][^>]*>\s*<\/i>).*?(<\/a>)/is',
        function ($matches) {
            return $matches[1] . $matches[2];
        },
        $html,
        1
    );

    /* Write the current phone into the actual header contact block. */
    $html = preg_replace_callback(
        '/(<div\b[^>]*class=["\'][^"\']*\\bd-flex\\s+flex-column\\s+pe-3\\s+border-end\\s+border-primary\\b[^"\']*["\'][^>]*>\s*<span\b[^>]*>\s*Have\s+any\s+questions\?\s*<\/span>\s*<a\b[^>]*>\s*<span\b[^>]*class=["\'][^"\']*\\btext-secondary\\b[^"\']*["\'][^>]*>).*?(<\/span>)/is',
        function ($matches) use ($phone) {
            return $matches[1] . 'Free: ' . cm_escape($phone) . $matches[2];
        },
        $html,
        1,
        $headerPhoneCount
    );

    return $html;
}


function cm_update_footer_contact_details($html, $address, $email, $phone)
{
    $footerStartMarker = '<!-- Footer Start -->';
    $footerEndMarker   = '<!-- Footer End -->';

    $footerStart = strpos($html, $footerStartMarker);
    if ($footerStart === false) {
        return $html;
    }

    $footerEnd = strpos($html, $footerEndMarker, $footerStart + strlen($footerStartMarker));
    if ($footerEnd === false) {
        return $html;
    }

    $footer = substr($html, $footerStart, $footerEnd + strlen($footerEndMarker) - $footerStart);

    $footer = preg_replace_callback(
        '/(<a\b[^>]*class=["\'][^"\']*\\btext-body\\b[^"\']*["\'][^>]*>\s*<i\b[^>]*class=["\'][^"\']*\\bfa-map-marker-alt\\b[^"\']*["\'][^>]*>\s*<\/i>).*?(<\/a>)/is',
        function ($matches) use ($address) {
            return $matches[1] . ' ' . cm_escape($address) . $matches[2];
        },
        $footer,
        1
    );

    $footer = preg_replace_callback(
        '/(<a\b[^>]*class=["\'][^"\']*\\btext-body\\b[^"\']*["\'][^>]*>\s*<i\b[^>]*class=["\'][^"\']*\\bfa-phone-alt\\b[^"\']*["\'][^>]*>\s*<\/i>).*?(<\/a>)/is',
        function ($matches) use ($phone) {
            return $matches[1] . ' ' . cm_escape($phone) . $matches[2];
        },
        $footer,
        1
    );

    $footer = preg_replace_callback(
        '/(<a\b[^>]*class=["\'][^"\']*\\btext-body\\b[^"\']*["\'][^>]*>\s*<i\b[^>]*class=["\'][^"\']*\\bfa-envelope\\b[^"\']*["\'][^>]*>\s*<\/i>).*?(<\/a>)/is',
        function ($matches) use ($email) {
            return $matches[1] . ' ' . cm_escape($email) . $matches[2];
        },
        $footer,
        1
    );

    return substr($html, 0, $footerStart)
        . $footer
        . substr($html, $footerEnd + strlen($footerEndMarker));
}

function cm_get_contact_sync_files()
{
    /*
     * Synchronize contact details on every normal website page.
     * Only the actual named pages are included; backup/copy HTML files
     * are intentionally excluded.
     */
    $pageNames = array(
        'index.html',
        'about.html',
        'contact.html',
        'program.html',
        'service.html',
        'event.html',
        'team.html',
        'blog.html',
        'testimonial.html',
        'gallery.html',
        'announcement.html',
        '404.html'
    );

    $files = array();

    foreach ($pageNames as $pageName) {
        $file = SITE_ROOT . DIRECTORY_SEPARATOR . $pageName;

        if (is_file($file)) {
            $files[] = $file;
        }
    }

    $existing = array();

    foreach ($files as $file) {
        if (is_file($file)) {
            $existing[$file] = $file;
        }
    }

    return array_values($existing);
}


function cm_update_contact()
{
    cm_validate_request();

    $address = trim((string) ($_POST['contact_address'] ?? ''));
    $email   = trim((string) ($_POST['contact_email'] ?? ''));
    $phone   = preg_replace('/[^0-9]/', '', (string) ($_POST['contact_phone'] ?? ''));
    $mapLink = trim((string) ($_POST['contact_map_location'] ?? ''));

    if ($address === '') {
        throw new RuntimeException('Address is required.');
    }

    if ($email === '') {
        throw new RuntimeException('Email is required.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please enter a valid email address.');
    }

    if ($phone === '') {
        throw new RuntimeException('Phone number is required.');
    }

    if (!preg_match('/^[1-9][0-9]{9}$/', $phone)) {
        throw new RuntimeException(
            'Phone number must be exactly 10 digits and must not start with 0.'
        );
    }

    if ($mapLink === '') {
        throw new RuntimeException('Google Maps link is required.');
    }

    if (!filter_var($mapLink, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\/(?:www\.)?(?:google\.[^\/]+\/maps|maps\.app\.goo\.gl\/)/i', $mapLink)) {
        throw new RuntimeException('Please enter a valid Google Maps link.');
    }

    if (mb_strlen($mapLink) > 2000) {
        throw new RuntimeException('Google Maps link must not exceed 2000 characters.');
    }

    $html = cm_read_file(CONTACT_FILE);

    $section = cm_get_section(
        $html,
        '<!-- CONTACT-MANAGED-START -->',
        '<!-- CONTACT-MANAGED-END -->'
    );

    $newSection = preg_replace_callback(
        '/(<p\b[^>]*data-contact-field=["\']address["\'][^>]*>).*?(<\/p>)/is',
        function ($matches) use ($address) {
            return $matches[1] . cm_escape($address) . $matches[2];
        },
        $section,
        1,
        $addressCount
    );

    if ($newSection === null || $addressCount !== 1) {
        throw new RuntimeException('Address field could not be found in contact.html.');
    }
    $section = $newSection;

    $newSection = preg_replace_callback(
        '/(<p\b[^>]*data-contact-field=["\']email["\'][^>]*>).*?(<\/p>)/is',
        function ($matches) use ($email) {
            return $matches[1] . cm_escape($email) . $matches[2];
        },
        $section,
        1,
        $emailCount
    );

    if ($newSection === null || $emailCount !== 1) {
        throw new RuntimeException('Email field could not be found in contact.html.');
    }
    $section = $newSection;

    /*
     * Repair both the normal phone <p> and the malformed legacy
     * form where an old save left only "123456789</p>" behind.
     */
    $newSection = preg_replace_callback(
        '/(<h4\b[^>]*>\s*Telephone\s*<\/h4>\s*)(?:<p\b[^>]*data-contact-field=["\']phone["\'][^>]*>.*?<\/p>|[^<]*<\/p>)/is',
        function ($matches) use ($phone) {
            return $matches[1]
                . '<p class="mb-2" data-contact-field="phone">'
                . cm_escape($phone)
                . '</p>';
        },
        $section,
        1,
        $phoneCount
    );

    if ($newSection === null || $phoneCount !== 1) {
        throw new RuntimeException('Telephone field could not be repaired/found in contact.html.');
    }
    $section = $newSection;

    $newContactHtml = cm_replace_section(
        $html,
        '<!-- CONTACT-MANAGED-START -->',
        '<!-- CONTACT-MANAGED-END -->',
        $section
    );

    /*
     * ---------------------------------------------------------
     * UPDATE GOOGLE MAP LINK
     * ---------------------------------------------------------
     * The human-readable address remains separate. The Location field
     * stores the Google Maps URL in dedicated markers.
     */
    $mapLinkStartMarker = '<!-- CONTACT-MAP-LINK-START -->';
    $mapLinkEndMarker   = '<!-- CONTACT-MAP-LINK-END -->';
    $escapedMapLink = cm_escape($mapLink);
    $hiddenMapLink = '<span id="contact-map-link-value" style="display:none">' . $mapLinkStartMarker . $escapedMapLink . $mapLinkEndMarker . '</span>';

    if (strpos($newContactHtml, $mapLinkStartMarker) !== false && strpos($newContactHtml, $mapLinkEndMarker) !== false) {
        $newContactHtml = preg_replace(
            '/<span\b[^>]*\bid=["\']contact-map-link-value["\'][^>]*>\s*<!-- CONTACT-MAP-LINK-START -->.*?<!-- CONTACT-MAP-LINK-END -->\s*<\/span>/is',
            $hiddenMapLink,
            $newContactHtml,
            1
        );
    } else {
        $newContactHtml = preg_replace(
            '/(<div class="border border-primary h-100 rounded">)/i',
            '$1\n' . $hiddenMapLink,
            $newContactHtml,
            1
        );
    }

    if ($newContactHtml === null) {
        throw new RuntimeException('Google Maps link could not be updated.');
    }

    // Keep the Google Maps button synchronized with the saved link.
    $newContactHtml = preg_replace_callback(
        '/<a\b[^>]*\bid=["\']contact-map-button["\'][^>]*>.*?<\/a>/is',
        function ($matches) use ($mapLink) {
            $href = cm_escape($mapLink !== '' ? $mapLink : '#');
            $style = $mapLink !== '' ? '' : ' style="display:none;"';
            return '<a id="contact-map-button" href="' . $href . '" target="_blank" rel="noopener noreferrer" class="btn btn-primary px-4 py-2"' . $style . '>
                                <i class="fas fa-map-marker-alt me-2"></i>Open in Google Maps
                            </a>';
        },
        $newContactHtml,
        1,
        $mapButtonCount
    );

    /*
     * Page-by-page rollout:
     * All normal website pages are synchronized.
     * Duplicate/copy HTML files are intentionally excluded.
     */
    $htmlFiles = cm_get_contact_sync_files();

    $changes = array();

    foreach ($htmlFiles as $htmlFile) {
        if (!is_file($htmlFile)) {
            continue;
        }

        $pageHtml = cm_read_file($htmlFile);

        if (realpath($htmlFile) === realpath(CONTACT_FILE)) {
            $pageHtml = $newContactHtml;
        }

        /* Keep topbar, header and footer contact details synchronized. */
        $pageHtml = cm_update_topbar_contact_details(
            $pageHtml,
            $address,
            $email
        );

        $pageHtml = cm_update_header_contact_details(
            $pageHtml,
            $phone
        );

        $updatedPageHtml = cm_update_footer_contact_details(
            $pageHtml,
            $address,
            $email,
            $phone
        );

        if ($updatedPageHtml !== $pageHtml) {
            $changes[$htmlFile] = $updatedPageHtml;
        }
    }

    if (!isset($changes[CONTACT_FILE])) {
        $changes[CONTACT_FILE] = $newContactHtml;
    }

    cm_save_multiple_files($changes);

    setFlash(
        'success',
        'Contact details updated successfully on all website pages.'
    );

    return true;
}


/* =========================================================
   PARENT REVIEWS / TESTIMONIALS
   ========================================================= */

function cm_get_reviews()
{
    $file = defined('REVIEWS_FILE') ? REVIEWS_FILE : (SITE_ROOT . '/reviews.json');
    if (!is_file($file)) {
        return array();
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    if (!is_array($decoded)) {
        return array();
    }

    $reviews = array();
    foreach ($decoded as $review) {
        if (!is_array($review)) continue;
        $reviews[] = array(
            'id' => (string)($review['id'] ?? ''),
            'parent_name' => trim((string)($review['parent_name'] ?? '')),
            'profession' => trim((string)($review['profession'] ?? 'Parent')),
            'rating' => max(1, min(5, (int)($review['rating'] ?? 5))),
            'review' => trim((string)($review['review'] ?? '')),
            'photo' => trim((string)($review['photo'] ?? '')),
            'status' => in_array(($review['status'] ?? 'pending'), array('pending','approved','rejected'), true) ? $review['status'] : 'pending',
            'submitted_at' => (string)($review['submitted_at'] ?? '')
        );
    }
    return $reviews;
}

function cm_review_signature($review)
{
    return strtolower(trim(
        preg_replace('/\s+/', ' ', (string)($review['parent_name'] ?? '')) . '|' .
        preg_replace('/\s+/', ' ', (string)($review['profession'] ?? 'Parent')) . '|' .
        (string)((int)($review['rating'] ?? 0)) . '|' .
        preg_replace('/\s+/', ' ', (string)($review['review'] ?? ''))
    ));
}

function cm_dedupe_reviews($reviews)
{
    $clean = array();
    $seen = array();

    foreach ($reviews as $review) {
        if (!is_array($review)) continue;

        // Keep rejected reviews available for audit/re-submission.
        if (($review['status'] ?? '') === 'rejected') {
            $clean[] = $review;
            continue;
        }

        $signature = cm_review_signature($review);
        if ($signature !== '' && isset($seen[$signature])) {
            continue;
        }
        if ($signature !== '') {
            $seen[$signature] = true;
        }
        $clean[] = $review;
    }

    return array_values($clean);
}

function cm_save_reviews($reviews)
{
    $file = defined('REVIEWS_FILE') ? REVIEWS_FILE : (SITE_ROOT . '/reviews.json');
    $reviews = cm_dedupe_reviews($reviews);
    $json = json_encode(array_values($reviews), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    if (file_put_contents($file, $json, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save parent reviews.');
    }
}

function cm_find_review_index($reviews, $id)
{
    foreach ($reviews as $i => $review) {
        if (($review['id'] ?? '') === $id) return $i;
    }
    return -1;
}

function cm_render_review_card($review, $delay='0.1')
{
    $reviewId = cm_escape($review['id'] ?? '');
    $name = cm_escape($review['parent_name']);
    $profession = cm_escape($review['profession'] ?: 'Parent');
    $text = cm_escape($review['review']);
    $photo = !empty($review['photo']) ? cm_escape(cm_normalize_image_path($review['photo'])) : 'img/testimonial-2.jpg';
    $rating = max(1, min(5, (int)$review['rating']));
    $stars = str_repeat('<i class="fas fa-star text-primary"></i>', $rating);
    $stars .= str_repeat('<i class="far fa-star text-primary"></i>', 5 - $rating);
    return <<<HTML
                    <div class="testimonial-item img-border-radius bg-light border border-primary p-4 wow fadeIn" data-review-id="{$reviewId}" data-wow-delay="{$delay}s">
                        <div class="p-4 position-relative">
                            <i class="fa fa-quote-right fa-2x text-primary position-absolute" style="top: 15px; right: 15px;"></i>
                            <div class="d-flex align-items-center">
                                <div class="border border-primary bg-white rounded-circle" style="flex:0 0 auto;">
                                    <img src="{$photo}" class="rounded-circle p-2" style="width: 80px; height: 80px; border-style: dotted; border-color: var(--bs-primary); object-fit: cover;" alt="{$name}">
                                </div>
                                <div class="ms-4">
                                    <h4 class="text-dark">{$name}</h4>
                                    <p class="m-0 pb-3">{$profession}</p>
                                    <div class="d-flex pe-5">{$stars}</div>
                                </div>
                            </div>
                            <div class="border-top border-primary mt-4 pt-3">
                                <p class="mb-0">{$text}</p>
                            </div>
                        </div>
                    </div>
HTML;
}

function cm_render_approved_reviews()
{
    $reviews = array_values(array_filter(cm_get_reviews(), function($r){ return ($r['status'] ?? '') === 'approved'; }));

    // Prevent duplicate cards even when old duplicate records exist.
    // Photo and record ID are intentionally ignored so the same review cannot
    // appear twice just because it received a different upload/file name.
    $unique = array();
    $seen = array();
    foreach ($reviews as $review) {
        $signature = cm_review_signature($review);
        if ($signature !== '' && isset($seen[$signature])) continue;
        if ($signature !== '') $seen[$signature] = true;
        $unique[] = $review;
    }
    $reviews = $unique;

    if (!$reviews) {
        return <<<HTML
                    <div class="testimonial-item img-border-radius bg-light border border-primary p-4">
                        <div class="p-4 text-center">
                            <i class="fa fa-comments fa-3x text-primary mb-3"></i>
                            <h4 class="text-dark">Be the first parent to share your experience</h4>
                            <p class="mb-3">Your feedback helps other parents understand our school and services.</p>
                            <a href="review.php" class="btn btn-primary rounded-pill px-4">Submit Your Review</a>
                        </div>
                    </div>
HTML;
    }
    $html='';
    foreach($reviews as $i=>$review){ $html .= cm_render_review_card($review, number_format(0.1 + (($i%4)*0.1),1,'.','')); }
    return $html;
}

function cm_sync_reviews_to_pages()
{
    $block = '<!-- REVIEWS-MANAGED-START -->' . "\n" . cm_render_approved_reviews() . "\n" . '<!-- REVIEWS-MANAGED-END -->';
    $changes=array();
    foreach(array(INDEX_FILE, TESTIMONIAL_FILE) as $file){
        if(!defined('TESTIMONIAL_FILE') && basename($file)==='testimonial.html') continue;
        if(!is_file($file)) continue;
        $html=cm_read_file($file);
        $start='<!-- REVIEWS-MANAGED-START -->'; $end='<!-- REVIEWS-MANAGED-END -->';
        if(strpos($html,$start)!==false && strpos($html,$end)!==false){
            $html=cm_replace_section($html,$start,$end,cm_render_approved_reviews());
        }
        $changes[$file]=$html;
    }
    if($changes) cm_save_multiple_files($changes);
}

function cm_add_public_review()
{
    cm_validate_request();
    $honeypot = trim((string)($_POST['website'] ?? ''));
    if($honeypot !== '') throw new RuntimeException('Unable to submit this review.');

    $name = cm_required($_POST['parent_name'] ?? '', 'Parent name');
    $profession = trim((string)($_POST['profession'] ?? 'Parent'));
    $profession = preg_replace('/\s+/', ' ', $profession);
    $rating=(int)($_POST['rating'] ?? 0);
    if($rating < 1 || $rating > 5) throw new RuntimeException('Please select a rating from 1 to 5 stars.');
    $review = cm_required($_POST['review_text'] ?? '', 'Review');
    if(mb_strlen($review) > 1000) throw new RuntimeException('Review must not exceed 1000 characters.');

    $photo='';
    if(isset($_FILES['review_photo']) && is_array($_FILES['review_photo']) && ($_FILES['review_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE){
        $photoPath=uploadImage($_FILES['review_photo'],'review_');
        $photo=cm_normalize_image_path($photoPath);
    }

    $reviews=cm_get_reviews();

    // Do not create another identical review accidentally (double submit,
    // browser retry, or repeated form submission).
    $newSignature = cm_review_signature(array(
        'parent_name' => $name,
        'profession' => $profession !== '' ? $profession : 'Parent',
        'rating' => $rating,
        'review' => $review
    ));
    foreach ($reviews as $existingReview) {
        if (($existingReview['status'] ?? '') === 'rejected') continue;
        if ($newSignature !== '' && $newSignature === cm_review_signature($existingReview)) {
            throw new RuntimeException('This review has already been submitted. It is awaiting approval or has already been published.');
        }
    }

    $reviews[]=array(
        'id'=>'review-'.bin2hex(random_bytes(6)),
        'parent_name'=>$name,
        'profession'=>$profession !== '' ? $profession : 'Parent',
        'rating'=>$rating,
        'review'=>$review,
        'photo'=>$photo,
        'status'=>'pending',
        'submitted_at'=>date('c')
    );
    cm_save_reviews($reviews);
    setFlash('success','Thank you. Your review has been submitted and is awaiting approval.');
}

function cm_update_review_status($status)
{
    cm_validate_request();
    $id=cm_required($_POST['review_id'] ?? '', 'Review ID');
    if(!in_array($status,array('approved','rejected'),true)) throw new RuntimeException('Invalid review status.');
    $reviews=cm_get_reviews(); $idx=cm_find_review_index($reviews,$id);
    if($idx<0) throw new RuntimeException('Review not found.');
    $reviews[$idx]['status']=$status;
    cm_save_reviews($reviews);
    cm_sync_reviews_to_pages();
}

function cm_delete_review()
{
    cm_validate_request();
    $id=cm_required($_POST['review_id'] ?? '', 'Review ID');
    $reviews=cm_get_reviews(); $idx=cm_find_review_index($reviews,$id);
    if($idx<0) throw new RuntimeException('Review not found.');
    $photo=$reviews[$idx]['photo'] ?? '';
    array_splice($reviews,$idx,1);
    cm_save_reviews($reviews);
    if($photo) cm_delete_image_if_managed($photo,'review');
    cm_sync_reviews_to_pages();
}


function cm_process_action()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return null;
    }


    cm_validate_request();


    $action =
        $_POST['action'] ?? '';


    try {

        switch ($action) {

            case 'add_teacher':

                cm_add_teacher();

                return array(
                    'type' => 'success',
                    'message' =>
                        'Teacher added successfully.'
                );


            case 'delete_teacher':

                cm_delete_teacher();

                return array(
                    'type' => 'success',
                    'message' =>
                        'Teacher deleted successfully.'
                );


            case 'add_event':

                cm_add_event();

                return array(
                    'type' => 'success',
                    'message' =>
                        'Event added successfully.'
                );


            case 'delete_event':

                cm_delete_event();

                return array(
                    'type' => 'success',
                    'message' =>
                        'Event deleted successfully.'
                );


            case 'update_hero_image':
            case 'add_hero_image':

                cm_add_hero_image();

                return array(
                    'type' => 'success',
                    'message' => 'Hero image added successfully.'
                );


            case 'delete_hero_image':

                cm_delete_hero_image($_POST['hero_image_path'] ?? '');

                return array(
                    'type' => 'success',
                    'message' => 'Hero image deleted successfully.'
                );


            case 'update_about':

                cm_save_about($_POST);

                return array(
                    'type' => 'success',
                    'message' => 'About Us section updated successfully.'
                );


            case 'update_theme':

                cm_update_theme();

                return array(
                    'type' => 'success',
                    'message' => 'Website colour theme updated successfully.'
                );


            case 'reset_theme':

                cm_reset_theme();

                return array(
                    'type' => 'success',
                    'message' => 'Website colour theme has been restored to the default theme.'
                );


            case 'add_gallery_section':

                cm_add_gallery_section();

                return array(
                    'type' => 'success',
                    'message' => 'Gallery section added successfully.'
                );


            case 'update_gallery_section':

                cm_update_gallery_section();

                return array(
                    'type' => 'success',
                    'message' => 'Gallery section updated successfully.'
                );


            case 'delete_gallery_section':

                cm_delete_gallery_section();

                return array(
                    'type' => 'success',
                    'message' => 'Gallery section and its images deleted successfully.'
                );


            case 'add_gallery_images':

                $galleryCount = cm_add_gallery_images();

                return array(
                    'type' => 'success',
                    'message' => $galleryCount . ' gallery image(s) added successfully.'
                );


            case 'delete_gallery_image':

                cm_delete_gallery_image();

                return array(
                    'type' => 'success',
                    'message' => 'Gallery image deleted successfully.'
                );


            case 'add_announcement':

                cm_add_announcement();

                return array(
                    'type' => 'success',
                    'message' => 'Announcement added successfully.'
                );


            case 'update_announcement':

                cm_update_announcement();

                return array(
                    'type' => 'success',
                    'message' => 'Announcement updated successfully.'
                );


            case 'delete_announcement':

                cm_delete_announcement();

                return array(
                    'type' => 'success',
                    'message' => 'Announcement deleted successfully.'
                );


            case 'update_social_media':

                cm_update_social_media();

                return array(
                    'type' => 'success',
                    'message' => 'Social media handles updated successfully.'
                );


            case 'update_whatsapp':

                cm_update_whatsapp();

                return array(
                    'type' => 'success',
                    'message' =>
                        'WhatsApp number and Contact us buttons updated successfully.'
                );


            case 'update_contact':

                cm_update_contact();

                return array(
                    'type' => 'success',
                    'message' =>
                        'Contact details updated successfully.'
                );


            case 'add_program':

                cm_add_program();

                return array(
                    'type' => 'success',
                    'message' => 'Program added successfully.'
                );


            case 'delete_program':

                cm_delete_program();

                return array(
                    'type' => 'success',
                    'message' => 'Program deleted successfully.'
                );


            case 'add_birthday':

                cm_add_birthday();

                return array(
                    'type' => 'success',
                    'message' =>
                        'Student birthday added successfully.'
                );


            case 'delete_birthday':

                cm_delete_birthday();

                return array(
                    'type' => 'success',
                    'message' =>
                        'Student birthday deleted successfully.'
                );


            case 'approve_review':

                cm_update_review_status('approved');

                return array(
                    'type' => 'success',
                    'message' => 'Parent review approved and published successfully.'
                );


            case 'reject_review':

                cm_update_review_status('rejected');

                return array(
                    'type' => 'success',
                    'message' => 'Parent review rejected successfully.'
                );


            case 'delete_review':

                cm_delete_review();

                return array(
                    'type' => 'success',
                    'message' => 'Parent review deleted successfully.'
                );


            case 'logout':

                if (function_exists('adminLogout')) {
                    adminLogout();
                }

                return array(
                    'type' => 'success',
                    'message' =>
                        'Logged out successfully.'
                );


            default:

                return array(
                    'type' => 'error',
                    'message' =>
                        'Invalid action.'
                );
        }


    } catch (Throwable $e) {

        return array(
            'type' => 'error',
            'message' =>
                $e->getMessage()
        );
    }
}