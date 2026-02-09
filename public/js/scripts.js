function addToCart(bookId, bookTitle, bookPrice, bookImage) {
    console.log("Parameters:", { bookId, bookTitle, bookPrice, bookImage });
    
    // Get the button element that was clicked (supports both .add-btn and .action-btn)
    const button = event.target.closest('.add-btn') || event.target.closest('.action-btn');
    
    fetch(`/add-to-cart/${bookId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            title: bookTitle,
            price: bookPrice,
            image: bookImage
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Add success animation
            button.classList.add('add-success');
            
            // Simulate API call
            setTimeout(() => {
                button.classList.remove('add-success');
                // Show success feedback
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.style.background = '#28a745';
                
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-shopping-cart"></i>';
                    button.style.background = '';
                }, 1500);
                
                console.log(`تمت إضافة الكتاب ${bookId} إلى السلة`);
            }, 300);
            
            updateCartCount(data.cartCount);
            showCartAlert(`تمت إضافة "${bookTitle}" إلى السلة`);
            
            // Update the cart modal if it's open
            const cartModal = document.getElementById('cartDetailsModal');
            if(cartModal && cartModal.classList.contains('show')) {
                showCartModal();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCartAlert('حدث خطأ أثناء الإضافة إلى السلة', 'danger');
    });
}

// Fixed helper functions to match your HTML structure
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}
function showCartAlert(message, type = 'success') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.setAttribute('role', 'alert');
    alert.style.position = 'fixed';
    alert.style.top = '80px'; // Adjust this value based on your header height
    alert.style.left = '50%';
    alert.style.transform = 'translateX(-50%)';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.style.maxWidth = '500px';
    alert.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
    
    // Choose icon based on alert type
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    // Get progress bar color based on alert type
    const progressBarColor = type === 'success' ? 'bg-success' : 
                            type === 'danger' ? 'bg-danger' : 
                            type === 'warning' ? 'bg-warning' : 'bg-info';
    
    alert.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${icon} me-2" style="font-size: 1.5rem;"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="progress mt-2" style="height: 3px; background-color: rgba(228, 21, 21, 0.1);">
            <div class="progress-bar ${progressBarColor}" role="progressbar" style="width: 100%; transition: width 3s linear;"></div>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(alert);
    
    // Start the timer animation (progress bar goes from 100% to 0% in 3 seconds)
    const progressBar = alert.querySelector('.progress-bar');
    setTimeout(() => {
        progressBar.style.width = '0%';
    }, 10);
    
    // Auto remove after 3 seconds (when timer finishes)
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 2000);
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('/get-cart')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                updateCartCount(data.cartCount);
            }
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
        });
});

document.getElementById('cartDetailsModal').addEventListener('hidden.bs.modal', function () {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.paddingRight = '';
});

///////////////////////////////
// UPDATED SEARCH BAR FUNCTIONALITY
///////////////////////////////

function toggleSearchBar() {
    const searchBar = document.getElementById('searchBar');
    const searchInput = searchBar.querySelector('.search-input');
    
    if (searchBar.classList.contains('active')) {
        searchBar.classList.remove('active');
        document.body.style.overflow = '';
    } else {
        searchBar.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            searchInput.focus();
        }, 300);
    }
}

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const searchBar = document.getElementById('searchBar');
    
    // Close search bar when clicking on the overlay (outside the search content)
    if (searchBar) {
        searchBar.addEventListener('click', function(event) {
            // If clicked directly on the search bar container (the dark overlay), close it
            if (event.target === searchBar) {
                toggleSearchBar();
            }
        });
    }
    
    // Close search bar with ESC key
    document.addEventListener('keydown', function(event) {
        if (searchBar && event.key === 'Escape' && searchBar.classList.contains('active')) {
            toggleSearchBar();
        }
    });
});