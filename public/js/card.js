function toggleWishlist(bookId, buttonElement) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const icon = buttonElement.querySelector('i');
    
    // Check if already in wishlist by checking if icon is filled (fas)
    const isInWishlist = icon.classList.contains('fas');
    
    // Determine which route to use
    const route = isInWishlist ? `/wishlist/remove/${bookId}` : `/wishlist/add/${bookId}`;
    
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
            alert(data.message);
            
            // Toggle the icon
            if (isInWishlist) {
                // Remove from wishlist - change to empty heart
                icon.classList.remove('fas');
                icon.classList.add('far');
                buttonElement.classList.remove('active');
            } else {
                // Add to wishlist - change to filled heart
                icon.classList.remove('far');
                icon.classList.add('fas');
                buttonElement.classList.add('active');
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في العملية');
    });
}