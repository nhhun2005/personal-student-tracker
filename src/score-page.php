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

// --- LẤY THAM SỐ SEARCH & SORT ---
$search_name = $_GET['q'] ?? '';
$search_credit = (isset($_GET['c']) && $_GET['c'] !== '') ? intval($_GET['c']) : null;
$sort_by = in_array($_GET['sort'] ?? '', ['score', 'credits']) ? $_GET['sort'] : 'c.id';
$sort_order = strtoupper($_GET['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

// --- PHÂN TRANG ---
$items_per_page = 5;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// --- 1. XÂY DỰNG WHERE CLAUSE ĐỘNG ---
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

// --- 2. TÍNH TOÁN GPA (Dựa trên kết quả đã lọc) ---
$stmt_gpa = $conn->prepare("SELECT c.credits, c.score FROM courses c JOIN semesters s ON c.semester_id = s.id $where_sql");
$stmt_gpa->bind_param($types, ...$params);
$stmt_gpa->execute();
$all_data = $stmt_gpa->get_result()->fetch_all(MYSQLI_ASSOC);

$total_points = 0;
$total_credits = 0;
foreach ($all_data as $row) {
  $total_points += ($row['credits'] * $row['score']);
  $total_credits += $row['credits'];
}
$final_score = $total_credits > 0 ? round($total_points / $total_credits, 2) : 0;

// --- 3. TRUY VẤN PHÂN TRANG & SẮP XẾP ---
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses c JOIN semesters s ON c.semester_id = s.id $where_sql");
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_items / $items_per_page));

// Thêm LIMIT, OFFSET và ORDER BY vào query hiển thị
$display_sql = "SELECT c.course_name as name, c.credits, c.score 
                FROM courses c 
                JOIN semesters s ON c.semester_id = s.id 
                $where_sql 
                ORDER BY $sort_by $sort_order 
                LIMIT ? OFFSET ?";
$stmt_display = $conn->prepare($display_sql);
$display_types = $types . "ii";
$display_params = array_merge($params, [$items_per_page, $offset]);
$stmt_display->bind_param($display_types, ...$display_params);
$stmt_display->execute();
$display_courses = $stmt_display->get_result()->fetch_all(MYSQLI_ASSOC);

// --- XỬ LÝ LƯU (POST) ---
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
      $name = trim($n);
      if (empty($name))
        continue;
      $c_cred = intval($creds[$i]);
      $c_sco = floatval($scos[$i]);
      if ($c_cred <= 0 || $c_sco < 0 || $c_sco > 4.0)
        throw new Exception("Dữ liệu không hợp lệ.");
      $ins_c->bind_param("isid", $s_id, $name, $c_cred, $c_sco);
      $ins_c->execute();
    }
    $conn->commit();
    header("Location: score-page.php?semester=" . urlencode($selected_semester));
    exit();
  } catch (Exception $e) {
    $conn->rollback();
    $error_msg = $e->getMessage();
  }
}

$u_info = $conn->query("SELECT full_name, student_id FROM users WHERE id = $user_id")->fetch_assoc();

