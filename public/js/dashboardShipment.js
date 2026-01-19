let currentItem = {
    book_id: null,
    isbn: null,
    title: null,
    author_id: null,
    author_name: null,
    publishing_house_id: null,
    publisher_name: null
};

let shipmentItems = [];
let itemIndex = 0;

// ===== BOOK SEARCH =====
document.getElementById('searchBtn').addEventListener('click', searchBooks);
document.getElementById('bookSearchInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') searchBooks();
});

function searchBooks() {
    const query = document.getElementById('bookSearchInput').value.trim();
    if (query.length < 2) {
        alert('أدخل على الأقل حرفين للبحث');
        return;
    }

    fetch(`/admin/search-book?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('searchResults').querySelector('.list-group');
            resultsDiv.innerHTML = '';

            if (data.length === 0) {
                resultsDiv.innerHTML = `
                    <div class="list-group-item">
                        <p class="text-muted">لم يتم العثور على كتب</p>
                        <button type="button" class="btn btn-sm btn-primary mt-2" id="createNewBookBtn">
                            <i class="fas fa-plus"></i>إنشاء كتاب جديد
                        </button>
                    </div>
                `;
                document.getElementById('searchResults').style.display = 'block';
                document.getElementById('createNewBookBtn').addEventListener('click', showNewBookForm);
                return;
            }

            data.forEach(book => {
                const item = document.createElement('div');
                item.className = 'list-group-item';
                item.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${book.title}</strong>
                            <br><small class="text-muted">ISBN: ${book.ISBN}</small>
                            <br><small>المؤلف: ${book.author}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success">الكمية: ${book.Quantity}</span>
                            <br><span class="badge bg-info">${book.price} DH</span>
                        </div>
                    </div>
                `;
                item.addEventListener('click', () => selectExistingBook(book));
                resultsDiv.appendChild(item);
            });

            // Add "Create New" option at the end
            const newItem = document.createElement('div');
            newItem.className = 'list-group-item';
            newItem.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-primary w-100" id="createNewBtn">
                    <i class="fas fa-plus"></i>إنشاء كتاب جديد بدلاً من ذلك
                </button>
            `;
            resultsDiv.appendChild(newItem);
            document.getElementById('createNewBtn').addEventListener('click', showNewBookForm);

            document.getElementById('searchResults').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء البحث');
        });
}

function selectExistingBook(book) {
    currentItem = {
        book_id: book.id,
        isbn: book.ISBN,
        title: book.title,
        author_id: null,
        author_name: book.author,
        publishing_house_id: null,
        publisher_name: null
    };

    document.getElementById('existingBookTitle').textContent = book.title;
    document.getElementById('existingBookISBN').textContent = book.ISBN;
    document.getElementById('existingBookAuthor').textContent = book.author;
    document.getElementById('existingBookQuantity').textContent = book.Quantity;
    document.getElementById('existingBookPrice').textContent = book.price;

    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('newBookForm').style.display = 'none';
    document.getElementById('existingBookInfo').style.display = 'block';

    document.getElementById('selectExistingBtn').onclick = proceedToItemDetails;
    document.getElementById('cancelExistingBtn').onclick = resetSearchPhase;
}

function showNewBookForm() {
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('existingBookInfo').style.display = 'none';
    document.getElementById('newBookForm').style.display = 'block';
    document.getElementById('newBookISBN').focus();
}
// New Book Form - Proceed Button
document.getElementById('proceedNewBookBtn').addEventListener('click', function() {
    const isbn = document.getElementById('newBookISBN').value.trim();
    const title = document.getElementById('newBookTitle').value.trim();
    const sellingPrice = document.getElementById('itemSellingPrice').value;

    if (!isbn) {
        alert('الرجاء إدخال ISBN');
        return;
    }
    if (!title) {
        alert('الرجاء إدخال عنوان الكتاب');
        return;
    }

    currentItem = {
        book_id: null,
        isbn: isbn,
        title: title,
        author_id: document.getElementById('newBookAuthorId').value || null,
        author_name: document.getElementById('selectedAuthorName').textContent.replace('✓ تم اختيار: ', ''),
        publishing_house_id: document.getElementById('newBookPublisherId').value || null,
        publisher_name: document.getElementById('selectedPublisherName').textContent.replace('✓ تم اختيار: ', '')
    };

    proceedToItemDetails();
});

// Cancel New Book Form
document.getElementById('cancelNewBookBtn').addEventListener('click', function() {
    document.getElementById('newBookForm').style.display = 'none';
    document.getElementById('searchPhase').style.display = 'block';
    resetSearchPhase();
});

// ===== AUTHOR SEARCH =====
document.getElementById('searchAuthorBtn').addEventListener('click', searchAuthors);
document.getElementById('newBookAuthorSearch').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') searchAuthors();
});

function searchAuthors() {
    const query = document.getElementById('newBookAuthorSearch').value.trim();
    if (query.length < 2) return;

    fetch(`/admin/search-authors?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('authorSearchResults');
            resultsDiv.innerHTML = '';

            if (data.length === 0) {
                resultsDiv.innerHTML = '<div class="list-group-item text-muted">لم يتم العثور على مؤلفين</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            data.forEach(author => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `${author.name} <small class="text-muted">(${author.nationality || 'غير محدد'})</small>`;
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    selectAuthor(author);
                });
                resultsDiv.appendChild(item);
            });

            resultsDiv.style.display = 'block';
        });
}

