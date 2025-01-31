// login.js
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form");
    const usernameInput = document.getElementById("username");
    const passwordInput = document.getElementById("password");

    form.addEventListener("submit", (e) => {
        // Reset errors
        usernameInput.classList.remove("is-invalid");
        passwordInput.classList.remove("is-invalid");

        let valid = true;

        // Validate username
        if (usernameInput.value.trim() === "") {
            usernameInput.classList.add("is-invalid");
            showError(usernameInput, "Username cannot be empty.");
            valid = false;
        }

        // Validate password
        if (passwordInput.value.trim() === "") {
            passwordInput.classList.add("is-invalid");
            showError(passwordInput, "Password cannot be empty.");
            valid = false;
        }

        // If invalid, prevent submission
        if (!valid) e.preventDefault();
    });

    // Function to show error messages
    function showError(input, message) {
        const errorDiv = input.nextElementSibling;
        if (errorDiv) {
            errorDiv.textContent = message;
        } else {
            const errorMsg = document.createElement("div");
            errorMsg.className = "invalid-feedback";
            errorMsg.textContent = message;
            input.parentNode.appendChild(errorMsg);
        }
    }
});
