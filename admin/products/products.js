const modal = document.getElementById("productFormModal");
const form = document.getElementById("productForm");
const modalTitle = document.getElementById("modalTitle");

function openForm(productId = null) {
  form.reset();
  document.getElementById("productId").value = "";
  modalTitle.textContent = "Add New Product";
  document.getElementById("currentImagePreview").innerHTML = "";

  if (productId) {
    // Prefill form with product data
    const row = document.querySelector(`tr[data-id="${productId}"]`);
    if (!row) return;

    document.getElementById("productId").value = productId;
    document.getElementById("name").value = row.dataset.name;
    document.getElementById("category_id").value = row.dataset.category_id;
    document.getElementById("description").value = row.dataset.description;
    document.getElementById("price").value = row.dataset.price;
    document.getElementById("sale_price").value = row.dataset.sale_price;
    document.getElementById("stock").value = row.dataset.stock;
    document.getElementById("status").value = row.dataset.status;

    if (row.dataset.image_url) {
      document.getElementById(
        "currentImagePreview"
      ).innerHTML = `<img src="${row.dataset.image_url}" alt="Current Image" />`;
    }

    modalTitle.textContent = "Edit Product";
  }
  modal.style.display = "block";
}

function closeForm() {
  modal.style.display = "none";
}

// Close modal when clicking outside content
window.onclick = function (event) {
  if (event.target === modal) {
    closeForm();
  }
};
