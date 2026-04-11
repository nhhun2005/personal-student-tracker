//công tắc đổi tab giữa đăng nhập và đăng ký
const tabs = document.querySelectorAll(".option-item");
const forms = document.querySelectorAll(
  "#login-form-container, #signup-form-container",
);

tabs.forEach((tab, index) => {
  tab.addEventListener("click", function () {
    //bat tat class cho nut duoc chon
    tabs.forEach((t) => t.classList.remove("is-active"));
    this.classList.add("is-active");

    //bat tat class cho nut duoc chon
    forms.forEach((frm) => frm.classList.add("is-disabled"));
    forms[index].classList.remove("is-disabled");

  });
});