// Hàm Helper để build URL phân trang không bị mất filter
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
  <title>Quản lý điểm số - Student Tracker</title>
  <link rel="stylesheet" href="./css/global-style.css" />
  <link rel="stylesheet" href="./css/score-page.css" />
  <style>
    .filter-container {
      background: white;
      padding: 20px;
      border-radius: 12px;
      width: 40%;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .filter-item {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .filter-item label {
      font-weight: bold;
      font-size: 0.9rem;
      color: #4b5563;
    }

    .pagination {
      display: flex;
      gap: 8px;
      margin: 20px 0;
      justify-content: center;
    }

    .page-link {
      padding: 10px 15px;
      background: white;
      border-radius: 8px;
      text-decoration: none;
      color: #1d71bb;
      border: 1px solid #ddd;
      font-weight: bold;
    }

    .page-link.active {
      background: #1d71bb;
      color: white;
      border-color: #1d71bb;
    }

    .search-btn {
      grid-column: span 2;
      background: #1d71bb;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    }
  </style>
  <script>
    const SAVED_COURSES = <?php echo json_encode($all_data); ?>;
    const IS_ALL_MODE = <?php echo json_encode($is_all_mode); ?>;
  </script>
</head>

<body>
  <header>
    <h2><?php echo $is_all_mode ? "Tích lũy toàn khóa" : "Điểm trung bình học kỳ"; ?></h2>
    <svg onclick="window.location.href='hub-page.php'" class="header-exit-icon" width="24" height="24"
      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
      stroke-linejoin="round">
      <path d="M19 12H5M12 19l-7-7 7-7" />
    </svg>
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
      <p>GPA <?php echo $is_all_mode ? "Tích lũy" : "Học kỳ"; ?></p>
      <h2><?php echo number_format($final_score, 2); ?></h2>
      <h3>Xếp loại:
        <?php echo ($final_score >= 2.0) ? ($final_score >= 3.6 ? "Xuất sắc" : ($final_score >= 3.2 ? "Giỏi" : ($final_score >= 2.5 ? "Khá" : "Trung bình"))) : "Yếu/Kém"; ?>
      </h3>
    </div>

    <form method="GET" class="filter-container">
      <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">
      <div class="filter-item">
        <label>Tìm tên môn</label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($search_name); ?>" placeholder="Nhập từ khóa...">
      </div>
      <div class="filter-item">
        <label>Số tín chỉ</label>
        <input type="number" name="c" value="<?php echo $search_credit; ?>" placeholder="Tất cả">
      </div>
      <div class="filter-item">
        <label>Sắp xếp theo</label>
        <select name="sort">
          <option value="score" <?php echo $sort_by == 'score' ? 'selected' : ''; ?>>Điểm số</option>
          <option value="credits" <?php echo $sort_by == 'credits' ? 'selected' : ''; ?>>Số tín chỉ</option>
        </select>
      </div>
      <div class="filter-item">
        <label>Thứ tự</label>
        <select name="order">
          <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Giảm dần</option>
          <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Tăng dần</option>
        </select>
      </div>
      <button type="submit" class="search-btn">Áp dụng bộ lọc</button>
    </form>

    <form method="POST" id="scoreForm"
      style="width: 100%; display: flex; flex-direction: column; align-items: center; gap: 2rem;">
      <div class="semester-card">
        <h3>Thông tin học kỳ</h3>
        <select onchange="window.location.href='score-page.php?semester=' + this.value">
          <option value="Tất cả" <?php echo $is_all_mode ? 'selected' : ''; ?>>Tất cả (Xem tích lũy)</option>
          <?php foreach (["HK2 2025-2026", "HK3 2025-2026", "HK1 2026-2027", "HK2 2026-2027", "HK3 2026-2027"] as $opt): ?>
            <option value="<?php echo $opt; ?>" <?php echo ($opt == $selected_semester) ? 'selected' : ''; ?>>
              <?php echo $opt; ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (!$is_all_mode): ?>
          <h4>Số môn học</h4>
          <input type="number" id="courseCount" value="<?php echo count($all_data); ?>">
        <?php endif; ?>
      </div>

      <h3 id="guide">Danh sách môn học (Trang <?php echo $current_page; ?>/<?php echo $total_pages; ?>)</h3>

      <div id="courseContainer"
        style="width: 100%; display: flex; flex-direction: column; align-items: center; gap: 2rem;">
        <?php foreach ($display_courses as $index => $course): ?>
          <div class="course-card">
            <h4>Môn <?php echo $offset + $index + 1; ?></h4>
            <input type="text" name="c_name[]" value="<?php echo htmlspecialchars($course['name']); ?>" <?php echo $is_all_mode ? 'readonly style="background:#f9f9f9"' : ''; ?> required>
            <div class="course-card-details">
              <input type="number" name="c_credit[]" value="<?php echo $course['credits']; ?>" <?php echo $is_all_mode ? 'readonly style="background:#f9f9f9"' : ''; ?> required>
              <input type="number" step="0.1" max="4.0" name="c_score[]" value="<?php echo $course['score']; ?>" <?php echo $is_all_mode ? 'readonly style="background:#f9f9f9"' : ''; ?> required>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo build_url($i); ?>"
              class="page-link <?php echo ($i == $current_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

      <?php if (!$is_all_mode): ?>
        <button type="submit" name="save_score" class="save-button" style="border:none;">Lưu & cập nhật điểm số</button>
      <?php endif; ?>
    </form>
  </div>
  <script src="./js/score-page.js"></script>
</body>

</html>