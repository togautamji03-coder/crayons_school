<?php
ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);
require_once __DIR__ . '/super-admin-config.php';

function sa_csrf(){ if(empty($_SESSION['sa_csrf'])) $_SESSION['sa_csrf']=bin2hex(random_bytes(32)); return $_SESSION['sa_csrf']; }
function sa_logged(){ return !empty($_SESSION['school_super_admin_logged_in']); }
function sa_flash($type,$msg){ $_SESSION['sa_flash']=[$type,$msg]; }
function sa_get_flash(){ $x=$_SESSION['sa_flash']??null; unset($_SESSION['sa_flash']); return $x; }
function sa_e($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    if($action==='login'){
        if(password_verify((string)($_POST['password']??''), SUPER_ADMIN_PASSWORD_HASH)){
            $_SESSION['school_super_admin_logged_in']=true; session_regenerate_id(true); sa_flash('success','Super Admin login successful.');
        } else sa_flash('error','Incorrect Super Admin password.');
        header('Location: super-admin.php'); exit;
    }
    if(!sa_logged()){ header('Location: super-admin.php'); exit; }
    if(!hash_equals(sa_csrf(),(string)($_POST['csrf']??''))){ sa_flash('error','Invalid security token.'); header('Location: super-admin.php'); exit; }
    if($action==='logout'){ unset($_SESSION['school_super_admin_logged_in']); session_regenerate_id(true); header('Location: super-admin.php'); exit; }
    if($action==='save_settings'){
        try { sa_save_settings($_POST); sa_flash('success','School branding settings updated successfully.'); }
        catch(Throwable $e){ sa_flash('error',$e->getMessage()); }
        header('Location: super-admin.php'); exit;
    }
    if($action==='save_content_limits'){
        try { sa_save_content_limits($_POST); sa_flash('success','Website content limits updated successfully.'); }
        catch(Throwable $e){ sa_flash('error',$e->getMessage()); }
        header('Location: super-admin.php'); exit;
    }
    if($action==='clone_website'){
        try {
            $folderName = trim((string)($_POST['folder_name'] ?? ''));
            if($folderName === '') throw new RuntimeException('Folder name is required.');
            if(!preg_match('/^[A-Za-z0-9_-]+$/', $folderName)) {
                throw new RuntimeException('Invalid folder name. Use only letters, numbers, hyphen (-) and underscore (_).');
            }
            $blocked = ['.','..','admin','admin-backups','super-admin'];
            if(in_array(strtolower($folderName), $blocked, true)) {
                throw new RuntimeException('This folder name is not allowed.');
            }

            $source = realpath(__DIR__);
            if($source === false) throw new RuntimeException('Unable to determine website root.');
            $destination = $source . DIRECTORY_SEPARATOR . $folderName;
            if(file_exists($destination)) throw new RuntimeException('A folder with this name already exists.');
            if(!is_writable($source)) throw new RuntimeException('Website root is not writable. Please check folder permissions.');

            if(!mkdir($destination, 0755, true)) {
                throw new RuntimeException('Unable to create the new website folder.');
            }

            $copyItem = function($src, $dst) use (&$copyItem, $destination) {
                if(is_link($src)) {
                    $target = readlink($src);
                    if($target === false || !symlink($target, $dst)) throw new RuntimeException('Unable to copy symbolic link: '.basename($src));
                    return;
                }
                if(is_dir($src)) {
                    if(!is_dir($dst) && !mkdir($dst, 0755, true)) throw new RuntimeException('Unable to create folder: '.basename($dst));
                    $items = scandir($src);
                    if($items === false) throw new RuntimeException('Unable to read folder: '.basename($src));
                    foreach($items as $item) {
                        if($item === '.' || $item === '..') continue;
                        $srcPath = $src . DIRECTORY_SEPARATOR . $item;
                        $dstPath = $dst . DIRECTORY_SEPARATOR . $item;
                        if(realpath($srcPath) !== false && realpath($srcPath) === realpath($destination)) continue;
                        $copyItem($srcPath, $dstPath);
                    }
                    @chmod($dst, fileperms($src) & 0777);
                    return;
                }
                if(!copy($src, $dst)) throw new RuntimeException('Unable to copy file: '.basename($src));
                @chmod($dst, fileperms($src) & 0777);
            };

            try {
                $copyItem($source, $destination);
            } catch(Throwable $copyError) {
                $removeTree = function($dir) use (&$removeTree) {
                    if(!is_dir($dir)) return;
                    $items = scandir($dir) ?: [];
                    foreach($items as $item) {
                        if($item === '.' || $item === '..') continue;
                        $path = $dir . DIRECTORY_SEPARATOR . $item;
                        if(is_link($path)) @unlink($path);
                        elseif(is_dir($path)) $removeTree($path);
                        else @unlink($path);
                    }
                    @rmdir($dir);
                };
                $removeTree($destination);
                throw $copyError;
            }

            $_SESSION['sa_clone_link'] = $folderName . '/';
            sa_flash('success','Website copied successfully into the folder "'.$folderName.'".');
        } catch(Throwable $e) {
            unset($_SESSION['sa_clone_link']);
            sa_flash('error',$e->getMessage());
        }
        header('Location: super-admin.php'); exit;
    }
}

