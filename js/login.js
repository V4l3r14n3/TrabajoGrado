document.addEventListener("DOMContentLoaded", function () {
    let errorMessage = document.querySelector(".error-message");
    if (errorMessage && errorMessage.textContent.trim() !== "") {
        errorMessage.style.display = "block";
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const inputs = document.querySelectorAll(".input-container");
    inputs.forEach((input, index) => {
        setTimeout(() => {
            input.style.opacity = "1";
            input.style.transform = "translateY(0)";
        }, 200 * index);
    });
});

