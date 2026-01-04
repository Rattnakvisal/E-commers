function editProduct(id) {
  fetch("ajax_handlers/product_handler.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `action=get&id=${id}`,
  })
    .then((response) => response.json())
    .then((data) => {
      document.getElementById("edit_product_id").value = data.id;
      document.getElementById("edit_name").value = data.name;
      document.getElementById("edit_category_id").value = data.category_id;
      document.getElementById("edit_description").value = data.description;
      document.getElementById("edit_price").value = data.price;
      document.getElementById("edit_stock").value = data.stock;
      document.getElementById("edit_status").value = data.status;

      document.getElementById("editProductModal").style.display = "block";
    });
}

function deleteProduct(id) {
  if (confirm("Are you sure you want to delete this product?")) {
    fetch("ajax_handlers/product_handler.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=delete&id=${id}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          location.reload();
        } else {
          alert("Error deleting product: " + data.error);
        }
      });
  }
}

document
  .getElementById("editProductForm")
  .addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append("action", "update");

    fetch("ajax_handlers/product_handler.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          location.reload();
        } else {
          alert("Error updating product: " + data.error);
        }
      });
  });

function closeEditModal() {
  document.getElementById("editProductModal").style.display = "none";
}

// Close modal when clicking outside
window.onclick = function (event) {
  if (event.target == document.getElementById("editProductModal")) {
    closeEditModal();
  }
};
