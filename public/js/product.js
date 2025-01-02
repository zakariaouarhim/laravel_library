
      // Sample products data
      let products = [
        { id: 1, name: "كتاب تعلم البرمجة", description: "كتاب شامل لتعلم البرمجة من البداية", price: 50.00, image: "https://via.placeholder.com/50" },
        { id: 2, name: "أساسيات التصميم", description: "دليل شامل لتعلم تصميم الجرافيك", price: 75.00, image: "https://via.placeholder.com/50" }
      ];

      // Initialize
      document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
        renderProducts();
        setupSearch();
      });

      // Render products
      function renderProducts() {
        const tbody = document.getElementById('productsTableBody');
        tbody.innerHTML = products.map(product => `
          <tr>
            <td>${product.id}</td>
            <td><img src="${product.image}" alt="${product.name}"></td>
            <td>${product.name}</td>
            <td>${product.description}</td>
            <td>${product.price.toFixed(2)} ريال</td>
            <td class="action-buttons">
              <button class="btn btn-sm btn-success" onclick="editProduct(${product.id})">
                <span data-feather="edit-2"></span> تعديل
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
                <span data-feather="trash-2"></span> حذف
              </button>
            </td>
          </tr>
        `).join('');
        feather.replace();
      }

      // Setup search functionality
      function setupSearch() {
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function() {
          const searchTerm = this.value.toLowerCase();
          const filteredProducts = products.filter(product => 
            product.name.toLowerCase().includes(searchTerm) ||
            product.description.toLowerCase().includes(searchTerm)
          );
          renderFilteredProducts(filteredProducts);
        });
      }

      function renderFilteredProducts(filteredProducts) {
        const tbody = document.getElementById('productsTableBody');
        tbody.innerHTML = filteredProducts.map(product => `
          <tr>
            <td>${product.id}</td>
            <td><img src="${product.image}" alt="${product.name}"></td>
            <td>${product.name}</td>
            <td>${product.description}</td>
            <td>${product.price.toFixed(2)} ريال</td>
            <td class="action-buttons">
              <button class="btn btn-sm btn-success" onclick="editProduct(${product.id})">
                <span data-feather="edit-2"></span> تعديل
              </button>
              <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
                <span data-feather="trash-2"></span> حذف
              </button>
            </td>
          </tr>
        `).join('');
        feather.replace();
      }

      function saveProduct() {
        const form = document.getElementById('productForm');
        if (form.checkValidity()) {
          const newProduct = {
            id: products.length + 1,
            name: document.getElementById('productName').value,
            description: document.getElementById('productDescription').value,
            price: parseFloat(document.getElementById('productPrice').value),
            image: "https://via.placeholder.com/50"
          };
          products.push(newProduct);
          renderProducts();
          bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
          form.reset();
        } else {
          form.reportValidity();
        }
      }

      function deleteProduct(id) {
        if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
          products = products.filter(product => product.id !== id);
          renderProducts();
        }
      }

      function editProduct(id) {
        const product = products.find(p => p.id === id);
        if (product) {
          document.getElementById('productName').value = product.name;
          document.getElementById('productDescription').value = product.description;
          document.getElementById('productPrice').value = product.price;
          bootstrap.Modal.show(document.getElementById('addProductModal'));
        }
      }
    