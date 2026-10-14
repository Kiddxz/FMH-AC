<?php
require_once __DIR__ . '/config/security.php';
start_secure_session();
require_once __DIR__ . '/config/database.php';

$settings = clinic_settings($conn);
$contactMessage = '';
$contactType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'contact') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $honeypot = trim($_POST['website'] ?? '');

    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $contactMessage = 'Your session expired. Refresh the page and try again.';
        $contactType = 'error';
    } elseif ($honeypot !== '') {
        $contactMessage = 'Unable to send your message.';
        $contactType = 'error';
    } elseif ($name === '' || mb_strlen($name) > 150 || !valid_email_address($email) || mb_strlen($phone) > 50 || mb_strlen($subject) > 180 || mb_strlen($message) < 5 || mb_strlen($message) > 3000) {
        $contactMessage = 'Please enter your name, a valid email, and a message.';
        $contactType = 'error';
    } elseif (!sd_rate_allow($conn, 'contact:' . client_ip(), 5, 900)) {
        // Stops one visitor from flooding the clinic inbox.
        $contactMessage = 'You have sent several messages already. Please wait a few minutes before sending another.';
        $contactType = 'error';
    } else {
        $st = $conn->prepare('INSERT INTO contact_messages(name,email,phone,subject,message) VALUES(?,?,?,?,?)');
        $st->bind_param('sssss', $name, $email, $phone, $subject, $message);
        if ($st->execute()) {
            $contactMessage = 'Thank you. Your inquiry has been sent to SupremeDent.';
            $contactType = 'success';
        } else {
            $contactMessage = 'Your message could not be sent. Please try again.';
            $contactType = 'error';
        }
        $st->close();
    }
}

$publicServices = [];
$r = $conn->query("SELECT name,description FROM services WHERE is_active=1 ORDER BY id LIMIT 4");
if ($r) while ($x = $r->fetch_assoc()) $publicServices[] = $x;
if (!$publicServices) {
    $publicServices = [
        ['name'=>'Dental Checkup','description'=>'Routine oral examination and consultation to maintain healthy teeth and gums.'],
        ['name'=>'Dental Cleaning','description'=>'Professional cleaning to remove plaque and tartar for a fresher, healthier smile.'],
        ['name'=>'Dental Restoration','description'=>'Restorative procedures such as fillings, crowns, and other treatments planned by the dentist.'],
        ['name'=>'Tooth Extraction','description'=>'Tooth extraction when clinically indicated to relieve pain and prevent further dental issues.'],
    ];
}

