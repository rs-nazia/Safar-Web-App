document.addEventListener('DOMContentLoaded', () => {
    const filterLocation = document.getElementById('filter-location');
    const filterDuration = document.getElementById('filter-duration');
    const filterPrice = document.getElementById('filter-price');
    const priceDisplay = document.getElementById('price-display');
    const toursGrid = document.getElementById('tours-grid');
    const resultsCount = document.getElementById('results-count');

    const fallbackImages = [
        'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=500&q=60',
        'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=500&q=60',
        'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=500&q=60'
    ];

    function parseDuration(desc) {
        // Regex search for "X-day" in description
        const match = desc.match(/(\d+)-day/i);
        if (match) return parseInt(match[1]);
        // Fallback default duration
        return 7;
    }

    function fetchTours() {
        const location = filterLocation.value;
        const price = filterPrice.value;
        const durationFilter = filterDuration.value;

        const params = new URLSearchParams({
            type: 'tour',
            location: location,
            price: price
        });

        toursGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px;"><p>Loading Tours...</p></div>';

        fetch(`../api/filter_listings.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    let tours = data.data;

                    // Client side duration filtering
                    if (durationFilter !== 'all') {
                        tours = tours.filter(t => {
                            const days = parseDuration(t.description);
                            if (durationFilter === 'short') return days <= 5;
                            if (durationFilter === 'medium') return days > 5 && days <= 10;
                            if (durationFilter === 'long') return days > 10;
                            return true;
                        });
                    }

                    renderTours(tours);
                } else {
                    toursGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px; color: #ef4444;"><p>Failed to load tours.</p></div>';
                }
            })
            .catch(() => {
                toursGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px; color: #ef4444;"><p>Connection error.</p></div>';
            });
    }

    function renderTours(tours) {
        resultsCount.textContent = `${tours.length} Guided Tours`;
        toursGrid.innerHTML = '';

        if (tours.length === 0) {
            toursGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px; color: var(--text-muted);"><p>No tour packages found matching your criteria.</p></div>';
            return;
        }

        tours.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'card fade-in';
            card.style.animationDelay = `${index * 0.05}s`;

            const imgUrl = item.image_url || fallbackImages[index % fallbackImages.length];
            const days = parseDuration(item.description);
            const isFav = parseInt(item.is_favorited) === 1;
            const heartClass = isFav ? 'fas fa-heart' : 'far fa-heart';
            const heartColor = isFav ? '#ef4444' : '#64748b';
            
            card.innerHTML = `
                <div class="card-img" style="background-image: url('${imgUrl}'); position: relative;">
                    <button type="button" class="fav-heart-btn" data-id="${item.id}" style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 50%; background: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.12); color: ${heartColor}; font-size: 0.95rem; z-index: 5; transition: transform 0.2s ease;" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
                        <i class="${heartClass}"></i>
                    </button>
                    <span class="card-badge" style="background: #e0f2fe; color: #0369a1;"><i class="far fa-clock"></i> ${days} Days</span>
                </div>
                <div class="card-body">
                    <div class="card-meta">
                        <span><i class="fas fa-map-marker-alt"></i> ${item.location}</span>
                        <span>By ${item.company_name}</span>
                    </div>
                    <h3 class="card-title">${item.title}</h3>
                    <p class="card-price">$${item.price_formatted}</p>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px; flex-grow: 1;">
                        ${item.description.substring(0, 120)}...
                    </p>
                    <div style="display: flex; gap: 10px;">
                        <a href="package-details.php?id=${item.id}" class="btn btn-outline" style="flex: 1;">Details</a>
                        <a href="package-details.php?id=${item.id}" class="btn" style="flex: 1;">Book Now</a>
                    </div>
                </div>
            `;
            toursGrid.appendChild(card);
        });

        // Delegate favorite button click handler
        if (!toursGrid.dataset.listenerBound) {
            toursGrid.addEventListener('click', (e) => {
                const btn = e.target.closest('.fav-heart-btn');
                if (!btn) return;

                e.preventDefault();
                e.stopPropagation();

                const pkgId = btn.getAttribute('data-id');
                const icon = btn.querySelector('i');

                const formData = new FormData();
                formData.append('package_id', pkgId);

                fetch('../api/toggle_favorite.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => {
                    if (res.status === 401) {
                        alert('Please log in to add favorites.');
                        window.location.href = 'login.php';
                        return;
                    }
                    return res.json();
                })
                .then(data => {
                    if (data && data.status === 'success') {
                        if (data.action === 'added') {
                            icon.className = 'fas fa-heart';
                            btn.style.color = '#ef4444';
                        } else {
                            icon.className = 'far fa-heart';
                            btn.style.color = '#64748b';
                        }
                    }
                })
                .catch(err => console.error(err));
            });
            toursGrid.dataset.listenerBound = 'true';
        }

        setTimeout(() => {
            document.querySelectorAll('#tours-grid .card.fade-in').forEach(el => el.classList.add('visible'));
        }, 50);
    }

    // Listeners
    filterLocation.addEventListener('input', fetchTours);
    filterDuration.addEventListener('change', fetchTours);
    filterPrice.addEventListener('input', (e) => {
        priceDisplay.textContent = `$${e.target.value}`;
    });
    filterPrice.addEventListener('change', fetchTours);

    // Read URL query params from homepage tab search
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) {
        filterLocation.value = urlParams.get('search');
    }
    if (urlParams.has('duration')) {
        filterDuration.value = urlParams.get('duration');
    }

    fetchTours();
});
