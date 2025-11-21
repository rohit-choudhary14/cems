<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_rjcode'])) {
  header("Location: http://10.130.8.68/intrahc/");
  exit;
}
$user_rjcode = $_SESSION['user_rjcode'];
$IncommingNotifications = [];
$selfRequestNotification = [];
if ($user_rjcode) {
  $stmt = $stmt = $pdo->prepare("
    SELECT ei.id, e.title, ei.inviter_rjcode, ei.status
    FROM event_invitations ei
    JOIN events e ON ei.event_id = e.id
    WHERE ei.invitee_rjcode = ?
      AND ei.status = 'pending'
    ORDER BY ei.created_at DESC
");
  $stmt->execute([$user_rjcode]);
  $IncommingNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->execute([$user_rjcode]);
  $IncommingNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt = $pdo->prepare("
    SELECT ei.id, e.title AS event_title, ei.invitee_rjcode, ei.status, ei.created_at
    FROM event_invitations ei
    JOIN events e ON ei.event_id = e.id
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
  <link href="./css/bootstrap.min.css" rel="stylesheet">
  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="./css/flatpickr.min.css">
  <link rel="stylesheet" href="./css/bootstrap-icons.css">

  <script src="./js/index.global.min.js"></script>
  <script src="./js/axios.min.js"></script>
  <script src="./js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="./css/font-awesome.min.css">

  <script src="./js/jquery-3.6.0.min.js"></script>
  <script src="./js/jquery-ui.min.js"></script>
  <link rel="stylesheet" href="./css/jquery-ui.css">
  <style>
    body,
    html {
      height: 100%;
      overflow-x: hidden;
      font-family: "Segoe UI", sans-serif;
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
      background: #022f66;
    }

    #miniCalendar {
      position: relative;
      margin-bottom: 30px;
    }

    #left_section_for_miniCal {
      margin-top: 85px;
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
      padding: 15px;
    }

    #calendar {
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
      margin-top: 10px;
      margin-bottom: 50px;
    }

    .ui-datepicker-inline {

      width: -webkit-fill-available !important;
    }

    .selected-date a {
      background-color: #fff !important;
      color: #000 !important;
      font-weight: bold;
    }

    .date-number-red-holiday a {
      color: red !important;
    }

    .date-number-rh a {
      background-color: #444 !important;
      color: #fff !important;
      transition: background-color 0.3s ease;
    }

    .date-number-gh a {
      background-color: #e42727ff !important;
      color: #fff !important;
      transition: background-color 0.3s ease;
    }

    .date-number-sunday a {
      background-color: #d33333ff !important;
      color: #fff !important;
      transition: background-color 0.3s ease;
      font-weight: 700 !important;
    }

    .date-number-1th-saturday a {
      background-color: #439e4bff !important;
      color: #fff !important;
      transition: background-color 0.3s ease;
      font-weight: 700 !important;
    }

    .date-number-3th-saturday a {
      background-color: #439e4bff !important;
      color: #fff !important;
      transition: background-color 0.3s ease;
      font-weight: 700 !important;
    }

    .date-number-2th-saturday a {
      background-color: #d33333ff !important;
      color: #fff !important;
      transition: background-color 0.3s ease;
      font-weight: 700 !important;
    }

    .date-number-4th-saturday a {
      background-color: #d33333ff !important;
      color: #fff !important;
      transition: background-color 0.3s ease;
      font-weight: 700 !important;
    }

    .date-number-red a {
      color: red !important;
    }

    .date-number-green a {
      color: green !important;
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

    #calendar .fc-header-toolbar .fc-toolbar-chunk:nth-child(2)>div {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      align-items: center;
    }

    @media (max-width: 768px) {
      #calendar {
        padding: 15px;
        margin-bottom: 180px;
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
      border-radius: 5px !important;
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
      /* top: -6px; */
      /* right: -10px; */
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


    .notification-container {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000000;
      display: flex;
      flex-direction: column-reverse;
      gap: 12px;
    }

    .notification {
      position: relative;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 18px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      color: #fff;
      font-family: "Segoe UI", sans-serif;
      font-size: 15px;
      font-weight: 500;
      min-width: 280px;
      max-width: 350px;
      backdrop-filter: blur(8px);
      animation: slideIn 0.35s ease-out;
      overflow: hidden;
    }

    .notification img {
      width: 26px;
      height: 26px;
      flex-shrink: 0;
    }

    .notification .close-btn {
      margin-left: auto;
      cursor: pointer;
      font-size: 18px;
      font-weight: bold;
      opacity: 0.7;
      transition: 0.2s;
    }

    .notification .close-btn:hover {
      opacity: 1;
    }

    .progress {
      position: absolute;
      bottom: 0;
      left: 0;
      height: 4px;
      background: rgba(255, 255, 255, 0.7);
      width: 100%;
      animation: shrink linear forwards;
    }

    .success {
      background: linear-gradient(135deg, #28a745, #218838);
    }

    .error {
      background: linear-gradient(135deg, #dc3545, #a71d2a);
    }

    .info {
      background: linear-gradient(135deg, #17a2b8, #11707f);
    }

    .warning {
      background: linear-gradient(135deg, #ffc107, #e0a800);
      color: #000;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(50px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes shrink {
      from {
        width: 100%;
      }

      to {
        width: 0;
      }
    }
  </style>
  <style>
    .overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 1040;
    }

    /* Panel */
    .notification-panel {
      position: fixed;
      top: 0;
      right: -450px;
      width: 400px;
      height: 100%;
      background: #f9f9fb;
      box-shadow: -5px 0 20px rgba(0, 0, 0, 0.2);
      border-left: 1px solid #ddd;
      border-radius: 12px 0 0 12px;
      padding: 20px;
      transition: right 0.3s ease-in-out;
      z-index: 1050;
      overflow-y: auto;
    }

    .notification-panel.active {
      right: 0;
    }

    .panel-header {
      border-bottom: 1px solid #e2e2e2;
      padding-bottom: 10px;
    }

    .custom-tabs .nav-link {
      border: none;
      font-weight: 500;
      color: #555;
    }

    .custom-tabs .nav-link.active {
      color: #fff;
      background: #022f66;
      border-radius: 20px;
    }

    /* Notification Cards */
    .notif-card {
      background: #fff;
      border-radius: 12px;
      padding: 15px;
      margin-bottom: 12px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
      transition: transform 0.2s;
    }

    .notif-card:hover {
      transform: translateY(-2px);
    }

    .notif-title {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .notif-body {
      font-size: 0.9rem;
      margin-bottom: 10px;
    }

    .notif-actions {
      display: flex;
      gap: 8px;
    }

    .notification-panel.mobile {
      display: none;
    }

    @media (max-width: 768px) {
      .notification-panel {
        width: 100%;
        height: 70%;
        max-height: 80%;
        right: 0;
        bottom: -100%;
        top: auto;
        border-radius: 16px 16px 0 0;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
        transition: bottom 0.3s ease-in-out;
      }

      .notification-panel.active {
        bottom: 0;
      }

      /* Tabs pinned at top inside bottom sheet */
      .custom-tabs {
        position: sticky;
        top: 0;
        background: #fff;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        z-index: 10;
      }

      .panel-header {
        padding: 12px 16px;
        font-size: 1.1rem;
      }

      .notif-card {
        padding: 14px;
      }
    }


    /* For very small screens (e.g. 360px width phones) */
    @media (max-width: 480px) {
      .notif-card {
        font-size: 0.85rem;
        padding: 15px;
      }

      .notif-title {
        font-size: 0.95rem;
      }

      .notif-body {
        font-size: 0.8rem;
      }
    }

    /* Bottom Sheet Notification Panel */
    .notification-panel.mobile {
      position: fixed;
      left: 0;
      right: 0;
      bottom: -100%;
      height: 75%;
      background: #fff;
      border-radius: 16px 16px 0 0;
      box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
      transition: bottom 0.35s ease-in-out;
      z-index: 1050;
      overflow-y: auto;
      padding-bottom: 20px;
    }

    .notification-panel.mobile.active {
      bottom: 0;
    }

    .panel-header {
      padding: 12px 16px;
      background: #022f66;
      color: #fff;
      border-radius: 16px 16px 0 0;
    }

    .notif-card {
      background: #f9f9f9;
      border-radius: 10px;
      padding: 12px;
      margin-bottom: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .notif-title {
      font-weight: 600;
      margin-bottom: 4px;
    }

    .notif-body {
      font-size: 0.9rem;
      color: #555;
    }

    .notif-actions button {
      margin-right: 5px;
    }

    /* Overlay */
    .notif-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      z-index: 1040;
    }

    /* Bottom Sheet */
    .notif-sheet {
      position: fixed;
      bottom: -100%;
      left: 0;
      right: 0;
      height: 70%;
      background: #fff;
      border-radius: 16px 16px 0 0;
      box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.2);
      z-index: 1050;
      transition: bottom 0.3s ease-in-out;
      display: flex;
      flex-direction: column;
    }

    /* Active states */
    .notif-sheet.active {
      bottom: 0;
    }

    .notif-overlay.active {
      display: block;
    }

    /* Header */
    .sheet-header {
      padding: 10px;
      border-bottom: 1px solid #ddd;
      position: relative;
    }

    .drag-handle {
      width: 50px;
      height: 5px;
      background: #ccc;
      border-radius: 5px;
      margin: 6px auto;
    }

    .sheet-content {
      overflow-y: auto;
      flex-grow: 1;
      padding: 10px;
    }

    .nav-tabs .nav-link {
      color: #6c757d;
      font-weight: 600;
      transition: box-shadow 0.3s ease;

    }

    .nav-tabs .nav-link.text-warning.active {
      color: #856404 !important;
      border-bottom: 3px solid #ffc107 !important;
      box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
      border-radius: 0.25rem 0.25rem 0 0;
    }

    .nav-tabs .nav-link.text-success.active {
      color: #155724 !important;
      border-bottom: 3px solid #28a745 !important;
      box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
      border-radius: 0.25rem 0.25rem 0 0;
    }

    .nav-tabs .nav-link.text-danger.active {
      color: #721c24 !important;
      border-bottom: 3px solid #dc3545 !important;
      box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
      border-radius: 0.25rem 0.25rem 0 0;
    }

    .nav-tabs .nav-link.text-secondary.active {
      color: #383d41 !important;
      border-bottom: 3px solid #6c757d !important;
      box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
      border-radius: 0.25rem 0.25rem 0 0;
    }

    #holidayList {
      display: grid;
      gap: 10px;

      color: red;
    }

    #holidayList div {
      background-color: #e1e1e1;
      /* optional for visual clarity */
      padding: 8px;
      border-radius: 4px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Container for tag input and suggestions */
    .invite-wrapper {
      position: relative;
    }

    /* Tag container style */
    #tag-container {
      min-height: 42px;
      padding: 0.375rem 0.75rem;
      display: flex;
      flex-wrap: wrap;
      gap: 0.25rem;
      align-items: center;
      cursor: text;
    }

    /* Input inside tag container */
    .user-search-input {
      border: none;
      flex-grow: 1;
      min-width: 120px;
      outline: none;
      padding: 0.25rem 0;
      font-size: 1rem;
    }

    /* Suggestion dropdown */
    .user-suggestions {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      z-index: 1050;
      display: none;
      /* JS will toggle this */
      max-height: 150px;
      overflow-y: auto;
      background-color: #fff;
      border: 1px solid #ced4da;
      border-top: none;
      border-radius: 0 0 0.375rem 0.375rem;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    /* Each suggestion item */
    .user-suggestions .list-group-item {
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .user-suggestions .list-group-item:hover {
      background-color: #f8f9fa;
    }

    /* Responsive tweaks */
    @media (max-width: 576px) {
      #tag-container {
        padding: 0.5rem;
      }

      .user-search-input {
        font-size: 0.95rem;
      }

      .user-suggestions {
        font-size: 0.95rem;
        max-height: 160px;
      }
    }
  </style>

</head>

<body>

  <nav class="navbar navbar-expand-lg navbar-dark d-lg-flex" style="background:#022f66">
    <div class="container-fluid">

      <img src="./images/logo.png" style="height:50px" />

      <a class="navbar-brand" href="#" style="color:white;margin-left:20px"><?= $_SESSION['program_name'] ?></a>
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
                  <?= htmlspecialchars($_SESSION['user_rjcode'] . '(' . $_SESSION['user_name'] . ')') ?>
                </a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li style="padding: 2px;">
                <p class="btn btn-warning w-100">
                  <a href="http://10.130.8.68/intrahc/"><i class="fa fa-arrow"></i> Back to sso</a>
                </p>
                <a href="logout.php" class="btn btn-danger w-100">Logout</a>
              </li>
            </ul>
          </div>
          <div class="dropdown">
            <button id="notificationBell" class="btn btn-link position-relative">
              <i class="bi bi-bell fs-4 text-white"></i>
              <sup class="position-absolute start-100 translate-middle badge rounded-pill bg-danger" style="top:15px">
                <?= count($IncommingNotifications) + count($selfRequestNotification) ?>
              </sup>
            </button>
            <div id="notificationOverlay" class="overlay"></div>
            <div id="notificationPanel" class="notification-panel">
              <div class="panel-header d-none justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">🔔 Notifications</h5>
                <button id="closePanel" class="btn btn-sm btn-outline-light">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
              <!-- Tabs -->
              <ul class="nav nav-tabs custom-tabs mt-3" id="notifTabs" role="tablist">
                <li class="nav-item">
                  <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#allTab">All</button>
                </li>
                <li class="nav-item">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#invitesTab">Invitations</button>
                </li>
                <li class="nav-item">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#updatesTab">Updates</button>
                </li>
              </ul>

              <!-- Tab Content -->
              <div class="tab-content mt-3">

                <!-- All Tab -->
                <div class="tab-pane fade show active" id="allTab">
                  <?php if (!empty($IncommingNotifications) || !empty($selfRequestNotification)): ?>
                    <?php foreach ($IncommingNotifications as $n): ?>
                      <div class="notif-card invite">
                        <div class="notif-title">
                          <strong><?= htmlspecialchars($n['inviter_rjcode']) ?></strong> invited you
                        </div>
                        <div class="notif-body">Event: <em><?= htmlspecialchars($n['title']) ?></em></div>
                        <div class="notif-actions">
                          <?php if ($n['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-success invite-action-btn" data-id="<?= $n['id'] ?>" data-action="accept">Accept</button>
                            <button class="btn btn-sm btn-outline-danger invite-action-btn" data-id="<?= $n['id'] ?>" data-action="reject">Reject</button>
                          <?php else: ?>
                            <span class="badge bg-secondary"><?= ucfirst($n['status']) ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                    <?php foreach ($selfRequestNotification as $note): ?>
                      <div class="notif-card update">
                        <div class="notif-title">To: <strong><?= htmlspecialchars($note['invitee_rjcode']) ?></strong></div>
                        <div class="notif-body">Event: <em><?= htmlspecialchars($note['event_title']) ?></em></div>
                        <div class="notif-actions">
                          <?php if ($note['status'] === 'pending'): ?>
                            <button class="badge btn btn-sm  btn-warning text-white">Pending</button>
                            <button class="btn btn-sm btn-danger cancel-invite-btn" data-id="<?= $note['id'] ?>">Cancel</button>
                          <?php elseif ($note['status'] === 'accepted'): ?>
                            <span class="badge bg-success">Accepted</span>
                          <?php else: ?>
                            <span class="badge bg-danger">Rejected</span>
                          <?php endif; ?>
                        </div>
                        <div class="small text-muted"><?= $note['created_at'] ?></div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-center text-muted py-4">No notifications 📭</div>
                  <?php endif; ?>
                </div>

                <!-- Invitations Tab -->
                <div class="tab-pane fade" id="invitesTab">
                  <?php if (!empty($IncommingNotifications)): ?>
                    <?php foreach ($IncommingNotifications as $n): ?>
                      <div class="notif-card invite">
                        <div class="notif-title">
                          <strong><?= htmlspecialchars($n['inviter_rjcode']) ?></strong> invited you
                        </div>
                        <div class="notif-body">Event: <em><?= htmlspecialchars($n['title']) ?></em></div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-center text-muted py-4">No invitations 👀</div>
                  <?php endif; ?>
                </div>

                <!-- Updates Tab -->
                <div class="tab-pane fade" id="updatesTab">
                  <?php if (!empty($selfRequestNotification)): ?>
                    <?php foreach ($selfRequestNotification as $note): ?>
                      <div class="notif-card update">
                        <div class="notif-title">Invite to <strong><?= htmlspecialchars($note['invitee_rjcode']) ?></strong>
                        </div>
                        <div class="notif-body">Status: <span class="badge bg-info"><?= ucfirst($note['status']) ?></span>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="text-center text-muted py-4">No updates 💤</div>
                  <?php endif; ?>
                </div>

              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </nav>
  <!-- Mobile Bottom Navigation -->
  <!-- Mobile Bottom Nav -->
  <div class="mobile-bottom-nav d-md-none" style="background:#022f66">
    <div class="nav-container">
      <a href="#" class="nav-item"><i class="fa fa-home"></i></a>
      <a href="#" class="nav-item" data-bs-toggle="modal" data-bs-target="#searchModal">
        <i class="fa fa-search"></i>
      </a>
      <a href="#" class="nav-item">
        <button class="btn btn-light" id="newEventBtnMb">+ New Event</button>
      </a>
      <a href="#" class="nav-item">
        <div class="dropdown">
          <!-- Bell Icon -->
          <button id="notificationBellMb" class="btn btn-link position-relative">
            <i class="bi bi-bell fs-4 text-white"></i>
            <sup class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="margin-top:15px">
              <?= count($IncommingNotifications) + count($selfRequestNotification) ?>
            </sup>
          </button>
        </div>
      </a>
      <a href="#" class="nav-item" data-bs-toggle="modal" data-bs-target="#profileModal">
        <i class="fa fa-user"></i>
      </a>
    </div>
  </div>

  <!-- Overlay -->
  <div id="notifOverlayMb" class="notif-overlay"></div>

  <!-- Notification Bottom Sheet -->
  <div id="notifSheetMb" class="notif-sheet">
    <div class="sheet-header d-flex justify-content-between align-items-center">
      <div class="drag-handle"></div>

      <button id="closeSheetMb" class="btn btn-sm btn-outline-light">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="sheet-content">

      <ul class="nav nav-tabs custom-tabs mt-3" id="notifTabsMb">
        <li class="nav-item">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#allTabMb">All</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#invitesTabMb">Invitations</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#updatesTabMb">Updates</button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content mt-3 px-2">
        <!-- All -->
        <div class="tab-pane fade show active" id="allTabMb">
          <?php if (!empty($IncommingNotifications) || !empty($selfRequestNotification)): ?>
            <?php foreach ($IncommingNotifications as $n): ?>
              <div class="notif-card invite">
                <div class="notif-title">
                  <strong><?= htmlspecialchars($n['inviter_rjcode']) ?></strong> invited you
                </div>
                <div class="notif-body">Event: <em><?= htmlspecialchars($n['title']) ?></em></div>
                <div class="notif-actions">
                  <?php if ($n['status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-success invite-action-btn" data-id="<?= $n['id'] ?>" data-action="accept">Accept</button>
                    <button class="btn btn-sm btn-outline-danger invite-action-btn" data-id="<?= $n['id'] ?>" data-action="reject">Reject</button>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?= ucfirst($n['status']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
            <?php foreach ($selfRequestNotification as $note): ?>
              <div class="notif-card update">
                <div class="notif-title">To: <strong><?= htmlspecialchars($note['invitee_rjcode']) ?></strong></div>
                <div class="notif-body">Event: <em><?= htmlspecialchars($note['event_title']) ?></em></div>
                <div class="notif-actions">
                  <?php if ($note['status'] === 'pending'): ?>
                    <button class="badge btn btn-sm  btn-warning text-white">Pending</button>
                    <button class="btn btn-sm btn-danger cancel-invite-btn" data-id="<?= $note['id'] ?>">Cancel</button>
                  <?php elseif ($note['status'] === 'accepted'): ?>
                    <span class="badge bg-success">Accepted</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Rejected</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center text-muted py-4">No notifications 📭</div>
          <?php endif; ?>
        </div>

        <!-- Invitations -->
        <div class="tab-pane fade" id="invitesTabMb">
          <?php if (!empty($IncommingNotifications)): ?>
            <?php foreach ($IncommingNotifications as $n): ?>
              <div class="notif-card invite">
                <div class="notif-title"><strong><?= htmlspecialchars($n['inviter_rjcode']) ?></strong> invited you</div>
                <div class="notif-body">Event: <em><?= htmlspecialchars($n['title']) ?></em></div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center text-muted py-4">No invitations 👀</div>
          <?php endif; ?>
        </div>

        <!-- Updates -->
        <div class="tab-pane fade" id="updatesTabMb">
          <?php if (!empty($selfRequestNotification)): ?>
            <?php foreach ($selfRequestNotification as $note): ?>
              <div class="notif-card update">
                <div class="notif-title">Invite to <strong><?= htmlspecialchars($note['invitee_rjcode']) ?></strong></div>
                <div class="notif-body">Status: <span class="badge bg-info"><?= ucfirst($note['status']) ?></span></div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center text-muted py-4">No updates 💤</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>


  <!-- 📱 Bottom Sheet Notification Panel -->
  <div id="notificationPanelMb" class="notification-panel mobile">


    <!-- Tabs -->
    <ul class="nav nav-tabs custom-tabs mt-3" id="notifTabsMb">
      <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#allTabMb">All</button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#invitesTabMb">Invitations</button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#updatesTabMb">Updates</button>
      </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content mt-3 px-2">
      <!-- All -->
      <div class="tab-pane fade show active" id="allTabMb">
        <?php if (!empty($IncommingNotifications) || !empty($selfRequestNotification)): ?>
          <?php foreach ($IncommingNotifications as $n): ?>
            <div class="notif-card invite">
              <div class="notif-title">
                <strong><?= htmlspecialchars($n['inviter_rjcode']) ?></strong> invited you
              </div>
              <div class="notif-body">Event: <em><?= htmlspecialchars($n['title']) ?></em></div>
              <div class="notif-actions">
                <?php if ($n['status'] === 'pending'): ?>
                  <button class="btn btn-sm btn-success invite-action-btn" data-id="<?= $n['id'] ?>" data-action="accept">Accept</button>
                  <button class="btn btn-sm btn-outline-danger invite-action-btn" data-id="<?= $n['id'] ?>" data-action="reject">Reject</button>
                <?php else: ?>
                  <span class="badge bg-secondary"><?= ucfirst($n['status']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
          <?php foreach ($selfRequestNotification as $note): ?>
            <div class="notif-card update">
              <div class="notif-title">To: <strong><?= htmlspecialchars($note['invitee_rjcode']) ?></strong></div>
              <div class="notif-body">Event: <em><?= htmlspecialchars($note['event_title']) ?></em></div>
              <div class="notif-actions">
                <?php if ($note['status'] === 'pending'): ?>
                  <span class="badge bg-warning text-dark">Pending</span>
                  <button class="btn btn-sm btn-outline-danger cancel-invite-btn" data-id="<?= $note['id'] ?>">Cancel</button>
                <?php elseif ($note['status'] === 'accepted'): ?>
                  <span class="badge bg-success">Accepted</span>
                <?php else: ?>
                  <span class="badge bg-danger">Rejected</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center text-muted py-4">No notifications 📭</div>
        <?php endif; ?>
      </div>

      <!-- Invitations -->
      <div class="tab-pane fade" id="invitesTabMb">
        <?php if (!empty($IncommingNotifications)): ?>
          <?php foreach ($IncommingNotifications as $n): ?>
            <div class="notif-card invite">
              <div class="notif-title"><strong><?= htmlspecialchars($n['inviter_rjcode']) ?></strong> invited you</div>
              <div class="notif-body">Event: <em><?= htmlspecialchars($n['title']) ?></em></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center text-muted py-4">No invitations 👀</div>
        <?php endif; ?>
      </div>

      <!-- Updates -->
      <div class="tab-pane fade" id="updatesTabMb">
        <?php if (!empty($selfRequestNotification)): ?>
          <?php foreach ($selfRequestNotification as $note): ?>
            <div class="notif-card update">
              <div class="notif-title">Invite to <strong><?= htmlspecialchars($note['invitee_rjcode']) ?></strong></div>
              <div class="notif-body">Status: <span class="badge bg-info"><?= ucfirst($note['status']) ?></span></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-center text-muted py-4">No updates 💤</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content shadow border-0 rounded-4 overflow-hidden">
        <div class="modal-header text-white py-3" style="background-color:#022f66">
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
          <p>
            <i class="fa fa-user-circle"></i>
            <?= htmlspecialchars($_SESSION['user_rjcode']) ?>
          </p>
          <hr>
          <p class="btn btn-warning w-100">
            <a href="http://10.130.8.68/intrahc/"><i class="fa fa-arrow"></i> Back to sso</a>
          </p>
          <hr>
          <a href="logout.php" class="btn btn-danger w-100">Logout</a>
        </div>
      </div>
    </div>
  </div>
  <div class="container-fluid mt-3" style="margin-top:100px;">
    <div class="row">
      <div class="col-md-4" id="left_section_for_miniCal">
        <div class="row">
          <div class="col-12">
            <div id="miniCalendar"></div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div id="holidayList">
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-8">
        <ul class="nav nav-tabs" style="margin-top:85px;">
          <li class="nav-item col-6 col-md-3">
            <a class="nav-link text-warning fw-semibold" id="todayBtn" data-bs-toggle="tab" href="#today" role="tab"
              aria-controls="today" aria-selected="true">
              📅 Today
            </a>
          </li>
          <li class="nav-item col-6 col-md-3">
            <a class="nav-link text-success fw-semibold" id="upcomingBtn" data-bs-toggle="tab" href="#upcoming"
              role="tab" aria-controls="upcoming" aria-selected="false">
              ⏩ Upcoming
            </a>
          </li>
          <li class="nav-item col-6 col-md-3">
            <a class="nav-link text-danger fw-semibold" id="previousBtn" data-bs-toggle="tab" href="#previous"
              role="tab" aria-controls="previous" aria-selected="false">
              ⏪ Previous
            </a>
          </li>
          <li class="nav-item col-6 col-md-3">
            <a class="nav-link text-secondary fw-semibold" id="allBtn" data-bs-toggle="tab" href="#all" role="tab"
              aria-controls="all" aria-selected="false">
              📋 All Events
            </a>
          </li>
        </ul>

        <div class="tab-content mt-3">
          <div class="tab-pane fade show active" role="tabpanel" aria-labelledby="todayBtn">
          </div>
          <div class="tab-pane fade" role="tabpanel" aria-labelledby="upcomingBtn">
          </div>
          <div class="tab-pane fade" role="tabpanel" aria-labelledby="previousBtn">
          </div>
          <div class="tab-pane fade" role="tabpanel" aria-labelledby="allBtn">
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
        <div class="mb-3 invite-wrapper">
          <label for="invited_users" class="form-label">Invite People / Subordinates (Enter rjcodes)</label>
          <div id="selected-user-tag-container">

          </div>
          <div id="tag-container" class="form-control d-flex flex-wrap align-items-center">
            <input
              type="text"
              id="user-search"
              class="user-search-input"
              placeholder="Type rjcode..."
              autocomplete="off" />
          </div>

          <input type="hidden" name="invited_users" id="invited_users">

          <ul id="user-suggestions" class="list-group user-suggestions"></ul>
        </div>


        <div class="mb-3">
          <label class="form-label" for="priority">
            <i class="bi bi-exclamation-circle-fill me-2"></i>Priority
          </label>
          <select id="priority" class="form-control">
            <option value="low"> <span>🟩</span> Low</option>
            <option value="medium"> <span>🟨</span> Medium</option>
            <option value="high"><span>🟥</span> High</option>
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
  <div class="notification-container" id="notification-container"></div>
  <script>
    $(document).ready(function() {
      let selectedUsers = [];

      function renderTags() {
        const container = $('#tag-container');
        container.find('.tag').remove();
        selectedUsers.forEach(user => {
          $('<span class="badge bg-primary me-1 mb-1 tag">')
            .text(user.rjcode)
            .append(
              $('<span class="ms-1" style="cursor:pointer;">&times;</span>')
              .click(() => {
                selectedUsers = selectedUsers.filter(u => u.rjcode !== user.rjcode);
                renderTags();
              })
            )
            .insertBefore('#user-search');
        });
        $('#invited_users').val(selectedUsers.map(u => u.rjcode).join(','));
      }

      $('#user-search').on('input', function() {
        const query = $(this).val();
        if (query.length < 2) {
          $('#user-suggestions').hide();
          return;
        }

        $.ajax({
          url: 'searchUsers.php',
          method: 'GET',
          dataType: 'json',
          data: {
            q: query
          },
          success: function(data) {
            if (!Array.isArray(data)) {
              console.error("Response is not an array:", data);
              return;
            }

            const suggestions = $('#user-suggestions');
            suggestions.empty().show();
            console.log(suggestions)
            if (data.length === 0) {
              suggestions.append('<li class="list-group-item text-muted">No results</li>');
            } else {
              data.forEach(user => {
                if (selectedUsers.some(u => u.rjcode === user.rjcode)) return;
                const item = $('<li class="list-group-item list-group-item-action">').text(user.rjcode + "-" + user.display_name);
                item.click(function() {
                  selectedUsers.push(user);
                  renderTags();
                  $('#user-search').val('');
                  suggestions.hide();
                });
                suggestions.append(item);
              });
            }
          }

        });
      });

      // Hide suggestions when clicking outside
      $(document).click(function(e) {
        if (!$(e.target).closest('#tag-container, #user-suggestions').length) {
          $('#user-suggestions').hide();
        }
      });
    });
  </script>



  <script>
    const bell = document.getElementById("notificationBell");
    const panel = document.getElementById("notificationPanel");
    const overlay = document.getElementById("notificationOverlay");
    const closeBtn = document.getElementById("closePanel");
    bell.addEventListener("click", () => {
      panel.classList.add("active");
      overlay.style.display = "block";
    });
    closeBtn.addEventListener("click", () => {
      panel.classList.remove("active");
      overlay.style.display = "none";
    });
    overlay.addEventListener("click", () => {
      panel.classList.remove("active");
      overlay.style.display = "none";
    });
    const notifBtnMb = document.getElementById("notificationBellMb");
    const notifSheetMb = document.getElementById("notifSheetMb");
    const notifOverlayMb = document.getElementById("notifOverlayMb");
    const closeBtnMb = document.getElementById("closeSheetMb");

    function openSheet() {
      notifSheetMb.classList.add("active");
      notifOverlayMb.classList.add("active");
    }

    function closeSheet() {
      notifSheetMb.classList.remove("active");
      notifOverlayMb.classList.remove("active");
    }
    notifBtnMb.addEventListener("click", openSheet);
    closeBtnMb.addEventListener("click", closeSheet);
    notifOverlayMb.addEventListener("click", closeSheet);
    let startY = 0;
    notifSheetMb.addEventListener("touchstart", (e) => {
      startY = e.touches[0].clientY;
    });
    notifSheetMb.addEventListener("touchmove", (e) => {
      let currentY = e.touches[0].clientY;
      if (currentY - startY > 100) {
        closeSheet();
      }
    });
  </script>
  <script>
    const currentUser = "<?php echo $_SESSION['user_rjcode']; ?>";
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
      document.getElementById("repeatEventCheck").addEventListener("change", function() {
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
      let holidayMap = {};

      function renderHolidayList(holidayListNameForRhcJaipur) {
        let filteredHolidays = holidayListNameForRhcJaipur.filter(holiday =>
          holiday.holiday_name !== "Sunday" &&
          holiday.holiday_name !== "Second Saturday" &&
          holiday.holiday_name !== "Fourth Saturday"
        );
        filteredHolidays.sort((a, b) => new Date(a.leave_date) - new Date(b.leave_date));
        let html = "";
        let grouped = [];
        for (let i = 0; i < filteredHolidays.length; i++) {
          let current = filteredHolidays[i];
          let currentDate = new Date(current.leave_date);
          let currentName = current.holiday_name;
          let startDate = currentDate;
          let endDate = currentDate;
          while (i + 1 < filteredHolidays.length) {
            let next = filteredHolidays[i + 1];
            let nextDate = new Date(next.leave_date);
            let nextName = next.holiday_name;
            if (nextName === currentName && (nextDate - endDate) === 86400000) {
              endDate = nextDate;
              i++;
            } else {
              break;
            }
          }
          let options = {
            day: 'numeric',
            month: 'short'
          };
          let startStr = startDate.toLocaleDateString(undefined, options);
          let endStr = endDate.toLocaleDateString(undefined, options);

          if (startStr === endStr) {
            html += `<div><strong>${startStr}:</strong> ${currentName}</div>`;
          } else {
            html += `<div><strong>${startStr} - ${endStr}:</strong> ${currentName}</div>`;
          }
        }
        $("#holidayList").html(html);
      }



      function fetchHolidays(year, month) {
        $.ajax({
          url: "proxy.php",
          method: "POST",
          contentType: "application/json",
          data: JSON.stringify({
            year: year,
            month: month
          }),
          success: function(response) {

            if (response.status === "1" && response.holiday_list) {
              holidayMap = {};
              holidayListNameForRhcJaipur = [];
              response.holiday_list.forEach(h => {
                let courts = h.court;
                if (typeof courts === "string") {
                  courts = courts.split(",");
                }

                if (Array.isArray(courts) && courts[0] === "HC_JAI") {
                  holidayListNameForRhcJaipur.push({
                    leave_date: h.leave_date,
                    holiday_name: h.holiday_name
                  });
                }
                if (!holidayMap[h.leave_date]) {
                  holidayMap[h.leave_date] = [];
                }
                holidayMap[h.leave_date].push({
                  leave_type: h.leave_type,
                  holiday_name: h.holiday_name,
                  court: h.court
                });
              });
              $("#miniCalendar").datepicker("refresh");
              renderHolidayList(holidayListNameForRhcJaipur);
            } else {
              console.warn("No holidays found");
            }
          },
          error: function(xhr, status, error) {
            console.error("Failed to fetch holiday data:", error);
          }
        });
      }

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
      $(function() {
        const currentDate = new Date();
        fetchHolidays(currentDate.getFullYear(), currentDate.getMonth() + 1);
        $("#miniCalendar").datepicker({
          showOtherMonths: true,
          selectOtherMonths: true,
          onSelect: function(dateText) {
            const selected = new Date(dateText);
            calendar.gotoDate(selected);
          },
          onChangeMonthYear: function(year, month) {
            const newDate = new Date(year, month - 1, 1);
            calendar.gotoDate(newDate);
            fetchHolidays(year, month);
          },
          beforeShowDay: function(date) {
            let classes = "";
            let tooltip = "";
            let dateStr = formatDateLocalForMiniCalendar(date);
            let isHoliday = false;
            if (holidayMap[dateStr]) {
              let holidayNames = holidayMap[dateStr].map(holiday => holiday.holiday_name || "Holiday").join(", ");
              let addedRHClass = false;
              holidayMap[dateStr].forEach(holiday => {
                if (addedRHClass) return;
                const leaveType = holiday.leave_type;
                let courts = holiday?.court?.split(",");
                if (courts && courts.length > 0 && courts[0] === "HC_JAI") {
                  if (leaveType === "RH") {
                    classes += " date-number-rh";
                    addedRHClass = true;
                  } else if (leaveType === "GH") {
                    classes += " date-number-gh";
                    addedRHClass = true;
                  }
                }
              });
              if (addedRHClass) {
                isHoliday = true;
                tooltip = holidayNames;
              }
              if (!isHoliday) {
                const day = date.getDay();
                if (day === 0) {
                  classes += " date-number-sunday";
                  tooltip = tooltip ? tooltip + ", Sunday" : "Sunday";
                }
                if (day === 6) {
                  const dayOfMonth = date.getDate();
                  const saturdayNumber = Math.floor((dayOfMonth - 1) / 7) + 1;
                  if (saturdayNumber >= 1 && saturdayNumber <= 4) {
                    classes += ` date-number-${saturdayNumber}th-saturday`;
                    const saturdayLabel = ["First", "Second", "Third", "Fourth"][saturdayNumber - 1] + " Saturday";
                    tooltip = tooltip ? tooltip + ", " + saturdayLabel : saturdayLabel;
                  }
                }
              }

            }


            return [true, classes.trim(), tooltip];
          }

        });
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
                }, {
                  once: true
                });
              }
            }
          }
        });
      }
      setInterval(checkReminders, 10000);

      function formatDateLocalForMiniCalendar(date) {
        let y = date.getFullYear();
        let m = (date.getMonth() + 1).toString().padStart(2, "0");
        let d = date.getDate().toString().padStart(2, "0");
        return `${y}-${m}-${d}`;
      }

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


      function showSelectedTags(selectedUsers) {
        console.log(selectedUsers);
        const container = $('#selected-user-tag-container');
        container.find('.tag').remove();

        selectedUsers.forEach(user => {
          $('<span class="badge bg-dark me-1 mb-1 tag">')
            .text(user)
            .appendTo(container); // append each tag to the container
        });
      }

      /* ------------------ Main Calendar ------------------ */
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'listYear',
        selectable: true,
        eventSources: [],
        height: 'auto',
        eventDisplay: 'block',
        datesSet: function(info) {
          calendar.removeAllEvents();
          calendar.refetchEvents();
        },
        eventDidMount: function(info) {
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
          left: 'today',
          center: 'prev,title,next',
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
        events: function(fetchInfo, successCallback, failureCallback) {
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
        },
        dayCellDidMount: function(info) {
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
        dateClick: function(info) {
          const container = $('#tag-container');
          container.find('.tag').remove();
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
        eventClick: function(info) {

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
            parseInt(event.extendedProps.reminder_before) == 0 ?
            0 :
            event.extendedProps.reminder_before || 0;
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
            event.extendedProps.repeat_frequency && event.extendedProps.repeat_frequency !== "" ?
            event.extendedProps.repeat_frequency :
            "";

          if (event.extendedProps.invitees && event.extendedProps.invitees.length > 0) {
            document.getElementById('invited_users').value = event.extendedProps.invitees.join(",");
            showSelectedTags(event.extendedProps.invitees);
          } else {
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
        const today = [],
          upcoming = [],
          past = [];
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
                }, {
                  once: true
                });
              }
            }
          });

          // --- helper funcs ---
          function wait(ms) {
            return new Promise(r => setTimeout(r, ms));
          }

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
            } catch (err) {}
            await wait(120);
            const calEl = calendar.el || document.querySelector('#calendar') || document.body;
            const title = (event.title || '').trim();
            const start = new Date(event.start);
            const timeCandidates = [
              start.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
              }),
              start.toLocaleTimeString(),
              start.toLocaleString()
            ];
            let foundEl = null;
            try {
              if (event.id != null) {
                const sel = calEl.querySelector(`[data-event-id="${event.id}"]`);
                if (sel) foundEl = sel;
              }
            } catch (err) {
              /* ignore */
            }
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
                  if (timeCandidates.some(t => t && txt.includes(t))) {
                    foundEl = c;
                    break;
                  }
                  if (!foundEl) foundEl = c;
                }
              }
            }
            if (!foundEl) {
              try {
                calendar.scrollToTime(event.start);
              } catch (err) {}
              return;
            }
            document.querySelectorAll('.highlight-event').forEach(n => n.classList.remove('highlight-event'));

            const scrollParent = getScrollableParent(foundEl) || document.scrollingElement || document.body;
            if (scrollParent === document.body || scrollParent === document.documentElement) {
              foundEl.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
              });
            } else {
              const parentRect = scrollParent.getBoundingClientRect();
              const elRect = foundEl.getBoundingClientRect();
              const offset = (elRect.top + scrollParent.scrollTop) - (parentRect.height / 2) + (elRect.height / 2);
              scrollParent.scrollTo({
                top: Math.max(0, offset),
                behavior: 'smooth'
              });
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
          renderSection("Past", past, true)
        ] // Past collapsible
        .forEach(section => {
          if (section) searchResults.appendChild(section);
        });
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
        const container = $('#tag-container');
        container.find('.tag').remove();
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = "";
        document.getElementById('priority').value = "low";
        if (deleteBtn) deleteBtn.style.display = "none";
        new bootstrap.Offcanvas(document.getElementById('eventOffcanvas')).show();
      });

      /* ------------------ Form & Buttons ------------------ */
      document.getElementById('newEventBtnMb').addEventListener('click', () => {
        const container = $('#tag-container');
        container.find('.tag').remove();
        document.getElementById('eventForm').reset();
        document.getElementById('eventId').value = "";
        document.getElementById('priority').value = "low";
        if (deleteBtn) deleteBtn.style.display = "none";
        new bootstrap.Offcanvas(document.getElementById('eventOffcanvas')).show();
      });

      document.getElementById('eventForm').addEventListener('submit', function(e) {
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
          repeat_frequency: isRepeating ?
            (repeatVal && repeatVal !== "--NONE--" ? repeatVal : null) : null,
          reminder: parseInt(document.getElementById("reminder").value)
        };

             const start = new Date(document.getElementById('start').value);
              const end = new Date(document.getElementById('end').value);

              if (start >= end) {
                  alert();
                  showToast("Start time must be earlier than end time!", "error");
                  return; 
              }


        axios.post('save_event.php', formData)
          .then((success) => {
            calendar.refetchEvents();
            showToast(success.data.message, 'success');
            bootstrap.Offcanvas.getInstance(document.getElementById('eventOffcanvas')).hide();
          })
          .catch(() => showToast('Could not save event', 'error'));
      });

      /* ------------------ Filters ------------------ */
      document.getElementById('searchBox').addEventListener('keyup', function() {
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
        deleteBtn.addEventListener('click', function() {
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
        btn.addEventListener("click", async function() {
          const inviteId = this.dataset.id;

          if (!confirm("Are you sure you want to cancel this invitation?")) return;
          try {
            const res = await fetch("cancel_invitation.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json"
              },
              body: JSON.stringify({
                invite_id: inviteId
              })
            });

            const data = await res.json();
            if (data.success) {
              showToast('Invitation cancelled successfully!', 'success');
              this.closest(".notif-card").remove();
            } else {
              showToast(data.message, 'error');
            }
          } catch (err) {
            console.log(err)
            showToast("Request failed. Try again later.", 'error');
          }
        });
      });


      // Handle Accept / Reject actions
      document.querySelectorAll(".invite-action-btn").forEach(btn => {
        btn.addEventListener("click", async function() {
          const inviteId = this.dataset.id;
          const action = this.dataset.action;
          console.log(action)
          try {
            const res = await fetch("invite_action.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json"
              },
              body: JSON.stringify({
                invite_id: inviteId,
                action
              })
            });

            const data = await res.json();

            if (data.success) {
              // Update UI with new status
              const container = this.closest(".notif-actions");
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

      document.getElementById("leaveBtn").addEventListener("click", function(e) {
        e.preventDefault();
        if (!currentEvent) {
          showToast("No event selected.", "error");
          return;
        }
        if (confirm("Are you sure you want to leave this event?")) {
          fetch("leave_event.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json"
              },
              body: JSON.stringify({
                event_id: currentEvent.id
              })
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


      function showToast(message, type, duration = 5000) {
        const container = document.getElementById("notification-container");
        const icons = {
          success: "✅",
          error: "❌",
          info: "ℹ️"
        };
        const notif = document.createElement("div");
        notif.classList.add("notification", type);
        notif.innerHTML = `
        <span  alt="${type} icon">${icons[type]}</span>
        <span>${message}</span>
        <span class="close-btn">&times;</span>
        <div class="progress" style="animation-duration:${duration}ms"></div>
      `;
        notif.querySelector(".close-btn").onclick = () => notif.remove();
        container.appendChild(notif);
        setTimeout(() => notif.remove(), duration);
      }

    });
  </script>
</body>

</html>