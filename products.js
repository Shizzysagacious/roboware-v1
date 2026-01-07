document.addEventListener('DOMContentLoaded', function() {
    const productId = new URLSearchParams(window.location.search).get('id');
    fetch(`products.php?id=${productId}`)
        .then(response => response.json())
        .then(product => {
            const productDetails = document.getElementById('product-details');
            productDetails.innerHTML = `
                <h2>${product.name}</h2>
                <p>Price: $${product.price}</p>
                <p>Tools Used: ${product.toolsUsed}</p>
            `;
        })
        .catch(error => console.error(error));
});