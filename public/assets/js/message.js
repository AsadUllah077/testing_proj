function showErrorNotification(message) {
    // alert('You need at least one row in the table.');
    document.getElementById("showErrorMessage").classList.remove("hidden");
    document.getElementById("errorMessage").innerText = message;
}

function showSuccessNotification(message) {
    // alert('You need at least one row in the table.');
    document.getElementById("showSuccessMessage").classList.remove("hidden");
    document.getElementById("successMessage").innerText = message;
}

const hideErrorBtn = document.getElementById("hideErrorMessage");
if (hideErrorBtn) {
    hideErrorBtn.addEventListener("click", function () {
        document.getElementById("showErrorMessage")?.classList.add("hidden");
    });
}

const hideSuccessBtn = document.getElementById("hideSuccessMessage");
if (hideSuccessBtn) {
    hideSuccessBtn.addEventListener("click", function () {
        document.getElementById("showSuccessMessage")?.classList.add("hidden");
    });
}

setTimeout(() => {
    document.getElementById("showErrorMessage")?.classList.add("hidden");
    document.getElementById("showSuccessMessage")?.classList.add("hidden");
}, 5000);
