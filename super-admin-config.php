<?php
if(session_status()!==PHP_SESSION_ACTIVE){
 $https=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
 session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$https,'httponly'=>true,'samesite'=>'Strict']); session_start();
}
define('SUPER_ADMIN_PASSWORD_HASH','$2y$12$Un8dYf0yIKbdiOVN1UHWDe41xiy8KxiNz5ICPSqktNYnKtVXglwEi');
define('SA_ROOT',__DIR__); define('SA_SETTINGS_FILE',SA_ROOT.'/school-settings.json'); define('SA_IMAGE_DIR',SA_ROOT.'/img'); define('SA_BACKUP_DIR',SA_ROOT.'/admin-backups');
function sa_defaults(){ return ['school_name'=>'Baby Memorial School','bold_words'=>'','small_words'=>'','logo'=>'img/school-logo.svg','director_photo'=>'','director_name'=>'','content_limits'=>['max_hero_images'=>5,'max_gallery_sections'=>10,'max_gallery_images_per_section'=>20]]; }
function sa_get_settings(){ $d=sa_defaults(); if(is_file(SA_SETTINGS_FILE)){ $x=json_decode((string)file_get_contents(SA_SETTINGS_FILE),true); if(is_array($x)) $d=array_merge($d,$x); } return $d; }
function sa_clean($v){ return trim(preg_replace('/[ \t]+/',' ',str_replace("\0",'',(string)$v))); }
function sa_clean_phrases($v){
    $parts = preg_split('/[,\n]+/', (string)$v);
    $out = [];
    foreach($parts as $part){
        $part = sa_clean($part);
        if($part !== '' && !in_array($part,$out,true)) $out[] = $part;
    }
    return implode(', ', $out);
}
function sa_backup($file){ if(!is_dir(SA_BACKUP_DIR)) @mkdir(SA_BACKUP_DIR,0755,true); if(is_file($file)) @copy($file,SA_BACKUP_DIR.'/superadmin-'.basename($file).'.'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.bak'); }
function sa_write($file,$content){ sa_backup($file); $tmp=$file.'.tmp-'.bin2hex(random_bytes(4)); if(file_put_contents($tmp,$content,LOCK_EX)===false) throw new RuntimeException('Unable to write '.basename($file)); if(!rename($tmp,$file)) throw new RuntimeException('Unable to finalize '.basename($file)); }
function sa_upload($f,$prefix){ if(!isset($f['error'])||$f['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed.'); if($f['size']<=0||$f['size']>5*1024*1024) throw new RuntimeException('Image must be between 1 byte and 5 MB.'); $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION)); if(!in_array($ext,['jpg','jpeg','png','webp'],true)) throw new RuntimeException('Only JPG, JPEG, PNG and WEBP images are allowed.'); if(function_exists('finfo_open')){ $fi=finfo_open(FILEINFO_MIME_TYPE); $mime=finfo_file($fi,$f['tmp_name']); finfo_close($fi); if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)) throw new RuntimeException('Invalid image file.'); } $name=$prefix.bin2hex(random_bytes(8)).'.'.$ext; if(!move_uploaded_file($f['tmp_name'],SA_IMAGE_DIR.'/'.$name)) throw new RuntimeException('Unable to save uploaded image.'); return 'img/'.$name; }
function sa_director_block($settings){
    $name = htmlspecialchars(
        $settings['director_name'] !== '' ? $settings['director_name'] : 'Director',
        ENT_QUOTES,
        'UTF-8'
    );
    $school = htmlspecialchars(
        $settings['school_name'] !== '' ? $settings['school_name'] : 'Baby Memorial School',
        ENT_QUOTES,
        'UTF-8'
    );
    $photo = $settings['director_photo'] !== ''
        ? htmlspecialchars($settings['director_photo'], ENT_QUOTES, 'UTF-8')
        : '';

    if ($photo !== '') {
        $image = '<img src="' . $photo . '" alt="' . $name . '" class="director-message-photo" style="display:block;width:100%;height:100%;object-fit:cover;object-position:center center;">';
    } else {
        $image = '<div class="director-message-photo-placeholder" style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:linear-gradient(135deg,#f8f9fa,#eef1f5);color:var(--bs-primary,#FF4880);font-weight:700;font-size:18px;text-align:center;padding:20px;">Director Photo</div>';
    }

    return <<<HTML
<!-- SUPER-DIRECTOR-MANAGED-START -->
<section class="director-message-section" style="background:#fff;">
    <div class="container-fluid py-5">
        <div class="container py-4">
            <div class="mx-auto text-center mb-5" style="max-width:800px;">
                <h4 class="text-primary mb-3 border-bottom border-primary border-2 d-inline-block p-2 title-border-radius">Director Message</h4>
                <h1 class="display-5 fw-bold text-dark mb-3">A Message From Our Director</h1>
                <p class="text-dark mb-0">A message for our parents and students</p>
            </div>

            <div class="row g-5 align-items-center">
                <div class="col-lg-4 text-center">
                    <div style="width:315px;max-width:100%;height:450px;margin:0 auto;overflow:hidden;border-radius:8px;background:#f8f9fa;">
                        {$image}
                    </div>
                    <div class="pt-3">
                        <h3 class="text-dark mb-1">{$name}</h3>
                        <p class="text-primary mb-0">{$school}</p>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div style="border:1px solid var(--bs-primary,#FF4880);border-radius:10px;padding:42px 48px;background:#fff;position:relative;">
                        <div aria-hidden="true" style="font-size:72px;line-height:.7;color:var(--bs-primary,#FF4880);font-family:Georgia,serif;font-weight:700;margin-bottom:28px;">“</div>
                        <p class="text-dark mb-4" style="font-size:17px;line-height:1.8;">Dear Parents and Students,</p>
                        <p class="text-dark mb-4" style="font-size:17px;line-height:1.8;">It gives me immense pleasure to welcome you to our school family. Education is not merely about academic achievement; it is about developing confidence, character, curiosity and compassion.</p>
                        <p class="text-dark mb-4" style="font-size:17px;line-height:1.8;">We believe that every child has unique potential. With the continued support of our parents, dedicated teachers and enthusiastic students, we can create an environment where every learner feels valued, inspired and empowered to grow.</p>
                        <p class="text-dark mb-4" style="font-size:17px;line-height:1.8;">I encourage our students to remain curious, disciplined and kind, and I invite parents to continue working closely with us in every step of their child's educational journey.</p>
                        <p class="text-dark mb-4" style="font-size:17px;line-height:1.8;">Together, let us nurture responsible, confident and compassionate young citizens for a brighter tomorrow.</p>
                        <p class="text-dark mb-0" style="font-size:17px;line-height:1.8;">Warm regards,<br><strong class="text-primary" style="font-size:20px;">{$name}</strong><br>{$school}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- SUPER-DIRECTOR-MANAGED-END -->
HTML;
}

