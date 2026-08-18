function scrollGrid(gridId, direction) {
    var grid = document.getElementById(gridId);
    if (!grid) return;
    var card = grid.querySelector('.hp-card-wrap') || grid.querySelector('.hp-cat-pill');
    var scrollAmount = card ? card.offsetWidth + 16 : 300;
    grid.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
}

function checkAllSliders() {
    document.querySelectorAll('.hp-grid, .hp-cats-bar__inner').forEach(function(grid) {
        var container = grid.closest('.hp-slider-container');
        if (!container) return;

        var leftArrow = container.querySelector('.hp-slider-arrow--left');
        var rightArrow = container.querySelector('.hp-slider-arrow--right');
        if (!leftArrow || !rightArrow) return;

        var hasOverflow = grid.scrollWidth > grid.clientWidth;
        if (hasOverflow) {
            leftArrow.classList.add('is-visible');
            rightArrow.classList.add('is-visible');
            if (grid.classList.contains('hp-cats-bar__inner')) {
                grid.style.justifyContent = 'flex-start';
            }
        } else {
            leftArrow.classList.remove('is-visible');
            rightArrow.classList.remove('is-visible');
            if (grid.classList.contains('hp-cats-bar__inner')) {
                grid.style.justifyContent = 'center';
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    checkAllSliders();
    window.addEventListener('load', checkAllSliders);
    window.addEventListener('resize', checkAllSliders);
});