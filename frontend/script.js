
/*
Git Happens - Frontend Authentication
Handles login/register form switching and validation.
*/

// Get the login and registration sections
const loginSection = document.getElementById("login-section");
const registerSection = document.getElementById("register-section");

// Get the navigation links
const showRegister = document.getElementById("show-register");
const showLogin = document.getElementById("show-login");

// Get both forms
const loginForm = document.getElementById("login-form");
const registerForm = document.getElementById("register-form");

// Get the message element
const authMessage = document.getElementById("auth-message");

// Display a message to the user
function displayMessage(message, isError = false) {
    authMessage.textContent = message;
    authMessage.style.color = isError ? "#dc2626" : "#16a34a";
}

// Switch to the registration form
showRegister.addEventListener("click", function(event) {
    event.preventDefault();

    loginSection.hidden = true;
    registerSection.hidden = false;

    displayMessage("");
});

// Switch back to the login form
showLogin.addEventListener("click", function(event) {
    event.preventDefault();

    registerSection.hidden = true;
    loginSection.hidden = false;

    displayMessage("");
});

// Handle login form submission
loginForm.addEventListener("submit", function(event) {
    event.preventDefault();

    const username = document.getElementById("login-username").value.trim();
    const password = document.getElementById("login-password").value;

    if (!username || !password) {
        displayMessage("Please enter your username and password.", true);
        return;
    }

    // Backend integration will be added later
    displayMessage("Login form submitted. Backend connection is not set up yet.");
});

// Handle registration form submission
registerForm.addEventListener("submit", function(event) {
    event.preventDefault();

    const username = document.getElementById("register-username").value.trim();
    const password = document.getElementById("register-password").value;
    const confirmPassword = document.getElementById("confirm-password").value;

    if (!username || !password || !confirmPassword) {
        displayMessage("Please complete all registration fields.", true);
        return;
    }

    // Verify that both passwords match
    if (password !== confirmPassword) {
        displayMessage("Passwords do not match. Please try again.", true);
        return;
    }

    // Backend integration will be added later
    displayMessage("Registration form submitted. Backend connection is not set up yet.");
});
