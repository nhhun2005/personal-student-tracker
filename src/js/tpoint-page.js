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

function displayFileName(input) {
    const name = input.files[0] ? input.files[0].name : "";
    const display = input.closest('.event-card').querySelector('.file-name-display');
    if (display) display.textContent = "File: " + name;
}

function updateProgress(score, maxScore) {
    const percent = Math.min((score / maxScore) * 100, 100);
    const fill = document.getElementById("progressFill");
    if (fill) fill.style.width = percent + "%";
}

function checkScoreLimit(input) {
    const block = input.closest('.criterion-block');
    const badge = block.querySelector('.badge');
    const maxCritAllowed = parseFloat(badge.textContent);
    
    // 1. Kiểm tra tiêu chí nhỏ (Ia, Ib..)
    let critTotal = 0;
    block.querySelectorAll('.event-score').forEach(inp => {
        critTotal += parseFloat(inp.value) || 0;
    });

    if (critTotal > maxCritAllowed) {
        alert(`Tiêu chí này tối đa chỉ được ${maxCritAllowed} điểm!`);
        input.value = "";
    }

    // 2. Kiểm tra mục lớn (I, II..)
    const sectionCard = input.closest('.accordion-card');
    const sectionKey = sectionCard.getAttribute('data-section');
    const maxSectionAllowed = sectionLimits[sectionKey];

    let sectionTotal = 0;
    sectionCard.querySelectorAll('.event-score').forEach(inp => {
        sectionTotal += parseFloat(inp.value) || 0;
    });

    if (sectionTotal > maxSectionAllowed) {
        alert(`Mục ${sectionKey} đã vượt quá giới hạn ${maxSectionAllowed} điểm!`);
        input.value = "";
    }

    checkAllAddButtons();
}

function checkAllAddButtons() {
    // Lặp qua từng thẻ mục lớn
    document.querySelectorAll('.accordion-card').forEach(sectionCard => {
        const sectionKey = sectionCard.getAttribute('data-section');
        const maxSectionAllowed = sectionLimits[sectionKey];
        
        let sectionTotal = 0;
        sectionCard.querySelectorAll('.event-score').forEach(inp => {
            sectionTotal += parseFloat(inp.value) || 0;
        });

        // Nếu cả mục lớn đầy điểm, khóa TẤT CẢ nút thêm trong mục đó
        const isSectionFull = sectionTotal >= maxSectionAllowed;

        sectionCard.querySelectorAll('.criterion-block').forEach(block => {
            const maxCritAllowed = parseFloat(block.querySelector('.badge').textContent);
            let critTotal = 0;
            block.querySelectorAll('.event-score').forEach(inp => {
                critTotal += parseFloat(inp.value) || 0;
            });

            const addButton = block.querySelector('.add-event-btn');
            if (!addButton) return;

            if (isSectionFull) {
                addButton.style.pointerEvents = "none";
                addButton.style.opacity = "0.5";
                addButton.innerText = `Mục ${sectionKey} đã đủ điểm`;
            } else if (critTotal >= maxCritAllowed) {
                addButton.style.pointerEvents = "none";
                addButton.style.opacity = "0.5";
                addButton.innerText = "Tiêu chí này đã đủ điểm";
            } else {
                addButton.style.pointerEvents = "auto";
                addButton.style.opacity = "1";
                addButton.innerText = "+ Thêm sự kiện";
            }
        });
    });
}

function addEvent(button, criterionId) {
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
          📤 Minh chứng
          <input type="file" name="evidences[]" accept="image/*,.pdf" onchange="displayFileName(this)">
        </label>
      </div>
      <small class="file-name-display" style="display:block; margin-top:5px; color:#666;"></small>
    `;

    container.appendChild(eventCard);
}

function renderSavedEvent(container, data) {
    const card = document.createElement("div");
    card.classList.add("event-card", "saved");

    const hasEvidence = data.has_content == 1;

    card.innerHTML = `
      <div class="event-actions">
        <input type="date" value="${data.event_date}" disabled>
        <input type="number" value="${data.score_value}" disabled class="event-score">
        
        ${hasEvidence ?
            `<a href="./includes/view-evidence.php?id=${data.id}" target="_blank" class="upload-label" 
              style="background:#e0f2fe; border:1px solid #7dd3fc; text-decoration:none; color:#0369a1;">
              Xem minh chứng
           </a>` :
            `<span class="upload-label" style="background:#f3f4f6; color:#9ca3af; cursor:not-allowed;">
              Không có minh chứng
           </span>`
        }
      </div>
    `;
    container.appendChild(card);
}

document.addEventListener("DOMContentLoaded", function () {
    const config = window.TPOINT_DATA || { currentScore: 0, maxScore: 100, savedEvidences: [] };
    
    updateProgress(config.currentScore, config.maxScore);

    if (config.savedEvidences && config.savedEvidences.length > 0) {
        config.savedEvidences.forEach(ev => {
            const block = document.querySelector(`.criterion-block[data-criterion-id="${ev.criterion_id}"]`);
            if (block) {
                const container = block.querySelector('.event-container');
                renderSavedEvent(container, ev);
            }
        });
    }

    checkAllAddButtons();

    const form = document.getElementById('tpointForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            // JS validate trước khi gửi
            let hasError = false;
            document.querySelectorAll('.accordion-card').forEach(sectionCard => {
                const sectionKey = sectionCard.getAttribute('data-section');
                const maxSectionAllowed = sectionLimits[sectionKey];
                let sectionTotal = 0;
                sectionCard.querySelectorAll('.event-score').forEach(inp => {
                    sectionTotal += parseFloat(inp.value) || 0;
                });
                if (sectionTotal > maxSectionAllowed) {
                    alert(`Mục ${sectionKey} quá giới hạn (${maxSectionAllowed})! Vui lòng kiểm tra lại.`);
                    hasError = true;
                }
            });

            if (hasError) {
                e.preventDefault();
            } else {
                const btn = document.querySelector('.submit-btn');
                if (btn) {
                    btn.innerHTML = "Đang xử lý...";
                    btn.style.opacity = "0.7";
                    btn.style.pointerEvents = "none";
                }
            }
        });
    }
});