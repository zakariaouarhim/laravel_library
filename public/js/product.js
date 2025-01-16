// Fetch products from the backend
async function fetchProducts() {
  try {
    const response = await fetch('/Dashbord_Admin/Product/data');
    if (!response.ok) {
      throw new Error('Failed to fetch products');
    }
    const data = await response.json();
    return data;
  } catch (error) {
    console.error('Error fetching products:', error);
    alert('حدث خطأ أثناء جلب البيانات. يرجى المحاولة مرة أخرى.');
    return [];
  }
}
async function AddProduct() {
  productName=document.getElementById("productName");
  productDescription=document.getElementById("productDescription");
  productPrice=document.getElementById("productPrice");
  productNumPages=document.getElementById("productNumPages");
  productLanguage=document.getElementById("productLanguage");
  ProductPublishingHouse=document.getElementById("ProductPublishingHouse");
  productIsbn=document.getElementById("productIsbn");
  
}

// Render products in the table
async function renderProducts() {
  const products = await fetchProducts();
  const tbody = document.getElementById('productsTableBody');
  tbody.innerHTML = products
    .map(
      (product) => `
      <tr>
        <td>${product.id}</td>
        <td><img src="/${product.image}" alt="${product.title}" class="card-img-top" loading="lazy"></td>
        <td>${product.title}</td>
        <td>${product.description}</td>
        <td>${product.price} ريال</td>
        <td>${product.author}</td>
        <td>${product.Page_Num}</td>
        <td>${product.Langue}</td>
        <td>${product.ISBN}</td>
        <td class="action-buttons">
          <button class="btn btn-sm btn-success" onclick="editProduct(${product.id})">
            <span data-feather="edit-2"></span> تعديل
          </button>
          <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
            <span data-feather="trash-2"></span> حذف
          </button>
        </td>
      </tr>
    `
    )
    .join('');
  feather.replace();
}

// Setup search functionality
function setupSearch() {
  const searchInput = document.getElementById('searchInput');
  searchInput.addEventListener('input', async function () {
    const searchTerm = this.value.toLowerCase();
    const products = await fetchProducts();
    const filteredProducts = products.filter(
      (product) =>
        product.title.toLowerCase().includes(searchTerm) ||
        product.description.toLowerCase().includes(searchTerm)
    );
    renderFilteredProducts(filteredProducts);
  });
}

// Render filtered products
function renderFilteredProducts(filteredProducts) {
  const tbody = document.getElementById('productsTableBody');
  tbody.innerHTML = filteredProducts
    .map(
      (product) => `
      <tr>
        <td>${product.id}</td>
        <td><img src="/${product.image}" alt="${product.title}" class="card-img-top" loading="lazy"></td>
        <td>${product.title}</td>
        <td>${product.description}</td>
        <td>${product.price} ريال</td>
        <td>${product.author}</td>
        <td>${product.Page_Num}</td>
        <td>${product.Langue}</td>
        <td>${product.ISBN}</td>
        <td class="action-buttons">
          <button class="btn btn-sm btn-success" onclick="editProduct(${product.id})">
            <span data-feather="edit-2"></span> تعديل
          </button>
          <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
            <span data-feather="trash-2"></span> حذف
          </button>
        </td>
      </tr>
    `
    )
    .join('');
  feather.replace();
}

// Fetch a single product by ID
async function fetchProductById(productId) {
  try {
    const response = await fetch(`/Dashbord_Admin/Product/${productId}`);
    if (!response.ok) {
      throw new Error('Failed to fetch product details');
    }
    return await response.json();
  } catch (error) {
    console.error('Error fetching product:', error);
    alert('حدث خطأ أثناء جلب تفاصيل المنتج. يرجى المحاولة مرة أخرى.');
    return null;
  }
}

// Edit product
async function editProduct(productId) {
  const product = await fetchProductById(productId);
  if (!product) {
    return;
  }

  // Populate form fields
  document.getElementById('productId').value = product.id;
  document.getElementById('editProductName').value = product.title;
  document.getElementById('editAuthor').value = product.author;
  document.getElementById('editProductDescription').value = product.description;
  document.getElementById('editProductPrice').value = product.price;
  document.getElementById('editProductPageNum').value = product.Page_Num;
  document.getElementById('editProductLangue').value = product.Langue;
  document.getElementById('editProductPublishingHouse').value = product.Publishing_House;
  document.getElementById('editProductISBN').value = product.ISBN;

  // Show the modal
  const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
  modal.show();
}

// Save product (update)
async function saveProduct() {
  const productId = document.getElementById('productId').value;
  const updatedProduct = {
    title: document.getElementById('editProductName').value,
    author: document.getElementById('editAuthor').value,
    description: document.getElementById('editProductDescription').value,
    price: parseFloat(document.getElementById('editProductPrice').value),
    Page_Num: document.getElementById('editProductPageNum').value,
    Langue: document.getElementById('editProductLangue').value,
    Publishing_House: document.getElementById('editProductPublishingHouse').value,
    ISBN: document.getElementById('editProductISBN').value,
  };

  try {
    const response = await fetch(`/Dashbord_Admin/Product/${productId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify(updatedProduct),
    });

    if (response.ok) {
      alert('تم تحديث المنتج بنجاح!');
      renderProducts(); // Refresh the product list
      bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
    } else {
      const errorData = await response.json();
      alert(`فشل تحديث المنتج: ${errorData.message || 'خطأ غير معروف'}`);
    }
  } catch (error) {
  console.error('Error updating product:', error);
  console.log('Response:', await response.json()); // Log the response
  alert('حدث خطأ أثناء تحديث المنتج.');
}
}

// Delete product
async function deleteProduct(productId) {
  if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
    try {
      const response = await fetch(`/Dashbord_Admin/Product/${productId}`, {
        method: 'DELETE',
      });

      if (response.ok) {
        alert('تم حذف المنتج بنجاح!');
        renderProducts(); // Refresh the product list
      } else {
        const errorData = await response.json();
        alert(`فشل حذف المنتج: ${errorData.message || 'خطأ غير معروف'}`);
      }
    } catch (error) {
      console.error('Error deleting product:', error);
      alert('حدث خطأ أثناء حذف المنتج.');
    }
  }
}

// Initialize
document.addEventListener('DOMContentLoaded', function () {
  feather.replace();
  renderProducts();
  setupSearch();
});