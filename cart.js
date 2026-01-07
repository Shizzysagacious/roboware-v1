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
      item.innerHTML = `
        <img src="${product.image}" alt="${product.name}">
        <h3>${product.name}</h3>
        <p>$${product.price}</p>
        <button class="remove" data-index="${index}">Remove</button>
        <button class="add-one" data-index="${index}">Add One</button>
      `;
      cartItems.appendChild(item);
      total += product.price;
      document.getElementById('total').innerText = total.toFixed(2);

      // Remove product event listener
      item.querySelector('.remove').addEventListener('click', () => {
        removeProduct(index);
      });

      // Add one product event listener
      item.querySelector('.add-one').addEventListener('click', () => {
        addOneProduct(index);
      });
    });
  }

  // Function to add one product to cart
  function addOneProduct(index) {
    const product = cart[index];
    cart.push(product);
    updateCart();
  }

  // Initialize cart event listeners
  document.addEventListener('DOMContentLoaded', () => {
    const checkoutBtn = document.getElementById('checkout_btn');
    checkoutBtn.addEventListener('click', () => {
      // Proceed to checkout functionality
      window.location.href = 'checkout.html'; // Redirect to checkout page
    });

    // Get products from local storage (if any)
    const storedCart = localStorage.getItem('cart');
    if (storedCart) {
      cart = JSON.parse(storedCart);
      updateCart();
    }

    // Display cart items if cart is not empty
    if (cart.length > 0) {
      document.getElementById('cart').style.display = 'block';
    }
  });

  // Store cart in local storage
  window.addEventListener('beforeunload', () => {
    localStorage.setItem('cart', JSON.stringify(cart));
  });
})();
