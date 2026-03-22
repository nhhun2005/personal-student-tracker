<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>TPoint Page</title>
  <link rel="stylesheet" href="./css/tpoint-page.css">
  <link rel="stylesheet" href="./css/score-page.css" />
</head>

<body>

  <header>
    <h2>Sổ Tay Sinh Viên Cá Nhân</h2>
    <p>Personal Student Tracker</p>
    <svg class="header-exit-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
      stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M19 12H5M12 19l-7-7 7-7" />
    </svg>
  </header>

  <div class="flex-container">

    <!-- Student Card -->
    <div class="tpoint-card">
      <h3 class="center-text">Sinh viên</h3>
      <h2 id="fullname"></h2>
      <h4 id="studentid"></h4>

      <h3 class="scenter-text" id="totalScore">1 / 5</h3>

      <!-- Progress Bar -->
      <div class="progress-bar">
        <div class="progress-fill" id="progressFill"></div>
      </div>
    </div>

    <!-- Accordion 1 -->

    <div class="accordion-card">

      <div class="accordion-header" onclick="toggleAccordion(this)">
        <div class="header-left">
          <span class="icon">🌿</span>
          <div>
            <h3>1. Tiêu chuẩn Đạo đức tốt</h3>
            <p>Đạt 03 tiêu chuẩn sau</p>
          </div>
        </div>
        <span class="arrow">⌄</span>
      </div>

      <div class="accordion-content">

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(1.1) Điểm rèn luyện trung bình năm ≥ 80 điểm.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(1.2) Không vi phạm pháp luật và nội quy.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(1.3) Đạt thêm 01 tiêu chí khác theo quy định.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

      </div>
    </div>
    <!-- Accordion 2 -->
    <div class="accordion-card">

      <div class="accordion-header" onclick="toggleAccordion(this)">
        <div class="header-left">
          <span class="icon">📚</span>
          <div>
            <h3>2. Tiêu chuẩn Học tập tốt</h3>
            <p>Đạt 01 trong 02 tiêu chuẩn</p>
          </div>
        </div>
        <span class="arrow">⌄</span>
      </div>

      <div class="accordion-content">

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(2.1) GPA năm ≥ 2.8.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(2.2) GPA ≥ 2.6 và HK2 > HK1, tín chỉ HK2 ≥ HK1.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

      </div>
    </div>

    <!-- Accordion 3 -->
    <div class="accordion-card">

      <div class="accordion-header" onclick="toggleAccordion(this)">
        <div class="header-left">
          <span class="icon">🏃</span>
          <div>
            <h3>3. Tiêu chuẩn Thể lực tốt</h3>
            <p>Đạt 01 trong 02 tiêu chuẩn</p>
          </div>
        </div>
        <span class="arrow">⌄</span>
      </div>

      <div class="accordion-content">

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(3.1) Học ≥ 1 học phần GDTC và đạt B trở lên.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(3.2) Tham gia ≥ 1 hoạt động rèn luyện thể lực.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

      </div>
    </div>

    <!-- Accordion 4 -->
    <div class="accordion-card">

      <div class="accordion-header" onclick="toggleAccordion(this)">
        <div class="header-left">
          <span class="icon">🤝</span>
          <div>
            <h3>4. Tiêu chuẩn Tình nguyện tốt</h3>
            <p>Đạt 01 trong 03 tiêu chuẩn</p>
          </div>
        </div>
        <span class="arrow">⌄</span>
      </div>

      <div class="accordion-content">

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(4.1) Được khen thưởng hoạt động tình nguyện.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(4.2) Tham gia ≥ 3 hoạt động tình nguyện.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(4.3) Hiến máu ≥ 2 lần.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

      </div>
    </div>

    <!-- Accordion 5 -->
    <div class="accordion-card">

      <div class="accordion-header" onclick="toggleAccordion(this)">
        <div class="header-left">
          <span class="icon">🌏</span>
          <div>
            <h3>5. Tiêu chuẩn Hội nhập tốt</h3>
            <p>Đạt 02 trong 03 tiêu chuẩn</p>
          </div>
        </div>
        <span class="arrow">⌄</span>
      </div>

      <div class="accordion-content">

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(5.1) Ngoại ngữ đạt chuẩn theo quy định.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(5.2) Tin học đạt chuẩn theo quy định.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

        <div class="criterion-block">
          <div class="criterion-row">
            <span>(5.3) Tham gia hoạt động nâng cao kỹ năng hội nhập.</span>
          </div>
          <div class="event-container"></div>
          <div class="add-event-btn" onclick="addEvent(this)">+ Thêm minh chứng</div>
        </div>

      </div>
    </div>


    <button class="submit-btn">
      <span class="btn-text">Nộp minh chứng</span>
    </button>

    <script src="./js/tpoint-page.js"></script>
</body>

</html>