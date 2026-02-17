<!-- Empty Cart Modal -->
<div class="modal fade" id="emptyCartModal" tabindex="-1" aria-labelledby="emptyCartLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content cart-modal-content text-center">
            <div class="cart-modal-empty-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <h5 class="cart-modal-empty-title">سلّة التسوق فارغة</h5>
            <p class="cart-modal-empty-text">لم تقم بإضافة أي منتجات بعد</p>
            <button type="button" class="cart-modal-btn-close" data-bs-dismiss="modal">إغلاق</button>
        </div>
    </div>
</div>

<!-- Cart Details Modal -->
<div class="modal fade" id="cartDetailsModal" tabindex="-1" aria-labelledby="cartDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content cart-modal-content">
            <div class="cart-modal-header">
                <div class="cart-modal-header-title">
                    <i class="fas fa-shopping-bag"></i>
                    <h5>سلّة التسوق</h5>
                </div>
                <button type="button" class="cart-modal-close" data-bs-dismiss="modal" aria-label="إغلاق">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body cart-modal-body">
                <form id="checkoutForm" action="{{ route('checkout.store-cart') }}" method="POST">
                    @csrf
                    <div id="cartItemsContainer">
                        <!-- Cart items will be inserted dynamically -->
                    </div>
                    <input type="hidden" name="cart_data" id="cartDataInput">
                </form>
            </div>
            <div class="cart-modal-footer">
                <button type="button" class="cart-modal-btn-checkout" id="checkoutButton" onclick="submitCheckoutForm()">
                    <i class="fas fa-credit-card"></i>
                    إتمام الشراء
                </button>
                <a href="{{ route('cart.page') }}" class="cart-modal-btn-view">
                    <i class="fas fa-shopping-bag"></i>
                    عرض السلة
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Cart Modal Styles ===== */
.cart-modal-content {
    border: none;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

/* Header */
.cart-modal-header {
    background: linear-gradient(135deg, #2C4B79, #48CAE4);
    color: #fff;
    padding: 18px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.cart-modal-header-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cart-modal-header-title i {
    font-size: 1.2rem;
}

.cart-modal-header-title h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
}

.cart-modal-close {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 0.9rem;
}

.cart-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Body */
.cart-modal-body {
    padding: 20px 24px;
    max-height: 400px;
    overflow-y: auto;
}

.cart-modal-body::-webkit-scrollbar {
    width: 5px;
}

.cart-modal-body::-webkit-scrollbar-track {
    background: #f5f5f5;
}

.cart-modal-body::-webkit-scrollbar-thumb {
    background: #48CAE4;
    border-radius: 10px;
}

/* Cart item styling (items are injected by JS) */
.cart-modal-body .d-flex.align-items-center {
    padding: 12px;
    border-radius: 12px;
    background: #f8f9fa;
    margin-bottom: 12px;
    border: none !important;
    transition: background 0.2s;
}

.cart-modal-body .d-flex.align-items-center:hover {
    background: #f0f4ff;
}

.cart-modal-body .img-thumbnail {
    border-radius: 10px;
    border: 2px solid #e9ecef;
}

.cart-modal-body .btn-outline-danger {
    border-radius: 8px;
    border-color: #fde8e8;
    background: #fde8e8;
    color: #e74c3c;
    transition: all 0.2s;
}

.cart-modal-body .btn-outline-danger:hover {
    background: #e74c3c;
    color: #fff;
    border-color: #e74c3c;
}

/* Total row */
.cart-modal-body .border-top {
    border-color: #e9ecef !important;
    padding-top: 16px !important;
    margin-top: 8px !important;
}

.cart-modal-body .text-primary {
    color: #2C4B79 !important;
    font-weight: 700;
}

/* Footer */
.cart-modal-footer {
    padding: 16px 24px;
    background: #f8f9fa;
    display: flex;
    gap: 10px;
    border-top: 1px solid #e9ecef;
}

.cart-modal-btn-checkout {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #2C4B79, #48CAE4);
    color: #fff;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-family: inherit;
    box-shadow: 0 4px 15px rgba(44, 75, 121, 0.25);
}

.cart-modal-btn-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(44, 75, 121, 0.35);
}

.cart-modal-btn-view {
    flex: 1;
    padding: 12px;
    border: 2px solid #2C4B79;
    border-radius: 12px;
    background: transparent;
    color: #2C4B79;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.cart-modal-btn-view:hover {
    background: #2C4B79;
    color: #fff;
}

/* Empty Cart Modal */
.cart-modal-empty-icon {
    width: 80px;
    height: 80px;
    margin: 30px auto 20px;
    background: linear-gradient(135deg, rgba(44, 75, 121, 0.1), rgba(72, 202, 228, 0.1));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cart-modal-empty-icon i {
    font-size: 2rem;
    color: #2C4B79;
}

.cart-modal-empty-title {
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 8px;
}

.cart-modal-empty-text {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin-bottom: 24px;
}

.cart-modal-btn-close {
    width: calc(100% - 48px);
    margin: 0 24px 24px;
    padding: 10px;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    background: transparent;
    color: #7f8c8d;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.cart-modal-btn-close:hover {
    border-color: #2C4B79;
    color: #2C4B79;
}

@media (max-width: 576px) {
    .cart-modal-footer {
        flex-direction: column;
    }
}
</style>
