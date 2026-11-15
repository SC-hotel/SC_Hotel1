<!-- Modern Luxury Footer -->
<footer class="premium-footer">
    <div class="container">
        <div class="row g-5">
            <!-- Brand & About -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <img src="<?= SITE_URL ?>assets/img/logo.png" alt="SC Hotel Logo" class="footer-logo">
                    <p class="mt-4">Luxury & comfort in the heart of the city. Since 2016, we’ve been creating unforgettable stays for discerning travelers.</p>
                </div>
                <div class="social-links mt-4">
                    <a href="#" class="social-icon" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-icon" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-heading">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?= SITE_URL ?>">Home</a></li>
                    <li><a href="<?= SITE_URL ?>booking.php">Rooms</a></li>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Contact</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Contact Info</h5>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> <span>123 Luxury Avenue, Downtown City, 10001</span></li>
                    <li><i class="fas fa-phone-alt"></i> <span>+1 (555) 123-4567</span></li>
                    <li><i class="fas fa-envelope"></i> <span>info@schotel.com</span></li>
                    <li><i class="fas fa-clock"></i> <span>24/7 Front Desk</span></li>
                </ul>
            </div>

            <!-- Newsletter & Payment Icons -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-heading">Newsletter</h5>
                <p class="footer-text">Subscribe for exclusive offers and updates.</p>
                <form class="newsletter-form" action="#" method="POST">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Your email address" required>
                        <button class="btn btn-subscribe" type="submit"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </form>
                <div class="mt-4">
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa" title="Visa"></i>
                        <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                        <i class="fab fa-cc-amex" title="American Express"></i>
                        <i class="fab fa-cc-paypal" title="PayPal"></i>
                        <i class="fab fa-cc-discover" title="Discover"></i>
                    </div>
                    <small class="d-block mt-3" style="color: rgba(255,255,255,0.4); font-size: 0.7rem; letter-spacing: 0.1em; text-transform: uppercase;">Secure Payments</small>
                </div>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="footer-copy mb-0">&copy; <?= date('Y') ?> SC Hotel. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                <a href="#" class="back-to-top" id="backToTop">Back to Top <i class="fas fa-arrow-up ms-1"></i></a>
            </div>
        </div>
    </div>
</footer>

<style>
    /* ========== LUXURY FOOTER STYLES ========== */
    .premium-footer {
        background: var(--charcoal, #1a1a1a);
        color: #ffffff;
        padding: 80px 0 30px;
        margin-top: 80px;
        position: relative;
        font-family: var(--font-body, 'Jost', sans-serif);
        border-top: 3px solid var(--gold, #c9a227);
    }
    
    .footer-brand .footer-logo {
        height: 80px;
        width: auto;
        filter: brightness(0) invert(1); /* Makes logo white */
        transition: transform 0.3s ease;
    }
    
    .footer-brand .footer-logo:hover {
        transform: scale(1.02);
    }

    .footer-brand p {
        color: rgba(255,255,255,0.6);
        font-weight: 300;
        font-size: 0.9rem;
        line-height: 1.8;
        margin-top: 20px;
    }

    /* Social Links */
    .social-links {
        display: flex;
        gap: 12px;
    }
    
    .social-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        background: transparent;
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 50%;
        color: rgba(255,255,255,0.7);
        font-size: 0.9rem;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .social-icon:hover {
        background: var(--gold, #c9a227);
        border-color: var(--gold, #c9a227);
        color: var(--charcoal, #1a1a1a);
        transform: translateY(-4px);
    }

    /* Headings & Links */
    .footer-heading {
        font-family: var(--font-display, 'Cormorant Garamond', serif);
        font-size: 1.3rem;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 24px;
        letter-spacing: 0.05em;
    }

    .footer-links, .footer-contact {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 14px;
    }

    .footer-links a {
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        font-weight: 300;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .footer-links a:hover {
        color: var(--gold, #c9a227);
        transform: translateX(5px);
    }

    /* Contact Info */
    .footer-contact li {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 16px;
        color: rgba(255,255,255,0.6);
        font-weight: 300;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .footer-contact li i {
        color: var(--gold, #c9a227);
        font-size: 1rem;
        margin-top: 4px;
    }

    /* Newsletter Form */
    .footer-text {
        color: rgba(255,255,255,0.6);
        font-weight: 300;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }

    .newsletter-form .input-group {
        background: transparent;
        border: 1px solid rgba(255,255,255,0.15);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .newsletter-form .input-group:focus-within {
        border-color: var(--gold, #c9a227);
    }

    .newsletter-form .form-control {
        background: transparent;
        border: none;
        color: #ffffff;
        padding: 14px 18px;
        font-family: var(--font-body, 'Jost', sans-serif);
        font-size: 0.9rem;
    }

    .newsletter-form .form-control::placeholder {
        color: rgba(255,255,255,0.3);
        font-weight: 300;
    }

    .newsletter-form .form-control:focus {
        box-shadow: none;
        background: transparent;
        color: #ffffff;
    }

    .btn-subscribe {
        background: var(--gold, #c9a227);
        border: none;
        color: var(--charcoal, #1a1a1a);
        padding: 0 24px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .btn-subscribe:hover {
        background: var(--gold-light, #e6c85a);
        color: var(--charcoal, #1a1a1a);
    }

    /* Payment Icons */
    .payment-icons {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .payment-icons i {
        font-size: 1.6rem;
        color: rgba(255,255,255,0.4);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .payment-icons i:hover {
        color: var(--gold, #c9a227);
        transform: translateY(-2px);
    }

    /* Divider & Copyright */
    .footer-divider {
        border-color: rgba(255,255,255,0.08);
        margin: 60px 0 30px;
    }

    .footer-copy {
        color: rgba(255,255,255,0.4);
        font-size: 0.8rem;
        font-weight: 300;
        letter-spacing: 0.05em;
    }

    /* Back to Top */
    .back-to-top {
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        font-size: 0.8rem;
        font-weight: 500;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .back-to-top i {
        color: var(--gold, #c9a227);
        transition: transform 0.3s ease;
    }

    .back-to-top:hover {
        color: var(--gold, #c9a227);
    }

    .back-to-top:hover i {
        transform: translateY(-3px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .premium-footer {
            text-align: center;
            padding: 60px 0 30px;
        }
        .social-links {
            justify-content: center;
        }
        .footer-contact li {
            justify-content: center;
        }
        .footer-links li:hover {
            transform: none;
        }
        .footer-links a:hover {
            transform: none;
        }
        .back-to-top {
            margin-top: 15px;
            display: inline-flex;
        }
        .payment-icons {
            justify-content: center;
        }
    }
</style>

<script>
    // Smooth back to top
    document.getElementById('backToTop')?.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>