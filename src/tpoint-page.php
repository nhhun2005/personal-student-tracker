<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ./index.php");
  exit();
}

require_once './includes/connect-db.php';
$user_id = $_SESSION['user_id'];
$available_semesters = ["HK2 2025-2026", "HK3 2025-2026", "HK1 2026-2027", "HK2 2026-2027", "HK3 2026-2027"];
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : $available_semesters[0];

// 1. Lấy ID của học kỳ hiện tại (BẮT BUỘC có cái này thì phần sau mới chạy đúng)
$stmt_sem = $conn->prepare("SELECT id FROM semesters WHERE semester_name = ? AND user_id = ? LIMIT 1");
$stmt_sem->bind_param("si", $selected_semester, $user_id);
$stmt_sem->execute();
$res_sem = $stmt_sem->get_result()->fetch_assoc();
$sem_id = $res_sem ? $res_sem['id'] : 0;

// 2. Lấy thông tin User và Tổng điểm
$sql = "SELECT u.full_name, u.student_id, 
               (SELECT SUM(e.score_value) 
                FROM evidences e 
                WHERE e.user_id = u.id AND e.semester_id = ?) as total_rln
        FROM users u 
        WHERE u.id = ? 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $sem_id, $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if (!$user_data) {
  session_destroy();
  header("Location: ./index.php");
  exit();
}

$current_score = $user_data['total_rln'] ? $user_data['total_rln'] : 0;
$max_score = 100;

// 3. LẤY TẤT CẢ MINH CHỨNG ĐÃ LƯU (Đầy đủ dữ liệu để render)
$sql_evidences = "SELECT id, criterion_id, score_value, event_date, 
                  (CASE WHEN content IS NOT NULL AND content != '' THEN 1 ELSE 0 END) as has_content 
                  FROM evidences 
                  WHERE user_id = ? AND semester_id = ?";
$stmt_ev = $conn->prepare($sql_evidences);
$stmt_ev->bind_param("ii", $user_id, $sem_id);
$stmt_ev->execute();
$evidences_result = $stmt_ev->get_result();

$saved_evidences = [];
while ($row = $evidences_result->fetch_assoc()) {
  $saved_evidences[] = $row;
}
$evidences_json = json_encode($saved_evidences);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>TPoint Page</title>
  <link rel="stylesheet" href="./css/tpoint-page.css">
  <link rel="stylesheet" href="./css/score-page.css" />
  <style>
    .semester-filter {
      width: 40%;
      background: white;
      padding: 1rem 2rem;
      border-radius: 16px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
      box-sizing: border-box;
    }

    .semester-filter select {
      padding: 8px 12px;
      border-radius: 10px;
      border: 1px solid #e5e7eb;
      background: #f9fafb;
    }

    .file-name-display {
      display: block;
      margin-top: 5px;
      font-size: 11px;
      color: #6b7280;
      word-break: break-all;
    }

    .event-card.saved {
      border-left: 4px solid #10b981;
      background: #f0fdf4;
    }

    .saved-label {
      color: #10b981;
      font-size: 11px;
      font-weight: bold;
      margin-bottom: 5px;
      display: block;
    }
  </style>
</head>

