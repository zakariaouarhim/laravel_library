// Global variables
let currentPage = 1;
let currentSearch = '';
let currentStatus = '';
let selectedProducts = [];
let productToDelete = null;

// Initialize on page load
$(document).ready(function() {
    loadProducts();
    initializeEventListeners();
});

// Initialize event listeners
function initializeEventListeners() {
    // Search input
    $('#searchInput').on('input', function() {
        currentSearch = $(this).val();
        currentPage = 1;
        loadProducts();
    });

    // Status filter
    $('#statusFilter').on('change', function() {
        currentStatus = $(this).val();
        currentPage = 1;
        loadProducts();
    });

    // Select all checkbox
    $('#selectAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.product-checkbox').prop('checked', isChecked);
        updateSelectedProducts();
    });

    // CSRF Token setup for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
}

// Load products with pagination and filters
function loadProducts(page = 1) {
    const params = {
        page: page,
        search: currentSearch,
        status: currentStatus
    };

    $.get('/products/api', params) // Use direct URL path
        .done(function(response) {
            console.log('Success:', response);
            if (response.success) {
                renderProductsTable(response.data.data);
                renderPagination(response.data);
                // Update stats if available
                if (response.stats) {
                    updateStatsCards(response.stats);
                } else {
                    // Fetch stats separately if not included
                    loadStats();
                }
            } else {
                showAlert('حدث خطأ في تحميل البيانات', 'danger');
            }
        })
        .fail(function(xhr, status, error) {
            console.log('Error details:', xhr.responseText);
            showAlert('خطأ في الاتصال بالخادم', 'danger');
        });
}

// Load statistics for stats cards
function loadStats() {
    $.get('/products/api/stats')
        .done(function(response) {
            if (response.success) {
                updateStatsCards(response.stats);
            }
        })
        .fail(function(xhr, status, error) {
            console.log('Failed to load stats:', error);
        });
}

// Update stats cards with data
function updateStatsCards(stats) {
    $('#totalProductsStat').text(stats.total || 0);
    $('#enrichedProductsStat').text(stats.enriched || 0);
    $('#pendingProductsStat').text(stats.pending || 0);
}

