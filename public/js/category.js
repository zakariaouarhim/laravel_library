function toggleChildCategories(event, categoryId) {
    event.stopPropagation(); // Prevent the click from propagating to the parent link

    const childContainer = document.getElementById(`child-categories-${categoryId}`);
    const plusIcon = event.target.closest('.plus-icon');

    if (childContainer.style.display === 'none') {
        childContainer.style.display = 'block';
        plusIcon.textContent = '-';
    } else {
        childContainer.style.display = 'none';
        plusIcon.textContent = '+';
    }
}