// js/tpoint-page.js
const sectionLimits = {
    'I': 20,
    'II': 25,
    'III': 20,
    'IV': 25,
    'V': 10
};
function toggleAccordion(element) {
    const card = element.parentElement;
    card.classList.toggle("active");
}

function changeSemester(s) {
    window.location.href = "?semester=" + encodeURIComponent(s);
}

function getTodayDate() {
    return new Date().toISOString().split('T')[0];
}

/**
 * Hiển thị tên file đã chọn
 */
function displayFileName(input) {
    const name = input.files[0] ? input.files[0].name : "";
    // Tìm thẻ small kế tiếp trong cấu trúc DOM
    const display = input.closest('.event-card').querySelector('.file-name-display');
    if (display) display.textContent = "File: " + name;
}

/**
 * Cập nhật thanh tiến trình điểm rèn luyện
 */
function updateProgress(score, maxScore) {
    const percent = Math.min((score / maxScore) * 100, 100);
    const fill = document.getElementById("progressFill");
    if (fill) fill.style.width = percent + "%";

    const scoreText = document.getElementById("totalScore");
    if (scoreText) scoreText.textContent = score + " / " + maxScore;
}

/**
 * Kiểm tra giới hạn điểm khi nhập liệu
 */
function checkScoreLimit(input) {
    const block = input.closest('.criterion-block');
    // Giả sử tiêu đề mục lớn nằm trong class .section-title hoặc dựa vào ID tiêu chí
    // Ở đây ta sẽ xác định mục lớn dựa vào cấu trúc class hoặc thuộc tính data của block cha
    const sectionWrapper = input.closest('.semester-card'); 
    const sectionHeader = sectionWrapper.querySelector('h3').textContent; // Ví dụ: "I. Ý thức học tập"
    
    // Lấy ký tự La Mã đầu tiên (I, II, III, IV, V)
    const sectionKey = sectionHeader.split('.')[0].trim();
    const maxSectionAllowed = sectionLimits[sectionKey];

    // 1. Kiểm tra giới hạn của riêng tiêu chí này (Badge)
    const badge = block.querySelector('.badge');
    const maxCritAllowed = parseFloat(badge.textContent);
    
    let critTotal = 0;
    block.querySelectorAll('.event-score').forEach(inp => {
        critTotal += parseFloat(inp.value) || 0;
    });

    if (critTotal > maxCritAllowed) {
        alert(`Tiêu chí này tối đa chỉ được ${maxCritAllowed} điểm!`);
        input.value = "";
        checkAllAddButtons();
        return;
    }

    // 2. Kiểm tra giới hạn tổng của cả mục lớn (I, II, III...)
    let sectionTotal = 0;
    sectionWrapper.querySelectorAll('.event-score').forEach(inp => {
        sectionTotal += parseFloat(inp.value) || 0;
    });

    if (maxSectionAllowed && sectionTotal > maxSectionAllowed) {
        alert(`Tổng điểm mục ${sectionKey} không được vượt quá ${maxSectionAllowed} điểm theo quy chế!`);
        input.value = "";
    }

    checkAllAddButtons();
}

/**
 * Kiểm tra và vô hiệu hóa nút "Thêm sự kiện" nếu đã đạt tối đa điểm
 */
function checkAllAddButtons() {
    const blocks = document.querySelectorAll('.criterion-block');
    blocks.forEach(block => {
        const maxAllowed = parseFloat(block.querySelector('.badge').textContent);
        let currentTotal = 0;

        block.querySelectorAll('.event-score').forEach(input => {
            currentTotal += parseFloat(input.value) || 0;
        });

        const addButton = block.querySelector('.add-event-btn');
        if (addButton) {
            if (currentTotal >= maxAllowed) {
                addButton.style.pointerEvents = "none";
                addButton.style.opacity = "0.5";
                addButton.innerText = "❌ Đã đạt điểm tối đa";
            } else {
                addButton.style.pointerEvents = "auto";
                addButton.style.opacity = "1";
                addButton.innerText = "+ Thêm sự kiện";
            }
        }
    });
}

/**
 * Thêm một ô nhập sự kiện mới
 */
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
    const today = getTodayDate();

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

/**
 * Render dữ liệu đã lưu từ DB
 */
function renderSavedEvent(container, data) {
    const card = document.createElement("div");
    card.classList.add("event-card", "saved");

    const hasEvidence = data.has_content == 1;

    card.innerHTML = `
      <span class="saved-label">✓ Đã lưu</span>
      <div class="event-actions">
        <input type="date" value="${data.event_date}" disabled>
        <input type="number" value="${data.score_value}" disabled class="event-score">
        
        ${hasEvidence ?
            `<a href="./includes/view-evidence.php?id=${data.id}" target="_blank" class="upload-label" 
              style="background:#e0f2fe; border:1px solid #7dd3fc; text-decoration:none; color:#0369a1; display:flex; align-items:center; justify-content:center; font-size: 0.8rem;">
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

document.addEventListener("DOMContentLoaded", function () {
    // Đọc dữ liệu từ đối tượng window đã nhúng ở PHP
    const config = window.TPOINT_DATA;
    
    // Kiểm tra trong Console trình duyệt xem config có dữ liệu không
    console.log("Dữ liệu từ PHP:", config);

    if (config) {
        // 1. Cập nhật progress bar
        updateProgress(config.currentScore, config.maxScore);

        // 2. Render các minh chứng đã lưu
        if (config.savedEvidences && config.savedEvidences.length > 0) {
            config.savedEvidences.forEach(ev => {
                // Phải dùng đúng cột criterion_id từ Database
                const block = document.querySelector(`.criterion-block[data-criterion-id="${ev.criterion_id}"]`);
                if (block) {
                    const container = block.querySelector('.event-container');
                    renderSavedEvent(container, ev);
                }
            });
        }
    }

    // 3. Kiểm tra các nút thêm
    checkAllAddButtons();

    // 4. Lắng nghe sự kiện submit form để validate
    const form = document.getElementById('tpointForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const blocks = document.querySelectorAll('.criterion-block');
            let hasError = false;

            for (let block of blocks) {
                const maxAllowed = parseFloat(block.querySelector('.badge').textContent);
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
                if (btn) {
                    btn.innerHTML = "⌛ Đang lưu...";
                    btn.style.opacity = "0.7";
                    btn.style.pointerEvents = "none";
                }
            }
        });
    }
});