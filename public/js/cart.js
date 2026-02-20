document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Quantity increase buttons
    document.querySelectorAll('.qty-increase').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.dataset.id;
            var input = document.getElementById('qty_' + id);
            var newQty = parseInt(input.value) + 1;
            input.value = newQty;
            updateQuantityOnServer(id, newQty);
        });
    });

    // Quantity decrease buttons
    document.querySelectorAll('.qty-decrease').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.dataset.id;
            var input = document.getElementById('qty_' + id);
            var currentQty = parseInt(input.value);
            if (currentQty > 1) {
                var newQty = currentQty - 1;
                input.value = newQty;
                updateQuantityOnServer(id, newQty);
            }
        });
    });

    // Remove buttons
    document.querySelectorAll('.remove-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.dataset.id;
            removeCartItem(id);
        });
    });

    // Clear cart button
    var clearBtn = document.getElementById('clearCartBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            clearCart();
        });
    }

    function updateQuantityOnServer(itemId, newQuantity) {
        fetch(window.routes.updateCartQuantity, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ id: parseInt(itemId), quantity: newQuantity })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                updateItemTotal(itemId);
                updateSummary();
            } else {
                showToast(data.message || 'حدث خطأ', 'danger');
            }
        })
        .catch(function () {
            showToast('حدث خطأ في الاتصال', 'danger');
        });
    }

    function removeCartItem(itemId) {
        var itemEl = document.getElementById('cartItem' + itemId);
        if (itemEl) {
            itemEl.classList.add('removing');
        }

        fetch(window.routes.removeFromCart, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ id: parseInt(itemId) })
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                setTimeout(function () {
                    if (itemEl) itemEl.remove();
                    updateSummary();
                    updateCartCount(data.cartCount);

                    // Check if cart is empty
                    var remaining = document.querySelectorAll('.cart-item');
                    if (remaining.length === 0) {
                        location.reload();
                    }

                    // Update items count badge
                    var countEl = document.getElementById('countcart');
                    if (countEl) {
                        countEl.textContent = remaining.length + ' منتج';
                    }
                }, 400);
                showToast('تم حذف المنتج بنجاح', 'success');
            } else {
                if (itemEl) itemEl.classList.remove('removing');
                showToast('حدث خطأ أثناء الحذف', 'danger');
            }
        })
        .catch(function () {
            if (itemEl) itemEl.classList.remove('removing');
            showToast('حدث خطأ في الاتصال', 'danger');
        });
    }

    function clearCart() {
        var items = document.querySelectorAll('.cart-item');
        var ids = [];
        items.forEach(function (item) {
            ids.push(item.dataset.itemId);
            item.classList.add('removing');
        });

        // Remove items one by one
        var chain = Promise.resolve();
        ids.forEach(function (id) {
            chain = chain.then(function () {
                return fetch(window.routes.removeFromCart, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ id: parseInt(id) })
                });
            });
        });

        chain.then(function () {
            setTimeout(function () {
                location.reload();
            }, 500);
        });
    }

    function updateItemTotal(itemId) {
        var itemEl = document.getElementById('cartItem' + itemId);
        if (!itemEl) return;
        var price = parseFloat(itemEl.dataset.price);
        var qty = parseInt(document.getElementById('qty_' + itemId).value);
        var totalEl = document.getElementById('itemTotal_' + itemId);
        if (totalEl) {
            totalEl.textContent = (price * qty).toFixed(2);
        }
    }

    function updateSummary() {
        var items = document.querySelectorAll('.cart-item');
        var subtotal = 0;
        items.forEach(function (item) {
            var price = parseFloat(item.dataset.price);
            var qty = parseInt(item.querySelector('.qty-input').value);
            subtotal += price * qty;
        });

        var shipping = 25.00;
        var discount = 0.00;
        var total = subtotal + shipping - discount;

        var subtotalEl = document.getElementById('subtotal');
        var totalEl = document.getElementById('total');

        if (subtotalEl) subtotalEl.textContent = subtotal.toFixed(2) + ' د.م';
        if (totalEl) totalEl.textContent = total.toFixed(2) + ' د.م';
    }

    function updateCartCount(count) {
        var badge = document.getElementById('cartCount');
        if (badge) badge.textContent = count;
    }

    function showToast(message, type) {
        // Remove existing toast
        var existing = document.querySelector('.cart-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.className = 'cart-toast ' + type;
        toast.textContent = message;
        document.querySelector('.layout-cart').appendChild(toast);

        setTimeout(function () { toast.classList.add('show'); }, 10);
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 400);
        }, 2500);
    }
});
