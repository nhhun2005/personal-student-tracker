<?php
header('Content-Type: text/html; charset=UTF-8');
require_once './includes/process-hub.php';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Personal Student Tracker - Hub</title>
  <link rel="stylesheet" href="./css/global-style.css" />
  <link rel="stylesheet" href="./css/hub-page.css" />

</head>

<body>
  <header>
    <h2>Sảnh tính năng</h2>
    <p>Feature hub</p>
  </header>

  <div class="flex-container">
    <div class="feature-card user-card-static">
      <svg class="feature-card-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path
          d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z">
        </path>
        <path d="M22 10v6"></path>
        <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
      </svg>
      <div class="feature-card-content">
        <h3 id="fullname"><?php echo htmlspecialchars($user['full_name']); ?></h3>
        <h4 id="studentid">MSSV: <?php echo htmlspecialchars($user['student_id']); ?></h4>
      </div>
      <a href="./includes/logout.php" class="feature-card-button logout-btn" title="Đăng xuất">
        <svg class="feature-card-button-icon" width="24" height="24" viewBox="0 0 24 24" fill="none"
          xmlns="http://www.w3.org/2000/svg" stroke="currentColor">
          <path
            d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M16 17L21 12L16 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M21 12H9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </a>
    </div>

    <h3 id="guide-text">Chọn chức năng để tiếp tục</h3>

    <a href="score-page.php" class="feature-card">
      <svg class="feature-card-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path
          d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z">
        </path>
        <path d="M22 10v6"></path>
        <path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"></path>
      </svg>
      <div class="feature-card-content">
        <h3>Điểm trung bình học kỳ</h3>
        <h4>Theo dõi điểm trung bình học kỳ</h4>
      </div>
      <div class="feature-card-button">
        <svg class="feature-card-button-icon" width="24" height="24" viewBox="0 0 24 24" fill="none"
          xmlns="http://www.w3.org/2000/svg" stroke="currentColor">
          <path d="M9 18L15 12L9 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
    </a>

    <a href="tpoint-page.php" class="feature-card">
      <svg class="feature-card-icon" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
        stroke="currentColor" fill="none" stroke-width="2">
        <path
          d="M12 15C14.2091 15 16 13.2091 16 11C16 8.79086 14.2091 7 12 7C9.79086 7 8 8.79086 8 11C8 13.2091 9.79086 15 12 15Z"
          stroke-linecap="round" stroke-linejoin="round" />
        <path d="M8.21 13.89L7 21L12 18L17 21L15.79 13.88" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
      <div class="feature-card-content">
        <h3>Điểm rèn luyện</h3>
        <h4>Theo dõi điểm rèn luyện của học kỳ</h4>
      </div>
      <div class="feature-card-button">
        <svg class="feature-card-button-icon" width="24" height="24" viewBox="0 0 24 24" fill="none"
          xmlns="http://www.w3.org/2000/svg" stroke="currentColor">
          <path d="M9 18L15 12L9 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
    </a>


  </div>

</body>

</html>
