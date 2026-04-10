<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
// Nạp ScoreService
require_once __DIR__ . '/services/ScoreService.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

$scoreService = new ScoreService();

// Lấy dữ liệu để hiển thị (Logic GET bình thường cho lần đầu load trang)
$data = $scoreService->getScorePageData($_SESSION['user_id'], $_GET);

$selected_semester = $data['semester'];
$is_all_mode = ($selected_semester === 'Tất cả');
$final_score = $data['gpa'];
$total_pages = $data['total_pages'];
$current_page = $data['current_page'];

// Map lại dữ liệu môn học, lấy thêm field id
$display_courses = array_map(function ($c) {
  return [
    'id' => $c['id'] ?? null,
    'name' => $c['course_name'] ?? '',
    'credits' => $c['credits'] ?? 0,
    'score' => $c['score'] ?? 0
  ];
}, $data['courses']);

$all_data = $display_courses;

// Lấy thông tin sinh viên từ session
$u_info = [
  'full_name' => $_SESSION['full_name'] ?? 'Sinh viên',
  'student_id' => $_SESSION['student_id'] ?? 'N/A'
];

$items_per_page = 5;
$offset = ($current_page - 1) * $items_per_page;

// Hàm build_url giữ lại để hỗ trợ nếu JS bị lỗi (fallback)
function build_url($p)
{
  global $selected_semester;
  $q = $_GET['q'] ?? '';
  $c = $_GET['c'] ?? '';
  $sort = $_GET['sort'] ?? 'c.id';
  $order = $_GET['order'] ?? 'DESC';
  return "score-page.php?semester=" . urlencode($selected_semester) . "&page=$p&q=" . urlencode($q) . "&c=$c&sort=$sort&order=$order";
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Bảng điểm - Student Tracker</title>
  <link rel="stylesheet" href="./css/global-style.css" />
  <link rel="stylesheet" href="./css/score-page.css" />
  <script>
    // Các biến global cho JS sử dụng
    const SAVED_COURSES = <?php echo json_encode($all_data); ?>;
    const IS_ALL_MODE = <?php echo json_encode($is_all_mode); ?>;
  </script>
</head>

<body>
  <header>
    <svg onclick="window.location.href='hub-page.php'" class="header-exit-icon" width="24" height="24"
      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
      stroke-linejoin="round">
      <path d="M19 12H5M12 19l-7-7 7-7" />
    </svg>
    <h2><?php echo $is_all_mode ? "GPA Tích lũy toàn khóa" : "Điểm trung bình học kỳ"; ?></h2>
    <p>Hệ thống theo dõi kết quả học tập</p>
  </header>

  <div class="flex-container">
    <div class="student-card">
      <div>
        <h4>Sinh viên</h4>
        <h3><?php echo htmlspecialchars($u_info['full_name']); ?></h3>
      </div>
      <div>
        <h4>MSSV</h4>
        <h3><?php echo htmlspecialchars($u_info['student_id']); ?></h3>
      </div>
    </div>

    <div class="total-score">
      <p><?php echo $is_all_mode ? "GPA Tích lũy" : "ĐTB Học kỳ"; ?></p>
      <h2><?php echo number_format($final_score, 2); ?></h2>
      <h3>Xếp loại: <?php
      if ($final_score >= 3.6)
        echo "Xuất sắc";
      elseif ($final_score >= 3.2)
        echo "Giỏi";
      elseif ($final_score >= 2.5)
        echo "Khá";
      elseif ($final_score >= 2.0)
        echo "Trung bình";
      else
        echo "Yếu/Kém";
      ?></h3>
    </div>

    <form method="GET" action="score-page.php" id="filterForm" class="filter-card">
      <input type="hidden" name="semester" value="<?php echo htmlspecialchars($selected_semester); ?>">
      <div class="input-box">
        <label>Tìm tên môn học</label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
          placeholder="Nhập từ khóa...">
      </div>
      <div class="filter-grid">
        <div class="input-box"><label>Số tín chỉ</label><input type="number" name="c" min="0"
            value="<?php echo htmlspecialchars($_GET['c'] ?? ''); ?>"></div>
        <div class="input-box"><label>Sắp xếp theo</label>
          <select name="sort">
            <option value="score" <?php echo ($_GET['sort'] ?? '') == 'score' ? 'selected' : ''; ?>>Điểm số</option>
            <option value="credits" <?php echo ($_GET['sort'] ?? '') == 'credits' ? 'selected' : ''; ?>>Số tín chỉ
            </option>
          </select>
        </div>
      </div>
      <div class="filter-grid">
        <div class="input-box"><label>Thứ tự</label>
          <select name="order">
            <option value="DESC" <?php echo strtoupper($_GET['order'] ?? '') == 'DESC' ? 'selected' : ''; ?>>Giảm dần
            </option>
            <option value="ASC" <?php echo strtoupper($_GET['order'] ?? '') == 'ASC' ? 'selected' : ''; ?>>Tăng dần
            </option>
          </select>
        </div>
        <div class="input-box" style="display:flex; align-items:flex-end;">
          <button type="submit" class="apply-btn">Lọc kết quả</button>
        </div>
      </div>
    </form>

    <form method="POST" action="./services/ScoreService.php" class="flex-container" style="width:100%; margin-top:0;">
      <div class="semester-card">
        <h3>Thông tin học kỳ</h3>
        <div class="input-box">
          <label>Chọn học kỳ</label>
          <select onchange="window.location.href='score-page.php?semester=' + this.value">
            <option value="Tất cả" <?php echo $is_all_mode ? 'selected' : ''; ?>>Tất cả (Xem tích lũy)</option>
            <?php foreach (["HK2 2025-2026", "HK3 2025-2026", "HK1 2026-2027", "HK2 2026-2027", "HK3 2026-2027"] as $opt): ?>
              <option value="<?php echo $opt; ?>" <?php echo ($opt == $selected_semester) ? 'selected' : ''; ?>>
                <?php echo $opt; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <input type="hidden" name="semester_name" value="<?php echo htmlspecialchars($selected_semester); ?>">
        <?php if (!$is_all_mode): ?>
          <div class="input-box"><label>Số môn học</label><input type="number" id="courseCount" min="0"
              value="<?php echo count($all_data); ?>"></div>
        <?php endif; ?>
      </div>

      <div id="courseContainer" class="flex-container" style="width:100%; margin-top:0;">
        <?php foreach ($display_courses as $index => $course): ?>
          <div class="course-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="color:#1d71bb; margin: 0;">Môn <?php echo $offset + $index + 1; ?></h4>
                <?php if (!$is_all_mode && !empty($course['id'])): ?>
                    <button type="submit" name="delete_course" value="<?php echo $course['id']; ?>" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa môn này khỏi dữ liệu?');">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="input-box"><label>Tên môn</label><input type="text" name="c_name[]"
                value="<?php echo htmlspecialchars($course['name']); ?>" <?php echo $is_all_mode ? 'readonly' : ''; ?>
                required></div>
            <div class="course-card-details">
              <div class="input-box"><label>Tín chỉ</label><input type="number" name="c_credit[]" min="0"
                  value="<?php echo $course['credits']; ?>" <?php echo $is_all_mode ? 'readonly' : ''; ?> required></div>
              <div class="input-box"><label>Điểm</label><input type="number" step="0.1" min="0" max="4.0" name="c_score[]"
                  value="<?php echo $course['score']; ?>" <?php echo $is_all_mode ? 'readonly' : ''; ?> required></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="<?php echo build_url($i); ?>" data-page="<?php echo $i; ?>"
            class="page-link <?php echo ($i == $current_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
      </div>

      <?php if (!$is_all_mode): ?>
        <button type="submit" name="save_score" class="save-button">Lưu & cập nhật điểm số</button>
      <?php endif; ?>
    </form>

    <div class="note">
      Lưu ý: Điểm được tính trên thang 4. Công thức: ĐTB = Tổng(Điểm x Tín chỉ) / Tổng Tín chỉ.
    </div>
  </div>
  <script src="./js/score-page.js"></script>
</body>

</html>