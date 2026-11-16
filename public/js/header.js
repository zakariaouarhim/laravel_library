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
                <div class="text-center py-5">
                    <div style="width:70px;height:70px;margin:0 auto 16px;background:linear-gradient(135deg,rgba(44,75,121,0.1),rgba(72,202,228,0.1));border-radius:50%;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-shopping-bag" style="font-size:1.8rem;color:#2C4B79;"></i>
                    </div>
                    <p class="text-muted mb-3">سلّة التسوق فارغة</p>
                    <a href="/" class="btn" style="background:linear-gradient(135deg,#2C4B79,#48CAE4);color:#fff;border-radius:10px;padding:8px 24px;font-weight:600;">تصفح الكتب</a>
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
                                    <span class="fw-bold">${item.price} د.م</span>
                                </div>
                                <span class="fw-bold">${(item.price * item.quantity).toFixed(2)} د.م</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm ms-2" onclick="removeFromCart('${item.id}')">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    `;
                    modalBody.innerHTML += itemHTML;
                });

                // Add total
                modalBody.innerHTML += `
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <h5 class="mb-0">الإجمالي:</h5>
                    <h5 class="mb-0 text-primary">${total.toFixed(2)} د.م</h5>
                </div>`;
            }
            if (data.success) {
                document.getElementById('cartCount').textContent = data.cartCount; // Update count
            }
            // Show modal
            new bootstrap.Modal(document.getElementById('cartDetailsModal')).show();
        })
        .catch(error => {
            showCartToast('حدث خطأ أثناء تحميل السلة');
        });
}

// Function to submit checkout form
function submitCheckoutForm() {
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
    .catch(() => {});
}
// Sticky scroll behavior is handled in header.blade.php



/*///////////////search//////////////////////////*/

// Debounce timers per container
var _searchTimers = {};
var _searchAborters = {};        // per-container AbortController for in-flight fetches
var _searchCache = new Map();    // query -> books[], so retyping doesn't re-fetch
var SEARCH_CACHE_MAX = 30;

// Recent searches (stored in localStorage)
function getRecentSearches() {
    try {
        return JSON.parse(localStorage.getItem('recentSearches') || '[]');
    } catch(e) { return []; }
}

function saveRecentSearch(query) {
    if (!query || query.length < 2) return;
    var recents = getRecentSearches().filter(function(s) { return s !== query; });
    recents.unshift(query);
    if (recents.length > 5) recents = recents.slice(0, 5);
    localStorage.setItem('recentSearches', JSON.stringify(recents));
}

// Highlight matching text
function highlightMatch(text, query) {
    if (!text || !query) return text || '';
    var escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp('(' + escaped + ')', 'gi'), '<mark>$1</mark>');
}

// Show recent searches on focus (when input is empty or short)
function showRecentSearches(containerId) {
    var resultsContainer = document.getElementById(containerId);
    if (!resultsContainer) return;

    var recents = getRecentSearches();
    if (recents.length === 0) {
        resultsContainer.style.display = 'none';
        return;
    }

    var html = '<div class="search-section">';
    html += '<div class="search-section-header"><i class="fas fa-history"></i> عمليات البحث الأخيرة</div>';
    recents.forEach(function(term) {
        html += '<a href="/search-results?query=' + encodeURIComponent(term) + '" class="search-recent-item">';
        html += '<i class="fas fa-history"></i> <span>' + term + '</span>';
        html += '</a>';
    });
    html += '</div>';

    resultsContainer.innerHTML = html;
    resultsContainer.style.display = 'block';
}

// Main autocomplete function with debouncing, loading state, and highlighting
function searchBooksAutocomplete(query, containerId) {
    if (!containerId) containerId = 'searchResults';
    var resultsContainer = document.getElementById(containerId);

    if (!resultsContainer) return;

    query = query.trim();

    // Clear previous timer (debounce 300ms)
    if (_searchTimers[containerId]) {
        clearTimeout(_searchTimers[containerId]);
    }

    if (query.length < 2) {
        if (query.length === 0) {
            showRecentSearches(containerId);
        } else {
            resultsContainer.style.display = 'none';
        }
        return;
    }

    // Serve repeats (backspacing, retyping) from the client cache instantly
    if (_searchCache.has(query)) {
        renderAutocompleteResults(_searchCache.get(query), query, resultsContainer);
        return;
    }

    _searchTimers[containerId] = setTimeout(function() {
        // Abort the previous in-flight request for this box — on a slow
        // connection an older query's late response must not win.
        if (_searchAborters[containerId]) {
            _searchAborters[containerId].abort();
        }
        var aborter = new AbortController();
        _searchAborters[containerId] = aborter;

        // Show loading spinner only when a fetch actually starts
        resultsContainer.innerHTML = '<div class="search-loading"><div class="search-spinner"></div> جاري البحث...</div>';
        resultsContainer.style.display = 'block';

        fetch('/search-books?query=' + encodeURIComponent(query), { signal: aborter.signal })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (!data.success) {
                    resultsContainer.style.display = 'none';
                    return;
                }
                if (_searchCache.size >= SEARCH_CACHE_MAX) {
                    _searchCache.delete(_searchCache.keys().next().value);
                }
                _searchCache.set(query, data.books);
                renderAutocompleteResults(data.books, query, resultsContainer);
            })
            .catch(function(err) {
                if (err && err.name === 'AbortError') return; // superseded by a newer query
                resultsContainer.style.display = 'none';
            });
    }, 300);
}

