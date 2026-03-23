<?php
session_start();
require_once __DIR__ . '/includes/env-loader.php';
require_once __DIR__ . '/includes/connect-db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: index.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$selected_semester = $_GET['semester'] ?? 'HK2 2025-2026';
$is_all_mode = ($selected_semester === 'Tất cả');

// Search & Sort parameters
$search_name = $_GET['q'] ?? '';
$search_credit = (isset($_GET['c']) && $_GET['c'] !== '') ? intval($_GET['c']) : null;
$sort_by = in_array($_GET['sort'] ?? '', ['score', 'credits']) ? $_GET['sort'] : 'c.id';
$sort_order = strtoupper($_GET['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

// Pagination
$items_per_page = 5;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Where Clauses
$where_clauses = ["s.user_id = ?"];
$params = [$user_id];
$types = "i";
if (!$is_all_mode) {
  $where_clauses[] = "s.semester_name = ?";
  $params[] = $selected_semester;
  $types .= "s";
}
if (!empty($search_name)) {
  $where_clauses[] = "c.course_name LIKE ?";
  $params[] = "%$search_name%";
  $types .= "s";
}
if ($search_credit !== null) {
  $where_clauses[] = "c.credits = ?";
  $params[] = $search_credit;
  $types .= "i";
}
$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// Tính GPA & Phân trang (Prepared Statements)
$stmt_gpa = $conn->prepare("SELECT c.credits, c.score FROM courses c JOIN semesters s ON c.semester_id = s.id $where_sql");
$stmt_gpa->bind_param($types, ...$params);
$stmt_gpa->execute();
$all_data = $stmt_gpa->get_result()->fetch_all(MYSQLI_ASSOC);
$pts = 0;
$cre = 0;
foreach ($all_data as $r) {
  $pts += ($r['credits'] * $r['score']);
  $cre += $r['credits'];
}
$final_score = $cre > 0 ? round($pts / $cre, 2) : 0;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses c JOIN semesters s ON c.semester_id = s.id $where_sql");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_items / $items_per_page));

$display_sql = "SELECT c.course_name as name, c.credits, c.score FROM courses c JOIN semesters s ON c.semester_id = s.id $where_sql ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$stmt_disp = $conn->prepare($display_sql);
$stmt_disp->bind_param($types . "ii", ...array_merge($params, [$items_per_page, $offset]));
$stmt_disp->execute();
$display_courses = $stmt_disp->get_result()->fetch_all(MYSQLI_ASSOC);

// Post logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_score']) && !$is_all_mode) {
  $names = $_POST['c_name'] ?? [];
  $creds = $_POST['c_credit'] ?? [];
  $scos = $_POST['c_score'] ?? [];
  $conn->begin_transaction();
  try {
    $del = $conn->prepare("DELETE FROM semesters WHERE user_id = ? AND semester_name = ?");
    $del->bind_param("is", $user_id, $selected_semester);
    $del->execute();
    $ins_s = $conn->prepare("INSERT INTO semesters (user_id, semester_name) VALUES (?, ?)");
    $ins_s->bind_param("is", $user_id, $selected_semester);
    $ins_s->execute();
    $s_id = $conn->insert_id;
    $ins_c = $conn->prepare("INSERT INTO courses (semester_id, course_name, credits, score) VALUES (?, ?, ?, ?)");
    foreach ($names as $i => $n) {
      $nm = trim($n);
      if (empty($nm))
        continue;
      $ins_c->bind_param("isid", $s_id, $nm, $creds[$i], $scos[$i]);
      $ins_c->execute();
    }
    $conn->commit();
    header("Location: score-page.php?semester=" . urlencode($selected_semester));
    exit();
  } catch (Exception $e) {
    $conn->rollback();
  }
}

$u_info = $conn->query("SELECT full_name, student_id FROM users WHERE id = $user_id")->fetch_assoc();
function build_url($p)
{
  global $selected_semester, $search_name, $search_credit, $sort_by, $sort_order;
  return "score-page.php?semester=" . urlencode($selected_semester) . "&page=$p&q=" . urlencode($search_name) . "&c=$search_credit&sort=$sort_by&order=$sort_order";
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

    <form method="GET" class="filter-card">
      <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">
      <div class="input-box">
        <label>Tìm tên môn học</label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="Nhập từ khóa...">
      </div>
      <div class="filter-grid">
        <div class="input-box"><label>Số tín chỉ</label><input type="number" name="c"
            value="<?php echo $search_credit; ?>"></div>
        <div class="input-box"><label>Sắp xếp theo</label>
          <select name="sort">
            <option value="score" <?php echo $sort_by == 'score' ? 'selected' : ''; ?>>Điểm số</option>
            <option value="credits" <?php echo $sort_by == 'credits' ? 'selected' : ''; ?>>Số tín chỉ</option>
          </select>
        </div>
      </div>
      <div class="filter-grid">
        <div class="input-box"><label>Thứ tự</label>
          <select name="order">
            <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Giảm dần</option>
            <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Tăng dần</option>
          </select>
        </div>
        <div class="input-box" style="display:flex; align-items:flex-end;">
          <button type="submit" class="apply-btn">Lọc kết quả</button>
        </div>
      </div>
    </form>

    <form method="POST" class="flex-container" style="width:100%; margin-top:0;">
      <div class="semester-card">
        <h3>Thông tin học kỳ</h3>
        <div class="input-box">
          <label>Chọn học kỳ</label>
          <select onchange="window.location.href='score-page.php?semester=' + this.value">
            <option value="Tất cả" <?php echo $is_all_mode ? 'selected' : ''; ?>>Tất cả (Xem tích lũy)</option>
            <?php foreach (["HK2 2025-2026", "HK3 2025-2026", "HK1 2026-2027", "HK2 2026-2027", "HK3 2026-2027"] as $opt): ?>
              <option value="<?php echo $opt; ?>" <?php echo ($opt == $selected_semester) ? 'selected' : ''; ?>>
                <?php echo $opt; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if (!$is_all_mode): ?>
          <div class="input-box"><label>Số môn học</label><input type="number" id="courseCount"
              value="<?php echo count($all_data); ?>"></div>
        <?php endif; ?>
      </div>

      <div id="courseContainer" class="flex-container" style="width:100%; margin-top:0;">
        <?php foreach ($display_courses as $index => $course): ?>
          <div class="course-card">
            <h4 style="color:#1d71bb; margin-bottom:1rem;">Môn <?php echo $offset + $index + 1; ?></h4>
            <div class="input-box"><label>Tên môn</label><input type="text" name="c_name[]"
                value="<?php echo htmlspecialchars($course['name']); ?>" <?php echo $is_all_mode ? 'readonly' : ''; ?>
                required></div>
            <div class="course-card-details">
              <div class="input-box"><label>Tín chỉ</label><input type="number" name="c_credit[]"
                  value="<?php echo $course['credits']; ?>" <?php echo $is_all_mode ? 'readonly' : ''; ?> required></div>
              <div class="input-box"><label>Điểm</label><input type="number" step="0.1" max="4.0" name="c_score[]"
                  value="<?php echo $course['score']; ?>" <?php echo $is_all_mode ? 'readonly' : ''; ?> required></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="<?php echo build_url($i); ?>"
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