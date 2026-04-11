//Max điểm từng phần, 
const sectionLimits = {
    I: 20,
    II: 25,
    III: 20,
    IV: 25,
    V: 10
};

let tpointState = {
    currentScore: 0,
    maxScore: 100,
    semesterName: "",
    sectionScores: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
    classification: "N/A",
    savedEvidences: []
};
// Hàm tiện ích để toggle accordion, có thể dùng cho cả phần tổng quan và phần chi tiết của từng tiêu chí
function toggleAccordion(element) {
    const card = element.parentElement;
    card.classList.toggle("active");
}
// Hàm tiện ích để chuyển đổi học kỳ, có thể dùng cho dropdown hoặc các nút chuyển học kỳ khác nếu cần thiết
function changeSemester(semesterName) {
    window.location.href = "?semester=" + encodeURIComponent(semesterName);
}
// Hàm tiện ích để lấy ngày hiện tại theo định dạng YYYY-MM-DD, có thể dùng cho cả thẻ mới và thẻ đã lưu khi cần thiết
function getTodayDate() {
    return new Date().toISOString().split("T")[0];
}
// Hàm chung để hiển thị tên file đã chọn, có thể dùng cho cả thẻ mới và thẻ đã lưu khi chọn file
function displayFileName(input) {
    const name = input.files[0] ? input.files[0].name : "";
    const display = input.closest(".event-card").querySelector(".file-name-display");
    if (display) {
        display.textContent = name ? `File: ${name}` : "";
    }
}
// Hàm chung để cập nhật thanh tiến trình dựa trên điểm số hiện tại và điểm tối đa, có thể gọi lại sau khi fetch dữ liệu mới hoặc sau khi tạo/cập nhật/xóa minh chứng để đồng bộ UI
function updateProgress(score, maxScore) {
    const percent = maxScore > 0 ? Math.min((score / maxScore) * 100, 100) : 0;
    const fill = document.getElementById("progressFill");
    if (fill) {
        fill.style.width = percent + "%";
    }
}
// Hàm chung để hiển thị thông báo trạng thái, có thể dùng cho cả thành công và lỗi
function showStatusMessage(message, isError = false) {
    const box = document.getElementById("statusMessage");
    if (!box) return;

    if (!message) {
        box.style.display = "none";
        box.textContent = "";
        return;
    }

    box.textContent = message;
    box.style.display = "block";
    box.style.background = isError ? "#fef2f2" : "#e0f2fe";
    box.style.color = isError ? "#b91c1c" : "#0369a1";
    box.style.borderColor = isError ? "#fecaca" : "#bae6fd";
}
// Hàm chung để cập nhật toàn bộ UI liên quan đến điểm số và phân loại dựa trên state hiện tại, có thể gọi lại sau khi fetch dữ liệu mới hoặc sau khi tạo/cập nhật/xóa minh chứng để đồng bộ UI
function updateSummaryUI() {
    const totalScore = document.getElementById("totalScore");
    if (totalScore) {
        totalScore.textContent = `${tpointState.currentScore} / ${tpointState.maxScore}`;
    }

    const classificationText = document.getElementById("classificationText");
    if (classificationText) {
        classificationText.textContent = tpointState.classification;
    }

    const sectionMap = { I: 1, II: 2, III: 3, IV: 4, V: 5 };
    document.querySelectorAll(".accordion-card").forEach((sectionCard) => {
        const sectionKey = sectionCard.getAttribute("data-section");
        const scoreIndex = sectionMap[sectionKey];
        const currentValue = tpointState.sectionScores?.[scoreIndex] ?? 0;
        const maxValue = sectionLimits[sectionKey] ?? 0;
        const display = sectionCard.querySelector(".section-score-display");
        if (display) {
            display.textContent = `${currentValue}/${maxValue}`;
        }
    });

    updateProgress(tpointState.currentScore, tpointState.maxScore);
}
// Hàm chung để xóa tất cả minh chứng đã render, chuẩn bị cho việc render lại từ state mới
function clearRenderedEvidence() {
    document.querySelectorAll(".event-container").forEach((container) => {
        container.innerHTML = "";
    });
}
// Hàm chung để render lại tất cả minh chứng từ state, có thể gọi lại sau khi fetch dữ liệu mới hoặc sau khi tạo/cập nhật/xóa minh chứng để đồng bộ UI
function renderAllEvidence() {
    clearRenderedEvidence();

    (tpointState.savedEvidences || []).forEach((evidence) => {
        const critId = String(evidence.criterion_id);

        const block = document.querySelector(
            `.criterion-block[data-criterion-id="${critId}"]`
        );

        if (!block) {
            console.warn("Không tìm thấy block cho criterion:", critId);
            return;
        }

        const container = block.querySelector(".event-container");
        if (!container) return;

        renderSavedEvent(container, evidence);
    });
}
// Hàm chung để áp dụng dữ liệu mới từ backend vào state và cập nhật UI, có thể gọi lại sau khi tạo/cập nhật/xóa minh chứng để đồng bộ UI
function applyBackendData(data, message = "") {
    tpointState = {
        currentScore: Number(data.final_score || 0),
        maxScore: tpointState.maxScore || 100,
        semesterName: data.semester || tpointState.semesterName,
        sectionScores: data.scores || { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
        classification: data.classification || "N/A",
        savedEvidences: data.evidence_data || []
    };

    const app = document.getElementById("tpointApp");
    if (app) {
        app.dataset.semesterName = tpointState.semesterName;
    }

    updateSummaryUI();
    renderAllEvidence();
    showStatusMessage(message, false);
}
// Hàm chung để fetch dữ liệu điểm rèn luyện từ backend, có thể gọi lại sau khi tạo/cập nhật/xóa minh chứng để đồng bộ UI
async function fetchTpointData() {
    const params = new URLSearchParams({
        action: "fetch_tpoint_data",
        semester: tpointState.semesterName
    });

    const response = await fetch(`./services/TrainingPointService.php?${params.toString()}`, {
        headers: { Accept: "application/json" }
    });

    const payload = await response.json();
    if (!response.ok || !payload.ok) {
        throw new Error(payload.message || "Không thể tải dữ liệu điểm rèn luyện.");
    }

    applyBackendData(payload.data, payload.message || "");
}
// Hàm chung để gửi yêu cầu tạo/cập nhật/xóa minh chứng,
async function sendEvidenceRequest(formData) {
    const response = await fetch("./services/TrainingPointService.php", {
        method: "POST",
        headers: { Accept: "application/json" },
        body: formData
    });

    const payload = await response.json();
    if (!response.ok || !payload.ok) {
        throw new Error(payload.message || "Không thể lưu minh chứng.");
    }

    await fetchTpointData();
    showStatusMessage(payload.message || "Thao tác thành công.", false);
}
// Hàm tiện ích để xây dựng FormData từ thẻ sự kiện mới, có thể dùng chung cho cả nút lưu từng thẻ và nút lưu tất cả
function buildCreateEvidenceFormData(card, criterionId) {
    const dateValue = card.querySelector(".new-date-input")?.value || "";
    const scoreValue = card.querySelector(".new-score-input")?.value || "";
    const fileInput = card.querySelector(".new-file-input");

    if (!dateValue || scoreValue === "") {
        throw new Error("Vui lòng nhập đủ ngày và điểm trước khi lưu.");
    }

    const formData = new FormData();
    formData.append("create_tpoint_evidence", "1");
    formData.append("semester_name", tpointState.semesterName);
    formData.append("criterion_id", String(criterionId));
    formData.append("event_date", dateValue);
    formData.append("event_score", scoreValue);
    if (fileInput?.files?.[0]) {
        formData.append("evidence", fileInput.files[0]);
    }

    return formData;
}

// Logic validation mới: Chỉ chặn nhập số âm
function attachScoreValidation(input) {
    if (input.dataset.hasValidation) return; // chặn duplicate
    input.dataset.hasValidation = "true";

    input.addEventListener("input", () => {
        let value = parseFloat(input.value);
        if (value < 0) {
            input.value = 0;
        }
    });
}
// Logic thêm sự kiện mới: Cho phép tạo thẻ mới ngay lập tức, nhưng chỉ chặn khi lưu nếu thiếu dữ liệu hoặc điểm âm
function addEvent(button, criterionId) {
    const container = button.previousElementSibling;
    const eventCount = container.querySelectorAll(".event-card").length + 1;
    const today = getTodayDate();

    const card = document.createElement("div");
    card.classList.add("event-card");
    card.dataset.pending = "true";
    card.dataset.criterionId = String(criterionId);

    card.innerHTML = `
      <div class="event-title">
  Sự kiện mới ${eventCount}
  <span class="delete-temp-btn">✖</span>
</div>
      <div class="event-actions">
        <input type="date" class="new-date-input" value="${today}" max="${today}" required>
        <input type="number" class="new-score-input" placeholder="Điểm" min="0" step="0.1" required>
        <label class="upload-label">
          📤 Minh chứng
          <input type="file" class="new-file-input" accept="image/*,text/plain">
        </label>
      </div>
      <small class="file-name-display" style="display:block; margin-top:5px; color:#666;"></small>
      <div class="saved-event-actions">
        <button type="button" class="save-event-btn">Lưu minh chứng</button>
        <button type="button" class="cancel-edit-btn">Hủy</button>
      </div>
    `;

    const saveButton = card.querySelector(".save-event-btn");
    const cancelButton = card.querySelector(".cancel-edit-btn");
    const deleteBtn = card.querySelector(".delete-temp-btn");

    deleteBtn.addEventListener("click", () => {
        card.remove();
    });

    // SAVE
    saveButton.addEventListener("click", async () => {
        try {
            const scoreInput = card.querySelector(".new-score-input");
            const dateInput = card.querySelector(".new-date-input");
            const fileInput = card.querySelector(".new-file-input");

            const score = parseFloat(scoreInput.value);
            const date = dateInput.value;

            // Chỉ chặn nếu thiếu dữ liệu hoặc điểm là số âm
            if (isNaN(score) || !date || score < 0) {
                alert("Vui lòng nhập đầy đủ điểm và ngày hợp lệ (>= 0)");
                return;
            }

            const formData = new FormData();
            formData.append("create_tpoint_evidence", "1");
            formData.append("semester_name", tpointState.semesterName);
            formData.append("criterion_id", criterionId);
            formData.append("event_score", score);
            formData.append("event_date", date);

            if (fileInput.files[0]) {
                formData.append("evidence", fileInput.files[0]);
            }

            // 🔄 UI loading
            saveButton.disabled = true;
            saveButton.textContent = "Đang lưu...";

            const res = await fetch("./services/TrainingPointService.php", {
                method: "POST",
                body: formData
            });

            const result = await res.json();

            if (!result.ok) {
                throw new Error(result.message);
            }

            // SUCCESS
            showStatusMessage("Lưu thành công");

            // update UI từ backend
            await fetchTpointData();

            // remove card pending
            card.remove();

        } catch (error) {
            console.error(error);
            alert(error.message);

            saveButton.disabled = false;
            saveButton.textContent = "Lưu minh chứng";
        }
    });

    // CANCEL
    cancelButton.addEventListener("click", () => {
        card.remove();
    });

    container.prepend(card);
    const scoreInput = card.querySelector(".new-score-input");
    attachScoreValidation(scoreInput);
}

function setSavedEventEditMode(card, isEditing) {
    card.classList.toggle("is-editing", isEditing);

    const dateInput = card.querySelector(".saved-date-input");
    const scoreInput = card.querySelector(".saved-score-input");
    const fileInput = card.querySelector(".saved-file-input");
    const fileHint = card.querySelector(".saved-file-hint");
    const editButton = card.querySelector(".edit-event-btn");
    const saveButton = card.querySelector(".save-event-btn");
    const cancelButton = card.querySelector(".cancel-edit-btn");
    const deleteButton = card.querySelector(".delete-event-btn");

    if (dateInput) dateInput.disabled = !isEditing;
    if (scoreInput) scoreInput.disabled = !isEditing;
    if (fileInput) fileInput.disabled = !isEditing;
    if (fileHint) fileHint.style.display = isEditing ? "block" : "none";
    if (editButton) editButton.style.display = isEditing ? "none" : "inline-flex";
    if (saveButton) saveButton.style.display = isEditing ? "inline-flex" : "none";
    if (cancelButton) cancelButton.style.display = isEditing ? "inline-flex" : "none";
    if (deleteButton) deleteButton.style.display = isEditing ? "none" : "inline-flex";
}

function resetSavedEventForm(card) {
    const dateInput = card.querySelector(".saved-date-input");
    const scoreInput = card.querySelector(".saved-score-input");
    const fileInput = card.querySelector(".saved-file-input");
    const display = card.querySelector(".file-name-display");
    const defaultFileMessage = display?.dataset.defaultText || "";

    if (dateInput) dateInput.value = dateInput.dataset.originalValue || "";
    if (scoreInput) scoreInput.value = scoreInput.dataset.originalValue || "";
    if (fileInput) fileInput.value = "";
    if (display) display.textContent = defaultFileMessage;

    setSavedEventEditMode(card, false);
}

function renderSavedEvent(container, data) {
    const card = document.createElement("div");
    card.classList.add("event-card", "saved");

    const hasEvidence = Number(data.has_content) === 1;
    const fileStatusMarkup = hasEvidence
        ? `<a href="./includes/view-evidence.php?id=${data.id}" target="_blank" class="upload-label saved-file-link">Xem minh chứng hiện tại</a>`
        : `<span class="upload-label saved-file-link saved-file-empty">Chưa có minh chứng</span>`;
    const fileStatusText = hasEvidence
        ? "Đang giữ file minh chứng hiện tại. Chọn file mới nếu muốn thay thế."
        : "Chưa có file minh chứng. Có thể chọn file để bổ sung.";

    card.innerHTML = `
      <div class="event-actions">
        <input type="date" value="${data.event_date}" data-original-value="${data.event_date}" max="${getTodayDate()}" disabled class="saved-date-input" required>
        <input type="number" value="${data.score_value}" data-original-value="${data.score_value}" class="saved-score-input" min="0" step="0.1" disabled required>
        <div class="saved-file-stack">
          ${fileStatusMarkup}
          <label class="upload-label saved-upload-label">
            Chọn file mới
            <input type="file" class="saved-file-input" accept="image/*,text/plain" onchange="displayFileName(this)" disabled>
          </label>
        </div>
      </div>
      <small class="file-name-display saved-file-hint" data-default-text="${encodeHtml(fileStatusText)}">${fileStatusText}</small>
      <div class="saved-event-actions">
        <button type="button" class="edit-event-btn">Sửa</button>
        <button type="button" class="save-event-btn">Cập nhật</button>
        <button type="button" class="cancel-edit-btn">Hủy sửa</button>
        <button type="button" class="delete-event-btn">Xóa</button>
      </div>
    `;

    const editButton = card.querySelector(".edit-event-btn");
    const saveButton = card.querySelector(".save-event-btn");
    const cancelButton = card.querySelector(".cancel-edit-btn");
    const deleteButton = card.querySelector(".delete-event-btn");
  
    editButton?.addEventListener("click", () => {
        setSavedEventEditMode(card, true);
        const scoreInput = card.querySelector(".saved-score-input");
        attachScoreValidation(scoreInput);
    });

    cancelButton?.addEventListener("click", () => {
        resetSavedEventForm(card);
    });

    saveButton?.addEventListener("click", async () => {
        const dateValue = card.querySelector(".saved-date-input")?.value || "";
        const scoreValue = parseFloat(card.querySelector(".saved-score-input")?.value);
        const fileInput = card.querySelector(".saved-file-input");

        // Chỉ chặn nếu thiếu dữ liệu hoặc điểm là số âm
        if (!dateValue || isNaN(scoreValue) || scoreValue < 0) {
            showStatusMessage("Vui lòng nhập đủ ngày và điểm hợp lệ (>= 0) trước khi cập nhật.", true);
            return;
        }

        const formData = new FormData();
        formData.append("update_tpoint_evidence", "1");
        formData.append("semester_name", tpointState.semesterName);
        formData.append("evidence_id", String(data.id));
        formData.append("update_event_date", dateValue);
        formData.append("update_event_score", scoreValue);
        if (fileInput?.files?.[0]) {
            formData.append("update_evidence", fileInput.files[0]);
        }

        saveButton.disabled = true;
        saveButton.textContent = "Đang cập nhật...";

        try {
            await sendEvidenceRequest(formData);
            setSavedEventEditMode(card, false);
        } catch (error) {
            showStatusMessage(error.message, true);
            saveButton.disabled = false;
            saveButton.textContent = "Cập nhật";
        }
    });
    
    deleteButton?.addEventListener("click", async () => {
        const confirmed = confirm("Xóa minh chứng này?");
        if (!confirmed) return;

        const formData = new FormData();
        formData.append("delete_tpoint_evidence", "1");
        formData.append("semester_name", tpointState.semesterName);
        formData.append("evidence_id", String(data.id));

        deleteButton.disabled = true;
        deleteButton.textContent = "Đang xóa...";

        try {
            await sendEvidenceRequest(formData);
        } catch (error) {
            showStatusMessage(error.message, true);
            deleteButton.disabled = false;
            deleteButton.textContent = "Xóa";
        }
    });

    setSavedEventEditMode(card, false);
    container.appendChild(card);
}

function encodeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}

