// Global variables
let currentPage = 1;
let currentSearch = '';
let currentStatus = '';
let currentApiStatus = '';
let enrichmentApiData = null;

// Initialize on page load
$(document).ready(function () {
    loadAuthors();
    initializeEventListeners();
});

function initializeEventListeners() {
    let searchTimeout;
    $('#searchInput').on('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = $(this).val();
            currentPage = 1;
            loadAuthors();
        }, 400);
    });

    $('#statusFilter').on('change', function () {
        currentStatus = $(this).val();
        currentPage = 1;
        loadAuthors();
    });

    $('#apiStatusFilter').on('change', function () {
        currentApiStatus = $(this).val();
        currentPage = 1;
        loadAuthors();
    });

    $('#selectAllFields').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.enrich-field-checkbox').prop('checked', isChecked);
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
}

// =================== Load Authors ===================
function loadAuthors(page = 1) {
    currentPage = page;

    $.get('/admin/authors/api', {
        page: page,
        search: currentSearch,
        status: currentStatus,
        api_status: currentApiStatus
    })
    .done(function (response) {
        if (response.success) {
            renderAuthorsTable(response.data.data);
            renderPagination(response.data);
            updateStatsCards(response.stats);
        } else {
            showAlert('حدث خطأ في تحميل البيانات', 'danger');
        }
    })
    .fail(function () {
        showAlert('خطأ في الاتصال بالخادم', 'danger');
    });
}