$serviceMeta = [
    'Dental Checkup' => [
        'image'=>'dental_checkup.jpg',
        'points'=>['Oral examination and consultation','Early screening for dental concerns','Personalized preventive-care advice'],
        'extra'=>'A dental checkup helps the clinic evaluate your current oral condition and determine whether additional imaging, cleaning, restoration, or treatment planning is needed.'
    ],
    'Dental Cleaning' => [
        'image'=>'dental_cleaning.jpg',
        'points'=>['Plaque and tartar removal','Polishing and oral-hygiene guidance','Helps support gum health'],
        'extra'=>'Professional cleaning supports day-to-day brushing and flossing by removing buildup that is difficult to remove at home.'
    ],
    'Dental Restoration' => [
        'image'=>'dental_restoration.jpg',
        'points'=>['Assessment of damaged teeth','Treatment planning by the dentist','Restoration options based on clinical findings'],
        'extra'=>'Restorative care may include fillings, crowns, or other procedures depending on the dentist’s examination and the condition of the tooth.'
    ],
    'Tooth Extraction' => [
        'image'=>'dental_extraction.jpg',
        'points'=>['Clinical assessment before extraction','Procedure planning and informed consent','Post-procedure care instructions'],
        'extra'=>'Extraction is performed only when clinically appropriate after evaluation by the dentist. Patients can use the portal to report where discomfort is located before the visit.'
    ],
];
$servicePayload = [];
foreach ($publicServices as $service) {
    $meta = $serviceMeta[$service['name']] ?? ['image'=>'service-checkup.jpg','points'=>['Professional dental evaluation','Clinic-managed treatment planning','Digital record documentation'],'extra'=>$service['description']];
    $servicePayload[] = ['title'=>$service['name'],'description'=>$service['description'],'extra'=>$meta['extra'],'points'=>$meta['points']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SupremeDent dental clinic management system for appointments, dental records, treatment plans, and patient care.">
    <title>SupremeDent | Modern Dental Care</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/revamp.css">
    <link rel="stylesheet" href="assets/css/ui_fixes.css">
    <link rel="stylesheet" href="assets/css/revisions_2026_10.css?v=20261002">
    <link rel="stylesheet" href="assets/css/revision2_2026_10.css?v=20261009">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="landing-page">
    <header class="site-header">
        <a class="site-brand" href="#home" aria-label="SupremeDent home">
            <img src="assets/images/logo.png" alt="SupremeDent logo">
            <span><strong>SupremeDent</strong><small>DENTAL CLINIC</small></span>
        </a>
        <nav class="site-nav" aria-label="Main navigation">
            <a href="#home" class="is-active">Home</a><a href="#about">About Us</a><a href="#services">Services</a><a href="#contact">Contact</a>
        </nav>
        <div class="site-account"><a class="site-login" href="auth/login.php">Log In</a><a class="site-signup" href="auth/signup.php">Sign Up</a></div>
    </header>

    <main>
        <section class="landing-hero" id="home">
            <div class="landing-inner hero-grid">
                <div class="hero-copy">
                    <span class="eyebrow"><i class="fa-solid fa-shield-heart"></i> Dental care made simpler</span>
                    <h1>A Quality Smile <span>Creations Dentistry.</span></h1>
                    <p>Treats Patients Smile. Fabricates Customized Dental Restorations, Prosthesis & Orthodontic Appliances. Serves as Station for Great Resolution and Define Radiographic Dental Images/Xray.</p>
                    <div class="hero-actions">
                        <a class="landing-primary" href="auth/signup.php"><i class="fa-solid fa-calendar-plus"></i> Get Started</a>
                        <a class="landing-secondary" href="#about">Learn More <i class="fa-solid fa-arrow-down"></i></a>
                    </div>
                    <div class="hero-trust"><span><i class="fa-solid fa-circle-check"></i> Secure account access</span><span><i class="fa-solid fa-circle-check"></i> Digital dental records</span></div>
                </div>

                <div class="hero-media" aria-label="SupremeDent clinic highlights">
                    <div class="hero-slider" id="heroSlider">
                        <div class="hero-slider-track">
                            <article class="hero-slide is-active"><img src="assets/images/hero-slide-1.jpg" alt="SupremeDent dental clinic treatment room"><div class="hero-slide-overlay"><strong>Modern clinic environment</strong><span>Comfortable care in an organized dental setting.</span></div></article>
                            <article class="hero-slide"><img src="assets/images/hero-slide-2.jpg" alt="SupremeDent before and after smile result"><div class="hero-slide-overlay"><strong>Smile-focused dental care</strong><span>Treatment planning guided by professional dental evaluation.</span></div></article>
                            <article class="hero-slide"><img src="assets/images/hero-slide-3.jpg" alt="SupremeDent dental chair and clinic"><div class="hero-slide-overlay"><strong>SupremeDent Dental Services</strong><span>Monday to Saturday, 9:00 AM to 6:00 PM.</span></div></article>
                        </div>
                        <button class="hero-slider-arrow prev" type="button" aria-label="Previous slide"><i class="fa-solid fa-chevron-left"></i></button>
                        <button class="hero-slider-arrow next" type="button" aria-label="Next slide"><i class="fa-solid fa-chevron-right"></i></button>
                        <div class="hero-slider-dots" aria-label="Choose slide"><button class="hero-slider-dot is-active" aria-label="Slide 1"></button><button class="hero-slider-dot" aria-label="Slide 2"></button><button class="hero-slider-dot" aria-label="Slide 3"></button></div>
                        <div class="hero-slide-count"><span id="heroCurrent">01</span> / 03</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section about-section" id="about">
            <div class="landing-inner">
                <div class="clinic-about-intro about-v2">
                    <span class="section-label">ABOUT</span>
                    <h2>Your Smile, Our Priority</h2>
                    <p>Welcome to SupremeDent Dental Services, a dental clinic located in Las Piñas City, dedicated to helping patients achieve healthier, brighter, and more confident smiles. We believe everyone deserves quality dental care in a comfortable and welcoming environment. Our goal is to provide personalized dental services that prioritize each patient's oral health, comfort, and individual needs.</p>
                </div>
                <div class="value-grid clinic-values about-v2-cards">
                    <article class="value-card"><span class="clinic-card-icon"><i class="fa-solid fa-bullseye"></i></span><h3>Our Mission</h3><p>To provide accessible, patient-centered dental care that promotes oral health, builds confidence, and encourages lifelong healthy dental habits.</p></article>
                    <article class="value-card"><span class="clinic-card-icon"><i class="fa-solid fa-eye"></i></span><h3>Our Vision</h3><p>To become a trusted dental clinic recognized for quality dental services, compassionate patient care, and a commitment to improving the oral health of our community.</p></article>
                    <article class="value-card"><span class="clinic-card-icon"><i class="fa-solid fa-location-dot"></i></span><h3>Visit Our Clinic</h3><p><strong>SupremeDent Dental Services</strong><br>Tia Maria Sarao Building, C.V. Starr Avenue, Pamplona Dos, Las Piñas City, Metro Manila.</p><a class="clinic-map-link" target="_blank" rel="noopener noreferrer" href="https://www.google.com/maps/search/?api=1&amp;query=Tia+Maria+Sarao+Building+CV+Starr+Avenue+Pamplona+Dos+Las+Pinas">Find directions <i class="fa-solid fa-arrow-up-right-from-square"></i></a></article>
                </div>
            </div>
        </section>

        <section class="landing-section services-section" id="services">
            <div class="landing-inner">
                <div class="services-head"><span class="section-label">SERVICES</span><h2>Dental services can be easier to manage.</h2>
                <p class="section-intro">Choose Learn More to see what each service is for and how it fits into the clinic workflow.</p></div>
                <div class="services-grid">
                    <?php foreach ($publicServices as $i => $service): $meta=$serviceMeta[$service['name']] ?? ['image'=>'service-checkup.jpg']; ?>
                    <article class="service-card">
                        <div class="service-photo"><img src="assets/images/<?=e($meta['image'])?>" alt="<?=e($service['name'])?>"></div>
                        <div class="service-body"><span class="service-icon"><i class="fa-solid fa-tooth"></i></span><h3><?=e($service['name'])?></h3><p><?=e($service['description'])?></p><button type="button" class="service-learn" data-service-index="<?=$i?>">Learn More <i class="fa-solid fa-arrow-right"></i></button></div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section contact-section" id="contact">
            <div class="landing-inner">
                <div class="contact-heading-v2"><span class="section-label">CONTACT SUPREMEDENT</span><h2>Questions before your appointment?</h2></div>
                <div class="contact-grid">
                    <div class="contact-details">
                        <div><i class="fa-solid fa-location-dot"></i><p><strong>Clinic Address</strong><span><?=e($settings['clinic_address'] ?: 'Unit 125 Tia Maria Sarao Bldg., C.V. Starr Ave., Pamplona Dos, Las Piñas, Philippines')?></span></p></div>
                        <div><i class="fa-solid fa-phone"></i><p><strong>Contact Number</strong><span><?=e($settings['clinic_phone'] ?: '0998 583 3721')?></span></p></div>
                        <div><i class="fa-solid fa-envelope"></i><p><strong>Email Address</strong><span><?=e($settings['clinic_email'] ?: 'supremedentdentalservices@gmail.com')?></span></p></div>
                        <div><i class="fa-solid fa-clock"></i><p><strong>Clinic Hours</strong><span>Monday - Saturday | 9:00 AM - 6:00 PM</span></p></div>
                    </div>
                    <form class="contact-form" method="post" action="#contact">
                        <input type="hidden" name="form_action" value="contact"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
                        <h3>Send an Inquiry</h3><?php if($contactMessage):?><div class="landing-form-message <?=$contactType==='error'?'is-error':''?>"><?=e($contactMessage)?></div><?php endif;?>
                        <div><input name="name" maxlength="150" placeholder="Your name" required><input type="email" name="email" maxlength="180" placeholder="Email address" required></div>
                        <div><input name="phone" maxlength="50" placeholder="Contact number"><input name="subject" maxlength="180" placeholder="Subject"></div>
                        <textarea name="message" maxlength="3000" placeholder="How can SupremeDent help you?" required></textarea>
                        <button class="landing-primary" type="submit"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="landing-inner footer-grid">
            <div><div class="footer-brand"><img src="assets/images/logo.png" alt="SupremeDent logo"><div><h2><?=e($settings['clinic_name'] ?: 'SupremeDent Dental Services')?></h2><small>DENTAL CLINIC</small></div></div><p>Digital dental care management for appointments, records, treatment, and patient communication.</p><div class="footer-tagline">Healthier smiles • brighter tomorrows</div></div>
            <div><h3>Quick Links</h3><a href="#home"><i class="fa-solid fa-house"></i> Home</a><a href="#about"><i class="fa-solid fa-users"></i> About Us</a><a href="#services"><i class="fa-solid fa-gear"></i> Services</a><a href="#contact"><i class="fa-regular fa-envelope"></i> Contact</a></div>
            <div><h3>Social Media Platform</h3><div class="footer-socials">
                <a class="footer-social" href="https://www.facebook.com/supremedent.dentalservices" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook"></i><span>Facebook</span><i class="fa-solid fa-chevron-right"></i></a>
                <a class="footer-social" href="https://www.instagram.com/supremedent_dentalservices" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-instagram"></i><span>Instagram</span><i class="fa-solid fa-chevron-right"></i></a>
                <a class="footer-social" href="https://www.tiktok.com/@supremedent?is_from_webapp=1&amp;sender_device=pc" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-tiktok"></i><span>TikTok</span><i class="fa-solid fa-chevron-right"></i></a>
            </div></div>
        </div>
        <div class="footer-bottom"><div class="landing-inner">© <?=date('Y')?> SupremeDent. Dental Clinic Management System.</div></div>
    </footer>
</div>

<div class="service-modal" id="serviceModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="serviceModalTitle">
    <div class="service-modal-card clinic-service-modal">
        <div class="service-modal-head"><div><span class="section-label">SUPREMEDENT DENTAL SERVICES</span><h3 id="serviceModalTitle">Dental Service</h3></div><button type="button" class="service-modal-close" aria-label="Close service details"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="service-modal-content"><p id="serviceModalText" class="service-modal-lead"></p><h4>About this service</h4><ul id="serviceModalPoints" class="service-modal-list"></ul><p id="serviceModalExtra"></p><div class="service-modal-actions"><a class="landing-primary" href="auth/signup.php"><i class="fa-solid fa-calendar-check"></i> Book an Appointment</a><a id="serviceAskLink" href="#contact" class="landing-secondary">Contact the Clinic</a></div></div>
    </div>
</div>
<style>
.clinic-about-intro{max-width:850px;margin-bottom:30px}.clinic-about-intro h2{font-size:clamp(30px,4vw,48px);margin:10px 0 18px}.clinic-about-intro p{line-height:1.8;color:#526674;font-size:16px;margin:10px 0}.clinic-values .value-card{min-height:240px}.clinic-card-icon{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:13px;background:#e9f5f2;color:#167b79;margin-bottom:12px}.clinic-map-link{display:inline-flex;gap:8px;align-items:center;color:#167b79;font-weight:700;margin-top:12px}.clinic-service-modal{width:min(560px,calc(100vw - 32px));max-height:min(85vh,740px);overflow-y:auto;border-radius:18px;box-shadow:0 24px 80px #102f3740}.clinic-service-modal .service-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:26px 30px;background:#ecf7f8}.clinic-service-modal .service-modal-close{border:0;border-radius:50%;background:white;color:#26444c;width:38px;height:38px;flex-shrink:0;cursor:pointer;font-size:19px;display:grid;place-items:center;box-shadow:0 2px 9px #102f3715}.clinic-service-modal .service-modal-content{padding:24px 30px 30px}.clinic-service-modal .service-modal-lead{color:#415d68;line-height:1.7}.clinic-service-modal h4{margin:18px 0 8px;font-size:16px}.clinic-service-modal .service-modal-list{padding-left:22px;margin-bottom:16px;line-height:1.9}.clinic-service-modal .service-modal-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:24px}.clinic-service-modal .landing-secondary{border:1px solid #bfd7db;color:#185f69;border-radius:12px;padding:12px 16px;text-decoration:none}
</style>

<script>
const sections=document.querySelectorAll('main section[id]'),navLinks=document.querySelectorAll('.site-nav a');
function updateActiveNav(){let current='home';sections.forEach(s=>{if(window.scrollY>=s.offsetTop-140)current=s.id});navLinks.forEach(a=>a.classList.toggle('is-active',a.getAttribute('href')==='#'+current))}window.addEventListener('scroll',updateActiveNav,{passive:true});updateActiveNav();

const slider=document.getElementById('heroSlider'),slides=[...slider.querySelectorAll('.hero-slide')],dots=[...slider.querySelectorAll('.hero-slider-dot')],counter=document.getElementById('heroCurrent');let slideIndex=0,timer;
function showSlide(i){slideIndex=(i+slides.length)%slides.length;slides.forEach((s,n)=>s.classList.toggle('is-active',n===slideIndex));dots.forEach((d,n)=>d.classList.toggle('is-active',n===slideIndex));counter.textContent=String(slideIndex+1).padStart(2,'0')}
function autoStart(){clearInterval(timer);timer=setInterval(()=>showSlide(slideIndex+1),5000)}
slider.querySelector('.next').addEventListener('click',()=>{showSlide(slideIndex+1);autoStart()});slider.querySelector('.prev').addEventListener('click',()=>{showSlide(slideIndex-1);autoStart()});dots.forEach((d,i)=>d.addEventListener('click',()=>{showSlide(i);autoStart()}));slider.addEventListener('mouseenter',()=>clearInterval(timer));slider.addEventListener('mouseleave',autoStart);autoStart();

const serviceData=<?=json_encode($servicePayload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>,modal=document.getElementById('serviceModal'),modalTitle=document.getElementById('serviceModalTitle'),modalText=document.getElementById('serviceModalText'),modalExtra=document.getElementById('serviceModalExtra'),modalPoints=document.getElementById('serviceModalPoints');
function closeService(){modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true')}
document.querySelectorAll('[data-service-index]').forEach(btn=>btn.addEventListener('click',()=>{const data=serviceData[Number(btn.dataset.serviceIndex)];if(!data)return;modalTitle.textContent=data.title;modalText.textContent=data.description;modalExtra.textContent=data.extra;modalPoints.innerHTML='';data.points.forEach(p=>{const li=document.createElement('li');li.textContent=p;modalPoints.appendChild(li)});modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');modal.querySelector('.service-modal-close').focus()}));modal.querySelector('.service-modal-close').addEventListener('click',closeService);modal.addEventListener('click',e=>{if(e.target===modal)closeService()});document.addEventListener('keydown',e=>{if(e.key==='Escape')closeService()});document.getElementById('serviceAskLink').addEventListener('click',closeService);
</script>
</body>
</html>