function selectAuthor(author) {
    currentItem.author_id = author.id;
    currentItem.author_name = author.name;
    document.getElementById('newBookAuthorId').value = author.id;
    document.getElementById('selectedAuthorName').textContent = `✓ تم اختيار: ${author.name}`;
    document.getElementById('authorSearchResults').style.display = 'none';
}

// ===== PUBLISHER SEARCH =====
document.getElementById('searchPublisherBtn').addEventListener('click', searchPublishers);
document.getElementById('newBookPublisherSearch').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') searchPublishers();
});

function searchPublishers() {
    const query = document.getElementById('newBookPublisherSearch').value.trim();
    if (query.length < 2) return;

    fetch(`/admin/search-publishers?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('publisherSearchResults');
            resultsDiv.innerHTML = '';

            if (data.length === 0) {
                resultsDiv.innerHTML = '<div class="list-group-item text-muted">لم يتم العثور على دور نشر</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            data.forEach(publisher => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `${publisher.name} <small class="text-muted">(${publisher.country || 'غير محدد'})</small>`;
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    selectPublisher(publisher);
                });
                resultsDiv.appendChild(item);
            });

            resultsDiv.style.display = 'block';
        });
}

function selectPublisher(publisher) {
    currentItem.publishing_house_id = publisher.id;
    currentItem.publisher_name = publisher.name;
    document.getElementById('newBookPublisherId').value = publisher.id;
    document.getElementById('selectedPublisherName').textContent = `✓ تم اختيار: ${publisher.name}`;
    document.getElementById('publisherSearchResults').style.display = 'none';
}

// ===== ITEM DETAILS PHASE =====
function proceedToItemDetails() {
    document.getElementById('searchPhase').style.display = 'none';
    document.getElementById('itemDetailsPhase').style.display = 'block';

    let details = `ISBN: ${currentItem.isbn}`;
    if (currentItem.author_name) details += ` | المؤلف: ${currentItem.author_name}`;
    if (currentItem.publisher_name) details += ` | الناشر: ${currentItem.publisher_name}`;

    document.getElementById('itemBookTitle').textContent = currentItem.title;
    document.getElementById('itemBookDetails').textContent = details;

    // Set default selling price if existing book
    if (currentItem.book_id) {
        fetch(`/admin/search-book?q=${currentItem.isbn}`)
            .then(r => r.json())
            .then(data => {
                if (data.length > 0) {
                    document.getElementById('itemSellingPrice').value = data[0].price;
                }
            });
    }
}

document.getElementById('addItemBtn').addEventListener('click', addItemToShipment);
document.getElementById('backToSearchBtn').addEventListener('click', () => {
    document.getElementById('itemDetailsPhase').style.display = 'none';
    document.getElementById('searchPhase').style.display = 'block';
    resetSearchPhase();
});

function addItemToShipment() {
    const quantity = parseInt(document.getElementById('itemQuantity').value);
    const costPrice = document.getElementById('itemCostPrice').value;
    const sellingPrice = document.getElementById('itemSellingPrice').value;

    if (!quantity || quantity < 1) {
        alert('الرجاء إدخال كمية صحيحة');
        return;
    }
    if (!sellingPrice) {
        alert('الرجاء إدخال سعر البيع');
        return;
    }

    const item = {
        index: itemIndex++,
        book_id: currentItem.book_id,
        isbn: currentItem.isbn,
        title: currentItem.title,
        author_id: currentItem.author_id,
        author_name: currentItem.author_name,
        publishing_house_id: currentItem.publishing_house_id,
        publisher_name: currentItem.publisher_name,
        quantity_received: quantity,
        cost_price: costPrice || null,
        selling_price: sellingPrice
    };

    shipmentItems.push(item);
    renderShipmentItems();
    resetForNextItem();
}

function renderShipmentItems() {
    const container = document.getElementById('shipmentItemsList');
    
    if (shipmentItems.length === 0) {
        container.innerHTML = '<p class="text-muted text-center" id="emptyItemsMessage">لم تضف أي كتب حتى الآن</p>';
        document.getElementById('saveShipmentBtn').disabled = true;
        return;
    }

    container.innerHTML = '';
    document.getElementById('saveShipmentBtn').disabled = false;

    shipmentItems.forEach((item, idx) => {
        const card = document.createElement('div');
        card.className = 'shipment-item-card';
        card.innerHTML = `
            <div class="item-card-details">
                <p><strong>${item.title}</strong></p>
                <p><small class="text-muted">ISBN: ${item.isbn}</small></p>
                <p><small>الكمية: <strong>${item.quantity_received}</strong> | السعر: <strong>${item.selling_price} DH</strong></small></p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-warning" onclick="editItem(${idx})">
                    <i class="fas fa-edit"></i>تعديل
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteItem(${idx})">
                    <i class="fas fa-trash"></i>حذف
                </button>
            </div>
        `;
        container.appendChild(card);
    });

    updateHiddenFormFields();
}

function updateHiddenFormFields() {
    const container = document.getElementById('itemsDataContainer');
    container.innerHTML = '';

    shipmentItems.forEach((item, idx) => {
        const fields = `
            <input type="hidden" name="items[${idx}][book_id]" value="${item.book_id || ''}">
            <input type="hidden" name="items[${idx}][isbn]" value="${item.isbn}">
            <input type="hidden" name="items[${idx}][title]" value="${item.title}">
            <input type="hidden" name="items[${idx}][author_id]" value="${item.author_id || ''}">
            <input type="hidden" name="items[${idx}][publishing_house_id]" value="${item.publishing_house_id || ''}">
            <input type="hidden" name="items[${idx}][quantity_received]" value="${item.quantity_received}">
            <input type="hidden" name="items[${idx}][cost_price]" value="${item.cost_price || ''}">
            <input type="hidden" name="items[${idx}][selling_price]" value="${item.selling_price}">
        `;
        container.insertAdjacentHTML('beforeend', fields);
    });
}

function deleteItem(idx) {
    if (confirm('هل أنت متأكد من حذف هذا العنصر؟')) {
        shipmentItems.splice(idx, 1);
        renderShipmentItems();
    }
}

function editItem(idx) {
    // TODO: Implement edit functionality
    alert('تحت التطوير');
}

function resetSearchPhase() {
    document.getElementById('bookSearchInput').value = '';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('existingBookInfo').style.display = 'none';
    document.getElementById('newBookForm').style.display = 'none';
}

function resetForNextItem() {
    currentItem = {
        book_id: null,
        isbn: null,
        title: null,
        author_id: null,
        author_name: null,
        publishing_house_id: null,
        publisher_name: null
    };

    document.getElementById('itemDetailsPhase').style.display = 'none';
    document.getElementById('searchPhase').style.display = 'block';
    document.getElementById('bookSearchInput').value = '';
    document.getElementById('bookSearchInput').focus();
    document.getElementById('itemQuantity').value = '1';
    document.getElementById('itemCostPrice').value = '';
    document.getElementById('itemSellingPrice').value = '';
    document.getElementById('newBookISBN').value = '';
    document.getElementById('newBookTitle').value = '';
    document.getElementById('newBookAuthorSearch').value = '';
    document.getElementById('newBookPublisherSearch').value = '';
    document.getElementById('newBookAuthorId').value = '';
    document.getElementById('newBookPublisherId').value = '';
    document.getElementById('selectedAuthorName').textContent = '';
    document.getElementById('selectedPublisherName').textContent = '';
}

// Form submission
document.getElementById('shipmentForm').addEventListener('submit', function(e) {
    
    
    if (shipmentItems.length === 0) {
        e.preventDefault();
        alert('الرجاء إضافة كتاب واحد على الأقل');
        return;
    }

    const submitBtn = document.getElementById('saveShipmentBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...';

    this.submit();
});

// Reset modal when closed
document.getElementById('addShipmentModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('shipmentForm').reset();
    shipmentItems = [];
    itemIndex = 0;
    renderShipmentItems();
    resetSearchPhase();
    document.getElementById('saveShipmentBtn').disabled = true;
    document.getElementById('saveShipmentBtn').innerHTML = '<i class="fas fa-save me-2"></i>حفظ الشحنة';
});

// Set action URL
//document.getElementById('shipmentForm').action = "{{ route('shipments.store') }}";