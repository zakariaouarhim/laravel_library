
function toggleChildCategories(event, categoryId) {
            event.preventDefault();
            const childContainer = document.getElementById(`child-categories-${categoryId}`);
            const plusIcon = event.currentTarget;
            
            if (childContainer.style.display === 'none') {
                childContainer.style.display = 'block';
                plusIcon.classList.add('rotate');
                plusIcon.innerHTML = '×';
            } else {
                childContainer.style.display = 'none';
                plusIcon.classList.remove('rotate');
                plusIcon.innerHTML = '+';
            }
        }