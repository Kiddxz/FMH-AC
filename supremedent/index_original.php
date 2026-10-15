<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SupremeDent - Dental Clinic Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>

<body>

    <!-- Public landing page -->
    <div class="landing-page" id="landingPage">
        <header class="site-header">
            <a class="site-brand" href="#home" aria-label="SupremeDent home"><img src="assets/images/logo.png"
                    alt="SupremeDent logo"><span><strong>SupremeDent</strong><small>DENTAL CLINIC</small></span></a>
            <nav class="site-nav" aria-label="Main navigation">
                <a href="#home" class="is-active">Home</a><a href="#about">About Us</a><a
                    href="#services">Services</a><a href="#contact">Contact</a>
            </nav>
            <div class="site-account"><a class="site-login" href="auth/login.php">Log In</a>

                <a class="site-signup" href="auth/signup.php">Sign Up</a>
            </div>
        </header>

        <main>
            <section class="landing-hero" id="home">
                <div class="landing-inner hero-grid">
                    <div class="hero-copy"><span class="eyebrow">Trusted Dentistry, Modern Care</span>
                        <h1>Healthy smiles start with a clinic that has it all together.</h1>
                        <p>SupremeDent brings patients, assistants, and administrators onto one simple platform - book
                            visits, track treatment, and manage your dental health without the back-and-forth.</p>
                        <div class="hero-actions"><a class="landing-primary" href="auth/signup.php">Book
                                Appointment</a><a class="landing-secondary" href="#services">Explore Services</a></div>
                        <div class="hero-stats"><span><strong>15+</strong>Years of Care</span><span><strong>4.9<i
                                        class="fas fa-star" aria-hidden="true"></i></strong>Patient
                                Rating</span><span><strong>20k+</strong>Smiles Treated</span></div>
                    </div>
                    <div class="hero-highlights" aria-label="Clinic benefits">
                        <article><i class="fas fa-tooth" aria-hidden="true"></i>
                            <h2>Patient Care</h2>
                            <p>Secure records, friendly reminders, and treatment history in one place.</p>
                        </article>
                        <article><i class="fas fa-calendar-check" aria-hidden="true"></i>
                            <h2>Easy Booking</h2>
                            <p>Pick a date, pick a time, and you're on the calendar - no phone tag.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="landing-section about-section" id="about">
                <div class="landing-inner"><span class="section-label">About Us</span>
                    <h2>Care that treats the whole person, not just the tooth.</h2>
                    <p class="section-intro">SupremeDent has been the neighborhood's trusted dental home for over a
                        decade, combining gentle, modern dentistry with a management system built to make every visit
                        easier - for patients and our team alike.</p>
                    <div class="value-grid">
                        <article class="value-card"><span><i class="fas fa-bullseye" aria-hidden="true"></i></span>
                            <h3>Our Mission</h3>
                            <p>To deliver accessible, high-quality dental care in a calm, judgment-free environment,
                                supported by technology that respects your time.</p>
                        </article>
                        <article class="value-card"><span><i class="fas fa-eye" aria-hidden="true"></i></span>
                            <h3>Our Vision</h3>
                            <p>A community where every smile gets the attention it deserves, and where managing dental
                                health feels as simple as it should be.</p>
                        </article>
                        <article class="value-card"><span><i class="fas fa-heart" aria-hidden="true"></i></span>
                            <h3>Why Choose Us</h3>
                            <p>Experienced clinicians, transparent pricing, and a digital dashboard that keeps your
                                records, appointments, and reminders in one tidy place.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="landing-section services-section" id="services">
                <div class="landing-inner"><span class="section-label">Services</span>
                    <h2>Everything your smile needs, under one roof.</h2>
                    <p class="section-intro">From routine checkups to restorative work, our team handles it with care
                        and clear communication at every step.</p>
                    <div class="services-grid">
                        <article class="service-card"><i class="fas fa-tooth" aria-hidden="true"></i>
                            <h3>Dental Check-up</h3>
                            <p>Thorough exams to catch issues early and keep your smile on track.</p>
                        </article>
                        <article class="service-card"><i class="fas fa-calendar-alt" aria-hidden="true"></i>
                            <h3>Tooth Extraction</h3>
                            <p>Safe, comfortable removal of damaged or problem teeth when needed.</p>
                        </article>
                        <article class="service-card"><i class="fas fa-tooth" aria-hidden="true"></i>
                            <h3>Teeth Cleaning</h3>
                            <p>Professional cleaning and polishing for a fresher, healthier mouth.</p>
                        </article>
                        <article class="service-card"><i class="fas fa-smile" aria-hidden="true"></i>
                            <h3>Braces Consultation</h3>
                            <p>Personalized guidance on alignment options that fit your goals.</p>
                        </article>
                        <article class="service-card"><i class="fas fa-syringe" aria-hidden="true"></i>
                            <h3>Root Canal</h3>
                            <p>Gentle, modern root canal therapy to relieve pain and save the tooth.</p>
                        </article>
                        <article class="service-card"><i class="fas fa-band-aid" aria-hidden="true"></i>
                            <h3>Dental Filling</h3>
                            <p>Durable, natural-looking fillings to repair cavities and chips.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="landing-section contact-section" id="contact">
                <div class="landing-inner"><span class="section-label">Contact</span>
                    <h2>We'd love to see your smile.</h2>
                    <p class="section-intro">Reach out with questions, or send us a message and our team will get back
                        to you shortly.</p>
                    <div class="contact-grid">
                        <div class="contact-details">
                            <div><i class="fas fa-phone" aria-hidden="true"></i>
                                <p><strong>Call Us</strong><span>+1 (555) 234-7890</span></p>
                            </div>
                            <div><i class="fas fa-envelope" aria-hidden="true"></i>
                                <p><strong>Email</strong><span>hello@supremedent.com</span></p>
                            </div>
                            <div><i class="fas fa-location-dot" aria-hidden="true"></i>
                                <p><strong>Visit Us</strong><span>123 Wellness Avenue, Suite 4, Springfield</span></p>
                            </div>
                            <div><i class="fas fa-clock" aria-hidden="true"></i>
                                <p><strong>Clinic Hours</strong><span>Mon-Fri: 8am-6pm · Sat: 9am-2pm</span></p>
                            </div>
                            <div class="map-placeholder"><i class="fas fa-map-location-dot" aria-hidden="true"></i>Map
                                preview - clinic location</div>
                        </div>
                        <form class="contact-form" id="contactForm">
                            <div><input required aria-label="Full name" placeholder="Full Name"><input required
                                    type="email" aria-label="Email address" placeholder="Email Address"></div><input
                                required aria-label="Contact number" placeholder="Contact Number"><textarea required
                                aria-label="How can we help?" placeholder="How can we help?"></textarea>
                            <p class="landing-form-message" id="contactMessage" role="status"></p><button type="submit"
                                class="landing-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="landing-inner footer-grid">
                <div>
                    <h2>SupremeDent</h2>
                    <p>Modern dental care for every smile, backed by a clinic system built around you.</p>
                    <div class="social-links"><a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><a
                            href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a><a href="#"
                            aria-label="Twitter"><i class="fab fa-twitter"></i></a></div>
                </div>
                <div>
                    <h3>Quick Links</h3><a href="#home">Home</a><a href="#about">About Us</a><a
                        href="#services">Services</a><a href="#contact">Contact</a>
                </div>
                <div>
                    <h3>Contact Info</h3>
                    <p>+1 (555) 234-7890</p>
                    <p>hello@supremedent.com</p>
                    <p>123 Wellness Avenue, Springfield</p>
                </div>
                <div>
                    <h3>Account</h3><a href="auth/login.php">Login</a><a href="auth/signup.php">Sign Up</a>
                </div>
            </div>
        </footer>
    </div>



    <script>
        document.getElementById('contactForm')?.addEventListener('submit', function (event) {
            event.preventDefault();
            const message = document.getElementById('contactMessage');
            this.reset();
            if (message) {
                message.textContent = 'Thanks - your message has been sent to the SupremeDent team.';
            }
        });
    </script>

</body>

</html>