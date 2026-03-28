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

// Chuyển mảng sang JSON để JS render
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
  <title>TPoint Page</title>
  <link rel="stylesheet" href="./css/tpoint-page.css">
  <link rel="stylesheet" href="./css/score-page.css" />

</head>

<body>
  <header>
    <h2>Sổ Tay Sinh Viên Cá Nhân</h2>
    <p>Personal Student Tracker</p>
    <svg class="header-exit-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
      stroke-width="2" stroke-linecap="round" stroke-linejoin="round" onclick="window.location.href='../hub-page.php'"
      style="cursor:pointer; position: absolute; left: 30%;">
      <path d="M19 12H5M12 19l-7-7 7-7" />
    </svg>
  </header>

  <?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
    <div
      style="background: #dcfce7; color: #166534; padding: 15px; text-align: center; margin: 10px auto; width: 40%; border-radius: 12px; border: 1px solid #bbf7d0;">
      🎉 Dữ liệu đã được lưu thành công!
    </div>
  <?php endif; ?>

  <!-- GỬI ĐẾN SERVICE MỚI THAY VÌ PROCESS CŨ -->
  <form id="tpointForm" action="./services/TrainingPointService.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="semester_name" value="<?php echo htmlspecialchars($selected_semester); ?>">

    <div class="flex-container">
      <div class="tpoint-card">
        <h3 class="center-text">Sinh viên</h3>
        <h2 id="fullname"><?php echo htmlspecialchars($user_data['full_name']); ?></h2>
        <h4 id="studentid"><?php echo htmlspecialchars($user_data['student_id']); ?></h4>
        <h3 class="scenter-text" id="totalScore"><?php echo $current_score; ?> / <?php echo $max_score; ?></h3>
        <div class="progress-bar">
          <div class="progress-fill" id="progressFill"></div>
        </div>
      </div>

      <div class="semester-filter">
        <label for="semester-select"><strong>Học kỳ: </strong></label>
        <select id="semester-select" onchange="changeSemester(this.value)">
          <?php foreach ($available_semesters as $sem): ?>
            <option value="<?php echo $sem; ?>" <?php echo ($selected_semester == $sem) ? 'selected' : ''; ?>>
              <?php echo $sem; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="accordion-card">
        <div class="accordion-header" onclick="toggleAccordion(this)">
          <div class="header-left"><span class="icon">📘</span>
            <h3>I. Đánh giá về ý thức tham gia học tập (Điều 4)</h3>
          </div><span class="arrow">⌄</span>
        </div>
        <div class="accordion-content">
          <div class="criterion-block" data-criterion-id="1">
            <div class="criterion-row"><span>I.a Ý thức và thái độ trong học tập.</span><span class="badge">6
                điểm</span></div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 1)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="2">
            <div class="criterion-row"><span>I.b Ý thức CLB, nghiên cứu khoa học...</span><span class="badge">10
                điểm</span></div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 2)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="3">
            <div class="criterion-row"><span>I.c Tham gia kỳ thi, cuộc thi.</span><span class="badge">6 điểm</span>
            </div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 3)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="4">
            <div class="criterion-row"><span>I.d Tinh thần vượt khó...</span><span class="badge">2 điểm</span></div>
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

      <div class="accordion-card">
        <div class="accordion-header" onclick="toggleAccordion(this)">
          <div class="header-left"><span class="icon">📘</span>
            <h3>Phần II: Ý thức chấp hành nội quy (Điều 5)</h3>
          </div><span class="arrow">⌄</span>
        </div>
        <div class="accordion-content">
          <div class="criterion-block" data-criterion-id="6">
            <div class="criterion-row"><span>II.a Chấp hành văn bản chỉ đạo...</span><span class="badge">15 điểm</span>
            </div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 6)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="7">
            <div class="criterion-row"><span>II.b Chấp hành nội quy, quy chế...</span><span class="badge">10 điểm</span>
            </div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 7)">+ Thêm sự kiện</div>
          </div>
        </div>
      </div>

      <div class="accordion-card">
        <div class="accordion-header" onclick="toggleAccordion(this)">
          <div class="header-left"><span class="icon">📘</span>
            <h3>III: Ý thức tham gia CT-XH, thể thao (Điều 6)</h3>
          </div><span class="arrow">⌄</span>
        </div>
        <div class="accordion-content">
          <div class="criterion-block" data-criterion-id="8">
            <div class="criterion-row"><span>III.a Tham gia rèn luyện chính trị...</span><span class="badge">15
                điểm</span></div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 8)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="9">
            <div class="criterion-row"><span>III.b Hoạt động công ích, tình nguyện...</span><span class="badge">5
                điểm</span></div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 9)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="10">
            <div class="criterion-row"><span>III.c Phòng chống tội phạm, tệ nạn...</span><span class="badge">10
                điểm</span></div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 10)">+ Thêm sự kiện</div>
          </div>
        </div>
      </div>

      <div class="accordion-card">
        <div class="accordion-header" onclick="toggleAccordion(this)">
          <div class="header-left"><span class="icon">📘</span>
            <h3>IV. Đánh giá về ý thức công dân (Điều 7)</h3>
          </div><span class="arrow">⌄</span>
        </div>
        <div class="accordion-content">
          <div class="criterion-block" data-criterion-id="11">
            <div class="criterion-row"><span>IV.a Chấp hành chủ trương của Đảng...</span><span class="badge">15
                điểm</span></div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 11)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="12">
            <div class="criterion-row"><span>IV.b Thành tích trong hoạt động xã hội...</span><span class="badge">10
                điểm</span></div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 12)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="13">
            <div class="criterion-row"><span>IV.c Giúp đỡ người khó khăn...</span><span class="badge">5 điểm</span>
            </div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 13)">+ Thêm sự kiện</div>
          </div>
        </div>
      </div>

      <div class="accordion-card">
        <div class="accordion-header" onclick="toggleAccordion(this)">
          <div class="header-left"><span class="icon">📘</span>
            <h3>V. Cán bộ lớp, đoàn thể (Điều 8)</h3>
          </div><span class="arrow">⌄</span>
        </div>
        <div class="accordion-content">
          <div class="criterion-block" data-criterion-id="14">
            <div class="criterion-row"><span>V.a Hiệu quả công việc cán bộ...</span><span class="badge">10 điểm</span>
            </div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 14)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="15">
            <div class="criterion-row"><span>V.b Kỹ năng tổ chức...</span><span class="badge">9 điểm</span></div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 15)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="16">
            <div class="criterion-row"><span>V.c Hỗ trợ tham gia tích cực...</span><span class="badge">8 điểm</span>
            </div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 16)">+ Thêm sự kiện</div>
          </div>
          <div class="criterion-block" data-criterion-id="17">
            <div class="criterion-row"><span>V.d Thành tích đặc biệt...</span><span class="badge">8 điểm</span></div>
            <div class="event-container"></div>
            <div class="add-event-btn" onclick="addEvent(this, 17)">+ Thêm sự kiện</div>
          </div>
        </div>
      </div>

      <button type="submit" name="save_tpoint" class="submit-btn"><span class="btn-icon">💾</span><span
          class="btn-text">Lưu toàn bộ dữ liệu</span></button>
    </div>
  </form>


  <script>
    // Chỉ để lại các biến môi trường cần thiết để JS bên ngoài có thể đọc được
    window.TPOINT_DATA = {
      currentScore: <?php echo (float) $current_score; ?>,
      maxScore: <?php echo (float) $max_score; ?>,
      savedEvidences: <?php echo $evidences_json; ?>
    };
  </script>
  <script src="./js/tpoint-page.js"></script>
</body>

</html>