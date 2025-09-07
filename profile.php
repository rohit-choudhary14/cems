<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_rjcode'])) {
  header("Location: index.php");
  exit;
}

$user_rjcode = $_SESSION['user_rjcode'];
$stmt = $pdo->prepare("SELECT rjcode FROM users WHERE rjcode = ?");
$stmt->execute([$user_rjcode]);
$currentUser = $stmt->fetch();

$IncommingNotifications = [];
$selfRequestNotification = [];
if ($currentUser) {
  $stmt = $pdo->prepare("
        SELECT ei.id, e.title, u.rjcode AS inviter_rjcode, ei.status
        FROM event_invitations ei
        JOIN events e ON ei.event_id = e.id
        JOIN users u ON ei.inviter_rjcode = u.rjcode
        WHERE ei.invitee_rjcode = ?
          AND ei.status = 'pending'
        ORDER BY ei.created_at DESC
    ");
  $stmt->execute([$currentUser['rjcode']]);
  $IncommingNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
  // Invitations sent by the logged-in user (only pending)
  $stmt = $pdo->prepare("
    SELECT ei.id, e.title AS event_title, u.rjcode AS invitee_rjcode, ei.status, ei.created_at
    FROM event_invitations ei
    JOIN events e ON ei.event_id = e.id
    JOIN users u ON ei.invitee_rjcode = u.rjcode
    WHERE ei.inviter_rjcode = :userId
      AND ei.status = 'pending'
    ORDER BY ei.created_at DESC
  ");

  $stmt->execute(["userId" => $user_rjcode]);
  $selfRequestNotification = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Calendar Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="./css/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    body,
    html {
      height: 100%;
      overflow-x: hidden;
      font-family: "Segoe UI", sans-serif;
      background-color: #f4f6f9;
    }

    .container-fluid {
      padding: 0 20px;
    }

    .navbar {
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      background: #0067b8;
    }

    .sidebar {
      position: sticky;
      width: 100%;
      top: 20px;
      margin-top: 21px;
      margin-bottom: 20px;
    }


    #miniCalendar {
      position: relative;
      padding: 20px;
      margin-bottom: 30px;
    }

    #miniCalendar::before {
      content: "";
      position: absolute;
      top: 580%;
      left: 50%;
      width: 120px;
      height: 296px;
      background: url("images/logo.png") no-repeat center center;
      background-size: contain;
      opacity: 0.3;
      transform: translate(-50%, -50%);
      pointer-events: none;
      z-index: 1;
    }

    /* Ensure calendar stays above watermark */
    #miniCalendar .flatpickr-innerContainer,
    #miniCalendar .flatpickr-months,
    #miniCalendar .flatpickr-days {
      position: relative;
      z-index: 1;
    }

    #calendar {
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
      margin-top: 10px;
      margin-bottom: 50px;
    }

    body::-webkit-scrollbar {
      width: 0px;
      background: transparent;
    }

    body {
      scrollbar-width: none;
      -ms-overflow-style: none;
    }

    #searchBox {
      border-radius: 8px;
      padding: 10px;
      border: none;
      width: 100%;
      max-width: 400px;
    }

    .fc-daygrid-day {
      padding: 0 !important;
      height: 60px;
    }

    .fc-daygrid-day-frame {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100%;
    }

    .fc-daygrid-day-number {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: #e0e0e0;
      color: #333;
      font-size: 13px;
      font-weight: 500;
      margin: auto;
      transition: background-color 0.3s ease;
    }

    .fc-day-today .fc-daygrid-day-number {
      /* background-color: whitesmoke; */
      color: white;
      font-weight: bold;
      cursor: pointer;
    }

    .selected-day .fc-daygrid-day-number {
      background-color: #5528a7ff !important;
      color: white !important;
      transition: background-color 0.3s ease;
    }

    .selected-day {
      background-color: transparent !important;
    }

    .fc-prev-button.fc-button,
    .fc-next-button.fc-button {
      background-color: #007bff !important;
      /* blue */
      border-color: #007bff !important;
      color: white !important;
    }

    .fc-next-button.fc-button {
      background-color: #28a745 !important;
      border-color: #28a745 !important;
    }

    .fc-prev-button.fc-button:hover,
    .fc-next-button.fc-button:hover {
      filter: brightness(85%) !important;
    }

    #calendar .fc-dayGridMonth-view .fc-daygrid-event {
      display: none !important;
    }

    #calendar .fc-dayGridMonth-view .fc-daygrid-day-events {
      min-height: 0 !important;
    }

    @media (max-width: 768px) {
      #calendar {
        padding: 15px;
        margin-bottom: 180px;
      }

      .sidebar {
        position: static;
        margin-top: 0px;

      }

      #calendar .fc-header-toolbar,
      #calendar .fc-footer-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 4px 0;
      }

      #calendar .fc-header-toolbar .fc-toolbar-chunk:nth-child(1),
      #calendar .fc-footer-toolbar .fc-toolbar-chunk:nth-child(1) {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        flex: 1;
      }

      #calendar .fc-header-toolbar .fc-toolbar-chunk:nth-child(2),
      #calendar .fc-footer-toolbar .fc-toolbar-chunk:nth-child(2) {
        display: flex;
        justify-content: center;
        align-items: center;
        flex: 1;
      }

      #calendar .fc-header-toolbar .fc-toolbar-chunk:nth-child(3),
      #calendar .fc-footer-toolbar .fc-toolbar-chunk:nth-child(3) {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        flex: 1;
      }

      #calendar .fc-toolbar-title {
        font-size: 1rem;
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
        text-overflow: ellipsis;
        overflow: hidden;
        max-width: 100%;
      }

      #calendar .fc-footer-toolbar {
        padding-top: 6px;
      }

      #calendar .fc-button {
        padding: 6px 10px;
        font-size: 0.9rem;
      }

      @media (max-width: 768px) {

        #calendar .fc-header-toolbar,
        #calendar .fc-footer-toolbar {
          flex-wrap: wrap;
        }

        #calendar .fc-header-toolbar .fc-toolbar-chunk:nth-child(1),
        #calendar .fc-header-toolbar .fc-toolbar-chunk:nth-child(2),
        #calendar .fc-header-toolbar .fc-toolbar-chunk:nth-child(3) {
          flex-basis: 100%;
          justify-content: space-between !important;
          margin-bottom: 6px;
          gap: 10px;
        }

        #calendar .fc-button-group {
          display: contents !important;
        }

        #calendar .fc-footer-toolbar .fc-toolbar-chunk:nth-child(1),
        #calendar .fc-footer-toolbar .fc-toolbar-chunk:nth-child(3) {
          flex-basis: 48%;
        }

        #calendar .fc-toolbar-title {
          font-size: 0.95rem;
          max-width: 60%;
        }

        #calendar .fc-button {
          padding: 5px 8px;
          font-size: 0.85rem;
        }
      }

    }

    #calendar .fc-col-header-cell-cushion {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      white-space: normal !important;
      /* allow wrapping */
      font-size: 0.8rem;
      /* slightly smaller */
      line-height: 1.2;
      padding: 2px 4px;
    }

    #calendar .fc-col-header-cell {
      min-height: 45px;
    }

    #calendar .fc-timegrid-axis-cushion {
      font-size: 0.7rem;
      white-space: nowrap;
    }

    a {
      text-decoration: none;
      color: black;
    }

    .fc-event {
      white-space: normal !important;
      overflow: hidden;
      border-radius: 6px !important;
      padding: 3px 5px !important;
      margin: 2px 0 !important;
      font-size: 12px !important;
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

    /* Overlay background */
    .reminder-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 9999;
    }

    /* Popup box */
    .reminder-box {
      background: #fff;
      padding: 25px;
      width: 350px;
      max-width: 90%;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      animation: slideDown 0.4s ease;
    }

    /* Heading */
    .reminder-box h3 {
      margin-bottom: 12px;
      font-size: 20px;
      color: #333;
    }

    /* Message */
    .reminder-box p {
      margin-bottom: 18px;
      color: #666;
      font-size: 16px;
    }

    /* Button */
    .reminder-btn {
      background: #007bff;
      border: none;
      padding: 10px 20px;
      color: white;
      font-size: 15px;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .reminder-btn:hover {
      background: #0056b3;
    }

    .fc-event-title {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      display: block;
      max-width: 100%;
    }

    /* Animation */
    @keyframes slideDown {
      from {
        transform: translateY(-50px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .fc-desc {
      font-size: 13px;
      color: white;
      margin-top: 2px;
      white-space: normal;
      font-weight: 700;
    }

    .event-count {
      display: inline-block;
      background: #3b82f6;
      /* blue circle */
      color: white;
      font-size: 12px;
      font-weight: bold;
      border-radius: 50%;
      padding: 2px 6px;
      position: absolute;
      top: 2px;
      right: 2px;
    }

    .event-count-badge {
      background: #007bff;
      color: white;
      border-radius: 50%;
      width: 22px;
      height: 22px;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto;
      cursor: pointer;
    }

    .tooltip-wrapper {
      padding: 5px;
      max-width: 200px;
    }

    .tooltip-item {
      padding: 4px 6px;
      border-bottom: 1px solid #eee;
    }

    .tooltip-item:last-child {
      border-bottom: none;
    }

    .fc .fc-toolbar-title {
      font-size: 1rem !important;
    }

    .flatpickr-calendar {
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      width: 100%;
    }

    .flatpickr-day {
      border-radius: 0px !important;
    }

    .flatpickr-innerContainer {
      display: inline-block !important;
    }

    .text-truncate {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .tooltip-priority-low {
      color: #28a745;
    }

    .tooltip-priority-medium {
      color: #ffc107;
    }

    .tooltip-priority-high {
      color: #dc3545;
    }
  </style>
  <style>
    .fc-col-header-cell:first-child .fc-col-header-cell-cushion {
      color: red !important;
      font-weight: 700 !important;
    }

    .date-number-red {
      color: red !important;
      font-weight: bold;

    }

    .date-number-green {
      color: green !important;
      font-weight: bold;

    }

    .flatpickr-weekdays div :first-child {
      border: solid 1px red;
    }

    .flatpickr-weekdays div :last-child {
      border: solid 1px red;
    }

    .flatpickr-weekdays span:first-child {
      color: red;
      font-weight: bold;
    }

    .flatpickr-weekdays span:last-child {
      font-weight: bold;
      background: linear-gradient(to right, red 50%, green 50%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .dropdown-toggle::after {
      display: none !important;
    }

    .dropdown-menu {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* Floating curved bottom nav */
    .mobile-bottom-nav {
      position: fixed;
      bottom: 15px;
      left: 50%;
      transform: translateX(-50%);
      background: #222;
      width: 90%;
      border-radius: 25px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      z-index: 1050;
    }

    .mobile-bottom-nav .nav-container {
      display: flex;
      justify-content: space-around;
      align-items: center;
      padding: 10px 0;
    }

    .mobile-bottom-nav .nav-item {
      color: white;
      font-size: 22px;
      position: relative;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .mobile-bottom-nav .nav-item:hover {
      color: #0d6efd;
    }

    .mobile-bottom-nav sup.badge {
      position: absolute;
      top: -6px;
      right: -10px;
      font-size: 12px;
    }

    .modal.modal-bottom .modal-dialog {
      position: fixed;
      bottom: 0;
      margin: 0;
      width: 100%;
    }

    .modal.modal-bottom .modal-content {
      border-radius: 20px 20px 0 0;
    }

    .dropdown-item {
      cursor: pointer;
    }

    #searchResults .list-group-item {
      transition: transform 0.15s ease, background 0.15s ease;
    }

    #searchResults .list-group-item:hover {
      background: #f8f9fa;
      transform: translateY(-2px);
    }

    #searchModal .input-group .form-control {
      flex: 1 1 auto;
      min-width: 0;
    }

    #searchResults .fw-bold {
      letter-spacing: 0.5px;
    }

    #searchResults .list-group-item {
      border: none;
      border-bottom: 1px solid #eee;
    }

    #searchResults .section-header {
      position: sticky;
      top: 0;
      background: #f8f9fa;
      z-index: 10;
      padding: 6px 10px;
      font-weight: 600;
      text-transform: uppercase;
      border-bottom: 1px solid #dee2e6;
    }

    .highlight-event {
      position: relative;
      box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.18);
      transition: box-shadow .25s ease-in-out, transform .15s ease;
      transform: translateZ(0);
      border-radius: 6px !important;
    }

    @keyframes highlight-fade {
      0% {
        background-color: yellowgreen;
        box-shadow: 0 0 0 6px rgba(255, 193, 7, 0.18);
      }

      50% {
        background-color: blue;
        box-shadow: 0 0 10px 6px rgba(255, 193, 7, 0.22);
      }

      100% {
        background-color: transparent;
        box-shadow: none;
      }
    }

    .fc-event.highlight,
    .highlight-event {
      animation: highlight-fade 50000ms ease-out forwards;
      border-radius: 6px !important;
      z-index: 9999 !important;
    }

    .highlight {
      animation: flashHighlight 2s ease-in-out;
    }

    @keyframes flashHighlight {
      0% {
        background-color: #ffeb3b;
      }

      50% {
        background-color: #fff176;
      }

      100% {
        background-color: inherit;
      }
    }

    #toastContainer {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
    }

    .toast {
      min-width: 250px;
      margin-bottom: 12px;
      padding: 14px 20px;
      border-radius: 8px;
      color: #fff;
      font-size: 14px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      opacity: 0;
      transform: translateX(100%);
      transition: all 0.5s ease;
    }

    .toast.show {
      opacity: 1;
      transform: translateX(0);
    }

    .toast.success {
      background-color: #28a745;
    }

    .toast.error {
      background-color: #dc3545;
    }

    .toast.info {
      background-color: #17a2b8;
    }
  </style>
</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-dark d-lg-flex" style="background:#0074ff">
    <div class="container-fluid">
      <a class="navbar-brand" href="#" style="color:white">Rajasthan High Court CEMS</a>
      <div class="collapse navbar-collapse" id="navbarContent">
        <div class="d-flex flex-grow-1 justify-content-center my-2 my-lg-0">
          <button class="btn btn-light" id="newEventBtn">+ New Event</button>
        </div>
        <div class="d-flex justify-content-end align-items-center ms-auto gap-4">
          <button class="btn btn-light fa fa-search" data-bs-toggle="modal" data-bs-target="#searchModal"></button>
          <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
              id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i style="font-size:24px" class="fa">&#xf2be;</i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-light" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="#">
                  <?= htmlspecialchars($_SESSION['user_rjcode']) ?>
                </a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li style="padding: 2px;">
                <a href="logout.php" class="btn btn-danger w-100">Logout</a>
              </li>
            </ul>
          </div>
          <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
              id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i style="font-size:20px" class="fa">&#xf0f3;</i>
              <sup class="badge bg-danger rounded-pill ms-1">
                <?php echo count($IncommingNotifications) + count($selfRequestNotification) ?>
              </sup>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" aria-labelledby="notificationDropdown"
              style="width: 350px; max-height: 450px; overflow-y: auto; border-radius: 12px;">
              <li class="px-2 py-1 text-muted small fw-bold border-bottom">
                Incoming Invitations
              </li>
              <?php if (!empty($IncommingNotifications)): ?>
                <?php foreach ($IncommingNotifications as $n): ?>
                  <li class="dropdown-item d-flex flex-column align-items-start">
                    <div class="w-100 p-2 rounded bg-light mb-2 shadow-sm">
                      <div class="fw-semibold text-primary">
                        <?= htmlspecialchars($n['inviter_rjcode']) ?>
                        <span class="text-muted small">invited you</span>
                      </div>
                      <div class="small text-dark mb-1">
                        Event: <em><?= htmlspecialchars($n['title']) ?></em>
                      </div>
                      <?php if ($n['status'] === 'pending'): ?>
                        <div class="d-flex gap-2">
                          <button class="btn btn-sm btn-success flex-fill invite-action-btn" data-id="<?= $n['id'] ?>"
                            data-action="accept">Accept</button>
                          <button class="btn btn-sm btn-outline-danger flex-fill invite-action-btn" data-id="<?= $n['id'] ?>"
                            data-action="reject">Reject</button>
                        </div>
                      <?php else: ?>
                        <span class="badge bg-secondary"><?= ucfirst($n['status']) ?></span>
                      <?php endif; ?>
                    </div>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="dropdown-item text-center text-muted py-3">
                  <em>No incoming invitations</em>
                </li>
              <?php endif; ?>
              <li class="px-2 py-1 text-muted small fw-bold border-top border-bottom mt-2">
                My Sent Invites
              </li>
              <?php if (!empty($selfRequestNotification)): ?>
                <?php foreach ($selfRequestNotification as $note): ?>
                  <li class="dropdown-item d-flex flex-column align-items-start">
                    <div class="w-100 p-2 rounded bg-white border mb-2 shadow-sm">
                      <div class="fw-semibold">
                        To: <span class="text-primary"><?= htmlspecialchars($note['invitee_rjcode']) ?></span>
                      </div>
                      <div class="small text-dark mb-1">
                        Event: <em><?= htmlspecialchars($note['event_title']) ?></em>
                      </div>
                      <div>
                        Status:
                        <?php if ($note['status'] === 'pending'): ?>
                          <span class="badge bg-warning text-dark" style="padding: 10px;">Pending</span>
                          <button class="btn btn-sm btn-outline-danger ms-2 cancel-invite-btn"
                            data-id="<?= $note['id'] ?>">Cancel</button>
                        <?php elseif ($note['status'] === 'accepted'): ?>
                          <span class="badge bg-success" style="padding: 10px;">Accepted</span>
                        <?php else: ?>
                          <span class="badge bg-danger" style="padding: 10px;">Rejected</span>
                        <?php endif; ?>
                      </div>

                      <div class="small text-muted mt-1"><?= $note['created_at'] ?></div>
                    </div>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li class="dropdown-item text-center text-muted py-3">
                  <em>No sent invitations</em>
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>
  <div class="mobile-bottom-nav d-md-none" style="background:#0074ff">
    <div class="nav-container">
      <a href="#" class="nav-item"><i class="fa fa-home"></i></a>
      <a href="#" class="nav-item" data-bs-toggle="modal" data-bs-target="#searchModal">
        <i class="fa fa-search"></i>
      </a>
      <a href="#" class="nav-item">
        <button class="btn btn-light" id="newEventBtnMb">+ New Event</button>
      </a>
      <a href="#" class="nav-item" data-bs-toggle="modal" data-bs-target="#mobileNotificationModal">
        <i class="fa fa-bell"></i>
        <sup class="badge bg-danger rounded-pill ms-1">
          <?php echo count($IncommingNotifications) + count($selfRequestNotification) ?>
        </sup>
      </a>
      <a href="#" class="nav-item" data-bs-toggle="modal" data-bs-target="#profileModal">
        <i class="fa fa-user"></i>
      </a>
    </div>
  </div>
  <div class="modal fade" id="mobileNotificationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-bottom">
      <div class="modal-content bg-white text-dark">
        <div class="modal-header">
          <h6 class="modal-title">Notifications</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0" style="max-height:400px;overflow-y:auto;">
          <div class="px-3 py-2 text-muted small fw-bold border-bottom bg-light">
            Incoming Invitations
          </div>
          <?php if (!empty($IncommingNotifications)): ?>
            <?php foreach ($IncommingNotifications as $n): ?>
              <li class="dropdown-item d-flex flex-column align-items-start">
                <div class="w-100 p-2 rounded bg-light mb-2 shadow-sm">
                  <div class="fw-semibold text-primary">
                    <?= htmlspecialchars($n['inviter_rjcode']) ?>
                    <span class="text-muted small">invited you</span>
                  </div>
                  <div class="small text-dark mb-1">
                    Event: <em><?= htmlspecialchars($n['title']) ?></em>
                  </div>
                  <?php if ($n['status'] === 'pending'): ?>
                    <div class="d-flex gap-2">
                      <button class="btn btn-sm btn-success flex-fill invite-action-btn" data-id="<?= $n['id'] ?>"
                        data-action="accept">Accept</button>
                      <button class="btn btn-sm btn-outline-danger flex-fill invite-action-btn" data-id="<?= $n['id'] ?>"
                        data-action="reject">Reject</button>
                    </div>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?= ucfirst($n['status']) ?></span>
                  <?php endif; ?>
                </div>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center text-muted py-3">
              <em>No incoming invitations</em>
            </div>
          <?php endif; ?>
          <div class="px-3 py-2 text-muted small fw-bold border-top border-bottom bg-light">
            My Sent Invites
          </div>
          <?php if (!empty($selfRequestNotification)): ?>
            <?php foreach ($selfRequestNotification as $note): ?>
              <div class="p-3 border-bottom">
                <div class="fw-semibold">
                  To: <span class="text-primary"><?= htmlspecialchars($note['invitee_rjcode']) ?></span>
                </div>
                <div class="small text-dark mb-2">
                  Event: <em><?= htmlspecialchars($note['event_title']) ?></em>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-2">
                  <span>
                    Status:
                    <?php if ($note['status'] === 'pending'): ?>
                      <span class="badge bg-warning text-dark" style="padding:10px">Pending</span>
                    <?php elseif ($note['status'] === 'accepted'): ?>
                      <span class="badge bg-success" style="padding:10px">Accepted</span>
                    <?php else: ?>
                      <span class="badge bg-danger" style="padding:10px">Rejected</span>
                    <?php endif; ?>
                  </span>

                  <?php if ($note['status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-outline-danger ms-2 cancel-invite-btn"
                      data-id="<?= $note['id'] ?>">Cancel</button>
                  <?php endif; ?>
                </div>

                <div class="small text-muted mt-1"><?= $note['created_at'] ?></div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center text-muted py-3">
              <em>No sent invitations</em>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
  <!-- ✅ Mobile Search Modal -->
  <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content shadow border-0 rounded-4 overflow-hidden">
        <div class="modal-header bg-primary text-white py-3">
          <h5 class="modal-title fw-semibold" id="searchModalLabel">
            <i class="bi bi-search me-2"></i> Search Events
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body bg-light p-0">
          <div class="p-3 border-bottom bg-light sticky-top" style="z-index: 10;">
            <div class="input-group flex-nowrap rounded-pill shadow-sm bg-white">
              <span class="input-group-text bg-white border-0 rounded-start-pill">
                <i class="bi bi-search text-muted"></i>
              </span>
              <input type="text" id="searchBox" class="form-control border-0 rounded-end-pill"
                placeholder="Search by event name..." autofocus />
            </div>
          </div>
          <div id="searchFilterBar" class="bg-white border-bottom px-3 py-2 sticky-top d-grid gap-2 align-items-center">
            <div class="btn-group btn-group-sm ms-2  row" role="group" aria-label="Filter events"
              style="row-gap: 10px;">
              <div class="col-6">
                <button class="btn btn-outline-secondary btn-sm w-100" data-filter="all">📋&nbsp;All</button>
              </div>
              <div class="col-6 ">
                <button type="button" class="btn btn-outline-primary btn-sm w-100"
                  data-filter="today">📅&nbsp;Today</button>
              </div>
              <div class="col-6">
                <button class="btn btn-outline-success btn-sm w-100" data-filter="upcoming">⏩&nbsp;Upcoming</button>
              </div>
              <div class="col-6 ">
                <button class="btn btn-outline-danger btn-sm w-100" data-filter="past">⏪&nbsp;Previous</button>

              </div>
            </div>
          </div>
          <!-- Results -->
          <div id="searchResults" class="list-group border-0 bg-transparent"
            style="max-height: 55vh; overflow-y: auto;">
            <div class="text-center text-muted py-4">🔍 Start typing to search events...</div>
          </div>
        </div>
        <!-- Footer -->
        <div class="modal-footer border-0 bg-light">
          <button type="button" class="btn btn-secondary w-100 rounded-pill" data-bs-dismiss="modal">
            Close
          </button>
        </div>

      </div>
    </div>
  </div>
  </div>
  <!-- ✅ Mobile Profile Modal -->
  <div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title">Profile</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p><i class="fa fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_rjcode']) ?></p>
          <hr>
          <a href="logout.php" class="btn btn-danger w-100">Logout</a>
        </div>
      </div>
    </div>
  </div>
  <div class="container-fluid mt-3" style="margin-top:100px;">
    <div class="row">
      <div class="col-md-4">
        <div class="sidebar">
          <div id="miniCalendar"></div>
        </div>
      </div>
      <!-- Main Calendar -->
      <div class="col-md-8">
        <div class="row g-2" style="margin-top:85px">
          <div class="col-6 col-md-3">
            <button class="btn btn-primary btn-sm w-100" id="todayBtn">📅 Today</button>
          </div>
          <div class="col-6 col-md-3">
            <button class="btn btn-success btn-sm w-100" id="upcomingBtn">⏩ Upcoming</button>
          </div>
          <div class="col-6 col-md-3">
            <button class="btn btn-danger btn-sm w-100" id="previousBtn">⏪ Previous</button>
          </div>
          <div class="col-6 col-md-3">
            <button class="btn btn-secondary btn-sm w-100" id="allBtn">📋 All Events</button>
          </div>
        </div>
        <div id="calendar"></div>
      </div>
    </div>
  </div>
  <!-- Event Offcanvas -->
  <div class="offcanvas offcanvas-end" tabindex="-1" style="z-index: 10000;" id="eventOffcanvas">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">
        <i class="bi bi-calendar-check-fill me-2"></i>Event
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <form id="eventForm">
        <input type="hidden" id="eventId">
        <div class="mb-3">
          <label class="form-label" for="title">
            <i class="bi bi-card-text me-2"></i>Title
          </label>
          <input type="text" id="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="start">
            <i class="bi bi-calendar-event-fill me-2"></i>Start
          </label>
          <input type="datetime-local" id="start" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label" for="end">
            <i class="bi bi-calendar-event me-2"></i>End
          </label>
          <input type="datetime-local" id="end" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="invited_users" class="form-label">Invite People / Subordinates (Enter rjcodes)</label>
          <input type="text" id="invited_users" name="invited_users" class="form-control"
            placeholder="Enter rjcodes separated by commas">
          <small class="form-text text-muted">Example: RJ1001, RJ1002, RJ1003</small>
        </div>
        <div class="mb-3">
          <label class="form-label" for="priority">
            <i class="bi bi-exclamation-circle-fill me-2"></i>Priority
          </label>
          <select id="priority" class="form-control">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <!-- Remind Me Before -->
        <div class="mb-3">
          <label class="form-label" for="reminder">
            <i class="bi bi-bell-fill me-2"></i>Remind me before
          </label>
          <select id="reminder" class="form-control">
            <option value="0">At time of event</option>
            <option value="5">5 minutes before</option>
            <option value="10">10 minutes before</option>
            <option value="15">15 minutes before</option>
            <option value="30">30 minutes before</option>
            <option value="60">1 hour before</option>
            <option value="1440">1 day before</option>
          </select>
        </div>
        <!-- Repeat Event -->
        <div class="mb-3 form-check">
          <input type="checkbox" class="form-check-input" id="repeatEventCheck">
          <label class="form-check-label" for="repeatEventCheck">
            <i class="bi bi-arrow-repeat me-2"></i>Repeat event
          </label>
        </div>
        <div class="mb-3" id="repeatOptions" style="display:none;">
          <label class="form-label" for="repeatFrequency">
            <i class="bi bi-clock-history me-2"></i>Repeat Frequency
          </label>
          <select id="repeatFrequency" class="form-control">
            <option value="">--None--</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="description">
            <i class="bi bi-file-text-fill me-2"></i>Description
          </label>
          <textarea id="description" class="form-control"></textarea>
        </div>
        <div class="d-flex justify-content-between">
          <button id="leaveBtn" style="display:none;" class="btn btn-warning">Leave Event</button>
          <button type="button" class="btn btn-danger" id="deleteEventBtn" style="display:none;">Delete</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
  <!-- Reminder Popup -->
  <div id="reminderPopup" class="reminder-overlay">
    <input type="hidden" id="eventIdStore" value="" />
    <div class="reminder-box">
      <h3 id="reminderTitle">🔔 Reminder</h3>
      <p id="reminderMessage">You have an event starting soon!</p>
      <div class="reminder-actions">
        <button id="reminderCloseBtn" class="reminder-btn">OK</button>
        <button id="reminderdontshowBtn" class="reminder-btn danger">
          Don’t Remind Me Again
        </button>
      </div>

    </div>
  </div>
  <!-- Toast container -->
  <div id="toastContainer"></div>
  <script>
    const currentUser = "<?php echo $_SESSION['user_rjcode']; ?>";
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      let currentEvent = null;
      function nthWeekdayOfMonth(year, month, weekday, n) {
        let date = new Date(year, month, 1);
        let count = 0;
        while (date.getMonth() === month) {
          if (date.getDay() === weekday) {
            count++;
            if (count === n) {
              return new Date(date);
            }
          }
          date.setDate(date.getDate() + 1);
        }
        return null;
      }

      document.getElementById("repeatEventCheck").addEventListener("change", function () {
        const repeatOptions = document.getElementById("repeatOptions");
        if (this.checked) {
          repeatOptions.style.display = "block";
        } else {
          repeatOptions.style.display = "none";
          document.getElementById("repeatFrequency").value = "";
        }
      });

      const calendarEl = document.getElementById('calendar');
      const deleteBtn = document.getElementById('deleteEventBtn');
      let allEvents = [];
      let reminderQueue = [];
      let isReminderShowing = false;
      const miniCal = flatpickr("#miniCalendar", {
        inline: true,
        defaultDate: "today",
        onChange: function (selectedDates) {
          if (selectedDates.length > 0) {
            const selected = selectedDates[0];
            calendar.gotoDate(selected);
          }
        },
        onDayCreate: function (dObj, dStr, fp, dayElem) {
          const cellDate = new Date(dayElem.dateObj);
          const Y = cellDate.getFullYear();
          const M = cellDate.getMonth();
          const D = cellDate.getDate();
          const weekday = cellDate.getDay();
          dayElem.classList.remove("date-number-red", "date-number-green");
          if (weekday === 0) {
            dayElem.classList.add("date-number-red");
          }
          const secondSat = nthWeekdayOfMonth(Y, M, 6, 2);
          const fourthSat = nthWeekdayOfMonth(Y, M, 6, 4);
          if ((secondSat && secondSat.getDate() === D) || (fourthSat && fourthSat.getDate() === D)) {
            dayElem.classList.add("date-number-red");
          }
          const firstSat = nthWeekdayOfMonth(Y, M, 6, 1);
          const thirdSat = nthWeekdayOfMonth(Y, M, 6, 3);
          if ((firstSat && firstSat.getDate() === D) ||
            (thirdSat && thirdSat.getDate() === D)) {
            dayElem.classList.add("date-number-green");
            return;
          }
        }
      });
      /* ------------------ Reminder System ------------------ */
      function showReminder(title, message, id) {

        reminderQueue.push({
          title,
          message,
          id
        });
        if (!isReminderShowing) displayNextReminder();
      }

      function displayNextReminder() {
        if (reminderQueue.length === 0) {
          isReminderShowing = false;
          return;
        }
        isReminderShowing = true;
        const {
          title,
          message,
          id
        } = reminderQueue.shift();
        document.getElementById("reminderTitle").innerText = "🔔 " + title;
        document.getElementById("reminderMessage").innerText = message;
        document.getElementById("reminderPopup").style.display = "flex";
        document.getElementById("eventIdStore").value = id;
      }

      function closeReminder() {
        document.getElementById("reminderPopup").style.display = "none";
        setTimeout(displayNextReminder, 10000);
      }
      function dontShowReminder() {
        document.getElementById("reminderPopup").style.display = "none";

        let eventId = document.getElementById("eventIdStore").value;
        if (eventId) {
          localStorage.setItem(eventId + currentUser, "hidden");
        }

      }
      function checkReminders() {
        const now = new Date();
        allEvents.forEach(e => {
          if (!e.start) return;
          const key = e.id + currentUser
          if (localStorage.getItem(key)) {
            return;
          }
          const start = new Date((e.start || "").replace(" ", "T"));
          const diffMins = (start.getTime() - now.getTime()) / 60000;
          const remindBefore = e.reminder_before ? parseInt(e.reminder_before, 10) : 10;
          if (diffMins > 0 && diffMins <= remindBefore) {
            showReminder(
              `Reminder: ${e.title}`,
              `Your event "${e.title}" starts at ${start.toLocaleString()}`,
              e.id
            );
            const calendarEvent = calendar.getEventById(e.id);
            if (calendarEvent) {
              const el = document.querySelector(`[data-event-id="${e.id}"]`);
              if (el) {
                el.classList.add("highlight-event");
                el.addEventListener("animationend", () => {
                  el.classList.remove("highlight-event");
                }, { once: true });
              }
            }
          }
        });
      }
      setInterval(checkReminders, 10000);

      function formatDateLocal(date) {
        if (!date) return "";
        const d = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
        return d.toISOString().slice(0, 16);
      }
      const colors = {
        low: "#28a745",
        medium: "#ffc107",
        high: "#dc3545"
      };
      /* ------------------ Main Calendar ------------------ */
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'listYear',
        selectable: true,
        eventSources: [],
        height: 'auto',
        eventDisplay: 'block',
        datesSet: function (info) {
          calendar.removeAllEvents();
          calendar.refetchEvents();
        },
        eventDidMount: function (info) {
          const priority = info.event.extendedProps.priority || "low";
          if (info.view.type !== "dayGridMonth") {
            info.el.style.backgroundColor = colors[priority] || colors.low;
            info.el.style.color = "white";
            info.el.style.borderRadius = "6px";
            info.el.style.padding = "2px 6px";
            info.el.style.fontSize = "13px";
            info.el.style.cursor = "pointer";
          }
          if (info.el._tooltip) info.el._tooltip.dispose();
          const tooltip = new bootstrap.Tooltip(info.el, {
            title: `
          <div style="text-align:left;color:${colors[priority]}">
            <b style="color:${colors[priority]}">${info.event.title}</b><br>
            <small>${info.event.start.toLocaleString()} - ${info.event.end ? info.event.end.toLocaleString() : ""}</small><br>
            <span style="color:${colors[priority]}">Priority: ${priority}</span>
          </div>
        `,
            html: true,
            placement: "top",
            trigger: "hover",
            container: "body",
            sanitize: false
          });
          info.el._tooltip = tooltip;
        },
        headerToolbar: {
          left: 'today prev,next',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listYear'
        },
        views: {
          dayGridMonth: {
            titleFormat: {
              year: 'numeric',
              month: 'long'
            }
          },
          timeGridWeek: {
            titleFormat: {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              weekday: 'short'
            }
          },
          timeGridDay: {
            titleFormat: {
              year: 'numeric',
              month: 'short',
              day: 'numeric'
            }
          },
          listYear: {
            buttonText: 'Year',
            titleFormat: {
              year: 'numeric'
            }
          }
        },
        events: function (fetchInfo, successCallback, failureCallback) {
          axios.get('events.php')
            .then(res => {
              let baseEvents = Array.isArray(res.data) ? res.data : [];
              let expandedEvents = [];
              baseEvents.forEach(e => {
                expandedEvents.push(e);
                if (e.is_repeating && e.is_repeating !== "none") {
                  let startDate = new Date(e.start);
                  let endDate = e.end ? new Date(e.end) : null;
                  for (let i = 1; i <= 30; i++) {
                    let nextStart = new Date(startDate);
                    let nextEnd = endDate ? new Date(endDate) : null;
                    if (e.repeat_frequency === "daily") nextStart.setDate(startDate.getDate() + i);
                    if (e.repeat_frequency === "weekly") nextStart.setDate(startDate.getDate() + i * 7);
                    if (e.repeat_frequency === "monthly") nextStart.setMonth(startDate.getMonth() + i);
                    if (e.repeat_frequency === "yearly") nextStart.setFullYear(startDate.getFullYear() + i);
                    if (nextEnd) {
                      if (e.repeat_frequency === "daily") nextEnd.setDate(endDate.getDate() + i);
                      if (e.repeat_frequency === "weekly") nextEnd.setDate(endDate.getDate() + i * 7);
                      if (e.repeat_frequency === "monthly") nextEnd.setMonth(endDate.getMonth() + i);
                      if (e.repeat_frequency === "yearly") nextEnd.setFullYear(endDate.getFullYear() + i);
                    }
                    expandedEvents.push({
                      ...e,
                      start: nextStart.toISOString(),
                      end: nextEnd ? nextEnd.toISOString() : null,
                      id: e.id + "_r" + i,
                      parentId: e.id
                    });
                  }

                }
              });
              allEvents = expandedEvents;
              successCallback(expandedEvents);
            })
            .catch(() => failureCallback());
        }
        ,
        dayCellDidMount: function (info) {
          const cellDate = info.date;
          const Y = cellDate.getFullYear();
          const M = cellDate.getMonth();
          const D = cellDate.getDate();
          const weekday = cellDate.getDay();

          let numEl = info.el.querySelector(".fc-daygrid-day-number");
          if (!numEl) return;
          numEl.classList.remove("date-number-red", "date-number-green");
          if (weekday === 0) {
            numEl.classList.add("date-number-red");
          }
          const secondSat = nthWeekdayOfMonth(Y, M, 6, 2);
          const fourthSat = nthWeekdayOfMonth(Y, M, 6, 4);
          if ((secondSat && secondSat.getDate() === D) ||
            (fourthSat && fourthSat.getDate() === D)) {
            numEl.classList.add("date-number-red");
          }
          const firstSat = nthWeekdayOfMonth(Y, M, 6, 1);
          const thirdSat = nthWeekdayOfMonth(Y, M, 6, 3);
          if ((firstSat && firstSat.getDate() === D) ||
            (thirdSat && thirdSat.getDate() === D)) {
            numEl.classList.add("date-number-green");
          }
          if (info.view.type === "dayGridMonth") {
            let events = calendar.getEvents().filter(event => {
              return FullCalendar.formatDate(event.start, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
              }) ===
                FullCalendar.formatDate(info.date, {
                  year: 'numeric',
                  month: '2-digit',
                  day: '2-digit'
                });
            });
            let oldBadge = info.el.querySelector('.event-badge');
            if (oldBadge) oldBadge.remove();
            if (events.length > 0) {
              let badge = document.createElement("div");
              badge.classList.add("event-badge");
              badge.innerText = events.length;
              let tooltipHtml = events.map(e =>
                `<div style="color:${colors[e.extendedProps.priority]}"><b>${e.title}</b> <small>(${new Date(e.start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })})</small></div>`
              ).join("");
              new bootstrap.Tooltip(badge, {
                title: `<div style="text-align:left;">${tooltipHtml}</div>`,
                html: true,
                placement: "top",
                container: "body",
                sanitize: false
              });

              info.el.style.position = "relative";
              info.el.appendChild(badge);
            }
          }
        },
        dateClick: function (info) {
          document.getElementById('eventForm').reset();
          document.getElementById('eventId').value = "";
          document.getElementById('start').value = info.dateStr.slice(0, 16);
          document.getElementById('end').value = info.dateStr.slice(0, 16);
          document.getElementById('priority').value = "low";
          const repeatOptions = document.getElementById('repeatOptions');
          if (this.checked) {
            repeatOptions.style.display = "block";
          } else {
            repeatOptions.style.display = "none";
          }
          if (deleteBtn) deleteBtn.style.display = "none";
          new bootstrap.Offcanvas(document.getElementById('eventOffcanvas')).show();
        },
        eventClick: function (info) {

          const event = info.event;
          currentEvent = event;
          const baseId = info.event.extendedProps.parentId || info.event.id;
          document.getElementById('eventId').value = baseId;
          document.getElementById('title').value = event.title;
          document.getElementById('start').value = formatDateLocal(event.start);
          document.getElementById('end').value = event.end ? formatDateLocal(event.end) : '';
          document.getElementById('priority').value = event.extendedProps.priority || "low";
          document.getElementById('description').value = event.extendedProps.description || "";

          document.getElementById('repeatFrequency').value = event.extendedProps.repeat_frequency || "";

          document.getElementById('reminder').value =
            parseInt(event.extendedProps.reminder_before) == 0
              ? 0
              : event.extendedProps.reminder_before || 0;
          const repeatCheck = document.getElementById('repeatEventCheck');
          repeatCheck.checked = event.extendedProps.is_repeating == true;
          const repeatOptions = document.getElementById('repeatOptions');
          if (event.extendedProps.is_repeating) {
            repeatOptions.style.display = "block";
          } else {
            repeatOptions.style.display = "none";
          }
          const saveBtn = document.querySelector('#eventForm button[type="submit"]');
          if (event.extendedProps.user_id !== currentUser) {
            document.querySelectorAll('#eventForm input, #eventForm select, #eventForm textarea')
              .forEach(el => el.setAttribute('disabled', 'disabled'));
            if (deleteBtn) deleteBtn.style.display = "none";
            if (saveBtn) saveBtn.style.display = "none";
            document.getElementById("leaveBtn").style.display = "inline-block";
          } else {
            document.querySelectorAll('#eventForm input, #eventForm select, #eventForm textarea')
              .forEach(el => el.removeAttribute('disabled'));
            if (deleteBtn) deleteBtn.style.display = "inline-block";
            if (saveBtn) saveBtn.style.display = "inline-block";
            document.getElementById("leaveBtn").style.display = "none";
          }
          document.getElementById('repeatFrequency').value =
            event.extendedProps.repeat_frequency && event.extendedProps.repeat_frequency !== ""
              ? event.extendedProps.repeat_frequency
              : "";

          if (event.extendedProps.invitees && event.extendedProps.invitees.length > 0) {
            document.getElementById('invited_users').value = event.extendedProps.invitees.join(",");
          } else {
            console.log(event.extendedProps.invitees)
            document.getElementById('invited_users').value = "";
          }
          new bootstrap.Offcanvas(document.getElementById('eventOffcanvas')).show();
        },
        locale: {
          code: 'en',
          buttonText: {
            today: "TODAY",
            month: "MONTH",
            week: "WEEK",
            day: "DAY",
            list: "LIST"
          },
          allDayText: "ALL DAY"
        }
      });

      calendar.render();
      const searchModalEl = document.getElementById('searchModal');
      const searchModal = new bootstrap.Modal(searchModalEl);
      const searchBox = document.getElementById('searchBox');
      const searchResults = document.getElementById('searchResults');
      const filterButtons = document.querySelectorAll('#searchFilterBar [data-filter]');
      let currentFilter = "all";
      function createBadge(type) {
        if (type === "Today") return `<span class="badge bg-success ms-2">Today</span>`;
        if (type === "Upcoming") return `<span class="badge bg-primary ms-2">Upcoming</span>`;
        return `<span class="badge bg-secondary ms-2">Past</span>`;
      }
      function renderSection(title, items, collapsible = false) {
        if (!items.length) return null;
        const section = document.createElement('div');
        section.className = 'mb-3';

        const header = document.createElement('div');
        header.className = 'fw-bold bg-light px-2 py-1 border-bottom sticky-top d-flex justify-content-between align-items-center';
        header.textContent = title;
        if (collapsible) {
          const toggleBtn = document.createElement('button');
          toggleBtn.className = "btn btn-sm btn-link";
          toggleBtn.textContent = "Show";
          toggleBtn.addEventListener('click', () => {
            const collapsed = section.classList.toggle('collapsed');
            toggleBtn.textContent = collapsed ? "Show" : "Hide";
          });
          header.appendChild(toggleBtn);
        }
        section.appendChild(header);
        const container = document.createElement('div');
        if (collapsible) section.classList.add('collapsed');
        items.forEach(item => container.appendChild(item));
        section.appendChild(container);
        return section;
      }

      function renderResults(query) {
        searchResults.innerHTML = '';
        if (!query) {
          searchResults.innerHTML = `<div class="text-center text-muted py-4">Start typing to search events...</div>`;
          return;
        }
        const allEvents = calendar.getEvents();
        const matched = allEvents.filter(ev => {
          const t = ev.title.toLowerCase();
          const d = (ev.extendedProps.description || "").toLowerCase();
          return t.includes(query) || d.includes(query);
        });

        if (matched.length === 0) {
          searchResults.innerHTML = `<div class="list-group-item text-danger">No matching events found.</div>`;
          return;
        }
        const today = [], upcoming = [], past = [];
        const now = new Date();

        matched.forEach(event => {
          const startTime = new Date(event.start);
          const desc = event.extendedProps.description || "No description";

          let category = "Upcoming";
          if (startTime.toDateString() === now.toDateString()) category = "Today";
          else if (startTime < now) category = "Past";

          if (currentFilter !== "all" && currentFilter.toLowerCase() !== category.toLowerCase()) return;

          const item = document.createElement('a');
          item.href = '#';
          item.className = 'list-group-item list-group-item-action flex-column align-items-start';
          item.innerHTML = `
      <div class="d-flex w-100 justify-content-between align-items-center">
        <h6 class="mb-1">${event.title} ${createBadge(category)}</h6>
        <small class="text-muted">${startTime.toLocaleString()}</small>
      </div>
      <p class="mb-1 text-truncate" title="${desc}">${desc}</p>
    `;

          item.addEventListener('click', async (e) => {
            e.preventDefault();
            await focusEventOnCalendar(event);
            searchModal.hide();
            searchBox.value = '';
            const eventEl = calendar.getEventById(event.id);
            if (eventEl) {
              const el = document.querySelector(`[data-event-id="${event.id}"]`);
              if (el) {
                el.classList.add("highlight-event");

                // remove highlight class after animation ends
                el.addEventListener("animationend", () => {
                  el.classList.remove("highlight-event");
                }, { once: true });
              }
            }
          });

          // --- helper funcs ---
          function wait(ms) { return new Promise(r => setTimeout(r, ms)); }

          function getScrollableParent(el) {
            while (el) {
              const style = getComputedStyle(el);
              const overflowY = style.overflowY;
              if ((overflowY === 'auto' || overflowY === 'scroll') && el.scrollHeight > el.clientHeight) {
                return el;
              }
              el = el.parentElement;
            }
            return null;
          }

          async function focusEventOnCalendar(event) {
            // 1) switch view + goto date
            try {
              calendar.changeView('timeGridDay', event.start);
            } catch (err) {

            }
            try {
              calendar.gotoDate(event.start);
            } catch (err) { }
            await wait(120);
            const calEl = calendar.el || document.querySelector('#calendar') || document.body;
            const title = (event.title || '').trim();
            const start = new Date(event.start);
            const timeCandidates = [
              start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
              start.toLocaleTimeString(),
              start.toLocaleString()
            ];
            let foundEl = null;
            try {
              if (event.id != null) {
                const sel = calEl.querySelector(`[data-event-id="${event.id}"]`);
                if (sel) foundEl = sel;
              }
            } catch (err) { /* ignore */ }
            if (!foundEl) {
              const candidates = calEl.querySelectorAll('.fc-event, .fc-timegrid-event, .fc-list-item'); // cover common classes
              for (const c of candidates) {
                const txt = (c.innerText || '').trim();
                if (!txt) continue;
                if (title && txt.toLowerCase().includes(title.toLowerCase())) {
                  if (timeCandidates.some(t => t && txt.includes(t))) {
                    foundEl = c;
                    break;
                  }
                  if (!foundEl) foundEl = c;
                }
              }
            }
            let attempts = 0;
            while (!foundEl && attempts < 15) {
              await wait(100);
              attempts++;
              const candidates = calEl.querySelectorAll('.fc-event, .fc-timegrid-event, .fc-list-item');
              for (const c of candidates) {
                const txt = (c.innerText || '').trim();
                if (title && txt.toLowerCase().includes(title.toLowerCase())) {
                  if (timeCandidates.some(t => t && txt.includes(t))) { foundEl = c; break; }
                  if (!foundEl) foundEl = c;
                }
              }
            }
            if (!foundEl) {
              try { calendar.scrollToTime(event.start); } catch (err) { }
              return;
            }
            document.querySelectorAll('.highlight-event').forEach(n => n.classList.remove('highlight-event'));

            const scrollParent = getScrollableParent(foundEl) || document.scrollingElement || document.body;
            if (scrollParent === document.body || scrollParent === document.documentElement) {
              foundEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
              const parentRect = scrollParent.getBoundingClientRect();
              const elRect = foundEl.getBoundingClientRect();
              const offset = (elRect.top + scrollParent.scrollTop) - (parentRect.height / 2) + (elRect.height / 2);
              scrollParent.scrollTo({ top: Math.max(0, offset), behavior: 'smooth' });
            }
            foundEl.classList.add('highlight-event');
            setTimeout(() => foundEl.classList.remove('highlight-event'), 2500);
          }
          if (category === "Today") today.push(item);
          else if (category === "Upcoming") upcoming.push(item);
          else past.push(item);
        });

        [renderSection("Today", today),
        renderSection("Upcoming", upcoming),
        renderSection("Past", past, true)] // Past collapsible
          .forEach(section => { if (section) searchResults.appendChild(section); });
      }

      // Input handler
      searchBox.addEventListener('input', e => renderResults(e.target.value.trim().toLowerCase()));

      // Filter buttons
      filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          filterButtons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          currentFilter = btn.dataset.filter;
          renderResults(searchBox.value.trim().toLowerCase());
        });
      });

      // Clear on modal close
      searchModalEl.addEventListener('hidden.bs.modal', () => {
        searchBox.value = '';
        currentFilter = "all";
        filterButtons.forEach(b => b.classList.remove('active'));
        filterButtons[0].classList.add('active');
        searchResults.innerHTML = `<div class="text-center text-muted py-4">Start typing to search events...</div>`;
      });
      let selectedDateEl = null;

      /* ------------------ Badge CSS ------------------ */
      let style = document.createElement("style");
      style.innerHTML = `
           .event-badge {
              position: absolute;
              top: 4px;
              right: 4px;
              background: #d10000ff;
              color: white;
              font-size: 12px;
              font-weight: bold;
              width: 20px;
              height: 20px;
              border-radius: 50%;
              display: flex;
              align-items: center;
              justify-content: center;
              cursor: pointer;
              z-index:100;
          }
        `;
      document.head.appendChild(style);

      /* ------------------ Form & Buttons ------------------ */
      document.getElementById('newEventBtn').addEventListener('click', () => {

        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = "";
        document.getElementById('priority').value = "low";
        if (deleteBtn) deleteBtn.style.display = "none";
        new bootstrap.Offcanvas(document.getElementById('eventOffcanvas')).show();
      });

      /* ------------------ Form & Buttons ------------------ */
      document.getElementById('newEventBtnMb').addEventListener('click', () => {
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = "";
        document.getElementById('priority').value = "low";
        if (deleteBtn) deleteBtn.style.display = "none";
        new bootstrap.Offcanvas(document.getElementById('eventOffcanvas')).show();
      });

      document.getElementById('eventForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const repeatVal = document.getElementById("repeatFrequency").value;
        const isRepeating = document.getElementById("repeatEventCheck").checked ? 1 : 0;
        const formData = {
          id: document.getElementById('eventId').value,
          title: document.getElementById('title').value,
          start: document.getElementById('start').value,
          end: document.getElementById('end').value,
          priority: document.getElementById('priority').value,
          invite_rjcode: document.getElementById('invited_users').value,
          description: document.getElementById('description').value,
          is_repeating: isRepeating,
          repeat_frequency: isRepeating
            ? (repeatVal && repeatVal !== "--NONE--" ? repeatVal : null)
            : null,
          reminder: parseInt(document.getElementById("reminder").value)
        };
        axios.post('save_event.php', formData)
          .then(() => {
            calendar.refetchEvents();
            showToast('Event saved successfully!', 'success');
            bootstrap.Offcanvas.getInstance(document.getElementById('eventOffcanvas')).hide();
          })
          .catch(() => showToast('Could not save event', 'error'));
      });

      /* ------------------ Filters ------------------ */
      document.getElementById('searchBox').addEventListener('keyup', function () {
        const query = this.value.toLowerCase();
        const filtered = allEvents.filter(e => (e.title || "").toLowerCase().includes(query));
        calendar.removeAllEvents();
        calendar.addEventSource(filtered);
      });

      document.getElementById('todayBtn').addEventListener('click', () => {
        const today = new Date().toISOString().slice(0, 10);
        const filtered = allEvents.filter(e => (e.start || "").startsWith(today));
        calendar.removeAllEvents();
        calendar.addEventSource(filtered);
      });

      document.getElementById('upcomingBtn').addEventListener('click', () => {
        const now = new Date();
        const filtered = allEvents.filter(e => new Date((e.start || "").replace(" ", "T")) > now);
        calendar.removeAllEvents();
        calendar.addEventSource(filtered);
      });

      document.getElementById('previousBtn').addEventListener('click', () => {
        const now = new Date();
        const filtered = allEvents.filter(e => new Date((e.start || "").replace(" ", "T")) < now);
        calendar.removeAllEvents();
        calendar.addEventSource(filtered);
      });

      document.getElementById('allBtn').addEventListener('click', () => {
        calendar.removeAllEvents();
        calendar.addEventSource(allEvents);
      });

      /* ------------------ Delete Event ------------------ */
      if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
          const id = document.getElementById('eventId').value;
          if (!id) return;
          if (confirm("Are you sure you want to delete this event?")) {
            axios.post('delete_event.php', {
              id
            })
              .then(() => {
                calendar.refetchEvents();
                showToast('Event deleted.', 'success');
                bootstrap.Offcanvas.getInstance(document.getElementById('eventOffcanvas')).hide();
              })
              .catch(() => showToast("Could not delete event", 'error'));
          }
        });
      }

      document.getElementById('reminderCloseBtn').addEventListener('click', closeReminder);
      document.getElementById('reminderdontshowBtn').addEventListener('click', dontShowReminder);
      document.querySelectorAll(".cancel-invite-btn").forEach(btn => {
        btn.addEventListener("click", async function () {
          const inviteId = this.dataset.id;

          if (!confirm("Are you sure you want to cancel this invitation?")) return;

          try {
            const res = await fetch("cancel_invitation.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json"
              },
              body: JSON.stringify({ invite_id: inviteId })
            });

            const data = await res.json();

            if (data.success) {
              showToast('Invitation cancelled successfully!', 'success');

              this.closest("li").remove();
            } else {
              showToast(data.message, 'error');
            }
          } catch (err) {
            showToast("Request failed. Try again later.", 'error');
          }
        });
      });


      // Handle Accept / Reject actions
      document.querySelectorAll(".invite-action-btn").forEach(btn => {
        btn.addEventListener("click", async function () {
          const inviteId = this.dataset.id;
          const action = this.dataset.action;

          try {
            const res = await fetch("invite_action.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json"
              },
              body: JSON.stringify({ invite_id: inviteId, action })
            });

            const data = await res.json();

            if (data.success) {
              // Update UI with new status
              const container = this.closest("div");
              container.innerHTML = `<span class="badge bg-${action === 'accept' ? 'success' : 'danger'}">
                                  ${action.charAt(0).toUpperCase() + action.slice(1)}ed
                                </span>`;
            } else {
              showToast(data.message, "error");
            }
          } catch (err) {
            showToast("Request failed. Try again later.", "info");
          }
        });
      });

      document.getElementById("leaveBtn").addEventListener("click", function (e) {
        e.preventDefault();
        if (!currentEvent) {
          showToast("No event selected.", "error");
          return;
        }
        if (confirm("Are you sure you want to leave this event?")) {
          fetch("leave_event.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ event_id: currentEvent.id })
          })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                showToast("You have left the event.", "success");
                calendar.refetchEvents();
                bootstrap.Offcanvas.getInstance(document.getElementById('eventOffcanvas')).hide();
              } else {
                showToast(data.message || "Could not leave event", "error");
              }
            });
        }
      });


      function showToast(message, type = "info", duration = 7000) {
        const container = document.getElementById("toastContainer");
        const toast = document.createElement("div");
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add("show"), 100);
        setTimeout(() => {
          toast.classList.remove("show");
          setTimeout(() => toast.remove(), 500);
        }, duration);
      }
    });
  </script>
  <script src="./js/flatpickr.js"></script>

  <script>
    // ✅ Swipe down to close (mobile-like behavior)
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      let startY = 0;
      modal.addEventListener('touchstart', e => {
        startY = e.touches[0].clientY;
      });
      modal.addEventListener('touchmove', e => {
        let endY = e.touches[0].clientY;
        if (endY - startY > 100) { // swipe down
          const bsModal = bootstrap.Modal.getInstance(modal);
          bsModal.hide();
        }
      });
    });
  </script>


</body>

</html>