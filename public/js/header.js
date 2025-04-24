// Fetch and display cart modal
// Update showCartModal function to handle empty cart better
function showCartModal() {
    fetch('/get-cart')
        .then(response => response.json())
        .then(data => {
            const modalBody = document.querySelector('#cartItemsContainer');
            modalBody.innerHTML = '';
            // Set cart data in hidden input
            if (data.success && Object.keys(data.cart).length > 0) {
                document.getElementById('cartDataInput').value = JSON.stringify(data.cart);
            }
            if (!data.success || Object.keys(data.cart).length === 0) {
                modalBody.innerHTML = `
                <div class="text-center py-4">
                    <i class="bi bi-cart-x fs-1 text-muted"></i>
                    <p class="mt-2">سلّة التسوق فارغة</p>
                    <a href="{{ route('index.page') }}" class="btn btn-primary mt-2">تصفح الكتب</a>
                </div>`;
            } else {
                let total = 0;
                Object.values(data.cart).forEach(item => {
                    total += item.price * item.quantity;
                    
                    const itemHTML = `
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                        <img src="/${item.image}" alt="${item.image}" class="img-thumbnail" style="width: 80px; height: 100px; object-fit: cover;">
                        <div class="ms-3 flex-grow-1">
                            <h6 class="mb-1">${item.title}</h6>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted me-2">${item.quantity} × </span>
                                    <span class="fw-bold">${item.price} ر.س</span>
                                </div>
                                <span class="fw-bold">${(item.price * item.quantity).toFixed(2)} ر.س</span>
                            </div>
                        </div>
                        <button  type="button"class="btn btn-outline-danger btn-sm ms-2" onclick="removeFromCart('${item.id}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    `;
                    modalBody.innerHTML += itemHTML;
                });

                // Add total
                modalBody.innerHTML += `
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <h5 class="mb-0">الإجمالي:</h5>
                    <h5 class="mb-0 text-primary">${total.toFixed(2)} ر.س</h5>
                </div>`;
            }

            // Show modal
            new bootstrap.Modal(document.getElementById('cartDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            showCartToast('حدث خطأ أثناء تحميل السلة');
        });
}

// Function to submit checkout form
function submitCheckoutForm() {
    const cartData = document.getElementById('cartDataInput');
    console.log("Cart data being sent:", cartData);
    document.getElementById('checkoutForm').submit();
}


// Toast notification function
function showCartToast(message) {
    const toastElement = document.getElementById('cartToast');
    const toastBody = toastElement.querySelector('.toast-body');
    toastBody.textContent = message;
    new bootstrap.Toast(toastElement).show();
}

function removeFromCart(itemId) {
    fetch('/remove-from-cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') // CSRF Token for Laravel
        },
        body: JSON.stringify({ id: itemId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showCartModal(); // Refresh the modal
            document.getElementById('cartCount').textContent = data.cartCount; // Update cart count badge
        } else {
            alert('حدث خطأ أثناء حذف المنتج');
        }
    })
    .catch(error => console.error('Error:', error));
}



///////////////////////////////
document.addEventListener('DOMContentLoaded', function() {
    const lettersContainer = document.getElementById('letters-container');
    if (!lettersContainer) return; // Exit if element not found
    
    const arabicLetters = ['ا', 'ب', 'ت', 'ث', 'ج', 'ح', 'خ', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ك', 'ل', 'م', 'ن', 'ه', 'و', 'ي'];
    
    // Create letters dynamically
    function createLetters() {
        // Initial batch of letters
        for (let i = 0; i < 60; i++) {
            createLetter();
        }
        
        // Continue adding letters over time
        setInterval(createLetter, 500);
    }
    
    function createLetter() {
        const letter = document.createElement('div');
        letter.className = 'letter';
        
        const randomLetter = arabicLetters[Math.floor(Math.random() * arabicLetters.length)];
        letter.textContent = randomLetter;
        
        // Random positioning and animation properties
        const size = Math.random() * 40 + 20; // Size between 20px and 60px
        const leftPos = Math.random() * 100; // Position from left 0% to 100%
        const duration = Math.random() * 15 + 10; // Animation duration between 10s and 25s
        const delay = Math.random() * 10; // Delay between 0s and 10s
        const opacity = Math.random() * 0.4 + 0.1; // Opacity between 0.1 and 0.5
        
        letter.style.fontSize = `${size}px`;
        letter.style.left = `${leftPos}%`;
        letter.style.animationDuration = `${duration}s`;
        letter.style.animationDelay = `${delay}s`;
        letter.style.opacity = opacity;
        
        lettersContainer.appendChild(letter);
        
        // Remove the letter after animation completes to prevent memory issues
        setTimeout(() => {
            if (letter.parentNode === lettersContainer) {
                lettersContainer.removeChild(letter);
            }
        }, (duration + delay) * 1000);
    }
    
    // Start the animation when the page loads
    createLetters();
});

// header.js
function searchBooks(query) {
    const resultsContainer = document.getElementById('searchResults');
    resultsContainer.innerHTML = '';
    resultsContainer.style.display = 'none';

    if (!query.trim()) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch(`/search-books?query=${encodeURIComponent(query)}`, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (data.books.length === 0) {
                resultsContainer.innerHTML = '<div class="text-center p-2">لم يتم العثور على نتائج</div>';
            } else {
                data.books.forEach(book => {
                    const resultItem = document.createElement('div');
                    resultItem.className = 'search-result-item';
                    resultItem.innerHTML = `
                        <img src="${book.image}" class="me-2" style="width: 40px; height: 50px;">
                        <span style="color:#000000;">${book.title}</span>
                    `;
                    resultItem.onclick = () => window.location.href = `/moredetail/${book.id}`;
                    resultsContainer.appendChild(resultItem);
                });
            }
            resultsContainer.style.display = 'block';
        } else {
            throw new Error(data.message || 'Unknown error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultsContainer.innerHTML = '<div class="text-center p-2" style="color:#000000;">حدث خطأ أثناء البحث</div>';
        resultsContainer.style.display = 'block';
    });
}