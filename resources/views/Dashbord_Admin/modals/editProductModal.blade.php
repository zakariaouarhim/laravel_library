
<!-- resources/views/Dashbord_Admin/Modals/editProductModal.blade.php -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title" id="editProductModalLabel">تعديل المنتج</h5>
        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
      </div>

      <div class="modal-body">
        <form id="editProductForm">
          <input type="hidden" id="productId" name="productId" />
          <meta name="csrf-token" content="{{ csrf_token() }}">

          <!-- Product Name -->
          <div class="mb-3">
            <label for="editProductName" class="form-label">اسم المنتج</label>
            <input 
              type="text" 
              class="form-control" 
              id="editProductName" 
              required
              aria-required="true"
            >
          </div>

          <!-- Author -->
          <div class="mb-3">
            <label for="editAuthor" class="form-label">المؤلف</label>
            <input 
              type="text" 
              class="form-control" 
              id="editAuthor" 
              required
              aria-required="true"
            >
          </div>

          <!-- Description -->
          <div class="mb-3">
            <label for="editProductDescription" class="form-label">الوصف</label>
            <textarea 
              class="form-control" 
              id="editProductDescription" 
              rows="3" 
              required
              aria-required="true"
            ></textarea>
          </div>

          <!-- Price -->
          <div class="mb-3">
            <label for="editProductPrice" class="form-label">السعر</label>
            <input 
              type="number" 
              class="form-control" 
              id="editProductPrice" 
              step="0.01" 
              required
              aria-required="true"
            >
          </div>

          <!-- Number of Pages -->
          <div class="mb-3">
            <label for="editProductPageNum" class="form-label">عدد الصفحات</label>
            <input 
              type="number" 
              class="form-control" 
              id="editProductPageNum" 
              step="1" 
              required
              aria-required="true"
            >
          </div>

          <!-- Language -->
          <div class="mb-3">
            <label for="editProductLanguage" class="form-label">اللغة</label>
            <input 
              type="text" 
              class="form-control" 
              id="editProductLanguage" 
              required
              aria-required="true"
            >
          </div>

          <!-- Publishing House -->
          <div class="mb-3">
            <label for="editProductPublisher" class="form-label">دار النشر</label>
            <input 
              type="text" 
              class="form-control" 
              id="editProductPublisher" 
              required
              aria-required="true"
            >
          </div>

          <!-- ISBN -->
          <div class="mb-3">
            <label for="editProductISBN" class="form-label">ISBN</label>
            <input 
              type="text" 
              class="form-control" 
              id="editProductISBN" 
              required
              aria-required="true"
            >
          </div>

          <!-- Product Image -->
          <div class="mb-3">
            <label for="editProductImage" class="form-label">صورة المنتج</label>
            <input 
              type="file" 
              class="form-control" 
              id="editProductImage" 
              name="editProductImage" 
              accept="image/*"
            >
            <small class="form-text text-muted">ترك الحقل فارغاً للاحتفاظ بالصورة الحالية</small>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
        <button type="button" class="btn btn-primary" onclick="saveProduct()">حفظ التعديلات</button>
      </div>

    </div>
  </div>
</div>