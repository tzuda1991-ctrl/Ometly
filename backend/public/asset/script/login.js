$(document).ready(function () {
    console.log(`${domainUrl}loginForm`);
    $("#loginForm").on("submit", function (e) {
        e.preventDefault();

        let formData = new FormData(this);

        $.ajax({
            url:  `${domainUrl}loginForm`, // use Laravel route helper
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                console.log("Server response:", response);

                if (response.status) {
                    window.location.href = `${domainUrl}index`;
                } else {
                    iziToast.error({
                        title: `${app.Error}!`,
                        message: response.message || "Login failed",
                        position: "topRight",
                    });
                }
            },
            error: function (xhr) {
                if (xhr.status === 419) {
                    iziToast.warning({
                        title: "Session Expired",
                        message: "Refreshing page, please try again...",
                        position: "topRight",
                    });
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                    return;
                }
                iziToast.error({
                    title: `${app.Error}!`,
                    message:
                        (xhr.responseJSON && xhr.responseJSON.message) ||
                        "Something went wrong",
                    position: "topRight",
                });
            },
        });
    });

    $("#forgotPasswordForm").on("submit", function (event) {
        event.preventDefault();
        var formData = new FormData(this);

        var newPassword = $("#new_password").val();
        var confirmPassword = $("#confirm_password").val();

        if (newPassword !== confirmPassword) {
            showErrorToast("Passwords do not match!");
            return;
        }

        $.ajax({
            url: `${domainUrl}forgotPasswordForm`,
            type: "POST",
            data: formData,
            dataType: "json",
            contentType: false,
            processData: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                if (response.status) {
                    $("#forgotPasswordModal").modal("hide");
                    resetForm("#forgotPasswordForm");
                    resetForm("#loginForm");
                    iziToast.show({
                        title: app.Success,
                        message: "Password Reset successfully.",
                        color: app.greenToast,
                        position: app.toastPosition,
                        transitionIn: app.fadeInAction,
                        transitionOut: app.fadeOutAction,
                        timeout: app.timeout,
                        animateInside: false,
                        iconUrl: app.checkCircleIcon,
                    });
                } else {
                    showErrorToast(response.message);
                }
            },
            error: function (xhr) {
                if (xhr.status === 419) {
                    window.location.reload();
                    return;
                }
                showErrorToast(
                    (xhr.responseJSON && xhr.responseJSON.message) ||
                        "Something went wrong"
                );
            },
        });
    });
});
