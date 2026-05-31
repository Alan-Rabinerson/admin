const tabButtons = document.querySelectorAll(".tab-link");
const tabSections = document.querySelectorAll(".tab-content");

tabButtons.forEach((button) => {
  button.addEventListener("click", () => {
    const target = button.dataset.tab;
    tabButtons.forEach((item) => item.classList.remove("active"));
    tabSections.forEach((section) => section.classList.remove("active"));
    button.classList.add("active");
    document.getElementById(target).classList.add("active");
  });
});

const editor = document.getElementById("content_editor");
const contentInput = document.getElementById("content");
const form = document.getElementById("newsForm");

document.querySelectorAll(".editor-toolbar button").forEach((button) => {
  button.addEventListener("click", () => {
    const cmd = button.dataset.cmd;
    if (cmd === "createLink") {
      const url = window.prompt("URL del enlace");
      if (url) {
        document.execCommand(cmd, false, url);
      }
      return;
    }
    document.execCommand(cmd, false, null);
  });
});

function syncContent() {
  contentInput.value = editor.innerHTML;
}

editor.addEventListener("input", syncContent);
form.addEventListener("submit", syncContent);

function wireImages(inputId, buttonId, dropzoneId, galleryId) {
  const input = document.getElementById(inputId);
  const button = document.getElementById(buttonId);
  const dropzone = document.getElementById(dropzoneId);
  const gallery = document.getElementById(galleryId);

  if (!input || !button || !dropzone || !gallery) {
    return;
  }

  button.addEventListener("click", () => input.click());
  dropzone.addEventListener("click", () => input.click());

  input.addEventListener("change", () => {
    Array.from(input.files || []).forEach((file) => {
      const reader = new FileReader();
      reader.onload = (event) => {
        const item = document.createElement("div");
        item.className = "image-item";
        item.innerHTML =
          '<img src="' +
          event.target.result +
          '" alt="Imagen"><button class="image-item-delete" type="button">Borrar</button>';
        item
          .querySelector("button")
          .addEventListener("click", () => item.remove());
        gallery.appendChild(item);
      };
      reader.readAsDataURL(file);
    });
  });
}

wireImages("imageInput", "pickImages", "dropzone", "imageGallery");
wireImages("imageInput2", "pickImages2", "dropzone2", "imageGallery2");