$flash=sa_get_flash();
$cloneLink=$_SESSION['sa_clone_link']??''; unset($_SESSION['sa_clone_link']);
if(!sa_logged()): ?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Super Admin Login</title><style>body{font-family:Arial,sans-serif;background:#f4f6f9;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}.box{background:#fff;width:360px;padding:32px;border-radius:14px;box-shadow:0 10px 35px #0002}h1{margin-top:0}input{width:100%;box-sizing:border-box;padding:12px;margin:8px 0 18px;border:1px solid #ddd;border-radius:8px}button{width:100%;padding:12px;border:0;border-radius:8px;background:#111827;color:#fff;font-weight:700;cursor:pointer}.msg{padding:10px;border-radius:8px;margin-bottom:15px;background:#fee2e2;color:#991b1b}.hint{font-size:13px;color:#666}</style></head><body><div class="box"><h1>Super Admin</h1><?php if($flash): ?><div class="msg"><?=sa_e($flash[1])?></div><?php endif; ?><form method="post"><input type="hidden" name="action" value="login"><label>Password</label><input type="password" name="password" required autocomplete="current-password"><button>Login</button></form><p class="hint">Default setup password: ChangeMe@123. Change it in super-admin-config.php before production.</p></div></body></html>
<?php exit; endif;
$settings=sa_get_settings();
$limits=$settings['content_limits']??['max_hero_images'=>5,'max_gallery_sections'=>10,'max_gallery_images_per_section'=>20];
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Super Admin — School Branding</title><style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f5f7fb;color:#1f2937}.top{background:#111827;color:#fff;padding:16px 28px;display:flex;justify-content:space-between;align-items:center}.top h1{font-size:20px;margin:0}.top form{margin:0}.logout{background:#fff;color:#111827;border:0;padding:8px 14px;border-radius:7px;cursor:pointer}.wrap{max-width:900px;margin:30px auto;padding:0 18px}.card{background:#fff;border-radius:14px;padding:28px;box-shadow:0 5px 22px #00000012;margin-bottom:20px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}@media(max-width:700px){.grid{grid-template-columns:1fr}}label{display:block;font-weight:700;margin-bottom:8px}input[type=text],input[type=file]{width:100%;padding:11px;border:1px solid #d1d5db;border-radius:8px;background:#fff}small{display:block;color:#6b7280;margin-top:6px}.preview{max-width:180px;max-height:100px;display:block;margin-top:12px;border:1px solid #ddd;border-radius:8px;padding:5px;background:#fff}.save{background:#111827;color:#fff;border:0;padding:13px 22px;border-radius:8px;font-weight:700;cursor:pointer}.msg{padding:12px 15px;border-radius:8px;margin-bottom:18px}.success{background:#dcfce7;color:#166534}.error{background:#fee2e2;color:#991b1b}.note{background:#eef2ff;padding:14px;border-radius:9px;color:#3730a3;font-size:14px}
</style></head><body><div class="top"><h1>School Super Admin</h1><form method="post"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?=sa_e(sa_csrf())?>"><button class="logout">Logout</button></form></div><div class="wrap">
<?php if($flash): ?><div class="msg <?=sa_e($flash[0])?>"><?=sa_e($flash[1])?></div><?php endif; ?><div class="card"><h2>School Branding & Director</h2><p class="note">These settings are written directly into the website HTML files. No database is used. Header school-name formatting is controlled by the Bold Words / Phrases and Small Words / Phrases fields.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="save_settings"><input type="hidden" name="csrf" value="<?=sa_e(sa_csrf())?>"><div class="grid"><div><label>School Name</label><input type="text" name="school_name" value="<?=sa_e($settings['school_name'])?>" maxlength="200" required><small>Replaces the current “Baby Memorial School” branding throughout the website.</small></div><div><label>Director Name</label><input type="text" name="director_name" value="<?=sa_e($settings['director_name'])?>" maxlength="150"><small>Updates existing director-name placeholders/managed director blocks.</small></div><div><label>Bold Words / Phrases</label><input type="text" name="bold_words" value="<?=sa_e($settings['bold_words'] ?? '')?>" maxlength="500" placeholder="e.g. MODERN EDUCATIONAL"><small>Enter one or more exact words/phrases, separated by commas. These will appear bold in the animated header name.</small></div><div><label>Small Words / Phrases</label><input type="text" name="small_words" value="<?=sa_e($settings['small_words'] ?? '')?>" maxlength="500" placeholder="e.g. PUBLIC SCHOOL"><small>Enter one or more exact words/phrases, separated by commas. These will appear smaller and start on the second line of the animated header name. This is useful for long school names.</small></div><div><label>School Logo</label><input type="file" name="school_logo" accept="image/png,image/jpeg,image/webp"><small>Upload PNG, JPG/JPEG or WEBP. Existing logo is retained if no file is selected.</small><?php if($settings['logo']): ?><img class="preview" src="<?=sa_e($settings['logo'])?>" alt="Current school logo"><?php endif; ?></div><div><label>Director Photo</label><input type="file" name="director_photo" accept="image/png,image/jpeg,image/webp"><small>Existing photo is retained if no file is selected.</small><?php if($settings['director_photo']): ?><img class="preview" src="<?=sa_e($settings['director_photo'])?>" alt="Current director photo"><?php endif; ?></div></div><br><button class="save">Save School Settings</button></form></div>

<div class="card">
<h2>Website Content Limits</h2>
<p class="note">Set the maximum number of Hero images, Gallery sections and images allowed in each Gallery section. These limits are enforced by the PHP backend as well as the Admin Dashboard UI. Lowering a limit will not delete existing content; it only prevents adding more until the limit is increased.</p>
<form method="post">
<input type="hidden" name="action" value="save_content_limits">
<input type="hidden" name="csrf" value="<?=sa_e(sa_csrf())?>">
<div class="grid">
<div><label>Maximum Hero Images</label><input type="number" name="max_hero_images" value="<?=sa_e($limits['max_hero_images'])?>" min="1" max="10000" required><small>Maximum Hero slider images that Admin can add.</small></div>
<div><label>Maximum Gallery Sections</label><input type="number" name="max_gallery_sections" value="<?=sa_e($limits['max_gallery_sections'])?>" min="1" max="10000" required><small>Maximum number of Gallery sections that Admin can create.</small></div>
<div><label>Maximum Images Per Gallery Section</label><input type="number" name="max_gallery_images_per_section" value="<?=sa_e($limits['max_gallery_images_per_section'])?>" min="1" max="10000" required><small>Maximum images allowed inside each individual Gallery section.</small></div>
</div><br><button class="save">Save Content Limits</button>
</form>
</div>

<div class="card">
<h2>Clone / Copy Complete Website</h2>
<p class="note">Create a new folder in the website root and copy the complete current website into it. This includes PHP, HTML, CSS, JavaScript, images, JSON data, uploads and other website files. No database is used.</p>
<form method="post" onsubmit="return confirm('Create a complete copy of the current website in this new folder?');">
<input type="hidden" name="action" value="clone_website">
<input type="hidden" name="csrf" value="<?=sa_e(sa_csrf())?>">
<label>New Folder Name</label>
<input type="text" name="folder_name" maxlength=100 pattern="[A-Za-z0-9_-]+" placeholder="e.g. school2026" required>
<small>Use only letters, numbers, hyphen (-) and underscore (_). The folder must not already exist.</small>
<br>
<button class="save" type="submit">Create Website Copy</button>
</form>
<?php if($cloneLink): ?>
<div class="msg success" style="margin-top:18px;margin-bottom:0;">Website copy is ready: <a href="<?=sa_e($cloneLink)?>" target="_blank" rel="noopener noreferrer" style="font-weight:700;color:#166534;">Open Cloned Website</a></div>
<?php endif; ?>
</div>

</div></body></html>
