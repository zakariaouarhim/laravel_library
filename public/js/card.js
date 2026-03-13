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
        showCartAlert('حدث خطأ في العملية', 'danger');
    });
}

// showCartAlert() is provided by scripts.js (always loaded first)