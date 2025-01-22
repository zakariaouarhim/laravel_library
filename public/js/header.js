 function showCartModal() {
        let cartCount = parseInt(document.getElementById('cartCount').innerText);
        
        if (cartCount > 0) {
            var myModal = new bootstrap.Modal(document.getElementById('cartDetailsModal'));
            myModal.show();
        } else {
            var emptyModal = new bootstrap.Modal(document.getElementById('emptyCartModal'));
            emptyModal.show();
        }
    }