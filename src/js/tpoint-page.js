function toggleAccordion(element) {
  const card = element.parentElement;
  card.classList.toggle("active");
}

// Hàm lấy ngày hôm nay định dạng YYYY-MM-DD
function getTodayDate() {
  return new Date().toISOString().split('T')[0];
}
function checkScoreLimit(input) {
  const block = input.closest('.criterion-block');
  const badge = block.querySelector('.badge');
  const maxAllowed = parseFloat(badge.textContent);
  
  let total = 0;
  block.querySelectorAll('.event-score').forEach(inp => {
    total += parseFloat(inp.value) || 0;
  });

  if (total > maxAllowed) {
    alert(`Tổng điểm mục này không được vượt quá ${maxAllowed} điểm!`);
    input.value = ""; // Xóa giá trị sai
  }

  // Cập nhật lại trạng thái nút ngay lập tức
  if (typeof checkAllAddButtons === "function") {
    checkAllAddButtons();
  }
}

// Chỉnh sửa một chút ở addEvent để tự động chặn nếu bấm cố
function addEvent(button, criterionId) {
  const block = button.closest('.criterion-block');
  const maxAllowed = parseFloat(block.querySelector('.badge').textContent);
  let currentTotal = 0;
  block.querySelectorAll('.event-score').forEach(input => {
    currentTotal += parseFloat(input.value) || 0;
  });

  if (currentTotal >= maxAllowed) {
    alert("Mục này đã đạt điểm tối đa!");
    return;
  }

  const container = button.previousElementSibling;
  const eventCount = container.querySelectorAll('.event-card').length + 1;
  const now = new Date();
  const today = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');

  const eventCard = document.createElement("div");
  eventCard.classList.add("event-card");

  eventCard.innerHTML = `
    <div class="event-title">Sự kiện mới ${eventCount}</div>
    <div class="event-actions">
      <input type="hidden" name="crit_id[]" value="${criterionId}">
      <input type="date" name="event_dates[]" value="${today}" max="${today}" required>
      <input type="number" name="event_scores[]" class="event-score" 
             placeholder="Điểm" min="0" step="0.1" required 
             oninput="checkScoreLimit(this)">
      <label class="upload-label">
        📤 Minh chứng (tùy chọn)
        <input type="file" name="evidences[]" accept="image/*,.pdf" onchange="displayFileName(this)">
      </label>
    </div>
    <small class="file-name-display" style="display:block; margin-top:5px; color:#666;"></small>
  `;

  container.appendChild(eventCard);
}

function checkScoreLimit(input) {
  const block = input.closest('.criterion-block');
  const badge = block.querySelector('.badge');
  const maxAllowed = parseFloat(badge.textContent);
  
  let total = 0;
  block.querySelectorAll('.event-score').forEach(inp => {
    total += parseFloat(inp.value) || 0;
  });

  if (total > maxAllowed) {
    alert(`Tổng điểm mục này không được vượt quá ${maxAllowed} điểm!`);
    input.value = ""; // Xóa giá trị vừa nhập sai
  }
}

function displayFileName(input) {
  const name = input.files[0] ? input.files[0].name : "";
  // Sửa lại đường dẫn DOM để hiển thị đúng thẻ small
  input.parentElement.parentElement.nextElementSibling.textContent = "File: " + name;
}

function updateProgress(score, maxScore) {
  const percent = Math.min((score / maxScore) * 100, 100);
  const fill = document.getElementById("progressFill");
  if (fill) fill.style.width = percent + "%";
  
  const scoreText = document.getElementById("totalScore");
  if (scoreText) scoreText.textContent = score + " / " + maxScore;
}
