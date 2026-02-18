const fullname = document.getElementById("fullname");
fullname.textContent = "Nguyễn Huỳnh Núi";

const studentid = document.getElementById("studentid");
studentid.textContent = "B2303872";

function toggleAccordion(element) {
  const card = element.parentElement;
  card.classList.toggle("active");
}

function addEvent(button) {
  const container = button.previousElementSibling;
  const eventCount = container.children.length + 1;

  const eventCard = document.createElement("div");
  eventCard.classList.add("event-card");

 eventCard.innerHTML = `
  <div class="event-title">Sự kiện ${eventCount}</div>

  <div class="event-actions">
    <input type="date">

    <input type="number" class="event-score" placeholder="Điểm" min="0">

    <label class="upload-label">
      📤 Minh chứng
      <input type="file">
    </label>
  </div>
`;

  container.appendChild(eventCard);
}
function updateProgress(score, maxScore) {
  const percent = (score / maxScore) * 100;
  document.getElementById("progressFill").style.width = percent + "%";
}

//Update điểm tạm thời till have logical
updateProgress(1, 5);