// Render products table
function renderProductsTable(products) {
    const tbody = $('#productsTableBody');
    tbody.empty();

    if (products.length === 0) {
        tbody.html('<tr><td colspan="11" class="text-center">لا توجد منتجات</td></tr>');
        return;
    }

    products.forEach((product, index) => {
        const row = `
            <tr>
                <td>
                    <input type="checkbox" class="product-checkbox" value="${product.id}">
                </td>
                <td>${((currentPage - 1) * 10) + index + 1}</td>
                <td>
                    <img src="/${product.image || 'images/books/default-book.png'}" 
                         alt="${product.title}" class="product-thumb" 
                         style="width: 50px; height: 60px; object-fit: cover;">
                </td>
                <td>
                    <strong>${product.title}</strong>
                </td>
                <td style="max-width: 300px;">
                    <div class="text-truncate" title="${product.description || ''}">
                        ${product.description ? product.description.substring(0, 100) + '...' : 'لا يوجد وصف'}
                    </div>
                </td>
                <td>
                    <span class="badge bg-success">${product.price} درهم</span>
                </td>
                <td>${product.author || 'غير محدد'}</td>
                <td>
                    <span class="badge ${product.Quantity  > 0 ? 'bg-primary' : 'bg-danger'}">
                        ${product.Quantity }
                    </span>
                </td>
                <td>
                    <small class="text-muted">${product.ISBN || 'غير محدد'}</small>
                </td>
                <td>
                    ${renderApiStatus(product.api_data_status)}
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-info" onclick="viewProduct(${product.id})" title="عرض">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="editProduct(${product.id})" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="enrichProduct(${product.id})" title="إثراء API">
                            <i class="fas fa-magic"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${product.id}, '${product.title}')" title="حذف">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    // Update checkbox listeners
    $('.product-checkbox').on('change', updateSelectedProducts);
}

// Render API status badge
function renderApiStatus(status) {
    switch (status) {
        case 'enriched':
            return '<span class="badge bg-success">معالج</span>';
        case 'pending':
            return '<span class="badge bg-warning">في الانتظار</span>';
        case 'failed':
            return '<span class="badge bg-danger">فشل</span>';
        default:
            return '<span class="badge bg-secondary">غير محدد</span>';
    }
}

// Render pagination
function renderPagination(paginationData) {
    const container = $('#paginationContainer');
    container.empty();

    if (paginationData.last_page <= 1) return;

    let paginationHtml = '';

    // Previous button
    if (paginationData.current_page > 1) {
        paginationHtml += `<li class="page-item">
            <a class="page-link" href="#" onclick="changePage(${paginationData.current_page - 1})">السابق</a>
        </li>`;
    }

    // Page numbers
    for (let i = 1; i <= paginationData.last_page; i++) {
        if (i === paginationData.current_page) {
            paginationHtml += `<li class="page-item active">
                <span class="page-link">${i}</span>
            </li>`;
        } else if (Math.abs(i - paginationData.current_page) <= 2 || i === 1 || i === paginationData.last_page) {
            paginationHtml += `<li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>`;
        } else if (Math.abs(i - paginationData.current_page) === 3) {
            paginationHtml += `<li class="page-item disabled">
                <span class="page-link">...</span>
            </li>`;
        }
    }

    // Next button
    if (paginationData.current_page < paginationData.last_page) {
        paginationHtml += `<li class="page-item">
            <a class="page-link" href="#" onclick="changePage(${paginationData.current_page + 1})">التالي</a>
        </li>`;
    }

    container.html(paginationHtml);
}

// Change page
function changePage(page) {
    currentPage = page;
    loadProducts(page);
}

// Update selected products array
function updateSelectedProducts() {
    selectedProducts = [];
    $('.product-checkbox:checked').each(function() {
        selectedProducts.push($(this).val());
    });
}

// Show alert message
function showAlert(message, type = 'info', duration = 5000) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('#alertContainer').html(alertHtml);
    
    if (duration > 0) {
        setTimeout(() => {
            $('.alert').alert('close');
        }, duration);
    }
}

// View product details
function viewProduct(id) {
    $.get(`/products/api/${id}`)
        .done(function(response) {
            // Check if response is the product directly or wrapped in success/data
            const product = response.success ? response.data : response;
            
            if (product && product.id) { // Check if product exists and has an ID
                const detailsHtml = `
                    <div class="row">
                        <div class="col-md-4">
                            <img src="/${product.image || 'images/books/default-book.png'}"
                                 alt="${product.title}" class="img-fluid rounded"
                                 onerror="this.src='/images/default-book.png'">
                        </div>
                        <div class="col-md-8">
                            <h4>${product.title}</h4>
                            <p><strong>المؤلف:</strong> ${product.author || 'غير محدد'}</p>
                            <p><strong>السعر:</strong> ${product.price} درهم</p>
                            <p><strong>الكمية:</strong> ${product.Quantity}</p>
                            <p><strong>ISBN:</strong> ${product.ISBN || 'غير محدد'}</p>
                            <p><strong>عدد الصفحات:</strong> ${product.	Page_Num || 'غير محدد'}</p>
                            <p><strong>اللغة:</strong> ${product.Langue || 'غير محدد'}</p>
                            <p><strong>دار النشر:</strong> ${product.Publishing_House || 'غير محدد'}</p>
                            <p><strong>حالة API:</strong> ${renderApiStatus(product.api_data_status)}</p>
                            <hr>
                            <h6>الوصف:</h6>
                            <p>${product.description || 'لا يوجد وصف'}</p>
                        </div>
                    </div>
                `;
                $('#productDetailsContent').html(detailsHtml);
                $('#productDetailsModal').modal('show');
            } else {
                showAlert('لم يتم العثور على المنتج', 'danger');
            }
        })
        .fail(function(xhr, status, error) {
            console.log('Error details:', xhr.responseText);
            showAlert('خطأ في تحميل تفاصيل المنتج', 'danger');
        });
}

// Edit product
function editProduct(id) {
    $.get(`/products/api/${id}`)
        .done(function(response) {
            console.log('Edit Product Response:', response); // Debug line
            
            if (response.success) {
                const product = response.data;
                $('#editProductId').val(product.id);
                $('#editProductName').val(product.title); // Changed from 'name' to 'title'
                $('#editProductAuthor').val(product.author);
                $('#editProductDescription').val(product.description);
                $('#editProductPrice').val(product.price);
                $('#editProductNumPages').val(product.Page_Num); // Match your DB column
                $('#editProductLanguage').val(product.Langue); // Match your DB column
                $('#editProductPublishingHouse').val(product.Publishing_House); // Match your DB column
                $('#editProductIsbn').val(product.ISBN); // Match your DB column
                $('#editProductCategorie').val(product.category_id);
                $('#editProductQuantity').val(product.Quantity); // Match your DB column
                $('#editProductModal').modal('show');
            } else {
                showAlert('لم يتم العثور على المنتج', 'danger');
            }
        })
        .fail(function(xhr, status, error) {
            console.log('Error details:', xhr.responseText);
            showAlert('خطأ في تحميل بيانات المنتج', 'danger');
        });
}

// Update product
function updateProduct() {
    const formData = new FormData();
    const productId = $('#editProductId').val();
    
    // Validate product ID
    if (!productId) {
        showAlert('معرف المنتج مفقود', 'danger');
        return;
    }
    
    formData.append('_method', 'PUT');
    formData.append('title', $('#editProductName').val());
    formData.append('author', $('#editProductAuthor').val());
    formData.append('description', $('#editProductDescription').val());
    formData.append('price', $('#editProductPrice').val());
    formData.append('Page_Num', $('#editProductNumPages').val() || '');
    formData.append('Langue', $('#editProductLanguage').val() || '');
    formData.append('Publishing_House', $('#editProductPublishingHouse').val() || '');
    formData.append('ISBN', $('#editProductIsbn').val() || '');
    formData.append('category_id', $('#editProductCategorie').val());
    formData.append('Quantity', $('#editProductQuantity').val());
    formData.append('auto_enrich', $('#editAutoEnrich').is(':checked') ? '1' : '0');
    
    const imageFile = $('#editProductImage')[0].files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }

    // Show loading
    // showLoadingModal();

    $.ajax({
        url: `/products/${productId}`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 30000, // 30 seconds timeout
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Success Response:', response);
            hideLoadingModal();
            
            // Check if response has success property
            if (response && response.success === true) {
                $('#editProductModal').modal('hide');
                showAlert('تم تحديث المنتج بنجاح', 'success');
                loadProducts(currentPage);
            } else {
                // Handle unexpected success response format
                console.warn('Unexpected response format:', response);
                showAlert(response.message || 'تم التحديث ولكن هناك مشكلة في الاستجابة', 'warning');
                // Still reload products since update might have worked
                loadProducts(currentPage);
            }
        },
        error: function(xhr, status, error) {
            hideLoadingModal();
            
            console.log('=== AJAX ERROR DEBUG ===');
            console.log('Status:', xhr.status);
            console.log('Status Text:', xhr.statusText);
            console.log('Ready State:', xhr.readyState);
            console.log('Response Text:', xhr.responseText);
            console.log('Error:', error);
            console.log('Settings:', this);
            
            let errorMessage = 'حدث خطأ في التحديث';
            
            try {
                // Try to parse JSON response
                const jsonResponse = JSON.parse(xhr.responseText);
                console.log('Parsed JSON Response:', jsonResponse);
                
                if (jsonResponse.message) {
                    errorMessage = jsonResponse.message;
                }
            } catch (e) {
                console.log('Could not parse JSON response:', e);
            }
            
            if (xhr.status === 404) {
                errorMessage = 'الرابط غير موجود';
            } else if (xhr.status === 422) {
                errorMessage = 'بيانات غير صحيحة';
                if (xhr.responseJSON?.errors) {
                    console.log('Validation Errors:', xhr.responseJSON.errors);
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage += ': ' + errors.join(', ');
                }
            } else if (xhr.status === 500) {
                errorMessage = 'خطأ في الخادم - تحقق من سجلات النظام';
                // Since the product might still be updated, offer to reload
                setTimeout(() => {
                    if (confirm('قد يكون المنتج تم تحديثه رغم الخطأ. هل تريد إعادة تحميل الصفحة؟')) {
                        loadProducts(currentPage);
                    }
                }, 2000);
            } else if (xhr.status === 419) {
                errorMessage = 'انتهت صلاحية الجلسة - يرجى إعادة تحميل الصفحة';
            }
            
            showAlert(errorMessage, 'danger');
            console.log('======================');
        }
    });
}

// Delete product
function deleteProduct(id, name) {
    productToDelete = id;
    $('#deleteProductName').text(name);
    $('#deleteProductModal').modal('show');
}

// Confirm delete
function confirmDelete() {
    if (!productToDelete) return;

    $.ajax({
        url: `/admin/products/${productToDelete}`,
        type: 'DELETE',
        success: function(response) {
            $('#deleteProductModal').modal('hide');
            if (response.success) {
                showAlert('تم حذف المنتج بنجاح', 'success');
                loadProducts(currentPage);
            } else {
                showAlert(response.message || 'حدث خطأ في الحذف', 'danger');
            }
        },
        error: function() {
            $('#deleteProductModal').modal('hide');
            showAlert('حدث خطأ في حذف المنتج', 'danger');
        }
    });

    productToDelete = null;
}

// Enrich single product - Now shows preview first for user confirmation
function enrichProduct(id) {
    // Show the preview modal
    $('#enrichPreviewModal').modal('show');
    $('#enrichPreviewLoading').show();
    $('#enrichPreviewContent').hide();
    $('#enrichPreviewError').hide();
    $('#btnConfirmEnrichment').hide();
    $('#btnRejectEnrichment').hide();
    $('#enrichPreviewBookId').val(id);

    // Fetch preview data
    $.get(`/books/${id}/preview-enrich`)
        .done(function(response) {
            $('#enrichPreviewLoading').hide();

            if (response.success) {
                displayEnrichmentPreview(response);
                $('#enrichPreviewContent').show();
                $('#btnConfirmEnrichment').show();
                $('#btnRejectEnrichment').show();
            } else {
                $('#enrichPreviewErrorMessage').text(response.message || 'فشل في جلب بيانات المعاينة');
                $('#enrichPreviewError').show();
            }
        })
        .fail(function(xhr) {
            $('#enrichPreviewLoading').hide();
            let errorMessage = 'حدث خطأ في جلب بيانات المعاينة';

            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch (e) {}

            $('#enrichPreviewErrorMessage').text(errorMessage);
            $('#enrichPreviewError').show();
        });
}

// Display enrichment preview data in the modal
function displayEnrichmentPreview(response) {
    const preview = response.preview;
    const book = response.book;

    // Set title info
    $('#previewCurrentTitle').text(book.title || 'غير محدد');
    $('#previewApiTitle').text(response.api_book_title || 'غير محدد');
    $('#previewSearchMethod').text(response.search_method === 'ISBN' ? 'بحث بـ ISBN' : 'بحث بالعنوان');

    // Field labels in Arabic
    const fieldLabels = {
        'title': 'العنوان',
        'author': 'المؤلف',
        'description': 'الوصف',
        'page_count': 'عدد الصفحات',
        'publisher': 'دار النشر',
        'language': 'اللغة',
        'image': 'الصورة'
    };

    // Build preview table
    let tableHtml = '';
    for (const [field, data] of Object.entries(preview)) {
        if (field === 'image') continue; // Handle image separately

        const willUpdate = data.will_update;
        const rowClass = willUpdate ? 'table-success' : '';
        const updateIcon = willUpdate ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-minus text-muted"></i>';

        tableHtml += `
            <tr class="${rowClass}">
                <td><strong>${fieldLabels[field] || field}</strong></td>
                <td>${data.current || '<span class="text-muted">فارغ</span>'}</td>
                <td>${data.api || '<span class="text-muted">غير متوفر</span>'}</td>
                <td class="text-center">${updateIcon}</td>
            </tr>
        `;
    }
    $('#enrichPreviewTable').html(tableHtml);

    // Handle image preview
    if (preview.image && preview.image.api) {
        $('#previewImageSection').show();
        $('#previewCurrentImage').attr('src', '/' + (preview.image.current || 'images/books/default-book.png'));
        $('#previewApiImage').attr('src', preview.image.api);
    } else {
        $('#previewImageSection').hide();
    }
}

// Confirm and apply enrichment data
function confirmEnrichment() {
    const bookId = $('#enrichPreviewBookId').val();

    if (!bookId) {
        showAlert('معرف الكتاب مفقود', 'danger');
        return;
    }

    // Show loading
    $('#btnConfirmEnrichment').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري التطبيق...');
    $('#btnRejectEnrichment').prop('disabled', true);

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $.post(`/books/${bookId}/enrich`)
        .done(function(response) {
            $('#enrichPreviewModal').modal('hide');

            if (response.success) {
                showAlert('تم إثراء الكتاب بنجاح', 'success');
                loadProducts(currentPage);
            } else {
                showAlert(response.message || 'فشل في إثراء الكتاب', 'warning');
            }
        })
        .fail(function(xhr) {
            $('#enrichPreviewModal').modal('hide');
            let errorMessage = 'حدث خطأ في إثراء الكتاب';

            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch (e) {}

            showAlert(errorMessage, 'danger');
        })
        .always(function() {
            // Reset button states
            $('#btnConfirmEnrichment').prop('disabled', false).html('<i class="fas fa-check me-2"></i>تأكيد وتطبيق البيانات');
            $('#btnRejectEnrichment').prop('disabled', false);
        });
}

// Reject enrichment - just close the modal
function rejectEnrichment() {
    $('#enrichPreviewModal').modal('hide');
    showAlert('تم إلغاء عملية الإثراء', 'info');
}

// Optional: Add a function to check if a book is currently being processed
function checkEnrichmentStatus(id) {
    return $.get(`/books/${id}/status`)
        .then(function(response) {
            return response.api_data_status;
        })
        .catch(function() {
            return 'unknown';
        });
}

// Bulk enrich selected products
function bulkEnrichSelected() {
    updateSelectedProducts();
    
    if (selectedProducts.length === 0) {
        showAlert('يرجى تحديد منتجات للمعالجة', 'warning');
        return;
    }

    showLoadingModal();

    $.post('/admin/products/bulk-enrich', {
        product_ids: selectedProducts
    })
    .done(function(response) {
        hideLoadingModal();
        if (response.success) {
            showAlert(`تم إثراء ${response.enriched_count} منتج بنجاح`, 'success');
            loadProducts(currentPage);
            $('#selectAll').prop('checked', false);
        } else {
            showAlert(response.message || 'فشل في المعالجة المجمعة', 'warning');
        }
    })
    .fail(function() {
        hideLoadingModal();
        showAlert('حدث خطأ في المعالجة المجمعة', 'danger');
    });
}

// Show pending enrichment products
function showPendingEnrichment() {
    $('#statusFilter').val('pending');
    currentStatus = 'pending';
    currentPage = 1;
    loadProducts();
}

// Show/hide loading modal
function showLoadingModal() {
    $('#loadingModal').modal('show');
}

function hideLoadingModal() {
    $('#loadingModal').modal('hide');
}

// Add product form submission
$('#addproductform').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    //showLoadingModal();

    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            hideLoadingModal();
            if (response.success) {
                $('#addProductModal').modal('hide');
                $('#addproductform')[0].reset();
                showAlert('تم إضافة المنتج بنجاح', 'success');
                loadProducts(currentPage);
            } else {
                showAlert(response.message || 'حدث خطأ في الإضافة', 'danger');
            }
        },
        error: function(xhr) {
            hideLoadingModal();
            let errorMessage = 'حدث خطأ في إضافة المنتج';
            
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                errorMessage = Object.values(errors).flat().join('<br>');
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            showAlert(errorMessage, 'danger');
        }
    });
});

// Reset form when modal is hidden
$('#addProductModal').on('hidden.bs.modal', function() {
    $('#addproductform')[0].reset();
});

$('#editProductModal').on('hidden.bs.modal', function() {
    $('#editProductForm')[0].reset();
});

// Auto-resize textareas
$('textarea').on('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

// Image preview functionality
$('#productImage').on('change', function() {
    previewImage(this, 'imagePreview');
});

$('#editProductImage').on('change', function() {
    previewImage(this, 'editImagePreview');
});

function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let preview = $('#' + previewId);
            if (preview.length === 0) {
                preview = $('<img>', {
                    id: previewId,
                    class: 'img-thumbnail mt-2',
                    style: 'max-width: 200px; max-height: 200px;'
                });
                $(input).after(preview);
            }
            preview.attr('src', e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Keyboard shortcuts
$(document).on('keydown', function(e) {
    // Ctrl+N for new product
    if (e.ctrlKey && e.which === 78) {
        e.preventDefault();
        $('#addProductModal').modal('show');
    }
    
    // ESC to close modals
    if (e.which === 27) {
        $('.modal.show').modal('hide');
    }
});

// Auto-save functionality for edit form
let autoSaveTimeout;
$('#editProductForm input, #editProductForm textarea, #editProductForm select').on('input change', function() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(function() {
        // Could implement auto-save draft functionality here
        console.log('Auto-save triggered');
    }, 2000);
});

// Table row hover effects
$(document).on('mouseenter', '#productsTable tbody tr', function() {
    $(this).addClass('table-hover-highlight');
}).on('mouseleave', '#productsTable tbody tr', function() {
    $(this).removeClass('table-hover-highlight');
});

// Bulk actions
function bulkDelete() {
    updateSelectedProducts();
    
    if (selectedProducts.length === 0) {
        showAlert('يرجى تحديد منتجات للحذف', 'warning');
        return;
    }

    if (confirm(`هل أنت متأكد من حذف ${selectedProducts.length} منتج؟`)) {
        showLoadingModal();

        $.ajax({
            url: '/admin/products/bulk-delete',
            type: 'POST',
            data: {
                product_ids: selectedProducts,
                _method: 'DELETE'
            },
            success: function(response) {
                hideLoadingModal();
                if (response.success) {
                    showAlert(`تم حذف ${response.deleted_count} منتج بنجاح`, 'success');
                    loadProducts(currentPage);
                    $('#selectAll').prop('checked', false);
                } else {
                    showAlert(response.message || 'فشل في الحذف المجمع', 'danger');
                }
            },
            error: function() {
                hideLoadingModal();
                showAlert('حدث خطأ في الحذف المجمع', 'danger');
            }
        });
    }
}

// Export functionality
function exportProducts() {
    const params = new URLSearchParams({
        search: currentSearch,
        status: currentStatus
    });
    
    window.location.href = `/admin/products/export?${params.toString()}`;
}

// Print functionality
function printProducts() {
    window.print();
}

// Advanced search toggle
function toggleAdvancedSearch() {
    $('#advancedSearchPanel').slideToggle();
}

// Initialize tooltips
$(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
});

// Refresh products
function refreshProducts() {
    loadProducts(currentPage);
    showAlert('تم تحديث القائمة', 'info', 2000);
}

// Check for updates every 30 seconds
setInterval(function() {
    // Could implement real-time updates here
    // For now, just refresh if user is idle
    if (document.hidden === false) {
        // Refresh only if no modals are open
        if ($('.modal.show').length === 0) {
            // Uncomment to enable auto-refresh
            // loadProducts(currentPage);
        }
    }
}, 30000);

// Handle connection errors gracefully
$(document).ajaxError(function(event, xhr, settings) {
    if (xhr.status === 419) {
        showAlert('انتهت صلاحية الجلسة. يرجى إعادة تحميل الصفحة.', 'warning');
    } else if (xhr.status === 500) {
        showAlert('خطأ في الخادم. يرجى المحاولة لاحقاً.', 'danger');
    } else if (xhr.status === 0) {
        showAlert('فقدان الاتصال بالإنترنت.', 'warning');
    }
});

// Service worker registration for offline functionality
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(function(registration) {
            console.log('SW registered successfully');
        })
        .catch(function(registrationError) {
            console.log('SW registration failed');
        });
}