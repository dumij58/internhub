function toggleFilters() {
    const filters = document.getElementById('advancedFilters');
    const isVisible = filters.style.display !== 'none';
    filters.style.display = isVisible ? 'none' : 'grid';
}

// Show filters if any are active
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hasFilters = urlParams.has('category') || urlParams.has('location') || 
                      urlParams.has('experience') || urlParams.has('remote') || 
                      urlParams.has('salary_min');
    
    if (hasFilters) {
        document.getElementById('advancedFilters').style.display = 'grid';
    }
});