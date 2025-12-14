// Máquina de escribir
const messages = [
    "¡Bienvenido a la Academia Ampere Maxwell!",
    "Tu futuro empieza hoy.",
    "Prepárate con nosotros para ingresar a la universidad."
];

let messageIndex = 0;
let charIndex = 0;
const typedText = document.getElementById("typed-text");

function type() {
    if (charIndex < messages[messageIndex].length) {
        typedText.textContent += messages[messageIndex].charAt(charIndex);
        charIndex++;
        setTimeout(type, 100);
    } else {
        setTimeout(erase, 3000); // espera antes de borrar
    }
}

function erase() {
    if (charIndex > 0) {
        typedText.textContent = messages[messageIndex].substring(0, charIndex - 1);
        charIndex--;
        setTimeout(erase, 50);
    } else {
        messageIndex = (messageIndex + 1) % messages.length;
        setTimeout(type, 500);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    if (typedText) type();
});


document.addEventListener("DOMContentLoaded", () => {
    const banner = document.getElementById("intro-banner");
    const closeBtn = document.getElementById("close-banner");

    closeBtn.addEventListener("click", () => {
        banner.classList.add("fade-out");
        setTimeout(() => {
            banner.style.display = "none";
        }, 600); // tiempo igual al de la animación
    });
});




window.onload = typeWriter;


document.getElementById('form-contacto').addEventListener('submit', function (e) {
    e.preventDefault();

    // Validación simple
    const nombre = document.getElementById('nombre').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    const mensaje = document.getElementById('mensaje').value.trim();

    if (!nombre || !correo || !telefono || !mensaje) {
        alert('Por favor completa todos los campos.');
        return;
    }

    alert('¡Mensaje enviado correctamente! (Funcionalidad pendiente)');
});

// Slider automático
const slides = document.querySelectorAll(".slide");
let idx = 0;
setInterval(() => {
    slides[idx].classList.remove("active");
    idx = (idx + 1) % slides.length;
    slides[idx].classList.add("active");
}, 5000);





