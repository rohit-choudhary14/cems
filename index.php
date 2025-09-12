<?php
session_start();


$_SESSION['user_rjcode'] = 'RJ00001';
$_SESSION['user_name'] ='Rohit';

$_SESSION['success'] = true;
$_SESSION['type'] = 'success';
$_SESSION['message'] = 'Login successful';
if (isset($_SESSION['user_rjcode'])) {
  header("Location: profile.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>RAJASTHAN HIGH COURT CEMS</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"
    integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script src="./js/jquery.min.js"></script>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Poppins", sans-serif;
      background: #f7f8fc;
    }

    .main-container {
      display: flex;
      height: 100vh;
      width: 100%;
    }

    .image-section {
      flex: 1;
      background-size: contain;
      background-color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Right side form */
    .form-section {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 30px;
      background: white;

    }

    .form-box {
      width: 100%;
      max-width: 380px;
      height: 420px;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      position: relative;
      transform-style: preserve-3d;
      transition: transform 0.9s ease-in-out;
    }

    .form-box.flipped {
      transform: rotateY(180deg);
    }

    .form-content {
      position: absolute;
      width: 100%;
      height: 100%;
      padding: 30px;
      backface-visibility: hidden;
    }

    .form-content h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
      font-weight: 600;
    }

    .form-content form {
      display: flex;
      flex-direction: column;
    }

    .form-content .input-group {
      margin-bottom: 15px;
    }

    .form-content input {
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 14px;
      width: 100%;
      outline: none;
      transition: border-color 0.3s;
    }

    .form-content input:focus {
      border-color: #0067b8;
    }

    button {
      background: #0067b8;
      color: #fff;
      padding: 12px;
      font-size: 15px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }

    button:hover {
      background: #5a67d8;
    }

    .bottom-link {
      text-align: center;
      margin-top: 15px;
      font-size: 13px;
    }

    .bottom-link a {
      color: #667eea;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
    }

    .signup {
      transform: rotateY(180deg);
    }

    .has-search .form-control {
      padding-left: 2.375rem;
    }

    .has-search .form-control-feedback {
      position: absolute;
      z-index: 2;
      display: block;
      width: 2.375rem;
      height: 2.375rem;
      line-height: 2.375rem;
      text-align: center;
      pointer-events: none;
      color: #aaa;
    }

    /* Responsive for mobile */
    @media (max-width: 768px) {
      .main-container {
        flex-direction: column;
      }

      .image-section {
        display: none;
        /* hide on small screens */
      }

      .form-section {
        flex: unset;
        height: 100vh;
        padding: 20px;
      }
    }

    /* Loader Overlay */
    #overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }

    /* Spinner */
    .loader {
      border: 6px solid #f3f3f3;
      /* Light gray */
      border-top: 6px solid #667eea;
      /* Blue */
      border-radius: 50%;
      width: 60px;
      height: 60px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* Popup / Toast */
    .popup {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #ff4d4d;
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      display: none;
      z-index: 999;
      animation: fadeInOut 3s forwards;
    }

    .popup.success {
      background: #28a745;
    }

    @keyframes fadeInOut {
      0% {
        opacity: 0;
        transform: translateY(-20px);
      }

      10% {
        opacity: 1;
        transform: translateY(0);
      }

      90% {
        opacity: 1;
        transform: translateY(0);
      }

      100% {
        opacity: 0;
        transform: translateY(-20px);
      }
    }

    @keyframes swingY {

      0%,
      100% {
        transform: rotateY(0deg);
      }

      50% {
        transform: rotateY(30deg);
      }
    }

    .image-section img {
      transform-style: preserve-3d;
      animation: swingY 3s ease-in-out infinite;
    }
  </style>
</head>

<body>
  <div id="overlay" style="display:none;">
    <div class="loader"></div>
  </div>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark"
    style="background:#0067b8;box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);position:fixed;width:100%;padding:20px">
    <div style="display:flex;justify-content:space-between;gap:10px">
      <div style="height:20px;width:20px;border-radius:50%;background:#c01111;">

      </div>
      <div style="height:20px;width:20px;border-radius:50%;background:orange">

      </div>
      <div style="height:20px;width:20px;border-radius:50%;background:#1dc01d;">

      </div>
    </div>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <div class="navbar-nav mr-auto"></div>
    </div>
  </nav>
  <div class="main-container">
    <div class="image-section">
      <div>
        <img src="./images/logo.png" style="height:430px" />
      </div>
    </div>

    <div class="form-section">

      <div class="form-box" id="formBox">

        <!-- Login -->
        <div class="form-content login">
          <h2>Login</h2>
          <form id="loginForm" action="" method="POST">
            <div class="input-group">
              <input type="text" placeholder="RJ Code (ex: RJ00012)" required pattern="[A-Za-z]{2}[0-9]{5}"
                title="Please match the request formate (EX:RJ00012) " name="rjcode" />
            </div>
            <div class="input-group">
              <input type="password" placeholder="Enter Password" name="password" required />
            </div>
            <button type="submit">Log In</button>
          </form>
          <div class="bottom-link">
            Don't have an account? <a id="signup-link">Signup</a>
          </div>
        </div>

        <!-- Signup -->
        <div class="form-content signup">
          <h2>Sign Up</h2>
          <form id="registerForm">
            <div class="input-group">
              <input type="text" placeholder="RJ Code (ex: RJ00012)" required pattern="[A-Za-z]{2}[0-9]{5}"
                title="Enter only 5 digits (RJ is prefixed)" name="rjcode" />
            </div>
            <div class="input-group">
              <input type="password" placeholder="Create Password" name="password" required />
            </div>
            <button type="submit" name="register_submit">Sign Up</button>
          </form>
          <div class="bottom-link">
            Already have an account? <a id="login-link">Login</a>
          </div>
        </div>

      </div>
    </div>
  </div>
  <!-- Popup Message -->
  <div id="popup" class="popup"></div>
  <script src="./js/login.js"></script>
  <script>
    const formBox = document.getElementById("formBox");
    const signupLink = document.getElementById("signup-link");
    const loginLink = document.getElementById("login-link");

    signupLink.addEventListener("click", () => {
      formBox.classList.add("flipped");
    });

    loginLink.addEventListener("click", () => {
      formBox.classList.remove("flipped");
    });
  </script>

</body>

</html>