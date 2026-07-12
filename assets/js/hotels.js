document.addEventListener('DOMContentLoaded', () => {
    const filterLocation = document.getElementById('filter-location');
    const filterPrice = document.getElementById('filter-price');
    const priceDisplay = document.getElementById('price-display');
    const hotelsGrid = document.getElementById('hotels-grid');
    const resultsCount = document.getElementById('results-count');
    const amenityCheckboxes = document.querySelectorAll('.filter-amenity');

    const fallbackImages = [
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=500&q=60',
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=500&q=60',
        'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=500&q=60'
    ];

    function fetchHotels() {
        const location = filterLocation.value;
        const price = filterPrice.value;

        // Get selected amenities
        const selectedAmenities = [];
        amenityCheckboxes.forEach(cb => {
            if (cb.checked) {
                selectedAmenities.push(cb.value);
            }
        });

        const params = new URLSearchParams({
            type: 'hotel',
            location: location,
            price: price
        });

        hotelsGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px;"><p>Loading Hotels...</p></div>';

        fetch(`../api/filter_listings.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    let hotels = data.data;

                    // Client side amenities filtering
                    if (selectedAmenities.length > 0) {
                        hotels = hotels.filter(h => {
                            const desc = h.description.toLowerCase();
                            // Match all selected amenities in description text
                            return selectedAmenities.every(a => desc.includes(a));
                        });
                    }

                    renderHotels(hotels);
                } else {
                    hotelsGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px; color: #ef4444;"><p>Failed to load hotels.</p></div>';
                }
            })
            .catch(() => {
                hotelsGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px; color: #ef4444;"><p>Connection error.</p></div>';
            });
    }

    function renderHotels(hotels) {
        resultsCount.textContent = `${hotels.length} Stays Found`;
        hotelsGrid.innerHTML = '';

        if (hotels.length === 0) {
            hotelsGrid.innerHTML = '<div style="text-align: center; grid-column: 1 / -1; padding: 40px; color: var(--text-muted);"><p>No hotels found matching your criteria.</p></div>';
            return;
        }

        hotels.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'card fade-in';
            card.style.animationDelay = `${index * 0.05}s`;

            const imgUrl = item.image_url || fallbackImages[index % fallbackImages.length];
            const isFav = parseInt(item.is_favorited) === 1;
            const heartClass = isFav ? 'fas fa-heart' : 'far fa-heart';
            const heartColor = isFav ? '#ef4444' : '#64748b';
            
            card.innerHTML = `
                <div class="card-img" style="background-image: url('${imgUrl}'); position: relative;">
                    <button type="button" class="fav-heart-btn" data-id="${item.id}" style="position: absolute; top: 12px; right: 12px; width: 34px; height: 34px; border-radius: 50%; background: #ffffff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.12); color: ${heartColor}; font-size: 0.95rem; z-index: 5; transition: transform 0.2s ease;" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
                        <i class="${heartClass}"></i>
                    </button>
                    <span class="card-badge" style="background: var(--bg-light); color: var(--primary);"><i class="fas fa-star" style="color: #fbbf24;"></i> Premium Stay</span>
                </div>
                <div class="card-body">
                    <div class="card-meta">
                        <span><i class="fas fa-map-marker-alt"></i> ${item.location}</span>
                        <span>By ${item.company_name}</span>
                    </div>
                    <h3 class="card-title">${item.title}</h3>
                    <p class="card-price">$${item.price_formatted} <span style="font-size: 0.85rem; font-weight: normal; color: var(--text-muted);">/ night</span></p>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px; flex-grow: 1;">
                        ${item.description.substring(0, 120)}...
                    </p>
                    <div style="display: flex; gap: 10px;">
                        <a href="package-details.php?id=${item.id}" class="btn btn-outline" style="flex: 1;">Details</a>
                        <a href="package-details.php?id=${item.id}" class="btn" style="flex: 1;">Book Now</a>
                    </div>
                </div>
            `;
            hotelsGrid.appendChild(card);
        });

        // Delegate favorite button click handler
        if (!hotelsGrid.dataset.listenerBound) {
            hotelsGrid.addEventListener('click', (e) => {
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
            hotelsGrid.dataset.listenerBound = 'true';
        }

        setTimeout(() => {
            document.querySelectorAll('#hotels-grid .card.fade-in').forEach(el => el.classList.add('visible'));
        }, 50);
    }

    // Listeners
    filterLocation.addEventListener('input', fetchHotels);
    filterPrice.addEventListener('input', (e) => {
        priceDisplay.textContent = `$${e.target.value}`;
    });
    filterPrice.addEventListener('change', fetchHotels);
    
    amenityCheckboxes.forEach(cb => {
        cb.addEventListener('change', fetchHotels);
    });

    // Read URL query params from homepage tab search
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) {
        filterLocation.value = urlParams.get('search');
    }

    fetchHotels();
});
