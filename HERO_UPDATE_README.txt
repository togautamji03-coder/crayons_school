Hero Image Admin Update

Admin Dashboard now includes a Hero Image section.

Features:
- Upload a new homepage hero image from Admin Dashboard.
- JPG, JPEG, PNG, WEBP; max 5 MB.
- Any source dimensions/aspect ratio are accepted.
- The homepage hero frame uses background-size: 100% 100% so the uploaded image fills the entire existing hero pane.
- The hero image does not change inner-page .page-header backgrounds.
- Previous CMS-managed hero_ image is deleted after a successful replacement.
- Default img/hero-img.jpg remains protected.
- Current hero image preview is shown in Admin Dashboard.
- Existing theme, logo, director, programs, events and birthday features are retained.

Files changed:
- admin.php
- admin-config.php
- content-manager.php
- css/style.css (normalized hero rule)
