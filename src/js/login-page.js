// This one is for switching "dang nhap" and "dang ky"
const tabs = document.querySelectorAll(".option-item");
const forms = document.querySelectorAll(
  "#login-form-container, #signup-form-container",
);

tabs.forEach((tab, index) => {
  tab.addEventListener("click", function () {
    // A. Xử lý phần Tab (nổi bật nút được chọn)
    tabs.forEach((t) => t.classList.remove("is-active"));
    this.classList.add("is-active");

    // B. Xử lý phần Form (ẩn hết, hiện cái tương ứng)
    forms.forEach((frm) => frm.classList.add("is-disabled"));
    forms[index].classList.remove("is-disabled");

    // Giải thích: Nếu bấm Tab số 0 (Đăng nhập) -> Hiện Form số 0
  });
});
