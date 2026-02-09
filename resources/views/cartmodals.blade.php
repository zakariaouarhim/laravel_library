<!-- Empty Cart Modal -->
<div class="modal fade" id="emptyCartModal" tabindex="-1" aria-labelledby="emptyCartLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-center p-4">
            <h5 class="modal-title" id="emptyCartLabel">سلّة التسوق فارغة</h5>
            <button type="button" class="btn btn-secondary mt-3" data-bs-dismiss="modal">إغلاق</button>
        </div>
    </div>
</div>

<!-- Cart Details Modal -->
<div class="modal fade" id="cartDetailsModal" tabindex="-1" aria-labelledby="cartDetailsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title" id="cartDetailsLabel">سلّة التسوق</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <form id="checkoutForm" action="{{ route('checkout.store-cart') }}" method="POST">
                    @csrf
                    <div id="cartItemsContainer">
                        <!-- Cart items will be inserted dynamically -->
                    </div>
                    <input type="hidden" name="cart_data" id="cartDataInput">
                </form>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-primary" id="checkoutButton" onclick="submitCheckoutForm()">إتمام الشراء ✔️</button>
                <a href="{{ route('cart.page') }}" class="btn btn-outline-warning">سلّة التسوق</a>
            </div>
        </div>
    </div>
</div>