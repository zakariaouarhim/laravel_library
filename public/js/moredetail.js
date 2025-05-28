document.addEventListener('DOMContentLoaded', function() {
            // Initialize the toast
            const cartSuccessToast = new bootstrap.Toast(document.getElementById('cartSuccessToast'));

            // Select the "Add to Cart" button
            const addToCartButton = document.querySelector('.btn-primary[aria-label="أضف الكتاب للسلة"]');

            // Get quantity input
            const quantityInput = document.querySelector('input[aria-label="عدد النسخ"]');

            // Add click event to "Add to Cart" button
           
        });

        function addToCartM(bookId) {
            const quantityInput = document.querySelector('input[aria-label="عدد النسخ"]');
            const quantity = quantityInput.value;
            const button = document.getElementById('addToCartButton');
            
            // Get book details from data attributes
            const title = button.getAttribute('data-title');
            const price = button.getAttribute('data-price');
            const image = button.getAttribute('data-image');
        
            // Disable button during request
            button.disabled = true;
            
            fetch('/add-to-cart/' + bookId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    quantity: quantity,
                    title: title,
                    price: price,
                    image: image
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update header cart count
                    const cartCount = document.getElementById('cartCount');
                    if (cartCount) cartCount.textContent = data.cartCount;
                    
                    // Show success toast
                    const toastBody = document.querySelector('#cartSuccessToast .toast-body');
                    toastBody.textContent = `تمت إضافة "${title}" (${quantity} نسخ) بسعر ${price} ر.س بنجاح`;
                    const toast = new bootstrap.Toast(document.getElementById('cartSuccessToast'));
                    toast.show();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إضافة المنتج للسلة');
            })
            .finally(() => {
                button.disabled = false;
            });
        }

        