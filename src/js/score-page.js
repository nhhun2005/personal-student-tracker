document.addEventListener("DOMContentLoaded", () => {
    const courseCountInput = document.getElementById("courseCount");
    const courseContainer = document.getElementById("courseContainer");
    const filterForm = document.getElementById("filterForm");
    const paginationContainer = document.querySelector(".pagination");
    const gpaValue = document.querySelector(".total-score h2");
    const gpaClassification = document.querySelector(".total-score h3");

    // 1. CHỨC NĂNG AJAX: Tải dữ liệu từ server
    async function fetchScores(url) {
        try {
            const response = await fetch(url);
            const data = await response.json();
            updateUI(data);
        } catch (error) {
            console.error('Lỗi khi tải dữ liệu:', error);
        }
    }

    function updateUI(data) {
        // Cập nhật GPA và Xếp loại
        gpaValue.innerText = parseFloat(data.gpa).toFixed(2);
        updateClassification(data.gpa);

        // Render lại danh sách môn học từ server
        const offset = (data.current_page - 1) * 5;
        const formattedData = data.courses.map(c => ({
            id: c.id,
            name: c.course_name,
            credits: c.credits,
            score: c.score
        }));
        
        renderCards(formattedData.length, formattedData, offset);

        // Render lại phân trang
        let pagHtml = "";
        for (let i = 1; i <= data.total_pages; i++) {
            pagHtml += `<a href="#" data-page="${i}" class="page-link ${i == data.current_page ? 'active' : ''}">${i}</a>`;
        }
        if (paginationContainer) {
            paginationContainer.innerHTML = pagHtml;
            paginationContainer.style.display = "flex";
        }
    }

    function updateClassification(score) {
        let rank = "Yếu/Kém";
        if (score >= 3.6) rank = "Xuất sắc";
        else if (score >= 3.2) rank = "Giỏi";
        else if (score >= 2.5) rank = "Khá";
        else if (score >= 2.0) rank = "Trung bình";
        gpaClassification.innerText = "Xếp loại: " + rank;
    }

    // 2. CHỨC NĂNG THỦ CÔNG: Thay đổi số lượng môn
    if (typeof IS_ALL_MODE !== 'undefined' && IS_ALL_MODE) {
        // Nếu ở chế độ "Tất cả", không cho sửa số lượng
    } else if (courseCountInput) {
        courseCountInput.addEventListener("input", (e) => {
            let count = parseInt(e.target.value) || 0;
            if (count > 25) count = 25;
            if (count < 0) count = 0; // Tránh số âm
            
            const currentData = harvestCurrentData();
            // Nếu tăng số lượng, lấy thêm từ dữ liệu mẫu (SAVED_COURSES) nếu có
            if (currentData.length < count && typeof SAVED_COURSES !== 'undefined') {
                const remaining = SAVED_COURSES.slice(currentData.length, count);
                currentData.push(...remaining);
            }

            renderCards(count, currentData);
            
            if (paginationContainer) paginationContainer.style.display = "none";
        });
    }

    function harvestCurrentData() {
        const names = document.getElementsByName("c_name[]");
        const credits = document.getElementsByName("c_credit[]");
        const scores = document.getElementsByName("c_score[]");
        const data = [];
        for (let i = 0; i < names.length; i++) {
            data.push({
                id: null,
                name: names[i].value, 
                credits: credits[i] ? credits[i].value : 0, 
                score: scores[i] ? scores[i].value : 0 
            });
        }
        return data;
    }

    function renderCards(count, data = [], offset = 0) {
        courseContainer.innerHTML = "";
        const isReadOnly = (typeof IS_ALL_MODE !== 'undefined' && IS_ALL_MODE) ? 'readonly' : '';
        
        for (let i = 0; i < count; i++) {
            const course = data[i] || { id: null, name: '', credits: '', score: '' };
            const card = document.createElement("div");
            card.className = "course-card";
            
            const deleteBtnHtml = (!isReadOnly && course.id) 
                ? `<button type="submit" name="delete_course" value="${course.id}" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa môn này khỏi dữ liệu?');">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                   </button>` 
                : '';

            card.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4 style="color:#1d71bb; margin: 0;">Môn ${offset + i + 1}</h4>
                    ${deleteBtnHtml}
                </div>
                <div class="input-box">
                    <label>Tên môn</label>
                    <input type="text" name="c_name[]" value="${escapeHtml(course.name)}" ${isReadOnly} required>
                </div>
                <div class="course-card-details">
                    <div class="input-box"><label>Tín chỉ</label>
                        <input type="number" name="c_credit[]" min="0" value="${course.credits}" ${isReadOnly} required>
                    </div>
                    <div class="input-box"><label>Điểm</label>
                        <input type="number" step="0.1" min="0" max="4.0" name="c_score[]" value="${course.score}" ${isReadOnly} required>
                    </div>
                </div>
            `;
            courseContainer.appendChild(card);
        }
    }

    function escapeHtml(text) {
        if (!text) return "";
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 3. EVENT LISTENERS CHO FILTER & PAGINATION
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new URLSearchParams(new FormData(filterForm)).toString();
            fetchScores(`includes/get-score-ajax.php?${formData}`);
        });
    }

    if (paginationContainer) {
        paginationContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('page-link')) {
                e.preventDefault();
                const page = e.target.dataset.page;
                const formData = new URLSearchParams(new FormData(filterForm));
                formData.set('page', page);
                fetchScores(`includes/get-score-ajax.php?${formData.toString()}`);
            }
        });
    }

    // Validate logic ngay khi gõ
    courseContainer.addEventListener('change', (e) => {
        if (e.target.name === "c_score[]") {
            const val = parseFloat(e.target.value);
            if (val > 4.0) { 
                alert("Điểm tối đa là 4.0"); 
                e.target.value = 4.0; 
            } else if (val < 0) {
                alert("Điểm tối thiểu là 0"); 
                e.target.value = 0; 
            }
        } else if (e.target.name === "c_credit[]") {
            const val = parseInt(e.target.value);
            if (val < 0) {
                alert("Số tín chỉ không được âm");
                e.target.value = 0;
            }
        }
    });
});