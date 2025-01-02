
      // Sample Orders Data
      const orders = [
        { id: 1, client: 'أحمد محمد', date: '2024-12-10', amount: '150.00', status: 'قيد التنفيذ' },
        { id: 2, client: 'سارة خالد', date: '2024-12-11', amount: '200.00', status: 'مكتمل' },
        { id: 3, client: 'خالد إبراهيم', date: '2024-12-12', amount: '300.00', status: 'ملغي' },
        { id: 4, client: 'نورا علي', date: '2024-12-13', amount: '400.00', status: 'قيد التنفيذ' },
        // Add more sample data here
      ];

      let currentPage = 1;
      const rowsPerPage = 5;

      // Render Orders
      function renderOrders() {
        const tableBody = document.getElementById('ordersTableBody');
        tableBody.innerHTML = '';

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const visibleOrders = orders.slice(start, end);

        visibleOrders.forEach(order => {
          const row = `
            <tr>
              <td>${order.id}</td>
              <td>${order.client}</td>
              <td>${order.date}</td>
              <td>${order.amount} ر.س</td>
              <td><span class="badge bg-${getStatusClass(order.status)}">${order.status}</span></td>
              <td><button class="btn btn-sm btn-primary">عرض التفاصيل</button></td>
            </tr>
          `;
          tableBody.innerHTML += row;
        });
      }

      // Get Status Class for Badge
      function getStatusClass(status) {
        switch (status) {
          case 'قيد التنفيذ': return 'warning';
          case 'مكتمل': return 'success';
          case 'ملغي': return 'danger';
          default: return 'secondary';
        }
      }

      // Pagination Handlers
      function prevPage() {
        if (currentPage > 1) {
          currentPage--;
          renderOrders();
        }
      }

      function nextPage() {
        if (currentPage < Math.ceil(orders.length / rowsPerPage)) {
          currentPage++;
          renderOrders();
        }
      }

      // Filter Orders
      function applyFilters() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;

        const filteredOrders = orders.filter(order => {
          const matchesSearch = order.client.toLowerCase().includes(searchInput);
          const matchesStatus = statusFilter ? order.status === statusFilter : true;
          return matchesSearch && matchesStatus;
        });

        renderFilteredOrders(filteredOrders);
      }

      // Render Filtered Orders
      function renderFilteredOrders(filteredOrders) {
        const tableBody = document.getElementById('ordersTableBody');
        tableBody.innerHTML = '';

        filteredOrders.forEach(order => {
          const row = `
            <tr>
              <td>${order.id}</td>
              <td>${order.client}</td>
              <td>${order.date}</td>
              <td>${order.amount} ر.س</td>
              <td><span class="badge bg-${getStatusClass(order.status)}">${order.status}</span></td>
              <td><button class="btn btn-sm btn-primary">عرض التفاصيل</button></td>
            </tr>
          `;
          tableBody.innerHTML += row;
        });
      }

      // Initial Render
      renderOrders();
    