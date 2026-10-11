
/*
Git Happens - Frontend Authentication
Handles login/register switching, form validation,
and communication with the PHP authentication endpoint.
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
loginForm.addEventListener("submit", async function(event) {
    event.preventDefault();

    const username = document.getElementById("login-username").value.trim();
    const password = document.getElementById("login-password").value;

    // Check for empty fields
    if (!username || !password) {
        displayMessage("Please enter your username and password.", true);
        return;
    }

    // Send login credentials to the PHP endpoint
    displayMessage("Logging in...");

    try {
        const response = await fetch("auth.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                type: "login",
                username: username,
                password: password
            })
        });

        const data = await response.json();

        if (data.success) {
            displayMessage(data.message || "Login successful!");

            // Homepage navigation will be enabled after
            // authentication and session handling are ready.

        } else {
            displayMessage(data.message || "Login failed.", true);
        }

    } catch (error) {
        console.error("Login request failed:", error);

        displayMessage(
            "Unable to connect to the authentication service.",
            true
        );
    }
});

// Handle registration form submission
registerForm.addEventListener("submit", function(event) {
    event.preventDefault();

    const username = document.getElementById("register-username").value.trim();
    const password = document.getElementById("register-password").value;
    const confirmPassword = document.getElementById("confirm-password").value;

    // Check for empty fields
    if (!username || !password || !confirmPassword) {
        displayMessage("Please complete all registration fields.", true);
        return;
    }

    // Verify that both passwords match
    if (password !== confirmPassword) {
        displayMessage("Passwords do not match. Please try again.", true);
        return;
    }

    // Registration backend is not implemented yet
    displayMessage(
        "Registration backend is not available yet.",
        true
    );
});
