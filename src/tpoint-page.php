<?php
session_start();
require_once __DIR__ . '/services/TrainingPointService.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

$tpService = new TrainingPointService();
$available_semesters = ["HK2 2025-2026", "HK3 2025-2026", "HK1 2026-2027", "HK2 2026-2027", "HK3 2026-2027"];
$selected_semester = $_GET['semester'] ?? $available_semesters[0];

$data = $tpService->getPageData($_SESSION['user_id'], $selected_semester);
$saved_evidences = $data['evidence_data'];
$section_scores = $data['scores']; // Điểm của 5 mục lớn

$evidences_json = json_encode($saved_evidences);

$user_data = [
  'full_name' => $_SESSION['full_name'] ?? 'Sinh viên',
  'student_id' => $_SESSION['student_id'] ?? 'N/A'
];

$current_score = $data['final_score'];
$max_score = 100;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>TPoint Page - Student Tracker</title>
  <link rel="stylesheet" href="./css/tpoint-page.css?v=<?php echo time(); ?>">
</head>

<body>
  <div class="flex-container">
    <div class="main-wrapper">
      <header>
        <svg class="header-exit-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
          onclick="window.location.href='../hub-page.php'">
          <path d="M19 12H5M12 19l-7-7 7-7" />
        </svg>
        <div class="app-icon-wrapper">
          <svg id="app-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
          </svg>
        </div>
        <h2>Sổ Tay Sinh Viên Cá Nhân</h2>
        <p>Hệ thống Đánh giá Điểm rèn luyện</p>
      </header>

      <?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
        <div class="alert-success">
          🎉 Dữ liệu đã được lưu thành công!
        </div>
      <?php endif; ?>

      <form id="tpointForm" action="./services/TrainingPointService.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="semester_name" value="<?php echo htmlspecialchars($selected_semester); ?>">

        <!-- Thẻ thông tin sinh viên -->
        <div class="tpoint-card">
          <div class="student-info-grid">
            <div>
              <p class="label">Sinh viên</p>
              <h3 id="fullname"><?php echo htmlspecialchars($user_data['full_name']); ?></h3>
            </div>
            <div>
              <p class="label">MSSV</p>
              <h3 id="studentid"><?php echo htmlspecialchars($user_data['student_id']); ?></h3>
            </div>
          </div>
          <div class="score-display">
            <p>Tổng điểm Rèn luyện</p>
            <h2 id="totalScore"><?php echo $current_score; ?> / <?php echo $max_score; ?></h2>
            <div class="progress-bar">
              <div class="progress-fill" id="progressFill"></div>
            </div>
            <p style="margin-top: 10px; font-weight: bold; color: #1d71bb;">Xếp loại:
              <?php echo $data['classification']; ?></p>
          </div>
        </div>

        <!-- Bộ lọc học kỳ -->
        <div class="semester-filter">
          <label for="semester-select">Chọn học kỳ:</label>
          <select id="semester-select" onchange="changeSemester(this.value)">
            <?php foreach ($available_semesters as $sem): ?>
              <option value="<?php echo $sem; ?>" <?php echo ($selected_semester == $sem) ? 'selected' : ''; ?>>
                <?php echo $sem; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- CÁC MỤC LỚN -->

        <!-- MỤC I -->
        <div class="accordion-card" data-section="I">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            <div class="header-left">
              <span class="icon">📘</span>
              <h3>I. Đánh giá về ý thức tham gia học tập</h3>
            </div>
            <div class="header-right">
              <span class="section-score-display"><?php echo $section_scores[1]; ?>/20</span>
              <span class="arrow">⌄</span>
            </div>
          </div>
          <div class="accordion-content">
            <div class="criterion-block" data-criterion-id="1">
              <div class="criterion-row"><span>I.a Ý thức và thái độ học tập</span><span class="badge">6 điểm</span>
              </div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 1)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="2">
              <div class="criterion-row"><span>I.b Tham gia CLB, NCKH</span><span class="badge">10 điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 2)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="3">
              <div class="criterion-row"><span>I.c Tham gia kỳ thi, cuộc thi</span><span class="badge">6 điểm</span>
              </div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 3)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="4">
              <div class="criterion-row"><span>I.d Tinh thần vượt khó</span><span class="badge">2 điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 4)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="5">
              <div class="criterion-row"><span>I.e Kết quả học tập</span><span class="badge">8 điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 5)">+ Thêm sự kiện</div>
            </div>
          </div>
        </div>

        <!-- MỤC II -->
        <div class="accordion-card" data-section="II">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            <div class="header-left">
              <span class="icon">📗</span>
              <h3>II. Ý thức chấp hành nội quy</h3>
            </div>
            <div class="header-right">
              <span class="section-score-display"><?php echo $section_scores[2]; ?>/25</span>
              <span class="arrow">⌄</span>
            </div>
          </div>
          <div class="accordion-content">
            <div class="criterion-block" data-criterion-id="6">
              <div class="criterion-row"><span>II.a Chấp hành văn bản chỉ đạo</span><span class="badge">15 điểm</span>
              </div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 6)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="7">
              <div class="criterion-row"><span>II.b Chấp hành nội quy, quy chế</span><span class="badge">10 điểm</span>
              </div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 7)">+ Thêm sự kiện</div>
            </div>
          </div>
        </div>

        <!-- MỤC III -->
        <div class="accordion-card" data-section="III">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            <div class="header-left">
              <span class="icon">📙</span>
              <h3>III. Ý thức tham gia CT-XH, thể thao</h3>
            </div>
            <div class="header-right">
              <span class="section-score-display"><?php echo $section_scores[3]; ?>/20</span>
              <span class="arrow">⌄</span>
            </div>
          </div>
          <div class="accordion-content">
            <div class="criterion-block" data-criterion-id="8">
              <div class="criterion-row"><span>III.a Rèn luyện chính trị, VH-TT</span><span class="badge">15 điểm</span>
              </div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 8)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="9">
              <div class="criterion-row"><span>III.b Hoạt động công ích, tình nguyện</span><span class="badge">5
                  điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 9)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="10">
              <div class="criterion-row"><span>III.c Phòng chống tội phạm, tệ nạn</span><span class="badge">10
                  điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 10)">+ Thêm sự kiện</div>
            </div>
          </div>
        </div>

        <!-- MỤC IV -->
        <div class="accordion-card" data-section="IV">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            <div class="header-left">
              <span class="icon">📕</span>
              <h3>IV. Đánh giá về ý thức công dân</h3>
            </div>
            <div class="header-right">
              <span class="section-score-display"><?php echo $section_scores[4]; ?>/25</span>
              <span class="arrow">⌄</span>
            </div>
          </div>
          <div class="accordion-content">
            <div class="criterion-block" data-criterion-id="11">
              <div class="criterion-row"><span>IV.a Chấp hành chủ trương của Đảng</span><span class="badge">15
                  điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 11)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="12">
              <div class="criterion-row"><span>IV.b Thành tích trong hoạt động xã hội</span><span class="badge">10
                  điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 12)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="13">
              <div class="criterion-row"><span>IV.c Giúp đỡ người khó khăn</span><span class="badge">5 điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 13)">+ Thêm sự kiện</div>
            </div>
          </div>
        </div>

        <!-- MỤC V -->
        <div class="accordion-card" data-section="V">
          <div class="accordion-header" onclick="toggleAccordion(this)">
            <div class="header-left">
              <span class="icon">🏅</span>
              <h3>V. Cán bộ lớp, đoàn thể</h3>
            </div>
            <div class="header-right">
              <span class="section-score-display"><?php echo $section_scores[5]; ?>/10</span>
              <span class="arrow">⌄</span>
            </div>
          </div>
          <div class="accordion-content">
            <div class="criterion-block" data-criterion-id="14">
              <div class="criterion-row"><span>V.a Hiệu quả công việc cán bộ</span><span class="badge">10 điểm</span>
              </div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 14)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="15">
              <div class="criterion-row"><span>V.b Kỹ năng tổ chức</span><span class="badge">9 điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 15)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="16">
              <div class="criterion-row"><span>V.c Hỗ trợ tham gia tích cực</span><span class="badge">8 điểm</span>
              </div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 16)">+ Thêm sự kiện</div>
            </div>
            <div class="criterion-block" data-criterion-id="17">
              <div class="criterion-row"><span>V.d Thành tích đặc biệt</span><span class="badge">8 điểm</span></div>
              <div class="event-container"></div>
              <div class="add-event-btn" onclick="addEvent(this, 17)">+ Thêm sự kiện</div>
            </div>
          </div>
        </div>

        <button type="submit" name="save_tpoint" class="submit-btn">Lưu toàn bộ dữ liệu</button>
      </form>
    </div>
  </div>

  <script>
    window.TPOINT_DATA = {
      currentScore: <?php echo (float) $current_score; ?>,
      maxScore: <?php echo (float) $max_score; ?>,
      savedEvidences: <?php echo $evidences_json; ?>
    };
  </script>
  <script src="./js/tpoint-page.js?v=<?php echo time(); ?>"></script>
</body>

</html>