// =================== Render Table ===================
function renderAuthorsTable(authors) {
    const tbody = $('#authorsTableBody');
    tbody.empty();

    if (!authors || authors.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="9" class="text-center py-4">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3 d-block"></i>
                    <p class="text-muted">لا يوجد مؤلفين</p>
                </td>
            </tr>
        `);
        return;
    }

    authors.forEach(function (author, index) {
        const imageHtml = author.profile_image
            ? `<img src="/storage/${escapeHtml(author.profile_image)}" class="author-img" alt="${escapeHtml(author.name)}">`
            : `<div class="author-img-placeholder">${escapeHtml(author.name.charAt(0))}</div>`;

        const statusBadge = author.status === 'active'
            ? '<span class="badge-status badge-active">نشط</span>'
            : '<span class="badge-status badge-inactive">غير نشط</span>';

        const apiBadge = author.api_id
            ? '<span class="badge-status badge-enriched">معالج</span>'
            : '<span class="badge-status badge-pending">غير معالج</span>';

        const birthDate = author.birth_date
            ? new Date(author.birth_date).getFullYear()
            : '-';

        const booksCount = author.primary_books_count || 0;

        tbody.append(`
            <tr>
                <td>${((currentPage - 1) * 15) + index + 1}</td>
                <td>${imageHtml}</td>
                <td><strong>${escapeHtml(author.name)}</strong></td>
                <td>${escapeHtml(author.nationality || '-')}</td>
                <td>${birthDate}</td>
                <td><span class="books-count">${booksCount}</span></td>
                <td>${apiBadge}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn-action view" onclick="viewAuthor(${author.id})" title="عرض">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action edit" onclick="editAuthor(${author.id})" title="تعديل">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action enrich" onclick="enrichAuthor(${author.id})" title="معالجة API">
                        <i class="fas fa-magic"></i>
                    </button>
                    <button class="btn-action delete" onclick="deleteAuthor(${author.id}, '${escapeHtml(author.name)}')" title="حذف">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

// =================== Pagination ===================
function renderPagination(data) {
    const container = $('#paginationContainer');
    container.empty();

    if (data.last_page <= 1) return;

    // Previous
    container.append(`
        <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadAuthors(${data.current_page - 1}); return false;">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);

    // Pages
    let start = Math.max(1, data.current_page - 2);
    let end = Math.min(data.last_page, data.current_page + 2);

    if (start > 1) {
        container.append(`<li class="page-item"><a class="page-link" href="#" onclick="loadAuthors(1); return false;">1</a></li>`);
        if (start > 2) container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
    }

    for (let i = start; i <= end; i++) {
        container.append(`
            <li class="page-item ${i === data.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadAuthors(${i}); return false;">${i}</a>
            </li>
        `);
    }

    if (end < data.last_page) {
        if (end < data.last_page - 1) container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        container.append(`<li class="page-item"><a class="page-link" href="#" onclick="loadAuthors(${data.last_page}); return false;">${data.last_page}</a></li>`);
    }

    // Next
    container.append(`
        <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadAuthors(${data.current_page + 1}); return false;">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
}

// =================== Stats ===================
function updateStatsCards(stats) {
    $('#totalAuthorsStat').text(stats.total || 0);
    $('#activeAuthorsStat').text(stats.active || 0);
    $('#enrichedAuthorsStat').text(stats.enriched || 0);
    $('#pendingAuthorsStat').text(stats.pending || 0);
}

// =================== View Author ===================
function viewAuthor(id) {
    $('#viewAuthorContent').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
    new bootstrap.Modal(document.getElementById('viewAuthorModal')).show();

    $.get(`/admin/authors/${id}`)
    .done(function (response) {
        if (response.success) {
            renderAuthorDetails(response.author, response.books_by_type);
        }
    })
    .fail(function () {
        $('#viewAuthorContent').html('<div class="alert alert-danger">فشل في تحميل البيانات</div>');
    });
}

function renderAuthorDetails(author, booksByType) {
    const imageHtml = author.profile_image
        ? `<img src="/storage/${escapeHtml(author.profile_image)}" class="author-profile-img">`
        : `<div class="author-profile-img-placeholder">${escapeHtml(author.name.charAt(0))}</div>`;

    const typeLabels = {
        'primary': 'مؤلف رئيسي',
        'co-author': 'مؤلف مشارك',
        'translator': 'مترجم',
        'editor': 'محرر',
        'illustrator': 'رسام'
    };

    let typeBadgesHtml = '';
    for (const [type, count] of Object.entries(booksByType)) {
        typeBadgesHtml += `<span class="type-badge ${type}">${typeLabels[type] || type}: ${count}</span>`;
    }

    let booksHtml = '';
    if (author.primary_books && author.primary_books.length > 0) {
        author.primary_books.forEach(function (book) {
            const bookImg = book.image ? `/${book.image}` : '/images/book-placeholder.png';
            booksHtml += `
                <div class="author-book-item">
                    <img src="${bookImg}" alt="">
                    <div>
                        <strong>${escapeHtml(book.title)}</strong>
                        <br><small class="text-muted">${book.price} د.م</small>
                    </div>
                </div>
            `;
        });
    } else {
        booksHtml = '<p class="text-muted">لا توجد كتب مرتبطة</p>';
    }

    const birthDate = author.birth_date ? new Date(author.birth_date).toLocaleDateString('ar-SA') : 'غير محدد';
    const deathDate = author.death_date ? new Date(author.death_date).toLocaleDateString('ar-SA') : (author.birth_date ? 'على قيد الحياة' : 'غير محدد');

    $('#viewAuthorContent').html(`
        <div class="author-profile-header">
            ${imageHtml}
            <div class="author-profile-info">
                <h4>${escapeHtml(author.name)}</h4>
                <div class="author-detail-row"><i class="fas fa-globe"></i> ${escapeHtml(author.nationality || 'غير محدد')}</div>
                <div class="author-detail-row"><i class="fas fa-birthday-cake"></i> ${birthDate}</div>
                <div class="author-detail-row"><i class="fa-solid fa-dove"></i> ${deathDate}</div>
                ${author.website ? `<div class="author-detail-row"><i class="fas fa-link"></i> <a href="${escapeHtml(author.website)}" target="_blank">${escapeHtml(author.website)}</a></div>` : ''}
                <div class="author-detail-row"><i class="fas fa-book"></i> <strong>${author.primary_books_count || 0}</strong> كتاب</div>
            </div>
        </div>

        ${author.biography ? `
        <div class="mb-3">
            <h6><i class="fas fa-align-right me-2"></i>السيرة الذاتية</h6>
            <p style="line-height: 1.8; color: #555;">${escapeHtml(author.biography)}</p>
        </div>` : ''}

        ${typeBadgesHtml ? `
        <div class="mb-3">
            <h6><i class="fas fa-tags me-2"></i>الأدوار</h6>
            ${typeBadgesHtml}
        </div>` : ''}

        <div>
            <h6><i class="fas fa-book-open me-2"></i>الكتب (${author.primary_books_count || 0})</h6>
            ${booksHtml}
        </div>
    `);
}

// =================== Edit Author ===================
function editAuthor(id) {
    $.get(`/admin/authors/${id}`)
    .done(function (response) {
        if (response.success) {
            const a = response.author;
            $('#editAuthorId').val(a.id);
            $('#editAuthorName').val(a.name);
            $('#editAuthorNationality').val(a.nationality || '');
            $('#editAuthorBiography').val(a.biography || '');
            $('#editAuthorBirthDate').val(a.birth_date ? a.birth_date.split('T')[0] : '');
            $('#editAuthorDeathDate').val(a.death_date ? a.death_date.split('T')[0] : '');
            $('#editAuthorStatus').val(a.status || 'active');
            $('#editAuthorWebsite').val(a.website || '');

            new bootstrap.Modal(document.getElementById('editAuthorModal')).show();
        }
    })
    .fail(function () {
        showAlert('فشل في تحميل بيانات المؤلف', 'danger');
    });
}

function saveAuthor() {
    const id = $('#editAuthorId').val();
    const data = {
        name: $('#editAuthorName').val(),
        nationality: $('#editAuthorNationality').val() || null,
        biography: $('#editAuthorBiography').val() || null,
        birth_date: $('#editAuthorBirthDate').val() || null,
        death_date: $('#editAuthorDeathDate').val() || null,
        status: $('#editAuthorStatus').val(),
        website: $('#editAuthorWebsite').val() || null,
    };

    $.ajax({
        url: `/admin/authors/${id}`,
        method: 'PUT',
        data: data,
    })
    .done(function (response) {
        if (response.success) {
            bootstrap.Modal.getInstance(document.getElementById('editAuthorModal')).hide();
            showAlert(response.message, 'success');
            loadAuthors(currentPage);
        } else {
            showAlert(response.message || 'حدث خطأ', 'danger');
        }
    })
    .fail(function (xhr) {
        const errors = xhr.responseJSON?.errors;
        if (errors) {
            const msg = Object.values(errors).flat().join('<br>');
            showAlert(msg, 'danger');
        } else {
            showAlert('فشل في حفظ التغييرات', 'danger');
        }
    });
}

// =================== Delete Author ===================
function deleteAuthor(id, name) {
    $('#deleteAuthorId').val(id);
    $('#deleteAuthorName').text(name);
    new bootstrap.Modal(document.getElementById('deleteAuthorModal')).show();
}

function confirmDelete() {
    const id = $('#deleteAuthorId').val();

    $.ajax({
        url: `/admin/authors/${id}`,
        method: 'DELETE',
    })
    .done(function (response) {
        bootstrap.Modal.getInstance(document.getElementById('deleteAuthorModal')).hide();
        if (response.success) {
            showAlert(response.message, 'success');
            loadAuthors(currentPage);
        } else {
            showAlert(response.message, 'danger');
        }
    })
    .fail(function (xhr) {
        bootstrap.Modal.getInstance(document.getElementById('deleteAuthorModal')).hide();
        showAlert(xhr.responseJSON?.message || 'فشل في حذف المؤلف', 'danger');
    });
}

// =================== Enrichment ===================
function enrichAuthor(id) {
    enrichmentApiData = null;
    $('#enrichPreviewAuthorId').val(id);
    $('#enrichPreviewLoading').show();
    $('#enrichPreviewContent').hide();
    $('#enrichPreviewError').hide();
    $('#btnConfirmEnrichment').hide();

    new bootstrap.Modal(document.getElementById('enrichPreviewModal')).show();

    $.get(`/admin/authors/${id}/preview-enrich`)
    .done(function (response) {
        $('#enrichPreviewLoading').hide();

        if (response.success) {
            enrichmentApiData = response.api_data;
            renderEnrichmentPreview(response.current, response.api_data);
            $('#enrichPreviewContent').show();
            $('#btnConfirmEnrichment').show();
        } else {
            $('#enrichPreviewError').show();
            $('#enrichPreviewErrorMessage').text(response.message);
        }
    })
    .fail(function () {
        $('#enrichPreviewLoading').hide();
        $('#enrichPreviewError').show();
        $('#enrichPreviewErrorMessage').text('فشل في الاتصال بالخادم');
    });
}

function renderEnrichmentPreview(current, apiData) {
    $('#previewCurrentName').text(current.name);
    $('#previewApiName').text(apiData.search_match_name || apiData.api_name || '-');

    const tbody = $('#enrichPreviewTable');
    tbody.empty();

    const fields = [
        {
            key: 'biography', label: 'السيرة الذاتية',
            current: current.biography ? current.biography.substring(0, 100) + '...' : '-',
            api: apiData.biography ? apiData.biography.substring(0, 100) + '...' : null
        },
        {
            key: 'birth_date', label: 'تاريخ الميلاد',
            current: current.birth_date || '-',
            api: apiData.birth_date_raw || apiData.birth_date || null
        },
        {
            key: 'death_date', label: 'تاريخ الوفاة',
            current: current.death_date || '-',
            api: apiData.death_date_raw || apiData.death_date || null
        },
        {
            key: 'nationality', label: 'الجنسية',
            current: current.nationality || '-',
            api: apiData.nationality || null
        },
        {
            key: 'website', label: 'الموقع',
            current: current.website || '-',
            api: apiData.website || null
        },
        {
            key: 'photo', label: 'الصورة',
            current: current.profile_image ? 'موجودة' : '-',
            api: apiData.photo_url ? 'متوفرة' : null
        }
    ];

    fields.forEach(function (field) {
        const hasApi = field.api !== null && field.api !== undefined;
        const checked = hasApi ? 'checked' : '';
        const disabled = !hasApi ? 'disabled' : '';

        tbody.append(`
            <tr>
                <td><input type="checkbox" class="form-check-input enrich-field-checkbox"
                    data-field="${field.key}" ${checked} ${disabled}></td>
                <td><strong>${field.label}</strong></td>
                <td>${escapeHtml(String(field.current))}</td>
                <td>${hasApi ? escapeHtml(String(field.api)) : '<span class="text-muted">غير متوفر</span>'}</td>
            </tr>
        `);
    });

    // Image preview
    if (apiData.photo_url) {
        $('#previewImageSection').show();
        if (current.profile_image) {
            $('#previewCurrentImage').attr('src', '/storage/' + current.profile_image).show();
        } else {
            $('#previewCurrentImage').hide();
        }
        $('#previewApiImage').attr('src', apiData.photo_url);
    } else {
        $('#previewImageSection').hide();
    }

    // Extra info
    if (apiData.work_count) {
        tbody.append(`
            <tr class="table-info">
                <td colspan="4">
                    <i class="fas fa-info-circle me-2"></i>
                    عدد الأعمال في Open Library: <strong>${apiData.work_count}</strong>
                    ${apiData.top_subjects ? ' | المواضيع: ' + apiData.top_subjects.join(', ') : ''}
                </td>
            </tr>
        `);
    }

    if (apiData.wikipedia_url) {
        tbody.append(`
            <tr class="table-info">
                <td colspan="4">
                    <i class="fab fa-wikipedia-w me-2"></i>
                    المصدر: <a href="${escapeHtml(apiData.wikipedia_url)}" target="_blank">ويكيبيديا العربية</a>
                </td>
            </tr>
        `);
    }
}

function confirmEnrichment() {
    const id = $('#enrichPreviewAuthorId').val();
    const selectedFields = [];

    $('.enrich-field-checkbox:checked:not(:disabled)').each(function () {
        selectedFields.push($(this).data('field'));
    });

    if (selectedFields.length === 0) {
        showAlert('يرجى اختيار حقل واحد على الأقل', 'warning');
        return;
    }

    $('#btnConfirmEnrichment').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري التطبيق...');

    $.ajax({
        url: `/admin/authors/${id}/apply-enrich`,
        method: 'POST',
        data: {
            fields: selectedFields,
            api_data: enrichmentApiData,
        },
    })
    .done(function (response) {
        bootstrap.Modal.getInstance(document.getElementById('enrichPreviewModal')).hide();
        if (response.success) {
            showAlert(response.message, 'success');
            loadAuthors(currentPage);
        } else {
            showAlert(response.message, 'danger');
        }
    })
    .fail(function () {
        showAlert('فشل في تطبيق البيانات', 'danger');
    })
    .always(function () {
        $('#btnConfirmEnrichment').prop('disabled', false).html('<i class="fas fa-check me-2"></i>تأكيد وتطبيق البيانات');
    });
}

// =================== Import from Books ===================
function importFromBooks() {
    const modal = new bootstrap.Modal(document.getElementById('importResultsModal'));
    $('#importResultsContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary mb-3"></div>
            <p>جاري استيراد المؤلفين من الكتب...</p>
            <small class="text-muted">قد يستغرق هذا بعض الوقت حسب عدد الكتب</small>
        </div>
    `);
    modal.show();

    $.post('/admin/authors/import-from-books')
    .done(function (response) {
        if (response.success) {
            let html = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    ${escapeHtml(response.message)}
                </div>
                <div class="row text-center mb-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="fw-bold fs-4 text-success">${response.created}</div>
                            <small>مؤلف جديد</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="fw-bold fs-4 text-primary">${response.linked}</div>
                            <small>كتاب تم ربطه</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded">
                            <div class="fw-bold fs-4 text-warning">${response.duplicates.length}</div>
                            <small>تشابه محتمل</small>
                        </div>
                    </div>
                </div>
            `;

            if (response.duplicates.length > 0) {
                html += '<h6 class="mt-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>مؤلفين متشابهين يحتاجون مراجعة:</h6>';

                response.duplicates.forEach(function (dup) {
                    html += `<div class="duplicate-card">
                        <h6>"${escapeHtml(dup.name)}" <small class="text-muted">(${dup.books_count} كتاب)</small></h6>
                        <p class="mb-2">مشابه لـ:</p>`;

                    dup.similar.forEach(function (sim) {
                        html += `
                        <div class="similar-author-item">
                            <div>
                                <strong>${escapeHtml(sim.name)}</strong>
                                <small class="text-muted">(${sim.books_count} كتاب)</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="similarity-bar">
                                    <div class="similarity-fill" style="width: ${sim.similarity}%; background: ${sim.similarity >= 90 ? '#e74c3c' : '#f39c12'};"></div>
                                </div>
                                <small>${sim.similarity}%</small>
                                <button class="btn btn-sm btn-outline-primary" onclick="resolveDuplicate('merge', '${escapeJs(dup.name)}', ${sim.id})">
                                    <i class="fas fa-link"></i> دمج
                                </button>
                            </div>
                        </div>`;
                    });

                    html += `
                        <button class="btn btn-sm btn-outline-success mt-2" onclick="resolveDuplicate('create', '${escapeJs(dup.name)}')">
                            <i class="fas fa-plus"></i> إنشاء كمؤلف جديد
                        </button>
                    </div>`;
                });
            }

            $('#importResultsContent').html(html);
            loadAuthors(currentPage);
        } else {
            $('#importResultsContent').html('<div class="alert alert-danger">' + escapeHtml(response.message) + '</div>');
        }
    })
    .fail(function () {
        $('#importResultsContent').html('<div class="alert alert-danger">فشل في عملية الاستيراد</div>');
    });
}

function resolveDuplicate(action, name, existingAuthorId) {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    $.post('/admin/authors/resolve-duplicate', {
        action: action,
        name: name,
        existing_author_id: existingAuthorId || null,
    })
    .done(function (response) {
        if (response.success) {
            // Remove this duplicate card
            $(btn).closest('.duplicate-card').fadeOut(300, function () {
                $(this).remove();
            });
            showAlert(response.message, 'success');
            loadAuthors(currentPage);
        } else {
            showAlert(response.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .fail(function () {
        showAlert('فشل في العملية', 'danger');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

// =================== Utilities ===================
function showAlert(message, type = 'info') {
    const alert = $(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    $('#alertContainer').append(alert);
    setTimeout(() => alert.alert('close'), 5000);
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeJs(str) {
    if (!str) return '';
    return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}
