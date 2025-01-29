function showCartModal() {
     console.log("Cart modal opened"); // Debugging
    // Get the cart modal body element
    let modalBody = document.querySelector('#cartDetailsModal .modal-body');

    // Clear any previous cart items in the modal
    modalBody.innerHTML = '';

    if (cartItems.length === 0) {
        modalBody.innerHTML = '<p>سلّة التسوق فارغة</p>';
    } else {
        // Loop through cartItems array and display each item
        cartItems.forEach(item => {
            let itemHTML = `
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <img src="${item.image}" alt="${item.title}" class="img-fluid" style="width: 50px;">
                    <div>
                        <p class="mb-0">${item.title}</p>
                        <span>${item.price} ر.س</span>
                    </div>
                </div>
            `;
            modalBody.innerHTML += itemHTML;
        });
    }

    // Show the modal
    let cartModal = new bootstrap.Modal(document.getElementById('cartDetailsModal'));
    cartModal.show();
}
