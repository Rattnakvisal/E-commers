function openAddForm() {
  const modal = document.getElementById("addCategoryForm");
  modal.style.display = "block";
  modal.querySelector("h2").textContent = "Add New Category";
  modal.querySelector(".btn-submit").textContent = "Add Category";
  modal.querySelector('input[name="action"]').value = "add";
  modal.querySelector('input[name="id"]').value = "";
  modal.querySelector('input[name="name"]').value = "";
  modal.querySelector('textarea[name="description"]').value = "";
}

function editCategory(id) {
  const card = document.querySelector(`[data-id='${id}']`);
  const name = card.getAttribute("data-name");
  const description = card.getAttribute("data-description");

  const modal = document.getElementById("addCategoryForm");
  modal.style.display = "block";
  modal.querySelector("h2").textContent = "Edit Category";
  modal.querySelector(".btn-submit").textContent = "Update Category";
  modal.querySelector('input[name="action"]').value = "edit";
  modal.querySelector('input[name="id"]').value = id;
  modal.querySelector('input[name="name"]').value = name;
  modal.querySelector('textarea[name="description"]').value = description;
}

function closeForm() {
  document.getElementById("addCategoryForm").style.display = "none";
}

function deleteCategory(id) {
  if (confirm("Are you sure you want to delete this category?")) {
    window.location.href = `?action=delete&id=${id}`;
  }
}

// Close modal on background click
window.onclick = function (event) {
  const modal = document.getElementById("addCategoryForm");
  if (event.target === modal) {
    modal.style.display = "none";
  }
};
