 document.addEventListener('DOMContentLoaded', function() {
            // Initialize the toast
            const cartSuccessToast = new bootstrap.Toast(document.getElementById('cartSuccessToast'));

            // Select the "Add to Cart" button
            const addToCartButton = document.querySelector('.btn-primary[aria-label="أضف الكتاب للسلة"]');

            // Get quantity input
            const quantityInput = document.querySelector('input[aria-label="عدد النسخ"]');

            // Add click event to "Add to Cart" button
            addToCartButton.addEventListener('click', function() {
                const bookTitle = document.getElementById('book-title').textContent;
                const quantity = quantityInput.value;
                const price = document.querySelector('.fs-4.text-primary.fw-bold').textContent;

                // Optional: You can enhance this with AJAX to actually add to cart
                // For now, we'll just show the toast
                document.querySelector('.toast-body').textContent = 
                    `تمت إضافة "${bookTitle}" (  ${quantity} نسخة) بسعر ${price} بنجاح إلى السلة`;

                // Show the toast
                cartSuccessToast.show();
            });
        });