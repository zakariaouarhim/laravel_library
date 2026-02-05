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
                        <img src="${item.image}" alt="${item.image}" class="img-thumbnail" style="width: 80px; height: 100px; object-fit: cover;">
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
            if (data.success) {
                document.getElementById('cartCount').textContent = data.cartCount; // Update count
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
let lastScrollTop = 0;
    const navbar = document.getElementById("mainNavbar");

    window.addEventListener("scroll", function () {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;

        if (currentScroll > lastScrollTop) {
            // Scrolling down → hide navbar
            navbar.style.top = "-100px";
        } else {
            // Scrolling up → show navbar
            navbar.style.top = "0";
        }

        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    });



/*///////////////search//////////////////////////*/
// Autocomplete search function
// Unified search function that works for both index page and header
function searchBooksAutocomplete(query, containerId = 'searchResults') {
    const resultsContainer = document.getElementById(containerId);
    
    if (!resultsContainer) {
        console.error(`Container with ID "${containerId}" not found`);
        return;
    }
    
    if (query.length < 2) {
        resultsContainer.style.display = 'none';
        return;
    }
    
    fetch(`/search-books?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.books.length > 0) {
                let html = '<div class="list-group">';
                
                data.books.forEach(book => {
                    const imageUrl = book.image 
                        ? `${book.image}` 
                        : '/default-book.png';
                    
                    html += `
                        <a href="/moredetail/${book.id}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                            <img src="${imageUrl}" 
                                 alt="${book.title}" 
                                 style="width: 50px; height: 70px; object-fit: cover; margin-left: 15px; border-radius: 4px;">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${book.title}</h6>
                                <small class="text-muted">${book.author}</small>
                                ${book.price ? `<div class="text-primary fw-bold">${book.price} ر.س</div>` : ''}
                            </div>
                        </a>
                    `;
                });
                
                html += `
                    <div class="list-group-item text-center bg-light">
                        <small class="text-muted">اضغط Enter أو زر البحث لعرض جميع النتائج</small>
                    </div>
                </div>`;
                
                resultsContainer.innerHTML = html;
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.innerHTML = `
                    <div class="p-3 text-center text-muted">
                        <i class="bi bi-search"></i> لم يتم العثور على نتائج
                    </div>
                `;
                resultsContainer.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultsContainer.style.display = 'none';
        });
}



// Hide autocomplete when clicking outside
document.addEventListener('click', function(event) {
    // Index page search
    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.getElementById('searchResults');

    if (searchInput && resultsContainer &&
        !searchInput.contains(event.target) &&
        !resultsContainer.contains(event.target)) {
        resultsContainer.style.display = 'none';
    }

    // Header search
    const headerSearchInput = document.getElementById('searchInputHeader');
    const headerResults = document.getElementById('searchResultsHeader');

    if (headerSearchInput && headerResults &&
        !headerSearchInput.contains(event.target) &&
        !headerResults.contains(event.target)) {
        headerResults.style.display = 'none';
    }
});