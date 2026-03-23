document.addEventListener("DOMContentLoaded", () => {
    const courseCountInput = document.getElementById("courseCount");
    const courseContainer = document.getElementById("courseContainer");

    if (typeof IS_ALL_MODE !== 'undefined' && IS_ALL_MODE) return;

    if (courseCountInput) {
        courseCountInput.addEventListener("input", (e) => {
            let count = parseInt(e.target.value) || 0;
            if (count > 25) count = 25;
            
            const currentData = harvestCurrentData();
            const mergedData = [...currentData];
            if (mergedData.length < count) {
                const remaining = SAVED_COURSES.slice(mergedData.length, count);
                mergedData.push(...remaining);
            }

            renderCards(count, mergedData);
            const pagin = document.querySelector(".pagination");
            if (pagin) pagin.style.display = "none";
        });
    }

    function harvestCurrentData() {
        const names = document.getElementsByName("c_name[]");
        const credits = document.getElementsByName("c_credit[]");
        const scores = document.getElementsByName("c_score[]");
        const data = [];
        for (let i = 0; i < names.length; i++) {
            data.push({ name: names[i].value, credits: credits[i].value, score: scores[i].value });
        }
        return data;
    }

    function renderCards(count, data = []) {
        courseContainer.innerHTML = "";
        for (let i = 0; i < count; i++) {
            const course = data[i] || { name: '', credits: '', score: '' };
            const card = document.createElement("div");
            card.className = "course-card";
            card.innerHTML = `
                <h4>Môn ${i + 1}</h4>
                <div class="input-box">
                    <label>Tên môn học</label>
                    <input type="text" name="c_name[]" value="${escapeHtml(course.name)}" placeholder="VD: Lập trình..." required />
                </div>
                <div class="course-card-details">
                  <div class="input-box">
                    <label>Số tín chỉ</label>
                    <input type="number" name="c_credit[]" value="${course.credits}" min="1" required />
                  </div>
                  <div class="input-box">
                    <label>Điểm (Thang 4)</label>
                    <input type="number" name="c_score[]" value="${course.score}" step="0.1" min="0" max="4.0" required />
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

    courseContainer.addEventListener('change', (e) => {
        if (e.target.name === "c_score[]") {
            const val = parseFloat(e.target.value);
            if (val > 4.0) { alert("Điểm tối đa là 4.0"); e.target.value = 4.0; }
        }
    });
});