function renderAutocompleteResults(books, query, resultsContainer) {
    if (books.length > 0) {
        var html = '<div class="search-results-list">';

        books.forEach(function(book) {
            var imageUrl = book.image ? book.image : '/default-book.png';
            var title = highlightMatch(book.title, query);
            var author = highlightMatch(book.author_name || '', query);
            var publisher = book.publishing_house_name ? highlightMatch(book.publishing_house_name, query) : '';

            html += '<a href="/moredetail-v2/' + book.id + '" class="search-result-item">';
            html += '<img src="/' + imageUrl + '" alt="' + (book.title || '') + '">';
            html += '<div class="search-result-info">';
            html += '<div class="search-result-title">' + title + '</div>';
            if (author) {
                html += '<div class="search-result-author"><i class="fas fa-user-edit"></i> ' + author + '</div>';
            }
            if (publisher) {
                html += '<div class="search-result-publisher"><i class="fas fa-building"></i> ' + publisher + '</div>';
            }
            if (book.price) {
                html += '<div class="search-result-price">' + book.price + ' د.م</div>';
            }
            html += '</div></a>';
        });

        html += '<a href="/search-results?query=' + encodeURIComponent(query) + '" class="search-view-all">';
        html += '<i class="fas fa-search"></i> عرض جميع النتائج';
        html += '</a></div>';

        resultsContainer.innerHTML = html;
        resultsContainer.style.display = 'block';
    } else {
        resultsContainer.innerHTML = '<div class="search-no-results">' +
            '<i class="fas fa-search"></i> لم يتم العثور على نتائج لـ "' + query + '"' +
            '</div>';
        resultsContainer.style.display = 'block';
    }
}

// Handle focus on search inputs — show recent searches
document.addEventListener('DOMContentLoaded', function() {
    // Header search
    var headerInput = document.getElementById('searchInputHeader');
    if (headerInput) {
        headerInput.addEventListener('focus', function() {
            if (this.value.length < 2) showRecentSearches('searchResultsHeader');
        });
    }

    // Index page search
    var indexInput = document.getElementById('searchInput');
    if (indexInput) {
        indexInput.addEventListener('focus', function() {
            if (this.value.length < 2) showRecentSearches('searchResults');
        });
    }

    // Mobile search — add autocomplete support
    var mobileSearchInputs = document.querySelectorAll('.mobile-search input[name="query"]');
    mobileSearchInputs.forEach(function(input) {
        // Create results container if it doesn't exist
        if (!input.parentElement.querySelector('.search-results-mobile')) {
            var mobileResults = document.createElement('div');
            mobileResults.id = 'searchResultsMobile';
            mobileResults.className = 'search-results-header search-results-mobile';
            input.parentElement.appendChild(mobileResults);
        }

        input.addEventListener('input', function() {
            searchBooksAutocomplete(this.value, 'searchResultsMobile');
        });
        input.addEventListener('focus', function() {
            if (this.value.length < 2) showRecentSearches('searchResultsMobile');
        });
    });

    // Save search term when form is submitted
    document.querySelectorAll('form[action*="search-results"]').forEach(function(form) {
        form.addEventListener('submit', function() {
            var input = form.querySelector('input[name="query"]');
            if (input) saveRecentSearch(input.value.trim());
        });
    });
});

// Hide autocomplete when clicking outside
document.addEventListener('click', function(event) {
    var containers = ['searchResults', 'searchResultsHeader', 'searchResultsMobile'];
    containers.forEach(function(id) {
        var container = document.getElementById(id);
        if (!container) return;
        var input = container.previousElementSibling ||
                    container.parentElement.querySelector('input[name="query"]');
        if (input && !input.contains(event.target) && !container.contains(event.target)) {
            container.style.display = 'none';
        }
    });
});