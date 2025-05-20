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
        // Handle plus button
    document.querySelectorAll('.quantity-increase').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = this.parentElement.querySelector('.quantity-input');
            let currentValue = parseInt(input.value);
            if (!isNaN(currentValue)) {
                input.value = currentValue + 1;
            }
        });
    });

    // Handle minus button
    document.querySelectorAll('.quantity-decrease').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = this.parentElement.querySelector('.quantity-input');
            let currentValue = parseInt(input.value);
            if (!isNaN(currentValue) && currentValue > 1) {
                input.value = currentValue - 1;
            }
        });
    });
    // Connect edit buttons to enableQuantityInput function
    document.querySelectorAll('.edit-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const itemElement = this.closest('[data-item-id]');
            const itemId = itemElement ? itemElement.getAttribute('data-item-id') : null;
            if (itemId) {
                enableQuantityInput(itemId);
            }
        });
    });
    
    // Update quantity increase/decrease buttons to update totals
    document.querySelectorAll('.quantity-increase, .quantity-decrease').forEach(function (button) {
        button.addEventListener('click', function () {
            const itemElement = this.closest('[data-item-id]');
            const itemId = itemElement ? itemElement.getAttribute('data-item-id') : null;
            const quantityInput = this.parentElement.querySelector('.quantity-input');
            const quantity = parseInt(quantityInput.value);
            
            if (itemId && !isNaN(quantity)) {
                // Update item price display immediately for better UX
                const itemPrice = parseFloat(quantityInput.getAttribute('data-price'));
                const itemTotal = itemPrice * quantity;
                const priceElement = itemElement.querySelector('.fw-bold.text-primary');
                
                if (priceElement) {
                    priceElement.textContent = `${itemTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')} ر.س`;
                }
                
                // Update overall totals
                updateTotals();
                
               
                
                
            }
        });
    });
    
    // Also allow updating when quantity input loses focus
    document.querySelectorAll('.quantity-input').forEach(function (input) {
        input.addEventListener('blur', function () {
            if (this.hasAttribute('data-editing')) {
                const itemElement = this.closest('[data-item-id]');
                const itemId = itemElement ? itemElement.getAttribute('data-item-id') : null;
                
                if (itemId) {
                    const editBtn = itemElement.querySelector('.edit-btn');
                    if (editBtn) {
                        editBtn.click(); // Simulate clicking the edit/save button
                    }
                }
            }
        });
        
        // Allow Enter key to submit changes
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && this.hasAttribute('data-editing')) {
                event.preventDefault();
                const itemElement = this.closest('[data-item-id]');
                const itemId = itemElement ? itemElement.getAttribute('data-item-id') : null;
                
                if (itemId) {
                    const editBtn = itemElement.querySelector('.edit-btn');
                    if (editBtn) {
                        editBtn.click(); // Simulate clicking the edit/save button
                    }
                }
            }
        });
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


function removeFromCart2(itemId) {
    
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

/////////////

// Function to handle the edit button functionality
function enableQuantityInput(itemId) {
    // Find the item container
    const itemElement = document.getElementById(`element${itemId}`) || 
                        document.querySelector(`[data-item-id="${itemId}"]`);
    
    if (!itemElement) return;
    
    // Get the quantity input for this item
    const quantityInput = itemElement.querySelector('.quantity-input');
    const editBtn = itemElement.querySelector('.edit-btn');
    
    // Toggle editing state
    if (!quantityInput.hasAttribute('data-editing')) {
        // Enable editing
        quantityInput.setAttribute('data-editing', 'true');
        quantityInput.readOnly = false;
        quantityInput.focus();
        quantityInput.select();
        editBtn.innerHTML = '<i class="fas fa-save"></i>';
        
        // Store original value to restore if needed
        quantityInput.setAttribute('data-original-value', quantityInput.value);
    } else {
        // Save changes
        const newQuantity = parseInt(quantityInput.value);
        
        // Validate input (ensure it's a number greater than 0)
        if (isNaN(newQuantity) || newQuantity <= 0) {
            // If invalid, restore original value
            quantityInput.value = quantityInput.getAttribute('data-original-value');
            showCartToast('الرجاء إدخال كمية صحيحة', 'error');
        } else {
            // Update cart on server
            updateCartItemQuantity(itemId, newQuantity);
        }
        
        // Disable editing
        quantityInput.removeAttribute('data-editing');
        quantityInput.readOnly = true;
        editBtn.innerHTML = '<i class="bi bi-pencil"></i>';
    }
}

// Function to update cart item quantity on server and UI
function updateCartItemQuantity(itemId, quantity) {
    // Show loading indicator
    const loadingSpinner = document.createElement('span');
    loadingSpinner.className = 'spinner-border spinner-border-sm ms-2';
    loadingSpinner.setAttribute('role', 'status');
    
    const itemElement = document.getElementById(`element${itemId}`)  || 
                        document.querySelector('[data-item-id="${itemId}"]');
    const priceElement = itemElement.querySelector('.fw-bold.text-primary');
    
    // Add loading spinner
    priceElement.appendChild(loadingSpinner);
    
    // Send update to server
    fetch(window.routes.updateCartQuantity, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ 
            id: itemId,
            quantity: quantity 
        })
    })
    .then(response => response.json())
    .then(data => {
        // Remove loading spinner
        if (loadingSpinner.parentNode) {
            loadingSpinner.parentNode.removeChild(loadingSpinner);
        }
        
        if (data.success) {
            // Update price display for this item
            const quantityInput = itemElement.querySelector('.quantity-input');
            const itemPrice = parseFloat(quantityInput.getAttribute('data-price'));
            const itemTotal = itemPrice * quantity;
            
            // Update the price display for this specific item
            priceElement.textContent = `${itemTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')} ر.س`;
            
            // Update overall totals
            updateTotals();
            // Update cart modal content
            
            // Show success message
            showCartToast('تم تحديث الكمية بنجاح');
        } else {
            // Show error message
            showCartToast(data.message || 'حدث خطأ أثناء تحديث الكميةyyyyy', 'error');
        }
    })
    .catch(error => {
        // Remove loading spinner
        if (loadingSpinner.parentNode) {
            loadingSpinner.parentNode.removeChild(loadingSpinner);
        }
        
        console.error('Error:', error);
        showCartToast('حدث خطأ أثناء تحديث الكميةzaaa', 'error');
    });
}

