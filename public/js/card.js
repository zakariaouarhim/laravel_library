/**
 * POST a wishlist toggle, surviving a stale CSRF token.
 *
 * js/csrf.js refreshes the token and retries once on a 419. That matters here
 * because a visitor can leave a product page open past SESSION_LIFETIME and
 * then lose the click: a real 419 on /wishlist-session/add was observed in the
 * production access log. Falls back to a plain fetch when that helper is not on
 * the page, so no view breaks by forgetting to include it.
 *
 * Accept: application/json is deliberate - without it Laravel answers a 419
 * with an HTML error page, which both callers below would reject as an invalid
 * response format instead of showing the real message.
 */
function wishlistPost(url) {
    if (window.csrfPostJson) { return window.csrfPostJson(url); }

    const meta = document.querySelector('meta[name="csrf-token"]');
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': meta ? meta.getAttribute('content') : '',
        },
    });
}

function toggleWishlist(bookId, buttonElement) {
    const icon = buttonElement.querySelector('i');
    
    // Check if already in wishlist by checking if icon is filled (fas)
    const isInWishlist = icon.classList.contains('fas');
    
    // Check if user is authenticated
    const isAuthenticated = document.querySelector('meta[name="auth-user"]')?.getAttribute('content') === 'true';
    
    if (isAuthenticated) {
        // User is logged in - use database
        const route = isInWishlist ? `/wishlist/remove/${bookId}` : `/wishlist/add/${bookId}`;
        
        wishlistPost(route)
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
    
    // Send to session via API
    const route = isInWishlist ? `/wishlist-session/remove/${bookId}` : `/wishlist-session/add/${bookId}`;
    
    wishlistPost(route)
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