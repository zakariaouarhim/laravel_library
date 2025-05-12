document.addEventListener("DOMContentLoaded", function () {
        const creditCardRadio = document.getElementById("creditCard");
        const cashOnDeliveryRadio = document.getElementById("cashOnDelivery");
        const creditCardInfo = document.getElementById("creditCardInfo");

        creditCardRadio.addEventListener("change", function () {
            if (this.checked) {
                creditCardInfo.style.display = "block";
            }
           
        });

        cashOnDeliveryRadio.addEventListener("change", function () {
            if (this.checked) {
                creditCardInfo.style.display = "none";
                
            }
        });
    });
    $('input[name="payment_method"]').change(function() {
    $('.payment-method-card').removeClass('selected');
    $(this).closest('.payment-method-card').addClass('selected');
    
    if ($(this).val() === 'credit_card') {
        $('#creditCardInfo').slideDown(300);
    } else {
        $('#creditCardInfo').slideUp(300);
    }
});

/*function removeFromCart2(itemId) {
    fetch('/remove-from-cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ id: itemId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // ✅ Update cart count
            document.getElementById('cartCount').textContent = data.cartCount;

            // ✅ Remove the cart item div from the DOM
            const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
            if (itemElement) {
                itemElement.remove();  
            }

            // Optional: show a toast or message
            showCartToast('تم حذف المنتج من السلة');
        } else {
            alert('حدث خطأ أثناء حذف المنتج');
        }
    })
    .catch(error => console.error('Error:', error));
}


function removeFromCart2(itemId) {
    if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) return;
    fetch('/remove-from-cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ id: itemId })
    })
    fetch(`/remove-from-cart/${itemId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the item from the DOM
            document.getElementById(`element${itemId}`).remove();

            // Update cart count in the header
            document.getElementById('cartCount').textContent = data.cartCount;

            // Show success toast
            showCartToast(data.message);

            // If cart is empty, show empty state
            if (data.cartCount === 0) {
                const cartContent = document.getElementById('cartContent');
                cartContent.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">سلة التسوق فارغة</p>
                        <a href="{{ route('index.page') }}" class="btn btn-primary">تصفح الكتب</a>
                    </div>
                `;
            }

            // Update totals
            updateTotals();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCartToast('حدث خطأ أثناء حذف المنتج', 'error');
    });
}*/

function removeFromCart2(itemId) {
    if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) return;
    fetch('/remove-from-cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ id: itemId })
    })
    fetch(`/remove-from-cart/${itemId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the item from the DOM
            const itemElement = document.getElementById(`element${itemId}`);
            if (itemElement) itemElement.remove();

            // Update cart count in the header
            const cartCountElement = document.getElementById('cartCount');
            if (cartCountElement) cartCountElement.textContent = data.cartCount;

            let countCartSpan = document.getElementById('countcart');
            if (countCartSpan) {
                let text = countCartSpan.textContent.trim(); // e.g., "3 منتجات"
                let count = parseInt(text); // Get the number
                if (!isNaN(count) && count > 0) {
                    countCartSpan.textContent = `${count - 1} منتجات`;
                }
            }

            // Show success toast
            showCartToast(data.message);

            // If cart is empty, show empty state
            const cartContent = document.getElementById('cartContent');
            if (data.cartCount === 0 && cartContent) {
                cartContent.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">سلة التسوق فارغة</p>
                        <a href="{{ route('index.page') }}" class="btn btn-primary">تصفح الكتب</a>
                    </div>
                `;
            }

            // Update totals
            updateTotals();
        } else {
            showCartToast('حدث خطأ أثناء حذف المنتج. يرجى المحاولة لاحقًا.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCartToast('ayyehحدث خطأ أثناء حذف المنتج. يرجى المحاولة لاحقًا.', 'error');
    });
}

function showCartToast(message, type = 'success') {
    const toastElement = document.getElementById('cartToast');
    const toastBody = toastElement.querySelector('.toast-body');
    
    // Set toast content
    toastBody.textContent = message;
    
    // Reset classes
    toastElement.classList.remove('bg-success', 'bg-danger');
    toastBody.classList.remove('text-success', 'text-danger');
    
    // Add appropriate classes based on type
    toastElement.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
    toastBody.classList.add(type === 'success' ? 'text-success' : 'text-danger');
    
    // Create and show the toast with options
    const toast = new bootstrap.Toast(toastElement, {
        animation: true,
        autohide: true,
        delay: 3000
    });
    
    toast.show();
}
function updateTotals() {
    // Get all cart items
    const cartItems = document.querySelectorAll('[data-item-id]');
    
    // Initialize values
    let subtotal = 0;
    const shipping = 25.00; // Fixed shipping cost
    let discount = 0;
    
    // Calculate subtotal based on all items in cart
    cartItems.forEach(item => {
        const itemId = item.getAttribute('data-item-id');
        const quantityInput = item.querySelector('.quantity-input');
        const quantity = parseInt(quantityInput.value);
        const price = parseFloat(quantityInput.getAttribute('data-price'));
        
        if (!isNaN(quantity) && !isNaN(price)) {
            subtotal += price * quantity;
        }
    });
    
    // Get discount value (in case it was set by a coupon)
    const discountElement = document.getElementById('discount');
    if (discountElement) {
        // Parse the discount value, removing the currency symbol and minus sign
        const discountText = discountElement.textContent.trim();
        const discountMatch = discountText.match(/-?([\d.,]+)/);
        if (discountMatch) {
            discount = parseFloat(discountMatch[1].replace(',', ''));
        }
    }
    
    // Calculate total
    const total = subtotal + shipping - discount;
    
    // Update DOM elements
    document.getElementById('subtotal').textContent = `${subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')} ر.س`;
    document.getElementById('shipping').textContent = `${shipping.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')} ر.س`;
    document.getElementById('discount').textContent = `-${discount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')} ر.س`;
    document.getElementById('total').textContent = `${total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')} ر.س`;
    
    // Disable checkout button if cart is empty
    const completeOrderBtn = document.getElementById('completeOrder');
    if (completeOrderBtn) {
        completeOrderBtn.disabled = cartItems.length === 0;
    }
}