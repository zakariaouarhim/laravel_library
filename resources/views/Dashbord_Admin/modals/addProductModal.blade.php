<!-- resources/views/Dashbord_Admin/Modals/addProductModal.blade.php -->
<form id="addProductForm" method="POST" action="{{ route('product.add') }}" enctype="multipart/form-data">
  @csrf
  <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        
        <div class="modal-header">
          <h5 class="modal-title" id="addProductModalLabel">إضافة منتج جديد</h5>
          <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
        </div>

        <div class="modal-body">
          <!-- Product Name -->
          <div class="mb-3">
            <label for="productName" class="form-label">اسم المنتج</label>
            <input 
              type="text" 
              class="form-control" 
              id="productName" 
              name="productName" 
              required
              aria-required="true"
            >
          </div>

          <!-- Author -->
          <div class="mb-3">
            <label for="productAuthor" class="form-label">الكاتب</label>
            <input 
              type="text" 
              class="form-control" 
              id="productAuthor" 
              name="productauthor" 
              required
              aria-required="true"
            >
          </div>

          <!-- Description -->
          <div class="mb-3">
            <label for="productDescription" class="form-label">الوصف</label>
            <textarea 
              class="form-control" 
              id="productDescription" 
              name="productDescription" 
              rows="3" 
              required
              aria-required="true"
            ></textarea>
          </div>

          <!-- Price -->
          <div class="mb-3">
            <label for="productPrice" class="form-label">السعر</label>
            <input 
              type="number" 
              class="form-control" 
              id="productPrice" 
              name="productPrice" 
              step="0.01" 
              required
              aria-required="true"
            >
          </div>

          <!-- Number of Pages -->
          <div class="mb-3">
            <label for="productNumPages" class="form-label">عدد الصفحات</label>
            <input 
              type="number" 
              class="form-control" 
              id="productNumPages" 
              name="productNumPages" 
              step="1" 
              required
              aria-required="true"
            >
          </div>

          <!-- Language -->
          <div class="mb-3">
            <label for="productLanguage" class="form-label">اللغة</label>
            <input 
              type="text" 
              class="form-control" 
              id="productLanguage" 
              name="productLanguage" 
              required
              aria-required="true"
            >
          </div>

          <!-- Publishing House -->
          <div class="mb-3">
            <label for="productPublisher" class="form-label">دار النشر</label>
            <input 
              type="text" 
              class="form-control" 
              id="productPublisher" 
              name="ProductPublishingHouse" 
              required
              aria-required="true"
            >
          </div>

          <!-- ISBN -->
          <div class="mb-3">
            <label for="productIsbn" class="form-label">ISBN</label>
            <input 
              type="text" 
              class="form-control" 
              id="productIsbn" 
              name="productIsbn" 
              required
              aria-required="true"
            >
          </div>

          <!-- Category -->
          <div class="mb-3">
            <label for="productCategory" class="form-label">الفئة</label>
            <select 
              class="form-select" 
              id="productCategory" 
              name="Productcategorie" 
              required
              aria-required="true"
            >
              <option value="">-- اختر فئة --</option>
              <option value="1">روايات</option>
              <option value="2">كتب دينية</option>
              <option value="3">التنمية البشرية وتنمية وتطوير الذات</option>
              <option value="4">قصص الأطفال</option>
              <option value="5">فلسفة</option>
              <option value="6">كتب الفكر</option>
              <option value="7">علم النفس</option>
              <option value="8">علم الاجتماع</option>
            </select>
          </div>

          <!-- Product Image -->
          <div class="mb-3">
            <label for="productImage" class="form-label">صورة المنتج</label>
            <input 
              type="file" 
              class="form-control" 
              id="productImage" 
              name="productImage" 
              accept="image/*" 
              required
              aria-required="true"
            >
            <small class="form-text text-muted">الحد الأقصى للحجم: 5MB</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
          <button type="submit" class="btn btn-primary">حفظ المنتج</button>
        </div>

      </div>
    </div>
  </div>
</form>

