function toggleWishlist(bookId, buttonElement) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const icon = buttonElement.querySelector('i');
    
    // Check if already in wishlist by checking if icon is filled (fas)
    const isInWishlist = icon.classList.contains('fas');
    
    // Check if user is authenticated
    const isAuthenticated = document.querySelector('meta[name="auth-user"]')?.getAttribute('content') === 'true';
    
    if (isAuthenticated) {
        // User is logged in - use database
        const route = isInWishlist ? `/wishlist/remove/${bookId}` : `/wishlist/add/${bookId}`;
        
        fetch(route, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            }
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Invalid response format');
            }
            
            return response.json().then(data => ({
                status: response.status,
                data: data
            }));
        })
        .then(({ status, data }) => {
            if (data.success) {
                showCartAlert(data.message, 'success');
                
                // Toggle the icon
                if (isInWishlist) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    buttonElement.classList.remove('active');
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    buttonElement.classList.add('active');
                }
            } else {
                showCartAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showCartAlert('حدث خطأ في العملية', 'danger');
        });
    } else {
        // User is guest - use session storage
        handleGuestWishlist(bookId, buttonElement, icon, isInWishlist);
    }
}

function handleGuestWishlist(bookId, buttonElement, icon, isInWishlist) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Send to session via API
    const route = isInWishlist ? `/wishlist-session/remove/${bookId}` : `/wishlist-session/add/${bookId}`;
    
    fetch(route, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showCartAlert(data.message, 'success');
            
            // Toggle the icon
            if (isInWishlist) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                buttonElement.classList.remove('active');
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
                buttonElement.classList.add('active');
            }
        } else {
            showCartAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCartAlert('حدث خطأ في العملية', 'danger');
    });
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