<body>
  <header>
    <h2>Sổ Tay Sinh Viên Cá Nhân</h2>
    <p>Personal Student Tracker</p>
    <svg class="header-exit-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
      stroke-width="2" stroke-linecap="round" stroke-linejoin="round" onclick="window.location.href='../hub-page.php'"
      style="cursor:pointer">
      <path d="M19 12H5M12 19l-7-7 7-7" />
    </svg>
  </header>

  <?php if (isset($_GET['msg']) && $_GET['msg'] == 'saved'): ?>
    <div
      style="background: #dcfce7; color: #166534; padding: 15px; text-align: center; margin: 10px auto; width: 40%; border-radius: 12px; border: 1px solid #bbf7d0;">
      🎉 Dữ liệu đã được lưu thành công!
    </div>
  <?php endif; ?>

  <form id="tpointForm" action="./includes/process-tpoint.php" method="POST" enctype="multipart/form-data">
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

      <button type="submit" class="submit-btn"><span class="btn-icon">💾</span><span class="btn-text">Lưu toàn bộ dữ
          liệu</span></button>
    </div>
  </form>

  <script src="./js/tpoint-page.js"></script>
  <script>
    function changeSemester(s) { window.location.href = "?semester=" + encodeURIComponent(s); }

    document.addEventListener("DOMContentLoaded", function () {
      // 1. Cập nhật progress bar
      const scoreFromDB = <?php echo $current_score; ?>;
      if (typeof updateProgress === "function") updateProgress(scoreFromDB, 100);

      // 2. Tự động hiển thị các minh chứng đã lưu
      const savedEvidences = <?php echo $evidences_json; ?>;
      savedEvidences.forEach(ev => {
        const block = document.querySelector(`.criterion-block[data-criterion-id="${ev.criterion_id}"]`);
        if (block) {
          const container = block.querySelector('.event-container');
          renderSavedEvent(container, ev);
        }
      });
      checkAllAddButtons();
    });
    function checkAllAddButtons() {
      const blocks = document.querySelectorAll('.criterion-block');
      blocks.forEach(block => {
        const maxAllowed = parseFloat(block.querySelector('.badge').textContent);
        let currentTotal = 0;

        // Tính tổng điểm từ các ô input (cả ô disabled và ô mới tạo)
        block.querySelectorAll('.event-score').forEach(input => {
          currentTotal += parseFloat(input.value) || 0;
        });

        const addButton = block.querySelector('.add-event-btn');
        if (currentTotal >= maxAllowed) {
          addButton.style.pointerEvents = "none";
          addButton.style.opacity = "0.5";
          addButton.style.background = "#e5e7eb";
          addButton.style.borderColor = "#d1d5db";
          addButton.style.color = "#9ca3af";
          addButton.innerText = "❌ Đã đạt điểm tối đa";
        } else {
          // Khôi phục nếu xóa bớt hoặc chưa đủ
          addButton.style.pointerEvents = "auto";
          addButton.style.opacity = "1";
          addButton.style.background = ""; // reset về mặc định css
          addButton.style.borderColor = "";
          addButton.style.color = "";
          addButton.innerText = "+ Thêm sự kiện";
        }
      });
    }
    // Hàm render card cho dữ liệu đã có trong DB
    function renderSavedEvent(container, data) {
      const card = document.createElement("div");
      card.classList.add("event-card", "saved");

      // Kiểm tra xem bản ghi này có file minh chứng không
      const hasEvidence = data.has_content == 1;

      card.innerHTML = `
        <span class="saved-label">✓ Đã lưu</span>
        <div class="event-actions">
          <input type="date" value="${data.event_date}" disabled>
          <input type="number" value="${data.score_value}" disabled class="event-score">
          
          ${hasEvidence ?
          `<a href="./includes/view-evidence.php?id=${data.id}" target="_blank" class="upload-label" 
                style="background:#e0f2fe; border:1px solid #7dd3fc; text-decoration:none; color:#0369a1; display:flex; align-items:center; justify-content:center;">
                🔍 Xem minh chứng
             </a>` :
          `<span class="upload-label" 
                style="background:#f3f4f6; border:1px solid #d1d5db; color:#9ca3af; cursor:not-allowed; display:flex; align-items:center; justify-content:center; font-size: 0.8rem;">
                🚫 Không minh chứng
             </span>`
        }
        </div>
      `;
      container.appendChild(card);
    }

    // Validate form trước khi submit
    document.getElementById('tpointForm').addEventListener('submit', function (e) {
      const blocks = document.querySelectorAll('.criterion-block');
      let hasError = false;

      for (let block of blocks) {
        const maxText = block.querySelector('.badge').textContent;
        const maxAllowed = parseFloat(maxText);
        let total = 0;

        block.querySelectorAll('.event-score').forEach(input => {
          total += parseFloat(input.value) || 0;
        });

        if (total > maxAllowed) {
          alert(`Mục "${block.querySelector('.criterion-row span').textContent}" bị quá điểm tối đa (${maxAllowed}).`);
          hasError = true;
          break;
        }
      }

      if (hasError) {
        e.preventDefault();
      } else {
        const btn = document.querySelector('.submit-btn');
        btn.innerHTML = "⌛ Đang lưu...";
        btn.style.opacity = "0.7";
        btn.style.pointerEvents = "none";
      }
    });
  </script>
</body>

</html>