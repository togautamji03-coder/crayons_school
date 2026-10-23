(function () {
    'use strict';
    var defaults = {primary:'#FF4880',secondary:'#1A1A1A',dark:'#1A1A1A',light:'#FFECF2',block:'#FFECF2',section:'#FFFFFF'};
    function validHex(v,f){v=String(v||'').trim();if(/^#[0-9a-fA-F]{6}$/.test(v))return v.toUpperCase();if(/^#[0-9a-fA-F]{3}$/.test(v))return ('#'+v[1]+v[1]+v[2]+v[2]+v[3]+v[3]).toUpperCase();return f;}
    function rgb(h){h=h.replace('#','');return parseInt(h.slice(0,2),16)+', '+parseInt(h.slice(2,4),16)+', '+parseInt(h.slice(4,6),16);}
    function apply(t){t=t||{};var c={primary:validHex(t.primary,defaults.primary),secondary:validHex(t.secondary,defaults.secondary),dark:validHex(t.dark,defaults.dark),light:validHex(t.light,defaults.light),block:validHex(t.block,defaults.block),section:validHex(t.section,defaults.section)};var r=document.documentElement;Object.keys(c).forEach(function(k){r.style.setProperty('--bs-'+k,c[k]);r.style.setProperty('--bs-'+k+'-rgb',rgb(c[k]));});r.style.setProperty('--bs-link-color',c.primary);r.style.setProperty('--bs-link-hover-color',c.primary);var old=document.getElementById('dynamic-school-theme');if(old)old.remove();var st=document.createElement('style');st.id='dynamic-school-theme';st.textContent=':root{--bs-primary:'+c.primary+' !important;--bs-primary-rgb:'+rgb(c.primary)+' !important;--bs-secondary:'+c.secondary+' !important;--bs-secondary-rgb:'+rgb(c.secondary)+' !important;--bs-dark:'+c.dark+' !important;--bs-dark-rgb:'+rgb(c.dark)+' !important;--bs-light:'+c.light+' !important;--bs-light-rgb:'+rgb(c.light)+' !important;--school-block-color:'+c.block+' !important;--school-section-color:'+c.section+' !important;--bs-link-color:'+c.primary+' !important;--bs-link-hover-color:'+c.primary+' !important}.btn-primary,.bg-primary{background-color:'+c.primary+' !important;border-color:'+c.primary+' !important}.btn-primary:hover,.btn-primary:focus,.btn-primary:active{background-color:'+c.secondary+' !important;border-color:'+c.secondary+' !important}.text-primary{color:'+c.primary+' !important}.border-primary{border-color:'+c.primary+' !important}.btn-secondary,.bg-secondary{background-color:'+c.secondary+' !important;border-color:'+c.secondary+' !important}.btn-secondary:hover,.btn-secondary:focus,.btn-secondary:active{background-color:'+c.primary+' !important;border-color:'+c.primary+' !important}.text-secondary{color:'+c.secondary+' !important}.border-secondary{border-color:'+c.secondary+' !important}.bg-dark{background-color:'+c.dark+' !important}.text-dark{color:'+c.dark+' !important}.border-dark{border-color:'+c.dark+' !important}.bg-light{background-color:'+c.light+' !important}.school-theme-block,.service .service-item,.program .program-item .program-text,.program .program-item .program-teacher,.events .events-text,.blog .blog-item,.blog .blog-item .blog-date-comments,.blog .blog-item .blog-content,.team .team-item .team-content,.testimonial .testimonial-item,.about .border.border-primary.p-3.rounded.bg-light,.contact .d-flex.w-100.border.border-primary.p-4.rounded.bg-white,.contact .border.border-primary.h-100.rounded{background-color:'+c.block+' !important}.about,.service,.program,.events,.team,.testimonial,.blog,.contact{background-color:'+c.section+' !important}.text-light{color:'+c.light+' !important}.border-light{border-color:'+c.light+' !important}a:hover,a:focus,.nav-link:hover,.nav-link.active{color:'+c.primary+' !important}';document.head.appendChild(st);}
    function initHeroSlider(settings){
        var slider=document.getElementById('heroSlider');
        if(!slider)return;
        var images=Array.isArray(settings&&settings.hero_images)?settings.hero_images.slice():[];
        if(!images.length && settings && settings.hero_image) images=[settings.hero_image];
        if(!images.length) images=['img/hero-img.jpg'];
        images=images.filter(function(src){return String(src||'').trim()!=='';});
        if(!images.length)return;
        slider.innerHTML='';
        // Build every slide as an absolutely-positioned layer. Inline sizing is intentional
        // so the slider still works if an old/cached stylesheet is present on the server.
        slider.style.position = 'absolute';
        slider.style.inset = '0';
        slider.style.width = '100%';
        slider.style.height = '100%';
        slider.style.overflow = 'hidden';
        slider.style.zIndex = '0';
        images.forEach(function(src,index){
            var img=document.createElement('img');
            img.className='hero-slide'+(index===0?' active':'');
            img.src=String(src);
            img.alt='';
            img.loading=index===0?'eager':'lazy';
            img.decoding='async';
            img.style.position='absolute';
            img.style.top='0';
            img.style.left='0';
            img.style.width='100%';
            img.style.height='100%';
            img.style.display='block';
            img.style.objectFit='fill';
            img.style.objectPosition='center center';
            img.style.opacity=index===0?'1':'0';
            img.style.transition='opacity 0.7s ease-in-out';
            img.style.zIndex='1';
            img.style.pointerEvents='none';
            slider.appendChild(img);
        });
        if(images.length<2)return;
        var current=0;
        window.setInterval(function(){
            var slides=slider.querySelectorAll('.hero-slide');
            if(!slides.length)return;
            slides[current].classList.remove('active');
            slides[current].style.opacity='0';
            current=(current+1)%slides.length;
            slides[current].classList.add('active');
            slides[current].style.opacity='1';
        },3000);
    }
    function load(){fetch('school-settings.json?_theme='+Date.now(),{cache:'no-store'}).then(function(r){if(!r.ok)throw new Error();return r.json();}).then(function(s){apply(s&&s.theme?s.theme:defaults);initHeroSlider(s||{});}).catch(function(){apply(defaults);initHeroSlider({hero_images:['img/hero-img.jpg']});});}
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',load);else load();
})();
