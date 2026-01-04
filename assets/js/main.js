// Toggle mobile menu
document.getElementById("mobileMenu").addEventListener("click", function () {
  document.getElementById("navMenu").classList.toggle("show");
});

// Slideshow functionality
let slideIndex = 0;
const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");

function showSlide(n) {
  if (n >= slides.length) slideIndex = 0;
  if (n < 0) slideIndex = slides.length - 1;

  slides.forEach((slide) => slide.classList.remove("active"));
  dots.forEach((dot) => dot.classList.remove("active"));

  slides[slideIndex].classList.add("active");
  dots[slideIndex].classList.add("active");
}

document.getElementById("slideNext").addEventListener("click", () => {
  slideIndex++;
  showSlide(slideIndex);
});

document.getElementById("slidePrev").addEventListener("click", () => {
  slideIndex--;
  showSlide(slideIndex);
});

// Auto advance slides
setInterval(() => {
  slideIndex++;
  showSlide(slideIndex);
}, 5000);

// Cart functionality
let cart = [];
const cartIcon = document.getElementById("cartIcon");
const cartModal = document.getElementById("cartModal");
const cartModalClose = document.getElementById("cartModalClose");
const cartCount = document.getElementById("cartCount");
const cartItems = document.getElementById("cartItems");
const cartSummary = document.getElementById("cartSummary");
const cartInfoSummary = document.getElementById("cartInfoSummary");
const toast = document.getElementById("toast");

// Add to cart buttons
document.querySelectorAll(".add-to-cart").forEach((button) => {
  button.addEventListener("click", function () {
    const id = this.getAttribute("data-id");
    const name = this.getAttribute("data-name");
    const price = parseFloat(this.getAttribute("data-price"));

    // Check if item already in cart
    const existingItem = cart.find((item) => item.id === id);

    if (existingItem) {
      existingItem.qty++;
    } else {
      cart.push({
        id,
        name,
        price,
        qty: 1,
      });
    }

    updateCart();
    showToast(`${name} added to cart!`);
  });
});

// Show cart modal
cartIcon.addEventListener("click", () => {
  cartModal.classList.add("show");
  updateCart();
});

// Close cart modal
cartModalClose.addEventListener("click", () => {
  cartModal.classList.remove("show");
});

// Show toast message
function showToast(message) {
  document.getElementById("toastMessage").textContent = message;
  toast.classList.add("show");

  setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);
}

// Update cart display
function updateCart() {
  cartCount.textContent = cart.reduce((total, item) => total + item.qty, 0);

  // Update cart items table
  cartItems.innerHTML = "";
  let totalItems = 0;
  let totalPrice = 0;

  cart.forEach((item) => {
    const itemTotal = item.price * item.qty;
    totalItems += item.qty;
    totalPrice += itemTotal;

    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${item.name}</td>
      <td>$${item.price.toFixed(2)}</td>
      <td>${item.qty}</td>
      <td>$${itemTotal.toFixed(2)}</td>
      <td>
        <button class="cart-cancel-btn" data-id="${
          item.id
        }" style="background:#ef4444;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;">
          Cancel
        </button>
      </td>
    `;
    cartItems.appendChild(row);
  });

  cartSummary.textContent = `Total: $${totalPrice.toFixed(2)}`;
  cartInfoSummary.textContent = `You have ${totalItems} ${
    totalItems === 1 ? "item" : "items"
  } in your cart`;

  // Update cart data for form submission
  document.getElementById("cartDataInput").value = JSON.stringify(cart);

  // Add cancel event listeners
  document.querySelectorAll(".cart-cancel-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const id = this.getAttribute("data-id");
      cart = cart.filter((item) => item.id !== id);
      updateCart();
      showToast("Item removed from cart!");
    });
  });
}

// Form handling
document.getElementById("cartForm").addEventListener("submit", function (e) {
  // Update hidden fields with visible values
  document.getElementById("checkoutName").value = document.getElementById(
    "checkoutNameVisible"
  ).value;
  document.getElementById("checkoutEmail").value = document.getElementById(
    "checkoutEmailVisible"
  ).value;
  document.getElementById("checkoutAddress").value = document.getElementById(
    "checkoutAddressVisible"
  ).value;
});
