$("#loginForm").submit(function (event) {
  event.preventDefault();

  document.getElementById("overlay").style.display = "flex";
  var formData = $(this).serialize();
  console.log(formData);
  $.ajax({
    url: "login.php",
    type: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
      console.log("Server response:", response);
      document.getElementById("overlay").style.display = "none";

      showPopup(response.message, response.type);
      if (response.success) {
        window.location.href = "profile.php";
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX error:", error);
      document.getElementById("overlay").style.display = "none";
      showPopup("An error occurred. Please try again.");
    },
  });
});

// Function to show popup message
function showPopup(message, type = "info") {
  console.log("show");
  popup.innerText = message;
  popup.className = "popup " + (type === "success" ? "success" : "");
  popup.style.display = "block";
  setTimeout(() => {
    popup.style.display = "none";
  }, 5000);
}

$("#registerForm").submit(function (event) {
  event.preventDefault();

  document.getElementById("overlay").style.display = "flex";

  var formData = $(this).serialize();
  console.log("Serialized data:", formData);

  $.ajax({
    url: "register.php",
    type: "POST",
    data: formData,
    success: function (response) {
      console.log("Server response:", response.message);
      document.getElementById("overlay").style.display = "none";
      showPopup(response.message, response.type);
      if (response.success) {
        const formBox = document.getElementById("formBox");
        formBox.classList.remove("flipped");
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX error:", error);
      document.getElementById("overlay").style.display = "none";
      showPopup("An error occurred. Please try again.");
    },
  });
});
