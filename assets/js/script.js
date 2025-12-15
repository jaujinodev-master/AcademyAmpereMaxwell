/**
 * Academia Ampere Maxwell - JavaScript Principal
 * Efectos de scroll, animaciones y funcionalidades
 */

document.addEventListener("DOMContentLoaded", () => {
    // ===================================
    // INTRO BANNER
    // ===================================
    const banner = document.getElementById("intro-banner");
    const closeBtn = document.getElementById("close-banner");

    if (banner && closeBtn) {
        closeBtn.addEventListener("click", () => {
            banner.classList.add("fade-out");
            setTimeout(() => {
                banner.style.display = "none";
            }, 600);
        });

        // Auto-close after 5 seconds
        setTimeout(() => {
            if (banner && !banner.classList.contains("fade-out")) {
                banner.classList.add("fade-out");
                setTimeout(() => {
                    banner.style.display = "none";
                }, 600);
            }
        }, 5000);
    }

    // ===================================
    // HEADER SCROLL EFFECT
    // ===================================
    const header = document.querySelector(".header");
    let lastScroll = 0;

    window.addEventListener("scroll", () => {
        const currentScroll = window.pageYOffset;

        if (header) {
            if (currentScroll > 50) {
                header.classList.add("scrolled");
            } else {
                header.classList.remove("scrolled");
            }
        }

        lastScroll = currentScroll;
    });

    // ===================================
    // MOBILE MENU
    // ===================================
    const hamburger = document.getElementById("hamburger");
    const navLinks = document.querySelector(".nav-links");
    const navItems = document.querySelectorAll(".nav-links a");

    if (hamburger && navLinks) {
        hamburger.addEventListener("click", () => {
            navLinks.classList.toggle("show");
            hamburger.classList.toggle("active");
        });

        // Close menu on link click
        navItems.forEach((link) => {
            link.addEventListener("click", () => {
                navLinks.classList.remove("show");
                hamburger.classList.remove("active");
            });
        });

        // Close on outside click
        document.addEventListener("click", (e) => {
            if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove("show");
                hamburger.classList.remove("active");
            }
        });
    }

    // ===================================
    // TYPEWRITER EFFECT
    // ===================================
    const messages = [
        "¡Bienvenido a la Academia Ampere Maxwell!",
        "Tu futuro empieza hoy.",
        "Prepárate con nosotros para ingresar a la universidad."
    ];

    let messageIndex = 0;
    let charIndex = 0;
    const typedText = document.getElementById("typed-text");

    function type() {
        if (!typedText) return;

        if (charIndex < messages[messageIndex].length) {
            typedText.textContent += messages[messageIndex].charAt(charIndex);
            charIndex++;
            setTimeout(type, 80);
        } else {
            setTimeout(erase, 3000);
        }
    }

    function erase() {
        if (!typedText) return;

        if (charIndex > 0) {
            typedText.textContent = messages[messageIndex].substring(0, charIndex - 1);
            charIndex--;
            setTimeout(erase, 40);
        } else {
            messageIndex = (messageIndex + 1) % messages.length;
            setTimeout(type, 500);
        }
    }

    if (typedText) {
        setTimeout(type, 1000);
    }

    // ===================================
    // HERO SLIDER
    // ===================================
    const slides = document.querySelectorAll(".slide");
    let slideIndex = 0;

    function nextSlide() {
        if (slides.length === 0) return;

        slides[slideIndex].classList.remove("active");
        slideIndex = (slideIndex + 1) % slides.length;
        slides[slideIndex].classList.add("active");
    }

    if (slides.length > 0) {
        setInterval(nextSlide, 5000);
    }

    // ===================================
    // TESTIMONIOS SLIDER
    // ===================================
    const track = document.querySelector(".slider-track");
    const prevBtn = document.querySelector(".slider-btn.prev");
    const nextBtn = document.querySelector(".slider-btn.next");
    const cards = document.querySelectorAll(".testimonio-card");

    let index = 0;

    const updateSlider = () => {
        if (!track || cards.length === 0) return;

        const cardWidth = cards[0].offsetWidth + 30;
        const visibleCards = window.innerWidth < 768 ? 1 : 2;
        const maxIndex = Math.max(0, cards.length - visibleCards);

        if (index < 0) index = 0;
        if (index > maxIndex) index = maxIndex;

        track.style.transform = `translateX(-${index * cardWidth}px)`;
    };

    if (nextBtn) {
        nextBtn.addEventListener("click", () => {
            index++;
            updateSlider();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", () => {
            index--;
            updateSlider();
        });
    }

    window.addEventListener("resize", updateSlider);
    updateSlider();

    // ===================================
    // COUNTDOWN TIMER
    // ===================================
    const countdown = () => {
        // Set countdown to 30 days from now
        const countdownDate = new Date();
        countdownDate.setDate(countdownDate.getDate() + 30);
        const targetTime = countdownDate.getTime();

        const now = new Date().getTime();
        const distance = targetTime - now;

        const countdownEl = document.getElementById("countdown");
        if (!countdownEl) return;

        if (distance <= 0) {
            countdownEl.innerHTML = "<strong>¡Inscripciones abiertas!</strong>";
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const daysEl = document.getElementById("days");
        const hoursEl = document.getElementById("hours");
        const minutesEl = document.getElementById("minutes");
        const secondsEl = document.getElementById("seconds");

        if (daysEl) daysEl.textContent = String(days).padStart(2, "0");
        if (hoursEl) hoursEl.textContent = String(hours).padStart(2, "0");
        if (minutesEl) minutesEl.textContent = String(minutes).padStart(2, "0");
        if (secondsEl) secondsEl.textContent = String(seconds).padStart(2, "0");
    };

    countdown();
    setInterval(countdown, 1000);

    // ===================================
    // SCROLL ANIMATIONS
    // ===================================
    const observerOptions = {
        root: null,
        rootMargin: "0px",
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("revealed");
            }
        });
    }, observerOptions);

    // Observe all cards and sections
    const animatedElements = document.querySelectorAll(
        ".benefit-card, .ciclo-card, .testimonio-card, .cta-feature-card"
    );

    animatedElements.forEach(el => {
        el.classList.add("reveal-up");
        observer.observe(el);
    });

    // ===================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ===================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener("click", function (e) {
            const href = this.getAttribute("href");
            if (href === "#") return;

            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            }
        });
    });

    // ===================================
    // CONTACT FORM (Placeholder)
    // ===================================
    const contactForm = document.querySelector(".contact-form form");
    if (contactForm) {
        contactForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const nombre = document.getElementById("nombre")?.value.trim();
            const email = document.getElementById("email")?.value.trim();
            const mensaje = document.getElementById("mensaje")?.value.trim();

            if (!nombre || !email || !mensaje) {
                showNotification("Por favor completa todos los campos.", "error");
                return;
            }

            // Simulate form submission
            showNotification("¡Mensaje enviado correctamente! Te contactaremos pronto.", "success");
            contactForm.reset();
        });
    }

    // ===================================
    // NOTIFICATION HELPER
    // ===================================
    function showNotification(message, type = "info") {
        // Remove existing notification
        const existing = document.querySelector(".notification");
        if (existing) existing.remove();

        const notification = document.createElement("div");
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">&times;</button>
        `;

        // Add styles
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 12px;
            background: ${type === "success" ? "#10B981" : type === "error" ? "#EF4444" : "#3B82F6"};
            color: white;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.animation = "slideOut 0.3s ease forwards";
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    // Add animation keyframes
    const style = document.createElement("style");
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
});

// ===================================
// AOS INITIALIZATION (if loaded)
// ===================================
if (typeof AOS !== "undefined") {
    AOS.init({
        duration: 800,
        once: true,
        offset: 100,
        easing: "ease-out-cubic"
    });
}