function sa_replace_branding($old,$new,$settings){
    $files=glob(SA_ROOT.'/*.html')?:[];
    foreach($files as $file){
        $html=file_get_contents($file); if($html===false) continue;

        /*
         * IMPORTANT: School name is used in many places, not only the
         * header/footer.  The previous implementation replaced only the
         * original template name ("Baby Memorial School"), so once the
         * school name had been changed, later Super Admin updates changed
         * the header/footer but left About, Director, cards, page titles,
         * copyright and other page sections unchanged.
         *
         * Always replace the ACTUAL previous school name as well as the
         * legacy template name.  This keeps every static HTML occurrence
         * synchronized with school-settings.json.
         */
        $escapedNew = htmlspecialchars($new, ENT_QUOTES, 'UTF-8');
        if ($old !== '' && strcasecmp($old, $new) !== 0) {
            $html = preg_replace(
                '/'.preg_quote($old, '/').'/i',
                $escapedNew,
                $html
            );
        }
        /* Backward compatibility for pages still containing the original template name. */
        $html = preg_replace(
            '/Baby Memorial School/i',
            $escapedNew,
            $html
        );
        if($settings['logo']) $html=preg_replace('/img\/school-logo\.(?:svg|png|jpe?g|webp)/i',$settings['logo'],$html);
        $headerBrand = '<a href="index.html" class="navbar-brand school-logo-brand" aria-label="'.htmlspecialchars($new,ENT_QUOTES,'UTF-8').'"><span class="school-logo-box"><img src="'.htmlspecialchars($settings['logo'] ?: 'img/school-logo.svg',ENT_QUOTES,'UTF-8').'" alt="'.htmlspecialchars($new,ENT_QUOTES,'UTF-8').' Logo" class="school-logo-header"></span><span class="school-name-header">'.htmlspecialchars($new,ENT_QUOTES,'UTF-8').'</span></a>';
        $html = preg_replace('/<a href="index\.html" class="navbar-brand school-logo-brand".*?<\/a>/is', $headerBrand, $html, 1);
        $footerLogo = htmlspecialchars($settings['logo'] ?: 'img/school-logo.svg',ENT_QUOTES,'UTF-8');
        $footerName = htmlspecialchars($new,ENT_QUOTES,'UTF-8');
        $footerBrand = '<a href="index.html" class="school-footer-brand" aria-label="'.$footerName.'" style="display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:12px!important;width:max-content!important;max-width:100%!important;margin:0 0 18px 0!important;padding:0!important;text-decoration:none!important;"><span class="school-footer-logo-box" style="display:flex!important;align-items:center!important;justify-content:center!important;width:72px!important;height:72px!important;min-width:72px!important;min-height:72px!important;max-width:72px!important;max-height:72px!important;flex:0 0 72px!important;aspect-ratio:1/1!important;overflow:hidden!important;background:#fff!important;border-radius:6px!important;"><img src="'.$footerLogo.'" alt="'.$footerName.' Logo" class="school-footer-logo" style="display:block!important;width:100%!important;height:100%!important;min-width:0!important;min-height:0!important;max-width:none!important;max-height:none!important;object-fit:contain!important;object-position:center center!important;flex:none!important;"></span><span class="school-footer-name" style="display:block!important;min-width:0!important;max-width:260px!important;font-size:22px!important;font-weight:700!important;line-height:1.2!important;color:var(--bs-dark,#333)!important;">'.$footerName.'</span></a>';
        $html = preg_replace('/<a href="index\.html" class="school-footer-brand".*?<\/a>/is', $footerBrand, $html, 1);

        $html=preg_replace('/<!-- SUPER-DIRECTOR-MANAGED-START -->.*?<!-- SUPER-DIRECTOR-MANAGED-END -->/is',sa_director_block($settings),$html);
        if(basename($file)==='about.html' && strpos($html,'<!-- SUPER-DIRECTOR-MANAGED-START -->')===false){
            $marker='<!-- About End -->';
            if(strpos($html,$marker)!==false) $html=str_replace($marker,sa_director_block($settings)."\n\n        ".$marker,$html);
        }
        if(basename($file)==='index.html'){
            if(strpos($html,'<!-- SUPER-DIRECTOR-MANAGED-START -->')===false){
                $marker='<!-- Team Start-->';
                if(strpos($html,$marker)!==false){
                    $html=str_replace($marker,sa_director_block($settings)."\n\n        ".$marker,$html,1);
                }
            }
        }
        sa_write($file,$html);
    }
}
function sa_save_director_profile($post){
    $settings = sa_get_settings();
    $name = sa_clean($post['director_name'] ?? '');

    $settings['director_name'] = $name;

    if (!empty($_FILES['director_photo']['name'])) {
        $settings['director_photo'] = sa_upload($_FILES['director_photo'], 'director_');
    }

    /*
     * Rebuild the managed Director section everywhere without requiring
     * the school-name form to be submitted from the normal admin dashboard.
     */
    sa_replace_branding(
        $settings['school_name'] ?? '',
        $settings['school_name'] ?? '',
        $settings
    );

    sa_write(
        SA_SETTINGS_FILE,
        json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
    );
}
function sa_save_settings($post){ $old=sa_get_settings(); $name=sa_clean($post['school_name']??''); if($name==='') throw new RuntimeException('School name is required.'); $s=$old; $s['school_name']=$name; $s['bold_words']=sa_clean_phrases($post['bold_words']??''); $s['small_words']=sa_clean_phrases($post['small_words']??''); $s['director_name']=sa_clean($post['director_name']??''); if(!empty($_FILES['school_logo']['name'])) $s['logo']=sa_upload($_FILES['school_logo'],'school_logo_'); if(!empty($_FILES['director_photo']['name'])) $s['director_photo']=sa_upload($_FILES['director_photo'],'director_'); sa_replace_branding($old['school_name'],$name,$s); sa_write(SA_SETTINGS_FILE,json_encode($s,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL); }
function sa_save_content_limits($post){
    $fields=['max_hero_images'=>'Maximum Hero Images','max_gallery_sections'=>'Maximum Gallery Sections','max_gallery_images_per_section'=>'Maximum Images Per Gallery Section'];
    $limits=[];
    foreach($fields as $key=>$label){
        $value=filter_var($post[$key]??null,FILTER_VALIDATE_INT);
        if($value===false || $value < 1 || $value > 10000) throw new RuntimeException($label.' must be a whole number between 1 and 10000.');
        $limits[$key]=(int)$value;
    }
    $settings=sa_get_settings();
    $settings['content_limits']=$limits;
    sa_write(SA_SETTINGS_FILE,json_encode($settings,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);
}
