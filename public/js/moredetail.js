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

        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star-rating label');
            const ratingText = document.getElementById('rating-text');
            const ratingMessages = {
                1: 'نجمة واحدة - ضعيف',
                2: 'نجمتان - مقبول',
                3: 'ثلاث نجوم - جيد',
                4: 'أربع نجوم - جيد جداً',
                5: 'خمس نجوم - ممتاز'
            };
            
            // Add click animation and feedback
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    // Animation effect
                    this.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 200);
                    
                    // Update feedback text
                    const rating = this.previousElementSibling.value;
                    ratingText.textContent = ratingMessages[rating];
                    ratingText.style.color = '#ffc107';
                    ratingText.style.fontWeight = '600';
                });
                
                // Hover effect for better UX
                star.addEventListener('mouseenter', function() {
                    const rating = this.previousElementSibling.value;
                    ratingText.textContent = ratingMessages[rating];
                    ratingText.style.color = '#6c757d';
                });
            });
            
            // Reset text when mouse leaves star area
            document.querySelector('.star-rating').addEventListener('mouseleave', function() {
                const checkedInput = document.querySelector('input[name="rating"]:checked');
                if (checkedInput) {
                    ratingText.textContent = ratingMessages[checkedInput.value];
                    ratingText.style.color = '#ffc107';
                    ratingText.style.fontWeight = '600';
                } else {
                    ratingText.textContent = 'اختر عدد النجوم';
                    ratingText.style.color = '#6c757d';
                    ratingText.style.fontWeight = 'normal';
                }
            });
            
            // Initialize display if there's a pre-selected rating
            const checkedInput = document.querySelector('input[name="rating"]:checked');
            if (checkedInput) {
                ratingText.textContent = ratingMessages[checkedInput.value];
                ratingText.style.color = '#ffc107';
                ratingText.style.fontWeight = '600';
            }
        });