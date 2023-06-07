// Maximum number of login attempts
const MAX_ATTEMPTS = 5;

// Lockout time in seconds
const LOCKOUT_TIME = 10;

// Remaining attempts before lockout
let remainingAttempts = MAX_ATTEMPTS;

// Flag indicating if the user is locked out
let isLockedOut = false;

// Timeout ID for lockout timer
let lockoutTimeoutId;

// Event listener for the window load event
window.addEventListener("load", function () {
    // Retrieve stored login attempts and lockout status from localStorage
    const storedAttempts = localStorage.getItem("attempts");
    const storedLockedOut = localStorage.getItem("lockedOut");

    if (storedAttempts && storedLockedOut) {
        // Parse the stored values
        remainingAttempts = parseInt(storedAttempts);
        isLockedOut = JSON.parse(storedLockedOut);

        if (isLockedOut) {
            // Start the lockout timer if the user is locked out
            startLockoutTimer();
        }
    }
});

// Get the login form and error div elements
const loginForm = document.getElementById("loginForm");
const errorDiv = document.getElementById("error");

// Event listener for the login form submit event
loginForm.addEventListener("submit", function (event) {
    event.preventDefault();

    if (isLockedOut) {
        // Show an error message if the user is locked out
        showErrorMessage("Please wait before the next attempt.");
        return;
    }

    // Get the username and password values from the form
    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;

    // Disable the login button
    disableBtn();

    // Perform the login request
    fetch("login.php", {
        method: "POST",
        headers: {
            "Content-type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            username: username,
            password: password
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reset the remaining attempts and lockout status
                remainingAttempts = MAX_ATTEMPTS;
                isLockedOut = false;
                localStorage.removeItem("attempts");
                localStorage.removeItem("lockedOut");

                // Show the user data and success message
                showUserData(data.user);
                showSuccessMessage(data.message);
            } else {
                remainingAttempts--;

                if (remainingAttempts === 2) {
                    showErrorMessage("You have 2 successful password attempts left.");
                } else if (remainingAttempts === 0) {
                    // Lock out the user if no remaining attempts
                    isLockedOut = true;
                    localStorage.setItem("lockedOut", JSON.stringify(isLockedOut));
                    startLockoutTimer();
                } else {
                    showErrorMessage(data.message);
                }

                localStorage.setItem("attempts", remainingAttempts);
            }
            enableBtn();
        })
        .catch(error => {
            showErrorMessage("An error occurred while sending the request.");
            enableBtn();
        });
});

// Check if session cookie exists
const session_token = getTokenFromCookie();
if (session_token) {
    disableBtn();

    // Verify the user with the session token from cookie
    fetch("verify_users.php", {
        method: "POST",
        headers: {
            "Content-type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
            session_token: session_token
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show the user data and success message
                showUserData(data.user);
            } else {
                console.log(data.message);
            }
            enableBtn();
        })
        .catch(error => {
            enableBtn();
            showErrorMessage("An error occurred while sending the request.");
        });
}

// Start the lockout timer
function startLockoutTimer() {
    let timer = localStorage.getItem("timer") || LOCKOUT_TIME;
    showErrorMessage(`Exceeded the maximum number of unsuccessful attempts. Please wait for ${timer} seconds.`);

    lockoutTimeoutId = setInterval(() => {
        timer--;
        localStorage.setItem("timer", timer);
        if (timer === 0) {
            clearInterval(lockoutTimeoutId);
            isLockedOut = false;
            localStorage.removeItem("lockedOut");
            localStorage.removeItem("timer");
            remainingAttempts = MAX_ATTEMPTS;
            hideErrorMessage();
        } else {
            showErrorMessage(`Exceeded the maximum number of unsuccessful attempts. Please wait for ${timer} seconds.`);
        }
    }, 1000);
}

// Hide the error message
function hideErrorMessage() {
    errorDiv.innerText = '';
    errorDiv.style.display = "none";
}

// Show the error message
function showErrorMessage(message, innerHtml = false) {
    innerHtml ? (errorDiv.innerText = message) : (errorDiv.innerHTML = message);
    errorDiv.style.display = "block";
}

// Show a success message
function showSuccessMessage(message) {
    const successDiv = document.createElement("div");
    successDiv.className = "success";
    successDiv.innerText = message;

    const container = document.querySelector('.container');
    container.appendChild(successDiv);

    setTimeout(function () {
        successDiv.style.opacity = "0";
        setTimeout(function () {
            successDiv.remove();
        }, 1000);
    }, 10000);
}

// Disable the login button to prevent multiple requests
function disableBtn() {
    document.querySelector(".btn").disabled = true;
}

// Enable the login button
function enableBtn() {
    document.querySelector(".btn").disabled = false;
}

// Show the user data
function showUserData(user) {
    const container = document.querySelector(".container");
    container.innerHTML = "";

    const userDiv = document.createElement("div");
    userDiv.className = "user-info";

    const name = document.createElement("h3");
    name.innerText = "Name: " + user.name;
    userDiv.appendChild(name);

    const photo = document.createElement("img");
    photo.src = user.photo;
    photo.alt = "User Photo";
    photo.onerror = function () {
        photo.src = './images/default_images.png';
    };
    userDiv.appendChild(photo);

    const birthday = document.createElement("p");
    birthday.innerText = "Birthday: " + user.birthday;
    userDiv.appendChild(birthday);

    container.appendChild(userDiv);

    const logoutButton = document.createElement("button");
    logoutButton.classList.add('exit_btn');
    logoutButton.classList.add('btn');
    logoutButton.innerText = "Logout";
    logoutButton.addEventListener("click", function () {
        logout();
    });

    container.appendChild(logoutButton);
}

// Logout the user
function logout() {
    disableBtn();
    fetch("logout.php", {
        method: "GET"
    })
        .then(response => response.json())
        .then(data => {
            enableBtn();
            if (data.success) {
                deleteCookie("session_token");
                window.location.reload();
            } else {
                deleteCookie("session_token");
                window.location.reload();
            }
        })
        .catch(error => {
            enableBtn();
            deleteCookie("session_token");
            window.location.reload();
        });
}

// Get the session token from the cookie
function getTokenFromCookie() {
    const token = document.cookie.split('; ').find(cookie => cookie.startsWith('session_token='));
    return token ? token.split('=')[1] : null;
}

// Delete a cookie by name
function deleteCookie(name) {
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
}