document.addEventListener("DOMContentLoaded", async () => {
    const config = window.TPOINT_DATA || {};
    tpointState = {
        currentScore: Number(config.currentScore || 0),
        maxScore: Number(config.maxScore || 100),
        semesterName: config.semesterName || "",
        sectionScores: config.sectionScores || { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
        classification: config.classification || "N/A",
        savedEvidences: config.savedEvidences || []
    };

    updateSummaryUI();
    renderAllEvidence();

    await fetchTpointData();
});

document.getElementById("saveAllBtn")?.addEventListener("click", async () => {
    const pendingCards = document.querySelectorAll('.event-card[data-pending="true"]');

    if (pendingCards.length === 0) {
        alert("Không có dữ liệu cần lưu");
        return;
    }
// UI loading chung cho tất cả
    const saveBtn = document.getElementById("saveAllBtn");
    const originalText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = "Đang lưu tất cả...";

    let hasError = false;

    try {
        for (const card of pendingCards) {
            const criterionId = card.dataset.criterionId;
            
            if (!criterionId || criterionId === "undefined" || criterionId === "null") {
                console.warn("Bỏ qua 1 thẻ do không có criterionId hợp lệ:", card);
                continue;
            }

            const date = card.querySelector(".new-date-input")?.value;
            const scoreVal = parseFloat(card.querySelector(".new-score-input")?.value);
            const fileInput = card.querySelector(".new-file-input");

            // Chỉ chặn nếu thiếu dữ liệu hoặc điểm là số âm
            if (isNaN(scoreVal) || !date || scoreVal < 0) {
                hasError = true;
                continue; 
            }

            const formData = new FormData();
            formData.append("create_tpoint_evidence", "1");
            formData.append("semester_name", tpointState.semesterName);
            formData.append("criterion_id", criterionId);
            formData.append("event_score", scoreVal);
            formData.append("event_date", date);

            if (fileInput && fileInput.files[0]) {
                formData.append("evidence", fileInput.files[0]);
            }

            // Fetch trực tiếp để không gọi lại data liên tục
            const response = await fetch("./services/TrainingPointService.php", {
                method: "POST",
                headers: { Accept: "application/json" },
                body: formData
            });

            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                console.error("Lỗi thẻ ID " + criterionId + ":", payload.message);
                hasError = true;
            }
        }
// Sau khi xử lý tất cả, gọi lại fetch để cập nhật UI 1 lần duy nhất
        await fetchTpointData();
        
        if (hasError) {
            showStatusMessage("Đã xử lý, nhưng có vài mục bị lỗi (Kiểm tra lại dữ liệu điểm ngày/tháng).", true);
        } else {
            showStatusMessage("Lưu tất cả thành công", false);
        }

    } catch (err) {
        console.error(err);
        alert("Lỗi nghiêm trọng khi lưu dữ liệu");
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText;
    }
});