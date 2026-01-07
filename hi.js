(function() {
// Initialize cart array
let cart = [];

// Function to add product to cart
function addToCart(product) {
cart.push(product);
updateCart();
}

// Function to remove product from cart
function removeProduct(index) {
cart.splice(index, 1);
updateCart();
}

// Function to update cart display
function updateCart() {
const cartItems = document.getElementById('cart-items');
cartItems.innerHTML = '';
let total = 0;
cart.forEach((product, index) => {
const item = document.createElement('div');
item.innerHTML = `<h3>${product.name}</h3> <p>$${product.price}</p> <button class="remove" data-index="${index}">Remove</button>`;
cartItems.appendChild(item);
total += product.price;

// Remove product event listener
  item.querySelector('.remove').addEventListener('click', () => {
    removeProduct(index);
  });
});
document.getElementById('cart-total').innerHTML = `<h3>Total: $${total.toFixed(2)}</h3>`;
}

// Add event listeners to add to cart buttons
const addToCartButtons = document.querySelectorAll('.add-to-cart');
addToCartButtons.forEach(button => {
button.addEventListener('click', () => {
const product = button.parentNode;
const productName = product.querySelector('h3').textContent;
const productPrice = product.querySelector('p').textContent;
const productImage = product.querySelector('img').src;
const productObject = {
name: productName,
price: parseFloat(productPrice.replace('$', '')),
image: productImage
};
addToCart(productObject);
updateCartDisplay(); // Update cart display
localStorage.setItem('cart', JSON.stringify(cart)); // Store cart in local storage
});
});

// Function to update cart display
function updateCartDisplay() {
const cartItems = document.getElementById('cart-items');
const cartTotal = document.getElementById('cart-total');
if (cartItems.children.length > 0) {
document.getElementById('cart').style.display = 'block';
document.getElementById('checkout-btn').style.display = 'block';
} else {
document.getElementById('cart').style.display = 'none';
document.getElementById('checkout-btn').style.display = 'none';
}
}

// Initialize cart event listeners
document.addEventListener('DOMContentLoaded', () => {
const storedCart = localStorage.getItem('cart');
if (storedCart) {
cart = JSON.parse(storedCart);
updateCart();
}

```const checkoutBtn = document.getElementById('checkout-btn');
checkoutBtn.addEventListener('click', () => {
  // Proceed to checkout functionality
  window.location.href = 'cart.html'; // Redirect to cart page
});

// Display cart items if cart is not empty
if (cart.length > 0) {
  document.getElementById('cart').style.display = 'block';
  document.getElementById('checkout-btn').style.display = 'block';
}
```
});

// Store cart in local storage
window.addEventListener('beforeunload', () => {
localStorage.setItem('cart', JSON.stringify(cart));
});
})();

