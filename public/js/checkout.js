document.addEventListener("DOMContentLoaded", function () {
    // Function to sync visible quantity input with hidden input
    function syncQuantityInputs(itemElement) {
        const visibleInput = itemElement.querySelector('.quantity-input');
        const hiddenInput = itemElement.querySelector('.hidden-quantity');
        if (visibleInput && hiddenInput) {
            hiddenInput.value = visibleInput.value;
        }
    }

    // Function to update quantity on server
    function updateQuantityOnServer(itemId, newQuantity, callback) {
        fetch('/cart/update-quantity', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                id: itemId,
                quantity: newQuantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (callback) callback(true, data);
            } else {
                if (callback) callback(false, data);
                showCartToast(data.message || 'حدث خطأ في تحديث الكمية', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (callback) callback(false, null);
            showCartToast('حدث خطأ في الاتصال بالخادم', 'error');
        });
    }

    // Handle plus button
    document.querySelectorAll('.quantity-increase').forEach(function (button) {
        button.addEventListener('click', function () {
            const itemElement = this.closest('[data-item-id]');
            const itemId = itemElement.getAttribute('data-item-id');
            const input = this.parentElement.querySelector('.quantity-input');
            let currentValue = parseInt(input.value);
            
            if (!isNaN(currentValue)) {
                const newQuantity = currentValue + 1;
                
                // Update server first
                updateQuantityOnServer(itemId, newQuantity, function(success, data) {
                    if (success) {
                        // Update UI only if server update was successful
                        input.value = newQuantity;
                        syncQuantityInputs(itemElement);
                        updateItemPriceDisplay(itemElement);
                        updateTotals();
                        showCartToast('تم تحديث الكمية بنجاح');
                    } else {
                        // Revert to original value if server update failed
                        input.value = currentValue;
                    }
                });
            }
        });
    });

    // Handle minus button
    document.querySelectorAll('.quantity-decrease').forEach(function (button) {
        button.addEventListener('click', function () {
            const itemElement = this.closest('[data-item-id]');
            const itemId = itemElement.getAttribute('data-item-id');
            const input = this.parentElement.querySelector('.quantity-input');
            let currentValue = parseInt(input.value);
            
            if (!isNaN(currentValue) && currentValue > 1) {
                const newQuantity = currentValue - 1;
                
                // Update server first
                updateQuantityOnServer(itemId, newQuantity, function(success, data) {
                    if (success) {
                        // Update UI only if server update was successful
                        input.value = newQuantity;
                        syncQuantityInputs(itemElement);
                        updateItemPriceDisplay(itemElement);
                        updateTotals();
                        showCartToast('تم تحديث الكمية بنجاح');
                    } else {
                        // Revert to original value if server update failed
                        input.value = currentValue;
                    }
                });
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

    // Allow updating when quantity input loses focus
    document.querySelectorAll('.quantity-input').forEach(function (input) {
        input.addEventListener('blur', function () {
            if (this.hasAttribute('data-editing')) {
                const itemElement = this.closest('[data-item-id]');
                const itemId = itemElement ? itemElement.getAttribute('data-item-id') : null;
                
                if (itemId) {
                    const editBtn = itemElement.querySelector('.edit-btn');
                    if (editBtn) {
                        editBtn.click();
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
                        editBtn.click();
                    }
                }
            }
        });
    });
});

// Function to update individual item price display
function updateItemPriceDisplay(itemElement) {
    const quantityInput = itemElement.querySelector('.quantity-input');
    const quantity = parseInt(quantityInput.value);
    const itemPrice = parseFloat(quantityInput.getAttribute('data-price'));
    const itemTotal = itemPrice * quantity;
    const priceElement = itemElement.querySelector('.fw-bold.text-primary');
    
    if (priceElement) {
        priceElement.textContent = `${itemTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')} ر.س`;
    }
}

// Payment method selection with jQuery
$('input[name="payment_method"]').change(function() {
    $('.payment-method-card').removeClass('selected');
    $(this).closest('.payment-method-card').addClass('selected');
});

function removeFromCart2(itemId) {
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
                let text = countCartSpan.textContent.trim();
                let count = parseInt(text);
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
                        <a href="/browse" class="btn btn-primary">تصفح الكتب</a>
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
        showCartToast('حدث خطأ أثناء حذف المنتج. يرجى المحاولة لاحقًا.', 'error');
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
        const originalQuantity = parseInt(quantityInput.getAttribute('data-original-value'));
        
        // Validate input (ensure it's a number greater than 0)
        if (isNaN(newQuantity) || newQuantity <= 0) {
            // If invalid, restore original value
            quantityInput.value = originalQuantity;
            showCartToast('الرجاء إدخال كمية صحيحة', 'error');
            
            // Disable editing
            quantityInput.removeAttribute('data-editing');
            quantityInput.readOnly = true;
            editBtn.innerHTML = '<i class="bi bi-pencil"></i>';
        } else if (newQuantity !== originalQuantity) {
            // Only update if quantity actually changed
            
            // Show loading state
            editBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            editBtn.disabled = true;
            
            // Update server
            updateQuantityOnServer(itemId, newQuantity, function(success, data) {
                if (success) {
                    // Update the hidden input and display
                    const hiddenInput = itemElement.querySelector('.hidden-quantity');
                    if (hiddenInput) {
                        hiddenInput.value = newQuantity;
                    }
                    
                    // Update item price display
                    updateItemPriceDisplay(itemElement);
                    
                    // Update overall totals
                    updateTotals();
                    
                    // Show success message
                    showCartToast('تم تحديث الكمية بنجاح');
                } else {
                    // If server update failed, restore original value
                    quantityInput.value = originalQuantity;
                    showCartToast('حدث خطأ في تحديث الكمية', 'error');
                }
                
                // Disable editing and restore button
                quantityInput.removeAttribute('data-editing');
                quantityInput.readOnly = true;
                editBtn.innerHTML = '<i class="bi bi-pencil"></i>';
                editBtn.disabled = false;
            });
        } else {
            // No change, just disable editing
            quantityInput.removeAttribute('data-editing');
            quantityInput.readOnly = true;
            editBtn.innerHTML = '<i class="bi bi-pencil"></i>';
        }
    }
}

// ============================================================
// Coupon / discount code handler
// ============================================================
(function () {
    function getSubtotal() {
        let subtotal = 0;
        document.querySelectorAll('[data-item-id]').forEach(function (item) {
            const q = parseInt(item.querySelector('.quantity-input').value);
            const p = parseFloat(item.querySelector('.quantity-input').getAttribute('data-price'));
            if (!isNaN(q) && !isNaN(p)) subtotal += q * p;
        });
        return subtotal;
    }

    function applyCoupon() {
        const code  = document.getElementById('couponCode').value.trim();
        const msgEl = document.getElementById('couponMessage');
        const btn   = document.getElementById('applyCoupon');

        if (!code) {
            msgEl.innerHTML = '<span class="text-danger">يرجى إدخال كود الخصم</span>';
            return;
        }

        btn.disabled    = true;
        btn.textContent = '...';

        fetch('/checkout/apply-coupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ code: code, subtotal: getSubtotal() })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                const discountEl = document.getElementById('discount');
                if (discountEl) {
                    discountEl.setAttribute('data-coupon-discount', data.discount);
                }
                updateTotals();
                msgEl.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>' + data.message + '</span>';
                btn.textContent = 'إزالة';
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-outline-danger');
                btn.removeEventListener('click', applyCoupon);
                btn.addEventListener('click', removeCoupon);
            } else {
                msgEl.innerHTML = '<span class="text-danger">' + data.message + '</span>';
                btn.textContent = 'تطبيق';
            }
            btn.disabled = false;
        })
        .catch(function () {
            msgEl.innerHTML = '<span class="text-danger">حدث خطأ، يرجى المحاولة لاحقاً</span>';
            btn.textContent = 'تطبيق';
            btn.disabled    = false;
        });
    }

    function removeCoupon() {
        const msgEl = document.getElementById('couponMessage');
        const btn   = document.getElementById('applyCoupon');

        fetch('/checkout/remove-coupon', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(function () {
            const discountEl = document.getElementById('discount');
            if (discountEl) discountEl.removeAttribute('data-coupon-discount');
            updateTotals();
            msgEl.innerHTML = '';
            document.getElementById('couponCode').value = '';
            btn.textContent = 'تطبيق';
            btn.classList.remove('btn-outline-danger');
            btn.classList.add('btn-outline-primary');
            btn.removeEventListener('click', removeCoupon);
            btn.addEventListener('click', applyCoupon);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('applyCoupon');
        if (btn) btn.addEventListener('click', applyCoupon);
    });

    // Patch updateTotals to respect the stored coupon discount
    const _origUpdateTotals = window.updateTotals;
    window.updateTotals = function () {
        if (typeof _origUpdateTotals === 'function') _origUpdateTotals();
        const discountEl = document.getElementById('discount');
        if (!discountEl || !discountEl.hasAttribute('data-coupon-discount')) return;
        const couponDiscount = parseFloat(discountEl.getAttribute('data-coupon-discount')) || 0;
        discountEl.textContent = '-' + couponDiscount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ' ر.س';

        // Recalculate total with coupon
        let subtotal = 0;
        document.querySelectorAll('[data-item-id]').forEach(function (item) {
            const q = parseInt(item.querySelector('.quantity-input').value);
            const p = parseFloat(item.querySelector('.quantity-input').getAttribute('data-price'));
            if (!isNaN(q) && !isNaN(p)) subtotal += q * p;
        });
        const total = subtotal + 25.00 - couponDiscount;
        document.getElementById('total').textContent = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ' ر.س';
    };
})();

// Form submission handler to ensure all data is synced
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            // Sync all quantity inputs before submission
            document.querySelectorAll('[data-item-id]').forEach(function(itemElement) {
                const visibleInput = itemElement.querySelector('.quantity-input');
                const hiddenInput = itemElement.querySelector('.hidden-quantity');
                if (visibleInput && hiddenInput) {
                    hiddenInput.value = visibleInput.value;
                }
            });
            
            // Show loading state
            const submitBtn = this.querySelector('#completeOrder');
            const submitText = submitBtn.querySelector('.submit-text');
            const spinner = submitBtn.querySelector('.spinner-border');
            
            if (submitText && spinner) {
                submitText.classList.add('d-none');
                spinner.classList.remove('d-none');
                submitBtn.disabled = true;
            }
        });
    }
});