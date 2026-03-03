(function() {
    var badge    = document.getElementById('notifBadge');
    var list     = document.getElementById('notifList');
    var menu     = document.getElementById('notifMenu');
    var toggle   = document.getElementById('notifToggle');
    var markAll  = document.getElementById('notifMarkAll');
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function loadNotifications() {
        fetch('/notifications/recent')
            .then(r => r.json())
            .then(data => {
                var items = data.notifications || [];
                var unread = items.filter(n => !n.read_at).length;

                if (unread > 0) {
                    badge.textContent = unread > 9 ? '9+' : unread;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }

                if (items.length === 0) {
                    list.innerHTML = '<div class="notif-empty">لا توجد إشعارات</div>';
                    return;
                }

                list.innerHTML = items.map(n => `
                    <div class="notif-item ${n.read_at ? '' : 'unread'}" data-id="${n.id}">
                        <div class="notif-icon"><i class="fas fa-book-open"></i></div>
                        <div class="notif-body">
                            <div class="notif-title">${n.title}</div>
                            <div class="notif-text">${n.body}</div>
                            <div class="notif-time">${timeAgo(n.created_at)}</div>
                        </div>
                    </div>
                `).join('');

                list.querySelectorAll('.notif-item.unread').forEach(function(el) {
                    el.addEventListener('click', function() {
                        var id = el.dataset.id;
                        fetch('/notifications/' + id + '/read', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
                        });
                        el.classList.remove('unread');
                        var url = (data.notifications.find(n => n.id == id) || {}).url;
                        if (url) window.location.href = url;
                        updateBadge();
                    });
                });
            });
    }

    function updateBadge() {
        var unreads = list.querySelectorAll('.notif-item.unread').length;
        if (unreads > 0) {
            badge.textContent = unreads > 9 ? '9+' : unreads;
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }
    }

    function timeAgo(dateStr) {
        var diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60)   return 'الآن';
        if (diff < 3600) return Math.floor(diff/60) + ' دقيقة';
        if (diff < 86400) return Math.floor(diff/3600) + ' ساعة';
        return Math.floor(diff/86400) + ' يوم';
    }

    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.classList.toggle('show');
        if (menu.classList.contains('show')) loadNotifications();
    });

    document.addEventListener('click', function(e) {
        if (!menu.contains(e.target) && e.target !== toggle) {
            menu.classList.remove('show');
        }
    });

    markAll.addEventListener('click', function() {
        fetch('/notifications/read-all', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
        }).then(() => {
            list.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
            badge.style.display = 'none';
        });
    });

    // Poll unread count every 60 seconds
    function pollCount() {
        fetch('/notifications/unread-count')
            .then(r => r.json())
            .then(data => {
                if (data.count > 0) {
                    badge.textContent = data.count > 9 ? '9+' : data.count;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            });
    }

    pollCount();
    setInterval(pollCount, 60000);
})();
