// CampusConnect Practical 5: registration form validation using regular expressions.

const form = document.getElementById("registrationForm");
const formMessage = document.getElementById("formMessage");

const patterns = {
    // Letters and spaces; each word may contain a hyphen or apostrophe.
    name: /^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/,
    email: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,
    // Accepts a 10-digit Indian mobile number beginning with 6, 7, 8 or 9.
    mobile: /^[6-9]\d{9}$/,
    // At least 8 chars, uppercase, lowercase, digit and special character.
    password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s]).{8,}$/
};

function showError(id, message) {
    document.getElementById(id).textContent = message;
}

function clearErrors() {
    document.querySelectorAll(".field-error").forEach((item) => {
        item.textContent = "";
    });
    formMessage.hidden = true;
    formMessage.textContent = "";
    formMessage.classList.remove("success");
}

form.addEventListener("submit", function (event) {
    event.preventDefault();
    clearErrors();

    let isValid = true;

    const name = document.getElementById("fullName").value.trim();
    const email = document.getElementById("email").value.trim();
    const mobile = document.getElementById("mobile").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const course = document.getElementById("course").value;
    const year = document.getElementById("year").value;
    const gender = document.querySelector('input[name="gender"]:checked');
    const terms = document.getElementById("terms").checked;

    if (!patterns.name.test(name)) {
        showError("nameError", "Enter a valid name using letters, spaces, hyphens or apostrophes.");
        isValid = false;
    }

    if (!patterns.email.test(email)) {
        showError("emailError", "Enter a valid email address, such as student@example.com.");
        isValid = false;
    }

    if (!patterns.mobile.test(mobile)) {
        showError("mobileError", "Enter a valid 10-digit Indian mobile number starting with 6, 7, 8 or 9.");
        isValid = false;
    }

    if (!patterns.password.test(password)) {
        showError("passwordError", "Password must be 8+ characters and include uppercase, lowercase, number and special character.");
        isValid = false;
    }

    if (confirmPassword === "" || confirmPassword !== password) {
        showError("confirmPasswordError", "Passwords do not match.");
        isValid = false;
    }

    if (course === "") {
        showError("courseError", "Please select your course.");
        isValid = false;
    }

    if (year === "") {
        showError("yearError", "Please select your year of study.");
        isValid = false;
    }

    if (!gender) {
        showError("genderError", "Please select a gender option.");
        isValid = false;
    }

    if (!terms) {
        showError("termsError", "You must accept the terms and conditions.");
        isValid = false;
    }

    if (isValid) {
        formMessage.textContent = "Registration form validated successfully! (Demo only; no data has been sent to a server.)";
        formMessage.classList.add("success");
        formMessage.hidden = false;
        form.reset();
    } else {
        formMessage.textContent = "Please correct the highlighted errors and submit again.";
        formMessage.hidden = false;
    }
});

// Remove a field's error while the user corrects that field.
document.getElementById("fullName").addEventListener("input", () => showError("nameError", ""));
document.getElementById("email").addEventListener("input", () => showError("emailError", ""));
document.getElementById("mobile").addEventListener("input", () => showError("mobileError", ""));
document.getElementById("password").addEventListener("input", () => showError("passwordError", ""));
document.getElementById("confirmPassword").addEventListener("input", () => showError("confirmPasswordError", ""));
document.getElementById("course").addEventListener("change", () => showError("courseError", ""));
document.getElementById("year").addEventListener("change", () => showError("yearError", ""));
document.querySelectorAll('input[name="gender"]').forEach((radio) => {
    radio.addEventListener("change", () => showError("genderError", ""));
});
document.getElementById("terms").addEventListener("change", () => showError("termsError", ""));
