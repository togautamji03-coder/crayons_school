<?php
require_once __DIR__ . '/admin-config.php';
require_once __DIR__ . '/content-manager.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    try {
        cm_add_public_review();
        header('Location: review.php');
        exit;
    } catch (Throwable $e) {
        setFlash('error', $e->getMessage());
        header('Location: review.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Baby Memorial School - Daycare Website Template</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="" name="keywords">
        <meta content="" name="description">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
        <link href="../css2?family=Fredoka:wght@600;700&family=Montserrat:wght@200;400;600&display=swap" rel="stylesheet"> 

        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="../releases/v5.15.4/css/all.css">
        <link href="../npm/bootstrap-icons%401.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Libraries Stylesheet -->
        <link href="lib/animate/animate.min.css" rel="stylesheet">
        <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
        <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

        <!-- Customized Bootstrap Stylesheet -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="css/style.css" rel="stylesheet">

        

    <style id="school-logo-emergency-fix">.school-logo-brand{display:flex!important;align-items:center!important;flex:0 0 auto!important}.school-logo-box{display:flex!important;width:72px!important;height:72px!important;min-width:72px!important;min-height:72px!important;max-width:72px!important;max-height:72px!important;aspect-ratio:1/1!important;overflow:hidden!important;align-items:center!important;justify-content:center!important}.school-logo-header{display:block!important;width:100%!important;height:100%!important;max-width:none!important;max-height:none!important;object-fit:contain!important}.school-name-header{display:block!important;min-width:0!important}</style>        <script src="js/theme.js"></script>
</head>

    <body>

        <!-- Spinner Start -->
        <div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50  d-flex align-items-center justify-content-center">
            <div class="spinner-grow text-primary" role="status"></div>
        </div>
        <!-- Spinner End -->
        <!-- Mobile Spinner Fallback -->
        <script id="mobile-spinner-fallback">
        (function(){
            function hide(){var e=document.getElementById('spinner');if(e){e.classList.remove('show');e.style.opacity='0';e.style.visibility='hidden';e.style.pointerEvents='none';}}
            document.addEventListener('DOMContentLoaded',hide,{once:true});
            window.addEventListener('load',hide,{once:true});
            setTimeout(hide,2500);
        })();
        </script>


        <!-- Navbar start -->
        <div class="container-fluid border-bottom bg-light wow fadeIn" data-wow-delay="0.1s">
            <div class="container topbar bg-primary d-none d-lg-block py-2" style="border-radius: 0 40px">
                <div class="d-flex justify-content-between">
                    <div class="top-info ps-2">
                        <small class="me-3"><i class="fas fa-map-marker-alt me-2 text-secondary"></i> <a href="#" class="text-white">test 2</a></small>
                        <small class="me-3"><i class="fas fa-envelope me-2 text-secondary"></i><a href="#" class="text-white">test2@abc.com</a></small>
                    </div>
                    <div class="top-link pe-2">
                        <a href="" class="btn btn-light btn-sm-square rounded-circle"><i class="fab fa-facebook-f text-secondary"></i></a>
                        <a href="" class="btn btn-light btn-sm-square rounded-circle"><i class="fab fa-twitter text-secondary"></i></a>
                        <a href="" class="btn btn-light btn-sm-square rounded-circle"><i class="fab fa-instagram text-secondary"></i></a>
                        <a href="" class="btn btn-light btn-sm-square rounded-circle me-0"><i class="fab fa-linkedin-in text-secondary"></i></a>
                    </div>
                </div>
            </div>
            <div class="container px-0">
                <nav class="navbar navbar-light navbar-expand-xl py-3">
                    <a href="index.html" class="navbar-brand school-logo-brand" style="display:flex!important;align-items:center!important;justify-content:flex-start!important;flex:0 0 auto!important;width:auto!important;min-width:0!important;max-width:100%!important;gap:12px!important;margin:0 20px 0 0!important;padding:0!important;text-decoration:none!important;overflow:visible!important;" aria-label="Baby Memorial School"><span class="school-logo-box" style="display:flex!important;align-items:center!important;justify-content:center!important;width:72px!important;height:72px!important;min-width:72px!important;min-height:72px!important;max-width:72px!important;max-height:72px!important;flex:0 0 72px!important;aspect-ratio:1/1!important;overflow:hidden!important;background:#fff!important;border-radius:6px!important;"><img src="img/school-logo.svg" alt="Baby Memorial School Logo" class="school-logo-header" style="display:block!important;width:100%!important;height:100%!important;min-width:0!important;min-height:0!important;max-width:none!important;max-height:none!important;object-fit:contain!important;object-position:center center!important;flex:none!important;"></span><span class="school-name-header" style="display:block!important;max-width:300px!important;min-width:0!important;font-size:24px!important;line-height:1.15!important;font-weight:700!important;white-space:normal!important;overflow-wrap:anywhere!important;">Baby Memorial School</span></a>
                    <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars text-primary"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav mx-auto">
                            <a href="index.html" class="nav-item nav-link">Home</a>
                            <a href="about.html" class="nav-item nav-link">About</a>
                            <a href="service.html" class="nav-item nav-link">Services</a>
                            <a href="program.html" class="nav-item nav-link">Programs</a>
                            <a href="event.html" class="nav-item nav-link">Events</a>
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle active" data-bs-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu m-0 bg-secondary rounded-0">
                                    <a href="blog.html" class="dropdown-item">Our Blog</a>
                                    <a href="team.html" class="dropdown-item">Our Team</a>
                                    <a href="testimonial.html" class="dropdown-item active">Testimonial</a>
                                    <a href="review.php" class="dropdown-item">Submit a Review</a>
                                    <a href="404.html" class="dropdown-item">404 Page</a>
                                </div>
                            </div>
                            <a href="contact.html" class="nav-item nav-link">Contact</a>
                        </div>
                        <div class="d-flex me-4">
                            <div id="phone-tada" class="d-flex align-items-center justify-content-center">
                                <a href="" class="position-relative wow tada" data-wow-delay=".9s">
                                    <i class="fa fa-phone-alt text-primary fa-2x me-4"></i></a>
                            </div>
                            <div class="d-flex flex-column pe-3 border-end border-primary">
                                <span class="text-primary">Have any questions?</span>
                                <a href="#"><span class="text-secondary">Free: 3333333333</span></a>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
        <!-- Navbar End -->

        
        <!-- Page Header Start -->
        <div class="container-fluid page-header py-5 wow fadeIn" data-wow-delay="0.1s">
            <div class="container text-center py-5">
                <h1 class="display-2 text-white mb-4">Submit Your Review</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-0">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Pages</a></li>
                        <li class="breadcrumb-item text-white" aria-current="page">Testimonial</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- Page Header End -->


        <!-- Review Submission Start -->
        <div class="container-fluid testimonial py-5">
            <div class="container py-5">
                <div class="mx-auto text-center wow fadeIn" data-wow-delay="0.1s" style="max-width: 760px;">
                    <h4 class="text-primary mb-4 border-bottom border-primary border-2 d-inline-block p-2 title-border-radius">Parents Say About Us</h4>
                    <h1 class="mb-4 display-4">Share Your Experience</h1>
                    <p class="mb-5">We value your feedback. Please share your experience with our school. Your review will be published after approval by the school.</p>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <?php $reviewFlash = getFlash(); ?>
                        <?php if ($reviewFlash): ?>
                            <div class="alert alert-<?= $reviewFlash['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                                <?= cm_escape($reviewFlash['message']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="bg-light border border-primary rounded p-4 p-md-5">
                            <form method="POST" action="review.php" enctype="multipart/form-data">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="submit_review">
                                <div style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold" for="parent_name">Parent Name *</label>
                                        <input class="form-control p-3" type="text" id="parent_name" name="parent_name" maxlength="120" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold" for="profession">Profession / Relationship</label>
                                        <input class="form-control p-3" type="text" id="profession" name="profession" maxlength="120" placeholder="e.g. Mother, Father, Guardian">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold" for="rating">Rating *</label>
                                        <select class="form-select p-3" id="rating" name="rating" required>
                                            <option value="5" selected>★★★★★ — Excellent</option>
                                            <option value="4">★★★★☆ — Very Good</option>
                                            <option value="3">★★★☆☆ — Good</option>
                                            <option value="2">★★☆☆☆ — Fair</option>
                                            <option value="1">★☆☆☆☆ — Needs Improvement</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold" for="review_photo">Your Photo (Optional)</label>
                                        <input class="form-control p-3" type="file" id="review_photo" name="review_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                        <small class="text-muted">JPG, JPEG, PNG or WEBP. Maximum 5 MB.</small>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold" for="review_text">Your Review *</label>
                                        <textarea class="form-control p-3" id="review_text" name="review_text" rows="6" maxlength="1000" required placeholder="Tell us about your experience..."></textarea>
                                        <small class="text-muted">Maximum 1000 characters.</small>
                                    </div>
                                    <div class="col-12 text-center">
                                        <button class="btn btn-primary rounded-pill px-5 py-3" type="submit">Submit Review</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Review Submission End -->


        <!-- Footer Start -->
        <div class="container-fluid footer py-5 wow fadeIn" data-wow-delay="0.1s">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <a href="index.html" class="school-footer-brand" aria-label="Baby Memorial School" style="display:flex!important;align-items:center!important;justify-content:flex-start!important;gap:12px!important;width:max-content!important;max-width:100%!important;margin:0 0 18px 0!important;padding:0!important;text-decoration:none!important;"><span class="school-footer-logo-box" style="display:flex!important;align-items:center!important;justify-content:center!important;width:72px!important;height:72px!important;min-width:72px!important;min-height:72px!important;max-width:72px!important;max-height:72px!important;flex:0 0 72px!important;aspect-ratio:1/1!important;overflow:hidden!important;background:#fff!important;border-radius:6px!important;"><img src="img/school-logo.svg" alt="Baby Memorial School Logo" class="school-footer-logo" style="display:block!important;width:100%!important;height:100%!important;min-width:0!important;min-height:0!important;max-width:none!important;max-height:none!important;object-fit:contain!important;object-position:center center!important;flex:none!important;"></span><span class="school-footer-name" style="display:block!important;min-width:0!important;max-width:260px!important;font-size:22px!important;font-weight:700!important;line-height:1.2!important;color:var(--bs-dark,#333)!important;">Baby Memorial School</span></a>
                            <div class="footer-item">
                            <h2 class="fw-bold mb-3"><span class="text-primary mb-0">Baby Memorial<span class="text-secondary"> School</span></h2>
                            <p class="mb-4">There cursus massa at urnaaculis estieSed aliquamellus vitae ultrs condmentum leo massamollis its estiegittis miristum.</p>
                            <div class="border border-primary p-3 rounded bg-light">
                                <h5 class="mb-3">Newsletter</h5>
                                <div class="position-relative mx-auto border border-primary rounded" style="max-width: 400px;">
                                    <input class="form-control border-0 w-100 py-3 ps-4 pe-5" type="text" placeholder="Your email">
                                    <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2 text-white">SignUp</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="footer-item">
                            <div class="d-flex flex-column p-4 ps-5 text-dark border border-primary" style="border-radius: 50% 20% / 10% 40%;">
                                <p>Monday: 8am to 5pm</p>
                                <p>Tuesday: 8am to 5pm</p>
                                <p>Wednes: 8am to 5pm</p>
                                <p>Thursday: 8am to 5pm</p>
                                <p>Friday: 8am to 5pm</p>
                                <p>Saturday: 8am to 5pm</p>
                                <p class="mb-0">Sunday: Closed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="footer-item">
                            <h4 class="text-primary mb-4 border-bottom border-primary border-2 d-inline-block p-2 title-border-radius">LOCATION</h4>
                            <div class="d-flex flex-column align-items-start">
                                <a href="" class="text-body mb-4"><i class="fa fa-map-marker-alt text-primary me-2"></i> test 3</a>
                                <a href="" class="text-start rounded-0 text-body mb-4"><i class="fa fa-phone-alt text-primary me-2"></i> 3333333333</a>
                                <a href="" class="text-start rounded-0 text-body mb-4"><i class="fas fa-envelope text-primary me-2"></i> test3@abc.com</a>
                                <a href="" class="text-start rounded-0 text-body mb-4"><i class="fa fa-clock text-primary me-2"></i> 26/7 Hours Service</a>
                                <div class="footer-icon d-flex">
                                    <a class="btn btn-primary btn-sm-square me-3 rounded-circle text-white" href=""><i class="fab fa-facebook-f"></i></a>
                                    <a class="btn btn-primary btn-sm-square me-3 rounded-circle text-white" href=""><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="btn btn-primary btn-sm-square me-3 rounded-circle text-white"><i class="fab fa-instagram"></i></a>
                                    <a href="#" class="btn btn-primary btn-sm-square rounded-circle text-white"><i class="fab fa-linkedin-in"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="footer-item">
                            <h4 class="text-primary mb-4 border-bottom border-primary border-2 d-inline-block p-2 title-border-radius">OUR GALLARY</h4>
                            <div class="row g-3">
                                <div class="col-4">
                                    <div class="footer-galary-img rounded-circle border border-primary">
                                        <img src="img/galary-1.jpg" class="img-fluid rounded-circle p-2" alt="">
                                    </div>
                               </div>
                               <div class="col-4">
                                    <div class="footer-galary-img rounded-circle border border-primary">
                                        <img src="img/galary-2.jpg" class="img-fluid rounded-circle p-2" alt="">
                                    </div>
                               </div>
                                <div class="col-4">
                                    <div class="footer-galary-img rounded-circle border border-primary">
                                        <img src="img/galary-3.jpg" class="img-fluid rounded-circle p-2" alt="">
                                    </div>
                               </div>
                                <div class="col-4">
                                    <div class="footer-galary-img rounded-circle border border-primary">
                                        <img src="img/galary-4.jpg" class="img-fluid rounded-circle p-2" alt="">
                                    </div>
                               </div>
                                <div class="col-4">
                                    <div class="footer-galary-img rounded-circle border border-primary">
                                        <img src="img/galary-5.jpg" class="img-fluid rounded-circle p-2" alt="">
                                    </div>
                               </div>
                                <div class="col-4">
                                    <div class="footer-galary-img rounded-circle border border-primary">
                                        <img src="img/galary-6.jpg" class="img-fluid rounded-circle p-2" alt="">
                                    </div>
                               </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->


        <!-- Copyright Start -->
        <div class="container-fluid copyright bg-dark py-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        <span class="text-light"><a href="#"><i class="fas fa-copyright text-light me-2"></i>Your Site Name</a>, All right reserved.</span>
                    </div>
                    <div class="col-md-6 my-auto text-center text-md-end text-white">
                        <!--/*** This template is free as long as you keep the below author’s credit link/attribution link/backlink. ***/-->
                        <!--/*** If you'd like to use the template without the below author’s credit link/attribution link/backlink, ***/-->
                        <!--/*** you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a> Distributed By <a clas="border-bottom" href="https://themewagon.com">ThemeWagon</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Copyright End -->


        <!-- Back to Top -->
        <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   

        
    <!-- JavaScript Libraries -->
    <script src="../ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="../npm/bootstrap%405.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    </body>

</html>